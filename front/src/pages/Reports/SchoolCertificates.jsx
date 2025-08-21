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
    ButtonGroup
} from 'react-bootstrap';
import {
    Award,
    Download,
    FiletypePdf,
    Search,
    BookFill,
    PersonFill,
    PeopleFill,
    Printer
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { extractErrorMessage } from '../../utils/errorHandler';
import { authService } from '../../services/authService';
import Swal from 'sweetalert2';
import { host } from '../../utils/fetch';

const SchoolCertificates = () => {
    const [certificates, setCertificates] = useState([]);
    const [students, setStudents] = useState([]);
    const [classes, setClasses] = useState([]);
    const [sections, setSections] = useState([]);
    const [series, setSeries] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [generationType, setGenerationType] = useState('by-series');

    // Filtres
    const [filters, setFilters] = useState({
        section_id: '',
        class_id: '',
        series_id: '',
        student_id: ''
    });

    useEffect(() => {
        loadSections();
        loadClasses();
        loadSeries();
    }, []);

    useEffect(() => {
        if (filters.series_id && generationType === 'by-student') {
            loadStudentsBySeries();
        }
    }, [filters.series_id, generationType]);

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

    const loadSeries = async () => {
        try {
            const response = await secureApiEndpoints.accountant.getClasses();
            if (response.success && response.data && response.data.classes) {
                const allSeries = [];
                response.data.classes.forEach(schoolClass => {
                    if (schoolClass.series && Array.isArray(schoolClass.series)) {
                        schoolClass.series.forEach(serie => {
                            allSeries.push({
                                ...serie,
                                section_id: schoolClass?.level?.section?.id,
                                section_name: schoolClass?.level?.section?.name,
                                class_id: schoolClass?.id,
                                class_name: schoolClass?.name,
                                full_name: `${schoolClass?.level?.section?.name || ''} - ${schoolClass?.name || ''} - ${serie.name || ''}`
                            });
                        });
                    }
                });
                setSeries(allSeries);
            }
        } catch (error) {
            console.error('Error loading series:', error);
        }
    };

    const loadStudentsBySeries = async () => {
        if (!filters.series_id) return;
        
        try {
            const response = await secureApiEndpoints.students.getByClassSeries(filters.series_id);
            if (response.success) {
                setStudents(response.data.students || []);
            }
        } catch (error) {
            console.error('Error loading students:', error);
        }
    };

    const generateCertificates = async () => {
        if (generationType === 'by-section' && !filters.section_id) {
            setError('Veuillez sélectionner une section');
            return;
        }
        
        if (generationType === 'by-class' && !filters.class_id) {
            setError('Veuillez sélectionner une classe');
            return;
        }
        
        if (generationType === 'by-series' && !filters.series_id) {
            setError('Veuillez sélectionner une série');
            return;
        }
        
        if (generationType === 'by-student' && !filters.student_id) {
            setError('Veuillez sélectionner un élève');
            return;
        }

        try {
            setLoading(true);
            setError('');

            const response = await secureApiEndpoints.reports.generateSchoolCertificates({
                type: generationType,
                ...filters
            });

            if (response.success) {
                setCertificates(response.data.certificates);
                setSuccess(`${response.data.certificates.length} certificat(s) généré(s)`);
            } else {
                setError(response.message);
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const previewCertificate = async (studentId) => {
        try {
            setLoading(true);

            const response = await fetch(`${host}/api/reports/school-certificate/preview/${studentId}`, {
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
            
            setSuccess('Aperçu du certificat ouvert');
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const downloadAllCertificates = async () => {
        try {
            setLoading(true);

            const exportParams = {
                type: generationType,
                ...filters
            };

            const response = await fetch(`${host}/api/reports/school-certificates/download?${new URLSearchParams(exportParams).toString()}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/pdf'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const pdfBlob = await response.blob();
            const blobUrl = window.URL.createObjectURL(pdfBlob);
            
            // Créer un lien de téléchargement
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = `certificats_scolarite_${generationType}_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1000);
            setSuccess('Certificats téléchargés avec succès');
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR');
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>
                                <Award className="me-2" />
                                Certificats de Scolarité
                            </h2>
                            <p className="text-muted">
                                Génération et gestion des certificats de scolarité par classe, élève ou section
                            </p>
                        </div>
                        {certificates.length > 0 && (
                            <Button
                                variant="outline-danger"
                                onClick={downloadAllCertificates}
                                disabled={loading}
                            >
                                <Download className="me-2" />
                                Télécharger Tout (PDF)
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

            {/* Filtres et génération */}
            <Card className="mb-4">
                <Card.Header>
                    <h5 className="mb-0">
                        <Award className="me-2" />
                        Paramètres de Génération
                    </h5>
                </Card.Header>
                <Card.Body>
                    {/* Type de génération */}
                    <Row className="mb-3">
                        <Col>
                            <Form.Label><strong>Type de génération</strong></Form.Label>
                            <ButtonGroup className="d-flex">
                                <Button
                                    variant={generationType === 'by-section' ? 'primary' : 'outline-primary'}
                                    onClick={() => {
                                        setGenerationType('by-section');
                                        setFilters({ section_id: '', class_id: '', series_id: '', student_id: '' });
                                    }}
                                >
                                    <PeopleFill className="me-2" />
                                    Par Section
                                </Button>
                                <Button
                                    variant={generationType === 'by-class' ? 'primary' : 'outline-primary'}
                                    onClick={() => {
                                        setGenerationType('by-class');
                                        setFilters({ section_id: '', class_id: '', series_id: '', student_id: '' });
                                    }}
                                >
                                    <BookFill className="me-2" />
                                    Par Classe
                                </Button>
                                <Button
                                    variant={generationType === 'by-series' ? 'primary' : 'outline-primary'}
                                    onClick={() => {
                                        setGenerationType('by-series');
                                        setFilters({ section_id: '', class_id: '', series_id: '', student_id: '' });
                                    }}
                                >
                                    <Award className="me-2" />
                                    Par Série
                                </Button>
                                <Button
                                    variant={generationType === 'by-student' ? 'primary' : 'outline-primary'}
                                    onClick={() => {
                                        setGenerationType('by-student');
                                        setFilters({ section_id: '', class_id: '', series_id: '', student_id: '' });
                                    }}
                                >
                                    <PersonFill className="me-2" />
                                    Par Élève
                                </Button>
                            </ButtonGroup>
                        </Col>
                    </Row>

                    {/* Filtres selon le type */}
                    <Row>
                        {generationType === 'by-section' && (
                            <Col md={3}>
                                <Form.Group>
                                    <Form.Label>
                                        Section <span className="text-danger">*</span>
                                    </Form.Label>
                                    <Form.Select
                                        value={filters.section_id}
                                        onChange={(e) => setFilters({ ...filters, section_id: e.target.value })}
                                        required
                                    >
                                        <option value="">Sélectionner une section</option>
                                        {sections.map(section => (
                                            <option key={section.id} value={section.id}>
                                                {section.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        )}

                        {generationType === 'by-class' && (
                            <Col md={3}>
                                <Form.Group>
                                    <Form.Label>
                                        Classe <span className="text-danger">*</span>
                                    </Form.Label>
                                    <Form.Select
                                        value={filters.class_id}
                                        onChange={(e) => setFilters({ ...filters, class_id: e.target.value })}
                                        required
                                    >
                                        <option value="">Sélectionner une classe</option>
                                        {classes.map(cls => (
                                            <option key={cls.id} value={cls.id}>
                                                {cls.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        )}

                        {generationType === 'by-series' && (
                            <Col md={3}>
                                <Form.Group>
                                    <Form.Label>
                                        Série <span className="text-danger">*</span>
                                    </Form.Label>
                                    <Form.Select
                                        value={filters.series_id}
                                        onChange={(e) => setFilters({ ...filters, series_id: e.target.value })}
                                        required
                                    >
                                        <option value="">Sélectionner une série</option>
                                        {series.map(serie => (
                                            <option key={serie.id} value={serie.id}>
                                                {serie.full_name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        )}

                        {generationType === 'by-student' && (
                            <>
                                <Col md={3}>
                                    <Form.Group>
                                        <Form.Label>
                                            Série <span className="text-danger">*</span>
                                        </Form.Label>
                                        <Form.Select
                                            value={filters.series_id}
                                            onChange={(e) => setFilters({ ...filters, series_id: e.target.value, student_id: '' })}
                                            required
                                        >
                                            <option value="">Sélectionner une série</option>
                                            {series.map(serie => (
                                                <option key={serie.id} value={serie.id}>
                                                    {serie.full_name}
                                                </option>
                                            ))}
                                        </Form.Select>
                                    </Form.Group>
                                </Col>
                                {filters.series_id && (
                                    <Col md={3}>
                                        <Form.Group>
                                            <Form.Label>
                                                Élève <span className="text-danger">*</span>
                                            </Form.Label>
                                            <Form.Select
                                                value={filters.student_id}
                                                onChange={(e) => setFilters({ ...filters, student_id: e.target.value })}
                                                required
                                            >
                                                <option value="">Sélectionner un élève</option>
                                                {students.map(student => (
                                                    <option key={student.id} value={student.id}>
                                                        {student.last_name} {student.first_name}
                                                    </option>
                                                ))}
                                            </Form.Select>
                                        </Form.Group>
                                    </Col>
                                )}
                            </>
                        )}

                        <Col md={3} className="d-flex align-items-end">
                            <Button
                                variant="success"
                                onClick={generateCertificates}
                                disabled={loading}
                                className="w-100"
                            >
                                <Award className="me-2" />
                                {loading ? 'Génération...' : 'Générer les Certificats'}
                            </Button>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            {/* Liste des certificats générés */}
            {certificates.length > 0 && (
                <Card>
                    <Card.Header>
                        <h5 className="mb-0">
                            Certificats Générés
                            <Badge bg="primary" className="ms-2">
                                {certificates.length} certificat{certificates.length > 1 ? 's' : ''}
                            </Badge>
                        </h5>
                    </Card.Header>
                    <Card.Body>
                        <div className="table-responsive">
                            <Table striped hover>
                                <thead className="table-dark">
                                    <tr>
                                        <th>Matricule</th>
                                        <th>Nom et Prénom</th>
                                        <th>Classe</th>
                                        <th>Date de Naissance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {certificates.map((certificate, index) => (
                                        <tr key={index}>
                                            <td>
                                                <Badge bg="outline-primary" text="primary">
                                                    {certificate.matricule}
                                                </Badge>
                                            </td>
                                            <td>
                                                <strong>{certificate.nom} {certificate.prenom}</strong>
                                            </td>
                                            <td>{certificate.classe}</td>
                                            <td>{formatDate(certificate.date_naissance)}</td>
                                            <td>
                                                <ButtonGroup size="sm">
                                                    <Button
                                                        variant="outline-info"
                                                        onClick={() => previewCertificate(certificate.student_id)}
                                                        disabled={loading}
                                                    >
                                                        <Printer size={14} />
                                                    </Button>
                                                </ButtonGroup>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </div>
                    </Card.Body>
                </Card>
            )}

            {/* État vide */}
            {!loading && certificates.length === 0 && (
                <Card>
                    <Card.Body>
                        <div className="text-center py-5">
                            <Award size={64} className="text-muted mb-3" />
                            <h5 className="text-muted">Aucun certificat généré</h5>
                            <p className="text-muted">
                                Sélectionnez les paramètres et cliquez sur "Générer les Certificats"
                            </p>
                        </div>
                    </Card.Body>
                </Card>
            )}

            {/* Loading state */}
            {loading && certificates.length === 0 && (
                <div className="text-center py-5">
                    <Spinner animation="border" role="status" variant="primary">
                        <span className="visually-hidden">Génération en cours...</span>
                    </Spinner>
                    <p className="text-muted mt-3">Génération des certificats en cours...</p>
                </div>
            )}
        </Container>
    );
};

export default SchoolCertificates;