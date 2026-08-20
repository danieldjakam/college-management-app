import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Table, Form, Alert, Spinner, Badge, Modal } from 'react-bootstrap';
import { CashCoin, Search, CheckCircle, XCircle, Download, PrinterFill } from 'react-bootstrap-icons';
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

    const generatePdfHtml = () => {
        const today = new Date().toLocaleDateString('fr-FR');
        const yearName = schoolYears.find(y => String(y.id) === String(selectedYearId))?.name || '';
        const statusLabel = selectedStatus ? { pending: 'Impayes', partially_paid: 'Partiels', paid: 'Soldes', waived: 'Annules' }[selectedStatus] || 'Tous' : 'Tous';

        const totalDebt = filteredArrears.reduce((s, a) => s + (a.arrear_amount || 0), 0);
        const totalPaid = filteredArrears.reduce((s, a) => s + (a.amount_paid_on_arrear || 0), 0);
        const totalRemaining = filteredArrears.reduce((s, a) => s + Math.max(0, (a.arrear_amount || 0) - (a.amount_paid_on_arrear || 0)), 0);

        const rows = filteredArrears.map((a, idx) => {
            const remaining = Math.max(0, a.arrear_amount - a.amount_paid_on_arrear);
            const statusMap = { pending: 'Impaye', partially_paid: 'Partiel', paid: 'Solde', waived: 'Annule' };
            return `<tr>
                <td style="text-align:center;">${idx + 1}</td>
                <td style="font-weight:bold;">${getStudentName(a)}</td>
                <td>${getStudentClass(a)}</td>
                <td>${getSourceYear(a)}</td>
                <td style="text-align:right;color:#dc3545;">${formatAmount(a.arrear_amount)}</td>
                <td style="text-align:right;color:#198754;">${formatAmount(a.amount_paid_on_arrear)}</td>
                <td style="text-align:right;font-weight:bold;">${formatAmount(remaining)}</td>
                <td style="text-align:center;">${statusMap[a.status] || a.status}</td>
            </tr>`;
        }).join('');

        return `<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Arrieres Insolvables - ${yearName}</title>
  <style>
    @page { margin: 8mm 10mm; size: A4 landscape; }
    body { font-family: Arial, sans-serif; font-size: 9pt; color: #333; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 4mm; }
    .header h2 { margin: 0; font-size: 13pt; color: #009B3A; }
    .header h3 { margin: 2mm 0; font-size: 11pt; }
    .header p { margin: 1mm 0; font-size: 8pt; color: #666; }
    .separator { height: 2px; background: linear-gradient(to right, #009B3A 33%, #CE1126 33%, #CE1126 66%, #FCD116 66%); margin: 3mm 60mm; }
    .summary { display: flex; justify-content: space-around; margin: 4mm 0; }
    .summary-box { text-align: center; padding: 2mm 6mm; border: 1px solid #ddd; border-radius: 2mm; }
    .summary-box .label { font-size: 7pt; color: #666; }
    .summary-box .value { font-size: 14pt; font-weight: bold; }
    .total-count .value { color: #dc3545; }
    .total-debt .value { color: #dc3545; }
    .recovered .value { color: #198754; }
    .remaining .value { color: #0dcaf0; }
    .status-bar { display: flex; gap: 3mm; justify-content: center; margin: 3mm 0; font-size: 8pt; }
    .status-bar span { padding: 1mm 4mm; border-radius: 2mm; color: white; }
    table { width: 100%; border-collapse: collapse; margin-top: 3mm; }
    th { background-color: #2c3e50; color: white; padding: 2mm 2mm; font-size: 8pt; text-align: center; }
    th:nth-child(2), th:nth-child(3), th:nth-child(4) { text-align: left; }
    td { padding: 1.5mm 2mm; border-bottom: 0.5px solid #ddd; font-size: 8.5pt; }
    tr:nth-child(even) { background-color: #f8f9fa; }
    tfoot td { background-color: #2c3e50; color: white; font-weight: bold; padding: 2mm; font-size: 9pt; }
    .footer { text-align: center; margin-top: 5mm; font-size: 7pt; color: #999; border-top: 1px solid #ddd; padding-top: 2mm; }
  </style>
</head>
<body>
  <div class="header">
    <h2>COLLEGE POLYVALENT BILINGUE DE DOUALA</h2>
    <div class="separator"></div>
    <h3>ETAT DES ARRIERES INSOLVABLES</h3>
    <p>Annee cible : ${yearName} | Statut : ${statusLabel} | ${filteredArrears.length} eleve(s)</p>
  </div>

  <div class="summary">
    <div class="summary-box total-count">
      <div class="label">Total Insolvables</div>
      <div class="value">${summary.total_count || filteredArrears.length}</div>
    </div>
    <div class="summary-box total-debt">
      <div class="label">Dette Totale</div>
      <div class="value">${formatAmount(summary.total_debt || totalDebt)}</div>
    </div>
    <div class="summary-box recovered">
      <div class="label">Recouvre</div>
      <div class="value">${formatAmount(summary.total_recovered || totalPaid)}</div>
    </div>
    <div class="summary-box remaining">
      <div class="label">Reste a Recouvrer</div>
      <div class="value">${formatAmount(summary.total_remaining || totalRemaining)}</div>
    </div>
  </div>

  <div class="status-bar">
    <span style="background:#dc3545;">Impayes: ${summary.pending_count || 0}</span>
    <span style="background:#ffc107;color:#333;">Partiels: ${summary.partially_paid_count || 0}</span>
    <span style="background:#198754;">Soldes: ${summary.paid_count || 0}</span>
    <span style="background:#6c757d;">Annules: ${summary.waived_count || 0}</span>
  </div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Eleve</th>
        <th>Classe (annee source)</th>
        <th>Annee source</th>
        <th>Dette</th>
        <th>Paye sur dette</th>
        <th>Reste</th>
        <th>Statut</th>
      </tr>
    </thead>
    <tbody>
      ${rows}
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" style="text-align:right;">TOTAUX (${filteredArrears.length} eleves)</td>
        <td style="text-align:right;">${formatAmount(totalDebt)}</td>
        <td style="text-align:right;">${formatAmount(totalPaid)}</td>
        <td style="text-align:right;">${formatAmount(totalRemaining)}</td>
        <td></td>
      </tr>
    </tfoot>
  </table>

  <div class="footer">
    Imprime le ${today} - College Polyvalent Bilingue de Douala
  </div>
</body>
</html>`;
    };

    const handlePrintPdf = () => {
        const html = generatePdfHtml();
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            alert('Veuillez autoriser les popups pour imprimer.');
            return;
        }
        printWindow.document.write(html);
        printWindow.document.close();
        setTimeout(() => printWindow.print(), 500);
    };

    const handleDownloadPdf = () => {
        const html = generatePdfHtml();
        const yearName = schoolYears.find(y => String(y.id) === String(selectedYearId))?.name || '';
        const blob = new Blob([html], { type: 'text/html;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
        iframe.contentWindow.document.open();
        iframe.contentWindow.document.write(html);
        iframe.contentWindow.document.close();
        iframe.contentWindow.onafterprint = () => {
            document.body.removeChild(iframe);
        };
        // Télécharger comme HTML que l'utilisateur peut ouvrir et imprimer en PDF
        const link = document.createElement('a');
        link.href = url;
        link.download = `Arrieres_${yearName.replace(/\//g, '-')}.html`;
        link.click();
        URL.revokeObjectURL(url);
    };

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
                            <div className="d-flex gap-2">
                                <Button variant="outline-primary" size="sm" onClick={handlePrintPdf}>
                                    <PrinterFill className="me-1" /> Imprimer
                                </Button>
                                <Button variant="outline-danger" size="sm" onClick={handleDownloadPdf}>
                                    <Download className="me-1" /> Télécharger PDF
                                </Button>
                                <Button variant="outline-success" size="sm" onClick={exportCsv}>
                                    <Download className="me-1" /> Exporter CSV
                                </Button>
                            </div>
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
