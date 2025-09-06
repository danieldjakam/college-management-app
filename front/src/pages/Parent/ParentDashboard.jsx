import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
    Container, Row, Col, Card, Alert, Badge, Button, 
    Spinner, ListGroup, Modal
} from 'react-bootstrap';
import {
    Trophy, ExclamationTriangleFill, CheckCircleFill, InfoCircle,
    ClockHistory, People, BookHalf, GraphUp, CalendarEvent, Calendar, ChatDots, Bell
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import ParentSidebar from '../../components/ParentSidebar';
import './ParentDashboard.css';

const ParentDashboard = () => {
    const navigate = useNavigate();
    
    // États
    const [loading, setLoading] = useState(true);
    const [dashboardData, setDashboardData] = useState(null);
    const [error, setError] = useState(null);
    const [activeTab, setActiveTab] = useState('dashboard');
    const [parentData, setParentData] = useState(null);
    const [selectedNotification, setSelectedNotification] = useState(null);
    const [showNotificationModal, setShowNotificationModal] = useState(false);
    const [scheduleData, setScheduleData] = useState(null);

    useEffect(() => {
        // Vérifier l'authentification
        const token = localStorage.getItem('parentToken');
        const parent = localStorage.getItem('parentData');
        
        if (!token || !parent) {
            navigate('/parent/login');
            return;
        }

        setParentData(JSON.parse(parent));
        loadDashboard();
    }, [navigate]);

    const loadDashboard = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.parent.dashboard();
            
            if (response.success) {
                setDashboardData(response.data);
            }
        } catch (error) {
            console.error('Erreur chargement tableau de bord:', error);
            setError('Erreur lors du chargement des données');
        } finally {
            setLoading(false);
        }
    };

    const handleLogout = () => {
        localStorage.removeItem('parentToken');
        localStorage.removeItem('parentData');
        localStorage.removeItem('childrenCount');
        navigate('/parent/login');
    };

    const getPriorityIcon = (priority) => {
        switch (priority) {
            case 'urgent': return <ExclamationTriangleFill className="text-danger" />;
            case 'high': return <InfoCircle className="text-warning" />;
            case 'normal': return <InfoCircle className="text-info" />;
            default: return <InfoCircle className="text-secondary" />;
        }
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    // Fonction pour récupérer les emplois du temps
    const fetchSchedules = async () => {
        try {
            const response = await secureApiEndpoints.parent.getSchedules();
            if (response.data.success) {
                setScheduleData(response.data.schedules);
            }
        } catch (error) {
            console.error('Erreur lors de la récupération des emplois du temps:', error);
        }
    };

    // Charger les emplois du temps quand l'onglet est sélectionné
    useEffect(() => {
        if (activeTab === 'schedule' && !scheduleData) {
            fetchSchedules();
        }
    }, [activeTab]);

    // Fonction pour formater l'emploi du temps en tableau
    const renderScheduleTable = (scheduleByDay) => {
        if (!scheduleByDay) return null;
        
        // Créer une structure de données pour les créneaux horaires
        const timeSlots = new Set();
        Object.values(scheduleByDay).forEach(daySchedule => {
            daySchedule.forEach(course => {
                timeSlots.add(`${course.start_time}-${course.end_time}`);
            });
        });
        
        const sortedTimeSlots = Array.from(timeSlots).sort();
        
        return sortedTimeSlots.map((slot, index) => {
            const [start, end] = slot.split('-');
            return (
                <tr key={index}>
                    <td className="text-nowrap"><strong>{start} - {end}</strong></td>
                    {[1, 2, 3, 4, 5, 6].map(day => {
                        const course = scheduleByDay[day]?.find(c => 
                            c.start_time === start && c.end_time === end
                        );
                        return (
                            <td key={day} className={course ? 'table-info' : ''}>
                                {course && (
                                    <div>
                                        <strong>{course.subject}</strong>
                                        {course.teacher && <div className="small">{course.teacher}</div>}
                                        {course.room && <div className="small text-muted">Salle: {course.room}</div>}
                                    </div>
                                )}
                            </td>
                        );
                    })}
                </tr>
            );
        });
    };

    const handleNotificationClick = async (notification) => {
        setSelectedNotification(notification);
        setShowNotificationModal(true);
        
        // Marquer comme lue si elle ne l'est pas déjà
        if (!notification.is_read) {
            try {
                await secureApiEndpoints.parent.markNotificationAsRead(notification.id);
                
                // Mettre à jour l'état local
                const updatedNotifications = dashboardData.notifications.recent.map(n => 
                    n.id === notification.id ? {...n, is_read: true} : n
                );
                
                setDashboardData({
                    ...dashboardData,
                    notifications: {
                        ...dashboardData.notifications,
                        recent: updatedNotifications,
                        unread_count: Math.max(0, dashboardData.notifications.unread_count - 1)
                    }
                });
            } catch (error) {
                console.error('Erreur lors de la mise à jour de la notification:', error);
            }
        }
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center min-vh-100">
                <Spinner animation="border" variant="primary" size="lg" />
            </div>
        );
    }

    return (
        <div className="parent-dashboard d-flex">
            {/* Sidebar */}
            <ParentSidebar 
                activeTab={activeTab}
                setActiveTab={setActiveTab}
                parentData={parentData}
                dashboardData={dashboardData}
                handleLogout={handleLogout}
            />

            {/* Main Content */}
            <div className="main-content flex-grow-1" style={{ marginLeft: '280px', minHeight: '100vh' }}>
                <Container fluid className="py-4">
                {error && (
                    <Alert variant="danger" dismissible onClose={() => setError(null)}>
                        {error}
                    </Alert>
                )}

                {activeTab === 'dashboard' && dashboardData && (
                    <>
                        {/* Résumé global */}
                        <Row className="mb-4">
                            <Col md={3}>
                                <Card className="text-center h-100 border-primary">
                                    <Card.Body>
                                        <People size={40} className="text-primary mb-2" />
                                        <h3 className="text-primary">{dashboardData.summary.total_children}</h3>
                                        <p className="text-muted mb-0">Enfant(s) inscrit(s)</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={3}>
                                <Card className="text-center h-100 border-warning">
                                    <Card.Body>
                                        <ClockHistory size={40} className="text-warning mb-2" />
                                        <h3 className="text-warning">{dashboardData.summary.total_absences_week}</h3>
                                        <p className="text-muted mb-0">Absence(s) cette semaine</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={3}>
                                <Card className="text-center h-100 border-danger">
                                    <Card.Body>
                                        <Bell size={40} className="text-danger mb-2" />
                                        <h3 className="text-danger">{dashboardData.summary.urgent_notifications}</h3>
                                        <p className="text-muted mb-0">Alerte(s) urgente(s)</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={3}>
                                <Card className="text-center h-100 border-info">
                                    <Card.Body>
                                        <ChatDots size={40} className="text-info mb-2" />
                                        <h3 className="text-info">{dashboardData.unread_messages}</h3>
                                        <p className="text-muted mb-0">Message(s) non lu(s)</p>
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>

                        {/* Vue d'ensemble des enfants */}
                        <Row className="mb-4">
                            <Col>
                                <Card>
                                    <Card.Header>
                                        <h5 className="mb-0">
                                            <People className="me-2" />
                                            Mes Enfants
                                        </h5>
                                    </Card.Header>
                                    <Card.Body>
                                        <Row>
                                            {dashboardData.children_stats.map((childStat) => (
                                                <Col md={6} lg={4} key={childStat.student.id} className="mb-3">
                                                    <Card className="child-card h-100">
                                                        <Card.Body>
                                                            <div className="d-flex align-items-center mb-2">
                                                                <div className="child-avatar me-3">
                                                                    {childStat.student.photo ? (
                                                                        <img 
                                                                            src={childStat.student.photo} 
                                                                            alt={childStat.student.full_name}
                                                                            className="rounded-circle"
                                                                            width="50"
                                                                            height="50"
                                                                        />
                                                                    ) : (
                                                                        <div className="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style={{width: '50px', height: '50px'}}>
                                                                            {childStat.student.first_name?.[0]}{childStat.student.last_name?.[0]}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                                <div>
                                                                    <h6 className="mb-1">{childStat.student.full_name}</h6>
                                                                    <small className="text-muted">
                                                                        {childStat.student.student_number}
                                                                    </small>
                                                                </div>
                                                            </div>

                                                            <div className="child-stats">
                                                                <div className="d-flex justify-content-between align-items-center mb-2">
                                                                    <small className="text-muted">Classe</small>
                                                                    <Badge bg="secondary">
                                                                        {childStat.student.class_series?.[0]?.school_class?.name || 'N/A'}
                                                                    </Badge>
                                                                </div>

                                                                {childStat.average_grade && (
                                                                    <div className="d-flex justify-content-between align-items-center mb-2">
                                                                        <small className="text-muted">
                                                                            <Trophy className="me-1" />
                                                                            Moyenne
                                                                        </small>
                                                                        <Badge bg={childStat.average_grade >= 10 ? 'success' : 'warning'}>
                                                                            {childStat.average_grade}/20
                                                                        </Badge>
                                                                    </div>
                                                                )}

                                                                <div className="d-flex justify-content-between align-items-center mb-2">
                                                                    <small className="text-muted">
                                                                        <GraphUp className="me-1" />
                                                                        Assiduité
                                                                    </small>
                                                                    <Badge bg={childStat.attendance_rate >= 90 ? 'success' : childStat.attendance_rate >= 75 ? 'warning' : 'danger'}>
                                                                        {childStat.attendance_rate}%
                                                                    </Badge>
                                                                </div>

                                                                {childStat.absences_this_week > 0 && (
                                                                    <div className="d-flex justify-content-between align-items-center">
                                                                        <small className="text-danger">
                                                                            <ClockHistory className="me-1" />
                                                                            Absences semaine
                                                                        </small>
                                                                        <Badge bg="danger">
                                                                            {childStat.absences_this_week}
                                                                        </Badge>
                                                                    </div>
                                                                )}
                                                            </div>

                                                            <div className="mt-2">
                                                                <Button 
                                                                    variant="outline-primary" 
                                                                    size="sm" 
                                                                    className="w-100"
                                                                >
                                                                    <BookHalf className="me-1" />
                                                                    Détails académiques
                                                                </Button>
                                                            </div>
                                                        </Card.Body>
                                                    </Card>
                                                </Col>
                                            ))}
                                        </Row>
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>

                        <Row>
                            {/* Notifications récentes */}
                            <Col md={6}>
                                <Card className="h-100">
                                    <Card.Header className="d-flex justify-content-between align-items-center">
                                        <h6 className="mb-0">
                                            <Bell className="me-2" />
                                            Notifications Récentes
                                        </h6>
                                        {dashboardData.notifications.unread_count > 0 && (
                                            <Badge bg="danger">{dashboardData.notifications.unread_count}</Badge>
                                        )}
                                    </Card.Header>
                                    <Card.Body className="p-0">
                                        <ListGroup variant="flush">
                                            {dashboardData.notifications.recent.slice(0, 5).map((notification) => (
                                                <ListGroup.Item 
                                                    key={notification.id}
                                                    className="d-flex align-items-start"
                                                    style={{cursor: 'pointer'}}
                                                    onClick={() => handleNotificationClick(notification)}
                                                >
                                                    <div className="me-2 mt-1">
                                                        {getPriorityIcon(notification.priority)}
                                                    </div>
                                                    <div className="flex-grow-1">
                                                        <div className="d-flex justify-content-between">
                                                            <h6 className="mb-1">{notification.title}</h6>
                                                            <small className="text-muted">
                                                                {formatDate(notification.created_at)}
                                                            </small>
                                                        </div>
                                                        <p className="mb-1 text-muted small">
                                                            {notification.message}
                                                        </p>
                                                        {notification.student && (
                                                            <small className="text-primary">
                                                                Concerne: {notification.student.first_name} {notification.student.last_name}
                                                            </small>
                                                        )}
                                                    </div>
                                                </ListGroup.Item>
                                            ))}
                                        </ListGroup>
                                        {dashboardData.notifications.recent.length === 0 && (
                                            <div className="text-center py-3 text-muted">
                                                <CheckCircleFill size={32} className="mb-2" />
                                                <p>Aucune notification récente</p>
                                            </div>
                                        )}
                                    </Card.Body>
                                </Card>
                            </Col>

                            {/* Événements à venir */}
                            <Col md={6}>
                                <Card className="h-100">
                                    <Card.Header>
                                        <h6 className="mb-0">
                                            <CalendarEvent className="me-2" />
                                            Événements à Venir
                                        </h6>
                                    </Card.Header>
                                    <Card.Body className="p-0">
                                        <ListGroup variant="flush">
                                            {dashboardData.upcoming_events.slice(0, 5).map((event) => (
                                                <ListGroup.Item 
                                                    key={event.id}
                                                    className="d-flex align-items-start"
                                                >
                                                    <div className="me-2 mt-1">
                                                        <CalendarEvent className="text-primary" />
                                                    </div>
                                                    <div className="flex-grow-1">
                                                        <div className="d-flex justify-content-between">
                                                            <h6 className="mb-1">{event.title}</h6>
                                                            <Badge bg="primary">{formatDate(event.date)}</Badge>
                                                        </div>
                                                        {event.description && (
                                                            <p className="mb-0 text-muted small">
                                                                {event.description}
                                                            </p>
                                                        )}
                                                    </div>
                                                </ListGroup.Item>
                                            ))}
                                        </ListGroup>
                                        {dashboardData.upcoming_events.length === 0 && (
                                            <div className="text-center py-3 text-muted">
                                                <Calendar size={32} className="mb-2" />
                                                <p>Aucun événement à venir</p>
                                            </div>
                                        )}
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>
                    </>
                )}

                {/* Onglets Calendar et Messages - placeholders pour futur développement */}
                {activeTab === 'calendar' && (
                    <Card>
                        <Card.Body className="text-center py-5">
                            <Calendar size={64} className="text-muted mb-3" />
                            <h5>Calendrier Scolaire</h5>
                            <p className="text-muted">Fonctionnalité en cours de développement</p>
                        </Card.Body>
                    </Card>
                )}

                {activeTab === 'messages' && (
                    <Card>
                        <Card.Body className="text-center py-5">
                            <ChatDots size={64} className="text-muted mb-3" />
                            <h5>Messagerie École-Parent</h5>
                            <p className="text-muted">Fonctionnalité en cours de développement</p>
                        </Card.Body>
                    </Card>
                )}

                {activeTab === 'schedule' && dashboardData && (
                    <Row>
                        <Col lg={12}>
                            <Card>
                                <Card.Header>
                                    <h5 className="mb-0">
                                        <ClockHistory className="me-2" />
                                        Emploi du Temps
                                    </h5>
                                </Card.Header>
                                <Card.Body>
                                    {dashboardData.children_stats.length === 0 ? (
                                        <Alert variant="info">
                                            <InfoCircle className="me-2" />
                                            Aucun enfant inscrit.
                                        </Alert>
                                    ) : (
                                        <div>
                                            {scheduleData && scheduleData.length > 0 ? (
                                                scheduleData.map((childSchedule, index) => (
                                                    <div key={index} className="mb-4">
                                                        <Card className="border-primary">
                                                            <Card.Header className="bg-primary text-white">
                                                                <h6 className="mb-0">
                                                                    {childSchedule.student.name} - {childSchedule.student.class}
                                                                </h6>
                                                            </Card.Header>
                                                            <Card.Body>
                                                                <div className="table-responsive">
                                                                    <table className="table table-bordered">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Horaire</th>
                                                                                <th>Lundi</th>
                                                                                <th>Mardi</th>
                                                                                <th>Mercredi</th>
                                                                                <th>Jeudi</th>
                                                                                <th>Vendredi</th>
                                                                                <th>Samedi</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            {renderScheduleTable(childSchedule.schedule)}
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </Card.Body>
                                                        </Card>
                                                    </div>
                                                ))
                                            ) : (
                                                <Alert variant="warning">
                                                    <InfoCircle className="me-2" />
                                                    Aucun emploi du temps disponible pour le moment.
                                                </Alert>
                                            )}
                                        </div>
                                    )}
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>
                )}

                {activeTab === 'results' && dashboardData && (
                    <Row>
                        <Col lg={12}>
                            <Card>
                                <Card.Header>
                                    <h5 className="mb-0">
                                        <Trophy className="me-2" />
                                        Résultats Scolaires
                                    </h5>
                                </Card.Header>
                                <Card.Body>
                                    {dashboardData.children_stats.length === 0 ? (
                                        <Alert variant="info">
                                            <InfoCircle className="me-2" />
                                            Aucun enfant inscrit.
                                        </Alert>
                                    ) : (
                                        <Row>
                                            {dashboardData.children_stats.map((childStat) => (
                                                <Col key={childStat.student.id} lg={12} className="mb-4">
                                                    <Card className="border-primary">
                                                        <Card.Header className="bg-primary text-white">
                                                            <div className="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <h6 className="mb-0">
                                                                        {childStat.student.first_name} {childStat.student.last_name}
                                                                    </h6>
                                                                    <small>
                                                                        Matricule: {childStat.student.student_number} | 
                                                                        Classe: {childStat.student.class_series?.[0]?.school_class?.name || 'N/A'}
                                                                    </small>
                                                                </div>
                                                                <div className="text-end">
                                                                    {childStat.average_grade ? (
                                                                        <div>
                                                                            <h4 className="mb-0">{childStat.average_grade}/20</h4>
                                                                            <small>Moyenne Générale</small>
                                                                        </div>
                                                                    ) : (
                                                                        <Badge bg="warning">Pas de notes</Badge>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </Card.Header>
                                                        <Card.Body>
                                                            <Row>
                                                                <Col md={6}>
                                                                    <h6 className="text-muted mb-3">Dernières évaluations</h6>
                                                                    <ListGroup variant="flush">
                                                                        <ListGroup.Item className="d-flex justify-content-between">
                                                                            <span>Mathématiques</span>
                                                                            <Badge bg="success">15/20</Badge>
                                                                        </ListGroup.Item>
                                                                        <ListGroup.Item className="d-flex justify-content-between">
                                                                            <span>Français</span>
                                                                            <Badge bg="warning">12/20</Badge>
                                                                        </ListGroup.Item>
                                                                        <ListGroup.Item className="d-flex justify-content-between">
                                                                            <span>Sciences</span>
                                                                            <Badge bg="success">16/20</Badge>
                                                                        </ListGroup.Item>
                                                                    </ListGroup>
                                                                </Col>
                                                                <Col md={6}>
                                                                    <h6 className="text-muted mb-3">Statistiques</h6>
                                                                    <ListGroup variant="flush">
                                                                        <ListGroup.Item className="d-flex justify-content-between">
                                                                            <span><Trophy className="me-2 text-warning" />Rang dans la classe</span>
                                                                            <strong>5ème / 30</strong>
                                                                        </ListGroup.Item>
                                                                        <ListGroup.Item className="d-flex justify-content-between">
                                                                            <span><GraphUp className="me-2 text-info" />Progression</span>
                                                                            <Badge bg="success">+2.5 pts</Badge>
                                                                        </ListGroup.Item>
                                                                        <ListGroup.Item className="d-flex justify-content-between">
                                                                            <span><CheckCircleFill className="me-2 text-success" />Assiduité</span>
                                                                            <strong>{childStat.attendance_rate}%</strong>
                                                                        </ListGroup.Item>
                                                                    </ListGroup>
                                                                </Col>
                                                            </Row>
                                                            
                                                            <div className="mt-3 text-center">
                                                                <Button variant="outline-primary" size="sm">
                                                                    <BookHalf className="me-2" />
                                                                    Voir le bulletin détaillé
                                                                </Button>
                                                            </div>
                                                        </Card.Body>
                                                    </Card>
                                                </Col>
                                            ))}
                                        </Row>
                                    )}
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>
                )}

                {activeTab === 'notifications' && dashboardData && (
                    <Row>
                        <Col lg={12}>
                            <Card>
                                <Card.Header className="d-flex justify-content-between align-items-center">
                                    <h5 className="mb-0">
                                        <Bell className="me-2" />
                                        Toutes les Notifications
                                    </h5>
                                    {dashboardData.notifications.unread_count > 0 && (
                                        <Badge bg="danger" pill>
                                            {dashboardData.notifications.unread_count} non lue(s)
                                        </Badge>
                                    )}
                                </Card.Header>
                                <Card.Body className="p-0">
                                    <ListGroup variant="flush">
                                        {dashboardData.notifications.recent.length === 0 ? (
                                            <ListGroup.Item className="text-center text-muted py-5">
                                                <Bell size={48} className="mb-3" />
                                                <p>Aucune notification pour le moment</p>
                                            </ListGroup.Item>
                                        ) : (
                                            dashboardData.notifications.recent.map((notification) => (
                                                <ListGroup.Item 
                                                    key={notification.id}
                                                    className={`d-flex align-items-start ${!notification.is_read ? 'bg-light' : ''}`}
                                                    style={{cursor: 'pointer'}}
                                                    onClick={() => handleNotificationClick(notification)}
                                                >
                                                    <div className="me-3 mt-1">
                                                        {getPriorityIcon(notification.priority)}
                                                    </div>
                                                    <div className="flex-grow-1">
                                                        <div className="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6 className="mb-1">
                                                                    {notification.title}
                                                                    {!notification.is_read && (
                                                                        <Badge bg="info" className="ms-2" pill>Nouveau</Badge>
                                                                    )}
                                                                </h6>
                                                                <p className="mb-2">{notification.message}</p>
                                                                {notification.student && (
                                                                    <small className="text-primary d-block mb-1">
                                                                        <People className="me-1" />
                                                                        Concerne: {notification.student.first_name} {notification.student.last_name}
                                                                    </small>
                                                                )}
                                                                <small className="text-muted">
                                                                    <ClockHistory className="me-1" />
                                                                    {formatDate(notification.created_at)}
                                                                </small>
                                                            </div>
                                                            <div className="text-end">
                                                                {notification.priority === 'urgent' && (
                                                                    <Badge bg="danger" className="mb-2">Urgent</Badge>
                                                                )}
                                                                {notification.priority === 'high' && (
                                                                    <Badge bg="warning" className="mb-2">Important</Badge>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </ListGroup.Item>
                                            ))
                                        )}
                                    </ListGroup>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>
                )}
                </Container>

                {/* Modal pour afficher la notification complète */}
                <Modal 
                    show={showNotificationModal} 
                    onHide={() => setShowNotificationModal(false)}
                    size="lg"
                >
                    <Modal.Header closeButton className={
                        selectedNotification?.priority === 'urgent' ? 'bg-danger text-white' :
                        selectedNotification?.priority === 'high' ? 'bg-warning' :
                        'bg-primary text-white'
                    }>
                        <Modal.Title>
                            <Bell className="me-2" />
                            {selectedNotification?.title}
                        </Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        {selectedNotification && (
                            <>
                                <div className="mb-3">
                                    <Badge bg={
                                        selectedNotification.priority === 'urgent' ? 'danger' :
                                        selectedNotification.priority === 'high' ? 'warning' :
                                        selectedNotification.priority === 'normal' ? 'info' :
                                        'success'
                                    } className="me-2">
                                        Priorité: {selectedNotification.priority}
                                    </Badge>
                                    <Badge bg="secondary">
                                        Type: {selectedNotification.type}
                                    </Badge>
                                </div>
                                
                                <div className="mb-3">
                                    <h6>Message :</h6>
                                    <p className="text-justify">{selectedNotification.message}</p>
                                </div>
                                
                                {selectedNotification.student && (
                                    <Alert variant="info">
                                        <People className="me-2" />
                                        <strong>Élève concerné :</strong> {selectedNotification.student.first_name} {selectedNotification.student.last_name}
                                    </Alert>
                                )}
                                
                                <div className="text-muted">
                                    <small>
                                        <ClockHistory className="me-1" />
                                        Envoyée le : {formatDate(selectedNotification.created_at)}
                                        {selectedNotification.created_at && (
                                            <> à {new Date(selectedNotification.created_at).toLocaleTimeString('fr-FR', {
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            })}</>
                                        )}
                                    </small>
                                </div>
                                
                                {selectedNotification.is_read && selectedNotification.read_at && (
                                    <div className="text-muted mt-2">
                                        <small>
                                            <CheckCircleFill className="me-1 text-success" />
                                            Lue le : {formatDate(selectedNotification.read_at)}
                                        </small>
                                    </div>
                                )}
                            </>
                        )}
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowNotificationModal(false)}>
                            Fermer
                        </Button>
                    </Modal.Footer>
                </Modal>
            </div>
        </div>
    );
};

export default ParentDashboard;