/**
 * Service de gestion des notifications audio
 * Pour le scanner de présence
 */

class AudioService {
    constructor() {
        this.enabled = true;
        this.volume = 0.7;
        
        // Sons pré-générés (Web Audio API)
        this.audioContext = null;
        this.sounds = {};
        
        this.initAudioContext();
        this.generateSounds();
    }

    /**
     * Initialiser le contexte audio
     */
    initAudioContext() {
        try {
            // Support cross-browser
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                this.audioContext = new AudioContext();
            }
        } catch (error) {
            console.warn('Audio Context non supporté:', error);
            this.enabled = false;
        }
    }

    /**
     * Générer des sons synthétiques
     */
    generateSounds() {
        if (!this.audioContext) return;

        // Son de succès (bip haut)
        this.sounds.success = this.createTone(800, 0.2, 'sine');
        
        // Son d'erreur (double bip grave)
        this.sounds.error = this.createMultiTone([400, 350], [0.15, 0.15], 'square');
        
        // Son de détection QR (bip court)
        this.sounds.detection = this.createTone(600, 0.1, 'sine');
        
        // Son de blocage géographique (triple bip décroissant)
        this.sounds.blocked = this.createMultiTone([500, 400, 300], [0.1, 0.1, 0.2], 'sawtooth');
        
        // Son d'entrée (bip montant)
        this.sounds.entry = this.createTone(700, 0.3, 'sine', 'up');
        
        // Son de sortie (bip descendant)
        this.sounds.exit = this.createTone(500, 0.3, 'sine', 'down');
    }

    /**
     * Créer un ton synthétique
     */
    createTone(frequency, duration, type = 'sine', pitch = 'steady') {
        if (!this.audioContext) return null;

        return () => {
            try {
                const oscillator = this.audioContext.createOscillator();
                const gainNode = this.audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(this.audioContext.destination);

                oscillator.type = type;
                oscillator.frequency.setValueAtTime(frequency, this.audioContext.currentTime);

                // Effet de pitch
                if (pitch === 'up') {
                    oscillator.frequency.exponentialRampToValueAtTime(
                        frequency * 1.5, 
                        this.audioContext.currentTime + duration
                    );
                } else if (pitch === 'down') {
                    oscillator.frequency.exponentialRampToValueAtTime(
                        frequency * 0.7, 
                        this.audioContext.currentTime + duration
                    );
                }

                // Envelope ADSR simple
                gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
                gainNode.gain.linearRampToValueAtTime(this.volume, this.audioContext.currentTime + 0.01);
                gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + duration);

                oscillator.start(this.audioContext.currentTime);
                oscillator.stop(this.audioContext.currentTime + duration);

            } catch (error) {
                console.warn('Erreur lecture son:', error);
            }
        };
    }

    /**
     * Créer un ton multiple (pour erreurs/blocages)
     */
    createMultiTone(frequencies, durations, type = 'sine') {
        if (!this.audioContext) return null;

        return () => {
            let delay = 0;
            
            frequencies.forEach((freq, index) => {
                setTimeout(() => {
                    const sound = this.createTone(freq, durations[index], type);
                    if (sound) sound();
                }, delay * 1000);
                
                delay += durations[index] + 0.05; // Petit gap entre les tons
            });
        };
    }

    /**
     * Jouer un son spécifique
     */
    play(soundName) {
        if (!this.enabled) return;
        
        // Reprendre le contexte audio si suspendu (politique navigateur)
        if (this.audioContext && this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }

        const sound = this.sounds[soundName];
        if (sound && typeof sound === 'function') {
            try {
                sound();
                console.log('🔊 Son joué:', soundName);
            } catch (error) {
                console.warn('Erreur lecture son:', soundName, error);
            }
        } else {
            console.warn('Son non trouvé:', soundName);
        }
    }

    /**
     * Sons spécifiques pour le scanner
     */
    playSuccess() {
        this.play('success');
    }

    playError() {
        this.play('error');
    }

    playDetection() {
        this.play('detection');
    }

    playBlocked() {
        this.play('blocked');
    }

    playEntry() {
        this.play('entry');
    }

    playExit() {
        this.play('exit');
    }

    /**
     * Configuration
     */
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
    }

    setEnabled(enabled) {
        this.enabled = enabled;
    }

    isEnabled() {
        return this.enabled;
    }

    /**
     * Test des sons
     */
    testAllSounds() {
        const sounds = ['detection', 'success', 'entry', 'exit', 'error', 'blocked'];
        
        sounds.forEach((sound, index) => {
            setTimeout(() => {
                console.log(`🧪 Test son: ${sound}`);
                this.play(sound);
            }, index * 1000);
        });
    }
}

// Instance globale
const audioService = new AudioService();

export default audioService;