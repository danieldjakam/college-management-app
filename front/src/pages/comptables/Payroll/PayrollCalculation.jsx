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
    ProgressBar,
    Tabs,
    Tab
} from 'react-bootstrap';
import {
    Calculator,
    ArrowLeft,
    CheckCircle,
    XCircle,
    Clock,
    CashCoin,
    People,
    ExclamationTriangle,
    SendFill,
    Save
} from 'react-bootstrap-icons';
import { Link, useParams } from 'react-router-dom';
import { authService } from '../../../services/authService';
import { extractErrorMessage } from '../../../utils/errorHandler';
import { host } from '../../../utils/fetch';

const PayrollCalculation = () => {
    const { periodId } = useParams();
    const [period, setPeriod] = useState(null);
    const [payslips, setPayslips] = useState([]);
    const [employees, setEmployees] = useState([]);
    const [stats, setStats] = useState({});
    const [loading, setLoading] = useState(true);
    const [calculating, setCalculating] = useState(false);
    const [validating, setValidating] = useState(false);
    const [markingAvailable, setMarkingAvailable] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    
    // États pour les modals
    const [showCalculationModal, setShowCalculationModal] = useState(false);
    const [showValidationModal, setShowValidationModal] = useState(false);
    const [showAvailableModal, setShowAvailableModal] = useState(false);
    
    // États pour le calcul personnalisé
    const [customCalculation, setCustomCalculation] = useState([]);
    const [sendNotifications, setSendNotifications] = useState(true);
    const [paymentDate, setPaymentDate] = useState(new Date().toISOString().split('T')[0]);

    // Charger les données de la période
    useEffect(() => {
        if (periodId) {
            loadPeriodDetails();
        }
    }, [periodId]);

    const loadPeriodDetails = async () => {
        try {
            setLoading(true);
            setError('');

            const response = await fetch(`${host}/api/payroll/periods/${periodId}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setPeriod(data.data.period);
                    setStats(data.data.stats);
                    if (data.data.period.statut !== 'ouverte') {
                        loadPayslips();
                    }
                } else {
                    setError(data.message || 'Erreur lors du chargement');
                }
            } else {
                setError('Erreur lors du chargement de la période');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const loadPayslips = async () => {
        try {
            const response = await fetch(`${host}/api/payroll/periods/${periodId}/payslips`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setPayslips(data.data.data || []);
                }
            }
        } catch (error) {
            console.error('Erreur lors du chargement des bulletins:', error);
        }
    };

    const loadEmployees = async () => {
        try {
            const response = await fetch(`${host}/api/payroll/employees?per_page=100&statut=actif`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    const employeesData = data.data.data || [];
                    setEmployees(employeesData);
                    
                    // Initialiser le calcul personnalisé
                    setCustomCalculation(employeesData.map(emp => ({
                        employee_id: emp.id,
                        employee: emp,
                        primes_mensuelles: 0,
                        deductions_mensuelles: 0,
                        selected: true
                    })));
                }
            }
        } catch (error) {
            console.error('Erreur lors du chargement des employés:', error);
        }
    };

    const handleCalculatePayroll = async (useCustomData = false) => {
        try {
            setCalculating(true);
            setError('');
            setSuccess('');

            const requestData = useCustomData ? {
                employees: customCalculation
                    .filter(emp => emp.selected)
                    .map(emp => ({
                        employee_id: emp.employee_id,
                        primes_mensuelles: parseFloat(emp.primes_mensuelles) || 0,
                        deductions_mensuelles: parseFloat(emp.deductions_mensuelles) || 0
                    }))
            } : {};

            const response = await fetch(`${host}/api/payroll/periods/${periodId}/calculate`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            const data = await response.json();

            if (data.success) {
                setSuccess('Paie calculée avec succès');
                setShowCalculationModal(false);
                loadPeriodDetails();
                loadPayslips();
            } else {
                setError(data.message || 'Erreur lors du calcul');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setCalculating(false);
        }
    };

    const handleValidatePeriod = async () => {
        try {
            setValidating(true);
            setError('');
            setSuccess('');

            const response = await fetch(`${host}/api/payroll/periods/${periodId}/validate`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                setSuccess('Période validée avec succès');
                setShowValidationModal(false);
                loadPeriodDetails();
            } else {
                setError(data.message || 'Erreur lors de la validation');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setValidating(false);
        }
    };

    const handleMarkSalariesAvailable = async () => {
        try {
            setMarkingAvailable(true);
            setError('');
            setSuccess('');

            const response = await fetch(`${host}/api/payroll/periods/${periodId}/mark-available`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    date_paie: paymentDate,
                    send_notifications: sendNotifications
                })
            });

            const data = await response.json();

            if (data.success) {
                const notificationResults = data.data.notifications;
                let message = 'Salaires marqués comme disponibles';
                
                if (sendNotifications && notificationResults) {
                    message += `. Notifications: ${notificationResults.sent} envoyées, ${notificationResults.failed} échecs, ${notificationResults.skipped} ignorées`;
                }
                
                setSuccess(message);
                setShowAvailableModal(false);
                loadPeriodDetails();
            } else {
                setError(data.message || 'Erreur lors du marquage');
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setMarkingAvailable(false);
        }
    };

    const handleCustomCalculationChange = (index, field, value) => {
        setCustomCalculation(prev => prev.map((emp, i) => 
            i === index ? { ...emp, [field]: value } : emp
        ));
    };

    const toggleEmployeeSelection = (index) => {
        setCustomCalculation(prev => prev.map((emp, i) => 
            i === index ? { ...emp, selected: !emp.selected } : emp
        ));
    };

    const selectAllEmployees = () => {
        setCustomCalculation(prev => prev.map(emp => ({ ...emp, selected: true })));
    };

    const deselectAllEmployees = () => {
        setCustomCalculation(prev => prev.map(emp => ({ ...emp, selected: false })));
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

    const getPayslipStatusBadge = (status) => {
        const statusConfig = {
            'brouillon': { color: 'secondary', label: 'Brouillon' },
            'valide': { color: 'success', label: 'Validé' },
            'paye': { color: 'primary', label: 'Payé' }
        };
        const config = statusConfig[status] || { color: 'secondary', label: status };
        return <Badge bg={config.color}>{config.label}</Badge>;
    };

    if (loading) {
        return (
            <Container fluid className="py-4">
                <div className="text-center">
                    <Spinner animation="border" />
                    <p className="mt-2">Chargement...</p>
                </div>
            </Container>
        );
    }

    if (!period) {
        return (
            <Container fluid className="py-4">
                <Alert variant="danger">
                    Période non trouvée
                </Alert>
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
                                <Calculator className="text-primary" />
                                Calcul de Paie - {period.libelle_periode}
                            </h2>
                            <p className="text-muted mb-0">
                                {getStatusBadge(period.statut)} | 
                                Du {new Date(period.date_debut).toLocaleDateString('fr-FR')} au {new Date(period.date_fin).toLocaleDateString('fr-FR')}
                            </p>
                        </div>
                        <div className="d-flex gap-2">
                            {period.statut === 'ouverte' && (
                                <Button 
                                    variant="primary" 
                                    onClick={() => {
                                        loadEmployees();
                                        setShowCalculationModal(true);
                                    }}
                                    className="d-flex align-items-center gap-2"
                                >
                                    <Calculator size={16} />
                                    Calculer Paie
                                </Button>
                            )}
                            {period.statut === 'calculee' && (
                                <Button 
                                    variant="success" 
                                    onClick={() => setShowValidationModal(true)}
                                    className="d-flex align-items-center gap-2"
                                >
                                    <CheckCircle size={16} />
                                    Valider Période
                                </Button>
                            )}
                            {period.statut === 'validee' && (
                                <Button 
                                    variant="warning" 
                                    onClick={() => setShowAvailableModal(true)}
                                    className="d-flex align-items-center gap-2"
                                >
                                    <SendFill size={16} />
                                    Marquer Disponible
                                </Button>
                            )}
                        </div>
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
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="bg-info text-white h-100">
                        <Card.Body className="text-center">
                            <People size={24} className="mb-2" />
                            <h4>{stats.payslips_count || 0}/{stats.total_employees || 0}</h4>
                            <p className="mb-0">Bulletins Calculés</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="bg-success text-white h-100">
                        <Card.Body className="text-center">
                            <CashCoin size={24} className="mb-2" />
                            <h4>{formatMontant(stats.total_salaries || 0)}</h4>
                            <p className="mb-0">Total Salaires</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="bg-warning text-white h-100">
                        <Card.Body className="text-center">
                            <ExclamationTriangle size={24} className="mb-2" />
                            <h4>{stats.salary_cuts?.count || 0}</h4>
                            <p className="mb-0">Coupures Actives</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="bg-primary text-white h-100">
                        <Card.Body className="text-center">
                            <SendFill size={24} className="mb-2" />
                            <h4>{stats.notifications?.sent || 0}</h4>
                            <p className="mb-0">Notifications Envoyées</p>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Répartition par mode de paiement */}
            {stats.total_by_mode && (
                <Row className="mb-4">
                    <Col>
                        <Card>
                            <Card.Header className="bg-light">
                                <h5 className="mb-0">Répartition par Mode de Paiement</h5>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md={4} className="text-center">
                                        <h6>Espèces</h6>
                                        <h4 className="text-success">{formatMontant(stats.total_by_mode.especes || 0)}</h4>
                                    </Col>
                                    <Col md={4} className="text-center">
                                        <h6>Chèque</h6>
                                        <h4 className="text-info">{formatMontant(stats.total_by_mode.cheque || 0)}</h4>
                                    </Col>
                                    <Col md={4} className="text-center">
                                        <h6>Virement</h6>
                                        <h4 className="text-primary">{formatMontant(stats.total_by_mode.virement || 0)}</h4>
                                    </Col>
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Liste des bulletins */}
            {payslips.length > 0 && (
                <Card>
                    <Card.Header className="bg-light">
                        <h5 className="mb-0">Bulletins de Paie Calculés</h5>
                    </Card.Header>
                    <Card.Body>
                        <div className="table-responsive">
                            <Table striped hover>
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Salaire Base</th>
                                        <th>Primes</th>
                                        <th>Déductions</th>
                                        <th>Coupures</th>
                                        <th>Salaire Net</th>
                                        <th>Mode Paiement</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {payslips.map((payslip) => (
                                        <tr key={payslip.id}>
                                            <td>
                                                <div className="fw-semibold">
                                                    {payslip.employee?.nom} {payslip.employee?.prenom}
                                                </div>
                                                <small className="text-muted">
                                                    {payslip.employee?.matricule}
                                                </small>
                                            </td>
                                            <td>{formatMontant(payslip.salaire_base)}</td>
                                            <td className="text-success">
                                                +{formatMontant(payslip.primes_mensuelles)}
                                            </td>
                                            <td className="text-warning">
                                                -{formatMontant(payslip.deductions_mensuelles)}
                                            </td>
                                            <td className="text-danger">
                                                {payslip.montant_coupures > 0 ? 
                                                    `-${formatMontant(payslip.montant_coupures)}` : 
                                                    '0 FCFA'
                                                }
                                            </td>
                                            <td>
                                                <strong className="text-primary">
                                                    {formatMontant(payslip.salaire_net)}
                                                </strong>
                                            </td>
                                            <td>
                                                <Badge bg="info">
                                                    {payslip.mode_paiement_label}
                                                </Badge>
                                            </td>
                                            <td>
                                                {getPayslipStatusBadge(payslip.statut)}
                                                {payslip.retire && (
                                                    <Badge bg="success" className="ms-1">Retiré</Badge>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </div>
                    </Card.Body>
                </Card>
            )}

            {/* Modal de calcul */}
            <Modal show={showCalculationModal} onHide={() => setShowCalculationModal(false)} size="xl">
                <Modal.Header closeButton>
                    <Modal.Title>Calculer la Paie</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Tabs defaultActiveKey="simple" className="mb-3">
                        <Tab eventKey="simple" title="Calcul Simple">
                            <div className="text-center py-4">
                                <h5>Calcul automatique pour tous les employés actifs</h5>
                                <p className="text-muted">
                                    Utilise les salaires de base et primes fixes configurés pour chaque employé.
                                    Les coupures de salaire actives seront automatiquement déduites.
                                </p>
                                <Button 
                                    variant="primary" 
                                    size="lg"
                                    onClick={() => handleCalculatePayroll(false)}
                                    disabled={calculating}
                                    className="d-flex align-items-center gap-2 mx-auto"
                                >
                                    {calculating ? (
                                        <>
                                            <Spinner size="sm" />
                                            Calcul en cours...
                                        </>
                                    ) : (
                                        <>
                                            <Calculator />
                                            Calculer pour Tous
                                        </>
                                    )}
                                </Button>
                            </div>
                        </Tab>
                        <Tab eventKey="custom" title="Calcul Personnalisé">
                            <div className="mb-3">
                                <div className="d-flex gap-2 mb-3">
                                    <Button size="sm" variant="outline-success" onClick={selectAllEmployees}>
                                        Tout Sélectionner
                                    </Button>
                                    <Button size="sm" variant="outline-danger" onClick={deselectAllEmployees}>
                                        Tout Désélectionner
                                    </Button>
                                    <span className="text-muted ms-auto">
                                        {customCalculation.filter(emp => emp.selected).length} employé(s) sélectionné(s)
                                    </span>
                                </div>
                                
                                <div style={{ maxHeight: '400px', overflowY: 'auto' }}>
                                    <Table size="sm" striped>
                                        <thead>
                                            <tr>
                                                <th>Sél.</th>
                                                <th>Employé</th>
                                                <th>Salaire Base</th>
                                                <th>Primes du Mois</th>
                                                <th>Déductions du Mois</th>
                                                <th>Total Estimé</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {customCalculation.map((emp, index) => {
                                                const estimatedTotal = emp.employee.salaire_base + 
                                                    (parseFloat(emp.primes_mensuelles) || 0) - 
                                                    (parseFloat(emp.deductions_mensuelles) || 0);
                                                
                                                return (
                                                    <tr key={emp.employee_id} className={!emp.selected ? 'text-muted' : ''}>
                                                        <td>
                                                            <Form.Check
                                                                type="checkbox"
                                                                checked={emp.selected}
                                                                onChange={() => toggleEmployeeSelection(index)}
                                                            />
                                                        </td>
                                                        <td>
                                                            <div>
                                                                <strong>{emp.employee.nom} {emp.employee.prenom}</strong>
                                                                <br />
                                                                <small className="text-muted">{emp.employee.matricule}</small>
                                                            </div>
                                                        </td>
                                                        <td>{formatMontant(emp.employee.salaire_base)}</td>
                                                        <td>
                                                            <Form.Control
                                                                type="number"
                                                                size="sm"
                                                                min="0"
                                                                value={emp.primes_mensuelles}
                                                                onChange={(e) => handleCustomCalculationChange(index, 'primes_mensuelles', e.target.value)}
                                                                disabled={!emp.selected}
                                                            />
                                                        </td>
                                                        <td>
                                                            <Form.Control
                                                                type="number"
                                                                size="sm"
                                                                min="0"
                                                                value={emp.deductions_mensuelles}
                                                                onChange={(e) => handleCustomCalculationChange(index, 'deductions_mensuelles', e.target.value)}
                                                                disabled={!emp.selected}
                                                            />
                                                        </td>
                                                        <td>
                                                            <strong className={estimatedTotal > 0 ? 'text-success' : 'text-danger'}>
                                                                {formatMontant(Math.max(0, estimatedTotal))}
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </Table>
                                </div>
                            </div>
                        </Tab>
                    </Tabs>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowCalculationModal(false)}>
                        Annuler
                    </Button>
                    <Button 
                        variant="primary" 
                        onClick={() => handleCalculatePayroll(true)}
                        disabled={calculating || customCalculation.filter(emp => emp.selected).length === 0}
                    >
                        {calculating ? 'Calcul...' : 'Calculer Sélection'}
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Modal de validation */}
            <Modal show={showValidationModal} onHide={() => setShowValidationModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Valider la Période</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className="text-center">
                        <CheckCircle size={48} className="text-success mb-3" />
                        <h5>Confirmer la validation</h5>
                        <p className="text-muted">
                            Cette action va valider tous les bulletins de paie de la période.
                            Une fois validée, vous pourrez marquer les salaires comme disponibles.
                        </p>
                        <Alert variant="info">
                            <strong>Bulletins à valider:</strong> {stats.payslips_count}<br />
                            <strong>Montant total:</strong> {formatMontant(stats.total_salaries || 0)}
                        </Alert>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowValidationModal(false)}>
                        Annuler
                    </Button>
                    <Button 
                        variant="success" 
                        onClick={handleValidatePeriod}
                        disabled={validating}
                    >
                        {validating ? 'Validation...' : 'Valider'}
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Modal de mise à disposition */}
            <Modal show={showAvailableModal} onHide={() => setShowAvailableModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Marquer Salaires Disponibles</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Date de Paie</Form.Label>
                                    <Form.Control
                                        type="date"
                                        value={paymentDate}
                                        onChange={(e) => setPaymentDate(e.target.value)}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label className="d-block">Options</Form.Label>
                                    <Form.Check
                                        type="checkbox"
                                        label="Envoyer notifications WhatsApp"
                                        checked={sendNotifications}
                                        onChange={(e) => setSendNotifications(e.target.checked)}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>

                        <Alert variant="warning">
                            <strong>⚠️ Attention!</strong><br />
                            Cette action va :
                            <ul className="mb-0 mt-2">
                                <li>Marquer tous les salaires comme disponibles</li>
                                <li>Changer le statut de la période à "Payée"</li>
                                {sendNotifications && <li>Envoyer des notifications WhatsApp à tous les employés</li>}
                                <li>Cette action est <strong>irréversible</strong></li>
                            </ul>
                        </Alert>

                        <div className="bg-light p-3 rounded">
                            <h6>Résumé:</h6>
                            <ul className="mb-0">
                                <li><strong>Employés concernés:</strong> {stats.payslips_count}</li>
                                <li><strong>Montant total:</strong> {formatMontant(stats.total_salaries || 0)}</li>
                                <li><strong>Notifications WhatsApp:</strong> {sendNotifications ? 'Oui' : 'Non'}</li>
                            </ul>
                        </div>
                    </Form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowAvailableModal(false)}>
                        Annuler
                    </Button>
                    <Button 
                        variant="warning" 
                        onClick={handleMarkSalariesAvailable}
                        disabled={markingAvailable}
                    >
                        {markingAvailable ? 'Traitement...' : 'Confirmer'}
                    </Button>
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default PayrollCalculation;