import React, { useEffect, useMemo, useState } from 'react';
import { Card, Form, Button, Row, Col, Alert, Table } from 'react-bootstrap';
import secureApiEndpoints from '../../utils/apiMigration';

export default function ManualDiscounts() {
  const [sections, setSections] = useState([]);
  const [levels, setLevels] = useState([]);
  const [classes, setClasses] = useState([]);
  const [series, setSeries] = useState([]);
  const [students, setStudents] = useState([]);

  const [selectedSection, setSelectedSection] = useState('');
  const [selectedLevel, setSelectedLevel] = useState('');
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedSeries, setSelectedSeries] = useState('');
  const [selectedStudent, setSelectedStudent] = useState('');

  const [amount, setAmount] = useState('');
  const [reason, setReason] = useState('');

  const [currentDiscount, setCurrentDiscount] = useState(null);
  const [discountsInSeries, setDiscountsInSeries] = useState([]);

  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const canSubmit = useMemo(() => {
    return selectedStudent && amount !== '' && Number(amount) >= 0;
  }, [selectedStudent, amount]);

  useEffect(() => {
    (async () => {
      try {
        const [secRes, lvlRes, clsRes] = await Promise.all([
          secureApiEndpoints.sections.getAll(),
          secureApiEndpoints.levels.getAll(),
          secureApiEndpoints.schoolClasses.getAll(),
        ]);

        setSections(secRes?.data?.data || secRes?.data || []);
        setLevels(lvlRes?.data?.data || lvlRes?.data || []);
        setClasses(clsRes?.data?.data || clsRes?.data || []);
      } catch (e) {
        setError("Impossible de charger les filtres (sections/niveaux/classes)");
      }
    })();
  }, []);

  // Charger les séries quand on choisit une classe
  useEffect(() => {
    if (!selectedClass) {
      setSeries([]);
      setSelectedSeries('');
      return;
    }

    (async () => {
      try {
        const res = await secureApiEndpoints.schoolClasses.getSeriesInSameClass(selectedClass);
        setSeries(res?.data?.data || res?.data || []);
      } catch (e) {
        setError("Impossible de charger les séries");
      }
    })();
  }, [selectedClass]);

  // Charger les élèves quand on choisit une série
  useEffect(() => {
    if (!selectedSeries) {
      setStudents([]);
      setSelectedStudent('');
      return;
    }

    (async () => {
      try {
        const res = await secureApiEndpoints.students.getByClassSeries(selectedSeries);
        // Ensure students is always an array
        const studentsData = res?.data?.data || res?.data || [];
        setStudents(Array.isArray(studentsData) ? studentsData : []);

        // Charger les réductions déjà existantes sur la série
        const dRes = await secureApiEndpoints.manualDiscounts.list({ class_series_id: selectedSeries });
        const discountsData = dRes?.data?.data || dRes?.data || [];
        setDiscountsInSeries(Array.isArray(discountsData) ? discountsData : []);
      } catch (e) {
        setError("Impossible de charger les élèves ou les réductions");
      }
    })();
  }, [selectedSeries]);

  // Charger la réduction courante d'un élève
  useEffect(() => {
    if (!selectedStudent) {
      setCurrentDiscount(null);
      return;
    }

    (async () => {
      try {
        const res = await secureApiEndpoints.manualDiscounts.getForStudent(selectedStudent);
        const d = res?.data?.data || null;
        setCurrentDiscount(d);
        setAmount(d?.amount != null ? String(d.amount) : '');
        setReason(d?.reason || '');
      } catch (e) {
        setError("Impossible de charger la réduction de l'élève");
      }
    })();
  }, [selectedStudent]);

  const handleSave = async () => {
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      await secureApiEndpoints.manualDiscounts.upsert({
        student_id: Number(selectedStudent),
        amount: Number(amount || 0),
        reason: reason || null,
      });

      setSuccess('Réduction enregistrée. Le reste à payer sera recalculé automatiquement.');

      // refresh
      const res = await secureApiEndpoints.manualDiscounts.getForStudent(selectedStudent);
      setCurrentDiscount(res?.data?.data || null);

      const dRes = await secureApiEndpoints.manualDiscounts.list({ class_series_id: selectedSeries });
      setDiscountsInSeries(dRes?.data?.data || dRes?.data || []);
    } catch (e) {
      setError("Erreur lors de l'enregistrement de la réduction");
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!selectedStudent) return;

    setError('');
    setSuccess('');
    setLoading(true);

    try {
      await secureApiEndpoints.manualDiscounts.deleteForStudent(selectedStudent);
      setSuccess('Réduction supprimée.');
      setCurrentDiscount(null);
      setAmount('');
      setReason('');

      const dRes = await secureApiEndpoints.manualDiscounts.list({ class_series_id: selectedSeries });
      setDiscountsInSeries(dRes?.data?.data || dRes?.data || []);
    } catch (e) {
      setError('Erreur lors de la suppression.');
    } finally {
      setLoading(false);
    }
  };

  const filteredLevels = useMemo(() => {
    if (!selectedSection) return levels;
    return levels.filter(l => String(l.section_id) === String(selectedSection));
  }, [levels, selectedSection]);

  const filteredClasses = useMemo(() => {
    if (!selectedLevel) return classes;
    return classes.filter(c => String(c.level_id) === String(selectedLevel));
  }, [classes, selectedLevel]);

  return (
    <div className="container-fluid p-3">
      <h3>Réductions (manuelles)</h3>
      <p className="text-muted">
        Appliquer une réduction manuelle (montant fixe) sur la <strong>scolarité</strong> uniquement (jamais sur l'inscription).
      </p>

      {error && <Alert variant="danger">{error}</Alert>}
      {success && <Alert variant="success">{success}</Alert>}

      <Card className="mb-3">
        <Card.Body>
          <Row className="g-3">
            <Col md={4}>
              <Form.Group>
                <Form.Label>Section</Form.Label>
                <Form.Select value={selectedSection} onChange={(e) => {
                  setSelectedSection(e.target.value);
                  setSelectedLevel('');
                  setSelectedClass('');
                  setSelectedSeries('');
                  setSelectedStudent('');
                }}>
                  <option value="">-- Toutes / Choisir --</option>
                  {sections.map(s => (
                    <option key={s.id} value={s.id}>{s.name}</option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={4}>
              <Form.Group>
                <Form.Label>Cycle / Niveau</Form.Label>
                <Form.Select value={selectedLevel} onChange={(e) => {
                  setSelectedLevel(e.target.value);
                  setSelectedClass('');
                  setSelectedSeries('');
                  setSelectedStudent('');
                }}>
                  <option value="">-- Tous / Choisir --</option>
                  {filteredLevels.map(l => (
                    <option key={l.id} value={l.id}>{l.name}</option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={4}>
              <Form.Group>
                <Form.Label>Classe</Form.Label>
                <Form.Select value={selectedClass} onChange={(e) => {
                  setSelectedClass(e.target.value);
                  setSelectedSeries('');
                  setSelectedStudent('');
                }}>
                  <option value="">-- Choisir --</option>
                  {filteredClasses.map(c => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={4}>
              <Form.Group>
                <Form.Label>Série</Form.Label>
                <Form.Select value={selectedSeries} onChange={(e) => {
                  setSelectedSeries(e.target.value);
                  setSelectedStudent('');
                }}>
                  <option value="">-- Choisir --</option>
                  {series.map(cs => (
                    <option key={cs.id} value={cs.id}>{cs.name}</option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={8}>
              <Form.Group>
                <Form.Label>Élève</Form.Label>
                <Form.Select value={selectedStudent} onChange={(e) => setSelectedStudent(e.target.value)}>
                  <option value="">-- Choisir --</option>
                  {Array.isArray(students) && students.map(st => (
                    <option key={st.id} value={st.id}>{st.full_name || `${st.last_name || ''} ${st.first_name || ''}`}</option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={4}>
              <Form.Group>
                <Form.Label>Montant réduction (FCFA)</Form.Label>
                <Form.Control
                  type="number"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  min={0}
                  step={1}
                />
              </Form.Group>
            </Col>

            <Col md={8}>
              <Form.Group>
                <Form.Label>Motif (texte libre)</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={2}
                  value={reason}
                  onChange={(e) => setReason(e.target.value)}
                  placeholder="Ex: remise spéciale..."
                />
              </Form.Group>
            </Col>

            <Col md={4} className="d-flex align-items-end gap-2">
              <Button disabled={!canSubmit || loading} onClick={handleSave}>
                {loading ? 'Enregistrement...' : 'Enregistrer'}
              </Button>
              <Button variant="outline-danger" disabled={!selectedStudent || loading} onClick={handleDelete}>
                Supprimer
              </Button>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {selectedStudent && (
        <Card className="mb-3">
          <Card.Header>Réduction actuelle</Card.Header>
          <Card.Body>
            {currentDiscount ? (
              <div>
                <div><strong>Montant :</strong> {currentDiscount.amount} FCFA</div>
                <div><strong>Motif :</strong> {currentDiscount.reason || '—'}</div>
              </div>
            ) : (
              <div className="text-muted">Aucune réduction manuelle pour cet élève.</div>
            )}
          </Card.Body>
        </Card>
      )}

      <Card>
        <Card.Header>Réductions existantes dans la série</Card.Header>
        <Card.Body>
          <Table striped bordered size="sm" responsive>
            <thead>
              <tr>
                <th>Élève</th>
                <th>Montant</th>
                <th>Motif</th>
              </tr>
            </thead>
            <tbody>
              {(!Array.isArray(discountsInSeries) || discountsInSeries.length === 0) ? (
                <tr><td colSpan={3} className="text-center text-muted">Aucune réduction</td></tr>
              ) : discountsInSeries.map(d => (
                <tr key={d.id}>
                  <td>{d.student?.full_name || `${d.student?.last_name || ''} ${d.student?.first_name || ''}`}</td>
                  <td>{d.amount}</td>
                  <td>{d.reason || '—'}</td>
                </tr>
              ))}
            </tbody>
          </Table>
        </Card.Body>
      </Card>
    </div>
  );
}
