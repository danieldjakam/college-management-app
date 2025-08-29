import React, { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Table,
    Alert,
    Spinner,
    Badge,
    Form,
    InputGroup,
    Modal,
    Pagination
} from 'react-bootstrap';
import {
    People,
    Search,
    Plus,
    Eye,
    PencilSquare,
    ArrowLeft,
    PersonFill,
    Telephone,
    CashCoin
} from 'react-bootstrap-icons';
import { Link } from 'react-router-dom';
import { authService } from '../../../services/authService';
import { extractErrorMessage } from '../../../utils/errorHandler';
import { host } from '../../../utils/fetch';

const EmployeeManagement = () => {
    const [employees, setEmployees] = useState([]);
    const [availableUsers, setAvailableUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    // États pour les filtres et pagination
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);

    // États pour le modal
    const [showModal, setShowModal] = useState(false);
    const [isEditMode, setIsEditMode] = useState(false);
    const [selectedEmployee, setSelectedEmployee] = useState(null);
    const [formData, setFormData] = useState({
        user_id: '',
        matricule: '',
        nom: '',
        prenom: '',
        poste: '',
        department: '',
        salaire_base: '',
        primes_fixes: '',
        deductions_fixes: '',
        mode_paiement: 'especes',
        telephone_whatsapp: '',
        statut: 'actif'
    });

    const statusOptions = [
        { value: '', label: 'Tous les statuts' },
        { value: 'actif', label: 'Actif', color: 'success' },
        { value: 'suspendu', label: 'Suspendu', color: 'warning' },
        { value: 'conge', label: 'En congé', color: 'info' }
    ];

    const paymentModes = [
        { value: 'especes', label: 'Espèces' },
        { value: 'cheque', label: 'Chèque' },
        { value: 'virement', label: 'Virement' }
    ];

    // Charger les données
    useEffect(() => {
        loadEmployees();
        loadAvailableUsers();
    }, [currentPage, searchTerm, statusFilter]);

    const loadEmployees = async () => {
        try {
            setLoading(true);
            setError('');

            const params = new URLSearchParams({
                page: currentPage,
                per_page: 15
            });

            if (searchTerm) params.append('search', searchTerm);
            if (statusFilter) params.append('statut', statusFilter);

            const response = await fetch(`${host}/api/payroll/employees?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setEmployees(data.data.data || []);
                    setCurrentPage(data.data.current_page || 1);
                    setTotalPages(data.data.last_page || 1);
                } else {
                    setError(data.message || 'Erreur lors du chargement');
                }
            } else {
                setError('Erreur lors du chargement des employés');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const loadAvailableUsers = async () => {
        try {
            const response = await fetch(`${host}/api/payroll/employees/available-users`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setAvailableUsers(data.data || []);
                }
            }
        } catch (error) {
            console.error('Erreur lors du chargement des utilisateurs:', error);
        }
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');

        try {
            const url = isEditMode 
                ? `${host}/api/payroll/employees/${selectedEmployee.id}`
                : `${host}/api/payroll/employees`;
            
            const method = isEditMode ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(data.message);
                setShowModal(false);
                resetForm();
                loadEmployees();
                loadAvailableUsers();
            } else {
                setError(data.message || 'Erreur lors de l\'enregistrement');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        }
    };

    const resetForm = () => {
        setFormData({
            user_id: '',
            matricule: '',
            nom: '',
            prenom: '',
            poste: '',
            department: '',
            salaire_base: '',
            primes_fixes: '',
            deductions_fixes: '',
            mode_paiement: 'especes',
            telephone_whatsapp: '',
            statut: 'actif'
        });
        setSelectedEmployee(null);
        setIsEditMode(false);
    };

    const handleEdit = (employee) => {
        setSelectedEmployee(employee);
        setFormData({
            user_id: employee.user_id,
            matricule: employee.matricule,
            nom: employee.nom,
            prenom: employee.prenom,
            poste: employee.poste,
            department: employee.department || '',
            salaire_base: employee.salaire_base,
            primes_fixes: employee.primes_fixes || 0,
            deductions_fixes: employee.deductions_fixes || 0,
            mode_paiement: employee.mode_paiement,
            telephone_whatsapp: employee.telephone_whatsapp || '',
            statut: employee.statut
        });
        setIsEditMode(true);
        setShowModal(true);
    };

    const handleNewEmployee = () => {
        resetForm();
        setIsEditMode(false);
        setShowModal(true);
    };

    const formatMontant = (montant) => {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(montant) + ' FCFA';
    };

    const getStatusBadge = (status) => {
        const statusConfig = statusOptions.find(s => s.value === status);
        return statusConfig ? 
            <Badge bg={statusConfig.color}>{statusConfig.label}</Badge> :
            <Badge bg="secondary">{status}</Badge>;
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <Button
                                variant="outline-secondary"
                                as={Link}
                                to="/payroll/dashboard"
                                className="me-3 d-flex align-items-center gap-2"
                                size="sm"
                            >
                                <ArrowLeft />
                                Retour
                            </Button>
                            <h2 className="d-inline-flex align-items-center gap-2 mb-1">
                                <People className="text-primary" />
                                Gestion des Employés
                            </h2>
                            <p className="text-muted mb-0">
                                Gérer les employés du système de paie
                            </p>
                        </div>
                        <Button
                            variant="primary"
                            onClick={handleNewEmployee}
                            className="d-flex align-items-center gap-2"
                        >
                            <Plus size={16} />
                            Nouvel Employé
                        </Button>
                    </div>
                </Col>
            </Row>

            {/* Alerts */}
            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')} className="mb-4">
                    <strong>Erreur:</strong> {error}
                </Alert>
            )}
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess('')} className="mb-4">
                    <strong>Succès:</strong> {success}
                </Alert>
            )}

            {/* Filtres */}
            <Card className="mb-4">
                <Card.Header className="bg-light">
                    <h5 className="mb-0">Filtres et Recherche</h5>
                </Card.Header>
                <Card.Body>
                    <Row>
                        <Col lg={6} md={6} className="mb-2">
                            <InputGroup>
                                <InputGroup.Text><Search /></InputGroup.Text>
                                <Form.Control
                                    type="text"
                                    placeholder="Rechercher par nom, prénom, matricule..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </InputGroup>
                        </Col>
                        <Col lg={3} md={6} className="mb-2">
                            <Form.Select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                            >
                                {statusOptions.map(option => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </Form.Select>
                        </Col>
                        <Col lg={3} md={12} className="mb-2">
                            <div className="text-muted">
                                <strong>{employees.length}</strong> employé(s) trouvé(s)
                            </div>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            {/* Table des employés */}
            <Card>
                <Card.Header className="bg-light">
                    <h5 className="mb-0">Liste des Employés</h5>
                </Card.Header>
                <Card.Body>
                    {loading ? (
                        <div className="text-center py-4">
                            <Spinner animation="border" />
                            <p className="mt-2">Chargement...</p>
                        </div>
                    ) : employees.length > 0 ? (
                        <div className="table-responsive">
                            <Table striped hover>
                                <thead>
                                    <tr>
                                        <th>Matricule</th>
                                        <th>Nom & Prénom</th>
                                        <th>Poste</th>
                                        <th>Salaire Base</th>
                                        <th>Mode Paiement</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {employees.map((employee) => (
                                        <tr key={employee.id}>
                                            <td>
                                                <code>{employee.matricule}</code>
                                            </td>
                                            <td>
                                                <div className="d-flex align-items-center gap-2">
                                                    <PersonFill className="text-muted" />
                                                    <div>
                                                        <div className="fw-semibold">
                                                            {employee.nom} {employee.prenom}
                                                        </div>
                                                        {employee.telephone_whatsapp && (
                                                            <small className="text-muted">
                                                                <Telephone size={12} className="me-1" />
                                                                {employee.telephone_whatsapp}
                                                            </small>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    {employee.poste}
                                                    {employee.department && (
                                                        <small className="d-block text-muted">
                                                            {employee.department}
                                                        </small>
                                                    )}
                                                </div>
                                            </td>
                                            <td>
                                                <div className="d-flex align-items-center gap-1">
                                                    <CashCoin size={16} className="text-success" />
                                                    <strong>{formatMontant(employee.salaire_base)}</strong>
                                                </div>
                                                {(employee.primes_fixes > 0 || employee.deductions_fixes > 0) && (
                                                    <small className="text-muted d-block">
                                                        {employee.primes_fixes > 0 && `+${formatMontant(employee.primes_fixes)}`}
                                                        {employee.deductions_fixes > 0 && ` -${formatMontant(employee.deductions_fixes)}`}
                                                    </small>
                                                )}
                                            </td>
                                            <td>
                                                <Badge bg="info">
                                                    {employee.mode_paiement_label}
                                                </Badge>
                                            </td>
                                            <td>
                                                {getStatusBadge(employee.statut)}
                                            </td>
                                            <td>
                                                <div className="d-flex gap-2">
                                                    <Button
                                                        variant="outline-info"
                                                        size="sm"
                                                        as={Link}
                                                        to={`/payroll/employees/${employee.id}/payslips`}
                                                        title="Voir bulletins"
                                                    >
                                                        <Eye size={14} />
                                                    </Button>
                                                    <Button
                                                        variant="outline-primary"
                                                        size="sm"
                                                        onClick={() => handleEdit(employee)}
                                                        title="Modifier"
                                                    >
                                                        <PencilSquare size={14} />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </div>
                    ) : (
                        <div className="text-center py-4">
                            <p className="text-muted">Aucun employé trouvé</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="d-flex justify-content-center mt-3">
                            <Pagination>
                                {Array.from({ length: totalPages }, (_, i) => i + 1).map(page => (
                                    <Pagination.Item
                                        key={page}
                                        active={page === currentPage}
                                        onClick={() => setCurrentPage(page)}
                                    >
                                        {page}
                                    </Pagination.Item>
                                ))}
                            </Pagination>
                        </div>
                    )}
                </Card.Body>
            </Card>

            {/* Modal pour ajouter/modifier un employé */}
            <Modal show={showModal} onHide={() => setShowModal(false)} size="lg">
                <Form onSubmit={handleSubmit}>
                    <Modal.Header closeButton>
                        <Modal.Title>
                            {isEditMode ? 'Modifier Employé' : 'Nouvel Employé'}
                        </Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <Row>
                            {!isEditMode && (
                                <Col md={12} className="mb-3">
                                    <Form.Group>
                                        <Form.Label>Utilisateur *</Form.Label>
                                        <Form.Select
                                            name="user_id"
                                            value={formData.user_id}
                                            onChange={handleInputChange}
                                            required
                                        >
                                            <option value="">Sélectionner un utilisateur</option>
                                            {availableUsers.map(user => (
                                                <option key={user.id} value={user.id}>
                                                    {user.name} - {user.email} ({user.role})
                                                </option>
                                            ))}
                                        </Form.Select>
                                    </Form.Group>
                                </Col>
                            )}
                            <Col md={6} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Matricule *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        name="matricule"
                                        value={formData.matricule}
                                        onChange={handleInputChange}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Nom *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        name="nom"
                                        value={formData.nom}
                                        onChange={handleInputChange}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Prénom *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        name="prenom"
                                        value={formData.prenom}
                                        onChange={handleInputChange}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Poste *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        name="poste"
                                        value={formData.poste}
                                        onChange={handleInputChange}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={12} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Département</Form.Label>
                                    <Form.Control
                                        type="text"
                                        name="department"
                                        value={formData.department}
                                        onChange={handleInputChange}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={4} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Salaire Base (FCFA) *</Form.Label>
                                    <Form.Control
                                        type="number"
                                        name="salaire_base"
                                        value={formData.salaire_base}
                                        onChange={handleInputChange}
                                        min="0"
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={4} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Primes Fixes (FCFA)</Form.Label>
                                    <Form.Control
                                        type="number"
                                        name="primes_fixes"
                                        value={formData.primes_fixes}
                                        onChange={handleInputChange}
                                        min="0"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={4} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Déductions Fixes (FCFA)</Form.Label>
                                    <Form.Control
                                        type="number"
                                        name="deductions_fixes"
                                        value={formData.deductions_fixes}
                                        onChange={handleInputChange}
                                        min="0"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Mode de Paiement *</Form.Label>
                                    <Form.Select
                                        name="mode_paiement"
                                        value={formData.mode_paiement}
                                        onChange={handleInputChange}
                                        required
                                    >
                                        {paymentModes.map(mode => (
                                            <option key={mode.value} value={mode.value}>
                                                {mode.label}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col md={6} className="mb-3">
                                <Form.Group>
                                    <Form.Label>Téléphone WhatsApp</Form.Label>
                                    <Form.Control
                                        type="tel"
                                        name="telephone_whatsapp"
                                        value={formData.telephone_whatsapp}
                                        onChange={handleInputChange}
                                        placeholder="Ex: 237690123456"
                                    />
                                </Form.Group>
                            </Col>
                            {isEditMode && (
                                <Col md={6} className="mb-3">
                                    <Form.Group>
                                        <Form.Label>Statut *</Form.Label>
                                        <Form.Select
                                            name="statut"
                                            value={formData.statut}
                                            onChange={handleInputChange}
                                            required
                                        >
                                            {statusOptions.slice(1).map(status => (
                                                <option key={status.value} value={status.value}>
                                                    {status.label}
                                                </option>
                                            ))}
                                        </Form.Select>
                                    </Form.Group>
                                </Col>
                            )}
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowModal(false)}>
                            Annuler
                        </Button>
                        <Button variant="primary" type="submit">
                            {isEditMode ? 'Modifier' : 'Créer'}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </Container>
    );
};

export default EmployeeManagement;