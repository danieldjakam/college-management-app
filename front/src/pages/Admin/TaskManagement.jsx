import React, { useState, useEffect } from 'react';
import { 
  Container, Row, Col, Card, Form, Button, Alert, 
  Table, Badge, Modal, Spinner, Tab, Tabs, Dropdown
} from 'react-bootstrap';
import { 
  ListTask, Plus, Eye, Edit, Trash, UserPlus, 
  CheckCircle, XCircle, RotateCcw, Award, Calendar,
  Search, Filter, Download, TrendingUp, Clock
} from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';

const TaskManagement = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  
  // Données
  const [tasks, setTasks] = useState([]);
  const [templates, setTemplates] = useState([]);
  const [users, setUsers] = useState([]);
  const [statistics, setStatistics] = useState({});
  
  // Filtres
  const [filterStatus, setFilterStatus] = useState('');
  const [filterPriority, setFilterPriority] = useState('');
  const [filterAssignee, setFilterAssignee] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  
  // Modals
  const [showTaskModal, setShowTaskModal] = useState(false);
  const [showAssignModal, setShowAssignModal] = useState(false);
  const [showTemplateModal, setShowTemplateModal] = useState(false);
  const [selectedTask, setSelectedTask] = useState(null);
  const [selectedTemplate, setSelectedTemplate] = useState(null);
  
  // Formulaires
  const [taskForm, setTaskForm] = useState({
    title: '',
    description: '',
    category: 'general',
    priority: 'medium',
    difficulty_level: 1,
    points: 10,
    due_date: '',
    checklist: []
  });
  
  const [assignForm, setAssignForm] = useState({
    user_id: '',
    due_date: '',
    notes: ''
  });

  const [templateForm, setTemplateForm] = useState({
    name: '',
    title: '',
    description: '',
    category: 'general',
    priority: 'medium',
    difficulty_level: 1,
    points: 10,
    estimated_duration: 60,
    default_checklist: []
  });

  const [activeTab, setActiveTab] = useState('tasks');

  useEffect(() => {
    loadInitialData();
  }, []);

  useEffect(() => {
    if (activeTab === 'tasks') {
      loadTasks();
    } else if (activeTab === 'templates') {
      loadTemplates();
    }
  }, [activeTab, filterStatus, filterPriority, filterAssignee, searchTerm]);

  const loadInitialData = async () => {
    try {
      setLoading(true);
      const [statsRes, usersRes] = await Promise.all([
        secureApi.get('/tasks/statistics'),
        secureApi.get('/users/all') // Assuming this endpoint exists
      ]);
      
      if (statsRes.success) setStatistics(statsRes.data);
      if (usersRes.success) setUsers(usersRes.data);
      
    } catch (err) {
      setError('Erreur lors du chargement des données');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const loadTasks = async () => {
    try {
      const params = new URLSearchParams();
      if (filterStatus) params.append('status', filterStatus);
      if (filterPriority) params.append('priority', filterPriority);
      if (filterAssignee) params.append('assigned_to', filterAssignee);
      if (searchTerm) params.append('search', searchTerm);

      const response = await secureApi.get(`/tasks?${params.toString()}`);
      if (response.success) {
        setTasks(response.data);
      }
    } catch (err) {
      console.error('Erreur chargement tâches:', err);
    }
  };

  const loadTemplates = async () => {
    try {
      const response = await secureApi.get('/tasks/templates');
      if (response.success) {
        setTemplates(response.data);
      }
    } catch (err) {
      console.error('Erreur chargement templates:', err);
    }
  };

  const handleCreateTask = async (e) => {
    e.preventDefault();
    
    try {
      setLoading(true);
      const response = await secureApi.post('/tasks', taskForm);
      
      if (response.success) {
        setSuccess('Tâche créée avec succès');
        setShowTaskModal(false);
        resetTaskForm();
        loadTasks();
      } else {
        setError(response.message || 'Erreur lors de la création');
      }
    } catch (err) {
      setError('Erreur lors de la création de la tâche');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleAssignTask = async (e) => {
    e.preventDefault();
    
    try {
      setLoading(true);
      const response = await secureApi.post(`/tasks/${selectedTask.id}/assign`, assignForm);
      
      if (response.success) {
        setSuccess('Tâche assignée avec succès');
        setShowAssignModal(false);
        resetAssignForm();
        loadTasks();
      } else {
        setError(response.message || 'Erreur lors de l\'assignation');
      }
    } catch (err) {
      setError('Erreur lors de l\'assignation');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleTaskAction = async (taskId, action) => {
    try {
      setLoading(true);
      let response;
      
      switch (action) {
        case 'approve':
          response = await secureApi.post(`/tasks/${taskId}/approve`);
          break;
        case 'reject':
          response = await secureApi.post(`/tasks/${taskId}/reject`);
          break;
        case 'reopen':
          response = await secureApi.post(`/tasks/${taskId}/reopen`);
          break;
        case 'delete':
          response = await secureApi.delete(`/tasks/${taskId}`);
          break;
        default:
          return;
      }
      
      if (response.success) {
        setSuccess(`Action ${action} réalisée avec succès`);
        loadTasks();
      } else {
        setError(response.message || 'Erreur lors de l\'action');
      }
    } catch (err) {
      setError(`Erreur lors de l'action ${action}`);
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateTemplate = async (e) => {
    e.preventDefault();
    
    try {
      setLoading(true);
      const response = await secureApi.post('/tasks/templates', templateForm);
      
      if (response.success) {
        setSuccess('Template créé avec succès');
        setShowTemplateModal(false);
        resetTemplateForm();
        loadTemplates();
      } else {
        setError(response.message || 'Erreur lors de la création du template');
      }
    } catch (err) {
      setError('Erreur lors de la création du template');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateFromTemplate = async (templateId) => {
    try {
      setLoading(true);
      const response = await secureApi.post(`/tasks/templates/${templateId}/create-task`, {
        assigned_to: null, // Will be assigned separately
        due_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0] // 7 days from now
      });
      
      if (response.success) {
        setSuccess('Tâche créée à partir du template avec succès');
        loadTasks();
      } else {
        setError(response.message || 'Erreur lors de la création');
      }
    } catch (err) {
      setError('Erreur lors de la création de la tâche');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const resetTaskForm = () => {
    setTaskForm({
      title: '',
      description: '',
      category: 'general',
      priority: 'medium',
      difficulty_level: 1,
      points: 10,
      due_date: '',
      checklist: []
    });
  };

  const resetAssignForm = () => {
    setAssignForm({
      user_id: '',
      due_date: '',
      notes: ''
    });
  };

  const resetTemplateForm = () => {
    setTemplateForm({
      name: '',
      title: '',
      description: '',
      category: 'general',
      priority: 'medium',
      difficulty_level: 1,
      points: 10,
      estimated_duration: 60,
      default_checklist: []
    });
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      'pending': { bg: 'secondary', text: 'En attente' },
      'in_progress': { bg: 'primary', text: 'En cours' },
      'completed': { bg: 'warning', text: 'Terminé' },
      'approved': { bg: 'success', text: 'Approuvé' },
      'rejected': { bg: 'danger', text: 'Rejeté' }
    };
    const config = statusConfig[status] || { bg: 'secondary', text: status };
    
    return <Badge bg={config.bg}>{config.text}</Badge>;
  };

  const getPriorityBadge = (priority) => {
    const priorityConfig = {
      'high': { bg: 'danger', text: 'Haute' },
      'medium': { bg: 'warning', text: 'Moyenne' },
      'low': { bg: 'info', text: 'Basse' }
    };
    const config = priorityConfig[priority] || { bg: 'secondary', text: priority };
    
    return <Badge bg={config.bg}>{config.text}</Badge>;
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR');
  };

  const getDifficultyStars = (level) => {
    return '★'.repeat(level || 1) + '☆'.repeat(5 - (level || 1));
  };

  return (
    <Container fluid className="p-4">
      <Row className="mb-4">
        <Col>
          <h2 className="d-flex align-items-center gap-2 mb-2">
            <ListTask className="text-primary" />
            Gestion des Tâches
          </h2>
          <p className="text-muted mb-0">
            Créez, assignez et gérez les tâches du personnel
          </p>
        </Col>
        <Col xs="auto">
          <div className="d-flex gap-2">
            <Button 
              variant="primary" 
              className="d-flex align-items-center gap-2"
              onClick={() => setShowTaskModal(true)}
            >
              <Plus /> Nouvelle Tâche
            </Button>
            <Button 
              variant="success" 
              className="d-flex align-items-center gap-2"
              onClick={() => {
                // Ouvrir le modal d'assignation sans tâche sélectionnée pour assignation en masse
                setSelectedTask(null);
                setShowAssignModal(true);
              }}
            >
              <UserPlus /> Assigner Tâches
            </Button>
          </div>
        </Col>
      </Row>

      {error && (
        <Alert variant="danger" dismissible onClose={() => setError(null)}>
          {error}
        </Alert>
      )}

      {success && (
        <Alert variant="success" dismissible onClose={() => setSuccess(null)}>
          {success}
        </Alert>
      )}

      {/* Statistiques */}
      <Row className="mb-4">
        <Col md={3}>
          <Card className="border-0 shadow-sm h-100">
            <Card.Body className="text-center">
              <ListTask size={24} className="text-primary mb-2" />
              <h3 className="text-primary mb-1">{statistics.total_tasks || 0}</h3>
              <p className="text-muted mb-0 small">Total tâches</p>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="border-0 shadow-sm h-100">
            <Card.Body className="text-center">
              <Clock size={24} className="text-warning mb-2" />
              <h3 className="text-warning mb-1">{statistics.pending_tasks || 0}</h3>
              <p className="text-muted mb-0 small">En attente</p>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="border-0 shadow-sm h-100">
            <Card.Body className="text-center">
              <CheckCircle size={24} className="text-success mb-2" />
              <h3 className="text-success mb-1">{statistics.completed_tasks || 0}</h3>
              <p className="text-muted mb-0 small">Terminées</p>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="border-0 shadow-sm h-100">
            <Card.Body className="text-center">
              <TrendingUp size={24} className="text-info mb-2" />
              <h3 className="text-info mb-1">{Math.round(statistics.completion_rate || 0)}%</h3>
              <p className="text-muted mb-0 small">Taux de réussite</p>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Tabs */}
      <Card>
        <Card.Header>
          <Tabs 
            activeKey={activeTab} 
            onSelect={(k) => setActiveTab(k)}
            className="border-0"
          >
            <Tab eventKey="tasks" title="Tâches">
              <div className="mt-3">
                {/* Filtres */}
                <Row className="mb-3">
                  <Col md={3}>
                    <Form.Group>
                      <Form.Control
                        type="text"
                        placeholder="Rechercher..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                      />
                    </Form.Group>
                  </Col>
                  <Col md={2}>
                    <Form.Select
                      value={filterStatus}
                      onChange={(e) => setFilterStatus(e.target.value)}
                    >
                      <option value="">Tous les statuts</option>
                      <option value="pending">En attente</option>
                      <option value="in_progress">En cours</option>
                      <option value="completed">Terminé</option>
                      <option value="approved">Approuvé</option>
                      <option value="rejected">Rejeté</option>
                    </Form.Select>
                  </Col>
                  <Col md={2}>
                    <Form.Select
                      value={filterPriority}
                      onChange={(e) => setFilterPriority(e.target.value)}
                    >
                      <option value="">Toutes priorités</option>
                      <option value="high">Haute</option>
                      <option value="medium">Moyenne</option>
                      <option value="low">Basse</option>
                    </Form.Select>
                  </Col>
                  <Col md={3}>
                    <Form.Select
                      value={filterAssignee}
                      onChange={(e) => setFilterAssignee(e.target.value)}
                    >
                      <option value="">Tous les assignés</option>
                      {users.map(user => (
                        <option key={user.id} value={user.id}>
                          {user.first_name} {user.last_name}
                        </option>
                      ))}
                    </Form.Select>
                  </Col>
                  <Col md={2}>
                    <Button variant="outline-secondary" onClick={loadTasks}>
                      <Search /> Rechercher
                    </Button>
                  </Col>
                </Row>

                {/* Tableau des tâches */}
                {loading ? (
                  <div className="text-center py-4">
                    <Spinner animation="border" />
                  </div>
                ) : (
                  <div className="table-responsive">
                    <Table striped hover>
                      <thead className="bg-light">
                        <tr>
                          <th>Tâche</th>
                          <th>Assigné à</th>
                          <th>Priorité</th>
                          <th>Difficulté</th>
                          <th>Échéance</th>
                          <th>Statut</th>
                          <th>Points</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {tasks.map((task) => (
                          <tr key={task.id}>
                            <td>
                              <div>
                                <strong>{task.title}</strong>
                                <br />
                                <small className="text-muted">
                                  {task.description?.substring(0, 50)}...
                                </small>
                              </div>
                            </td>
                            <td>
                              {task.assigned_user ? (
                                <div>
                                  <span className="d-block">{task.assigned_user.first_name} {task.assigned_user.last_name}</span>
                                  <Button 
                                    size="sm" 
                                    variant="outline-warning"
                                    className="mt-1"
                                    onClick={() => {
                                      setSelectedTask(task);
                                      setShowAssignModal(true);
                                    }}
                                  >
                                    <UserPlus size={12} className="me-1" />
                                    Réassigner
                                  </Button>
                                </div>
                              ) : (
                                <div>
                                  <Badge bg="secondary" className="d-block mb-2">Non assigné</Badge>
                                  <Button 
                                    size="sm" 
                                    variant="success"
                                    onClick={() => {
                                      setSelectedTask(task);
                                      setShowAssignModal(true);
                                    }}
                                  >
                                    <UserPlus size={12} className="me-1" />
                                    Assigner maintenant
                                  </Button>
                                </div>
                              )}
                            </td>
                            <td>{getPriorityBadge(task.priority)}</td>
                            <td>
                              <span className="text-warning">
                                {getDifficultyStars(task.difficulty_level)}
                              </span>
                            </td>
                            <td>
                              <small className={new Date(task.due_date) < new Date() ? 'text-danger' : 'text-muted'}>
                                {formatDate(task.due_date)}
                              </small>
                            </td>
                            <td>{getStatusBadge(task.status)}</td>
                            <td>
                              <Badge bg="info">{task.points} pts</Badge>
                            </td>
                            <td>
                              <Dropdown>
                                <Dropdown.Toggle variant="outline-primary" size="sm">
                                  Actions
                                </Dropdown.Toggle>
                                <Dropdown.Menu>
                                  <Dropdown.Item onClick={() => {
                                    setSelectedTask(task);
                                    setShowAssignModal(true);
                                  }}>
                                    <UserPlus className="me-2" />
                                    {task.assigned_user ? 'Réassigner' : 'Assigner'}
                                  </Dropdown.Item>
                                  
                                  {task.status === 'completed' && (
                                    <>
                                      <Dropdown.Item onClick={() => handleTaskAction(task.id, 'approve')}>
                                        <CheckCircle className="me-2" />
                                        Approuver
                                      </Dropdown.Item>
                                      <Dropdown.Item onClick={() => handleTaskAction(task.id, 'reject')}>
                                        <XCircle className="me-2" />
                                        Rejeter
                                      </Dropdown.Item>
                                    </>
                                  )}
                                  
                                  {task.status === 'rejected' && (
                                    <Dropdown.Item onClick={() => handleTaskAction(task.id, 'reopen')}>
                                      <RotateCcw className="me-2" />
                                      Rouvrir
                                    </Dropdown.Item>
                                  )}
                                  
                                  <Dropdown.Divider />
                                  <Dropdown.Item 
                                    onClick={() => handleTaskAction(task.id, 'delete')}
                                    className="text-danger"
                                  >
                                    <Trash className="me-2" />
                                    Supprimer
                                  </Dropdown.Item>
                                </Dropdown.Menu>
                              </Dropdown>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  </div>
                )}
              </div>
            </Tab>

            <Tab eventKey="templates" title="Templates">
              <div className="mt-3">
                <div className="d-flex justify-content-between align-items-center mb-3">
                  <h5>Templates de Tâches</h5>
                  <Button 
                    variant="success" 
                    onClick={() => setShowTemplateModal(true)}
                  >
                    <Plus className="me-2" />
                    Nouveau Template
                  </Button>
                </div>

                <Row>
                  {templates.map((template) => (
                    <Col md={4} key={template.id} className="mb-3">
                      <Card className="h-100">
                        <Card.Body>
                          <h6>{template.name}</h6>
                          <p className="text-muted small mb-2">{template.description}</p>
                          <div className="d-flex justify-content-between align-items-center mb-2">
                            {getPriorityBadge(template.priority)}
                            <Badge bg="info">{template.points} pts</Badge>
                          </div>
                          <div className="d-flex justify-content-between">
                            <small className="text-warning">
                              {getDifficultyStars(template.difficulty_level)}
                            </small>
                            <Button 
                              size="sm" 
                              variant="outline-primary"
                              onClick={() => handleCreateFromTemplate(template.id)}
                            >
                              Utiliser
                            </Button>
                          </div>
                        </Card.Body>
                      </Card>
                    </Col>
                  ))}
                </Row>
              </div>
            </Tab>
          </Tabs>
        </Card.Header>
      </Card>

      {/* Modal Création Tâche */}
      <Modal show={showTaskModal} onHide={() => setShowTaskModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Nouvelle Tâche</Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleCreateTask}>
          <Modal.Body>
            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Titre *</Form.Label>
                  <Form.Control
                    type="text"
                    required
                    value={taskForm.title}
                    onChange={(e) => setTaskForm({...taskForm, title: e.target.value})}
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Catégorie</Form.Label>
                  <Form.Select
                    value={taskForm.category}
                    onChange={(e) => setTaskForm({...taskForm, category: e.target.value})}
                  >
                    <option value="general">Général</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="administration">Administration</option>
                    <option value="pedagogy">Pédagogie</option>
                    <option value="finance">Finance</option>
                  </Form.Select>
                </Form.Group>
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Description *</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                required
                value={taskForm.description}
                onChange={(e) => setTaskForm({...taskForm, description: e.target.value})}
              />
            </Form.Group>

            <Row>
              <Col md={4}>
                <Form.Group className="mb-3">
                  <Form.Label>Priorité</Form.Label>
                  <Form.Select
                    value={taskForm.priority}
                    onChange={(e) => setTaskForm({...taskForm, priority: e.target.value})}
                  >
                    <option value="low">Basse</option>
                    <option value="medium">Moyenne</option>
                    <option value="high">Haute</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group className="mb-3">
                  <Form.Label>Difficulté (1-5)</Form.Label>
                  <Form.Control
                    type="number"
                    min="1"
                    max="5"
                    value={taskForm.difficulty_level}
                    onChange={(e) => setTaskForm({...taskForm, difficulty_level: parseInt(e.target.value)})}
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group className="mb-3">
                  <Form.Label>Points</Form.Label>
                  <Form.Control
                    type="number"
                    min="1"
                    value={taskForm.points}
                    onChange={(e) => setTaskForm({...taskForm, points: parseInt(e.target.value)})}
                  />
                </Form.Group>
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Date d'échéance</Form.Label>
              <Form.Control
                type="date"
                value={taskForm.due_date}
                onChange={(e) => setTaskForm({...taskForm, due_date: e.target.value})}
              />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowTaskModal(false)}>
              Annuler
            </Button>
            <Button type="submit" variant="primary" disabled={loading}>
              {loading ? <Spinner size="sm" className="me-2" /> : null}
              Créer la Tâche
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* Modal Assignation */}
      <Modal show={showAssignModal} onHide={() => setShowAssignModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>
            {selectedTask ? 'Assigner une Tâche' : 'Assignation Rapide'}
          </Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleAssignTask}>
          <Modal.Body>
            {selectedTask ? (
              <div className="mb-3">
                <h6>Tâche: {selectedTask.title}</h6>
                <p className="text-muted">{selectedTask.description}</p>
              </div>
            ) : (
              <div className="mb-3">
                <Alert variant="info">
                  <strong>Assignation Rapide</strong><br/>
                  Sélectionnez une tâche non assignée dans le tableau puis une personne pour l'attribution.
                </Alert>
                <Form.Group className="mb-3">
                  <Form.Label>Sélectionner une tâche *</Form.Label>
                  <Form.Select
                    required
                    onChange={(e) => {
                      const taskId = e.target.value;
                      const task = tasks.find(t => t.id.toString() === taskId);
                      setSelectedTask(task);
                    }}
                  >
                    <option value="">Choisir une tâche à assigner</option>
                    {tasks.filter(t => !t.assigned_user).map(task => (
                      <option key={task.id} value={task.id}>
                        {task.title} - {getPriorityBadge(task.priority).props.children}
                      </option>
                    ))}
                  </Form.Select>
                </Form.Group>
              </div>
            )}

            <Form.Group className="mb-3">
              <Form.Label>Assigner à *</Form.Label>
              <Form.Select
                required
                value={assignForm.user_id}
                onChange={(e) => setAssignForm({...assignForm, user_id: e.target.value})}
              >
                <option value="">Sélectionner une personne</option>
                {users.map(user => (
                  <option key={user.id} value={user.id}>
                    {user.first_name} {user.last_name} - {user.role}
                  </option>
                ))}
              </Form.Select>
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Date d'échéance</Form.Label>
              <Form.Control
                type="date"
                value={assignForm.due_date}
                onChange={(e) => setAssignForm({...assignForm, due_date: e.target.value})}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Notes</Form.Label>
              <Form.Control
                as="textarea"
                rows={2}
                value={assignForm.notes}
                onChange={(e) => setAssignForm({...assignForm, notes: e.target.value})}
                placeholder="Instructions supplémentaires..."
              />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowAssignModal(false)}>
              Annuler
            </Button>
            <Button type="submit" variant="primary" disabled={loading}>
              {loading ? <Spinner size="sm" className="me-2" /> : null}
              Assigner
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* Modal Template */}
      <Modal show={showTemplateModal} onHide={() => setShowTemplateModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Nouveau Template</Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleCreateTemplate}>
          <Modal.Body>
            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Nom du Template *</Form.Label>
                  <Form.Control
                    type="text"
                    required
                    value={templateForm.name}
                    onChange={(e) => setTemplateForm({...templateForm, name: e.target.value})}
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Titre de la Tâche *</Form.Label>
                  <Form.Control
                    type="text"
                    required
                    value={templateForm.title}
                    onChange={(e) => setTemplateForm({...templateForm, title: e.target.value})}
                  />
                </Form.Group>
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Description *</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                required
                value={templateForm.description}
                onChange={(e) => setTemplateForm({...templateForm, description: e.target.value})}
              />
            </Form.Group>

            <Row>
              <Col md={3}>
                <Form.Group className="mb-3">
                  <Form.Label>Catégorie</Form.Label>
                  <Form.Select
                    value={templateForm.category}
                    onChange={(e) => setTemplateForm({...templateForm, category: e.target.value})}
                  >
                    <option value="general">Général</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="administration">Administration</option>
                    <option value="pedagogy">Pédagogie</option>
                    <option value="finance">Finance</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group className="mb-3">
                  <Form.Label>Priorité</Form.Label>
                  <Form.Select
                    value={templateForm.priority}
                    onChange={(e) => setTemplateForm({...templateForm, priority: e.target.value})}
                  >
                    <option value="low">Basse</option>
                    <option value="medium">Moyenne</option>
                    <option value="high">Haute</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group className="mb-3">
                  <Form.Label>Difficulté (1-5)</Form.Label>
                  <Form.Control
                    type="number"
                    min="1"
                    max="5"
                    value={templateForm.difficulty_level}
                    onChange={(e) => setTemplateForm({...templateForm, difficulty_level: parseInt(e.target.value)})}
                  />
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group className="mb-3">
                  <Form.Label>Points</Form.Label>
                  <Form.Control
                    type="number"
                    min="1"
                    value={templateForm.points}
                    onChange={(e) => setTemplateForm({...templateForm, points: parseInt(e.target.value)})}
                  />
                </Form.Group>
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Durée Estimée (minutes)</Form.Label>
              <Form.Control
                type="number"
                min="15"
                value={templateForm.estimated_duration}
                onChange={(e) => setTemplateForm({...templateForm, estimated_duration: parseInt(e.target.value)})}
              />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowTemplateModal(false)}>
              Annuler
            </Button>
            <Button type="submit" variant="success" disabled={loading}>
              {loading ? <Spinner size="sm" className="me-2" /> : null}
              Créer le Template
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </Container>
  );
};

export default TaskManagement;