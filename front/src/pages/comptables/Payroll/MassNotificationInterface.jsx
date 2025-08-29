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
    Progress,
    ListGroup,
    ListGroupItem
} from 'reactstrap';
import {
    SendFill,
    People,
    CheckCircle,
    XCircle,
    ExclamationTriangle,
    Clock,
    Megaphone,
    Eye,
    BarChart
} from 'react-bootstrap-icons';
import { authService } from '../../../services/authService';
import { extractErrorMessage } from '../../../utils/errorHandler';
import { host } from '../../../utils/fetch';

const MassNotificationInterface = () => {
    const [periods, setPeriods] = useState([]);
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    
    // Modal states
    const [showSendModal, setShowSendModal] = useState(false);
    const [showDetailsModal, setShowDetailsModal] = useState(false);
    const [selectedNotification, setSelectedNotification] = useState(null);
    
    // Send notification form
    const [sendForm, setSendForm] = useState({
        period_id: '',
        message_template: 'default',
        custom_message: ''
    });
    const [sendLoading, setSendLoading] = useState(false);
    const [sendProgress, setSendProgress] = useState(null);
    
    // Statistics
    const [stats, setStats] = useState({
        total_sent_today: 0,
        total_employees: 0,
        success_rate: 0,
        recent_notifications: []
    });

    useEffect(() => {
        loadData();
        loadPeriods();
        loadStats();
    }, []);

    const loadData = async () => {
        try {
            setLoading(true);
            setError('');

            const response = await fetch(`${host}/api/payroll/mass-notifications`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Handle Laravel pagination structure
                    const notificationsData = data.data?.data || data.data || [];
                    setNotifications(Array.isArray(notificationsData) ? notificationsData : []);
                } else {
                    setError(data.message || 'Erreur lors du chargement');
                }
            } else {
                setError('Erreur lors du chargement des notifications');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const loadPeriods = async () => {
        try {
            const response = await fetch(`${host}/api/payroll/periods?status=validee,payee`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Handle Laravel pagination structure
                    const periodsData = data.data?.data || data.data || [];
                    setPeriods(Array.isArray(periodsData) ? periodsData : []);
                }
            }
        } catch (error) {
            console.error('Erreur lors du chargement des périodes:', error);
        }
    };

    const loadStats = async () => {
        try {
            const response = await fetch(`${host}/api/payroll/mass-notifications/stats`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setStats(data.data);
                }
            }
        } catch (error) {
            console.error('Erreur lors du chargement des statistiques:', error);
        }
    };

    const handleSendMassNotification = async (e) => {
        e.preventDefault();
        setSendLoading(true);
        setSendProgress({ sent: 0, total: 0, errors: [] });
        setError('');
        setSuccess('');

        try {
            const response = await fetch(`${host}/api/payroll/periods/${sendForm.period_id}/send-mass-notifications`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message_template: sendForm.message_template,
                    custom_message: sendForm.custom_message || undefined
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                setSendProgress(data.data.progress);
                setSuccess(`Notifications envoyées: ${data.data.progress.sent}/${data.data.progress.total} succès`);
                setShowSendModal(false);
                setSendForm({
                    period_id: '',
                    message_template: 'default',
                    custom_message: ''
                });
                loadData();
                loadStats();
            } else {
                setError(data.message || 'Erreur lors de l\'envoi des notifications');
                if (data.data?.progress) {
                    setSendProgress(data.data.progress);
                }
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setSendLoading(false);
        }
    };

    const showNotificationDetails = (notification) => {
        setSelectedNotification(notification);
        setShowDetailsModal(true);
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            'sent': { color: 'success', icon: CheckCircle, label: 'Envoyée' },
            'failed': { color: 'danger', icon: XCircle, label: 'Échec' },
            'pending': { color: 'warning', icon: Clock, label: 'En attente' },
            'processing': { color: 'info', icon: ExclamationTriangle, label: 'Traitement' }
        };
        const config = statusConfig[status] || { color: 'secondary', icon: ExclamationTriangle, label: status };
        const Icon = config.icon;
        
        return (
            <Badge color={config.color} className="d-flex align-items-center gap-1">
                <Icon size={12} />
                {config.label}
            </Badge>
        );
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleString('fr-FR', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const messageTemplates = {
        'default': 'Bonjour {nom}, votre salaire pour la période {periode} est disponible à la comptabilité. Merci de passer le récupérer.',
        'urgent': '🚨 URGENT: {nom}, votre salaire {periode} est disponible. Merci de passer aujourd\'hui à la comptabilité.',
        'reminder': 'Rappel: {nom}, votre salaire {periode} vous attend à la comptabilité depuis quelques jours.',
        'custom': ''
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
                                <Megaphone className="text-info" />
                                Notifications Massives
                            </h2>
                            <p className="text-muted mb-0">
                                Envoyer des notifications WhatsApp à tous les employés
                            </p>
                        </div>
                        <Button 
                            color="info"
                            onClick={() => setShowSendModal(true)}
                            className="d-flex align-items-center gap-2"
                        >
                            <SendFill size={16} />
                            Nouvelle Notification
                        </Button>
                    </div>
                </Col>
            </Row>

            {/* Messages */}
            {error && <Alert color="danger" className="mb-4">{error}</Alert>}
            {success && <Alert color="success" className="mb-4">{success}</Alert>}

            {/* Progress Card */}
            {sendProgress && (
                <Card className="mb-4">
                    <CardBody>
                        <h5>Progression de l'envoi</h5>
                        <Progress 
                            value={(sendProgress.sent / sendProgress.total) * 100} 
                            className="mb-2"
                        />
                        <div className="d-flex justify-content-between text-sm">
                            <span>{sendProgress.sent} / {sendProgress.total} envoyées</span>
                            <span>{Math.round((sendProgress.sent / sendProgress.total) * 100)}%</span>
                        </div>
                        {sendProgress.errors.length > 0 && (
                            <div className="mt-3">
                                <h6 className="text-danger">Erreurs ({sendProgress.errors.length}):</h6>
                                <ListGroup>
                                    {sendProgress.errors.slice(0, 5).map((error, index) => (
                                        <ListGroupItem key={index} color="danger-subtle">
                                            <small>{error.employee}: {error.error}</small>
                                        </ListGroupItem>
                                    ))}
                                </ListGroup>
                                {sendProgress.errors.length > 5 && (
                                    <small className="text-muted">
                                        ... et {sendProgress.errors.length - 5} autres erreurs
                                    </small>
                                )}
                            </div>
                        )}
                    </CardBody>
                </Card>
            )}

            {/* Statistiques */}
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="bg-info text-white h-100">
                        <CardBody className="text-center">
                            <SendFill size={32} className="mb-2" />
                            <h3>{stats.total_sent_today}</h3>
                            <p className="mb-0">Envoyées aujourd'hui</p>
                        </CardBody>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="bg-primary text-white h-100">
                        <CardBody className="text-center">
                            <People size={32} className="mb-2" />
                            <h3>{stats.total_employees}</h3>
                            <p className="mb-0">Employés Total</p>
                        </CardBody>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="bg-success text-white h-100">
                        <CardBody className="text-center">
                            <BarChart size={32} className="mb-2" />
                            <h3>{stats.success_rate}%</h3>
                            <p className="mb-0">Taux de Succès</p>
                        </CardBody>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="bg-warning text-white h-100">
                        <CardBody className="text-center">
                            <Clock size={32} className="mb-2" />
                            <h3>{stats.recent_notifications.length}</h3>
                            <p className="mb-0">Récentes</p>
                        </CardBody>
                    </Card>
                </Col>
            </Row>

            {/* Table des notifications */}
            <Card>
                <CardHeader>
                    <h5 className="mb-0">Historique des Notifications Massives</h5>
                </CardHeader>
                <CardBody className="p-0">
                    <Table responsive hover className="mb-0">
                        <thead className="table-light">
                            <tr>
                                <th>Période</th>
                                <th>Date d'envoi</th>
                                <th>Total envoyées</th>
                                <th>Succès</th>
                                <th>Échecs</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {Array.isArray(notifications) && notifications.map((notification) => (
                                <tr key={notification.id}>
                                    <td>
                                        <strong>{notification.period?.libelle_periode}</strong><br />
                                        <small className="text-muted">
                                            {notification.period && (
                                                <>
                                                    {new Date(notification.period.date_debut).toLocaleDateString('fr-FR')} - 
                                                    {new Date(notification.period.date_fin).toLocaleDateString('fr-FR')}
                                                </>
                                            )}
                                        </small>
                                    </td>
                                    <td>{formatDate(notification.sent_at)}</td>
                                    <td>
                                        <Badge color="secondary" className="fs-6">
                                            {notification.total_sent}
                                        </Badge>
                                    </td>
                                    <td>
                                        <Badge color="success" className="fs-6">
                                            {notification.successful_count}
                                        </Badge>
                                    </td>
                                    <td>
                                        <Badge color="danger" className="fs-6">
                                            {notification.failed_count}
                                        </Badge>
                                    </td>
                                    <td>
                                        {getStatusBadge(notification.status)}
                                    </td>
                                    <td>
                                        <Button
                                            color="info"
                                            size="sm"
                                            onClick={() => showNotificationDetails(notification)}
                                            title="Voir détails"
                                        >
                                            <Eye size={14} />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                    
                    {(!Array.isArray(notifications) || notifications.length === 0) && (
                        <div className="text-center py-4">
                            <p className="text-muted">Aucune notification massive envoyée</p>
                        </div>
                    )}
                </CardBody>
            </Card>

            {/* Modal Envoi */}
            <Modal isOpen={showSendModal} toggle={() => setShowSendModal(false)} size="lg">
                <ModalHeader toggle={() => setShowSendModal(false)}>
                    <h5>Envoyer Notification Massive</h5>
                </ModalHeader>
                <Form onSubmit={handleSendMassNotification}>
                    <ModalBody>
                        <Alert color="info" className="mb-4">
                            <strong>Attention:</strong> Cette action enverra une notification WhatsApp à tous les employés 
                            actifs de la période sélectionnée.
                        </Alert>
                        
                        <FormGroup>
                            <Label for="period_id">Période de Paie *</Label>
                            <Input
                                type="select"
                                id="period_id"
                                value={sendForm.period_id}
                                onChange={(e) => setSendForm(prev => ({
                                    ...prev,
                                    period_id: e.target.value
                                }))}
                                required
                            >
                                <option value="">Sélectionner une période</option>
                                {Array.isArray(periods) && periods.map(period => (
                                    <option key={period.id} value={period.id}>
                                        {period.libelle_periode} - {period.statut}
                                    </option>
                                ))}
                            </Input>
                        </FormGroup>
                        
                        <FormGroup>
                            <Label for="message_template">Modèle de Message *</Label>
                            <Input
                                type="select"
                                id="message_template"
                                value={sendForm.message_template}
                                onChange={(e) => setSendForm(prev => ({
                                    ...prev,
                                    message_template: e.target.value
                                }))}
                                required
                            >
                                <option value="default">Message Standard</option>
                                <option value="urgent">Message Urgent</option>
                                <option value="reminder">Rappel</option>
                                <option value="custom">Message Personnalisé</option>
                            </Input>
                        </FormGroup>
                        
                        {/* Preview du message */}
                        {sendForm.message_template !== 'custom' && (
                            <FormGroup>
                                <Label>Aperçu du Message:</Label>
                                <div className="bg-light p-3 rounded">
                                    <small className="text-muted">
                                        {messageTemplates[sendForm.message_template]
                                            ?.replace('{nom}', '[Nom de l\'employé]')
                                            ?.replace('{periode}', '[Période sélectionnée]')
                                        }
                                    </small>
                                </div>
                            </FormGroup>
                        )}
                        
                        {sendForm.message_template === 'custom' && (
                            <FormGroup>
                                <Label for="custom_message">Message Personnalisé *</Label>
                                <Input
                                    type="textarea"
                                    id="custom_message"
                                    placeholder="Tapez votre message personnalisé ici. Utilisez {nom} pour le nom de l'employé et {periode} pour la période."
                                    value={sendForm.custom_message}
                                    onChange={(e) => setSendForm(prev => ({
                                        ...prev,
                                        custom_message: e.target.value
                                    }))}
                                    required={sendForm.message_template === 'custom'}
                                    rows="4"
                                />
                                <small className="text-muted">
                                    Variables disponibles: {'{nom}'} (nom de l'employé), {'{periode}'} (période de paie)
                                </small>
                            </FormGroup>
                        )}
                    </ModalBody>
                    <ModalFooter>
                        <Button
                            type="button"
                            color="secondary"
                            onClick={() => setShowSendModal(false)}
                            disabled={sendLoading}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="submit"
                            color="info"
                            disabled={sendLoading || !sendForm.period_id}
                        >
                            {sendLoading ? (
                                <>
                                    <Spinner size="sm" className="me-2" />
                                    Envoi en cours...
                                </>
                            ) : (
                                <>
                                    <SendFill size={16} className="me-2" />
                                    Envoyer à Tous
                                </>
                            )}
                        </Button>
                    </ModalFooter>
                </Form>
            </Modal>

            {/* Modal Détails */}
            <Modal isOpen={showDetailsModal} toggle={() => setShowDetailsModal(false)} size="lg">
                <ModalHeader toggle={() => setShowDetailsModal(false)}>
                    <h5>Détails de la Notification</h5>
                </ModalHeader>
                <ModalBody>
                    {selectedNotification && (
                        <div>
                            <Row className="mb-3">
                                <Col md={6}>
                                    <strong>Période:</strong><br />
                                    {selectedNotification.period?.libelle_periode}
                                </Col>
                                <Col md={6}>
                                    <strong>Date d'envoi:</strong><br />
                                    {formatDate(selectedNotification.sent_at)}
                                </Col>
                            </Row>
                            
                            <Row className="mb-3">
                                <Col md={4}>
                                    <div className="text-center p-3 bg-secondary-subtle rounded">
                                        <h4>{selectedNotification.total_sent}</h4>
                                        <small>Total envoyées</small>
                                    </div>
                                </Col>
                                <Col md={4}>
                                    <div className="text-center p-3 bg-success-subtle rounded">
                                        <h4>{selectedNotification.successful_count}</h4>
                                        <small>Succès</small>
                                    </div>
                                </Col>
                                <Col md={4}>
                                    <div className="text-center p-3 bg-danger-subtle rounded">
                                        <h4>{selectedNotification.failed_count}</h4>
                                        <small>Échecs</small>
                                    </div>
                                </Col>
                            </Row>
                            
                            {selectedNotification.message_sent && (
                                <div className="mb-3">
                                    <strong>Message envoyé:</strong><br />
                                    <div className="bg-light p-3 rounded">
                                        {selectedNotification.message_sent}
                                    </div>
                                </div>
                            )}
                            
                            {selectedNotification.error_details && (
                                <div className="mb-3">
                                    <strong>Détails des erreurs:</strong><br />
                                    <div className="bg-danger-subtle p-3 rounded">
                                        <small>{selectedNotification.error_details}</small>
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
                </ModalFooter>
            </Modal>
        </Container>
    );
};

export default MassNotificationInterface;