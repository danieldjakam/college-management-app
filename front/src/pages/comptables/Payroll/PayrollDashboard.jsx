import React, { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Alert,
    Spinner,
    Badge,
    ListGroup,
    Table,
    Modal
} from 'react-bootstrap';
import {
    CashCoin,
    People,
    Calendar,
    ExclamationTriangle,
    CheckCircle,
    Clock,
    SendFill,
    PersonFill,
    FileText,
    Plus
} from 'react-bootstrap-icons';
import { Link } from 'react-router-dom';
import { authService } from '../../../services/authService';
import { extractErrorMessage } from '../../../utils/errorHandler';
import { host } from '../../../utils/fetch';

const PayrollDashboard = () => {
    const [dashboardData, setDashboardData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [showCreatePeriodModal, setShowCreatePeriodModal] = useState(false);

    // Charger les données du dashboard
    useEffect(() => {
        loadDashboardData();
    }, []);

    const loadDashboardData = async () => {
        try {
            setLoading(true);
            setError('');

            const response = await fetch(`${host}/api/payroll/dashboard`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setDashboardData(data.data);
                } else {
                    setError(data.message || 'Erreur lors du chargement');
                }
            } else {
                setError('Erreur lors du chargement du dashboard');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const formatMontant = (montant) => {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(montant) + ' FCFA';
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            'ouverte': { color: 'primary', label: 'Ouverte' },
            'calculee': { color: 'info', label: 'Calculée' },
            'validee': { color: 'warning', label: 'Validée' },
            'payee': { color: 'success', label: 'Payée' }
        };
        const config = statusConfig[status] || { color: 'secondary', label: status };
        return <Badge bg={config.color}>{config.label}</Badge>;
    };

    if (loading) {
        return (
            <Container fluid className="py-4">
                <div className="text-center">
                    <Spinner animation="border" />
                    <p className="mt-2">Chargement du dashboard...</p>
                </div>
            </Container>
        );
    }

    if (error) {
        return (
            <Container fluid className="py-4">
                <Alert variant="danger">
                    <strong>Erreur:</strong> {error}
                </Alert>
            </Container>
        );
    }

    const { stats, recent_notifications, recent_cuts } = dashboardData;

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 className="d-flex align-items-center gap-2 mb-1">
                                <CashCoin className="text-primary" />
                                Dashboard Paie
                            </h2>
                            <p className="text-muted mb-0">
                                Vue d'ensemble du système de paie
                            </p>
                        </div>
                        <div className="d-flex gap-2">
                            <Button 
                                variant="outline-primary" 
                                as={Link} 
                                to="/payroll/employees"
                                className="d-flex align-items-center gap-2"
                            >
                                <People size={16} />
                                Gérer Employés
                            </Button>
                            <Button 
                                variant="primary" 
                                onClick={() => setShowCreatePeriodModal(true)}
                                className="d-flex align-items-center gap-2"
                            >
                                <Plus size={16} />
                                Nouvelle Période
                            </Button>
                        </div>
                    </div>
                </Col>
            </Row>

            {/* Cards de statistiques */}
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="bg-primary text-white h-100">
                        <Card.Body className="text-center">
                            <People size={32} className="mb-2" />
                            <h3>{stats.total_employees}</h3>
                            <p className="mb-0">Employés Actifs</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="bg-info text-white h-100">
                        <Card.Body className="text-center">
                            <Calendar size={32} className="mb-2" />
                            <h3>{stats.periods_this_year}</h3>
                            <p className="mb-0">Périodes cette année</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="bg-success text-white h-100">
                        <Card.Body className="text-center">
                            <SendFill size={32} className="mb-2" />
                            <h3>{stats.total_notifications_sent}</h3>
                            <p className="mb-0">Notifications envoyées</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="bg-warning text-white h-100">
                        <Card.Body className="text-center">
                            <ExclamationTriangle size={32} className="mb-2" />
                            <h3>{stats.active_salary_cuts}</h3>
                            <p className="mb-0">Coupures actives</p>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row>
                {/* Période actuelle */}
                <Col lg={6} className="mb-4">
                    <Card className="h-100">
                        <Card.Header className="bg-light">
                            <h5 className="mb-0 d-flex align-items-center gap-2">
                                <Calendar />
                                Période Actuelle
                            </h5>
                        </Card.Header>
                        <Card.Body>
                            {stats.current_period ? (
                                <div>
                                    <div className="d-flex justify-content-between align-items-center mb-3">
                                        <h6>{stats.current_period.libelle_periode}</h6>
                                        {getStatusBadge(stats.current_period.statut)}
                                    </div>
                                    <div className="mb-3">
                                        <small className="text-muted d-block">Du {new Date(stats.current_period.date_debut).toLocaleDateString('fr-FR')}</small>
                                        <small className="text-muted d-block">Au {new Date(stats.current_period.date_fin).toLocaleDateString('fr-FR')}</small>
                                    </div>
                                    <div className="d-flex gap-2">
                                        <Button
                                            variant="outline-primary"
                                            size="sm"
                                            as={Link}
                                            to={`/payroll/periods/${stats.current_period.id}`}
                                        >
                                            <FileText size={14} className="me-1" />
                                            Voir Détails
                                        </Button>
                                        {stats.current_period.statut === 'ouverte' && (
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                as={Link}
                                                to={`/payroll/periods/${stats.current_period.id}/calculate`}
                                            >
                                                Calculer Paie
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ) : (
                                <div className="text-center py-3">
                                    <p className="text-muted">Aucune période active ce mois</p>
                                    <Button 
                                        variant="primary" 
                                        size="sm"
                                        onClick={() => setShowCreatePeriodModal(true)}
                                    >
                                        Créer Période
                                    </Button>
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </Col>

                {/* Actions rapides */}
                <Col lg={6} className="mb-4">
                    <Card className="h-100">
                        <Card.Header className="bg-light">
                            <h5 className="mb-0">Actions Rapides</h5>
                        </Card.Header>
                        <Card.Body>
                            <ListGroup variant="flush">
                                <ListGroup.Item className="px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span>
                                        <People className="me-2" />
                                        Gestion des Employés
                                    </span>
                                    <Button variant="outline-primary" size="sm" as={Link} to="/payroll/employees">
                                        Gérer
                                    </Button>
                                </ListGroup.Item>
                                <ListGroup.Item className="px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span>
                                        <ExclamationTriangle className="me-2" />
                                        Coupures de Salaire
                                    </span>
                                    <Button variant="outline-warning" size="sm" as={Link} to="/payroll/salary-cuts">
                                        Voir
                                    </Button>
                                </ListGroup.Item>
                                <ListGroup.Item className="px-0 py-2 d-flex justify-content-between align-items-center">
                                    <span>
                                        <SendFill className="me-2" />
                                        Notifications
                                    </span>
                                    <Button variant="outline-info" size="sm" as={Link} to="/payroll/notifications">
                                        Suivre
                                    </Button>
                                </ListGroup.Item>
                            </ListGroup>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row>
                {/* Dernières notifications */}
                <Col lg={6} className="mb-4">
                    <Card>
                        <Card.Header className="bg-light">
                            <h5 className="mb-0">Dernières Notifications</h5>
                        </Card.Header>
                        <Card.Body>
                            {recent_notifications.length > 0 ? (
                                <div>
                                    {recent_notifications.slice(0, 5).map((notification, index) => (
                                        <div key={index} className="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <small className="fw-bold">{notification.employee?.nom_complet}</small>
                                                <br />
                                                <small className="text-muted">{notification.type_label}</small>
                                            </div>
                                            <div className="text-end">
                                                <Badge bg={notification.statut === 'sent' ? 'success' : 'danger'}>
                                                    {notification.statut_label}
                                                </Badge>
                                                <br />
                                                <small className="text-muted">
                                                    {new Date(notification.created_at).toLocaleDateString('fr-FR')}
                                                </small>
                                            </div>
                                        </div>
                                    ))}
                                    <div className="text-center mt-3">
                                        <Button variant="outline-primary" size="sm" as={Link} to="/payroll/notifications">
                                            Voir Toutes
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <p className="text-muted text-center">Aucune notification récente</p>
                            )}
                        </Card.Body>
                    </Card>
                </Col>

                {/* Dernières coupures */}
                <Col lg={6} className="mb-4">
                    <Card>
                        <Card.Header className="bg-light">
                            <h5 className="mb-0">Coupures Récentes</h5>
                        </Card.Header>
                        <Card.Body>
                            {recent_cuts.length > 0 ? (
                                <div>
                                    {recent_cuts.map((cut, index) => (
                                        <div key={index} className="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <small className="fw-bold">{cut.employee?.nom_complet}</small>
                                                <br />
                                                <small className="text-muted">{cut.motif.substring(0, 30)}...</small>
                                            </div>
                                            <div className="text-end">
                                                <small className="fw-bold text-danger">
                                                    {formatMontant(cut.montant_coupe)}
                                                </small>
                                                <br />
                                                <small className="text-muted">
                                                    {new Date(cut.date_coupure).toLocaleDateString('fr-FR')}
                                                </small>
                                            </div>
                                        </div>
                                    ))}
                                    <div className="text-center mt-3">
                                        <Button variant="outline-warning" size="sm" as={Link} to="/payroll/salary-cuts">
                                            Voir Toutes
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <p className="text-muted text-center">Aucune coupure récente</p>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </Container>
    );
};

export default PayrollDashboard;