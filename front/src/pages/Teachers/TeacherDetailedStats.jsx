/**
 * Statistiques détaillées du personnel (enseignants, comptables, surveillants, etc.)
 * Affichage complet des heures de travail, mouvements et analyses
 */

import React, { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Table,
    Badge,
    Form,
    Button,
    Modal,
    Spinner,
    Alert,
    ProgressBar,
    ListGroup,
    Tabs,
    Tab
} from 'react-bootstrap';
import {
    PersonCheck,
    Clock,
    Calendar,
    BarChart,
    ArrowRightCircle,
    ArrowLeftCircle,
    TrendingUp,
    TrendingDown,
    Eye,
    Download,
    Filter,
    CheckCircleFill,
    XCircleFill,
    ClockHistory,
    Activity
} from 'react-bootstrap-icons';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Legend,
    ArcElement
} from 'chart.js';
import { Bar, Line, Doughnut } from 'react-chartjs-2';
import { useAuth } from '../../hooks/useAuth';
import { secureApiEndpoints } from '../../utils/apiMigration';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';

// Enregistrement des composants Chart.js
ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Legend,
    ArcElement
);

const TeacherDetailedStats = () => {
    const [staffMembers, setStaffMembers] = useState([]);
    const [selectedStaff, setSelectedStaff] = useState('');
    const [startDate, setStartDate] = useState(new Date(new Date().setDate(new Date().getDate() - 30)));
    const [endDate, setEndDate] = useState(new Date());
    const [detailedStats, setDetailedStats] = useState(null);
    const [dayMovements, setDayMovements] = useState(null);
    const [selectedDate, setSelectedDate] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [showMovementsModal, setShowMovementsModal] = useState(false);
    const [activeTab, setActiveTab] = useState('overview');

    const { user } = useAuth();

    useEffect(() => {
        loadStaffMembers();
    }, []);

    useEffect(() => {
        if (selectedStaff) {
            loadDetailedStats();
        }
    }, [selectedStaff, startDate, endDate]);

    const loadStaffMembers = async () => {
        try {
            const response = await secureApiEndpoints.staff.getStaffWithQR();
            if (response.success) {
                setStaffMembers(response.data || []);
            }
        } catch (error) {
            console.error('Erreur lors du chargement du personnel:', error);
        }
    };

    const loadDetailedStats = async () => {
        if (!selectedStaff) return;

        try {
            setIsLoading(true);
            const response = await secureApiEndpoints.staff.getStaffReport(selectedStaff, {
                start_date: startDate.toISOString().split('T')[0],
                end_date: endDate.toISOString().split('T')[0]
            });

            if (response.success) {
                setDetailedStats(response.data);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des statistiques:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const loadDayMovements = async (date) => {
        try {
            // Pour l'instant, on utilise les données des mouvements récents depuis les statistiques détaillées
            // car l'API staff n'a pas encore d'endpoint spécifique pour les mouvements du jour
            const dayData = detailedStats?.attendances?.find(a => a.attendance_date === date);
            if (dayData) {
                setDayMovements({
                    movements_count: 1,
                    timeline: [{
                        id: dayData.id,
                        time: dayData.scanned_at ? new Date(dayData.scanned_at).toLocaleTimeString('fr-FR') : '-',
                        type: dayData.event_type || 'entry',
                        type_label: dayData.event_type === 'exit' ? 'Sortie' : 'Entrée',
                        status: {
                            is_late: dayData.late_minutes > 0,
                            is_early_departure: false
                        },
                        late_minutes: dayData.late_minutes || 0,
                        supervisor: 'Système',
                        time_until_next: null
                    }]
                });
                setSelectedDate(date);
                setShowMovementsModal(true);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des mouvements:', error);
        }
    };

    // Configuration des graphiques
    const getWorkHoursChartData = () => {
        if (!detailedStats?.attendances) return null;

        const last14Days = detailedStats.attendances.slice(-14);
        
        return {
            labels: last14Days.map(day => new Date(day.attendance_date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })),
            datasets: [
                {
                    label: 'Heures travaillées',
                    data: last14Days.map(day => day.work_hours || 0),
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 2
                },
                {
                    label: 'Heures attendues',
                    data: last14Days.map(day => 8), // Valeur par défaut de 8h
                    backgroundColor: 'rgba(156, 163, 175, 0.3)',
                    borderColor: 'rgba(156, 163, 175, 1)',
                    borderWidth: 1,
                    borderDash: [5, 5]
                }
            ]
        };
    };

    const getWeekdayAnalysisData = () => {
        if (!detailedStats?.attendances) return null;

        // Calculer les statistiques par jour de la semaine
        const weekdayStats = {};
        const weekdays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        
        detailedStats.attendances.forEach(attendance => {
            const date = new Date(attendance.attendance_date);
            const dayName = date.toLocaleDateString('fr-FR', { weekday: 'long' });
            const dayCapitalized = dayName.charAt(0).toUpperCase() + dayName.slice(1);
            
            if (!weekdayStats[dayCapitalized]) {
                weekdayStats[dayCapitalized] = { present: 0, total: 0 };
            }
            
            weekdayStats[dayCapitalized].total++;
            if (attendance.is_present) {
                weekdayStats[dayCapitalized].present++;
            }
        });

        const attendanceRates = weekdays.map(day => {
            const stats = weekdayStats[day];
            return stats ? (stats.present / stats.total) * 100 : 0;
        });

        return {
            labels: weekdays,
            datasets: [
                {
                    label: 'Taux de présence (%)',
                    data: attendanceRates,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }
            ]
        };
    };

    const chartOptions = {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    };

    const getPunctualityBadge = (status) => {
        switch (status) {
            case 'punctual':
                return <Badge bg="success">Ponctuel</Badge>;
            case 'late':
                return <Badge bg="warning">En retard</Badge>;
            case 'early_departure':
                return <Badge bg="info">Départ anticipé</Badge>;
            case 'late_and_early':
                return <Badge bg="danger">Retard + Départ</Badge>;
            default:
                return <Badge bg="secondary">-</Badge>;
        }
    };

    const formatDuration = (minutes) => {
        if (!minutes) return '-';
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return hours > 0 ? `${hours}h ${mins}min` : `${mins}min`;
    };

    const selectedStaffData = staffMembers.find(s => s.id === parseInt(selectedStaff));

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 className="mb-0 d-flex align-items-center">
                                <Activity className="me-2" />
                                Statistiques Détaillées Personnel
                            </h2>
                            <p className="text-muted mb-0">
                                Analyse complète des heures et mouvements du personnel
                            </p>
                        </div>
                        
                        <Button variant="outline-primary" onClick={() => window.print()}>
                            <Download className="me-1" />
                            Imprimer
                        </Button>
                    </div>
                </Col>
            </Row>

            {/* Filtres */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Header className="d-flex align-items-center">
                            <Filter className="me-2" />
                            Sélection et période
                        </Card.Header>
                        <Card.Body>
                            <Row>
                                <Col md={4}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Membre du personnel</Form.Label>
                                        <Form.Select 
                                            value={selectedStaff} 
                                            onChange={(e) => setSelectedStaff(e.target.value)}
                                        >
                                            <option value="">Sélectionner un membre du personnel</option>
                                            {staffMembers.map(staff => (
                                                <option key={staff.id} value={staff.id}>
                                                    {staff.name} ({staff.role})
                                                </option>
                                            ))}
                                        </Form.Select>
                                    </Form.Group>
                                </Col>
                                <Col md={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Date de début</Form.Label>
                                        <DatePicker
                                            selected={startDate}
                                            onChange={setStartDate}
                                            className="form-control"
                                            dateFormat="dd/MM/yyyy"
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={3}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Date de fin</Form.Label>
                                        <DatePicker
                                            selected={endDate}
                                            onChange={setEndDate}
                                            className="form-control"
                                            dateFormat="dd/MM/yyyy"
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={2} className="d-flex align-items-end">
                                    <Button 
                                        variant="primary"
                                        onClick={loadDetailedStats}
                                        disabled={!selectedStaff || isLoading}
                                        className="mb-3"
                                    >
                                        Analyser
                                    </Button>
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Contenu principal */}
            {detailedStats && selectedStaffData ? (
                <Tabs activeKey={activeTab} onSelect={setActiveTab} className="mb-4">
                    {/* Onglet Vue d'ensemble */}
                    <Tab eventKey="overview" title="Vue d'ensemble">
                        <Row className="mb-4">
                            {/* Informations personnel */}
                            <Col md={4}>
                                <Card>
                                    <Card.Header className="d-flex align-items-center">
                                        <PersonCheck className="me-2" />
                                        Informations Personnel
                                    </Card.Header>
                                    <Card.Body>
                                        <h5>{selectedStaffData.name}</h5>
                                        <p className="text-muted mb-2">{selectedStaffData.email}</p>
                                        {selectedStaffData.contact && (
                                            <p className="text-muted mb-2">Contact: {selectedStaffData.contact}</p>
                                        )}
                                        <hr />
                                        <p className="mb-1">
                                            <strong>Rôle:</strong> {selectedStaffData.role}
                                        </p>
                                        <p className="mb-1">
                                            <strong>Type:</strong> {selectedStaffData.staff_type}
                                        </p>
                                        <p className="mb-0">
                                            <strong>Période:</strong> {startDate.toLocaleDateString()} - {endDate.toLocaleDateString()}
                                        </p>
                                    </Card.Body>
                                </Card>
                            </Col>

                            {/* Statistiques résumées */}
                            <Col md={8}>
                                <Card>
                                    <Card.Header className="d-flex align-items-center">
                                        <BarChart className="me-2" />
                                        Statistiques de la période
                                    </Card.Header>
                                    <Card.Body>
                                        <Row>
                                            <Col md={3} className="text-center">
                                                <h4 className="text-success">{detailedStats.stats?.present_days || detailedStats.attendances?.filter(a => a.is_present).length || 0}</h4>
                                                <small className="text-muted">Jours présents</small>
                                            </Col>
                                            <Col md={3} className="text-center">
                                                <h4 className="text-danger">{detailedStats.stats?.absent_days || detailedStats.attendances?.filter(a => !a.is_present).length || 0}</h4>
                                                <small className="text-muted">Jours absents</small>
                                            </Col>
                                            <Col md={3} className="text-center">
                                                <h4 className="text-info">{detailedStats.stats?.total_work_hours || detailedStats.attendances?.reduce((sum, a) => sum + (a.work_hours || 0), 0) || 0}h</h4>
                                                <small className="text-muted">Heures travaillées</small>
                                            </Col>
                                            <Col md={3} className="text-center">
                                                <h4 className="text-warning">{detailedStats.stats?.average_work_hours || (detailedStats.attendances?.length > 0 ? Math.round(detailedStats.attendances.reduce((sum, a) => sum + (a.work_hours || 0), 0) / detailedStats.attendances.length * 10) / 10 : 0)}h</h4>
                                                <small className="text-muted">Moyenne/jour</small>
                                            </Col>
                                        </Row>
                                        <hr />
                                        <Row>
                                            <Col md={6}>
                                                <div className="text-center">
                                                    {(() => {
                                                        const presentDays = detailedStats.attendances?.filter(a => a.is_present).length || 0;
                                                        const totalDays = detailedStats.attendances?.length || 1;
                                                        const rate = Math.round((presentDays / totalDays) * 100);
                                                        return (
                                                            <>
                                                                <h5 className="text-primary">{rate}%</h5>
                                                                <small className="text-muted">Taux de présence</small>
                                                                <ProgressBar 
                                                                    now={rate} 
                                                                    variant={rate >= 90 ? 'success' : rate >= 70 ? 'warning' : 'danger'}
                                                                    className="mt-2"
                                                                />
                                                            </>
                                                        );
                                                    })()}
                                                </div>
                                            </Col>
                                            <Col md={6}>
                                                <div className="text-center">
                                                    <h5 className="text-info">{detailedStats.attendances?.length || 0}</h5>
                                                    <small className="text-muted">Total enregistrements</small>
                                                    <div className="mt-2">
                                                        <small className="text-muted">
                                                            Retard moyen: {Math.round(detailedStats.attendances?.reduce((sum, a) => sum + (a.late_minutes || 0), 0) / (detailedStats.attendances?.length || 1)) || 0} min
                                                        </small>
                                                    </div>
                                                </div>
                                            </Col>
                                        </Row>
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>

                        {/* Graphiques */}
                        <Row className="mb-4">
                            <Col md={8}>
                                <Card>
                                    <Card.Header>Heures de travail quotidiennes</Card.Header>
                                    <Card.Body>
                                        {getWorkHoursChartData() && (
                                            <Bar data={getWorkHoursChartData()} options={chartOptions} />
                                        )}
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={4}>
                                <Card>
                                    <Card.Header>Analyse par jour de semaine</Card.Header>
                                    <Card.Body>
                                        {getWeekdayAnalysisData() && (
                                            <Doughnut data={getWeekdayAnalysisData()} />
                                        )}
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>
                    </Tab>

                    {/* Onglet Détails quotidiens */}
                    <Tab eventKey="daily" title="Détails quotidiens">
                        <Card>
                            <Card.Header className="d-flex align-items-center">
                                <Calendar className="me-2" />
                                Détails par jour - Toutes les entrées et sorties
                            </Card.Header>
                            <Card.Body>
                                {isLoading ? (
                                    <div className="text-center py-4">
                                        <Spinner animation="border" />
                                    </div>
                                ) : detailedStats.daily_details ? (
                                    <div>
                                        {detailedStats.daily_details.map((dayDetail, dayIndex) => (
                                            <Card key={dayIndex} className="mb-3">
                                                <Card.Header className="d-flex justify-content-between align-items-center">
                                                    <h6 className="mb-0">
                                                        {new Date(dayDetail.date).toLocaleDateString('fr-FR', { 
                                                            weekday: 'long', 
                                                            day: 'numeric', 
                                                            month: 'long' 
                                                        })}
                                                    </h6>
                                                    <div>
                                                        <Badge bg="primary" className="me-2">
                                                            {dayDetail.work_pairs.length} session{dayDetail.work_pairs.length > 1 ? 's' : ''}
                                                        </Badge>
                                                        <Badge bg="success">
                                                            Total: {dayDetail.total_formatted}
                                                        </Badge>
                                                    </div>
                                                </Card.Header>
                                                <Card.Body>
                                                    {dayDetail.work_pairs.length > 0 ? (
                                                        <div>
                                                            {dayDetail.work_pairs.map((pair, pairIndex) => (
                                                                <div key={pairIndex} className="d-flex justify-content-between align-items-center py-2 border-bottom">
                                                                    <div className="d-flex align-items-center">
                                                                        <ArrowRightCircle className="text-success me-2" />
                                                                        <span className="me-3">
                                                                            <strong>Entrée:</strong> {pair.entry_time}
                                                                        </span>
                                                                        {pair.exit_time ? (
                                                                            <>
                                                                                <ArrowLeftCircle className="text-warning me-2" />
                                                                                <span>
                                                                                    <strong>Sortie:</strong> {pair.exit_time}
                                                                                </span>
                                                                            </>
                                                                        ) : (
                                                                            <Badge bg="info">En cours...</Badge>
                                                                        )}
                                                                    </div>
                                                                    <div>
                                                                        <Badge bg="outline-primary">
                                                                            {pair.duration_formatted}
                                                                        </Badge>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <div className="text-center text-muted py-3">
                                                            <XCircleFill className="mb-2" size={24} />
                                                            <p className="mb-0">Absent ce jour</p>
                                                        </div>
                                                    )}
                                                </Card.Body>
                                            </Card>
                                        ))}
                                        
                                        {detailedStats.daily_details.length === 0 && (
                                            <Alert variant="info" className="text-center">
                                                <Calendar size={48} className="mb-3" />
                                                <h5>Aucune donnée de présence</h5>
                                                <p>Aucun enregistrement de présence trouvé pour cette période.</p>
                                            </Alert>
                                        )}
                                    </div>
                                ) : (
                                    <Alert variant="warning" className="text-center">
                                        <Clock size={48} className="mb-3" />
                                        <h5>Données non disponibles</h5>
                                        <p>Les détails quotidiens ne sont pas disponibles pour cette sélection.</p>
                                    </Alert>
                                )}
                            </Card.Body>
                        </Card>
                    </Tab>

                    {/* Onglet Mouvements récents */}
                    <Tab eventKey="movements" title="Mouvements récents">
                        <Card>
                            <Card.Header className="d-flex align-items-center">
                                <ClockHistory className="me-2" />
                                20 derniers mouvements
                            </Card.Header>
                            <Card.Body>
                                <ListGroup>
                                    {detailedStats.attendances?.slice(-20).reverse().map((attendance, index) => (
                                        <ListGroup.Item key={index} className="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div className="d-flex align-items-center">
                                                    {attendance.event_type === 'exit' ? (
                                                        <ArrowLeftCircle className="text-warning me-2" />
                                                    ) : (
                                                        <ArrowRightCircle className="text-success me-2" />
                                                    )}
                                                    <div>
                                                        <strong>
                                                            {new Date(attendance.attendance_date).toLocaleDateString('fr-FR')} 
                                                            {attendance.scanned_at && ' à ' + new Date(attendance.scanned_at).toLocaleTimeString('fr-FR')}
                                                        </strong>
                                                        <small className="text-muted d-block">
                                                            {attendance.event_type === 'exit' ? 'Sortie' : 'Entrée'} - 
                                                            Personnel: {selectedStaffData?.name}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                {(attendance.late_minutes || 0) > 0 && (
                                                    <Badge bg="warning" className="me-1">
                                                        {attendance.late_minutes} min retard
                                                    </Badge>
                                                )}
                                                {attendance.is_present && (
                                                    <Badge bg="success" className="me-1">
                                                        Présent
                                                    </Badge>
                                                )}
                                            </div>
                                        </ListGroup.Item>
                                    ))}
                                </ListGroup>
                            </Card.Body>
                        </Card>
                    </Tab>
                </Tabs>
            ) : !selectedStaff ? (
                <Alert variant="info" className="text-center">
                    <PersonCheck size={48} className="mb-3" />
                    <h5>Sélectionnez un membre du personnel</h5>
                    <p>Choisissez un membre du personnel dans la liste ci-dessus pour voir ses statistiques détaillées.</p>
                </Alert>
            ) : isLoading ? (
                <div className="text-center py-5">
                    <Spinner animation="border" size="lg" />
                    <p className="mt-3">Chargement des statistiques...</p>
                </div>
            ) : null}

            {/* Modal des mouvements du jour */}
            <Modal show={showMovementsModal} onHide={() => setShowMovementsModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        <ClockHistory className="me-2" />
                        Mouvements du {selectedDate && new Date(selectedDate).toLocaleDateString('fr-FR')}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {dayMovements && (
                        <div>
                            <p><strong>Total mouvements:</strong> {dayMovements.movements_count}</p>
                            <Table responsive>
                                <thead>
                                    <tr>
                                        <th>Heure</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                        <th>Superviseur</th>
                                        <th>Temps jusqu'au suivant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {dayMovements.timeline.map((movement, index) => (
                                        <tr key={movement.id}>
                                            <td>{movement.time}</td>
                                            <td>
                                                <div className="d-flex align-items-center">
                                                    {movement.type === 'entry' ? (
                                                        <ArrowRightCircle className="text-success me-1" />
                                                    ) : (
                                                        <ArrowLeftCircle className="text-warning me-1" />
                                                    )}
                                                    {movement.type_label}
                                                </div>
                                            </td>
                                            <td>
                                                {movement.status.is_late && (
                                                    <Badge bg="warning" className="me-1">
                                                        {movement.late_minutes} min retard
                                                    </Badge>
                                                )}
                                                {movement.status.is_early_departure && (
                                                    <Badge bg="info" className="me-1">
                                                        {movement.early_departure_minutes} min tôt
                                                    </Badge>
                                                )}
                                                {!movement.status.is_late && !movement.status.is_early_departure && (
                                                    <Badge bg="success">Ponctuel</Badge>
                                                )}
                                            </td>
                                            <td>{movement.supervisor}</td>
                                            <td>
                                                {movement.time_until_next ? 
                                                    formatDuration(movement.time_until_next) : 
                                                    '-'
                                                }
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowMovementsModal(false)}>
                        Fermer
                    </Button>
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default TeacherDetailedStats;