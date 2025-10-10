const isDevelopment =
  process.env.NODE_ENV === "development" ||
  window.location.hostname === "localhost" ||
  window.location.hostname === "192.168.1.119";

// Utiliser l'IP locale au lieu de 127.0.0.1 pour permettre l'accès depuis mobile
export const host = isDevelopment
  ? "http://192.168.1.119:8000"
  : "http://admin1.cpb-douala.com";
