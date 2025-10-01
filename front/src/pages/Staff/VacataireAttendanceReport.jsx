import React, { useState } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Form,
    Table,
    Badge,
    Alert,
    Spinner
} from 'react-bootstrap';
import {
    FiletypePdf,
    Calendar,
    People,
    Clock,
    BarChart,
    Download
} from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';
import Swal from 'sweetalert2';

const VacataireAttendanceReport = () => {
    const [month, setMonth] = useState(new Date().toISOString().slice(0, 7)); // Format YYYY-MM
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [selectedUserId, setSelectedUserId] = useState('');
    const [vacatairesList, setVacatairesList] = useState([]);
    const [loading, setLoading] = useState(false);
    const [reportData, setReportData] = useState(null);
    const [generating, setGenerating] = useState(false);
    const [useCustomDates, setUseCustomDates] = useState(false);

    // Charger la liste des vacataires
    const loadVacataires = async () => {
        try {
            const response = await secureApi.get('/reports/vacataires-list');
            if (response.success) {
                setVacatairesList(response.data || []);
            }
        } catch (error) {
            console.error('Erreur chargement vacataires:', error);
        }
    };

    React.useEffect(() => {
        loadVacataires();
    }, []);

    const loadReport = async () => {
        try {
            setLoading(true);

            let url = '/reports/vacataire-attendance?';

            // Si on utilise les dates personnalisées
            if (useCustomDates && startDate && endDate) {
                url += `start_date=${startDate}&end_date=${endDate}`;
            } else {
                url += `month=${month}`;
            }

            // Ajouter le filtre par personne si sélectionné
            if (selectedUserId) {
                url += `&user_id=${selectedUserId}`;
            }

            const response = await secureApi.get(url);

            if (response.success) {
                setReportData(response.data);
            } else {
                Swal.fire('Erreur', response.message || 'Impossible de charger le rapport', 'error');
            }
        } catch (error) {
            console.error('Erreur chargement rapport:', error);
            Swal.fire('Erreur', 'Erreur lors du chargement du rapport', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleGeneratePdf = async () => {
        try {
            setGenerating(true);

            const token = localStorage.getItem('token');
            let url = `${secureApi.baseURL}/reports/vacataire-attendance/export-pdf?`;

            // Si on utilise les dates personnalisées
            if (useCustomDates && startDate && endDate) {
                url += `start_date=${startDate}&end_date=${endDate}`;
            } else {
                url += `month=${month}`;
            }

            // Ajouter le filtre par personne
            if (selectedUserId) {
                url += `&user_id=${selectedUserId}`;
            }

            url += `&token=${token}`;

            const link = document.createElement('a');
            link.href = url;
            link.download = `rapport_vacataires_${useCustomDates ? startDate + '_' + endDate : month}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire('Succès', 'Rapport PDF généré avec succès', 'success');
        } catch (error) {
            console.error('Erreur génération PDF:', error);
            Swal.fire('Erreur', 'Erreur lors de la génération du PDF', 'error');
        } finally {
            setGenerating(false);
        }
    };

    const handleGenerateExcel = async () => {
        try {
            setGenerating(true);

            const token = localStorage.getItem('token');
            let url = `${secureApi.baseURL}/reports/vacataire-attendance/export-excel?`;

            // Si on utilise les dates personnalisées
            if (useCustomDates && startDate && endDate) {
                url += `start_date=${startDate}&end_date=${endDate}`;
            } else {
                url += `month=${month}`;
            }

            // Ajouter le filtre par personne
            if (selectedUserId) {
                url += `&user_id=${selectedUserId}`;
            }

            url += `&token=${token}`;

            const link = document.createElement('a');
            link.href = url;
            link.download = `rapport_vacataires_${useCustomDates ? startDate + '_' + endDate : month}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire('Succès', 'Rapport Excel généré avec succès', 'success');
        } catch (error) {
            console.error('Erreur génération Excel:', error);
            Swal.fire('Erreur', 'Erreur lors de la génération du fichier Excel', 'error');
        } finally {
            setGenerating(false);
        }
    };

    const formatHours = (hours) => {
        return `${hours}h`;
    };

    return (
        <Container fluid className="py-4">
            {/* En-tête */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 style={{ color: '#9333ea' }}>
                                <People className="me-2" />
                                Rapport Enseignants Vacataires
                            </h2>
                            <p className="text-muted">
                                Suivi détaillé des présences et classes enseignées
                            </p>
                        </div>
                    </div>
                </Col>
            </Row>

            {/* Filtres */}
            <Card className="mb-4">
                <Card.Body>
                    <Row className="mb-3">
                        <Col md={12}>
                            <Form.Check
                                type="switch"
                                id="use-custom-dates"
                                label="Utiliser une plage de dates personnalisée (jour à jour)"
                                checked={useCustomDates}
                                onChange={(e) => setUseCustomDates(e.target.checked)}
                            />
                        </Col>
                    </Row>

                    {!useCustomDates ? (
                        <Row>
                            <Col md={4}>
                                <Form.Group>
                                    <Form.Label>
                                        <Calendar className="me-2" />
                                        Mois
                                    </Form.Label>
                                    <Form.Control
                                        type="month"
                                        value={month}
                                        onChange={(e) => setMonth(e.target.value)}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={4}>
                                <Form.Group>
                                    <Form.Label>
                                        <People className="me-2" />
                                        Vacataire (optionnel)
                                    </Form.Label>
                                    <Form.Select
                                        value={selectedUserId}
                                        onChange={(e) => setSelectedUserId(e.target.value)}
                                    >
                                        <option value="">Tous les vacataires</option>
                                        {vacatairesList.map((vac) => (
                                            <option key={vac.id} value={vac.id}>
                                                {vac.name} ({vac.role === 'vacataire' ? 'Vacataire' : 'Semi-Perm.'})
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col md={4} className="d-flex align-items-end">
                                <Button
                                    variant="primary"
                                    onClick={loadReport}
                                    disabled={loading}
                                    className="me-2 w-100"
                                >
                                    {loading ? (
                                        <>
                                            <Spinner animation="border" size="sm" className="me-2" />
                                            Chargement...
                                        </>
                                    ) : (
                                        <>
                                            <BarChart className="me-2" />
                                            Afficher Rapport
                                        </>
                                    )}
                                </Button>
                            </Col>
                        </Row>
                    ) : (
                        <Row>
                            <Col md={3}>
                                <Form.Group>
                                    <Form.Label>
                                        <Calendar className="me-2" />
                                        Date de début
                                    </Form.Label>
                                    <Form.Control
                                        type="date"
                                        value={startDate}
                                        onChange={(e) => setStartDate(e.target.value)}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={3}>
                                <Form.Group>
                                    <Form.Label>
                                        <Calendar className="me-2" />
                                        Date de fin
                                    </Form.Label>
                                    <Form.Control
                                        type="date"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={3}>
                                <Form.Group>
                                    <Form.Label>
                                        <People className="me-2" />
                                        Vacataire (optionnel)
                                    </Form.Label>
                                    <Form.Select
                                        value={selectedUserId}
                                        onChange={(e) => setSelectedUserId(e.target.value)}
                                    >
                                        <option value="">Tous les vacataires</option>
                                        {vacatairesList.map((vac) => (
                                            <option key={vac.id} value={vac.id}>
                                                {vac.name} ({vac.role === 'vacataire' ? 'Vacataire' : 'Semi-Perm.'})
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col md={3} className="d-flex align-items-end">
                                <Button
                                    variant="primary"
                                    onClick={loadReport}
                                    disabled={loading || !startDate || !endDate}
                                    className="me-2 w-100"
                                >
                                    {loading ? (
                                        <>
                                            <Spinner animation="border" size="sm" className="me-2" />
                                            Chargement...
                                        </>
                                    ) : (
                                        <>
                                            <BarChart className="me-2" />
                                            Afficher Rapport
                                        </>
                                    )}
                                </Button>
                            </Col>
                        </Row>
                    )}

                    {reportData && (
                        <Row className="mt-3">
                            <Col md={12} className="d-flex justify-content-end gap-2">
                                <Button
                                    variant="success"
                                    onClick={handleGenerateExcel}
                                    disabled={generating}
                                >
                                    {generating ? (
                                        <>
                                            <Spinner animation="border" size="sm" className="me-2" />
                                            Génération...
                                        </>
                                    ) : (
                                        <>
                                            <Download className="me-2" />
                                            Exporter Excel
                                        </>
                                    )}
                                </Button>
                                <Button
                                    variant="danger"
                                    onClick={handleGeneratePdf}
                                    disabled={generating}
                                >
                                    {generating ? (
                                        <>
                                            <Spinner animation="border" size="sm" className="me-2" />
                                            Génération...
                                        </>
                                    ) : (
                                        <>
                                            <FiletypePdf className="me-2" />
                                            Exporter PDF
                                        </>
                                    )}
                                </Button>
                            </Col>
                        </Row>
                    )}
                </Card.Body>
            </Card>

            {/* Statistiques globales */}
            {reportData && reportData.statistics && (
                <Row className="mb-4">
                    <Col md={3}>
                        <Card className="text-center" style={{ borderLeft: '4px solid #9333ea' }}>
                            <Card.Body>
                                <h3 style={{ color: '#9333ea' }}>{reportData.statistics.total_vacataires}</h3>
                                <small className="text-muted">Vacataires/Semi-Perm.</small>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center" style={{ borderLeft: '4px solid #059669' }}>
                            <Card.Body>
                                <h3 style={{ color: '#059669' }}>{reportData.statistics.total_days_taught}</h3>
                                <small className="text-muted">Jours Enseignés</small>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center" style={{ borderLeft: '4px solid #0284c7' }}>
                            <Card.Body>
                                <h3 style={{ color: '#0284c7' }}>{formatHours(reportData.statistics.total_hours_taught)}</h3>
                                <small className="text-muted">Heures Totales</small>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center" style={{ borderLeft: '4px solid #ea580c' }}>
                            <Card.Body>
                                <h3 style={{ color: '#ea580c' }}>{reportData.statistics.avg_attendance_rate}%</h3>
                                <small className="text-muted">Taux Présence Moyen</small>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Tableau des vacataires */}
            {reportData && reportData.vacataires && (
                <Card>
                    <Card.Header style={{ background: '#9333ea', color: 'white' }}>
                        <h5 className="mb-0">
                            Détail par Vacataire ({reportData.vacataires.length})
                        </h5>
                    </Card.Header>
                    <Card.Body>
                        <Table striped bordered hover responsive>
                            <thead>
                                <tr>
                                    <th style={{ width: '40px' }}>#</th>
                                    <th>Nom & Prénom</th>
                                    <th style={{ width: '100px' }}>Type</th>
                                    <th style={{ width: '80px' }}>Jours</th>
                                    <th style={{ width: '80px' }}>Présent</th>
                                    <th style={{ width: '80px' }}>Taux</th>
                                    <th style={{ width: '80px' }}>Heures</th>
                                    <th style={{ width: '80px' }}>H/Jour</th>
                                    <th style={{ width: '80px' }}>Classes</th>
                                    <th>Classes Enseignées</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reportData.vacataires.map((vacataire, index) => (
                                    <tr key={vacataire.id}>
                                        <td className="text-center">{index + 1}</td>
                                        <td><strong>{vacataire.name}</strong></td>
                                        <td className="text-center">
                                            <Badge bg={vacataire.role === 'vacataire' ? 'warning' : 'info'}>
                                                {vacataire.role === 'vacataire' ? 'Vacataire' : 'Semi-Perm.'}
                                            </Badge>
                                        </td>
                                        <td className="text-center">{vacataire.total_days}</td>
                                        <td className="text-center">{vacataire.present_days}</td>
                                        <td className="text-center">
                                            <Badge bg={
                                                vacataire.attendance_rate >= 95 ? 'success' :
                                                vacataire.attendance_rate >= 80 ? 'primary' :
                                                vacataire.attendance_rate >= 70 ? 'warning' : 'danger'
                                            }>
                                                {vacataire.attendance_rate}%
                                            </Badge>
                                        </td>
                                        <td className="text-center">{formatHours(vacataire.total_hours)}</td>
                                        <td className="text-center">{formatHours(vacataire.avg_hours_per_day)}</td>
                                        <td className="text-center">
                                            <Badge bg="secondary">{vacataire.total_classes}</Badge>
                                        </td>
                                        <td>
                                            {vacataire.classes_taught.length > 0 ? (
                                                <div style={{ fontSize: '0.85em' }}>
                                                    {vacataire.classes_taught.map((cls, idx) => (
                                                        <Badge
                                                            key={idx}
                                                            bg="success"
                                                            className="me-1 mb-1"
                                                            style={{ fontSize: '0.75em' }}
                                                        >
                                                            {cls.class_name} ({cls.days_count}j, {formatHours(cls.total_hours)})
                                                        </Badge>
                                                    ))}
                                                </div>
                                            ) : (
                                                <span className="text-muted">Aucune</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>

                        {reportData.vacataires.length === 0 && (
                            <Alert variant="info" className="text-center">
                                Aucun vacataire trouvé pour cette période
                            </Alert>
                        )}
                    </Card.Body>
                </Card>
            )}

            {/* Message initial */}
            {!reportData && !loading && (
                <Alert variant="info" className="text-center">
                    <Clock size={48} className="mb-3" />
                    <p>Sélectionnez un mois et cliquez sur "Afficher Rapport" pour voir les données</p>
                </Alert>
            )}
        </Container>
    );
};

export default VacataireAttendanceReport;
