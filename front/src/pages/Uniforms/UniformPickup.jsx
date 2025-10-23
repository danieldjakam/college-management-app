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
  Form
} from 'react-bootstrap';
import {
  BoxSeam,
  CreditCard,
  Download,
  Search
} from 'react-bootstrap-icons';
import { apiEndpoints } from '../../utils/api';
import { authService } from '../../services/authService';
import { host } from '../../utils/fetch';
import Swal from 'sweetalert2';

const UniformPickup = () => {
  const [loading, setLoading] = useState(false);
  const [readyOrders, setReadyOrders] = useState([]);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [paymentData, setPaymentData] = useState({
    payment_method: 'cash',
    payment_reference: '',
    notes: ''
  });

  useEffect(() => {
    fetchReadyOrders();
  }, []);

  const fetchReadyOrders = async () => {
    try {
      setLoading(true);
      const response = await apiEndpoints.getUniformOrders({ status: 'ready' });
      if (response.success) {
        setReadyOrders(response.data);
      }
    } catch (error) {
      console.error('Erreur chargement commandes:', error);
    } finally {
      setLoading(false);
    }
  };

  const filteredOrders = readyOrders.filter(
    (order) =>
      order.student.first_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      order.student.last_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      order.student.matricule.toLowerCase().includes(searchTerm.toLowerCase()) ||
      order.order_number.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleStartPickup = async (orderId) => {
    try {
      setLoading(true);
      const response = await apiEndpoints.getUniformOrderDetails(orderId);
      if (response.success) {
        setSelectedOrder(response.data);
        setPaymentData({
          payment_method: 'cash',
          payment_reference: '',
          notes: ''
        });
        setShowPaymentModal(true);
      }
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'Impossible de charger les détails de la commande'
      });
    } finally {
      setLoading(false);
    }
  };

  const handleProcessPickup = async () => {
    try {
      setLoading(true);

      const response = await apiEndpoints.processUniformPickup(selectedOrder.id, paymentData);

      if (response.success) {
        const { sale } = response.data;

        // Download receipt automatically
        await downloadReceipt(sale.id);

        Swal.fire({
          icon: 'success',
          title: 'Retrait traité!',
          html: `
            <p>Commande <strong>${selectedOrder.order_number}</strong> complétée</p>
            <p>Reçu: <strong>${sale.receipt_number}</strong></p>
            <p class="text-muted mt-2">Le reçu PDF est en cours de téléchargement...</p>
          `,
          timer: 3000
        });

        setShowPaymentModal(false);
        setSelectedOrder(null);
        fetchReadyOrders();
      }
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: error.message || 'Erreur lors du traitement'
      });
    } finally {
      setLoading(false);
    }
  };

  const downloadReceipt = async (saleId) => {
    try {
      const token = authService.getToken();
      const response = await fetch(
        `${host}/api/uniforms/sales/${saleId}/receipt`,
        {
          headers: {
            Authorization: `Bearer ${token}`
          }
        }
      );

      if (!response.ok) {
        throw new Error('Erreur téléchargement');
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `recu_uniforme_${saleId}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Erreur téléchargement PDF:', error);
      Swal.fire({
        icon: 'warning',
        title: 'Attention',
        text: 'La vente a été enregistrée mais le téléchargement du reçu a échoué'
      });
    }
  };

  const handlePaymentChange = (e) => {
    const { name, value } = e.target;
    setPaymentData((prev) => ({
      ...prev,
      [name]: value
    }));
  };

  return (
    <Container className="mt-4">
      <Row className="mb-4">
        <Col>
          <h2>
            <BoxSeam className="me-2" style={{ color: '#007bff' }} />
            Retrait et Paiement
          </h2>
          <p className="text-muted">
            Traiter les retraits et paiements des commandes prêtes
          </p>
        </Col>
      </Row>

      {/* Summary Card */}
      <Row className="mb-4">
        <Col md={6}>
          <Card className="shadow-sm border-success">
            <Card.Body>
              <h5>Commandes prêtes pour retrait</h5>
              <h2 className="text-success">{readyOrders.length}</h2>
              <small className="text-muted">
                Total:{' '}
                {new Intl.NumberFormat('fr-FR').format(
                  readyOrders.reduce((sum, order) => sum + parseFloat(order.total_amount), 0)
                )}{' '}
                FCFA
              </small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={6}>
          <Card className="shadow-sm">
            <Card.Body>
              <Form.Group>
                <Form.Label>
                  <Search className="me-2" />
                  Rechercher une commande
                </Form.Label>
                <Form.Control
                  type="text"
                  placeholder="Nom, matricule ou n° de commande..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </Form.Group>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Ready Orders Table */}
      <Card className="shadow-sm">
        <Card.Header>
          <h5 className="mb-0">Commandes Prêtes</h5>
        </Card.Header>
        <Card.Body>
          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" />
              <p className="mt-2">Chargement...</p>
            </div>
          ) : filteredOrders.length === 0 ? (
            <Alert variant="info">
              {searchTerm
                ? 'Aucune commande trouvée'
                : 'Aucune commande prête pour retrait'}
            </Alert>
          ) : (
            <Table responsive hover>
              <thead>
                <tr>
                  <th>N° Commande</th>
                  <th>Élève</th>
                  <th>Articles</th>
                  <th>Montant à payer</th>
                  <th>Prête depuis</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                {filteredOrders.map((order) => (
                  <tr key={order.id} className="table-success">
                    <td>
                      <code>{order.order_number}</code>
                    </td>
                    <td>
                      <strong>
                        {order.student.first_name} {order.student.last_name}
                      </strong>
                      <br />
                      <small className="text-muted">{order.student.matricule}</small>
                    </td>
                    <td>{order.items?.length || 0} article(s)</td>
                    <td className="fw-bold text-success fs-5">
                      {new Intl.NumberFormat('fr-FR').format(order.total_amount)} FCFA
                    </td>
                    <td>
                      {new Date(order.ready_at).toLocaleDateString('fr-FR')}
                      <br />
                      <small className="text-muted">
                        {new Date(order.ready_at).toLocaleTimeString('fr-FR')}
                      </small>
                    </td>
                    <td>
                      <Button
                        variant="success"
                        size="sm"
                        onClick={() => handleStartPickup(order.id)}
                      >
                        <CreditCard className="me-2" />
                        Traiter le retrait
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card.Body>
      </Card>

      {/* Payment Modal */}
      <Modal show={showPaymentModal} onHide={() => setShowPaymentModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            <CreditCard className="me-2" />
            Traiter le Retrait et Paiement
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedOrder && (
            <>
              {/* Order Summary */}
              <Alert variant="info">
                <Row>
                  <Col md={6}>
                    <strong>Élève:</strong> {selectedOrder.student.first_name}{' '}
                    {selectedOrder.student.last_name}
                    <br />
                    <strong>Matricule:</strong> {selectedOrder.student.matricule}
                  </Col>
                  <Col md={6}>
                    <strong>N° Commande:</strong> <code>{selectedOrder.order_number}</code>
                    <br />
                    <strong>Date commande:</strong>{' '}
                    {new Date(selectedOrder.placed_at).toLocaleDateString('fr-FR')}
                  </Col>
                </Row>
              </Alert>

              {/* Items */}
              <h6 className="mb-3">Articles à remettre</h6>
              <Table bordered size="sm">
                <thead>
                  <tr>
                    <th>Article</th>
                    <th>Taille</th>
                    <th>Qté</th>
                    <th>Prix unit.</th>
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
                  <tr className="table-success">
                    <th colSpan="4" className="text-end">
                      MONTANT À ENCAISSER:
                    </th>
                    <th className="text-success fs-5">
                      {new Intl.NumberFormat('fr-FR').format(
                        selectedOrder.total_amount
                      )}{' '}
                      FCFA
                    </th>
                  </tr>
                </tfoot>
              </Table>

              {/* Payment Form */}
              <hr />
              <h6 className="mb-3">Informations de Paiement</h6>

              <Form.Group className="mb-3">
                <Form.Label>Mode de Paiement *</Form.Label>
                <Form.Select
                  name="payment_method"
                  value={paymentData.payment_method}
                  onChange={handlePaymentChange}
                  required
                >
                  <option value="cash">💵 Espèces</option>
                  <option value="bank_transfer">🏦 Virement bancaire</option>
                  <option value="mobile_money">📱 Mobile Money</option>
                  <option value="check">📝 Chèque</option>
                  <option value="other">Autre</option>
                </Form.Select>
              </Form.Group>

              {paymentData.payment_method !== 'cash' && (
                <Form.Group className="mb-3">
                  <Form.Label>Référence de transaction</Form.Label>
                  <Form.Control
                    type="text"
                    name="payment_reference"
                    value={paymentData.payment_reference}
                    onChange={handlePaymentChange}
                    placeholder="N° de transaction, chèque, etc."
                  />
                </Form.Group>
              )}

              <Form.Group className="mb-3">
                <Form.Label>Notes (Optionnel)</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={2}
                  name="notes"
                  value={paymentData.notes}
                  onChange={handlePaymentChange}
                  placeholder="Remarques ou informations supplémentaires..."
                />
              </Form.Group>

              <Alert variant="warning">
                <strong>Important:</strong> Assurez-vous de bien vérifier tous les articles
                avant de remettre la commande à l'élève.
              </Alert>
            </>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowPaymentModal(false)}>
            Annuler
          </Button>
          <Button
            variant="success"
            size="lg"
            onClick={handleProcessPickup}
            disabled={loading}
          >
            {loading ? (
              <>
                <Spinner size="sm" className="me-2" />
                Traitement...
              </>
            ) : (
              <>
                <Download className="me-2" />
                Confirmer et Générer Reçu
              </>
            )}
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default UniformPickup;
