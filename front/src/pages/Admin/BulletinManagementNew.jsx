import React, { useState, useEffect } from 'react';
import { Card, Button, Table, Badge, Modal, Row, Col, Alert, Spinner, ProgressBar, Accordion, Form, ButtonGroup } from 'react-bootstrap';
import { CardText, Download, Eye, Printer, ArrowClockwise, Clock, CheckCircle, ExclamationCircle, Calendar, Book } from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';
import { authService } from '../../services/authService';

function BulletinManagementNew() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  
  // Structure hiérarchique
  const [hierarchicalData, setHierarchicalData] = useState([]);
  const [loadingHierarchy, setLoadingHierarchy] = useState(true);
  const [selectedSection, setSelectedSection] = useState('');
  const [selectedLevel, setSelectedLevel] = useState('');
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedSeries, setSelectedSeries] = useState('');
  
  // Timeline académique
  const [academicTimeline, setAcademicTimeline] = useState(null);
  
  // Étudiants et statuts des bulletins
  const [studentsData, setStudentsData] = useState([]);
  const [selectedPeriodType, setSelectedPeriodType] = useState('all');
  
  // Modal de preview
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [previewContent, setPreviewContent] = useState('');
  const [previewStudent, setPreviewStudent] = useState(null);

  useEffect(() => {
    fetchHierarchicalStructure();
    fetchAcademicTimeline();
  }, []);

  useEffect(() => {
    if (selectedSeries) {
      fetchStudentsData();
    }
  }, [selectedSeries]);

  const fetchHierarchicalStructure = async () => {
    try {
      console.log('Fetching hierarchical structure...');
      setLoadingHierarchy(true);
      const response = await secureApi.get('/bulletins/hierarchical-structure');
      console.log('Response received:', response);
      
      // secureApi returns parsed JSON directly, not wrapped in a data property
      let sectionsData = [];
      if (response && response.success && response.data) {
        sectionsData = response.data;
        console.log('Using response.data from success object');
      } else if (Array.isArray(response)) {
        sectionsData = response;
        console.log('Using response as array');
      } else {
        console.log('No valid data structure found');
        sectionsData = [];
      }
      console.log('Setting hierarchical data:', sectionsData);
      setHierarchicalData(sectionsData);
      
      if (sectionsData.length > 0) {
        console.log('First section:', sectionsData[0]);
      }
    } catch (error) {
      console.error('Erreur structure hiérarchique:', error);
      console.error('Error details:', error.response);
      setError(`Erreur lors du chargement de la structure: ${error.message}`);
    } finally {
      setLoadingHierarchy(false);
    }
  };

  const fetchAcademicTimeline = async () => {
    try {
      const response = await secureApi.get('/bulletins/academic-timeline');
      // secureApi returns parsed JSON directly
      if (response && response.success && response.data) {
        setAcademicTimeline(response.data);
      } else {
        setAcademicTimeline(null);
      }
    } catch (error) {
      console.error('Erreur timeline:', error);
      setError('Erreur lors du chargement de la timeline');
    }
  };

  const fetchStudentsData = async () => {
    if (!selectedSeries) return;
    
    try {
      setLoading(true);
      const response = await secureApi.get(`/bulletins/students-status/${selectedSeries}`);
      // secureApi returns parsed JSON directly
      if (response && response.success && response.students) {
        setStudentsData(response.students);
      } else {
        setStudentsData([]);
      }
    } catch (error) {
      console.error('Erreur étudiants:', error);
      setError('Erreur lors du chargement des étudiants');
    } finally {
      setLoading(false);
    }
  };

  const handleSectionChange = (sectionId) => {
    setSelectedSection(sectionId);
    setSelectedLevel('');
    setSelectedClass('');
    setSelectedSeries('');
    setStudentsData([]);
  };

  const handleLevelChange = (levelId) => {
    setSelectedLevel(levelId);
    setSelectedClass('');
    setSelectedSeries('');
    setStudentsData([]);
  };

  const handleClassChange = (classId) => {
    setSelectedClass(classId);
    setSelectedSeries('');
    setStudentsData([]);
  };

  const handleSeriesChange = (seriesId) => {
    setSelectedSeries(seriesId);
  };

  const handleDownloadBulletin = async (bulletinId, studentName, periodType, periodId) => {
    try {
      setLoading(true);
      
      // Utiliser fetch directement pour les téléchargements de fichiers
      const token = authService.getToken();
      const response = await fetch(`http://localhost:8000/api/bulletins/download/${bulletinId}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf'
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const blob = await response.blob();
      const fileName = `bulletin_${periodType}_${periodId}_${studentName}_${new Date().toISOString().slice(0,10)}.pdf`;
      
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      
      setSuccess(`Bulletin téléchargé: ${fileName}`);
    } catch (error) {
      console.error('Erreur téléchargement:', error);
      setError('Erreur lors du téléchargement');
    } finally {
      setLoading(false);
    }
  };

  const handlePreviewBulletin = async (studentId, studentName, type, periodIdentifier) => {
    try {
      setLoading(true);
      const response = await secureApi.post('/bulletins/preview', {
        student_id: studentId,
        type: type,
        period_identifier: periodIdentifier
      });
      
      // secureApi returns parsed JSON directly
      if (response && response.success && response.html) {
        setPreviewContent(response.html);
      } else {
        setPreviewContent('<p>Contenu non disponible</p>');
      }
      setPreviewStudent({ id: studentId, name: studentName });
      setShowPreviewModal(true);
    } catch (error) {
      setError('Erreur lors de la prévisualisation');
    } finally {
      setLoading(false);
    }
  };

  const handleForceRegenerate = async (studentId, periodType, periodId) => {
    if (window.confirm('Forcer la régénération de ce bulletin ?')) {
      try {
        setLoading(true);
        await secureApi.post('/bulletins/force-regenerate', {
          student_id: studentId,
          period_type: periodType,
          period_identifier: periodId
        });
        setSuccess('Bulletin régénéré avec succès');
        fetchStudentsData();
      } catch (error) {
        setError('Erreur lors de la régénération');
      } finally {
        setLoading(false);
      }
    }
  };

  const handleDownloadAllBulletins = async () => {
    if (!selectedSeries) {
      setError('Veuillez sélectionner une série');
      return;
    }

    if (!window.confirm('Télécharger tous les bulletins de cette série ?')) {
      return;
    }

    try {
      setLoading(true);
      
      const token = authService.getToken();
      const response = await fetch('http://localhost:8000/api/bulletins/download-all', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/zip'
        },
        body: JSON.stringify({
          series_id: selectedSeries,
          period_type: selectedPeriodType !== 'all' ? selectedPeriodType : null
        })
      });

      if (!response.ok) {
        const errorData = await response.json();
        console.error('Détails de l\'erreur:', errorData);
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      const blob = await response.blob();
      const fileName = `bulletins_${new Date().toISOString().slice(0,10)}.zip`;
      
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      
      setSuccess(`Archive téléchargée: ${fileName}`);
    } catch (error) {
      console.error('Erreur téléchargement groupé:', error);
      setError(error.message || 'Erreur lors du téléchargement groupé');
    } finally {
      setLoading(false);
    }
  };

  const getCompletionBadge = (completionPercentage, isGenerated) => {
    if (isGenerated) {
      return <Badge bg="success" className="d-flex align-items-center">
        <CheckCircle className="me-1" size={12} />
        Généré ({completionPercentage}%)
      </Badge>;
    } else if (completionPercentage >= 50) {
      return <Badge bg="warning" className="d-flex align-items-center">
        <Clock className="me-1" size={12} />
        Prêt ({completionPercentage}%)
      </Badge>;
    } else {
      return <Badge bg="secondary" className="d-flex align-items-center">
        <ExclamationCircle className="me-1" size={12} />
        Incomplet ({completionPercentage}%)
      </Badge>;
    }
  };

  const getCurrentPeriodBadge = (current, type) => {
    if (!current) return null;
    return (
      <Badge bg="primary" className="ms-2">
        <Calendar className="me-1" size={12} />
        {type === 'sequence' ? 'Séquence' : 'Trimestre'} Actuel: {current.name}
      </Badge>
    );
  };

  const filterStudentsByPeriod = (students) => {
    if (selectedPeriodType === 'all') return students;
    
    return students.filter(student => {
      const bulletins = Object.values(student.bulletins);
      if (selectedPeriodType === 'sequences') {
        return bulletins.some(b => b.type === 'sequence');
      } else if (selectedPeriodType === 'trimesters') {
        return bulletins.some(b => b.type === 'trimester');
      } else if (selectedPeriodType === 'generated') {
        return bulletins.some(b => b.is_generated);
      } else if (selectedPeriodType === 'pending') {
        return bulletins.some(b => !b.is_generated && b.completion_percentage >= 50);
      }
      return true;
    });
  };

  const getSelectedLevels = () => {
    if (!selectedSection || !hierarchicalData) return [];
    const section = hierarchicalData.find(s => s.id.toString() === selectedSection);
    return section ? section.levels : [];
  };

  const getSelectedClasses = () => {
    const levels = getSelectedLevels();
    if (!selectedLevel || !levels) return [];
    const level = levels.find(l => l.id.toString() === selectedLevel);
    return level ? level.school_classes : [];
  };

  const getSelectedSeries = () => {
    const classes = getSelectedClasses();
    if (!selectedClass || !classes) return [];
    const schoolClass = classes.find(c => c.id.toString() === selectedClass);
    return schoolClass ? schoolClass.series : [];
  };

  return (
    <div className="container-fluid">
      {/* Header avec timeline académique */}
      <Row className="mb-4">
        <Col>
          <Card className="border-0 shadow-sm">
            <Card.Header className="bg-primary text-white d-flex align-items-center">
              <Book className="me-2" />
              <h5 className="mb-0">Gestion des Bulletins Scolaires</h5>
            </Card.Header>
            <Card.Body>
              {academicTimeline && (
                <Row>
                  <Col md={6}>
                    <h6>Année Scolaire: {academicTimeline.school_year}</h6>
                    {getCurrentPeriodBadge(academicTimeline.current_sequence, 'sequence')}
                    {getCurrentPeriodBadge(academicTimeline.current_trimester, 'trimester')}
                  </Col>
                  <Col md={6} className="text-end">
                    <div className="d-flex flex-wrap gap-1 justify-content-end">
                      {academicTimeline.sequences?.map(seq => (
                        <Badge 
                          key={seq.id} 
                          bg={seq.is_active ? 'success' : 'secondary'}
                        >
                          Seq {seq.number}
                        </Badge>
                      ))}
                    </div>
                  </Col>
                </Row>
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Sélection hiérarchique */}
      <Row className="mb-4">
        <Col>
          <Card>
            <Card.Header className="d-flex justify-content-between align-items-center">
              <h6 className="mb-0">Sélection de la Classe</h6>
              <Button 
                variant="outline-primary" 
                size="sm" 
                onClick={fetchHierarchicalStructure}
                disabled={loadingHierarchy}
              >
                {loadingHierarchy ? (
                  <>
                    <Spinner size="sm" className="me-1" />
                    Chargement...
                  </>
                ) : (
                  <>
                    <ArrowClockwise className="me-1" />
                    Recharger
                  </>
                )}
              </Button>
            </Card.Header>
            <Card.Body>
              {/* Debug info */}
              <div className="mb-2 small text-muted">
                Sections chargées: {hierarchicalData.length} | 
                Section sélectionnée: {selectedSection || 'Aucune'} | 
                Niveau sélectionné: {selectedLevel || 'Aucun'} | 
                Classe sélectionnée: {selectedClass || 'Aucune'} | 
                Série sélectionnée: {selectedSeries || 'Aucune'}
              </div>
              <Row>
                <Col md={3}>
                  <Form.Group>
                    <Form.Label>Section</Form.Label>
                    <Form.Select 
                      value={selectedSection} 
                      onChange={(e) => handleSectionChange(e.target.value)}
                      disabled={loadingHierarchy}
                    >
                      <option value="">
                        {loadingHierarchy ? 'Chargement des sections...' : 'Choisir une section...'}
                      </option>
                      {hierarchicalData.map(section => {
                        console.log('Rendering section:', section);
                        return (
                          <option key={section.id} value={section.id}>
                            {section.name}
                          </option>
                        );
                      })}
                    </Form.Select>
                  </Form.Group>
                </Col>
                
                <Col md={3}>
                  <Form.Group>
                    <Form.Label>Niveau</Form.Label>
                    <Form.Select 
                      value={selectedLevel} 
                      onChange={(e) => handleLevelChange(e.target.value)}
                      disabled={!selectedSection}
                    >
                      <option value="">Choisir un niveau...</option>
                      {getSelectedLevels().map(level => (
                        <option key={level.id} value={level.id}>
                          {level.name}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
                
                <Col md={3}>
                  <Form.Group>
                    <Form.Label>Classe</Form.Label>
                    <Form.Select 
                      value={selectedClass} 
                      onChange={(e) => handleClassChange(e.target.value)}
                      disabled={!selectedLevel}
                    >
                      <option value="">Choisir une classe...</option>
                      {getSelectedClasses().map(schoolClass => (
                        <option key={schoolClass.id} value={schoolClass.id}>
                          {schoolClass.name}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
                
                <Col md={3}>
                  <Form.Group>
                    <Form.Label>Série</Form.Label>
                    <Form.Select 
                      value={selectedSeries} 
                      onChange={(e) => handleSeriesChange(e.target.value)}
                      disabled={!selectedClass}
                    >
                      <option value="">Choisir une série...</option>
                      {getSelectedSeries().map(series => (
                        <option key={series.id} value={series.id}>
                          {series.name}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
              </Row>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Filtres des périodes */}
      {selectedSeries && (
        <Row className="mb-3">
          <Col md={8}>
            <ButtonGroup>
              <Button 
                variant={selectedPeriodType === 'all' ? 'primary' : 'outline-primary'}
                onClick={() => setSelectedPeriodType('all')}
              >
                Tous
              </Button>
              <Button 
                variant={selectedPeriodType === 'sequences' ? 'primary' : 'outline-primary'}
                onClick={() => setSelectedPeriodType('sequences')}
              >
                Séquences
              </Button>
              <Button 
                variant={selectedPeriodType === 'trimesters' ? 'primary' : 'outline-primary'}
                onClick={() => setSelectedPeriodType('trimesters')}
              >
                Trimestres
              </Button>
              <Button 
                variant={selectedPeriodType === 'generated' ? 'success' : 'outline-success'}
                onClick={() => setSelectedPeriodType('generated')}
              >
                Générés
              </Button>
              <Button 
                variant={selectedPeriodType === 'pending' ? 'warning' : 'outline-warning'}
                onClick={() => setSelectedPeriodType('pending')}
              >
                En attente
              </Button>
            </ButtonGroup>
          </Col>
          <Col md={4} className="text-end">
            <Button 
              variant="success" 
              onClick={handleDownloadAllBulletins}
              disabled={loading || !selectedSeries}
              className="d-flex align-items-center justify-content-center"
            >
              <Download className="me-2" size={16} />
              {loading ? (
                <>
                  <Spinner size="sm" className="me-2" />
                  Téléchargement...
                </>
              ) : (
                'Télécharger Tous les Bulletins'
              )}
            </Button>
          </Col>
        </Row>
      )}

      {/* Messages d'alerte */}
      {error && <Alert variant="danger" onClose={() => setError('')} dismissible>{error}</Alert>}
      {success && <Alert variant="success" onClose={() => setSuccess('')} dismissible>{success}</Alert>}

      {/* Liste des étudiants avec statuts des bulletins */}
      {selectedSeries && (
        <Row>
          <Col>
            <Card>
              <Card.Header className="d-flex justify-content-between align-items-center">
                <h6 className="mb-0">Étudiants et Statuts des Bulletins</h6>
                <Button 
                  variant="outline-primary" 
                  size="sm" 
                  onClick={fetchStudentsData}
                  disabled={loading}
                >
                  <ArrowClockwise className="me-1" />
                  Actualiser
                </Button>
              </Card.Header>
              <Card.Body>
                {loading ? (
                  <div className="text-center py-4">
                    <Spinner animation="border" role="status">
                      <span className="visually-hidden">Chargement...</span>
                    </Spinner>
                  </div>
                ) : (
                  <div className="table-responsive">
                    <Table striped hover>
                      <thead>
                        <tr>
                          <th>Étudiant</th>
                          <th>Matricule</th>
                          <th>Séq 1</th>
                          <th>Séq 3</th>
                          <th>Trim 1</th>
                          <th>Trim 2</th>
                          <th>Trim 3</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filterStudentsByPeriod(studentsData).map((student) => (
                          <tr key={student.id}>
                            <td>
                              <strong>{student.first_name} {student.last_name}</strong>
                            </td>
                            <td>{student.matricule}</td>
                            
                            {/* Séquence 1 */}
                            <td>
                              {student.bulletins.sequence_1 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(
                                    student.bulletins.sequence_1.completion_percentage, 
                                    student.bulletins.sequence_1.is_generated
                                  )}
                                  {student.bulletins.sequence_1.completion_percentage > 0 && (
                                    <ProgressBar 
                                      now={student.bulletins.sequence_1.completion_percentage} 
                                      size="sm" 
                                      className="mt-1 w-100" 
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>
                            
                            {/* Séquence 3 */}
                            <td>
                              {student.bulletins.sequence_3 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(
                                    student.bulletins.sequence_3.completion_percentage, 
                                    student.bulletins.sequence_3.is_generated
                                  )}
                                  {student.bulletins.sequence_3.completion_percentage > 0 && (
                                    <ProgressBar 
                                      now={student.bulletins.sequence_3.completion_percentage} 
                                      size="sm" 
                                      className="mt-1 w-100" 
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>
                            
                            {/* Trimestres */}
                            {[1, 2, 3].map(trimNumber => (
                              <td key={`trim_${trimNumber}`}>
                                {student.bulletins[`trimester_${trimNumber}`] && (
                                  <div className="d-flex flex-column align-items-start">
                                    {getCompletionBadge(
                                      student.bulletins[`trimester_${trimNumber}`].completion_percentage, 
                                      student.bulletins[`trimester_${trimNumber}`].is_generated
                                    )}
                                    {student.bulletins[`trimester_${trimNumber}`].completion_percentage > 0 && (
                                      <ProgressBar 
                                        now={student.bulletins[`trimester_${trimNumber}`].completion_percentage} 
                                        size="sm" 
                                        className="mt-1 w-100" 
                                        style={{height: '4px'}}
                                      />
                                    )}
                                  </div>
                                )}
                              </td>
                            ))}
                            
                            {/* Actions */}
                            <td>
                              <div className="d-flex gap-1 flex-wrap">
                                {Object.entries(student.bulletins).map(([key, bulletin]) => {
                                  if (!bulletin.is_generated && bulletin.completion_percentage < 50) return null;
                                  
                                  return (
                                    <div key={key} className="btn-group-vertical btn-group-sm">
                                      {/* Preview */}
                                      <Button
                                        variant="outline-info"
                                        size="sm"
                                        onClick={() => handlePreviewBulletin(
                                          student.id, 
                                          `${student.first_name}_${student.last_name}`,
                                          bulletin.type,
                                          bulletin.identifier
                                        )}
                                        title={`Prévisualiser ${bulletin.name}`}
                                      >
                                        <Eye size={12} />
                                      </Button>
                                      
                                      {/* Download */}
                                      {bulletin.is_generated && (
                                        <Button
                                          variant="outline-success"
                                          size="sm"
                                          onClick={() => handleDownloadBulletin(
                                            bulletin.bulletin_id,
                                            `${student.first_name}_${student.last_name}`,
                                            bulletin.type,
                                            bulletin.identifier
                                          )}
                                          title={`Télécharger ${bulletin.name}`}
                                        >
                                          <Download size={12} />
                                        </Button>
                                      )}
                                      
                                      {/* Force Regenerate */}
                                      <Button
                                        variant="outline-warning"
                                        size="sm"
                                        onClick={() => handleForceRegenerate(
                                          student.id,
                                          bulletin.type,
                                          bulletin.identifier
                                        )}
                                        title={`Forcer régénération ${bulletin.name}`}
                                      >
                                        <ArrowClockwise size={12} />
                                      </Button>
                                    </div>
                                  );
                                })}
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                    
                    {filterStudentsByPeriod(studentsData).length === 0 && (
                      <div className="text-center py-4">
                        <p className="text-muted">Aucun étudiant trouvé pour les critères sélectionnés.</p>
                      </div>
                    )}
                  </div>
                )}
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Modal de prévisualisation */}
      <Modal 
        show={showPreviewModal} 
        onHide={() => setShowPreviewModal(false)} 
        size="xl"
        fullscreen="lg-down"
      >
        <Modal.Header closeButton>
          <Modal.Title>
            <Printer className="me-2" />
            Prévisualisation du Bulletin
            {previewStudent && ` - ${previewStudent.name}`}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body style={{ maxHeight: '70vh', overflow: 'auto' }}>
          {previewContent && (
            <div dangerouslySetInnerHTML={{ __html: previewContent }} />
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowPreviewModal(false)}>
            Fermer
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

export default BulletinManagementNew;