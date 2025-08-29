import React, { useState, useEffect, useMemo } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Table,
    Modal,
    Form,
    Alert,
    Spinner,
    Badge,
    ButtonGroup,
    Dropdown
} from 'react-bootstrap';
import {
    PersonPlus,
    People,
    PersonFill,
    PersonX,
    Key,
    Trash,
    PencilSquare,
    EyeFill,
    PersonCheck,
    CreditCard2Back,
    QrCode,
    PrinterFill,
    FileEarmarkPdf,
    Upload
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../utils/apiMigration';
import { host } from '../utils/fetch';
import Swal from 'sweetalert2';
import PhotoCapture from '../components/PhotoCapture';

const UserManagement = () => {
    const [users, setUsers] = useState([]);
    const [filteredUsers, setFilteredUsers] = useState([]);
    const [stats, setStats] = useState({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [showModal, setShowModal] = useState(false);
    const [modalMode, setModalMode] = useState('create'); // create, edit, view
    const [selectedUser, setSelectedUser] = useState(null);
    const [showPhotoCapture, setShowPhotoCapture] = useState(false);
    const [showQRModal, setShowQRModal] = useState(false);
    const [selectedUserQR, setSelectedUserQR] = useState(null);
    
    // États pour la sélection multiple
    const [selectedUsers, setSelectedUsers] = useState([]);
    const [isGeneratingMultipleBadges, setIsGeneratingMultipleBadges] = useState(false);
    
    // États pour la recherche et les filtres
    const [searchTerm, setSearchTerm] = useState('');
    const [roleFilter, setRoleFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');

    const [formData, setFormData] = useState({
        name: '',
        email: '',
        contact: '',
        photo: '',
        role: 'accountant',
        qualification: '',
        is_active: true,
        generate_password: true
    });

    const roleLabels = useMemo(() => ({
        admin: 'Administrateur',
        surveillant_general: 'Surveillant Général',
        general_accountant: 'Comptable Général',
        comptable_superieur: 'Comptable Supérieur',
        comptable: 'Comptable',
        secretaire: 'Secrétaire',
        enseignant: 'Enseignant',
        teacher: 'Enseignant',
        accountant: 'Comptable',
        principal: 'Principal',
        user: 'Utilisateur',
        bibliothecaire: 'Bibliothécaire',
        responsable_pedagogique: 'Responsable Pédagogique',
        dean_of_studies: 'Dean of Studies',
        censeur_esg: 'Censeur ESG',
        censeur: 'Censeur',
        surveillant_secteur: 'Surveillant de Secteur',
        caissiere: 'Caissière',
        chef_travaux: 'Chef des Travaux',
        chef_securite: 'Chef de Sécurité',
        reprographe: 'Reprographe',
    }), []);

    const roleColors = useMemo(() => ({
        admin: 'danger',
        surveillant_general: 'primary',
        general_accountant: 'warning',
        comptable_superieur: 'dark',
        comptable: 'success',
        secretaire: 'info',
        enseignant: 'warning',
        teacher: 'warning',
        accountant: 'success',
        principal: 'danger',
        user: 'info',
        bibliothecaire: 'secondary',
        responsable_pedagogique: 'primary',
        dean_of_studies: 'secondary',
        censeur_esg: 'info',
        censeur: 'info',
        surveillant_secteur: 'primary',
        caissiere: 'warning',
        chef_travaux: 'dark',
        chef_securite: 'danger',
        reprographe: 'light',
    }), []);

    // Liste complète des rôles du personnel (incluant tous les nouveaux rôles)
    const staffRoles = useMemo(() => [
        'teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire', 'principal', 'user', 'bibliothecaire', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'chef_travaux', 'chef_securite', 'reprographe'
    ], []);

    const getFieldLabel = (fieldName) => {
        const fieldLabels = {
            name: 'Nom complet',
            email: 'Adresse e-mail',
            contact: 'Numéro de téléphone',
            role: 'Rôle',
            photo: 'Photo',
            password: 'Mot de passe',
            username: 'Nom d\'utilisateur'
        };
        return fieldLabels[fieldName] || fieldName;
    };

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const [usersRes, statsRes] = await Promise.all([
                secureApiEndpoints.userManagement.getAll(),
                secureApiEndpoints.userManagement.getStats()
            ]);

            if (usersRes.success) {
                setUsers(usersRes.data);
                setFilteredUsers(usersRes.data);
            }
            
            if (statsRes.success) {
                setStats(statsRes.data);
            }
        } catch (error) {
            setError('Erreur lors du chargement des données');
            console.error('Error loading data:', error);
        } finally {
            setLoading(false);
        }
    };

    // Fonction de filtrage
    useEffect(() => {
        let filtered = users;

        // Filtrage par terme de recherche
        if (searchTerm) {
            filtered = filtered.filter(user => 
                user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                user.contact?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                roleLabels[user.role]?.toLowerCase().includes(searchTerm.toLowerCase())
            );
        }

        // Filtrage par rôle
        if (roleFilter !== 'all') {
            filtered = filtered.filter(user => user.role === roleFilter);
        }

        // Filtrage par statut
        if (statusFilter !== 'all') {
            filtered = filtered.filter(user => 
                statusFilter === 'active' ? user.is_active : !user.is_active
            );
        }

        setFilteredUsers(filtered);
    }, [users, searchTerm, roleFilter, statusFilter, roleLabels]);

    // Fonction pour obtenir l'avatar de l'utilisateur
    const getUserAvatar = (user, size = 40) => {
        const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        
        const InitialsAvatar = () => (
            <div style={{
                width: size,
                height: size,
                borderRadius: '50%',
                backgroundColor: roleColors[user.role] === 'warning' ? '#ffc107' : 
                               roleColors[user.role] === 'success' ? '#28a745' :
                               roleColors[user.role] === 'primary' ? '#007bff' : '#6c757d',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: size * 0.4,
                fontWeight: 'bold',
                color: 'white'
            }}>
                {initials}
            </div>
        );

        if (user.photo) {
            return (
                <img 
                    src={user.photo} 
                    alt={user.name}
                    style={{
                        width: size,
                        height: size,
                        borderRadius: '50%',
                        objectFit: 'cover',
                        border: '2px solid #fff',
                        boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                    }}
                    onError={(e) => {
                        console.error('Error loading image:', user.photo);
                        e.target.style.display = 'none';
                        // Créer et insérer l'avatar avec initiales
                        const parent = e.target.parentNode;
                        if (parent && !parent.querySelector('.initials-avatar')) {
                            const initialsDiv = document.createElement('div');
                            initialsDiv.className = 'initials-avatar';
                            initialsDiv.style.cssText = `
                                width: ${size}px;
                                height: ${size}px;
                                border-radius: 50%;
                                background-color: ${roleColors[user.role] === 'warning' ? '#ffc107' : 
                                                   roleColors[user.role] === 'success' ? '#28a745' :
                                                   roleColors[user.role] === 'primary' ? '#007bff' : '#6c757d'};
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: ${size * 0.4}px;
                                font-weight: bold;
                                color: white;
                            `;
                            initialsDiv.textContent = initials;
                            parent.appendChild(initialsDiv);
                        }
                    }}
                />
            );
        } else {
            return <InitialsAvatar />;
        }
    };

    // Fonction pour gérer la sélection de photo
    const handlePhotoSelected = (photoUrl) => {
        setFormData({...formData, photo: photoUrl});
        setShowPhotoCapture(false);
    };

    const handleShowModal = (mode, user = null) => {
        setModalMode(mode);
        setSelectedUser(user);
        
        if (mode === 'create') {
            setFormData({
                name: '',
                email: '',
                contact: '',
                photo: '',
                role: 'accountant',
                qualification: '',
                is_active: true,
                generate_password: true
            });
        } else if (mode === 'edit' && user) {
            setFormData({
                name: user.name,
                email: user.email,
                contact: user.contact || '',
                photo: user.photo || '',
                role: user.role,
                qualification: user.qualification || '',
                is_active: user.is_active,
                generate_password: false
            });
        }
        
        setShowModal(true);
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setSelectedUser(null);
        setError('');
    };

    const validateForm = () => {
        const errors = {};
        
        if (!formData.name.trim()) {
            errors.name = 'Le nom complet est obligatoire';
        } else if (formData.name.length > 255) {
            errors.name = 'Le nom ne peut pas dépasser 255 caractères';
        }
        
        if (!formData.email.trim()) {
            errors.email = 'L\'adresse e-mail est obligatoire';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
            errors.email = 'L\'adresse e-mail doit être valide';
        }
        
        if (formData.contact && formData.contact.length > 20) {
            errors.contact = 'Le numéro de téléphone ne peut pas dépasser 20 caractères';
        }
        
        if (!formData.role) {
            errors.role = 'Le rôle est obligatoire';
        }
        
        return errors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        // Validation côté client
        const validationErrors = validateForm();
        if (Object.keys(validationErrors).length > 0) {
            const errorMessages = Object.entries(validationErrors).map(([field, message]) => {
                const fieldLabel = getFieldLabel(field);
                return `<strong>${fieldLabel}:</strong> ${message}`;
            }).join('<br>');
            
            await Swal.fire({
                title: 'Erreurs de validation',
                html: `
                    <div class="text-left">
                        <p>Veuillez corriger les erreurs suivantes :</p>
                        <div class="alert alert-danger mt-3" style="text-align: left;">
                            ${errorMessages}
                        </div>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'Corriger'
            });
            return;
        }
        
        setLoading(true);
        
        try {
            let response;
            
            if (modalMode === 'create') {
                response = await secureApiEndpoints.userManagement.create(formData);
            } else if (modalMode === 'edit') {
                response = await secureApiEndpoints.userManagement.update(selectedUser.id, formData);
            }

            if (response.success) {
                if (modalMode === 'create' && response.data.password) {
                    await Swal.fire({
                        title: 'Utilisateur créé !',
                        html: `
                            <p>L'utilisateur <strong>${response.data.user.name}</strong> a été créé avec succès.</p>
                            <div class="alert alert-warning mt-3">
                                <strong>Mot de passe généré :</strong><br>
                                <code style="font-size: 1.2em;">${response.data.password}</code><br>
                                <small>Notez ce mot de passe car il ne sera plus affiché.</small>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Compris'
                    });
                } else {
                    Swal.fire('Succès', response.message, 'success');
                }
                
                handleCloseModal();
                await loadData();
            } else {
                // Gérer les erreurs de validation détaillées
                if (response.errors && Object.keys(response.errors).length > 0) {
                    const errorMessages = Object.entries(response.errors).map(([field, messages]) => {
                        const fieldLabel = getFieldLabel(field);
                        const message = Array.isArray(messages) ? messages[0] : messages;
                        return `<strong>${fieldLabel}:</strong> ${message}`;
                    }).join('<br>');
                    
                    await Swal.fire({
                        title: 'Erreurs de validation',
                        html: `
                            <div class="text-left">
                                <p>Veuillez corriger les erreurs suivantes :</p>
                                <div class="alert alert-danger mt-3" style="text-align: left;">
                                    ${errorMessages}
                                </div>
                            </div>
                        `,
                        icon: 'error',
                        confirmButtonText: 'Corriger'
                    });
                } else {
                    // Pas d'erreurs de validation spécifiques, afficher le message d'erreur général
                    await Swal.fire({
                        title: 'Erreur',
                        text: response.message || 'Erreur lors de la sauvegarde',
                        icon: 'error',
                        confirmButtonText: 'Fermer'
                    });
                }
                
                // Ne pas utiliser setError ici car on affiche déjà avec SweetAlert2
            }
        } catch (error) {
            console.error('Error saving user:', error);
            
            // Gérer les différents types d'erreurs
            let errorMessage = 'Erreur lors de la sauvegarde';
            let errorDetails = '';
            
            if (error.response) {
                // Erreur de réponse du serveur
                const { status, data } = error.response;
                
                if (status === 422 && data.errors && Object.keys(data.errors).length > 0) {
                    // Erreurs de validation Laravel
                    const errorMessages = Object.entries(data.errors).map(([field, messages]) => {
                        const fieldLabel = getFieldLabel(field);
                        const message = Array.isArray(messages) ? messages[0] : messages;
                        return `<strong>${fieldLabel}:</strong> ${message}`;
                    }).join('<br>');
                    
                    await Swal.fire({
                        title: 'Erreurs de validation',
                        html: `
                            <div class="text-left">
                                <p>Veuillez corriger les erreurs suivantes :</p>
                                <div class="alert alert-danger mt-3" style="text-align: left;">
                                    ${errorMessages}
                                </div>
                            </div>
                        `,
                        icon: 'error',
                        confirmButtonText: 'Corriger'
                    });
                    return; // Ne pas afficher l'erreur générale
                } else if (status === 409) {
                    errorMessage = 'Conflit de données';
                    errorDetails = data.message || 'Un utilisateur avec ces informations existe déjà.';
                } else if (status === 500) {
                    errorMessage = 'Erreur serveur';
                    errorDetails = 'Une erreur interne s\'est produite. Veuillez réessayer plus tard.';
                } else {
                    errorMessage = data.message || `Erreur ${status}`;
                    errorDetails = data.error || 'Une erreur inattendue s\'est produite.';
                }
            } else if (error.request) {
                // Erreur de réseau
                errorMessage = 'Erreur de connexion';
                errorDetails = 'Impossible de joindre le serveur. Vérifiez votre connexion internet.';
            } else {
                // Autre erreur
                errorMessage = 'Erreur inattendue';
                errorDetails = error.message || 'Une erreur inattendue s\'est produite.';
            }
            
            // Afficher l'erreur avec SweetAlert2 pour plus de visibilité
            await Swal.fire({
                title: errorMessage,
                text: errorDetails,
                icon: 'error',
                confirmButtonText: 'Fermer'
            });
            
            setError(errorMessage);
        } finally {
            setLoading(false);
        }
    };

    const handleResetPassword = async (user) => {
        const result = await Swal.fire({
            title: 'Réinitialiser le mot de passe ?',
            text: `Voulez-vous vraiment réinitialiser le mot de passe de ${user.name} ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, réinitialiser',
            cancelButtonText: 'Annuler'
        });

        if (result.isConfirmed) {
            try {
                const response = await secureApiEndpoints.userManagement.resetPassword(user.id);
                
                if (response.success) {
                    await Swal.fire({
                        title: 'Mot de passe réinitialisé !',
                        html: `
                            <p>Le mot de passe de <strong>${user.name}</strong> a été réinitialisé.</p>
                            <div class="alert alert-info mt-3">
                                <strong>Nouveau mot de passe :</strong><br>
                                <code style="font-size: 1.2em;">${response.data.new_password}</code><br>
                                <small>Notez ce mot de passe car il ne sera plus affiché.</small>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Compris'
                    });
                } else {
                    Swal.fire('Erreur', response.message, 'error');
                }
            } catch (error) {
                Swal.fire('Erreur', 'Erreur lors de la réinitialisation du mot de passe', 'error');
            }
        }
    };

    const handleToggleStatus = async (user) => {
        try {
            const response = await secureApiEndpoints.userManagement.toggleStatus(user.id);
            
            if (response.success) {
                Swal.fire('Succès', response.message, 'success');
                await loadData();
            } else {
                Swal.fire('Erreur', response.message, 'error');
            }
        } catch (error) {
            Swal.fire('Erreur', 'Erreur lors du changement de statut', 'error');
        }
    };

    const handleDelete = async (user) => {
        const result = await Swal.fire({
            title: 'Supprimer l\'utilisateur ?',
            text: `Voulez-vous vraiment supprimer ${user.name} ? Cette action est irréversible.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        });

        if (result.isConfirmed) {
            try {
                const response = await secureApiEndpoints.userManagement.delete(user.id);
                
                if (response.success) {
                    Swal.fire('Supprimé !', response.message, 'success');
                    await loadData();
                } else {
                    Swal.fire('Erreur', response.message, 'error');
                }
            } catch (error) {
                Swal.fire('Erreur', 'Erreur lors de la suppression', 'error');
            }
        }
    };

    const handleGenerateProfessionalCard = async (user) => {
        try {
            setLoading(true);
            
            const pdfBlob = await secureApiEndpoints.userManagement.generateProfessionalCard(user.id);
            
            // Créer un lien de téléchargement
            const url = window.URL.createObjectURL(pdfBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `carte_professionnelle_${user.name.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            
            await Swal.fire({
                title: 'Carte générée !',
                text: `La carte d'identité professionnelle de ${user.name} a été générée et téléchargée.`,
                icon: 'success',
                confirmButtonText: 'OK'
            });
            
        } catch (error) {
            console.error('Error generating professional card:', error);
            await Swal.fire({
                title: 'Erreur',
                text: 'Erreur lors de la génération de la carte professionnelle: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Fermer'
            });
        } finally {
            setLoading(false);
        }
    };


    const handlePrintBadge = async (user) => {
        try {
            setLoading(true);
            
            // Vérifier que l'utilisateur est un membre du personnel
            if (!staffRoles.includes(user.role)) {
                await Swal.fire({
                    title: 'Information',
                    text: 'L\'impression de badges QR n\'est disponible que pour les membres du personnel (enseignants, comptables, surveillants, etc.)',
                    icon: 'info',
                    confirmButtonText: 'Compris'
                });
                return;
            }

            // Utiliser l'API du StaffAttendanceController pour générer le badge
            const response = await secureApiEndpoints.staff.downloadBadgePDF({
                user_id: user.id
            });

            // Télécharger le PDF
            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `badge_${user.name.replace(/\s+/g, '_')}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);
            
            await Swal.fire({
                title: 'Badge généré !',
                text: `Badge PDF téléchargé pour ${user.name}`,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            
        } catch (error) {
            console.error('Error generating badge:', error);
            await Swal.fire({
                title: 'Erreur',
                text: 'Erreur lors de la génération du badge: ' + (error.message || 'Une erreur inattendue s\'est produite'),
                icon: 'error',
                confirmButtonText: 'Fermer'
            });
        } finally {
            setLoading(false);
        }
    };

    // Fonctions pour la sélection multiple
    const handleSelectUser = (userId, isSelected) => {
        if (isSelected) {
            setSelectedUsers(prev => [...prev, userId]);
        } else {
            setSelectedUsers(prev => prev.filter(id => id !== userId));
        }
    };

    const handleSelectAll = () => {
        const eligibleUsers = filteredUsers.filter(user => staffRoles.includes(user.role));
        
        if (selectedUsers.length === eligibleUsers.length) {
            // Tout désélectionner
            setSelectedUsers([]);
        } else {
            // Tout sélectionner
            setSelectedUsers(eligibleUsers.map(user => user.id));
        }
    };

    const handlePrintMultipleBadges = async () => {
        if (selectedUsers.length === 0) {
            await Swal.fire({
                title: 'Aucune sélection',
                text: 'Veuillez sélectionner au moins un utilisateur pour imprimer les badges.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        const result = await Swal.fire({
            title: 'Confirmer l\'impression',
            text: `Voulez-vous imprimer les badges de ${selectedUsers.length} utilisateur(s) sélectionné(s) ?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, imprimer',
            cancelButtonText: 'Annuler'
        });

        if (!result.isConfirmed) return;

        try {
            setIsGeneratingMultipleBadges(true);

            const response = await secureApiEndpoints.staff.generateMultipleBadges(selectedUsers);

            // Télécharger le PDF
            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `badges_multiples_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);
            
            await Swal.fire({
                title: 'Badges générés !',
                text: `PDF avec ${selectedUsers.length} badge(s) téléchargé avec succès.`,
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });

            // Réinitialiser la sélection
            setSelectedUsers([]);
            
        } catch (error) {
            console.error('Error generating multiple badges:', error);
            await Swal.fire({
                title: 'Erreur',
                text: 'Erreur lors de la génération des badges: ' + (error.message || 'Une erreur inattendue s\'est produite'),
                icon: 'error',
                confirmButtonText: 'Fermer'
            });
        } finally {
            setIsGeneratingMultipleBadges(false);
        }
    };

    const handleImportAdministrativeStaff = async () => {
        await Swal.fire({
            title: 'Fonctionnalité non disponible',
            text: 'L\'import du personnel administratif sera disponible prochainement.',
            icon: 'info',
            confirmButtonText: 'OK'
        });
    };

    const handleExportAdministrativeStaff = async () => {
        const result = await Swal.fire({
            title: 'Confirmer l\'export',
            text: 'Voulez-vous exporter la liste du personnel administratif en PDF ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, exporter',
            cancelButtonText: 'Annuler'
        });

        if (!result.isConfirmed) return;

        try {
            const response = await secureApiEndpoints.userManagement.exportAdministrativeStaffPdf();

            // Télécharger le PDF
            const blob = await response;
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `Fichier_Personnel_Administratif_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);
            
            await Swal.fire({
                title: 'Export réussi !',
                text: 'Le fichier du personnel administratif a été téléchargé avec succès.',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
            
        } catch (error) {
            console.error('Error exporting administrative staff:', error);
            await Swal.fire({
                title: 'Erreur',
                text: 'Erreur lors de l\'export: ' + (error.message || 'Une erreur inattendue s\'est produite'),
                icon: 'error',
                confirmButtonText: 'Fermer'
            });
        }
    };

    const renderStatsCards = () => (
        <Row className="mb-4">
            <Col md={3}>
                <Card className="text-center">
                    <Card.Body>
                        <People size={40} className="text-primary mb-2" />
                        <h3>{stats.total_users}</h3>
                        <p className="text-muted mb-0">Total Utilisateurs</p>
                    </Card.Body>
                </Card>
            </Col>
            <Col md={3}>
                <Card className="text-center">
                    <Card.Body>
                        <PersonCheck size={40} className="text-success mb-2" />
                        <h3>{stats.active_users}</h3>
                        <p className="text-muted mb-0">Actifs</p>
                    </Card.Body>
                </Card>
            </Col>
            <Col md={3}>
                <Card className="text-center">
                    <Card.Body>
                        <PersonX size={40} className="text-warning mb-2" />
                        <h3>{stats.inactive_users}</h3>
                        <p className="text-muted mb-0">Inactifs</p>
                    </Card.Body>
                </Card>
            </Col>
            <Col md={3}>
                <Card className="text-center">
                    <Card.Body>
                        <PersonFill size={40} className="text-info mb-2" />
                        <h3>{stats.by_role?.teacher || 0}</h3>
                        <p className="text-muted mb-0">Enseignants</p>
                    </Card.Body>
                </Card>
            </Col>
        </Row>
    );

    const renderUserModal = () => (
        <Modal show={showModal} onHide={handleCloseModal} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>
                    {modalMode === 'create' && 'Créer un utilisateur'}
                    {modalMode === 'edit' && 'Modifier l\'utilisateur'}
                    {modalMode === 'view' && 'Détails de l\'utilisateur'}
                </Modal.Title>
            </Modal.Header>
            <Form onSubmit={handleSubmit}>
                <Modal.Body>
                    {error && <Alert variant="danger">{error}</Alert>}
                    
                    <Row>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Nom complet *</Form.Label>
                                <Form.Control
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => setFormData({...formData, name: e.target.value})}
                                    required
                                    disabled={modalMode === 'view'}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Email *</Form.Label>
                                <Form.Control
                                    type="email"
                                    value={formData.email}
                                    onChange={(e) => setFormData({...formData, email: e.target.value})}
                                    required
                                    disabled={modalMode === 'view'}
                                />
                            </Form.Group>
                        </Col>
                    </Row>
                    
                    <Row>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Contact</Form.Label>
                                <Form.Control
                                    type="tel"
                                    value={formData.contact}
                                    onChange={(e) => setFormData({...formData, contact: e.target.value})}
                                    placeholder="Ex: +237 6XX XXX XXX"
                                    disabled={modalMode === 'view'}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Photo</Form.Label>
                                <div className="d-flex align-items-center gap-3">
                                    <Button 
                                        variant={formData.photo ? "outline-success" : "outline-primary"}
                                        onClick={() => setShowPhotoCapture(true)}
                                        disabled={modalMode === 'view'}
                                    >
                                        <i className={`bi bi-${formData.photo ? 'check-circle' : 'camera'} me-2`}></i>
                                        {formData.photo ? 'Photo sélectionnée' : 'Choisir une photo'}
                                    </Button>
                                    {formData.photo && (
                                        <img 
                                            src={formData.photo} 
                                            alt="Aperçu"
                                            style={{
                                                width: '50px',
                                                height: '50px',
                                                borderRadius: '50%',
                                                objectFit: 'cover',
                                                border: '2px solid #28a745'
                                            }}
                                            onError={(e) => {
                                                e.target.style.display = 'none';
                                            }}
                                        />
                                    )}
                                </div>
                                {!formData.photo && (
                                    <Form.Text className="text-muted">
                                        Optionnel - Prenez une photo avec la caméra ou sélectionnez un fichier
                                    </Form.Text>
                                )}
                            </Form.Group>
                        </Col>
                    </Row>
                    
                    <Row>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Rôle *</Form.Label>
                                <Form.Select
                                    value={formData.role}
                                    onChange={(e) => setFormData({...formData, role: e.target.value})}
                                    required
                                    disabled={modalMode === 'view'}
                                >
                                    <optgroup label="Rôles administratifs principaux">
                                        <option value="principal">Principal</option>
                                        <option value="surveillant_general">Surveillant Général</option>
                                        <option value="general_accountant">Comptable Général</option>
                                        <option value="comptable_superieur">Comptable Supérieur</option>
                                        <option value="accountant">Comptable</option>
                                        <option value="secretaire">Secrétaire</option>
                                        <option value="user">Utilisateur</option>
                                        <option value="bibliothecaire">Bibliothécaire</option>
                                    </optgroup>
                                    <optgroup label="Nouveaux rôles">
                                        <option value="responsable_pedagogique">Responsable Pédagogique</option>
                                        <option value="dean_of_studies">Dean of Studies</option>
                                        <option value="censeur_esg">Censeur ESG</option>
                                        <option value="censeur">Censeur</option>
                                        <option value="surveillant_secteur">Surveillant de Secteur</option>
                                        <option value="caissiere">Caissière</option>
                                        <option value="bibliothecaire">Bibliothécaire</option>
                                        <option value="chef_travaux">Chef des Travaux</option>
                                        <option value="chef_securite">Chef de Sécurité</option>
                                        <option value="reprographe">Reprographe</option>
                                    </optgroup>
                                </Form.Select>
                            </Form.Group>
                        </Col>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Qualification</Form.Label>
                                <Form.Control
                                    type="text"
                                    placeholder="Ex: BAC, BTS, Licence, Maîtrise..."
                                    value={formData.qualification}
                                    onChange={(e) => setFormData({...formData, qualification: e.target.value})}
                                    disabled={modalMode === 'view'}
                                />
                            </Form.Group>
                        </Col>
                    </Row>
                    
                    <Row>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Statut</Form.Label>
                                <Form.Check
                                    type="switch"
                                    id="is_active"
                                    label={formData.is_active ? "Actif" : "Inactif"}
                                    checked={formData.is_active}
                                    onChange={(e) => setFormData({...formData, is_active: e.target.checked})}
                                    disabled={modalMode === 'view'}
                                />
                            </Form.Group>
                        </Col>
                    </Row>

                    {modalMode === 'create' && (
                        <Form.Group className="mb-3">
                            <Form.Check
                                type="checkbox"
                                id="generate_password"
                                label="Générer automatiquement un mot de passe"
                                checked={formData.generate_password}
                                onChange={(e) => setFormData({...formData, generate_password: e.target.checked})}
                            />
                        </Form.Group>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleCloseModal}>
                        Annuler
                    </Button>
                    {modalMode !== 'view' && (
                        <Button 
                            variant="primary" 
                            type="submit" 
                            disabled={loading}
                        >
                            {loading ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Sauvegarde...
                                </>
                            ) : (
                                modalMode === 'create' ? 'Créer' : 'Modifier'
                            )}
                        </Button>
                    )}
                </Modal.Footer>
            </Form>
        </Modal>
    );

    return (
        <Container fluid className="py-4">
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>
                                <People className="me-3" />
                                Gestion des Utilisateurs
                            </h2>
                            <p className="text-muted">
                                Gérer les comptes des surveillants généraux, comptables et secrétaires (enseignants créés via gestion des utilisateurs)
                            </p>
                        </div>
                        <ButtonGroup>
                            <Button
                                variant="success"
                                onClick={handleExportAdministrativeStaff}
                                className="d-flex align-items-center"
                                title="Exporter le personnel administratif"
                            >
                                <FileEarmarkPdf className="me-2" />
                                Export Personnel Admin
                            </Button>
                            <Button
                                variant="info"
                                onClick={handleImportAdministrativeStaff}
                                className="d-flex align-items-center"
                                title="Importer le personnel administratif"
                            >
                                <Upload className="me-2" />
                                Import Personnel Admin
                            </Button>
                            <Button
                                variant="primary"
                                onClick={() => handleShowModal('create')}
                                className="d-flex align-items-center"
                            >
                                <PersonPlus className="me-2" />
                                Nouvel Utilisateur
                            </Button>
                        </ButtonGroup>
                    </div>
                </Col>
            </Row>

            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')}>
                    {error}
                </Alert>
            )}

            {renderStatsCards()}

            {/* Barre de recherche et filtres */}
            <Card className="mb-4">
                <Card.Body>
                    <Row>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Rechercher</Form.Label>
                                <Form.Control
                                    type="text"
                                    placeholder="Nom, email, contact ou rôle..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Filtrer par rôle</Form.Label>
                                <Form.Select
                                    value={roleFilter}
                                    onChange={(e) => setRoleFilter(e.target.value)}
                                >
                                    <option value="all">Tous les rôles</option>
                                    <optgroup label="Rôles principaux">
                                        <option value="surveillant_general">Surveillants Généraux</option>
                                        <option value="general_accountant">Comptables Généraux</option>
                                        <option value="comptable_superieur">Comptables Supérieurs</option>
                                        <option value="comptable">Comptables</option>
                                        <option value="secretaire">Secrétaires</option>
                                        <option value="enseignant">Enseignants</option>
                                        <option value="teacher">Enseignants (anciens)</option>
                                        <option value="accountant">Comptables (anciens)</option>
                                    </optgroup>
                                    <optgroup label="Nouveaux rôles">
                                        <option value="responsable_pedagogique">Responsables Pédagogiques</option>
                                        <option value="dean_of_studies">Dean of Studies</option>
                                        <option value="censeur_esg">Censeurs ESG</option>
                                        <option value="censeur">Censeurs</option>
                                        <option value="surveillant_secteur">Surveillants de Secteur</option>
                                        <option value="caissiere">Caissières</option>
                                        <option value="bibliothecaire">Bibliothécaires</option>
                                        <option value="chef_travaux">Chefs des Travaux</option>
                                        <option value="chef_securite">Chefs de Sécurité</option>
                                        <option value="reprographe">Reprographes</option>
                                    </optgroup>
                                </Form.Select>
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group className="mb-3">
                                <Form.Label>Filtrer par statut</Form.Label>
                                <Form.Select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                >
                                    <option value="all">Tous</option>
                                    <option value="active">Actifs</option>
                                    <option value="inactive">Inactifs</option>
                                </Form.Select>
                            </Form.Group>
                        </Col>
                    </Row>
                    <div className="d-flex justify-content-between align-items-center">
                        <div className="d-flex align-items-center gap-3">
                            <small className="text-muted">
                                {filteredUsers.length} utilisateur(s) trouvé(s) sur {users.length} total
                            </small>
                            {selectedUsers.length > 0 && (
                                <Badge bg="primary" className="d-flex align-items-center gap-1">
                                    <i className="bi bi-check-square"></i>
                                    {selectedUsers.length} sélectionné(s)
                                </Badge>
                            )}
                        </div>
                        <div className="d-flex gap-2">
                            {/* Boutons de sélection multiple */}
                            {(() => {
                                const eligibleUsers = filteredUsers.filter(user => staffRoles.includes(user.role));
                                
                                if (eligibleUsers.length > 0) {
                                    return (
                                        <>
                                            <Button
                                                variant="outline-info"
                                                size="sm"
                                                onClick={handleSelectAll}
                                                className="d-flex align-items-center gap-1"
                                            >
                                                <i className={`bi bi-${selectedUsers.length === eligibleUsers.length ? 'square' : 'check-square'}`}></i>
                                                {selectedUsers.length === eligibleUsers.length ? 'Tout désélectionner' : 'Tout sélectionner'}
                                            </Button>
                                            {selectedUsers.length > 0 && (
                                                <Button
                                                    variant="success"
                                                    size="sm"
                                                    onClick={handlePrintMultipleBadges}
                                                    disabled={isGeneratingMultipleBadges}
                                                    className="d-flex align-items-center gap-1"
                                                >
                                                    {isGeneratingMultipleBadges ? (
                                                        <>
                                                            <Spinner animation="border" size="sm" />
                                                            Génération...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <PrinterFill />
                                                            Imprimer badges ({selectedUsers.length})
                                                        </>
                                                    )}
                                                </Button>
                                            )}
                                        </>
                                    );
                                }
                                return null;
                            })()}
                            
                            <Button
                                variant="outline-secondary"
                                size="sm"
                                onClick={() => {
                                    setSearchTerm('');
                                    setRoleFilter('all');
                                    setStatusFilter('all');
                                    setSelectedUsers([]);
                                }}
                            >
                                Réinitialiser les filtres
                            </Button>
                        </div>
                    </div>
                </Card.Body>
            </Card>

            <Card>
                <Card.Header>
                    <h5 className="mb-0">Liste des Utilisateurs</h5>
                </Card.Header>
                <Card.Body>
                    {loading ? (
                        <div className="text-center py-4">
                            <Spinner animation="border" />
                        </div>
                    ) : (
                        <Table responsive hover>
                            <thead>
                                <tr>
                                    <th style={{ width: '40px' }}>
                                        {(() => {
                                            const eligibleUsers = filteredUsers.filter(user => staffRoles.includes(user.role));
                                            
                                            if (eligibleUsers.length > 0) {
                                                return (
                                                    <Form.Check
                                                        type="checkbox"
                                                        checked={selectedUsers.length === eligibleUsers.length && eligibleUsers.length > 0}
                                                        onChange={handleSelectAll}
                                                        title="Sélectionner/Désélectionner tout"
                                                    />
                                                );
                                            }
                                            return null;
                                        })()}
                                    </th>
                                    <th>Photo</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Rôle</th>
                                    <th>Qualification</th>
                                    <th>Statut</th>
                                    <th>Date de création</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredUsers.map(user => {
                                    const isStaff = staffRoles.includes(user.role);
                                    const isSelected = selectedUsers.includes(user.id);
                                    
                                    return (
                                        <tr key={user.id}>
                                            <td style={{ textAlign: 'center' }}>
                                                {isStaff ? (
                                                    <Form.Check
                                                        type="checkbox"
                                                        checked={isSelected}
                                                        onChange={(e) => handleSelectUser(user.id, e.target.checked)}
                                                        title={`Sélectionner ${user.name}`}
                                                    />
                                                ) : (
                                                    <span 
                                                        style={{ 
                                                            color: '#6c757d', 
                                                            fontSize: '12px',
                                                            cursor: 'help'
                                                        }}
                                                        title="Non éligible pour l'impression de badges"
                                                    >
                                                        N/A
                                                    </span>
                                                )}
                                            </td>
                                            <td style={{ textAlign: 'center' }}>
                                                {getUserAvatar(user, 35)}
                                            </td>
                                        <td>{user.name}</td>
                                        <td>{user.email}</td>
                                        <td>{user.contact || '-'}</td>
                                        <td>
                                            <Badge bg={roleColors[user.role]}>
                                                {roleLabels[user.role]}
                                            </Badge>
                                        </td>
                                        <td>{user.qualification || '-'}</td>
                                        <td>
                                            <Badge bg={user.is_active ? 'success' : 'secondary'}>
                                                {user.is_active ? 'Actif' : 'Inactif'}
                                            </Badge>
                                        </td>
                                        <td>
                                            {new Date(user.created_at).toLocaleDateString('fr-FR')}
                                        </td>
                                        <td>
                                            <Dropdown as={ButtonGroup}>
                                                <Button
                                                    variant="outline-primary"
                                                    size="sm"
                                                    onClick={() => handleShowModal('view', user)}
                                                >
                                                    <EyeFill />
                                                </Button>
                                                <Dropdown.Toggle 
                                                    split 
                                                    variant="outline-primary" 
                                                    size="sm"
                                                />
                                                <Dropdown.Menu>
                                                    <Dropdown.Item 
                                                        onClick={() => handleShowModal('edit', user)}
                                                    >
                                                        <PencilSquare className="me-2" />
                                                        Modifier
                                                    </Dropdown.Item>
                                                    <Dropdown.Item 
                                                        onClick={() => handleResetPassword(user)}
                                                    >
                                                        <Key className="me-2" />
                                                        Réinitialiser mot de passe
                                                    </Dropdown.Item>
                                                    <Dropdown.Item 
                                                        onClick={() => handleToggleStatus(user)}
                                                    >
                                                        {user.is_active ? <PersonX className="me-2" /> : <PersonCheck className="me-2" />}
                                                        {user.is_active ? 'Désactiver' : 'Activer'}
                                                    </Dropdown.Item>
                                                    <Dropdown.Divider />
                                                    {/* <Dropdown.Item 
                                                        onClick={() => handleShowQRCode(user)}
                                                        className="text-info"
                                                    >
                                                        <QrCode className="me-2" />
                                                        Voir code QR
                                                    </Dropdown.Item>
                                                    <Dropdown.Item 
                                                        onClick={() => handleGenerateProfessionalCard(user)}
                                                        className="text-success"
                                                    >
                                                        <CreditCard2Back className="me-2" />
                                                        Générer carte professionnelle
                                                    </Dropdown.Item> */}
                                                    <Dropdown.Item 
                                                        onClick={() => handlePrintBadge(user)}
                                                        className="text-primary"
                                                    >
                                                        <PrinterFill className="me-2" />
                                                        Imprimer badge QR
                                                    </Dropdown.Item>
                                                    {user.role !== 'admin' && (
                                                        <>
                                                            <Dropdown.Divider />
                                                            <Dropdown.Item 
                                                                className="text-danger"
                                                                onClick={() => handleDelete(user)}
                                                            >
                                                                <Trash className="me-2" />
                                                                Supprimer
                                                            </Dropdown.Item>
                                                        </>
                                                    )}
                                                </Dropdown.Menu>
                                            </Dropdown>
                                        </td>
                                    </tr>
                                    );
                                })}
                            </tbody>
                        </Table>
                    )}
                </Card.Body>
            </Card>

            {renderUserModal()}
            
            {/* Modal QR Code Preview */}
            <Modal show={showQRModal} onHide={() => setShowQRModal(false)} centered>
                <Modal.Header closeButton>
                    <Modal.Title>
                        <QrCode className="me-2" />
                        Code QR - {selectedUserQR?.user_name}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body className="text-center">
                    {selectedUserQR && (
                        <>
                            <div className="mb-3">
                                <img 
                                    src={selectedUserQR.qr_url} 
                                    alt="QR Code" 
                                    style={{ 
                                        width: '200px', 
                                        height: '200px',
                                        border: '2px solid #dee2e6',
                                        borderRadius: '8px'
                                    }}
                                />
                            </div>
                            <div className="mb-3">
                                <h6>Informations du QR Code</h6>
                                <div className="text-start">
                                    <p><strong>Nom:</strong> {selectedUserQR.user_name}</p>
                                    <p><strong>Rôle:</strong> {roleLabels[selectedUserQR.user_role] || selectedUserQR.user_role}</p>
                                    <p><strong>Code:</strong> <code>{selectedUserQR.qr_content}</code></p>
                                </div>
                            </div>
                            <Alert variant="info" className="text-start">
                                <small>
                                    <strong>Note:</strong> Ce code QR est unique pour cet utilisateur et peut être utilisé 
                                    pour l'identification et l'authentification dans le système.
                                </small>
                            </Alert>
                        </>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowQRModal(false)}>
                        Fermer
                    </Button>
                    {selectedUserQR && (
                        <Button 
                            variant="success" 
                            onClick={() => {
                                setShowQRModal(false);
                                handleGenerateProfessionalCard({
                                    id: selectedUserQR.user_id,
                                    name: selectedUserQR.user_name
                                });
                            }}
                        >
                            <CreditCard2Back className="me-2" />
                            Générer la carte
                        </Button>
                    )}
                </Modal.Footer>
            </Modal>
            
            <PhotoCapture
                show={showPhotoCapture}
                onHide={() => setShowPhotoCapture(false)}
                onPhotoSelected={handlePhotoSelected}
            />
        </Container>
    );
};

export default UserManagement;