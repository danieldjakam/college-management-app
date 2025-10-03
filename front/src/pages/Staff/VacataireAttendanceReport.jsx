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
    const [viewMode, setViewMode] = useState('detailed'); // 'detailed' ou 'calendar'

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

            let url = viewMode === 'calendar'
                ? '/reports/vacataire-attendance-calendar?'
                : '/reports/vacataire-attendance?';

            // Si on utilise les dates personnalisées
            if (useCustomDates && startDate && endDate) {
                url += `start_date=${startDate}&end_date=${endDate}`;
            } else {
                url += `month=${month}`;
            }

            // Ajouter le filtre par personne si sélectionné
            if (selectedUserId) {
                url += `&vacataire_id=${selectedUserId}`;
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

            const token = localStorage.getItem('auth_token');

            if (!token) {
                Swal.fire('Erreur', 'Vous devez être connecté pour générer le rapport', 'error');
                return;
            }

            const endpoint = viewMode === 'calendar'
                ? '/reports/vacataire-attendance-calendar/export-pdf?'
                : '/reports/vacataire-attendance/export-pdf?';

            let url = `${secureApi.baseURL}${endpoint}`;

            // Si on utilise les dates personnalisées
            if (useCustomDates && startDate && endDate) {
                url += `start_date=${startDate}&end_date=${endDate}`;
            } else {
                url += `month=${month}`;
            }

            // Ajouter le filtre par personne
            if (selectedUserId) {
                url += `&vacataire_id=${selectedUserId}`;
            }

            url += `&token=${token}`;

            const link = document.createElement('a');
            link.href = url;
            link.download = `rapport_vacataires_${viewMode}_${useCustomDates ? startDate + '_' + endDate : month}.pdf`;
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

            const token = localStorage.getItem('auth_token');

            if (!token) {
                Swal.fire('Erreur', 'Vous devez être connecté pour générer le rapport', 'error');
                return;
            }

            const endpoint = viewMode === 'calendar'
                ? '/reports/vacataire-attendance-calendar/export-excel?'
                : '/reports/vacataire-attendance/export-excel?';

            let url = `${secureApi.baseURL}${endpoint}`;

            // Si on utilise les dates personnalisées
            if (useCustomDates && startDate && endDate) {
                url += `start_date=${startDate}&end_date=${endDate}`;
            } else {
                url += `month=${month}`;
            }

            // Ajouter le filtre par personne
            if (selectedUserId) {
                url += `&vacataire_id=${selectedUserId}`;
            }

            url += `&token=${token}`;

            const link = document.createElement('a');
            link.href = url;
            link.download = `rapport_vacataires_${viewMode}_${useCustomDates ? startDate + '_' + endDate : month}.xlsx`;
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
                        <Col md={6}>
                            <Form.Check
                                type="switch"
                                id="use-custom-dates"
                                label="Utiliser une plage de dates personnalisée (jour à jour)"
                                checked={useCustomDates}
                                onChange={(e) => setUseCustomDates(e.target.checked)}
                            />
                        </Col>
                        <Col md={6}>
                            <Form.Group>
                                <Form.Label>Mode d'affichage</Form.Label>
                                <Form.Select
                                    value={viewMode}
                                    onChange={(e) => {
                                        setViewMode(e.target.value);
                                        setReportData(null); // Reset data when changing view
                                    }}
                                >
                                    <option value="detailed">Vue détaillée (par jour avec classes)</option>
                                    <option value="calendar">Vue calendrier (In/Out par jour)</option>
                                </Form.Select>
                                <Form.Text className="text-muted">
                                    {viewMode === 'calendar' && 'Format: Un employé par ligne, un jour par colonne'}
                                </Form.Text>
                            </Form.Group>
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

            {/* Tableau des vacataires - Mode détaillé */}
            {viewMode === 'detailed' && reportData && reportData.vacataires && (
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

            {/* Tableau calendrier - Mode calendrier */}
            {viewMode === 'calendar' && reportData && reportData.vacataires && (
                <Card>
                    <Card.Header style={{ background: '#9333ea', color: 'white' }}>
                        <h5 className="mb-0">
                            Calendrier Mensuel - {reportData.monthName} {reportData.year} ({reportData.vacataires.length} vacataires)
                        </h5>
                    </Card.Header>
                    <Card.Body style={{
                        overflowX: 'auto',
                        overflowY: 'visible',
                        WebkitOverflowScrolling: 'touch',
                        scrollbarWidth: 'thin',
                        maxWidth: '100%'
                    }}>
                        <style>{`
                            .table-wrapper {
                                overflow-x: auto !important;
                                overflow-y: visible;
                                padding-bottom: 15px;
                            }
                            .table-wrapper::-webkit-scrollbar {
                                height: 14px;
                                width: 14px;
                            }
                            .table-wrapper::-webkit-scrollbar-track {
                                background: #e0e0e0;
                                border-radius: 8px;
                            }
                            .table-wrapper::-webkit-scrollbar-thumb {
                                background: #666;
                                border-radius: 8px;
                                border: 2px solid #e0e0e0;
                            }
                            .table-wrapper::-webkit-scrollbar-thumb:hover {
                                background: #333;
                            }
                            .calendar-compact th {
                                font-size: 0.75rem !important;
                                padding: 6px 4px !important;
                                white-space: nowrap;
                            }
                            .calendar-compact td {
                                font-size: 0.7rem !important;
                                padding: 6px 4px !important;
                            }
                            .calendar-compact .time-in,
                            .calendar-compact .time-out {
                                font-size: 0.65rem !important;
                                line-height: 1.3;
                            }
                        `}</style>
                        <div className="table-wrapper" style={{
                            overflowX: 'auto',
                            paddingBottom: '20px',
                            display: 'block',
                            width: '100%'
                        }}>
                        <Table bordered hover size="sm" className="calendar-compact" style={{
                            fontSize: '0.65rem',
                            minWidth: '2000px',
                            marginBottom: '0'
                        }}>
                            <thead style={{ position: 'sticky', top: 0, background: '#fff', zIndex: 10 }}>
                                <tr>
                                    <th style={{ minWidth: '120px', maxWidth: '120px', position: 'sticky', left: 0, background: '#007bff', color: 'white', zIndex: 20, fontSize: '0.75rem', padding: '6px' }}>
                                        Employé
                                    </th>
                                    {reportData.daysHeader && reportData.daysHeader.map((dayInfo) => (
                                        <th key={dayInfo.day} className="text-center" style={{ minWidth: '60px', maxWidth: '60px', width: '60px', background: '#007bff', color: 'white', padding: '4px' }}>
                                            <div style={{ fontSize: '0.65rem', lineHeight: '1.2' }}>{dayInfo.day_name}</div>
                                            <div style={{ fontSize: '0.75rem', fontWeight: 'bold' }}>{String(dayInfo.day).padStart(2, '0')}</div>
                                        </th>
                                    ))}
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '45px', maxWidth: '45px', padding: '4px', fontSize: '0.7rem' }}>%</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '65px', maxWidth: '65px', padding: '4px', fontSize: '0.7rem' }}>Total H</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>W</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>P</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>A</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>L</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>HD</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reportData.vacataires.map((vacataire, index) => (
                                    <tr key={vacataire.id || index}>
                                        <td style={{
                                            position: 'sticky',
                                            left: 0,
                                            background: '#f8f9fa',
                                            fontWeight: 'bold',
                                            zIndex: 5,
                                            fontSize: '0.7rem',
                                            padding: '6px',
                                            minWidth: '120px',
                                            maxWidth: '120px'
                                        }}>
                                            {vacataire.name}
                                        </td>
                                        {vacataire.days && vacataire.days.map((dayData, dayIndex) => {
                                            const isWeekend = dayData && dayData.is_weekend;
                                            return (
                                                <td key={dayIndex} className="text-center" style={{
                                                    background: isWeekend ? '#ffe5e5' : 'white',
                                                    padding: '4px 2px',
                                                    fontSize: '0.65rem',
                                                    lineHeight: '1.3',
                                                    minWidth: '60px',
                                                    maxWidth: '60px',
                                                    verticalAlign: 'top'
                                                }}>
                                                    {dayData && dayData.pairs && dayData.pairs.length > 0 ? (
                                                        <>
                                                            {dayData.pairs.map((pair, pairIndex) => (
                                                                <div key={pairIndex} style={{
                                                                    marginBottom: pairIndex < dayData.pairs.length - 1 ? '8px' : '0',
                                                                    padding: '2px',
                                                                    border: '1px solid #ddd',
                                                                    borderRadius: '3px',
                                                                    backgroundColor: '#f8f9fa'
                                                                }}>
                                                                    <div className="time-in" style={{ color: '#28a745', fontWeight: 'bold', fontSize: '0.65rem' }}>
                                                                        In: {pair.in}
                                                                    </div>
                                                                    <div className="time-out" style={{ color: '#dc3545', fontWeight: 'bold', fontSize: '0.65rem' }}>
                                                                        Out: {pair.out || '--:--'}
                                                                    </div>
                                                                    {pair.late_minutes > 0 && (
                                                                        <div>
                                                                            <Badge bg="warning" style={{ fontSize: '0.55rem', padding: '1px 3px' }}>
                                                                                {pair.late_minutes}min
                                                                            </Badge>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            ))}
                                                        </>
                                                    ) : (
                                                        <span style={{ color: '#ccc', fontSize: '0.7rem' }}>-</span>
                                                    )}
                                                </td>
                                            );
                                        })}
                                        <td className="text-center" style={{ fontWeight: 'bold', padding: '4px', fontSize: '0.7rem' }}>
                                            <Badge bg={
                                                vacataire.attendance_rate >= 80 ? 'success' :
                                                vacataire.attendance_rate >= 50 ? 'warning' : 'danger'
                                            } style={{ fontSize: '0.65rem' }}>
                                                {vacataire.attendance_rate}%
                                            </Badge>
                                        </td>
                                        <td className="text-center" style={{ fontWeight: 'bold', color: '#007bff', padding: '4px', fontSize: '0.7rem' }}>
                                            {vacataire.total_hours}
                                        </td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{vacataire.working_days}</td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{vacataire.present_days}</td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{vacataire.absent_days}</td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{vacataire.late_days}</td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{vacataire.half_days}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                        </div>

                        {reportData.vacataires.length === 0 && (
                            <Alert variant="info" className="text-center">
                                Aucun vacataire trouvé pour cette période
                            </Alert>
                        )}

                        <div className="mt-3" style={{ fontSize: '0.8rem', color: '#666' }}>
                            <strong>Légende:</strong> % = Taux présence | Total H = Total heures | W = Working days | P = Present | A = Absent | L = Late | HD = Half Day
                        </div>
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
