import React, { useState, useEffect } from 'react';
import {
    Container, Row, Col, Card, Alert, Spinner, Badge,
    ProgressBar, Table, ListGroup
} from 'react-bootstrap';
import {
    PersonFill, PeopleFill, BookFill, HouseFill,
    GraphUp, ClipboardCheck, ExclamationTriangle,
    InfoCircle, CalendarEvent, Award
} from 'react-bootstrap-icons';
import { secureApiEndpoints, secureApi } from '../../utils/apiMigration';
import { Line, Pie, Bar } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

// Enregistrer les composants Chart.js
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    BarElement,
    Title,
    Tooltip,
    Legend
);

const AdminDashboard = () => {
    const [dashboardData, setDashboardData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadDashboard();
    }, []);

    const loadDashboard = async () => {
        try {
            setLoading(true);
            setError(null);

            // Utiliser directement secureApi en cas de problème avec secureApiEndpoints
            const response = await secureApi.get('/admin/dashboard');

            if (response.success) {
                setDashboardData(response.data);
            } else {
                setError(response.message || 'Erreur lors du chargement du tableau de bord');
            }
        } catch (error) {
            console.error('Erreur dashboard:', error);
            setError('Impossible de charger le tableau de bord');
        } finally {
            setLoading(false);
        }
    };

    // Configuration des graphiques
    const getGenderChartData = () => {
        if (!dashboardData?.student_stats?.by_gender) return null;

        const data = dashboardData.student_stats.by_gender;
        return {
            labels: ['Masculin', 'Féminin'],
            datasets: [{
                data: [data.male || 0, data.female || 0],
                backgroundColor: ['#007bff', '#e83e8c'],
                borderWidth: 0
            }]
        };
    };

    const getSectionChartData = () => {
        if (!dashboardData?.student_stats?.by_section) return null;

        const data = dashboardData.student_stats.by_section;
        return {
            labels: Object.keys(data),
            datasets: [{
                label: 'Étudiants par section',
                data: Object.values(data),
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'
                ],
                borderWidth: 1,
                borderColor: '#fff'
            }]
        };
    };

    const getEvaluationTypesData = () => {
        if (!dashboardData?.academic_stats?.evaluation_types_distribution) return null;

        const data = dashboardData.academic_stats.evaluation_types_distribution;
        return {
            labels: Object.keys(data).map(key => key === 'composition' ? 'Compositions' : 'Travaux Pratiques'),
            datasets: [{
                data: Object.values(data),
                backgroundColor: ['#28a745', '#17a2b8'],
                borderWidth: 0
            }]
        };
    };

    if (loading) {
        return (
            <Container className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <div className="text-center">
                    <Spinner animation="border" variant="primary" />
                    <p className="mt-2">Chargement du tableau de bord...</p>
                </div>
            </Container>
        );
    }

    if (error) {
        return (
            <Container className="mt-4">
                <Alert variant="danger">
                    <ExclamationTriangle className="me-2" />
                    {error}
                </Alert>
            </Container>
        );
    }

    const { general_stats, student_stats, teacher_stats, academic_stats, alerts, school_year } = dashboardData;

    return (
        <Container fluid className="p-4">
            {/* En-tête */}
            <Row className="mb-4">
                <Col>
                    <Card className="bg-primary text-white">
                        <Card.Body>
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 className="mb-1">
                                        <GraphUp className="me-2" />
                                        Tableau de Bord Administrateur
                                    </h4>
                                    <p className="mb-0 opacity-75">
                                        Vue d'ensemble de l'établissement - {school_year?.name || 'Année scolaire non définie'}
                                    </p>
                                </div>
                                <div className="text-end">
                                    <small className="opacity-75">Dernière mise à jour</small>
                                    <div className="fw-bold">
                                        {new Date(dashboardData.generated_at).toLocaleString('fr-FR')}
                                    </div>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Alertes système */}
            {alerts && alerts.length > 0 && (
                <Row className="mb-4">
                    <Col>
                        <Card>
                            <Card.Header>
                                <h6 className="mb-0">
                                    <ExclamationTriangle className="me-2" />
                                    Alertes système
                                </h6>
                            </Card.Header>
                            <Card.Body>
                                {alerts.map((alert, index) => (
                                    <Alert
                                        key={index}
                                        variant={alert.type === 'warning' ? 'warning' : 'info'}
                                        className="mb-2"
                                    >
                                        <strong>{alert.title}:</strong> {alert.message}
                                    </Alert>
                                ))}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Statistiques générales */}
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="text-center border-primary">
                        <Card.Body>
                            <PeopleFill size={40} className="text-primary mb-2" />
                            <h3 className="mb-1">{general_stats?.total_students || 0}</h3>
                            <p className="text-muted mb-0">Étudiants</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center border-success">
                        <Card.Body>
                            <PersonFill size={40} className="text-success mb-2" />
                            <h3 className="mb-1">{general_stats?.total_teachers || 0}</h3>
                            <p className="text-muted mb-0">Enseignants</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center border-warning">
                        <Card.Body>
                            <BookFill size={40} className="text-warning mb-2" />
                            <h3 className="mb-1">{general_stats?.total_subjects || 0}</h3>
                            <p className="text-muted mb-0">Matières</p>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center border-info">
                        <Card.Body>
                            <HouseFill size={40} className="text-info mb-2" />
                            <h3 className="mb-1">{general_stats?.total_classes || 0}</h3>
                            <p className="text-muted mb-0">Classes</p>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row>
                {/* Colonne gauche */}
                <Col lg={8}>
                    {/* Période académique actuelle */}
                    {academic_stats?.current_sequence && (
                        <Card className="mb-4">
                            <Card.Header>
                                <CalendarEvent className="me-2" />
                                Période académique actuelle
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md={6}>
                                        <h6>Séquence actuelle</h6>
                                        <h5>{academic_stats.current_sequence.name}</h5>
                                        <ProgressBar
                                            now={academic_stats.current_sequence.progress}
                                            label={`${academic_stats.current_sequence.progress}%`}
                                            variant="primary"
                                        />
                                    </Col>
                                    {academic_stats.current_trimester && (
                                        <Col md={6}>
                                            <h6>Trimestre actuel</h6>
                                            <h5>{academic_stats.current_trimester.name}</h5>
                                            <ProgressBar
                                                now={academic_stats.current_trimester.progress}
                                                label={`${academic_stats.current_trimester.progress}%`}
                                                variant="success"
                                            />
                                        </Col>
                                    )}
                                </Row>
                            </Card.Body>
                        </Card>
                    )}

                    {/* Répartition des étudiants */}
                    <Row>
                        <Col md={6}>
                            <Card className="mb-4">
                                <Card.Header>
                                    <h6 className="mb-0">Répartition par genre</h6>
                                </Card.Header>
                                <Card.Body>
                                    {getGenderChartData() && (
                                        <Pie
                                            data={getGenderChartData()}
                                            options={{
                                                plugins: {
                                                    legend: {
                                                        position: 'bottom'
                                                    }
                                                },
                                                maintainAspectRatio: false
                                            }}
                                            height={200}
                                        />
                                    )}
                                </Card.Body>
                            </Card>
                        </Col>
                        <Col md={6}>
                            <Card className="mb-4">
                                <Card.Header>
                                    <h6 className="mb-0">Répartition par section</h6>
                                </Card.Header>
                                <Card.Body>
                                    {getSectionChartData() && (
                                        <Bar
                                            data={getSectionChartData()}
                                            options={{
                                                plugins: {
                                                    legend: {
                                                        display: false
                                                    }
                                                },
                                                maintainAspectRatio: false
                                            }}
                                            height={200}
                                        />
                                    )}
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>

                    {/* Top classes */}
                    {student_stats?.by_class && (
                        <Card className="mb-4">
                            <Card.Header>
                                <h6 className="mb-0">Classes avec le plus d'étudiants</h6>
                            </Card.Header>
                            <Card.Body>
                                <Table responsive size="sm">
                                    <thead>
                                        <tr>
                                            <th>Classe</th>
                                            <th>Nombre d'étudiants</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Object.entries(student_stats.by_class).map(([className, count]) => (
                                            <tr key={className}>
                                                <td>{className}</td>
                                                <td>
                                                    <Badge bg="primary">{count}</Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            </Card.Body>
                        </Card>
                    )}
                </Col>

                {/* Colonne droite */}
                <Col lg={4}>
                    {/* Statistiques académiques */}
                    <Card className="mb-4">
                        <Card.Header>
                            <Award className="me-2" />
                            Académique
                        </Card.Header>
                        <Card.Body>
                            <ListGroup variant="flush">
                                <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                    Évaluations totales
                                    <Badge bg="primary" pill>{academic_stats?.total_evaluations || 0}</Badge>
                                </ListGroup.Item>
                                <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                    Évaluations cette séquence
                                    <Badge bg="success" pill>{academic_stats?.evaluations_this_sequence || 0}</Badge>
                                </ListGroup.Item>
                                <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                    Notes saisies
                                    <Badge bg="info" pill>{academic_stats?.total_grades || 0}</Badge>
                                </ListGroup.Item>
                            </ListGroup>
                        </Card.Body>
                    </Card>

                    {/* Types d'évaluations */}
                    {getEvaluationTypesData() && (
                        <Card className="mb-4">
                            <Card.Header>
                                <h6 className="mb-0">Types d'évaluations</h6>
                            </Card.Header>
                            <Card.Body>
                                <Pie
                                    data={getEvaluationTypesData()}
                                    options={{
                                        plugins: {
                                            legend: {
                                                position: 'bottom'
                                            }
                                        },
                                        maintainAspectRatio: false
                                    }}
                                    height={150}
                                />
                            </Card.Body>
                        </Card>
                    )}

                    {/* Statistiques enseignants */}
                    <Card className="mb-4">
                        <Card.Header>
                            <PersonFill className="me-2" />
                            Enseignants
                        </Card.Header>
                        <Card.Body>
                            <ListGroup variant="flush">
                                <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                    Affectations actives
                                    <Badge bg="primary" pill>{teacher_stats?.total_assignments || 0}</Badge>
                                </ListGroup.Item>
                                <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                    Enseignants avec affectations
                                    <Badge bg="success" pill>{teacher_stats?.teachers_with_assignments || 0}</Badge>
                                </ListGroup.Item>
                                <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                    Professeurs principaux
                                    <Badge bg="info" pill>{teacher_stats?.main_teachers || 0}</Badge>
                                </ListGroup.Item>
                                {teacher_stats?.classes_without_main_teacher > 0 && (
                                    <ListGroup.Item className="d-flex justify-content-between align-items-center text-warning">
                                        Classes sans PP
                                        <Badge bg="warning" pill>{teacher_stats.classes_without_main_teacher}</Badge>
                                    </ListGroup.Item>
                                )}
                            </ListGroup>
                        </Card.Body>
                    </Card>

                    {/* Activité récente */}
                    {dashboardData.recent_activity && (
                        <Card>
                            <Card.Header>
                                <ClipboardCheck className="me-2" />
                                Activité récente (7 jours)
                            </Card.Header>
                            <Card.Body>
                                <ListGroup variant="flush">
                                    <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                        Nouveaux étudiants
                                        <Badge bg="primary" pill>{dashboardData.recent_activity.new_students}</Badge>
                                    </ListGroup.Item>
                                    <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                        Nouveaux enseignants
                                        <Badge bg="success" pill>{dashboardData.recent_activity.new_teachers}</Badge>
                                    </ListGroup.Item>
                                    <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                        Nouvelles évaluations
                                        <Badge bg="info" pill>{dashboardData.recent_activity.new_evaluations}</Badge>
                                    </ListGroup.Item>
                                    <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                        Nouvelles notes
                                        <Badge bg="warning" pill>{dashboardData.recent_activity.new_grades}</Badge>
                                    </ListGroup.Item>
                                </ListGroup>
                            </Card.Body>
                        </Card>
                    )}
                </Col>
            </Row>
        </Container>
    );
};

export default AdminDashboard;