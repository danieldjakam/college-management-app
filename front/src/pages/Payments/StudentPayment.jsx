import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
    Container,
    Row,
    Col,
    Card,
    Table,
    Button,
    Form,
    Modal,
    Alert,
    Badge,
    Spinner
} from 'react-bootstrap';
import {
    ArrowLeft,
    CashCoin,
    Receipt,
    Person,
    Calendar,
    CreditCard,
    Check,
    X,
    Printer,
    Clock
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { useSchool } from '../../contexts/SchoolContext';
import Swal from 'sweetalert2';

const StudentPayment = () => {
    const { studentId } = useParams();
    const navigate = useNavigate();
    const { schoolSettings, formatCurrency, getLogoUrl } = useSchool();
    
    const [student, setStudent] = useState(null);
    const [paymentStatus, setPaymentStatus] = useState([]);
    const [paymentHistory, setPaymentHistory] = useState([]);
    const [schoolYear, setSchoolYear] = useState(null);
    const [totals, setTotals] = useState({
        required: 0,
        paid: 0,
        remaining: 0
    });
    
    const [discountInfo, setDiscountInfo] = useState({
        eligible_for_scholarship: false,
        scholarship_amount: 0,
        eligible_for_reduction: false,
        reduction_percentage: 0,
        deadline: null,
        reasons: []
    });
    
    const [showPaymentModal, setShowPaymentModal] = useState(false);
    const [showReceiptModal, setShowReceiptModal] = useState(false);
    const [receiptHtml, setReceiptHtml] = useState('');
    const [loading, setLoading] = useState(true);
    const [paymentLoading, setPaymentLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    
    const [paymentForm, setPaymentForm] = useState({
        amount: '',
        payment_method: 'cash',
        reference_number: '',
        notes: '',
        payment_date: new Date().toISOString().split('T')[0],
        is_rame_physical: false,
        rame_choice: 'none' // Par défaut: ne pas payer la RAME
    });

    useEffect(() => {
        loadStudentPaymentInfo();
        loadPaymentHistory();
    }, [studentId]);

    const loadStudentPaymentInfo = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.payments.getStudentInfo(studentId);
            
            if (response.success) {
                setStudent(response.data.student);
                setPaymentStatus(response.data.payment_status);
                setSchoolYear(response.data.school_year);
                setTotals({
                    required: response.data.total_required,
                    paid: response.data.total_paid,
                    remaining: response.data.total_remaining
                });
                setDiscountInfo(response.data.discount_info || {
                    eligible_for_scholarship: false,
                    scholarship_amount: 0,
                    eligible_for_reduction: false,
                    reduction_percentage: 0,
                    deadline: null,
                    reasons: []
                });
            } else {
                setError(response.message);
            }
        } catch (error) {
            setError('Erreur lors du chargement des informations de paiement');
            console.error('Error loading payment info:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadPaymentHistory = async () => {
        try {
            const response = await secureApiEndpoints.payments.getStudentHistory(studentId);
            
            if (response.success) {
                setPaymentHistory(response.data);
            }
        } catch (error) {
            console.error('Error loading payment history:', error);
        }
    };

    const handlePaymentSubmit = async (e) => {
        e.preventDefault();
        
        // Validation
        if (!paymentForm.amount || parseFloat(paymentForm.amount) <= 0) {
            setError('Veuillez saisir un montant valide');
            return;
        }

        if (parseFloat(paymentForm.amount) > totals.remaining) {
            setError(`Le montant saisi (${formatCurrency(parseInt(paymentForm.amount))}) est supérieur au montant restant (${formatCurrency(totals.remaining)}). Veuillez saisir un montant inférieur ou égal au solde restant.`);
            return;
        }

        try {
            setPaymentLoading(true);
            setError('');
            
            const paymentData = {
                student_id: parseInt(studentId),
                amount: parseFloat(paymentForm.amount),
                payment_method: paymentForm.payment_method,
                reference_number: paymentForm.reference_number || null,
                notes: paymentForm.notes || null,
                payment_date: paymentForm.payment_date
            };

            const response = await secureApiEndpoints.payments.create(paymentData);
            
            if (response.success) {
                setSuccess('Paiement enregistré avec succès');
                setShowPaymentModal(false);
                
                // Réinitialiser le formulaire
                setPaymentForm({
                    amount: '',
                    payment_method: 'cash',
                    reference_number: '',
                    notes: '',
                    payment_date: new Date().toISOString().split('T')[0],
                    is_rame_physical: false,
                    rame_choice: 'none'
                });
                
                // Recharger les données
                await loadStudentPaymentInfo();
                await loadPaymentHistory();
                
                // Proposer d'imprimer le reçu
                const printResult = await Swal.fire({
                    title: 'Paiement enregistré !',
                    text: 'Voulez-vous imprimer le reçu maintenant ?',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Imprimer le reçu',
                    cancelButtonText: 'Plus tard'
                });
                
                if (printResult.isConfirmed) {
                    handlePrintReceipt(response.data.id);
                }
                
            } else {
                setError(response.message || 'Erreur lors de l\'enregistrement du paiement');
            }
        } catch (error) {
            setError('Erreur lors de l\'enregistrement du paiement');
            console.error('Error creating payment:', error);
        } finally {
            setPaymentLoading(false);
        }
    };

    const handlePrintReceipt = async (paymentId) => {
        try {
            const response = await secureApiEndpoints.payments.generateReceipt(paymentId);
            
            if (response.success) {
                setReceiptHtml(response.data.html);
                setShowReceiptModal(true);
            } else {
                setError('Erreur lors de la génération du reçu');
            }
        } catch (error) {
            setError('Erreur lors de la génération du reçu');
            console.error('Error generating receipt:', error);
        }
    };

    const handlePrintReceiptFromHistory = async (paymentId) => {
        await handlePrintReceipt(paymentId);
    };

    const printReceipt = () => {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Reçu de Paiement</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        @media print { 
                            body { margin: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    ${receiptHtml}
                    <div class="no-print" style="text-align: center; margin-top: 30px;">
                        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Imprimer</button>
                        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Fermer</button>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
    };

    const formatAmount = (amount) => {
        return formatCurrency(parseInt(amount));
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR');
    };

    const getPaymentMethodLabel = (method, isRamePhysical = false) => {
        if (isRamePhysical) {
            return 'RAME Physique';
        }
        const methods = {
            cash: 'Espèces',
            card: 'Carte',
            transfer: 'Virement',
            check: 'Chèque'
        };
        return methods[method] || method;
    };

    if (loading) {
        return (
            <Container fluid className="py-4">
                <div className="text-center">
                    <Spinner animation="border" role="status">
                        <span className="visually-hidden">Chargement...</span>
                    </Spinner>
                </div>
            </Container>
        );
    }

    if (!student) {
        return (
            <Container fluid className="py-4">
                <Alert variant="danger">
                    Étudiant non trouvé ou erreur lors du chargement des données.
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
                        <div className="d-flex align-items-center">
                            <Button 
                                variant="outline-secondary" 
                                size="sm" 
                                onClick={() => navigate(-1)}
                                className="me-3"
                            >
                                <ArrowLeft size={16} />
                            </Button>
                            <div>
                                <h2 className="mb-1">Paiement - {student.last_name} {student.first_name}</h2>
                                <p className="text-muted mb-0">
                                    {student.classSeries?.schoolClass?.name} - {student.classSeries?.name} | {schoolYear?.name}
                                </p>
                            </div>
                        </div>
                        {totals.remaining <= 0 ? (
                            <Button variant="success" disabled>
                                <CashCoin size={16} className="me-2" />
                                Paiements Complets
                            </Button>
                        ) : (
                            <Button 
                                variant="primary" 
                                onClick={() => setShowPaymentModal(true)}
                            >
                                <CashCoin size={16} className="me-2" />
                                Nouveau Paiement
                            </Button>
                        )}
                    </div>
                </Col>
            </Row>

            {/* Alerts */}
            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')}>
                    {error}
                </Alert>
            )}
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess('')}>
                    {success}
                </Alert>
            )}

            {/* Discount Information */}
            {(discountInfo.eligible_for_scholarship || discountInfo.eligible_for_reduction || discountInfo.reasons.length > 0) && (
                <Row className="mb-4">
                    <Col>
                        <Card className="border-success">
                            <Card.Header className="bg-success text-white">
                                <h5 className="mb-0">💰 Bourses et Réductions Disponibles</h5>
                            </Card.Header>
                            <Card.Body>
                                {discountInfo.reasons.map((reason, index) => (
                                    <div key={index} className="d-flex align-items-center mb-2">
                                        <span className="badge bg-success me-2">✓</span>
                                        <span>{reason}</span>
                                    </div>
                                ))}
                                
                                {discountInfo.deadline && (
                                    <div className="mt-3 p-2 bg-warning bg-opacity-25 rounded">
                                        <small className="text-warning">
                                            <strong>⏰ Date limite:</strong> {new Date(discountInfo.deadline).toLocaleDateString('fr-FR')}
                                        </small>
                                    </div>
                                )}
                                
                                {(!discountInfo.eligible_for_scholarship && !discountInfo.eligible_for_reduction && discountInfo.reasons.length === 0) && (
                                    <div className="text-muted">
                                        <em>Aucune réduction disponible pour cet élève actuellement.</em>
                                    </div>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Summary Cards */}
            <Row className="mb-4">
                <Col md={4}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className="text-primary">{formatAmount(totals.required)}</h3>
                            <p className="text-muted mb-0">Total à payer</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className="text-success">{formatAmount(totals.paid)}</h3>
                            <p className="text-muted mb-0">Total payé</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className={totals.remaining > 0 ? "text-warning" : "text-success"}>
                                {formatAmount(totals.remaining)}
                            </h3>
                            <p className="text-muted mb-0">Reste à payer</p>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row>
                {/* Payment Status */}
                <Col md={7}>
                    <Card className="mb-4">
                        <Card.Header>
                            <h5 className="mb-0">Statut des Paiements par Tranche</h5>
                        </Card.Header>
                        <Card.Body>
                            <Table responsive>
                                <thead>
                                    <tr>
                                        <th>Tranche</th>
                                        <th>Montant Requis</th>
                                        <th>Montant Payé</th>
                                        <th>Reste</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {paymentStatus.map((status, index) => (
                                        <tr key={index} className={status.is_optional ? 'table-secondary' : ''}>
                                            <td>
                                                {status.tranche.name}
                                                {status.is_optional && <small className="text-muted d-block">(Optionnelle)</small>}
                                            </td>
                                            <td>{formatAmount(status.required_amount)}</td>
                                            <td>{formatAmount(status.paid_amount)}</td>
                                            <td>{formatAmount(status.remaining_amount)}</td>
                                            <td>
                                                <Badge bg={status.is_fully_paid ? 'success' : 'warning'}>
                                                    {status.is_fully_paid ? 'Complet' : 'Partiel'}
                                                </Badge>
                                                {status.is_optional && !status.is_fully_paid && (
                                                    <small className="text-muted d-block">Non obligatoire</small>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </Card.Body>
                    </Card>
                </Col>

                {/* Payment History */}
                <Col md={5}>
                    <Card>
                        <Card.Header>
                            <h5 className="mb-0">Historique des Paiements</h5>
                        </Card.Header>
                        <Card.Body>
                            {paymentHistory.length === 0 ? (
                                <p className="text-muted text-center">Aucun paiement enregistré</p>
                            ) : (
                                <div style={{ maxHeight: '400px', overflowY: 'auto' }}>
                                    {paymentHistory.map((payment) => (
                                        <div key={payment.id} className="border-bottom pb-3 mb-3">
                                            <div className="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong>{formatAmount(payment.total_amount)}</strong>
                                                    {(payment.has_scholarship || payment.has_reduction) && (
                                                        <span className="badge bg-success ms-2">Réduit</span>
                                                    )}
                                                    <br />
                                                    <small className="text-muted">
                                                        <Calendar size={14} className="me-1" />
                                                        {formatDate(payment.payment_date)}
                                                    </small>
                                                    <br />
                                                    <small className="text-muted">
                                                        <CreditCard size={14} className="me-1" />
                                                        {getPaymentMethodLabel(payment.payment_method, payment.is_rame_physical)}
                                                    </small>
                                                    
                                                    {/* Affichage des réductions appliquées */}
                                                    {(payment.has_scholarship || payment.has_reduction) && (
                                                        <div className="mt-2">
                                                            {payment.has_scholarship && (
                                                                <div className="badge bg-success me-1 mb-1">
                                                                    Bourse: {formatAmount(payment.scholarship_amount)}
                                                                </div>
                                                            )}
                                                            {payment.has_reduction && (
                                                                <div className="badge bg-info me-1 mb-1">
                                                                    Réduction: {formatAmount(payment.reduction_amount)}
                                                                </div>
                                                            )}
                                                            {payment.discount_reason && (
                                                                <small className="text-success d-block">
                                                                    <em>{payment.discount_reason}</em>
                                                                </small>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                                <Button
                                                    variant="outline-primary"
                                                    size="sm"
                                                    onClick={() => handlePrintReceiptFromHistory(payment.id)}
                                                >
                                                    <Receipt size={14} />
                                                </Button>
                                            </div>
                                            {payment.notes && (
                                                <small className="text-muted d-block mt-1">
                                                    Note: {payment.notes}
                                                </small>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Payment Modal */}
            <Modal show={showPaymentModal} onHide={() => setShowPaymentModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>Nouveau Paiement</Modal.Title>
                </Modal.Header>
                <Form onSubmit={handlePaymentSubmit}>
                    <Modal.Body>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Montant du versement ({schoolSettings.currency || 'FCFA'}) *</Form.Label>
                                    <Form.Control
                                        type="number"
                                        min="1"
                                        max={totals.remaining}
                                        step="1"
                                        value={paymentForm.amount}
                                        onChange={(e) => setPaymentForm({...paymentForm, amount: e.target.value})}
                                        placeholder="Ex: 25000"
                                        required
                                    />
                                    <Form.Text className="text-muted">
                                        Reste à payer: {formatAmount(totals.remaining)}
                                        <br />
                                        {paymentForm.payment_method === 'rame_physical' ? (
                                            <small className="text-info">
                                                ℹ️ Paiement par rame physique - Montant équivalent de la tranche RAME
                                            </small>
                                        ) : (
                                            <small className="text-warning">
                                                ⚠️ Le montant ne peut pas dépasser le solde restant
                                            </small>
                                        )}
                                    </Form.Text>
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Mode de paiement *</Form.Label>
                                    <Form.Select
                                        value={paymentForm.payment_method}
                                        onChange={(e) => {
                                            const newMethod = e.target.value;
                                            setPaymentForm({
                                                ...paymentForm, 
                                                payment_method: newMethod,
                                                is_rame_physical: false // Reset RAME choice when method changes
                                            });
                                        }}
                                        required
                                    >
                                        <option value="cash">Espèces</option>
                                        <option value="card">Carte bancaire</option>
                                        <option value="transfer">Virement</option>
                                        <option value="check">Chèque</option>
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>

                        {/* Section spéciale pour la tranche RAME */}
                        {paymentStatus && paymentStatus.length > 0 && paymentStatus.some(status => {
                            // Vérification de sécurité pour la structure des données
                            if (!status || !status.tranche) return false;
                            
                            const trancheName = status.tranche.name || '';
                            const remaining = status.remaining_amount || 0;
                            
                            return trancheName && (
                                trancheName === 'RAME' || 
                                trancheName.toUpperCase().includes('RAME')
                            ) && remaining > 0;
                        }) && (
                            <Row className="mb-3">
                                <Col>
                                    <Card className="bg-light border-warning">
                                        <Card.Body className="py-3">
                                            <h6 className="text-warning mb-3">
                                                <CashCoin className="me-2" />
                                                Paiement de la tranche RAME
                                            </h6>
                                            <Row>
                                                <Col md={12}>
                                                    <Form.Group>
                                                        <Form.Label className="fw-bold">Options RAME *</Form.Label>
                                                        <div className="d-flex flex-column gap-2">
                                                            <Form.Check
                                                                type="radio"
                                                                name="rame_choice"
                                                                id="rame_none"
                                                                label="Ne pas payer (par défaut)"
                                                                checked={paymentForm.rame_choice === 'none'}
                                                                onChange={() => setPaymentForm({
                                                                    ...paymentForm, 
                                                                    rame_choice: 'none',
                                                                    is_rame_physical: false
                                                                })}
                                                            />
                                                            <Form.Check
                                                                type="radio"
                                                                name="rame_choice"
                                                                id="rame_espece"
                                                                label="Espèce (paiement normal)"
                                                                checked={paymentForm.rame_choice === 'cash'}
                                                                onChange={() => setPaymentForm({
                                                                    ...paymentForm, 
                                                                    rame_choice: 'cash',
                                                                    is_rame_physical: false
                                                                })}
                                                            />
                                                            <Form.Check
                                                                type="radio"
                                                                name="rame_choice"
                                                                id="rame_physique"
                                                                label="Physique (rame apportée)"
                                                                checked={paymentForm.rame_choice === 'physical'}
                                                                onChange={() => setPaymentForm({
                                                                    ...paymentForm, 
                                                                    rame_choice: 'physical',
                                                                    is_rame_physical: true
                                                                })}
                                                            />
                                                        </div>
                                                    </Form.Group>
                                                </Col>
                                            </Row>
                                        </Card.Body>
                                    </Card>
                                </Col>
                            </Row>
                        )}

                        {/* Section information réduction pour paiement intégral */}
                        {totals.remaining > 0 && discountInfo && discountInfo.eligible_for_reduction && (
                            <Row className="mb-3">
                                <Col>
                                    <Card className="bg-success bg-opacity-10 border-success">
                                        <Card.Body className="py-3">
                                            <h6 className="text-success mb-2">
                                                <Badge bg="success" className="me-2">💰</Badge>
                                                Réduction disponible !
                                            </h6>
                                            <p className="mb-2 small">
                                                <strong>Payez la totalité maintenant et économisez {discountInfo.reduction_percentage || 10}% !</strong>
                                            </p>
                                            <div className="d-flex justify-content-between align-items-center">
                                                <div className="small text-muted">
                                                    <strong className="text-success">
                                                        Montants réduits de {discountInfo.reduction_percentage || 10}% déjà appliqués !
                                                    </strong><br/>
                                                    Total à payer avec réduction : {formatAmount(totals.remaining)}
                                                </div>
                                                <Button 
                                                    variant="success" 
                                                    size="sm"
                                                    onClick={() => {
                                                        // Avec la nouvelle logique, le montant restant affiché est déjà réduit
                                                        setPaymentForm({
                                                            ...paymentForm, 
                                                            amount: totals.remaining.toString()
                                                        });
                                                    }}
                                                >
                                                    Payer la totalité
                                                </Button>
                                            </div>
                                            {discountInfo.deadline && (
                                                <small className="text-warning d-block mt-2">
                                                    ⏰ Offre valable jusqu'au {new Date(discountInfo.deadline).toLocaleDateString('fr-FR')}
                                                </small>
                                            )}
                                        </Card.Body>
                                    </Card>
                                </Col>
                            </Row>
                        )}

                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Date du paiement</Form.Label>
                                    <Form.Control
                                        type="date"
                                        value={paymentForm.payment_date}
                                        onChange={(e) => setPaymentForm({...paymentForm, payment_date: e.target.value})}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Numéro de référence</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={paymentForm.reference_number}
                                        onChange={(e) => setPaymentForm({...paymentForm, reference_number: e.target.value})}
                                        placeholder="Numéro chèque, virement..."
                                    />
                                </Form.Group>
                            </Col>
                        </Row>

                        <Row>
                            <Col>
                                <Form.Group className="mb-3">
                                    <Form.Label>Notes</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={paymentForm.notes}
                                        onChange={(e) => setPaymentForm({...paymentForm, notes: e.target.value})}
                                        placeholder="Notes additionnelles..."
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowPaymentModal(false)}>
                            Annuler
                        </Button>
                        <Button variant="primary" type="submit" disabled={paymentLoading}>
                            {paymentLoading ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Enregistrement...
                                </>
                            ) : (
                                <>
                                    <Check size={16} className="me-2" />
                                    Enregistrer le Paiement
                                </>
                            )}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* Receipt Modal */}
            <Modal show={showReceiptModal} onHide={() => setShowReceiptModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>Reçu de Paiement</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div dangerouslySetInnerHTML={{ __html: receiptHtml }} />
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowReceiptModal(false)}>
                        Fermer
                    </Button>
                    <Button variant="primary" onClick={printReceipt}>
                        <Printer size={16} className="me-2" />
                        Imprimer
                    </Button>
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default StudentPayment;