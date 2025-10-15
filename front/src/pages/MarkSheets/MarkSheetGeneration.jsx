import React, { useState, useEffect } from 'react';
import { Card, Form, Button, Table, Badge, Alert, Row, Col, Spinner } from 'react-bootstrap';
import { FileEarmarkText, Download, CalendarCheck, Book } from 'react-bootstrap-icons';
import { host } from '../../utils/fetch';
import Swal from 'sweetalert2';

const MarkSheetGeneration = () => {
    const [loading, setLoading] = useState(false);
    const [classSeries, setClassSeries] = useState([]);
    const [subjects, setSubjects] = useState([]);
    const [schoolYears, setSchoolYears] = useState([]);
    const [selectedSeries, setSelectedSeries] = useState('');
    const [selectedSubject, setSelectedSubject] = useState('');
    const [selectedSchoolYear, setSelectedSchoolYear] = useState('');
    const [generating, setGenerating] = useState(false);
    const [generatingFilled, setGeneratingFilled] = useState(false);
    const [generatingAll, setGeneratingAll] = useState(false);

    useEffect(() => {
        loadInitialData();
    }, []);

    const loadInitialData = async () => {
        try {
            setLoading(true);
            const token = localStorage.getItem('token');

            // Charger les années scolaires
            const yearResponse = await fetch(`${host}/api/school-years/active`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            const yearData = await yearResponse.json();
            if (yearData.success) {
                setSchoolYears(yearData.data);
                // Sélectionner l'année courante par défaut
                const currentYear = yearData.data.find(y => y.is_current);
                if (currentYear) {
                    setSelectedSchoolYear(currentYear.id.toString());
                }
            }

            // Charger les classes-séries
            const seriesResponse = await fetch(`${host}/api/school-classes`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            const seriesData = await seriesResponse.json();
            if (seriesData.success) {
                // Extraire toutes les séries des classes
                const allSeries = [];
                seriesData.data.forEach(schoolClass => {
                    if (schoolClass.series && schoolClass.series.length > 0) {
                        schoolClass.series.forEach(series => {
                            allSeries.push({
                                id: series.id,
                                name: `${schoolClass.name} ${series.name}`,
                                school_class: schoolClass
                            });
                        });
                    }
                });
                setClassSeries(allSeries);
            }

            // Charger les matières
            const subjectsResponse = await fetch(`${host}/api/subjects`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            const subjectsData = await subjectsResponse.json();
            if (subjectsData.success) {
                setSubjects(subjectsData.data.filter(s => s.is_active));
            }

        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Impossible de charger les données', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleGenerateBlank = async () => {
        if (!selectedSeries || !selectedSubject || !selectedSchoolYear) {
            Swal.fire('Attention', 'Veuillez sélectionner une classe, une matière et une année scolaire', 'warning');
            return;
        }

        try {
            setGenerating(true);
            const token = localStorage.getItem('token');

            const response = await fetch(`${host}/api/mark-sheets/generate-blank`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    class_series_id: selectedSeries,
                    subject_id: selectedSubject,
                    school_year_id: selectedSchoolYear
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;

                const selectedClass = classSeries.find(c => c.id === parseInt(selectedSeries));
                const selectedSubj = subjects.find(s => s.id === parseInt(selectedSubject));
                const fileName = `Fiche_Report_Notes_${selectedClass?.name.replace(/\s+/g, '_')}_${selectedSubj?.name.replace(/\s+/g, '_')}.pdf`;

                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                Swal.fire('Succès !', 'La fiche de report de notes a été générée et téléchargée avec succès', 'success');
            } else {
                const errorData = await response.json();
                Swal.fire('Erreur', errorData.message || 'Erreur lors de la génération de la fiche', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Une erreur est survenue lors de la génération de la fiche', 'error');
        } finally {
            setGenerating(false);
        }
    };

    const handleGenerateFilled = async () => {
        if (!selectedSeries || !selectedSubject || !selectedSchoolYear) {
            Swal.fire('Attention', 'Veuillez sélectionner une classe, une matière et une année scolaire', 'warning');
            return;
        }

        try {
            setGeneratingFilled(true);
            const token = localStorage.getItem('token');

            const response = await fetch(`${host}/api/mark-sheets/generate-filled`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    class_series_id: selectedSeries,
                    subject_id: selectedSubject,
                    school_year_id: selectedSchoolYear
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;

                const selectedClass = classSeries.find(c => c.id === parseInt(selectedSeries));
                const selectedSubj = subjects.find(s => s.id === parseInt(selectedSubject));
                const fileName = `Fiche_Report_Notes_Remplie_${selectedClass?.name.replace(/\s+/g, '_')}_${selectedSubj?.name.replace(/\s+/g, '_')}.pdf`;

                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                Swal.fire('Succès !', 'La fiche de report avec les notes réelles a été générée avec succès', 'success');
            } else {
                const errorData = await response.json();
                Swal.fire('Erreur', errorData.message || 'Erreur lors de la génération de la fiche', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Une erreur est survenue lors de la génération de la fiche', 'error');
        } finally {
            setGeneratingFilled(false);
        }
    };

    const handleGenerateAll = async () => {
        if (!selectedSeries || !selectedSchoolYear) {
            Swal.fire('Attention', 'Veuillez sélectionner une classe et une année scolaire', 'warning');
            return;
        }

        try {
            setGeneratingAll(true);
            const token = localStorage.getItem('token');

            const response = await fetch(`${host}/api/mark-sheets/generate-all`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    class_series_id: selectedSeries,
                    school_year_id: selectedSchoolYear
                })
            });

            const data = await response.json();

            if (data.success) {
                const sheetsCount = data.data.sheets.length;

                Swal.fire({
                    title: 'Fiches disponibles',
                    html: `
                        <p class="mb-3">${sheetsCount} fiche(s) peuvent être générées pour cette classe :</p>
                        <div class="text-start" style="max-height: 300px; overflow-y: auto;">
                            <ul>
                                ${data.data.sheets.map(sheet => `
                                    <li><strong>${sheet.subject_name}</strong> - Coef: ${sheet.coefficient} - ${sheet.students_count} élève(s)</li>
                                `).join('')}
                            </ul>
                        </div>
                        <p class="mt-3 text-muted">Veuillez générer chaque fiche individuellement en sélectionnant la matière ci-dessus.</p>
                    `,
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire('Erreur', data.message || 'Erreur lors de la récupération des matières', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            Swal.fire('Erreur', 'Une erreur est survenue', 'error');
        } finally {
            setGeneratingAll(false);
        }
    };

    if (loading) {
        return (
            <div className="container-fluid py-4">
                <div className="text-center">
                    <Spinner animation="border" variant="primary" />
                    <p className="mt-3">Chargement...</p>
                </div>
            </div>
        );
    }

    const selectedClassInfo = classSeries.find(c => c.id === parseInt(selectedSeries));
    const selectedSubjectInfo = subjects.find(s => s.id === parseInt(selectedSubject));
    const selectedYearInfo = schoolYears.find(y => y.id === parseInt(selectedSchoolYear));

    return (
        <div className="container-fluid py-4">
            {/* En-tête */}
            <div className="mb-4">
                <h2 className="mb-2">
                    <FileEarmarkText className="me-2" style={{ color: '#10b981' }} />
                    Fiches de Report de Notes
                </h2>
                <p className="text-muted">
                    Générez des fiches vierges pour le report manuel des notes par les enseignants
                </p>
            </div>

            {/* Année scolaire */}
            {selectedYearInfo && (
                <Alert variant="info" className="mb-4">
                    <CalendarCheck className="me-2" />
                    <strong>Année scolaire :</strong> {selectedYearInfo.name}
                </Alert>
            )}

            {/* Formulaire de sélection */}
            <Card className="mb-4">
                <Card.Header className="bg-light">
                    <h5 className="mb-0">Sélection des paramètres</h5>
                </Card.Header>
                <Card.Body>
                    <Row>
                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>
                                    Année Scolaire <span className="text-danger">*</span>
                                </Form.Label>
                                <Form.Select
                                    value={selectedSchoolYear}
                                    onChange={(e) => setSelectedSchoolYear(e.target.value)}
                                    size="lg"
                                >
                                    <option value="">-- Sélectionner --</option>
                                    {schoolYears.map((year) => (
                                        <option key={year.id} value={year.id}>
                                            {year.name} {year.is_current && '(En cours)'}
                                        </option>
                                    ))}
                                </Form.Select>
                            </Form.Group>
                        </Col>

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
                                    <option value="">-- Sélectionner --</option>
                                    {classSeries.map((series) => (
                                        <option key={series.id} value={series.id}>
                                            {series.name}
                                        </option>
                                    ))}
                                </Form.Select>
                                <Form.Text className="text-muted">
                                    Ex: 6ème A, 6ème B, 2nd C, etc.
                                </Form.Text>
                            </Form.Group>
                        </Col>

                        <Col md={4}>
                            <Form.Group className="mb-3">
                                <Form.Label>
                                    Matière <span className="text-danger">*</span>
                                </Form.Label>
                                <Form.Select
                                    value={selectedSubject}
                                    onChange={(e) => setSelectedSubject(e.target.value)}
                                    size="lg"
                                >
                                    <option value="">-- Sélectionner --</option>
                                    {subjects.map((subject) => (
                                        <option key={subject.id} value={subject.id}>
                                            {subject.name} {subject.code && `(${subject.code})`}
                                        </option>
                                    ))}
                                </Form.Select>
                                <Form.Text className="text-muted">
                                    Ex: Mathématiques, Français, etc.
                                </Form.Text>
                            </Form.Group>
                        </Col>
                    </Row>

                    {/* Résumé de la sélection */}
                    {selectedClassInfo && selectedSubjectInfo && selectedYearInfo && (
                        <Alert variant="success" className="mb-3">
                            <Book className="me-2" />
                            <strong>Vous allez générer :</strong> Fiche de report de notes pour{' '}
                            <strong>{selectedClassInfo.name}</strong> en{' '}
                            <strong>{selectedSubjectInfo.name}</strong> ({selectedYearInfo.name})
                        </Alert>
                    )}

                    <div className="d-flex gap-2 justify-content-end mt-4">
                        <Button
                            variant="outline-primary"
                            onClick={handleGenerateAll}
                            disabled={!selectedSeries || !selectedSchoolYear || generatingAll}
                        >
                            {generatingAll ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Chargement...
                                </>
                            ) : (
                                <>
                                    <FileEarmarkText className="me-2" />
                                    Voir toutes les matières
                                </>
                            )}
                        </Button>

                        <Button
                            variant="outline-success"
                            onClick={handleGenerateBlank}
                            disabled={!selectedSeries || !selectedSubject || !selectedSchoolYear || generating}
                        >
                            {generating ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Génération...
                                </>
                            ) : (
                                <>
                                    <Download className="me-2" />
                                    Fiche Vierge
                                </>
                            )}
                        </Button>

                        <Button
                            variant="success"
                            onClick={handleGenerateFilled}
                            disabled={!selectedSeries || !selectedSubject || !selectedSchoolYear || generatingFilled}
                        >
                            {generatingFilled ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Génération...
                                </>
                            ) : (
                                <>
                                    <Download className="me-2" />
                                    Fiche avec Notes Réelles
                                </>
                            )}
                        </Button>
                    </div>
                </Card.Body>
            </Card>

            {/* Informations */}
            <Card className="border-info">
                <Card.Header className="bg-info text-white">
                    <h6 className="mb-0">ℹ️ À propos des Fiches de Report de Notes</h6>
                </Card.Header>
                <Card.Body>
                    <p className="mb-2"><strong>La Fiche de Report de Notes contient :</strong></p>
                    <ul className="mb-3">
                        <li>En-tête avec nom de l'école et année scolaire</li>
                        <li>Informations : Classe, Matière, Coefficient, Nom de l'enseignant</li>
                        <li>Tableau avec colonnes :
                            <ul>
                                <li>N° d'ordre, Matricule, Nom et Prénom de l'élève</li>
                                <li><strong>TRIMESTRE 1 :</strong> EVAL1, EVAL2, COMP</li>
                                <li><strong>TRIMESTRE 2 :</strong> EVAL3, EVAL4, COMP</li>
                                <li><strong>TRIMESTRE 3 :</strong> EVAL5, EVAL6, COMP</li>
                            </ul>
                        </li>
                        <li>Section statistiques pour chaque évaluation :
                            <ul>
                                <li>Effectif présent</li>
                                <li>Nombre d'admis (≥ 10/20)</li>
                                <li>Nombre d'échoués (&lt; 10/20)</li>
                                <li>% de réussite</li>
                                <li>% d'échec</li>
                            </ul>
                        </li>
                        <li>Espace pour signature de l'enseignant et date</li>
                    </ul>

                    <Alert variant="success" className="mb-2">
                        <strong>✅ Deux types de fiches :</strong>
                        <ul className="mb-0 mt-2">
                            <li><strong>Fiche Vierge :</strong> Sans notes, à remplir manuellement par l'enseignant</li>
                            <li><strong>Fiche avec Notes Réelles :</strong> Contient toutes les notes déjà saisies dans le système + statistiques automatiques calculées en temps réel</li>
                        </ul>
                    </Alert>

                    <Alert variant="info" className="mb-0">
                        <strong>💡 Mise à jour automatique :</strong> Au fur et à mesure qu'un enseignant remplit les notes dans le système,
                        la "Fiche avec Notes Réelles" se remplira automatiquement et les statistiques seront calculées en temps réel !
                    </Alert>
                </Card.Body>
            </Card>
        </div>
    );
};

export default MarkSheetGeneration;
