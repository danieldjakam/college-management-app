import React, { useState, useEffect } from 'react';
import {
  Container,
  Row,
  Col,
  Card,
  Form,
  Button,
  Alert,
  Spinner,
  Table,
  Badge,
  Modal,
  InputGroup,
  ListGroup
} from 'react-bootstrap';
import { BusFront, Plus, Download, Calendar, Person, Search, X } from 'react-bootstrap-icons';
import { apiEndpoints } from '../../utils/api';
import { host } from '../../utils/fetch';
import { authService } from '../../services/authService';
import Swal from 'sweetalert2';

const BusSubscriptions = () => {
  const [showModal, setShowModal] = useState(false);
  const [students, setStudents] = useState([]);
  const [schoolYears, setSchoolYears] = useState([]);
  const [subscriptions, setSubscriptions] = useState([]);
  const [busSettings, setBusSettings] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  // Search states
  const [studentSearch, setStudentSearch] = useState('');
  const [showStudentDropdown, setShowStudentDropdown] = useState(false);
  const [filteredStudents, setFilteredStudents] = useState([]);

  // Formulaire
  const [formData, setFormData] = useState({
    student_id: '',
    school_year_id: '',
    start_month: new Date().getMonth() + 1,
    start_year: new Date().getFullYear(),
    months_count: 1,
    payment_method: 'cash',
    transaction_reference: '',
    notes: ''
  });

  useEffect(() => {
    fetchInitialData();
  }, []);

  useEffect(() => {
    if (students.length > 0) {
      setFilteredStudents(students.slice(0, 50));
    }
  }, [students]);

  useEffect(() => {
    if (showModal) {
      // Reset search when modal opens
      setStudentSearch('');
      setShowStudentDropdown(false);
      setFilteredStudents(students.slice(0, 50));
    }
  }, [showModal, students]);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (e) => {
      if (showStudentDropdown && !e.target.closest('.student-search-container')) {
        setShowStudentDropdown(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [showStudentDropdown]);

  const fetchInitialData = async () => {
    try {
      setLoading(true);
      await Promise.all([
        fetchBusSettings(),
        fetchStudents(),
        fetchSchoolYears(),
        fetchSubscriptions()
      ]);
    } catch (error) {
      console.error('Erreur lors du chargement des données:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchBusSettings = async () => {
    try {
      const response = await apiEndpoints.getBusSettings();
      if (response.success && response.data) {
        setBusSettings(response.data);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des paramètres du bus:', error);
    }
  };

  const fetchStudents = async () => {
    try {
      const response = await apiEndpoints.getAllStudents();
      if (response.success && response.data) {
        setStudents(response.data);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des élèves:', error);
    }
  };

  const fetchSchoolYears = async () => {
    try {
      const response = await apiEndpoints.getSchoolYears();
      if (response.success && response.data) {
        setSchoolYears(response.data);
        // Sélectionner l'année courante par défaut
        const currentYear = response.data.find(y => y.is_current);
        if (currentYear) {
          setFormData(prev => ({ ...prev, school_year_id: currentYear.id }));
        }
      }
    } catch (error) {
      console.error('Erreur lors du chargement des années scolaires:', error);
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

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleStudentSearch = (e) => {
    const searchValue = e.target.value;
    setStudentSearch(searchValue);
    setShowStudentDropdown(true);

    if (searchValue.trim() === '') {
      setFilteredStudents(students.slice(0, 50)); // Limit to 50 for performance
    } else {
      const filtered = students.filter(student => {
        const fullName = `${student.last_name} ${student.first_name}`.toLowerCase();
        const matricule = (student.matricule || '').toLowerCase();
        const className = (student.class_series?.school_class?.name || '').toLowerCase();
        const search = searchValue.toLowerCase();

        return fullName.includes(search) || matricule.includes(search) || className.includes(search);
      }).slice(0, 50); // Limit results

      setFilteredStudents(filtered);
    }
  };

  const selectStudent = (student) => {
    setFormData(prev => ({ ...prev, student_id: student.id }));
    setStudentSearch(`${student.last_name} ${student.first_name} - ${student.class_series?.school_class?.name || 'N/A'}`);
    setShowStudentDropdown(false);
  };

  const clearStudentSelection = () => {
    setFormData(prev => ({ ...prev, student_id: '' }));
    setStudentSearch('');
    setFilteredStudents(students.slice(0, 50));
  };

  const calculateTotalAmount = () => {
    if (!busSettings) return 0;
    return parseFloat(busSettings.monthly_price) * parseInt(formData.months_count || 1);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Validation
    if (!formData.student_id) {
      Swal.fire({
        title: 'Erreur',
        text: 'Veuillez sélectionner un élève',
        icon: 'error'
      });
      return;
    }

    if (!busSettings || busSettings.monthly_price <= 0) {
      Swal.fire({
        title: 'Erreur',
        text: 'Le tarif du bus n\'est pas encore configuré. Veuillez configurer le tarif dans les paramètres.',
        icon: 'error'
      });
      return;
    }

    try {
      setSaving(true);

      const selectedStudent = students.find(s => s.id === parseInt(formData.student_id));
      const totalAmount = calculateTotalAmount();

      // Confirmation
      const result = await Swal.fire({
        title: 'Confirmer l\'achat',
        html: `
          <div class="text-start">
            <p><strong>Élève :</strong> ${selectedStudent?.last_name} ${selectedStudent?.first_name}</p>
            <p><strong>Période :</strong> ${formData.months_count} mois (à partir de ${getMonthName(formData.start_month)} ${formData.start_year})</p>
            <p><strong>Montant total :</strong> ${new Intl.NumberFormat('fr-FR').format(totalAmount)} FCFA</p>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Confirmer',
        cancelButtonText: 'Annuler'
      });

      if (!result.isConfirmed) {
        setSaving(false);
        return;
      }

      const response = await apiEndpoints.createBusSubscription(formData);

      if (response.success) {
        Swal.fire({
          title: 'Succès',
          text: 'Abonnement créé avec succès ! Les tickets ont été générés.',
          icon: 'success',
          confirmButtonText: 'OK'
        });

        // Réinitialiser le formulaire
        setFormData({
          student_id: '',
          school_year_id: formData.school_year_id,
          start_month: new Date().getMonth() + 1,
          start_year: new Date().getFullYear(),
          months_count: 1,
          payment_method: 'cash',
          transaction_reference: '',
          notes: ''
        });

        // Réinitialiser la recherche
        setStudentSearch('');
        setShowStudentDropdown(false);

        // Recharger la liste des abonnements
        fetchSubscriptions();

        // Fermer le modal
        setShowModal(false);

        // Proposer de télécharger les tickets
        if (response.data && response.data.id) {
          const downloadResult = await Swal.fire({
            title: 'Télécharger les tickets ?',
            text: 'Voulez-vous télécharger les tickets maintenant ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Télécharger',
            cancelButtonText: 'Plus tard'
          });

          if (downloadResult.isConfirmed) {
            handleDownloadTickets(response.data.id);
          }
        }
      }
    } catch (error) {
      console.error('Erreur lors de la création de l\'abonnement:', error);
      Swal.fire({
        title: 'Erreur',
        text: error.message || 'Erreur lors de la création de l\'abonnement',
        icon: 'error'
      });
    } finally {
      setSaving(false);
    }
  };

  const handleDownloadTickets = (subscriptionId) => {
    const token = authService.getToken();
    const url = `${host}/api${apiEndpoints.downloadBusTickets(subscriptionId)}`;

    // Créer un lien temporaire pour télécharger
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Bus_Tickets_${subscriptionId}.pdf`);

    // Ajouter le token dans les headers via fetch
    fetch(url, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
      .then(response => response.blob())
      .then(blob => {
        const blobUrl = window.URL.createObjectURL(blob);
        link.href = blobUrl;
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
      .catch(error => {
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
      'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
      'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
    ];
    return months[month - 1] || '';
  };

  const getPaymentMethodLabel = (method) => {
    const labels = {
      'cash': 'Espèces',
      'bank_transfer': 'Virement bancaire',
      'mobile_money': 'Mobile Money',
      'check': 'Chèque',
      'other': 'Autre'
    };
    return labels[method] || method;
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
                Gestion des Tickets de Bus
              </h2>
              <p className="text-muted">
                Vendre des tickets de bus aux élèves et gérer les abonnements
              </p>
            </div>
            <Button
              variant="primary"
              size="lg"
              onClick={() => setShowModal(true)}
            >
              <Plus className="me-2" />
              Nouvel Abonnement
            </Button>
          </div>
        </Col>
      </Row>

      {busSettings && busSettings.monthly_price > 0 && (
        <Row className="mb-4">
          <Col>
            <Alert variant="info">
              <strong>Tarif actuel :</strong> {new Intl.NumberFormat('fr-FR').format(busSettings.monthly_price)} FCFA par mois
            </Alert>
          </Col>
        </Row>
      )}

      {/* Liste des abonnements récents */}
      <Row>
        <Col>
          <Card className="shadow-sm">
            <Card.Header className="bg-primary text-white">
              <h5 className="mb-0">Abonnements Récents</h5>
            </Card.Header>
            <Card.Body>
              {subscriptions.length === 0 ? (
                <p className="text-muted text-center py-4">
                  Aucun abonnement enregistré pour le moment
                </p>
              ) : (
                <Table responsive hover>
                  <thead>
                    <tr>
                      <th>Élève</th>
                      <th>Classe</th>
                      <th>Période</th>
                      <th>Montant</th>
                      <th>Statut</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {subscriptions.slice(0, 10).map((sub) => (
                      <tr key={sub.id}>
                        <td>
                          <Person className="me-2 text-muted" />
                          {sub.student?.last_name} {sub.student?.first_name}
                        </td>
                        <td>
                          {sub.student?.class_series?.school_class?.name || 'N/A'}
                        </td>
                        <td>
                          <Calendar className="me-2 text-muted" />
                          {sub.months_count} mois ({getMonthName(sub.start_month)} {sub.start_year})
                        </td>
                        <td className="fw-bold">
                          {new Intl.NumberFormat('fr-FR').format(sub.total_amount)} FCFA
                        </td>
                        <td>
                          <Badge bg={sub.payment_status === 'paid' ? 'success' : 'warning'}>
                            {sub.payment_status === 'paid' ? 'Payé' : 'En attente'}
                          </Badge>
                        </td>
                        <td>
                          <Button
                            size="sm"
                            variant="outline-primary"
                            onClick={() => handleDownloadTickets(sub.id)}
                          >
                            <Download className="me-1" />
                            Télécharger
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

      {/* Modal de création d'abonnement */}
      <Modal show={showModal} onHide={() => setShowModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Nouvel Abonnement Bus</Modal.Title>
        </Modal.Header>
        <Form onSubmit={handleSubmit}>
          <Modal.Body>
            <Row>
              <Col md={12}>
                <Form.Group className="mb-3 student-search-container" style={{ position: 'relative' }}>
                  <Form.Label>
                    Élève <span className="text-danger">*</span>
                  </Form.Label>
                  <InputGroup>
                    <InputGroup.Text>
                      <Search />
                    </InputGroup.Text>
                    <Form.Control
                      type="text"
                      placeholder="Rechercher un élève par nom, matricule ou classe..."
                      value={studentSearch}
                      onChange={handleStudentSearch}
                      onFocus={() => setShowStudentDropdown(true)}
                      autoComplete="off"
                    />
                    {formData.student_id && (
                      <Button
                        variant="outline-secondary"
                        onClick={clearStudentSelection}
                        title="Effacer la sélection"
                      >
                        <X />
                      </Button>
                    )}
                  </InputGroup>
                  {/* Hidden input for form validation */}
                  <input
                    type="hidden"
                    value={formData.student_id}
                    required
                  />
                  <Form.Text className="text-muted">
                    {formData.student_id ?
                      `✓ Élève sélectionné` :
                      `Tapez pour rechercher parmi ${students.length} élèves`
                    }
                  </Form.Text>

                  {showStudentDropdown && filteredStudents.length > 0 && (
                    <ListGroup
                      style={{
                        position: 'absolute',
                        top: '100%',
                        left: 0,
                        right: 0,
                        maxHeight: '300px',
                        overflowY: 'auto',
                        zIndex: 1050,
                        boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
                        marginTop: '2px'
                      }}
                    >
                      {filteredStudents.map((student) => (
                        <ListGroup.Item
                          key={student.id}
                          action
                          onClick={() => selectStudent(student)}
                          style={{
                            cursor: 'pointer',
                            backgroundColor: formData.student_id === student.id ? '#e7f3ff' : 'white'
                          }}
                        >
                          <div className="d-flex justify-content-between align-items-center">
                            <div>
                              <strong>{student.last_name} {student.first_name}</strong>
                              <br />
                              <small className="text-muted">
                                {student.matricule} • {student.class_series?.school_class?.name || 'N/A'}
                              </small>
                            </div>
                            {formData.student_id === student.id && (
                              <Badge bg="primary">Sélectionné</Badge>
                            )}
                          </div>
                        </ListGroup.Item>
                      ))}
                      {filteredStudents.length === 50 && (
                        <ListGroup.Item className="text-center text-muted">
                          <small>50 premiers résultats affichés. Affinez votre recherche...</small>
                        </ListGroup.Item>
                      )}
                    </ListGroup>
                  )}
                  {showStudentDropdown && filteredStudents.length === 0 && studentSearch && (
                    <Alert variant="warning" className="mt-2">
                      Aucun élève trouvé pour "{studentSearch}"
                    </Alert>
                  )}
                </Form.Group>
              </Col>

              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>
                    Année Scolaire <span className="text-danger">*</span>
                  </Form.Label>
                  <Form.Select
                    name="school_year_id"
                    value={formData.school_year_id}
                    onChange={handleInputChange}
                    required
                  >
                    <option value="">Sélectionner une année</option>
                    {schoolYears.map((year) => (
                      <option key={year.id} value={year.id}>
                        {year.name} {year.is_current ? '(Actuelle)' : ''}
                      </option>
                    ))}
                  </Form.Select>
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={4}>
                <Form.Group className="mb-3">
                  <Form.Label>
                    Mois de Début <span className="text-danger">*</span>
                  </Form.Label>
                  <Form.Select
                    name="start_month"
                    value={formData.start_month}
                    onChange={handleInputChange}
                    required
                  >
                    {Array.from({ length: 12 }, (_, i) => i + 1).map((month) => (
                      <option key={month} value={month}>
                        {getMonthName(month)}
                      </option>
                    ))}
                  </Form.Select>
                </Form.Group>
              </Col>

              <Col md={4}>
                <Form.Group className="mb-3">
                  <Form.Label>
                    Année <span className="text-danger">*</span>
                  </Form.Label>
                  <Form.Control
                    type="number"
                    name="start_year"
                    value={formData.start_year}
                    onChange={handleInputChange}
                    min="2020"
                    max="2050"
                    required
                  />
                </Form.Group>
              </Col>

              <Col md={4}>
                <Form.Group className="mb-3">
                  <Form.Label>
                    Nombre de Mois <span className="text-danger">*</span>
                  </Form.Label>
                  <Form.Control
                    type="number"
                    name="months_count"
                    value={formData.months_count}
                    onChange={handleInputChange}
                    min="1"
                    max="12"
                    required
                  />
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>
                    Moyen de Paiement <span className="text-danger">*</span>
                  </Form.Label>
                  <Form.Select
                    name="payment_method"
                    value={formData.payment_method}
                    onChange={handleInputChange}
                    required
                  >
                    <option value="cash">Espèces</option>
                    <option value="bank_transfer">Virement bancaire</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="check">Chèque</option>
                    <option value="other">Autre</option>
                  </Form.Select>
                </Form.Group>
              </Col>

              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Référence de Transaction</Form.Label>
                  <Form.Control
                    type="text"
                    name="transaction_reference"
                    value={formData.transaction_reference}
                    onChange={handleInputChange}
                    placeholder="Ex: REF-12345"
                  />
                </Form.Group>
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Notes</Form.Label>
              <Form.Control
                as="textarea"
                rows={2}
                name="notes"
                value={formData.notes}
                onChange={handleInputChange}
                placeholder="Informations complémentaires..."
              />
            </Form.Group>

            {busSettings && (
              <Alert variant="success">
                <strong>Montant Total :</strong>{' '}
                {new Intl.NumberFormat('fr-FR').format(calculateTotalAmount())} FCFA
                <br />
                <small className="text-muted">
                  ({new Intl.NumberFormat('fr-FR').format(busSettings.monthly_price)} FCFA × {formData.months_count} mois)
                </small>
              </Alert>
            )}
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowModal(false)}>
              Annuler
            </Button>
            <Button variant="primary" type="submit" disabled={saving}>
              {saving ? (
                <>
                  <Spinner animation="border" size="sm" className="me-2" />
                  Enregistrement...
                </>
              ) : (
                'Créer l\'Abonnement'
              )}
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </Container>
  );
};

export default BusSubscriptions;
