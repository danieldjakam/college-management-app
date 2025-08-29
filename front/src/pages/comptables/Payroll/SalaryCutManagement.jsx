import React, { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    CardHeader,
    CardBody,
    Button,
    Alert,
    Spinner,
    Badge,
    Table,
    Modal,
    ModalHeader,
    ModalBody,
    ModalFooter,
    Form,
    FormGroup,
    Label,
    Input,
    FormFeedback,
    Pagination,
    PaginationItem,
    PaginationLink
} from 'reactstrap';
import {
    ExclamationTriangle,
    Plus,
    Eye,
    Trash,
    Search,
    Filter,
    Send,
    CheckCircle,
    XCircle
} from 'react-bootstrap-icons';
import { authService } from '../../../services/authService';
import { extractErrorMessage } from '../../../utils/errorHandler';
import { host } from '../../../utils/fetch';

const SalaryCutManagement = () => {
    const [salaryCuts, setSalaryCuts] = useState([]);
    const [employees, setEmployees] = useState([]);
    const [periods, setPeriods] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    
    // Pagination
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    
    // Filters
    const [filters, setFilters] = useState({
        search: '',
        employee_id: '',
        period_id: '',
        statut: '',
        notification_status: ''
    });
    
    // Modals
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showDetailsModal, setShowDetailsModal] = useState(false);
    const [selectedCut, setSelectedCut] = useState(null);
    
    // Form data
    const [formData, setFormData] = useState({
        employee_id: '',
        period_id: '',
        montant_coupe: '',
        motif: '',
        send_notification: true
    });
    const [formLoading, setFormLoading] = useState(false);
    const [formErrors, setFormErrors] = useState({});

    useEffect(() => {
        loadData();
        loadEmployees();
        loadPeriods();
    }, [currentPage, filters]);

    const loadData = async () => {
        try {
            setLoading(true);
            setError('');

            const params = new URLSearchParams({
                page: currentPage,
                per_page: 10,
                ...filters
            });

            const response = await fetch(`${host}/api/payroll/salary-cuts?${params}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    const cutsData = data.data?.data || [];
                    setSalaryCuts(Array.isArray(cutsData) ? cutsData : []);
                    setTotalPages(Math.ceil((data.data?.total || 0) / (data.data?.per_page || 10)));
                } else {
                    setError(data.message || 'Erreur lors du chargement');
                    setSalaryCuts([]);
                }
            } else {
                setError('Erreur lors du chargement des coupures');
                setSalaryCuts([]);
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const loadEmployees = async () => {
        try {
            const response = await fetch(`${host}/api/payroll/employees?active_only=1`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // La structure est data.data.data car c'est une réponse paginée
                    const employeesData = data.data?.data || data.data || [];
                    setEmployees(Array.isArray(employeesData) ? employeesData : []);
                } else {
                    console.error('Erreur API employés:', data.message);
                    setEmployees([]);
                }
            } else {
                console.error('Erreur HTTP employés:', response.status);
                setEmployees([]);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des employés:', error);
            setEmployees([]);
        }
    };

    const loadPeriods = async () => {
        try {
            const response = await fetch(`${host}/api/payroll/periods`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // La structure est data.data.data car c'est une réponse paginée
                    const periodsData = data.data?.data || data.data || [];
                    setPeriods(Array.isArray(periodsData) ? periodsData : []);
                } else {
                    console.error('Erreur API périodes:', data.message);
                    setPeriods([]);
                }
            } else {
                console.error('Erreur HTTP périodes:', response.status);
                setPeriods([]);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des périodes:', error);
            setPeriods([]);
        }
    };

    const handleFilterChange = (field, value) => {
        setFilters(prev => ({
            ...prev,
            [field]: value
        }));
        setCurrentPage(1);
    };

    const resetFilters = () => {
        setFilters({
            search: '',
            employee_id: '',
            period_id: '',
            statut: '',
            notification_status: ''
        });
        setCurrentPage(1);
    };

    const handleCreateCut = async (e) => {
        e.preventDefault();
        setFormLoading(true);
        setFormErrors({});
        setError('');
        setSuccess('');

        try {
            const response = await fetch(`${host}/api/payroll/salary-cuts`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                setSuccess('Coupure de salaire créée avec succès');
                setShowCreateModal(false);
                setFormData({
                    employee_id: '',
                    period_id: '',
                    montant_coupe: '',
                    motif: '',
                    send_notification: true
                });
                loadData();
            } else {
                if (data.errors) {
                    setFormErrors(data.errors);
                } else {
                    setError(data.message || 'Erreur lors de la création');
                }
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setFormLoading(false);
        }
    };

    const handleCancelCut = async (cutId) => {
        if (!window.confirm('Êtes-vous sûr de vouloir annuler cette coupure ?')) {
            return;
        }

        try {
            const response = await fetch(`${host}/api/payroll/salary-cuts/${cutId}/cancel`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setSuccess('Coupure annulée avec succès');
                    loadData();
                } else {
                    setError(data.message || 'Erreur lors de l\'annulation');
                }
            } else {
                setError('Erreur lors de l\'annulation de la coupure');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        }
    };

    const handleResendNotification = async (cutId) => {
        try {
            const response = await fetch(`${host}/api/payroll/salary-cuts/${cutId}/resend-notification`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setSuccess('Notification renvoyée avec succès');
                    loadData();
                } else {
                    setError(data.message || 'Erreur lors de l\'envoi');
                }
            } else {
                setError('Erreur lors de l\'envoi de la notification');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        }
    };

    const showDetails = (cut) => {
        setSelectedCut(cut);
        setShowDetailsModal(true);
    };

    const formatMontant = (montant) => {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(montant) + ' FCFA';
    };

    const getStatusBadge = (statut) => {
        const statusConfig = {
            'active': { color: 'warning', label: 'Active' },
            'cancelled': { color: 'secondary', label: 'Annulée' }
        };
        const config = statusConfig[statut] || { color: 'secondary', label: statut };
        return <Badge color={config.color}>{config.label}</Badge>;
    };

    const getNotificationStatusBadge = (status) => {
        const statusConfig = {
            'sent': { color: 'success', icon: CheckCircle, label: 'Envoyée' },
            'failed': { color: 'danger', icon: XCircle, label: 'Échec' },
            'pending': { color: 'warning', icon: ExclamationTriangle, label: 'En attente' }
        };
        const config = statusConfig[status] || { color: 'secondary', icon: ExclamationTriangle, label: 'Inconnue' };
        const Icon = config.icon;
        
        return (
            <Badge color={config.color} className="d-flex align-items-center gap-1">
                <Icon size={12} />
                {config.label}
            </Badge>
        );
    };

    if (loading) {
        return (
            <Container fluid className="py-4">
                <div className="text-center">
                    <Spinner color="primary" />
                    <p className="mt-2">Chargement...</p>
                </div>
            </Container>
        );
    }

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 className="d-flex align-items-center gap-2 mb-1">
                                <ExclamationTriangle className="text-warning" />
                                Gestion des Coupures de Salaire
                            </h2>
                            <p className="text-muted mb-0">
                                Gérer les coupures de salaire et notifications
                            </p>
                        </div>
                        <Button 
                            color="warning"
                            onClick={() => setShowCreateModal(true)}
                            className="d-flex align-items-center gap-2"
                        >
                            <Plus size={16} />
                            Nouvelle Coupure
                        </Button>
                    </div>
                </Col>
            </Row>

            {/* Messages */}
            {error && <Alert color="danger" className="mb-4">{error}</Alert>}
            {success && <Alert color="success" className="mb-4">{success}</Alert>}

            {/* Filtres */}
            <Card className="mb-4">
                <CardHeader>
                    <div className="d-flex align-items-center gap-2">
                        <Filter size={16} />
                        <span>Filtres</span>
                    </div>
                </CardHeader>
                <CardBody>
                    <Row>
                        <Col md={3}>
                            <FormGroup>
                                <Label>Recherche</Label>
                                <div className="position-relative">
                                    <Input
                                        type="text"
                                        placeholder="Nom employé, motif..."
                                        value={filters.search}
                                        onChange={(e) => handleFilterChange('search', e.target.value)}
                                    />
                                    <Search className="position-absolute" style={{
                                        right: '10px',
                                        top: '50%',
                                        transform: 'translateY(-50%)',
                                        pointerEvents: 'none'
                                    }} size={16} />
                                </div>
                            </FormGroup>
                        </Col>
                        <Col md={2}>
                            <FormGroup>
                                <Label>Employé</Label>
                                <Input
                                    type="select"
                                    value={filters.employee_id}
                                    onChange={(e) => handleFilterChange('employee_id', e.target.value)}
                                >
                                    <option value="">Tous</option>
                                    {employees.map(emp => (
                                        <option key={emp.id} value={emp.id}>
                                            {emp.user.nom_complet}
                                        </option>
                                    ))}
                                </Input>
                            </FormGroup>
                        </Col>
                        <Col md={2}>
                            <FormGroup>
                                <Label>Période</Label>
                                <Input
                                    type="select"
                                    value={filters.period_id}
                                    onChange={(e) => handleFilterChange('period_id', e.target.value)}
                                >
                                    <option value="">Toutes</option>
                                    {periods.map(period => (
                                        <option key={period.id} value={period.id}>
                                            {period.libelle_periode}
                                        </option>
                                    ))}
                                </Input>
                            </FormGroup>
                        </Col>
                        <Col md={2}>
                            <FormGroup>
                                <Label>Statut</Label>
                                <Input
                                    type="select"
                                    value={filters.statut}
                                    onChange={(e) => handleFilterChange('statut', e.target.value)}
                                >
                                    <option value="">Tous</option>
                                    <option value="active">Active</option>
                                    <option value="cancelled">Annulée</option>
                                </Input>
                            </FormGroup>
                        </Col>
                        <Col md={2}>
                            <FormGroup>
                                <Label>Notification</Label>
                                <Input
                                    type="select"
                                    value={filters.notification_status}
                                    onChange={(e) => handleFilterChange('notification_status', e.target.value)}
                                >
                                    <option value="">Toutes</option>
                                    <option value="sent">Envoyée</option>
                                    <option value="failed">Échec</option>
                                    <option value="pending">En attente</option>
                                </Input>
                            </FormGroup>
                        </Col>
                        <Col md={1} className="d-flex align-items-end">
                            <Button
                                color="secondary"
                                outline
                                onClick={resetFilters}
                                className="w-100"
                            >
                                Reset
                            </Button>
                        </Col>
                    </Row>
                </CardBody>
            </Card>

            {/* Table des coupures */}
            <Card>
                <CardBody className="p-0">
                    <Table responsive className="mb-0">
                        <thead className="table-light">
                            <tr>
                                <th>Employé</th>
                                <th>Période</th>
                                <th>Montant</th>
                                <th>Motif</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Notification</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {salaryCuts.map((cut) => (
                                <tr key={cut.id}>
                                    <td>
                                        <div>
                                            <strong>{cut.employee?.user?.nom_complet}</strong>
                                            <br />
                                            <small className="text-muted">
                                                {cut.employee?.user?.email}
                                            </small>
                                        </div>
                                    </td>
                                    <td>{cut.period?.libelle_periode}</td>
                                    <td>
                                        <span className="fw-bold text-danger">
                                            {formatMontant(cut.montant_coupe)}
                                        </span>
                                    </td>
                                    <td>
                                        <div style={{ maxWidth: '200px' }}>
                                            {cut.motif.length > 50 
                                                ? cut.motif.substring(0, 50) + '...'
                                                : cut.motif
                                            }
                                        </div>
                                    </td>
                                    <td>
                                        {new Date(cut.date_coupure).toLocaleDateString('fr-FR')}
                                    </td>
                                    <td>{getStatusBadge(cut.statut)}</td>
                                    <td>
                                        <div className="d-flex flex-column gap-1">
                                            {getNotificationStatusBadge(cut.notification_status)}
                                            {cut.notification_status === 'failed' && (
                                                <Button
                                                    color="link"
                                                    size="sm"
                                                    className="p-0 text-decoration-none"
                                                    onClick={() => handleResendNotification(cut.id)}
                                                >
                                                    <Send size={12} className="me-1" />
                                                    Renvoyer
                                                </Button>
                                            )}
                                        </div>
                                    </td>
                                    <td>
                                        <div className="d-flex gap-1">
                                            <Button
                                                color="info"
                                                size="sm"
                                                onClick={() => showDetails(cut)}
                                                title="Voir détails"
                                            >
                                                <Eye size={14} />
                                            </Button>
                                            {cut.statut === 'active' && (
                                                <Button
                                                    color="danger"
                                                    size="sm"
                                                    onClick={() => handleCancelCut(cut.id)}
                                                    title="Annuler coupure"
                                                >
                                                    <Trash size={14} />
                                                </Button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                    
                    {salaryCuts.length === 0 && (
                        <div className="text-center py-4">
                            <p className="text-muted">Aucune coupure de salaire trouvée</p>
                        </div>
                    )}
                </CardBody>
            </Card>

            {/* Pagination */}
            {totalPages > 1 && (
                <div className="d-flex justify-content-center mt-4">
                    <Pagination>
                        <PaginationItem disabled={currentPage === 1}>
                            <PaginationLink
                                previous
                                onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                            />
                        </PaginationItem>
                        
                        {[...Array(totalPages)].map((_, i) => (
                            <PaginationItem key={i + 1} active={currentPage === i + 1}>
                                <PaginationLink onClick={() => setCurrentPage(i + 1)}>
                                    {i + 1}
                                </PaginationLink>
                            </PaginationItem>
                        ))}
                        
                        <PaginationItem disabled={currentPage === totalPages}>
                            <PaginationLink
                                next
                                onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                            />
                        </PaginationItem>
                    </Pagination>
                </div>
            )}

            {/* Modal Création */}
            <Modal isOpen={showCreateModal} toggle={() => setShowCreateModal(false)} size="lg">
                <ModalHeader toggle={() => setShowCreateModal(false)}>
                    Nouvelle Coupure de Salaire
                </ModalHeader>
                <Form onSubmit={handleCreateCut}>
                    <ModalBody>
                        <Row>
                            <Col md={6}>
                                <FormGroup>
                                    <Label for="employee_id">Employé *</Label>
                                    <Input
                                        type="select"
                                        id="employee_id"
                                        value={formData.employee_id}
                                        onChange={(e) => setFormData(prev => ({
                                            ...prev,
                                            employee_id: e.target.value
                                        }))}
                                        invalid={!!formErrors.employee_id}
                                        required
                                    >
                                        <option value="">Sélectionner un employé</option>
                                        {employees.map(emp => (
                                            <option key={emp.id} value={emp.id}>
                                                {emp.user.nom_complet} - {formatMontant(emp.salaire_base)}
                                            </option>
                                        ))}
                                    </Input>
                                    {formErrors.employee_id && (
                                        <FormFeedback>
                                            {formErrors.employee_id[0]}
                                        </FormFeedback>
                                    )}
                                </FormGroup>
                            </Col>
                            <Col md={6}>
                                <FormGroup>
                                    <Label for="period_id">Période *</Label>
                                    <Input
                                        type="select"
                                        id="period_id"
                                        value={formData.period_id}
                                        onChange={(e) => setFormData(prev => ({
                                            ...prev,
                                            period_id: e.target.value
                                        }))}
                                        invalid={!!formErrors.period_id}
                                        required
                                    >
                                        <option value="">Sélectionner une période</option>
                                        {periods.map(period => (
                                            <option key={period.id} value={period.id}>
                                                {period.libelle_periode}
                                            </option>
                                        ))}
                                    </Input>
                                    {formErrors.period_id && (
                                        <FormFeedback>
                                            {formErrors.period_id[0]}
                                        </FormFeedback>
                                    )}
                                </FormGroup>
                            </Col>
                        </Row>
                        
                        <Row>
                            <Col md={6}>
                                <FormGroup>
                                    <Label for="montant_coupe">Montant de la coupure (FCFA) *</Label>
                                    <Input
                                        type="number"
                                        id="montant_coupe"
                                        placeholder="Ex: 50000"
                                        value={formData.montant_coupe}
                                        onChange={(e) => setFormData(prev => ({
                                            ...prev,
                                            montant_coupe: e.target.value
                                        }))}
                                        invalid={!!formErrors.montant_coupe}
                                        required
                                        min="0"
                                    />
                                    {formErrors.montant_coupe && (
                                        <FormFeedback>
                                            {formErrors.montant_coupe[0]}
                                        </FormFeedback>
                                    )}
                                </FormGroup>
                            </Col>
                            <Col md={6}>
                                <FormGroup check className="mt-4">
                                    <Label check>
                                        <Input
                                            type="checkbox"
                                            checked={formData.send_notification}
                                            onChange={(e) => setFormData(prev => ({
                                                ...prev,
                                                send_notification: e.target.checked
                                            }))}
                                        />
                                        Envoyer notification WhatsApp
                                    </Label>
                                </FormGroup>
                            </Col>
                        </Row>
                        
                        <FormGroup>
                            <Label for="motif">Motif de la coupure *</Label>
                            <Input
                                type="textarea"
                                id="motif"
                                placeholder="Ex: Absence injustifiée du 15 au 17 janvier"
                                value={formData.motif}
                                onChange={(e) => setFormData(prev => ({
                                    ...prev,
                                    motif: e.target.value
                                }))}
                                invalid={!!formErrors.motif}
                                required
                                rows="3"
                            />
                            {formErrors.motif && (
                                <FormFeedback>
                                    {formErrors.motif[0]}
                                </FormFeedback>
                            )}
                        </FormGroup>
                    </ModalBody>
                    <ModalFooter>
                        <Button
                            type="button"
                            color="secondary"
                            onClick={() => setShowCreateModal(false)}
                            disabled={formLoading}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="submit"
                            color="warning"
                            disabled={formLoading}
                        >
                            {formLoading ? (
                                <>
                                    <Spinner size="sm" className="me-2" />
                                    Création...
                                </>
                            ) : (
                                'Créer la Coupure'
                            )}
                        </Button>
                    </ModalFooter>
                </Form>
            </Modal>

            {/* Modal Détails */}
            <Modal isOpen={showDetailsModal} toggle={() => setShowDetailsModal(false)} size="lg">
                <ModalHeader toggle={() => setShowDetailsModal(false)}>
                    Détails de la Coupure
                </ModalHeader>
                <ModalBody>
                    {selectedCut && (
                        <div>
                            <Row className="mb-3">
                                <Col md={6}>
                                    <strong>Employé:</strong><br />
                                    {selectedCut.employee?.user?.nom_complet}<br />
                                    <small className="text-muted">
                                        {selectedCut.employee?.user?.email}
                                    </small>
                                </Col>
                                <Col md={6}>
                                    <strong>Période:</strong><br />
                                    {selectedCut.period?.libelle_periode}<br />
                                    <small className="text-muted">
                                        Du {new Date(selectedCut.period?.date_debut).toLocaleDateString('fr-FR')} au {new Date(selectedCut.period?.date_fin).toLocaleDateString('fr-FR')}
                                    </small>
                                </Col>
                            </Row>
                            
                            <Row className="mb-3">
                                <Col md={6}>
                                    <strong>Montant coupé:</strong><br />
                                    <span className="fs-5 fw-bold text-danger">
                                        {formatMontant(selectedCut.montant_coupe)}
                                    </span>
                                </Col>
                                <Col md={6}>
                                    <strong>Date de la coupure:</strong><br />
                                    {new Date(selectedCut.date_coupure).toLocaleDateString('fr-FR')}
                                </Col>
                            </Row>
                            
                            <Row className="mb-3">
                                <Col md={6}>
                                    <strong>Statut:</strong><br />
                                    {getStatusBadge(selectedCut.statut)}
                                </Col>
                                <Col md={6}>
                                    <strong>Notification:</strong><br />
                                    {getNotificationStatusBadge(selectedCut.notification_status)}
                                </Col>
                            </Row>
                            
                            <div className="mb-3">
                                <strong>Motif:</strong><br />
                                <div className="bg-light p-3 rounded">
                                    {selectedCut.motif}
                                </div>
                            </div>
                            
                            {selectedCut.notification_error && (
                                <div className="mb-3">
                                    <strong>Erreur de notification:</strong><br />
                                    <div className="bg-danger-subtle p-3 rounded">
                                        <small>{selectedCut.notification_error}</small>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </ModalBody>
                <ModalFooter>
                    <Button color="secondary" onClick={() => setShowDetailsModal(false)}>
                        Fermer
                    </Button>
                    {selectedCut?.notification_status === 'failed' && (
                        <Button
                            color="primary"
                            onClick={() => {
                                handleResendNotification(selectedCut.id);
                                setShowDetailsModal(false);
                            }}
                        >
                            <Send size={16} className="me-1" />
                            Renvoyer Notification
                        </Button>
                    )}
                </ModalFooter>
            </Modal>
        </Container>
    );
};

export default SalaryCutManagement;