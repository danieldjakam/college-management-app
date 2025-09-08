import { host } from '../utils/fetch';
import logger from '../utils/logger';

/**
 * Service pour la gestion des présences étudiants
 * Intègre avec les nouveaux endpoints du MobileAttendanceController
 */
class StudentAttendanceService {
    constructor() {
        this.baseURL = `${host}/api`;
        this.tokenKey = 'auth_token';
    }

    // Configuration des headers par défaut avec authentification
    getHeaders() {
        const token = localStorage.getItem(this.tokenKey);
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': token ? `Bearer ${token}` : ''
        };
    }

    // Gestion des erreurs de réponse API
    async handleResponse(response) {
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const error = new Error(errorData.message || `Erreur HTTP ${response.status}`);
            error.status = response.status;
            error.data = errorData;
            throw error;
        }
        return response.json();
    }

    /**
     * NAVIGATION HIÉRARCHIQUE
     */

    // Récupérer les sections disponibles
    async getSections() {
        try {
            const response = await fetch(`${this.baseURL}/sections`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            const data = await this.handleResponse(response);
            logger.info('Sections récupérées:', data);
            return data;
        } catch (error) {
            logger.error('Erreur lors de la récupération des sections:', error);
            throw error;
        }
    }

    // Récupérer les niveaux d'une section
    async getLevelsBySection(sectionId) {
        try {
            const response = await fetch(`${this.baseURL}/mobile/sections/${sectionId}/levels`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            const data = await this.handleResponse(response);
            logger.info(`Niveaux récupérés pour la section ${sectionId}:`, data);
            return data;
        } catch (error) {
            logger.error(`Erreur lors de la récupération des niveaux pour la section ${sectionId}:`, error);
            throw error;
        }
    }

    // Récupérer les classes d'un niveau
    async getClassesByLevel(levelId) {
        try {
            const response = await fetch(`${this.baseURL}/mobile/levels/${levelId}/classes`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            const data = await this.handleResponse(response);
            logger.info(`Classes récupérées pour le niveau ${levelId}:`, data);
            return data;
        } catch (error) {
            logger.error(`Erreur lors de la récupération des classes pour le niveau ${levelId}:`, error);
            throw error;
        }
    }

    // Récupérer les séries d'une classe
    async getSeriesByClass(classId) {
        try {
            const response = await fetch(`${this.baseURL}/mobile/classes/${classId}/series`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            const data = await this.handleResponse(response);
            logger.info(`Séries récupérées pour la classe ${classId}:`, data);
            return data;
        } catch (error) {
            logger.error(`Erreur lors de la récupération des séries pour la classe ${classId}:`, error);
            throw error;
        }
    }

    // Récupérer les étudiants d'une série
    async getStudentsBySeries(seriesId) {
        try {
            const response = await fetch(`${this.baseURL}/mobile/students/series/${seriesId}`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            const data = await this.handleResponse(response);
            logger.info(`Étudiants récupérés pour la série ${seriesId}:`, data);
            return data;
        } catch (error) {
            logger.error(`Erreur lors de la récupération des étudiants pour la série ${seriesId}:`, error);
            throw error;
        }
    }

    /**
     * GESTION MANUELLE DES PRÉSENCES
     */

    // Marquer la présence/absence d'un étudiant
    async markStudentAttendance(studentId, eventType, attendanceDate, isPresent, notes = '') {
        try {
            const response = await fetch(`${this.baseURL}/attendance/students/mark`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    student_id: studentId,
                    event_type: eventType, // 'entry' ou 'exit'
                    attendance_date: attendanceDate,
                    is_present: isPresent,
                    notes: notes
                })
            });
            const data = await this.handleResponse(response);
            logger.info('Présence marquée:', data);
            return data;
        } catch (error) {
            logger.error('Erreur lors du marquage de présence:', error);
            throw error;
        }
    }

    // Marquer tous les étudiants absents d'une série
    async markAllAbsentInSeries(seriesId, attendanceDate, notes = '') {
        try {
            const response = await fetch(`${this.baseURL}/attendance/students/mark-absent-series`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    series_id: seriesId,
                    attendance_date: attendanceDate,
                    notes: notes
                })
            });
            const data = await this.handleResponse(response);
            logger.info('Absences marquées en masse:', data);
            return data;
        } catch (error) {
            logger.error('Erreur lors du marquage des absences en masse:', error);
            throw error;
        }
    }

    // Obtenir le statut actuel d'un étudiant
    async getStudentStatus(studentId, date = null) {
        try {
            const params = new URLSearchParams({
                student_id: studentId
            });
            
            if (date) {
                params.append('date', date);
            }

            const response = await fetch(`${this.baseURL}/attendance/students/status?${params}`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            const data = await this.handleResponse(response);
            logger.info(`Statut récupéré pour l'étudiant ${studentId}:`, data);
            return data;
        } catch (error) {
            logger.error(`Erreur lors de la récupération du statut de l'étudiant ${studentId}:`, error);
            throw error;
        }
    }

    // Soumission des présences en masse
    async submitBulkAttendance(seriesId, eventType, attendanceDate, studentsData, notes = '') {
        try {
            const response = await fetch(`${this.baseURL}/attendance/students/submit`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    series_id: seriesId,
                    event_type: eventType,
                    attendance_date: attendanceDate,
                    students: studentsData,
                    notes: notes
                })
            });
            const data = await this.handleResponse(response);
            logger.info('Présences soumises en masse:', data);
            return data;
        } catch (error) {
            logger.error('Erreur lors de la soumission en masse:', error);
            throw error;
        }
    }

    /**
     * STATISTIQUES ET RAPPORTS
     */

    // Obtenir les statistiques de présence avec filtres
    async getAttendanceStats(date, filters = {}) {
        try {
            const params = new URLSearchParams({ date });
            
            // Ajouter les filtres optionnels
            Object.keys(filters).forEach(key => {
                if (filters[key] !== null && filters[key] !== undefined) {
                    params.append(key, filters[key]);
                }
            });

            const response = await fetch(`${this.baseURL}/attendance/students/mobile/stats?${params}`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            const data = await this.handleResponse(response);
            logger.info('Statistiques de présence récupérées:', data);
            return data;
        } catch (error) {
            logger.error('Erreur lors de la récupération des statistiques:', error);
            throw error;
        }
    }

    /**
     * UTILITAIRES
     */

    // Formater la date pour l'API (YYYY-MM-DD)
    formatDate(date) {
        if (typeof date === 'string') return date;
        return date.toISOString().split('T')[0];
    }

    // Obtenir la date d'aujourd'hui formatée
    getTodayFormatted() {
        return this.formatDate(new Date());
    }

    // Valider les données d'étudiants pour soumission en masse
    validateStudentsData(studentsData) {
        if (!Array.isArray(studentsData) || studentsData.length === 0) {
            throw new Error('Les données des étudiants doivent être un tableau non vide');
        }

        return studentsData.map(student => {
            if (!student.student_id || typeof student.is_present !== 'boolean') {
                throw new Error('Chaque étudiant doit avoir un student_id et un statut is_present');
            }
            return {
                student_id: student.student_id,
                is_present: student.is_present,
                student_number: student.student_number || null
            };
        });
    }

    // Calculer le taux de présence
    calculateAttendanceRate(presentCount, totalCount) {
        if (totalCount === 0) return 0;
        return Math.round((presentCount / totalCount) * 100 * 10) / 10; // 1 décimale
    }

    /**
     * GESTION LOCALE DES DONNÉES
     */

    // Cache local pour améliorer les performances
    setCache(key, data, ttl = 300000) { // TTL par défaut: 5 minutes
        const cacheData = {
            data,
            timestamp: Date.now(),
            ttl
        };
        localStorage.setItem(`attendance_cache_${key}`, JSON.stringify(cacheData));
    }

    getCache(key) {
        try {
            const cacheData = JSON.parse(localStorage.getItem(`attendance_cache_${key}`));
            if (!cacheData) return null;
            
            const now = Date.now();
            if (now - cacheData.timestamp > cacheData.ttl) {
                localStorage.removeItem(`attendance_cache_${key}`);
                return null;
            }
            
            return cacheData.data;
        } catch (error) {
            logger.error('Erreur lors de la lecture du cache:', error);
            return null;
        }
    }

    // Nettoyer le cache expiré
    cleanExpiredCache() {
        const keys = Object.keys(localStorage);
        keys.forEach(key => {
            if (key.startsWith('attendance_cache_')) {
                this.getCache(key.replace('attendance_cache_', '')); // Déclenchera la suppression si expiré
            }
        });
    }
}

// Export d'une instance singleton
const studentAttendanceService = new StudentAttendanceService();
export default studentAttendanceService;