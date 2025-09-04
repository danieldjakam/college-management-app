import React, { useState, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { 
    Card, Row, Col, Button, Alert, Modal, Form, Table,
    Badge, ProgressBar, Tabs, Tab, Spinner
} from 'react-bootstrap';
import { 
    Calendar, Clock, Play, Pause, Plus, Pencil, Eye,
    CheckCircle, XCircle, Gear, Book, BarChart
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';

const TrimesterSequenceManagement = () => {
    const { user } = useAuth();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    // États des données
    const [schoolYear, setSchoolYear] = useState(null);
    const [trimesters, setTrimesters] = useState([]);
    const [sequences, setSequences] = useState([]);
    const [evaluationConfigs, setEvaluationConfigs] = useState([]);

    // États des modals
    const [showCreateTrimester, setShowCreateTrimester] = useState(false);
    const [showCreateSequences, setShowCreateSequences] = useState(false);
    const [selectedTrimester, setSelectedTrimester] = useState(null);

    // États des formulaires
    const [trimesterForm, setTrimesterForm] = useState({
        name: '',
        number: 1,
        start_date: '',
        end_date: '',
        is_active: true
    });

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            setLoading(true);
            setError(null);

            // Charger année scolaire courante
            const yearResponse = await secureApiEndpoints.schoolYears.getCurrent();
            setSchoolYear(yearResponse.data);

            // Charger trimestres existants
            const trimestersResponse = await secureApiEndpoints.trimesters.getAll({
                school_year_id: yearResponse.data.id
            });
            setTrimesters(trimestersResponse.data || []);

            // Charger séquences
            const sequencesResponse = await secureApiEndpoints.sequences.getAll({
                school_year_id: yearResponse.data.id
            });
            setSequences(sequencesResponse.data || []);

            // Charger configs d'évaluations par cycle
            const configsResponse = await secureApiEndpoints.evaluationConfigs.getAll();
            setEvaluationConfigs(configsResponse.data || []);

        } catch (error) {
            console.error('Erreur chargement:', error);
            setError('Erreur lors du chargement des données');
        } finally {
            setLoading(false);
        }
    };

    const handleCreateTrimester = async () => {
        try {
            setError(null);
            
            const trimesterData = {
                ...trimesterForm,
                school_year_id: schoolYear.id
            };

            await secureApiEndpoints.trimesters.create(trimesterData);
            
            setSuccess('Trimestre créé avec succès');
            setShowCreateTrimester(false);
            setTrimesterForm({
                name: '',
                number: 1,
                start_date: '',
                end_date: '',
                is_active: true
            });
            
            await loadData();
            setTimeout(() => setSuccess(null), 3000);

        } catch (error) {
            console.error('Erreur création trimestre:', error);
            setError('Erreur lors de la création du trimestre');
        }
    };

    const handleActivateTrimester = async (trimesterId) => {
        try {
            setError(null);
            await secureApiEndpoints.trimesters.activate(trimesterId);
            setSuccess('Trimestre activé avec succès');
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur activation:', error);
            setError('Erreur lors de l\'activation du trimestre');
        }
    };

    const handleActivateSequence = async (sequenceId) => {
        try {
            setError(null);
            await secureApiEndpoints.sequences.activate(sequenceId);
            setSuccess('Séquence activée avec succès');
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur activation séquence:', error);
            setError('Erreur lors de l\'activation de la séquence');
        }
    };

    const generateDefaultTrimesters = async () => {
        try {
            setError(null);
            setLoading(true);

            // Vérifier les trimestres existants pour cette année scolaire
            const existingTrimesters = await secureApiEndpoints.trimesters.getAll({
                school_year_id: schoolYear.id
            });

            const startYear = new Date(schoolYear.start_date).getFullYear();
            const endYear = new Date(schoolYear.end_date).getFullYear();
            
            const defaultTrimesters = [
                {
                    name: 'Premier Trimestre',
                    number: 1,
                    start_date: `${startYear}-09-01`,
                    end_date: `${startYear}-12-15`,
                    is_active: true
                },
                {
                    name: 'Deuxième Trimestre', 
                    number: 2,
                    start_date: `${endYear}-01-08`,
                    end_date: `${endYear}-04-05`,
                    is_active: true
                },
                {
                    name: 'Troisième Trimestre',
                    number: 3, 
                    start_date: `${endYear}-04-22`,
                    end_date: `${endYear}-07-12`,
                    is_active: true
                }
            ];

            // Créer seulement les trimestres qui n'existent pas encore
            for (const trimesterData of defaultTrimesters) {
                const exists = existingTrimesters.data.some(t => t.number === trimesterData.number);
                if (!exists) {
                    await secureApiEndpoints.trimesters.create({
                        ...trimesterData,
                        school_year_id: schoolYear.id
                    });
                }
            }

            // Activer le premier trimestre
            await loadData();
            if (trimesters.length > 0) {
                await handleActivateTrimester(trimesters[0].id);
            }

            setSuccess('Trimestres par défaut créés et configurés');
            setTimeout(() => setSuccess(null), 4000);

        } catch (error) {
            console.error('Erreur génération trimestres:', error);
            setError('Erreur lors de la génération des trimestres');
        } finally {
            setLoading(false);
        }
    };

    const generateSequencesForTrimester = async (trimester) => {
        try {
            setError(null);
            
            // Analyser les configs d'évaluation pour déterminer le nombre de séquences
            const configs = evaluationConfigs.filter(config => config.is_active);
            const sequencesNeeded = new Set();
            
            configs.forEach(config => {
                if (config.evaluation_mode === '1ds_1comp') {
                    // 1 DS = 1 séquence par trimestre
                    sequencesNeeded.add(1);
                } else if (config.evaluation_mode === '2ds_1comp') {
                    // 2 DS = 2 séquences par trimestre  
                    sequencesNeeded.add(1);
                    sequencesNeeded.add(2);
                }
            });

            const numSequences = Math.max(...sequencesNeeded);
            const sequences = [];
            
            for (let i = 1; i <= numSequences; i++) {
                const sequenceNumber = (trimester.number - 1) * 2 + i;
                const duration = (new Date(trimester.end_date) - new Date(trimester.start_date)) / numSequences;
                const startDate = new Date(new Date(trimester.start_date).getTime() + (i - 1) * duration);
                const endDate = new Date(new Date(trimester.start_date).getTime() + i * duration);
                
                sequences.push({
                    name: `Séquence ${sequenceNumber}`,
                    number: sequenceNumber,
                    trimester_id: trimester.id,
                    school_year_id: trimester.school_year_id,
                    start_date: startDate.toISOString().split('T')[0],
                    end_date: endDate.toISOString().split('T')[0],
                    is_active: true,
                    is_current: trimester.number === 1 && i === 1
                });
            }

            for (const sequenceData of sequences) {
                await secureApiEndpoints.sequences.create(sequenceData);
            }

            setSuccess(`Séquences générées pour ${trimester.name}`);
            await loadData();
            setTimeout(() => setSuccess(null), 3000);

        } catch (error) {
            console.error('Erreur génération séquences:', error);
            setError('Erreur lors de la génération des séquences');
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'Non définie';
        return new Date(dateString).toLocaleDateString('fr-FR');
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <div className="text-center">
                    <Spinner animation="border" variant="primary" />
                    <p className="mt-2">Chargement...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid p-4">
            {/* En-tête */}
            <Row className="mb-4">
                <Col>
                    <Card className="bg-dark text-white">
                        <Card.Body>
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 className="mb-1">
                                        <Gear className="me-2" />
                                        Gestion des Trimestres & Séquences
                                    </h4>
                                    <p className="mb-0 opacity-75">
                                        Configuration académique - {schoolYear?.name}
                                    </p>
                                </div>
                                <div className="d-flex gap-2">
                                    {trimesters.length === 0 ? (
                                        <Button 
                                            variant="success" 
                                            onClick={generateDefaultTrimesters}
                                        >
                                            <Plus className="me-1" />
                                            Générer Trimestres
                                        </Button>
                                    ) : (
                                        <Button 
                                            variant="outline-light" 
                                            onClick={() => setShowCreateTrimester(true)}
                                        >
                                            <Plus className="me-1" />
                                            Nouveau Trimestre
                                        </Button>
                                    )}
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

            {/* Onglets */}
            <Tabs defaultActiveKey="trimesters" className="mb-4">
                
                {/* Onglet Trimestres */}
                <Tab eventKey="trimesters" title={
                    <span>
                        <Calendar className="me-1" />
                        Trimestres ({trimesters.length})
                    </span>
                }>
                    {trimesters.length === 0 ? (
                        <Card>
                            <Card.Body className="text-center p-5">
                                <Calendar size={48} className="text-muted mb-3" />
                                <h5 className="text-muted">Aucun trimestre configuré</h5>
                                <p className="text-muted mb-4">
                                    Générez les trimestres par défaut pour l'année {schoolYear?.name}
                                </p>
                                <Button variant="primary" onClick={generateDefaultTrimesters}>
                                    <Plus className="me-2" />
                                    Générer les 3 trimestres
                                </Button>
                            </Card.Body>
                        </Card>
                    ) : (
                        <Row>
                            {trimesters.map((trimester) => (
                                <Col lg={4} key={trimester.id} className="mb-4">
                                    <Card className={trimester.is_current ? 'border-success' : ''}>
                                        <Card.Header className={trimester.is_current ? 'bg-success text-white' : 'bg-light'}>
                                            <div className="d-flex justify-content-between align-items-center">
                                                <h5 className="mb-0">
                                                    {trimester.name}
                                                    {trimester.is_current && (
                                                        <Badge bg="light" text="success" className="ms-2">Actuel</Badge>
                                                    )}
                                                </h5>
                                                <small>Trimestre {trimester.number}</small>
                                            </div>
                                        </Card.Header>
                                        <Card.Body>
                                            <div className="mb-3">
                                                <small className="text-muted">Période</small>
                                                <div>
                                                    <Calendar className="me-1" />
                                                    {formatDate(trimester.start_date)} - {formatDate(trimester.end_date)}
                                                </div>
                                            </div>

                                            <div className="mb-3">
                                                <small className="text-muted">Séquences</small>
                                                <div>
                                                    {trimester.sequences?.length > 0 ? (
                                                        <Badge bg="info">{trimester.sequences.length} séquence(s)</Badge>
                                                    ) : (
                                                        <Button 
                                                            size="sm" 
                                                            variant="outline-primary"
                                                            onClick={() => generateSequencesForTrimester(trimester)}
                                                        >
                                                            <Plus className="me-1" />
                                                            Générer séquences
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="d-flex justify-content-between">
                                                {trimester.is_current ? (
                                                    <Badge bg="success">Trimestre actuel</Badge>
                                                ) : (
                                                    <Button 
                                                        size="sm" 
                                                        variant="outline-success"
                                                        onClick={() => handleActivateTrimester(trimester.id)}
                                                    >
                                                        <Play className="me-1" />
                                                        Activer
                                                    </Button>
                                                )}
                                            </div>
                                        </Card.Body>
                                    </Card>
                                </Col>
                            ))}
                        </Row>
                    )}
                </Tab>

                {/* Onglet Séquences */}
                <Tab eventKey="sequences" title={
                    <span>
                        <Clock className="me-1" />
                        Séquences ({sequences.length})
                    </span>
                }>
                    {sequences.length === 0 ? (
                        <Card>
                            <Card.Body className="text-center p-5">
                                <Clock size={48} className="text-muted mb-3" />
                                <h5 className="text-muted">Aucune séquence configurée</h5>
                                <p className="text-muted">
                                    Créez d'abord les trimestres pour générer automatiquement les séquences
                                </p>
                            </Card.Body>
                        </Card>
                    ) : (
                        <Card>
                            <Card.Body>
                                <div className="table-responsive">
                                    <Table hover>
                                        <thead className="table-dark">
                                            <tr>
                                                <th>Séquence</th>
                                                <th>Trimestre</th>
                                                <th>Période</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {sequences.map((sequence) => (
                                                <tr key={sequence.id}>
                                                    <td>
                                                        <strong>{sequence.name}</strong>
                                                        <div className="small text-muted">
                                                            Séquence #{sequence.number}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <Badge bg="outline-secondary">
                                                            {sequence.trimester?.name}
                                                        </Badge>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            {formatDate(sequence.start_date)} <br />
                                                            {formatDate(sequence.end_date)}
                                                        </small>
                                                    </td>
                                                    <td>
                                                        {sequence.is_current ? (
                                                            <Badge bg="success">En cours</Badge>
                                                        ) : sequence.is_active ? (
                                                            <Badge bg="secondary">Programmée</Badge>
                                                        ) : (
                                                            <Badge bg="light" text="dark">Inactive</Badge>
                                                        )}
                                                    </td>
                                                    <td>
                                                        <div className="d-flex gap-1">
                                                            {!sequence.is_current && sequence.is_active && (
                                                                <Button 
                                                                    size="sm" 
                                                                    variant="outline-success"
                                                                    onClick={() => handleActivateSequence(sequence.id)}
                                                                >
                                                                    <Play size={14} />
                                                                </Button>
                                                            )}
                                                            <Button size="sm" variant="outline-primary">
                                                                <Eye size={14} />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </Table>
                                </div>
                            </Card.Body>
                        </Card>
                    )}
                </Tab>

                {/* Onglet Configuration */}
                <Tab eventKey="config" title={
                    <span>
                        <Book className="me-1" />
                        Configuration Cycles
                    </span>
                }>
                    <Row>
                        <Col md={8}>
                            <Card>
                                <Card.Header>
                                    <h5 className="mb-0">Configurations d'évaluations par cycle</h5>
                                </Card.Header>
                                <Card.Body>
                                    {evaluationConfigs.length > 0 ? (
                                        <div className="table-responsive">
                                            <Table size="sm">
                                                <thead>
                                                    <tr>
                                                        <th>Cycle</th>
                                                        <th>Type Éval</th>
                                                        <th>Coefficient</th>
                                                        <th>Note Max</th>
                                                        <th>Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {evaluationConfigs.map((config) => (
                                                        <tr key={config.id}>
                                                            <td>
                                                                <strong>{config.level?.name}</strong>
                                                            </td>
                                                            <td>
                                                                <Badge bg="info">{config.evaluation_type}</Badge>
                                                            </td>
                                                            <td>{config.coefficient}</td>
                                                            <td>{config.max_score}</td>
                                                            <td>
                                                                {config.is_active ? (
                                                                    <Badge bg="success">Actif</Badge>
                                                                ) : (
                                                                    <Badge bg="secondary">Inactif</Badge>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </Table>
                                        </div>
                                    ) : (
                                        <div className="text-center p-4">
                                            <Book size={32} className="text-muted mb-2" />
                                            <p className="text-muted">
                                                Aucune configuration trouvée. 
                                                <br />
                                                Configurez d'abord les évaluations par cycle.
                                            </p>
                                        </div>
                                    )}
                                </Card.Body>
                            </Card>
                        </Col>
                        <Col md={4}>
                            <Alert variant="info">
                                <h6><Book className="me-1" />Information</h6>
                                <p className="small mb-2">
                                    Les configurations d'évaluations par cycle sont utilisées 
                                    pour définir les types et barèmes des évaluations.
                                </p>
                                <ul className="small mb-0">
                                    <li>Premier cycle: 6e, 5e, 4e, 3e</li>
                                    <li>Second cycle: 2de, 1re, Tle</li>
                                    <li>Coefficients selon le type d'évaluation</li>
                                </ul>
                            </Alert>
                        </Col>
                    </Row>
                </Tab>
            </Tabs>

            {/* Modal Création Trimestre */}
            <Modal show={showCreateTrimester} onHide={() => setShowCreateTrimester(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Créer un nouveau trimestre</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Nom du trimestre</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={trimesterForm.name}
                                        onChange={(e) => setTrimesterForm({
                                            ...trimesterForm,
                                            name: e.target.value
                                        })}
                                        placeholder="ex: Premier Trimestre"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Numéro</Form.Label>
                                    <Form.Select
                                        value={trimesterForm.number}
                                        onChange={(e) => setTrimesterForm({
                                            ...trimesterForm,
                                            number: parseInt(e.target.value)
                                        })}
                                    >
                                        <option value={1}>1er Trimestre</option>
                                        <option value={2}>2e Trimestre</option>
                                        <option value={3}>3e Trimestre</option>
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Date de début</Form.Label>
                                    <Form.Control
                                        type="date"
                                        value={trimesterForm.start_date}
                                        onChange={(e) => setTrimesterForm({
                                            ...trimesterForm,
                                            start_date: e.target.value
                                        })}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Date de fin</Form.Label>
                                    <Form.Control
                                        type="date"
                                        value={trimesterForm.end_date}
                                        onChange={(e) => setTrimesterForm({
                                            ...trimesterForm,
                                            end_date: e.target.value
                                        })}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Form.Check
                            type="checkbox"
                            label="Trimestre actif"
                            checked={trimesterForm.is_active}
                            onChange={(e) => setTrimesterForm({
                                ...trimesterForm,
                                is_active: e.target.checked
                            })}
                        />
                    </Form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowCreateTrimester(false)}>
                        Annuler
                    </Button>
                    <Button variant="primary" onClick={handleCreateTrimester}>
                        Créer Trimestre
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Guide d'utilisation */}
            {trimesters.length > 0 && (
                <Alert variant="success" className="mt-4">
                    <h6>
                        <CheckCircle className="me-2" />
                        Système configuré !
                    </h6>
                    <p className="mb-2">
                        Les trimestres sont configurés. Les professeurs peuvent maintenant :
                    </p>
                    <ul className="mb-0 small">
                        <li>Voir leurs trimestres selon leurs classes assignées</li>
                        <li>Accéder aux séquences en cours pour la saisie de notes</li>
                        <li>Suivre la progression des évaluations</li>
                    </ul>
                </Alert>
            )}
        </div>
    );
};

export default TrimesterSequenceManagement;