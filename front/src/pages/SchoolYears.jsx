import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Table, Modal, Form, Alert, Badge, Spinner, ProgressBar } from 'react-bootstrap';
import { Plus, Pencil, Star, StarFill, ArrowRepeat, CheckCircle, ExclamationTriangle } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../utils/apiMigration';

const SchoolYears = () => {
    const [schoolYears, setSchoolYears] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingYear, setEditingYear] = useState(null);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [formData, setFormData] = useState({
        name: '',
        start_date: '',
        end_date: '',
        is_current: false
    });

    // Transition state
    const [showTransitionModal, setShowTransitionModal] = useState(false);
    const [transitionSourceYear, setTransitionSourceYear] = useState(null);
    const [transitionPreview, setTransitionPreview] = useState(null);
    const [transitionLoading, setTransitionLoading] = useState(false);
    const [transitionExecuting, setTransitionExecuting] = useState(false);
    const [transitionStep, setTransitionStep] = useState('form'); // form, preview, result
    const [transitionResult, setTransitionResult] = useState(null);
    const [transitionForm, setTransitionForm] = useState({
        name: '',
        start_date: '',
        end_date: '',
        copy_teacher_assignments: true,
        copy_main_teachers: true,
        promote_students: false,
    });

    useEffect(() => {
        loadSchoolYears();
    }, []);

    const loadSchoolYears = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.schoolYears.getAll();
            setSchoolYears(response.data);
        } catch (error) {
            setError('Erreur lors du chargement des annees scolaires: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const handleShowModal = (year = null) => {
        if (year) {
            setEditingYear(year);
            setFormData({
                name: year.name,
                start_date: year.start_date,
                end_date: year.end_date,
                is_current: year.is_current
            });
        } else {
            setEditingYear(null);
            setFormData({
                name: '',
                start_date: '',
                end_date: '',
                is_current: false
            });
        }
        setShowModal(true);
        setError('');
        setSuccess('');
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setEditingYear(null);
        setError('');
        setSuccess('');
    };

    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        try {
            if (editingYear) {
                await secureApiEndpoints.schoolYears.update(editingYear.id, formData);
                setSuccess('Annee scolaire modifiee avec succes');
            } else {
                await secureApiEndpoints.schoolYears.create(formData);
                setSuccess('Annee scolaire ajoutee avec succes');
            }

            await loadSchoolYears();
            setTimeout(() => {
                handleCloseModal();
                setSuccess('');
            }, 1500);
        } catch (error) {
            setError('Erreur: ' + error.message);
        }
    };

    const handleSetCurrent = async (yearId) => {
        try {
            await secureApiEndpoints.schoolYears.setCurrent(yearId);
            setSuccess('Annee scolaire courante definie avec succes');
            await loadSchoolYears();
            setTimeout(() => setSuccess(''), 3000);
        } catch (error) {
            setError('Erreur lors de la definition de l\'annee courante: ' + error.message);
            setTimeout(() => setError(''), 5000);
        }
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fr-FR');
    };

    // === Transition functions ===

    const suggestNewYearName = (sourceYear) => {
        // Try to extract years from name like "2025-2026"
        const match = sourceYear.name.match(/(\d{4})-(\d{4})/);
        if (match) {
            const nextStart = parseInt(match[2]);
            return `${nextStart}-${nextStart + 1}`;
        }
        return '';
    };

    const suggestStartDate = (sourceYear) => {
        if (sourceYear.start_date) {
            const d = new Date(sourceYear.start_date);
            d.setFullYear(d.getFullYear() + 1);
            return d.toISOString().split('T')[0];
        }
        return '';
    };

    const suggestEndDate = (sourceYear) => {
        if (sourceYear.end_date) {
            const d = new Date(sourceYear.end_date);
            d.setFullYear(d.getFullYear() + 1);
            return d.toISOString().split('T')[0];
        }
        return '';
    };

    const handleOpenTransition = (year) => {
        setTransitionSourceYear(year);
        setTransitionForm({
            name: suggestNewYearName(year),
            start_date: suggestStartDate(year),
            end_date: suggestEndDate(year),
            copy_teacher_assignments: true,
            copy_main_teachers: true,
            promote_students: false,
        });
        setTransitionStep('form');
        setTransitionPreview(null);
        setTransitionResult(null);
        setShowTransitionModal(true);
        setError('');
    };

    const handleTransitionFormChange = (e) => {
        const { name, value, type, checked } = e.target;
        setTransitionForm(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
    };

    const handlePreviewTransition = async () => {
        try {
            setTransitionLoading(true);
            setError('');
            const response = await secureApiEndpoints.schoolYears.previewTransition(transitionSourceYear.id);
            setTransitionPreview(response.data);
            setTransitionStep('preview');
        } catch (err) {
            setError('Erreur lors du preview: ' + (err.message || 'Erreur inconnue'));
        } finally {
            setTransitionLoading(false);
        }
    };

    const handleExecuteTransition = async () => {
        try {
            setTransitionExecuting(true);
            setError('');
            const response = await secureApiEndpoints.schoolYears.executeTransition(
                transitionSourceYear.id,
                transitionForm
            );
            setTransitionResult(response.data);
            setTransitionStep('result');
            await loadSchoolYears();
        } catch (err) {
            setError('Erreur lors de la transition: ' + (err.message || 'Erreur inconnue'));
        } finally {
            setTransitionExecuting(false);
        }
    };

    const handleCloseTransition = () => {
        setShowTransitionModal(false);
        setTransitionSourceYear(null);
        setTransitionPreview(null);
        setTransitionResult(null);
        setTransitionStep('form');
        setError('');
    };

    // Find the current year for the transition button
    const currentYear = schoolYears.find(y => y.is_current);

    if (loading) {
        return (
            <Container fluid className="py-4">
                <div className="text-center">
                    <div className="spinner-border" role="status">
                        <span className="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </Container>
        );
    }

    return (
        <Container fluid className="py-4">
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <h2>Gestion des Annees Scolaires</h2>
                        <div className="d-flex gap-2">
                            {currentYear && (
                                <Button
                                    variant="success"
                                    onClick={() => handleOpenTransition(currentYear)}
                                >
                                    <ArrowRepeat size={18} className="me-2" />
                                    Passage a la nouvelle annee
                                </Button>
                            )}
                            <Button
                                variant="primary"
                                onClick={() => handleShowModal()}
                            >
                                <Plus size={18} className="me-2" />
                                Ajouter une annee
                            </Button>
                        </div>
                    </div>
                </Col>
            </Row>

            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')}>
                    {error}
                </Alert>
            )}

            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess('')}>
                    {success}
                </Alert>
            )}

            <Row>
                <Col>
                    <Card>
                        <Card.Body>
                            <Table responsive hover>
                                <thead>
                                    <tr>
                                        <th>Nom de l'annee</th>
                                        <th>Date de debut</th>
                                        <th>Date de fin</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {schoolYears.map(year => (
                                        <tr key={year.id}>
                                            <td>
                                                <strong>{year.name}</strong>
                                                {year.is_current && (
                                                    <Badge bg="success" className="ms-2">
                                                        <StarFill size={12} className="me-1" />
                                                        Annee courante
                                                    </Badge>
                                                )}
                                            </td>
                                            <td>{formatDate(year.start_date)}</td>
                                            <td>{formatDate(year.end_date)}</td>
                                            <td>
                                                <Badge bg={year.is_active ? 'success' : 'secondary'}>
                                                    {year.is_active ? 'Active' : 'Inactive'}
                                                </Badge>
                                            </td>
                                            <td>
                                                <div className="d-flex gap-2">
                                                    <Button
                                                        variant="outline-primary"
                                                        size="sm"
                                                        onClick={() => handleShowModal(year)}
                                                        title="Modifier"
                                                    >
                                                        <Pencil size={14} />
                                                    </Button>
                                                    {!year.is_current && (
                                                        <Button
                                                            variant="outline-success"
                                                            size="sm"
                                                            onClick={() => handleSetCurrent(year.id)}
                                                            title="Definir comme annee courante"
                                                        >
                                                            <Star size={14} />
                                                        </Button>
                                                    )}
                                                    {year.is_current && (
                                                        <Button
                                                            variant="outline-warning"
                                                            size="sm"
                                                            onClick={() => handleOpenTransition(year)}
                                                            title="Transition vers nouvelle annee"
                                                        >
                                                            <ArrowRepeat size={14} />
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>

                            {schoolYears.length === 0 && (
                                <div className="text-center py-4">
                                    <p className="text-muted">Aucune annee scolaire trouvee</p>
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Modal Ajouter/Modifier */}
            <Modal show={showModal} onHide={handleCloseModal} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        {editingYear ? 'Modifier l\'annee scolaire' : 'Ajouter une annee scolaire'}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleSubmit}>
                    <Modal.Body>
                        {error && <Alert variant="danger">{error}</Alert>}
                        {success && <Alert variant="success">{success}</Alert>}

                        <Row>
                            <Col md={12}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Nom de l'annee *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        name="name"
                                        value={formData.name}
                                        onChange={handleInputChange}
                                        placeholder="Ex: 2024-2025"
                                        required
                                    />
                                </Form.Group>
                            </Col>
                        </Row>

                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Date de debut *</Form.Label>
                                    <Form.Control
                                        type="date"
                                        name="start_date"
                                        value={formData.start_date}
                                        onChange={handleInputChange}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Date de fin *</Form.Label>
                                    <Form.Control
                                        type="date"
                                        name="end_date"
                                        value={formData.end_date}
                                        onChange={handleInputChange}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                        </Row>

                        <Row>
                            <Col md={12}>
                                <Form.Group className="mb-3">
                                    <Form.Check
                                        type="checkbox"
                                        name="is_current"
                                        checked={formData.is_current}
                                        onChange={handleInputChange}
                                        label="Definir comme annee scolaire courante"
                                    />
                                    <Form.Text className="text-muted">
                                        Si cochee, cette annee deviendra l'annee de reference par defaut
                                    </Form.Text>
                                </Form.Group>
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleCloseModal}>
                            Annuler
                        </Button>
                        <Button variant="primary" type="submit">
                            {editingYear ? 'Modifier' : 'Ajouter'}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* Modal Transition */}
            <Modal show={showTransitionModal} onHide={handleCloseTransition} size="lg" backdrop="static">
                <Modal.Header closeButton>
                    <Modal.Title>
                        {transitionStep === 'form' && 'Passage a la nouvelle annee scolaire'}
                        {transitionStep === 'preview' && 'Confirmation de la transition'}
                        {transitionStep === 'result' && 'Transition terminee'}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {error && <Alert variant="danger" dismissible onClose={() => setError('')}>{error}</Alert>}

                    {/* Step 1: Form */}
                    {transitionStep === 'form' && transitionSourceYear && (
                        <>
                            <Alert variant="info">
                                <strong>Annee source :</strong> {transitionSourceYear.name}
                                <br />
                                <small>Les classes, matieres, coefficients et affectations seront copies vers la nouvelle annee.</small>
                            </Alert>

                            <h6 className="mb-3">Nouvelle annee scolaire</h6>

                            <Row>
                                <Col md={12}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Nom *</Form.Label>
                                        <Form.Control
                                            type="text"
                                            name="name"
                                            value={transitionForm.name}
                                            onChange={handleTransitionFormChange}
                                            placeholder="Ex: 2026-2027"
                                            required
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Row>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Date de debut *</Form.Label>
                                        <Form.Control
                                            type="date"
                                            name="start_date"
                                            value={transitionForm.start_date}
                                            onChange={handleTransitionFormChange}
                                            required
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Date de fin *</Form.Label>
                                        <Form.Control
                                            type="date"
                                            name="end_date"
                                            value={transitionForm.end_date}
                                            onChange={handleTransitionFormChange}
                                            required
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>

                            <hr />
                            <h6 className="mb-3">Options de copie</h6>

                            <Form.Group className="mb-2">
                                <Form.Check
                                    type="checkbox"
                                    name="copy_teacher_assignments"
                                    checked={transitionForm.copy_teacher_assignments}
                                    onChange={handleTransitionFormChange}
                                    label="Copier les affectations des enseignants"
                                />
                                <Form.Text className="text-muted">
                                    Chaque enseignant gardera les memes matieres et classes
                                </Form.Text>
                            </Form.Group>

                            <Form.Group className="mb-2">
                                <Form.Check
                                    type="checkbox"
                                    name="copy_main_teachers"
                                    checked={transitionForm.copy_main_teachers}
                                    onChange={handleTransitionFormChange}
                                    label="Copier les professeurs titulaires"
                                />
                                <Form.Text className="text-muted">
                                    Les professeurs principaux resteront les memes par classe
                                </Form.Text>
                            </Form.Group>

                            <Form.Group className="mb-3">
                                <Form.Check
                                    type="checkbox"
                                    name="promote_students"
                                    checked={transitionForm.promote_students}
                                    onChange={handleTransitionFormChange}
                                    label="Inscrire les eleves dans la nouvelle annee"
                                />
                                <Form.Text className="text-muted">
                                    Les eleves seront inscrits dans la meme classe pour la nouvelle annee.
                                    Vous pourrez ensuite les promouvoir (changer de classe) individuellement.
                                </Form.Text>
                            </Form.Group>

                            {transitionForm.promote_students && (
                                <Alert variant="warning">
                                    <ExclamationTriangle className="me-2" />
                                    <strong>Attention :</strong> Les eleves seront inscrits dans la meme classe
                                    (ex: 6eme A reste en 6eme A). Pour les promotions (6eme vers 5eme),
                                    vous devrez utiliser la fonction de transfert apres la transition.
                                </Alert>
                            )}
                        </>
                    )}

                    {/* Step 2: Preview */}
                    {transitionStep === 'preview' && transitionPreview && (
                        <>
                            <Alert variant="warning">
                                <ExclamationTriangle className="me-2" />
                                <strong>Verifiez les informations avant de confirmer.</strong> Cette action est irreversible.
                            </Alert>

                            <Card className="mb-3">
                                <Card.Header><strong>Nouvelle annee : {transitionForm.name}</strong></Card.Header>
                                <Card.Body>
                                    <Row>
                                        <Col md={6}>
                                            <p><strong>Debut :</strong> {formatDate(transitionForm.start_date)}</p>
                                        </Col>
                                        <Col md={6}>
                                            <p><strong>Fin :</strong> {formatDate(transitionForm.end_date)}</p>
                                        </Col>
                                    </Row>
                                </Card.Body>
                            </Card>

                            <h6 className="mb-2">Ce qui sera cree automatiquement :</h6>
                            <Table bordered size="sm" className="mb-3">
                                <tbody>
                                    <tr>
                                        <td>Trimestres</td>
                                        <td><Badge bg="primary">3</Badge></td>
                                    </tr>
                                    <tr>
                                        <td>Sequences (6 regulieres + 3 compositions)</td>
                                        <td><Badge bg="primary">9</Badge></td>
                                    </tr>
                                    <tr>
                                        <td>Classes dupliquees</td>
                                        <td><Badge bg="info">{transitionPreview.classes_count}</Badge></td>
                                    </tr>
                                    <tr>
                                        <td>Matieres / coefficients copies</td>
                                        <td><Badge bg="info">{transitionPreview.subjects_assignments_count}</Badge></td>
                                    </tr>
                                    {transitionForm.copy_teacher_assignments && (
                                        <tr>
                                            <td>Affectations enseignants copiees</td>
                                            <td><Badge bg="success">{transitionPreview.teacher_assignments_count}</Badge></td>
                                        </tr>
                                    )}
                                    {transitionForm.copy_main_teachers && (
                                        <tr>
                                            <td>Professeurs titulaires copies</td>
                                            <td><Badge bg="success">{transitionPreview.main_teachers_count}</Badge></td>
                                        </tr>
                                    )}
                                    {transitionForm.promote_students && (
                                        <tr>
                                            <td>Eleves a inscrire</td>
                                            <td><Badge bg="warning" text="dark">{transitionPreview.students_count}</Badge></td>
                                        </tr>
                                    )}
                                </tbody>
                            </Table>

                            {transitionPreview.classes && transitionPreview.classes.length > 0 && (
                                <>
                                    <h6 className="mb-2">Detail des classes :</h6>
                                    <div style={{ maxHeight: '200px', overflowY: 'auto' }}>
                                        <Table bordered size="sm">
                                            <thead>
                                                <tr>
                                                    <th>Classe</th>
                                                    <th>Eleves</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {transitionPreview.classes.map((cls, idx) => (
                                                    <tr key={idx}>
                                                        <td>{cls.name}</td>
                                                        <td>{cls.students_count}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </Table>
                                    </div>
                                </>
                            )}
                        </>
                    )}

                    {/* Step 3: Result */}
                    {transitionStep === 'result' && transitionResult && (
                        <>
                            <Alert variant="success">
                                <CheckCircle size={20} className="me-2" />
                                <strong>Transition effectuee avec succes !</strong>
                            </Alert>

                            <Table bordered size="sm">
                                <tbody>
                                    <tr>
                                        <td>Nouvelle annee</td>
                                        <td><strong>{transitionResult.new_year?.name}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Trimestres crees</td>
                                        <td><Badge bg="primary">{transitionResult.trimesters_created}</Badge></td>
                                    </tr>
                                    <tr>
                                        <td>Sequences creees</td>
                                        <td><Badge bg="primary">{transitionResult.sequences_created}</Badge></td>
                                    </tr>
                                    <tr>
                                        <td>Classes creees</td>
                                        <td><Badge bg="info">{transitionResult.classes_created}</Badge></td>
                                    </tr>
                                    <tr>
                                        <td>Matieres copiees</td>
                                        <td><Badge bg="info">{transitionResult.subjects_copied}</Badge></td>
                                    </tr>
                                    <tr>
                                        <td>Affectations enseignants</td>
                                        <td><Badge bg="success">{transitionResult.teacher_assignments_copied}</Badge></td>
                                    </tr>
                                    <tr>
                                        <td>Professeurs titulaires</td>
                                        <td><Badge bg="success">{transitionResult.main_teachers_copied}</Badge></td>
                                    </tr>
                                    <tr>
                                        <td>Eleves inscrits</td>
                                        <td><Badge bg="warning" text="dark">{transitionResult.students_promoted}</Badge></td>
                                    </tr>
                                </tbody>
                            </Table>

                            <Alert variant="info" className="mt-3">
                                <strong>Prochaines etapes :</strong>
                                <ul className="mb-0 mt-1">
                                    <li>Definir la nouvelle annee comme <strong>annee courante</strong> quand vous etes pret</li>
                                    {transitionForm.promote_students && (
                                        <li>Utiliser la fonction <strong>Transfert</strong> pour promouvoir les eleves (6eme vers 5eme, etc.)</li>
                                    )}
                                    {!transitionForm.promote_students && (
                                        <li><strong>Inscrire les eleves</strong> dans les nouvelles classes</li>
                                    )}
                                    <li>Verifier et ajuster les <strong>affectations enseignants</strong> si necessaire</li>
                                </ul>
                            </Alert>
                        </>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    {transitionStep === 'form' && (
                        <>
                            <Button variant="secondary" onClick={handleCloseTransition}>
                                Annuler
                            </Button>
                            <Button
                                variant="primary"
                                onClick={handlePreviewTransition}
                                disabled={!transitionForm.name || !transitionForm.start_date || !transitionForm.end_date || transitionLoading}
                            >
                                {transitionLoading ? (
                                    <><Spinner size="sm" className="me-2" />Chargement...</>
                                ) : (
                                    'Suivant'
                                )}
                            </Button>
                        </>
                    )}
                    {transitionStep === 'preview' && (
                        <>
                            <Button variant="secondary" onClick={() => setTransitionStep('form')}>
                                Retour
                            </Button>
                            <Button
                                variant="success"
                                onClick={handleExecuteTransition}
                                disabled={transitionExecuting}
                            >
                                {transitionExecuting ? (
                                    <><Spinner size="sm" className="me-2" />Transition en cours...</>
                                ) : (
                                    'Confirmer la transition'
                                )}
                            </Button>
                        </>
                    )}
                    {transitionStep === 'result' && (
                        <Button variant="primary" onClick={handleCloseTransition}>
                            Fermer
                        </Button>
                    )}
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default SchoolYears;
