import React, { useState, useEffect } from 'react';
import {
  Container,
  Row,
  Col,
  Card,
  Button,
  Badge,
  Table,
  Alert,
  Spinner,
  Modal,
  Tab,
  Tabs
} from 'react-bootstrap';
import {
  ListCheck,
  CheckCircle,
  XCircle,
  Eye,
  Bell
} from 'react-bootstrap-icons';
import { apiEndpoints } from '../../utils/api';
import Swal from 'sweetalert2';

const UniformOrderManagement = () => {
  const [loading, setLoading] = useState(false);
  const [orders, setOrders] = useState([]);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [activeTab, setActiveTab] = useState('pending');

  useEffect(() => {
    fetchOrders();
  }, []);

  const fetchOrders = async () => {
    try {
      setLoading(true);
      const response = await apiEndpoints.getUniformOrders();
      if (response.success) {
        setOrders(response.data);
      }
    } catch (error) {
      console.error('Erreur chargement commandes:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'Impossible de charger les commandes'
      });
    } finally {
      setLoading(false);
    }
  };

  const filteredOrders = orders.filter((order) => order.status === activeTab);

  const handleMarkAsReady = async (orderId) => {
    const result = await Swal.fire({
      title: 'Marquer comme prête?',
      html: `
        <p>La commande sera marquée comme prête pour retrait.</p>
        <p><strong>L'élève sera notifié de venir retirer sa commande à la comptabilité.</strong></p>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Oui, marquer comme prête',
      cancelButtonText: 'Annuler'
    });

    if (result.isConfirmed) {
      try {
        setLoading(true);
        const response = await apiEndpoints.markUniformOrderReady(orderId);

        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Commande prête!',
            text: 'L\'élève a été notifié pour venir retirer sa commande',
            timer: 2000
          });
          fetchOrders();
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.message || 'Erreur lors de la mise à jour'
        });
      } finally {
        setLoading(false);
      }
    }
  };

  const handleCancelOrder = async (orderId, orderNumber) => {
    const result = await Swal.fire({
      title: 'Annuler la commande?',
      html: `
        <p>Êtes-vous sûr de vouloir annuler la commande <strong>${orderNumber}</strong>?</p>
        <p class="text-danger">Le stock réservé sera libéré.</p>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Oui, annuler',
      cancelButtonText: 'Non'
    });

    if (result.isConfirmed) {
      try {
        setLoading(true);
        const response = await apiEndpoints.cancelUniformOrder(orderId);

        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Commande annulée',
            text: 'Le stock a été libéré',
            timer: 2000
          });
          fetchOrders();
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.message || 'Erreur lors de l\'annulation'
        });
      } finally {
        setLoading(false);
      }
    }
  };

  const viewOrderDetails = async (orderId) => {
    try {
      setLoading(true);
      const response = await apiEndpoints.getUniformOrderDetails(orderId);
      if (response.success) {
        setSelectedOrder(response.data);
        setShowDetailsModal(true);
      }
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'Impossible de charger les détails'
      });
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      pending: { bg: 'warning', text: 'En attente' },
      ready: { bg: 'success', text: 'Prête pour retrait' },
      fulfilled: { bg: 'primary', text: 'Retirée' },
      cancelled: { bg: 'danger', text: 'Annulée' }
    };

    const config = statusConfig[status] || statusConfig.pending;
    return <Badge bg={config.bg}>{config.text}</Badge>;
  };

  const getPendingCount = () => orders.filter((o) => o.status === 'pending').length;
  const getReadyCount = () => orders.filter((o) => o.status === 'ready').length;
  const getFulfilledCount = () => orders.filter((o) => o.status === 'fulfilled').length;

  return (
    <Container className="mt-4">
      <Row className="mb-4">
        <Col>
          <h2>
            <ListCheck className="me-2" style={{ color: '#007bff' }} />
            Gestion des Commandes
          </h2>
          <p className="text-muted">
            Gérez les commandes de tenues scolaires et notifiez les élèves
          </p>
        </Col>
      </Row>

      {/* Summary Cards */}
      <Row className="mb-4">
        <Col md={4}>
          <Card className="shadow-sm border-warning">
            <Card.Body>
              <h5>En attente</h5>
              <h2 className="text-warning">{getPendingCount()}</h2>
              <small className="text-muted">Commandes à traiter</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4}>
          <Card className="shadow-sm border-success">
            <Card.Body>
              <h5>Prêtes pour retrait</h5>
              <h2 className="text-success">{getReadyCount()}</h2>
              <small className="text-muted">Élèves notifiés</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4}>
          <Card className="shadow-sm border-primary">
            <Card.Body>
              <h5>Retirées</h5>
              <h2 className="text-primary">{getFulfilledCount()}</h2>
              <small className="text-muted">Commandes complétées</small>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Orders Table */}
      <Card className="shadow-sm">
        <Card.Header>
          <h5 className="mb-0">Liste des Commandes</h5>
        </Card.Header>
        <Card.Body>
          <Tabs
            activeKey={activeTab}
            onSelect={(k) => setActiveTab(k)}
            className="mb-3"
          >
            <Tab
              eventKey="pending"
              title={
                <>
                  En attente <Badge bg="warning">{getPendingCount()}</Badge>
                </>
              }
            />
            <Tab
              eventKey="ready"
              title={
                <>
                  Prêtes <Badge bg="success">{getReadyCount()}</Badge>
                </>
              }
            />
            <Tab
              eventKey="fulfilled"
              title={
                <>
                  Retirées <Badge bg="primary">{getFulfilledCount()}</Badge>
                </>
              }
            />
            <Tab eventKey="cancelled" title="Annulées" />
          </Tabs>

          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" />
              <p className="mt-2">Chargement...</p>
            </div>
          ) : filteredOrders.length === 0 ? (
            <Alert variant="info">Aucune commande dans cette catégorie</Alert>
          ) : (
            <Table responsive hover>
              <thead>
                <tr>
                  <th>N° Commande</th>
                  <th>Date</th>
                  <th>Élève</th>
                  <th>Articles</th>
                  <th>Montant</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredOrders.map((order) => (
                  <tr key={order.id}>
                    <td>
                      <code>{order.order_number}</code>
                    </td>
                    <td>{new Date(order.placed_at).toLocaleDateString('fr-FR')}</td>
                    <td>
                      {order.student.first_name} {order.student.last_name}
                      <br />
                      <small className="text-muted">{order.student.matricule}</small>
                    </td>
                    <td>{order.items?.length || 0} article(s)</td>
                    <td className="fw-bold text-primary">
                      {new Intl.NumberFormat('fr-FR').format(order.total_amount)} FCFA
                    </td>
                    <td>{getStatusBadge(order.status)}</td>
                    <td>
                      <div className="d-flex gap-2">
                        <Button
                          variant="outline-info"
                          size="sm"
                          onClick={() => viewOrderDetails(order.id)}
                          title="Voir les détails"
                        >
                          <Eye />
                        </Button>

                        {order.status === 'pending' && (
                          <>
                            <Button
                              variant="outline-success"
                              size="sm"
                              onClick={() => handleMarkAsReady(order.id)}
                              title="Marquer comme prête"
                            >
                              <CheckCircle />
                            </Button>
                            <Button
                              variant="outline-danger"
                              size="sm"
                              onClick={() =>
                                handleCancelOrder(order.id, order.order_number)
                              }
                              title="Annuler la commande"
                            >
                              <XCircle />
                            </Button>
                          </>
                        )}

                        {order.status === 'ready' && order.student_notified && (
                          <Badge bg="info" className="ms-2">
                            <Bell className="me-1" />
                            Notifié
                          </Badge>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card.Body>
      </Card>

      {/* Order Details Modal */}
      <Modal
        show={showDetailsModal}
        onHide={() => setShowDetailsModal(false)}
        size="lg"
      >
        <Modal.Header closeButton>
          <Modal.Title>
            Détails de la Commande {selectedOrder?.order_number}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedOrder && (
            <>
              <Row className="mb-3">
                <Col md={6}>
                  <h6>Informations Élève</h6>
                  <p>
                    <strong>Nom:</strong> {selectedOrder.student.first_name}{' '}
                    {selectedOrder.student.last_name}
                    <br />
                    <strong>Matricule:</strong> {selectedOrder.student.matricule}
                    <br />
                    <strong>Classe:</strong> {selectedOrder.student.classe?.name || 'N/A'}
                  </p>
                </Col>
                <Col md={6}>
                  <h6>Statut Commande</h6>
                  <p>
                    <strong>Statut:</strong> {getStatusBadge(selectedOrder.status)}
                    <br />
                    <strong>Date commande:</strong>{' '}
                    {new Date(selectedOrder.placed_at).toLocaleString('fr-FR')}
                    <br />
                    {selectedOrder.ready_at && (
                      <>
                        <strong>Prête depuis:</strong>{' '}
                        {new Date(selectedOrder.ready_at).toLocaleString('fr-FR')}
                        <br />
                      </>
                    )}
                    {selectedOrder.fulfilled_at && (
                      <>
                        <strong>Retirée le:</strong>{' '}
                        {new Date(selectedOrder.fulfilled_at).toLocaleString('fr-FR')}
                      </>
                    )}
                  </p>
                </Col>
              </Row>

              <h6>Articles commandés</h6>
              <Table bordered>
                <thead>
                  <tr>
                    <th>Article</th>
                    <th>Taille</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Sous-total</th>
                  </tr>
                </thead>
                <tbody>
                  {selectedOrder.items.map((item) => (
                    <tr key={item.id}>
                      <td>{item.uniform_item.name}</td>
                      <td>
                        <Badge bg="secondary">{item.variant.size}</Badge>
                      </td>
                      <td>{item.quantity}</td>
                      <td>
                        {new Intl.NumberFormat('fr-FR').format(item.unit_price)} FCFA
                      </td>
                      <td className="fw-bold">
                        {new Intl.NumberFormat('fr-FR').format(item.subtotal)} FCFA
                      </td>
                    </tr>
                  ))}
                </tbody>
                <tfoot>
                  <tr>
                    <th colSpan="4" className="text-end">
                      TOTAL:
                    </th>
                    <th className="text-primary">
                      {new Intl.NumberFormat('fr-FR').format(
                        selectedOrder.total_amount
                      )}{' '}
                      FCFA
                    </th>
                  </tr>
                </tfoot>
              </Table>

              {selectedOrder.notes && (
                <>
                  <h6>Notes</h6>
                  <Alert variant="secondary">{selectedOrder.notes}</Alert>
                </>
              )}

              {selectedOrder.placed_by && (
                <p className="text-muted mt-3">
                  <small>
                    Commande passée par: {selectedOrder.placed_by.name} le{' '}
                    {new Date(selectedOrder.placed_at).toLocaleString('fr-FR')}
                  </small>
                </p>
              )}
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          {selectedOrder?.status === 'pending' && (
            <>
              <Button
                variant="success"
                onClick={() => {
                  setShowDetailsModal(false);
                  handleMarkAsReady(selectedOrder.id);
                }}
              >
                <CheckCircle className="me-2" />
                Marquer comme prête
              </Button>
              <Button
                variant="danger"
                onClick={() => {
                  setShowDetailsModal(false);
                  handleCancelOrder(selectedOrder.id, selectedOrder.order_number);
                }}
              >
                <XCircle className="me-2" />
                Annuler
              </Button>
            </>
          )}
          <Button variant="secondary" onClick={() => setShowDetailsModal(false)}>
            Fermer
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default UniformOrderManagement;
