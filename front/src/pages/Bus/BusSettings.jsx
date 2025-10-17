import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Form, Button, Alert, Spinner } from 'react-bootstrap';
import { BusFront, Save } from 'react-bootstrap-icons';
import { apiEndpoints } from '../../utils/api';
import Swal from 'sweetalert2';

const BusSettings = () => {
  const [settings, setSettings] = useState({
    monthly_price: 0,
    description: ''
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);

  // Charger les paramètres au chargement
  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await apiEndpoints.getBusSettings();

      if (response.success && response.data) {
        setSettings({
          monthly_price: response.data.monthly_price || 0,
          description: response.data.description || ''
        });
      }
    } catch (err) {
      console.error('Erreur lors du chargement des paramètres:', err);
      setError('Erreur lors du chargement des paramètres du bus');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setSettings(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Validation
    if (!settings.monthly_price || settings.monthly_price <= 0) {
      Swal.fire({
        title: 'Erreur',
        text: 'Le prix mensuel doit être supérieur à 0',
        icon: 'error',
        confirmButtonText: 'OK'
      });
      return;
    }

    try {
      setSaving(true);
      setError(null);

      const response = await apiEndpoints.updateBusSettings({
        monthly_price: parseFloat(settings.monthly_price),
        description: settings.description
      });

      if (response.success) {
        Swal.fire({
          title: 'Succès',
          text: 'Tarif mis à jour avec succès',
          icon: 'success',
          confirmButtonText: 'OK'
        });
        fetchSettings(); // Recharger les données
      }
    } catch (err) {
      console.error('Erreur lors de la mise à jour:', err);
      Swal.fire({
        title: 'Erreur',
        text: err.message || 'Erreur lors de la mise à jour du tarif',
        icon: 'error',
        confirmButtonText: 'OK'
      });
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <Container className="mt-4 text-center">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Chargement...</span>
        </Spinner>
        <p className="mt-2">Chargement des paramètres...</p>
      </Container>
    );
  }

  return (
    <Container className="mt-4">
      <Row className="mb-4">
        <Col>
          <h2 className="d-flex align-items-center">
            <BusFront className="me-2" style={{ color: '#007bff' }} />
            Configuration du Service de Transport
          </h2>
          <p className="text-muted">
            Définissez le tarif mensuel du bus pour les élèves
          </p>
        </Col>
      </Row>

      {error && (
        <Alert variant="danger" dismissible onClose={() => setError(null)}>
          {error}
        </Alert>
      )}

      <Row>
        <Col md={8}>
          <Card className="shadow-sm">
            <Card.Header className="bg-primary text-white">
              <h5 className="mb-0">Tarification du Bus</h5>
            </Card.Header>
            <Card.Body>
              <Form onSubmit={handleSubmit}>
                <Form.Group className="mb-4">
                  <Form.Label className="fw-bold">
                    Prix Mensuel (FCFA) <span className="text-danger">*</span>
                  </Form.Label>
                  <Form.Control
                    type="number"
                    name="monthly_price"
                    value={settings.monthly_price}
                    onChange={handleChange}
                    placeholder="Ex: 10000"
                    min="0"
                    step="100"
                    required
                    style={{ fontSize: '1.2rem', fontWeight: 'bold' }}
                  />
                  <Form.Text className="text-muted">
                    Ce tarif sera appliqué pour tous les élèves qui souscrivent au service de transport
                  </Form.Text>
                </Form.Group>

                <Form.Group className="mb-4">
                  <Form.Label className="fw-bold">Description / Notes</Form.Label>
                  <Form.Control
                    as="textarea"
                    rows={3}
                    name="description"
                    value={settings.description}
                    onChange={handleChange}
                    placeholder="Informations complémentaires sur le service de transport..."
                  />
                  <Form.Text className="text-muted">
                    Optionnel : ajoutez des informations sur les horaires, les itinéraires, etc.
                  </Form.Text>
                </Form.Group>

                <div className="d-grid gap-2">
                  <Button
                    type="submit"
                    variant="primary"
                    size="lg"
                    disabled={saving}
                  >
                    {saving ? (
                      <>
                        <Spinner animation="border" size="sm" className="me-2" />
                        Enregistrement...
                      </>
                    ) : (
                      <>
                        <Save className="me-2" />
                        Enregistrer les Modifications
                      </>
                    )}
                  </Button>
                </div>
              </Form>
            </Card.Body>
          </Card>
        </Col>

        <Col md={4}>
          <Card className="shadow-sm border-info">
            <Card.Header className="bg-info text-white">
              <h6 className="mb-0">Informations</h6>
            </Card.Header>
            <Card.Body>
              <div className="mb-3">
                <h6 className="text-muted mb-2">Tarif Actuel</h6>
                <h3 className="text-primary mb-0">
                  {new Intl.NumberFormat('fr-FR').format(settings.monthly_price)} FCFA
                  <small className="text-muted d-block mt-1" style={{ fontSize: '0.8rem' }}>
                    par mois
                  </small>
                </h3>
              </div>

              <hr />

              <div className="mb-3">
                <h6 className="text-muted mb-2">Exemples de calcul</h6>
                <ul className="list-unstyled">
                  <li className="mb-2">
                    <strong>1 mois :</strong>{' '}
                    {new Intl.NumberFormat('fr-FR').format(settings.monthly_price * 1)} FCFA
                  </li>
                  <li className="mb-2">
                    <strong>3 mois :</strong>{' '}
                    {new Intl.NumberFormat('fr-FR').format(settings.monthly_price * 3)} FCFA
                  </li>
                  <li className="mb-2">
                    <strong>6 mois :</strong>{' '}
                    {new Intl.NumberFormat('fr-FR').format(settings.monthly_price * 6)} FCFA
                  </li>
                </ul>
              </div>

              <hr />

              <Alert variant="warning" className="mb-0">
                <small>
                  <strong>Note :</strong> La modification du tarif s'appliquera aux prochains abonnements. Les abonnements déjà payés conservent leur tarif d'origine.
                </small>
              </Alert>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default BusSettings;
