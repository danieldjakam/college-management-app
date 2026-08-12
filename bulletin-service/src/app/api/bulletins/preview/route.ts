import { NextRequest } from "next/server";
import { createLaravelClient, extractToken } from "@/lib/laravel-client";
import { renderBulletinTrimestre } from "@/templates/bulletin-trimestre";
import { StudentBulletinData, SchoolInfo } from "@/lib/types";

export const dynamic = "force-dynamic";

/**
 * POST /api/bulletins/preview
 *
 * Returns HTML preview of a student bulletin (no PDF generation).
 * Useful for quick preview in browser.
 *
 * Body: {
 *   student_id: number,
 *   period_type: "sequence" | "trimester" | "annual",
 *   period_identifier: string
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

    const html = renderBulletinTrimestre(student_data, school_info);

    return new Response(html, {
      status: 200,
      headers: {
        "Content-Type": "text/html; charset=utf-8",
      },
    });
  } catch (error: unknown) {
    const message =
      error instanceof Error ? error.message : "Erreur interne";
    return Response.json({ success: false, message }, { status: 500 });
  }
}
