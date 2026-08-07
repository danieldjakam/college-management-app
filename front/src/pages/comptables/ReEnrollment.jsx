import React, { useState, useEffect, useCallback } from 'react';
import {
  Container, Row, Col, Card, Form, Button, Alert, Spinner, Badge, InputGroup, Modal, Table
} from 'react-bootstrap';
import { Search, PersonPlus, ExclamationTriangleFill, CheckCircleFill, ArrowRight } from 'react-bootstrap-icons';
import { useNavigate } from 'react-router-dom';
import { secureApiEndpoints } from '../../utils/apiMigration';

function ReEnrollment() {
  const navigate = useNavigate();

  // Search state
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);
  const [searchDone, setSearchDone] = useState(false);

  // Selected student
  const [selectedStudent, setSelectedStudent] = useState(null);

  // Form state
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    date_of_birth: '',
    place_of_birth: '',
    gender: 'M',
    parent_name: '',
    parent_phone: '',
    parent_email: '',
    mother_name: '',
    mother_phone: '',
    address: '',
    has_scholarship_enabled: false,
    class_series_id: '',
  });

  // Classes
  const [classSeries, setClassSeries] = useState([]);
  const [loadingClasses, setLoadingClasses] = useState(false);

  // Submission
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [successInfo, setSuccessInfo] = useState(null);

  // Load classes for the current year
  useEffect(() => {
    loadClasses();
  }, []);

  const loadClasses = async () => {
    setLoadingClasses(true);
    try {
      const response = await secureApiEndpoints.classSeries.getAll();
      const data = response.data?.data || response.data || [];
      setClassSeries(Array.isArray(data) ? data : []);
    } catch (err) {
      console.error('Erreur chargement classes:', err);
    } finally {
      setLoadingClasses(false);
    }
  };

  // Debounced search
  useEffect(() => {
    if (searchQuery.length < 2) {
      setSearchResults([]);
      setSearchDone(false);
      return;
    }

    const timer = setTimeout(() => {
      performSearch(searchQuery);
    }, 400);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  const performSearch = async (query) => {
    setSearching(true);
    setSearchDone(false);
    setError('');
    try {
      const response = await secureApiEndpoints.students.searchPreviousYear(query);
      const data = response.data?.data || response.data || [];
      setSearchResults(Array.isArray(data) ? data : []);
    } catch (err) {
      console.error('Erreur recherche:', err);
      setError(err.message || 'Erreur lors de la recherche');
      setSearchResults([]);
    } finally {
      setSearching(false);
      setSearchDone(true);
    }
  };

  const selectStudent = (student) => {
    setSelectedStudent(student);
    setError('');
    setSuccessInfo(null);
    setFormData({
      first_name: student.first_name || '',
      last_name: student.last_name || '',
      date_of_birth: student.date_of_birth || '',
      place_of_birth: student.place_of_birth || '',
      gender: student.gender || 'M',
      parent_name: student.parent_name || '',
      parent_phone: student.parent_phone || '',
      parent_email: student.parent_email || '',
      mother_name: student.mother_name || '',
      mother_phone: student.mother_phone || '',
      address: student.address || '',
      has_scholarship_enabled: false,
      class_series_id: '',
    });
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccessInfo(null);

    if (!formData.class_series_id) {
      setError('Veuillez sélectionner une classe.');
      return;
    }

    setSubmitting(true);
    try {
      const response = await secureApiEndpoints.students.reenroll({
        student_id: selectedStudent.id,
        ...formData,
        has_scholarship_enabled: formData.has_scholarship_enabled ? 1 : 0,
      });

      const result = response.data;
      if (result.success) {
        const newStudent = result.data;
        setSuccessInfo({
          message: result.message || 'Reinscription reussie!',
          studentId: newStudent.id,
          studentName: `${newStudent.last_name} ${newStudent.first_name}`,
        });
        setSelectedStudent(null);
        setSearchQuery('');
        setSearchResults([]);
      } else {
        setError(result.message || 'Erreur lors de la reinscription');
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Erreur lors de la reinscription';
      setError(msg);
    } finally {
      setSubmitting(false);
    }
  };

  const cancelSelection = () => {
    setSelectedStudent(null);
    setError('');
    setSuccessInfo(null);
  };

  // Group classes by school class name
  const groupedClasses = classSeries.reduce((acc, series) => {
    const className = series.school_class?.name || series.class_name || 'Autre';
    if (!acc[className]) acc[className] = [];
    acc[className].push(series);
    return acc;
  }, {});

  return (
    <Container fluid className="py-4">
      <Row className="mb-4">
        <Col>
          <h3 className="mb-1">
            <PersonPlus className="me-2" />
            Reinscription des anciens eleves
          </h3>
          <p className="text-muted">
            Recherchez un ancien eleve par nom ou prenom pour le reinscrire dans la nouvelle annee scolaire.
          </p>
        </Col>
      </Row>

      {/* Success message */}
      {successInfo && (
        <Alert variant="success" dismissible onClose={() => setSuccessInfo(null)}>
          <Alert.Heading>
            <CheckCircleFill className="me-2" />
            {successInfo.message}
          </Alert.Heading>
          <p className="mb-2">
            <strong>{successInfo.studentName}</strong> a ete reinscrit avec succes.
          </p>
          <Button
            variant="success"
            size="sm"
            onClick={() => navigate(`/student-payment/${successInfo.studentId}`)}
          >
            <ArrowRight className="me-1" />
            Proceder au paiement
          </Button>
        </Alert>
      )}

      {/* Error message */}
      {error && (
        <Alert variant="danger" dismissible onClose={() => setError('')}>
          {error}
        </Alert>
      )}

      {/* Search section */}
      {!selectedStudent && (
        <Card className="mb-4 shadow-sm">
          <Card.Body>
            <Card.Title>Rechercher un ancien eleve</Card.Title>
            <InputGroup className="mb-3">
              <InputGroup.Text>
                <Search />
              </InputGroup.Text>
              <Form.Control
                type="text"
                placeholder="Entrez le nom, prenom ou matricule de l'eleve..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                autoFocus
              />
              {searching && (
                <InputGroup.Text>
                  <Spinner animation="border" size="sm" />
                </InputGroup.Text>
              )}
            </InputGroup>

            {/* Search results */}
            {searchResults.length > 0 && (
              <Table striped hover responsive size="sm">
                <thead>
                  <tr>
                    <th>Matricule</th>
                    <th>Nom & Prenom</th>
                    <th>Classe precedente</th>
                    <th>Annee</th>
                    <th>Statut</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  {searchResults.map(student => (
                    <tr key={student.id}>
                      <td><small>{student.student_number}</small></td>
                      <td><strong>{student.last_name} {student.first_name}</strong></td>
                      <td>{student.previous_class}</td>
                      <td><small>{student.previous_year}</small></td>
                      <td>
                        {student.already_enrolled ? (
                          <Badge bg="info">Deja inscrit</Badge>
                        ) : student.has_arrear ? (
                          <Badge bg="danger">
                            <ExclamationTriangleFill className="me-1" />
                            Arrieres: {Number(student.arrear_amount).toLocaleString('fr-FR')} FCFA
                          </Badge>
                        ) : (
                          <Badge bg="success">Eligible</Badge>
                        )}
                      </td>
                      <td>
                        {student.already_enrolled ? (
                          <Button variant="outline-secondary" size="sm" disabled>
                            Deja inscrit
                          </Button>
                        ) : (
                          <Button
                            variant="primary"
                            size="sm"
                            onClick={() => selectStudent(student)}
                          >
                            Selectionner
                          </Button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            )}

            {searchDone && searchResults.length === 0 && searchQuery.length >= 2 && (
              <Alert variant="info" className="mb-0">
                Aucun ancien eleve trouve pour "{searchQuery}".
              </Alert>
            )}
          </Card.Body>
        </Card>
      )}

      {/* Re-enrollment form */}
      {selectedStudent && (
        <Card className="shadow-sm">
          <Card.Header className="bg-primary text-white d-flex justify-content-between align-items-center">
            <span>
              Reinscription de <strong>{selectedStudent.last_name} {selectedStudent.first_name}</strong>
              <small className="ms-2">({selectedStudent.previous_class} - {selectedStudent.previous_year})</small>
            </span>
            <Button variant="outline-light" size="sm" onClick={cancelSelection}>
              Annuler
            </Button>
          </Card.Header>
          <Card.Body>
            {/* Arrear warning */}
            {selectedStudent.has_arrear && (
              <Alert variant="danger" className="d-flex align-items-center">
                <ExclamationTriangleFill size={24} className="me-3 flex-shrink-0" />
                <div>
                  <strong>Attention !</strong> Cet eleve a un arrieres de{' '}
                  <strong>{Number(selectedStudent.arrear_amount).toLocaleString('fr-FR')} FCFA</strong>.
                  Il doit d'abord solder sa dette avant la reinscription.
                  <br />
                  <Button
                    variant="danger"
                    size="sm"
                    className="mt-2"
                    onClick={() => navigate('/arrears-management')}
                  >
                    Gerer les arrieres
                  </Button>
                </div>
              </Alert>
            )}

            <Form onSubmit={handleSubmit}>
              <Row>
                {/* Student info */}
                <Col md={6}>
                  <h5 className="mb-3 text-primary">Informations de l'eleve</h5>

                  <Row>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Nom <span className="text-danger">*</span></Form.Label>
                        <Form.Control
                          type="text"
                          name="last_name"
                          value={formData.last_name}
                          onChange={handleChange}
                          required
                        />
                      </Form.Group>
                    </Col>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Prenom <span className="text-danger">*</span></Form.Label>
                        <Form.Control
                          type="text"
                          name="first_name"
                          value={formData.first_name}
                          onChange={handleChange}
                          required
                        />
                      </Form.Group>
                    </Col>
                  </Row>

                  <Row>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Date de naissance <span className="text-danger">*</span></Form.Label>
                        <Form.Control
                          type="date"
                          name="date_of_birth"
                          value={formData.date_of_birth}
                          onChange={handleChange}
                          required
                        />
                      </Form.Group>
                    </Col>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Lieu de naissance <span className="text-danger">*</span></Form.Label>
                        <Form.Control
                          type="text"
                          name="place_of_birth"
                          value={formData.place_of_birth}
                          onChange={handleChange}
                          required
                        />
                      </Form.Group>
                    </Col>
                  </Row>

                  <Form.Group className="mb-3">
                    <Form.Label>Sexe <span className="text-danger">*</span></Form.Label>
                    <Form.Select name="gender" value={formData.gender} onChange={handleChange}>
                      <option value="M">Masculin</option>
                      <option value="F">Feminin</option>
                    </Form.Select>
                  </Form.Group>
                </Col>

                {/* Parent info & class */}
                <Col md={6}>
                  <h5 className="mb-3 text-primary">Parent / Tuteur & Classe</h5>

                  <Form.Group className="mb-3">
                    <Form.Label>Nom du pere/tuteur <span className="text-danger">*</span></Form.Label>
                    <Form.Control
                      type="text"
                      name="parent_name"
                      value={formData.parent_name}
                      onChange={handleChange}
                      required
                    />
                  </Form.Group>

                  <Row>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Tel. pere/tuteur</Form.Label>
                        <Form.Control
                          type="text"
                          name="parent_phone"
                          value={formData.parent_phone}
                          onChange={handleChange}
                        />
                      </Form.Group>
                    </Col>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Email pere/tuteur</Form.Label>
                        <Form.Control
                          type="email"
                          name="parent_email"
                          value={formData.parent_email}
                          onChange={handleChange}
                        />
                      </Form.Group>
                    </Col>
                  </Row>

                  <Row>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Nom de la mere</Form.Label>
                        <Form.Control
                          type="text"
                          name="mother_name"
                          value={formData.mother_name}
                          onChange={handleChange}
                        />
                      </Form.Group>
                    </Col>
                    <Col md={6}>
                      <Form.Group className="mb-3">
                        <Form.Label>Tel. mere</Form.Label>
                        <Form.Control
                          type="text"
                          name="mother_phone"
                          value={formData.mother_phone}
                          onChange={handleChange}
                        />
                      </Form.Group>
                    </Col>
                  </Row>

                  <Form.Group className="mb-3">
                    <Form.Label>Adresse</Form.Label>
                    <Form.Control
                      type="text"
                      name="address"
                      value={formData.address}
                      onChange={handleChange}
                    />
                  </Form.Group>

                  <Form.Group className="mb-3">
                    <Form.Label>Nouvelle classe <span className="text-danger">*</span></Form.Label>
                    <Form.Select
                      name="class_series_id"
                      value={formData.class_series_id}
                      onChange={handleChange}
                      required
                    >
                      <option value="">-- Selectionner une classe --</option>
                      {Object.keys(groupedClasses).sort().map(className => (
                        <optgroup key={className} label={className}>
                          {groupedClasses[className].map(series => (
                            <option key={series.id} value={series.id}>
                              {className} {series.name}
                            </option>
                          ))}
                        </optgroup>
                      ))}
                    </Form.Select>
                    {loadingClasses && <Form.Text>Chargement des classes...</Form.Text>}
                  </Form.Group>

                  <Form.Check
                    type="checkbox"
                    name="has_scholarship_enabled"
                    label="Boursier"
                    checked={formData.has_scholarship_enabled}
                    onChange={handleChange}
                    className="mb-3"
                  />
                </Col>
              </Row>

              <hr />

              <div className="d-flex justify-content-end gap-2">
                <Button variant="secondary" onClick={cancelSelection} disabled={submitting}>
                  Annuler
                </Button>
                <Button
                  type="submit"
                  variant="success"
                  disabled={submitting || selectedStudent.has_arrear}
                  size="lg"
                >
                  {submitting ? (
                    <>
                      <Spinner animation="border" size="sm" className="me-2" />
                      Reinscription en cours...
                    </>
                  ) : (
                    <>
                      <PersonPlus className="me-2" />
                      Reinscrire l'eleve
                    </>
                  )}
                </Button>
              </div>
            </Form>
          </Card.Body>
        </Card>
      )}
    </Container>
  );
}

export default ReEnrollment;
