const isDevelopment =
  process.env.NODE_ENV === "development" ||
  window.location.hostname === "localhost";

export const host = isDevelopment
  ? "http://127.0.0.1:8000"
  : "http://admin1.cpb-douala.com";
