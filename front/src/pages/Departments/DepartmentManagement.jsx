import React, { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Table,
    Button,
    Modal,
    Form,
    Badge,
    Alert,
    Spinner,
    Dropdown,
    ButtonGroup
} from 'react-bootstrap';
import {
    PlusCircle,
    Pencil,
    Trash,
    Eye,
    PersonPlus,
    Award,
    Building,
    People,
    Calendar,
    FileEarmarkPdf
} from 'react-bootstrap-icons';
import { host } from '../../utils/fetch';
import './DepartmentManagement.css';

// Simple API helper to avoid circular imports
const makeApiCall = async (endpoint, options = {}) => {
    const token = localStorage.getItem('token');
    const defaultHeaders = {
        'Content-Type': 'application/json',
        ...(token && { 'Authorization': `Bearer ${token}` })
    };
    
    const config = {
        ...options,
        headers: {
            ...defaultHeaders,
            ...options.headers
        }
    };
    
    const response = await fetch(`${host}/api${endpoint}`, config);
    
    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
    }
    
    return response.json();
};

const api = {
    get: (endpoint) => makeApiCall(endpoint),
    post: (endpoint, data) => makeApiCall(endpoint, {
        method: 'POST',
        body: JSON.stringify(data)
    }),
    put: (endpoint, data) => makeApiCall(endpoint, {
        method: 'PUT',
        body: JSON.stringify(data)
    }),
    delete: (endpoint) => makeApiCall(endpoint, {
        method: 'DELETE'
    })
};

const DepartmentManagement = () => {
    const [departments, setDepartments] = useState([]);
    const [teachers, setTeachers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    // Modal states
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showViewModal, setShowViewModal] = useState(false);
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [showHeadModal, setShowHeadModal] = useState(false);
    
    const [selectedDepartment, setSelectedDepartment] = useState(null);
    const [selectedTeacher, setSelectedTeacher] = useState('');
    const [downloadingPdf, setDownloadingPdf] = useState(false);

    // Form data
    const [formData, setFormData] = useState({
        name: '',
        code: '',
        description: '',
        color: '#6c757d',
        head_teacher_id: '',
        order: 0
    });

    useEffect(() => {
        loadDepartments();
        loadTeachers();
    }, []);

    const loadDepartments = async () => {
        try {
            const response = await api.get('/departments');
            if (response.success) {
                setDepartments(response.data);
            }
        } catch (error) {
            setError('Erreur lors du chargement des départements');
            console.error('Error loading departments:', error);
        }
    };

    const loadTeachers = async () => {
        try {
            const response = await api.get('/teachers');
            if (response.success) {
                setTeachers(response.data);
            }
        } catch (error) {
            console.error('Error loading teachers:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleExportPdf = async () => {
        try {
            setDownloadingPdf(true);
            
            const token = localStorage.getItem('token');
            const response = await fetch(`${host}/api/departments/export/pdf`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/pdf'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP Error ${response.status}: ${response.statusText}`);
            }

            // Créer le blob PDF
            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            
            // Créer le lien de téléchargement
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `Liste_Personnel_Enseignant_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Nettoyer l'URL
            window.URL.revokeObjectURL(downloadUrl);
            
            setSuccess('PDF téléchargé avec succès');
            
        } catch (error) {
            setError('Erreur lors du téléchargement du PDF: ' + (error.message || 'Une erreur inattendue s\'est produite'));
            console.error('Error downloading PDF:', error);
        } finally {
            setDownloadingPdf(false);
        }
    };

    const resetForm = () => {
        setFormData({
            name: '',
            code: '',
            description: '',
            color: '#6c757d',
            head_teacher_id: '',
            order: 0
        });
    };

    const handleCreate = async (e) => {
        e.preventDefault();
        try {
            const response = await api.post('/departments', formData);
            if (response.success) {
                setSuccess('Département créé avec succès');
                setShowCreateModal(false);
                resetForm();
                loadDepartments();
            }
        } catch (error) {
            setError(error.message || 'Erreur lors de la création');
        }
    };

    const handleEdit = async (e) => {
        e.preventDefault();
        try {
            const response = await api.put(`/departments/${selectedDepartment.id}`, formData);
            if (response.success) {
                setSuccess('Département modifié avec succès');
                setShowEditModal(false);
                resetForm();
                loadDepartments();
            }
        } catch (error) {
            setError(error.message || 'Erreur lors de la modification');
        }
    };

    const handleDelete = async (department) => {
        if (window.confirm(`Êtes-vous sûr de vouloir supprimer le département "${department.name}"?`)) {
            try {
                const response = await api.delete(`/departments/${department.id}`);
                if (response.success) {
                    setSuccess('Département supprimé avec succès');
                    loadDepartments();
                }
            } catch (error) {
                setError(error.message || 'Erreur lors de la suppression');
            }
        }
    };

    const handleAssignTeacher = async (e) => {
        e.preventDefault();
        try {
            const response = await api.post(`/departments/${selectedDepartment.id}/assign-teacher`, {
                teacher_id: selectedTeacher
            });
            if (response.success) {
                setSuccess('Enseignant assigné avec succès');
                setShowAssignModal(false);
                setSelectedTeacher('');
                loadDepartments();
                loadTeachers();
            }
        } catch (error) {
            setError(error.message || 'Erreur lors de l\'assignation');
        }
    };

    const handleRemoveTeacher = async (departmentId, teacherId) => {
        if (window.confirm('Êtes-vous sûr de vouloir retirer cet enseignant du département?')) {
            try {
                const response = await api.post(`/departments/${departmentId}/remove-teacher`, {
                    teacher_id: teacherId
                });
                if (response.success) {
                    setSuccess('Enseignant retiré avec succès');
                    loadDepartments();
                    loadTeachers();
                }
            } catch (error) {
                setError(error.message || 'Erreur lors du retrait');
            }
        }
    };

    const handleSetHead = async (e) => {
        e.preventDefault();
        try {
            const response = await api.post(`/departments/${selectedDepartment.id}/set-head`, {
                teacher_id: selectedTeacher
            });
            if (response.success) {
                setSuccess('Chef de département nommé avec succès');
                setShowHeadModal(false);
                setSelectedTeacher('');
                loadDepartments();
            }
        } catch (error) {
            setError(error.message || 'Erreur lors de la nomination');
        }
    };

    const openCreateModal = () => {
        resetForm();
        setShowCreateModal(true);
    };

    const openEditModal = (department) => {
        console.log('Opening edit modal for department:', department);
        if (!department || !department.name) {
            setError('Données du département invalides');
            return;
        }
        setSelectedDepartment(department);
        setFormData({
            name: department.name || '',
            code: department.code || '',
            description: department.description || '',
            color: department.color || '#6c757d',
            head_teacher_id: department.head_teacher?.id || '',
            order: department.order || 0
        });
        setShowEditModal(true);
    };

    const openViewModal = async (department) => {
        try {
            const response = await api.get(`/departments/${department.id}`);
            if (response.success) {
                setSelectedDepartment(response.data);
                setShowViewModal(true);
            }
        } catch (error) {
            setError('Erreur lors du chargement des détails');
        }
    };

    const openAssignModal = (department) => {
        console.log('Opening assign modal for department:', department);
        if (!department || !department.id) {
            setError('Données du département invalides');
            return;
        }
        setSelectedDepartment(department);
        setSelectedTeacher('');
        setShowAssignModal(true);
    };

    const openHeadModal = (department) => {
        console.log('Opening head modal for department:', department);
        if (!department || !department.id) {
            setError('Données du département invalides');
            return;
        }
        setSelectedDepartment(department);
        setSelectedTeacher('');
        setShowHeadModal(true);
    };

    const getAvailableTeachers = () => {
        return teachers.filter(teacher => 
            teacher.is_active && 
            (!teacher.department_id || teacher.department_id === selectedDepartment?.id)
        );
    };

    const getDepartmentTeachers = (department) => {
        if (!department || !department.id) return [];
        return teachers.filter(teacher => teacher.department_id === department.id);
    };

    // Fonction pour obtenir l'avatar de l'enseignant
    const getTeacherAvatar = (teacher, size = 32) => {
        const initials = teacher.full_name ? 
            teacher.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() :
            'T';
        
        const InitialsAvatar = () => (
            <div style={{
                width: size,
                height: size,
                borderRadius: '50%',
                backgroundColor: '#007bff',
                color: 'white',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: `${size * 0.4}px`,
                fontWeight: 'bold',
                border: '2px solid #fff',
                boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
            }}>
                {initials}
            </div>
        );

        if (teacher.photo) {
            return (
                <img 
                    src={teacher.photo} 
                    alt={teacher.full_name}
                    style={{
                        width: size,
                        height: size,
                        borderRadius: '50%',
                        objectFit: 'cover',
                        border: '2px solid #fff',
                        boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                    }}
                    onError={(e) => {
                        console.error('Error loading teacher image:', teacher.photo);
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
                                background-color: #007bff;
                                color: white;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: ${size * 0.4}px;
                                font-weight: bold;
                                border: 2px solid #fff;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                            `;
                            initialsDiv.textContent = initials;
                            parent.appendChild(initialsDiv);
                        }
                    }}
                />
            );
        }

        return <InitialsAvatar />;
    };

    if (loading) {
        return (
            <Container className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <Spinner animation="border" variant="primary" />
            </Container>
        );
    }

    return (
        <Container fluid className="department-management">
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <h2><Building className="me-2" />Gestion des Départements</h2>
                        <div className="d-flex gap-2">
                            <Button 
                                variant="success" 
                                onClick={handleExportPdf}
                                disabled={downloadingPdf || loading}
                                className="d-flex align-items-center"
                            >
                                {downloadingPdf ? (
                                    <>
                                        <Spinner animation="border" size="sm" className="me-2" />
                                        Génération...
                                    </>
                                ) : (
                                    <>
                                        <FileEarmarkPdf className="me-2" />
                                        Export PDF
                                    </>
                                )}
                            </Button>
                            <Button variant="primary" onClick={openCreateModal}>
                                <PlusCircle className="me-2" />Nouveau Département
                            </Button>
                        </div>
                    </div>
                </Col>
            </Row>

            {error && (
                <Row className="mb-3">
                    <Col>
                        <Alert variant="danger" onClose={() => setError('')} dismissible>
                            {error}
                        </Alert>
                    </Col>
                </Row>
            )}

            {success && (
                <Row className="mb-3">
                    <Col>
                        <Alert variant="success" onClose={() => setSuccess('')} dismissible>
                            {success}
                        </Alert>
                    </Col>
                </Row>
            )}

            <Row>
                <Col>
                    <Card>
                        <Card.Header>
                            <h5>Liste des Départements</h5>
                        </Card.Header>
                        <Card.Body>
                            {departments.length === 0 ? (
                                <div className="text-center py-4">
                                    <Building size={48} className="text-muted mb-3" />
                                    <p className="text-muted">Aucun département créé</p>
                                </div>
                            ) : (
                                <Table responsive striped hover>
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Code</th>
                                            <th>Chef</th>
                                            <th>Enseignants</th>
                                            <th>Statut</th>
                                            <th>Créé le</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {departments.map(department => (
                                            <tr key={department.id}>
                                                <td>
                                                    <div className="d-flex align-items-center">
                                                        <div 
                                                            className="department-color-indicator me-2"
                                                            style={{ backgroundColor: department.color }}
                                                        ></div>
                                                        {department.name}
                                                    </div>
                                                </td>
                                                <td>
                                                    <Badge bg="secondary">{department.code}</Badge>
                                                </td>
                                                <td>
                                                    {department.head_teacher ? (
                                                        <span>
                                                            <Award className="me-1 text-warning" size={14} />
                                                            {department.head_teacher.full_name}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted">Aucun chef</span>
                                                    )}
                                                </td>
                                                <td>
                                                    <Badge bg="info">
                                                        <People className="me-1" size={12} />
                                                        {department.stats.active_teachers}/{department.stats.total_teachers}
                                                    </Badge>
                                                </td>
                                                <td>
                                                    <Badge bg={department.is_active ? 'success' : 'secondary'}>
                                                        {department.is_active ? 'Actif' : 'Inactif'}
                                                    </Badge>
                                                </td>
                                                <td>
                                                    <Calendar className="me-1" size={14} />
                                                    {department.created_at}
                                                </td>
                                                <td>
                                                    <Dropdown as={ButtonGroup}>
                                                        <Button 
                                                            variant="outline-primary" 
                                                            size="sm"
                                                            onClick={() => openViewModal(department)}
                                                        >
                                                            <Eye size={14} />
                                                        </Button>
                                                        
                                                        <Dropdown.Toggle 
                                                            split 
                                                            variant="outline-primary" 
                                                            size="sm" 
                                                        />

                                                        <Dropdown.Menu>
                                                            <Dropdown.Item onClick={() => openEditModal(department)}>
                                                                <Pencil className="me-2" size={14} />Modifier
                                                            </Dropdown.Item>
                                                            <Dropdown.Item onClick={() => openAssignModal(department)}>
                                                                <PersonPlus className="me-2" size={14} />Assigner Enseignant
                                                            </Dropdown.Item>
                                                            <Dropdown.Item onClick={() => openHeadModal(department)}>
                                                                <Award className="me-2" size={14} />Nommer Chef
                                                            </Dropdown.Item>
                                                            <Dropdown.Divider />
                                                            <Dropdown.Item 
                                                                className="text-danger"
                                                                onClick={() => handleDelete(department)}
                                                            >
                                                                <Trash className="me-2" size={14} />Supprimer
                                                            </Dropdown.Item>
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
                </Col>
            </Row>

            {/* Create Modal */}
            <Modal show={showCreateModal} onHide={() => setShowCreateModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>Créer un Département</Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleCreate}>
                    <Modal.Body>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Nom du département *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => setFormData({...formData, name: e.target.value})}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Code *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={formData.code}
                                        onChange={(e) => setFormData({...formData, code: e.target.value})}
                                        maxLength={10}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={8}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Description</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={formData.description}
                                        onChange={(e) => setFormData({...formData, description: e.target.value})}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Couleur</Form.Label>
                                    <Form.Control
                                        type="color"
                                        value={formData.color}
                                        onChange={(e) => setFormData({...formData, color: e.target.value})}
                                    />
                                </Form.Group>
                                <Form.Group className="mb-3">
                                    <Form.Label>Ordre d'affichage</Form.Label>
                                    <Form.Control
                                        type="number"
                                        value={formData.order}
                                        onChange={(e) => setFormData({...formData, order: parseInt(e.target.value)})}
                                        min={0}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col>
                                <Form.Group className="mb-3">
                                    <Form.Label>Chef de département</Form.Label>
                                    <Form.Select
                                        value={formData.head_teacher_id}
                                        onChange={(e) => setFormData({...formData, head_teacher_id: e.target.value})}
                                    >
                                        <option value="">Sélectionner un enseignant</option>
                                        {teachers.filter(t => t.is_active).map(teacher => (
                                            <option key={teacher.id} value={teacher.id}>
                                                {teacher.full_name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowCreateModal(false)}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary">
                            Créer
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* Edit Modal */}
            <Modal show={showEditModal} onHide={() => setShowEditModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>Modifier le Département</Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleEdit}>
                    <Modal.Body>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Nom du département *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => setFormData({...formData, name: e.target.value})}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Code *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={formData.code}
                                        onChange={(e) => setFormData({...formData, code: e.target.value})}
                                        maxLength={10}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={8}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Description</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={formData.description}
                                        onChange={(e) => setFormData({...formData, description: e.target.value})}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Couleur</Form.Label>
                                    <Form.Control
                                        type="color"
                                        value={formData.color}
                                        onChange={(e) => setFormData({...formData, color: e.target.value})}
                                    />
                                </Form.Group>
                                <Form.Group className="mb-3">
                                    <Form.Label>Ordre d'affichage</Form.Label>
                                    <Form.Control
                                        type="number"
                                        value={formData.order}
                                        onChange={(e) => setFormData({...formData, order: parseInt(e.target.value)})}
                                        min={0}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col>
                                <Form.Group className="mb-3">
                                    <Form.Label>Chef de département</Form.Label>
                                    <Form.Select
                                        value={formData.head_teacher_id}
                                        onChange={(e) => setFormData({...formData, head_teacher_id: e.target.value})}
                                    >
                                        <option value="">Sélectionner un enseignant</option>
                                        {teachers.filter(t => t.is_active).map(teacher => (
                                            <option key={teacher.id} value={teacher.id}>
                                                {teacher.full_name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowEditModal(false)}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary">
                            Modifier
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* View Modal */}
            <Modal show={showViewModal} onHide={() => setShowViewModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>Détails du Département</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {selectedDepartment && (
                        <div>
                            <Row className="mb-4">
                                <Col md={6}>
                                    <h5>{selectedDepartment.department?.name || selectedDepartment.name}</h5>
                                    <p className="text-muted">{selectedDepartment.department?.description || selectedDepartment.description}</p>
                                </Col>
                                <Col md={6} className="text-end">
                                    <Badge bg="secondary" className="me-2">{selectedDepartment.department?.code || selectedDepartment.code}</Badge>
                                    <Badge bg={(selectedDepartment.department?.is_active ?? selectedDepartment.is_active) ? 'success' : 'secondary'}>
                                        {(selectedDepartment.department?.is_active ?? selectedDepartment.is_active) ? 'Actif' : 'Inactif'}
                                    </Badge>
                                </Col>
                            </Row>

                            <Row className="mb-4">
                                <Col md={4}>
                                    <Card className="text-center">
                                        <Card.Body>
                                            <h3 className="text-primary">{selectedDepartment.stats.total_teachers}</h3>
                                            <p className="mb-0">Enseignants Total</p>
                                        </Card.Body>
                                    </Card>
                                </Col>
                                <Col md={4}>
                                    <Card className="text-center">
                                        <Card.Body>
                                            <h3 className="text-success">{selectedDepartment.stats.active_teachers}</h3>
                                            <p className="mb-0">Enseignants Actifs</p>
                                        </Card.Body>
                                    </Card>
                                </Col>
                                <Col md={4}>
                                    <Card className="text-center">
                                        <Card.Body>
                                            <h3 className="text-info">{selectedDepartment.stats.subjects_count}</h3>
                                            <p className="mb-0">Matières</p>
                                        </Card.Body>
                                    </Card>
                                </Col>
                            </Row>

                            <h6>Enseignants du département</h6>
                            {selectedDepartment.teachers && selectedDepartment.teachers.length > 0 ? (
                                <Table responsive striped>
                                    <thead>
                                        <tr>
                                            <th>Photo</th>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Téléphone</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {selectedDepartment.teachers.map(teacher => (
                                            <tr key={teacher.id}>
                                                <td style={{ textAlign: 'center' }}>
                                                    {getTeacherAvatar(teacher, 35)}
                                                </td>
                                                <td>
                                                    {teacher.is_head && <Award className="me-1 text-warning" size={14} />}
                                                    {teacher.full_name}
                                                </td>
                                                <td>{teacher.email}</td>
                                                <td>{teacher.phone_number}</td>
                                                <td>
                                                    <Badge bg={teacher.is_active ? 'success' : 'secondary'}>
                                                        {teacher.is_active ? 'Actif' : 'Inactif'}
                                                    </Badge>
                                                </td>
                                                <td>
                                                    <Button
                                                        variant="outline-danger"
                                                        size="sm"
                                                        onClick={() => handleRemoveTeacher(selectedDepartment.department?.id || selectedDepartment.id, teacher.id)}
                                                    >
                                                        <Trash size={14} />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            ) : (
                                <p className="text-muted">Aucun enseignant assigné</p>
                            )}
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowViewModal(false)}>
                        Fermer
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Assign Teacher Modal */}
            <Modal show={showAssignModal} onHide={() => setShowAssignModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>
                        Assigner un Enseignant {selectedDepartment && `au département ${selectedDepartment.name}`}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleAssignTeacher}>
                    <Modal.Body>
                        <Form.Group className="mb-3">
                            <Form.Label>Sélectionner un enseignant</Form.Label>
                            <Form.Select
                                value={selectedTeacher}
                                onChange={(e) => setSelectedTeacher(e.target.value)}
                                required
                            >
                                <option value="">Choisir un enseignant</option>
                                {getAvailableTeachers().map(teacher => (
                                    <option key={teacher.id} value={teacher.id}>
                                        {teacher.full_name} {teacher.department_id ? '(déjà assigné)' : ''}
                                    </option>
                                ))}
                            </Form.Select>
                        </Form.Group>
                        
                        {/* Affichage visuel des enseignants disponibles */}
                        {getAvailableTeachers().length > 0 && (
                            <div className="mt-3">
                                <Form.Label>Enseignants disponibles :</Form.Label>
                                <div className="d-flex flex-wrap gap-2">
                                    {getAvailableTeachers().slice(0, 6).map(teacher => (
                                        <div 
                                            key={teacher.id}
                                            className={`d-flex align-items-center gap-2 p-2 border rounded ${selectedTeacher == teacher.id ? 'border-primary bg-light' : ''}`}
                                            style={{ cursor: 'pointer', fontSize: '0.875rem' }}
                                            onClick={() => setSelectedTeacher(teacher.id.toString())}
                                        >
                                            {getTeacherAvatar(teacher, 24)}
                                            <span>{teacher.full_name}</span>
                                        </div>
                                    ))}
                                    {getAvailableTeachers().length > 6 && (
                                        <small className="text-muted">
                                            +{getAvailableTeachers().length - 6} autres...
                                        </small>
                                    )}
                                </div>
                            </div>
                        )}
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowAssignModal(false)}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary">
                            Assigner
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* Set Head Modal */}
            <Modal show={showHeadModal} onHide={() => setShowHeadModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>
                        Nommer Chef {selectedDepartment && `du département ${selectedDepartment.name}`}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleSetHead}>
                    <Modal.Body>
                        <Form.Group className="mb-3">
                            <Form.Label>Sélectionner le chef de département</Form.Label>
                            <Form.Select
                                value={selectedTeacher}
                                onChange={(e) => setSelectedTeacher(e.target.value)}
                                required
                            >
                                <option value="">Choisir un enseignant</option>
                                {getDepartmentTeachers(selectedDepartment || {}).map(teacher => (
                                    <option key={teacher.id} value={teacher.id}>
                                        {teacher.full_name}
                                    </option>
                                ))}
                            </Form.Select>
                        </Form.Group>
                        
                        {/* Affichage visuel des enseignants du département */}
                        {getDepartmentTeachers(selectedDepartment || {}).length > 0 && (
                            <div className="mt-3">
                                <Form.Label>Enseignants du département :</Form.Label>
                                <div className="d-flex flex-wrap gap-2">
                                    {getDepartmentTeachers(selectedDepartment || {}).map(teacher => (
                                        <div 
                                            key={teacher.id}
                                            className={`d-flex align-items-center gap-2 p-2 border rounded ${selectedTeacher == teacher.id ? 'border-warning bg-light' : ''}`}
                                            style={{ cursor: 'pointer', fontSize: '0.875rem' }}
                                            onClick={() => setSelectedTeacher(teacher.id.toString())}
                                        >
                                            {getTeacherAvatar(teacher, 24)}
                                            <span>
                                                {teacher.full_name}
                                                {teacher.is_head && <Award className="ms-1 text-warning" size={12} />}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                        <Alert variant="info">
                            Seuls les enseignants déjà assignés à ce département peuvent être nommés chef.
                        </Alert>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowHeadModal(false)}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="warning">
                            Nommer Chef
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </Container>
    );
};

export default DepartmentManagement;