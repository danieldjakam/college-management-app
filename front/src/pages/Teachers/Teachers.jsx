import React, { useState, useEffect, useCallback } from 'react';
import { Button, Modal, Form, Badge, ButtonGroup, Tabs, Tab, Card, Table, Spinner, Alert } from 'react-bootstrap';
import { LoadingSpinner } from '../../components/UI';
import ImportExportButton from '../../components/ImportExportButton';
import { PlusCircle, PencilFill, Trash2, Eye, EyeSlashFill, Search, PersonFill, TelephoneFill, EnvelopeFill, PersonPlus, PersonDash, PrinterFill, QrCode } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import Swal from 'sweetalert2';

const Teachers = () => {
    const [loading, setLoading] = useState(false);
    const [teachers, setTeachers] = useState([]);
    const [userTeachers, setUserTeachers] = useState([]);
    const [allTeachers, setAllTeachers] = useState([]);
    const [filteredTeachers, setFilteredTeachers] = useState([]);
    const [showModal, setShowModal] = useState(false);
    const [editingTeacher, setEditingTeacher] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [formData, setFormData] = useState({
        teacher_id: '',
        first_name: '',
        last_name: '',
        phone_number: '',
        email: '',
        address: '',
        date_of_birth: '',
        gender: '',
        qualification: '',
        hire_date: '',
        type_personnel: 'V',
        is_active: true,
        create_user_account: false,
        username: '',
        password: ''
    });
    const [formErrors, setFormErrors] = useState({});
    const [showUserAccountModal, setShowUserAccountModal] = useState(false);
    const [selectedTeacherForAccount, setSelectedTeacherForAccount] = useState(null);
    const [userAccountData, setUserAccountData] = useState({
        username: '',
        password: '',
        email: ''
    });


    useEffect(() => {
        loadTeachers();
    }, []);

    const filterTeachers = useCallback(() => {
        let filtered = allTeachers;

        // Filtrer par terme de recherche
        if (searchTerm) {
            filtered = filtered.filter(teacher => {
                const fullName = `${teacher.first_name} ${teacher.last_name}`.toLowerCase();
                return fullName.includes(searchTerm.toLowerCase()) ||
                       teacher.phone_number?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                       teacher.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                       teacher.teacher_id?.toLowerCase().includes(searchTerm.toLowerCase());
            });
        }

        // Filtrer par statut
        if (statusFilter === 'active') {
            filtered = filtered.filter(teacher => teacher.is_active);
        } else if (statusFilter === 'inactive') {
            filtered = filtered.filter(teacher => !teacher.is_active);
        }

        setFilteredTeachers(filtered);
    }, [allTeachers, searchTerm, statusFilter]);

    useEffect(() => {
        filterTeachers();
    }, [filterTeachers]);

    const loadTeachers = async () => {
        try {
            setLoading(true);
            
            // Récupérer les enseignants de la table teachers
            const teachersResponse = await secureApiEndpoints.teachers.getAll({ with_details: true });
            
            // Récupérer les utilisateurs avec le rôle "enseignant"
            const usersResponse = await secureApiEndpoints.userManagement.getAll();
            
            if (teachersResponse.success && usersResponse.success) {
                const teachersData = teachersResponse.data;
                const usersData = usersResponse.data;
                
                // Filtrer les utilisateurs avec le rôle "enseignant"
                const teacherUsers = usersData.filter(user => user.role === 'enseignant');
                
                // Transformer les utilisateurs-enseignants pour qu'ils aient la même structure que les enseignants
                const transformedUserTeachers = teacherUsers.map(user => ({
                    id: `user_${user.id}`, // Préfixe pour éviter les conflits d'ID
                    user_id: user.id,
                    first_name: user.name.split(' ')[0] || '',
                    last_name: user.name.split(' ').slice(1).join(' ') || '',
                    full_name: user.name,
                    phone_number: user.contact || '',
                    email: user.email,
                    address: '',
                    date_of_birth: '',
                    gender: '',
                    qualification: user.qualification || '',
                    hire_date: user.created_at,
                    type_personnel: 'V', // Par défaut
                    is_active: user.is_active,
                    user: user, // Référence à l'utilisateur
                    isUserAccount: true, // Flag pour identifier les comptes utilisateurs
                    staff_identifier: user.staff_identifier
                }));
                
                // Fusionner les deux listes
                const combinedTeachers = [...teachersData, ...transformedUserTeachers];
                
                setTeachers(teachersData);
                setUserTeachers(transformedUserTeachers);
                setAllTeachers(combinedTeachers);
            } else {
                console.error('Erreur lors du chargement:', { teachersResponse, usersResponse });
                Swal.fire('Erreur', 'Impossible de charger les enseignants', 'error');
            }
        } catch (error) {
            console.error('Erreur lors du chargement des enseignants:', error);
            Swal.fire('Erreur', error.message || 'Impossible de charger les enseignants', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleShowModal = (teacher = null) => {
        if (teacher) {
            setEditingTeacher(teacher);
            setFormData({
                teacher_id: teacher.teacher_id || '',
                first_name: teacher.first_name,
                last_name: teacher.last_name,
                phone_number: teacher.phone_number,
                email: teacher.email || '',
                address: teacher.address || '',
                date_of_birth: teacher.date_of_birth || '',
                gender: teacher.gender || '',
                qualification: teacher.qualification || '',
                hire_date: teacher.hire_date || '',
                type_personnel: teacher.type_personnel || 'V',
                is_active: teacher.is_active,
                create_user_account: false,
                username: '',
                password: ''
            });
        } else {
            setEditingTeacher(null);
            setFormData({
                teacher_id: '',
                first_name: '',
                last_name: '',
                phone_number: '',
                email: '',
                address: '',
                date_of_birth: '',
                gender: '',
                qualification: '',
                hire_date: new Date().toISOString().split('T')[0],
                type_personnel: 'V',
                is_active: true,
                create_user_account: false,
                username: '',
                password: ''
            });
        }
        setFormErrors({});
        setShowModal(true);
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setEditingTeacher(null);
        setFormData({
            teacher_id: '',
            first_name: '',
            last_name: '',
            phone_number: '',
            email: '',
            address: '',
            date_of_birth: '',
            gender: '',
            qualification: '',
            hire_date: '',
            type_personnel: 'V',
            is_active: true,
            create_user_account: false,
            username: '',
            password: ''
        });
        setFormErrors({});
    };

    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));

        // Générer automatiquement le nom d'utilisateur
        if (name === 'first_name' || name === 'last_name') {
            const firstName = name === 'first_name' ? value : formData.first_name;
            const lastName = name === 'last_name' ? value : formData.last_name;
            if (firstName && lastName && formData.create_user_account) {
                const username = (firstName.charAt(0) + lastName).toLowerCase().replace(/\s+/g, '');
                setFormData(prev => ({
                    ...prev,
                    username: username
                }));
            }
        }

        // Nettoyer les erreurs du champ modifié
        if (formErrors[name]) {
            setFormErrors(prev => ({
                ...prev,
                [name]: null
            }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        try {
            let response;
            if (editingTeacher) {
                response = await secureApiEndpoints.teachers.update(editingTeacher.id, formData);
            } else {
                response = await secureApiEndpoints.teachers.create(formData);
            }

            if (response.success) {
                Swal.fire(
                    'Succès!',
                    editingTeacher ? 'Enseignant mis à jour avec succès' : 'Enseignant créé avec succès',
                    'success'
                );
                handleCloseModal();
                loadTeachers();
            }
        } catch (error) {
            if (error.response?.data?.errors) {
                setFormErrors(error.response.data.errors);
            } else {
                Swal.fire('Erreur', error.response?.data?.message || 'Une erreur est survenue', 'error');
            }
        }
    };

    const handleToggleStatus = async (teacher) => {
        try {
            const result = await Swal.fire({
                title: 'Confirmation',
                text: `Voulez-vous ${teacher.is_active ? 'désactiver' : 'activer'} cet enseignant ?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Oui',
                cancelButtonText: 'Annuler'
            });

            if (result.isConfirmed) {
                let response;
                
                if (teacher.isUserAccount) {
                    // Pour les comptes utilisateurs, utiliser l'API des utilisateurs
                    response = await secureApiEndpoints.userManagement.toggleStatus(teacher.user_id);
                } else {
                    // Pour les enseignants standards
                    response = await secureApiEndpoints.teachers.toggleStatus(teacher.id);
                }

                if (response.success) {
                    Swal.fire('Succès!', 'Statut mis à jour avec succès', 'success');
                    loadTeachers();
                }
            }
        } catch (error) {
            Swal.fire('Erreur', error.response?.data?.message || 'Une erreur est survenue', 'error');
        }
    };

    const handleDelete = async (teacher) => {
        try {
            const result = await Swal.fire({
                title: 'Confirmation',
                text: teacher.isUserAccount 
                    ? 'Êtes-vous sûr de vouloir supprimer ce compte utilisateur enseignant ? Cette action est irréversible.'
                    : 'Êtes-vous sûr de vouloir supprimer cet enseignant ? Cette action est irréversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#d33'
            });

            if (result.isConfirmed) {
                let response;
                
                if (teacher.isUserAccount) {
                    // Pour les comptes utilisateurs, utiliser l'API des utilisateurs
                    response = await secureApiEndpoints.userManagement.delete(teacher.user_id);
                } else {
                    // Pour les enseignants standards
                    response = await secureApiEndpoints.teachers.delete(teacher.id);
                }

                if (response.success) {
                    Swal.fire('Supprimé!', 'Enseignant supprimé avec succès', 'success');
                    loadTeachers();
                }
            }
        } catch (error) {
            Swal.fire('Erreur', error.response?.data?.message || 'Une erreur est survenue', 'error');
        }
    };

    const handleCreateUserAccount = async (teacher) => {
        setSelectedTeacherForAccount(teacher);
        setUserAccountData({
            username: teacher.last_name.toLowerCase() + '.' + teacher.first_name.toLowerCase(),
            password: '',
            email: teacher.email || ''
        });
        setShowUserAccountModal(true);
    };

    const handleSubmitUserAccount = async (e) => {
        e.preventDefault();
        try {
            const response = await secureApiEndpoints.teachers.createUserAccount(
                selectedTeacherForAccount.id,
                userAccountData
            );

            if (response.success) {
                Swal.fire('Succès!', 'Compte utilisateur créé avec succès', 'success');
                setShowUserAccountModal(false);
                setSelectedTeacherForAccount(null);
                setUserAccountData({ username: '', password: '', email: '' });
                loadTeachers();
            }
        } catch (error) {
            Swal.fire('Erreur', error.response?.data?.message || 'Une erreur est survenue', 'error');
        }
    };

    const handleRemoveUserAccount = async (teacher) => {
        try {
            const result = await Swal.fire({
                title: 'Confirmation',
                text: 'Êtes-vous sûr de vouloir supprimer le compte utilisateur de cet enseignant ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#d33'
            });

            if (result.isConfirmed) {
                const response = await secureApiEndpoints.teachers.removeUserAccount(teacher.id);

                if (response.success) {
                    Swal.fire('Supprimé!', 'Compte utilisateur supprimé avec succès', 'success');
                    loadTeachers();
                }
            }
        } catch (error) {
            Swal.fire('Erreur', error.response?.data?.message || 'Une erreur est survenue', 'error');
        }
    };

    const getStatusBadge = (isActive) => {
        return (
            <Badge bg={isActive ? 'success' : 'secondary'}>
                {isActive ? 'Actif' : 'Inactif'}
            </Badge>
        );
    };

    const getTypePersonnelBadge = (typePersonnel) => {
        const typeConfig = {
            'V': { label: 'Vacataire', bg: 'warning' },
            'SP': { label: 'Semi-Permanent', bg: 'info' },
            'P': { label: 'Permanent', bg: 'primary' }
        };

        const config = typeConfig[typePersonnel] || { label: 'Non défini', bg: 'secondary' };

        return (
            <Badge bg={config.bg} className="text-white">
                {config.label}
            </Badge>
        );
    };

    const getGenderText = (gender) => {
        switch (gender) {
            case 'm': return 'Masculin';
            case 'f': return 'Féminin';
            default: return '-';
        }
    };

    const handlePrintTeacherBadge = async (teacher) => {
        try {
            setLoading(true);

            let response;
            
            if (teacher.isUserAccount) {
                // Pour les comptes utilisateurs, utiliser l'API du staff (StaffAttendanceController)
                response = await secureApiEndpoints.staff.downloadBadgePDF({
                    user_id: teacher.user_id
                });
            } else {
                // Pour les enseignants standards
                response = await secureApiEndpoints.teachers.generateBadge(teacher.id);
            }

            // Télécharger le PDF
            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `badge_enseignant_${teacher.full_name.replace(/\s+/g, '_')}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);

            await Swal.fire({
                title: 'Badge généré !',
                text: `Badge PDF téléchargé pour ${teacher.full_name}`,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

        } catch (error) {
            console.error('Error generating teacher badge:', error);
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



    if (loading && teachers.length === 0) {
        return <LoadingSpinner />;
    }

    return (
        <div className="container-fluid">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Gestion des Enseignants</h2>
                    <p className="text-muted mb-0">
                        Tous les enseignants : standards et comptes utilisateurs avec le rôle "enseignant"
                    </p>
                </div>
                <div className="d-flex gap-2">
                    <ImportExportButton
                        title="Enseignants"
                        apiBasePath="/api/teachers"
                        onImportSuccess={loadTeachers}
                        templateFileName="template_enseignants.csv"
                    />
                    <Button
                        variant="warning"
                        onClick={() => {
                            console.clear();
                            console.log('=== LISTE ID PERSONNEL - TOUS ENSEIGNANTS ===');
                            filteredTeachers.forEach(teacher => {
                                const id = teacher.staff_identifier || 'PAS D\'ID';
                                const type = teacher.type || 'N/A';
                                console.log(`${teacher.first_name} ${teacher.last_name} (${type}) → ID: ${id}`);
                            });
                            console.log('=============================================');
                            alert(`Liste des ${filteredTeachers.length} enseignants affichée dans la console (F12)`);
                        }}
                        className="me-2"
                    >
                        📋 Voir tous les ID
                    </Button>
                    <Button variant="primary" onClick={() => handleShowModal()}>
                        <PlusCircle className="me-2" />
                        Nouvel Enseignant
                    </Button>
                </div>
            </div>

            {/* Filtres */}
            <Card className="mb-4">
                <Card.Body>
                    <div className="row g-3">
                        <div className="col-md-6">
                            <div className="position-relative">
                                <Search className="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" size={16} />
                                <Form.Control
                                    type="text"
                                    placeholder="Rechercher par nom, prénom, téléphone, email ou ID..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="ps-5"
                                />
                            </div>
                        </div>
                        <div className="col-md-3">
                            <Form.Select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                            >
                                <option value="all">Tous les statuts</option>
                                <option value="active">Actifs uniquement</option>
                                <option value="inactive">Inactifs uniquement</option>
                            </Form.Select>
                        </div>
                        <div className="col-md-3">
                            <div className="text-muted">
                                Total: {filteredTeachers.length} enseignant(s)
                            </div>
                        </div>
                    </div>

                    {/* Info pour génération de badges multiples */}
                    <Alert variant="info" className="mt-3 d-flex align-items-center">
                        <div className="me-3">
                            <QrCode size={24} />
                        </div>
                        <div>
                            <strong>💡 Génération de badges multiples :</strong>
                            <br />
                            Pour créer plusieurs badges avec prévisualisation et impression optimisée (2 par page A4), 
                            utilisez le <strong>Générateur de Cartes</strong> accessible depuis le menu principal.
                        </div>
                    </Alert>
                </Card.Body>
            </Card>

            {/* Tableau des enseignants */}
            <Card>
                <Table responsive>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom complet</th>
                            <th>Contact</th>
                            <th>Qualification</th>
                            <th>Date d'embauche</th>
                            <th>Type de personnel</th>
                            <th>Statut</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredTeachers.length > 0 ? (
                            filteredTeachers.map((teacher) => {
                                return (
                                    <tr key={teacher.id}>
                                        <td>
                                            <div className="text-center">
                                                {teacher.teacher_id ? (
                                                    <Badge bg="primary" className="font-monospace">
                                                        {teacher.teacher_id}
                                                    </Badge>
                                                ) : teacher.staff_identifier ? (
                                                    <Badge bg="info" className="font-monospace">
                                                        {teacher.staff_identifier}
                                                    </Badge>
                                                ) : (
                                                    <div className="d-flex align-items-center gap-2">
                                                        <Badge bg="warning" className="font-monospace">
                                                            PAS D'ID
                                                        </Badge>
                                                        <Button
                                                            size="sm"
                                                            variant="outline-primary"
                                                            onClick={() => {
                                                                const id = prompt(`Saisir ID Personnel pour ${teacher.first_name} ${teacher.last_name}:`);
                                                                if (id) {
                                                                    // Mettre à jour à la fois staff_identifier ET qr_code pour la reconnaissance
                                                                    const updateData = {
                                                                        staff_identifier: id,
                                                                        qr_code: id // Synchroniser le QR code avec l'ID Personnel
                                                                    };

                                                                    // Appeler l'API pour sauvegarder
                                                                    secureApiEndpoints.teachers.update(teacher.id, updateData)
                                                                        .then(() => {
                                                                            alert(`ID "${id}" assigné à ${teacher.first_name} ${teacher.last_name} (QR code mis à jour)`);
                                                                            loadTeachers(); // Recharger les données
                                                                        })
                                                                        .catch(err => {
                                                                            alert('Erreur lors de la sauvegarde');
                                                                            console.error(err);
                                                                        });
                                                                }
                                                            }}
                                                            title="Assigner un ID Personnel"
                                                        >
                                                            ➕ ID
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td>
                                            <div className="d-flex align-items-center">
                                                <PersonFill className="text-muted me-2" />
                                                <div>
                                                    <strong>{teacher.full_name}</strong>
                                                    <div className="small text-muted">
                                                        {getGenderText(teacher.gender)}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div className="small">
                                                <div className="d-flex align-items-center mb-1">
                                                    <TelephoneFill className="text-muted me-2" size={12} />
                                                    {teacher.phone_number}
                                                </div>
                                                {teacher.email && (
                                                    <div className="d-flex align-items-center">
                                                        <EnvelopeFill className="text-muted me-2" size={12} />
                                                        {teacher.email}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td>
                                            <small className="text-muted">
                                                {teacher.qualification || '-'}
                                            </small>
                                        </td>
                                        <td>
                                            <small>
                                                {teacher.hire_date
                                                    ? new Date(teacher.hire_date).toLocaleDateString('fr-FR')
                                                    : '-'
                                                }
                                            </small>
                                        </td>
                                        <td>
                                            {getTypePersonnelBadge(teacher.type_personnel)}
                                        </td>
                                        <td>
                                            {getStatusBadge(teacher.is_active)}
                                            {teacher.user && !teacher.isUserAccount && (
                                                <Badge bg="info" className="ms-1" size="sm">
                                                    Compte utilisateur
                                                </Badge>
                                            )}
                                        </td>
                                        <td>
                                            <ButtonGroup size="sm">
                                                {/* Modification : seulement pour les enseignants standards */}
                                                {!teacher.isUserAccount && (
                                                    <Button
                                                        variant="outline-primary"
                                                        onClick={() => handleShowModal(teacher)}
                                                        title="Modifier"
                                                    >
                                                        <PencilFill size={14} />
                                                    </Button>
                                                )}
                                                
                                                {/* Badge QR : disponible pour tous */}
                                                <Button
                                                    variant="outline-success"
                                                    onClick={() => handlePrintTeacherBadge(teacher)}
                                                    title="Imprimer badge QR"
                                                    disabled={loading}
                                                >
                                                    <PrinterFill size={14} />
                                                </Button>
                                                
                                                {/* Gestion du compte utilisateur : seulement pour les enseignants standards */}
                                                {!teacher.isUserAccount && (
                                                    <>
                                                        {!teacher.user ? (
                                                            <Button
                                                                variant="outline-info"
                                                                onClick={() => handleCreateUserAccount(teacher)}
                                                                title="Créer compte utilisateur"
                                                            >
                                                                <PersonPlus size={14} />
                                                            </Button>
                                                        ) : (
                                                            <Button
                                                                variant="outline-warning"
                                                                onClick={() => handleRemoveUserAccount(teacher)}
                                                                title="Supprimer compte utilisateur"
                                                            >
                                                                <PersonDash size={14} />
                                                            </Button>
                                                        )}
                                                    </>
                                                )}
                                                
                                                {/* Activation/Désactivation : disponible pour tous */}
                                                <Button
                                                    variant={teacher.is_active ? "outline-warning" : "outline-success"}
                                                    onClick={() => handleToggleStatus(teacher)}
                                                    title={teacher.is_active ? "Désactiver" : "Activer"}
                                                >
                                                    {teacher.is_active ? <EyeSlashFill size={14} /> : <Eye size={14} />}
                                                </Button>
                                                
                                                {/* Suppression : disponible pour tous */}
                                                <Button
                                                    variant="outline-danger"
                                                    onClick={() => handleDelete(teacher)}
                                                    title="Supprimer"
                                                >
                                                    <Trash2 size={14} />
                                                </Button>
                                            </ButtonGroup>
                                        </td>
                                    </tr>
                                );
                            })
                        ) : (
                            <tr>
                                <td colSpan="8" className="text-center py-4">
                                    <div className="text-muted">
                                        {searchTerm || statusFilter !== 'all'
                                            ? 'Aucun enseignant ne correspond aux critères de recherche'
                                            : 'Aucun enseignant trouvé'
                                        }
                                    </div>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </Table>
            </Card>

            {/* Modal de création/édition */}
            <Modal show={showModal} onHide={handleCloseModal} size="xl">
                <Modal.Header closeButton>
                    <Modal.Title>
                        {editingTeacher ? 'Modifier l\'Enseignant' : 'Nouvel Enseignant'}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleSubmit}>
                    <Modal.Body>
                        <Tabs defaultActiveKey="personal" className="mb-3">
                            <Tab eventKey="personal" title="Informations personnelles">
                                <div className="row g-3">
                                    <div className="col-md-4">
                                        <Form.Group>
                                            <Form.Label>ID Enseignant</Form.Label>
                                            <Form.Control
                                                type="text"
                                                name="teacher_id"
                                                value={formData.teacher_id}
                                                onChange={handleInputChange}
                                                isInvalid={!!formErrors.teacher_id}
                                                placeholder="Ex: TCH_001, STAF_027"
                                                className="font-monospace"
                                            />
                                            <Form.Text className="text-muted">
                                                Identifiant unique pour l'enseignant (optionnel)
                                            </Form.Text>
                                            <Form.Control.Feedback type="invalid">
                                                {formErrors.teacher_id && formErrors.teacher_id[0]}
                                            </Form.Control.Feedback>
                                        </Form.Group>
                                    </div>
                                    <div className="col-md-4">
                                        <Form.Group>
                                            <Form.Label>Prénom <span className="text-danger">*</span></Form.Label>
                                            <Form.Control
                                                type="text"
                                                name="first_name"
                                                value={formData.first_name}
                                                onChange={handleInputChange}
                                                isInvalid={!!formErrors.first_name}
                                                required
                                            />
                                            <Form.Control.Feedback type="invalid">
                                                {formErrors.first_name && formErrors.first_name[0]}
                                            </Form.Control.Feedback>
                                        </Form.Group>
                                    </div>
                                    <div className="col-md-4">
                                        <Form.Group>
                                            <Form.Label>Nom de famille <span className="text-danger">*</span></Form.Label>
                                            <Form.Control
                                                type="text"
                                                name="last_name"
                                                value={formData.last_name}
                                                onChange={handleInputChange}
                                                isInvalid={!!formErrors.last_name}
                                                required
                                            />
                                            <Form.Control.Feedback type="invalid">
                                                {formErrors.last_name && formErrors.last_name[0]}
                                            </Form.Control.Feedback>
                                        </Form.Group>
                                    </div>
                                    <div className="col-md-6">
                                        <Form.Group>
                                            <Form.Label>Téléphone <span className="text-danger">*</span></Form.Label>
                                            <Form.Control
                                                type="tel"
                                                name="phone_number"
                                                value={formData.phone_number}
                                                onChange={handleInputChange}
                                                isInvalid={!!formErrors.phone_number}
                                                required
                                            />
                                            <Form.Control.Feedback type="invalid">
                                                {formErrors.phone_number && formErrors.phone_number[0]}
                                            </Form.Control.Feedback>
                                        </Form.Group>
                                    </div>
                                    <div className="col-md-6">
                                        <Form.Group>
                                            <Form.Label>Email</Form.Label>
                                            <Form.Control
                                                type="email"
                                                name="email"
                                                value={formData.email}
                                                onChange={handleInputChange}
                                                isInvalid={!!formErrors.email}
                                            />
                                            <Form.Control.Feedback type="invalid">
                                                {formErrors.email && formErrors.email[0]}
                                            </Form.Control.Feedback>
                                        </Form.Group>
                                    </div>
                                    <div className="col-md-6">
                                        <Form.Group>
                                            <Form.Label>Date de naissance</Form.Label>
                                            <Form.Control
                                                type="date"
                                                name="date_of_birth"
                                                value={formData.date_of_birth}
                                                onChange={handleInputChange}
                                            />
                                        </Form.Group>
                                    </div>
                                    <div className="col-md-6">
                                        <Form.Group>
                                            <Form.Label>Genre</Form.Label>
                                            <Form.Select
                                                name="gender"
                                                value={formData.gender}
                                                onChange={handleInputChange}
                                            >
                                                <option value="">Sélectionner...</option>
                                                <option value="m">Masculin</option>
                                                <option value="f">Féminin</option>
                                            </Form.Select>
                                        </Form.Group>
                                    </div>
                                    <div className="col-12">
                                        <Form.Group>
                                            <Form.Label>Adresse</Form.Label>
                                            <Form.Control
                                                as="textarea"
                                                rows={2}
                                                name="address"
                                                value={formData.address}
                                                onChange={handleInputChange}
                                            />
                                        </Form.Group>
                                    </div>
                                </div>
                            </Tab>
                            <Tab eventKey="professional" title="Informations professionnelles">
                                <div className="row g-3">
                                    <div className="col-md-6">
                                        <Form.Group>
                                            <Form.Label>Qualification/Diplôme</Form.Label>
                                            <Form.Control
                                                type="text"
                                                name="qualification"
                                                value={formData.qualification}
                                                onChange={handleInputChange}
                                                placeholder="Ex: Licence en Mathématiques"
                                            />
                                        </Form.Group>
                                    </div>
                                    <div className="col-md-6">
                                        <Form.Group>
                                            <Form.Label>Date d'embauche</Form.Label>
                                            <Form.Control
                                                type="date"
                                                name="hire_date"
                                                value={formData.hire_date}
                                                onChange={handleInputChange}
                                            />
                                        </Form.Group>
                                    </div>
                                    <div className="col-md-6">
                                        <Form.Group>
                                            <Form.Label>Type de personnel</Form.Label>
                                            <Form.Select
                                                name="type_personnel"
                                                value={formData.type_personnel}
                                                onChange={handleInputChange}
                                            >
                                                <option value="V">Vacataire</option>
                                                <option value="SP">Semi-Permanent</option>
                                                <option value="P">Permanent</option>
                                            </Form.Select>
                                        </Form.Group>
                                    </div>
                                    <div className="col-12">
                                        <Form.Check
                                            type="checkbox"
                                            name="is_active"
                                            checked={formData.is_active}
                                            onChange={handleInputChange}
                                            label="Enseignant actif"
                                        />
                                    </div>
                                </div>
                            </Tab>
                            {!editingTeacher && (
                                <Tab eventKey="account" title="Compte utilisateur">
                                    <div className="row g-3">
                                        <div className="col-12">
                                            <Form.Check
                                                type="checkbox"
                                                name="create_user_account"
                                                checked={formData.create_user_account}
                                                onChange={handleInputChange}
                                                label="Créer un compte utilisateur pour cet enseignant"
                                            />
                                            <Form.Text className="text-muted">
                                                Permet à l'enseignant de se connecter au système
                                            </Form.Text>
                                        </div>
                                        {formData.create_user_account && (
                                            <>
                                                <div className="col-md-6">
                                                    <Form.Group>
                                                        <Form.Label>Nom d'utilisateur <span className="text-danger">*</span></Form.Label>
                                                        <Form.Control
                                                            type="text"
                                                            name="username"
                                                            value={formData.username}
                                                            onChange={handleInputChange}
                                                            isInvalid={!!formErrors.username}
                                                            required={formData.create_user_account}
                                                        />
                                                        <Form.Control.Feedback type="invalid">
                                                            {formErrors.username && formErrors.username[0]}
                                                        </Form.Control.Feedback>
                                                    </Form.Group>
                                                </div>
                                                <div className="col-md-6">
                                                    <Form.Group>
                                                        <Form.Label>Mot de passe <span className="text-danger">*</span></Form.Label>
                                                        <Form.Control
                                                            type="password"
                                                            name="password"
                                                            value={formData.password}
                                                            onChange={handleInputChange}
                                                            isInvalid={!!formErrors.password}
                                                            required={formData.create_user_account}
                                                            minLength="6"
                                                        />
                                                        <Form.Control.Feedback type="invalid">
                                                            {formErrors.password && formErrors.password[0]}
                                                        </Form.Control.Feedback>
                                                    </Form.Group>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                </Tab>
                            )}
                        </Tabs>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleCloseModal}>
                            Annuler
                        </Button>
                        <Button variant="primary" type="submit" disabled={loading}>
                            {loading ? 'Enregistrement...' : editingTeacher ? 'Mettre à jour' : 'Créer'}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* Modal de création de compte utilisateur */}
            <Modal show={showUserAccountModal} onHide={() => setShowUserAccountModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>
                        Créer un compte utilisateur pour {selectedTeacherForAccount?.full_name}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleSubmitUserAccount}>
                    <Modal.Body>
                        <div className="mb-3">
                            <Form.Label>Nom d'utilisateur <span className="text-danger">*</span></Form.Label>
                            <Form.Control
                                type="text"
                                value={userAccountData.username}
                                onChange={(e) => setUserAccountData({ ...userAccountData, username: e.target.value })}
                                required
                                placeholder="nom.prenom"
                            />
                        </div>

                        <div className="mb-3">
                            <Form.Label>Mot de passe <span className="text-danger">*</span></Form.Label>
                            <Form.Control
                                type="password"
                                value={userAccountData.password}
                                onChange={(e) => setUserAccountData({ ...userAccountData, password: e.target.value })}
                                required
                                minLength="6"
                                placeholder="Minimum 6 caractères"
                            />
                        </div>

                        <div className="mb-3">
                            <Form.Label>Email</Form.Label>
                            <Form.Control
                                type="email"
                                value={userAccountData.email}
                                onChange={(e) => setUserAccountData({ ...userAccountData, email: e.target.value })}
                                placeholder="email@example.com (optionnel)"
                            />
                            <Form.Text className="text-muted">
                                Si vide, l'email de l'enseignant sera utilisé ou un email temporaire sera généré.
                            </Form.Text>
                        </div>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowUserAccountModal(false)}>
                            Annuler
                        </Button>
                        <Button variant="primary" type="submit">
                            Créer le compte
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </div>
    );
};

export default Teachers;