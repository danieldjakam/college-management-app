import React, { useState, useEffect } from 'react';
import { Container, Card, Row, Col, Table, Alert, Spinner, Badge, Button, Form, Modal } from 'react-bootstrap';
import { 
  ListTask, 
  CheckCircle, 
  Clock, 
  Award, 
  TrendingUp, 
  MessageSquare, 
  PlayFill, 
  CheckSquare,
  Eye,
  Calendar,
  User,
  Trophy
} from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';

function TaskDashboard() {
  const [myTasks, setMyTasks] = useState([]);
  const [dashboard, setDashboard] = useState({});
  const [leaderboard, setLeaderboard] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [selectedTask, setSelectedTask] = useState(null);
  const [showTaskModal, setShowTaskModal] = useState(false);
  const [actionLoading, setActionLoading] = useState(null);
  const [progress, setProgress] = useState('');

  useEffect(() => {
    loadDashboardData();
    loadMyTasks();
    loadLeaderboard();
  }, []);

  const loadDashboardData = async () => {
    try {
      const response = await secureApi.get('/tasks/dashboard');
      if (response.success) {
        setDashboard(response.data);
      }
    } catch (error) {
      console.error('Erreur lors du chargement du dashboard:', error);
    }
  };

  const loadMyTasks = async () => {
    setLoading(true);
    try {
      const response = await secureApi.get('/tasks/my-tasks');
      if (response.success) {
        setMyTasks(response.data);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des tâches:', error);
      setError('Erreur lors du chargement des tâches: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const loadLeaderboard = async () => {
    try {
      const response = await secureApi.get('/tasks/leaderboard');
      if (response.success) {
        setLeaderboard(response.data.slice(0, 5)); // Top 5
      }
    } catch (error) {
      console.error('Erreur lors du chargement du classement:', error);
    }
  };

  const handleTaskAction = async (taskId, action) => {
    setActionLoading(taskId + '_' + action);
    try {
      let endpoint = '';
      let payload = {};

      switch (action) {
        case 'start':
          endpoint = `/tasks/${taskId}/start`;
          break;
        case 'complete':
          endpoint = `/tasks/${taskId}/complete`;
          break;
        case 'update_progress':
          endpoint = `/tasks/${taskId}/update-progress`;
          payload = { progress: parseInt(progress) };
          break;
        default:
          return;
      }

      const response = await secureApi.post(endpoint, payload);
      
      if (response.success) {
        await loadMyTasks();
        await loadDashboardData();
        setShowTaskModal(false);
        setProgress('');
      } else {
        setError(response.message || 'Erreur lors de l\'action');
      }
    } catch (error) {
      console.error(`Erreur lors de l'action ${action}:`, error);
      setError(`Erreur lors de l'action: ${error.message}`);
    } finally {
      setActionLoading(null);
    }
  };

  const handleShowTask = async (task) => {
    setSelectedTask(task);
    setShowTaskModal(true);
    
    // Charger les détails complets de la tâche
    try {
      const response = await secureApi.get(`/tasks/${task.id}`);
      if (response.success) {
        setSelectedTask(response.data);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des détails:', error);
    }
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
    
    return (
      <Badge bg={config.bg}>
        {config.text}
      </Badge>
    );
  };

  const getPriorityBadge = (priority) => {
    const priorityConfig = {
      'high': { bg: 'danger', text: 'Haute' },
      'medium': { bg: 'warning', text: 'Moyenne' },
      'low': { bg: 'info', text: 'Basse' }
    };
    const config = priorityConfig[priority] || { bg: 'secondary', text: priority };
    
    return (
      <Badge bg={config.bg} className="me-1">
        {config.text}
      </Badge>
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    });
  };

  const getDifficultyStars = (level) => {
    const stars = '★'.repeat(level || 1) + '☆'.repeat(5 - (level || 1));
    return <span className="text-warning">{stars}</span>;
  };

  return (
    <Container fluid className="p-4">
      <Row className="mb-4">
        <Col>
          <h2 className="d-flex align-items-center gap-2 mb-2">
            <ListTask className="text-primary" />
            Mes Tâches
          </h2>
          <p className="text-muted mb-0">
            Gérez vos tâches assignées et suivez votre progression
          </p>
        </Col>
      </Row>

      {error && (
        <Alert variant="danger" className="mb-4">
          {error}
        </Alert>
      )}

      {/* Statistiques du dashboard */}
      <Row className="mb-4">
        <Col md={3}>
          <Card className="border-0 shadow-sm h-100">
            <Card.Body className="text-center">
              <ListTask size={24} className="text-primary mb-2" />
              <h3 className="text-primary mb-1">{dashboard.total_tasks || 0}</h3>
              <p className="text-muted mb-0 small">Tâches assignées</p>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="border-0 shadow-sm h-100">
            <Card.Body className="text-center">
              <Clock size={24} className="text-warning mb-2" />
              <h3 className="text-warning mb-1">{dashboard.in_progress_tasks || 0}</h3>
              <p className="text-muted mb-0 small">En cours</p>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="border-0 shadow-sm h-100">
            <Card.Body className="text-center">
              <CheckCircle size={24} className="text-success mb-2" />
              <h3 className="text-success mb-1">{dashboard.completed_tasks || 0}</h3>
              <p className="text-muted mb-0 small">Terminées</p>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="border-0 shadow-sm h-100">
            <Card.Body className="text-center">
              <Award size={24} className="text-info mb-2" />
              <h3 className="text-info mb-1">{dashboard.total_points || 0}</h3>
              <p className="text-muted mb-0 small">Points gagnés</p>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      <Row>
        <Col md={8}>
          {/* Liste des tâches */}
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0 d-flex align-items-center gap-2">
                <ListTask /> Mes Tâches Assignées
              </h5>
            </Card.Header>
            <Card.Body className="p-0">
              {loading ? (
                <div className="text-center py-4">
                  <Spinner animation="border" role="status">
                    <span className="visually-hidden">Chargement...</span>
                  </Spinner>
                </div>
              ) : myTasks.length > 0 ? (
                <div className="table-responsive">
                  <Table striped hover className="mb-0">
                    <thead className="bg-light">
                      <tr>
                        <th>Tâche</th>
                        <th>Priorité</th>
                        <th>Difficulté</th>
                        <th>Échéance</th>
                        <th>Statut</th>
                        <th>Points</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {myTasks.map((task) => (
                        <tr key={task.id}>
                          <td>
                            <div>
                              <strong className="d-block">{task.title}</strong>
                              <small className="text-muted">
                                {task.description?.substring(0, 50)}{task.description?.length > 50 ? '...' : ''}
                              </small>
                            </div>
                          </td>
                          <td>{getPriorityBadge(task.priority)}</td>
                          <td>{getDifficultyStars(task.difficulty_level)}</td>
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
                            <div className="d-flex gap-1">
                              <Button
                                size="sm"
                                variant="outline-primary"
                                onClick={() => handleShowTask(task)}
                              >
                                <Eye size={14} />
                              </Button>
                              
                              {task.status === 'pending' && (
                                <Button
                                  size="sm"
                                  variant="success"
                                  onClick={() => handleTaskAction(task.id, 'start')}
                                  disabled={actionLoading === task.id + '_start'}
                                >
                                  {actionLoading === task.id + '_start' ? (
                                    <Spinner size="sm" />
                                  ) : (
                                    <PlayFill size={14} />
                                  )}
                                </Button>
                              )}
                              
                              {task.status === 'in_progress' && (
                                <Button
                                  size="sm"
                                  variant="warning"
                                  onClick={() => handleTaskAction(task.id, 'complete')}
                                  disabled={actionLoading === task.id + '_complete'}
                                >
                                  {actionLoading === task.id + '_complete' ? (
                                    <Spinner size="sm" />
                                  ) : (
                                    <CheckSquare size={14} />
                                  )}
                                </Button>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </div>
              ) : (
                <div className="text-center py-5">
                  <ListTask size={48} className="text-muted mb-3" />
                  <h5 className="text-muted">Aucune tâche assignée</h5>
                  <p className="text-muted mb-0">Vous n'avez actuellement aucune tâche assignée.</p>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>

        <Col md={4}>
          {/* Classement */}
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0 d-flex align-items-center gap-2">
                <Trophy /> Top 5 Classement
              </h5>
            </Card.Header>
            <Card.Body>
              {leaderboard.length > 0 ? (
                <div className="d-flex flex-column gap-2">
                  {leaderboard.map((user, index) => (
                    <div key={user.id} className="d-flex align-items-center gap-3 p-2 rounded bg-light">
                      <Badge 
                        bg={index === 0 ? 'warning' : index === 1 ? 'secondary' : index === 2 ? 'info' : 'light'}
                        className="text-dark"
                      >
                        #{index + 1}
                      </Badge>
                      <div className="flex-grow-1">
                        <div className="fw-bold">{user.first_name} {user.last_name}</div>
                        <small className="text-muted">{user.total_points} points</small>
                      </div>
                      <div className="text-end">
                        <small className="text-muted">{user.completed_tasks} tâches</small>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-3">
                  <TrendingUp size={32} className="text-muted mb-2" />
                  <p className="text-muted mb-0">Aucune donnée disponible</p>
                </div>
              )}
            </Card.Body>
          </Card>

          {/* Performance personnelle */}
          {dashboard.completion_rate !== undefined && (
            <Card>
              <Card.Header>
                <h5 className="mb-0 d-flex align-items-center gap-2">
                  <TrendingUp /> Ma Performance
                </h5>
              </Card.Header>
              <Card.Body>
                <div className="text-center mb-3">
                  <h3 className="text-primary">{Math.round(dashboard.completion_rate)}%</h3>
                  <p className="text-muted mb-0">Taux de réussite</p>
                </div>
                
                <div className="d-flex justify-content-between align-items-center mb-2">
                  <small className="text-muted">Temps moyen par tâche</small>
                  <Badge bg="info">{dashboard.avg_completion_time || 'N/A'}</Badge>
                </div>
                
                <div className="d-flex justify-content-between align-items-center">
                  <small className="text-muted">Niveau actuel</small>
                  <div>{getDifficultyStars(dashboard.user_level || 1)}</div>
                </div>
              </Card.Body>
            </Card>
          )}
        </Col>
      </Row>

      {/* Modal de détail de tâche */}
      <Modal show={showTaskModal} onHide={() => setShowTaskModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Détails de la tâche</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedTask && (
            <div>
              <h5 className="mb-3">{selectedTask.title}</h5>
              
              <Row className="mb-3">
                <Col sm={6}>
                  <small className="text-muted d-block">Priorité</small>
                  {getPriorityBadge(selectedTask.priority)}
                </Col>
                <Col sm={6}>
                  <small className="text-muted d-block">Difficulté</small>
                  {getDifficultyStars(selectedTask.difficulty_level)}
                </Col>
              </Row>
              
              <Row className="mb-3">
                <Col sm={6}>
                  <small className="text-muted d-block">Échéance</small>
                  <div className="d-flex align-items-center gap-2">
                    <Calendar size={14} />
                    <span className={new Date(selectedTask.due_date) < new Date() ? 'text-danger' : ''}>
                      {formatDate(selectedTask.due_date)}
                    </span>
                  </div>
                </Col>
                <Col sm={6}>
                  <small className="text-muted d-block">Points</small>
                  <Badge bg="info">{selectedTask.points} points</Badge>
                </Col>
              </Row>
              
              <div className="mb-3">
                <small className="text-muted d-block">Description</small>
                <p>{selectedTask.description}</p>
              </div>
              
              {selectedTask.checklist && selectedTask.checklist.length > 0 && (
                <div className="mb-3">
                  <small className="text-muted d-block">Liste de contrôle</small>
                  <ul className="list-unstyled">
                    {selectedTask.checklist.map((item, index) => (
                      <li key={index} className="d-flex align-items-center gap-2">
                        <CheckSquare size={14} className="text-muted" />
                        {item}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {selectedTask.status === 'in_progress' && (
                <div className="mb-3">
                  <Form.Group>
                    <Form.Label>Mettre à jour le progrès (%)</Form.Label>
                    <Form.Control
                      type="number"
                      min="0"
                      max="100"
                      value={progress}
                      onChange={(e) => setProgress(e.target.value)}
                      placeholder="Entrez le pourcentage de progression"
                    />
                  </Form.Group>
                </div>
              )}
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowTaskModal(false)}>
            Fermer
          </Button>
          
          {selectedTask && selectedTask.status === 'pending' && (
            <Button 
              variant="success"
              onClick={() => handleTaskAction(selectedTask.id, 'start')}
              disabled={actionLoading === selectedTask.id + '_start'}
            >
              {actionLoading === selectedTask.id + '_start' ? (
                <>
                  <Spinner size="sm" className="me-1" />
                  Démarrage...
                </>
              ) : (
                <>
                  <PlayFill className="me-1" />
                  Commencer
                </>
              )}
            </Button>
          )}
          
          {selectedTask && selectedTask.status === 'in_progress' && (
            <>
              {progress && (
                <Button 
                  variant="warning"
                  onClick={() => handleTaskAction(selectedTask.id, 'update_progress')}
                  disabled={actionLoading === selectedTask.id + '_update_progress'}
                >
                  {actionLoading === selectedTask.id + '_update_progress' ? (
                    <>
                      <Spinner size="sm" className="me-1" />
                      Mise à jour...
                    </>
                  ) : (
                    'Mettre à jour'
                  )}
                </Button>
              )}
              
              <Button 
                variant="success"
                onClick={() => handleTaskAction(selectedTask.id, 'complete')}
                disabled={actionLoading === selectedTask.id + '_complete'}
              >
                {actionLoading === selectedTask.id + '_complete' ? (
                  <>
                    <Spinner size="sm" className="me-1" />
                    Finalisation...
                  </>
                ) : (
                  <>
                    <CheckSquare className="me-1" />
                    Terminer
                  </>
                )}
              </Button>
            </>
          )}
        </Modal.Footer>
      </Modal>
    </Container>
  );
}

export default TaskDashboard;