import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Alert, Card, Row, Col, ProgressBar, Button, Badge } from 'react-bootstrap';
import { LoadingSpinner } from '../../components/UI';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { Calendar, Clock, Book, PlusCircle, BarChart, InfoCircle } from 'react-bootstrap-icons';

const Trimesters = () => {
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
            
            // Charger les trimestres ET toutes les séquences (y compris compositions)
            const [trimestersResponse, sequencesResponse] = await Promise.all([
                secureApiEndpoints.trimesters.getTeacherTrimesters(),
                secureApiEndpoints.sequences.getAll()
            ]);
            
            console.log('Trimestres response:', trimestersResponse);
            console.log('Sequences response:', sequencesResponse);
            
            // Organiser les séquences par trimestre
            const allSequences = sequencesResponse.data || [];
            const sequencesByTrimester = {};
            
            allSequences.forEach(sequence => {
                console.log('Processing sequence:', sequence);
                if (sequence.trimester_id) {
                    if (!sequencesByTrimester[sequence.trimester_id]) {
                        sequencesByTrimester[sequence.trimester_id] = [];
                    }
                    sequencesByTrimester[sequence.trimester_id].push(sequence);
                    
                    if (sequence.is_composition) {
                        console.log('Found composition sequence:', sequence);
                    }
                }
            });
            
            console.log('Sequences by trimester:', sequencesByTrimester);
            
            // Filtrer pour afficher seulement le trimestre actif et les suivants à venir
            const filteredTrimesters = (trimestersResponse.data || []).filter(trimester => {
                return trimester.is_current || (trimester.is_active && !trimester.is_current);
            }).map(trimester => ({
                ...trimester,
                sequences: sequencesByTrimester[trimester.id] || []
            }));
            
            console.log('Filtered trimesters with sequences:', filteredTrimesters);
            
            setTrimesters(filteredTrimesters);
            setTeacherInfo(trimestersResponse.teacher_info);
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
        // PRIORITÉ 1: Si c'est une composition et qu'elle est verrouillée
        if (sequence.is_composition && sequence.is_locked) {
            return { text: 'Verrouillée', variant: 'danger', canEdit: false };
        }
        
        // PRIORITÉ 2: Si c'est une composition et qu'elle est active/disponible
        if (sequence.is_composition && sequence.is_active && !sequence.is_locked) {
            return { text: 'Disponible', variant: 'primary', canEdit: true };
        }
        
        // PRIORITÉ 3: Si c'est terminé
        if (sequence.is_completed) {
            return { text: 'Terminée', variant: 'warning', canEdit: false };
        }
        
        // PRIORITÉ 4: Si c'est en cours
        if (sequence.is_current) {
            return { text: 'En cours', variant: 'success', canEdit: true };
        }
        
        // PRIORITÉ 5: Si c'est actif mais pas current 
        if (sequence.is_active && !sequence.is_locked) {
            return { text: 'Disponible', variant: 'primary', canEdit: true };
        }
        
        // PRIORITÉ 6: Si c'est une composition inactive (pas encore déverrouillée)
        if (sequence.is_composition) {
            return { text: 'En attente', variant: 'secondary', canEdit: false };
        }
        
        // Vérifications par dates (fallback pour séquences normales seulement)
        const now = new Date();
        const startDate = new Date(sequence.start_date);
        const endDate = new Date(sequence.end_date);

        if (now < startDate) return { text: 'À venir', variant: 'secondary', canEdit: false };
        if (now > endDate) return { text: 'Expirée', variant: 'dark', canEdit: false };
        
        return { text: 'Programmée', variant: 'info', canEdit: false };
    };

    const handleGradeEntry = (sequenceId) => {
        // Navigation vers les matières de la séquence
        navigate(`/teacher/sequences/${sequenceId}/subjects`);
    };

    const handleShowDSDetails = (trimesterId) => {
        // Naviguer vers la page dédiée aux détails DS
        navigate(`/trimesters/${trimesterId}/ds-details`);
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
            {trimesters.length === 0 ? (
                <Alert variant="info" className="text-center">
                    <h6>Aucun trimestre disponible</h6>
                    <p className="mb-0">
                        Il n'y a actuellement aucun trimestre en cours ou à venir. 
                        Contactez l'administration pour plus d'informations.
                    </p>
                </Alert>
            ) : (
                <Row>
                    {trimesters.map((trimester) => {
                    const isCurrentTrimester = trimester.is_current;
                    const now = new Date();
                    const startDate = new Date(trimester.start_date);
                    const isUpcoming = startDate > now;
                    const progressPercentage = trimester.getProgressPercentage || 0;
                    
                    return (
                        <Col xl={4} lg={6} key={trimester.id} className="mb-4">
                            <Card className={isCurrentTrimester ? 'border-success shadow' : isUpcoming ? 'border-info shadow-sm' : 'border-light'}>
                                <Card.Header className={isCurrentTrimester ? 'bg-success text-white' : isUpcoming ? 'bg-info text-white' : 'bg-light'}>
                                    <div className="d-flex justify-content-between align-items-center">
                                        <h5 className="mb-0">
                                            {trimester.name}
                                            {isCurrentTrimester && (
                                                <Badge bg="light" text="success" className="ms-2">En cours</Badge>
                                            )}
                                            {isUpcoming && (
                                                <Badge bg="light" text="info" className="ms-2">À venir</Badge>
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
                                            Évaluations - Trimestre {trimester.number}
                                        </h6>
                                        
                                        {trimester.sequences && trimester.sequences.length > 0 ? (
                                            <>
                                                {/* Affichage du DS calculé si les séquences normales sont terminées */}
                                                {(() => {
                                                    const normalSequences = trimester.sequences.filter(seq => !seq.is_composition);
                                                    const allNormalCompleted = normalSequences.length > 0 && normalSequences.every(seq => seq.is_completed);
                                                    const compositions = trimester.sequences.filter(seq => seq.is_composition);
                                                    
                                                    // Vérifier s'il y a des évaluations saisies
                                                    const hasEvaluations = normalSequences.some(seq => (seq.teacher_evaluations_count || 0) > 0);
                                                    
                                                    // LOGIQUE MÉTIER : Si toutes les séquences normales sont terminées → DS calculable
                                                    const dsCalculable = allNormalCompleted;

                                                    // Debug logs
                                                    console.log('Trimester', trimester.number, 'sequences:', trimester.sequences);
                                                    console.log('Normal sequences:', normalSequences);
                                                    console.log('All normal completed:', allNormalCompleted);
                                                    console.log('Compositions:', compositions);
                                                    console.log('Has evaluations:', hasEvaluations);
                                                    
                                                    return (
                                                        <>
                                                            {/* Séquences normales */}
                                                            {normalSequences.map((sequence) => {
                                                                const status = getSequenceStatus(sequence);
                                                                return (
                                                                    <div key={sequence.id} className="border rounded p-2 mb-2 bg-light">
                                                                        <div className="d-flex justify-content-between align-items-center mb-1">
                                                                            <span className="fw-bold small">
                                                                                {sequence.name}
                                                                            </span>
                                                                            <Badge bg={status.variant}>{status.text}</Badge>
                                                                        </div>
                                                                        
                                                                        <div className="d-flex justify-content-between align-items-center">
                                                                            <small className="text-muted">
                                                                                {sequence.teacher_evaluations_count || 0} évaluation(s)
                                                                            </small>
                                                                            
                                                                            {status.canEdit && (
                                                                                <Button 
                                                                                    size="sm" 
                                                                                    variant="success"
                                                                                    onClick={() => handleGradeEntry(sequence.id)}
                                                                                >
                                                                                    <PlusCircle size={14} className="me-1" />
                                                                                    Saisir notes
                                                                                </Button>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                );
                                                            })}
                                                            
                                                            {/* Affichage du DS */}
                                                            {allNormalCompleted && normalSequences.length > 0 && (
                                                                <div className={`border rounded p-2 mb-2 ${dsCalculable ? 'bg-success-subtle border-success' : 'bg-warning-subtle border-warning'}`}>
                                                                    <div className="d-flex justify-content-between align-items-center">
                                                                        <span className={`fw-bold small ${dsCalculable ? 'text-success' : 'text-warning'}`}>
                                                                            📊 DS {trimester.number} (Moyennes des séquences)
                                                                        </span>
                                                                        <div className="d-flex gap-1 align-items-center">
                                                                            <Badge bg={dsCalculable ? "success" : "warning"}>
                                                                                {dsCalculable ? 'Calculé' : 'En attente'}
                                                                            </Badge>
                                                                            {dsCalculable && (
                                                                                <Button 
                                                                                    size="sm" 
                                                                                    variant="outline-success"
                                                                                    onClick={() => handleShowDSDetails(trimester.id)}
                                                                                    disabled={loading}
                                                                                >
                                                                                    <InfoCircle size={12} className="me-1" />
                                                                                    Détails
                                                                                </Button>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                    <small className={`${dsCalculable ? 'text-success' : 'text-warning'}`}>
                                                                        {dsCalculable 
                                                                            ? `✅ DS calculé à partir des ${normalSequences.length} séquence(s) terminée(s)${hasEvaluations ? '' : ' (notes admin)'}`
                                                                            : `⚠️ En attente de la clôture des ${normalSequences.length} séquence(s)`
                                                                        }
                                                                    </small>
                                                                </div>
                                                            )}
                                                            
                                                            {/* Compositions */}
                                                            {compositions.map((composition) => {
                                                                const status = getSequenceStatus(composition);
                                                                return (
                                                                    <div key={composition.id} className="border rounded p-2 mb-2 bg-warning-subtle border-warning">
                                                                        <div className="d-flex justify-content-between align-items-center mb-1">
                                                                            <span className="fw-bold small">
                                                                                🏆 {composition.name}
                                                                            </span>
                                                                            <Badge bg={status.variant}>{status.text}</Badge>
                                                                        </div>
                                                                        
                                                                        <div className="d-flex justify-content-between align-items-center">
                                                                            <small className="text-muted">
                                                                                Composition
                                                                                {composition.is_locked && (
                                                                                    <span className="text-danger ms-1">🔒</span>
                                                                                )}
                                                                            </small>
                                                                            
                                                                            {status.canEdit && (
                                                                                <Button 
                                                                                    size="sm" 
                                                                                    variant="warning"
                                                                                    onClick={() => handleGradeEntry(composition.id)}
                                                                                >
                                                                                    <PlusCircle size={14} className="me-1" />
                                                                                    Composer
                                                                                </Button>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                );
                                                            })}
                                                        </>
                                                    );
                                                })()}
                                            </>
                                        ) : (
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
            )}

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