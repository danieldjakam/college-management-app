import { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Table,
    Form,
    Alert,
    Spinner,
    Badge
} from 'react-bootstrap';
import {
    FileEarmarkPdf,
    Calendar2,
    CurrencyDollar,
    Collection,
    Search,
    List,
    BuildingsFill,
    Download,
    FiletypePdf,
    BarChart
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { extractErrorMessage } from '../../utils/errorHandler';
import { authService } from '../../services/authService';
import { host } from '../../utils/fetch';

const RecoveryStatusReport = () => {
    const [recoveryData, setRecoveryData] = useState([]);
    const [summary, setSummary] = useState({});
    const [schoolYear, setSchoolYear] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    useEffect(() => {
        loadRecoveryStatus();
    }, []);

    const loadRecoveryStatus = async () => {
        try {
            setLoading(true);
            setError('');

            const response = await secureApiEndpoints.reports.getRecoveryStatusReport();

            if (response.success) {
                setRecoveryData(response.data.classes);
                setSummary(response.data.summary);
                setSchoolYear(response.data.school_year);
                setSuccess(`${response.data.classes.length} classes analysées`);
            } else {
                setError(response.message);
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const exportToPdf = async () => {
        try {
            const response = await fetch(`${host}/api/reports/recovery-status/export-pdf`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/pdf',
                },
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `etat_des_recouvrements_${new Date().toISOString().split('T')[0]}.pdf`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } else {
                setError('Erreur lors de l\'export PDF');
            }
        } catch (error) {
            setError('Erreur lors de l\'export PDF: ' + error.message);
        }
    };

    const formatAmount = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount).replace('XAF', 'FCFA');
    };

    const formatPercentage = (value) => {
        return `${parseFloat(value).toFixed(1)}%`;
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>État des Recouvrements</h2>
                            <p className="text-muted">
                                Rapport de suivi des recouvrements par classe
                            </p>
                        </div>
                        <div>
                            <Button
                                variant="primary"
                                onClick={loadRecoveryStatus}
                                disabled={loading}
                                className="me-2"
                            >
                                <Search className="me-2" />
                                {loading ? 'Actualisation...' : 'Actualiser'}
                            </Button>
                            <Button
                                variant="danger"
                                onClick={exportToPdf}
                                disabled={loading || recoveryData.length === 0}
                            >
                                <FiletypePdf className="me-2" />
                                Export PDF
                            </Button>
                        </div>
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

            {/* Résumé Global */}
            {summary.total_classes > 0 && (
                <Row className="mb-4">
                    <Col md={2}>
                        <Card className="bg-primary text-white">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Total Classes</h6>
                                        <h4>{summary.total_classes}</h4>
                                    </div>
                                    <BuildingsFill size={30} />
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={2}>
                        <Card className="bg-success text-white">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Eff. Total</h6>
                                        <h4>{summary.total_students}</h4>
                                    </div>
                                    <List size={30} />
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={2}>
                        <Card className="bg-warning text-white">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Recette Attendue</h6>
                                        <h5>{formatAmount(summary.total_expected)}</h5>
                                    </div>
                                    <CurrencyDollar size={25} />
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={2}>
                        <Card className="bg-info text-white">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Réalisation</h6>
                                        <h5>{formatAmount(summary.total_collected)}</h5>
                                    </div>
                                    <Collection size={25} />
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={2}>
                        <Card className="bg-danger text-white">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Reste à Recouvrer</h6>
                                        <h5>{formatAmount(summary.total_remaining)}</h5>
                                    </div>
                                    <CurrencyDollar size={25} />
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={2}>
                        <Card className="bg-secondary text-white">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>% Recouv.</h6>
                                        <h4>{formatPercentage(summary.recovery_percentage)}</h4>
                                    </div>
                                    <BarChart size={25} />
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Tableau des Recouvrements */}
            <Card>
                <Card.Header>
                    <h5 className="mb-0">
                        État Détaillé des Recouvrements par Classe
                        {summary.total_classes > 0 && (
                            <Badge bg="secondary" className="ms-2">
                                {summary.total_classes} classes
                            </Badge>
                        )}
                    </h5>
                </Card.Header>
                <Card.Body>
                    {loading ? (
                        <div className="text-center py-4">
                            <Spinner animation="border" role="status">
                                <span className="visually-hidden">Chargement...</span>
                            </Spinner>
                        </div>
                    ) : recoveryData.length === 0 ? (
                        <div className="text-center py-4">
                            <BarChart size={48} className="text-muted mb-3" />
                            <p className="text-muted">
                                Aucune donnée de recouvrement disponible
                            </p>
                        </div>
                    ) : (
                        <div className="table-responsive">
                            <Table striped hover size="sm" style={{fontSize: '0.85rem'}}>
                                <thead className="table-dark">
                                    <tr>
                                        <th rowSpan={2} className="text-center align-middle">N°</th>
                                        <th rowSpan={2} className="text-center align-middle">Nom de la Classe</th>
                                        <th colSpan={3} className="text-center border-end">Eff Départ</th>
                                        <th rowSpan={2} className="text-center align-middle">Dém</th>
                                        <th rowSpan={2} className="text-center align-middle">Eff Réel</th>
                                        <th rowSpan={2} className="text-center align-middle">Ins Perçu</th>
                                        <th rowSpan={2} className="text-center align-middle">Percep Dém</th>
                                        <th rowSpan={2} className="text-center align-middle">Perte Démission</th>
                                        <th rowSpan={2} className="text-center align-middle">Recette Attendue</th>
                                        <th rowSpan={2} className="text-center align-middle">Réalisation</th>
                                        <th rowSpan={2} className="text-center align-middle">Bourse</th>
                                        <th rowSpan={2} className="text-center align-middle">Rabais</th>
                                        <th rowSpan={2} className="text-center align-middle">Reste à Recouvrer</th>
                                        <th rowSpan={2} className="text-center align-middle">% Recouv.</th>
                                    </tr>
                                    <tr>
                                        <th className="text-center" style={{fontSize: '0.75rem'}}>Anc</th>
                                        <th className="text-center" style={{fontSize: '0.75rem'}}>Nouv</th>
                                        <th className="text-center border-end" style={{fontSize: '0.75rem'}}>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recoveryData.map((classData, index) => (
                                        <tr key={index}>
                                            <td className="text-center">{classData.numero}</td>
                                            <td><strong>{classData.class_name}</strong></td>
                                            <td className="text-center">{classData.eff_ancien}</td>
                                            <td className="text-center">{classData.eff_nouveau}</td>
                                            <td className="text-center border-end"><strong>{classData.eff_total}</strong></td>
                                            <td className="text-center">{classData.demissionnaires}</td>
                                            <td className="text-center"><strong>{classData.eff_reel}</strong></td>
                                            <td className="text-end">{formatAmount(classData.inscription_percu)}</td>
                                            <td className="text-end">{formatAmount(classData.perception_demission)}</td>
                                            <td className="text-end text-danger">{formatAmount(classData.perte_demission)}</td>
                                            <td className="text-end"><strong>{formatAmount(classData.recette_attendue)}</strong></td>
                                            <td className="text-end text-success"><strong>{formatAmount(classData.realisation)}</strong></td>
                                            <td className="text-end">{formatAmount(classData.bourse)}</td>
                                            <td className="text-end">{formatAmount(classData.rabais)}</td>
                                            <td className="text-end text-danger"><strong>{formatAmount(classData.reste_a_recouvrer)}</strong></td>
                                            <td className="text-center">
                                                <Badge 
                                                    bg={classData.pourcentage_recouv >= 80 ? 'success' : 
                                                        classData.pourcentage_recouv >= 50 ? 'warning' : 'danger'}
                                                >
                                                    {formatPercentage(classData.pourcentage_recouv)}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot className="table-secondary">
                                    <tr>
                                        <th colSpan={2} className="text-center"><strong>TOTAL</strong></th>
                                        <th className="text-center">{summary.total_ancien}</th>
                                        <th className="text-center">{summary.total_nouveau}</th>
                                        <th className="text-center border-end"><strong>{summary.total_students}</strong></th>
                                        <th className="text-center">{summary.total_demissionnaires}</th>
                                        <th className="text-center"><strong>{summary.total_eff_reel}</strong></th>
                                        <th className="text-end"><strong>{formatAmount(summary.total_inscription_percu)}</strong></th>
                                        <th className="text-end"><strong>{formatAmount(summary.total_perception_demission)}</strong></th>
                                        <th className="text-end text-danger"><strong>{formatAmount(summary.total_perte_demission)}</strong></th>
                                        <th className="text-end"><strong>{formatAmount(summary.total_expected)}</strong></th>
                                        <th className="text-end text-success"><strong>{formatAmount(summary.total_collected)}</strong></th>
                                        <th className="text-end"><strong>{formatAmount(summary.total_bourse)}</strong></th>
                                        <th className="text-end"><strong>{formatAmount(summary.total_rabais)}</strong></th>
                                        <th className="text-end text-danger"><strong>{formatAmount(summary.total_remaining)}</strong></th>
                                        <th className="text-center">
                                            <Badge bg={summary.recovery_percentage >= 80 ? 'success' : 
                                                    summary.recovery_percentage >= 50 ? 'warning' : 'danger'}>
                                                <strong>{formatPercentage(summary.recovery_percentage)}</strong>
                                            </Badge>
                                        </th>
                                    </tr>
                                </tfoot>
                            </Table>
                        </div>
                    )}
                </Card.Body>
            </Card>
        </Container>
    );
};

export default RecoveryStatusReport;