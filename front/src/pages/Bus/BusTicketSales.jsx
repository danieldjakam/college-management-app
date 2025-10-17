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
  Spinner,
  InputGroup,
  ListGroup
} from 'react-bootstrap';
import {
  CashCoin,
  Person,
  Search,
  Ticket,
  Download,
  X,
  CheckCircle,
  ExclamationTriangle
} from 'react-bootstrap-icons';
import { apiEndpoints } from '../../utils/api';
import { authService } from '../../services/authService';
import { host } from '../../utils/fetch';
import Swal from 'sweetalert2';

const BusTicketSales = () => {
  const [loading, setLoading] = useState(false);
  const [students, setStudents] = useState([]);
  const [todayBatches, setTodayBatches] = useState([]);
  const [todaySales, setTodaySales] = useState([]);
  const [stats, setStats] = useState(null);

  // Formulaire de vente
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [selectedBatch, setSelectedBatch] = useState(null);
  const [ticketNumber, setTicketNumber] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [notes, setNotes] = useState('');

  // Recherche d'élève
  const [studentSearch, setStudentSearch] = useState('');
  const [showStudentDropdown, setShowStudentDropdown] = useState(false);
  const [filteredStudents, setFilteredStudents] = useState([]);

  useEffect(() => {
    fetchStudents();
    fetchTodayBatches();
    fetchTodaySales();
  }, []);

  useEffect(() => {
    if (studentSearch.trim() === '') {
      setFilteredStudents(students.slice(0, 50));
    } else {
      const filtered = students.filter(student => {
        const fullName = `${student.last_name} ${student.first_name}`.toLowerCase();
        const matricule = (student.matricule || '').toLowerCase();
        const className = (student.class_series?.school_class?.name || '').toLowerCase();
        const search = studentSearch.toLowerCase();
        return fullName.includes(search) || matricule.includes(search) || className.includes(search);
      }).slice(0, 50);
      setFilteredStudents(filtered);
    }
  }, [studentSearch, students]);

  // Réinitialiser le numéro de ticket quand on change de lot
  useEffect(() => {
    if (selectedBatch) {
      setTicketNumber('');
    }
  }, [selectedBatch]);

  const fetchStudents = async () => {
    try {
      const response = await apiEndpoints.getAllStudents();
      if (response.success) {
        setStudents(response.data);
        setFilteredStudents(response.data.slice(0, 50));
      }
    } catch (error) {
      console.error('Erreur chargement élèves:', error);
    }
  };

  const fetchTodayBatches = async () => {
    try {
      const response = await apiEndpoints.getTodayBusTicketBatches();
      if (response.success) {
        setTodayBatches(response.data);
      }
    } catch (error) {
      console.error('Erreur chargement lots:', error);
    }
  };

  const fetchTodaySales = async () => {
    try {
      const response = await apiEndpoints.getTodayBusTicketSales();
      if (response.success) {
        setTodaySales(response.data);
        setStats(response.stats);
      }
    } catch (error) {
      console.error('Erreur chargement ventes:', error);
    }
  };

  const handleSelectStudent = (student) => {
    setSelectedStudent(student);
    setStudentSearch(`${student.last_name} ${student.first_name} - ${student.matricule}`);
    setShowStudentDropdown(false);
  };

  const clearStudentSelection = () => {
    setSelectedStudent(null);
    setStudentSearch('');
    setShowStudentDropdown(false);
  };

  const handleSellTicket = async () => {
    if (!selectedStudent) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'Veuillez sélectionner un élève'
      });
      return;
    }

    if (!selectedBatch) {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'Veuillez sélectionner un type de ticket'
      });
      return;
    }

    if (!ticketNumber || ticketNumber.trim() === '') {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: 'Veuillez saisir le numéro de ticket'
      });
      return;
    }

    try {
      setLoading(true);

      const response = await apiEndpoints.sellBusTicket({
        batch_id: selectedBatch.id,
        student_id: selectedStudent.id,
        ticket_number: ticketNumber.trim(),
        payment_method: paymentMethod,
        notes: notes
      });

      if (response.success) {
        const sale = response.data;

        // Télécharger le ticket PDF automatiquement
        await downloadTicket(sale.id);

        Swal.fire({
          icon: 'success',
          title: 'Ticket Vendu!',
          html: `
            <p>Ticket <strong>${sale.ticket_number}</strong> vendu à</p>
            <p><strong>${selectedStudent.last_name} ${selectedStudent.first_name}</strong></p>
            <p>Montant: <strong>${new Intl.NumberFormat('fr-FR').format(sale.price)} FCFA</strong></p>
          `,
          timer: 3000
        });

        // Reset form
        clearStudentSelection();
        setSelectedBatch(null);
        setTicketNumber('');
        setPaymentMethod('cash');
        setNotes('');

        // Recharger les données
        fetchTodayBatches();
        fetchTodaySales();
      }
    } catch (error) {
      console.error('Erreur vente:', error);
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: error.response?.data?.message || 'Erreur lors de la vente du ticket'
      });
    } finally {
      setLoading(false);
    }
  };

  const downloadTicket = async (saleId) => {
    try {
      const token = authService.getToken();
      const response = await fetch(`${host}/api/bus/tickets/${saleId}/download`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (!response.ok) throw new Error('Erreur téléchargement');

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `ticket_${saleId}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Erreur téléchargement ticket:', error);
    }
  };

  const getTypeColor = (type) => type === 'aller' ? 'primary' : 'success';
  const getTypeLabel = (type) => type === 'aller' ? 'ALLER' : 'RETOUR';

  return (
    <Container className="mt-4">
      <Row className="mb-4">
        <Col>
          <h2>
            <CashCoin className="me-2" style={{ color: '#28a745' }} />
            Point de Vente - Tickets Bus
          </h2>
          <p className="text-muted">
            Vente de tickets de bus au comptoir
          </p>
        </Col>
      </Row>

      {/* Stock disponible */}
      <Row className="mb-4">
        <Col md={6}>
          <Card className="shadow-sm border-primary">
            <Card.Header className="bg-primary text-white">
              <h6 className="mb-0">📦 Stock Disponible - {new Date().toLocaleDateString('fr-FR')}</h6>
            </Card.Header>
            <Card.Body>
              {todayBatches.length === 0 ? (
                <Alert variant="warning" className="mb-0">
                  <ExclamationTriangle className="me-2" />
                  Aucun lot généré pour aujourd'hui. Générez d'abord des tickets.
                </Alert>
              ) : (
                <Row>
                  {todayBatches.map((batch) => (
                    <Col md={6} key={batch.id} className="mb-2">
                      <Card
                        className={`cursor-pointer ${selectedBatch?.id === batch.id ? 'border-success border-3' : ''}`}
                        onClick={() => setSelectedBatch(batch)}
                        style={{ cursor: 'pointer' }}
                      >
                        <Card.Body>
                          <div className="d-flex justify-content-between align-items-center">
                            <div>
                              <Badge bg={getTypeColor(batch.ticket_type)} className="mb-2">
                                {getTypeLabel(batch.ticket_type)}
                              </Badge>
                              <div className="fs-3 fw-bold text-success">
                                {batch.quantity_remaining}
                              </div>
                              <small className="text-muted">
                                sur {batch.quantity_generated} tickets
                              </small>
                            </div>
                            <div className="text-end">
                              <div className="fs-5 fw-bold text-primary">
                                {new Intl.NumberFormat('fr-FR').format(batch.price_per_ticket)}
                              </div>
                              <small className="text-muted">FCFA</small>
                            </div>
                          </div>
                        </Card.Body>
                      </Card>
                    </Col>
                  ))}
                </Row>
              )}
            </Card.Body>
          </Card>
        </Col>

        {/* Formulaire de vente */}
        <Col md={6}>
          <Card className="shadow-sm border-success">
            <Card.Header className="bg-success text-white">
              <h6 className="mb-0">
                <Ticket className="me-2" />
                Vendre un Ticket
              </h6>
            </Card.Header>
            <Card.Body>
              <Form>
                {/* Recherche élève */}
                <Form.Group className="mb-3">
                  <Form.Label>Élève *</Form.Label>
                  <InputGroup>
                    <InputGroup.Text>
                      <Search />
                    </InputGroup.Text>
                    <Form.Control
                      type="text"
                      placeholder="Rechercher par nom, matricule ou classe..."
                      value={studentSearch}
                      onChange={(e) => {
                        setStudentSearch(e.target.value);
                        setShowStudentDropdown(true);
                      }}
                      onFocus={() => setShowStudentDropdown(true)}
                      autoComplete="off"
                    />
                    {selectedStudent && (
                      <Button
                        variant="outline-secondary"
                        onClick={clearStudentSelection}
                      >
                        <X />
                      </Button>
                    )}
                  </InputGroup>

                  {/* Dropdown élèves */}
                  {showStudentDropdown && !selectedStudent && filteredStudents.length > 0 && (
                    <ListGroup className="position-absolute w-100 mt-1" style={{ maxHeight: '300px', overflowY: 'auto', zIndex: 1000 }}>
                      {filteredStudents.map((student) => (
                        <ListGroup.Item
                          key={student.id}
                          action
                          onClick={() => handleSelectStudent(student)}
                          className="d-flex justify-content-between align-items-center"
                        >
                          <div>
                            <strong>{student.last_name} {student.first_name}</strong>
                            <br />
                            <small className="text-muted">
                              {student.matricule} - {student.class_series?.school_class?.name || 'N/A'}
                            </small>
                          </div>
                        </ListGroup.Item>
                      ))}
                    </ListGroup>
                  )}

                  {selectedStudent && (
                    <Alert variant="success" className="mt-2 mb-0">
                      <Person className="me-2" />
                      <strong>{selectedStudent.last_name} {selectedStudent.first_name}</strong>
                      <br />
                      <small>{selectedStudent.matricule} - {selectedStudent.class_series?.school_class?.name || 'N/A'}</small>
                    </Alert>
                  )}
                </Form.Group>

                {/* Type de ticket */}
                <Form.Group className="mb-3">
                  <Form.Label>Type de Ticket *</Form.Label>
                  <div className="text-muted mb-2">
                    {selectedBatch ? (
                      <Badge bg={getTypeColor(selectedBatch.ticket_type)} className="fs-6">
                        {getTypeLabel(selectedBatch.ticket_type)} - {new Intl.NumberFormat('fr-FR').format(selectedBatch.price_per_ticket)} FCFA
                      </Badge>
                    ) : (
                      <small>Sélectionnez un type dans le stock ci-dessus</small>
                    )}
                  </div>
                </Form.Group>

                {/* Numéro de ticket */}
                {selectedBatch && (
                  <Form.Group className="mb-3">
                    <Form.Label>Numéro de Ticket *</Form.Label>
                    <Form.Control
                      type="text"
                      placeholder={`Ex: ${selectedBatch.batch_prefix}-001`}
                      value={ticketNumber}
                      onChange={(e) => setTicketNumber(e.target.value.toUpperCase())}
                      autoComplete="off"
                    />
                    <Form.Text className="text-muted">
                      Saisissez le numéro du ticket déchiré ({selectedBatch.batch_prefix}-001 à {selectedBatch.batch_prefix}-{String(selectedBatch.end_number).padStart(3, '0')})
                    </Form.Text>
                  </Form.Group>
                )}

                {/* Méthode de paiement */}
                <Form.Group className="mb-3">
                  <Form.Label>Méthode de Paiement</Form.Label>
                  <Form.Select
                    value={paymentMethod}
                    onChange={(e) => setPaymentMethod(e.target.value)}
                  >
                    <option value="cash">💵 Espèces</option>
                    <option value="mobile_money">📱 Mobile Money</option>
                    <option value="bank_transfer">🏦 Virement Bancaire</option>
                    <option value="check">📝 Chèque</option>
                    <option value="other">➕ Autre</option>
                  </Form.Select>
                </Form.Group>

                {/* Notes */}
                <Form.Group className="mb-3">
                  <Form.Label>Notes (Optionnel)</Form.Label>
                  <Form.Control
                    as="textarea"
                    rows={2}
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    placeholder="Remarques..."
                  />
                </Form.Group>

                {/* Bouton vendre */}
                <div className="d-grid">
                  <Button
                    variant="success"
                    size="lg"
                    onClick={handleSellTicket}
                    disabled={loading || !selectedStudent || !selectedBatch || !ticketNumber}
                  >
                    {loading ? (
                      <>
                        <Spinner size="sm" className="me-2" />
                        Vente en cours...
                      </>
                    ) : (
                      <>
                        <CashCoin className="me-2" />
                        Vendre & Imprimer
                      </>
                    )}
                  </Button>
                </div>
              </Form>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Statistiques du jour */}
      {stats && (
        <Row className="mb-4">
          <Col md={3}>
            <Card className="text-center shadow-sm border-primary">
              <Card.Body>
                <h3 className="text-primary">{stats.total_sales}</h3>
                <small className="text-muted">Total Ventes</small>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="text-center shadow-sm border-success">
              <Card.Body>
                <h3 className="text-success">
                  {new Intl.NumberFormat('fr-FR').format(stats.total_revenue)}
                </h3>
                <small className="text-muted">FCFA Encaissés</small>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="text-center shadow-sm border-info">
              <Card.Body>
                <h3 className="text-info">{stats.aller_count}</h3>
                <small className="text-muted">Tickets Aller</small>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3}>
            <Card className="text-center shadow-sm border-warning">
              <Card.Body>
                <h3 className="text-warning">{stats.retour_count}</h3>
                <small className="text-muted">Tickets Retour</small>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Ventes du jour */}
      <Row>
        <Col>
          <Card className="shadow-sm">
            <Card.Header>
              <h5 className="mb-0">📋 Ventes du Jour</h5>
            </Card.Header>
            <Card.Body>
              {todaySales.length === 0 ? (
                <Alert variant="info" className="text-center mb-0">
                  Aucune vente pour aujourd'hui
                </Alert>
              ) : (
                <Table responsive hover>
                  <thead>
                    <tr>
                      <th>Heure</th>
                      <th>N° Ticket</th>
                      <th>Élève</th>
                      <th>Type</th>
                      <th>Prix</th>
                      <th>Paiement</th>
                      <th>Vendu par</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {todaySales.map((sale) => (
                      <tr key={sale.id}>
                        <td>{new Date(sale.sold_at).toLocaleTimeString('fr-FR')}</td>
                        <td><code>{sale.ticket_number}</code></td>
                        <td>
                          <strong>{sale.student?.last_name} {sale.student?.first_name}</strong>
                          <br />
                          <small className="text-muted">{sale.student?.matricule}</small>
                        </td>
                        <td>
                          <Badge bg={getTypeColor(sale.ticket_type)}>
                            {getTypeLabel(sale.ticket_type)}
                          </Badge>
                        </td>
                        <td className="fw-bold">
                          {new Intl.NumberFormat('fr-FR').format(sale.price)} FCFA
                        </td>
                        <td>
                          <small>{sale.payment_method === 'cash' ? '💵 Espèces' : sale.payment_method}</small>
                        </td>
                        <td>
                          <small>{sale.sold_by?.name || 'N/A'}</small>
                        </td>
                        <td>
                          <Button
                            variant="outline-primary"
                            size="sm"
                            onClick={() => downloadTicket(sale.id)}
                            title="Télécharger le ticket"
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
        </Col>
      </Row>
    </Container>
  );
};

export default BusTicketSales;
