import React, { useState, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { useNavigate } from 'react-router-dom';
import { 
    Container, Row, Col, Card, Form, Button, Alert, 
    Spinner, Badge, InputGroup, FloatingLabel 
} from 'react-bootstrap';
import { 
    PlusCircle, Calendar, Book, Award, FileText, 
    Clock, People, CheckCircle, XCircle 
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';

const EvaluationCreate = () => {
    const { user } = useAuth();
    const navigate = useNavigate();

    // États du formulaire
    const [formData, setFormData] = useState({
        name: '',
        type: '',
        series_subject_id: '',
        sequence_id: '', // Ajout de sequence_id dans le formulaire
        max_score: '20',
        coefficient: '1',
        description: ''
    });

    const [selectedSeriesSubject, setSelectedSeriesSubject] = useState(null);
    const [createForAllSequences, setCreateForAllSequences] = useState(false);

    // États de l'interface
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);
    const [successMessage, setSuccessMessage] = useState('');
    const [validationErrors, setValidationErrors] = useState({});

    // Données de référence
    const [sequences, setSequences] = useState([]);
    const [currentSequence, setCurrentSequence] = useState(null);
    const [seriesSubjects, setSeriesSubjects] = useState([]);
    const [evaluationTypes, setEvaluationTypes] = useState([]);

    // Types d'évaluations simplifiés
    const evaluationTypesConfig = {
        composition: {
            label: 'Composition',
            description: '2-4h, note sur 20',
            maxScore: [20],
            coefficientRange: [2, 5],
            color: 'success',
            icon: Award
        },
        tp: {
            label: 'Travaux Pratiques',
            description: 'Évaluation pratique',
            maxScore: [20],
            coefficientRange: [1, 3],
            color: 'secondary',
            icon: People
        }
    };

    useEffect(() => {
        loadInitialData();
    }, []);

    const loadInitialData = async () => {
        try {
            setLoading(true);
            setError(null);

            // Charger toutes les séquences (système ouvert - toutes les séquences sont accessibles)
            const sequencesData = await secureApiEndpoints.sequences.getAll();
            const allSequences = sequencesData.data || [];
            setSequences(allSequences);

            // Essayer de charger la séquence courante, sinon utiliser la première disponible
            try {
                const sequenceData = await secureApiEndpoints.sequences.getCurrent();
                setCurrentSequence(sequenceData.data);
                // Pré-sélectionner la séquence courante si elle existe
                if (sequenceData.data?.id) {
                    setFormData(prev => ({ ...prev, sequence_id: sequenceData.data.id.toString() }));
                }
            } catch (seqError) {
                console.warn('Pas de séquence courante:', seqError);
                // Pré-sélectionner la première séquence disponible
                if (allSequences.length > 0) {
                    setFormData(prev => ({ ...prev, sequence_id: allSequences[0].id.toString() }));
                }
            }

            // DEBUG COMPLET pour jean.dupont
            console.log('=== DEBUG MATIÈRES ENSEIGNANT ===');
            console.log('User complet:', user);
            console.log('User ID:', user.id);
            console.log('User name:', user.name);
            console.log('User email:', user.email);

            // Essayer d'abord avec l'endpoint teacherAssignments
            const teacherId = user.teacher_id || user.id;
            console.log('Teacher ID utilisé:', teacherId);
            try {
                const teacherAssignments = await secureApiEndpoints.teacherAssignments.getByTeacher(teacherId);
                console.log('teacherAssignments response:', teacherAssignments);
                console.log('teacherAssignments data:', teacherAssignments.data);
                
                if (teacherAssignments.success && teacherAssignments.data && teacherAssignments.data.assignments && teacherAssignments.data.assignments.length > 0) {
                    // Convertir les assignments en format series_subject (même structure que le dashboard)
                    const convertedSubjects = teacherAssignments.data.assignments.map(assignment => {
                        console.log('Assignment:', assignment);
                        return {
                            id: assignment.series_subject?.id || assignment.id,
                            subject: assignment.series_subject?.subject,
                            school_class: assignment.series_subject?.school_class,
                            series: assignment.series_subject?.series,
                            coefficient: assignment.series_subject?.coefficient, // Récupérer le coefficient de SeriesSubject
                            teacher_id: assignment.teacher_id
                        };
                    });
                    console.log('Matières converties depuis teacherAssignments:', convertedSubjects);
                    setSeriesSubjects(convertedSubjects);
                    return;
                }
            } catch (assignmentError) {
                console.log('Erreur teacherAssignments:', assignmentError);
            }

            // Fallback vers seriesSubjects
            console.log('Fallback vers seriesSubjects...');
            const teacherSubjects = await secureApiEndpoints.seriesSubjects.getAll();
            console.log('Toutes les seriesSubjects:', teacherSubjects.data);
            
            // Filtrer par teacher_id
            const allSubjects = teacherSubjects.data || [];
            console.log('Nombre total de matières:', allSubjects.length);
            
            const filteredSubjects = allSubjects.filter(ss => {
                console.log('Vérification matière:', ss);
                console.log('ss.teacher_id:', ss.teacher_id, 'vs user.id:', user.id);
                console.log('ss.teacher:', ss.teacher);
                console.log('ss.teachers:', ss.teachers);
                
                return ss.teacher_id === user.id || 
                       ss.teacher?.id === user.id ||
                       (Array.isArray(ss.teachers) && ss.teachers.some(t => t.id === user.id));
            });
            
            console.log('Matières filtrées pour cet enseignant:', filteredSubjects);
            console.log('Nombre de matières filtrées:', filteredSubjects.length);
            setSeriesSubjects(filteredSubjects);

            // Charger les types d'évaluations
            const typesData = await secureApiEndpoints.evaluations.getTypes();
            setEvaluationTypes(typesData.data || {});

        } catch (error) {
            console.error('Erreur lors du chargement des données:', error);
            setError('Impossible de charger les données nécessaires. Veuillez réessayer.');
        } finally {
            setLoading(false);
        }
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));

        // Nettoyer les erreurs de validation pour ce champ
        if (validationErrors[name]) {
            setValidationErrors(prev => ({
                ...prev,
                [name]: null
            }));
        }

        // Logique spéciale pour la sélection de matière
        if (name === 'series_subject_id' && value) {
            const selected = seriesSubjects.find(ss => ss.id.toString() === value);
            setSelectedSeriesSubject(selected);
            
            console.log('Matière sélectionnée:', selected);
            console.log('Coefficient trouvé:', selected?.coefficient);

            // Préremplir le coefficient depuis la configuration SeriesSubject
            if (selected && selected.coefficient) {
                setFormData(prev => ({
                    ...prev,
                    coefficient: selected.coefficient.toString()
                }));
            } else {
                // Valeur par défaut si pas de coefficient configuré
                setFormData(prev => ({
                    ...prev,
                    coefficient: '1'
                }));
            }
            
            // Réinitialiser le type et le nom quand on change de matière
            setFormData(prev => ({
                ...prev,
                type: '',
                name: ''
            }));
        }
        
        // Générer automatiquement le nom quand on sélectionne le type
        if (name === 'type' && value && selectedSeriesSubject && formData.sequence_id) {
            const selectedSequence = sequences.find(s => s.id.toString() === formData.sequence_id);
            const subjectName = selectedSeriesSubject.subject?.name || '';
            const typeLabel = evaluationTypesConfig[value]?.label || '';
            const sequenceName = selectedSequence?.name || '';

            const generatedName = `${sequenceName} [${typeLabel} de ${subjectName}]`;
            setFormData(prev => ({
                ...prev,
                name: generatedName
            }));
        }

        // Régénérer le nom quand on change de séquence
        if (name === 'sequence_id' && value && selectedSeriesSubject && formData.type) {
            const selectedSequence = sequences.find(s => s.id.toString() === value);
            const subjectName = selectedSeriesSubject.subject?.name || '';
            const typeLabel = evaluationTypesConfig[formData.type]?.label || '';
            const sequenceName = selectedSequence?.name || '';

            const generatedName = `${sequenceName} [${typeLabel} de ${subjectName}]`;
            setFormData(prev => ({
                ...prev,
                name: generatedName
            }));
        }

        // Ajustements automatiques selon le type d'évaluation
        if (name === 'type' && value && evaluationTypesConfig[value]) {
            const typeConfig = evaluationTypesConfig[value];
            setFormData(prev => ({
                ...prev,
                max_score: typeConfig.maxScore[0].toString()
            }));
        }
    };

    const validateForm = () => {
        const errors = {};

        // Validation nom
        if (!formData.name.trim()) {
            errors.name = 'Le nom de l\'évaluation est requis';
        } else if (formData.name.length < 3) {
            errors.name = 'Le nom doit contenir au moins 3 caractères';
        }

        // Validation type
        if (!formData.type) {
            errors.type = 'Le type d\'évaluation est requis';
        }

        // Validation matière
        if (!formData.series_subject_id) {
            errors.series_subject_id = 'La matière est requise';
        }

        // Validation séquence
        if (!formData.sequence_id) {
            errors.sequence_id = 'La séquence est requise';
        }

        // Validation note maximale
        const maxScore = parseFloat(formData.max_score);
        if (!maxScore || maxScore <= 0 || maxScore !== 20) {
            errors.max_score = 'La note maximale doit être 20';
        }

        // Validation coefficient
        const coefficient = parseFloat(formData.coefficient);
        if (!coefficient || coefficient <= 0) {
            errors.coefficient = 'Le coefficient doit être positif';
        }

        return errors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        const errors = validateForm();
        if (Object.keys(errors).length > 0) {
            setValidationErrors(errors);
            return;
        }

        try {
            setSubmitting(true);
            setError(null);

            const evaluationData = {
                ...formData,
                teacher_id: user.teacher_id || user.id,  // Utiliser teacher_id si disponible
                create_for_all_sequences: createForAllSequences  // Ajouter l'option
                // sequence_id est déjà dans formData
            };

            const response = await secureApiEndpoints.evaluations.create(evaluationData);

            if (response.success) {
                setSuccess(true);
                // Message personnalisé selon le nombre d'évaluations créées
                const createdCount = response.data?.created_count || 1;
                if (createdCount > 1) {
                    setSuccessMessage(`${createdCount} évaluations créées avec succès pour toutes les séquences !`);
                } else {
                    setSuccessMessage('Évaluation créée avec succès !');
                }
                setTimeout(() => {
                    navigate('/teacher/evaluations');
                }, 2000);
            }

        } catch (error) {
            console.error('Erreur lors de la création:', error);
            setError(error.message || 'Erreur lors de la création de l\'évaluation');
        } finally {
            setSubmitting(false);
        }
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR');
    };


    const getTypeConfig = (type) => {
        return evaluationTypesConfig[type] || {};
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
                                        <PlusCircle className="me-2" />
                                        Créer une Évaluation
                                    </h4>
                                    <p className="mb-0 opacity-75">
                                        Système d'évaluation camerounais - Toutes séquences disponibles
                                    </p>
                                </div>
                                <div className="text-end">
                                    <small className="opacity-75">Enseignant</small>
                                    <div className="fw-bold">{user?.name}</div>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Messages */}
            {error && (
                <Row className="mb-4">
                    <Col>
                        <Alert variant="danger">
                            <XCircle className="me-2" />
                            {error}
                        </Alert>
                    </Col>
                </Row>
            )}

            {success && (
                <Row className="mb-4">
                    <Col>
                        <Alert variant="success">
                            <CheckCircle className="me-2" />
                            {successMessage || 'Évaluation créée avec succès !'} Redirection en cours...
                        </Alert>
                    </Col>
                </Row>
            )}

            <Row>
                {/* Formulaire principal */}
                <Col lg={8}>
                    <Card>
                        <Card.Header>
                            <h5 className="mb-0">
                                <FileText className="me-2" />
                                Détails de l'Évaluation
                            </h5>
                        </Card.Header>
                        <Card.Body>
                            <Form onSubmit={handleSubmit}>
                                <Row>
                                    {/* Matière */}
                                    <Col md={6} className="mb-3">
                                        <FloatingLabel label="Matière">
                                            <Form.Select
                                                name="series_subject_id"
                                                value={formData.series_subject_id}
                                                onChange={handleInputChange}
                                                isInvalid={!!validationErrors.series_subject_id}
                                            >
                                                <option value="">Choisir la matière...</option>
                                                {seriesSubjects.map(ss => (
                                                    <option key={ss.id} value={ss.id}>
                                                        {ss.subject?.name} - {ss.school_class?.name}
                                                    </option>
                                                ))}
                                            </Form.Select>
                                            <Form.Control.Feedback type="invalid">
                                                {validationErrors.series_subject_id}
                                            </Form.Control.Feedback>
                                        </FloatingLabel>
                                    </Col>

                                    {/* Séquence */}
                                    <Col md={6} className="mb-3">
                                        <FloatingLabel label="Séquence">
                                            <Form.Select
                                                name="sequence_id"
                                                value={formData.sequence_id}
                                                onChange={handleInputChange}
                                                isInvalid={!!validationErrors.sequence_id}
                                            >
                                                <option value="">Choisir la séquence...</option>
                                                {sequences.map(seq => (
                                                    <option key={seq.id} value={seq.id}>
                                                        {seq.name} - {seq.trimester?.name}
                                                    </option>
                                                ))}
                                            </Form.Select>
                                            <Form.Control.Feedback type="invalid">
                                                {validationErrors.sequence_id}
                                            </Form.Control.Feedback>
                                        </FloatingLabel>
                                    </Col>

                                    {/* Option pour créer pour toutes les séquences */}
                                    {formData.sequence_id && sequences.length > 1 && (
                                        <Col md={12} className="mb-3">
                                            <Card className="border-info">
                                                <Card.Body className="py-2">
                                                    <Form.Check
                                                        type="checkbox"
                                                        id="createForAllSequences"
                                                        label={
                                                            <span>
                                                                <strong>Créer cette évaluation pour toutes les séquences</strong>
                                                                <br />
                                                                <small className="text-muted">
                                                                    L'évaluation sera automatiquement créée pour toutes les {sequences.filter(s => !s.is_composition).length} séquences disponibles avec les mêmes paramètres
                                                                </small>
                                                            </span>
                                                        }
                                                        checked={createForAllSequences}
                                                        onChange={(e) => setCreateForAllSequences(e.target.checked)}
                                                    />
                                                </Card.Body>
                                            </Card>
                                        </Col>
                                    )}

                                    {/* Type d'évaluation - N'apparaît que si une matière est sélectionnée */}
                                    {selectedSeriesSubject && (
                                        <Col md={12} className="mb-3">
                                            <FloatingLabel label="Type d'évaluation">
                                                <Form.Select
                                                    name="type"
                                                    value={formData.type}
                                                    onChange={handleInputChange}
                                                    isInvalid={!!validationErrors.type}
                                                >
                                                    <option value="">Choisir le type...</option>
                                                    {Object.entries(evaluationTypesConfig).map(([key, config]) => (
                                                        <option key={key} value={key}>
                                                            {config.label}
                                                        </option>
                                                    ))}
                                                </Form.Select>
                                                <Form.Control.Feedback type="invalid">
                                                    {validationErrors.type}
                                                </Form.Control.Feedback>
                                            </FloatingLabel>
                                        </Col>
                                    )}

                                    {/* Nom de l'évaluation - N'apparaît que si type sélectionné */}
                                    {selectedSeriesSubject && formData.type && (
                                        <Col md={12} className="mb-3">
                                            <FloatingLabel label="Nom de l'évaluation">
                                                <Form.Control
                                                    type="text"
                                                    name="name"
                                                    value={formData.name}
                                                    onChange={handleInputChange}
                                                    placeholder="Le nom sera généré automatiquement..."
                                                    isInvalid={!!validationErrors.name}
                                                />
                                                <Form.Control.Feedback type="invalid">
                                                    {validationErrors.name}
                                                </Form.Control.Feedback>
                                            </FloatingLabel>
                                        </Col>
                                    )}


                                    {/* Note maximale et Coefficient - N'apparaissent que si type sélectionné */}
                                    {selectedSeriesSubject && formData.type && (
                                        <>
                                            <Col md={6} className="mb-3">
                                                <FloatingLabel label="Note maximale">
                                                    <Form.Control
                                                        type="number"
                                                        name="max_score"
                                                        value={formData.max_score}
                                                        onChange={handleInputChange}
                                                        step="0.5"
                                                        min="1"
                                                        max="20"
                                                        isInvalid={!!validationErrors.max_score}
                                                        readOnly
                                                    />
                                                    <Form.Control.Feedback type="invalid">
                                                        {validationErrors.max_score}
                                                    </Form.Control.Feedback>
                                                </FloatingLabel>
                                            </Col>

                                            <Col md={6} className="mb-3">
                                                <FloatingLabel label="Coefficient (auto-récupéré)">
                                                    <Form.Control
                                                        type="number"
                                                        name="coefficient"
                                                        value={formData.coefficient}
                                                        onChange={handleInputChange}
                                                        step="0.1"
                                                        min="0.1"
                                                        max="10"
                                                        isInvalid={!!validationErrors.coefficient}
                                                        readOnly={selectedSeriesSubject && selectedSeriesSubject.coefficient}
                                                    />
                                                    <Form.Control.Feedback type="invalid">
                                                        {validationErrors.coefficient}
                                                    </Form.Control.Feedback>
                                                    {selectedSeriesSubject && selectedSeriesSubject.coefficient && (
                                                        <Form.Text className="text-success">
                                                            <i className="bi bi-check-circle me-1"></i>
                                                            Coefficient récupéré automatiquement de la configuration
                                                        </Form.Text>
                                                    )}
                                                    {selectedSeriesSubject && !selectedSeriesSubject.coefficient && (
                                                        <Form.Text className="text-warning">
                                                            <i className="bi bi-exclamation-triangle me-1"></i>
                                                            Coefficient non configuré - valeur par défaut utilisée
                                                        </Form.Text>
                                                    )}
                                                </FloatingLabel>
                                            </Col>
                                        </>
                                    )}

                                    {/* Description - N'apparaît que si type sélectionné */}
                                    {selectedSeriesSubject && formData.type && (
                                        <Col md={12} className="mb-3">
                                            <FloatingLabel label="Description (optionnel)">
                                                <Form.Control
                                                    as="textarea"
                                                    name="description"
                                                    value={formData.description}
                                                    onChange={handleInputChange}
                                                    style={{ height: '80px' }}
                                                    placeholder="Détails sur l'évaluation..."
                                                />
                                            </FloatingLabel>
                                        </Col>
                                    )}
                                </Row>

                                {/* Boutons */}
                                <div className="d-flex justify-content-between">
                                    <Button
                                        variant="secondary"
                                        onClick={() => navigate('/teacher/evaluations')}
                                    >
                                        Annuler
                                    </Button>
                                    <Button
                                        type="submit"
                                        variant="primary"
                                        disabled={submitting || !formData.sequence_id || !selectedSeriesSubject || !formData.type}
                                    >
                                        {submitting ? (
                                            <>
                                                <Spinner size="sm" className="me-2" />
                                                Création...
                                            </>
                                        ) : (
                                            <>
                                                <PlusCircle className="me-2" />
                                                Créer l'Évaluation
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </Form>
                        </Card.Body>
                    </Card>
                </Col>

                {/* Panneau d'aide */}
                <Col lg={4}>
                    {/* Info séquence sélectionnée */}
                    {formData.sequence_id && (() => {
                        const selectedSequence = sequences.find(s => s.id.toString() === formData.sequence_id);
                        return selectedSequence ? (
                            <Card className="mb-4">
                                <Card.Header className="bg-success text-white">
                                    <Calendar className="me-2" />
                                    Séquence Sélectionnée
                                </Card.Header>
                                <Card.Body>
                                    <h6>{selectedSequence.name}</h6>
                                    <p className="text-muted mb-2">
                                        {selectedSequence.trimester?.name}
                                    </p>
                                    <small className="text-muted">
                                        Du {formatDate(selectedSequence.start_date)} au {formatDate(selectedSequence.end_date)}
                                    </small>
                                </Card.Body>
                            </Card>
                        ) : null;
                    })()}

                    {/* Info type sélectionné */}
                    {formData.type && (
                        <Card className="mb-4">
                            <Card.Header>
                                <Award className="me-2" />
                                Type d'Évaluation
                            </Card.Header>
                            <Card.Body>
                                <Badge bg={getTypeConfig(formData.type).color} className="mb-2">
                                    {getTypeConfig(formData.type).label}
                                </Badge>
                                <p className="small text-muted mb-2">
                                    {getTypeConfig(formData.type).description}
                                </p>
                                <small className="text-muted">
                                    <strong>Notes acceptées :</strong> {getTypeConfig(formData.type).maxScore?.join(', ')}<br/>
                                    <strong>Coefficient :</strong> {getTypeConfig(formData.type).coefficientRange?.join(' - ')}
                                </small>
                            </Card.Body>
                        </Card>
                    )}

                    {/* Règles pédagogiques */}
                    <Card className="mb-4">
                        <Card.Header>
                            <Book className="me-2" />
                            Règles Pédagogiques
                        </Card.Header>
                        <Card.Body>
                            <ul className="small mb-0">
                                <li>Compositions : 2-4h, coefficient auto-récupéré</li>
                                <li>Travaux Pratiques : Évaluation pratique, coefficient auto-récupéré</li>
                                <li>Notes sur 20</li>
                                <li>Le nom est généré automatiquement</li>
                                <li><strong>Le coefficient est récupéré automatiquement de la configuration des matières</strong></li>
                            </ul>
                        </Card.Body>
                    </Card>

                    {/* Matière sélectionnée */}
                    {selectedSeriesSubject && (
                        <Card>
                            <Card.Header>
                                <People className="me-2" />
                                Matière Sélectionnée
                            </Card.Header>
                            <Card.Body>
                                <h6>{selectedSeriesSubject.subject?.name}</h6>
                                <p className="text-muted mb-2">Classe: {selectedSeriesSubject.school_class?.name}</p>
                                <div className="mt-2">
                                    <Badge bg="info" className="me-2">
                                        Coefficient: {selectedSeriesSubject.coefficient || 'Non défini'}
                                    </Badge>
                                    {selectedSeriesSubject.school_class && (
                                        <Badge bg="secondary">
                                            {selectedSeriesSubject.school_class.name}
                                        </Badge>
                                    )}
                                </div>
                            </Card.Body>
                        </Card>
                    )}
                </Col>
            </Row>
        </Container>
    );
};

export default EvaluationCreate;