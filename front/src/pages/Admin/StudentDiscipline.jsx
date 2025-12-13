import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, CardBody, Table, Button, Input, Label, FormGroup, Alert, Spinner } from 'reactstrap';
import { Search, Save, RefreshCw } from 'react-bootstrap-icons';
import secureApi from '../../utils/api';

const StudentDiscipline = () => {
  const [sections, setSections] = useState([]);
  const [levels, setLevels] = useState([]);
  const [classes, setClasses] = useState([]);
  const [series, setSeries] = useState([]);
  const [sequences, setSequences] = useState([]);
  const [trimesters, setTrimesters] = useState([]);

  const [selectedSection, setSelectedSection] = useState('');
  const [selectedLevel, setSelectedLevel] = useState('');
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedSeries, setSelectedSeries] = useState('');
  const [selectedPeriodType, setSelectedPeriodType] = useState('sequence');
  const [selectedSequence, setSelectedSequence] = useState('');
  const [selectedTrimester, setSelectedTrimester] = useState('');

  const [students, setStudents] = useState([]);
  const [disciplineData, setDisciplineData] = useState({});
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Charger les données initiales
  useEffect(() => {
    fetchSections();
    fetchSequences();
    fetchTrimesters();
  }, []);

  useEffect(() => {
    if (selectedSection) {
      fetchLevels(selectedSection);
    }
  }, [selectedSection]);

  useEffect(() => {
    if (selectedLevel) {
      fetchClasses(selectedLevel);
    }
  }, [selectedLevel]);

  useEffect(() => {
    if (selectedClass) {
      fetchSeries(selectedClass);
    }
  }, [selectedClass]);

  const fetchSections = async () => {
    try {
      const response = await secureApi.get('/sections');
      setSections(response.data.data || response.data);
    } catch (err) {
      console.error('Error fetching sections:', err);
    }
  };

  const fetchLevels = async (sectionId) => {
    try {
      const response = await secureApi.get(`/levels?section_id=${sectionId}`);
      setLevels(response.data.data || response.data);
    } catch (err) {
      console.error('Error fetching levels:', err);
    }
  };

  const fetchClasses = async (levelId) => {
    try {
      const response = await secureApi.get(`/school-classes?level_id=${levelId}`);
      setClasses(response.data.data || response.data);
    } catch (err) {
      console.error('Error fetching classes:', err);
    }
  };

  const fetchSeries = async (classId) => {
    try {
      const response = await secureApi.get(`/class-series?school_class_id=${classId}`);
      setSeries(response.data.data || response.data);
    } catch (err) {
      console.error('Error fetching series:', err);
    }
  };

  const fetchSequences = async () => {
    try {
      const response = await secureApi.get('/sequences');
      setSequences(response.data.data || response.data);
    } catch (err) {
      console.error('Error fetching sequences:', err);
    }
  };

  const fetchTrimesters = async () => {
    try {
      const response = await secureApi.get('/trimesters');
      setTrimesters(response.data.data || response.data);
    } catch (err) {
      console.error('Error fetching trimesters:', err);
    }
  };

  const fetchDisciplineData = async () => {
    if (!selectedSeries) {
      setError('Veuillez sélectionner une série');
      return;
    }

    if (!selectedSequence && !selectedTrimester) {
      setError('Veuillez sélectionner une période (séquence ou trimestre)');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const params = {
        class_series_id: selectedSeries,
        sequence_id: selectedPeriodType === 'sequence' ? selectedSequence : null,
        trimester_id: selectedPeriodType === 'trimester' ? selectedTrimester : null
      };

      const response = await secureApi.get('/discipline/class', { params });

      if (response.data.success) {
        setStudents(response.data.data);

        // Initialiser les données de discipline
        const initialData = {};
        response.data.data.forEach(student => {
          initialData[student.student_id] = student.discipline || {
            delays_justified: 0,
            delays_unjustified: 0,
            absences_justified: 0,
            absences_unjustified: 0,
            blame_conduct: 0,
            blame_work: 0,
            warning_conduct: 0,
            warning_work: 0,
            detention_hours: 0,
            exclusion_days: 0,
            observations: ''
          };
        });
        setDisciplineData(initialData);
      }
    } catch (err) {
      console.error('Error fetching discipline data:', err);
      setError('Erreur lors du chargement des données');
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (studentId, field, value) => {
    setDisciplineData(prev => ({
      ...prev,
      [studentId]: {
        ...prev[studentId],
        [field]: value === '' ? 0 : parseInt(value) || 0
      }
    }));
  };

  const handleSave = async () => {
    setSaving(true);
    setError('');
    setSuccess('');

    try {
      const studentsData = Object.keys(disciplineData).map(studentId => ({
        student_id: parseInt(studentId),
        ...disciplineData[studentId]
      }));

      await secureApi.post('/discipline/bulk-store', {
        students: studentsData,
        sequence_id: selectedPeriodType === 'sequence' ? selectedSequence : null,
        trimester_id: selectedPeriodType === 'trimester' ? selectedTrimester : null
      });

      setSuccess('✅ Données enregistrées avec succès !');
      setTimeout(() => setSuccess(''), 3000);
    } catch (err) {
      console.error('Error saving discipline data:', err);
      setError('❌ Erreur lors de l\'enregistrement');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Container fluid className="py-4">
      <h2 className="mb-4">📋 Gestion de la Discipline</h2>

      {/* Filtres */}
      <Card className="mb-4">
        <CardBody>
          <Row>
            <Col md={3}>
              <FormGroup>
                <Label>Section</Label>
                <Input type="select" value={selectedSection} onChange={(e) => setSelectedSection(e.target.value)}>
                  <option value="">Sélectionner...</option>
                  {sections.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                </Input>
              </FormGroup>
            </Col>
            <Col md={3}>
              <FormGroup>
                <Label>Niveau</Label>
                <Input type="select" value={selectedLevel} onChange={(e) => setSelectedLevel(e.target.value)} disabled={!selectedSection}>
                  <option value="">Sélectionner...</option>
                  {levels.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
                </Input>
              </FormGroup>
            </Col>
            <Col md={3}>
              <FormGroup>
                <Label>Classe</Label>
                <Input type="select" value={selectedClass} onChange={(e) => setSelectedClass(e.target.value)} disabled={!selectedLevel}>
                  <option value="">Sélectionner...</option>
                  {classes.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                </Input>
              </FormGroup>
            </Col>
            <Col md={3}>
              <FormGroup>
                <Label>Série</Label>
                <Input type="select" value={selectedSeries} onChange={(e) => setSelectedSeries(e.target.value)} disabled={!selectedClass}>
                  <option value="">Sélectionner...</option>
                  {series.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                </Input>
              </FormGroup>
            </Col>
          </Row>

          <Row>
            <Col md={4}>
              <FormGroup>
                <Label>Type de période</Label>
                <Input type="select" value={selectedPeriodType} onChange={(e) => setSelectedPeriodType(e.target.value)}>
                  <option value="sequence">Séquence</option>
                  <option value="trimester">Trimestre</option>
                </Input>
              </FormGroup>
            </Col>
            {selectedPeriodType === 'sequence' && (
              <Col md={4}>
                <FormGroup>
                  <Label>Séquence</Label>
                  <Input type="select" value={selectedSequence} onChange={(e) => setSelectedSequence(e.target.value)}>
                    <option value="">Sélectionner...</option>
                    {sequences.filter(s => !s.is_composition).map(s => (
                      <option key={s.id} value={s.id}>Séquence {s.number}</option>
                    ))}
                  </Input>
                </FormGroup>
              </Col>
            )}
            {selectedPeriodType === 'trimester' && (
              <Col md={4}>
                <FormGroup>
                  <Label>Trimestre</Label>
                  <Input type="select" value={selectedTrimester} onChange={(e) => setSelectedTrimester(e.target.value)}>
                    <option value="">Sélectionner...</option>
                    {trimesters.map(t => (
                      <option key={t.id} value={t.id}>Trimestre {t.number}</option>
                    ))}
                  </Input>
                </FormGroup>
              </Col>
            )}
            <Col md={4} className="d-flex align-items-end">
              <Button color="primary" onClick={fetchDisciplineData} disabled={loading}>
                <Search className="me-2" />
                {loading ? 'Chargement...' : 'Charger'}
              </Button>
            </Col>
          </Row>
        </CardBody>
      </Card>

      {/* Messages */}
      {error && <Alert color="danger">{error}</Alert>}
      {success && <Alert color="success">{success}</Alert>}

      {/* Tableau de saisie */}
      {students.length > 0 && (
        <Card>
          <CardBody>
            <div className="d-flex justify-content-between mb-3">
              <h5>Élèves : {students.length}</h5>
              <Button color="success" onClick={handleSave} disabled={saving}>
                {saving ? <Spinner size="sm" className="me-2" /> : <Save className="me-2" />}
                Enregistrer tout
              </Button>
            </div>

            <div style={{ overflowX: 'auto' }}>
              <Table bordered striped size="sm">
                <thead className="table-dark">
                  <tr>
                    <th rowSpan="2" style={{ verticalAlign: 'middle' }}>Élève</th>
                    <th colSpan="2" className="text-center">Retard (h)</th>
                    <th colSpan="2" className="text-center">Abs (h)</th>
                    <th colSpan="2" className="text-center">Blâme</th>
                    <th colSpan="2" className="text-center">Avert.</th>
                    <th rowSpan="2" className="text-center" style={{ verticalAlign: 'middle' }}>Cons (h)</th>
                    <th rowSpan="2" className="text-center" style={{ verticalAlign: 'middle' }}>Excl (j)</th>
                  </tr>
                  <tr>
                    <th className="text-center">Just</th>
                    <th className="text-center">Non Just</th>
                    <th className="text-center">Just</th>
                    <th className="text-center">Non Just</th>
                    <th className="text-center">Cond</th>
                    <th className="text-center">Trav</th>
                    <th className="text-center">Cond</th>
                    <th className="text-center">Trav</th>
                  </tr>
                </thead>
                <tbody>
                  {students.map(student => (
                    <tr key={student.student_id}>
                      <td>
                        <strong>{student.student_name}</strong>
                        <br />
                        <small className="text-muted">{student.matricule}</small>
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.delays_justified || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'delays_justified', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.delays_unjustified || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'delays_unjustified', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.absences_justified || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'absences_justified', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.absences_unjustified || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'absences_unjustified', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.blame_conduct || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'blame_conduct', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.blame_work || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'blame_work', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.warning_conduct || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'warning_conduct', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.warning_work || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'warning_work', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.detention_hours || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'detention_hours', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                      <td>
                        <Input
                          type="number"
                          min="0"
                          size="sm"
                          value={disciplineData[student.student_id]?.exclusion_days || 0}
                          onChange={(e) => handleInputChange(student.student_id, 'exclusion_days', e.target.value)}
                          style={{ width: '60px' }}
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            </div>
          </CardBody>
        </Card>
      )}

      {loading && (
        <div className="text-center my-5">
          <Spinner color="primary" />
          <p className="mt-2">Chargement des données...</p>
        </div>
      )}
    </Container>
  );
};

export default StudentDiscipline;
