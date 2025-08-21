import { useEffect, useState } from "react";
import {
  Alert,
  Badge,
  Button,
  Card,
  Col,
  Container,
  Row,
  Spinner,
  Modal,
  Form,
} from "react-bootstrap";
import {
  ArrowLeft,
  FileEarmarkText,
  CheckLg,
  XLg,
  PencilSquare,
  Receipt,
  Calendar,
  Person,
  CashCoin,
  ClockHistory,
  ExclamationTriangle,
} from "react-bootstrap-icons";
import { useNavigate, useParams } from "react-router-dom";
import Swal from "sweetalert2";
import { useSchool } from "../../contexts/SchoolContext";
import { secureApi } from "../../utils/apiMigration";
import { host } from "../../utils/fetch";

const DocumentaryFeeDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { formatCurrency } = useSchool();

  const [documentaryFee, setDocumentaryFee] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [actionLoading, setActionLoading] = useState(false);

  // Modal d'édition
  const [showEditModal, setShowEditModal] = useState(false);
  const [editForm, setEditForm] = useState({});
  const [editErrors, setEditErrors] = useState({});

  useEffect(() => {
    loadDocumentaryFee();
  }, [id]);

  const loadDocumentaryFee = async () => {
    try {
      setLoading(true);
      setError("");

      const data = await secureApi.makeRequest(`/documentary-fees/${id}`);
      
      if (data.success) {
        setDocumentaryFee(data.data);
        setEditForm({
          description: data.data.description || "",
          fee_amount: data.data.fee_amount || data.data.amount || "",
          penalty_amount: data.data.penalty_amount || "",
          payment_date: data.data.payment_date,
          versement_date: data.data.versement_date || "",
          payment_method: data.data.payment_method,
          reference_number: data.data.reference_number || "",
          notes: data.data.notes || "",
        });
      } else {
        setError(data.message || "Erreur lors du chargement");
      }
    } catch (err) {
      console.error("Error loading documentary fee:", err);
      setError("Frais de dossier non trouvé");
    } finally {
      setLoading(false);
    }
  };

  const handleValidate = async () => {
    try {
      const result = await Swal.fire({
        title: "Valider ce frais ?",
        text: "Cette action est irréversible",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#198754",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Oui, valider",
        cancelButtonText: "Annuler",
      });

      if (result.isConfirmed) {
        setActionLoading(true);
        const data = await secureApi.makeRequest(`/documentary-fees/${id}/validate`, {
          method: 'POST',
        });

        if (data.success) {
          setSuccess("Frais validé avec succès");
          loadDocumentaryFee();
          setTimeout(() => setSuccess(""), 3000);
        } else {
          setError(data.message || "Erreur lors de la validation");
        }
      }
    } catch (err) {
      console.error("Error validating fee:", err);
      setError("Erreur lors de la validation du frais");
    } finally {
      setActionLoading(false);
    }
  };

  const handleCancel = async () => {
    try {
      const result = await Swal.fire({
        title: "Annuler ce frais ?",
        text: "Cette action est irréversible",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Oui, annuler",
        cancelButtonText: "Non",
      });

      if (result.isConfirmed) {
        setActionLoading(true);
        const data = await secureApi.makeRequest(`/documentary-fees/${id}/cancel`, {
          method: 'POST',
        });

        if (data.success) {
          setSuccess("Frais annulé avec succès");
          loadDocumentaryFee();
          setTimeout(() => setSuccess(""), 3000);
        } else {
          setError(data.message || "Erreur lors de l'annulation");
        }
      }
    } catch (err) {
      console.error("Error cancelling fee:", err);
      setError("Erreur lors de l'annulation du frais");
    } finally {
      setActionLoading(false);
    }
  };

  const handleEdit = async () => {
    try {
      setActionLoading(true);
      setEditErrors({});

      const data = await secureApi.makeRequest(`/documentary-fees/${id}`, {
        method: 'PUT',
        body: JSON.stringify(editForm),
      });

      if (data.success) {
        setSuccess("Frais modifié avec succès");
        setShowEditModal(false);
        loadDocumentaryFee();
        setTimeout(() => setSuccess(""), 3000);
      } else {
        setError(data.message || "Erreur lors de la modification");
        if (data.errors) {
          setEditErrors(data.errors);
        }
      }
    } catch (err) {
      console.error("Error updating fee:", err);
      setError("Erreur lors de la modification du frais");
    } finally {
      setActionLoading(false);
    }
  };

  const downloadReceipt = async () => {
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
        link.download = `recu_frais_dossier_${documentaryFee.receipt_number}.pdf`;
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

  const getFeeTypeInfo = (feeType) => {
    const types = {
      'frais_dossier': { label: 'Frais de dossier', color: 'primary' },
      'penalite': { label: 'Pénalité', color: 'danger' },
      'autre': { label: 'Autre frais', color: 'secondary' },
    };
    return types[feeType] || { label: feeType, color: 'secondary' };
  };

  const getStatusInfo = (status) => {
    const statuses = {
      'pending': { label: 'En attente', color: 'warning', icon: ClockHistory },
      'validated': { label: 'Validé', color: 'success', icon: CheckLg },
      'cancelled': { label: 'Annulé', color: 'danger', icon: XLg },
    };
    return statuses[status] || { label: status, color: 'secondary', icon: ExclamationTriangle };
  };

  const getPaymentMethodLabel = (method) => {
    const methods = {
      'cash': 'Espèces',
      'cheque': 'Chèque',
      'transfer': 'Virement',
      'mobile_money': 'Mobile Money',
    };
    return methods[method] || method;
  };

  if (loading) {
    return (
      <Container fluid className="py-4">
        <div className="text-center py-5">
          <Spinner animation="border" variant="primary" />
          <p className="mt-2">Chargement...</p>
        </div>
      </Container>
    );
  }

  if (error && !documentaryFee) {
    return (
      <Container fluid className="py-4">
        <Alert variant="danger">{error}</Alert>
        <Button variant="outline-secondary" onClick={() => navigate("/payments/documentary-fees")}>
          <ArrowLeft className="me-1" />
          Retour à la liste
        </Button>
      </Container>
    );
  }

  const statusInfo = getStatusInfo(documentaryFee.status);
  const StatusIcon = statusInfo.icon;

  return (
    <Container fluid className="py-4">
      <Row className="mb-4">
        <Col>
          <div className="d-flex align-items-center">
            <Button
              variant="outline-secondary"
              onClick={() => navigate("/payments/documentary-fees")}
              className="me-3"
            >
              <ArrowLeft />
            </Button>
            <div>
              <h2 className="mb-1">
                <FileEarmarkText className="me-2" />
                Détails du Frais de Dossier
              </h2>
              <p className="text-muted mb-0">
                Reçu N° {documentaryFee.receipt_number}
              </p>
            </div>
          </div>
        </Col>
      </Row>

      {/* Messages */}
      {error && <Alert variant="danger" className="mb-3">{error}</Alert>}
      {success && <Alert variant="success" className="mb-3">{success}</Alert>}

      <Row>
        <Col lg={8}>
          {/* Informations principales */}
          <Card className="mb-4">
            <Card.Header className="d-flex justify-content-between align-items-center">
              <h5 className="mb-0">Informations Générales</h5>
              <Badge bg={statusInfo.color} className="d-flex align-items-center">
                <StatusIcon className="me-1" />
                {statusInfo.label}
              </Badge>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <div className="mb-3">
                    <strong className="text-muted">Type de frais</strong>
                    <div className="mt-1">
                      <Badge bg={getFeeTypeInfo(documentaryFee.fee_type).color}>
                        {getFeeTypeInfo(documentaryFee.fee_type).label}
                      </Badge>
                    </div>
                  </div>
                </Col>
                <Col md={6}>
                  <div className="mb-3">
                    <strong className="text-muted">Montant</strong>
                    <div className="mt-1">
                      {documentaryFee.penalty_amount && documentaryFee.penalty_amount > 0 ? (
                        <div>
                          <div>Frais: <span className="h6 text-primary">{formatCurrency(documentaryFee.fee_amount || documentaryFee.amount)}</span></div>
                          <div>Pénalité: <span className="h6 text-danger">{formatCurrency(documentaryFee.penalty_amount)}</span></div>
                          <hr className="my-1" />
                          <div>Total: <span className="h5 text-primary fw-bold">{formatCurrency(documentaryFee.total_amount || documentaryFee.amount)}</span></div>
                        </div>
                      ) : (
                        <div className="h5 text-primary">
                          {formatCurrency(documentaryFee.fee_amount || documentaryFee.amount)}
                        </div>
                      )}
                    </div>
                  </div>
                </Col>
              </Row>

              {documentaryFee.description && (
                <Row>
                  <Col md={12}>
                    <div className="mb-3">
                      <strong className="text-muted">Description</strong>
                      <div className="mt-1">{documentaryFee.description}</div>
                    </div>
                  </Col>
                </Row>
              )}
            </Card.Body>
          </Card>

          {/* Informations de l'étudiant */}
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">
                <Person className="me-2" />
                Étudiant
              </h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <div className="mb-3">
                    <strong className="text-muted">Nom complet</strong>
                    <div className="mt-1">
                      {documentaryFee.student.first_name} {documentaryFee.student.last_name}
                    </div>
                  </div>
                </Col>
                <Col md={6}>
                  <div className="mb-3">
                    <strong className="text-muted">Matricule</strong>
                    <div className="mt-1">
                      <code>{documentaryFee.student.student_number}</code>
                    </div>
                  </div>
                </Col>
              </Row>

              {documentaryFee.student.class_series?.school_class && (
                <Row>
                  <Col md={6}>
                    <div className="mb-3">
                      <strong className="text-muted">Classe</strong>
                      <div className="mt-1">
                        {documentaryFee.student.class_series.school_class.name}
                      </div>
                    </div>
                  </Col>
                  <Col md={6}>
                    <div className="mb-3">
                      <strong className="text-muted">Année scolaire</strong>
                      <div className="mt-1">
                        {documentaryFee.school_year.name}
                      </div>
                    </div>
                  </Col>
                </Row>
              )}
            </Card.Body>
          </Card>

          {/* Informations de paiement */}
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">
                <CashCoin className="me-2" />
                Informations de Paiement
              </h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <div className="mb-3">
                    <strong className="text-muted">Date de paiement</strong>
                    <div className="mt-1">
                      <Calendar className="me-1" />
                      {new Date(documentaryFee.payment_date).toLocaleDateString()}
                    </div>
                  </div>
                </Col>
                {documentaryFee.versement_date && (
                  <Col md={6}>
                    <div className="mb-3">
                      <strong className="text-muted">Date de versement</strong>
                      <div className="mt-1">
                        <Calendar className="me-1" />
                        {new Date(documentaryFee.versement_date).toLocaleDateString()}
                      </div>
                    </div>
                  </Col>
                )}
              </Row>

              <Row>
                <Col md={6}>
                  <div className="mb-3">
                    <strong className="text-muted">Mode de paiement</strong>
                    <div className="mt-1">
                      {getPaymentMethodLabel(documentaryFee.payment_method)}
                    </div>
                  </div>
                </Col>
                {documentaryFee.reference_number && (
                  <Col md={6}>
                    <div className="mb-3">
                      <strong className="text-muted">Numéro de référence</strong>
                      <div className="mt-1">
                        <code>{documentaryFee.reference_number}</code>
                      </div>
                    </div>
                  </Col>
                )}
              </Row>

              {documentaryFee.notes && (
                <Row>
                  <Col md={12}>
                    <div className="mb-3">
                      <strong className="text-muted">Notes</strong>
                      <div className="mt-1 p-2 bg-light rounded">
                        {documentaryFee.notes}
                      </div>
                    </div>
                  </Col>
                </Row>
              )}
            </Card.Body>
          </Card>

          {/* Historique */}
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <ClockHistory className="me-2" />
                Historique
              </h5>
            </Card.Header>
            <Card.Body>
              <div className="mb-3">
                <strong className="text-muted">Créé par</strong>
                <div className="mt-1">
                  {documentaryFee.created_by_user.name} le {' '}
                  {new Date(documentaryFee.created_at).toLocaleString()}
                </div>
              </div>

              {documentaryFee.validation_date && documentaryFee.validated_by_user && (
                <div className="mb-3">
                  <strong className="text-muted">Validé par</strong>
                  <div className="mt-1">
                    {documentaryFee.validated_by_user.name} le {' '}
                    {new Date(documentaryFee.validation_date).toLocaleString()}
                  </div>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>

        <Col lg={4}>
          {/* Actions */}
          <Card className="position-sticky" style={{ top: "20px" }}>
            <Card.Header>
              <h5 className="mb-0">Actions</h5>
            </Card.Header>
            <Card.Body>
              <div className="d-grid gap-2">
                <Button
                  variant="primary"
                  onClick={downloadReceipt}
                >
                  <Receipt className="me-1" />
                  Télécharger le reçu
                </Button>

                {documentaryFee.status === 'pending' && (
                  <>
                    <Button
                      variant="success"
                      onClick={handleValidate}
                      disabled={actionLoading}
                    >
                      {actionLoading ? (
                        <Spinner animation="border" size="sm" className="me-1" />
                      ) : (
                        <CheckLg className="me-1" />
                      )}
                      Valider
                    </Button>

                    <Button
                      variant="outline-warning"
                      onClick={() => setShowEditModal(true)}
                    >
                      <PencilSquare className="me-1" />
                      Modifier
                    </Button>

                    <Button
                      variant="outline-danger"
                      onClick={handleCancel}
                      disabled={actionLoading}
                    >
                      {actionLoading ? (
                        <Spinner animation="border" size="sm" className="me-1" />
                      ) : (
                        <XLg className="me-1" />
                      )}
                      Annuler
                    </Button>
                  </>
                )}
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Modal de modification */}
      <Modal show={showEditModal} onHide={() => setShowEditModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Modifier le frais</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form>
            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Frais de dossier (FCFA) *</Form.Label>
                  <Form.Control
                    type="number"
                    min="0"
                    step="1"
                    value={editForm.fee_amount}
                    onChange={(e) => setEditForm({...editForm, fee_amount: e.target.value})}
                    isInvalid={!!editErrors.fee_amount}
                  />
                  <Form.Control.Feedback type="invalid">
                    {editErrors.fee_amount}
                  </Form.Control.Feedback>
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Pénalité (FCFA)</Form.Label>
                  <Form.Control
                    type="number"
                    min="0"
                    step="1"
                    value={editForm.penalty_amount}
                    onChange={(e) => setEditForm({...editForm, penalty_amount: e.target.value})}
                    isInvalid={!!editErrors.penalty_amount}
                  />
                  <Form.Control.Feedback type="invalid">
                    {editErrors.penalty_amount}
                  </Form.Control.Feedback>
                  <Form.Text className="text-muted">
                    Montant optionnel pour les retards ou autres pénalités
                  </Form.Text>
                </Form.Group>
              </Col>
            </Row>

            {/* Total display */}
            {(editForm.fee_amount || editForm.penalty_amount) && (
              <Row className="mb-3">
                <Col md={12}>
                  <div className="p-2 bg-light border rounded">
                    <small className="text-muted">Total à payer: </small>
                    <span className="fw-bold text-primary">
                      {formatCurrency(
                        (parseFloat(editForm.fee_amount) || 0) + 
                        (parseFloat(editForm.penalty_amount) || 0)
                      )}
                    </span>
                  </div>
                </Col>
              </Row>
            )}

            <Form.Group className="mb-3">
              <Form.Label>Description</Form.Label>
              <Form.Control
                type="text"
                value={editForm.description}
                onChange={(e) => setEditForm({...editForm, description: e.target.value})}
                isInvalid={!!editErrors.description}
              />
              <Form.Control.Feedback type="invalid">
                {editErrors.description}
              </Form.Control.Feedback>
            </Form.Group>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Date de paiement</Form.Label>
                  <Form.Control
                    type="date"
                    value={editForm.payment_date}
                    onChange={(e) => setEditForm({...editForm, payment_date: e.target.value})}
                    isInvalid={!!editErrors.payment_date}
                  />
                  <Form.Control.Feedback type="invalid">
                    {editErrors.payment_date}
                  </Form.Control.Feedback>
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Date de versement</Form.Label>
                  <Form.Control
                    type="date"
                    value={editForm.versement_date}
                    onChange={(e) => setEditForm({...editForm, versement_date: e.target.value})}
                    isInvalid={!!editErrors.versement_date}
                  />
                  <Form.Control.Feedback type="invalid">
                    {editErrors.versement_date}
                  </Form.Control.Feedback>
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Mode de paiement</Form.Label>
                  <Form.Select
                    value={editForm.payment_method}
                    onChange={(e) => setEditForm({...editForm, payment_method: e.target.value})}
                    isInvalid={!!editErrors.payment_method}
                  >
                    <option value="cash">Espèces</option>
                    <option value="cheque">Chèque</option>
                    <option value="transfer">Virement</option>
                    <option value="mobile_money">Mobile Money</option>
                  </Form.Select>
                  <Form.Control.Feedback type="invalid">
                    {editErrors.payment_method}
                  </Form.Control.Feedback>
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group className="mb-3">
                  <Form.Label>Numéro de référence</Form.Label>
                  <Form.Control
                    type="text"
                    value={editForm.reference_number}
                    onChange={(e) => setEditForm({...editForm, reference_number: e.target.value})}
                    isInvalid={!!editErrors.reference_number}
                  />
                  <Form.Control.Feedback type="invalid">
                    {editErrors.reference_number}
                  </Form.Control.Feedback>
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
                isInvalid={!!editErrors.notes}
              />
              <Form.Control.Feedback type="invalid">
                {editErrors.notes}
              </Form.Control.Feedback>
            </Form.Group>
          </Form>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowEditModal(false)}>
            Annuler
          </Button>
          <Button 
            variant="primary" 
            onClick={handleEdit}
            disabled={actionLoading}
          >
            {actionLoading ? (
              <>
                <Spinner animation="border" size="sm" className="me-1" />
                Enregistrement...
              </>
            ) : (
              'Enregistrer les modifications'
            )}
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default DocumentaryFeeDetails;