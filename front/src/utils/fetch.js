// Configuration dynamique basée sur l'environnement
const isDevelopment = process.env.NODE_ENV === 'development' || window.location.hostname === 'localhost';

export const host = isDevelopment 
    ? "http://127.0.0.1:8000"  // Développement local
    : "http://admin1.cpb-douala.com";  // Production
