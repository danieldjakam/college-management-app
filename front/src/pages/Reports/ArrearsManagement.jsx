import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Table, Form, Alert, Spinner, Badge, Modal } from 'react-bootstrap';
import { CashCoin, Search, CheckCircle, XCircle, Download } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';

const ArrearsManagement = () => {
    const [arrears, setArrears] = useState([]);
    const [summary, setSummary] = useState({});
    const [schoolYears, setSchoolYears] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    // Filters
    const [selectedYearId, setSelectedYearId] = useState('');
    const [selectedStatus, setSelectedStatus] = useState('');
    const [searchTerm, setSearchTerm] = useState('');

    // Payment modal
    const [showPayModal, setShowPayModal] = useState(false);
    const [selectedArrear, setSelectedArrear] = useState(null);
    const [payAmount, setPayAmount] = useState('');
    const [payNotes, setPayNotes] = useState('');
    const [paying, setPaying] = useState(false);

    useEffect(() => {
        loadSchoolYears();
    }, []);

    useEffect(() => {
        if (selectedYearId) {
            loadArrears();
        }
    }, [selectedYearId, selectedStatus]);

    const loadSchoolYears = async () => {
        try {
            const response = await secureApiEndpoints.schoolYears.getActiveYears();
            const years = response.data?.data || response.data || [];
            setSchoolYears(years);
            // Select current year by default
            const current = years.find(y => y.is_current);
            if (current) setSelectedYearId(String(current.id));
        } catch (err) {
            setError('Erreur lors du chargement des annees scolaires');
        }
    };

    const loadArrears = async () => {
        try {
            setLoading(true);
            setError('');
            const params = { school_year_id: selectedYearId };
            if (selectedStatus) params.status = selectedStatus;
            const response = await secureApiEndpoints.schoolYears.getArrears(params);
            const data = response.data?.data || response.data || {};
            setArrears(data.arrears || []);
            setSummary(data.summary || {});
        } catch (err) {
            setError('Erreur lors du chargement des arrieres: ' + (err.message || 'Erreur inconnue'));
        } finally {
            setLoading(false);
        }
    };

    const formatAmount = (amount) => {
        return new Intl.NumberFormat('fr-FR').format(Math.round(amount || 0)) + ' FCFA';
    };

    const getStatusBadge = (status) => {
        const map = {
            pending: { bg: 'danger', label: 'Impaye' },
            partially_paid: { bg: 'warning', label: 'Partiel' },
            paid: { bg: 'success', label: 'Solde' },
            waived: { bg: 'secondary', label: 'Annule' },
        };
        const s = map[status] || { bg: 'light', label: status };
        return <Badge bg={s.bg}>{s.label}</Badge>;
    };

    const handleOpenPay = (arrear) => {
        setSelectedArrear(arrear);
        const remaining = Math.max(0, arrear.arrear_amount - arrear.amount_paid_on_arrear);
        setPayAmount(String(remaining));
        setPayNotes('');
        setShowPayModal(true);
    };

    const handlePay = async () => {
        if (!selectedArrear || !payAmount || parseFloat(payAmount) <= 0) return;
        try {
            setPaying(true);
            setError('');
            await secureApiEndpoints.schoolYears.updateArrear(selectedArrear.id, {
                amount_paid: parseFloat(payAmount),
                notes: payNotes || null,
            });
            setSuccess('Paiement enregistre avec succes');
            setShowPayModal(false);
            setSelectedArrear(null);
            await loadArrears();
            setTimeout(() => setSuccess(''), 3000);
        } catch (err) {
            setError('Erreur lors du paiement: ' + (err.message || 'Erreur inconnue'));
        } finally {
            setPaying(false);
        }
    };

    const handleWaive = async (arrear) => {
        if (!window.confirm(`Annuler la dette de ${getStudentName(arrear)} (${formatAmount(arrear.arrear_amount)}) ?`)) return;
        try {
            setError('');
            await secureApiEndpoints.schoolYears.updateArrear(arrear.id, { status: 'waived', notes: 'Annulation de dette' });
            setSuccess('Dette annulee');
            await loadArrears();
            setTimeout(() => setSuccess(''), 3000);
        } catch (err) {
            setError('Erreur: ' + (err.message || 'Erreur inconnue'));
        }
    };

    const getStudentName = (arrear) => {
        const s = arrear.student;
        if (!s) return 'N/A';
        return (s.last_name || s.name || '') + ' ' + (s.first_name || s.subname || '');
    };

    const getStudentClass = (arrear) => {
        if (arrear.source_class_name) return arrear.source_class_name;
        const s = arrear.student;
        if (!s || !s.class_series) return 'N/A';
        const sc = s.class_series.school_class || s.class_series.schoolClass;
        return (sc?.name || '') + ' ' + (s.class_series.name || '');
    };

    const getSourceYear = (arrear) => arrear.source_year?.name || 'N/A';

    const filteredArrears = arrears.filter(a => {
        if (!searchTerm) return true;
        const name = getStudentName(a).toLowerCase();
        const cls = getStudentClass(a).toLowerCase();
        const term = searchTerm.toLowerCase();
        return name.includes(term) || cls.includes(term);
    });

    const exportCsv = () => {
        const headers = ['Nom', 'Classe (annee source)', 'Annee source', 'Requis', 'Paye total', 'Dette', 'Paye sur dette', 'Reste', 'Statut'];
        const rows = filteredArrears.map(a => [
            getStudentName(a),
            getStudentClass(a),
            getSourceYear(a),
            a.total_required,
            a.total_paid,
            a.arrear_amount,
            a.amount_paid_on_arrear,
            Math.max(0, a.arrear_amount - a.amount_paid_on_arrear),
            a.status,
        ]);
        const csv = [headers.join(';'), ...rows.map(r => r.join(';'))].join('\n');
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `arrieres_${selectedYearId}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    };

    return (
        <Container fluid className="py-4">
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <h2><CashCoin className="me-2" />Gestion des Arrieres</h2>
                        {filteredArrears.length > 0 && (
                            <Button variant="outline-success" size="sm" onClick={exportCsv}>
                                <Download className="me-1" /> Exporter CSV
                            </Button>
                        )}
                    </div>
                </Col>
            </Row>

            {error && <Alert variant="danger" dismissible onClose={() => setError('')}>{error}</Alert>}
            {success && <Alert variant="success" dismissible onClose={() => setSuccess('')}>{success}</Alert>}

            {/* Filtres */}
            <Card className="mb-4">
                <Card.Body>
                    <Row className="g-3 align-items-end">
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>Annee cible (arrieres reportes vers)</Form.Label>
                                <Form.Select value={selectedYearId} onChange={e => setSelectedYearId(e.target.value)}>
                                    <option value="">-- Choisir --</option>
                                    {schoolYears.map(y => (
                                        <option key={y.id} value={y.id}>
                                            {y.name} {y.is_current ? '(courante)' : ''}
                                        </option>
                                    ))}
                                </Form.Select>
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>Statut</Form.Label>
                                <Form.Select value={selectedStatus} onChange={e => setSelectedStatus(e.target.value)}>
                                    <option value="">Tous</option>
                                    <option value="pending">Impaye</option>
                                    <option value="partially_paid">Partiellement paye</option>
                                    <option value="paid">Solde</option>
                                    <option value="waived">Annule</option>
                                </Form.Select>
                            </Form.Group>
                        </Col>
                        <Col md={4}>
                            <Form.Group>
                                <Form.Label>Rechercher</Form.Label>
                                <div className="input-group">
                                    <span className="input-group-text"><Search size={14} /></span>
                                    <Form.Control
                                        type="text"
                                        placeholder="Nom de l'eleve ou classe..."
                                        value={searchTerm}
                                        onChange={e => setSearchTerm(e.target.value)}
                                    />
                                </div>
                            </Form.Group>
                        </Col>
                        <Col md={2}>
                            <Button variant="primary" onClick={loadArrears} disabled={!selectedYearId || loading}>
                                {loading ? <Spinner size="sm" /> : 'Actualiser'}
                            </Button>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            {/* Statistiques */}
            {summary.total_count > 0 && (
                <Row className="mb-4 g-3">
                    <Col md={3}>
                        <Card className="text-center border-danger">
                            <Card.Body>
                                <h6 className="text-muted">Total Insolvables</h6>
                                <h3 className="text-danger">{summary.total_count}</h3>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center border-warning">
                            <Card.Body>
                                <h6 className="text-muted">Dette Totale</h6>
                                <h4 className="text-danger">{formatAmount(summary.total_debt)}</h4>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center border-success">
                            <Card.Body>
                                <h6 className="text-muted">Recouvre</h6>
                                <h4 className="text-success">{formatAmount(summary.total_recovered)}</h4>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center border-info">
                            <Card.Body>
                                <h6 className="text-muted">Reste a Recouvrer</h6>
                                <h4 className="text-info">{formatAmount(summary.total_remaining)}</h4>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Sous-statistiques par statut */}
            {summary.total_count > 0 && (
                <Row className="mb-4 g-3">
                    <Col md={3}>
                        <Badge bg="danger" className="p-2 w-100">Impayes: {summary.pending_count || 0}</Badge>
                    </Col>
                    <Col md={3}>
                        <Badge bg="warning" text="dark" className="p-2 w-100">Partiels: {summary.partially_paid_count || 0}</Badge>
                    </Col>
                    <Col md={3}>
                        <Badge bg="success" className="p-2 w-100">Soldes: {summary.paid_count || 0}</Badge>
                    </Col>
                    <Col md={3}>
                        <Badge bg="secondary" className="p-2 w-100">Annules: {summary.waived_count || 0}</Badge>
                    </Col>
                </Row>
            )}

            {/* Tableau */}
            <Card>
                <Card.Body className="p-0">
                    {loading ? (
                        <div className="text-center py-5">
                            <Spinner animation="border" />
                            <p className="mt-2 text-muted">Chargement...</p>
                        </div>
                    ) : filteredArrears.length === 0 ? (
                        <div className="text-center py-5">
                            <p className="text-muted">
                                {selectedYearId ? 'Aucun arriere trouve pour cette annee.' : 'Selectionnez une annee scolaire.'}
                            </p>
                        </div>
                    ) : (
                        <Table responsive hover striped className="mb-0">
                            <thead className="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Eleve</th>
                                    <th>Classe (annee source)</th>
                                    <th>Annee source</th>
                                    <th className="text-end">Dette</th>
                                    <th className="text-end">Paye sur dette</th>
                                    <th className="text-end">Reste</th>
                                    <th className="text-center">Statut</th>
                                    <th className="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredArrears.map((a, idx) => {
                                    const remaining = Math.max(0, a.arrear_amount - a.amount_paid_on_arrear);
                                    return (
                                        <tr key={a.id}>
                                            <td>{idx + 1}</td>
                                            <td><strong>{getStudentName(a)}</strong></td>
                                            <td>{getStudentClass(a)}</td>
                                            <td><small>{getSourceYear(a)}</small></td>
                                            <td className="text-end text-danger">{formatAmount(a.arrear_amount)}</td>
                                            <td className="text-end text-success">{formatAmount(a.amount_paid_on_arrear)}</td>
                                            <td className="text-end"><strong>{formatAmount(remaining)}</strong></td>
                                            <td className="text-center">{getStatusBadge(a.status)}</td>
                                            <td className="text-center">
                                                {a.status !== 'paid' && a.status !== 'waived' && (
                                                    <div className="d-flex gap-1 justify-content-center">
                                                        <Button
                                                            variant="outline-success"
                                                            size="sm"
                                                            onClick={() => handleOpenPay(a)}
                                                            title="Enregistrer un paiement"
                                                        >
                                                            <CashCoin size={14} />
                                                        </Button>
                                                        <Button
                                                            variant="outline-secondary"
                                                            size="sm"
                                                            onClick={() => handleWaive(a)}
                                                            title="Annuler la dette"
                                                        >
                                                            <XCircle size={14} />
                                                        </Button>
                                                    </div>
                                                )}
                                                {a.status === 'paid' && <CheckCircle className="text-success" />}
                                                {a.status === 'waived' && <XCircle className="text-secondary" />}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                            <tfoot className="table-dark">
                                <tr>
                                    <td colSpan={4} className="text-end"><strong>Totaux ({filteredArrears.length} eleves)</strong></td>
                                    <td className="text-end text-danger">
                                        <strong>{formatAmount(filteredArrears.reduce((s, a) => s + (a.arrear_amount || 0), 0))}</strong>
                                    </td>
                                    <td className="text-end text-success">
                                        <strong>{formatAmount(filteredArrears.reduce((s, a) => s + (a.amount_paid_on_arrear || 0), 0))}</strong>
                                    </td>
                                    <td className="text-end">
                                        <strong>{formatAmount(filteredArrears.reduce((s, a) => s + Math.max(0, (a.arrear_amount || 0) - (a.amount_paid_on_arrear || 0)), 0))}</strong>
                                    </td>
                                    <td colSpan={2}></td>
                                </tr>
                            </tfoot>
                        </Table>
                    )}
                </Card.Body>
            </Card>

            {/* Modal Paiement */}
            <Modal show={showPayModal} onHide={() => setShowPayModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Enregistrer un paiement sur arriere</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {selectedArrear && (
                        <>
                            <Alert variant="info">
                                <strong>{getStudentName(selectedArrear)}</strong><br />
                                Classe: {getStudentClass(selectedArrear)}<br />
                                Dette: <strong className="text-danger">{formatAmount(selectedArrear.arrear_amount)}</strong><br />
                                Deja paye: <strong className="text-success">{formatAmount(selectedArrear.amount_paid_on_arrear)}</strong><br />
                                Reste: <strong>{formatAmount(Math.max(0, selectedArrear.arrear_amount - selectedArrear.amount_paid_on_arrear))}</strong>
                            </Alert>
                            <Form.Group className="mb-3">
                                <Form.Label>Montant du paiement (FCFA)</Form.Label>
                                <Form.Control
                                    type="number"
                                    value={payAmount}
                                    onChange={e => setPayAmount(e.target.value)}
                                    min="1"
                                    max={Math.max(0, selectedArrear.arrear_amount - selectedArrear.amount_paid_on_arrear)}
                                />
                            </Form.Group>
                            <Form.Group>
                                <Form.Label>Notes (optionnel)</Form.Label>
                                <Form.Control
                                    as="textarea"
                                    rows={2}
                                    value={payNotes}
                                    onChange={e => setPayNotes(e.target.value)}
                                    placeholder="Ex: Paiement partiel, recu n°..."
                                />
                            </Form.Group>
                        </>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowPayModal(false)}>Annuler</Button>
                    <Button variant="success" onClick={handlePay} disabled={paying || !payAmount || parseFloat(payAmount) <= 0}>
                        {paying ? <><Spinner size="sm" className="me-1" />En cours...</> : 'Confirmer le paiement'}
                    </Button>
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default ArrearsManagement;
