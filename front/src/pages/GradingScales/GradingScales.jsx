import React, { useState, useEffect, useCallback } from 'react';
import { Card, Button, Modal, Form, Row, Col, Alert, Badge, ProgressBar } from 'react-bootstrap';
import { Plus, Award, Palette, Eye, Pencil, Trash, ToggleOff, ToggleOn } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { useAuth } from '../../hooks/useAuth';
import Swal from 'sweetalert2';

function GradingScales() {
  const { user } = useAuth();
  const [schoolYears, setSchoolYears] = useState([]);
  const [levels, setLevels] = useState([]);
  const [selectedYear, setSelectedYear] = useState('');
  const [gradingScales, setGradingScales] = useState([]);
  const [loading, setLoading] = useState(true);
  
  // Filtres
  const [filterLevel, setFilterLevel] = useState('all');
  const [filterGrade, setFilterGrade] = useState('all');
  
  // Modals
  const [showScaleModal, setShowScaleModal] = useState(false);
  const [editingScale, setEditingScale] = useState(null);
  
  // Form
  const [scaleForm, setScaleForm] = useState({
    level_id: null,
    grade_code: '',
    grade_label: '',
    min_score: '',
    max_score: '',
    appreciation: '',
    color_code: '#6c757d',
    is_passing_grade: true,
    is_active: true,
    order: 0
  });

  const defaultGrades = [
    { code: 'TB', label: 'Très Bien', min: 18, max: 20, color: '#28a745', appreciation: 'Excellent travail, félicitations !' },
    { code: 'B', label: 'Bien', min: 16, max: 17.99, color: '#20c997', appreciation: 'Bon travail, continue ainsi !' },
    { code: 'AB', label: 'Assez Bien', min: 14, max: 15.99, color: '#17a2b8', appreciation: 'Travail satisfaisant, peut mieux faire.' },
    { code: 'P', label: 'Passable', min: 12, max: 13.99, color: '#ffc107', appreciation: 'Travail moyen, des efforts sont nécessaires.' },
    { code: 'M', label: 'Médiocre', min: 10, max: 11.99, color: '#fd7e14', appreciation: 'Travail insuffisant, beaucoup d\'efforts à fournir.' },
    { code: 'I', label: 'Insuffisant', min: 0, max: 9.99, color: '#dc3545', appreciation: 'Travail très insuffisant, redoublement d\'efforts requis.' }
  ];

  const loadInitialData = useCallback(async () => {
    try {
      const [yearsRes, levelsRes] = await Promise.all([
        secureApiEndpoints.request('/school-years/active'),
        secureApiEndpoints.levels.getAll()
      ]);

      if (yearsRes.success) {
        setSchoolYears(yearsRes.data);
        const currentYear = yearsRes.data.find(year => year.is_current);
        if (currentYear) {
          setSelectedYear(currentYear.id.toString());
        }
      }

      if (levelsRes.success) {
        setLevels(levelsRes.data.filter(level => level.is_active));
      }
    } catch (error) {
      console.error('Erreur lors du chargement:', error);
    } finally {
      setLoading(false);
    }
  }, []);

  const loadGradingScales = useCallback(async () => {
    if (!selectedYear) return;
    
    try {
      const response = await secureApiEndpoints.gradingScales.getAll({
        school_year_id: selectedYear
      });
      
      if (response.success) {
        setGradingScales(response.data);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des barèmes:', error);
    }
  }, [selectedYear]);

  useEffect(() => {
    loadInitialData();
  }, [loadInitialData]);

  useEffect(() => {
    if (selectedYear) {
      loadGradingScales();
    }
  }, [selectedYear, loadGradingScales]);

  const handleScaleSubmit = async (e) => {
    e.preventDefault();
    
    // Validation
    if (parseFloat(scaleForm.min_score) >= parseFloat(scaleForm.max_score)) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur de validation',
        text: 'La note minimale doit être inférieure à la note maximale'
      });
      return;
    }
    
    try {
      const response = editingScale 
        ? await secureApiEndpoints.gradingScales.update(editingScale.id, {
            ...scaleForm,
            school_year_id: selectedYear
          })
        : await secureApiEndpoints.gradingScales.create({
            ...scaleForm,
            school_year_id: selectedYear
          });

      if (response.success) {
        setShowScaleModal(false);
        setEditingScale(null);
        loadGradingScales();
        Swal.fire({
          icon: 'success',
          title: 'Succès',
          text: response.message
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: response.message || 'Erreur lors de la sauvegarde'
        });
      }
    } catch (error) {
      console.error('Erreur lors de la sauvegarde:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erreur technique',
        text: 'Une erreur est survenue lors de la sauvegarde'
      });
    }
  };

  const handleCreateDefault = async (levelId = null) => {
    try {
      const response = await secureApiEndpoints.gradingScales.createDefault({
        school_year_id: selectedYear,
        level_id: levelId
      });

      if (response.success) {
        loadGradingScales();
        Swal.fire({
          icon: 'success',
          title: 'Succès',
          text: response.message
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: response.message
        });
      }
    } catch (error) {
      console.error('Erreur lors de la création:', error);
    }
  };

  const handleEdit = (scale) => {
    setEditingScale(scale);
    setScaleForm({
      level_id: scale.level_id,
      grade_code: scale.grade_code,
      grade_label: scale.grade_label,
      min_score: scale.min_score,
      max_score: scale.max_score,
      appreciation: scale.appreciation,
      color_code: scale.color_code,
      is_passing_grade: scale.is_passing_grade,
      is_active: scale.is_active,
      order: scale.order
    });
    setShowScaleModal(true);
  };

  const handleDelete = async (scale) => {
    const result = await Swal.fire({
      title: 'Confirmer la suppression',
      text: `Supprimer le barème "${scale.grade_code} - ${scale.grade_label}" ?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Supprimer',
      cancelButtonText: 'Annuler'
    });

    if (result.isConfirmed) {
      try {
        const response = await secureApiEndpoints.gradingScales.delete(scale.id);

        if (response.success) {
          loadGradingScales();
          Swal.fire('Supprimé!', response.message, 'success');
        }
      } catch (error) {
        console.error('Erreur lors de la suppression:', error);
      }
    }
  };

  const resetForm = () => {
    setScaleForm({
      level_id: null,
      grade_code: '',
      grade_label: '',
      min_score: '',
      max_score: '',
      appreciation: '',
      color_code: '#6c757d',
      is_passing_grade: true,
      is_active: true,
      order: 0
    });
    setEditingScale(null);
  };

  const getFilteredScales = () => {
    return gradingScales.filter(scale => {
      const matchesLevel = filterLevel === 'all' || 
        (filterLevel === 'global' && !scale.level_id) ||
        (scale.level_id && scale.level_id.toString() === filterLevel);
      const matchesGrade = filterGrade === 'all' || scale.grade_code === filterGrade;
      return matchesLevel && matchesGrade;
    });
  };

  if (loading) {
    return <div>Chargement...</div>;
  }

  return (
    <div className="container-fluid mt-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>Barèmes de Notation</h2>
        <div className="d-flex gap-2">
          <Button 
            variant="outline-primary"
            onClick={() => handleCreateDefault()}
            disabled={!selectedYear}
          >
            <Award className="me-2" />
            Créer Barème Global
          </Button>
          <Button 
            variant="primary"
            onClick={() => {
              resetForm();
              setShowScaleModal(true);
            }}
            disabled={!selectedYear}
          >
            <Plus className="me-2" />
            Nouveau Barème
          </Button>
        </div>
      </div>

      {/* Sélection + Filtres */}
      <Card className="mb-4">
        <Card.Body>
          <Row>
            <Col md={4}>
              <Form.Group>
                <Form.Label>Année Scolaire</Form.Label>
                <Form.Select 
                  value={selectedYear} 
                  onChange={(e) => setSelectedYear(e.target.value)}
                >
                  <option value="">Sélectionner une année</option>
                  {schoolYears.map(year => (
                    <option key={year.id} value={year.id}>
                      {year.name} {year.is_current ? '(Actuelle)' : ''}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
            <Col md={4}>
              <Form.Group>
                <Form.Label>Filtrer par Niveau</Form.Label>
                <Form.Select 
                  value={filterLevel} 
                  onChange={(e) => setFilterLevel(e.target.value)}
                >
                  <option value="all">Tous les niveaux</option>
                  <option value="global">Barèmes globaux</option>
                  {levels.map(level => (
                    <option key={level.id} value={level.id}>
                      {level.name} - {level.section?.name}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
            <Col md={4}>
              <Form.Group>
                <Form.Label>Filtrer par Grade</Form.Label>
                <Form.Select 
                  value={filterGrade} 
                  onChange={(e) => setFilterGrade(e.target.value)}
                >
                  <option value="all">Tous les grades</option>
                  {defaultGrades.map(grade => (
                    <option key={grade.code} value={grade.code}>
                      {grade.code} - {grade.label}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Barèmes existants */}
      {selectedYear && (
        <Card>
          <Card.Header>
            <h5 className="mb-0">Barèmes de Notation</h5>
          </Card.Header>
          <Card.Body>
            {getFilteredScales().length === 0 ? (
              <Alert variant="info">
                <div className="d-flex align-items-center">
                  <Award className="me-2" />
                  Aucun barème trouvé. Créez un barème global ou spécifique par niveau.
                </div>
              </Alert>
            ) : (
              <Row>
                {getFilteredScales().map(scale => (
                  <Col md={6} lg={4} key={scale.id} className="mb-3">
                    <Card className="h-100 border">
                      <Card.Body>
                        <div className="d-flex justify-content-between align-items-start mb-3">
                          <div>
                            <div className="d-flex align-items-center gap-2 mb-1">
                              <Badge 
                                style={{backgroundColor: scale.color_code, fontSize: '14px'}}
                                className="text-white"
                              >
                                {scale.grade_code}
                              </Badge>
                              <h6 className="mb-0">{scale.grade_label}</h6>
                            </div>
                            <small className="text-muted">
                              {scale.level ? `${scale.level.name} - ${scale.level.section?.name}` : 'Global'}
                            </small>
                          </div>
                          <span className={`badge ${scale.is_active ? 'bg-success' : 'bg-secondary'}`}>
                            {scale.is_active ? 'Actif' : 'Inactif'}
                          </span>
                        </div>
                        
                        <div className="mb-3">
                          <div className="d-flex justify-content-between mb-2">
                            <span>Intervalle:</span>
                            <strong>{scale.min_score} - {scale.max_score} / 20</strong>
                          </div>
                          
                          <div className="mb-2">
                            <small className="text-muted">Barre de progression:</small>
                            <ProgressBar 
                              now={((scale.max_score / 20) * 100)}
                              style={{backgroundColor: '#e9ecef'}}
                            >
                              <div 
                                style={{
                                  backgroundColor: scale.color_code,
                                  width: `${((scale.max_score - scale.min_score) / 20) * 100}%`,
                                  marginLeft: `${(scale.min_score / 20) * 100}%`
                                }}
                                className="progress-bar"
                              />
                            </ProgressBar>
                          </div>

                          <div className="mb-2">
                            <span className={`badge ${scale.is_passing_grade ? 'bg-success' : 'bg-danger'}`}>
                              {scale.is_passing_grade ? 'Note de passage' : 'Échec'}
                            </span>
                          </div>
                        </div>

                        <div className="mb-3">
                          <small className="text-muted d-block">Appréciation:</small>
                          <p className="small text-dark mb-0" style={{fontSize: '12px'}}>
                            {scale.appreciation}
                          </p>
                        </div>

                        <div className="d-flex gap-1">
                          <Button 
                            size="sm" 
                            variant="outline-primary"
                            onClick={() => handleEdit(scale)}
                          >
                            <Pencil size={14} />
                          </Button>
                          <Button 
                            size="sm" 
                            variant="outline-danger"
                            onClick={() => handleDelete(scale)}
                          >
                            <Trash size={14} />
                          </Button>
                        </div>
                      </Card.Body>
                    </Card>
                  </Col>
                ))}
              </Row>
            )}
          </Card.Body>
        </Card>
      )}

      {/* Modal Configuration */}
      <Modal show={showScaleModal} onHide={() => {setShowScaleModal(false); resetForm();}} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            {editingScale ? 'Modifier le Barème' : 'Nouveau Barème de Notation'}
          </Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleScaleSubmit}>
          <Modal.Body>
            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Niveau (optionnel)</Form.Label>
                  <Form.Select
                    value={scaleForm.level_id || ''}
                    onChange={(e) => setScaleForm({...scaleForm, level_id: e.target.value || null})}
                  >
                    <option value="">Barème global (tous niveaux)</option>
                    {levels.map(level => (
                      <option key={level.id} value={level.id}>
                        {level.name} - {level.section?.name}
                      </option>
                    ))}
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Code Grade</Form.Label>
                  <Form.Control
                    type="text"
                    value={scaleForm.grade_code}
                    onChange={(e) => setScaleForm({...scaleForm, grade_code: e.target.value.toUpperCase()})}
                    placeholder="TB, B, AB..."
                    maxLength={5}
                    required
                  />
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Libellé</Form.Label>
                  <Form.Control
                    type="text"
                    value={scaleForm.grade_label}
                    onChange={(e) => setScaleForm({...scaleForm, grade_label: e.target.value})}
                    placeholder="Très Bien, Bien..."
                    required
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Couleur</Form.Label>
                  <div className="d-flex gap-2 align-items-center">
                    <Form.Control
                      type="color"
                      value={scaleForm.color_code}
                      onChange={(e) => setScaleForm({...scaleForm, color_code: e.target.value})}
                      style={{width: '50px', height: '38px'}}
                    />
                    <Form.Control
                      type="text"
                      value={scaleForm.color_code}
                      onChange={(e) => setScaleForm({...scaleForm, color_code: e.target.value})}
                      placeholder="#28a745"
                    />
                  </div>
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Note Minimale (/20)</Form.Label>
                  <Form.Control
                    type="number"
                    step="0.01"
                    min="0"
                    max="20"
                    value={scaleForm.min_score}
                    onChange={(e) => setScaleForm({...scaleForm, min_score: e.target.value})}
                    required
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Note Maximale (/20)</Form.Label>
                  <Form.Control
                    type="number"
                    step="0.01"
                    min="0"
                    max="20"
                    value={scaleForm.max_score}
                    onChange={(e) => setScaleForm({...scaleForm, max_score: e.target.value})}
                    required
                  />
                </Form.Group>
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Appréciation Automatique</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                value={scaleForm.appreciation}
                onChange={(e) => setScaleForm({...scaleForm, appreciation: e.target.value})}
                placeholder="Texte d'appréciation qui apparaîtra automatiquement pour cette note..."
                required
              />
            </Form.Group>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Check
                    type="checkbox"
                    label="Note de passage (permet le passage en classe supérieure)"
                    checked={scaleForm.is_passing_grade}
                    onChange={(e) => setScaleForm({...scaleForm, is_passing_grade: e.target.checked})}
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Check
                    type="checkbox"
                    label="Barème actif"
                    checked={scaleForm.is_active}
                    onChange={(e) => setScaleForm({...scaleForm, is_active: e.target.checked})}
                  />
                </Form.Group>
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Ordre d'affichage</Form.Label>
              <Form.Control
                type="number"
                min="0"
                value={scaleForm.order}
                onChange={(e) => setScaleForm({...scaleForm, order: parseInt(e.target.value)})}
              />
            </Form.Group>

            {/* Aperçu */}
            <Alert variant="secondary">
              <div className="d-flex align-items-center gap-3">
                <Badge 
                  style={{backgroundColor: scaleForm.color_code, fontSize: '16px'}}
                  className="text-white px-3 py-2"
                >
                  {scaleForm.grade_code || '?'}
                </Badge>
                <div>
                  <strong>{scaleForm.grade_label || 'Libellé'}</strong>
                  <div className="small text-muted">
                    {scaleForm.min_score || '0'} - {scaleForm.max_score || '0'} / 20
                    {scaleForm.is_passing_grade ? ' • Note de passage' : ' • Échec'}
                  </div>
                </div>
              </div>
            </Alert>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => {setShowScaleModal(false); resetForm();}}>
              Annuler
            </Button>
            <Button 
              variant="primary" 
              type="submit"
              disabled={!scaleForm.grade_code || !scaleForm.grade_label || !scaleForm.min_score || !scaleForm.max_score || !scaleForm.appreciation}
            >
              {editingScale ? 'Mettre à jour' : 'Créer le Barème'}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

export default GradingScales;