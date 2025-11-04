import React, { useState, useEffect } from 'react';
import { Card, Button, Table, Badge, Modal, Row, Col, Alert, Spinner, ProgressBar, Accordion, Form, ButtonGroup } from 'react-bootstrap';
import { CardText, Download, Eye, Printer, ArrowClockwise, Clock, CheckCircle, ExclamationCircle, Calendar, Book } from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';
import { authService } from '../../services/authService';
import { host } from '../../utils/fetch';

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
  
  // Navigation temporelle
  const [availablePeriods, setAvailablePeriods] = useState([]);
  const [selectedViewPeriod, setSelectedViewPeriod] = useState('current');
  
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
  }, [selectedSeries, selectedViewPeriod]);

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
      // Ajouter le paramètre de période pour la navigation temporelle
      const params = selectedViewPeriod !== 'current' ? `?period=${selectedViewPeriod}` : '';
      const response = await secureApi.get(`/bulletins/students-status/${selectedSeries}${params}`);
      
      // secureApi returns parsed JSON directly
      if (response && response.success) {
        const students = response.students || [];
        setStudentsData(students);
        setAvailablePeriods(response.available_periods || []);
        
        // 🔍 DEBUG: Vérifier les bulletins de HASSIM ACHTA
        const hassim = students.find(s => s.first_name === 'HASSIM' && s.last_name === 'ACHTA');
        if (hassim) {
          console.log('🔍 DEBUG HASSIM ACHTA bulletins received from API:');
          console.log('  Full bulletins object:', hassim.bulletins);
          console.log('  sequence_1:', hassim.bulletins.sequence_1);
          console.log('  trimester_1:', hassim.bulletins.trimester_1);
          if (hassim.bulletins.sequence_1) {
            console.log('  sequence_1.bulletin_id:', hassim.bulletins.sequence_1.bulletin_id, '(type:', typeof hassim.bulletins.sequence_1.bulletin_id, ')');
            console.log('  sequence_1.is_generated:', hassim.bulletins.sequence_1.is_generated);
          }
        }
      } else {
        setStudentsData([]);
        setAvailablePeriods([]);
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

  const handleDownloadBulletin = async (bulletinId, studentName, periodType, periodId, studentId = null) => {
    try {
      setLoading(true);
      setError(''); // Clear previous errors
      
      // 🔍 DEBUG: Afficher les paramètres reçus avec plus de détails
      console.log('🔍 DEBUG handleDownloadBulletin called with:');
      console.log('  bulletinId:', bulletinId, '(type:', typeof bulletinId, ')');
      console.log('  studentName:', studentName);
      console.log('  periodType:', periodType);  
      console.log('  periodId:', periodId);
      console.log('  studentId:', studentId);
      
      // Utiliser fetch directement pour les téléchargements de fichiers
      const token = authService.getToken();
      const response = await fetch(`${host}/api/bulletins/download/${bulletinId}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf'
        }
      });

      if (!response.ok) {
        if (response.status === 404) {
          // LOGIQUE INTELLIGENTE: Auto-générer le bulletin manquant
          console.warn(`Bulletin ${bulletinId} not found, auto-generating...`);
          setError('Bulletin manquant. Génération automatique en cours...');
          
          try {
            // Forcer la régénération automatiquement
            await secureApi.post('/bulletins/force-regenerate', {
              student_id: studentId || null, // Il faudra passer studentId en paramètre
              period_type: periodType,
              period_identifier: periodId
            });
            
            // Actualiser les données
            await fetchStudentsData();
            
            setSuccess('Bulletin généré automatiquement. Vous pouvez maintenant le télécharger.');
            return;
            
          } catch (genError) {
            console.error('Auto-generation failed:', genError);
            setError('Impossible de générer automatiquement le bulletin. Veuillez utiliser le bouton "Régénérer".');
            return;
          }
        }
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
        setError(''); // Clear any previous errors
        
        await secureApi.post('/bulletins/force-regenerate', {
          student_id: studentId,
          period_type: periodType,
          period_identifier: periodId
        });
        
        setSuccess('Bulletin régénéré avec succès');
        
        // Force reload data to get new bulletin IDs
        await fetchStudentsData();
        
        // Additional delay to ensure data is updated
        setTimeout(() => {
          fetchStudentsData();
        }, 1000);
        
      } catch (error) {
        console.error('Error during regeneration:', error);
        setError('Erreur lors de la régénération: ' + (error.message || 'Unknown error'));
      } finally {
        setLoading(false);
      }
    }
  };

  const handleDownloadStudentBulletins = async (student) => {
    try {
      setLoading(true);
      setError('');

      // Collecter tous les bulletins disponibles (sequences + trimesters)
      const availableBulletins = [];

      // Vérifier toutes les propriétés de bulletins
      if (student.bulletins) {
        Object.entries(student.bulletins).forEach(([key, bulletin]) => {
          if (bulletin && (bulletin.is_generated || bulletin.bulletin_id)) {
            availableBulletins.push({
              ...bulletin,
              key: key
            });
          }
        });
      }

      if (availableBulletins.length === 0) {
        setError('Aucun bulletin disponible pour cet élève');
        setTimeout(() => setError(''), 3000);
        return;
      }

      if (!window.confirm(`Télécharger les ${availableBulletins.length} bulletin(s) de ${student.first_name} ${student.last_name} ?`)) {
        return;
      }

      // Télécharger chaque bulletin séquentiellement
      let downloadedCount = 0;
      for (const bulletin of availableBulletins) {
        try {
          await handleDownloadBulletin(
            bulletin.bulletin_id,
            `${student.first_name}_${student.last_name}`,
            bulletin.type || bulletin.period_type,
            bulletin.identifier || bulletin.period_identifier,
            student.id
          );
          downloadedCount++;
          // Petite pause entre les téléchargements
          await new Promise(resolve => setTimeout(resolve, 800));
        } catch (err) {
          console.error(`Erreur téléchargement bulletin ${bulletin.key}:`, err);
        }
      }

      setSuccess(`${downloadedCount}/${availableBulletins.length} bulletin(s) téléchargé(s) avec succès`);
      setTimeout(() => setSuccess(''), 3000);

    } catch (error) {
      console.error('Erreur téléchargement bulletins élève:', error);
      setError('Erreur lors du téléchargement des bulletins');
      setTimeout(() => setError(''), 3000);
    } finally {
      setLoading(false);
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
      const response = await fetch(`${host}/api/bulletins/download-all`, {
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

  const getCompletionBadge = (bulletin) => {
    const { completion_percentage, is_generated, status, is_archived } = bulletin;
    
    if (is_archived) {
      return <Badge bg="dark" className="d-flex align-items-center">
        📁 <span className="ms-1">Archivé ({completion_percentage}%)</span>
      </Badge>;
    }
    
    if (is_generated) {
      return <Badge bg="success" className="d-flex align-items-center">
        <CheckCircle className="me-1" size={12} />
        Généré ({completion_percentage}%)
      </Badge>;
    }
    
    if (status === 'future') {
      return <Badge bg="light" text="dark" className="d-flex align-items-center">
        ⏳ <span className="ms-1">Futur ({completion_percentage}%)</span>
      </Badge>;
    }
    
    if (completion_percentage >= 50) {
      return <Badge bg="warning" className="d-flex align-items-center">
        <Clock className="me-1" size={12} />
        Prêt ({completion_percentage}%)
      </Badge>;
    }
    
    return <Badge bg="secondary" className="d-flex align-items-center">
      <ExclamationCircle className="me-1" size={12} />
      Incomplet ({completion_percentage}%)
    </Badge>;
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
                  {/* <Col md={6} className="text-end">
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
                  </Col> */}
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

      {/* Sélecteur de navigation temporelle */}
      {selectedSeries && availablePeriods.length > 0 && (
        <Row className="mb-3">
          <Col>
            <Card>
              <Card.Header className="d-flex justify-content-between align-items-center">
                <h6 className="mb-0">🕐 Navigation Temporelle</h6>
                <Badge bg="info">
                  Vue: {availablePeriods.find(p => p.identifier === selectedViewPeriod)?.name || 'Actuelle'}
                </Badge>
              </Card.Header>
              <Card.Body>
                <Row>
                  <Col>
                    <Form.Label>Période à visualiser :</Form.Label>
                    <Form.Select 
                      value={selectedViewPeriod} 
                      onChange={(e) => setSelectedViewPeriod(e.target.value)}
                      className="mb-2"
                    >
                      {availablePeriods.map(period => (
                        <option key={period.identifier} value={period.identifier}>
                          {period.status === 'past' && '📁 '} 
                          {period.status === 'current' && '▶️ '} 
                          {period.status === 'future' && '⏳ '}
                          {period.name}
                          {period.status === 'past' && ' (Archive)'}
                          {period.status === 'current' && ' (En cours)'}
                          {period.status === 'future' && ' (Futur)'}
                        </option>
                      ))}
                    </Form.Select>
                  </Col>
                </Row>
                <div className="d-flex gap-2 flex-wrap">
                  {availablePeriods.filter(p => p.type !== 'view').map(period => (
                    <Button
                      key={period.identifier}
                      variant={selectedViewPeriod === period.identifier ? "primary" : "outline-secondary"}
                      size="sm"
                      onClick={() => setSelectedViewPeriod(period.identifier)}
                      className="d-flex align-items-center"
                    >
                      {period.status === 'past' && <span className="me-1">📁</span>}
                      {period.status === 'current' && <span className="me-1">▶️</span>}
                      {period.status === 'future' && <span className="me-1">⏳</span>}
                      {period.name}
                    </Button>
                  ))}
                </div>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

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
                  onClick={() => {
                    console.log('🔄 Force refresh des données...');
                    fetchStudentsData();
                  }}
                  disabled={loading}
                >
                  <ArrowClockwise className="me-1" />
                  Actualiser & Debug
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
                          <th>Séq 2</th>
                          <th>Trim 1</th>
                          <th>Séq 3</th>
                          <th>Séq 4</th>
                          <th>Trim 2</th>
                          <th>Trim 3</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filterStudentsByPeriod(studentsData).map((student) => (
                          <tr key={student.id}>
                            <td>
                              <div className="d-flex justify-content-between align-items-center">
                                <strong>{student.first_name} {student.last_name}</strong>
                                {student.bulletins && Object.values(student.bulletins).some(b => b && (b.is_generated || b.bulletin_id)) && (
                                  <Button
                                    variant="outline-primary"
                                    size="sm"
                                    onClick={() => handleDownloadStudentBulletins(student)}
                                    title={`Télécharger tous les bulletins de ${student.first_name}`}
                                    className="ms-2"
                                  >
                                    <Download size={12} /> Tous
                                  </Button>
                                )}
                              </div>
                            </td>
                            <td>{student.matricule}</td>
                            
                            {/* Séquence 1 */}
                            <td>
                              {student.bulletins.sequence_1 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.sequence_1)}
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

                            {/* Séquence 2 */}
                            <td>
                              {student.bulletins.sequence_2 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.sequence_2)}
                                  {student.bulletins.sequence_2.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.sequence_2.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Trimestre 1 */}
                            <td>
                              {student.bulletins.trimester_1 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.trimester_1)}
                                  {student.bulletins.trimester_1.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.trimester_1.completion_percentage}
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
                                  {getCompletionBadge(student.bulletins.sequence_3)}
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

                            {/* Séquence 4 */}
                            <td>
                              {student.bulletins.sequence_4 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.sequence_4)}
                                  {student.bulletins.sequence_4.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.sequence_4.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Trimestre 2 */}
                            <td>
                              {student.bulletins.trimester_2 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.trimester_2)}
                                  {student.bulletins.trimester_2.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.trimester_2.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Trimestre 3 */}
                            <td>
                              {student.bulletins.trimester_3 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.trimester_3)}
                                  {student.bulletins.trimester_3.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.trimester_3.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Actions */}
                            <td>
                              <div className="d-flex gap-1 flex-wrap">
                                {Object.entries(student.bulletins).map(([key, bulletin]) => {
                                  // Permettre l'affichage pour tous les bulletins avec données (pas seulement >= 50%)
                                  if (!bulletin.can_preview && bulletin.completion_percentage < 10) return null;
                                  
                                  return (
                                    <div key={key} className="btn-group-vertical btn-group-sm">
                                      {/* Preview - Toujours disponible si can_preview */}
                                      {bulletin.can_preview && (
                                        <Button
                                          variant={bulletin.status === 'future' ? 'outline-secondary' : 
                                                   bulletin.is_archived ? 'outline-dark' : 'outline-info'}
                                          size="sm"
                                          onClick={() => handlePreviewBulletin(
                                            student.id, 
                                            `${student.first_name}_${student.last_name}`,
                                            bulletin.type,
                                            bulletin.identifier
                                          )}
                                          title={`${bulletin.status === 'future' ? 'Aperçu futur' : 
                                                   bulletin.is_archived ? 'Voir archive' : 'Prévisualiser'} ${bulletin.name}`}
                                        >
                                          <Eye size={12} />
                                          {bulletin.status === 'future' && <span className="ms-1">⏳</span>}
                                          {bulletin.is_archived && <span className="ms-1">📁</span>}
                                        </Button>
                                      )}
                                      
                                      {/* Download - Only if is_generated or bulletin_id exists */}
                                      {(bulletin.is_generated || bulletin.bulletin_id) ? (
                                        <Button
                                          variant={bulletin.is_archived ? 'dark' : 'success'}
                                          size="sm"
                                          onClick={() => handleDownloadBulletin(
                                            bulletin.bulletin_id,
                                            `${student.first_name}_${student.last_name}`,
                                            bulletin.type,
                                            bulletin.identifier,
                                            student.id
                                          )}
                                          title={`Télécharger ${bulletin.name}${bulletin.is_archived ? ' (Archive)' : ''}`}
                                          className="d-flex align-items-center gap-1"
                                        >
                                          <Download size={14} />
                                          <span style={{ fontSize: '11px' }}>PDF</span>
                                          {bulletin.is_archived && <span>📁</span>}
                                        </Button>
                                      ) : null}
                                      
                                      {/* Force Regenerate - Seulement pour les périodes actuelles */}
                                      {bulletin.status === 'current' && (
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
                                      )}
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