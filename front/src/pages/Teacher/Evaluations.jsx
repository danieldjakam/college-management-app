import React, { useState, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { useNavigate } from 'react-router-dom';
import { 
    Container, Row, Col, Card, Table, Button, Alert, 
    Spinner, Badge, Dropdown, Form, InputGroup 
} from 'react-bootstrap';
import { 
    Plus, Search, Filter, Calendar, Book, Award, 
    Eye, Pencil, Trash, FileText, Clock, BarChart 
} from 'react-bootstrap-icons';
import Swal from 'sweetalert2';
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
        composition: { label: 'Composition', color: 'success', icon: Award },
        tp: { label: 'Travaux Pratiques', color: 'secondary', icon: Clock }
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
            // Utiliser teacher_id de l'utilisateur, pas user_id
            const teacherId = user.teacher_id || user.id;
            const evaluationsData = await secureApiEndpoints.evaluations.getAll({
                teacher_id: teacherId
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
                evaluation.series_subject?.subject?.name?.toLowerCase().includes(searchTerm) ||
                evaluation.series_subject?.class_series?.school_class?.name?.toLowerCase().includes(searchTerm) ||
                evaluation.series_subject?.class_series?.name?.toLowerCase().includes(searchTerm)
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

        // Trier par date de création (plus récentes en premier)
        filtered.sort((a, b) => new Date(b.created_at || b.id) - new Date(a.created_at || a.id));

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

    const getTypeConfig = (type) => {
        return typeConfig[type] || { label: type, color: 'secondary', icon: FileText };
    };


    const getSequenceName = (sequenceId) => {
        const sequence = sequences.find(s => s.id === sequenceId);
        return sequence ? sequence.name : 'N/A';
    };

    // Fonction pour voir les détails d'une évaluation
    const handleViewDetails = (evaluation) => {
        Swal.fire({
            title: evaluation.name,
            html: `
                <div class="text-left">
                    <p><strong>Type:</strong> ${getTypeConfig(evaluation.type).label}</p>
                    <p><strong>Matière:</strong> ${evaluation.series_subject?.subject?.name || 'N/A'}</p>
                    <p><strong>Classe:</strong> ${evaluation.series_subject?.class_series?.school_class?.name || evaluation.series_subject?.class_series?.name || 'N/A'}</p>
                    <p><strong>Séquence:</strong> ${getSequenceName(evaluation.sequence_id)}</p>
                    <p><strong>Note maximale:</strong> ${evaluation.max_score}</p>
                    <p><strong>Coefficient:</strong> ${evaluation.coefficient}</p>
                    ${evaluation.description ? `<p><strong>Description:</strong> ${evaluation.description}</p>` : ''}
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Fermer',
            width: '500px'
        });
    };

    // Fonction pour voir les statistiques d'une évaluation
    const handleViewStats = async (evaluation) => {
        try {
            // Afficher un loader
            Swal.fire({
                title: 'Chargement des statistiques...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const response = await secureApiEndpoints.evaluations.getStats(evaluation.id);
            
            if (response.success) {
                const stats = response.data;
                
                Swal.fire({
                    title: `Statistiques - ${evaluation.name}`,
                    html: `
                        <div class="text-left">
                            <div class="row">
                                <div class="col-6">
                                    <div class="card border-primary mb-2">
                                        <div class="card-body text-center p-2">
                                            <h4 class="text-primary mb-1">${stats.total_grades || 0}</h4>
                                            <small class="text-muted">Total élèves</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card border-success mb-2">
                                        <div class="card-body text-center p-2">
                                            <h4 class="text-success mb-1">${stats.graded_count || 0}</h4>
                                            <small class="text-muted">Notes saisies</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="card border-warning mb-2">
                                        <div class="card-body text-center p-2">
                                            <h4 class="text-warning mb-1">${stats.absent_count || 0}</h4>
                                            <small class="text-muted">Absents</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card border-info mb-2">
                                        <div class="card-body text-center p-2">
                                            <h4 class="text-info mb-1">${stats.class_average && typeof stats.class_average === 'number' ? parseFloat(stats.class_average).toFixed(2) : 'N/A'}</h4>
                                            <small class="text-muted">Moyenne</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="card border-secondary">
                                    <div class="card-body text-center p-2">
                                        <h4 class="text-secondary mb-1">${stats.success_rate && typeof stats.success_rate === 'number' ? parseFloat(stats.success_rate).toFixed(1) + '%' : 'N/A'}</h4>
                                        <small class="text-muted">Taux de réussite</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Fermer',
                    width: '400px'
                });
            } else {
                throw new Error(response.message || 'Erreur lors du chargement des statistiques');
            }
        } catch (error) {
            console.error('Erreur lors de la récupération des statistiques:', error);
            Swal.fire('Erreur', 'Impossible de charger les statistiques: ' + error.message, 'error');
        }
    };

    // Fonction pour supprimer une évaluation
    const handleDeleteEvaluation = async (evaluation) => {
        const className = evaluation.series_subject?.class_series?.school_class?.name ||
                         evaluation.series_subject?.class_series?.name || 'N/A';
        const subjectName = evaluation.series_subject?.subject?.name || 'N/A';

        const result = await Swal.fire({
            title: 'Confirmer la suppression',
            html: `
                <div class="text-left">
                    <p class="mb-3">Êtes-vous sûr de vouloir supprimer l'évaluation :</p>
                    <div class="alert alert-warning">
                        <strong>${evaluation.name}</strong><br>
                        <small class="text-muted">${subjectName} - ${className}</small>
                    </div>
                    <p class="text-danger"><small><i class="bi bi-exclamation-triangle"></i> Cette action est irréversible !</small></p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
            width: '450px'
        });

        if (result.isConfirmed) {
            try {
                // Afficher un loader pendant la suppression
                Swal.fire({
                    title: 'Suppression en cours...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const response = await secureApiEndpoints.evaluations.delete(evaluation.id);
                
                if (response.success) {
                    Swal.fire({
                        title: 'Supprimé !',
                        text: response.message || 'Lévaluation a été supprimée avec succès',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Recharger la liste après suppression
                    loadData();
                } else {
                    throw new Error(response.message || 'Erreur lors de la suppression');
                }
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                
                // Déterminer le type d'erreur pour un meilleur message
                let title = 'Erreur !';
                let icon = 'error';
                let text = error.message || 'Impossible de supprimer lévaluation';
                
                if (error.message && error.message.includes('notes ont été saisies')) {
                    title = 'Suppression impossible';
                    icon = 'warning';
                    text = `Cette évaluation ne peut pas être supprimée car des notes ont déjà été saisies.\n\nPour la supprimer, vous devez d'abord supprimer toutes les notes associées.`;
                } else if (error.message && error.message.includes('pas autorisé')) {
                    title = 'Accès refusé';
                    icon = 'warning';
                    text = 'Vous nêtes pas autorisé à supprimer cette évaluation.';
                }
                
                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonText: 'Compris',
                    width: '450px'
                });
            }
        }
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
                                        Gestion des évaluations - Toutes séquences disponibles
                                    </p>
                                </div>
                                <div>
                                    <Button
                                        variant="light"
                                        size="lg"
                                        onClick={() => navigate('/teacher/evaluations/create')}
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

            {/* Plus besoin de message - toutes les séquences sont accessibles */}

            {/* Statistiques */}
            <Row className="mb-4">
                <Col md={4}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className="text-primary">{evaluations.length}</h3>
                            <small className="text-muted">Total évaluations</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className="text-success">
                                {evaluations.filter(e => e.type === 'composition').length}
                            </h3>
                            <small className="text-muted">Compositions</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="text-center">
                        <Card.Body>
                            <h3 className="text-info">
                                {evaluations.filter(e => e.type === 'tp').length}
                            </h3>
                            <small className="text-muted">Travaux Pratiques</small>
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
                                    {evaluations.length === 0 && (
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
                                            <th className="d-none d-md-table-cell">Type</th>
                                            <th>Matière/Classe</th>
                                            <th className="d-none d-lg-table-cell">Séquence</th>
                                            <th className="d-none d-sm-table-cell text-center">Note/Coeff.</th>
                                            <th style={{width: '180px'}}>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredEvaluations.map(evaluation => (
                                            <tr key={evaluation.id}>
                                                <td>
                                                    <div>
                                                        <strong>{evaluation.name}</strong>
                                                        {evaluation.description && (
                                                            <small className="d-block text-muted">
                                                                {evaluation.description}
                                                            </small>
                                                        )}
                                                        {/* Informations mobiles */}
                                                        <div className="d-md-none mt-1">
                                                            <div className="mb-1">{getTypeDisplay(evaluation.type)}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="d-none d-md-table-cell">{getTypeDisplay(evaluation.type)}</td>
                                                <td>
                                                    <div>
                                                        <strong>{evaluation.series_subject?.subject?.name || 'N/A'}</strong>
                                                        <small className="d-block text-muted">
                                                            {evaluation.series_subject?.class_series?.school_class?.name ||
                                                             evaluation.series_subject?.class_series?.name || 'N/A'}
                                                        </small>
                                                        {/* Info mobile pour note max et coeff */}
                                                        <div className="d-sm-none mt-1">
                                                            <small className="text-muted">
                                                                Note max: {evaluation.max_score} | Coeff: {evaluation.coefficient}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="d-none d-lg-table-cell">
                                                    <small className="text-muted">
                                                        {getSequenceName(evaluation.sequence_id)}
                                                    </small>
                                                </td>
                                                <td className="text-center d-none d-sm-table-cell">
                                                    <div>
                                                        <Badge bg="light" text="dark" className="me-1">
                                                            /{evaluation.max_score}
                                                        </Badge>
                                                        <Badge bg="secondary">
                                                            ×{evaluation.coefficient}
                                                        </Badge>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div className="d-flex flex-column flex-sm-row gap-1">
                                                        <Button 
                                                            variant="outline-info" 
                                                            size="sm"
                                                            className="flex-fill"
                                                            title="Voir détails"
                                                            onClick={() => handleViewDetails(evaluation)}
                                                        >
                                                            <Eye size={14} />
                                                            <span className="d-none d-md-inline ms-1">Détails</span>
                                                        </Button>
                                                        <Button 
                                                            variant="outline-success" 
                                                            size="sm"
                                                            className="flex-fill"
                                                            onClick={() => navigate(`/teacher/evaluations/${evaluation.id}/grades`)}
                                                            title="Saisir notes"
                                                        >
                                                            <Pencil size={14} />
                                                            <span className="d-none d-md-inline ms-1">Notes</span>
                                                        </Button>
                                                        <Button 
                                                            variant="outline-warning" 
                                                            size="sm"
                                                            className="flex-fill"
                                                            title="Statistiques"
                                                            onClick={() => handleViewStats(evaluation)}
                                                        >
                                                            <BarChart size={14} />
                                                            <span className="d-none d-md-inline ms-1">Stats</span>
                                                        </Button>
                                                        <Button 
                                                            variant="outline-danger" 
                                                            size="sm"
                                                            className="flex-fill"
                                                            title="Supprimer"
                                                            onClick={() => handleDeleteEvaluation(evaluation)}
                                                        >
                                                            <Trash size={14} />
                                                            <span className="d-none d-md-inline ms-1">Suppr.</span>
                                                        </Button>
                                                    </div>
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