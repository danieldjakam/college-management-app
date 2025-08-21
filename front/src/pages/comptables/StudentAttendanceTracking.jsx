import React, { useState, useEffect } from 'react';
import { Container, Card, Row, Col, Form, Button, Table, Alert, Spinner, Badge } from 'react-bootstrap';
import { Calendar, Search, People, Check, X, Eye, Download, ChevronLeft, ChevronRight } from 'react-bootstrap-icons';
import { secureApiEndpoints, secureApi } from '../../utils/apiMigration';
import { host } from '../../utils/fetch';

function StudentAttendanceTracking() {
  const [sections, setSections] = useState([]);
  const [classes, setClasses] = useState([]);
  const [series, setSeries] = useState([]);
  const [selectedSection, setSelectedSection] = useState('');
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedSeries, setSelectedSeries] = useState('');
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [attendanceData, setAttendanceData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [stats, setStats] = useState({ total: 0, present: 0, absent: 0 });
  const [exportLoading, setExportLoading] = useState(false);

  useEffect(() => {
    fetchSections();
  }, []);

  useEffect(() => {
    if (selectedSection) {
      fetchClasses(selectedSection);
      setSelectedClass('');
      setSelectedSeries('');
    }
  }, [selectedSection]);

  useEffect(() => {
    if (selectedClass) {
      fetchSeries(selectedClass);
      setSelectedSeries('');
    }
  }, [selectedClass]);

  // Ajouter les raccourcis clavier pour navigation
  useEffect(() => {
    const handleKeyPress = (event) => {
      if (event.target.tagName.toLowerCase() === 'input' || event.target.tagName.toLowerCase() === 'select') return;
      
      switch(event.key) {
        case 'ArrowLeft':
          event.preventDefault();
          goToPreviousDay();
          break;
        case 'ArrowRight':
          event.preventDefault();
          goToNextDay();
          break;
        case 't':
        case 'T':
          event.preventDefault();
          goToToday();
          break;
        default:
          break;
      }
    };

    document.addEventListener('keydown', handleKeyPress);
    return () => {
      document.removeEventListener('keydown', handleKeyPress);
    };
  }, [selectedDate]);

  // Charger automatiquement les données quand les filtres changent
  useEffect(() => {
    if (selectedDate) {
      fetchAttendanceData();
    }
  }, [selectedDate, selectedSeries, selectedClass, selectedSection]);

  const fetchSections = async () => {
    try {
      const response = await secureApiEndpoints.sections.getAll();
      console.log('Réponse sections:', response);
      
      let sectionsData = [];
      if (response.success) {
        sectionsData = Array.isArray(response.data) ? response.data : [];
      } else if (Array.isArray(response)) {
        sectionsData = response;
      }
      
      setSections(sectionsData);
    } catch (error) {
      console.error('Erreur lors du chargement des sections:', error);
      setError('Erreur lors du chargement des sections: ' + error.message);
      setSections([]);
    }
  };

  const fetchClasses = async (sectionId) => {
    try {
      const response = await secureApiEndpoints.levels.getBySection(sectionId);
      console.log('Réponse classes:', response);
      
      let classesData = [];
      if (response.success) {
        classesData = Array.isArray(response.data) ? response.data : [];
      } else if (Array.isArray(response)) {
        classesData = response;
      }
      
      setClasses(classesData);
    } catch (error) {
      console.error('Erreur lors du chargement des classes:', error);
      setError('Erreur lors du chargement des classes: ' + error.message);
      setClasses([]);
    }
  };

  const fetchSeries = async (classId) => {
    try {
      // Utilisons l'API directement
      const response = await secureApi.get(`/levels/${classId}/series`);
      console.log('Réponse series:', response);
      
      let seriesData = [];
      if (response.success) {
        seriesData = Array.isArray(response.data) ? response.data : [];
      } else if (Array.isArray(response)) {
        seriesData = response;
      }
      
      setSeries(seriesData);
    } catch (error) {
      console.error('Erreur lors du chargement des séries:', error);
      setError('Erreur lors du chargement des séries: ' + error.message);
      setSeries([]);
    }
  };

  const fetchAttendanceData = async () => {
    if (!selectedDate) {
      setError('Veuillez sélectionner une date');
      return;
    }

    setLoading(true);
    setError('');

    try {
      // Construire les paramètres de la requête
      const params = new URLSearchParams({ date: selectedDate });
      
      if (selectedSeries) {
        params.append('series_id', selectedSeries);
      } else if (selectedClass) {
        params.append('class_id', selectedClass);
      } else if (selectedSection) {
        params.append('section_id', selectedSection);
      }

      const response = await secureApi.get(`/attendance/students?${params.toString()}`);
      console.log('Réponse attendance:', response);
      
      let data = [];
      if (response.success) {
        data = Array.isArray(response.data) ? response.data : [];
      } else if (Array.isArray(response)) {
        data = response;
      }
      
      setAttendanceData(data);
      
      // Calculer les statistiques
      const total = data.length;
      const present = data.filter(student => student.is_present).length;
      const absent = total - present;
      
      setStats({ total, present, absent });
    } catch (error) {
      console.error('Erreur lors du chargement des présences:', error);
      setError('Erreur lors du chargement des données de présence');
      setAttendanceData([]);
      setStats({ total: 0, present: 0, absent: 0 });
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    // Plus nécessaire car le chargement est automatique
  };

  const goToPreviousDay = () => {
    const currentDate = new Date(selectedDate);
    currentDate.setDate(currentDate.getDate() - 1);
    setSelectedDate(currentDate.toISOString().split('T')[0]);
  };

  const goToNextDay = () => {
    const currentDate = new Date(selectedDate);
    currentDate.setDate(currentDate.getDate() + 1);
    setSelectedDate(currentDate.toISOString().split('T')[0]);
  };

  const goToToday = () => {
    setSelectedDate(new Date().toISOString().split('T')[0]);
  };

  const handleExportPDF = async () => {
    if (!selectedDate) {
      setError('Veuillez sélectionner une date pour l\'export');
      return;
    }

    setExportLoading(true);
    setError('');

    try {
      // Construire les paramètres de la requête
      const params = new URLSearchParams({ date: selectedDate });
      
      if (selectedSeries) {
        params.append('series_id', selectedSeries);
      } else if (selectedClass) {
        params.append('class_id', selectedClass);
      } else if (selectedSection) {
        params.append('section_id', selectedSection);
      }

      // Utiliser fetch directement pour gérer la réponse binaire
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      const response = await fetch(`${host}/api/attendance/students/export/pdf?${params.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf',
        },
      });

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}: ${response.statusText}`);
      }
      
      // Créer un blob à partir de la réponse
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      
      // Créer un lien de téléchargement
      const link = document.createElement('a');
      link.href = url;
      link.download = `presences_eleves_${selectedDate}.pdf`;
      document.body.appendChild(link);
      link.click();
      
      // Nettoyer
      window.URL.revokeObjectURL(url);
      document.body.removeChild(link);
      
    } catch (error) {
      console.error('Erreur lors de l\'export PDF:', error);
      setError('Erreur lors de l\'export PDF: ' + error.message);
    } finally {
      setExportLoading(false);
    }
  };

  const getAttendanceBadge = (isPresent) => {
    return isPresent ? (
      <Badge bg="success" className="d-flex align-items-center gap-1">
        <Check size={12} /> Présent
      </Badge>
    ) : (
      <Badge bg="danger" className="d-flex align-items-center gap-1">
        <X size={12} /> Absent
      </Badge>
    );
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  return (
    <Container fluid className="p-4">
      <Row className="mb-4">
        <Col>
          <h2 className="d-flex align-items-center gap-2 mb-0">
            <People className="text-primary" />
            Suivi des Présences Élèves
          </h2>
          <p className="text-muted mb-0">
            Consultez les présences des élèves par section, classe ou série
          </p>
          <small className="text-muted">
            💡 Raccourcis: ← → pour changer de jour, T pour aujourd'hui
          </small>
        </Col>
      </Row>

      {/* Filtres */}
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0 d-flex align-items-center gap-2">
            <Search /> Filtres de Recherche
          </h5>
        </Card.Header>
        <Card.Body>
          <Row>
            <Col md={12} lg={6} className="mb-3">
              <Form.Group>
                <Form.Label>Date</Form.Label>
                <div className="d-flex align-items-center gap-2">
                  <Button 
                    variant="outline-primary" 
                    size="sm"
                    onClick={goToPreviousDay}
                    className="d-flex align-items-center"
                  >
                    <ChevronLeft />
                  </Button>
                  
                  <Form.Control
                    type="date"
                    value={selectedDate}
                    onChange={(e) => setSelectedDate(e.target.value)}
                    className="text-center"
                    style={{ width: '140px', fontSize: '0.9rem' }}
                    size="sm"
                  />
                  
                  <Button 
                    variant="outline-primary" 
                    size="sm"
                    onClick={goToNextDay}
                    className="d-flex align-items-center"
                  >
                    <ChevronRight />
                  </Button>
                  
                  <Button 
                    variant="outline-secondary" 
                    size="sm"
                    onClick={goToToday}
                    className="ms-2"
                  >
                    Aujourd'hui
                  </Button>
                </div>
              </Form.Group>
            </Col>

            <Col md={4} lg={2} className="mb-3">
              <Form.Group>
                <Form.Label>Section</Form.Label>
                <Form.Select
                  value={selectedSection}
                  onChange={(e) => setSelectedSection(e.target.value)}
                  size="sm"
                >
                  <option value="">Toutes les sections</option>
                  {sections.map(section => (
                    <option key={section.id} value={section.id}>
                      {section.name}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={4} lg={2} className="mb-3">
              <Form.Group>
                <Form.Label>Classe</Form.Label>
                <Form.Select
                  value={selectedClass}
                  onChange={(e) => setSelectedClass(e.target.value)}
                  disabled={!selectedSection}
                  size="sm"
                >
                  <option value="">Toutes les classes</option>
                  {Array.isArray(classes) && classes.map(classe => (
                    <option key={classe.id} value={classe.id}>
                      {classe.name}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={4} lg={2} className="mb-3">
              <Form.Group>
                <Form.Label>Série</Form.Label>
                <Form.Select
                  value={selectedSeries}
                  onChange={(e) => setSelectedSeries(e.target.value)}
                  disabled={!selectedClass}
                  size="sm"
                >
                  <option value="">Toutes les séries</option>
                  {Array.isArray(series) && series.map(serie => (
                    <option key={serie.id} value={serie.id}>
                      {serie.name}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
          </Row>

          <Row>
            <Col className="d-flex gap-2 align-items-center">
              {loading && (
                <div className="d-flex align-items-center gap-2 text-muted">
                  <Spinner size="sm" /> Chargement des données...
                </div>
              )}
              
              <div className="ms-auto">
                <Button 
                  variant="success" 
                  disabled={exportLoading || !selectedDate}
                  onClick={handleExportPDF}
                  className="d-flex align-items-center gap-2"
                >
                  {exportLoading ? (
                    <>
                      <Spinner size="sm" /> Export...
                    </>
                  ) : (
                    <>
                      <Download /> Exporter PDF
                    </>
                  )}
                </Button>
              </div>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {error && (
        <Alert variant="danger" className="mb-4">
          {error}
        </Alert>
      )}

      {/* Statistiques */}
      {attendanceData.length > 0 && (
        <Card className="mb-4">
          <Card.Header>
            <h5 className="mb-0 d-flex align-items-center gap-2">
              <Calendar /> Résumé - {formatDate(selectedDate)}
            </h5>
          </Card.Header>
          <Card.Body>
            <Row>
              <Col md={4} className="text-center">
                <h3 className="text-primary mb-1">{stats.total}</h3>
                <p className="text-muted mb-0">Total Élèves</p>
              </Col>
              <Col md={4} className="text-center">
                <h3 className="text-success mb-1">{stats.present}</h3>
                <p className="text-muted mb-0">Présents</p>
              </Col>
              <Col md={4} className="text-center">
                <h3 className="text-danger mb-1">{stats.absent}</h3>
                <p className="text-muted mb-0">Absents</p>
              </Col>
            </Row>
            <Row className="mt-3">
              <Col className="text-center">
                <p className="mb-0">
                  Taux de présence: <strong>{stats.total > 0 ? ((stats.present / stats.total) * 100).toFixed(1) : 0}%</strong>
                </p>
              </Col>
            </Row>
          </Card.Body>
        </Card>
      )}

      {/* Tableau des présences */}
      {attendanceData.length > 0 ? (
        <Card>
          <Card.Header>
            <h5 className="mb-0">Liste des Présences</h5>
          </Card.Header>
          <Card.Body className="p-0">
            <div className="table-responsive">
              <Table striped hover className="mb-0">
                <thead className="bg-light">
                  <tr>
                    <th>Matricule</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Classe</th>
                    <th>Série</th>
                    <th>Statut</th>
                    <th>Arrivée</th>
                    <th>Sortie</th>
                  </tr>
                </thead>
                <tbody>
                  {attendanceData.map((student, index) => (
                    <tr key={index}>
                      <td>
                        <strong>{student.matricule}</strong>
                      </td>
                      <td>{student.nom}</td>
                      <td>{student.prenom}</td>
                      <td>{student.class_name}</td>
                      <td>{student.series_name}</td>
                      <td>{getAttendanceBadge(student.is_present)}</td>
                      <td>
                        {student.is_present && student.arrival_time ? (
                          new Date(student.arrival_time).toLocaleTimeString('fr-FR', {
                            hour: '2-digit',
                            minute: '2-digit'
                          })
                        ) : (
                          '-'
                        )}
                      </td>
                      <td>
                        {student.exit_time ? (
                          new Date(student.exit_time).toLocaleTimeString('fr-FR', {
                            hour: '2-digit',
                            minute: '2-digit'
                          })
                        ) : (
                          '-'
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            </div>
          </Card.Body>
        </Card>
      ) : !loading && selectedDate && (
        <Card>
          <Card.Body className="text-center py-5">
            <People size={48} className="text-muted mb-3" />
            <h5 className="text-muted">Aucune donnée de présence</h5>
            <p className="text-muted mb-0">
              Aucune donnée de présence trouvée pour les critères sélectionnés.
            </p>
          </Card.Body>
        </Card>
      )}
    </Container>
  );
}

export default StudentAttendanceTracking;