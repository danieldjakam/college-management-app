import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { Alert, Card, Row, Col, Button, Badge, ListGroup } from 'react-bootstrap';
import { LoadingSpinner } from '../../components/UI';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { ArrowLeft, Book, People, ChevronRight, Award } from 'react-bootstrap-icons';

const SequenceSubjects = () => {
    const { sequenceId } = useParams();
    const { user } = useAuth();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [sequence, setSequence] = useState(null);
    const [subjects, setSubjects] = useState([]);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadSequenceSubjects();
    }, [sequenceId]);

    const loadSequenceSubjects = async () => {
        try {
            setLoading(true);
            setError(null);
            
            // Charger les détails de la séquence
            const sequenceResponse = await secureApiEndpoints.sequences.getById(sequenceId);
            setSequence(sequenceResponse.data);
            
            // Charger les matières de l'enseignant pour cette séquence
            const subjectsResponse = await secureApiEndpoints.seriesSubjects.getAll({
                teacher_id: user.id,
                sequence_id: sequenceId
            });
            setSubjects(subjectsResponse.data || []);
            
        } catch (error) {
            console.error('Erreur lors du chargement:', error);
            setError(error.message || 'Impossible de charger les matières');
        } finally {
            setLoading(false);
        }
    };

    const handleSubjectClick = (subject) => {
        // Navigation vers la liste des élèves de cette matière/classe
        navigate(`/teacher/sequences/${sequenceId}/subjects/${subject.id}/students`);
    };

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <LoadingSpinner text="Chargement des matières..." size="lg" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="container-fluid p-4">
                <Alert variant="danger">
                    <h6>Erreur</h6>
                    <p className="mb-0">{error}</p>
                    <Button variant="outline-danger" size="sm" className="mt-2" onClick={loadSequenceSubjects}>
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
                    <Card className="bg-primary text-white">
                        <Card.Body>
                            <div className="d-flex align-items-center justify-content-between">
                                <div className="d-flex align-items-center">
                                    <Button 
                                        variant="light" 
                                        size="sm" 
                                        className="me-3"
                                        onClick={() => navigate('/teacher/trimesters')}
                                    >
                                        <ArrowLeft />
                                    </Button>
                                    <div>
                                        <h4 className="mb-1">
                                            <Book className="me-2" />
                                            Matières - {sequence?.name}
                                        </h4>
                                        <p className="mb-0 opacity-75">
                                            Sélectionnez une matière pour saisir les notes
                                        </p>
                                    </div>
                                </div>
                                <div className="text-end">
                                    <Badge bg="light" text="primary" className="fs-6">
                                        {subjects.length} matière(s)
                                    </Badge>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Liste des matières */}
            <Row>
                <Col>
                    {subjects.length === 0 ? (
                        <Alert variant="info" className="text-center">
                            <Book size={48} className="mb-3" />
                            <h6>Aucune matière assignée</h6>
                            <p className="mb-0">
                                Vous n'avez aucune matière assignée pour cette séquence.
                                Contactez l'administration pour plus d'informations.
                            </p>
                        </Alert>
                    ) : (
                        <Card>
                            <Card.Header>
                                <h5 className="mb-0">
                                    <Book className="me-2" />
                                    Vos matières dans cette séquence
                                </h5>
                            </Card.Header>
                            <Card.Body className="p-0">
                                <ListGroup variant="flush">
                                    {subjects.map((subject) => (
                                        <ListGroup.Item 
                                            key={subject.id}
                                            action
                                            onClick={() => handleSubjectClick(subject)}
                                            className="d-flex align-items-center justify-content-between p-3"
                                        >
                                            <div className="d-flex align-items-center">
                                                <div className="me-3">
                                                    <div className="bg-primary rounded-circle d-flex align-items-center justify-content-center" style={{width: '40px', height: '40px'}}>
                                                        <Award className="text-white" size={20} />
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 className="mb-1 fw-bold">
                                                        {subject.subject?.name}
                                                    </h6>
                                                    <div className="text-muted small">
                                                        <People size={14} className="me-1" />
                                                        Classe: {subject.school_class?.name}
                                                        {subject.school_class?.students_count && (
                                                            <span className="ms-2">
                                                                ({subject.school_class.students_count} élèves)
                                                            </span>
                                                        )}
                                                    </div>
                                                    {subject.coefficient && (
                                                        <Badge bg="secondary" className="mt-1">
                                                            Coefficient: {subject.coefficient}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                            <ChevronRight className="text-muted" />
                                        </ListGroup.Item>
                                    ))}
                                </ListGroup>
                            </Card.Body>
                        </Card>
                    )}
                </Col>
            </Row>

            {/* Informations complémentaires */}
            {sequence && (
                <Alert variant="light" className="mt-4">
                    <Row>
                        <Col md={6}>
                            <h6 className="mb-2">Informations de la séquence</h6>
                            <ul className="mb-0 small">
                                <li><strong>Nom:</strong> {sequence.name}</li>
                                <li><strong>Période:</strong> {new Date(sequence.start_date).toLocaleDateString('fr-FR')} - {new Date(sequence.end_date).toLocaleDateString('fr-FR')}</li>
                            </ul>
                        </Col>
                        <Col md={6}>
                            <h6 className="mb-2">Instructions</h6>
                            <ul className="mb-0 small">
                                <li>Cliquez sur une matière pour voir les élèves</li>
                                <li>Vous pourrez ensuite saisir les notes par évaluation</li>
                            </ul>
                        </Col>
                    </Row>
                </Alert>
            )}
        </div>
    );
};

export default SequenceSubjects;