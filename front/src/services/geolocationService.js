/**
 * Service de géolocalisation pour contrôle des zones de scan
 * Version 2.0 - Utilise l'API backend au lieu du localStorage
 */

import { secureApiEndpoints } from '../utils/apiMigration.js';

class GeolocationService {
    constructor() {
        this.lastKnownPosition = null;
        this.watchId = null;
        this.isTracking = false;
        this.zonesCache = null;
        this.cacheExpiry = null;
        
        // Charger les zones depuis l'API au démarrage
        this.loadZoneConfig();
    }

    /**
     * Configuration des zones autorisées pour le scan
     * Chargées depuis l'API backend
     */
    AUTHORIZED_ZONES = {};

    /**
     * Obtenir la position actuelle du dispositif
     */
    async getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('La géolocalisation n\'est pas supportée par ce navigateur'));
                return;
            }

            const options = {
                enableHighAccuracy: true,
                timeout: 30000, // Augmenté à 30 secondes
                maximumAge: 10000 // Cache de 10 secondes
            };

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.lastKnownPosition = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: position.timestamp
                    };
                    resolve(this.lastKnownPosition);
                },
                (error) => {
                    console.error('Erreur géolocalisation:', error);
                    let errorMessage = '';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Permission de géolocalisation refusée. Veuillez autoriser l\'accès à votre position.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Position indisponible. Vérifiez que le GPS est activé.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Délai dépassé pour obtenir la position.';
                            break;
                        default:
                            errorMessage = 'Erreur inconnue lors de la géolocalisation.';
                            break;
                    }
                    reject(new Error(errorMessage));
                },
                options
            );
        });
    }

    /**
     * Calculer la distance entre deux points en mètres (Formule de Haversine)
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3; // Rayon de la Terre en mètres
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

        return R * c; // Distance en mètres
    }

    /**
     * Vérifier si une position est dans une zone autorisée
     */
    isPositionInAuthorizedZone(latitude, longitude) {
        const results = [];
        
        // Vérifier s'il y a des zones configurées
        if (!this.AUTHORIZED_ZONES || Object.keys(this.AUTHORIZED_ZONES).length === 0) {
            console.warn('Aucune zone autorisée configurée');
            return {
                isAuthorized: false,
                zones: [],
                closestZone: null,
                error: 'Aucune zone configurée'
            };
        }
        
        for (const [zoneId, zone] of Object.entries(this.AUTHORIZED_ZONES)) {
            if (!zone || !zone.enabled) continue;
            
            const distance = this.calculateDistance(
                latitude, longitude,
                zone.latitude, zone.longitude
            );
            
            const isInZone = distance <= zone.radius;
            
            results.push({
                zoneId,
                zoneName: zone.name,
                distance: Math.round(distance),
                radius: zone.radius,
                isInZone,
                accuracy: isInZone ? 'IN_ZONE' : 'OUT_OF_ZONE'
            });
        }
        
        // Si aucune zone active trouvée
        if (results.length === 0) {
            return {
                isAuthorized: false,
                zones: [],
                closestZone: null,
                error: 'Aucune zone active trouvée'
            };
        }
        
        // Retourner true si au moins une zone est valide
        const hasValidZone = results.some(result => result.isInZone);
        
        // Trouver la zone la plus proche
        const closestZone = results.reduce((closest, current) => 
            !closest || current.distance < closest.distance ? current : closest, 
            null
        );
        
        return {
            isAuthorized: hasValidZone,
            zones: results,
            closestZone: closestZone
        };
    }

    /**
     * Valider la position pour un scan de présence
     * Utilise l'API backend pour la validation
     */
    async validateScanLocation() {
        try {
            console.log('🎯 Validation géolocalisée démarrée...');
            
            // 1. Obtenir position actuelle
            const position = await this.getCurrentPosition();
            console.log('📍 Position obtenue:', position);
            
            // 2. Valider avec l'API backend
            const response = await secureApiEndpoints.geolocationZones.validatePosition({
                latitude: position.latitude,
                longitude: position.longitude
            });
            
            console.log('🔄 Réponse API validation:', response);
            
            if (response.success) {
                const validation = response.data;
                
                return {
                    success: validation.isAuthorized,
                    position: position,
                    validation: validation,
                    message: validation.isAuthorized 
                        ? `✅ Zone autorisée: ${validation.closestZone?.zoneName || 'Zone détectée'}`
                        : `❌ Hors zone autorisée (${validation.closestZone?.distance || '?'}m de ${validation.closestZone?.zoneName || 'zone la plus proche'})`
                };
            } else {
                throw new Error(response.message || 'Erreur de validation de position');
            }
            
        } catch (error) {
            console.error('❌ Erreur validation géolocalisation:', error);
            
            // En cas d'erreur, essayer la validation locale comme fallback
            try {
                console.log('🔄 Tentative de validation locale en fallback...');
                await this.loadZoneConfig();
                
                if (Object.keys(this.AUTHORIZED_ZONES).length === 0) {
                    throw new Error('Aucune zone configurée');
                }
                
                const position = await this.getCurrentPosition();
                const validation = this.isPositionInAuthorizedZone(position.latitude, position.longitude);
                
                return {
                    success: validation.isAuthorized,
                    position: position,
                    validation: validation,
                    message: validation.isAuthorized 
                        ? `✅ Zone autorisée (mode local): ${validation.closestZone?.zoneName || 'Zone détectée'}`
                        : `❌ Hors zone autorisée (mode local): ${validation.closestZone?.distance || '?'}m`
                };
                
            } catch (fallbackError) {
                return {
                    success: false,
                    position: null,
                    validation: null,
                    error: error.message,
                    message: `❌ Impossible de vérifier votre position: ${error.message}`
                };
            }
        }
    }

    /**
     * Démarrer le suivi de position en temps réel
     */
    startTracking(callback) {
        if (!navigator.geolocation) {
            callback({ error: 'Géolocalisation non supportée' });
            return;
        }

        const options = {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 10000
        };

        this.watchId = navigator.geolocation.watchPosition(
            (position) => {
                this.lastKnownPosition = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp
                };
                
                const validation = this.isPositionInAuthorizedZone(
                    position.coords.latitude,
                    position.coords.longitude
                );
                
                callback({
                    position: this.lastKnownPosition,
                    validation: validation
                });
            },
            (error) => {
                callback({ error: error.message });
            },
            options
        );
        
        this.isTracking = true;
    }

    /**
     * Arrêter le suivi de position
     */
    stopTracking() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        this.isTracking = false;
    }

    /**
     * Obtenir le statut de géolocalisation
     */
    getLocationStatus() {
        if (!navigator.geolocation) {
            return { 
                available: false, 
                message: 'Géolocalisation non supportée' 
            };
        }
        
        return {
            available: true,
            tracking: this.isTracking,
            lastPosition: this.lastKnownPosition,
            message: this.isTracking ? 'Suivi actif' : 'Suivi inactif'
        };
    }

    /**
     * Configuration des zones (pour admin)
     */
    updateZoneConfig(zoneId, config) {
        if (this.AUTHORIZED_ZONES[zoneId]) {
            this.AUTHORIZED_ZONES[zoneId] = { 
                ...this.AUTHORIZED_ZONES[zoneId], 
                ...config 
            };
            
            // Sauvegarder en localStorage pour persistance
            localStorage.setItem(
                'geolocation_zones', 
                JSON.stringify(this.AUTHORIZED_ZONES)
            );
            
            return true;
        }
        return false;
    }

    /**
     * Charger la configuration des zones depuis l'API backend
     */
    async loadZoneConfig() {
        try {
            console.log('🔄 Chargement des zones depuis l\'API...');
            
            // Vérifier si on a un cache valide (5 minutes)
            const now = new Date().getTime();
            if (this.zonesCache && this.cacheExpiry && now < this.cacheExpiry) {
                console.log('📦 Utilisation du cache zones');
                this.AUTHORIZED_ZONES = this.zonesCache;
                return;
            }
            
            const response = await secureApiEndpoints.geolocationZones.getEnabledZones();
            
            if (response.success && response.data) {
                // Convertir le format API en format attendu par le service
                const zones = {};
                response.data.forEach(zone => {
                    zones[`zone_${zone.id}`] = {
                        id: zone.id,
                        name: zone.name,
                        latitude: parseFloat(zone.latitude),
                        longitude: parseFloat(zone.longitude),
                        radius: parseInt(zone.radius),
                        enabled: true // On ne récupère que les zones actives
                    };
                });
                
                this.AUTHORIZED_ZONES = zones;
                this.zonesCache = zones;
                this.cacheExpiry = now + (5 * 60 * 1000); // Cache 5 minutes
                
                console.log('✅ Zones chargées depuis l\'API:', zones);
            } else {
                console.warn('⚠️ Aucune zone active trouvée');
                this.AUTHORIZED_ZONES = {};
            }
        } catch (error) {
            console.error('❌ Erreur chargement zones depuis API:', error);
            // En cas d'erreur API, garder les zones en cache si disponibles
            if (this.zonesCache) {
                console.log('🔄 Fallback sur cache zones');
                this.AUTHORIZED_ZONES = this.zonesCache;
            } else {
                console.log('⚠️ Aucune zone disponible');
                this.AUTHORIZED_ZONES = {};
            }
        }
    }

    /**
     * Obtenir les statistiques des zones (pour les composants UI)
     */
    async getZoneStats() {
        // S'assurer que les zones sont chargées
        await this.loadZoneConfig();
        
        const zones = Object.values(this.AUTHORIZED_ZONES);
        const totalZones = zones.length;
        const activeZones = zones.filter(zone => zone.enabled).length;
        const inactiveZones = totalZones - activeZones;
        
        return {
            total: totalZones,
            active: activeZones,
            inactive: inactiveZones,
            hasZones: totalZones > 0
        };
    }

    /**
     * Obtenir toutes les zones avec leur statut
     */
    async getAllZones() {
        await this.loadZoneConfig();
        return this.AUTHORIZED_ZONES;
    }
}

// Instance globale du service
const geolocationService = new GeolocationService();

export default geolocationService;