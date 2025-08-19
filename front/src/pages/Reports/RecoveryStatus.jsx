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
    Badge,
    Tabs,
    Tab
} from 'react-bootstrap';
import {
    BarChart,
    Calendar2,
    Download,
    FiletypePdf,
    Search,
    BookFill,
    PeopleFill
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { extractErrorMessage } from '../../utils/errorHandler';
import { authService } from '../../services/authService';
import Swal from 'sweetalert2';
import { Hospital } from 'lucide-react';
import { host } from '../../utils/fetch';

const RecoveryStatus = () => {
    const [recoveryData, setRecoveryData] = useState([]);
    const [summary, setSummary] = useState({});
    const [schoolYear, setSchoolYear] = useState(null);
    const [classes, setClasses] = useState([]);
    const [sections, setSections] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [activeTab, setActiveTab] = useState('by-class');

    // Filtres
    const [filters, setFilters] = useState({
        class_id: '',
        section_id: ''
    });

    useEffect(() => {
        loadClasses();
        loadSections();
    }, []);

    const loadClasses = async () => {
        try {
            const response = await secureApiEndpoints.schoolClasses.getAll();
            if (response.success) {
                setClasses(response.data);
            }
        } catch (error) {
            console.error('Error loading classes:', error);
        }
    };

    const loadSections = async () => {
        try {
            const response = await secureApiEndpoints.sections.getAll();
            if (response.success) {
                setSections(response.data);
            }
        } catch (error) {
            console.error('Error loading sections:', error);
        }
    };

    const loadRecoveryStatus = async () => {
        try {
            setLoading(true);
            setError('');

            const response = await secureApiEndpoints.reports.getRecoveryStatus({
                type: activeTab,
                ...filters
            });

            if (response.success) {
                setRecoveryData(response.data.recovery_data);
                setSummary(response.data.summary);
                setSchoolYear(response.data.school_year);
                setSuccess(`${response.data.recovery_data.length} enregistrements trouvés`);
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
            setLoading(true);

            const exportParams = {
                type: activeTab,
                ...filters
            };

            const response = await fetch(`${host}/api/reports/recovery-status/export-pdf?${new URLSearchParams(exportParams).toString()}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/pdf'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const htmlBlob = await response.blob();
            const blobUrl = window.URL.createObjectURL(htmlBlob);
            window.open(blobUrl, '_blank');
            setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1000);
            
            setSuccess('Export PDF lancé avec succès');
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const generateGeneralRecoveryReport = async () => {
        try {
            setLoading(true);

            const response = await fetch(`${host}/api/reports/general-recovery-status/export-pdf`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/pdf'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const htmlBlob = await response.blob();
            const blobUrl = window.URL.createObjectURL(htmlBlob);
            window.open(blobUrl, '_blank');
            setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1000);
            
            setSuccess('État Général de Recouvrement généré avec succès');
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const exportToCsv = async () => {
        try {
            setLoading(true);

            const csvHeaders = activeTab === 'by-class' ? 
                ['Classe', 'Série', 'Total Élèves', 'Élèves Payés', 'Élèves Non Payés', 'Taux de Recouvrement (%)', 'Montant Collecté', 'Montant Restant'] :
                ['Section', 'Total Élèves', 'Élèves Payés', 'Élèves Non Payés', 'Taux de Recouvrement (%)', 'Montant Collecté', 'Montant Restant'];

            const csvRows = recoveryData.map(item => activeTab === 'by-class' ? [
                item.class_name,
                item.series_name,
                item.total_students,
                item.paid_students,
                item.unpaid_students,
                item.recovery_rate,
                item.collected_amount,
                item.remaining_amount
            ] : [
                item.section_name,
                item.total_students,
                item.paid_students,
                item.unpaid_students,
                item.recovery_rate,
                item.collected_amount,
                item.remaining_amount
            ]);

            const csvContent = [
                csvHeaders.join(','),
                ...csvRows.map(row => row.join(','))
            ].join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `etat_recouvrement_${activeTab}_${new Date().toISOString().split('T')[0]}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            setSuccess('Export CSV téléchargé avec succès');
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
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

    const getRecoveryRateColor = (rate) => {
        if (rate >= 80) return 'success';
        if (rate >= 50) return 'warning';
        return 'danger';
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>
                                <BarChart className="me-2" />
                                État de Recouvrement
                            </h2>
                            <p className="text-muted">
                                Analyse du recouvrement des frais de scolarité par classe et section
                            </p>
                        </div>
                        <div className="d-flex gap-2">
                            <Button
                                variant="outline-success"
                                onClick={exportToCsv}
                                disabled={loading || recoveryData.length === 0}
                            >
                                <Download className="me-2" />
                                Exporter CSV
                            </Button>
                            <Button
                                variant="outline-danger"
                                onClick={exportToPdf}
                                disabled={loading || recoveryData.length === 0}
                            >
                                <FiletypePdf className="me-2" />
                                Exporter PDF
                            </Button>
                            <Button
                                variant="primary"
                                onClick={generateGeneralRecoveryReport}
                                disabled={loading}
                            >
                                <BarChart className="me-2" />
                                État Général de Recouvrement
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

            {/* Tabs */}
            <Card className="mb-4">
                <Card.Header>
                    <Tabs
                        activeKey={activeTab}
                        onSelect={(tab) => setActiveTab(tab)}
                        className="border-bottom-0"
                    >
                        <Tab 
                            eventKey="by-class" 
                            title={
                                <span>
                                    <BookFill className="me-2" />
                                    Par Classe
                                </span>
                            }
                        />
                        <Tab 
                            eventKey="by-section" 
                            title={
                                <span>
                                    <PeopleFill className="me-2" />
                                    Par Section
                                </span>
                            }
                        />
                    </Tabs>
                </Card.Header>
                <Card.Body>
                    <Row>
                        {activeTab === 'by-class' && (
                            <Col md={4}>
                                <Form.Group>
                                    <Form.Label>Filtrer par classe</Form.Label>
                                    <Form.Select
                                        value={filters.class_id}
                                        onChange={(e) => setFilters({ ...filters, class_id: e.target.value })}
                                    >
                                        <option value="">Toutes les classes</option>
                                        {classes.map(cls => (
                                            <option key={cls.id} value={cls.id}>
                                                {cls.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        )}
                        {activeTab === 'by-section' && (
                            <Col md={4}>
                                <Form.Group>
                                    <Form.Label>Filtrer par section</Form.Label>
                                    <Form.Select
                                        value={filters.section_id}
                                        onChange={(e) => setFilters({ ...filters, section_id: e.target.value })}
                                    >
                                        <option value="">Toutes les sections</option>
                                        {sections.map(section => (
                                            <option key={section.id} value={section.id}>
                                                {section.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        )}
                        <Col md={4} className="d-flex align-items-end">
                            <Button
                                variant="primary"
                                onClick={loadRecoveryStatus}
                                disabled={loading}
                            >
                                <Search className="me-2" />
                                {loading ? 'Chargement...' : 'Générer le Rapport'}
                            </Button>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            {/* Résumé */}
            {summary.total_amount && (
                <Row className="mb-4">
                    <Col md={3}>
                        <Card className="bg-primary text-white">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Total Général</h6>
                                        <h4>{formatAmount(summary.total_amount)}</h4>
                                    </div>
                                    <Calendar2 size={30} />
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="bg-success text-white">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Montant Collecté</h6>
                                        <h4>{formatAmount(summary.collected_amount)}</h4>
                                    </div>
                                    <Download size={30} />
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="bg-warning text-white">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6>Reste à Collecter</h6>
                                        <h4>{formatAmount(summary.remaining_amount)}</h4>
                                    </div>
                                    <BarChart size={30} />
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="bg-info text-white">
                            <Card.Body>
                                <div>
                                    <h6>Taux Global</h6>
                                    <h4>{summary.global_recovery_rate}%</h4>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Tableau des données */}
            <Card>
                <Card.Header>
                    <h5 className="mb-0">
                        État de Recouvrement {activeTab === 'by-class' ? 'par Classe' : 'par Section'}
                        {recoveryData.length > 0 && (
                            <Badge bg="secondary" className="ms-2">
                                {recoveryData.length} enregistrement{recoveryData.length > 1 ? 's' : ''}
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
                                Aucune donnée trouvée
                            </p>
                            <p className="small text-muted">
                                Cliquez sur "Générer le Rapport" pour charger les données
                            </p>
                        </div>
                    ) : (
                        <div className="table-responsive">
                            <Table striped hover>
                                <thead className="table-dark">
                                    <tr>
                                        {activeTab === 'by-class' ? (
                                            <>
                                                <th>Classe</th>
                                                <th>Série</th>
                                                <th>Total Élèves</th>
                                                <th>Payés</th>
                                                <th>Non Payés</th>
                                                <th>Taux (%)</th>
                                                <th className="text-end">Collecté</th>
                                                <th className="text-end">Restant</th>
                                            </>
                                        ) : (
                                            <>
                                                <th>Section</th>
                                                <th>Total Élèves</th>
                                                <th>Payés</th>
                                                <th>Non Payés</th>
                                                <th>Taux (%)</th>
                                                <th className="text-end">Collecté</th>
                                                <th className="text-end">Restant</th>
                                            </>
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {recoveryData.map((item, index) => (
                                        <tr key={index}>
                                            {activeTab === 'by-class' ? (
                                                <>
                                                    <td><strong>{item.class_name}</strong></td>
                                                    <td>{item.series_name}</td>
                                                    <td>{item.total_students}</td>
                                                    <td>
                                                        <Badge bg="success">{item.paid_students}</Badge>
                                                    </td>
                                                    <td>
                                                        <Badge bg="danger">{item.unpaid_students}</Badge>
                                                    </td>
                                                    <td>
                                                        <Badge bg={getRecoveryRateColor(item.recovery_rate)}>
                                                            {item.recovery_rate}%
                                                        </Badge>
                                                    </td>
                                                    <td className="text-end">
                                                        <strong className="text-success">
                                                            {formatAmount(item.collected_amount)}
                                                        </strong>
                                                    </td>
                                                    <td className="text-end">
                                                        <strong className="text-warning">
                                                            {formatAmount(item.remaining_amount)}
                                                        </strong>
                                                    </td>
                                                </>
                                            ) : (
                                                <>
                                                    <td><strong>{item.section_name}</strong></td>
                                                    <td>{item.total_students}</td>
                                                    <td>
                                                        <Badge bg="success">{item.paid_students}</Badge>
                                                    </td>
                                                    <td>
                                                        <Badge bg="danger">{item.unpaid_students}</Badge>
                                                    </td>
                                                    <td>
                                                        <Badge bg={getRecoveryRateColor(item.recovery_rate)}>
                                                            {item.recovery_rate}%
                                                        </Badge>
                                                    </td>
                                                    <td className="text-end">
                                                        <strong className="text-success">
                                                            {formatAmount(item.collected_amount)}
                                                        </strong>
                                                    </td>
                                                    <td className="text-end">
                                                        <strong className="text-warning">
                                                            {formatAmount(item.remaining_amount)}
                                                        </strong>
                                                    </td>
                                                </>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot className="table-secondary">
                                    <tr>
                                        <th colSpan={activeTab === 'by-class' ? 6 : 5} className="text-end">
                                            <strong>TOTAUX :</strong>
                                        </th>
                                        <th className="text-end">
                                            <strong className="text-success fs-6">
                                                {formatAmount(summary.collected_amount || 0)}
                                            </strong>
                                        </th>
                                        <th className="text-end">
                                            <strong className="text-warning fs-6">
                                                {formatAmount(summary.remaining_amount || 0)}
                                            </strong>
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

export default RecoveryStatus;