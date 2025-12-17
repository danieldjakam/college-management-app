import React, { useState, useEffect } from 'react';
import { Card, Form, Button, Table, Badge, Alert, Row, Col, Spinner } from 'react-bootstrap';
import { FileEarmarkPdf, Eye, Download, CalendarCheck } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { host } from '../../utils/fetch';
import Swal from 'sweetalert2';

const PVGeneration = () => {
    const [loading, setLoading] = useState(false);
    const [classSeries, setClassSeries] = useState([]);
    const [selectedSeries, setSelectedSeries] = useState('');
    const [pvType, setPvType] = useState('period'); // 'period' ou 'trimester'
    const [evaluations, setEvaluations] = useState([]);
    const [trimesters, setTrimesters] = useState([]);
    const [selectedEvaluation, setSelectedEvaluation] = useState('');
    const [selectedTrimester, setSelectedTrimester] = useState('');
    const [schoolYear, setSchoolYear] = useState(null);
    const [generating, setGenerating] = useState(false);

    useEffect(() => {
        loadClassSeries();
    }, []);

    useEffect(() => {
        if (selectedSeries) {
            if (pvType === 'period') {
                loadEvaluations(selectedSeries);
            } else {
                loadTrimesters(selectedSeries);
            }
        } else {
            setEvaluations([]);
            setTrimesters([]);
            setSelectedEvaluation('');
            setSelectedTrimester('');
        }
    }, [selectedSeries, pvType]);

    const loadClassSeries = async () => {
        try {
            setLoading(true);
            const token = localStorage.getItem('token');
            const response = await fetch(`${host}/api/pv/class-series`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                setClassSeries(data.data);
            } else {
                Swal.fire('Erreur', data.message || 'Impossible de charger les classes', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Impossible de charger les classes', 'error');
        } finally {
            setLoading(false);
        }
    };

    const loadEvaluations = async (seriesId) => {
        try {
            setLoading(true);
            const token = localStorage.getItem('token');
            const response = await fetch(`${host}/api/pv/evaluations/${seriesId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                setEvaluations(data.data.evaluations);
                setSchoolYear(data.data.school_year);
            } else {
                Swal.fire('Erreur', data.message || 'Impossible de charger les évaluations', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Impossible de charger les évaluations', 'error');
        } finally {
            setLoading(false);
        }
    };

    const loadTrimesters = async (seriesId) => {
        try {
            setLoading(true);
            const token = localStorage.getItem('token');
            const response = await fetch(`${host}/api/pv/trimesters/${seriesId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                setTrimesters(data.data.trimesters);
                setSchoolYear(data.data.school_year);
            } else {
                Swal.fire('Erreur', data.message || 'Impossible de charger les trimestres', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Impossible de charger les trimestres', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleGeneratePV = async () => {
        // Validation selon le type de PV
        if (pvType === 'period' && (!selectedSeries || !selectedEvaluation)) {
            Swal.fire('Attention', 'Veuillez sélectionner une classe et une évaluation', 'warning');
            return;
        }
        if (pvType === 'trimester' && (!selectedSeries || !selectedTrimester)) {
            Swal.fire('Attention', 'Veuillez sélectionner une classe et un trimestre', 'warning');
            return;
        }

        try {
            setGenerating(true);
            const token = localStorage.getItem('token');

            // Construire l'URL selon le type de PV
            let apiUrl;
            if (pvType === 'period') {
                apiUrl = `${host}/api/pv/generate/${selectedSeries}/${selectedEvaluation}`;
            } else {
                apiUrl = `${host}/api/pv/trimester/generate/${selectedSeries}/${selectedTrimester}`;
            }

            // Télécharger directement le PDF
            const response = await fetch(apiUrl, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;

                const selectedClass = classSeries.find(c => c.id === parseInt(selectedSeries));
                const fileName = `PV_${selectedClass?.name.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`;

                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                Swal.fire('Succès !', 'Le PV a été généré et téléchargé avec succès', 'success');
            } else {
                const errorData = await response.json();
                Swal.fire('Erreur', errorData.message || 'Erreur lors de la génération du PV', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Une erreur est survenue lors de la génération du PV', 'error');
        } finally {
            setGenerating(false);
        }
    };

    const handlePreviewPV = async () => {
        // Validation selon le type de PV
        if (pvType === 'period' && (!selectedSeries || !selectedEvaluation)) {
            Swal.fire('Attention', 'Veuillez sélectionner une classe et une évaluation', 'warning');
            return;
        }
        if (pvType === 'trimester' && (!selectedSeries || !selectedTrimester)) {
            Swal.fire('Attention', 'Veuillez sélectionner une classe et un trimestre', 'warning');
            return;
        }

        try {
            const token = localStorage.getItem('token');

            // Construire l'URL selon le type de PV
            let url;
            if (pvType === 'period') {
                url = `${host}/api/pv/preview/${selectedSeries}/${selectedEvaluation}`;
            } else {
                url = `${host}/api/pv/trimester/preview/${selectedSeries}/${selectedTrimester}`;
            }

            // Ouvrir dans un nouvel onglet
            window.open(url + `?token=${token}`, '_blank');
        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Impossible d\'ouvrir la prévisualisation', 'error');
        }
    };

    const getEvaluationLabel = (evaluation) => {
        if (evaluation.sequence) {
            // Vérifier si c'est une composition
            if (evaluation.sequence.is_composition) {
                return `Composition ${evaluation.trimester?.number || ''} - Trimestre ${evaluation.trimester?.number || ''}`;
            }
            return `Séquence ${evaluation.sequence.number} - Trimestre ${evaluation.trimester?.number || ''}`;
        } else {
            return `Composition - Trimestre ${evaluation.trimester?.number || ''}`;
        }
    };

    const getEvaluationBadge = (evaluation) => {
        if (evaluation.sequence) {
            // Vérifier si c'est une composition
            if (evaluation.sequence.is_composition) {
                return <Badge bg="warning">Composition {evaluation.trimester?.number || ''}</Badge>;
            }
            return <Badge bg="primary">Séquence {evaluation.sequence.number}</Badge>;
        } else {
            return <Badge bg="warning">Composition</Badge>;
        }
    };

    const getEvaluationId = (evaluation) => {
        // Retourne l'ID composite au format "sequenceId_trimesterId"
        return evaluation.id || `${evaluation.sequence_id}_${evaluation.trimester_id}`;
    };

    if (loading && classSeries.length === 0) {
        return (
            <div className="container-fluid py-4">
                <div className="text-center">
                    <Spinner animation="border" variant="primary" />
                    <p className="mt-3">Chargement...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid py-4">
            {/* En-tête */}
            <div className="mb-4">
                <h2 className="mb-2">
                    <FileEarmarkPdf className="me-2" style={{ color: '#9333ea' }} />
                    Génération de Procès-Verbaux (PV)
                </h2>
                <p className="text-muted">
                    Générez les procès-verbaux de délibération pour chaque classe et évaluation
                </p>
            </div>

            {/* Année scolaire */}
            {schoolYear && (
                <Alert variant="info" className="mb-4">
                    <CalendarCheck className="me-2" />
                    <strong>Année scolaire en cours :</strong> {schoolYear.name}
                </Alert>
            )}

            {/* Formulaire de sélection */}
            <Card className="mb-4">
                <Card.Header className="bg-light">
                    <h5 className="mb-0">Sélection de la classe et du type de PV</h5>
                </Card.Header>
                <Card.Body>
                    <Row>
                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>
                                    Classe / Série <span className="text-danger">*</span>
                                </Form.Label>
                                <Form.Select
                                    value={selectedSeries}
                                    onChange={(e) => setSelectedSeries(e.target.value)}
                                    size="lg"
                                >
                                    <option value="">-- Sélectionner une classe --</option>
                                    {classSeries.map((series) => (
                                        <option key={series.id} value={series.id}>
                                            {series.name} {series.school_class?.level && `(${series.school_class.level.name})`}
                                        </option>
                                    ))}
                                </Form.Select>
                                <Form.Text className="text-muted">
                                    Ex: 6ème A, 6ème B, 5ème ESP, etc.
                                </Form.Text>
                            </Form.Group>
                        </Col>

                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>
                                    Type de PV <span className="text-danger">*</span>
                                </Form.Label>
                                <Form.Select
                                    value={pvType}
                                    onChange={(e) => {
                                        setPvType(e.target.value);
                                        setSelectedEvaluation('');
                                        setSelectedTrimester('');
                                    }}
                                    size="lg"
                                >
                                    <option value="period">Séquence / Composition</option>
                                    <option value="trimester">Trimestre (DS + Compo)</option>
                                </Form.Select>
                                <Form.Text className="text-muted">
                                    {pvType === 'period' ? 'PV par séquence ou composition' : 'PV par trimestre complet'}
                                </Form.Text>
                            </Form.Group>
                        </Col>

                        <Col md={4}>
                            {pvType === 'period' ? (
                                <Form.Group className="mb-3">
                                    <Form.Label>
                                        Évaluation <span className="text-danger">*</span>
                                    </Form.Label>
                                    <Form.Select
                                        value={selectedEvaluation}
                                        onChange={(e) => setSelectedEvaluation(e.target.value)}
                                        disabled={!selectedSeries}
                                        size="lg"
                                    >
                                        <option value="">-- Sélectionner une évaluation --</option>
                                        {evaluations.map((evaluation) => {
                                            const evalId = getEvaluationId(evaluation);
                                            return (
                                                <option key={evalId} value={evalId}>
                                                    {getEvaluationLabel(evaluation)}
                                                </option>
                                            );
                                        })}
                                    </Form.Select>
                                    <Form.Text className="text-muted">
                                        Séquence ou Composition
                                    </Form.Text>
                                </Form.Group>
                            ) : (
                                <Form.Group className="mb-3">
                                    <Form.Label>
                                        Trimestre <span className="text-danger">*</span>
                                    </Form.Label>
                                    <Form.Select
                                        value={selectedTrimester}
                                        onChange={(e) => setSelectedTrimester(e.target.value)}
                                        disabled={!selectedSeries}
                                        size="lg"
                                    >
                                        <option value="">-- Sélectionner un trimestre --</option>
                                        {trimesters.map((trimester) => (
                                            <option key={trimester.id} value={trimester.id}>
                                                Trimestre {trimester.number}
                                            </option>
                                        ))}
                                    </Form.Select>
                                    <Form.Text className="text-muted">
                                        Moyennes trimestrielles
                                    </Form.Text>
                                </Form.Group>
                            )}
                        </Col>
                    </Row>

                    <div className="d-flex gap-2 justify-content-end mt-4">
                        <Button
                            variant="outline-primary"
                            onClick={handlePreviewPV}
                            disabled={
                                !selectedSeries ||
                                (pvType === 'period' && !selectedEvaluation) ||
                                (pvType === 'trimester' && !selectedTrimester)
                            }
                        >
                            <Eye className="me-2" />
                            Prévisualiser
                        </Button>

                        <Button
                            variant="success"
                            onClick={handleGeneratePV}
                            disabled={
                                !selectedSeries ||
                                (pvType === 'period' && !selectedEvaluation) ||
                                (pvType === 'trimester' && !selectedTrimester) ||
                                generating
                            }
                        >
                            {generating ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Génération...
                                </>
                            ) : (
                                <>
                                    <Download className="me-2" />
                                    Générer et Télécharger le PV (PDF)
                                </>
                            )}
                        </Button>
                    </div>
                </Card.Body>
            </Card>

            {/* Liste des évaluations disponibles */}
            {evaluations.length > 0 && (
                <Card>
                    <Card.Header className="bg-light">
                        <h5 className="mb-0">Évaluations disponibles ({evaluations.length})</h5>
                    </Card.Header>
                    <Card.Body>
                        <Table striped bordered hover responsive>
                            <thead>
                                <tr>
                                    <th style={{ width: '50px' }}>#</th>
                                    <th>Type</th>
                                    <th>Trimestre</th>
                                    <th>Description</th>
                                    <th style={{ width: '150px' }}>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {evaluations.map((evaluation, index) => {
                                    const evalId = getEvaluationId(evaluation);
                                    return (
                                        <tr key={evalId}>
                                            <td>{index + 1}</td>
                                            <td>{getEvaluationBadge(evaluation)}</td>
                                            <td>
                                                <Badge bg="secondary">
                                                    Trimestre {evaluation.trimester?.number || 'N/A'}
                                                </Badge>
                                            </td>
                                            <td>{getEvaluationLabel(evaluation)}</td>
                                            <td>
                                                <Button
                                                    variant="outline-success"
                                                    size="sm"
                                                    onClick={() => {
                                                        setSelectedEvaluation(evalId.toString());
                                                        setTimeout(() => handleGeneratePV(), 100);
                                                    }}
                                                >
                                                    <Download size={14} className="me-1" />
                                                    Générer
                                                </Button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </Table>
                    </Card.Body>
                </Card>
            )}

            {/* Informations */}
            <Card className="mt-4 border-info">
                <Card.Header className="bg-info text-white">
                    <h6 className="mb-0">ℹ️ Informations sur les PV</h6>
                </Card.Header>
                <Card.Body>
                    <p className="mb-2"><strong>Le Procès-Verbal contient :</strong></p>
                    <ul className="mb-3">
                        <li>En-tête officiel CPBD (bilingue)</li>
                        <li>Informations de la classe et de l'évaluation</li>
                        <li>Tableau complet des élèves avec notes par matière</li>
                        <li>Classement des élèves (rangs 1er, 2ème, 3ème colorés)</li>
                        <li>Moyennes et mentions (Excellent, Bien, Assez Bien, Passable, Échec)</li>
                        <li>Statistiques globales (taux de réussite, moyenne de classe, etc.)</li>
                        <li>Répartition par mention</li>
                        <li>Espaces pour signatures (Professeur Principal, Censeur, Proviseur)</li>
                    </ul>

                    <Alert variant="success" className="mb-0">
                        <p className="mb-2"><strong>📊 Types de PV disponibles :</strong></p>
                        <ul className="mb-0">
                            <li>
                                <strong>PV de Séquence / Composition :</strong> Notes d'une séquence ou composition spécifique
                            </li>
                            <li className="mt-2">
                                <strong>PV de Trimestre :</strong> Moyennes trimestrielles calculées automatiquement selon le cycle :
                                <ul className="mt-1">
                                    <li><em>Premier Cycle (6ème-3ème) :</em> M/20 = (DS + Compo) / 2 (DS = moyenne des 2 séquences)</li>
                                    <li><em>Deuxième Cycle (2nde-Tle) :</em> M/20 = (Seq1 + Seq2 + Compo) / 3</li>
                                    <li><em>Trimestre 3 (Premier Cycle) :</em> M/20 = Composition uniquement</li>
                                </ul>
                            </li>
                        </ul>
                    </Alert>
                </Card.Body>
            </Card>
        </div>
    );
};

export default PVGeneration;
