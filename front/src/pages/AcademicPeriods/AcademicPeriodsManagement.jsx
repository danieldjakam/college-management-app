import React, { useState, useEffect, useCallback } from 'react';
import { Card, Button, Modal, Form, Row, Col, Alert, ProgressBar } from 'react-bootstrap';
import { Plus, Gear } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';

function AcademicPeriodsManagement() {
  const [periods, setPeriods] = useState([]);
  const [schoolYears, setSchoolYears] = useState([]);
  const [selectedYear, setSelectedYear] = useState('');
  const [config, setConfig] = useState(null);
  const [validation, setValidation] = useState(null);
  const [loading, setLoading] = useState(true);
  
  // Modals
  const [showConfigModal, setShowConfigModal] = useState(false);
  const [showPeriodModal, setShowPeriodModal] = useState(false);
  const [editingPeriod, setEditingPeriod] = useState(null);
  
  // Forms
  const [configForm, setConfigForm] = useState({
    type: 'trimester',
    periods_count: 3,
    description: ''
  });
  
  const [periodForm, setPeriodForm] = useState({
    name: '',
    percentage: '',
    order: '',
    school_year_id: '',
    description: ''
  });

  const loadInitialData = useCallback(async () => {
    try {
      console.log('Chargement des données initiales...');
      console.log('Token disponible:', !!secureApiEndpoints.request);
      
      const [configRes, yearsRes] = await Promise.all([
        secureApiEndpoints.request('/academic-periods/config'),
        secureApiEndpoints.request('/school-years/active')
      ]);

      console.log('Config response:', configRes);
      console.log('Years response:', yearsRes);
      console.log('School years data:', yearsRes?.data);

      if (configRes.success) {
        setConfig(configRes.data);
        if (configRes.data) {
          setConfigForm({
            type: configRes.data.type,
            periods_count: configRes.data.periods_count,
            description: configRes.data.description || ''
          });
        }
      }

      if (yearsRes.success) {
        setSchoolYears(yearsRes.data);
        const currentYear = yearsRes.data.find(year => year.is_current);
        if (currentYear) {
          setSelectedYear(currentYear.id.toString());
        }
      }
    } catch (error) {
      console.error('Erreur lors du chargement:', error);
      console.error('Type d\'erreur:', error.name);
      console.error('Message d\'erreur:', error.message);
    } finally {
      setLoading(false);
    }
  }, []);

  const loadPeriods = useCallback(async () => {
    if (!selectedYear) return;
    
    try {
      const response = await secureApiEndpoints.request(`/academic-periods?school_year_id=${selectedYear}`);
      if (response.success) {
        setPeriods(response.data);
        setValidation(response.validation);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des périodes:', error);
    }
  }, [selectedYear]);

  useEffect(() => {
    loadInitialData();
  }, [loadInitialData]);

  useEffect(() => {
    if (selectedYear) {
      loadPeriods();
    }
  }, [selectedYear, loadPeriods]);

  const handleConfigSubmit = async (e) => {
    e.preventDefault();
    console.log('Soumission de la configuration:', configForm);
    
    try {
      const response = await secureApiEndpoints.request('/academic-periods/config', {
        method: 'POST',
        body: JSON.stringify(configForm)
      });

      console.log('Réponse config:', response);

      if (response.success) {
        setConfig(response.data);
        setShowConfigModal(false);
        loadPeriods();
      } else {
        console.error('Erreur dans la réponse:', response);
      }
    } catch (error) {
      console.error('Erreur lors de la configuration:', error);
    }
  };

  const handlePeriodSubmit = async (e) => {
    e.preventDefault();
    
    const formData = {
      ...periodForm,
      school_year_id: selectedYear
    };

    try {
      const url = editingPeriod 
        ? `/academic-periods/${editingPeriod.id}`
        : '/academic-periods';
      
      const method = editingPeriod ? 'PUT' : 'POST';
      
      const response = await secureApiEndpoints.request(url, {
        method,
        body: JSON.stringify(formData)
      });

      if (response.success) {
        setValidation(response.validation);
        setShowPeriodModal(false);
        resetPeriodForm();
        loadPeriods();
      }
    } catch (error) {
      console.error('Erreur lors de la sauvegarde:', error);
    }
  };

  const handleEdit = (period) => {
    setEditingPeriod(period);
    setPeriodForm({
      name: period.name,
      percentage: period.percentage,
      order: period.order,
      school_year_id: period.school_year_id,
      description: period.description || ''
    });
    setShowPeriodModal(true);
  };

  const handleDelete = async (periodId) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cette période ?')) {
      try {
        const response = await secureApiEndpoints.request(`/academic-periods/${periodId}`, {
          method: 'DELETE'
        });

        if (response.success) {
          loadPeriods();
        }
      } catch (error) {
        console.error('Erreur lors de la suppression:', error);
      }
    }
  };

  const resetPeriodForm = () => {
    setPeriodForm({
      name: '',
      percentage: '',
      order: '',
      school_year_id: '',
      description: ''
    });
    setEditingPeriod(null);
  };

  const getValidationVariant = () => {
    if (!validation) return 'info';
    if (validation.is_valid) return 'success';
    if (validation.total_percentage > 100) return 'danger';
    return 'warning';
  };

  const getValidationMessage = () => {
    if (!validation) return 'Aucune période configurée';
    
    const { total_percentage, is_valid, difference } = validation;
    
    if (is_valid) {
      return `✅ Configuration complète (${total_percentage}%)`;
    }
    
    if (total_percentage > 100) {
      return `❌ Total dépasse 100% (${total_percentage}%) - Surplus: ${Math.abs(difference)}%`;
    }
    
    return `⚠️ Configuration incomplète (${total_percentage}%) - Manque: ${difference}%`;
  };

  if (loading) {
    return <div>Chargement...</div>;
  }

  return (
    <div className="container-fluid mt-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des Semestres/Trimestres</h2>
        <div>
          <Button 
            variant="outline-primary" 
            className="me-2"
            onClick={() => setShowConfigModal(true)}
          >
            <Gear className="me-2" />
            Configuration Système
          </Button>
          <Button 
            variant="primary"
            onClick={() => setShowPeriodModal(true)}
            disabled={!selectedYear}
          >
            <Plus className="me-2" />
            Nouvelle Période
          </Button>
        </div>
      </div>

      {/* Sélection année scolaire */}
      <Card className="mb-4">
        <Card.Body>
          <Row>
            <Col md={6}>
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
            <Col md={6}>
              {/* Section de validation des pourcentages masquée */}
              {/* {validation && (
                <div>
                  <Form.Label>Validation de l'année</Form.Label>
                  <Alert variant={getValidationVariant()} className="mb-0">
                    {getValidationMessage()}
                  </Alert>
                  <ProgressBar
                    variant={getValidationVariant()}
                    now={validation.total_percentage}
                    label={`${validation.total_percentage}%`}
                    className="mt-2"
                  />
                </div>
              )} */}
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Configuration actuelle */}
      {config && (
        <Card className="mb-4">
          <Card.Body>
            <h5>Configuration Actuelle</h5>
            <p>
              <strong>Type:</strong> {config.type === 'semester' ? 'Semestre' : 'Trimestre'} 
              <span className="ms-3">
                <strong>Nombre de périodes:</strong> {config.periods_count}
              </span>
            </p>
            {config.description && <p><strong>Description:</strong> {config.description}</p>}
          </Card.Body>
        </Card>
      )}

      {/* Liste des périodes */}
      {selectedYear && (
        <Card>
          <Card.Header>
            <h5 className="mb-0">Périodes configurées</h5>
          </Card.Header>
          <Card.Body>
            {periods.length === 0 ? (
              <Alert variant="info">
                Aucune période configurée pour cette année scolaire.
              </Alert>
            ) : (
              <div className="table-responsive">
                <table className="table table-striped">
                  <thead>
                    <tr>
                      {/* <th>Ordre</th> */}
                      <th>Nom</th>
                      {/* <th>Pourcentage</th> */}
                      <th>Statut</th>
                      <th>Description</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {periods.map(period => (
                      <tr key={period.id}>
                        {/* <td>{period.order}</td> */}
                        <td>{period.name}</td>
                        {/* <td>
                          <span className="badge bg-info">{period.percentage}%</span>
                        </td> */}
                        <td>
                          <span className={`badge ${period.is_active ? 'bg-success' : 'bg-secondary'}`}>
                            {period.is_active ? 'Actif' : 'Inactif'}
                          </span>
                        </td>
                        <td>{period.description || '-'}</td>
                        <td>
                          <Button 
                            size="sm" 
                            variant="outline-primary"
                            className="me-2"
                            onClick={() => handleEdit(period)}
                          >
                            Modifier
                          </Button>
                          <Button 
                            size="sm" 
                            variant="outline-danger"
                            onClick={() => handleDelete(period.id)}
                          >
                            Supprimer
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Card.Body>
        </Card>
      )}

      {/* Modal Configuration */}
      <Modal show={showConfigModal} onHide={() => setShowConfigModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>Configuration du Système Académique</Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleConfigSubmit}>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label>Type de système</Form.Label>
              <Form.Select
                value={configForm.type}
                onChange={(e) => setConfigForm({
                  ...configForm, 
                  type: e.target.value,
                  periods_count: e.target.value === 'semester' ? 2 : 3
                })}
              >
                <option value="semester">Système par Semestre (2 périodes)</option>
                <option value="trimester">Système par Trimestre (3 périodes)</option>
              </Form.Select>
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Nombre de périodes</Form.Label>
              <Form.Control
                type="number"
                min="2"
                max="6"
                value={configForm.periods_count}
                onChange={(e) => setConfigForm({...configForm, periods_count: parseInt(e.target.value)})}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Description (optionnel)</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                value={configForm.description}
                onChange={(e) => setConfigForm({...configForm, description: e.target.value})}
                placeholder="Description de la configuration..."
              />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowConfigModal(false)}>
              Annuler
            </Button>
            <Button variant="primary" type="submit">
              Sauvegarder Configuration
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* Modal Période */}
      <Modal show={showPeriodModal} onHide={() => {setShowPeriodModal(false); resetPeriodForm();}}>
        <Modal.Header closeButton>
          <Modal.Title>
            {editingPeriod ? 'Modifier la Période' : 'Nouvelle Période'}
          </Modal.Title>
        </Modal.Header>
        <Form onSubmit={handlePeriodSubmit}>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label>Nom de la période</Form.Label>
              <Form.Control
                type="text"
                value={periodForm.name}
                onChange={(e) => setPeriodForm({...periodForm, name: e.target.value})}
                placeholder="Ex: Semestre 1, Trimestre 1"
                required
              />
            </Form.Group>

            {/* Champs Pourcentage et Ordre masqués comme demandé */}
            {/* <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Pourcentage</Form.Label>
                  <Form.Control
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    value={periodForm.percentage}
                    onChange={(e) => setPeriodForm({...periodForm, percentage: e.target.value})}
                    placeholder="Ex: 33.33"
                    required
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Ordre</Form.Label>
                  <Form.Control
                    type="number"
                    min="1"
                    value={periodForm.order}
                    onChange={(e) => setPeriodForm({...periodForm, order: e.target.value})}
                    placeholder="1, 2, 3..."
                    required
                  />
                </Form.Group>
              </Col>
            </Row> */}

            <Form.Group className="mb-3">
              <Form.Label>Description (optionnel)</Form.Label>
              <Form.Control
                as="textarea"
                rows={2}
                value={periodForm.description}
                onChange={(e) => setPeriodForm({...periodForm, description: e.target.value})}
                placeholder="Description de la période..."
              />
            </Form.Group>

            {validation && (
              <Alert variant={getValidationVariant()}>
                <strong>Aperçu de validation:</strong> {getValidationMessage()}
              </Alert>
            )}
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => {setShowPeriodModal(false); resetPeriodForm();}}>
              Annuler
            </Button>
            <Button variant="primary" type="submit">
              {editingPeriod ? 'Mettre à jour' : 'Créer'}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}

export default AcademicPeriodsManagement;