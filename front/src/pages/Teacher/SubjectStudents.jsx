import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { Alert, Card, Row, Col, Button, Badge, Table, Dropdown } from 'react-bootstrap';
import { LoadingSpinner } from '../../components/UI';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { ArrowLeft, Book, People, PlusCircle, Eye, Pencil, Award, Calendar, FileText } from 'react-bootstrap-icons';

const SubjectStudents = () => {
    const { sequenceId, subjectId } = useParams();
    const { user } = useAuth();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [sequence, setSequence] = useState(null);
    const [subject, setSubject] = useState(null);
    const [students, setStudents] = useState([]);
    const [evaluations, setEvaluations] = useState([]);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadSubjectStudents();
    }, [sequenceId, subjectId]);

    const loadSubjectStudents = async () => {
        try {
            setLoading(true);
            setError(null);
            
            // Charger les détails de la séquence
            const sequenceResponse = await secureApiEndpoints.sequences.getById(sequenceId);
            setSequence(sequenceResponse.data);
            
            // Charger les détails de la matière
            const subjectResponse = await secureApiEndpoints.seriesSubjects.getById(subjectId);
            setSubject(subjectResponse.data);
            
            // Charger les élèves de la classe
            const studentsResponse = await secureApiEndpoints.students.getAll({
                class_id: subjectResponse.data.school_class_id
            });
            setStudents(studentsResponse.data || []);
            
            // Charger les évaluations existantes pour cette matière/séquence
            const evaluationsResponse = await secureApiEndpoints.evaluations.getAll({
                sequence_id: sequenceId,
                series_subject_id: subjectId,
                teacher_id: user.id
            });
            setEvaluations(evaluationsResponse.data || []);
            
        } catch (error) {
            console.error('Erreur lors du chargement:', error);
            setError(error.message || 'Impossible de charger les données');
        } finally {
            setLoading(false);
        }
    };

    const handleCreateEvaluation = () => {
        // Navigation vers création d'évaluation pour cette matière/séquence
        navigate(`/teacher/evaluations/create?sequence_id=${sequenceId}&series_subject_id=${subjectId}`);
    };

    const handleGradeEntry = (evaluationId) => {
        // Navigation vers saisie des notes pour cette évaluation
        navigate(`/teacher/evaluations/${evaluationId}/grades`);
    };

    const getEvaluationTypeColor = (type) => {
        const colors = {
            interrogation: 'info',
            devoir: 'warning', 
            composition: 'success',
            tp: 'secondary',
            controle: 'primary'
        };
        return colors[type] || 'secondary';
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric'
        });
    };

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <LoadingSpinner text="Chargement des élèves..." size="lg" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="container-fluid p-4">
                <Alert variant="danger">
                    <h6>Erreur</h6>
                    <p className="mb-0">{error}</p>
                    <Button variant="outline-danger" size="sm" className="mt-2" onClick={loadSubjectStudents}>
                        Réessayer
                    </Button>
                </Alert>
            </div>
        );
    }

    return (
        <div className="container-fluid p-4">
            {/* En-tête avec navigation */}
            <Row className="mb-4">
                <Col>
                    <Card className="bg-success text-white">
                        <Card.Body>
                            <div className="d-flex align-items-center justify-content-between">
                                <div className="d-flex align-items-center">
                                    <Button 
                                        variant="light" 
                                        size="sm" 
                                        className="me-3"
                                        onClick={() => navigate(`/teacher/sequences/${sequenceId}/subjects`)}
                                    >
                                        <ArrowLeft />
                                    </Button>
                                    <div>
                                        <h4 className="mb-1">
                                            <People className="me-2" />
                                            {subject?.subject?.name} - {subject?.school_class?.name}
                                        </h4>
                                        <p className="mb-0 opacity-75">
                                            Séquence: {sequence?.name} • {students.length} élèves
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <Button 
                                        variant="light" 
                                        onClick={handleCreateEvaluation}
                                    >
                                        <PlusCircle className="me-2" />
                                        Nouvelle évaluation
                                    </Button>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Évaluations existantes */}
            {evaluations.length > 0 && (
                <Row className="mb-4">
                    <Col>
                        <Card>
                            <Card.Header>
                                <h6 className="mb-0">
                                    <FileText className="me-2" />
                                    Évaluations dans cette matière ({evaluations.length})
                                </h6>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    {evaluations.map((evaluation) => (
                                        <Col md={6} lg={4} key={evaluation.id} className="mb-3">
                                            <Card className="h-100 border-2">
                                                <Card.Body className="d-flex flex-column">
                                                    <div className="mb-2">
                                                        <Badge bg={getEvaluationTypeColor(evaluation.type)} className="mb-2">
                                                            {evaluation.type}
                                                        </Badge>
                                                        <h6 className="fw-bold">{evaluation.name}</h6>
                                                    </div>
                                                    <div className="small text-muted mb-3 flex-grow-1">
                                                        <div><Calendar size={14} className="me-1" />{formatDate(evaluation.date)}</div>
                                                        <div><Award size={14} className="me-1" />Note max: {evaluation.max_score}</div>
                                                        <div>Coefficient: {evaluation.coefficient}</div>
                                                    </div>
                                                    <div className="mt-auto">
                                                        <Button 
                                                            variant="outline-success" 
                                                            size="sm" 
                                                            className="w-100"
                                                            onClick={() => handleGradeEntry(evaluation.id)}
                                                        >
                                                            <Pencil size={14} className="me-1" />
                                                            Saisir notes
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
            )}

            {/* Liste des élèves */}
            <Row>
                <Col>
                    {students.length === 0 ? (
                        <Alert variant="info" className="text-center">
                            <People size={48} className="mb-3" />
                            <h6>Aucun élève trouvé</h6>
                            <p className="mb-0">
                                Cette classe ne contient aucun élève inscrit.
                                Contactez l'administration pour plus d'informations.
                            </p>
                        </Alert>
                    ) : (
                        <Card>
                            <Card.Header>
                                <h5 className="mb-0">
                                    <People className="me-2" />
                                    Liste des élèves ({students.length})
                                </h5>
                            </Card.Header>
                            <Card.Body className="p-0">
                                <Table responsive hover className="mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Nom complet</th>
                                            <th>Matricule</th>
                                            <th>Genre</th>
                                            <th>Date de naissance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {students.map((student, index) => (
                                            <tr key={student.id}>
                                                <td className="fw-bold">{index + 1}</td>
                                                <td>
                                                    <div>
                                                        <div className="fw-bold">
                                                            {student.first_name} {student.last_name}
                                                        </div>
                                                        {student.email && (
                                                            <small className="text-muted">{student.email}</small>
                                                        )}
                                                    </div>
                                                </td>
                                                <td>
                                                    <Badge bg="light" text="dark">
                                                        {student.student_number || 'N/A'}
                                                    </Badge>
                                                </td>
                                                <td>
                                                    <Badge bg={student.gender === 'M' ? 'primary' : 'info'}>
                                                        {student.gender === 'M' ? 'Masculin' : 'Féminin'}
                                                    </Badge>
                                                </td>
                                                <td>
                                                    {student.date_of_birth ? 
                                                        formatDate(student.date_of_birth) : 'Non définie'
                                                    }
                                                </td>
                                                <td>
                                                    <Dropdown size="sm">
                                                        <Dropdown.Toggle variant="outline-secondary" size="sm">
                                                            Actions
                                                        </Dropdown.Toggle>
                                                        <Dropdown.Menu>
                                                            <Dropdown.Item>
                                                                <Eye className="me-2" />
                                                                Voir profil
                                                            </Dropdown.Item>
                                                            <Dropdown.Item>
                                                                <FileText className="me-2" />
                                                                Historique notes
                                                            </Dropdown.Item>
                                                        </Dropdown.Menu>
                                                    </Dropdown>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            </Card.Body>
                        </Card>
                    )}
                </Col>
            </Row>

            {/* Message d'aide */}
            <Alert variant="light" className="mt-4">
                <Row>
                    <Col md={8}>
                        <h6 className="mb-2">Instructions</h6>
                        <ul className="mb-0 small">
                            <li>Créez une nouvelle évaluation pour cette matière</li>
                            <li>Ou saisissez les notes pour une évaluation existante</li>
                            <li>Les notes seront automatiquement associées à cette classe</li>
                        </ul>
                    </Col>
                    <Col md={4}>
                        <div className="text-end">
                            <div><strong>Matière:</strong> {subject?.subject?.name}</div>
                            <div><strong>Classe:</strong> {subject?.school_class?.name}</div>
                            <div><strong>Séquence:</strong> {sequence?.name}</div>
                        </div>
                    </Col>
                </Row>
            </Alert>
        </div>
    );
};

export default SubjectStudents;