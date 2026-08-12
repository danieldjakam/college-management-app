import axios, { AxiosInstance } from "axios";

const LARAVEL_API_URL =
  process.env.LARAVEL_API_URL || "http://localhost:8000/api";

/**
 * Create an axios instance configured to call the Laravel backend
 * Passes through the JWT token from the original request
 */
export function createLaravelClient(token: string): AxiosInstance {
  return axios.create({
    baseURL: LARAVEL_API_URL,
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    timeout: 30000,
  });
}

/**
 * Extract JWT token from request headers
 */
export function extractToken(request: Request): string | null {
  const authHeader = request.headers.get("Authorization");
  if (!authHeader?.startsWith("Bearer ")) return null;
  return authHeader.substring(7);
}
