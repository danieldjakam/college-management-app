export const dynamic = "force-dynamic";

export async function GET() {
  return Response.json({
    status: "ok",
    service: "bulletin-service",
    timestamp: new Date().toISOString(),
    laravel_api: process.env.LARAVEL_API_URL || "http://localhost:8000/api",
  });
}
