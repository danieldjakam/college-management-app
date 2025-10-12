import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { 
    Container, Row, Col, Card, Form, Button, Alert, 
    Spinner, Badge, Table, Modal, InputGroup,
    ProgressBar, Tooltip, OverlayTrigger 
} from 'react-bootstrap';
import { 
    Save, ArrowLeft, Calculator, Eye, People, 
    CheckCircle, XCircle, ExclamationTriangle,
    ClipboardData, BarChart, Award, PersonX
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';

const GradeEntry = () => {
    const { evaluationId } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();

    // États principaux
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    // Données
    const [evaluation, setEvaluation] = useState(null);
    const [grades, setGrades] = useState([]);
    const [statistics, setStatistics] = useState(null);

    // États de l'interface
    const [showStats, setShowStats] = useState(false);
    const [unsavedChanges, setUnsavedChanges] = useState(false);
    const [bulkAction, setBulkAction] = useState('');

    useEffect(() => {
        if (evaluationId) {
            loadGradeData();
        }
    }, [evaluationId]);

    const loadGradeData = async () => {
        try {
            setLoading(true);
            setError(null);

            // Charger les notes et l'évaluation
            const gradeData = await secureApiEndpoints.grades.getByEvaluation(evaluationId);
            
            setEvaluation(gradeData.data.evaluation);
            setGrades(gradeData.data.grades);
            setStatistics(gradeData.data.statistics);

        } catch (error) {
            console.error('Erreur lors du chargement:', error);
            setError('Impossible de charger les données de l\'évaluation.');
        } finally {
            setLoading(false);
        }
    };

    const handleGradeChange = (studentId, field, value) => {
        setGrades(prevGrades => 
            prevGrades.map(gradeData => 
                gradeData.student_id === studentId
                    ? {
                        ...gradeData,
                        grade: {
                            ...gradeData.grade,
                            [field]: value,
                            // Si on marque absent, effacer la note
                            ...(field === 'is_absent' && value ? { score: null } : {}),
                            // Si on saisit une note, démarquer absent
                            ...(field === 'score' && value !== null ? { is_absent: false } : {})
                        }
                    }
                    : gradeData
            )
        );
        setUnsavedChanges(true);
    };

    const saveGrade = async (studentId) => {
        try {
            const gradeData = grades.find(g => g.student_id === studentId);
            if (!gradeData) return;

            const gradeToSave = {
                student_id: studentId,
                evaluation_id: evaluationId,
                score: gradeData.grade?.score || null,
                is_absent: gradeData.grade?.is_absent || false,
                is_excused: gradeData.grade?.is_excused || false,
                comment: gradeData.grade?.comment || null
            };

            await secureApiEndpoints.grades.save(gradeToSave);
            
            // Recharger les données pour avoir les calculs à jour
            await loadGradeData();
            setUnsavedChanges(false);
            setSuccess('Note sauvegardée avec succès');

            // Effacer le message après 3 secondes
            setTimeout(() => setSuccess(null), 3000);

        } catch (error) {
            console.error('Erreur sauvegarde:', error);
            setError(error.message || 'Erreur lors de la sauvegarde');
        }
    };

    const saveBulkGrades = async () => {
        try {
            setSaving(true);
            setError(null);

            // Préparer toutes les notes modifiées
            const gradesToSave = grades
                .filter(gradeData => gradeData.grade && (
                    gradeData.grade.score !== null || 
                    gradeData.grade.is_absent || 
                    gradeData.grade.comment
                ))
                .map(gradeData => ({
                    student_id: gradeData.student_id,
                    score: gradeData.grade.score,
                    is_absent: gradeData.grade.is_absent || false,
                    is_excused: gradeData.grade.is_excused || false,
                    comment: gradeData.grade.comment || null
                }));

            if (gradesToSave.length === 0) {
                setError('Aucune note à sauvegarder');
                return;
            }

            await secureApiEndpoints.grades.saveBulk({
                evaluation_id: evaluationId,
                grades: gradesToSave
            });

            // Recharger les données
            await loadGradeData();
            setUnsavedChanges(false);
            setSuccess(`${gradesToSave.length} notes sauvegardées avec succès`);

            setTimeout(() => setSuccess(null), 3000);

        } catch (error) {
            console.error('Erreur sauvegarde lot:', error);
            setError(error.message || 'Erreur lors de la sauvegarde');
        } finally {
            setSaving(false);
        }
    };

    const handleBulkAction = (action) => {
        if (action === 'mark_all_absent') {
            setGrades(prevGrades => 
                prevGrades.map(gradeData => ({
                    ...gradeData,
                    grade: {
                        ...gradeData.grade,
                        is_absent: true,
                        score: null
                    }
                }))
            );
            setUnsavedChanges(true);
        }
        setBulkAction('');
    };

    const calculateScoreOn20 = (score, maxScore) => {
        if (!score || !maxScore) return null;
        return Math.round((score / maxScore) * 20 * 100) / 100;
    };

    const getMention = (scoreOn20) => {
        if (scoreOn20 === null) return null;
        if (scoreOn20 >= 16) return { text: 'Très Bien', color: 'success' };
        if (scoreOn20 >= 14) return { text: 'Bien', color: 'info' };
        if (scoreOn20 >= 12) return { text: 'Assez Bien', color: 'warning' };
        if (scoreOn20 >= 10) return { text: 'Passable', color: 'secondary' };
        return { text: 'Insuffisant', color: 'danger' };
    };

    const getCompletionRate = () => {
        if (!grades.length) return 0;
        const gradedCount = grades.filter(g => 
            g.grade && (g.grade.score !== null || g.grade.is_absent)
        ).length;
        return Math.round((gradedCount / grades.length) * 100);
    };

    if (loading) {
        return (
            <Container className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <div className="text-center">
                    <Spinner animation="border" variant="primary" />
                    <p className="mt-2">Chargement des données...</p>
                </div>
            </Container>
        );
    }

    if (error && !evaluation) {
        return (
            <Container className="mt-4">
                <Alert variant="danger">
                    <ExclamationTriangle className="me-2" />
                    {error}
                </Alert>
                <Button variant="secondary" onClick={() => navigate('/teacher/evaluations')}>
                    <ArrowLeft className="me-2" />
                    Retour aux évaluations
                </Button>
            </Container>
        );
    }

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
                                        <ClipboardData className="me-2" />
                                        Saisie des Notes
                                    </h4>
                                    <p className="mb-0 opacity-75">
                                        {evaluation?.name} - {evaluation?.series_subject?.subject?.name}
                                    </p>
                                    {/* Plus de restriction - toujours modifiable */}
                                </div>
                                <div className="text-end">
                                    <div className="d-flex gap-2">
                                        <Button 
                                            variant="light" 
                                            size="sm"
                                            onClick={() => navigate('/teacher/evaluations')}
                                        >
                                            <ArrowLeft className="me-1" />
                                            Retour
                                        </Button>
                                        <Button 
                                            variant="success" 
                                            size="sm"
                                            onClick={() => setShowStats(true)}
                                        >
                                            <BarChart className="me-1" />
                                            Statistiques
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Messages */}
            {error && (
                <Row className="mb-3">
                    <Col>
                        <Alert variant="danger" dismissible onClose={() => setError(null)}>
                            <XCircle className="me-2" />
                            {error}
                        </Alert>
                    </Col>
                </Row>
            )}

            {success && (
                <Row className="mb-3">
                    <Col>
                        <Alert variant="success" dismissible onClose={() => setSuccess(null)}>
                            <CheckCircle className="me-2" />
                            {success}
                        </Alert>
                    </Col>
                </Row>
            )}

            {/* Informations évaluation */}
            <Row className="mb-4">
                <Col md={8}>
                    <Card>
                        <Card.Body>
                            <Row>
                                <Col md={6}>
                                    <h6 className="text-muted">Détails de l'évaluation</h6>
                                    <p><strong>Type :</strong> {evaluation?.type}</p>
                                    <p><strong>Date :</strong> {new Date(evaluation?.date).toLocaleDateString('fr-FR')}</p>
                                    <p><strong>Classe :</strong> {evaluation?.series_subject?.school_class?.name}</p>
                                </Col>
                                <Col md={6}>
                                    <h6 className="text-muted">Barème</h6>
                                    <p><strong>Note max :</strong> /{evaluation?.max_score}</p>
                                    <p><strong>Coefficient :</strong> ×{evaluation?.coefficient}</p>
                                    <p><strong>Séquence :</strong> {evaluation?.sequence?.name}</p>
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card>
                        <Card.Body>
                            <h6 className="text-muted">Progression</h6>
                            <div className="mb-2">
                                <small>Saisie complétée</small>
                                <ProgressBar 
                                    now={getCompletionRate()} 
                                    label={`${getCompletionRate()}%`}
                                />
                            </div>
                            <div className="d-flex justify-content-between text-small">
                                <span>
                                    <People className="me-1" />
                                    {grades.length} élèves
                                </span>
                                <span>
                                    <CheckCircle className="me-1 text-success" />
                                    {grades.filter(g => g.grade?.score !== null).length} notés
                                </span>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Actions en lot */}
            <Row className="mb-3">
                <Col>
                    <Card>
                        <Card.Body>
                            <div className="d-flex justify-content-between align-items-center">
                                <div className="d-flex gap-3 align-items-center">
                                    <Form.Select 
                                        size="sm" 
                                        style={{width: 'auto'}}
                                        value={bulkAction}
                                        onChange={(e) => setBulkAction(e.target.value)}
                                    >
                                        <option value="">Actions en lot...</option>
                                        <option value="mark_all_absent">Marquer tous absents</option>
                                    </Form.Select>
                                    {bulkAction && (
                                        <Button 
                                            size="sm" 
                                            variant="outline-primary"
                                            onClick={() => handleBulkAction(bulkAction)}
                                        >
                                            Appliquer
                                        </Button>
                                    )}
                                </div>
                                <div>
                                    <Button
                                        variant="success"
                                        size="sm"
                                        onClick={saveBulkGrades}
                                        disabled={saving || !unsavedChanges}
                                    >
                                        {saving ? (
                                            <>
                                                <Spinner size="sm" className="me-2" />
                                                Sauvegarde...
                                            </>
                                        ) : (
                                            <>
                                                <Save className="me-2" />
                                                Sauvegarder tout
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Tableau de saisie */}
            <Row>
                <Col>
                    <Card>
                        <Card.Header>
                            <h5 className="mb-0">
                                <People className="me-2" />
                                Liste des élèves ({grades.length})
                            </h5>
                        </Card.Header>
                        <Card.Body className="p-0">
                            {grades.length === 0 ? (
                                <div className="text-center p-5">
                                    <People size={48} className="text-muted mb-3" />
                                    <p className="text-muted">Aucun élève trouvé pour cette évaluation</p>
                                </div>
                            ) : (
                                <div className="table-responsive">
                                    <Table hover>
                                        <thead className="table-dark">
                                            <tr>
                                                <th style={{width: '200px'}}>Élève</th>
                                                <th style={{width: '120px'}}>Note /{evaluation?.max_score}</th>
                                                <th style={{width: '100px'}}>Note /20</th>
                                                <th style={{width: '120px'}}>Mention</th>
                                                <th style={{width: '80px'}}>Absent</th>
                                                <th style={{width: '80px'}}>Excusé</th>
                                                <th>Commentaire</th>
                                                <th style={{width: '100px'}}>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {grades.map((gradeData) => {
                                                const student = gradeData.student;
                                                const grade = gradeData.grade || {};
                                                const scoreOn20 = calculateScoreOn20(grade.score, evaluation?.max_score);
                                                const mention = getMention(scoreOn20);
                                                
                                                return (
                                                    <tr key={student.id}>
                                                        <td>
                                                            <div>
                                                                <strong>{student.nom} {student.prenom}</strong>
                                                                <small className="d-block text-muted">
                                                                    {student.matricule}
                                                                </small>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <Form.Control
                                                                type="number"
                                                                size="sm"
                                                                step="0.25"
                                                                min="0"
                                                                max={evaluation?.max_score}
                                                                value={grade.score || ''}
                                                                disabled={grade.is_absent}
                                                                onChange={(e) => handleGradeChange(
                                                                    student.id,
                                                                    'score',
                                                                    e.target.value ? parseFloat(e.target.value) : null
                                                                )}
                                                                className={grade.score !== null ? 'border-success' : ''}
                                                            />
                                                        </td>
                                                        <td className="text-center">
                                                            {scoreOn20 !== null ? (
                                                                <Badge 
                                                                    bg={scoreOn20 >= 10 ? 'success' : 'danger'}
                                                                >
                                                                    {scoreOn20}
                                                                </Badge>
                                                            ) : (
                                                                <span className="text-muted">-</span>
                                                            )}
                                                        </td>
                                                        <td>
                                                            {mention ? (
                                                                <Badge bg={mention.color}>
                                                                    {mention.text}
                                                                </Badge>
                                                            ) : (
                                                                <span className="text-muted">-</span>
                                                            )}
                                                        </td>
                                                        <td className="text-center">
                                                            <Form.Check
                                                                type="checkbox"
                                                                checked={grade.is_absent || false}
                                                                onChange={(e) => handleGradeChange(
                                                                    student.id,
                                                                    'is_absent',
                                                                    e.target.checked
                                                                )}
                                                            />
                                                        </td>
                                                        <td className="text-center">
                                                            <Form.Check
                                                                type="checkbox"
                                                                checked={grade.is_excused || false}
                                                                disabled={!grade.is_absent}
                                                                onChange={(e) => handleGradeChange(
                                                                    student.id,
                                                                    'is_excused',
                                                                    e.target.checked
                                                                )}
                                                            />
                                                        </td>
                                                        <td>
                                                            <Form.Control
                                                                type="text"
                                                                size="sm"
                                                                placeholder="Commentaire..."
                                                                value={grade.comment || ''}
                                                                onChange={(e) => handleGradeChange(
                                                                    student.id,
                                                                    'comment',
                                                                    e.target.value
                                                                )}
                                                            />
                                                        </td>
                                                        <td>
                                                            <Button
                                                                size="sm"
                                                                variant="outline-success"
                                                                onClick={() => saveGrade(student.id)}
                                                            >
                                                                <Save size={14} />
                                                            </Button>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </Table>
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Modal Statistiques */}
            <Modal show={showStats} onHide={() => setShowStats(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        <BarChart className="me-2" />
                        Statistiques de l'évaluation
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {statistics && (
                        <Row>
                            <Col md={6}>
                                <h6>Vue d'ensemble</h6>
                                <p><strong>Total élèves :</strong> {statistics.total_students}</p>
                                <p><strong>Notes saisies :</strong> {statistics.graded_count}</p>
                                <p><strong>Présents :</strong> {statistics.present_count}</p>
                                <p><strong>Absents :</strong> {statistics.absent_count}</p>
                            </Col>
                            <Col md={6}>
                                <h6>Progression</h6>
                                <ProgressBar 
                                    now={getCompletionRate()} 
                                    label={`${getCompletionRate()}% complété`}
                                    className="mb-3"
                                />
                                <small className="text-muted">
                                    {grades.filter(g => g.grade?.score !== null).length} notes sur {grades.length} élèves
                                </small>
                            </Col>
                        </Row>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowStats(false)}>
                        Fermer
                    </Button>
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default GradeEntry;