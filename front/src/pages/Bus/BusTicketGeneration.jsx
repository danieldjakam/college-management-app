import React, { useState, useEffect } from 'react';
import {
  Container,
  Row,
  Col,
  Card,
  Form,
  Button,
  Table,
  Badge,
  Alert,
  Spinner
} from 'react-bootstrap';
import {
  Ticket,
  PlusCircle,
  Trash,
  XCircle,
  Download
} from 'react-bootstrap-icons';
import { apiEndpoints } from '../../utils/api';
import { authService } from '../../services/authService';
import { host } from '../../utils/fetch';
import Swal from 'sweetalert2';

const BusTicketGeneration = () => {
  const [loading, setLoading] = useState(false);
  const [batches, setBatches] = useState([]);
  const [formData, setFormData] = useState({
    batch_date: new Date().toISOString().split('T')[0],
    ticket_type: 'aller',
    quantity: 30,
    price_per_ticket: 100,
    notes: ''
  });

  useEffect(() => {
    fetchBatches();
  }, []);

  const fetchBatches = async () => {
    try {
      setLoading(true);
      const response = await apiEndpoints.getBusTicketBatches();
      if (response.success) {
        setBatches(response.data);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des lots:', error);
    } finally {
      setLoading(false);
    }
  };

  const downloadBatchPdf = async (batchId, ticketType, batchDate) => {
    try {
      const token = authService.getToken();
      const response = await fetch(`${host}/api/bus/tickets/batches/${batchId}/download`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (!response.ok) {
        throw new Error('Erreur téléchargement');
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `lot_tickets_${ticketType}_${batchDate}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Erreur téléchargement PDF lot:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'Impossible de télécharger le PDF du lot'
      });
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleGenerateBatch = async (e) => {
    e.preventDefault();

    // Validation
    if (formData.quantity < 1 || formData.quantity > 100) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'La quantité doit être entre 1 et 100'
      });
      return;
    }

    if (formData.price_per_ticket < 0) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'Le prix doit être positif'
      });
      return;
    }

    try {
      setLoading(true);

      const response = await apiEndpoints.generateBusTicketBatch(formData);

      if (response.success) {
        const batchId = response.data.id;

        // Télécharger automatiquement le PDF du lot avec authentification
        await downloadBatchPdf(batchId, formData.ticket_type, formData.batch_date);

        Swal.fire({
          icon: 'success',
          title: 'Succès!',
          html: `
            <p>Lot de ${formData.quantity} tickets ${formData.ticket_type} généré avec succès</p>
            <p class="text-muted mt-2">Le PDF du lot est en cours de téléchargement...</p>
          `,
          timer: 3000
        });

        // Reset form
        setFormData({
          batch_date: new Date().toISOString().split('T')[0],
          ticket_type: 'aller',
          quantity: 30,
          price_per_ticket: 100,
          notes: ''
        });

        // Recharger les lots
        fetchBatches();
      }
    } catch (error) {
      console.error('Erreur génération:', error);

      // Gérer l'erreur 409 (lot déjà existant)
      if (error.message && error.message.includes('lot actif existe déjà')) {
        Swal.fire({
          icon: 'warning',
          title: 'Lot déjà existant',
          html: `
            <p>Un lot actif existe déjà pour les tickets <strong>${formData.ticket_type.toUpperCase()}</strong> du <strong>${new Date(formData.batch_date).toLocaleDateString('fr-FR')}</strong>.</p>
            <p class="mt-3">Vous devez d'abord désactiver le lot existant dans le tableau ci-dessous avant d'en créer un nouveau.</p>
          `,
          confirmButtonText: 'J\'ai compris'
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: error.message || 'Erreur lors de la génération du lot'
        });
      }
    } finally {
      setLoading(false);
    }
  };

  const handleDeactivateBatch = async (batchId) => {
    const result = await Swal.fire({
      title: 'Désactiver ce lot?',
      text: 'Les tickets restants ne pourront plus être vendus',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Oui, désactiver',
      cancelButtonText: 'Annuler'
    });

    if (result.isConfirmed) {
      try {
        setLoading(true);
        const response = await apiEndpoints.deactivateBusTicketBatch(batchId);

        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Désactivé',
            text: 'Le lot a été désactivé',
            timer: 2000
          });
          fetchBatches();
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: 'Erreur lors de la désactivation'
        });
      } finally {
        setLoading(false);
      }
    }
  };

  const getTypeColor = (type) => {
    return type === 'aller' ? 'primary' : 'success';
  };

  const getTypeLabel = (type) => {
    return type === 'aller' ? 'ALLER' : 'RETOUR';
  };

  return (
    <Container className="mt-4">
      <Row className="mb-4">
        <Col>
          <h2>
            <Ticket className="me-2" style={{ color: '#007bff' }} />
            Génération de Tickets Journaliers
          </h2>
          <p className="text-muted">
            Générez des lots de tickets de bus pour la vente au comptoir
          </p>
        </Col>
      </Row>

      {/* Formulaire de génération */}
      <Row className="mb-4">
        <Col md={6}>
          <Card className="shadow-sm">
            <Card.Header className="bg-primary text-white">
              <h5 className="mb-0">
                <PlusCircle className="me-2" />
                Générer un Nouveau Lot
              </h5>
            </Card.Header>
            <Card.Body>
              <Form onSubmit={handleGenerateBatch}>
                <Form.Group className="mb-3">
                  <Form.Label>Date</Form.Label>
                  <Form.Control
                    type="date"
                    name="batch_date"
                    value={formData.batch_date}
                    onChange={handleChange}
                    required
                  />
                  <Form.Text className="text-muted">
                    Date de validité des tickets
                  </Form.Text>
                </Form.Group>

                <Form.Group className="mb-3">
                  <Form.Label>Type de Ticket</Form.Label>
                  <Form.Select
                    name="ticket_type"
                    value={formData.ticket_type}
                    onChange={handleChange}
                    required
                  >
                    <option value="aller">🚌 Aller</option>
                    <option value="retour">🏠 Retour</option>
                  </Form.Select>
                </Form.Group>

                <Form.Group className="mb-3">
                  <Form.Label>Quantité de Tickets</Form.Label>
                  <Form.Control
                    type="number"
                    name="quantity"
                    value={formData.quantity}
                    onChange={handleChange}
                    min="1"
                    max="100"
                    required
                  />
                  <Form.Text className="text-muted">
                    Entre 1 et 100 tickets
                  </Form.Text>
                </Form.Group>

                <Form.Group className="mb-3">
                  <Form.Label>Prix Unitaire (FCFA)</Form.Label>
                  <Form.Control
                    type="number"
                    name="price_per_ticket"
                    value={formData.price_per_ticket}
                    onChange={handleChange}
                    min="0"
                    required
                  />
                </Form.Group>

                <Form.Group className="mb-3">
                  <Form.Label>Notes (Optionnel)</Form.Label>
                  <Form.Control
                    as="textarea"
                    rows={2}
                    name="notes"
                    value={formData.notes}
                    onChange={handleChange}
                    placeholder="Remarques ou informations supplémentaires..."
                  />
                </Form.Group>

                <div className="d-grid gap-2">
                  <Button
                    variant="primary"
                    type="submit"
                    disabled={loading}
                    size="lg"
                  >
                    {loading ? (
                      <>
                        <Spinner size="sm" className="me-2" />
                        Génération...
                      </>
                    ) : (
                      <>
                        <PlusCircle className="me-2" />
                        Générer le Lot
                      </>
                    )}
                  </Button>
                </div>
              </Form>
            </Card.Body>
          </Card>
        </Col>

        {/* Aperçu */}
        <Col md={6}>
          <Card className="shadow-sm">
            <Card.Header className="bg-info text-white">
              <h5 className="mb-0">📋 Aperçu du Lot</h5>
            </Card.Header>
            <Card.Body>
              <Table borderless className="mb-0">
                <tbody>
                  <tr>
                    <td className="fw-bold">Date:</td>
                    <td>{new Date(formData.batch_date).toLocaleDateString('fr-FR')}</td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Type:</td>
                    <td>
                      <Badge bg={getTypeColor(formData.ticket_type)}>
                        {getTypeLabel(formData.ticket_type)}
                      </Badge>
                    </td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Quantité:</td>
                    <td className="fs-4 text-primary">{formData.quantity} tickets</td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Prix unitaire:</td>
                    <td className="fs-5 text-success">
                      {new Intl.NumberFormat('fr-FR').format(formData.price_per_ticket)} FCFA
                    </td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Revenu potentiel:</td>
                    <td className="fs-4 text-warning">
                      {new Intl.NumberFormat('fr-FR').format(
                        formData.quantity * formData.price_per_ticket
                      )}{' '}
                      FCFA
                    </td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Préfixe:</td>
                    <td>
                      <code>{formData.ticket_type === 'aller' ? 'T-A' : 'T-R'}-001 → {formData.ticket_type === 'aller' ? 'T-A' : 'T-R'}-{String(formData.quantity).padStart(3, '0')}</code>
                    </td>
                  </tr>
                </tbody>
              </Table>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Liste des lots générés */}
      <Row>
        <Col>
          <Card className="shadow-sm">
            <Card.Header>
              <h5 className="mb-0">📦 Lots Générés</h5>
              <small className="text-muted">
                Les lignes en vert sont les lots actifs. Un seul lot actif par type/date est permis.
              </small>
            </Card.Header>
            <Card.Body>
              {loading && batches.length === 0 ? (
                <div className="text-center py-4">
                  <Spinner animation="border" />
                  <p className="mt-2">Chargement...</p>
                </div>
              ) : batches.length === 0 ? (
                <Alert variant="info" className="text-center">
                  Aucun lot généré pour le moment
                </Alert>
              ) : (
                <Table responsive hover>
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Générés</th>
                      <th>Vendus</th>
                      <th>Restants</th>
                      <th>Prix Unit.</th>
                      <th>Statut</th>
                      <th>Généré par</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {batches.map((batch) => (
                      <tr key={batch.id} className={batch.is_active ? 'table-success' : ''}>
                        <td>{new Date(batch.batch_date).toLocaleDateString('fr-FR')}</td>
                        <td>
                          <Badge bg={getTypeColor(batch.ticket_type)}>
                            {getTypeLabel(batch.ticket_type)}
                          </Badge>
                        </td>
                        <td>{batch.quantity_generated}</td>
                        <td className="text-success fw-bold">{batch.quantity_sold}</td>
                        <td className="text-warning fw-bold">{batch.quantity_remaining}</td>
                        <td>{new Intl.NumberFormat('fr-FR').format(batch.price_per_ticket)} FCFA</td>
                        <td>
                          {batch.is_active ? (
                            <Badge bg="success">✓ Actif</Badge>
                          ) : (
                            <Badge bg="secondary">Inactif</Badge>
                          )}
                        </td>
                        <td>
                          <small>{batch.generated_by?.name || 'N/A'}</small>
                        </td>
                        <td>
                          <div className="d-flex gap-2">
                            <Button
                              variant="outline-info"
                              size="sm"
                              onClick={() => downloadBatchPdf(batch.id, batch.ticket_type, batch.batch_date)}
                              title="Télécharger le PDF du lot"
                            >
                              <Download />
                            </Button>
                            {batch.is_active && (
                              <Button
                                variant="outline-danger"
                                size="sm"
                                onClick={() => handleDeactivateBatch(batch.id)}
                                title="Désactiver ce lot pour en créer un nouveau"
                              >
                                <XCircle />
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
        </Col>
      </Row>
    </Container>
  );
};

export default BusTicketGeneration;
