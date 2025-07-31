import React, { useState, useEffect } from 'react';
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
    PersonCheck
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../utils/apiMigration';
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
    
    // États pour la recherche et les filtres
    const [searchTerm, setSearchTerm] = useState('');
    const [roleFilter, setRoleFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');

    const [formData, setFormData] = useState({
        name: '',
        email: '',
        contact: '',
        photo: '',
        role: 'comptable',
        is_active: true,
        generate_password: true
    });

    const roleLabels = {
        admin: 'Administrateur',
        surveillant_general: 'Surveillant Général',
        comptable: 'Comptable',
        secretaire: 'Secrétaire',
        enseignant: 'Enseignant',
        teacher: 'Enseignant',
        accountant: 'Comptable'
    };

    const roleColors = {
        admin: 'danger',
        surveillant_general: 'primary',
        comptable: 'success',
        secretaire: 'info',
        enseignant: 'warning',
        teacher: 'warning',
        accountant: 'success'
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
                role: 'comptable',
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

    const handleSubmit = async (e) => {
        e.preventDefault();
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
                setError(response.message || 'Erreur lors de la sauvegarde');
            }
        } catch (error) {
            setError('Erreur lors de la sauvegarde');
            console.error('Error saving user:', error);
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
                                <Form.Label>Contact *</Form.Label>
                                <Form.Control
                                    type="tel"
                                    value={formData.contact}
                                    onChange={(e) => setFormData({...formData, contact: e.target.value})}
                                    placeholder="Ex: +237 6XX XXX XXX"
                                    required
                                    disabled={modalMode === 'view'}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Photo *</Form.Label>
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
                                        Prenez une photo avec la caméra ou sélectionnez un fichier
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
                                    <option value="surveillant_general">Surveillant Général</option>
                                    <option value="comptable">Comptable</option>
                                    <option value="secretaire">Secrétaire</option>
                                </Form.Select>
                            </Form.Group>
                        </Col>
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
                        <Button
                            variant="primary"
                            onClick={() => handleShowModal('create')}
                            className="d-flex align-items-center"
                        >
                            <PersonPlus className="me-2" />
                            Nouvel Utilisateur
                        </Button>
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
                                    <option value="surveillant_general">Surveillants Généraux</option>
                                    <option value="comptable">Comptables</option>
                                    <option value="secretaire">Secrétaires</option>
                                    <option value="enseignant">Enseignants</option>
                                    <option value="teacher">Enseignants (anciens)</option>
                                    <option value="accountant">Comptables (anciens)</option>
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
                        <small className="text-muted">
                            {filteredUsers.length} utilisateur(s) trouvé(s) sur {users.length} total
                        </small>
                        <Button
                            variant="outline-secondary"
                            size="sm"
                            onClick={() => {
                                setSearchTerm('');
                                setRoleFilter('all');
                                setStatusFilter('all');
                            }}
                        >
                            Réinitialiser les filtres
                        </Button>
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
                                    <th>Photo</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Date de création</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredUsers.map(user => (
                                    <tr key={user.id}>
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
                                ))}
                            </tbody>
                        </Table>
                    )}
                </Card.Body>
            </Card>

            {renderUserModal()}
            
            <PhotoCapture
                show={showPhotoCapture}
                onHide={() => setShowPhotoCapture(false)}
                onPhotoSelected={handlePhotoSelected}
            />
        </Container>
    );
};

export default UserManagement;