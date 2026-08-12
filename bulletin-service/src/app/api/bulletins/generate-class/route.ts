import { NextRequest } from "next/server";
import { createLaravelClient, extractToken } from "@/lib/laravel-client";
import { batchHtmlToPdf } from "@/lib/pdf-generator";
import { mergePdfs } from "@/lib/pdf-merger";
import { renderBulletinTrimestre } from "@/templates/bulletin-trimestre";
import { StudentBulletinData, SchoolInfo } from "@/lib/types";

export const maxDuration = 300; // 5 minutes max
export const dynamic = "force-dynamic";

/**
 * POST /api/bulletins/generate-class
 *
 * Generates bulletins for an entire class and returns a single merged PDF.
 * One request → one PDF ready to print.
 *
 * Body: {
 *   class_id: number,
 *   period_type: "sequence" | "trimester" | "annual",
 *   period_identifier: string, // "seq1", "trim1", "annual"
 *   template?: string
 * }
 */
export async function POST(request: NextRequest) {
  try {
    // 1. Extract JWT token
    const token = extractToken(request);
    if (!token) {
      return Response.json(
        { success: false, message: "Non autorisé - Token manquant" },
        { status: 401 }
      );
    }

    // 2. Parse request body
    const body = await request.json();
    const { class_id, period_type, period_identifier } = body;

    if (!class_id || !period_type || !period_identifier) {
      return Response.json(
        {
          success: false,
          message: "Paramètres manquants: class_id, period_type, period_identifier",
        },
        { status: 400 }
      );
    }

    // 3. Call Laravel API to get bulletin data for all students in the class
    const laravel = createLaravelClient(token);

    const response = await laravel.post("/bulletins/class-data", {
      class_id,
      period_type,
      period_identifier,
    });

    if (!response.data?.success) {
      return Response.json(
        {
          success: false,
          message: response.data?.message || "Erreur lors de la récupération des données",
        },
        { status: 400 }
      );
    }

    const {
      students_data,
      school_info,
    }: {
      students_data: StudentBulletinData[];
      school_info: SchoolInfo;
    } = response.data.data;

    if (!students_data || students_data.length === 0) {
      return Response.json(
        { success: false, message: "Aucun élève trouvé pour cette classe" },
        { status: 404 }
      );
    }

    // 4. Render HTML for each student
    const htmlPages = students_data.map((studentData) =>
      renderBulletinTrimestre(studentData, school_info)
    );

    // 5. Convert all HTML to PDF (parallel batches of 5)
    const pdfBuffers = await batchHtmlToPdf(htmlPages, {
      format: "A4",
      landscape: false,
      margin: { top: "0mm", right: "0mm", bottom: "0mm", left: "0mm" },
    });

    // 6. Merge all PDFs into one
    const mergedPdf = await mergePdfs(pdfBuffers);

    // 7. Return the merged PDF
    return new Response(new Uint8Array(mergedPdf), {
      status: 200,
      headers: {
        "Content-Type": "application/pdf",
        "Content-Disposition": `attachment; filename="bulletins_${period_identifier}_class_${class_id}.pdf"`,
        "Content-Length": mergedPdf.length.toString(),
      },
    });
  } catch (error: unknown) {
    const message =
      error instanceof Error ? error.message : "Erreur interne du serveur";
    console.error("Erreur génération bulletin classe:", message);
    return Response.json(
      { success: false, message },
      { status: 500 }
    );
  }
}
