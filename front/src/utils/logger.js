/**
 * Utilitaire de logging pour contrôler les messages de debug et d'erreur
 */

const isDevelopment = process.env.NODE_ENV === 'development';

// Configuration des niveaux de log
const LOG_LEVELS = {
    ERROR: 0,
    WARN: 1,
    INFO: 2,
    DEBUG: 3
};

// Niveau de log actuel (peut être changé dynamiquement)
let currentLogLevel = isDevelopment ? LOG_LEVELS.DEBUG : LOG_LEVELS.ERROR;

// Messages d'erreur fréquents à ignorer ou réduire
const IGNORED_ERROR_PATTERNS = [
    'fetch', // Erreurs de réseau fetch
    'CORS', // Erreurs CORS
    'net::ERR_FAILED', // Erreurs de réseau génériques
    'Response to preflight request doesn\'t pass access control check',
    'No \'Access-Control-Allow-Origin\' header is present'
];

const NETWORK_ERROR_MESSAGES = [
    'Impossible de se connecter au serveur',
    'Vérifiez votre connexion',
    'Network request failed',
    'fetch'
];

class Logger {
    constructor() {
        this.errorCounts = new Map();
        this.lastErrorTime = new Map();
        this.ERROR_THROTTLE_TIME = 5000; // 5 secondes entre les mêmes erreurs
        this.MAX_SAME_ERROR = 3; // Maximum d'erreurs identiques à afficher
    }

    setLevel(level) {
        currentLogLevel = level;
    }

    shouldIgnoreError(error) {
        const errorString = error.toString().toLowerCase();
        return IGNORED_ERROR_PATTERNS.some(pattern => 
            errorString.includes(pattern.toLowerCase())
        );
    }

    isNetworkError(error) {
        const errorString = error.toString().toLowerCase();
        return NETWORK_ERROR_MESSAGES.some(pattern => 
            errorString.includes(pattern.toLowerCase())
        );
    }

    shouldThrottleError(error) {
        const errorKey = error.toString();
        const now = Date.now();
        
        if (!this.errorCounts.has(errorKey)) {
            this.errorCounts.set(errorKey, 0);
            this.lastErrorTime.set(errorKey, now);
            return false;
        }

        const count = this.errorCounts.get(errorKey);
        const lastTime = this.lastErrorTime.get(errorKey);

        // Reset counter if enough time has passed
        if (now - lastTime > this.ERROR_THROTTLE_TIME) {
            this.errorCounts.set(errorKey, 0);
            this.lastErrorTime.set(errorKey, now);
            return false;
        }

        // Throttle if we've seen this error too many times
        if (count >= this.MAX_SAME_ERROR) {
            return true;
        }

        this.errorCounts.set(errorKey, count + 1);
        return false;
    }

    error(message, ...args) {
        if (currentLogLevel >= LOG_LEVELS.ERROR) {
            // Check if this is a repeated or ignorable error
            if (this.shouldIgnoreError(message) || this.shouldThrottleError(message)) {
                return;
            }

            // For network errors, show a simplified message
            if (this.isNetworkError(message)) {
                console.warn('🔌 Problème de connexion réseau (les erreurs détaillées sont masquées)');
                return;
            }

            console.error('❌', message, ...args);
        }
    }

    warn(message, ...args) {
        if (currentLogLevel >= LOG_LEVELS.WARN) {
            console.warn('⚠️', message, ...args);
        }
    }

    info(message, ...args) {
        if (currentLogLevel >= LOG_LEVELS.INFO) {
            console.info('ℹ️', message, ...args);
        }
    }

    debug(message, ...args) {
        if (currentLogLevel >= LOG_LEVELS.DEBUG) {
            console.debug('🐛', message, ...args);
        }
    }

    // Méthode spéciale pour les erreurs d'API
    apiError(error, context = '') {
        if (this.isNetworkError(error)) {
            // Ne pas spam la console avec les erreurs de réseau
            this.debug(`Erreur réseau ${context}:`, error.message);
        } else {
            this.error(`Erreur API ${context}:`, error.message);
        }
    }

    // Méthode pour configurer le logging en mode production
    setProductionMode() {
        currentLogLevel = LOG_LEVELS.WARN;
        // Override console methods to reduce noise
        const originalError = console.error;
        const originalWarn = console.warn;
        
        console.error = (...args) => {
            const message = args.join(' ');
            if (!this.shouldIgnoreError(message)) {
                originalError.apply(console, args);
            }
        };

        console.warn = (...args) => {
            const message = args.join(' ');
            if (!this.shouldIgnoreError(message)) {
                originalWarn.apply(console, args);
            }
        };
    }

    // Méthode pour configurer le logging en mode silencieux
    setSilentMode() {
        currentLogLevel = LOG_LEVELS.ERROR;
        
        // Silence most console outputs
        const noop = () => {};
        console.debug = noop;
        console.info = noop;
        
        // Keep only critical errors
        const originalError = console.error;
        console.error = (...args) => {
            const message = args.join(' ');
            if (!this.shouldIgnoreError(message) && !this.isNetworkError(message)) {
                originalError.apply(console, args);
            }
        };
    }
}

// Instance globale
const logger = new Logger();

// Configuration automatique selon l'environnement
if (!isDevelopment) {
    logger.setProductionMode();
}

export default logger;
export { LOG_LEVELS };