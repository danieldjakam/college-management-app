import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, CardBody, CardHeader, Table, Button, Input, Label, FormGroup, Alert, Spinner, Badge } from 'reactstrap';
import { Search, Download, Award, TrophyFill, StarFill } from 'react-bootstrap-icons';
import secureApi from '../../utils/api';

const HonorRoll = () => {
  // Filtres
  const [sections, setSections] = useState([]);
  const [levels, setLevels] = useState([]);
  const [classes, setClasses] = useState([]);
  const [series, setSeries] = useState([]);
  const [trimesters, setTrimesters] = useState([]);

  // Sélections
  const [selectedSection, setSelectedSection] = useState('');
  const [selectedLevel, setSelectedLevel] = useState('');
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedSeries, setSelectedSeries] = useState('');
  const [selectedTrimester, setSelectedTrimester] = useState('');

  // Données
  const [eligibleStudents, setEligibleStudents] = useState([]);
  const [groupedByMention, setGroupedByMention] = useState({});
  const [statistics, setStatistics] = useState({});
  const [loading, setLoading] = useState(false);
  const [generating, setGenerating] = useState({});
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Charger les filtres au montage
  useEffect(() => {
    fetchFilters();
  }, []);

  // Cascade filters: Section → Level
  useEffect(() => {
    if (selectedSection) {
      const filteredLevels = levels.filter(l => l.section_id === parseInt(selectedSection));
      setLevels(filteredLevels.length > 0 ? filteredLevels : levels);
      setSelectedLevel('');
      setSelectedClass('');
      setSelectedSeries('');
    }
  }, [selectedSection]);

  // Cascade filters: Level → Class
  useEffect(() => {
    if (selectedLevel) {
      const filteredClasses = classes.filter(c => c.level_id === parseInt(selectedLevel));
      setClasses(filteredClasses.length > 0 ? filteredClasses : classes);
      setSelectedClass('');
      setSelectedSeries('');
    }
  }, [selectedLevel]);

  // Cascade filters: Class → Series
  useEffect(() => {
    if (selectedClass) {
      const filteredSeries = series.filter(s => s.school_class_id === parseInt(selectedClass));
      setSeries(filteredSeries.length > 0 ? filteredSeries : series);
      setSelectedSeries('');
    }
  }, [selectedClass]);

  const fetchFilters = async () => {
    try {
      const response = await secureApi.get('/honor-rolls/filters');
      console.log('API Response:', response); // DEBUG

      // secureApi returns the data directly, not wrapped in { data: ... }
      const data = response;

      setSections(data.sections || []);
      setLevels(data.levels || []);
      setClasses(data.classes || []);
      setSeries(data.series || []);
      setTrimesters(data.trimesters || []);

      // Sélectionner le premier trimestre par défaut
      if (data.trimesters && data.trimesters.length > 0) {
        setSelectedTrimester(data.trimesters[0].id.toString());
      }
    } catch (err) {
      console.error('Error fetching filters:', err);
      console.error('Error message:', err.message); // DEBUG
      console.error('Error stack:', err.stack); // DEBUG
      setError(`Erreur lors du chargement des filtres: ${err.message || 'Erreur inconnue'}`);
    }
  };

  const fetchEligibleStudents = async () => {
    if (!selectedTrimester) {
      setError('Veuillez sélectionner un trimestre');
      return;
    }

    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const response = await secureApi.post('/honor-rolls/eligible-students', {
        trimester_id: selectedTrimester,
        section_id: selectedSection || null,
        level_id: selectedLevel || null,
        class_id: selectedClass || null,
        series_id: selectedSeries || null,
      });

      const data = response.data;
      setEligibleStudents(data.students || []);
      setGroupedByMention(data.grouped_by_mention || {});
      setStatistics(data.statistics || {});

      if (data.students && data.students.length === 0) {
        setError('Aucun élève éligible trouvé avec les critères sélectionnés');
      } else {
        setSuccess(`${data.students.length} élève(s) éligible(s) au tableau d'honneur`);
      }
    } catch (err) {
      console.error('Error fetching eligible students:', err);
      setError(err.response?.data?.message || 'Erreur lors du chargement des élèves');
    } finally {
      setLoading(false);
    }
  };

  const generateCertificate = async (studentId) => {
    if (!selectedTrimester) {
      setError('Veuillez sélectionner un trimestre');
      return;
    }

    setGenerating({ ...generating, [studentId]: true });
    setError('');
    setSuccess('');

    try {
      const response = await secureApi.post('/honor-rolls/generate-certificate', {
        student_id: studentId,
        trimester_id: selectedTrimester,
      });

      const data = response.data;

      // Télécharger automatiquement le fichier
      if (data.file_path) {
        const downloadUrl = secureApi.defaults.baseURL + data.download_url;
        window.open(downloadUrl, '_blank');
        setSuccess(`Certificat généré avec succès pour l'élève`);
      }
    } catch (err) {
      console.error('Error generating certificate:', err);
      setError(err.response?.data?.message || 'Erreur lors de la génération du certificat');
    } finally {
      setGenerating({ ...generating, [studentId]: false });
    }
  };

  const getMentionBadgeColor = (mention) => {
    switch (mention) {
      case 'Excellent':
        return 'success';
      case 'Très bien':
        return 'primary';
      case 'Bien':
        return 'info';
      case 'Assez bien':
        return 'warning';
      default:
        return 'secondary';
    }
  };

  const getMentionIcon = (mention) => {
    switch (mention) {
      case 'Excellent':
        return <TrophyFill className="me-1" />;
      case 'Très bien':
        return <StarFill className="me-1" />;
      case 'Bien':
        return <Award className="me-1" />;
      case 'Assez bien':
        return <Award className="me-1" />;
      default:
        return null;
    }
  };

  const resetFilters = () => {
    setSelectedSection('');
    setSelectedLevel('');
    setSelectedClass('');
    setSelectedSeries('');
    setEligibleStudents([]);
    setGroupedByMention({});
    setStatistics({});
    setError('');
    setSuccess('');
  };

  return (
    <Container fluid className="p-4">
      <Row className="mb-4">
        <Col>
          <h2 className="mb-0">
            <TrophyFill className="me-2 text-warning" />
            Tableau d'Honneur
          </h2>
          <p className="text-muted mb-0">Gestion des certificats d'honneur (moyenne ≥ 12/20)</p>
        </Col>
      </Row>

      {/* Filtres */}
      <Card className="mb-4">
        <CardHeader>
          <h5 className="mb-0">Filtres de recherche</h5>
        </CardHeader>
        <CardBody>
          <Row>
            <Col md={2}>
              <FormGroup>
                <Label for="trimester">Trimestre *</Label>
                <Input
                  type="select"
                  id="trimester"
                  value={selectedTrimester}
                  onChange={(e) => setSelectedTrimester(e.target.value)}
                >
                  <option value="">Choisir...</option>
                  {trimesters.map((trim) => (
                    <option key={trim.id} value={trim.id}>
                      Trimestre {trim.trimester_number}
                    </option>
                  ))}
                </Input>
              </FormGroup>
            </Col>

            <Col md={2}>
              <FormGroup>
                <Label for="section">Section</Label>
                <Input
                  type="select"
                  id="section"
                  value={selectedSection}
                  onChange={(e) => setSelectedSection(e.target.value)}
                >
                  <option value="">Toutes les sections</option>
                  {sections.map((section) => (
                    <option key={section.id} value={section.id}>
                      {section.name}
                    </option>
                  ))}
                </Input>
              </FormGroup>
            </Col>

            <Col md={2}>
              <FormGroup>
                <Label for="level">Niveau</Label>
                <Input
                  type="select"
                  id="level"
                  value={selectedLevel}
                  onChange={(e) => setSelectedLevel(e.target.value)}
                  disabled={!selectedSection}
                >
                  <option value="">Tous les niveaux</option>
                  {levels
                    .filter(l => !selectedSection || l.section_id === parseInt(selectedSection))
                    .map((level) => (
                      <option key={level.id} value={level.id}>
                        {level.name}
                      </option>
                    ))}
                </Input>
              </FormGroup>
            </Col>

            <Col md={2}>
              <FormGroup>
                <Label for="class">Classe</Label>
                <Input
                  type="select"
                  id="class"
                  value={selectedClass}
                  onChange={(e) => setSelectedClass(e.target.value)}
                  disabled={!selectedLevel}
                >
                  <option value="">Toutes les classes</option>
                  {classes
                    .filter(c => !selectedLevel || c.level_id === parseInt(selectedLevel))
                    .map((cls) => (
                      <option key={cls.id} value={cls.id}>
                        {cls.name}
                      </option>
                    ))}
                </Input>
              </FormGroup>
            </Col>

            <Col md={2}>
              <FormGroup>
                <Label for="series">Série</Label>
                <Input
                  type="select"
                  id="series"
                  value={selectedSeries}
                  onChange={(e) => setSelectedSeries(e.target.value)}
                  disabled={!selectedClass}
                >
                  <option value="">Toutes les séries</option>
                  {series
                    .filter(s => !selectedClass || s.school_class_id === parseInt(selectedClass))
                    .map((ser) => (
                      <option key={ser.id} value={ser.id}>
                        {ser.name}
                      </option>
                    ))}
                </Input>
              </FormGroup>
            </Col>

            <Col md={2} className="d-flex align-items-end">
              <FormGroup className="w-100">
                <Button
                  color="primary"
                  className="w-100"
                  onClick={fetchEligibleStudents}
                  disabled={loading || !selectedTrimester}
                >
                  {loading ? (
                    <>
                      <Spinner size="sm" className="me-2" />
                      Recherche...
                    </>
                  ) : (
                    <>
                      <Search className="me-2" />
                      Rechercher
                    </>
                  )}
                </Button>
              </FormGroup>
            </Col>
          </Row>

          <Row>
            <Col>
              <Button color="secondary" size="sm" outline onClick={resetFilters}>
                Réinitialiser les filtres
              </Button>
            </Col>
          </Row>
        </CardBody>
      </Card>

      {/* Messages */}
      {error && (
        <Alert color="danger" className="mb-3" fade={false}>
          {error}
        </Alert>
      )}

      {success && (
        <Alert color="success" className="mb-3" fade={false}>
          {success}
        </Alert>
      )}

      {/* Statistiques */}
      {eligibleStudents.length > 0 && (
        <Row className="mb-4">
          <Col md={2}>
            <Card className="text-center border-success">
              <CardBody>
                <h2 className="text-success mb-0">{statistics.total || 0}</h2>
                <small className="text-muted">Total</small>
              </CardBody>
            </Card>
          </Col>
          <Col md={2}>
            <Card className="text-center border-success">
              <CardBody>
                <h3 className="text-success mb-0">{statistics.excellent || 0}</h3>
                <small className="text-muted">Excellent</small>
              </CardBody>
            </Card>
          </Col>
          <Col md={2}>
            <Card className="text-center border-primary">
              <CardBody>
                <h3 className="text-primary mb-0">{statistics.tres_bien || 0}</h3>
                <small className="text-muted">Très bien</small>
              </CardBody>
            </Card>
          </Col>
          <Col md={2}>
            <Card className="text-center border-info">
              <CardBody>
                <h3 className="text-info mb-0">{statistics.bien || 0}</h3>
                <small className="text-muted">Bien</small>
              </CardBody>
            </Card>
          </Col>
          <Col md={2}>
            <Card className="text-center border-warning">
              <CardBody>
                <h3 className="text-warning mb-0">{statistics.assez_bien || 0}</h3>
                <small className="text-muted">Assez bien</small>
              </CardBody>
            </Card>
          </Col>
        </Row>
      )}

      {/* Liste des élèves par mention */}
      {eligibleStudents.length > 0 && (
        <>
          {['Excellent', 'Très bien', 'Bien', 'Assez bien'].map((mention) => {
            const students = groupedByMention[mention] || [];
            if (students.length === 0) return null;

            return (
              <Card key={mention} className="mb-3">
                <CardHeader className={`bg-${getMentionBadgeColor(mention)} text-white`}>
                  <h5 className="mb-0">
                    {getMentionIcon(mention)}
                    {mention} ({students.length})
                  </h5>
                </CardHeader>
                <CardBody className="p-0">
                  <Table responsive hover className="mb-0">
                    <thead>
                      <tr>
                        <th>Nom et Prénom(s)</th>
                        <th>Classe</th>
                        <th>Moyenne</th>
                        <th>Rang</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      {students.map((student) => (
                        <tr key={student.id}>
                          <td>
                            <strong>{student.full_name}</strong>
                          </td>
                          <td>{student.class}</td>
                          <td>
                            <Badge color={getMentionBadgeColor(mention)}>
                              {student.average}/20
                            </Badge>
                          </td>
                          <td>{student.rank}e</td>
                          <td>
                            <Button
                              color="primary"
                              size="sm"
                              onClick={() => generateCertificate(student.id)}
                              disabled={generating[student.id]}
                            >
                              {generating[student.id] ? (
                                <>
                                  <Spinner size="sm" className="me-1" />
                                  Génération...
                                </>
                              ) : (
                                <>
                                  <Download className="me-1" />
                                  Générer certificat
                                </>
                              )}
                            </Button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </CardBody>
              </Card>
            );
          })}
        </>
      )}

      {/* Message si aucun élève */}
      {!loading && eligibleStudents.length === 0 && !error && (
        <Card>
          <CardBody className="text-center text-muted py-5">
            <TrophyFill size={48} className="mb-3 opacity-50" />
            <p className="mb-0">Sélectionnez un trimestre et cliquez sur "Rechercher" pour afficher les élèves éligibles</p>
          </CardBody>
        </Card>
      )}
    </Container>
  );
};

export default HonorRoll;
