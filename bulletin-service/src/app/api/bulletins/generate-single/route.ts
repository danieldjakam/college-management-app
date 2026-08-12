import { NextRequest } from "next/server";
import { createLaravelClient, extractToken } from "@/lib/laravel-client";
import { htmlToPdf } from "@/lib/pdf-generator";
import { renderBulletinTrimestre } from "@/templates/bulletin-trimestre";
import { StudentBulletinData, SchoolInfo } from "@/lib/types";

export const maxDuration = 60;
export const dynamic = "force-dynamic";

/**
 * POST /api/bulletins/generate-single
 *
 * Generates a single student bulletin PDF.
 *
 * Body: {
 *   student_id: number,
 *   period_type: "sequence" | "trimester" | "annual",
 *   period_identifier: string,
 *   template?: string
 * }
 */
export async function POST(request: NextRequest) {
  try {
    const token = extractToken(request);
    if (!token) {
      return Response.json(
        { success: false, message: "Non autorisé" },
        { status: 401 }
      );
    }

    const body = await request.json();
    const { student_id, period_type, period_identifier } = body;

    if (!student_id || !period_type || !period_identifier) {
      return Response.json(
        { success: false, message: "Paramètres manquants" },
        { status: 400 }
      );
    }

    // Get bulletin data from Laravel
    const laravel = createLaravelClient(token);
    const response = await laravel.post("/bulletins/student-data", {
      student_id,
      period_type,
      period_identifier,
    });

    if (!response.data?.success) {
      return Response.json(
        { success: false, message: response.data?.message || "Erreur" },
        { status: 400 }
      );
    }

    const {
      student_data,
      school_info,
    }: {
      student_data: StudentBulletinData;
      school_info: SchoolInfo;
    } = response.data.data;

    // Render HTML
    const html = renderBulletinTrimestre(student_data, school_info);

    // Convert to PDF
    const pdfBuffer = await htmlToPdf(html, {
      format: "A4",
      landscape: false,
      margin: { top: "0mm", right: "0mm", bottom: "0mm", left: "0mm" },
    });

    const studentName = `${student_data.student.last_name}_${student_data.student.first_name}`;

    return new Response(new Uint8Array(pdfBuffer), {
      status: 200,
      headers: {
        "Content-Type": "application/pdf",
        "Content-Disposition": `attachment; filename="bulletin_${period_identifier}_${studentName}.pdf"`,
        "Content-Length": pdfBuffer.length.toString(),
      },
    });
  } catch (error: unknown) {
    const message =
      error instanceof Error ? error.message : "Erreur interne";
    console.error("Erreur génération bulletin individuel:", message);
    return Response.json({ success: false, message }, { status: 500 });
  }
}
