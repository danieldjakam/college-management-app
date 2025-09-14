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
    PeopleFill,
    PersonLinesFill
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
    const [series, setSeries] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [activeTab, setActiveTab] = useState('by-class');
    const [showStudentList, setShowStudentList] = useState(false);

    // Filtres
    const [filters, setFilters] = useState({
        section_id: '',
        class_id: '',
        series_id: ''
    });

    useEffect(() => {
        loadSections();
    }, []);

    // Reset classes when section changes
    useEffect(() => {
        if (filters.section_id) {
            loadClassesBySection(filters.section_id);
            setFilters(prev => ({ ...prev, class_id: '', series_id: '' }));
            setSeries([]);
            setShowStudentList(false); // Reset checkbox when section changes
        } else {
            setClasses([]);
            setSeries([]);
            setShowStudentList(false);
        }
    }, [filters.section_id]);

    // Reset series when class changes
    useEffect(() => {
        if (filters.class_id) {
            loadSeriesByClass(filters.class_id);
            setFilters(prev => ({ ...prev, series_id: '' }));
        } else {
            setSeries([]);
            setShowStudentList(false); // Reset checkbox when class is deselected
        }
    }, [filters.class_id]);

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

    const loadClassesBySection = async (sectionId) => {
        try {
            const response = await secureApiEndpoints.schoolClasses.getAll();
            if (response.success) {
                // Filter classes by section through level.section_id
                const filteredClasses = response.data.filter(cls =>
                    cls.level && cls.level.section_id == sectionId
                );
                setClasses(filteredClasses);
            }
        } catch (error) {
            console.error('Error loading classes:', error);
            setClasses([]);
        }
    };

    const loadSeriesByClass = async (classId) => {
        try {
            const response = await secureApiEndpoints.schoolClasses.getSeriesInSameClass(classId);
            if (response.success) {
                setSeries(response.data);
            }
        } catch (error) {
            console.error('Error loading series:', error);
            setSeries([]);
        }
    };

    const loadRecoveryStatus = async () => {
        try {
            // Validation hierarchique obligatoire
            if (!filters.section_id) {
                setError("Veuillez sélectionner au moins une section");
                return;
            }

            // Pour la liste des élèves, la classe est obligatoire
            if (showStudentList && !filters.class_id) {
                setError("Veuillez sélectionner une classe pour afficher la liste des élèves");
                return;
            }

            setLoading(true);
            setError('');

            // Déterminer le type de rapport basé sur l'onglet actif et la checkbox
            let reportType = activeTab;
            if (activeTab === 'by-class' && showStudentList) {
                reportType = 'by-students';
            }

            const response = await secureApiEndpoints.reports.getRecoveryStatus({
                type: reportType,
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
            // Validation hierarchique obligatoire
            if (!filters.section_id) {
                setError("Veuillez sélectionner au moins une section pour exporter");
                return;
            }

            // Pour la liste des élèves, la classe est obligatoire
            if (showStudentList && !filters.class_id) {
                setError("Veuillez sélectionner une classe pour exporter la liste des élèves");
                return;
            }

            setLoading(true);

            // Déterminer le type de rapport basé sur l'onglet actif et la checkbox
            let reportType = activeTab;
            if (activeTab === 'by-class' && showStudentList) {
                reportType = 'by-students';
            }

            const exportParams = {
                type: reportType,
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

            let csvHeaders, csvRows;

            // Déterminer le type de rapport basé sur l'onglet actif et la checkbox
            const reportType = (activeTab === 'by-class' && showStudentList) ? 'by-students' : activeTab;

            if (reportType === 'by-students') {
                csvHeaders = ['Nom Complet', 'Matricule', 'Classe', 'Série', 'Statut de Paiement', 'Pension Base', 'Payé (Espèces)', 'Bourses', 'Réductions', 'Total Avantages', 'Montant Restant'];
                csvRows = recoveryData.map(item => [
                    item.full_name,
                    item.student_identifier,
                    item.class_name,
                    item.series_name,
                    item.payment_status,
                    item.total_amount,
                    item.paid_amount,
                    item.scholarship_amount || 0,
                    item.reduction_amount || 0,
                    item.total_virtual_payments || (item.scholarship_amount + item.reduction_amount) || 0,
                    item.remaining_amount
                ]);
            } else if (activeTab === 'by-class') {
                csvHeaders = ['Classe', 'Série', 'Total Élèves', 'Élèves Payés', 'Élèves Non Payés', 'Taux de Recouvrement (%)', 'Montant Collecté', 'Montant Restant'];
                csvRows = recoveryData.map(item => [
                    item.class_name,
                    item.series_name,
                    item.total_students,
                    item.paid_students,
                    item.unpaid_students,
                    item.recovery_rate,
                    item.collected_amount,
                    item.remaining_amount
                ]);
            } else {
                csvHeaders = ['Section', 'Total Élèves', 'Élèves Payés', 'Élèves Non Payés', 'Taux de Recouvrement (%)', 'Montant Collecté', 'Montant Restant'];
                csvRows = recoveryData.map(item => [
                    item.section_name,
                    item.total_students,
                    item.paid_students,
                    item.unpaid_students,
                    item.recovery_rate,
                    item.collected_amount,
                    item.remaining_amount
                ]);
            }

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
                        {/* Section (toujours obligatoire) */}
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>
                                    <span className="text-danger">*</span> Section
                                </Form.Label>
                                <Form.Select
                                    value={filters.section_id}
                                    onChange={(e) => setFilters({ ...filters, section_id: e.target.value })}
                                    className={!filters.section_id ? 'border-warning' : ''}
                                >
                                    <option value="">Sélectionnez une section</option>
                                    {sections.map(section => (
                                        <option key={section.id} value={section.id}>
                                            {section.name}
                                        </option>
                                    ))}
                                </Form.Select>
                            </Form.Group>
                        </Col>

                        {/* Classe (activée seulement si section sélectionnée) */}
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>
                                    Classe
                                    {!filters.section_id && <small className="text-muted">(sélectionnez une section d'abord)</small>}
                                </Form.Label>
                                <Form.Select
                                    value={filters.class_id}
                                    onChange={(e) => setFilters({ ...filters, class_id: e.target.value })}
                                    disabled={!filters.section_id}
                                    className={filters.section_id && !filters.class_id ? 'border-info' : ''}
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

                        {/* Série (activée seulement si classe sélectionnée) */}
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>
                                    Série
                                    {!filters.class_id && <small className="text-muted">(sélectionnez une classe d'abord)</small>}
                                </Form.Label>
                                <Form.Select
                                    value={filters.series_id}
                                    onChange={(e) => setFilters({ ...filters, series_id: e.target.value })}
                                    disabled={!filters.class_id}
                                    className={filters.class_id && !filters.series_id ? 'border-info' : ''}
                                >
                                    <option value="">Toutes les séries</option>
                                    {series.map(serie => (
                                        <option key={serie.id} value={serie.id}>
                                            {serie.name}
                                        </option>
                                    ))}
                                </Form.Select>
                            </Form.Group>
                        </Col>

                        <Col md={3} className="d-flex flex-column align-items-end">
                            {/* Checkbox pour afficher par liste d'élèves - visible seulement si classe sélectionnée et onglet par classe */}
                            {activeTab === 'by-class' && filters.class_id && (
                                <Form.Check
                                    type="checkbox"
                                    id="showStudentList"
                                    label="Afficher par liste d'élèves"
                                    checked={showStudentList}
                                    onChange={(e) => setShowStudentList(e.target.checked)}
                                    className="mb-2 text-small"
                                />
                            )}

                            <Button
                                variant="primary"
                                onClick={loadRecoveryStatus}
                                disabled={loading || !filters.section_id || (showStudentList && !filters.class_id)}
                                className={(!filters.section_id || (showStudentList && !filters.class_id)) ? 'opacity-50' : ''}
                            >
                                <Search className="me-2" />
                                {loading ? 'Chargement...' : 'Générer le Rapport'}
                            </Button>
                        </Col>
                    </Row>

                    {/* Aide visuelle */}
                    <Row className="mt-3">
                        <Col>
                            <div className="d-flex align-items-center">
                                <small className="text-muted">
                                    <span className="text-danger">*</span> Obligatoire |
                                    Ordre de filtrage : <strong>Section → Classe → Série</strong>
                                </small>
                                {filters.section_id && (
                                    <Badge bg="primary" className="ms-2">
                                        {sections.find(s => s.id == filters.section_id)?.name}
                                        {filters.class_id && (
                                            <>
                                                → {classes.find(c => c.id == filters.class_id)?.name}
                                                {filters.series_id && (
                                                    <>→ {series.find(s => s.id == filters.series_id)?.name}</>
                                                )}
                                            </>
                                        )}
                                    </Badge>
                                )}
                            </div>
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
                        État de Recouvrement {
                            activeTab === 'by-class' && showStudentList ? 'par Liste d\'Élèves' :
                            activeTab === 'by-class' ? 'par Classe' :
                            'par Section'
                        }
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
                                        {activeTab === 'by-class' && showStudentList ? (
                                            <>
                                                <th>Nom Complet</th>
                                                <th>Matricule</th>
                                                <th>Classe</th>
                                                <th>Série</th>
                                                <th>Statut</th>
                                                <th className="text-end">Pension Base</th>
                                                <th className="text-end">Payé (Espèces)</th>
                                                <th className="text-end">Bourses/Réductions</th>
                                                <th className="text-end">Restant</th>
                                            </>
                                        ) : activeTab === 'by-class' ? (
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
                                            {activeTab === 'by-class' && showStudentList ? (
                                                <>
                                                    <td><strong>{item.full_name || `${item.first_name} ${item.last_name}`}</strong></td>
                                                    <td><code>{item.student_identifier}</code></td>
                                                    <td>{item.class_name}</td>
                                                    <td>{item.series_name}</td>
                                                    <td>
                                                        <Badge bg={item.payment_status === 'Payé' ? 'success' : item.payment_status === 'Partiellement payé' ? 'warning' : 'danger'}>
                                                            {item.payment_status || (item.remaining_amount === 0 ? 'Payé' : item.paid_amount > 0 ? 'Partiellement payé' : 'Non payé')}
                                                        </Badge>
                                                    </td>
                                                    <td className="text-end">
                                                        <strong className="text-primary">
                                                            {formatAmount(item.total_amount)}
                                                        </strong>
                                                    </td>
                                                    <td className="text-end">
                                                        <strong className="text-info">
                                                            {formatAmount(item.paid_amount)}
                                                        </strong>
                                                    </td>
                                                    <td className="text-end">
                                                        <span className="text-success">
                                                            {formatAmount(item.total_virtual_payments || (item.scholarship_amount + item.reduction_amount))}
                                                        </span>
                                                        {item.scholarship_amount > 0 && (
                                                            <small className="d-block text-muted">
                                                                Bourses: {formatAmount(item.scholarship_amount)}
                                                            </small>
                                                        )}
                                                        {item.reduction_amount > 0 && (
                                                            <small className="d-block text-muted">
                                                                Réductions: {formatAmount(item.reduction_amount)}
                                                            </small>
                                                        )}
                                                    </td>
                                                    <td className="text-end">
                                                        <strong className="text-warning">
                                                            {formatAmount(item.remaining_amount)}
                                                        </strong>
                                                    </td>
                                                </>
                                            ) : activeTab === 'by-class' ? (
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
                                {!(activeTab === 'by-class' && showStudentList) && (
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
                                )}
                            </Table>
                        </div>
                    )}
                </Card.Body>
            </Card>
        </Container>
    );
};

export default RecoveryStatus;