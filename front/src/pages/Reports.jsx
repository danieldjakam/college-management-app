import React, { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Nav,
    Tab,
    Button,
    Form,
    Table,
    Spinner,
    Alert,
    Badge
} from 'react-bootstrap';
import {
    FileEarmarkText,
    Download,
    Printer,
    Calendar,
    Building,
    CashCoin,
    FileText,
    Search
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../utils/apiMigration';
import { useSchool } from '../contexts/SchoolContext';
import Swal from 'sweetalert2';

const Reports = () => {
    const { schoolSettings, formatCurrency } = useSchool();
    const [activeTab, setActiveTab] = useState('insolvable');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [reportData, setReportData] = useState(null);

    // États pour les filtres
    const [filters, setFilters] = useState({
        // Filtre principal
        filterType: 'section', // section, class, series
        
        // Filtres spécifiques
        sectionId: '',
        classId: '',
        seriesId: ''
    });

    const [availableOptions, setAvailableOptions] = useState({
        sections: [],
        classes: [],
        series: []
    });

    useEffect(() => {
        loadAvailableOptions();
    }, []);

    const loadAvailableOptions = async () => {
        try {
            // Charger les sections, classes disponibles
            const [sectionsRes, classesRes] = await Promise.all([
                secureApiEndpoints.sections.getAll(),
                secureApiEndpoints.schoolClasses.getAll()
            ]);

            // S'assurer que les données sont des tableaux
            const sections = (sectionsRes.success && Array.isArray(sectionsRes.data)) ? sectionsRes.data : [];
            const classes = (classesRes.success && Array.isArray(classesRes.data)) ? classesRes.data : [];
            
            
            // Charger toutes les séries de toutes les classes
            let allSeries = [];
            if (classes.length > 0) {
                try {
                    const seriesPromises = classes.map(schoolClass => 
                        secureApiEndpoints.accountant.getClassSeries(schoolClass.id)
                    );
                    const seriesResults = await Promise.all(seriesPromises);
                    
                    seriesResults.forEach((result, index) => {
                        if (result.success && result.data) {
                            // S'assurer que result.data est un tableau
                            const seriesData = Array.isArray(result.data) ? result.data : [];
                            allSeries = [...allSeries, ...seriesData];
                        }
                    });
                } catch (seriesError) {
                    console.error('Error loading series:', seriesError);
                }
            }


            setAvailableOptions({
                sections,
                classes,
                series: allSeries
            });
        } catch (error) {
            console.error('Error loading options:', error);
        }
    };

    const generateReport = async (reportType) => {
        setLoading(true);
        setError('');
        
        
        try {
            let response;
            
            switch (reportType) {
                case 'insolvable':
                    response = await secureApiEndpoints.reports.getInsolvableReport(filters);
                    break;
                case 'payments':
                    response = await secureApiEndpoints.reports.getPaymentsReport(filters);
                    break;
                case 'rame':
                    response = await secureApiEndpoints.reports.getRameReport(filters);
                    break;
                case 'recovery':
                    response = await secureApiEndpoints.reports.getRecoveryReport(filters);
                    break;
                case 'collection_summary':
                    response = await secureApiEndpoints.reports.getCollectionSummaryReport(filters);
                    break;
                case 'payment_details':
                    response = await secureApiEndpoints.reports.getPaymentDetailsReport(filters);
                    break;
                case 'scholarships_discounts':
                    response = await secureApiEndpoints.reports.getScholarshipsDiscountsReport(filters);
                    break;
                default:
                    throw new Error('Type de rapport non reconnu');
            }

            if (response.success) {
                setReportData(response.data);
            } else {
                setError(response.message || 'Erreur lors de la génération du rapport');
            }
        } catch (error) {
            setError('Erreur lors de la génération du rapport');
            console.error('Error generating report:', error);
        } finally {
            setLoading(false);
        }
    };

    const exportReport = async (format = 'pdf') => {
        if (!reportData) {
            Swal.fire('Erreur', 'Aucun rapport à exporter', 'error');
            return;
        }

        try {
            Swal.fire({
                title: 'Export en cours...',
                text: 'Génération du fichier PDF',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Préparer les paramètres pour l'export
            const exportParams = {
                ...filters,
                report_type: activeTab
            };

            // Appeler l'API d'export PDF
            const response = await secureApiEndpoints.reports.exportPdf(exportParams);
            
            if (response.success) {
                // Ouvrir le HTML dans une nouvelle fenêtre pour impression/sauvegarde PDF
                const printWindow = window.open('', '_blank');
                printWindow.document.write(response.data);
                printWindow.document.close();
                
                // Attendre un peu que le contenu se charge puis déclencher l'impression
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 500);

                Swal.fire('Succès', 'Rapport ouvert pour impression/sauvegarde PDF', 'success');
            } else {
                Swal.fire('Erreur', response.message || 'Erreur lors de l\'export PDF', 'error');
            }

        } catch (error) {
            console.error('Error exporting PDF:', error);
            Swal.fire('Erreur', 'Erreur lors de l\'export PDF', 'error');
        }
    };


    const renderFilterSection = () => (
        <Card className="mb-4">
            <Card.Header>
                <h5 className="mb-0">
                    <Search className="me-2" />
                    Filtres
                </h5>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={4}>
                        <Form.Group className="mb-3">
                            <Form.Label>Filtrer par</Form.Label>
                            <Form.Select
                                value={filters.filterType}
                                onChange={(e) => {
                                    setFilters({
                                        ...filters, 
                                        filterType: e.target.value,
                                        sectionId: '',
                                        classId: '',
                                        seriesId: ''
                                    });
                                }}
                            >
                                <option value="section">Section</option>
                                <option value="class">Classe</option>
                                <option value="series">Série</option>
                            </Form.Select>
                        </Form.Group>
                    </Col>
                    <Col md={4}>
                        <Form.Group className="mb-3">
                            <Form.Label>
                                {filters.filterType === 'section' ? 'Section' : 
                                 filters.filterType === 'class' ? 'Classe' : 'Série'}
                            </Form.Label>
                            <Form.Select
                                value={
                                    filters.filterType === 'section' ? filters.sectionId :
                                    filters.filterType === 'class' ? filters.classId :
                                    filters.seriesId
                                }
                                onChange={(e) => {
                                    const newFilters = {...filters};
                                    if (filters.filterType === 'section') {
                                        newFilters.sectionId = e.target.value;
                                    } else if (filters.filterType === 'class') {
                                        newFilters.classId = e.target.value;
                                    } else {
                                        newFilters.seriesId = e.target.value;
                                    }
                                    setFilters(newFilters);
                                }}
                            >
                                <option value="">
                                    {filters.filterType === 'section' ? 'Toutes les sections' :
                                     filters.filterType === 'class' ? 'Toutes les classes' : 
                                     'Toutes les séries'}
                                </option>
                                {filters.filterType === 'section' && availableOptions.sections.map(section => (
                                    <option key={section.id} value={section.id}>
                                        {section.name}
                                    </option>
                                ))}
                                {filters.filterType === 'class' && availableOptions.classes.map(schoolClass => (
                                    <option key={schoolClass.id} value={schoolClass.id}>
                                        {schoolClass.name}
                                    </option>
                                ))}
                                {filters.filterType === 'series' && availableOptions.series.map(series => (
                                    <option key={series.id} value={series.id}>
                                        {series.name}
                                    </option>
                                ))}
                            </Form.Select>
                        </Form.Group>
                    </Col>
                </Row>
                <Row>
                    <Col className="d-flex gap-2">
                        <Button 
                            variant="primary" 
                            onClick={() => generateReport(activeTab)}
                            disabled={loading}
                        >
                            {loading ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Génération...
                                </>
                            ) : (
                                <>
                                    <FileEarmarkText className="me-2" />
                                    Générer le rapport
                                </>
                            )}
                        </Button>
                        {reportData && (
                            <>
                                <Button variant="outline-success" onClick={() => exportReport('pdf')}>
                                    <Download className="me-2" />
                                    Exporter PDF
                                </Button>
                            </>
                        )}
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    );

    const renderInsolvableReport = () => (
        <Card>
            <Card.Header>
                <h5 className="mb-0">État Insolvable - Élèves n'ayant pas fini de payer</h5>
            </Card.Header>
            <Card.Body>
                {reportData ? (
                    <>
                        <div className="mb-3">
                            <Badge bg="info">Total des élèves insolvables: {reportData?.total_insolvable_students || 0}</Badge>
                        </div>
                        <Table responsive striped size="sm">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Classe/Série</th>
                                    <th>Total Requis</th>
                                    <th>Total Payé</th>
                                    <th>Reste à Payer</th>
                                    <th>Tranches Incomplètes</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reportData.students?.map((studentData, studentIndex) => (
                                    <tr key={studentIndex}>
                                        <td>{studentData?.student?.full_name}</td>
                                        <td>{studentData?.student?.class_series}</td>
                                        <td>{formatCurrency(studentData?.total_required || 0)}</td>
                                        <td>{formatCurrency(studentData?.total_paid || 0)}</td>
                                        <td className="text-danger">{formatCurrency(studentData?.total_remaining || 0)}</td>
                                        <td>
                                            {studentData.incomplete_tranches?.map((tranche, trancheIndex) => (
                                                <div key={trancheIndex}>
                                                    <small className="text-muted">
                                                        <strong>{tranche?.tranche_name}:</strong> {formatCurrency(tranche?.paid_amount || 0)}/{formatCurrency(tranche?.required_amount || 0)}
                                                        <span className="text-danger"> (reste: {formatCurrency(tranche?.remaining_amount || 0)})</span>
                                                    </small>
                                                </div>
                                            ))}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    </>
                ) : (
                    <p className="text-muted text-center">Générez un rapport pour voir les données</p>
                )}
            </Card.Body>
        </Card>
    );

    const renderPaymentsReport = () => (
        <Card>
            <Card.Header>
                <h5 className="mb-0">État des Paiements - Tous les élèves avec infos toutes tranches</h5>
            </Card.Header>
            <Card.Body>
                {reportData ? (
                    <>
                        <div className="mb-3">
                            <Badge bg="info">Total des élèves: {reportData?.total_students || 0}</Badge>
                        </div>
                        {reportData.students?.map((studentData, studentIndex) => (
                            <div key={studentIndex} className="mb-3 p-3 border rounded">
                                <h6 className="mb-2">{studentData?.student?.full_name} - {studentData?.student?.class_series}</h6>
                                <Table responsive striped size="sm">
                                    <thead>
                                        <tr>
                                            <th>Tranche</th>
                                            <th>Montant Requis</th>
                                            <th>Montant Payé</th>
                                            <th>Reste à Payer</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {studentData?.tranches_details?.map((tranche, trancheIndex) => (
                                            <tr key={trancheIndex}>
                                                <td>{tranche?.tranche_name}</td>
                                                <td>{formatCurrency(tranche?.required_amount || 0)}</td>
                                                <td>{formatCurrency(tranche?.paid_amount || 0)}</td>
                                                <td className={(tranche?.remaining_amount || 0) > 0 ? "text-danger" : "text-success"}>
                                                    {formatCurrency(tranche?.remaining_amount || 0)}
                                                </td>
                                                <td>
                                                    <Badge bg={tranche?.status === 'complete' ? 'success' : 'warning'}>
                                                        {tranche?.status === 'complete' ? 'Complet' : 'Incomplet'}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            </div>
                        ))}
                    </>
                ) : (
                    <p className="text-muted text-center">Générez un rapport pour voir les données</p>
                )}
            </Card.Body>
        </Card>
    );

    const renderRameReport = () => (
        <Card>
            <Card.Header>
                <h5 className="mb-0">État des RAME - Détails par élève (espèces/physique/pas payé)</h5>
            </Card.Header>
            <Card.Body>
                {reportData ? (
                    <>
                        <div className="mb-3">
                            <Row>
                                <Col md={3}>
                                    <Badge bg="info">Total élèves: {reportData?.summary?.total_students || 0}</Badge>
                                </Col>
                                <Col md={3}>
                                    <Badge bg="success">Payés: {reportData?.summary?.paid_count || 0}</Badge>
                                </Col>
                                <Col md={3}>
                                    <Badge bg="primary">Espèces: {reportData?.summary?.cash_count || 0}</Badge>
                                </Col>
                                <Col md={3}>
                                    <Badge bg="secondary">Physique: {reportData?.summary?.physical_count || 0}</Badge>
                                </Col>
                            </Row>
                        </div>
                        <Table responsive striped size="sm">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Classe/Série</th>
                                    <th>Montant RAME</th>
                                    <th>Montant Payé</th>
                                    <th>Type de Paiement</th>
                                    <th>Statut</th>
                                    <th>Date Paiement</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reportData.students?.map((studentData, studentIndex) => (
                                    <tr key={studentIndex}>
                                        <td>{studentData?.student?.full_name}</td>
                                        <td>{studentData?.student?.class_series}</td>
                                        <td>{formatCurrency(studentData?.rame_details?.required_amount || 0)}</td>
                                        <td>{formatCurrency(studentData?.rame_details?.paid_amount || 0)}</td>
                                        <td>
                                            <Badge bg={
                                                studentData?.rame_details?.payment_type === 'physical' ? 'success' : 
                                                studentData?.rame_details?.payment_type === 'cash' ? 'primary' : 
                                                'secondary'
                                            }>
                                                {studentData?.rame_details?.payment_type === 'physical' ? 'Physique' : 
                                                 studentData?.rame_details?.payment_type === 'cash' ? 'Espèces' : 
                                                 'Non payé'}
                                            </Badge>
                                        </td>
                                        <td>
                                            <Badge bg={studentData?.rame_details?.payment_status === 'paid' ? 'success' : 'warning'}>
                                                {studentData?.rame_details?.payment_status === 'paid' ? 'Payé' : 'En attente'}
                                            </Badge>
                                        </td>
                                        <td>
                                            {studentData?.rame_details?.payment_date ? 
                                                new Date(studentData.rame_details.payment_date).toLocaleDateString('fr-FR') : 
                                                '-'
                                            }
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    </>
                ) : (
                    <p className="text-muted text-center">Générez un rapport pour voir les données</p>
                )}
            </Card.Body>
        </Card>
    );

    const renderRecoveryReport = () => (
        <Card>
            <Card.Header>
                <h5 className="mb-0">Rapport de Recouvrement</h5>
            </Card.Header>
            <Card.Body>
                {reportData ? (
                    <>
                        <Row className="mb-4">
                            <Col md={3}>
                                <Card className="text-center">
                                    <Card.Body>
                                        <h3 className="text-primary">{formatCurrency(reportData.summary?.total_expected)}</h3>
                                        <p className="text-muted mb-0">Total Attendu</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={3}>
                                <Card className="text-center">
                                    <Card.Body>
                                        <h3 className="text-success">{formatCurrency(reportData.summary?.total_collected)}</h3>
                                        <p className="text-muted mb-0">Total Collecté</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={3}>
                                <Card className="text-center">
                                    <Card.Body>
                                        <h3 className="text-warning">{formatCurrency(reportData.summary?.total_remaining)}</h3>
                                        <p className="text-muted mb-0">Reste à Collecter</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={3}>
                                <Card className="text-center">
                                    <Card.Body>
                                        <h3 className="text-info">{reportData.summary?.recovery_rate?.toFixed(1)}%</h3>
                                        <p className="text-muted mb-0">Taux de Recouvrement</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>
                        
                        <Table responsive striped>
                            <thead>
                                <tr>
                                    <th>Période</th>
                                    <th>Attendu</th>
                                    <th>Collecté</th>
                                    <th>Reste</th>
                                    <th>Taux</th>
                                    <th>Bourses</th>
                                    <th>Réductions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reportData.periods?.map((period, index) => (
                                    <tr key={index}>
                                        <td>{period.period_name}</td>
                                        <td>{formatCurrency(period.expected)}</td>
                                        <td>{formatCurrency(period.collected)}</td>
                                        <td>{formatCurrency(period.remaining)}</td>
                                        <td>
                                            <Badge bg={period.rate >= 80 ? 'success' : period.rate >= 50 ? 'warning' : 'danger'}>
                                                {period.rate?.toFixed(1)}%
                                            </Badge>
                                        </td>
                                        <td>{formatCurrency(period.scholarships)}</td>
                                        <td>{formatCurrency(period.reductions)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    </>
                ) : (
                    <p className="text-muted text-center">Générez un rapport pour voir les données</p>
                )}
            </Card.Body>
        </Card>
    );

    const renderCollectionSummaryReport = () => (
        <Card>
            <Card.Header>
                <h5 className="mb-0">État Récapitulatif des Encaissements</h5>
                <small className="text-muted">Montant total dû, payé et reste par classe/série</small>
            </Card.Header>
            <Card.Body>
                {reportData ? (
                    <>
                        <Row className="mb-4">
                            <Col md={3}>
                                <Card className="text-center border-primary">
                                    <Card.Body>
                                        <h4 className="text-primary">{formatCurrency(reportData.summary?.total_due || 0)}</h4>
                                        <p className="text-muted mb-0">Total Dû</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={3}>
                                <Card className="text-center border-success">
                                    <Card.Body>
                                        <h4 className="text-success">{formatCurrency(reportData.summary?.total_paid || 0)}</h4>
                                        <p className="text-muted mb-0">Total Payé</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={3}>
                                <Card className="text-center border-warning">
                                    <Card.Body>
                                        <h4 className="text-warning">{formatCurrency(reportData.summary?.total_remaining || 0)}</h4>
                                        <p className="text-muted mb-0">Total Reste</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={3}>
                                <Card className="text-center border-info">
                                    <Card.Body>
                                        <h4 className="text-info">{reportData.summary?.collection_rate?.toFixed(1) || 0}%</h4>
                                        <p className="text-muted mb-0">Taux Encaissement</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>

                        <Table responsive striped size="sm">
                            <thead className="table-dark">
                                <tr>
                                    <th>Classe</th>
                                    <th>Série</th>
                                    <th>Nombre Élèves</th>
                                    <th>Montant Total Dû</th>
                                    <th>Montant Payé</th>
                                    <th>Montant Reste</th>
                                    <th>Taux (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reportData.classes?.map((classData, index) => (
                                    <tr key={index}>
                                        <td><strong>{classData.class_name}</strong></td>
                                        <td>{classData.series_name}</td>
                                        <td><Badge bg="info">{classData.student_count}</Badge></td>
                                        <td>{formatCurrency(classData.total_due)}</td>
                                        <td className="text-success">{formatCurrency(classData.total_paid)}</td>
                                        <td className="text-danger">{formatCurrency(classData.total_remaining)}</td>
                                        <td>
                                            <Badge bg={classData.collection_rate >= 80 ? 'success' : classData.collection_rate >= 50 ? 'warning' : 'danger'}>
                                                {classData.collection_rate?.toFixed(1)}%
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    </>
                ) : (
                    <p className="text-muted text-center">Générez un rapport pour voir les données</p>
                )}
            </Card.Body>
        </Card>
    );

    const renderPaymentDetailsReport = () => (
        <Card>
            <Card.Header>
                <h5 className="mb-0">Détail du Paiement des Frais de Scolarité</h5>
                <small className="text-muted">Versements de chaque élève par classe et série</small>
            </Card.Header>
            <Card.Body>
                {reportData ? (
                    <>
                        <div className="mb-3">
                            <Badge bg="info">Total des versements: {reportData.payments?.length || 0}</Badge>
                            <Badge bg="success" className="ms-2">Montant total: {formatCurrency(reportData.summary?.total_amount || 0)}</Badge>
                        </div>

                        {/* Grouper par classe */}
                        {reportData.classes?.map((classGroup, classIndex) => (
                            <div key={classIndex} className="mb-4">
                                <h6 className="bg-light p-2 rounded">
                                    <Building className="me-2" />
                                    {classGroup.class_name} - {classGroup.series_name}
                                    <Badge bg="secondary" className="ms-2">{classGroup.payments?.length || 0} versements</Badge>
                                </h6>
                                
                                <Table responsive striped size="sm">
                                    <thead>
                                        <tr>
                                            <th>Matricule</th>
                                            <th>Nom</th>
                                            <th>Prénom</th>
                                            <th>Type Paiement</th>
                                            <th>Date</th>
                                            <th>Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {classGroup.payments?.map((payment, paymentIndex) => (
                                            <tr key={paymentIndex}>
                                                <td><code>{payment.student_matricule}</code></td>
                                                <td><strong>{payment.student_lastname}</strong></td>
                                                <td>{payment.student_firstname}</td>
                                                <td>
                                                    <Badge bg="primary" size="sm">
                                                        {payment.payment_method === 'cash' ? 'ESP' : 
                                                         payment.payment_method === 'card' ? 'CB' :
                                                         payment.payment_method === 'transfer' ? 'VIR' :
                                                         payment.payment_method === 'check' ? 'CHQ' :
                                                         payment.payment_method}
                                                    </Badge>
                                                </td>
                                                <td>{new Date(payment.payment_date).toLocaleDateString('fr-FR')}</td>
                                                <td className="text-success"><strong>{formatCurrency(payment.amount)}</strong></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                                
                                <div className="text-end mb-3">
                                    <strong>Sous-total: {formatCurrency(classGroup.total_amount)}</strong>
                                </div>
                            </div>
                        ))}
                    </>
                ) : (
                    <p className="text-muted text-center">Générez un rapport pour voir les données</p>
                )}
            </Card.Body>
        </Card>
    );

    const renderScholarshipsDiscountsReport = () => (
        <Card>
            <Card.Header>
                <h5 className="mb-0">États Bourses et Rabais</h5>
                <small className="text-muted">Toutes les bourses/rabais attribués par élève, classe et série</small>
            </Card.Header>
            <Card.Body>
                {reportData ? (
                    <>
                        <Row className="mb-4">
                            <Col md={4}>
                                <Card className="text-center border-info">
                                    <Card.Body>
                                        <h4 className="text-info">{formatCurrency(reportData.summary?.total_scholarships || 0)}</h4>
                                        <p className="text-muted mb-0">Total Bourses</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={4}>
                                <Card className="text-center border-warning">
                                    <Card.Body>
                                        <h4 className="text-warning">{formatCurrency(reportData.summary?.total_discounts || 0)}</h4>
                                        <p className="text-muted mb-0">Total Rabais</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={4}>
                                <Card className="text-center border-success">
                                    <Card.Body>
                                        <h4 className="text-success">{reportData.summary?.beneficiary_count || 0}</h4>
                                        <p className="text-muted mb-0">Bénéficiaires</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>

                        <Table responsive striped size="sm">
                            <thead className="table-dark">
                                <tr>
                                    <th>Classe</th>
                                    <th>Nom & Prénoms de l'Élève</th>
                                    <th>Montant Scolarité</th>
                                    <th>Motif Rabais/Bourse</th>
                                    <th>Montant Rabais</th>
                                    <th>Obs</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reportData.students?.map((student, index) => (
                                    <tr key={index}>
                                        <td><strong>{student.class_name}</strong></td>
                                        <td>{student.student_name}</td>
                                        <td>{formatCurrency(student.tuition_amount)}</td>
                                        <td>
                                            {student.scholarship_reason && (
                                                <Badge bg="info" className="me-1">Bourse: {student.scholarship_reason}</Badge>
                                            )}
                                            {student.discount_reason && (
                                                <Badge bg="warning">Rabais: {student.discount_reason}</Badge>
                                            )}
                                        </td>
                                        <td className="text-success">
                                            <strong>{formatCurrency(student.total_benefit_amount)}</strong>
                                        </td>
                                        <td>
                                            {student.observation && (
                                                <small className="text-muted">{student.observation}</small>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>

                        {/* Récapitulatif par classe */}
                        <div className="mt-4">
                            <h6>Récapitulatif par Classe</h6>
                            <Table responsive size="sm">
                                <thead className="table-light">
                                    <tr>
                                        <th>Classe</th>
                                        <th>Série</th>
                                        <th>Nombre Bénéficiaires</th>
                                        <th>Total Bourses</th>
                                        <th>Total Rabais</th>
                                        <th>Total Avantages</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reportData.class_summary?.map((classSummary, index) => (
                                        <tr key={index}>
                                            <td><strong>{classSummary.class_name}</strong></td>
                                            <td>{classSummary.series_name}</td>
                                            <td><Badge bg="info">{classSummary.beneficiary_count}</Badge></td>
                                            <td className="text-info">{formatCurrency(classSummary.total_scholarships)}</td>
                                            <td className="text-warning">{formatCurrency(classSummary.total_discounts)}</td>
                                            <td className="text-success"><strong>{formatCurrency(classSummary.total_benefits)}</strong></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </div>
                    </>
                ) : (
                    <p className="text-muted text-center">Générez un rapport pour voir les données</p>
                )}
            </Card.Body>
        </Card>
    );

    return (
        <Container fluid className="py-4">
            <Row className="mb-4">
                <Col>
                    <h2>
                        <FileText className="me-3" />
                        Rapports Financiers
                    </h2>
                    <p className="text-muted">
                        Génération de rapports détaillés avec prise en compte des bourses et réductions
                    </p>
                </Col>
            </Row>

            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')}>
                    {error}
                </Alert>
            )}

            {renderFilterSection()}

            <Tab.Container activeKey={activeTab} onSelect={setActiveTab}>
                <Row>
                    <Col>
                        <Nav variant="tabs" className="mb-4">
                            <Nav.Item>
                                <Nav.Link eventKey="insolvable">
                                    <Building className="me-2" />
                                    État Insolvable
                                </Nav.Link>
                            </Nav.Item>
                            <Nav.Item>
                                <Nav.Link eventKey="payments">
                                    <CashCoin className="me-2" />
                                    Paiements
                                </Nav.Link>
                            </Nav.Item>
                            <Nav.Item>
                                <Nav.Link eventKey="rame">
                                    <FileEarmarkText className="me-2" />
                                    État RAME
                                </Nav.Link>
                            </Nav.Item>
                            <Nav.Item>
                                <Nav.Link eventKey="recovery">
                                    <Calendar className="me-2" />
                                    Recouvrement
                                </Nav.Link>
                            </Nav.Item>
                            <Nav.Item>
                                <Nav.Link eventKey="collection_summary">
                                    <FileEarmarkText className="me-2" />
                                    Récap. Encaissements
                                </Nav.Link>
                            </Nav.Item>
                            <Nav.Item>
                                <Nav.Link eventKey="payment_details">
                                    <CashCoin className="me-2" />
                                    Détails Paiements
                                </Nav.Link>
                            </Nav.Item>
                            <Nav.Item>
                                <Nav.Link eventKey="scholarships_discounts">
                                    <Building className="me-2" />
                                    Bourses & Rabais
                                </Nav.Link>
                            </Nav.Item>
                        </Nav>

                        <Tab.Content>
                            <Tab.Pane eventKey="insolvable">
                                {renderInsolvableReport()}
                            </Tab.Pane>
                            <Tab.Pane eventKey="payments">
                                {renderPaymentsReport()}
                            </Tab.Pane>
                            <Tab.Pane eventKey="rame">
                                {renderRameReport()}
                            </Tab.Pane>
                            <Tab.Pane eventKey="recovery">
                                {renderRecoveryReport()}
                            </Tab.Pane>
                            <Tab.Pane eventKey="collection_summary">
                                {renderCollectionSummaryReport()}
                            </Tab.Pane>
                            <Tab.Pane eventKey="payment_details">
                                {renderPaymentDetailsReport()}
                            </Tab.Pane>
                            <Tab.Pane eventKey="scholarships_discounts">
                                {renderScholarshipsDiscountsReport()}
                            </Tab.Pane>
                        </Tab.Content>
                    </Col>
                </Row>
            </Tab.Container>
        </Container>
    );
};

export default Reports;