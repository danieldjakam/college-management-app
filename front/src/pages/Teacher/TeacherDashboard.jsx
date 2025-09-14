import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Table, Badge, Button, Spinner, Alert } from 'react-bootstrap';
import { PersonFill, JournalBookmarkFill, HouseFill, Eye, Calendar } from 'react-bootstrap-icons';
import { useAuth } from '../../hooks/useAuth';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { useNavigate } from 'react-router-dom';

const TeacherDashboard = () => {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [hasTeacherProfile, setHasTeacherProfile] = useState(true);
    const [assignments, setAssignments] = useState([]);
    const [mainTeacherClasses, setMainTeacherClasses] = useState([]);
    const [currentYear, setCurrentYear] = useState(null);
    const [stats, setStats] = useState({
        totalClasses: 0,
        totalSubjects: 0,
        totalStudents: 0,
        isMainTeacher: false
    });

    useEffect(() => {
        // Charger les données si l'utilisateur est un enseignant
        if (user && user.role === 'teacher') {
            loadTeacherData();
        }
    }, [user]);

    const loadTeacherData = async () => {
        try {
            setLoading(true);
            
            // DEBUG: Voir le contenu complet de l'objet user
            console.log('DEBUG - Objet user complet:', user);
            console.log('DEBUG - user.teacher_id:', user.teacher_id);
            console.log('DEBUG - user.teacher:', user.teacher);
            
            // Récupérer l'ID de l'enseignant depuis l'objet user
            const teacherId = user.teacher_id;
            
            if (!teacherId) {
                console.error('Aucun teacher_id trouvé pour cet utilisateur');
                console.log('DEBUG - user disponibles:', Object.keys(user));
                setHasTeacherProfile(false);
                setLoading(false);
                return;
            }
            
            setHasTeacherProfile(true);
            console.log('Chargement des données pour l\'enseignant ID:', teacherId);
            
            // Récupérer les affectations de l'enseignant
            const response = await secureApiEndpoints.teacherAssignments.getByTeacher(teacherId);
            
            if (response.success) {
                setAssignments(response.data.assignments || []);
                setMainTeacherClasses(response.data.main_teacher_classes || []);
                setCurrentYear(response.data.school_year);
                
                // Calculer les statistiques
                calculateStats(response.data.assignments || [], response.data.main_teacher_classes || []);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des données:', error);
        } finally {
            setLoading(false);
        }
    };

    const calculateStats = (assignments, mainClasses) => {
        const uniqueClasses = new Set();
        const uniqueSubjects = new Set();
        
        assignments.forEach(assignment => {
            if (assignment.series_subject?.school_class) {
                uniqueClasses.add(assignment.series_subject.school_class.id);
            }
            if (assignment.series_subject?.subject) {
                uniqueSubjects.add(assignment.series_subject.subject.id);
            }
        });

        setStats({
            totalClasses: uniqueClasses.size,
            totalSubjects: uniqueSubjects.size,
            totalStudents: 0, // À calculer via API si nécessaire
            isMainTeacher: mainClasses.length > 0
        });
    };

    const handleViewStudents = (assignment) => {
        const classId = assignment.series_subject?.school_class?.id;
        const subjectId = assignment.series_subject?.subject?.id;
        
        if (classId) {
            navigate(`/teacher/class/${classId}/students`, {
                state: { 
                    className: assignment.series_subject.school_class.name,
                    subjectName: assignment.series_subject.subject?.name,
                    subjectId: subjectId
                }
            });
        }
    };

    if (loading) {
        return (
            <div className="text-center p-5">
                <Spinner animation="border" variant="primary" />
                <p className="mt-2">Chargement de votre espace enseignant...</p>
            </div>
        );
    }

    if (!loading && !hasTeacherProfile) {
        return (
            <Alert variant="warning" className="m-4">
                <h5>Accès limité</h5>
                <p>Votre compte utilisateur n'est pas associé à un profil enseignant. Contactez l'administration.</p>
                <hr />
                <button 
                    className="btn btn-primary btn-sm"
                    onClick={() => {
                        // Forcer la déconnexion et reconnexion
                        localStorage.clear();
                        window.location.href = '/login';
                    }}
                >
                    Se reconnecter
                </button>
            </Alert>
        );
    }

    return (
        <div className="container-fluid p-4">
            {/* En-tête enseignant */}
            <Row className="mb-4">
                <Col>
                    <Card className="bg-primary text-white">
                        <Card.Body>
                            <div className="d-flex align-items-center">
                                <PersonFill size={40} className="me-3" />
                                <div>
                                    <h4 className="mb-1">Bonjour, {user.name}</h4>
                                    <p className="mb-0">
                                        Enseignant - Année scolaire {currentYear?.name || 'Actuelle'}
                                    </p>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Statistiques */}
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="text-center h-100">
                        <Card.Body>
                            <JournalBookmarkFill size={30} className="text-info mb-2" />
                            <h5 className="mb-1">{stats.totalSubjects}</h5>
                            <small className="text-muted">Matière{stats.totalSubjects > 1 ? 's' : ''}</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center h-100">
                        <Card.Body>
                            <HouseFill size={30} className="text-success mb-2" />
                            <h5 className="mb-1">{stats.totalClasses}</h5>
                            <small className="text-muted">Classe{stats.totalClasses > 1 ? 's' : ''}</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center h-100">
                        <Card.Body>
                            <PersonFill size={30} className="text-warning mb-2" />
                            <h5 className="mb-1">{mainTeacherClasses.length}</h5>
                            <small className="text-muted">Prof. principal</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center h-100">
                        <Card.Body>
                            <Calendar size={30} className="text-primary mb-2" />
                            <h5 className="mb-1">{currentYear?.name || 'N/A'}</h5>
                            <small className="text-muted">Année en cours</small>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Actions rapides */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Header className="bg-primary text-white">
                            <h6 className="mb-0">
                                <JournalBookmarkFill className="me-2" />
                                Actions Rapides - Évaluations
                            </h6>
                        </Card.Header>
                        <Card.Body>
                            <div className="d-flex gap-3 flex-wrap">
                                <Button 
                                    variant="success"
                                    onClick={() => navigate('/teacher/evaluations')}
                                >
                                    <Eye className="me-2" />
                                    Mes Évaluations
                                </Button>
                                <Button 
                                    variant="primary"
                                    onClick={() => navigate('/teacher/evaluations/create')}
                                >
                                    <Calendar className="me-2" />
                                    Créer Évaluation
                                </Button>
                                {/* <Button 
                                    variant="info"
                                    onClick={() => navigate('/sequences')}
                                >
                                    <JournalBookmarkFill className="me-2" />
                                    Voir Séquences
                                </Button> */}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row>
                {/* Classes où je suis professeur principal */}
                {mainTeacherClasses.length > 0 && (
                    <Col md={6} className="mb-4">
                        <Card>
                            <Card.Header className="bg-success text-white">
                                <h6 className="mb-0">
                                    <HouseFill className="me-2" />
                                    Classes - Professeur Principal
                                </h6>
                            </Card.Header>
                            <Card.Body>
                                {mainTeacherClasses.map((mainClass, index) => (
                                    <div key={index} className="mb-2">
                                        <Badge bg="success" className="me-2">
                                            {mainClass.school_class?.name}
                                        </Badge>
                                        <small className="text-muted">
                                            {mainClass.school_class?.level?.name}
                                        </small>
                                    </div>
                                ))}
                            </Card.Body>
                        </Card>
                    </Col>
                )}

                {/* Mes affectations par matière */}
                <Col md={mainTeacherClasses.length > 0 ? 6 : 12}>
                    <Card>
                        <Card.Header className="bg-info text-white">
                            <h6 className="mb-0">
                                <JournalBookmarkFill className="me-2" />
                                Mes Affectations - Matières
                            </h6>
                        </Card.Header>
                        <Card.Body>
                            {assignments.length === 0 ? (
                                <Alert variant="info">
                                    Aucune affectation trouvée pour cette année scolaire.
                                </Alert>
                            ) : (
                                <Table hover responsive>
                                    <thead>
                                        <tr>
                                            <th>Matière</th>
                                            <th>Classe</th>
                                            <th>Niveau</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {assignments.map((assignment, index) => (
                                            <tr key={index}>
                                                <td>
                                                    <Badge bg="primary">
                                                        {assignment.series_subject?.subject?.name || 'N/A'}
                                                    </Badge>
                                                </td>
                                                <td>
                                                    {assignment.series_subject?.school_class?.name || 'N/A'}
                                                </td>
                                                <td>
                                                    <small className="text-muted">
                                                        {assignment.series_subject?.school_class?.level?.name || 'N/A'}
                                                    </small>
                                                </td>
                                                <td>
                                                    <Button
                                                        size="sm"
                                                        variant="outline-primary"
                                                        onClick={() => handleViewStudents(assignment)}
                                                    >
                                                        <Eye size={14} className="me-1" />
                                                        Voir élèves
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </div>
    );
};

export default TeacherDashboard;