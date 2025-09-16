import React, { useState, useEffect } from 'react';
import { Card, Button, Modal, Form, Row, Col, Alert, Badge, Table } from 'react-bootstrap';
import { Eye, Pencil, Check, X, Search, Filter } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { useAuth } from '../../hooks/useAuth';
import Swal from 'sweetalert2';

function PaymentManagement() {
  const { user } = useAuth();
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [selectedPayment, setSelectedPayment] = useState(null);
  const [filters, setFilters] = useState({
    status: 'all',
    start_date: '',
    end_date: '',
    student_id: ''
  });

  // Form states
  const [editForm, setEditForm] = useState({});
  const [cancelForm, setCancelForm] = useState({
    cancellation_reason: ''
  });

  const [paymentTranches, setPaymentTranches] = useState([]);

  useEffect(() => {
    loadPayments();
    loadPaymentTranches();
  }, [filters]);

  const loadPayments = async () => {
    try {
      setLoading(true);
      const response = await secureApiEndpoints.payments.getForManagement(filters);
      if (response.success) {
        setPayments(response.data.data || []);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des paiements:', error);
      Swal.fire('Erreur', 'Erreur lors du chargement des paiements', 'error');
    } finally {
      setLoading(false);
    }
  };

  const loadPaymentTranches = async () => {
    try {
      const response = await secureApiEndpoints.payments.getTranches();
      if (response.success) {
        setPaymentTranches(response.data || []);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des tranches:', error);
    }
  };

  const handleEdit = (payment) => {
    setSelectedPayment(payment);
    setEditForm({
      total_amount: payment.total_amount,
      payment_date: payment.payment_date,
      payment_method: payment.payment_method,
      reference_number: payment.reference_number || '',
      notes: payment.notes || '',
      payment_details: payment.payment_details || []
    });
    setShowEditModal(true);
  };

  const handleSaveEdit = async () => {
    try {
      const response = await secureApiEndpoints.payments.update(selectedPayment.id, editForm);
      if (response.success) {
        setShowEditModal(false);
        loadPayments();
        Swal.fire('Succès', 'Paiement modifié avec succès', 'success');
      } else {
        Swal.fire('Erreur', response.message, 'error');
      }
    } catch (error) {
      console.error('Erreur lors de la modification:', error);
      Swal.fire('Erreur', 'Erreur lors de la modification du paiement', 'error');
    }
  };

  const handleValidate = async (payment) => {
    const result = await Swal.fire({
      title: 'Valider le paiement',
      text: `Confirmer la validation du paiement de ${payment.total_amount}€ pour ${payment.student?.full_name}?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Valider',
      cancelButtonText: 'Annuler'
    });

    if (result.isConfirmed) {
      try {
        const response = await secureApiEndpoints.payments.validate(payment.id);
        if (response.success) {
          loadPayments();
          Swal.fire('Validé!', 'Paiement validé avec succès', 'success');
        }
      } catch (error) {
        console.error('Erreur lors de la validation:', error);
        Swal.fire('Erreur', 'Erreur lors de la validation du paiement', 'error');
      }
    }
  };

  const handleShowCancel = (payment) => {
    setSelectedPayment(payment);
    setCancelForm({ cancellation_reason: '' });
    setShowCancelModal(true);
  };

  const handleCancel = async () => {
    if (!cancelForm.cancellation_reason.trim()) {
      Swal.fire('Erreur', 'Veuillez indiquer une raison d\'annulation', 'error');
      return;
    }

    try {
      const response = await secureApiEndpoints.payments.cancel(selectedPayment.id, cancelForm);
      if (response.success) {
        setShowCancelModal(false);
        loadPayments();
        Swal.fire('Annulé!', 'Paiement annulé avec succès', 'success');
      } else {
        Swal.fire('Erreur', response.message, 'error');
      }
    } catch (error) {
      console.error('Erreur lors de l\'annulation:', error);
      Swal.fire('Erreur', 'Erreur lors de l\'annulation du paiement', 'error');
    }
  };

  const getStatusBadge = (status) => {
    const variants = {
      pending: 'warning',
      validated: 'success',
      cancelled: 'danger'
    };
    const labels = {
      pending: 'En attente',
      validated: 'Validé',
      cancelled: 'Annulé'
    };
    return <Badge bg={variants[status]}>{labels[status]}</Badge>;
  };

  if (loading) {
    return <div>Chargement...</div>;
  }

  return (
    <div className="container-fluid mt-4">
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des Paiements</h2>
      </div>

      {/* Filtres */}
      <Card className="mb-4">
        <Card.Body>
          <Row>
            <Col md={3}>
              <Form.Group>
                <Form.Label>Statut</Form.Label>
                <Form.Select
                  value={filters.status}
                  onChange={(e) => setFilters({...filters, status: e.target.value})}
                >
                  <option value="all">Tous</option>
                  <option value="pending">En attente</option>
                  <option value="validated">Validés</option>
                  <option value="cancelled">Annulés</option>
                </Form.Select>
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group>
                <Form.Label>Date début</Form.Label>
                <Form.Control
                  type="date"
                  value={filters.start_date}
                  onChange={(e) => setFilters({...filters, start_date: e.target.value})}
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group>
                <Form.Label>Date fin</Form.Label>
                <Form.Control
                  type="date"
                  value={filters.end_date}
                  onChange={(e) => setFilters({...filters, end_date: e.target.value})}
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group>
                <Form.Label>&nbsp;</Form.Label>
                <div>
                  <Button variant="primary" onClick={loadPayments}>
                    <Search className="me-2" />
                    Filtrer
                  </Button>
                </div>
              </Form.Group>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Liste des paiements */}
      <Card>
        <Card.Header>
          <h5 className="mb-0">Liste des Paiements</h5>
        </Card.Header>
        <Card.Body>
          {payments.length === 0 ? (
            <Alert variant="info">Aucun paiement trouvé.</Alert>
          ) : (
            <Table responsive hover>
              <thead>
                <tr>
                  <th>Reçu N°</th>
                  <th>Étudiant</th>
                  <th>Montant</th>
                  <th>Date</th>
                  <th>Méthode</th>
                  <th>Statut</th>
                  <th>Créé par</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {payments.map(payment => (
                  <tr key={payment.id}>
                    <td>{payment.receipt_number}</td>
                    <td>{payment.student?.full_name}</td>
                    <td>{payment.total_amount}€</td>
                    <td>{new Date(payment.payment_date).toLocaleDateString('fr-FR')}</td>
                    <td>{payment.payment_method}</td>
                    <td>{getStatusBadge(payment.status)}</td>
                    <td>{payment.created_by_user?.username}</td>
                    <td>
                      <div className="d-flex gap-1">
                        {payment.status === 'pending' && (
                          <>
                            <Button
                              size="sm"
                              variant="outline-primary"
                              onClick={() => handleEdit(payment)}
                              title="Modifier"
                            >
                              <Pencil size={14} />
                            </Button>
                            <Button
                              size="sm"
                              variant="outline-success"
                              onClick={() => handleValidate(payment)}
                              title="Valider"
                            >
                              <Check size={14} />
                            </Button>
                          </>
                        )}
                        {(payment.status === 'pending' || payment.status === 'validated') && (
                          <Button
                            size="sm"
                            variant="outline-danger"
                            onClick={() => handleShowCancel(payment)}
                            title="Annuler"
                          >
                            <X size={14} />
                          </Button>
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

      {/* Modal d'édition */}
      <Modal show={showEditModal} onHide={() => setShowEditModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Modifier le Paiement</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Montant Total</Form.Label>
                <Form.Control
                  type="number"
                  step="0.01"
                  value={editForm.total_amount}
                  onChange={(e) => setEditForm({...editForm, total_amount: e.target.value})}
                />
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Date de Paiement</Form.Label>
                <Form.Control
                  type="date"
                  value={editForm.payment_date}
                  onChange={(e) => setEditForm({...editForm, payment_date: e.target.value})}
                />
              </Form.Group>
            </Col>
          </Row>
          <Row>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Méthode de Paiement</Form.Label>
                <Form.Select
                  value={editForm.payment_method}
                  onChange={(e) => setEditForm({...editForm, payment_method: e.target.value})}
                >
                  <option value="cash">Espèces</option>
                  <option value="card">Carte</option>
                  <option value="transfer">Virement</option>
                  <option value="cheque">Chèque</option>
                </Form.Select>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group className="mb-3">
                <Form.Label>Référence</Form.Label>
                <Form.Control
                  type="text"
                  value={editForm.reference_number}
                  onChange={(e) => setEditForm({...editForm, reference_number: e.target.value})}
                />
              </Form.Group>
            </Col>
          </Row>
          <Form.Group className="mb-3">
            <Form.Label>Notes</Form.Label>
            <Form.Control
              as="textarea"
              rows={3}
              value={editForm.notes}
              onChange={(e) => setEditForm({...editForm, notes: e.target.value})}
            />
          </Form.Group>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowEditModal(false)}>
            Annuler
          </Button>
          <Button variant="primary" onClick={handleSaveEdit}>
            Sauvegarder
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Modal d'annulation */}
      <Modal show={showCancelModal} onHide={() => setShowCancelModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>Annuler le Paiement</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Group>
            <Form.Label>Raison de l'annulation *</Form.Label>
            <Form.Control
              as="textarea"
              rows={4}
              value={cancelForm.cancellation_reason}
              onChange={(e) => setCancelForm({...cancelForm, cancellation_reason: e.target.value})}
              placeholder="Expliquez pourquoi ce paiement doit être annulé..."
              required
            />
          </Form.Group>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowCancelModal(false)}>
            Annuler
          </Button>
          <Button variant="danger" onClick={handleCancel}>
            Confirmer l'annulation
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

export default PaymentManagement;