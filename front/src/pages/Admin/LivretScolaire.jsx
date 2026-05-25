import React, { useState, useEffect } from 'react';
import { Card, Button, Table, Badge, Row, Col, Alert, Spinner, Form, Tab, Tabs, ProgressBar } from 'react-bootstrap';
import { CheckCircle, PencilSquare, Printer, Book, Download } from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';
import { host } from '../../utils/fetch';

function LivretScolaire() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [activeTab, setActiveTab] = useState('config');

  // Config tab
  const [allClasses, setAllClasses] = useState([]);
  const [selectedExamClasses, setSelectedExamClasses] = useState([]);
  const [schoolYear, setSchoolYear] = useState('');
  const [savingConfig, setSavingConfig] = useState(false);

  // Edit tab
  const [examClasses, setExamClasses] = useState([]);
  const [selectedClass, setSelectedClass] = useState('');
  const [classData, setClassData] = useState(null);
  const [loadingData, setLoadingData] = useState(false);
  const [editedGrades, setEditedGrades] = useState({});
  const [savingGrades, setSavingGrades] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState(null);

  // Generate tab
  const [generating, setGenerating] = useState(false);
  const [genResult, setGenResult] = useState(null);
  const [genProgress, setGenProgress] = useState({ current: 0, total: 0, currentBatch: '' });

  useEffect(() => {
    loadExamClasses();
  }, []);

  const loadExamClasses = async () => {
    setLoading(true);
    try {
      const response = await secureApi.get('/livret-scolaire/exam-classes');
      if (response && response.success) {
        setAllClasses(response.data || []);
        setSchoolYear(response.school_year || '');
        const examIds = (response.data || [])
          .filter(c => c.is_exam_class)
          .map(c => c.id);
        setSelectedExamClasses(examIds);
        setExamClasses((response.data || []).filter(c => c.is_exam_class));
      }
    } catch (err) {
      setError('Erreur lors du chargement des classes');
    } finally {
      setLoading(false);
    }
  };

  const toggleExamClass = (classId) => {
    setSelectedExamClasses(prev =>
      prev.includes(classId)
        ? prev.filter(id => id !== classId)
        : [...prev, classId]
    );
  };

  const saveExamClasses = async () => {
    setSavingConfig(true);
    setError('');
    try {
      const response = await secureApi.post('/livret-scolaire/exam-classes', {
        class_series_ids: selectedExamClasses,
      });
      if (response && response.success) {
        setSuccess(response.message);
        await loadExamClasses();
        setTimeout(() => setSuccess(''), 3000);
      }
    } catch (err) {
      setError('Erreur lors de la sauvegarde');
    } finally {
      setSavingConfig(false);
    }
  };

  const loadClassData = async (seriesId) => {
    setLoadingData(true);
    setClassData(null);
    setSelectedStudent(null);
    setEditedGrades({});
    try {
      const response = await secureApi.get(`/livret-scolaire/class-data/${seriesId}`);
      if (response && response.success) {
        setClassData(response.data);
        const initial = {};
        (response.data.students || []).forEach(student => {
          student.subjects.forEach(sub => {
            const key = `${student.id}_${sub.class_series_subject_id}`;
            const hasAnyAdjustment =
              sub.adjusted_ev1 !== null || sub.adjusted_ev2 !== null || sub.adjusted_comp1 !== null ||
              sub.adjusted_ev3 !== null || sub.adjusted_ev4 !== null || sub.adjusted_comp2 !== null ||
              sub.adjusted_comp3 !== null ||
              sub.adjusted_trim1 !== null || sub.adjusted_trim2 !== null || sub.adjusted_trim3 !== null;

            if (hasAnyAdjustment) {
              initial[key] = {
                ev1: sub.adjusted_ev1 ?? sub.real_ev1,
                ev2: sub.adjusted_ev2 ?? sub.real_ev2,
                comp1: sub.adjusted_comp1 ?? sub.real_comp1,
                ev3: sub.adjusted_ev3 ?? sub.real_ev3,
                ev4: sub.adjusted_ev4 ?? sub.real_ev4,
                comp2: sub.adjusted_comp2 ?? sub.real_comp2,
                comp3: sub.adjusted_comp3 ?? sub.real_comp3,
                trim1: sub.adjusted_trim1,
                trim2: sub.adjusted_trim2,
                trim3: sub.adjusted_trim3,
              };
            }
          });
        });
        setEditedGrades(initial);
      }
    } catch (err) {
      setError('Erreur lors du chargement des donnees');
    } finally {
      setLoadingData(false);
    }
  };

  const handleGradeChange = (studentId, cssId, field, value) => {
    const key = `${studentId}_${cssId}`;
    const student = classData?.students?.find(s => s.id === studentId);
    const subject = student?.subjects?.find(s => s.class_series_subject_id === cssId);

    setEditedGrades(prev => {
      const existing = prev[key] || {
        ev1: subject?.real_ev1, ev2: subject?.real_ev2, comp1: subject?.real_comp1,
        ev3: subject?.real_ev3, ev4: subject?.real_ev4, comp2: subject?.real_comp2,
        comp3: subject?.real_comp3,
        trim1: null, trim2: null, trim3: null,
      };

      const updated = {
        ...existing,
        [field]: value === '' ? null : parseFloat(value),
      };

      // Quand une séquence change, nullifier le trim correspondant
      // pour forcer le recalcul backend depuis les EV/comp
      if (['ev1', 'ev2', 'comp1'].includes(field)) updated.trim1 = null;
      if (['ev3', 'ev4', 'comp2'].includes(field)) updated.trim2 = null;
      if (field === 'comp3') updated.trim3 = null;

      return { ...prev, [key]: updated };
    });
  };

  const saveGrades = async () => {
    setSavingGrades(true);
    setError('');
    try {
      const grades = Object.entries(editedGrades).map(([key, vals]) => {
        const [studentId, cssId] = key.split('_');
        return {
          student_id: parseInt(studentId),
          class_series_subject_id: parseInt(cssId),
          ev1: vals.ev1 ?? null, ev2: vals.ev2 ?? null, comp1: vals.comp1 ?? null,
          ev3: vals.ev3 ?? null, ev4: vals.ev4 ?? null, comp2: vals.comp2 ?? null,
          comp3: vals.comp3 ?? null,
          trim1: vals.trim1 ?? null, trim2: vals.trim2 ?? null, trim3: vals.trim3 ?? null,
        };
      });

      const response = await secureApi.post('/livret-scolaire/save-grades', { grades });
      if (response && response.success) {
        setSuccess(response.message);
        setTimeout(() => setSuccess(''), 3000);
      }
    } catch (err) {
      setError('Erreur lors de la sauvegarde des notes');
    } finally {
      setSavingGrades(false);
    }
  };

  const generateLivret = async (studentId) => {
    try {
      const response = await secureApi.post('/livret-scolaire/generate', {
        student_id: studentId,
      });
      if (response && response.success && response.file_path) {
        window.open(`${host}/storage/${response.file_path.replace('public/', '')}`, '_blank');
      }
    } catch (err) {
      setError('Erreur lors de la generation du livret');
    }
  };

  const batchGenerate = async () => {
    if (!selectedClass || !classData) {
      // Charger les donnees si pas encore fait
      if (selectedClass && !classData) {
        await loadClassData(selectedClass);
      }
      if (!classData) return;
    }

    // Utiliser les eleves du classData, sinon recharger
    let students = classData?.students;
    if (!students || students.length === 0) {
      // Recharger les donnees
      try {
        const resp = await secureApi.get(`/livret-scolaire/class-data/${selectedClass}`);
        if (resp?.success) {
          students = resp.data.students;
          setClassData(resp.data);
        }
      } catch (e) {
        setError('Erreur lors du chargement des eleves');
        return;
      }
    }

    if (!students || students.length === 0) {
      setError('Aucun eleve dans cette classe');
      return;
    }

    setGenerating(true);
    setGenResult(null);
    setError('');
    setGenProgress({ current: 0, total: students.length, currentBatch: '' });

    const BATCH_SIZE = 3;
    const allGenerated = [];
    const allErrors = [];

    for (let i = 0; i < students.length; i += BATCH_SIZE) {
      const batch = students.slice(i, i + BATCH_SIZE);
      const batchNames = batch.map(s => s.name).join(', ');
      setGenProgress({ current: i, total: students.length, currentBatch: batchNames });

      try {
        const response = await secureApi.post('/livret-scolaire/generate-small-batch', {
          student_ids: batch.map(s => s.id),
        });

        if (response?.success) {
          if (response.generated) allGenerated.push(...response.generated);
          if (response.errors) allErrors.push(...response.errors);
        }
      } catch (err) {
        batch.forEach(s => allErrors.push({ id: s.id, student: s.name, error: 'Timeout ou erreur reseau' }));
      }

      setGenProgress({ current: Math.min(i + BATCH_SIZE, students.length), total: students.length, currentBatch: '' });
    }

    const result = {
      success: true,
      generated: allGenerated.length,
      total: students.length,
      students: allGenerated,
      errors: allErrors,
    };

    setGenResult(result);
    if (allGenerated.length > 0) {
      setSuccess(`${allGenerated.length}/${students.length} livrets generes avec succes`);
      setTimeout(() => setSuccess(''), 5000);
    }
    setGenerating(false);
  };

  const downloadAll = async () => {
    if (!selectedClass) return;
    setError('');
    try {
      const response = await secureApi.post('/livret-scolaire/download-all', {
        series_id: parseInt(selectedClass),
      });
      if (response && response.success && response.download_url) {
        window.open(`${host}/storage/${response.download_url}`, '_blank');
      } else {
        setError(response?.error || 'Erreur lors du telechargement');
      }
    } catch (err) {
      setError('Erreur lors de la fusion des livrets');
    }
  };

  // Calcul de la moyenne trimestre a partir des sequences
  const calcTrimFromSeq = (s1, s2, comp) => {
    const hasAny = s1 !== null || s2 !== null || comp !== null;
    if (!hasAny) return null;
    const ds = ((s1 ?? 0) + (s2 ?? 0)) / 2;
    return (ds + (comp ?? 0)) / 2;
  };

  const getEffectiveTrim = (subject, edits, trimNum) => {
    const key = `${selectedStudent?.id}_${subject.class_series_subject_id}`;
    const adj = edits[key];
    // Si trim directement modifie, utiliser cette valeur
    const trimKey = `trim${trimNum}`;
    if (adj && adj[trimKey] !== undefined && adj[trimKey] !== subject[`real_${trimKey}`]) {
      return adj[trimKey];
    }
    // Sinon recalculer depuis les sequences si modifiees
    if (trimNum === 1) {
      const ev1 = adj?.ev1 ?? subject.real_ev1;
      const ev2 = adj?.ev2 ?? subject.real_ev2;
      const comp1 = adj?.comp1 ?? subject.real_comp1;
      if (adj && (adj.ev1 !== undefined || adj.ev2 !== undefined || adj.comp1 !== undefined)) {
        return calcTrimFromSeq(ev1, ev2, comp1);
      }
    } else if (trimNum === 2) {
      const ev3 = adj?.ev3 ?? subject.real_ev3;
      const ev4 = adj?.ev4 ?? subject.real_ev4;
      const comp2 = adj?.comp2 ?? subject.real_comp2;
      if (adj && (adj.ev3 !== undefined || adj.ev4 !== undefined || adj.comp2 !== undefined)) {
        return calcTrimFromSeq(ev3, ev4, comp2);
      }
    } else if (trimNum === 3) {
      if (adj && adj.comp3 !== undefined) {
        return adj.comp3;
      }
    }
    return adj?.[trimKey] ?? subject[`real_${trimKey}`] ?? 0;
  };

  const getAnnualAvg = (subject, edits) => {
    const t1 = getEffectiveTrim(subject, edits, 1) ?? 0;
    const t2 = getEffectiveTrim(subject, edits, 2) ?? 0;
    const t3 = getEffectiveTrim(subject, edits, 3) ?? 0;
    return ((t1 + t2 + t3) / 3).toFixed(2);
  };

  const seqFields = ['ev1', 'ev2', 'comp1', 'ev3', 'ev4', 'comp2', 'comp3'];
  const allEditFields = [...seqFields, 'trim1', 'trim2', 'trim3'];

  const hasBeenModified = (subject) => {
    const key = `${selectedStudent?.id}_${subject.class_series_subject_id}`;
    const adj = editedGrades[key];
    if (!adj) return false;
    return allEditFields.some(f => adj[f] !== null && adj[f] !== undefined && adj[f] !== subject[`real_${f}`]);
  };

  if (loading) {
    return (
      <div className="d-flex justify-content-center align-items-center" style={{ minHeight: 300 }}>
        <Spinner animation="border" />
      </div>
    );
  }

  return (
    <div className="container-fluid py-3">
      <div className="d-flex justify-content-between align-items-center mb-3">
        <h4 className="mb-0"><Book className="me-2" />Livret Scolaire {schoolYear && `- ${schoolYear}`}</h4>
      </div>

      {error && <Alert variant="danger" dismissible onClose={() => setError('')}>{error}</Alert>}
      {success && <Alert variant="success" dismissible onClose={() => setSuccess('')}>{success}</Alert>}

      <Tabs activeKey={activeTab} onSelect={setActiveTab} className="mb-3">
        <Tab eventKey="config" title="Classes d'examen">
          <Card>
            <Card.Header className="d-flex justify-content-between align-items-center">
              <strong>Selectionner les classes d'examen</strong>
              <Button
                variant="primary"
                size="sm"
                onClick={saveExamClasses}
                disabled={savingConfig}
              >
                {savingConfig ? <Spinner size="sm" className="me-1" /> : <CheckCircle className="me-1" />}
                Enregistrer
              </Button>
            </Card.Header>
            <Card.Body>
              <p className="text-muted mb-3">
                Cochez les classes dont les eleves passent un examen officiel (BEPC, BAC, Probatoire, GCE, etc.)
              </p>
              <Row>
                {allClasses.map(cls => (
                  <Col key={cls.id} xs={12} sm={6} md={4} lg={3} className="mb-2">
                    <Form.Check
                      type="checkbox"
                      id={`exam-class-${cls.id}`}
                      label={
                        <span>
                          {cls.name}
                          <Badge bg="secondary" className="ms-1" pill>{cls.student_count}</Badge>
                        </span>
                      }
                      checked={selectedExamClasses.includes(cls.id)}
                      onChange={() => toggleExamClass(cls.id)}
                    />
                  </Col>
                ))}
              </Row>
              {allClasses.length === 0 && (
                <Alert variant="info">Aucune classe trouvee</Alert>
              )}
            </Card.Body>
          </Card>
        </Tab>

        <Tab eventKey="edit" title="Modifier les notes">
          <Card>
            <Card.Header>
              <Row className="align-items-center">
                <Col md={5}>
                  <Form.Select
                    value={selectedClass}
                    onChange={(e) => {
                      setSelectedClass(e.target.value);
                      setSelectedStudent(null);
                      if (e.target.value) loadClassData(e.target.value);
                    }}
                  >
                    <option value="">-- Choisir une classe d'examen --</option>
                    {examClasses.map(cls => (
                      <option key={cls.id} value={cls.id}>{cls.name} ({cls.student_count} eleves)</option>
                    ))}
                  </Form.Select>
                </Col>
                <Col md={7} className="text-end">
                  {Object.keys(editedGrades).length > 0 && (
                    <Button
                      variant="success"
                      size="sm"
                      onClick={saveGrades}
                      disabled={savingGrades}
                    >
                      {savingGrades ? <Spinner size="sm" className="me-1" /> : <CheckCircle className="me-1" />}
                      Sauvegarder les modifications
                    </Button>
                  )}
                </Col>
              </Row>
            </Card.Header>
            <Card.Body>
              {loadingData && (
                <div className="text-center py-4"><Spinner animation="border" /></div>
              )}

              {!loadingData && classData && !selectedStudent && (
                <Table bordered hover size="sm" responsive>
                  <thead className="table-dark">
                    <tr>
                      <th style={{ width: '5%' }}>#</th>
                      <th style={{ width: '35%' }}>Nom de l'eleve</th>
                      <th style={{ width: '20%' }}>Matricule</th>
                      <th style={{ width: '20%' }} className="text-center">Notes modifiees</th>
                      <th style={{ width: '20%' }} className="text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    {classData.students.map((st, idx) => {
                      const modifiedCount = Object.keys(editedGrades).filter(k => k.startsWith(`${st.id}_`)).length;
                      return (
                        <tr key={st.id}>
                          <td>{idx + 1}</td>
                          <td className="fw-bold">{st.name}</td>
                          <td>{st.matricule}</td>
                          <td className="text-center">
                            {modifiedCount > 0 ? (
                              <Badge bg="warning" text="dark">{modifiedCount} matiere(s)</Badge>
                            ) : (
                              <Badge bg="secondary">Aucune</Badge>
                            )}
                          </td>
                          <td className="text-center">
                            <Button
                              variant="outline-primary"
                              size="sm"
                              onClick={() => setSelectedStudent(st)}
                            >
                              <PencilSquare className="me-1" /> Modifier
                            </Button>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </Table>
              )}

              {!loadingData && selectedStudent && (
                <>
                  <div className="d-flex justify-content-between align-items-center mb-3">
                    <div>
                      <Button
                        variant="outline-secondary"
                        size="sm"
                        onClick={() => setSelectedStudent(null)}
                      >
                        &larr; Retour a la liste
                      </Button>
                    </div>
                    <Alert variant="info" className="py-1 px-3 mb-0 d-inline-flex align-items-center">
                      <strong>{selectedStudent.name}</strong>
                      <span className="mx-2">-</span>
                      Matricule: {selectedStudent.matricule}
                      <Badge bg={Object.keys(editedGrades).some(k => k.startsWith(`${selectedStudent.id}_`)) ? 'warning' : 'secondary'} className="ms-3">
                        {Object.keys(editedGrades).filter(k => k.startsWith(`${selectedStudent.id}_`)).length} matiere(s) modifiee(s)
                      </Badge>
                    </Alert>
                  </div>
                  <Table bordered hover size="sm" responsive>
                    <thead className="table-dark">
                      <tr>
                        <th rowSpan="2" style={{ verticalAlign: 'middle' }}>Matiere</th>
                        <th rowSpan="2" className="text-center" style={{ verticalAlign: 'middle', width: 45 }}>Coef</th>
                        <th colSpan="3" className="text-center" style={{ background: '#4a2070' }}>Trimestre 1</th>
                        <th colSpan="3" className="text-center" style={{ background: '#4a2070' }}>Trimestre 2</th>
                        <th className="text-center" style={{ background: '#4a2070' }}>Trim 3</th>
                        <th colSpan="3" className="text-center" style={{ background: '#2c3e50' }}>Moyennes Trimestrielles</th>
                        <th rowSpan="2" className="text-center" style={{ verticalAlign: 'middle', width: 65 }}>Moy. An.</th>
                        <th rowSpan="2" className="text-center" style={{ verticalAlign: 'middle', width: 70 }}>Statut</th>
                      </tr>
                      <tr>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>EV1</th>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>EV2</th>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>Comp1</th>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>EV3</th>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>EV4</th>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>Comp2</th>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>Comp3</th>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>T1</th>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>T2</th>
                        <th className="text-center" style={{ fontSize: '0.7rem', width: 55 }}>T3</th>
                      </tr>
                    </thead>
                    <tbody>
                      {selectedStudent.subjects.map(sub => {
                        const key = `${selectedStudent.id}_${sub.class_series_subject_id}`;
                        const adj = editedGrades[key];
                        const modified = hasBeenModified(sub);
                        const avg = getAnnualAvg(sub, editedGrades);

                        const seqInput = (field) => (
                          <Form.Control
                            type="number"
                            size="sm"
                            min="0"
                            max="20"
                            step="0.25"
                            style={{ width: 52, margin: '0 auto', textAlign: 'center', fontSize: '0.75rem', padding: '2px 4px' }}
                            value={adj?.[field] ?? sub[`real_${field}`] ?? ''}
                            onChange={(e) => handleGradeChange(selectedStudent.id, sub.class_series_subject_id, field, e.target.value)}
                          />
                        );

                        return (
                          <tr key={sub.class_series_subject_id} className={modified ? 'table-warning' : ''}>
                            <td className="fw-bold" style={{ fontSize: '0.8rem' }}>{sub.subject_name}</td>
                            <td className="text-center">{sub.coefficient}</td>
                            <td className="text-center">{seqInput('ev1')}</td>
                            <td className="text-center">{seqInput('ev2')}</td>
                            <td className="text-center">{seqInput('comp1')}</td>
                            <td className="text-center">{seqInput('ev3')}</td>
                            <td className="text-center">{seqInput('ev4')}</td>
                            <td className="text-center">{seqInput('comp2')}</td>
                            <td className="text-center">{seqInput('comp3')}</td>
                            <td className="text-center">{seqInput('trim1')}</td>
                            <td className="text-center">{seqInput('trim2')}</td>
                            <td className="text-center">{seqInput('trim3')}</td>
                            <td className="text-center fw-bold" style={{ color: parseFloat(avg) >= 10 ? '#27ae60' : '#e74c3c' }}>
                              {avg}
                            </td>
                            <td className="text-center">
                              {modified ? (
                                <Badge bg="warning" text="dark"><PencilSquare size={10} /> Modifie</Badge>
                              ) : (
                                <Badge bg="secondary">Original</Badge>
                              )}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </Table>
                </>
              )}

              {!loadingData && !classData && !selectedClass && (
                <Alert variant="info">Selectionnez une classe d'examen pour commencer</Alert>
              )}
            </Card.Body>
          </Card>
        </Tab>

        <Tab eventKey="generate" title="Generer les livrets">
          <Card>
            <Card.Header>
              <strong>Generation des livrets scolaires</strong>
            </Card.Header>
            <Card.Body>
              <Row className="align-items-end mb-3">
                <Col md={5}>
                  <Form.Label>Classe d'examen</Form.Label>
                  <Form.Select
                    value={selectedClass}
                    onChange={(e) => {
                      setSelectedClass(e.target.value);
                      setGenResult(null);
                      if (e.target.value) loadClassData(e.target.value);
                    }}
                  >
                    <option value="">-- Choisir une classe --</option>
                    {examClasses.map(cls => (
                      <option key={cls.id} value={cls.id}>{cls.name} ({cls.student_count} eleves)</option>
                    ))}
                  </Form.Select>
                </Col>
                <Col md={7} className="d-flex gap-2">
                  <Button
                    variant="primary"
                    onClick={batchGenerate}
                    disabled={!selectedClass || generating}
                  >
                    {generating ? (
                      <><Spinner size="sm" className="me-1" /> Generation en cours...</>
                    ) : (
                      <><Printer className="me-1" /> Generer tous les livrets</>
                    )}
                  </Button>
                  {genResult?.students?.length > 0 && (
                    <Button
                      variant="success"
                      onClick={downloadAll}
                    >
                      <Download className="me-1" /> Telecharger tout en 1 PDF
                    </Button>
                  )}
                </Col>
              </Row>

              {generating && genProgress.total > 0 && (
                <div className="mb-3">
                  <div className="d-flex justify-content-between mb-1">
                    <small className="text-muted">
                      Generation en cours... {genProgress.current}/{genProgress.total} eleves
                    </small>
                    <small className="text-muted">
                      {Math.round((genProgress.current / genProgress.total) * 100)}%
                    </small>
                  </div>
                  <ProgressBar
                    animated
                    striped
                    variant="primary"
                    now={(genProgress.current / genProgress.total) * 100}
                  />
                  {genProgress.currentBatch && (
                    <small className="text-primary mt-1 d-block">
                      En cours : {genProgress.currentBatch}
                    </small>
                  )}
                </div>
              )}

              {genResult && (
                <>
                  <Alert variant={genResult.errors?.length > 0 ? 'warning' : 'success'} className="mb-3">
                    <strong>Resultat :</strong> {genResult.generated}/{genResult.total} livrets generes
                    {genResult.errors?.length > 0 && (
                      <div className="mt-2">
                        <strong>Erreurs ({genResult.errors.length}) :</strong>
                        <ul className="mb-0 mt-1">
                          {genResult.errors.map((err, i) => (
                            <li key={i}><strong>{err.student}</strong> : {err.error}</li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </Alert>

                  {genResult.students?.length > 0 && (
                    <Table bordered hover size="sm">
                      <thead className="table-dark">
                        <tr>
                          <th style={{ width: '5%' }}>#</th>
                          <th style={{ width: '55%' }}>Nom de l'eleve</th>
                          <th style={{ width: '20%' }} className="text-center">Statut</th>
                          <th style={{ width: '20%' }} className="text-center">Telecharger</th>
                        </tr>
                      </thead>
                      <tbody>
                        {genResult.students.map((st, idx) => (
                          <tr key={st.id}>
                            <td>{idx + 1}</td>
                            <td className="fw-bold">{st.name}</td>
                            <td className="text-center">
                              <Badge bg="success">Genere</Badge>
                            </td>
                            <td className="text-center">
                              <Button
                                variant="outline-primary"
                                size="sm"
                                onClick={() => window.open(`${host}/storage/${st.file_path.replace('public/', '')}`, '_blank')}
                              >
                                <Download className="me-1" /> PDF
                              </Button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  )}
                </>
              )}

              {examClasses.length === 0 && (
                <Alert variant="warning">
                  Aucune classe d'examen definie. Allez dans l'onglet "Classes d'examen" pour en selectionner.
                </Alert>
              )}
            </Card.Body>
          </Card>
        </Tab>
      </Tabs>
    </div>
  );
}

export default LivretScolaire;
