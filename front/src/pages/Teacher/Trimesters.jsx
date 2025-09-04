import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { Alert, Card, Row, Col, ProgressBar, Button, Badge } from 'react-bootstrap';
import { LoadingSpinner } from '../../components/UI';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { Calendar, Clock, Book, PlusCircle, BarChart } from 'react-bootstrap-icons';

const Trimesters = () => {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [trimesters, setTrimesters] = useState([]);
    const [teacherInfo, setTeacherInfo] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadTeacherTrimesters();
    }, []);

    const loadTeacherTrimesters = async () => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await secureApiEndpoints.trimesters.getTeacherTrimesters();
            setTrimesters(response.data || []);
            setTeacherInfo(response.teacher_info);
        } catch (error) {
            console.error('Erreur lors du chargement des trimestres:', error);
            setError(error.message || 'Impossible de charger les trimestres');
        } finally {
            setLoading(false);
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'Non définie';
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    };

    const getSequenceStatus = (sequence) => {
        const now = new Date();
        const startDate = new Date(sequence.start_date);
        const endDate = new Date(sequence.end_date);

        if (sequence.is_current) return { text: 'En cours', variant: 'success', canEdit: true };
        if (now < startDate) return { text: 'À venir', variant: 'secondary', canEdit: false };
        if (now > endDate) return { text: 'Terminée', variant: 'warning', canEdit: false };
        return { text: 'Programmée', variant: 'info', canEdit: false };
    };

    const handleGradeEntry = (sequenceId, sequenceName) => {
        // Navigation vers la saisie de notes par séquence
        // TODO: Créer une page pour sélectionner l'évaluation dans la séquence
        navigate(`/teacher/sequences/${sequenceId}/evaluations`);
    };

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <LoadingSpinner text="Chargement des trimestres..." size="lg" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="container-fluid p-4">
                <Alert variant="danger">
                    <h6>Erreur</h6>
                    <p className="mb-0">{error}</p>
                    <Button variant="outline-danger" size="sm" className="mt-2" onClick={loadTeacherTrimesters}>
                        Réessayer
                    </Button>
                </Alert>
            </div>
        );
    }

    return (
        <div className="container-fluid p-4">
            {/* En-tête avec info professeur */}
            <Row className="mb-4">
                <Col>
                    <Card className="bg-primary text-white">
                        <Card.Body>
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 className="mb-1">
                                        <Book className="me-2" />
                                        Gestion des Trimestres & Séquences
                                    </h4>
                                    <p className="mb-0 opacity-75">
                                        Suivi des évaluations par trimestre - {teacherInfo?.current_year}
                                    </p>
                                </div>
                                <div className="text-end">
                                    <small className="opacity-75">Professeur</small>
                                    <div className="fw-bold">{teacherInfo?.name}</div>
                                    <small className="opacity-75">
                                        {teacherInfo?.classes_count} classe(s) assignée(s)
                                    </small>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Grille des trimestres avec séquences intégrées */}
            <Row>
                {trimesters.map((trimester) => {
                    const isCurrentTrimester = trimester.is_current;
                    const progressPercentage = trimester.getProgressPercentage || 0;
                    
                    return (
                        <Col xl={4} lg={6} key={trimester.id} className="mb-4">
                            <Card className={isCurrentTrimester ? 'border-success shadow' : 'border-light'}>
                                <Card.Header className={isCurrentTrimester ? 'bg-success text-white' : 'bg-light'}>
                                    <div className="d-flex justify-content-between align-items-center">
                                        <h5 className="mb-0">
                                            {trimester.name}
                                            {isCurrentTrimester && (
                                                <Badge bg="light" text="success" className="ms-2">Actuel</Badge>
                                            )}
                                        </h5>
                                        <small className={isCurrentTrimester ? 'opacity-75' : 'text-muted'}>
                                            <Calendar className="me-1" />
                                            {formatDate(trimester.start_date)} - {formatDate(trimester.end_date)}
                                        </small>
                                    </div>
                                </Card.Header>
                                
                                <Card.Body>
                                    {/* Progression du trimestre */}
                                    {isCurrentTrimester && (
                                        <div className="mb-3">
                                            <div className="d-flex justify-content-between align-items-center mb-2">
                                                <small className="text-muted">Progression du trimestre</small>
                                                <small className="fw-bold">{progressPercentage}%</small>
                                            </div>
                                            <ProgressBar now={progressPercentage} variant="success" />
                                        </div>
                                    )}

                                    {/* Séquences du trimestre */}
                                    <div className="mb-3">
                                        <h6 className="small text-muted mb-2">
                                            <Clock className="me-1" />
                                            Séquences ({trimester.sequences?.length || 0})
                                        </h6>
                                        
                                        {trimester.sequences?.map((sequence) => {
                                            const status = getSequenceStatus(sequence);
                                            
                                            return (
                                                <div key={sequence.id} className="border rounded p-2 mb-2 bg-light">
                                                    <div className="d-flex justify-content-between align-items-center mb-1">
                                                        <span className="fw-bold small">{sequence.name}</span>
                                                        <Badge bg={status.variant}>{status.text}</Badge>
                                                    </div>
                                                    
                                                    <div className="d-flex justify-content-between align-items-center">
                                                        <small className="text-muted">
                                                            {sequence.teacher_evaluations_count || 0} évaluation(s)
                                                        </small>
                                                        
                                                        {status.canEdit && sequence.can_add_evaluations && (
                                                            <Button 
                                                                size="sm" 
                                                                variant="success"
                                                                onClick={() => handleGradeEntry(sequence.id, sequence.name)}
                                                            >
                                                                <PlusCircle size={14} className="me-1" />
                                                                Saisir notes
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                        
                                        {(!trimester.sequences || trimester.sequences.length === 0) && (
                                            <small className="text-muted fst-italic">
                                                Aucune séquence configurée
                                            </small>
                                        )}
                                    </div>

                                    {/* Actions du trimestre */}
                                    <div className="text-end">
                                        {isCurrentTrimester ? (
                                            <Button size="sm" variant="outline-primary">
                                                <BarChart size={14} className="me-1" />
                                                Voir statistiques
                                            </Button>
                                        ) : (
                                            <small className="text-muted">
                                                {trimester.is_active ? 'Trimestre programmé' : 'Trimestre inactif'}
                                            </small>
                                        )}
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>
                    );
                })}
            </Row>

            {/* Message informatif */}
            <Alert variant="info" className="mt-4">
                <h6>
                    <Book className="me-2" />
                    Système d'évaluation par trimestres
                </h6>
                <Row>
                    <Col md={8}>
                        <ul className="mb-0 small">
                            <li>Saisissez vos notes dans les séquences en cours</li>
                            <li>Consultez les progressions et statistiques par trimestre</li>
                        </ul>
                    </Col>
                    <Col md={4}>
                        {teacherInfo && (
                            <div className="text-end">
                                <div><strong>Classes assignées :</strong> {teacherInfo.classes_count}</div>
                                <div><strong>Année :</strong> {teacherInfo.current_year}</div>
                            </div>
                        )}
                    </Col>
                </Row>
            </Alert>
        </div>
    );
};

export default Trimesters;