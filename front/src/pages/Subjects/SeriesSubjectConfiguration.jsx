import React, { useState, useEffect } from 'react';
import { Button, Modal, Form, Alert, Badge, Card, Table, Row, Col } from 'react-bootstrap';
import { PlusCircle, PencilFill, Trash2, JournalBookmarkFill, HouseHeartFill, Save, X } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { extractErrorMessage } from '../../utils/errorHandler';
import Swal from 'sweetalert2';

const SeriesSubjectConfiguration = () => {
    const [loading, setLoading] = useState(false);
    const [classSeries, setClassSeries] = useState([]); // Changed from schoolClasses to classSeries
    const [subjects, setSubjects] = useState([]);
    const [seriesSubjects, setSeriesSubjects] = useState([]);
    const [showModal, setShowModal] = useState(false);
    const [selectedSeries, setSelectedSeries] = useState(null);
    const [availableSubjects, setAvailableSubjects] = useState([]);
    const [formData, setFormData] = useState({
        subject_id: '',
        coefficient: 1.0
    });

    // États pour la modification
    const [editingConfig, setEditingConfig] = useState(null);
    const [editCoefficient, setEditCoefficient] = useState('');

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            setLoading(true);
            const [classSeriesRes, subjectsRes] = await Promise.all([
                secureApiEndpoints.classSeries.getAll(), // Changed from schoolClasses to classSeries
                secureApiEndpoints.subjects.getAll({ active: true })
            ]);

            if (classSeriesRes.success) setClassSeries(classSeriesRes.data);
            if (subjectsRes.success) setSubjects(subjectsRes.data);

            // Charger les configurations existantes
            loadSeriesSubjects();
        } catch (error) {
            console.error('Erreur lors du chargement des données:', error);
            Swal.fire('Erreur', 'Impossible de charger les données', 'error');
        } finally {
            setLoading(false);
        }
    };

    const loadSeriesSubjects = async () => {
        try {
            const response = await secureApiEndpoints.seriesSubjects.getAll({ active: true });
            if (response.success) {
                setSeriesSubjects(response.data);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des configurations:', error);
        }
    };

    const handleConfigureSeries = (series) => {
        setSelectedSeries(series);

        // Filtrer les matières déjà configurées pour cette série
        const configuredSubjectIds = seriesSubjects
            .filter(ss => ss.class_series_id === series.id) // Changed from school_class_id to class_series_id
            .map(ss => ss.subject_id);

        const available = subjects.filter(subject =>
            !configuredSubjectIds.includes(subject.id)
        );

        setAvailableSubjects(available);
        setFormData({ subject_id: '', coefficient: 1.0 });
        setShowModal(true);
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setSelectedSeries(null);
        setAvailableSubjects([]);
        setFormData({ subject_id: '', coefficient: 1.0 });
    };

    const handleAddSubject = async (e) => {
        e.preventDefault();

        if (!selectedSeries || !formData.subject_id) {
            Swal.fire('Erreur', 'Veuillez sélectionner une matière', 'error');
            return;
        }

        try {
            const response = await secureApiEndpoints.seriesSubjects.create({
                class_series_id: selectedSeries.id, // Changed from school_class_id to class_series_id
                subject_id: formData.subject_id,
                coefficient: formData.coefficient
            });

            if (response.success) {
                Swal.fire('Succès!', response.message || 'Matière ajoutée à la série avec succès', 'success');
                handleCloseModal();
                loadData();
            } else {
                Swal.fire('Erreur', response.message || 'Erreur lors de l\'ajout', 'error');
            }
        } catch (error) {
            const errorMessage = extractErrorMessage(error, 'Erreur lors de l\'ajout de la matière');
            Swal.fire('Erreur', errorMessage, 'error');
        }
    };

    const handleEditCoefficient = (config) => {
        setEditingConfig(config.id);
        setEditCoefficient(config.coefficient);
    };

    const handleCancelEdit = () => {
        setEditingConfig(null);
        setEditCoefficient('');
    };

    const handleSaveCoefficient = async (configId) => {
        if (!editCoefficient || editCoefficient < 0.5 || editCoefficient > 10) {
            Swal.fire('Erreur', 'Le coefficient doit être entre 0.5 et 10', 'error');
            return;
        }

        try {
            const response = await secureApiEndpoints.seriesSubjects.update(configId, {
                coefficient: parseFloat(editCoefficient)
            });

            if (response.success) {
                Swal.fire('Succès!', 'Coefficient modifié avec succès', 'success');
                setEditingConfig(null);
                setEditCoefficient('');
                loadData();
            } else {
                Swal.fire('Erreur', response.message || 'Erreur lors de la modification', 'error');
            }
        } catch (error) {
            const errorMessage = extractErrorMessage(error, 'Erreur lors de la modification du coefficient');
            Swal.fire('Erreur', errorMessage, 'error');
        }
    };

    const handleRemoveSubject = async (seriesId, subjectId) => {
        try {
            const result = await Swal.fire({
                title: 'Confirmation',
                text: 'Êtes-vous sûr de vouloir retirer cette matière de la série ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, retirer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#d33'
            });

            if (result.isConfirmed) {
                // Trouver l'ID de la configuration à supprimer
                const configToDelete = seriesSubjects.find(
                    ss => ss.class_series_id === seriesId && ss.subject_id === subjectId // Changed from school_class_id
                );

                if (configToDelete) {
                    try {
                        const response = await secureApiEndpoints.seriesSubjects.delete(configToDelete.id);
                        if (response.success) {
                            Swal.fire('Supprimé!', response.message || 'Matière retirée de la série', 'success');
                            loadData();
                        } else {
                            Swal.fire('Erreur', response.message || 'Erreur lors de la suppression', 'error');
                        }
                    } catch (apiError) {
                        const errorMessage = extractErrorMessage(apiError, 'Erreur lors de la suppression de la configuration');
                        Swal.fire('Erreur', errorMessage, 'error');
                    }
                } else {
                    Swal.fire('Erreur', 'Configuration introuvable', 'error');
                }
            }
        } catch (error) {
            Swal.fire('Erreur', 'Une erreur est survenue', 'error');
        }
    };

    const getSeriesSubjects = (seriesId) => {
        return seriesSubjects
            .filter(ss => ss.class_series_id === seriesId) // Changed from school_class_id
            .map(ss => ({
                ...ss,
                subject: subjects.find(s => s.id === ss.subject_id)
            }));
    };

    const getSubjectName = (subjectId) => {
        const subject = subjects.find(s => s.id === subjectId);
        return subject ? subject.name : 'Matière inconnue';
    };

    if (loading) {
        return (
            <div className="container-fluid py-4">
                <div className="text-center">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid py-4">
            {/* Header */}
            <div className="mb-4">
                <h2>Configuration des Matières par Série</h2>
                <p className="text-muted">
                    Définissez quelles matières sont enseignées dans chaque série et leurs coefficients
                </p>
            </div>

            <Alert variant="info" className="mb-4">
                <strong>Étape importante :</strong> Vous devez d'abord configurer les matières pour chaque série 
                avant de pouvoir affecter des enseignants. Le coefficient défini ici sera utilisé pour tous 
                les enseignants de cette matière dans cette série.
            </Alert>

            {/* Configuration par série */}
            <div className="row g-4">
                {classSeries.map((series) => {
                    const configuredSubjects = getSeriesSubjects(series.id);

                    return (
                        <div key={series.id} className="col-md-6 col-lg-4">
                            <Card className="h-100">
                                <Card.Header className="d-flex justify-content-between align-items-center">
                                    <div className="d-flex align-items-center">
                                        <HouseHeartFill className="text-primary me-2" />
                                        <strong>{series.name}</strong>
                                        {series.school_class && (
                                            <Badge bg="info" className="ms-2">{series.school_class.name}</Badge>
                                        )}
                                    </div>
                                    <Button
                                        variant="outline-primary"
                                        size="sm"
                                        onClick={() => handleConfigureSeries(series)}
                                    >
                                        <PlusCircle size={14} className="me-1" />
                                        Ajouter Matière
                                    </Button>
                                </Card.Header>
                                <Card.Body>
                                    {configuredSubjects.length > 0 ? (
                                        <div className="space-y-2">
                                            {configuredSubjects.map((config) => (
                                                <div key={config.subject_id} className="p-2 bg-light rounded mb-2">
                                                    <div className="d-flex justify-content-between align-items-start">
                                                        <div className="flex-grow-1">
                                                            <strong className="text-primary d-block">
                                                                {config.subject?.name || getSubjectName(config.subject_id)}
                                                            </strong>
                                                            {editingConfig === config.id ? (
                                                                <div className="d-flex align-items-center gap-2 mt-2">
                                                                    <Form.Control
                                                                        type="number"
                                                                        size="sm"
                                                                        step="0.5"
                                                                        min="0.5"
                                                                        max="10"
                                                                        value={editCoefficient}
                                                                        onChange={(e) => setEditCoefficient(e.target.value)}
                                                                        style={{ width: '80px' }}
                                                                    />
                                                                    <Button
                                                                        variant="success"
                                                                        size="sm"
                                                                        onClick={() => handleSaveCoefficient(config.id)}
                                                                    >
                                                                        <Save size={12} />
                                                                    </Button>
                                                                    <Button
                                                                        variant="secondary"
                                                                        size="sm"
                                                                        onClick={handleCancelEdit}
                                                                    >
                                                                        <X size={12} />
                                                                    </Button>
                                                                </div>
                                                            ) : (
                                                                <Badge bg="secondary" className="mt-1">
                                                                    Coeff: {config.coefficient}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        {editingConfig !== config.id && (
                                                            <div className="d-flex gap-1">
                                                                <Button
                                                                    variant="outline-primary"
                                                                    size="sm"
                                                                    onClick={() => handleEditCoefficient(config)}
                                                                    title="Modifier le coefficient"
                                                                >
                                                                    <PencilFill size={12} />
                                                                </Button>
                                                                <Button
                                                                    variant="outline-danger"
                                                                    size="sm"
                                                                    onClick={() => handleRemoveSubject(series.id, config.subject_id)}
                                                                    title="Supprimer"
                                                                >
                                                                    <Trash2 size={12} />
                                                                </Button>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center text-muted py-4">
                                            <JournalBookmarkFill size={24} className="mb-2" />
                                            <br />
                                            <small>Aucune matière configurée</small>
                                            <br />
                                            <small className="text-info">
                                                Cliquez sur "Ajouter Matière" pour commencer
                                            </small>
                                        </div>
                                    )}
                                </Card.Body>
                            </Card>
                        </div>
                    );
                })}
            </div>

            {/* Modal d'ajout de matière */}
            <Modal show={showModal} onHide={handleCloseModal}>
                <Modal.Header closeButton>
                    <Modal.Title>
                        Ajouter une matière à {selectedSeries?.name}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleAddSubject}>
                    <Modal.Body>
                        <Row>
                            <Col md={8}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Matière <span className="text-danger">*</span></Form.Label>
                                    <Form.Select
                                        value={formData.subject_id}
                                        onChange={(e) => setFormData(prev => ({ ...prev, subject_id: e.target.value }))}
                                        required
                                    >
                                        <option value="">Sélectionner une matière...</option>
                                        {availableSubjects.map((subject) => (
                                            <option key={subject.id} value={subject.id}>
                                                {subject.name} ({subject.code})
                                            </option>
                                        ))}
                                    </Form.Select>
                                    {availableSubjects.length === 0 && (
                                        <Form.Text className="text-warning">
                                            Toutes les matières sont déjà configurées pour cette série
                                        </Form.Text>
                                    )}
                                </Form.Group>
                            </Col>
                            <Col md={4}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Coefficient</Form.Label>
                                    <Form.Control
                                        type="number"
                                        min="0.5"
                                        max="10"
                                        step="0.5"
                                        value={formData.coefficient}
                                        onChange={(e) => setFormData(prev => ({ 
                                            ...prev, 
                                            coefficient: parseFloat(e.target.value) || 1.0 
                                        }))}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        
                        <Alert variant="info" className="small">
                            <strong>Information :</strong> Ce coefficient sera appliqué à tous les enseignants 
                            qui enseigneront cette matière dans cette série.
                        </Alert>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleCloseModal}>
                            <X className="me-2" />
                            Annuler
                        </Button>
                        <Button 
                            variant="primary" 
                            type="submit"
                            disabled={!formData.subject_id || availableSubjects.length === 0}
                        >
                            <Save className="me-2" />
                            Ajouter
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </div>
    );
};

export default SeriesSubjectConfiguration;