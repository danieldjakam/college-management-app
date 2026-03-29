/**
 * Migration des anciens endpoints API vers le nouveau système d'authentification JWT
 * Ce fichier remplace progressivement l'ancien système basé sur sessionStorage.user
 */

import { authService } from '../services/authService';
import { host } from './fetch';
import logger from './logger';

/**
 * Nouveau service API qui utilise automatiquement les tokens JWT
 */
class SecureApiService {
    constructor() {
        this.baseURL = `${host}/api`;
    }

    // Fonction de requête sécurisée personnalisée - VERSION AMÉLIORÉE
    async makeRequest(endpoint, options = {}) {
        const fullUrl = endpoint.startsWith('http') ? endpoint : `${this.baseURL}${endpoint}`;

        const token = authService.getToken();
        const headers = {
            'Accept': 'application/json',
            ...options.headers
        };

        // Ne pas ajouter Content-Type pour FormData
        if (!(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
        }

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        try {
            // Support pour timeout personnalisé
            const timeout = options.timeout || 300000; // Default: 5 minutes (pour les requêtes lourdes comme les bulletins)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeout);

            const response = await fetch(fullUrl, {
                ...options,
                headers,
                signal: controller.signal
            }).finally(() => clearTimeout(timeoutId));

            // Gestion des erreurs HTTP
            if (!response.ok) {
                let errorMessage = `Erreur HTTP ${response.status}: ${response.statusText}`;

                try {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        errorMessage = errorData.message || errorData.error || errorMessage;
                    } else {
                        const errorText = await response.text();
                        if (errorText) {
                            errorMessage = errorText;
                        }
                    }
                } catch (parseError) {
                    console.warn('Impossible de parser la réponse d\'erreur:', parseError);
                }

                // Gestion spéciale pour les erreurs 401
                if (response.status === 401) {
                    authService.removeToken();
                    window.dispatchEvent(new CustomEvent('auth:unauthorized'));
                    throw new Error('Session expirée. Veuillez vous reconnecter.');
                }

                throw new Error(errorMessage);
            }

            // Vérifier le type de contenu
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const responseText = await response.text();

                if (!responseText.trim()) {
                    console.warn('Réponse JSON vide du serveur');
                    return { success: true };
                }

                try {
                    return JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Erreur de parsing JSON:', jsonError);
                    console.error('Contenu reçu:', responseText);
                    throw new Error('Format de réponse invalide du serveur');
                }
            } else {
                // Pour les réponses non-JSON (PDF, images, etc.)
                return response;
            }

        } catch (error) {
            if (error.name === 'AbortError') {
                logger.apiError(error, 'Timeout');
                throw new Error('La requête a pris trop de temps. Veuillez réessayer.');
            }
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                logger.apiError(error, 'Connexion');
                throw new Error('Impossible de se connecter au serveur. Vérifiez votre connexion.');
            }
            logger.apiError(error);
            throw error;
        }
    }

    // Méthode spéciale pour les requêtes longues (génération de bulletins, etc.)
    async makeLongRequest(endpoint, options = {}) {
        return await this.makeRequest(endpoint, {
            ...options,
            timeout: 600000 // 10 minutes pour les opérations longues
        });
    }

    // Méthodes GET
    async get(endpoint, options = {}) {
        return await this.makeRequest(endpoint, {
            method: 'GET',
            ...options
        });
    }

    // Méthodes POST (avec support pour timeout personnalisé)
    async post(endpoint, data = null, options = {}) {
        return await this.makeRequest(endpoint, {
            method: 'POST',
            body: data ? (data instanceof FormData ? data : JSON.stringify(data)) : null,
            ...options
        });
    }

    // Méthodes PUT
    async put(endpoint, data = null, options = {}) {
        return await this.makeRequest(endpoint, {
            method: 'PUT',
            body: data ? (data instanceof FormData ? data : JSON.stringify(data)) : null,
            ...options
        });
    }

    // Méthodes DELETE
    async delete(endpoint, options = {}) {
        return await this.makeRequest(endpoint, {
            method: 'DELETE',
            ...options
        });
    }

    // Méthodes PATCH
    async patch(endpoint, data = null, options = {}) {
        return await this.makeRequest(endpoint, {
            method: 'PATCH',
            body: data ? (data instanceof FormData ? data : JSON.stringify(data)) : null,
            ...options
        });
    }
}

// Instance singleton
const secureApi = new SecureApiService();

/**
 * Nouveaux endpoints API avec authentification JWT
 * Remplace progressivement les anciens endpoints
 */
export const secureApiEndpoints = {
    // === GENERIC REQUEST ===
    request: (endpoint, options = {}) => secureApi.makeRequest(endpoint, options),

    // === AUTHENTICATION ===
    auth: {
        login: (credentials) => authService.login(credentials),
        logout: () => authService.logout(),
        refresh: () => authService.refreshToken(),
        me: () => authService.getCurrentUser(),
        updateProfile: (data) => secureApi.put('/users/profile', data),
        changePassword: (data) => secureApi.put('/auth/change-password', data),
        uploadAvatar: async (formData) => {
            const token = authService.getToken();

            try {
                const response = await fetch(`${secureApi.baseURL}/upload-photo`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                        // Don't set Content-Type for FormData
                    },
                    body: formData
                });

                if (!response.ok) {
                    let errorMessage = `HTTP Error ${response.status}: ${response.statusText}`;

                    try {
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            const errorData = await response.json();
                            errorMessage = errorData.message || errorData.error || errorMessage;
                        }
                    } catch (e) {
                        // Ignore JSON parsing errors
                    }

                    throw new Error(errorMessage);
                }

                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const result = await response.json();
                    return {
                        success: result.success,
                        message: result.message,
                        data: {
                            url: result.data?.url || null,
                            filename: result.data?.filename || null
                        }
                    };
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (error) {
                throw error;
            }
        }
    },

    // === USERS ===
    users: {
        getAll: () => secureApi.get('/users/all'),
        getById: (id) => secureApi.get(`/users/${id}`),
        create: (data) => secureApi.post('/users', data),
        update: (id, data) => secureApi.put(`/users/${id}`, data),
        delete: (id) => secureApi.delete(`/users/${id}`),
        getProfile: () => secureApi.get('/users/profile'),
        updateProfile: (data) => secureApi.put('/users/profile', data),
        changePassword: (data) => secureApi.put('/users/change-password', data)
    },

    // === TEACHERS ===
    teachers: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teachers${queryString ? '?' + queryString : ''}`);
        },
        getById: (id) => secureApi.get(`/teachers/${id}`),
        create: (data) => secureApi.post('/teachers', data),
        update: (id, data) => secureApi.put(`/teachers/${id}`, data),
        delete: (id) => secureApi.delete(`/teachers/${id}`),
        toggleStatus: (id) => secureApi.post(`/teachers/${id}/toggle-status`),
        assignSubjects: (id, data) => secureApi.post(`/teachers/${id}/assign-subjects`, data),
        removeAssignment: (id, data) => secureApi.post(`/teachers/${id}/remove-assignment`, data),
        createUserAccount: (id, data) => secureApi.post(`/teachers/${id}/create-user-account`, data),
        removeUserAccount: (id) => secureApi.delete(`/teachers/${id}/remove-user-account`),
        getStats: (id, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teachers/${id}/stats${queryString ? '?' + queryString : ''}`);
        },
        generateBadge: (teacherId) => {
            return secureApi.get(`/teachers/${teacherId}/generate-badge`, {
                responseType: 'blob' // Important pour recevoir le PDF en blob
            });
        },

        generateMultipleBadges: (teacherIds) => {
            return secureApi.post('/teachers/generate-multiple-badges', {
                teacher_ids: teacherIds
            }, {
                responseType: 'blob' // Important pour recevoir le PDF en blob
            });
        }
    },

    // === SUBJECTS ===
    subjects: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/subjects${queryString ? '?' + queryString : ''}`);
        },
        getById: (id) => secureApi.get(`/subjects/${id}`),
        create: (data) => secureApi.post('/subjects', data),
        update: (id, data) => secureApi.put(`/subjects/${id}`, data),
        delete: (id) => secureApi.delete(`/subjects/${id}`),
        toggleStatus: (id) => secureApi.post(`/subjects/${id}/toggle-status`),
        getForSeries: (seriesId) => secureApi.get(`/subjects/series/${seriesId}`),
        configureForSeries: (seriesId, data) => secureApi.post(`/subjects/series/${seriesId}/configure`, data)
    },

    // === STAFF ATTENDANCE ===
    staff: {
        scanQR: (data) => secureApi.post('/staff-attendance/scan-qr', data),
        getDailyAttendance: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/staff-attendance/daily-attendance${queryString ? '?' + queryString : ''}`);
        },
        getEntryExitStats: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/staff-attendance/entry-exit-stats${queryString ? '?' + queryString : ''}`);
        },
        generateQRCode: (data) => secureApi.post('/staff-attendance/generate-qr', data),
        downloadBadgePDF: async (data) => {
            return secureApi.makeRequest('/staff-attendance/generate-qr', {
                method: 'POST',
                body: JSON.stringify(data)
            });
        },
        getStaffWithQR: () => secureApi.get('/staff-attendance/staff-with-qr'),
        getStaffReport: (staffId, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/staff-attendance/staff/${staffId}/report${queryString ? '?' + queryString : ''}`);
        },
        generateMultipleBadges: async (userIds) => {
            return secureApi.makeRequest('/staff-attendance/generate-multiple-badges', {
                method: 'POST',
                body: JSON.stringify({ user_ids: userIds })
            });
        },
        
        // Alias pour compatibilité avec le scanner
        getDailyAttendances: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/staff-attendance/daily-attendance${queryString ? '?' + queryString : ''}`);
        },
        getAttendanceStats: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/staff-attendance/entry-exit-stats${queryString ? '?' + queryString : ''}`);
        }
    },

    // === STUDENTS ===
    students: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/students${queryString ? '?' + queryString : ''}`);
        },
        getByClass: (classId) => secureApi.get(`/students/class/${classId}`),
        getByClassSeries: (seriesId) => secureApi.get(`/students/class-series/${seriesId}`),
        getById: (id) => secureApi.get(`/students/${id}`),
        create: (data) => secureApi.post('/students', data),

        // VERSION AMÉLIORÉE avec meilleure gestion des erreurs JSON
        createWithPhoto: async (formData) => {
            const token = authService.getToken();

            try {
                const response = await fetch(`${secureApi.baseURL}/students`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                        // Don't set Content-Type - let browser set it with boundary for FormData
                    },
                    body: formData
                });

                // Vérifier d'abord si la réponse est ok
                if (!response.ok) {
                    // Essayer de récupérer le message d'erreur
                    let errorMessage = `HTTP Error ${response.status}: ${response.statusText}`;

                    try {
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            const errorData = await response.json();
                            errorMessage = errorData.message || errorData.error || errorMessage;
                        } else {
                            // Si ce n'est pas du JSON, lire comme texte
                            const errorText = await response.text();
                            if (errorText) {
                                errorMessage = errorText;
                            }
                        }
                    } catch (parseError) {
                        console.warn('Impossible de parser la réponse d\'erreur:', parseError);
                    }

                    throw new Error(errorMessage);
                }

                // Vérifier le type de contenu de la réponse
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.warn('La réponse du serveur n\'est pas au format JSON, type:', contentType);
                    // Essayer quand même de lire comme texte pour voir ce qu'on reçoit
                    const responseText = await response.text();
                    console.log('Contenu de la réponse non-JSON:', responseText);

                    // Si c'est vide mais status 200/201, considérer comme succès
                    if (!responseText.trim() && (response.status === 200 || response.status === 201)) {
                        return { success: true, message: 'Étudiant créé avec succès' };
                    }

                    throw new Error('La réponse du serveur n\'est pas au format JSON');
                }

                // Vérifier si le body existe
                const responseText = await response.text();
                if (!responseText || responseText.trim() === '') {
                    console.warn('Réponse vide du serveur mais status OK');
                    return { success: true, message: 'Étudiant créé avec succès' };
                }

                // Parser le JSON
                try {
                    return JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Erreur de parsing JSON:', jsonError);
                    console.error('Contenu de la réponse:', responseText);
                    throw new Error('Format de réponse invalide du serveur');
                }

            } catch (networkError) {
                if (networkError.name === 'TypeError' && networkError.message.includes('fetch')) {
                    throw new Error('Impossible de se connecter au serveur. Vérifiez votre connexion.');
                }
                throw networkError;
            }
        },

        // Fonction de debug pour diagnostiquer les problèmes
        debugCreateStudent: async (formData) => {
            const token = authService.getToken();

            console.log('=== DEBUG CREATE STUDENT ===');
            console.log('Token présent:', !!token);
            console.log('FormData entries:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}:`, value instanceof File ? `File(${value.name})` : value);
            }

            try {
                const response = await fetch(`${secureApi.baseURL}/students`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                console.log('Response received:');
                console.log('- Status:', response.status);
                console.log('- Status Text:', response.statusText);
                console.log('- Headers:', Object.fromEntries(response.headers.entries()));

                const responseText = await response.text();
                console.log('- Body length:', responseText.length);
                console.log('- Body content:', responseText);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}\nBody: ${responseText}`);
                }

                if (!responseText.trim()) {
                    console.warn('Empty response body');
                    return { success: true, debug: true };
                }

                return JSON.parse(responseText);

            } catch (error) {
                console.error('=== ERROR ===', error);
                throw error;
            }
        },

        update: (id, data) => secureApi.put(`/students/${id}`, data),
        updateStatus: (id, status) => secureApi.patch(`/students/${id}/status`, { student_status: status }),
        updateWithPhoto: (id, formData) => {
            const token = authService.getToken();
            return fetch(`${secureApi.baseURL}/students/${id}/update-with-photo`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                    // Don't set Content-Type - let browser set it with boundary for FormData
                },
                body: formData
            }).then(async response => {
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP Error ${response.status}`);
                }
                return response.json();
            });
        },
        delete: (id) => secureApi.delete(`/students/${id}`),
        exportCsv: async (seriesId) => {
            const token = authService.getToken();
            const response = await fetch(`${secureApi.baseURL}/students/export-csv/${seriesId}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'text/csv'
                }
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erreur lors de l\'export CSV');
            }

            return await response.blob();
        },
        exportPdf: async (seriesId) => {
            const token = authService.getToken();
            const response = await fetch(`${secureApi.baseURL}/students/export-pdf/${seriesId}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erreur lors de l\'export PDF');
            }

            return response; // Retourner la réponse pour permettre .text()
        },
        importCsv: (formData, seriesId) => {
            const token = authService.getToken();

            // Utiliser la nouvelle route avec l'ID de série
            const endpoint = seriesId
                ? `/students/series/${seriesId}/import`
                : `/students/import/csv`; // Fallback vers l'ancienne route

            return fetch(`${secureApi.baseURL}${endpoint}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                    // Don't set Content-Type - let browser set it with boundary for FormData
                },
                body: formData
            }).then(async response => {
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP Error ${response.status}`);
                }
                return response.json();
            });
        },

        // Nouvelle méthode pour import Excel spécifique à une série
        importExcel: (formData, seriesId) => {
            const token = authService.getToken();

            // Utiliser la nouvelle route avec l'ID de série
            const endpoint = seriesId
                ? `/students/series/${seriesId}/import`
                : `/students/import/excel`; // Fallback vers l'ancienne route

            return fetch(`${secureApi.baseURL}${endpoint}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                    // Don't set Content-Type - let browser set it with boundary for FormData
                },
                body: formData
            }).then(async response => {
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP Error ${response.status}`);
                }
                return response.json();
            });
        },
        getSchoolYears: () => secureApi.get('/students/school-years'),
        transfer: (id, newClassId) => secureApi.post(`/students/${id}/transfer`, { class_id: newClassId }),
        transferToSeries: (id, newSeriesId) => secureApi.post(`/students/${id}/transfer-series`, { class_series_id: newSeriesId }),
        transferWithinClass: (id, targetSeriesId) => secureApi.post(`/students/${id}/transfer-within-class`, { target_series_id: targetSeriesId }),
        getOrdered: (classId) => secureApi.get(`/students/class/${classId}/ordered`),
        reorder: (data) => secureApi.post('/students/reorder', data),
        sortAlphabetically: (seriesId, data) => secureApi.post(`/students/class-series/${seriesId}/sort-alphabetically`, data),
        bulkUploadPhotos: (formData) => secureApi.post('/students/bulk-upload-photos', formData)
    },

    // === CLASSES ===
    classes: {
        getAll: () => secureApi.get('/classes'),
        getById: (id) => secureApi.get(`/classes/${id}`),
        getBySection: (sectionId) => secureApi.get(`/classes/section/${sectionId}`),
        create: (data) => secureApi.post('/classes', data),
        update: (id, data) => secureApi.put(`/classes/${id}`, data),
        delete: (id) => secureApi.delete(`/classes/${id}`)
    },

    // === SECTIONS ===
    sections: {
        getAll: () => secureApi.get('/sections'),
        getById: (id) => secureApi.get(`/sections/${id}`),
        create: (data) => secureApi.post('/sections', data),
        update: (id, data) => secureApi.put(`/sections/${id}`, data),
        delete: (id) => secureApi.delete(`/sections/${id}`),
        getDashboard: () => secureApi.get('/sections/dashboard'),
        toggleStatus: (id) => secureApi.post(`/sections/${id}/toggle-status`)
    },

    // === LEVELS ===
    levels: {
        getAll: () => secureApi.get('/levels'),
        getById: (id) => secureApi.get(`/levels/${id}`),
        create: (data) => secureApi.post('/levels', data),
        update: (id, data) => secureApi.put(`/levels/${id}`, data),
        delete: (id) => secureApi.delete(`/levels/${id}`),
        getDashboard: () => secureApi.get('/levels/dashboard'),
        toggleStatus: (id) => secureApi.post(`/levels/${id}/toggle-status`),
        getBySection: (sectionId) => secureApi.get(`/levels?section_id=${sectionId}`)
    },

    // === SCHOOL CLASSES ===
    schoolClasses: {
        getAll: () => secureApi.get('/school-classes'),
        getById: (id) => secureApi.get(`/school-classes/${id}`),
        getStudents: (id) => secureApi.get(`/students/class/${id}`), // Utilise l'endpoint students existant
        getSeriesInSameClass: (classId) => secureApi.get(`/school-classes/${classId}/series`),
        create: (data) => secureApi.post('/school-classes', data),
        update: (id, data) => secureApi.put(`/school-classes/${id}`, data),
        delete: (id) => secureApi.delete(`/school-classes/${id}`),
        getDashboard: () => secureApi.get('/school-classes/dashboard'),
        toggleStatus: (id) => secureApi.post(`/school-classes/${id}/toggle-status`),
        configurePayments: (id, data) => secureApi.post(`/school-classes/${id}/configure-payments`, data),
        getByLevel: (levelId) => secureApi.get(`/school-classes?level_id=${levelId}`),
        getBySection: (sectionId) => secureApi.get(`/school-classes?section_id=${sectionId}`)
    },

    // === STUDENT CARDS (Cartes d'identité scolaires) ===
    studentCards: {
        // Générer les cartes pour une classe entière (10 par page PDF)
        generateClassCards: async (classId, data) => {
            const token = authService.getToken();
            const response = await fetch(`${secureApi.baseURL}/student-cards/class/${classId}/generate`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/pdf'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erreur lors de la génération des cartes');
            }

            // Créer un blob pour le PDF
            const blob = await response.blob();

            // Créer un nom de fichier
            const contentDisposition = response.headers.get('content-disposition');
            let filename = `Cartes_Classe_${classId}_${data.academic_year}.pdf`;
            if (contentDisposition && contentDisposition.includes('filename=')) {
                filename = contentDisposition.split('filename=')[1].replace(/"/g, '');
            }

            // Créer et déclencher le téléchargement
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            return { success: true, filename };
        },

        // Générer une carte individuelle
        generateSingleCard: async (studentId, data) => {
            const token = authService.getToken();
            const response = await fetch(`${secureApi.baseURL}/student-cards/student/${studentId}/generate`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/pdf'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erreur lors de la génération de la carte');
            }

            // Créer un blob pour le PDF
            const blob = await response.blob();

            // Créer un nom de fichier
            const contentDisposition = response.headers.get('content-disposition');
            let filename = `Carte_Eleve_${studentId}.pdf`;
            if (contentDisposition && contentDisposition.includes('filename=')) {
                filename = contentDisposition.split('filename=')[1].replace(/"/g, '');
            }

            // Créer et déclencher le téléchargement
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            return { success: true, filename };
        },

        // Prévisualiser la carte d'un élève (ouvre dans un nouvel onglet)
        previewCard: async (studentId, academicYear) => {
            const token = authService.getToken();
            const url = `${secureApi.baseURL}/student-cards/student/${studentId}/preview`;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'text/html'
                    },
                    body: JSON.stringify({ academic_year: academicYear })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Erreur ${response.status}`);
                }

                // Récupérer le HTML
                let htmlContent = await response.text();

                // Injecter le token dans le HTML avant de l'afficher
                htmlContent = htmlContent.replace('</head>', `
                    <script>
                        window.__JWT_TOKEN__ = '${token}';
                        window.__API_BASE_URL__ = '${secureApi.baseURL}';
                    </script>
                    </head>
                `);

                // Ouvrir dans un nouvel onglet
                const newWindow = window.open('', '_blank');
                if (newWindow) {
                    newWindow.document.write(htmlContent);
                    newWindow.document.close();
                } else {
                    throw new Error('Impossible d\'ouvrir la fenêtre de prévisualisation. Veuillez autoriser les pop-ups.');
                }
            } catch (error) {
                console.error('Erreur lors de la prévisualisation:', error);
                throw error;
            }
        },

        // Vérifier une carte via QR Code
        verifyCard: (matricule) => secureApi.get(`/student-cards/verify/${matricule}`)
    },

    // === GRADES ===
    grades: {
        getByStudent: (studentId) => secureApi.get(`/grades/student/${studentId}`),
        getByClass: (classId) => secureApi.get(`/grades/class/${classId}`),
        create: (data) => secureApi.post('/grades', data),
        update: (id, data) => secureApi.put(`/grades/${id}`, data),
        delete: (id) => secureApi.delete(`/grades/${id}`)
    },

    // === PAYMENT TRANCHES ===
    paymentTranches: {
        getAll: () => secureApi.get('/payment-tranches'),
        getById: (id) => secureApi.get(`/payment-tranches/${id}`),
        create: (data) => secureApi.post('/payment-tranches', data),
        update: (id, data) => secureApi.put(`/payment-tranches/${id}`, data),
        delete: (id) => secureApi.delete(`/payment-tranches/${id}`),
        reorder: (data) => secureApi.post('/payment-tranches/reorder', data),
        getUsageStats: (id) => secureApi.get(`/payment-tranches/${id}/usage-stats`)
    },

    // === SETTINGS ===
    settings: {
        get: () => secureApi.get('/settings'),
        update: (data) => secureApi.put('/settings', data)
    },

    // === REPORTS ===
    reports: {
        getBulletin: (studentId, period) => secureApi.get(`/reports/bulletin/${studentId}/${period}`),
        getClassReport: (classId) => secureApi.get(`/reports/class/${classId}`),
        getFinancialReport: (period) => secureApi.get(`/reports/financial/${period}`),
        getInsolvableReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/insolvable?${queryString}`);
        },
        getSolvableReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/solvable?${queryString}`);
        },
        getPaymentsReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/payments?${queryString}`);
        },
        getRameReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/rame?${queryString}`);
        },
        getRecoveryReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/recovery?${queryString}`);
        },
        getCollectionSummaryReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/collection-summary?${queryString}`);
        },
        getPaymentDetailsReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/payment-details?${queryString}`);
        },
        getScholarshipsDiscountsReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/scholarships-discounts?${queryString}`);
        },
        getCollectionDetailsReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/collection-details?${queryString}`);
        },
        getSeriesCollectionSummary: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/series-collection-summary?${queryString}`);
        },
        getSchoolFeePaymentDetails: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/school-fee-payment-details?${queryString}`);
        },
        getRecoveryStatus: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/recovery-status?${queryString}`);
        },
        generateSchoolCertificates: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/school-certificates?${queryString}`);
        },
        getDetailedCollectionReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/detailed-collection?${queryString}`);
        },
        getClassSchoolFeesReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/class-school-fees?${queryString}`);
        },
        exportPdf: async (params) => {
            const queryString = new URLSearchParams(params).toString();
            const token = authService.getToken();
            const response = await fetch(`${secureApi.baseURL}/reports/export-pdf?${queryString}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erreur lors de l\'export PDF');
            }

            const htmlContent = await response.text();
            return { success: true, data: htmlContent };
        },
        downloadPdf: () => `${secureApi.baseURL}/reports/download-pdf`
    },

    // === SEARCH ===
    search: {
        global: (query, limit = 20) => secureApi.get(`/search?query=${encodeURIComponent(query)}&limit=${limit}`),
        quick: (query) => secureApi.get(`/search/quick?query=${encodeURIComponent(query)}`)
    },

    // === STATISTICS ===
    stats: {
        getGlobal: () => secureApi.get('/stats/global')
    },

    // === ACCOUNTANT ===
    accountant: {
        dashboard: () => secureApi.get('/accountant/dashboard'),
        getClasses: () => secureApi.get('/accountant/classes'),
        getClassSeries: (classId) => secureApi.get(`/accountant/classes/${classId}/series`),
        getSeriesStudents: (seriesId) => secureApi.get(`/accountant/series/${seriesId}/students`),
        getStudent: (studentId) => secureApi.get(`/accountant/students/${studentId}`)
    },

    // === SCHOOL YEARS ===
    schoolYears: {
        getAll: () => secureApi.get('/school-years'),
        getCurrent: () => secureApi.get('/school-years/active'),
        create: (data) => secureApi.post('/school-years', data),
        update: (id, data) => secureApi.put(`/school-years/${id}`, data),
        setCurrent: (id) => secureApi.post(`/school-years/${id}/set-current`),
        getActive: () => secureApi.get('/school-years/active'),
        getActiveYears: () => secureApi.get('/school-years/active'),
        getUserWorkingYear: () => secureApi.get('/school-years/user-working-year'),
        setUserWorkingYear: (yearId) => secureApi.post('/school-years/set-user-working-year', { school_year_id: yearId })
    },

    // === MANUAL DISCOUNTS (Module Réductions) ===
    manualDiscounts: {
        list: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/manual-discounts${queryString ? '?' + queryString : ''}`);
        },
        getForStudent: (studentId, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/manual-discounts/student/${studentId}${queryString ? '?' + queryString : ''}`);
        },
        upsert: (data) => secureApi.post('/manual-discounts', data),
        deleteForStudent: (studentId, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.delete(`/manual-discounts/student/${studentId}${queryString ? '?' + queryString : ''}`);
        },
    },

    // === PAYMENTS ===
    // payments: {
    // },

    // === STUDENT RAME (pour RameStatusToggle) ===
    studentRame: {
        getStatus: (studentId) => secureApi.get(`/student-rame/student/${studentId}/status`),
        updateStatus: (studentId, data) => secureApi.post(`/student-rame/student/${studentId}/update`, data),
        getClassStatus: (classSeriesId) => secureApi.get(`/student-rame/class-series/${classSeriesId}`)
    },

    // === PAYMENTS ===
    payments: {
        getStudentInfo: (studentId) => secureApi.get(`/payments/student/${studentId}/info`),
        getStudentInfoWithDiscount: (studentId) => secureApi.get(`/payments/student/${studentId}/info-with-discount`),
        getStudentHistory: (studentId) => secureApi.get(`/payments/student/${studentId}/history`),
        calculateWithDate: (studentId, data) => secureApi.post(`/payments/student/${studentId}/calculate-with-date`, data),
        store: (data) => secureApi.post('/payments', data),
        generateReceipt: (paymentId) => secureApi.get(`/payments/${paymentId}/receipt`),
        downloadReceipt: (paymentId) => secureApi.get(`/payments/${paymentId}/receipt/pdf`),
        getStats: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/payments/stats${queryString ? '?' + queryString : ''}`);
        },
        generateListingReport: (data) => secureApi.post('/payments/listing-report', data),
        generateTrancheListsReport: (data) => secureApi.post('/payments/tranche-lists-report', data),
        getTranches: () => secureApi.get('/payments/tranches'),
        downloadListingReportPDF: async (data) => {
            const token = authService.getToken();
            const response = await fetch(`${secureApi.baseURL}/payments/listing-report`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/pdf'
                },
                body: JSON.stringify({ ...data, format: 'pdf' })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const blob = await response.blob();

            // Créer un nom de fichier
            const filename = `listing_paiements_${data.start_date}_${data.end_date}.pdf`;

            // Créer et déclencher le téléchargement
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            return { success: true, filename };
        },
        downloadTrancheListsReportPDF: async (data) => {
            const token = authService.getToken();
            const response = await fetch(`${secureApi.baseURL}/payments/tranche-lists-report`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/pdf'
                },
                body: JSON.stringify({ ...data, format: 'pdf' })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const blob = await response.blob();

            // Créer un nom de fichier
            const filename = `liste_tranches_${data.start_date}_${data.end_date}.pdf`;

            // Créer et déclencher le téléchargement
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            return { success: true, filename };
        },
        create: (data) => secureApi.post('/payments', data),
        downloadReceiptPDF: async (paymentId) => {
            const token = authService.getToken();
            const response = await fetch(`${secureApi.baseURL}/payments/${paymentId}/receipt/pdf`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/pdf'
                }
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erreur lors du téléchargement du PDF');
            }

            // Créer un blob pour le PDF
            const blob = await response.blob();

            // Créer un nom de fichier à partir des headers ou par défaut
            const contentDisposition = response.headers.get('content-disposition');
            let filename = `Recu_${paymentId}.pdf`;
            if (contentDisposition && contentDisposition.includes('filename=')) {
                filename = contentDisposition.split('filename=')[1].replace(/"/g, '');
            }

            // Créer et déclencher le téléchargement
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            return { success: true, filename };
        },

        // Méthodes de gestion des paiements pour comptables
        getPending: () => secureApi.get('/payments/pending'),
        getForManagement: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/payments/management${queryString ? '?' + queryString : ''}`);
        },
        update: (paymentId, data) => secureApi.put(`/payments/${paymentId}`, data),
        validate: (paymentId) => secureApi.post(`/payments/${paymentId}/validate`),
        cancel: (paymentId, data) => secureApi.post(`/payments/${paymentId}/cancel`, data),
        deleteHistory: (studentId) => secureApi.delete(`/payments/student/${studentId}/history`),
    },


    // === SCHOOL SETTINGS ===
    schoolSettings: {
        get: () => secureApi.get('/school-settings'),
        update: (data) => {
            // Si c'est un FormData (avec logo), utiliser une requête multipart
            if (data instanceof FormData) {
                // Ajouter _method=PUT pour simuler PUT avec POST
                data.append('_method', 'PUT');

                const token = authService.getToken();
                return fetch(`${secureApi.baseURL}/school-settings`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                        // Ne pas définir Content-Type pour FormData
                    },
                    body: data
                }).then(async response => {
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        throw new Error(errorData.message || `HTTP Error ${response.status}`);
                    }
                    return response.json();
                });
            } else {
                return secureApi.put('/school-settings', data);
            }
        },
        getLogo: () => secureApi.get('/school-settings/logo'),
        testWhatsApp: () => secureApi.post('/school-settings/test-whatsapp')
    },

    // === CLASS SCHOLARSHIPS ===
    scholarships: {
        getAll: () => secureApi.get('/class-scholarships'),
        getById: (id) => secureApi.get(`/class-scholarships/${id}`),
        getByClass: (classId) => secureApi.get(`/class-scholarships/class/${classId}`),
        create: (data) => secureApi.post('/class-scholarships', data),
        update: (id, data) => secureApi.put(`/class-scholarships/${id}`, data),
        delete: (id) => secureApi.delete(`/class-scholarships/${id}`)
    },

    // === CLASS SERIES (6ème A, 6ème B, etc.) ===
    classSeries: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/series${queryString ? '?' + queryString : ''}`);
        }
    },

    // === SERIES SUBJECTS (Matières par série) ===
    seriesSubjects: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/series-subjects${queryString ? '?' + queryString : ''}`);
        },
        getById: (id) => secureApi.get(`/series-subjects/${id}`),
        getBySeries: (seriesId) => secureApi.get(`/series-subjects/series/${seriesId}`),
        create: (data) => secureApi.post('/series-subjects', data),
        update: (id, data) => secureApi.put(`/series-subjects/${id}`, data),
        delete: (id) => secureApi.delete(`/series-subjects/${id}`),
        toggleStatus: (id) => secureApi.post(`/series-subjects/${id}/toggle-status`),
        bulkConfigure: (seriesId, data) => secureApi.post(`/series-subjects/series/${seriesId}/bulk-configure`, data)
    },

    // === TEACHER ASSIGNMENTS ===
    teacherAssignments: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teacher-assignments${queryString ? '?' + queryString : ''}`);
        },
        getByTeacher: (teacherId, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teacher-assignments/teacher/${teacherId}${queryString ? '?' + queryString : ''}`);
        },
        getAvailableSubjects: (teacherId, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teacher-assignments/teacher/${teacherId}/available-subjects${queryString ? '?' + queryString : ''}`);
        },
        create: (data) => secureApi.post('/teacher-assignments', data),
        delete: (id) => secureApi.delete(`/teacher-assignments/${id}`),
        toggleStatus: (id) => secureApi.post(`/teacher-assignments/${id}/toggle-status`),
        bulkAssign: (teacherId, data) => secureApi.post(`/teacher-assignments/teacher/${teacherId}/bulk-assign`, data),
        updateCredentials: (teacherId, data) => secureApi.put(`/teacher-assignments/teacher/${teacherId}/update-credentials`, data)
    },

    // === MAIN TEACHERS ===
    mainTeachers: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/main-teachers${queryString ? '?' + queryString : ''}`);
        },
        getClassesWithoutMainTeacher: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/main-teachers/classes-without-main-teacher${queryString ? '?' + queryString : ''}`);
        },
        getAvailableTeachers: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/main-teachers/available-teachers${queryString ? '?' + queryString : ''}`);
        },
        create: (data) => secureApi.post('/main-teachers', data),
        update: (id, data) => secureApi.put(`/main-teachers/${id}`, data),
        delete: (id) => secureApi.delete(`/main-teachers/${id}`),
        toggleStatus: (id) => secureApi.post(`/main-teachers/${id}/toggle-status`)
    },

    // === NEEDS ===
    needs: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/needs${queryString ? '?' + queryString : ''}`);
        },
        getMyNeeds: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/needs/my-needs${queryString ? '?' + queryString : ''}`);
        },
        getById: (id) => secureApi.get(`/needs/${id}`),
        create: (data) => secureApi.post('/needs', data),
        update: (id, data) => secureApi.put(`/needs/${id}`, data),
        delete: (id) => secureApi.delete(`/needs/${id}`),
        approve: (id) => secureApi.post(`/needs/${id}/approve`),
        reject: (id, data) => secureApi.post(`/needs/${id}/reject`, data),
        getStatistics: () => secureApi.get('/needs/statistics/summary'),
        testWhatsApp: () => secureApi.post('/needs/test-whatsapp'),

        // Exports
        exportPdf: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return `${secureApi.baseURL}/needs/export/pdf${queryString ? '?' + queryString : ''}?token=${authService.getToken()}`;
        },
        exportExcel: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return `${secureApi.baseURL}/needs/export/excel${queryString ? '?' + queryString : ''}?token=${authService.getToken()}`;
        },
        exportWord: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return `${secureApi.baseURL}/needs/export/word${queryString ? '?' + queryString : ''}?token=${authService.getToken()}`;
        },

        // Bulk actions
        bulkApprove: (data) => secureApi.post('/needs/bulk/approve', data),
        bulkReject: (data) => secureApi.post('/needs/bulk/reject', data),

        // Approved report
        approvedReport: (queryString = '') => {
            return `${secureApi.baseURL}/needs/report/approved${queryString}${queryString.includes('?') ? '&' : '?'}token=${authService.getToken()}`;
        }
    },

    // === DOCUMENTS ===
    documents: {
        // Dossiers
        folders: {
            getAll: (params = {}) => {
                const queryString = new URLSearchParams(params).toString();
                return secureApi.get(`/documents/folders${queryString ? '?' + queryString : ''}`);
            },
            getTree: () => secureApi.get('/documents/folders/tree'),
            getTypes: () => secureApi.get('/documents/folders/types'),
            search: (query) => secureApi.get(`/documents/folders/search?query=${encodeURIComponent(query)}`),
            getById: (id) => secureApi.get(`/documents/folders/${id}`),
            create: (data) => secureApi.post('/documents/folders', data),
            update: (id, data) => secureApi.put(`/documents/folders/${id}`, data),
            delete: (id) => secureApi.delete(`/documents/folders/${id}`)
        },
        // Documents
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/documents${queryString ? '?' + queryString : ''}`);
        },
        getStatistics: () => secureApi.get('/documents/statistics'),
        getTypes: () => secureApi.get('/documents/types'),
        getVisibilityTypes: () => secureApi.get('/documents/visibility-types'),
        getById: (id) => secureApi.get(`/documents/${id}`),
        upload: (formData) => secureApi.post('/documents', formData),
        update: (id, data) => secureApi.put(`/documents/${id}`, data),
        delete: (id) => secureApi.delete(`/documents/${id}`),
        download: (id) => secureApi.get(`/documents/${id}/download`, { responseType: 'blob' }),
        toggleArchive: (id) => secureApi.post(`/documents/${id}/toggle-archive`)
    },

    // === USER MANAGEMENT ===
    userManagement: {
        getAll: () => secureApi.get('/user-management'),
        getStats: () => secureApi.get('/user-management/stats'),
        getById: (id) => secureApi.get(`/user-management/${id}`),
        create: (data) => secureApi.post('/user-management', data),
        update: (id, data) => secureApi.put(`/user-management/${id}`, data),
        resetPassword: (id) => secureApi.post(`/user-management/${id}/reset-password`),
        toggleStatus: (id) => secureApi.post(`/user-management/${id}/toggle-status`),
        delete: (id) => secureApi.delete(`/user-management/${id}`),
        uploadPhoto: (formData) => {
            const token = authService.getToken();
            return fetch(`${secureApi.baseURL}/upload-photo`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                    // Ne pas définir Content-Type pour FormData
                },
                body: formData
            }).then(async response => {
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP Error ${response.status}`);
                }
                return response.json();
            });
        },

        // Nouvelle fonctionnalité: cartes d'identité professionnelles
        generateProfessionalCard: (id) => {
            const token = authService.getToken();
            return fetch(`${secureApi.baseURL}/user-management/${id}/professional-card`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/pdf'
                }
            }).then(async response => {
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Erreur ${response.status}`);
                }
                return response.blob();
            });
        },

        getUserQR: (id) => secureApi.get(`/user-management/${id}/qr-code`),

        // Export personnel administratif
        exportAdministrativeStaffPdf: () => {
            const token = authService.getToken();
            return fetch(`${secureApi.baseURL}/user-management/export/administrative-staff/pdf`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/pdf'
                }
            }).then(async response => {
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Erreur ${response.status}`);
                }
                return response.blob();
            });
        }
    },

    // === SUPERVISORS & ATTENDANCE ===
    supervisors: {
        // Gestion des affectations (admin uniquement)
        assignToClass: (data) => secureApi.post('/supervisors/assign-to-class', data),
        getAllAssignments: () => secureApi.get('/supervisors/all-assignments'),
        deleteAssignment: (assignmentId) => secureApi.delete(`/supervisors/assignments/${assignmentId}`),
        getSupervisorAssignments: (supervisorId) => secureApi.get(`/supervisors/${supervisorId}/assignments`),
        getAvailableClasses: (supervisorId) => secureApi.get(`/supervisors/${supervisorId}/available-classes`),

        // Scanner QR et présences
        scanQR: (data) => secureApi.post('/supervisors/scan-qr', data),
        getDailyAttendance: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/supervisors/daily-attendance${queryString ? '?' + queryString : ''}`);
        },
        getAttendanceRange: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/supervisors/attendance-range${queryString ? '?' + queryString : ''}`);
        },
        getEntryExitStats: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/supervisors/entry-exit-stats${queryString ? '?' + queryString : ''}`);
        },
        getStudentCurrentStatus: (data) => secureApi.post('/supervisors/student-status', data),
        markAbsentStudents: (data) => secureApi.post('/supervisors/mark-absent-students', data),
        markAllAbsentStudents: (data) => secureApi.post('/supervisors/mark-all-absent-students', data),

        // Génération QR codes
        generateStudentQR: (studentId) => secureApi.get(`/supervisors/generate-qr/${studentId}`),
        generateAllQRs: () => secureApi.get('/supervisors/generate-all-qrs')
    },

    // === TEACHER ATTENDANCE ===
    teacherAttendance: {
        // Scanner QR et présences enseignants
        scanQR: (data) => secureApi.post('/teacher-attendance/scan-qr', data),
        getDailyAttendance: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teacher-attendance/daily-attendance${queryString ? '?' + queryString : ''}`);
        },
        getEntryExitStats: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teacher-attendance/entry-exit-stats${queryString ? '?' + queryString : ''}`);
        },

        // Gestion des QR codes enseignants
        generateQRCode: (data) => secureApi.post('/teacher-attendance/generate-qr', data),
        getTeachersWithQR: () => secureApi.get('/teacher-attendance/teachers-with-qr'),

        // Rapports et statistiques
        getTeacherReport: (teacherId, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teacher-attendance/teacher/${teacherId}/report${queryString ? '?' + queryString : ''}`);
        },
        getDetailedTeacherStats: (teacherId, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teacher-attendance/teacher/${teacherId}/detailed-stats${queryString ? '?' + queryString : ''}`);
        },
        getDayMovements: (teacherId, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teacher-attendance/teacher/${teacherId}/day-movements${queryString ? '?' + queryString : ''}`);
        },
        updateWorkSchedule: (teacherId, data) => secureApi.put(`/teacher-attendance/teacher/${teacherId}/work-schedule`, data)
    },


    // === DEPARTMENTS ===
    departments: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/departments${queryString ? '?' + queryString : ''}`);
        },
        getById: (id) => secureApi.get(`/departments/${id}`),
        create: (data) => secureApi.post('/departments', data),
        update: (id, data) => secureApi.put(`/departments/${id}`, data),
        delete: (id) => secureApi.delete(`/departments/${id}`),
        assignTeacher: (id, data) => secureApi.post(`/departments/${id}/assign-teacher`, data),
        removeTeacher: (id, data) => secureApi.post(`/departments/${id}/remove-teacher`, data),
        setHead: (id, data) => secureApi.post(`/departments/${id}/set-head`, data),
        exportPdf: async () => {
            return secureApi.makeRequest('/departments/export/pdf', {
                method: 'GET'
            });
        }
    },

    // === ACADEMIC PERIODS ===
    academicPeriods: {
        getConfig: () => secureApi.get('/academic-periods/config'),
        updateConfig: (data) => secureApi.post('/academic-periods/config', data),
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/academic-periods${queryString ? '?' + queryString : ''}`);
        },
        create: (data) => secureApi.post('/academic-periods', data),
        update: (id, data) => secureApi.put(`/academic-periods/${id}`, data),
        delete: (id) => secureApi.delete(`/academic-periods/${id}`),
        validateYear: (data) => secureApi.post('/academic-periods/validate-year', data)
    },

    // === EVALUATION CONFIGURATIONS ===
    evaluationConfigs: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/evaluation-configs${queryString ? '?' + queryString : ''}`);
        },
        create: (data) => secureApi.post('/evaluation-configs', data),
        update: (id, data) => secureApi.put(`/evaluation-configs/${id}`, data),
        delete: (id) => secureApi.delete(`/evaluation-configs/${id}`),
        toggleStatus: (id) => secureApi.patch(`/evaluation-configs/${id}/toggle-status`),
        show: (id) => secureApi.get(`/evaluation-configs/${id}`)
    },

    // === GRADING SCALES ===
    gradingScales: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/grading-scales${queryString ? '?' + queryString : ''}`);
        },
        create: (data) => secureApi.post('/grading-scales', data),
        update: (id, data) => secureApi.put(`/grading-scales/${id}`, data),
        delete: (id) => secureApi.delete(`/grading-scales/${id}`),
        show: (id) => secureApi.get(`/grading-scales/${id}`),
        createDefault: (data) => secureApi.post('/grading-scales/create-default', data),
        getGradeForScore: (data) => secureApi.post('/grading-scales/get-grade-for-score', data)
    },

    // === INVENTORY ===
    inventory: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/inventory${queryString ? '?' + queryString : ''}`);
        },
        getDashboard: () => secureApi.get('/inventory/dashboard'),
        getConfig: () => secureApi.get('/inventory/config'),
        getById: (id) => secureApi.get(`/inventory/${id}`),
        create: (data) => secureApi.post('/inventory', data),
        update: (id, data) => secureApi.put(`/inventory/${id}`, data),
        delete: (id) => secureApi.delete(`/inventory/${id}`),
        updateQuantity: (id, data) => secureApi.patch(`/inventory/${id}/quantity`, data),
        getMovements: (id) => secureApi.get(`/inventory/${id}/movements`),
        recordMovement: (id, data) => secureApi.post(`/inventory/${id}/movements`, data),
        exportData: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/inventory/export${queryString ? '?' + queryString : ''}`);
        },
        importData: (formData) => {
            const token = authService.getToken();
            return fetch(`${secureApi.baseURL}/inventory/import`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                    // Ne pas définir Content-Type pour FormData
                },
                body: formData
            }).then(async response => {
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP Error ${response.status}`);
                }
                return response.json();
            });
        },
        getLowStockItems: () => secureApi.get('/inventory/low-stock'),
        testWhatsApp: () => secureApi.post('/inventory/test-whatsapp')
    },

    // === SEQUENCES ===
    sequences: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/sequences${queryString ? '?' + queryString : ''}`);
        },
        getCurrent: () => secureApi.get('/sequences/current'),
        getById: (id) => secureApi.get(`/sequences/${id}`),
        create: (data) => secureApi.post('/sequences', data),
        activate: (id) => secureApi.post(`/sequences/${id}/activate`),
        markCompleted: (id) => secureApi.post(`/sequences/${id}/mark-completed`),
        markIncomplete: (id) => secureApi.post(`/sequences/${id}/mark-incomplete`),
        getStats: (id) => secureApi.get(`/sequences/${id}/stats`)
    },

    // === TRIMESTERS ===
    trimesters: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/trimesters${queryString ? '?' + queryString : ''}`);
        },
        getTeacherTrimesters: () => secureApi.get('/trimesters/teacher'),
        getCurrent: () => secureApi.get('/trimesters/current'),
        getById: (id) => secureApi.get(`/trimesters/${id}`),
        create: (data) => secureApi.post('/trimesters', data),
        update: (id, data) => secureApi.put(`/trimesters/${id}`, data),
        delete: (id, options = {}) => {
            const params = options.force ? '?force=true' : '';
            return secureApi.delete(`/trimesters/${id}${params}`);
        },
        activate: (id) => secureApi.post(`/trimesters/${id}/activate`),
        getStats: (id) => secureApi.get(`/trimesters/${id}/stats`)
    },

    // === EVALUATIONS ===
    evaluations: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/evaluations${queryString ? '?' + queryString : ''}`);
        },
        getById: (id) => secureApi.get(`/evaluations/${id}`),
        create: (data) => secureApi.post('/evaluations', data),
        update: (id, data) => secureApi.put(`/evaluations/${id}`, data),
        delete: (id) => secureApi.delete(`/evaluations/${id}`),
        getTypes: () => secureApi.get('/evaluations/types'),
        getStats: (id) => secureApi.get(`/evaluations/${id}/stats`),
        getDashboard: () => secureApi.get('/evaluations/dashboard')
    },

    // === GRADES ===
    grades: {
        getByEvaluation: (evaluationId) => secureApi.get(`/grades/evaluation/${evaluationId}`),
        save: (data) => secureApi.post('/grades', data),
        saveBulk: (data) => secureApi.post('/grades/bulk', data),
        delete: (id) => secureApi.delete(`/grades/${id}`),
        getEvaluationStats: (evaluationId) => secureApi.get(`/grades/evaluation/${evaluationId}/stats`)
    },

    // === GEOLOCATION ZONES ===
    geolocationZones: {
        // Pour tous les utilisateurs authentifiés
        getEnabledZones: () => secureApi.get('/geolocation-zones/enabled'),
        validatePosition: (data) => secureApi.post('/geolocation-zones/validate-position', data),
        
        // Pour les admins seulement
        getAll: () => secureApi.get('/geolocation-zones'),
        getById: (id) => secureApi.get(`/geolocation-zones/${id}`),
        create: (data) => secureApi.post('/geolocation-zones', data),
        update: (id, data) => secureApi.put(`/geolocation-zones/${id}`, data),
        delete: (id) => secureApi.delete(`/geolocation-zones/${id}`),
        toggleStatus: (id) => secureApi.post(`/geolocation-zones/${id}/toggle-status`)
    },

    // === NOTIFICATIONS (Admin) ===
    admin: {
        notifications: {
            index: async () => {
                const token = authService.getToken();
                const response = await fetch(`${host}/api/notifications`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                return await response.json();
            },
            store: async (data) => {
                const token = authService.getToken();

                // Si data est un FormData, ne pas définir Content-Type (le navigateur le fera automatiquement)
                const isFormData = data instanceof FormData;

                const headers = {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                };

                // Ajouter Content-Type uniquement si ce n'est pas du FormData
                if (!isFormData) {
                    headers['Content-Type'] = 'application/json';
                }

                const response = await fetch(`${host}/api/notifications`, {
                    method: 'POST',
                    headers: headers,
                    body: isFormData ? data : JSON.stringify(data)
                });
                return await response.json();
            },
            getParents: async () => {
                const token = authService.getToken();
                const response = await fetch(`${host}/api/notifications/parents`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                return await response.json();
            },
            getStudents: async () => {
                const token = authService.getToken();
                const response = await fetch(`${host}/api/notifications/students`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                return await response.json();
            },
            getClasses: async () => {
                const token = authService.getToken();
                const response = await fetch(`${host}/api/notifications/classes`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                return await response.json();
            },
            stats: async () => {
                const token = authService.getToken();
                const response = await fetch(`${host}/api/notifications/stats`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                return await response.json();
            },
            destroy: async (id) => {
                const token = authService.getToken();
                const response = await fetch(`${host}/api/notifications/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                return await response.json();
            }
        }
    },

    // === MODULE PARENT ===
    parent: {
        // Connexion (pas d'auth requise)
        login: async (data) => {
            const response = await fetch(`${host}/api/parent/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        },
        
        // Routes protégées (avec token parent)
        dashboard: async () => {
            const token = localStorage.getItem('parentToken');
            const response = await fetch(`${host}/api/parent/dashboard`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            return await response.json();
        },
        getChildren: async () => {
            const token = localStorage.getItem('parentToken');
            const response = await fetch(`${host}/api/parent/children`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            return await response.json();
        },
        getNotifications: async () => {
            const token = localStorage.getItem('parentToken');
            const response = await fetch(`${host}/api/parent/notifications`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            return await response.json();
        },
        markNotificationAsRead: async (id) => {
            const token = localStorage.getItem('parentToken');
            const response = await fetch(`${host}/api/parent/notifications/${id}/read`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            });
            return await response.json();
        },
        getCalendarEvents: async () => {
            const token = localStorage.getItem('parentToken');
            const response = await fetch(`${host}/api/parent/calendar/events`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            return await response.json();
        },
        getSchedules: async () => {
            const token = localStorage.getItem('parentToken');
            const response = await fetch(`${host}/api/parent/schedules`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            return await response.json();
        }
    }
};

/**
 * Wrapper de migration pour compatibilité avec l'ancien système
 * Permet une migration progressive des composants
 */
export const createMigrationWrapper = (oldEndpoint, newEndpoint) => {
    return async (...args) => {
        try {
            // Essayer d'abord le nouveau système
            return await newEndpoint(...args);
        } catch (error) {
            console.warn(`Nouveau endpoint échoué, utilisation de l'ancien système:`, error);
            // Fallback vers l'ancien système si nécessaire
            return await oldEndpoint(...args);
        }
    };
};

/**
 * Utilitaires de migration
 */
export const migrationUtils = {
    // Nettoyer l'ancien stockage
    cleanOldStorage: () => {
        try {
            sessionStorage.removeItem('user');
            sessionStorage.removeItem('stat');
            sessionStorage.removeItem('classId');
            localStorage.removeItem('user');
            localStorage.removeItem('stat');
        } catch (error) {
            console.error('Erreur lors du nettoyage du stockage:', error);
        }
    },

    // Migrer les données utilisateur si présentes
    migrateUserData: () => {
        try {
            const oldUser = sessionStorage.getItem('user');
            const oldStatus = sessionStorage.getItem('stat');

            if (oldUser && oldStatus) {
                console.log('Anciennes données utilisateur détectées, migration recommandée');
                // Ici vous pourriez implémenter une logique de migration
                return { token: oldUser, status: oldStatus };
            }
        } catch (error) {
            console.error('Erreur lors de la migration des données:', error);
        }
        return null;
    },

    // Vérifier si une migration est nécessaire
    needsMigration: () => {
        const hasOldData = !!(sessionStorage.getItem('user') || localStorage.getItem('user'));
        const hasNewData = !!authService.getToken();

        return hasOldData && !hasNewData;
    },

    // === EVALUATION CONFIGS ===
    evaluationConfigs: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/evaluation-configs${queryString ? '?' + queryString : ''}`);
        },
        getById: (id) => secureApi.get(`/evaluation-configs/${id}`),
        create: (data) => secureApi.post('/evaluation-configs', data),
        update: (id, data) => secureApi.put(`/evaluation-configs/${id}`, data),
        delete: (id) => secureApi.delete(`/evaluation-configs/${id}`),
        getByLevel: (levelId) => secureApi.get(`/evaluation-configs/level/${levelId}`),
        toggleStatus: (id) => secureApi.post(`/evaluation-configs/${id}/toggle-status`)
    },

    // === STUDENT ATTENDANCE (Manual System) === 
    attendance: {
        saveManualAttendance: (data) => secureApi.post('/attendance/manual', data),
        getDailyAttendanceByClass: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/attendance/class-daily${queryString ? '?' + queryString : ''}`);
        },
        getClassAttendanceStats: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/attendance/class-stats${queryString ? '?' + queryString : ''}`);
        }
    },

    // === ADMIN DASHBOARD ===
    admin: {
        dashboard: () => secureApi.get('/admin/dashboard'),
        getStatsByPeriod: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/admin/dashboard/stats-by-period${queryString ? '?' + queryString : ''}`);
        }
    },

    // === CONVENIENCE METHODS ===
    getTeachers: (params = {}) => secureApiEndpoints.teachers.getAll(params),
    getSubjects: (params = {}) => secureApiEndpoints.subjects.getAll(params)
};

export { secureApi };
export default secureApiEndpoints;
