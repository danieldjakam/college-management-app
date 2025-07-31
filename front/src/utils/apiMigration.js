/**
 * Migration des anciens endpoints API vers le nouveau système d'authentification JWT
 * Ce fichier remplace progressivement l'ancien système basé sur sessionStorage.user
 */

import { authService } from '../services/authService';
import { host } from './fetch';

/**
 * Nouveau service API qui utilise automatiquement les tokens JWT
 */
class SecureApiService {
    constructor() {
        this.baseURL = `${host}/api`;
    }

    // Fonction de requête sécurisée personnalisée
    async makeRequest(endpoint, options = {}) {
        const fullUrl = endpoint.startsWith('http') ? endpoint : `${this.baseURL}${endpoint}`;
        
        const token = authService.getToken();
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        try {
            const response = await fetch(fullUrl, {
                ...options,
                headers
            });

            // Gestion des erreurs HTTP
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                
                // Gestion spéciale pour les erreurs 401
                if (response.status === 401) {
                    authService.removeToken();
                    window.dispatchEvent(new CustomEvent('auth:unauthorized'));
                    throw new Error('Session expirée. Veuillez vous reconnecter.');
                }
                
                throw new Error(
                    errorData.message || 
                    errorData.error || 
                    `Erreur HTTP ${response.status}: ${response.statusText}`
                );
            }

            return await response.json();
        } catch (error) {
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                throw new Error('Impossible de se connecter au serveur. Vérifiez votre connexion.');
            }
            throw error;
        }
    }

    // Méthodes GET
    async get(endpoint, options = {}) {
        return await this.makeRequest(endpoint, {
            method: 'GET',
            ...options
        });
    }

    // Méthodes POST
    async post(endpoint, data = null, options = {}) {
        return await this.makeRequest(endpoint, {
            method: 'POST',
            body: data ? JSON.stringify(data) : null,
            ...options
        });
    }

    // Méthodes PUT
    async put(endpoint, data = null, options = {}) {
        return await this.makeRequest(endpoint, {
            method: 'PUT',
            body: data ? JSON.stringify(data) : null,
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
            body: data ? JSON.stringify(data) : null,
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
    // === AUTHENTICATION ===
    auth: {
        login: (credentials) => authService.login(credentials),
        logout: () => authService.logout(),
        refresh: () => authService.refreshToken(),
        me: () => authService.getCurrentUser()
    },

    // === USERS ===
    users: {
        getAll: () => secureApi.get('/users'),
        getById: (id) => secureApi.get(`/users/${id}`),
        create: (data) => secureApi.post('/users', data),
        update: (id, data) => secureApi.put(`/users/${id}`, data),
        delete: (id) => secureApi.delete(`/users/${id}`),
        getProfile: () => secureApi.get('/users/profile'),
        updateProfile: (data) => secureApi.put('/users/profile', data)
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
        getStats: (id, params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/teachers/${id}/stats${queryString ? '?' + queryString : ''}`);
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

    // === STUDENTS ===
    students: {
        getAll: () => secureApi.get('/students'),
        getByClass: (classId) => secureApi.get(`/students/class/${classId}`),
        getByClassSeries: (seriesId) => secureApi.get(`/students/class-series/${seriesId}`),
        getById: (id) => secureApi.get(`/students/${id}`),
        create: (data) => secureApi.post('/students', data),
        createWithPhoto: (formData) => {
            const token = authService.getToken();
            return fetch(`${secureApi.baseURL}/students`, {
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
        importCsv: (formData) => {
            const token = authService.getToken();
            return fetch(`${secureApi.baseURL}/students/import-csv`, {
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
        getOrdered: (classId) => secureApi.get(`/students/class/${classId}/ordered`),
        reorder: (data) => secureApi.post('/students/reorder', data),
        sortAlphabetically: (seriesId, data) => secureApi.post(`/students/class-series/${seriesId}/sort-alphabetically`, data)
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
        create: (data) => secureApi.post('/school-classes', data),
        update: (id, data) => secureApi.put(`/school-classes/${id}`, data),
        delete: (id) => secureApi.delete(`/school-classes/${id}`),
        getDashboard: () => secureApi.get('/school-classes/dashboard'),
        toggleStatus: (id) => secureApi.post(`/school-classes/${id}/toggle-status`),
        configurePayments: (id, data) => secureApi.post(`/school-classes/${id}/configure-payments`, data),
        getByLevel: (levelId) => secureApi.get(`/school-classes?level_id=${levelId}`),
        getBySection: (sectionId) => secureApi.get(`/school-classes?section_id=${sectionId}`)
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
        getFinancialReport: (period) => secureApi.get(`/reports/financial/${period}`)
    },

    // === SEARCH ===
    search: {
        global: (query) => secureApi.get(`/search?q=${encodeURIComponent(query)}`),
        students: (query) => secureApi.get(`/search/students?q=${encodeURIComponent(query)}`),
        teachers: (query) => secureApi.get(`/search/teachers?q=${encodeURIComponent(query)}`)
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
        create: (data) => secureApi.post('/school-years', data),
        update: (id, data) => secureApi.put(`/school-years/${id}`, data),
        setCurrent: (id) => secureApi.post(`/school-years/${id}/set-current`),
        getActiveYears: () => secureApi.get('/school-years/active'),
        getUserWorkingYear: () => secureApi.get('/school-years/user-working-year'),
        setUserWorkingYear: (yearId) => secureApi.post('/school-years/set-user-working-year', { school_year_id: yearId })
    },

    // === PAYMENTS ===
    payments: {
        getStudentInfo: (studentId) => secureApi.get(`/payments/student/${studentId}/info`),
        getStudentHistory: (studentId) => secureApi.get(`/payments/student/${studentId}/history`),
        create: (data) => secureApi.post('/payments', data),
        generateReceipt: (paymentId) => secureApi.get(`/payments/${paymentId}/receipt`),
        getStats: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/payments/stats${queryString ? '?' + queryString : ''}`);
        }
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

    // === SERIES SUBJECTS ===
    seriesSubjects: {
        getAll: (params = {}) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/series-subjects${queryString ? '?' + queryString : ''}`);
        },
        getByClass: (classId) => secureApi.get(`/series-subjects/class/${classId}`),
        create: (data) => secureApi.post('/series-subjects', data),
        update: (id, data) => secureApi.put(`/series-subjects/${id}`, data),
        delete: (id) => secureApi.delete(`/series-subjects/${id}`),
        toggleStatus: (id) => secureApi.post(`/series-subjects/${id}/toggle-status`),
        bulkConfigure: (classId, data) => secureApi.post(`/series-subjects/class/${classId}/bulk-configure`, data)
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
        bulkAssign: (teacherId, data) => secureApi.post(`/teacher-assignments/teacher/${teacherId}/bulk-assign`, data)
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
        approve: (id) => secureApi.post(`/needs/${id}/approve`),
        reject: (id, data) => secureApi.post(`/needs/${id}/reject`, data),
        getStatistics: () => secureApi.get('/needs/statistics/summary'),
        testWhatsApp: () => secureApi.post('/needs/test-whatsapp')
    },

    // === REPORTS ===
    reports: {
        getInsolvableReport: (params) => {
            const queryString = new URLSearchParams(params).toString();
            return secureApi.get(`/reports/insolvable?${queryString}`);
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
        }
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
        uploadPhoto: (formData) => secureApi.makeRequest('/upload-photo', {
            method: 'POST',
            headers: {}, // Pas de Content-Type pour FormData
            body: formData
        })
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

    // === CONVENIENCE METHODS ===
    getTeachers: (params = {}) => secureApiEndpoints.teachers.getAll(params),
    getSubjects: (params = {}) => secureApiEndpoints.subjects.getAll(params)
};

export { secureApi };
export default secureApiEndpoints;