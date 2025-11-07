import React, { useState, useEffect } from 'react';
import {
    Card, Row, Col, Form, Button, Spinner, Alert,
    Accordion, Badge, Table
} from 'react-bootstrap';
import {
    BookFill, CheckCircleFill, Save, Calendar
} from 'react-bootstrap-icons';
import { useAuth } from '../../hooks/useAuth';
import { secureApiEndpoints } from '../../utils/apiMigration';

const Competences = () => {
    const { user } = useAuth();
    const [loading, setLoading] = useState(true);
    const [classes, setClasses] = useState([]);
    const [trimesters, setTrimesters] = useState([]);
    const [selectedTrimester, setSelectedTrimester] = useState(null);
    const [competences, setCompetences] = useState({});
    const [saving, setSaving] = useState({});
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    useEffect(() => {
        if (user && user.teacher_id) {
            loadInitialData();
        }
    }, [user]);

    useEffect(() => {
        if (selectedTrimester && classes.length > 0) {
            loadAllCompetences();
        }
    }, [selectedTrimester, classes]);

    const loadInitialData = async () => {
        try {
            setLoading(true);
            setError(null);

            const teacherId = user.teacher_id;

            // Load teacher's assignments grouped by class
            const assignmentsResponse = await secureApiEndpoints.request(
                `/teacher-assignments/teacher/${teacherId}`
            );

            if (assignmentsResponse.success) {
                // Group assignments by class
                const classesMap = {};

                assignmentsResponse.data.assignments?.forEach(assignment => {
                    const classSeries = assignment.series_subject?.class_series;
                    const subject = assignment.series_subject?.subject;
                    const classSeriesSubjectId = assignment.series_subject?.id;

                    if (classSeries && subject && classSeriesSubjectId) {
                        const classId = classSeries.id;

                        if (!classesMap[classId]) {
                            classesMap[classId] = {
                                id: classId,
                                name: classSeries.name,
                                schoolClass: classSeries.school_class,
                                subjects: []
                            };
                        }

                        classesMap[classId].subjects.push({
                            id: subject.id,
                            name: subject.name,
                            classSeriesSubjectId: classSeriesSubjectId,
                            coefficient: assignment.coefficient
                        });
                    }
                });

                const classesArray = Object.values(classesMap).sort((a, b) =>
                    a.name.localeCompare(b.name)
                );
                setClasses(classesArray);
            }

            // Load trimesters
            const trimestersResponse = await secureApiEndpoints.request('/trimesters');
            if (trimestersResponse.data) {
                setTrimesters(trimestersResponse.data);
                if (trimestersResponse.data.length > 0) {
                    setSelectedTrimester(trimestersResponse.data[0]);
                }
            }

        } catch (error) {
            console.error('Error loading data:', error);
            setError(error.message || 'Erreur lors du chargement des données');
        } finally {
            setLoading(false);
        }
    };

    const loadAllCompetences = async () => {
        if (!selectedTrimester) return;

        try {
            const loadedCompetences = {};

            for (const classInfo of classes) {
                for (const subject of classInfo.subjects) {
                    const key = `${classInfo.id}-${subject.classSeriesSubjectId}-${selectedTrimester.id}`;

                    try {
                        const response = await secureApiEndpoints.request(
                            `/subject-competences/${classInfo.id}/${subject.classSeriesSubjectId}/${selectedTrimester.id}`
                        );

                        if (response) {
                            loadedCompetences[key] = {
                                competence_1: response.competence_1 || '',
                                competence_2: response.competence_2 || ''
                            };
                        }
                    } catch (error) {
                        // If no competences found, set empty strings
                        loadedCompetences[key] = {
                            competence_1: '',
                            competence_2: ''
                        };
                    }
                }
            }

            setCompetences(loadedCompetences);
        } catch (error) {
            console.error('Error loading competences:', error);
        }
    };

    const handleCompetenceChange = (classId, classSeriesSubjectId, field, value) => {
        const key = `${classId}-${classSeriesSubjectId}-${selectedTrimester.id}`;
        setCompetences(prev => ({
            ...prev,
            [key]: {
                ...(prev[key] || { competence_1: '', competence_2: '' }),
                [field]: value
            }
        }));
    };

    const handleSaveCompetences = async (classId, classSeriesSubjectId) => {
        const key = `${classId}-${classSeriesSubjectId}-${selectedTrimester.id}`;
        const competenceData = competences[key] || {};

        setSaving(prev => ({ ...prev, [key]: true }));
        setError(null);
        setSuccess(null);

        try {
            const payload = {
                class_series_id: classId,
                class_series_subject_id: classSeriesSubjectId,
                trimester_id: selectedTrimester.id,
                competence_1: competenceData.competence_1 || '',
                competence_2: competenceData.competence_2 || ''
            };

            await secureApiEndpoints.request('/subject-competences', {
                method: 'POST',
                body: JSON.stringify(payload)
            });

            setSuccess('Compétences enregistrées avec succès !');
            setTimeout(() => setSuccess(null), 3000);

        } catch (error) {
            console.error('Error saving competences:', error);
            setError(error.message || 'Erreur lors de l\'enregistrement');
        } finally {
            setSaving(prev => ({ ...prev, [key]: false }));
        }
    };

    if (loading) {
        return (
            <div className="text-center p-5">
                <Spinner animation="border" variant="primary" />
                <p className="mt-2">Chargement des compétences...</p>
            </div>
        );
    }

    if (!user.teacher_id) {
        return (
            <Alert variant="warning" className="m-4">
                <h5>Accès limité</h5>
                <p>Votre compte n'est pas associé à un profil enseignant.</p>
            </Alert>
        );
    }

    return (
        <div className="container-fluid p-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <Card className="bg-purple text-white" style={{ backgroundColor: '#6f42c1' }}>
                        <Card.Body>
                            <div className="d-flex align-items-center">
                                <BookFill size={40} className="me-3" />
                                <div>
                                    <h4 className="mb-1">Compétences Évaluées</h4>
                                    <p className="mb-0">
                                        Définissez les compétences évaluées pour chaque matière et trimestre
                                    </p>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Success/Error Messages */}
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess(null)}>
                    <CheckCircleFill className="me-2" />
                    {success}
                </Alert>
            )}

            {error && (
                <Alert variant="danger" dismissible onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}

            {/* Trimester Selector */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Header className="bg-light">
                            <Calendar className="me-2" />
                            Sélectionner un Trimestre
                        </Card.Header>
                        <Card.Body>
                            <div className="d-flex gap-2 flex-wrap">
                                {trimesters.map(trimester => (
                                    <Button
                                        key={trimester.id}
                                        variant={selectedTrimester?.id === trimester.id ? 'primary' : 'outline-primary'}
                                        onClick={() => setSelectedTrimester(trimester)}
                                    >
                                        Trimestre {trimester.number}
                                    </Button>
                                ))}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Classes and Subjects */}
            {classes.length === 0 ? (
                <Alert variant="info">
                    Aucune classe assignée pour cette année scolaire.
                </Alert>
            ) : (
                <Row>
                    <Col>
                        <Accordion defaultActiveKey="0">
                            {classes.map((classInfo, classIndex) => (
                                <Accordion.Item eventKey={String(classIndex)} key={classInfo.id}>
                                    <Accordion.Header>
                                        <div className="d-flex align-items-center w-100">
                                            <BookFill className="me-2 text-primary" />
                                            <strong>{classInfo.name}</strong>
                                            <Badge bg="secondary" className="ms-auto me-2">
                                                {classInfo.subjects.length} matière{classInfo.subjects.length > 1 ? 's' : ''}
                                            </Badge>
                                        </div>
                                    </Accordion.Header>
                                    <Accordion.Body>
                                        <Table responsive>
                                            <thead>
                                                <tr>
                                                    <th style={{ width: '20%' }}>Matière</th>
                                                    <th style={{ width: '35%' }}>Compétence 1</th>
                                                    <th style={{ width: '35%' }}>Compétence 2</th>
                                                    <th style={{ width: '10%' }}>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {classInfo.subjects.map(subject => {
                                                    const key = `${classInfo.id}-${subject.classSeriesSubjectId}-${selectedTrimester?.id}`;
                                                    const competenceData = competences[key] || { competence_1: '', competence_2: '' };
                                                    const isSaving = saving[key];

                                                    return (
                                                        <tr key={subject.classSeriesSubjectId}>
                                                            <td>
                                                                <Badge bg="info" className="p-2">
                                                                    {subject.name}
                                                                </Badge>
                                                                {subject.coefficient && (
                                                                    <div className="text-muted small mt-1">
                                                                        Coef: {subject.coefficient}
                                                                    </div>
                                                                )}
                                                            </td>
                                                            <td>
                                                                <Form.Control
                                                                    as="textarea"
                                                                    rows={2}
                                                                    placeholder="Entrez la première compétence évaluée..."
                                                                    value={competenceData.competence_1}
                                                                    onChange={(e) => handleCompetenceChange(
                                                                        classInfo.id,
                                                                        subject.classSeriesSubjectId,
                                                                        'competence_1',
                                                                        e.target.value
                                                                    )}
                                                                    disabled={isSaving}
                                                                />
                                                            </td>
                                                            <td>
                                                                <Form.Control
                                                                    as="textarea"
                                                                    rows={2}
                                                                    placeholder="Entrez la deuxième compétence évaluée..."
                                                                    value={competenceData.competence_2}
                                                                    onChange={(e) => handleCompetenceChange(
                                                                        classInfo.id,
                                                                        subject.classSeriesSubjectId,
                                                                        'competence_2',
                                                                        e.target.value
                                                                    )}
                                                                    disabled={isSaving}
                                                                />
                                                            </td>
                                                            <td>
                                                                <Button
                                                                    variant="success"
                                                                    size="sm"
                                                                    onClick={() => handleSaveCompetences(
                                                                        classInfo.id,
                                                                        subject.classSeriesSubjectId
                                                                    )}
                                                                    disabled={isSaving}
                                                                >
                                                                    {isSaving ? (
                                                                        <Spinner
                                                                            as="span"
                                                                            animation="border"
                                                                            size="sm"
                                                                            role="status"
                                                                        />
                                                                    ) : (
                                                                        <>
                                                                            <Save className="me-1" size={14} />
                                                                            Sauver
                                                                        </>
                                                                    )}
                                                                </Button>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </Table>
                                    </Accordion.Body>
                                </Accordion.Item>
                            ))}
                        </Accordion>
                    </Col>
                </Row>
            )}

            {/* Info Card */}
            <Row className="mt-4">
                <Col>
                    <Alert variant="info">
                        <strong>Information :</strong>
                        <ul className="mb-0 mt-2">
                            <li>Les compétences définies ici apparaîtront sur les bulletins des élèves</li>
                            <li>Chaque matière comporte 2 compétences évaluées</li>
                            <li>Les compétences sont définies par classe et par trimestre</li>
                            <li>N'oubliez pas de sauvegarder après chaque modification</li>
                        </ul>
                    </Alert>
                </Col>
            </Row>
        </div>
    );
};

export default Competences;
