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
        date: '',
        max_score: '20',
        coefficient: '1',
        description: ''
    });

    // États de l'interface
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);
    const [validationErrors, setValidationErrors] = useState({});

    // Données de référence
    const [sequences, setSequences] = useState([]);
    const [currentSequence, setCurrentSequence] = useState(null);
    const [seriesSubjects, setSeriesSubjects] = useState([]);
    const [evaluationTypes, setEvaluationTypes] = useState([]);

    // Types d'évaluations camerounaises avec règles
    const evaluationTypesConfig = {
        interrogation: {
            label: 'Interrogation écrite',
            description: '15-30 min, note sur 10 ou 20',
            maxScore: [10, 20],
            coefficientRange: [0.5, 2],
            color: 'info',
            icon: FileText
        },
        devoir: {
            label: 'Devoir surveillé', 
            description: '1-2h, note sur 20',
            maxScore: [20],
            coefficientRange: [1, 3],
            color: 'warning',
            icon: Book
        },
        composition: {
            label: 'Composition',
            description: '2-4h, note sur 20',
            maxScore: [20],
            coefficientRange: [2, 5],
            color: 'success',
            icon: Award
        },
        tp: {
            label: 'Travaux pratiques',
            description: 'Évaluation pratique',
            maxScore: [10, 20],
            coefficientRange: [1, 3],
            color: 'secondary',
            icon: People
        },
        controle: {
            label: 'Contrôle continu',
            description: 'Évaluation continue',
            maxScore: [10, 20],
            coefficientRange: [0.5, 2],
            color: 'primary',
            icon: CheckCircle
        }
    };

    useEffect(() => {
        loadInitialData();
    }, []);

    const loadInitialData = async () => {
        try {
            setLoading(true);
            setError(null);

            // Charger la séquence courante
            try {
                const sequenceData = await secureApiEndpoints.sequences.getCurrent();
                setCurrentSequence(sequenceData.data);
            } catch (seqError) {
                console.warn('Pas de séquence courante:', seqError);
            }

            // Charger toutes les séquences
            const sequencesData = await secureApiEndpoints.sequences.getAll();
            setSequences(sequencesData.data || []);

            // Charger les matières assignées à l'enseignant
            const teacherAssignments = await secureApiEndpoints.seriesSubjects.getAll({
                teacher_id: user.id,
                active: true
            });
            setSeriesSubjects(teacherAssignments.data || []);

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

        // Ajustements automatiques selon le type d'évaluation
        if (name === 'type' && value && evaluationTypesConfig[value]) {
            const typeConfig = evaluationTypesConfig[value];
            setFormData(prev => ({
                ...prev,
                max_score: typeConfig.maxScore[0].toString(),
                coefficient: typeConfig.coefficientRange[0].toString()
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

        // Validation date
        if (!formData.date) {
            errors.date = 'La date est requise';
        } else {
            const selectedDate = new Date(formData.date);
            const today = new Date();
            
            if (selectedDate < today.setHours(0, 0, 0, 0)) {
                errors.date = 'La date ne peut pas être dans le passé';
            }

            // Vérifier si la date est dans la séquence courante
            if (currentSequence) {
                const seqStart = new Date(currentSequence.start_date);
                const seqEnd = new Date(currentSequence.end_date);
                
                if (selectedDate < seqStart || selectedDate > seqEnd) {
                    errors.date = `La date doit être dans la séquence courante (${formatDate(seqStart)} - ${formatDate(seqEnd)})`;
                }
            }
        }

        // Validation note maximale
        const maxScore = parseFloat(formData.max_score);
        if (!maxScore || maxScore <= 0) {
            errors.max_score = 'La note maximale doit être positive';
        } else if (formData.type && evaluationTypesConfig[formData.type]) {
            const validScores = evaluationTypesConfig[formData.type].maxScore;
            if (!validScores.includes(maxScore)) {
                errors.max_score = `Pour ce type d'évaluation, la note doit être : ${validScores.join(' ou ')}`;
            }
        }

        // Validation coefficient
        const coefficient = parseFloat(formData.coefficient);
        if (!coefficient || coefficient <= 0) {
            errors.coefficient = 'Le coefficient doit être positif';
        } else if (formData.type && evaluationTypesConfig[formData.type]) {
            const [min, max] = evaluationTypesConfig[formData.type].coefficientRange;
            if (coefficient < min || coefficient > max) {
                errors.coefficient = `Pour ce type d'évaluation, le coefficient doit être entre ${min} et ${max}`;
            }
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
                teacher_id: user.id,
                sequence_id: currentSequence?.id
            };

            const response = await secureApiEndpoints.evaluations.create(evaluationData);

            if (response.success) {
                setSuccess(true);
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

    const getSelectedSeriesSubject = () => {
        return seriesSubjects.find(ss => ss.id.toString() === formData.series_subject_id);
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
                                        Système d'évaluation camerounais - {currentSequence?.name || 'Aucune séquence active'}
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
                            Évaluation créée avec succès ! Redirection en cours...
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
                                    {/* Nom de l'évaluation */}
                                    <Col md={12} className="mb-3">
                                        <FloatingLabel label="Nom de l'évaluation">
                                            <Form.Control
                                                type="text"
                                                name="name"
                                                value={formData.name}
                                                onChange={handleInputChange}
                                                placeholder="Ex: Interrogation - Les nombres entiers"
                                                isInvalid={!!validationErrors.name}
                                            />
                                            <Form.Control.Feedback type="invalid">
                                                {validationErrors.name}
                                            </Form.Control.Feedback>
                                        </FloatingLabel>
                                    </Col>

                                    {/* Type d'évaluation */}
                                    <Col md={6} className="mb-3">
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

                                    {/* Date */}
                                    <Col md={6} className="mb-3">
                                        <FloatingLabel label="Date de l'évaluation">
                                            <Form.Control
                                                type="date"
                                                name="date"
                                                value={formData.date}
                                                onChange={handleInputChange}
                                                isInvalid={!!validationErrors.date}
                                            />
                                            <Form.Control.Feedback type="invalid">
                                                {validationErrors.date}
                                            </Form.Control.Feedback>
                                        </FloatingLabel>
                                    </Col>

                                    {/* Note maximale */}
                                    <Col md={3} className="mb-3">
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
                                            />
                                            <Form.Control.Feedback type="invalid">
                                                {validationErrors.max_score}
                                            </Form.Control.Feedback>
                                        </FloatingLabel>
                                    </Col>

                                    {/* Coefficient */}
                                    <Col md={3} className="mb-3">
                                        <FloatingLabel label="Coefficient">
                                            <Form.Control
                                                type="number"
                                                name="coefficient"
                                                value={formData.coefficient}
                                                onChange={handleInputChange}
                                                step="0.1"
                                                min="0.1"
                                                max="10"
                                                isInvalid={!!validationErrors.coefficient}
                                            />
                                            <Form.Control.Feedback type="invalid">
                                                {validationErrors.coefficient}
                                            </Form.Control.Feedback>
                                        </FloatingLabel>
                                    </Col>

                                    {/* Description */}
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
                                        disabled={submitting || !currentSequence}
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
                    {/* Info séquence courante */}
                    {currentSequence && (
                        <Card className="mb-4">
                            <Card.Header className="bg-success text-white">
                                <Calendar className="me-2" />
                                Séquence Courante
                            </Card.Header>
                            <Card.Body>
                                <h6>{currentSequence.name}</h6>
                                <p className="text-muted mb-2">
                                    {currentSequence.trimester?.name}
                                </p>
                                <small className="text-muted">
                                    Du {formatDate(currentSequence.start_date)} au {formatDate(currentSequence.end_date)}
                                </small>
                            </Card.Body>
                        </Card>
                    )}

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

                    {/* Règles camerounaises */}
                    <Card className="mb-4">
                        <Card.Header>
                            <Book className="me-2" />
                            Règles Pédagogiques
                        </Card.Header>
                        <Card.Body>
                            <ul className="small mb-0">
                                <li>Interrogations : 15-30 min, coefficient 0.5-2</li>
                                <li>Devoirs : 1-2h, coefficient 1-3</li>
                                <li>Compositions : 2-4h, coefficient 2-5</li>
                                <li>Notes sur 10 ou 20 selon le type</li>
                                <li>Date dans la séquence courante</li>
                            </ul>
                        </Card.Body>
                    </Card>

                    {/* Matière sélectionnée */}
                    {formData.series_subject_id && (
                        <Card>
                            <Card.Header>
                                <People className="me-2" />
                                Matière Sélectionnée
                            </Card.Header>
                            <Card.Body>
                                {(() => {
                                    const selected = getSelectedSeriesSubject();
                                    return selected ? (
                                        <>
                                            <h6>{selected.subject?.name}</h6>
                                            <p className="text-muted mb-2">Classe: {selected.school_class?.name}</p>
                                            <small className="text-muted">
                                                Coefficient matière: {selected.coefficient}
                                            </small>
                                        </>
                                    ) : null;
                                })()}
                            </Card.Body>
                        </Card>
                    )}
                </Col>
            </Row>
        </Container>
    );
};

export default EvaluationCreate;