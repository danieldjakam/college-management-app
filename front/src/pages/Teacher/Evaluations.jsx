import React, { useState, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { useNavigate } from 'react-router-dom';
import { 
    Container, Row, Col, Card, Table, Button, Alert, 
    Spinner, Badge, Dropdown, Form, InputGroup 
} from 'react-bootstrap';
import { 
    Plus, Search, Filter, Calendar, Book, Award, 
    Eye, Pencil, Trash, FileText, Clock 
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';

const Evaluations = () => {
    const { user } = useAuth();
    const navigate = useNavigate();

    // États
    const [loading, setLoading] = useState(true);
    const [evaluations, setEvaluations] = useState([]);
    const [filteredEvaluations, setFilteredEvaluations] = useState([]);
    const [error, setError] = useState(null);
    const [currentSequence, setCurrentSequence] = useState(null);

    // Filtres
    const [filters, setFilters] = useState({
        search: '',
        type: '',
        sequence_id: '',
        series_subject_id: ''
    });

    // Données de référence
    const [sequences, setSequences] = useState([]);
    const [seriesSubjects, setSeriesSubjects] = useState([]);

    // Configuration des types
    const typeConfig = {
        interrogation: { label: 'Interrogation', color: 'info', icon: FileText },
        devoir: { label: 'Devoir', color: 'warning', icon: Book },
        composition: { label: 'Composition', color: 'success', icon: Award },
        tp: { label: 'TP', color: 'secondary', icon: Clock },
        controle: { label: 'Contrôle', color: 'primary', icon: Eye }
    };

    useEffect(() => {
        loadData();
    }, []);

    useEffect(() => {
        applyFilters();
    }, [evaluations, filters]);

    const loadData = async () => {
        try {
            setLoading(true);
            setError(null);

            // Charger les évaluations de l'enseignant
            const evaluationsData = await secureApiEndpoints.evaluations.getAll({
                teacher_id: user.id
            });

            // Charger la séquence courante
            try {
                const currentData = await secureApiEndpoints.sequences.getCurrent();
                setCurrentSequence(currentData.data);
            } catch (currentError) {
                console.warn('Pas de séquence courante');
            }

            // Charger toutes les séquences
            const sequencesData = await secureApiEndpoints.sequences.getAll();
            setSequences(sequencesData.data || []);

            // Charger les matières de l'enseignant
            const subjectsData = await secureApiEndpoints.seriesSubjects.getAll({
                teacher_id: user.id
            });
            setSeriesSubjects(subjectsData.data || []);

            setEvaluations(evaluationsData.data || []);

        } catch (error) {
            console.error('Erreur lors du chargement:', error);
            setError('Impossible de charger les évaluations. Veuillez réessayer.');
        } finally {
            setLoading(false);
        }
    };

    const applyFilters = () => {
        let filtered = [...evaluations];

        // Filtre par recherche
        if (filters.search) {
            const searchTerm = filters.search.toLowerCase();
            filtered = filtered.filter(evaluation => 
                evaluation.name.toLowerCase().includes(searchTerm) ||
                evaluation.series_subject?.subject?.name.toLowerCase().includes(searchTerm) ||
                evaluation.series_subject?.school_class?.name.toLowerCase().includes(searchTerm)
            );
        }

        // Filtre par type
        if (filters.type) {
            filtered = filtered.filter(evaluation => evaluation.type === filters.type);
        }

        // Filtre par séquence
        if (filters.sequence_id) {
            filtered = filtered.filter(evaluation => evaluation.sequence_id.toString() === filters.sequence_id);
        }

        // Filtre par matière
        if (filters.series_subject_id) {
            filtered = filtered.filter(evaluation => evaluation.series_subject_id.toString() === filters.series_subject_id);
        }

        // Trier par date (plus récentes en premier)
        filtered.sort((a, b) => new Date(b.date) - new Date(a.date));

        setFilteredEvaluations(filtered);
    };

    const handleFilterChange = (name, value) => {
        setFilters(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const clearFilters = () => {
        setFilters({
            search: '',
            type: '',
            sequence_id: '',
            series_subject_id: ''
        });
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    const getTypeDisplay = (type) => {
        const config = typeConfig[type] || { label: type, color: 'secondary', icon: FileText };
        const IconComponent = config.icon;
        return (
            <Badge bg={config.color}>
                <IconComponent size={12} className="me-1" />
                {config.label}
            </Badge>
        );
    };

    const getEvaluationStatus = (evaluation) => {
        const evalDate = new Date(evaluation.date);
        const today = new Date();
        
        if (evalDate > today) {
            return <Badge bg="secondary">À venir</Badge>;
        } else if (evalDate.toDateString() === today.toDateString()) {
            return <Badge bg="warning">Aujourd'hui</Badge>;
        } else {
            return <Badge bg="success">Passée</Badge>;
        }
    };

    const getSequenceName = (sequenceId) => {
        const sequence = sequences.find(s => s.id === sequenceId);
        return sequence ? sequence.name : 'N/A';
    };

    if (loading) {
        return (
            <Container className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <div className="text-center">
                    <Spinner animation="border" variant="primary" />
                    <p className="mt-2">Chargement des évaluations...</p>
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
                                        <Book className="me-2" />
                                        Mes Évaluations
                                    </h4>
                                    <p className="mb-0 opacity-75">
                                        Gestion des évaluations - {currentSequence ? currentSequence.name : 'Aucune séquence active'}
                                    </p>
                                </div>
                                <div>
                                    <Button 
                                        variant="light" 
                                        size="lg"
                                        onClick={() => navigate('/teacher/evaluations/create')}
                                        disabled={!currentSequence}
                                    >
                                        <Plus className="me-2" />
                                        Nouvelle Évaluation
                                    </Button>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Filtres */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Body>
                            <Row className="g-3">
                                <Col md={4}>
                                    <InputGroup>
                                        <InputGroup.Text><Search /></InputGroup.Text>
                                        <Form.Control
                                            type="text"
                                            placeholder="Rechercher une évaluation..."
                                            value={filters.search}
                                            onChange={(e) => handleFilterChange('search', e.target.value)}
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={2}>
                                    <Form.Select
                                        value={filters.type}
                                        onChange={(e) => handleFilterChange('type', e.target.value)}
                                    >
                                        <option value="">Tous les types</option>
                                        {Object.entries(typeConfig).map(([key, config]) => (
                                            <option key={key} value={key}>{config.label}</option>
                                        ))}
                                    </Form.Select>
                                </Col>
                                <Col md={3}>
                                    <Form.Select
                                        value={filters.sequence_id}
                                        onChange={(e) => handleFilterChange('sequence_id', e.target.value)}
                                    >
                                        <option value="">Toutes les séquences</option>
                                        {sequences.map(seq => (
                                            <option key={seq.id} value={seq.id}>{seq.name}</option>
                                        ))}
                                    </Form.Select>
                                </Col>
                                <Col md={2}>
                                    <Form.Select
                                        value={filters.series_subject_id}
                                        onChange={(e) => handleFilterChange('series_subject_id', e.target.value)}
                                    >
                                        <option value="">Toutes matières</option>
                                        {seriesSubjects.map(ss => (
                                            <option key={ss.id} value={ss.id}>
                                                {ss.subject?.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Col>
                                <Col md={1}>
                                    <Button variant="outline-secondary" onClick={clearFilters}>
                                        <Filter />
                                    </Button>
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Messages d'erreur */}
            {error && (
                <Row className="mb-4">
                    <Col>
                        <Alert variant="danger">{error}</Alert>
                    </Col>
                </Row>
            )}

            {/* Message si pas de séquence courante */}
            {!currentSequence && (
                <Row className="mb-4">
                    <Col>
                        <Alert variant="warning">
                            <Calendar className="me-2" />
                            Aucune séquence active. Contactez l'administration pour activer une séquence.
                        </Alert>
                    </Col>
                </Row>
            )}

            {/* Statistiques */}
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className="text-primary">{evaluations.length}</h3>
                            <small className="text-muted">Total évaluations</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className="text-success">
                                {evaluations.filter(e => new Date(e.date) < new Date()).length}
                            </h3>
                            <small className="text-muted">Évaluations passées</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className="text-warning">
                                {evaluations.filter(e => new Date(e.date) >= new Date()).length}
                            </h3>
                            <small className="text-muted">À venir</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className="text-info">
                                {currentSequence ? evaluations.filter(e => e.sequence_id === currentSequence.id).length : 0}
                            </h3>
                            <small className="text-muted">Séquence courante</small>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Tableau des évaluations */}
            <Row>
                <Col>
                    <Card>
                        <Card.Header>
                            <h5 className="mb-0">
                                <FileText className="me-2" />
                                Liste des Évaluations ({filteredEvaluations.length})
                            </h5>
                        </Card.Header>
                        <Card.Body className="p-0">
                            {filteredEvaluations.length === 0 ? (
                                <div className="text-center p-5">
                                    <FileText size={48} className="text-muted mb-3" />
                                    <p className="text-muted">
                                        {evaluations.length === 0 
                                            ? "Aucune évaluation créée pour le moment"
                                            : "Aucune évaluation trouvée avec ces filtres"
                                        }
                                    </p>
                                    {currentSequence && evaluations.length === 0 && (
                                        <Button 
                                            variant="primary" 
                                            onClick={() => navigate('/teacher/evaluations/create')}
                                        >
                                            <Plus className="me-2" />
                                            Créer ma première évaluation
                                        </Button>
                                    )}
                                </div>
                            ) : (
                                <Table responsive hover>
                                    <thead className="table-dark">
                                        <tr>
                                            <th>Nom</th>
                                            <th>Type</th>
                                            <th>Matière/Classe</th>
                                            <th>Date</th>
                                            <th>Séquence</th>
                                            <th>Note max</th>
                                            <th>Coeff.</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredEvaluations.map(evaluation => (
                                            <tr key={evaluation.id}>
                                                <td>
                                                    <strong>{evaluation.name}</strong>
                                                    {evaluation.description && (
                                                        <small className="d-block text-muted">
                                                            {evaluation.description}
                                                        </small>
                                                    )}
                                                </td>
                                                <td>{getTypeDisplay(evaluation.type)}</td>
                                                <td>
                                                    <div>
                                                        <strong>{evaluation.series_subject?.subject?.name}</strong>
                                                        <small className="d-block text-muted">
                                                            {evaluation.series_subject?.school_class?.name}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <Calendar size={14} className="me-1" />
                                                    {formatDate(evaluation.date)}
                                                </td>
                                                <td>
                                                    <small className="text-muted">
                                                        {getSequenceName(evaluation.sequence_id)}
                                                    </small>
                                                </td>
                                                <td className="text-center">
                                                    <Badge bg="light" text="dark">
                                                        /{evaluation.max_score}
                                                    </Badge>
                                                </td>
                                                <td className="text-center">
                                                    <Badge bg="secondary">
                                                        ×{evaluation.coefficient}
                                                    </Badge>
                                                </td>
                                                <td>{getEvaluationStatus(evaluation)}</td>
                                                <td>
                                                    <Dropdown>
                                                        <Dropdown.Toggle variant="outline-primary" size="sm">
                                                            Actions
                                                        </Dropdown.Toggle>
                                                        <Dropdown.Menu>
                                                            <Dropdown.Item>
                                                                <Eye className="me-2" />
                                                                Voir détails
                                                            </Dropdown.Item>
                                                            <Dropdown.Item 
                                                                onClick={() => navigate(`/teacher/evaluations/${evaluation.id}/grades`)}
                                                            >
                                                                <Pencil className="me-2" />
                                                                Saisir notes
                                                            </Dropdown.Item>
                                                            <Dropdown.Item>
                                                                <FileText className="me-2" />
                                                                Statistiques
                                                            </Dropdown.Item>
                                                            <Dropdown.Divider />
                                                            <Dropdown.Item className="text-danger">
                                                                <Trash className="me-2" />
                                                                Supprimer
                                                            </Dropdown.Item>
                                                        </Dropdown.Menu>
                                                    </Dropdown>
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
        </Container>
    );
};

export default Evaluations;