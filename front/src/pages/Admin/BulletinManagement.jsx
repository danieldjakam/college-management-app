import React, { useState, useEffect } from 'react';
import { Card, Button, Table, Badge, Modal, Form, Row, Col, Alert, Spinner, ProgressBar } from 'react-bootstrap';
import { CardText, Download, Eye, Printer, Plus, PencilSquare, Trash, CheckCircle, Clock, ExclamationCircle, ArrowClockwise } from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';

function BulletinManagement() {
  const [activeTab, setActiveTab] = useState('view');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  
  // States pour les bulletins générés
  const [generatedBulletins, setGeneratedBulletins] = useState([]);
  const [classes, setClasses] = useState([]);
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedPeriodType, setSelectedPeriodType] = useState('');
  const [selectedPeriodId, setSelectedPeriodId] = useState('');
  
  // States pour les templates
  const [templates, setTemplates] = useState([]);
  const [showTemplateModal, setShowTemplateModal] = useState(false);
  const [editingTemplate, setEditingTemplate] = useState(null);
  const [templateForm, setTemplateForm] = useState({
    name: '',
    type: 'sequence',
    description: '',
    template_html: '',
    css_styles: ''
  });

  useEffect(() => {
    fetchGeneratedBulletins();
    fetchClasses();
    if (activeTab === 'templates') {
      fetchTemplates();
    }
  }, [activeTab]);

  const fetchGeneratedBulletins = async () => {
    try {
      setLoading(true);
      // Récupérer tous les bulletins générés automatiquement
      const response = await secureApi.get('/bulletins/generated-bulletins', {
        params: {
          class_id: selectedClass || undefined,
          period_type: selectedPeriodType || undefined,
          period_identifier: selectedPeriodId || undefined
        }
      });
      setGeneratedBulletins(response.data.data || response.data);
    } catch (error) {
      console.error('Erreur bulletins:', error);
      console.error('Response:', error.response);
      setError(`Erreur lors du chargement des bulletins: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  const fetchClasses = async () => {
    try {
      const response = await secureApi.get('/school-classes');
      setClasses(response.data.data || response.data);
    } catch (error) {
      console.error('Erreur classes:', error);
      console.error('Response:', error.response);
      setError(`Erreur lors du chargement des classes: ${error.message}`);
    }
  };

  const fetchTemplates = async () => {
    try {
      const response = await secureApi.get('/bulletins/templates');
      setTemplates(response.data);
    } catch (error) {
      console.error('Erreur templates:', error);
      console.error('Response:', error.response);
      setError(`Erreur lors du chargement des templates: ${error.message}`);
    }
  };

  const handleDownloadBulletin = async (bulletinId, studentName, periodType, periodId) => {
    try {
      setLoading(true);
      const response = await secureApi.get(`/bulletins/download/${bulletinId}`, {
        responseType: 'blob'
      });
      
      // Créer un nom de fichier descriptif
      const fileName = `bulletin_${periodType}_${periodId}_${studentName}_${new Date().toISOString().slice(0,10)}.pdf`;
      
      // Télécharger le fichier
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      
      setSuccess(`Bulletin téléchargé: ${fileName}`);
    } catch (error) {
      setError('Erreur lors du téléchargement');
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
        fetchGeneratedBulletins();
      } catch (error) {
        setError('Erreur lors de la régénération');
      } finally {
        setLoading(false);
      }
    }
  };

  const getCompletionBadge = (completionPercentage, isComplete) => {
    if (isComplete) {
      return <Badge bg="success"><CheckCircle className="me-1" />Complet</Badge>;
    } else if (completionPercentage >= 75) {
      return <Badge bg="warning"><Clock className="me-1" />Presque complet</Badge>;
    } else if (completionPercentage >= 50) {
      return <Badge bg="info"><Clock className="me-1" />En cours</Badge>;
    } else {
      return <Badge bg="secondary"><ExclamationCircle className="me-1" />Incomplet</Badge>;
    }
  };

  const getBulletinTypeLabel = (type) => {
    switch (type) {
      case 'sequence': return 'Séquence';
      case 'trimester': return 'Trimestre';
      case 'annual': return 'Annuel';
      case 'honor_roll': return 'Tableau d\'Honneur';
      default: return type;
    }
  };

  const getPeriodLabel = (identifier) => {
    if (identifier.startsWith('seq')) {
      return `Séquence ${identifier.replace('seq', '')}`;
    } else if (identifier.startsWith('trim')) {
      return `Trimestre ${identifier.replace('trim', '')}`;
    }
    return identifier;
  };

  // Template management functions (unchanged from previous version)
  const handleSaveTemplate = async () => {
    setLoading(true);
    try {
      if (editingTemplate) {
        await secureApi.put(`/bulletins/templates/${editingTemplate.id}`, templateForm);
        setSuccess('Template modifié avec succès !');
      } else {
        await secureApi.post('/bulletins/templates', templateForm);
        setSuccess('Template créé avec succès !');
      }
      
      setShowTemplateModal(false);
      setEditingTemplate(null);
      setTemplateForm({
        name: '',
        type: 'sequence',
        description: '',
        template_html: '',
        css_styles: ''
      });
      fetchTemplates();
    } catch (error) {
      setError(error.response?.data?.message || 'Erreur lors de la sauvegarde');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteTemplate = async (templateId) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer ce template ?')) {
      try {
        await secureApi.delete(`/bulletins/templates/${templateId}`);
        setSuccess('Template supprimé avec succès !');
        fetchTemplates();
      } catch (error) {
        setError('Erreur lors de la suppression');
      }
    }
  };

  const handleEditTemplate = (template) => {
    setEditingTemplate(template);
    setTemplateForm({
      name: template.name,
      type: template.type,
      description: template.description || '',
      template_html: template.template_html,
      css_styles: template.css_styles || ''
    });
    setShowTemplateModal(true);
  };

  return (
    <div className="container-fluid py-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2><CardText className="me-2" />Bulletins Scolaires - Administration</h2>
          <p className="text-muted mb-0">Visualisation et gestion des bulletins générés automatiquement</p>
        </div>
        <Button variant="outline-primary" onClick={fetchGeneratedBulletins} disabled={loading}>
          <ArrowClockwise className="me-1" />
          Actualiser
        </Button>
      </div>

      {error && <Alert variant="danger" dismissible onClose={() => setError('')}>{error}</Alert>}
      {success && <Alert variant="success" dismissible onClose={() => setSuccess('')}>{success}</Alert>}

      {/* Tabs */}
      <div className="mb-4">
        <Button
          variant={activeTab === 'view' ? 'primary' : 'outline-primary'}
          className="me-2"
          onClick={() => setActiveTab('view')}
        >
          <Eye className="me-1" />
          Bulletins Générés
        </Button>
        <Button
          variant={activeTab === 'templates' ? 'primary' : 'outline-primary'}
          onClick={() => setActiveTab('templates')}
        >
          <PencilSquare className="me-1" />
          Gestion Templates
        </Button>
      </div>

      {/* View Generated Bulletins Tab */}
      {activeTab === 'view' && (
        <>
          {/* Filters */}
          <Card className="mb-4">
            <Card.Body>
              <Row>
                <Col md={4}>
                  <Form.Group>
                    <Form.Label>Classe</Form.Label>
                    <Form.Select
                      value={selectedClass}
                      onChange={(e) => {
                        setSelectedClass(e.target.value);
                        fetchGeneratedBulletins();
                      }}
                    >
                      <option value="">Toutes les classes</option>
                      {classes.map(classe => (
                        <option key={classe.id} value={classe.id}>
                          {classe.name}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
                
                <Col md={4}>
                  <Form.Group>
                    <Form.Label>Type de Bulletin</Form.Label>
                    <Form.Select
                      value={selectedPeriodType}
                      onChange={(e) => {
                        setSelectedPeriodType(e.target.value);
                        setSelectedPeriodId('');
                        fetchGeneratedBulletins();
                      }}
                    >
                      <option value="">Tous types</option>
                      <option value="sequence">Séquences</option>
                      <option value="trimester">Trimestres</option>
                      <option value="annual">Annuel</option>
                      <option value="honor_roll">Tableau d'Honneur</option>
                    </Form.Select>
                  </Form.Group>
                </Col>

                <Col md={4}>
                  <Form.Group>
                    <Form.Label>Période Spécifique</Form.Label>
                    <Form.Select
                      value={selectedPeriodId}
                      onChange={(e) => {
                        setSelectedPeriodId(e.target.value);
                        fetchGeneratedBulletins();
                      }}
                      disabled={!selectedPeriodType}
                    >
                      <option value="">Toutes périodes</option>
                      {selectedPeriodType === 'sequence' && (
                        <>
                          <option value="seq1">Séquence 1</option>
                          <option value="seq3">Séquence 3</option>
                        </>
                      )}
                      {selectedPeriodType === 'trimester' && (
                        <>
                          <option value="trim1">Trimestre 1</option>
                          <option value="trim2">Trimestre 2</option>
                          <option value="trim3">Trimestre 3</option>
                        </>
                      )}
                    </Form.Select>
                  </Form.Group>
                </Col>
              </Row>
            </Card.Body>
          </Card>

          {/* Generated Bulletins Table */}
          <Card>
            <Card.Header>
              <h5>
                <Eye className="me-2" />
                Bulletins Générés Automatiquement
                <Badge bg="info" className="ms-2">{generatedBulletins.length}</Badge>
              </h5>
            </Card.Header>
            <Card.Body>
              {loading ? (
                <div className="text-center">
                  <Spinner animation="border" />
                  <p className="mt-2">Chargement des bulletins...</p>
                </div>
              ) : generatedBulletins.length === 0 ? (
                <Alert variant="info">
                  <p className="mb-0">
                    <Clock className="me-2" />
                    Aucun bulletin généré automatiquement pour les critères sélectionnés.
                  </p>
                  <small className="text-muted">
                    Les bulletins se génèrent automatiquement au fur et à mesure que les enseignants saisissent les notes.
                  </small>
                </Alert>
              ) : (
                <Table responsive striped hover>
                  <thead>
                    <tr>
                      <th>Étudiant</th>
                      <th>Classe</th>
                      <th>Type</th>
                      <th>Période</th>
                      <th>Completion</th>
                      <th>Statut</th>
                      <th>Dernière MAJ</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {generatedBulletins.map(bulletin => (
                      <tr key={bulletin.id}>
                        <td>
                          <strong>{bulletin.student?.first_name} {bulletin.student?.last_name}</strong>
                          <br />
                          <small className="text-muted">{bulletin.student?.matricule}</small>
                        </td>
                        <td>{bulletin.student?.school_class?.name}</td>
                        <td>
                          <Badge bg="primary">
                            {getBulletinTypeLabel(bulletin.period_type)}
                          </Badge>
                        </td>
                        <td>{getPeriodLabel(bulletin.period_identifier)}</td>
                        <td>
                          <ProgressBar 
                            now={bulletin.completion_percentage} 
                            label={`${bulletin.completion_percentage}%`}
                            variant={bulletin.is_complete ? 'success' : bulletin.completion_percentage >= 75 ? 'warning' : 'info'}
                            style={{ width: '100px' }}
                          />
                        </td>
                        <td>{getCompletionBadge(bulletin.completion_percentage, bulletin.is_complete)}</td>
                        <td>
                          {new Date(bulletin.generated_at).toLocaleDateString('fr-FR')}
                          <br />
                          <small className="text-muted">
                            {new Date(bulletin.generated_at).toLocaleTimeString('fr-FR')}
                          </small>
                        </td>
                        <td>
                          <Button
                            variant="outline-primary"
                            size="sm"
                            className="me-1"
                            onClick={() => handleDownloadBulletin(
                              bulletin.id, 
                              `${bulletin.student?.first_name}_${bulletin.student?.last_name}`,
                              bulletin.period_type,
                              bulletin.period_identifier
                            )}
                            disabled={!bulletin.file_path}
                          >
                            <Download className="me-1" />
                            PDF
                          </Button>
                          
                          <Button
                            variant="outline-secondary"
                            size="sm"
                            onClick={() => handleForceRegenerate(
                              bulletin.student_id,
                              bulletin.period_type,
                              bulletin.period_identifier
                            )}
                            title="Forcer la régénération"
                          >
                            <ArrowClockwise />
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              )}
            </Card.Body>
          </Card>
        </>
      )}

      {/* Templates Tab - Same as before but simplified */}
      {activeTab === 'templates' && (
        <Card>
          <Card.Header className="d-flex justify-content-between align-items-center">
            <h5><PencilSquare className="me-2" />Templates de Bulletins</h5>
            <Button variant="success" onClick={() => setShowTemplateModal(true)}>
              <Plus className="me-1" />
              Nouveau Template
            </Button>
          </Card.Header>
          <Card.Body>
            <Table responsive striped>
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Type</th>
                  <th>Description</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {templates.map(template => (
                  <tr key={template.id}>
                    <td>{template.name}</td>
                    <td>
                      <Badge bg="secondary">
                        {getBulletinTypeLabel(template.type)}
                      </Badge>
                    </td>
                    <td>{template.description}</td>
                    <td>
                      <Badge bg={template.is_active ? 'success' : 'danger'}>
                        {template.is_active ? 'Actif' : 'Inactif'}
                      </Badge>
                    </td>
                    <td>
                      <Button
                        variant="outline-primary"
                        size="sm"
                        className="me-1"
                        onClick={() => handleEditTemplate(template)}
                      >
                        <PencilSquare />
                      </Button>
                      <Button
                        variant="outline-danger"
                        size="sm"
                        onClick={() => handleDeleteTemplate(template.id)}
                      >
                        <Trash />
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          </Card.Body>
        </Card>
      )}

      {/* Template Modal - Same as before */}
      <Modal show={showTemplateModal} onHide={() => setShowTemplateModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            {editingTemplate ? 'Modifier Template' : 'Nouveau Template'}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form>
            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Nom</Form.Label>
                  <Form.Control
                    type="text"
                    value={templateForm.name}
                    onChange={(e) => setTemplateForm({ ...templateForm, name: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Type</Form.Label>
                  <Form.Select
                    value={templateForm.type}
                    onChange={(e) => setTemplateForm({ ...templateForm, type: e.target.value })}
                  >
                    <option value="sequence">Séquence</option>
                    <option value="trimester">Trimestre</option>
                    <option value="annual">Annuel</option>
                    <option value="honor_roll">Tableau d'Honneur</option>
                  </Form.Select>
                </Form.Group>
              </Col>
            </Row>
            
            <Form.Group className="mb-3">
              <Form.Label>Description</Form.Label>
              <Form.Control
                as="textarea"
                rows={2}
                value={templateForm.description}
                onChange={(e) => setTemplateForm({ ...templateForm, description: e.target.value })}
              />
            </Form.Group>
            
            <Form.Group className="mb-3">
              <Form.Label>HTML Template</Form.Label>
              <Form.Control
                as="textarea"
                rows={8}
                value={templateForm.template_html}
                onChange={(e) => setTemplateForm({ ...templateForm, template_html: e.target.value })}
                placeholder="Code HTML du template..."
              />
            </Form.Group>
            
            <Form.Group className="mb-3">
              <Form.Label>Styles CSS</Form.Label>
              <Form.Control
                as="textarea"
                rows={6}
                value={templateForm.css_styles}
                onChange={(e) => setTemplateForm({ ...templateForm, css_styles: e.target.value })}
                placeholder="Styles CSS du template..."
              />
            </Form.Group>
          </Form>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowTemplateModal(false)}>
            Annuler
          </Button>
          <Button variant="primary" onClick={handleSaveTemplate} disabled={loading}>
            {loading ? 'Sauvegarde...' : 'Sauvegarder'}
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

export default BulletinManagement;