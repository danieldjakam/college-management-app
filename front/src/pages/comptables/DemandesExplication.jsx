import React, { useState, useEffect, useCallback } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Form,
    Table,
    Alert,
    Spinner,
    Badge,
    Modal,
    Tabs,
    Tab,
    InputGroup,
    Dropdown,
    OverlayTrigger,
    Tooltip,
    Pagination
} from 'react-bootstrap';
import {
    Plus,
    Search,
    Filter,
    Eye,
    Reply,
    XCircle,
    CheckCircle,
    Clock,
    AlertTriangle,
    FileEarmarkText,
    PersonFill,
    Calendar,
    ExclamationTriangle,
    InfoCircle,
    Archive
} from 'react-bootstrap-icons';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { authService } from '../../services/authService';
import { extractErrorMessage } from '../../utils/errorHandler';
import { host } from '../../utils/fetch';
import { useAuth } from '../../hooks/useAuth';

const DemandesExplication = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const { user } = useAuth();
    const [demandes, setDemandes] = useState([]);
    const [filteredDemandes, setFilteredDemandes] = useState([]);
    const [statistiques, setStatistiques] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    
    // Détecter si c'est un personnel (non-comptable) qui accède à ses demandes
    const isPersonnelView = location.pathname === '/mes-demandes-explication';
    const isComptable = user && (user.role === 'accountant' || user.role === 'comptable_superieur');
    
    // États pour les filtres
    const [activeTab, setActiveTab] = useState(isPersonnelView ? 'recues' : 'emises');
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [typeFilter, setTypeFilter] = useState('');
    const [priorityFilter, setPriorityFilter] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    
    // Modal pour voir les détails
    const [showDetailModal, setShowDetailModal] = useState(false);
    const [selectedDemande, setSelectedDemande] = useState(null);
    const [responseText, setResponseText] = useState('');
    const [showResponseForm, setShowResponseForm] = useState(false);

    // Configuration des filtres
    const statusOptions = [
        { value: '', label: 'Tous les statuts' },
        { value: 'brouillon', label: 'Brouillon', color: 'secondary' },
        { value: 'envoyee', label: 'Envoyée', color: 'primary' },
        { value: 'lue', label: 'Lue', color: 'info' },
        { value: 'repondue', label: 'Répondue', color: 'success' },
        { value: 'cloturee', label: 'Clôturée', color: 'dark' }
    ];

    const typeOptions = [
        { value: '', label: 'Tous les types' },
        { value: 'financier', label: 'Financier' },
        { value: 'absence', label: 'Absence' },
        { value: 'retard', label: 'Retard' },
        { value: 'disciplinaire', label: 'Disciplinaire' },
        { value: 'autre', label: 'Autre' }
    ];

    const priorityOptions = [
        { value: '', label: 'Toutes les priorités' },
        { value: 'basse', label: 'Basse', color: 'success' },
        { value: 'normale', label: 'Normale', color: 'info' },
        { value: 'haute', label: 'Haute', color: 'warning' },
        { value: 'urgente', label: 'Urgente', color: 'danger' }
    ];

    // Charger les demandes
    const loadDemandes = useCallback(async () => {
        try {
            setLoading(true);
            setError('');

            const params = new URLSearchParams({
                vue: activeTab,
                page: currentPage,
                per_page: 10
            });

            if (searchTerm) params.append('search', searchTerm);
            if (statusFilter) params.append('statut', statusFilter);
            if (typeFilter) params.append('type', typeFilter);
            if (priorityFilter) params.append('priorite', priorityFilter);

            const response = await fetch(`${host}/api/demandes-explication?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`Erreur ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                setDemandes(data.data.data || []);
                setFilteredDemandes(data.data.data || []);
                setCurrentPage(data.data.current_page || 1);
                setTotalPages(data.data.last_page || 1);
                setStatistiques(data.stats || {});
            } else {
                setError(data.message || 'Erreur lors du chargement des demandes');
            }
        } catch (error) {
            console.error('Erreur lors du chargement:', error);
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    }, [activeTab, currentPage, searchTerm, statusFilter, typeFilter, priorityFilter]);

    useEffect(() => {
        loadDemandes();
    }, [loadDemandes]);

    // Voir les détails d'une demande
    const handleViewDetails = async (demandeId) => {
        try {
            const response = await fetch(`${host}/api/demandes-explication/${demandeId}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                setSelectedDemande(data.data);
                setShowDetailModal(true);
            } else {
                setError('Erreur lors du chargement des détails');
            }
        } catch (error) {
            setError('Erreur lors du chargement des détails');
        }
    };

    // Répondre à une demande
    const handleResponse = async () => {
        if (!responseText.trim()) {
            setError('Veuillez saisir une réponse');
            return;
        }

        try {
            setLoading(true);
            const response = await fetch(`${host}/api/demandes-explication/${selectedDemande.id}/repondre`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    reponse: responseText
                })
            });

            const data = await response.json();

            if (data.success) {
                setSuccess('Réponse envoyée avec succès');
                setResponseText('');
                setShowResponseForm(false);
                setShowDetailModal(false);
                loadDemandes();
            } else {
                setError(data.message || 'Erreur lors de l\'envoi de la réponse');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    // Clôturer une demande
    const handleClose = async (demandeId) => {
        if (!window.confirm('Êtes-vous sûr de vouloir clôturer cette demande ?')) {
            return;
        }

        try {
            setLoading(true);
            const response = await fetch(`${host}/api/demandes-explication/${demandeId}/cloturer`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                setSuccess('Demande clôturée avec succès');
                loadDemandes();
                setShowDetailModal(false);
            } else {
                setError(data.message || 'Erreur lors de la clôture');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    // Réinitialiser les filtres
    const resetFilters = () => {
        setSearchTerm('');
        setStatusFilter('');
        setTypeFilter('');
        setPriorityFilter('');
        setCurrentPage(1);
    };

    // Obtenir le badge de statut
    const getStatusBadge = (statut) => {
        const statusConfig = statusOptions.find(s => s.value === statut);
        return (
            <Badge bg={statusConfig?.color || 'secondary'}>
                {statusConfig?.label || statut}
            </Badge>
        );
    };

    // Obtenir le badge de priorité
    const getPriorityBadge = (priorite) => {
        const priorityConfig = priorityOptions.find(p => p.value === priorite);
        return (
            <Badge bg={priorityConfig?.color || 'secondary'} className="me-1">
                {priorityConfig?.label || priorite}
            </Badge>
        );
    };

    // Obtenir l'icône de statut
    const getStatusIcon = (statut) => {
        switch (statut) {
            case 'brouillon': return <FileEarmarkText className="text-secondary" />;
            case 'envoyee': return <Clock className="text-primary" />;
            case 'lue': return <Eye className="text-info" />;
            case 'repondue': return <CheckCircle className="text-success" />;
            case 'cloturee': return <Archive className="text-dark" />;
            default: return <InfoCircle className="text-muted" />;
        }
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 className="d-flex align-items-center gap-2 mb-1">
                                <ExclamationTriangle className="text-warning" />
                                {isPersonnelView ? 'Mes Demandes d\'Explication' : 'Demandes d\'Explication (D.E)'}
                            </h2>
                            <p className="text-muted mb-0">
                                {isPersonnelView 
                                    ? 'Demandes d\'explication reçues et à traiter' 
                                    : 'Gestion des demandes d\'explication au personnel'}
                            </p>
                        </div>
                        {isComptable && !isPersonnelView && (
                            <Button
                                variant="primary"
                                as={Link}
                                to="/demandes-explication/nouvelle"
                                className="d-flex align-items-center gap-2"
                            >
                                <Plus size={16} />
                                Nouvelle demande
                            </Button>
                        )}
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

            {/* Statistiques */}
            {statistiques && Object.keys(statistiques).length > 0 && (
                <Row className="mb-4">
                    <Col md={3}>
                        <Card className="bg-primary text-white h-100">
                            <Card.Body className="text-center">
                                <h3>{statistiques.total || 0}</h3>
                                <p className="mb-0">Total</p>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="bg-warning text-dark h-100">
                            <Card.Body className="text-center">
                                <h3>{statistiques.en_attente || 0}</h3>
                                <p className="mb-0">En attente</p>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="bg-success text-white h-100">
                            <Card.Body className="text-center">
                                <h3>{statistiques.repondues || 0}</h3>
                                <p className="mb-0">Répondues</p>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="bg-info text-white h-100">
                            <Card.Body className="text-center">
                                <h3>{demandes.length}</h3>
                                <p className="mb-0">Affichées</p>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Onglets et Filtres */}
            <Card className="mb-4">
                <Card.Header>
                    <Tabs 
                        activeKey={activeTab} 
                        onSelect={(k) => {
                            setActiveTab(k);
                            setCurrentPage(1);
                        }}
                        className="mb-3"
                    >
                        {isPersonnelView ? (
                            // Pour le personnel : seulement ses demandes reçues
                            [
                                <Tab key="recues" eventKey="recues" title="Demandes reçues" />,
                                <Tab key="toutes" eventKey="toutes" title="Toutes mes demandes" />
                            ]
                        ) : (
                            // Pour les comptables : toutes les vues
                            [
                                <Tab key="emises" eventKey="emises" title="Demandes émises" />,
                                <Tab key="recues" eventKey="recues" title="Demandes reçues" />,
                                <Tab key="toutes" eventKey="toutes" title="Toutes les demandes" />
                            ]
                        )}
                    </Tabs>

                    <Row>
                        <Col lg={3} md={6} className="mb-2">
                            <InputGroup size="sm">
                                <InputGroup.Text><Search /></InputGroup.Text>
                                <Form.Control
                                    type="text"
                                    placeholder="Rechercher..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </InputGroup>
                        </Col>
                        <Col lg={2} md={6} className="mb-2">
                            <Form.Select
                                size="sm"
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
                        <Col lg={2} md={6} className="mb-2">
                            <Form.Select
                                size="sm"
                                value={typeFilter}
                                onChange={(e) => setTypeFilter(e.target.value)}
                            >
                                {typeOptions.map(option => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </Form.Select>
                        </Col>
                        <Col lg={2} md={6} className="mb-2">
                            <Form.Select
                                size="sm"
                                value={priorityFilter}
                                onChange={(e) => setPriorityFilter(e.target.value)}
                            >
                                {priorityOptions.map(option => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </Form.Select>
                        </Col>
                        <Col lg={3} md={12} className="mb-2">
                            <div className="d-flex gap-2">
                                <Button
                                    variant="outline-secondary"
                                    size="sm"
                                    onClick={resetFilters}
                                    className="flex-grow-1"
                                >
                                    <Filter className="me-1" />
                                    Réinitialiser
                                </Button>
                                <Button
                                    variant="primary"
                                    size="sm"
                                    onClick={() => setCurrentPage(1)}
                                    className="flex-grow-1"
                                >
                                    <Search className="me-1" />
                                    Filtrer
                                </Button>
                            </div>
                        </Col>
                    </Row>
                </Card.Header>
            </Card>

            {/* Liste des demandes */}
            <Card>
                <Card.Header>
                    <div className="d-flex justify-content-between align-items-center">
                        <h5 className="mb-0">
                            {activeTab === 'emises' && 'Demandes émises'}
                            {activeTab === 'recues' && 'Demandes reçues'}
                            {activeTab === 'toutes' && 'Toutes les demandes'}
                        </h5>
                        {demandes.length > 0 && (
                            <Badge bg="secondary">{demandes.length} demande(s)</Badge>
                        )}
                    </div>
                </Card.Header>
                <Card.Body className="p-0">
                    {loading ? (
                        <div className="text-center py-5">
                            <Spinner animation="border" variant="primary" />
                            <p className="mt-3 text-muted">Chargement des demandes...</p>
                        </div>
                    ) : demandes.length === 0 ? (
                        <div className="text-center py-5">
                            <ExclamationTriangle size={48} className="text-muted mb-3" />
                            <h5 className="text-muted">Aucune demande trouvée</h5>
                            <p className="text-muted">
                                {activeTab === 'emises' && 'Vous n\'avez pas encore émis de demandes d\'explication.'}
                                {activeTab === 'recues' && 'Vous n\'avez pas reçu de demandes d\'explication.'}
                                {activeTab === 'toutes' && 'Aucune demande ne correspond aux critères de recherche.'}
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="table-responsive">
                                <Table striped hover className="mb-0">
                                    <thead className="table-dark">
                                        <tr>
                                            <th width="40">#</th>
                                            <th>Sujet</th>
                                            <th width="150">{activeTab === 'emises' ? 'Destinataire' : 'Émetteur'}</th>
                                            <th width="100">Type</th>
                                            <th width="80">Priorité</th>
                                            <th width="100">Statut</th>
                                            <th width="120">Date</th>
                                            <th width="120">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {demandes.map((demande, index) => (
                                            <tr key={demande.id}>
                                                <td className="text-center">{((currentPage - 1) * 10) + index + 1}</td>
                                                <td>
                                                    <div className="d-flex align-items-center gap-2">
                                                        {getStatusIcon(demande.statut)}
                                                        <div>
                                                            <strong>{demande.sujet}</strong>
                                                            <br />
                                                            <small className="text-muted">
                                                                {demande.message.length > 60 
                                                                    ? demande.message.substring(0, 60) + '...'
                                                                    : demande.message
                                                                }
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div className="d-flex align-items-center gap-2">
                                                        <PersonFill className="text-muted" />
                                                        <div>
                                                            <strong>
                                                                {activeTab === 'emises' 
                                                                    ? demande.destinataire?.name 
                                                                    : demande.emetteur?.name
                                                                }
                                                            </strong>
                                                            <br />
                                                            <small className="text-muted">
                                                                {activeTab === 'emises'
                                                                    ? demande.destinataire?.email
                                                                    : demande.emetteur?.email
                                                                }
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <Badge bg="light" text="dark">
                                                        {typeOptions.find(t => t.value === demande.type)?.label || demande.type}
                                                    </Badge>
                                                </td>
                                                <td>{getPriorityBadge(demande.priorite)}</td>
                                                <td>{getStatusBadge(demande.statut)}</td>
                                                <td>
                                                    <div className="d-flex align-items-center gap-1">
                                                        <Calendar size={14} className="text-muted" />
                                                        <small>
                                                            {new Date(demande.created_at).toLocaleDateString('fr-FR')}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div className="d-flex gap-1">
                                                        <OverlayTrigger
                                                            overlay={<Tooltip>Voir les détails</Tooltip>}
                                                        >
                                                            <Button
                                                                variant="outline-info"
                                                                size="sm"
                                                                onClick={() => handleViewDetails(demande.id)}
                                                            >
                                                                <Eye size={14} />
                                                            </Button>
                                                        </OverlayTrigger>
                                                        
                                                        {activeTab === 'recues' && ['envoyee', 'lue'].includes(demande.statut) && (
                                                            <OverlayTrigger
                                                                overlay={<Tooltip>Répondre</Tooltip>}
                                                            >
                                                                <Button
                                                                    variant="outline-success"
                                                                    size="sm"
                                                                    onClick={() => {
                                                                        setSelectedDemande(demande);
                                                                        setShowResponseForm(true);
                                                                    }}
                                                                >
                                                                    <Reply size={14} />
                                                                </Button>
                                                            </OverlayTrigger>
                                                        )}
                                                        
                                                        {activeTab === 'emises' && demande.statut !== 'cloturee' && isComptable && (
                                                            <OverlayTrigger
                                                                overlay={<Tooltip>Clôturer</Tooltip>}
                                                            >
                                                                <Button
                                                                    variant="outline-secondary"
                                                                    size="sm"
                                                                    onClick={() => handleClose(demande.id)}
                                                                >
                                                                    <XCircle size={14} />
                                                                </Button>
                                                            </OverlayTrigger>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            {totalPages > 1 && (
                                <div className="d-flex justify-content-center p-3">
                                    <Pagination>
                                        <Pagination.First 
                                            onClick={() => setCurrentPage(1)}
                                            disabled={currentPage === 1}
                                        />
                                        <Pagination.Prev 
                                            onClick={() => setCurrentPage(currentPage - 1)}
                                            disabled={currentPage === 1}
                                        />
                                        
                                        {[...Array(Math.min(5, totalPages))].map((_, i) => {
                                            const pageNum = Math.max(1, currentPage - 2) + i;
                                            if (pageNum <= totalPages) {
                                                return (
                                                    <Pagination.Item
                                                        key={pageNum}
                                                        active={pageNum === currentPage}
                                                        onClick={() => setCurrentPage(pageNum)}
                                                    >
                                                        {pageNum}
                                                    </Pagination.Item>
                                                );
                                            }
                                            return null;
                                        })}
                                        
                                        <Pagination.Next 
                                            onClick={() => setCurrentPage(currentPage + 1)}
                                            disabled={currentPage === totalPages}
                                        />
                                        <Pagination.Last 
                                            onClick={() => setCurrentPage(totalPages)}
                                            disabled={currentPage === totalPages}
                                        />
                                    </Pagination>
                                </div>
                            )}
                        </>
                    )}
                </Card.Body>
            </Card>

            {/* Modal de détails */}
            <Modal show={showDetailModal} onHide={() => setShowDetailModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title className="d-flex align-items-center gap-2">
                        <ExclamationTriangle className="text-warning" />
                        Détails de la demande
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {selectedDemande && (
                        <div>
                            <Row className="mb-3">
                                <Col md={6}>
                                    <strong>Sujet:</strong><br />
                                    {selectedDemande.sujet}
                                </Col>
                                <Col md={6}>
                                    <strong>Statut:</strong><br />
                                    {getStatusBadge(selectedDemande.statut)}
                                </Col>
                            </Row>
                            <Row className="mb-3">
                                <Col md={4}>
                                    <strong>Type:</strong><br />
                                    <Badge bg="light" text="dark">
                                        {typeOptions.find(t => t.value === selectedDemande.type)?.label}
                                    </Badge>
                                </Col>
                                <Col md={4}>
                                    <strong>Priorité:</strong><br />
                                    {getPriorityBadge(selectedDemande.priorite)}
                                </Col>
                                <Col md={4}>
                                    <strong>Date de création:</strong><br />
                                    {new Date(selectedDemande.created_at).toLocaleString('fr-FR')}
                                </Col>
                            </Row>
                            <Row className="mb-3">
                                <Col md={6}>
                                    <strong>Émetteur:</strong><br />
                                    {selectedDemande.emetteur?.name}<br />
                                    <small className="text-muted">{selectedDemande.emetteur?.email}</small>
                                </Col>
                                <Col md={6}>
                                    <strong>Destinataire:</strong><br />
                                    {selectedDemande.destinataire?.name}<br />
                                    <small className="text-muted">{selectedDemande.destinataire?.email}</small>
                                </Col>
                            </Row>
                            <div className="mb-3">
                                <strong>Message:</strong><br />
                                <div className="border p-3 mt-2 rounded bg-light">
                                    {selectedDemande.message}
                                </div>
                            </div>
                            {selectedDemande.reponse && (
                                <div className="mb-3">
                                    <strong>Réponse:</strong><br />
                                    <div className="border p-3 mt-2 rounded bg-success bg-opacity-10">
                                        {selectedDemande.reponse}
                                    </div>
                                    {selectedDemande.date_reponse && (
                                        <small className="text-muted">
                                            Répondu le {new Date(selectedDemande.date_reponse).toLocaleString('fr-FR')}
                                        </small>
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowDetailModal(false)}>
                        Fermer
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Modal de réponse */}
            <Modal show={showResponseForm} onHide={() => setShowResponseForm(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Répondre à la demande</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {selectedDemande && (
                        <>
                            <div className="mb-3">
                                <strong>Sujet:</strong> {selectedDemande.sujet}
                            </div>
                            <Form.Group>
                                <Form.Label>Votre réponse</Form.Label>
                                <Form.Control
                                    as="textarea"
                                    rows={4}
                                    value={responseText}
                                    onChange={(e) => setResponseText(e.target.value)}
                                    placeholder="Saisissez votre réponse..."
                                />
                            </Form.Group>
                        </>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowResponseForm(false)}>
                        Annuler
                    </Button>
                    <Button 
                        variant="primary" 
                        onClick={handleResponse}
                        disabled={!responseText.trim()}
                    >
                        Envoyer la réponse
                    </Button>
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default DemandesExplication;