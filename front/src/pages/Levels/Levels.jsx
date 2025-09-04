import React, { useState, useEffect } from 'react';
import { 
    Card, Row, Col, Button, Alert, Modal, Form, Badge, Table
} from 'react-bootstrap';
import { 
    Award, Plus, Pencil, Trash2, CheckCircle, XCircle, 
    HospitalFill, Archive
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';

const Levels = () => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    // États des données
    const [levels, setLevels] = useState([]);
    const [sections, setSections] = useState([]);

    // États des modals
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedLevel, setSelectedLevel] = useState(null);

    // État du formulaire
    const [levelForm, setLevelForm] = useState({
        name: '',
        section_id: '',
        description: '',
        order: 0,
        is_active: true
    });

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            setLoading(true);
            setError(null);

            // Charger les cycles et sections en parallèle
            const [levelsResponse, sectionsResponse] = await Promise.all([
                secureApiEndpoints.levels.getAll(),
                secureApiEndpoints.sections.getAll()
            ]);

            setLevels(levelsResponse.data || []);
            setSections(sectionsResponse.data || []);

        } catch (error) {
            console.error('Erreur chargement:', error);
            setError('Erreur lors du chargement des données');
        } finally {
            setLoading(false);
        }
    };

    const resetForm = () => {
        setLevelForm({
            name: '',
            section_id: '',
            description: '',
            order: 0,
            is_active: true
        });
    };

    const handleCreateLevel = async () => {
        try {
            setError(null);
            await secureApiEndpoints.levels.create(levelForm);
            setSuccess('Cycle créé avec succès');
            setShowCreateModal(false);
            resetForm();
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur création:', error);
            setError('Erreur lors de la création du cycle');
        }
    };

    const handleEditLevel = (level) => {
        setSelectedLevel(level);
        setLevelForm({
            name: level.name,
            section_id: level.section_id,
            description: level.description || '',
            order: level.order,
            is_active: level.is_active
        });
        setShowEditModal(true);
    };

    const handleUpdateLevel = async () => {
        try {
            setError(null);
            await secureApiEndpoints.levels.update(selectedLevel.id, levelForm);
            setSuccess('Cycle mis à jour avec succès');
            setShowEditModal(false);
            setSelectedLevel(null);
            resetForm();
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur mise à jour:', error);
            setError('Erreur lors de la mise à jour du cycle');
        }
    };

    const handleDeleteLevel = (level) => {
        setSelectedLevel(level);
        setShowDeleteModal(true);
    };

    const confirmDeleteLevel = async () => {
        try {
            setError(null);
            await secureApiEndpoints.levels.delete(selectedLevel.id);
            setSuccess('Cycle supprimé avec succès');
            setShowDeleteModal(false);
            setSelectedLevel(null);
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur suppression:', error);
            setError(error.message || 'Erreur lors de la suppression du cycle');
        }
    };

    const handleToggleStatus = async (level) => {
        try {
            setError(null);
            await secureApiEndpoints.levels.toggleStatus(level.id);
            setSuccess(`Cycle ${level.is_active ? 'désactivé' : 'activé'} avec succès`);
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur changement statut:', error);
            setError('Erreur lors du changement de statut');
        }
    };

    const getSectionName = (sectionId) => {
        const section = sections.find(s => s.id === sectionId);
        return section ? section.name : 'Section inconnue';
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ height: '400px' }}>
                <div className="spinner-border" role="status">
                    <span className="visually-hidden">Chargement...</span>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid">
            {/* En-tête */}
            <Row className="mb-4">
                <Col>
                    <Card className="border-0 shadow-sm">
                        <Card.Body className="bg-primary text-white">
                            <div className="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 className="mb-1">
                                        <Award className="me-2" />
                                        Gestion des Cycles
                                    </h4>
                                    <p className="mb-0 opacity-75">
                                        Gérez les cycles d'enseignement (Primaire, Secondaire, etc.)
                                    </p>
                                </div>
                                <div>
                                    <Button 
                                        variant="light" 
                                        onClick={() => setShowCreateModal(true)}
                                    >
                                        <Plus className="me-1" />
                                        Nouveau Cycle
                                    </Button>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Messages */}
            {error && (
                <Alert variant="danger" dismissible onClose={() => setError(null)}>
                    <XCircle className="me-2" />
                    {error}
                </Alert>
            )}
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess(null)}>
                    <CheckCircle className="me-2" />
                    {success}
                </Alert>
            )}

            {/* Liste des cycles */}
            <Row>
                <Col>
                    <Card>
                        <Card.Header>
                            <h5 className="mb-0">Cycles Existants ({levels.length})</h5>
                        </Card.Header>
                        <Card.Body>
                            {levels.length === 0 ? (
                                <div className="text-center p-4">
                                    <Archive size={48} className="text-muted mb-3" />
                                    <h5 className="text-muted">Aucun cycle configuré</h5>
                                    <p className="text-muted">Créez votre premier cycle pour organiser l'enseignement</p>
                                    <Button variant="primary" onClick={() => setShowCreateModal(true)}>
                                        <Plus className="me-2" />
                                        Créer le premier cycle
                                    </Button>
                                </div>
                            ) : (
                                <div className="table-responsive">
                                    <Table hover>
                                        <thead className="table-light">
                                            <tr>
                                                <th>Nom du Cycle</th>
                                                <th>Section</th>
                                                <th>Description</th>
                                                <th>Ordre</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {levels.map((level) => (
                                                <tr key={level.id}>
                                                    <td>
                                                        <strong>{level.name}</strong>
                                                    </td>
                                                    <td>
                                                        <Badge bg="outline-secondary">
                                                            <HospitalFill size={12} className="me-1" />
                                                            {getSectionName(level.section_id)}
                                                        </Badge>
                                                    </td>
                                                    <td>
                                                        <small className="text-muted">
                                                            {level.description || 'Pas de description'}
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <Badge bg="info">{level.order}</Badge>
                                                    </td>
                                                    <td>
                                                        <Badge bg={level.is_active ? 'success' : 'secondary'}>
                                                            {level.is_active ? 'Actif' : 'Inactif'}
                                                        </Badge>
                                                    </td>
                                                    <td>
                                                        <div className="d-flex gap-1">
                                                            <Button 
                                                                size="sm" 
                                                                variant="outline-primary"
                                                                onClick={() => handleEditLevel(level)}
                                                                title="Modifier"
                                                            >
                                                                <Pencil size={14} />
                                                            </Button>
                                                            <Button 
                                                                size="sm" 
                                                                variant={level.is_active ? 'outline-warning' : 'outline-success'}
                                                                onClick={() => handleToggleStatus(level)}
                                                                title={level.is_active ? 'Désactiver' : 'Activer'}
                                                            >
                                                                {level.is_active ? <XCircle size={14} /> : <CheckCircle size={14} />}
                                                            </Button>
                                                            <Button 
                                                                size="sm" 
                                                                variant="outline-danger"
                                                                onClick={() => handleDeleteLevel(level)}
                                                                title="Supprimer"
                                                            >
                                                                <Trash2 size={14} />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </Table>
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Modal Création */}
            <Modal show={showCreateModal} onHide={() => setShowCreateModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>Créer un nouveau cycle</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Nom du cycle *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={levelForm.name}
                                        onChange={(e) => setLevelForm({ ...levelForm, name: e.target.value })}
                                        placeholder="Ex: Premier Cycle"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Section *</Form.Label>
                                    <Form.Select
                                        value={levelForm.section_id}
                                        onChange={(e) => setLevelForm({ ...levelForm, section_id: e.target.value })}
                                    >
                                        <option value="">Sélectionner une section</option>
                                        {sections.map(section => (
                                            <option key={section.id} value={section.id}>
                                                {section.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Ordre d'affichage</Form.Label>
                                    <Form.Control
                                        type="number"
                                        min="0"
                                        value={levelForm.order}
                                        onChange={(e) => setLevelForm({ ...levelForm, order: parseInt(e.target.value) || 0 })}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Check
                                        type="switch"
                                        id="create-is-active"
                                        label="Cycle actif"
                                        checked={levelForm.is_active}
                                        onChange={(e) => setLevelForm({ ...levelForm, is_active: e.target.checked })}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={12}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Description</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={levelForm.description}
                                        onChange={(e) => setLevelForm({ ...levelForm, description: e.target.value })}
                                        placeholder="Description optionnelle du cycle..."
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </Form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => {
                        setShowCreateModal(false);
                        resetForm();
                    }}>
                        Annuler
                    </Button>
                    <Button variant="primary" onClick={handleCreateLevel}>
                        Créer le Cycle
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Modal Édition */}
            <Modal show={showEditModal} onHide={() => setShowEditModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>Modifier le cycle</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Nom du cycle *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={levelForm.name}
                                        onChange={(e) => setLevelForm({ ...levelForm, name: e.target.value })}
                                        placeholder="Ex: Premier Cycle"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Section *</Form.Label>
                                    <Form.Select
                                        value={levelForm.section_id}
                                        onChange={(e) => setLevelForm({ ...levelForm, section_id: e.target.value })}
                                    >
                                        <option value="">Sélectionner une section</option>
                                        {sections.map(section => (
                                            <option key={section.id} value={section.id}>
                                                {section.name}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Ordre d'affichage</Form.Label>
                                    <Form.Control
                                        type="number"
                                        min="0"
                                        value={levelForm.order}
                                        onChange={(e) => setLevelForm({ ...levelForm, order: parseInt(e.target.value) || 0 })}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Check
                                        type="switch"
                                        id="edit-is-active"
                                        label="Cycle actif"
                                        checked={levelForm.is_active}
                                        onChange={(e) => setLevelForm({ ...levelForm, is_active: e.target.checked })}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={12}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Description</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={levelForm.description}
                                        onChange={(e) => setLevelForm({ ...levelForm, description: e.target.value })}
                                        placeholder="Description optionnelle du cycle..."
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </Form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => {
                        setShowEditModal(false);
                        setSelectedLevel(null);
                        resetForm();
                    }}>
                        Annuler
                    </Button>
                    <Button variant="primary" onClick={handleUpdateLevel}>
                        Mettre à jour
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Modal Suppression */}
            <Modal show={showDeleteModal} onHide={() => setShowDeleteModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title className="text-danger">
                        <Trash2 className="me-2" />
                        Confirmer la suppression
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {selectedLevel && (
                        <div>
                            <Alert variant="warning">
                                <XCircle className="me-2" />
                                <strong>Attention !</strong> Cette action est irréversible.
                            </Alert>
                            <p>
                                Êtes-vous sûr de vouloir supprimer le cycle <strong>"{selectedLevel.name}"</strong> ?
                            </p>
                            <small className="text-muted">
                                Note : Vous ne pouvez pas supprimer un cycle qui contient des classes.
                            </small>
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => {
                        setShowDeleteModal(false);
                        setSelectedLevel(null);
                    }}>
                        Annuler
                    </Button>
                    <Button variant="danger" onClick={confirmDeleteLevel}>
                        <Trash2 className="me-2" />
                        Supprimer définitivement
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    );
};

export default Levels;