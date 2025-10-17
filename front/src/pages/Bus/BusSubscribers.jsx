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
  Spinner,
  Alert,
  InputGroup
} from 'react-bootstrap';
import {
  BusFront,
  Download,
  Funnel,
  Person,
  Calendar,
  Search,
  FileEarmarkExcel,
  FilePdf
} from 'react-bootstrap-icons';
import { apiEndpoints } from '../../utils/api';
import { host } from '../../utils/fetch';
import { authService } from '../../services/authService';
import Swal from 'sweetalert2';

const BusSubscribers = () => {
  const [subscriptions, setSubscriptions] = useState([]);
  const [filteredSubscriptions, setFilteredSubscriptions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [schoolYears, setSchoolYears] = useState([]);
  const [filters, setFilters] = useState({
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
    school_year_id: '',
    payment_status: '',
    search: ''
  });

  useEffect(() => {
    fetchInitialData();
  }, []);

  useEffect(() => {
    applyFilters();
  }, [filters, subscriptions]);

  const fetchInitialData = async () => {
    try {
      setLoading(true);
      await Promise.all([fetchSubscriptions(), fetchSchoolYears()]);
    } catch (error) {
      console.error('Erreur lors du chargement des données:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchSubscriptions = async () => {
    try {
      const response = await apiEndpoints.getBusSubscriptions();
      if (response.success && response.data) {
        setSubscriptions(response.data);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des abonnements:', error);
    }
  };

  const fetchSchoolYears = async () => {
    try {
      const response = await apiEndpoints.getSchoolYears();
      if (response.success && response.data) {
        setSchoolYears(response.data);
        // Sélectionner l'année courante par défaut
        const currentYear = response.data.find((y) => y.is_current);
        if (currentYear) {
          setFilters((prev) => ({ ...prev, school_year_id: currentYear.id }));
        }
      }
    } catch (error) {
      console.error('Erreur lors du chargement des années scolaires:', error);
    }
  };

  const applyFilters = () => {
    let filtered = [...subscriptions];

    // Filtre par année scolaire
    if (filters.school_year_id) {
      filtered = filtered.filter(
        (sub) => sub.school_year_id === parseInt(filters.school_year_id)
      );
    }

    // Filtre par statut de paiement
    if (filters.payment_status) {
      filtered = filtered.filter(
        (sub) => sub.payment_status === filters.payment_status
      );
    }

    // Filtre par mois/année (vérifie si l'abonnement couvre ce mois)
    if (filters.month && filters.year) {
      filtered = filtered.filter((sub) => {
        if (!sub.covered_months || sub.covered_months.length === 0) {
          return false;
        }
        return sub.covered_months.some(
          (m) =>
            m.month === parseInt(filters.month) &&
            m.year === parseInt(filters.year)
        );
      });
    }

    // Recherche par nom d'élève
    if (filters.search) {
      const searchLower = filters.search.toLowerCase();
      filtered = filtered.filter((sub) => {
        const fullName = `${sub.student?.last_name || ''} ${
          sub.student?.first_name || ''
        }`.toLowerCase();
        const matricule = (sub.student?.matricule || '').toLowerCase();
        return fullName.includes(searchLower) || matricule.includes(searchLower);
      });
    }

    setFilteredSubscriptions(filtered);
  };

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters((prev) => ({
      ...prev,
      [name]: value
    }));
  };

  const handleDownloadTickets = (subscriptionId) => {
    const token = authService.getToken();
    const url = `${host}/api${apiEndpoints.downloadBusTickets(subscriptionId)}`;

    fetch(url, {
      headers: {
        Authorization: `Bearer ${token}`
      }
    })
      .then((response) => response.blob())
      .then((blob) => {
        const blobUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = blobUrl;
        link.setAttribute('download', `Bus_Tickets_${subscriptionId}.pdf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(blobUrl);

        Swal.fire({
          title: 'Téléchargement réussi',
          text: 'Les tickets ont été téléchargés',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        });
      })
      .catch((error) => {
        console.error('Erreur lors du téléchargement:', error);
        Swal.fire({
          title: 'Erreur',
          text: 'Erreur lors du téléchargement des tickets',
          icon: 'error'
        });
      });
  };

  const getMonthName = (month) => {
    const months = [
      'Janvier',
      'Février',
      'Mars',
      'Avril',
      'Mai',
      'Juin',
      'Juillet',
      'Août',
      'Septembre',
      'Octobre',
      'Novembre',
      'Décembre'
    ];
    return months[month - 1] || '';
  };

  const exportToCSV = () => {
    const headers = ['Élève', 'Matricule', 'Classe', 'Mois de début', 'Nombre de mois', 'Montant', 'Statut'];
    const rows = filteredSubscriptions.map((sub) => [
      `${sub.student?.last_name} ${sub.student?.first_name}`,
      sub.student?.matricule || '',
      sub.student?.class_series?.school_class?.name || 'N/A',
      `${getMonthName(sub.start_month)} ${sub.start_year}`,
      sub.months_count,
      sub.total_amount,
      sub.payment_status === 'paid' ? 'Payé' : 'En attente'
    ]);

    let csv = headers.join(',') + '\n';
    rows.forEach((row) => {
      csv += row.join(',') + '\n';
    });

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Abonnes_Bus_${new Date().toISOString()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  };

  const clearFilters = () => {
    setFilters({
      month: new Date().getMonth() + 1,
      year: new Date().getFullYear(),
      school_year_id: '',
      payment_status: '',
      search: ''
    });
  };

  if (loading) {
    return (
      <Container className="mt-4 text-center">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Chargement...</span>
        </Spinner>
        <p className="mt-2">Chargement des données...</p>
      </Container>
    );
  }

  return (
    <Container className="mt-4">
      <Row className="mb-4">
        <Col>
          <div className="d-flex justify-content-between align-items-center">
            <div>
              <h2 className="d-flex align-items-center">
                <BusFront className="me-2" style={{ color: '#007bff' }} />
                Liste des Abonnés Bus
              </h2>
              <p className="text-muted">
                Consultez et gérez tous les abonnements au service de transport
              </p>
            </div>
            <Button variant="success" onClick={exportToCSV}>
              <FileEarmarkExcel className="me-2" />
              Exporter CSV
            </Button>
          </div>
        </Col>
      </Row>

      {/* Filtres */}
      <Card className="shadow-sm mb-4">
        <Card.Header className="bg-light">
          <h6 className="mb-0 d-flex align-items-center">
            <Funnel className="me-2" />
            Filtres
          </h6>
        </Card.Header>
        <Card.Body>
          <Row>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label className="small fw-bold">Année Scolaire</Form.Label>
                <Form.Select
                  size="sm"
                  name="school_year_id"
                  value={filters.school_year_id}
                  onChange={handleFilterChange}
                >
                  <option value="">Toutes les années</option>
                  {schoolYears.map((year) => (
                    <option key={year.id} value={year.id}>
                      {year.name} {year.is_current ? '(Actuelle)' : ''}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={2}>
              <Form.Group className="mb-3">
                <Form.Label className="small fw-bold">Mois</Form.Label>
                <Form.Select
                  size="sm"
                  name="month"
                  value={filters.month}
                  onChange={handleFilterChange}
                >
                  {Array.from({ length: 12 }, (_, i) => i + 1).map((month) => (
                    <option key={month} value={month}>
                      {getMonthName(month)}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={2}>
              <Form.Group className="mb-3">
                <Form.Label className="small fw-bold">Année</Form.Label>
                <Form.Control
                  type="number"
                  size="sm"
                  name="year"
                  value={filters.year}
                  onChange={handleFilterChange}
                  min="2020"
                  max="2050"
                />
              </Form.Group>
            </Col>

            <Col md={2}>
              <Form.Group className="mb-3">
                <Form.Label className="small fw-bold">Statut</Form.Label>
                <Form.Select
                  size="sm"
                  name="payment_status"
                  value={filters.payment_status}
                  onChange={handleFilterChange}
                >
                  <option value="">Tous</option>
                  <option value="paid">Payé</option>
                  <option value="pending">En attente</option>
                </Form.Select>
              </Form.Group>
            </Col>

            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label className="small fw-bold">Recherche</Form.Label>
                <InputGroup size="sm">
                  <InputGroup.Text>
                    <Search />
                  </InputGroup.Text>
                  <Form.Control
                    type="text"
                    placeholder="Nom ou matricule..."
                    name="search"
                    value={filters.search}
                    onChange={handleFilterChange}
                  />
                </InputGroup>
              </Form.Group>
            </Col>
          </Row>
          <Row>
            <Col className="text-end">
              <Button size="sm" variant="outline-secondary" onClick={clearFilters}>
                Réinitialiser les filtres
              </Button>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Statistiques rapides */}
      <Row className="mb-4">
        <Col md={3}>
          <Card className="shadow-sm border-primary">
            <Card.Body className="text-center">
              <h3 className="text-primary mb-0">{filteredSubscriptions.length}</h3>
              <small className="text-muted">Total Abonnés</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="shadow-sm border-success">
            <Card.Body className="text-center">
              <h3 className="text-success mb-0">
                {filteredSubscriptions.filter((s) => s.payment_status === 'paid').length}
              </h3>
              <small className="text-muted">Payés</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="shadow-sm border-warning">
            <Card.Body className="text-center">
              <h3 className="text-warning mb-0">
                {
                  filteredSubscriptions.filter((s) => s.payment_status === 'pending')
                    .length
                }
              </h3>
              <small className="text-muted">En Attente</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="shadow-sm border-info">
            <Card.Body className="text-center">
              <h3 className="text-info mb-0">
                {new Intl.NumberFormat('fr-FR').format(
                  filteredSubscriptions.reduce((sum, s) => sum + parseFloat(s.total_amount || 0), 0)
                )}{' '}
                <small className="text-muted" style={{ fontSize: '0.7rem' }}>FCFA</small>
              </h3>
              <small className="text-muted">Revenus Total</small>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Liste des abonnements */}
      <Card className="shadow-sm">
        <Card.Header className="bg-primary text-white">
          <h5 className="mb-0">
            Abonnements ({filteredSubscriptions.length})
          </h5>
        </Card.Header>
        <Card.Body>
          {filteredSubscriptions.length === 0 ? (
            <Alert variant="info" className="text-center">
              Aucun abonnement trouvé avec les filtres sélectionnés
            </Alert>
          ) : (
            <Table responsive hover>
              <thead>
                <tr>
                  <th>Élève</th>
                  <th>Classe</th>
                  <th>Période</th>
                  <th>Montant</th>
                  <th>Statut</th>
                  <th>Date d'achat</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredSubscriptions.map((sub) => (
                  <tr key={sub.id}>
                    <td>
                      <div className="d-flex align-items-center">
                        <Person className="me-2 text-muted" />
                        <div>
                          <div className="fw-bold">
                            {sub.student?.last_name} {sub.student?.first_name}
                          </div>
                          <small className="text-muted">
                            {sub.student?.matricule}
                          </small>
                        </div>
                      </div>
                    </td>
                    <td>
                      {sub.student?.class_series?.school_class?.name || 'N/A'}
                    </td>
                    <td>
                      <Calendar className="me-2 text-muted" />
                      {sub.months_count} mois
                      <br />
                      <small className="text-muted">
                        {getMonthName(sub.start_month)} {sub.start_year}
                      </small>
                    </td>
                    <td className="fw-bold">
                      {new Intl.NumberFormat('fr-FR').format(sub.total_amount)} FCFA
                      <br />
                      <small className="text-muted">
                        ({new Intl.NumberFormat('fr-FR').format(sub.monthly_price)} FCFA/mois)
                      </small>
                    </td>
                    <td>
                      <Badge bg={sub.payment_status === 'paid' ? 'success' : 'warning'}>
                        {sub.payment_status === 'paid' ? 'Payé' : 'En attente'}
                      </Badge>
                    </td>
                    <td>
                      <small>
                        {new Date(sub.purchase_date).toLocaleDateString('fr-FR')}
                      </small>
                    </td>
                    <td>
                      <Button
                        size="sm"
                        variant="outline-primary"
                        onClick={() => handleDownloadTickets(sub.id)}
                        title="Télécharger les tickets"
                      >
                        <Download />
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card.Body>
      </Card>
    </Container>
  );
};

export default BusSubscribers;
