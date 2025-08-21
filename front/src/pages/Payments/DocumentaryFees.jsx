import { useEffect, useState } from "react";
import {
  Alert,
  Badge,
  Button,
  Card,
  Col,
  Container,
  Form,
  Modal,
  Row,
  Spinner,
  Table,
  Dropdown,
  InputGroup,
} from "react-bootstrap";
import {
  FileEarmarkText,
  Plus,
  Search,
  FunnelFill,
  Eye,
  PencilSquare,
  Trash,
  Receipt,
  CashCoin,
  ExclamationTriangle,
  FileEarmarkPdf,
  Grid,
  HouseHeartFill,
  PeopleFill,
  InfoCircle,
} from "react-bootstrap-icons";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import { useSchool } from "../../contexts/SchoolContext";
import { secureApi } from "../../utils/apiMigration";
import { host } from "../../utils/fetch";
import { useAuth } from "../../hooks/useAuth";

// Service API pour les frais de dossiers
const documentaryFeesApi = {
  async getList(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = `/documentary-fees${queryString ? '?' + queryString : ''}`;
    return await secureApi.makeRequest(url);
  },
  
  async getStatistics(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = `/documentary-fees/statistics${queryString ? '?' + queryString : ''}`;
    return await secureApi.makeRequest(url);
  },
  
  
  async delete(id) {
    return await secureApi.makeRequest(`/documentary-fees/${id}`, {
      method: 'DELETE'
    });
  },
  
  async downloadReceipt(id) {
    const response = await secureApi.makeRequest(`/documentary-fees/${id}/receipt`, {
      method: 'GET',
      headers: { 'Accept': 'application/pdf' }
    });
    return response;
  }
};

const DocumentaryFees = () => {
  const navigate = useNavigate();
  const { formatCurrency } = useSchool();

  const [documentaryFees, setDocumentaryFees] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  // Pagination et filtres
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  
  const [filters, setFilters] = useState({
    student_id: "",
    has_penalty: "",
    status: "",
    start_date: "",
    end_date: "",
    search: "",
  });

  const [showFilters, setShowFilters] = useState(false);
  const [statistics, setStatistics] = useState(null);
  
  // Modal de rapport de période
  const [showReportModal, setShowReportModal] = useState(false);
  const [reportForm, setReportForm] = useState({
    start_date: "",
    end_date: "",
    has_penalty: ""
  });
  const [reportLoading, setReportLoading] = useState(false);

  // Note: Maintenant il n'y a qu'un seul type de frais avec pénalité optionnelle

  const statuses = [
    { value: "validated", label: "Validé", color: "success" },
    { value: "cancelled", label: "Annulé", color: "danger" },
  ];

  useEffect(() => {
    loadDocumentaryFees();
    loadStatistics();
  }, [currentPage, filters]);

  const loadDocumentaryFees = async () => {
    try {
      setLoading(true);
      setError("");

      const params = {
        page: currentPage,
        ...Object.fromEntries(
          Object.entries(filters).filter(([_, value]) => value !== "")
        ),
      };

      const data = await documentaryFeesApi.getList(params);
      
      if (data.success) {
        setDocumentaryFees(data.data.data || []);
        setCurrentPage(data.data.current_page || 1);
        setTotalPages(data.data.last_page || 1);
        setTotalItems(data.data.total || 0);
      } else {
        setError(data.message || "Erreur lors du chargement des frais");
      }
    } catch (err) {
      console.error("Error loading documentary fees:", err);
      setError("Erreur lors du chargement des frais de dossiers");
    } finally {
      setLoading(false);
    }
  };

  const loadStatistics = async () => {
    try {
      const params = Object.fromEntries(
        Object.entries(filters).filter(([_, value]) => value !== "")
      );

      const data = await documentaryFeesApi.getStatistics(params);
      if (data.success) {
        setStatistics(data.data);
      }
    } catch (err) {
      console.error("Error loading statistics:", err);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    setCurrentPage(1);
  };

  const clearFilters = () => {
    setFilters({
      student_id: "",
      has_penalty: "",
      status: "",
      start_date: "",
      end_date: "",
      search: "",
    });
    setCurrentPage(1);
  };


  const handleGenerateReport = async () => {
    try {
      if (!reportForm.start_date || !reportForm.end_date) {
        setError("Veuillez sélectionner une période");
        return;
      }

      setReportLoading(true);
      
      const token = localStorage.getItem('token');
      const response = await fetch(`${host}/api/documentary-fees/report/period`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(reportForm)
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `rapport_frais_dossiers_${reportForm.start_date}_au_${reportForm.end_date}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        setShowReportModal(false);
        setSuccess("Rapport généré avec succès");
        setTimeout(() => setSuccess(""), 3000);
      } else {
        const errorData = await response.json();
        setError(errorData.message || "Erreur lors de la génération du rapport");
      }
    } catch (err) {
      console.error("Error generating report:", err);
      setError("Erreur lors de la génération du rapport");
    } finally {
      setReportLoading(false);
    }
  };

  const handleDelete = async (id) => {
    try {
      const result = await Swal.fire({
        title: "Supprimer ce frais ?",
        text: "Cette action est définitive et irréversible",
        icon: "error",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler",
      });

      if (result.isConfirmed) {
        const data = await documentaryFeesApi.delete(id);

        if (data.success) {
          setSuccess("Frais supprimé avec succès");
          loadDocumentaryFees();
          loadStatistics();
          setTimeout(() => setSuccess(""), 3000);
        } else {
          setError(data.message || "Erreur lors de la suppression");
        }
      }
    } catch (err) {
      console.error("Error deleting fee:", err);
      setError("Erreur lors de la suppression du frais");
    }
  };

  const downloadReceipt = async (id, receiptNumber) => {
    try {
      // Pour le téléchargement de fichier, nous devons utiliser fetch directement
      const token = localStorage.getItem('token');
      const response = await fetch(`${host}/api/documentary-fees/${id}/receipt`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf',
        },
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `recu_frais_dossier_${receiptNumber}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } else {
        setError("Erreur lors du téléchargement du reçu");
      }
    } catch (err) {
      console.error("Error downloading receipt:", err);
      setError("Erreur lors du téléchargement du reçu");
    }
  };

  const getFeeTypeInfo = (fee) => {
    if (fee.penalty_amount && fee.penalty_amount > 0) {
      return { label: "Frais de dossier + Pénalité", color: "warning" };
    }
    return { label: "Frais de dossier", color: "primary" };
  };

  const getStatusInfo = (status) => {
    return statuses.find(s => s.value === status) || 
           { label: status, color: "secondary" };
  };

    const { user } = useAuth();
  return (
    <Container fluid className="py-4">
      <Row className="mb-4">
        <Col>
          <div className="d-flex justify-content-between align-items-center">
            <div>
              <h2 className="mb-1">
                <FileEarmarkText className="me-2" />
                Frais de Dossiers & Pénalités
              </h2>
              <p className="text-muted mb-0">
                Gestion des frais hors scolarité - Frais de dossier avec pénalités optionnelles
              </p>
            </div>
            <div className="d-flex gap-2">
              <Button
                variant="outline-info"
                size="sm"
                onClick={() => {
                  Swal.fire({
                    title: 'Aide - Frais de Dossiers',
                    html: `
                      <div class="text-start">
                        <p><strong>Types de frais :</strong></p>
                        <ul>
                          <li><span class="badge bg-primary">Frais de dossier</span> : Frais principal obligatoire</li>
                          <li><span class="badge bg-danger">Pénalité</span> : Montant optionnel pour retards, etc.</li>
                        </ul>
                      </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Compris'
                  });
                }}
              >
                <ExclamationTriangle className="me-1" />
                Aide
              </Button>
              <Button
                variant="outline-success"
                onClick={() => setShowReportModal(true)}
                className="me-2"
              >
                <FileEarmarkPdf className="me-1" />
                Rapport PDF
              </Button>
              {user.role === "secretaire" && <Button
                variant="primary"
                onClick={() => navigate("/payments/documentary-fees/create")}
              >
                <Plus className="me-1" />
                Encaisser
              </Button>}
            </div>
          </div>
        </Col>
      </Row>

      {/* Statistiques compact */}
      {statistics && (
        
        <div className="row mb-4">
            <div className="col-md-3">
                <div className="card bg-primary text-white">
                    <div className="card-body">
                        <div className="d-flex justify-content-between">
                            <div>
                                <div className="fs-2 fw-bold">{statistics.total_fees}</div>
                                <div>Total</div>
                            </div>
                            <PeopleFill size={40} className="opacity-75" />
                        </div>
                    </div>
                </div>
            </div>
            <div className="col-md-3">
                <div className="card bg-success text-white">
                    <div className="card-body">
                        <div className="d-flex justify-content-between">
                            <div>
                                <div className="fs-2 fw-bold">{formatCurrency(statistics.total_amount)}</div>
                                <div>Classes</div>
                            </div>
                            <HouseHeartFill size={40} className="opacity-75" />
                        </div>
                    </div>
                </div>
            </div>
            <div className="col-md-3">
                <div className="card bg-warning text-white">
                    <div className="card-body">
                        <div className="d-flex justify-content-between">
                            <div>
                                <div className="fs-2 fw-bold">{statistics.fees_with_penalty || 0}</div>
                                <div>Avec pénalité</div>
                            </div>
                            <Grid size={40} className="opacity-75" />
                        </div>
                    </div>
                </div>
            </div>
            <div className="col-md-3">
                <div className="card bg-danger text-white">
                    <div className="card-body">
                        <div className="d-flex justify-content-between">
                            <div>
                                <div className="fs-6 fw-bold">{formatCurrency(statistics.total_penalty_amount || 0)}</div>
                                <div>Frais Pénalités</div>
                            </div>
                            <InfoCircle size={40} className="opacity-75" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
      )}

      {/* Filtres compact */}
      <div className="mb-3">
        <Row className="g-2">
          <Col md={3}>
            <Form.Select
              value={filters.has_penalty}
              onChange={(e) => handleFilterChange("has_penalty", e.target.value)}
              size="sm"
            >
              <option value="">Tous les frais</option>
              <option value="1">Avec pénalité</option>
              <option value="0">Sans pénalité</option>
            </Form.Select>
          </Col>
          <Col md={3}>
            <InputGroup size="sm">
              <Form.Control
                type="text"
                placeholder="Rechercher..."
                value={filters.search}
                onChange={(e) => handleFilterChange("search", e.target.value)}
              />
              <Button variant="outline-secondary">
                <Search />
              </Button>
            </InputGroup>
          </Col>
          <Col md={2}>
            <Form.Control
              type="date"
              value={filters.start_date}
              onChange={(e) => handleFilterChange("start_date", e.target.value)}
              size="sm"
              placeholder="Date début"
            />
          </Col>
          <Col md={2}>
            <Form.Control
              type="date"
              value={filters.end_date}
              onChange={(e) => handleFilterChange("end_date", e.target.value)}
              size="sm"
              placeholder="Date fin"
            />
          </Col>
          <Col md={2}>
            <Button variant="outline-secondary" size="sm" onClick={clearFilters}>
              Effacer
            </Button>
          </Col>
        </Row>
      </div>

      {/* Messages */}
      {error && <Alert variant="danger" className="mb-3">{error}</Alert>}
      {success && <Alert variant="success" className="mb-3">{success}</Alert>}

      {/* Liste des frais */}
      <Card>
        <Card.Header>
          <div className="d-flex justify-content-between align-items-center">
            <span>Liste des Frais ({totalItems})</span>
            {totalPages > 1 && (
              <div>
                Page {currentPage} sur {totalPages}
              </div>
            )}
          </div>
        </Card.Header>
        <Card.Body className="p-0">
          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" variant="primary" />
              <p className="mt-2">Chargement...</p>
            </div>
          ) : documentaryFees.length === 0 ? (
            <div className="text-center py-5">
              <FileEarmarkText size={48} className="text-muted mb-3" />
              <p className="text-muted">Aucun frais trouvé</p>
            </div>
          ) : (
            <Table responsive striped hover>
              <thead>
                <tr>
                  <th>Reçu</th>
                  <th>Étudiant</th>
                  <th>Montant</th>
                  <th>Date</th>
                  <th width="120">Actions</th>
                </tr>
              </thead>
              <tbody>
                {documentaryFees.map((fee) => (
                  <tr key={fee.id}>
                    <td>
                      <div>
                        <code className="small">{fee.receipt_number}</code>
                        {(fee.penalty_amount && fee.penalty_amount > 0) && (
                          <div><Badge bg="danger" size="sm">+Pén</Badge></div>
                        )}
                      </div>
                    </td>
                    <td>
                      <div>
                        <div className="fw-bold small">{fee.student?.first_name} {fee.student?.last_name}</div>
                        <small className="text-muted">{fee.student?.student_number}</small>
                      </div>
                    </td>
                    <td>
                      <div>
                        {(fee.penalty_amount && fee.penalty_amount > 0) ? (
                          <div>
                            <div><small className="text-muted">Frais:</small> <span className="text-primary">{formatCurrency(fee.fee_amount || fee.amount)}</span></div>
                            <div><small className="text-muted">Pénalité:</small> <span className="text-danger">{formatCurrency(fee.penalty_amount)}</span></div>
                            <div><strong>Total: {formatCurrency(fee.total_amount || fee.amount)}</strong></div>
                          </div>
                        ) : (
                          <strong>{formatCurrency(fee.fee_amount || fee.amount)}</strong>
                        )}
                      </div>
                    </td>
                    <td>
                      {new Date(fee.payment_date).toLocaleDateString()}
                    </td>
                    <td>
                      <div className="d-flex gap-1">
                        <Button
                          variant="outline-primary"
                          size="sm"
                          onClick={() => navigate(`/payments/documentary-fees/${fee.id}`)}
                          title="Détails"
                        >
                          <Eye />
                        </Button>
                        <Button
                          variant="outline-info"
                          size="sm"
                          onClick={() => downloadReceipt(fee.id, fee.receipt_number)}
                          title="Télécharger PDF"
                        >
                          <Receipt />
                        </Button>
                        <Dropdown>
                          <Dropdown.Toggle variant="outline-secondary" size="sm">
                            ⋮
                          </Dropdown.Toggle>
                          <Dropdown.Menu>
                            <Dropdown.Item onClick={() => navigate(`/payments/documentary-fees/${fee.id}/edit`)}>
                              <PencilSquare className="me-2" />
                              Modifier
                            </Dropdown.Item>
                            <Dropdown.Divider />
                            <Dropdown.Item onClick={() => handleDelete(fee.id)} className="text-danger">
                              <Trash className="me-2" />
                              Supprimer
                            </Dropdown.Item>
                          </Dropdown.Menu>
                        </Dropdown>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card.Body>
        
        {/* Pagination */}
        {totalPages > 1 && (
          <Card.Footer>
            <div className="d-flex justify-content-between align-items-center">
              <Button
                variant="outline-primary"
                disabled={currentPage === 1}
                onClick={() => setCurrentPage(currentPage - 1)}
              >
                Précédent
              </Button>
              <span>
                Page {currentPage} sur {totalPages}
              </span>
              <Button
                variant="outline-primary"
                disabled={currentPage === totalPages}
                onClick={() => setCurrentPage(currentPage + 1)}
              >
                Suivant
              </Button>
            </div>
          </Card.Footer>
        )}
      </Card>

      {/* Modal de génération de rapport */}
      <Modal show={showReportModal} onHide={() => setShowReportModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>
            <FileEarmarkPdf className="me-2" />
            Générer un Rapport PDF
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form>
            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Date de début *</Form.Label>
                  <Form.Control
                    type="date"
                    value={reportForm.start_date}
                    onChange={(e) => setReportForm({...reportForm, start_date: e.target.value})}
                    required
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Date de fin *</Form.Label>
                  <Form.Control
                    type="date"
                    value={reportForm.end_date}
                    onChange={(e) => setReportForm({...reportForm, end_date: e.target.value})}
                    required
                  />
                </Form.Group>
              </Col>
            </Row>
            
            <Form.Group className="mb-3">
              <Form.Label>Filtrer par type</Form.Label>
              <Form.Select
                value={reportForm.has_penalty}
                onChange={(e) => setReportForm({...reportForm, has_penalty: e.target.value})}
              >
                <option value="">Tous les frais</option>
                <option value="0">Frais de dossier uniquement</option>
                <option value="1">Frais avec pénalité</option>
              </Form.Select>
            </Form.Group>

            <div className="alert alert-info">
              <small>
                <strong>ℹ️ Contenu du rapport :</strong>
                <ul className="mb-0 mt-2">
                  <li>Liste complète des versements de la période</li>
                  <li>Statistiques détaillées (totaux, pénalités, etc.)</li>
                  <li>Informations étudiant et classe</li>
                  <li>Photo/logo de l'école en arrière-plan</li>
                </ul>
              </small>
            </div>
          </Form>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowReportModal(false)}>
            Annuler
          </Button>
          <Button 
            variant="success" 
            onClick={handleGenerateReport}
            disabled={reportLoading || !reportForm.start_date || !reportForm.end_date}
          >
            {reportLoading ? (
              <>
                <Spinner animation="border" size="sm" className="me-1" />
                Génération...
              </>
            ) : (
              <>
                <FileEarmarkPdf className="me-1" />
                Générer le Rapport
              </>
            )}
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default DocumentaryFees;