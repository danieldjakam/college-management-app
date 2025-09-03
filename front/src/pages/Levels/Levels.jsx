import React, { useState, useEffect, useCallback } from 'react';
import { Card, Button, Modal, Form, Row, Col, Alert, ProgressBar } from 'react-bootstrap';
import { Plus, Gear, BookFill, CheckCircle, XCircle } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { useAuth } from '../../hooks/useAuth';
import Swal from 'sweetalert2';

function EvaluationConfiguration() {
  const { user } = useAuth();
  const [schoolYears, setSchoolYears] = useState([]);
  const [levels, setLevels] = useState([]);
  const [selectedYear, setSelectedYear] = useState('');
  const [configurations, setConfigurations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filterMode, setFilterMode] = useState('all');
  const [filterLevel, setFilterLevel] = useState('all');
  
  // Modals
  const [showConfigModal, setShowConfigModal] = useState(false);
  const [editingConfig, setEditingConfig] = useState(null);
  
  // Form
  const [configForm, setConfigForm] = useState({
    level_id: '',
    evaluation_mode: '1ds_1comp', // ou '2ds_1comp'
    ds1_percentage: 50,
    ds2_percentage: 0, // uniquement pour 2ds_1comp
    composition_percentage: 50,
    description: '',
    is_active: true
  });

  const evaluationModes = [
    {
      value: '1ds_1comp',
      label: '1 DS + 1 Composition',
      description: 'Idéal pour les premiers cycles',
      default_percentages: { ds1: 50, ds2: 0, composition: 50 }
    },
    {
      value: '2ds_1comp', 
      label: '2 DS + 1 Composition',
      description: 'Généralement pour les seconds cycles',
      default_percentages: { ds1: 25, ds2: 25, composition: 50 }
    }
  ];

  const loadInitialData = useCallback(async () => {
    try {
      console.log('Chargement des données initiales...');
      
      const [yearsRes, levelsRes] = await Promise.all([
        secureApiEndpoints.request('/school-years/active'),
        secureApiEndpoints.levels.getAll()
      ]);

      console.log('Years response:', yearsRes);
      console.log('Levels response:', levelsRes);

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

  const loadConfigurations = useCallback(async () => {
    if (!selectedYear) return;
    
    try {
      console.log('Chargement des configurations pour l\'année:', selectedYear);
      const response = await secureApiEndpoints.evaluationConfigs.getAll({
        school_year_id: selectedYear
      });
      
      console.log('Configurations response:', response);
      
      if (response.success) {
        setConfigurations(response.data);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des configurations:', error);
    }
  }, [selectedYear]);

  useEffect(() => {
    loadInitialData();
  }, [loadInitialData]);

  useEffect(() => {
    if (selectedYear) {
      loadConfigurations();
    }
  }, [selectedYear, loadConfigurations]);

  const handleModeChange = (mode) => {
    const modeConfig = evaluationModes.find(m => m.value === mode);
    if (modeConfig) {
      setConfigForm({
        ...configForm,
        evaluation_mode: mode,
        ds1_percentage: modeConfig.default_percentages.ds1,
        ds2_percentage: modeConfig.default_percentages.ds2,
        composition_percentage: modeConfig.default_percentages.composition
      });
    }
  };

  const handleConfigSubmit = async (e) => {
    e.preventDefault();
    console.log('Soumission de la configuration:', configForm);
    
    // Validation du niveau
    if (!configForm.level_id) {
      Swal.fire({
        icon: 'warning',
        title: 'Niveau requis',
        text: 'Veuillez sélectionner un cycle/niveau'
      });
      return;
    }
    
    // Validation des pourcentages
    const total = configForm.ds1_percentage + configForm.ds2_percentage + configForm.composition_percentage;
    if (total !== 100) {
      Swal.fire({
        icon: 'error',
        title: 'Pourcentages invalides',
        text: `Le total des pourcentages doit être 100%. Total actuel: ${total}%`
      });
      return;
    }
    
    try {
      const response = editingConfig 
        ? await secureApiEndpoints.evaluationConfigs.update(editingConfig.id, {
            ...configForm,
            school_year_id: selectedYear,
            level_id: configForm.level_id
          })
        : await secureApiEndpoints.evaluationConfigs.create({
            ...configForm,
            school_year_id: selectedYear
          });

      console.log('Configuration sauvegardée:', response);

      if (response.success) {
        setShowConfigModal(false);
        setEditingConfig(null);
        loadConfigurations();
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
      console.error('Erreur lors de la configuration:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erreur technique',
        text: 'Une erreur est survenue lors de la sauvegarde'
      });
    }
  };

  const handleEdit = (config) => {
    setEditingConfig(config);
    setConfigForm({
      level_id: config.level_id.toString(),
      evaluation_mode: config.evaluation_mode,
      ds1_percentage: config.ds1_percentage,
      ds2_percentage: config.ds2_percentage || 0,
      composition_percentage: config.composition_percentage,
      description: config.description || '',
      is_active: config.is_active
    });
    setShowConfigModal(true);
  };

  const handleToggleStatus = async (config) => {
    try {
      const response = await secureApiEndpoints.evaluationConfigs.toggleStatus(config.id);
      if (response.success) {
        loadConfigurations();
        Swal.fire({
          icon: 'success',
          title: 'Statut mis à jour',
          text: response.message
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: response.message || 'Erreur lors de la mise à jour'
        });
      }
    } catch (error) {
      console.error('Erreur lors du toggle:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erreur technique',
        text: 'Erreur lors de la mise à jour du statut'
      });
    }
  };

  const resetConfigForm = () => {
    setConfigForm({
      level_id: '',
      evaluation_mode: '1ds_1comp',
      ds1_percentage: 50,
      ds2_percentage: 0,
      composition_percentage: 50,
      description: '',
      is_active: true
    });
    setEditingConfig(null);
  };

  const getFilteredConfigurations = () => {
    return configurations.filter(config => {
      const matchesMode = filterMode === 'all' || config.evaluation_mode === filterMode;
      const matchesLevel = filterLevel === 'all' || config.level_id.toString() === filterLevel;
      return matchesMode && matchesLevel;
    });
  };

  const getCurrentModeConfig = () => {
    return evaluationModes.find(mode => mode.value === configForm.evaluation_mode);
  };

  const getTotalPercentage = () => {
    return configForm.ds1_percentage + configForm.ds2_percentage + configForm.composition_percentage;
  };

  const getValidationVariant = () => {
    const total = getTotalPercentage();
    if (total === 100) return 'success';
    if (total > 100) return 'danger';
    return 'warning';
  };

  if (loading) {
    return <div>Chargement...</div>;
  }

  return (
    <div className="container-fluid mt-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>Configuration des Évaluations par Cycle</h2>
        <Button 
          variant="primary"
          onClick={() => {
            resetConfigForm();
            setShowConfigModal(true);
          }}
          disabled={!selectedYear}
        >
          <Plus className="me-2" />
          Nouvelle Configuration
        </Button>
      </div>

      {/* Sélection Année + Filtres */}
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
                <Form.Label>Filtrer par Mode</Form.Label>
                <Form.Select 
                  value={filterMode} 
                  onChange={(e) => setFilterMode(e.target.value)}
                >
                  <option value="all">Tous les modes</option>
                  <option value="1ds_1comp">1 DS + 1 Composition</option>
                  <option value="2ds_1comp">2 DS + 1 Composition</option>
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
                  {levels.map(level => (
                    <option key={level.id} value={level.id}>
                      {level.name} - {level.section?.name}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Configurations existantes */}
      {selectedYear && (
        <Card>
          <Card.Header>
            <h5 className="mb-0">Configurations des Évaluations</h5>
          </Card.Header>
          <Card.Body>
            {getFilteredConfigurations().length === 0 ? (
              <Alert variant="info">
                <div className="d-flex align-items-center">
                  <BookFill className="me-2" />
                  {configurations.length === 0 
                    ? "Aucune configuration trouvée. Créez une configuration pour définir les modes d'évaluation."
                    : "Aucune configuration correspondant aux filtres sélectionnés."
                  }
                </div>
              </Alert>
            ) : (
              <Row>
                {getFilteredConfigurations().map(config => (
                  <Col md={6} lg={4} key={config.id} className="mb-3">
                    <Card className="h-100 border">
                      <Card.Body>
                        <div className="d-flex justify-content-between align-items-start mb-3">
                          <div>
                            <h6 className="card-title">
                              {evaluationModes.find(m => m.value === config.evaluation_mode)?.label}
                            </h6>
                            <small className="text-muted">
                              {config.level?.name} - {config.level?.section?.name}
                            </small>
                          </div>
                          <span className={`badge ${config.is_active ? 'bg-success' : 'bg-secondary'}`}>
                            {config.is_active ? 'Actif' : 'Inactif'}
                          </span>
                        </div>
                        
                        <div className="mb-3">
                          <small className="text-muted d-block mb-2">Répartition des notes:</small>
                          <div className="d-flex justify-content-between mb-1">
                            <span>DS 1:</span>
                            <span className="badge bg-primary">{config.ds1_percentage}%</span>
                          </div>
                          {config.ds2_percentage > 0 && (
                            <div className="d-flex justify-content-between mb-1">
                              <span>DS 2:</span>
                              <span className="badge bg-primary">{config.ds2_percentage}%</span>
                            </div>
                          )}
                          <div className="d-flex justify-content-between mb-2">
                            <span>Composition:</span>
                            <span className="badge bg-success">{config.composition_percentage}%</span>
                          </div>
                          <ProgressBar 
                            variant="success"
                            now={100}
                            label="100%"
                            size="sm"
                          />
                        </div>

                        {config.description && (
                          <p className="text-muted small mb-3">{config.description}</p>
                        )}

                        <div className="d-flex gap-2">
                          <Button 
                            size="sm" 
                            variant="outline-primary"
                            onClick={() => handleEdit(config)}
                          >
                            Modifier
                          </Button>
                          <Button 
                            size="sm" 
                            variant={config.is_active ? "outline-danger" : "outline-success"}
                            onClick={() => handleToggleStatus(config)}
                          >
                            {config.is_active ? 'Désactiver' : 'Activer'}
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
      <Modal show={showConfigModal} onHide={() => {setShowConfigModal(false); resetConfigForm();}}>
        <Modal.Header closeButton>
          <Modal.Title>
            {editingConfig ? 'Modifier la Configuration' : 'Nouvelle Configuration d\'Évaluation'}
          </Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleConfigSubmit}>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label>Cycle (Niveau)</Form.Label>
              <Form.Select
                value={configForm.level_id}
                onChange={(e) => setConfigForm({...configForm, level_id: e.target.value})}
                required
              >
                <option value="">Sélectionner un cycle</option>
                {levels.map(level => (
                  <option key={level.id} value={level.id}>
                    {level.name} - {level.section?.name}
                  </option>
                ))}
              </Form.Select>
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Mode d'évaluation</Form.Label>
              <Form.Select
                value={configForm.evaluation_mode}
                onChange={(e) => handleModeChange(e.target.value)}
              >
                {evaluationModes.map(mode => (
                  <option key={mode.value} value={mode.value}>
                    {mode.label} - {mode.description}
                  </option>
                ))}
              </Form.Select>
            </Form.Group>

            <div className="mb-3">
              <Form.Label>Répartition des pourcentages</Form.Label>
              <div className="border rounded p-3 bg-light">
                <Row>
                  <Col md={configForm.evaluation_mode === '1ds_1comp' ? 6 : 4}>
                    <Form.Group>
                      <Form.Label>DS 1 (%)</Form.Label>
                      <Form.Control
                        type="number"
                        min="0"
                        max="100"
                        step="0.1"
                        value={configForm.ds1_percentage}
                        onChange={(e) => setConfigForm({
                          ...configForm, 
                          ds1_percentage: parseFloat(e.target.value) || 0
                        })}
                      />
                    </Form.Group>
                  </Col>
                  
                  {configForm.evaluation_mode === '2ds_1comp' && (
                    <Col md={4}>
                      <Form.Group>
                        <Form.Label>DS 2 (%)</Form.Label>
                        <Form.Control
                          type="number"
                          min="0"
                          max="100" 
                          step="0.1"
                          value={configForm.ds2_percentage}
                          onChange={(e) => setConfigForm({
                            ...configForm, 
                            ds2_percentage: parseFloat(e.target.value) || 0
                          })}
                        />
                      </Form.Group>
                    </Col>
                  )}
                  
                  <Col md={configForm.evaluation_mode === '1ds_1comp' ? 6 : 4}>
                    <Form.Group>
                      <Form.Label>Composition (%)</Form.Label>
                      <Form.Control
                        type="number"
                        min="0"
                        max="100"
                        step="0.1" 
                        value={configForm.composition_percentage}
                        onChange={(e) => setConfigForm({
                          ...configForm, 
                          composition_percentage: parseFloat(e.target.value) || 0
                        })}
                      />
                    </Form.Group>
                  </Col>
                </Row>
                
                <div className="mt-3">
                  <div className="d-flex justify-content-between align-items-center">
                    <span>Total:</span>
                    <span className={`badge ${getTotalPercentage() === 100 ? 'bg-success' : 'bg-danger'}`}>
                      {getTotalPercentage()}%
                    </span>
                  </div>
                  <ProgressBar 
                    variant={getValidationVariant()}
                    now={getTotalPercentage()}
                    className="mt-2"
                  />
                  {getTotalPercentage() !== 100 && (
                    <small className="text-danger">
                      Le total doit être exactement 100%
                    </small>
                  )}
                </div>
              </div>
            </div>

            <Form.Group className="mb-3">
              <Form.Label>Description (optionnel)</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                value={configForm.description}
                onChange={(e) => setConfigForm({...configForm, description: e.target.value})}
                placeholder="Description de cette configuration..."
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Check
                type="checkbox"
                label="Configuration active"
                checked={configForm.is_active}
                onChange={(e) => setConfigForm({...configForm, is_active: e.target.checked})}
              />
            </Form.Group>

            {configForm.level_id && selectedYear && (
              <Alert variant="info">
                <strong>Configuration pour:</strong><br/>
                Année: {schoolYears.find(y => y.id.toString() === selectedYear)?.name}<br/>
                Cycle: {levels.find(l => l.id.toString() === configForm.level_id)?.name}
              </Alert>
            )}
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => {setShowConfigModal(false); resetConfigForm();}}>
              Annuler
            </Button>
            <Button 
              variant="primary" 
              type="submit"
              disabled={getTotalPercentage() !== 100 || !configForm.level_id}
            >
              {editingConfig ? 'Mettre à jour' : 'Créer la Configuration'}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>

    </div>
  );
}

export default EvaluationConfiguration;