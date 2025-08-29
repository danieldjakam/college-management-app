import React, { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Form,
    Alert,
    Spinner,
    Badge,
    ListGroup
} from 'react-bootstrap';
import {
    ArrowLeft,
    Send,
    Save,
    PersonFill,
    ExclamationTriangle,
    InfoCircle,
    CheckCircle
} from 'react-bootstrap-icons';
import { Link, useNavigate } from 'react-router-dom';
import { authService } from '../../services/authService';
import { extractErrorMessage } from '../../utils/errorHandler';
import { host } from '../../utils/fetch';

const NouvelleDemande = () => {
    const navigate = useNavigate();
    
    // États pour le formulaire
    const [formData, setFormData] = useState({
        destinataire_id: '',
        sujet: '',
        message: '',
        type: 'autre',
        priorite: 'normale',
        notes_internes: ''
    });
    
    // États pour l'interface
    const [personnel, setPersonnel] = useState([]);
    const [loading, setLoading] = useState(false);
    const [loadingPersonnel, setLoadingPersonnel] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [validated, setValidated] = useState(false);

    // Options de configuration
    const typeOptions = [
        { value: 'financier', label: 'Financier', description: 'Questions financières, comptabilité' },
        { value: 'absence', label: 'Absence', description: 'Justification d\'absences' },
        { value: 'retard', label: 'Retard', description: 'Retards répétés' },
        { value: 'disciplinaire', label: 'Disciplinaire', description: 'Questions disciplinaires' },
        { value: 'autre', label: 'Autre', description: 'Autres motifs' }
    ];

    const priorityOptions = [
        { value: 'basse', label: 'Basse', color: 'success', description: 'Non urgent, peut attendre' },
        { value: 'normale', label: 'Normale', color: 'info', description: 'Priorité standard' },
        { value: 'haute', label: 'Haute', color: 'warning', description: 'Nécessite attention rapide' },
        { value: 'urgente', label: 'Urgente', color: 'danger', description: 'Réponse immédiate requise' }
    ];

    // Charger la liste du personnel
    useEffect(() => {
        const loadPersonnel = async () => {
            try {
                setLoadingPersonnel(true);
                const response = await fetch(`${host}/api/demandes-explication/personnel`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${authService.getToken()}`,
                        'Accept': 'application/json',
                    },
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        setPersonnel(data.data || []);
                    }
                } else {
                    setError('Erreur lors du chargement de la liste du personnel');
                }
            } catch (error) {
                setError('Erreur lors du chargement de la liste du personnel');
            } finally {
                setLoadingPersonnel(false);
            }
        };

        loadPersonnel();
    }, []);

    // Gérer les changements de formulaire
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        // Réinitialiser la validation si l'utilisateur modifie
        if (validated) {
            setValidated(false);
        }
    };

    // Valider le formulaire
    const validateForm = () => {
        const errors = {};

        if (!formData.destinataire_id) {
            errors.destinataire_id = 'Veuillez sélectionner un destinataire';
        }
        if (!formData.sujet.trim()) {
            errors.sujet = 'Le sujet est obligatoire';
        } else if (formData.sujet.length > 255) {
            errors.sujet = 'Le sujet ne peut pas dépasser 255 caractères';
        }
        if (!formData.message.trim()) {
            errors.message = 'Le message est obligatoire';
        }

        return errors;
    };

    // Envoyer la demande
    const handleSubmit = async (envoiImmediat = true) => {
        const errors = validateForm();
        
        if (Object.keys(errors).length > 0) {
            setValidated(true);
            setError('Veuillez corriger les erreurs du formulaire');
            return;
        }

        try {
            setLoading(true);
            setError('');
            setSuccess('');

            const submitData = {
                ...formData,
                envoi_immediat: envoiImmediat
            };

            const response = await fetch(`${host}/api/demandes-explication`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(submitData)
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(data.message);
                if (envoiImmediat) {
                    // Rediriger après 2 secondes
                    setTimeout(() => {
                        navigate('/demandes-explication');
                    }, 2000);
                } else {
                    // Réinitialiser le formulaire pour un nouveau brouillon
                    setFormData({
                        destinataire_id: '',
                        sujet: '',
                        message: '',
                        type: 'autre',
                        priorite: 'normale',
                        notes_internes: ''
                    });
                }
            } else {
                setError(data.message || 'Erreur lors de l\'envoi');
                if (data.errors) {
                    // Afficher les erreurs de validation
                    const errorMessages = Object.values(data.errors).flat().join(', ');
                    setError(errorMessages);
                }
            }
        } catch (error) {
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <Button
                                variant="outline-secondary"
                                as={Link}
                                to="/demandes-explication"
                                className="me-3 d-flex align-items-center gap-2"
                                size="sm"
                            >
                                <ArrowLeft />
                                Retour
                            </Button>
                            <h2 className="d-inline-flex align-items-center gap-2 mb-1">
                                <ExclamationTriangle className="text-warning" />
                                Nouvelle Demande d'Explication
                            </h2>
                            <p className="text-muted mb-0">
                                Créer une demande d'explication à envoyer au personnel
                            </p>
                        </div>
                    </div>
                </Col>
            </Row>

            {/* Alerts */}
            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')} className="mb-4">
                    <strong>Erreur:</strong> {error}
                </Alert>
            )}
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess('')} className="mb-4">
                    <CheckCircle className="me-2" />
                    <strong>Succès:</strong> {success}
                </Alert>
            )}

            <Row>
                <Col lg={8}>
                    {/* Formulaire principal */}
                    <Card className="shadow-sm">
                        <Card.Header className="bg-light">
                            <h5 className="mb-0">Détails de la demande</h5>
                        </Card.Header>
                        <Card.Body>
                            <Form noValidate validated={validated}>
                                {/* Destinataire */}
                                <Form.Group className="mb-4">
                                    <Form.Label className="fw-semibold d-flex align-items-center gap-2">
                                        <PersonFill className="text-primary" />
                                        Destinataire *
                                    </Form.Label>
                                    {loadingPersonnel ? (
                                        <div className="text-center py-3">
                                            <Spinner size="sm" className="me-2" />
                                            Chargement du personnel...
                                        </div>
                                    ) : (
                                        <Form.Select
                                            name="destinataire_id"
                                            value={formData.destinataire_id}
                                            onChange={handleInputChange}
                                            required
                                            isInvalid={validated && !formData.destinataire_id}
                                        >
                                            <option value="">Sélectionner un membre du personnel</option>
                                            {personnel.map(person => (
                                                <option key={person.id} value={person.id}>
                                                    {person.name} - {person.role_label} ({person.email})
                                                </option>
                                            ))}
                                        </Form.Select>
                                    )}
                                    <Form.Control.Feedback type="invalid">
                                        Veuillez sélectionner un destinataire
                                    </Form.Control.Feedback>
                                </Form.Group>

                                {/* Sujet */}
                                <Form.Group className="mb-4">
                                    <Form.Label className="fw-semibold">Sujet *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        name="sujet"
                                        value={formData.sujet}
                                        onChange={handleInputChange}
                                        placeholder="Objet de la demande d'explication"
                                        required
                                        maxLength={255}
                                        isInvalid={validated && !formData.sujet.trim()}
                                    />
                                    <Form.Text className="text-muted">
                                        {formData.sujet.length}/255 caractères
                                    </Form.Text>
                                    <Form.Control.Feedback type="invalid">
                                        Le sujet est obligatoire
                                    </Form.Control.Feedback>
                                </Form.Group>

                                {/* Type et Priorité */}
                                <Row className="mb-4">
                                    <Col md={6}>
                                        <Form.Group>
                                            <Form.Label className="fw-semibold">Type de demande</Form.Label>
                                            <Form.Select
                                                name="type"
                                                value={formData.type}
                                                onChange={handleInputChange}
                                            >
                                                {typeOptions.map(option => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </Form.Select>
                                            <Form.Text className="text-muted">
                                                {typeOptions.find(t => t.value === formData.type)?.description}
                                            </Form.Text>
                                        </Form.Group>
                                    </Col>
                                    <Col md={6}>
                                        <Form.Group>
                                            <Form.Label className="fw-semibold">Priorité</Form.Label>
                                            <Form.Select
                                                name="priorite"
                                                value={formData.priorite}
                                                onChange={handleInputChange}
                                            >
                                                {priorityOptions.map(option => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </Form.Select>
                                            <Form.Text className="text-muted">
                                                {priorityOptions.find(p => p.value === formData.priorite)?.description}
                                            </Form.Text>
                                        </Form.Group>
                                    </Col>
                                </Row>

                                {/* Message */}
                                <Form.Group className="mb-4">
                                    <Form.Label className="fw-semibold">Message *</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={6}
                                        name="message"
                                        value={formData.message}
                                        onChange={handleInputChange}
                                        placeholder="Détaillez votre demande d'explication..."
                                        required
                                        isInvalid={validated && !formData.message.trim()}
                                    />
                                    <Form.Control.Feedback type="invalid">
                                        Le message est obligatoire
                                    </Form.Control.Feedback>
                                </Form.Group>

                                {/* Notes internes */}
                                <Form.Group className="mb-4">
                                    <Form.Label className="fw-semibold">Notes internes (optionnel)</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        name="notes_internes"
                                        value={formData.notes_internes}
                                        onChange={handleInputChange}
                                        placeholder="Notes privées pour votre suivi (non visibles par le destinataire)"
                                    />
                                    <Form.Text className="text-muted">
                                        Ces notes ne seront visibles que par vous
                                    </Form.Text>
                                </Form.Group>

                                {/* Actions */}
                                <div className="d-flex gap-3 justify-content-end">
                                    <Button
                                        variant="outline-secondary"
                                        onClick={() => handleSubmit(false)}
                                        disabled={loading}
                                        className="d-flex align-items-center gap-2"
                                    >
                                        <Save size={16} />
                                        Sauvegarder en brouillon
                                    </Button>
                                    <Button
                                        variant="primary"
                                        onClick={() => handleSubmit(true)}
                                        disabled={loading}
                                        className="d-flex align-items-center gap-2"
                                    >
                                        {loading ? (
                                            <>
                                                <Spinner size="sm" />
                                                Envoi en cours...
                                            </>
                                        ) : (
                                            <>
                                                <Send size={16} />
                                                Envoyer maintenant
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </Form>
                        </Card.Body>
                    </Card>
                </Col>

                <Col lg={4}>
                    {/* Aide et informations */}
                    <Card className="shadow-sm mb-4">
                        <Card.Header className="bg-info text-white">
                            <h6 className="mb-0 d-flex align-items-center gap-2">
                                <InfoCircle />
                                Guide d'utilisation
                            </h6>
                        </Card.Header>
                        <Card.Body>
                            <ListGroup variant="flush">
                                <ListGroup.Item className="px-0 py-2">
                                    <strong>1. Destinataire</strong><br />
                                    <small className="text-muted">
                                        Choisissez le membre du personnel concerné
                                    </small>
                                </ListGroup.Item>
                                <ListGroup.Item className="px-0 py-2">
                                    <strong>2. Sujet</strong><br />
                                    <small className="text-muted">
                                        Résumez l'objet de votre demande en quelques mots
                                    </small>
                                </ListGroup.Item>
                                <ListGroup.Item className="px-0 py-2">
                                    <strong>3. Type</strong><br />
                                    <small className="text-muted">
                                        Catégorisez votre demande pour faciliter le suivi
                                    </small>
                                </ListGroup.Item>
                                <ListGroup.Item className="px-0 py-2">
                                    <strong>4. Priorité</strong><br />
                                    <small className="text-muted">
                                        Indiquez l'urgence de votre demande
                                    </small>
                                </ListGroup.Item>
                                <ListGroup.Item className="px-0 py-2">
                                    <strong>5. Message</strong><br />
                                    <small className="text-muted">
                                        Expliquez clairement votre demande
                                    </small>
                                </ListGroup.Item>
                            </ListGroup>
                        </Card.Body>
                    </Card>

                    {/* Aperçu de la priorité sélectionnée */}
                    <Card className="shadow-sm">
                        <Card.Header>
                            <h6 className="mb-0">Aperçu de la priorité</h6>
                        </Card.Header>
                        <Card.Body>
                            {priorityOptions.map(option => (
                                <div 
                                    key={option.value} 
                                    className={`mb-2 p-2 rounded ${formData.priorite === option.value ? 'border border-primary bg-light' : ''}`}
                                >
                                    <Badge bg={option.color} className="me-2">
                                        {option.label}
                                    </Badge>
                                    <small className="text-muted">
                                        {option.description}
                                    </small>
                                </div>
                            ))}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </Container>
    );
};

export default NouvelleDemande;