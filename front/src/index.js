import React from 'react';
import ReactDOM from 'react-dom/client';
import './tailwind.css';
import App from './App';
import logger from './utils/logger';

// Configuration des logs selon l'environnement
if (process.env.NODE_ENV === 'production') {
  logger.setProductionMode();
} else {
  // En développement, réduire les logs de réseau répétitifs
  logger.setSilentMode();
}
const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
  // StrictMode temporairement désactivé pour résoudre l'erreur insertBefore
  // <React.StrictMode>
    <App />
  // </React.StrictMode>
);
