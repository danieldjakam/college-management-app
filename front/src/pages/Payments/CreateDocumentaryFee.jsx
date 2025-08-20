import { useEffect, useState } from "react";
import {
  Alert,
  Button,
  Card,
  Col,
  Container,
  Form,
  Row,
  Spinner,
} from "react-bootstrap";
import {
  ArrowLeft,
  FileEarmarkText,
  Save,
  Search,
} from "react-bootstrap-icons";
import { useNavigate } from "react-router-dom";
import { useSchool } from "../../contexts/SchoolContext";
import { secureApi } from "../../utils/apiMigration";

const CreateDocumentaryFee = () => {
  const navigate = useNavigate();
  const { formatCurrency } = useSchool();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  // États pour la recherche d'étudiant
  const [searchTerm, setSearchTerm] = useState("");
  const [searchResults, setSearchResults] = useState([]);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [searchLoading, setSearchLoading] = useState(false);

  const [formData, setFormData] = useState({
    student_id: "",
    description: "",
    fee_amount: "",
    penalty_amount: "",
    payment_date: new Date().toISOString().split("T")[0],
    versement_date: "",
    payment_method: "cash",
    reference_number: "",
    notes: "",
  });

  const [formErrors, setFormErrors] = useState({});

  // Note: Fee type is now fixed as "frais_dossier" with optional penalty

  // Méthodes de paiement
  const paymentMethods = [
    { value: "cash", label: "Espèces" },
    { value: "cheque", label: "Chèque" },
    { value: "transfer", label: "Virement" },
    { value: "mobile_money", label: "Mobile Money" },
  ];

  useEffect(() => {
    if (searchTerm.length >= 2) {
      const delayedSearch = setTimeout(() => {
        searchStudents();
      }, 500);
      return () => clearTimeout(delayedSearch);
    } else {
      setSearchResults([]);
    }
  }, [searchTerm]);

  const searchStudents = async () => {
    try {
      setSearchLoading(true);
      const data = await secureApi.makeRequest(`/students/search?q=${encodeURIComponent(searchTerm)}`);
      if (data.success) {
        setSearchResults(data.data || []);
      }
    } catch (err) {
      console.error("Error searching students:", err);
    } finally {
      setSearchLoading(false);
    }
  };

  const selectStudent = (student) => {
    setSelectedStudent(student);
    setFormData(prev => ({ ...prev, student_id: student.id }));
    setSearchResults([]);
    setSearchTerm("");
    setFormErrors(prev => ({ ...prev, student_id: "" }));
  };

  const clearSelectedStudent = () => {
    setSelectedStudent(null);
    setFormData(prev => ({ ...prev, student_id: "" }));
  };

  const handleInputChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (formErrors[field]) {
      setFormErrors(prev => ({ ...prev, [field]: "" }));
    }
  };

  const validateForm = () => {
    const errors = {};

    if (!formData.student_id) {
      errors.student_id = "Veuillez sélectionner un étudiant";
    }

    if (!formData.fee_amount || parseFloat(formData.fee_amount) <= 0) {
      errors.fee_amount = "Le montant des frais de dossier doit être supérieur à 0";
    }

    if (formData.penalty_amount && parseFloat(formData.penalty_amount) < 0) {
      errors.penalty_amount = "Le montant de la pénalité ne peut pas être négatif";
    }

    if (!formData.payment_date) {
      errors.payment_date = "La date de paiement est requise";
    }

    if (formData.payment_method === "cheque" && !formData.reference_number) {
      errors.reference_number = "Le numéro de chèque est requis";
    }

    if (formData.payment_method === "transfer" && !formData.reference_number) {
      errors.reference_number = "Le numéro de virement est requis";
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    try {
      setLoading(true);
      setError("");
      setSuccess("");

      const data = await secureApi.makeRequest("/documentary-fees", {
        method: "POST",
        body: JSON.stringify(formData),
      });

      if (data.success) {
        setSuccess("Frais de dossier enregistré avec succès");
        setTimeout(() => {
          navigate("/payments/documentary-fees");
        }, 2000);
      } else {
        setError(data.message || "Erreur lors de l'enregistrement");
        if (data.errors) {
          setFormErrors(data.errors);
        }
      }
    } catch (err) {
      console.error("Error creating documentary fee:", err);
      setError("Erreur lors de l'enregistrement du frais");
    } finally {
      setLoading(false);
    }
  };

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
                Nouveau Frais de Dossier
              </h2>
              <p className="text-muted mb-0">
                Enregistrer un nouveau frais hors scolarité
              </p>
            </div>
          </div>
        </Col>
      </Row>

      {/* Messages */}
      {error && <Alert variant="danger" className="mb-3">{error}</Alert>}
      {success && <Alert variant="success" className="mb-3">{success}</Alert>}

      <Form onSubmit={handleSubmit}>
        <Row>
          <Col lg={8}>
            <Card className="mb-4">
              <Card.Header>
                <h5 className="mb-0">Informations du Frais</h5>
              </Card.Header>
              <Card.Body>
                {/* Recherche d'étudiant */}
                <Row>
                  <Col md={12}>
                    <Form.Group className="mb-3">
                      <Form.Label>Étudiant *</Form.Label>
                      {selectedStudent ? (
                        <div className="border rounded p-3 bg-light">
                          <div className="d-flex justify-content-between align-items-start">
                            <div>
                              <strong>{selectedStudent.first_name} {selectedStudent.last_name}</strong>
                              <br />
                              <small className="text-muted">
                                Matricule: {selectedStudent.student_number}
                              </small>
                              {selectedStudent.class_series?.school_class && (
                                <>
                                  <br />
                                  <small className="text-muted">
                                    Classe: {selectedStudent.class_series.school_class.name} - {selectedStudent.class_series.name}
                                  </small>
                                </>
                              )}
                            </div>
                            <Button
                              variant="outline-danger"
                              size="sm"
                              onClick={clearSelectedStudent}
                            >
                              Changer
                            </Button>
                          </div>
                        </div>
                      ) : (
                        <div>
                          <div className="position-relative">
                            <Form.Control
                              type="text"
                              placeholder="Rechercher un étudiant (nom, prénom, matricule...)"
                              value={searchTerm}
                              onChange={(e) => setSearchTerm(e.target.value)}
                              isInvalid={!!formErrors.student_id}
                            />
                            <div className="position-absolute end-0 top-50 translate-middle-y me-2">
                              {searchLoading ? (
                                <Spinner animation="border" size="sm" />
                              ) : (
                                <Search />
                              )}
                            </div>
                          </div>
                          
                          {searchResults.length > 0 && (
                            <div className="border rounded mt-1 bg-white position-relative" style={{ zIndex: 1000 }}>
                              {searchResults.map((student) => (
                                <div
                                  key={student.id}
                                  className="p-2 border-bottom cursor-pointer hover-bg-light"
                                  onClick={() => selectStudent(student)}
                                  style={{ cursor: "pointer" }}
                                  onMouseEnter={(e) => e.target.style.backgroundColor = "#f8f9fa"}
                                  onMouseLeave={(e) => e.target.style.backgroundColor = "white"}
                                >
                                  <div>
                                    <strong>{student.first_name} {student.last_name}</strong>
                                    <small className="text-muted ms-2">({student.student_number})</small>
                                  </div>
                                  {student.class_series?.school_class && (
                                    <small className="text-muted">
                                      {student.class_series.school_class.name } - {student.class_series.name}
                                    </small>
                                  )}
                                </div>
                              ))}
                            </div>
                          )}
                          
                          <Form.Control.Feedback type="invalid">
                            {formErrors.student_id}
                          </Form.Control.Feedback>
                        </div>
                      )}
                    </Form.Group>
                  </Col>
                </Row>

                <Row>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Frais de dossier (FCFA) *</Form.Label>
                      <Form.Control
                        type="number"
                        min="0"
                        step="1"
                        placeholder="Ex: 5000"
                        value={formData.fee_amount}
                        onChange={(e) => handleInputChange("fee_amount", e.target.value)}
                        isInvalid={!!formErrors.fee_amount}
                      />
                      <Form.Control.Feedback type="invalid">
                        {formErrors.fee_amount}
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
                        placeholder="Ex: 500 (optionnel)"
                        value={formData.penalty_amount}
                        onChange={(e) => handleInputChange("penalty_amount", e.target.value)}
                        isInvalid={!!formErrors.penalty_amount}
                      />
                      <Form.Control.Feedback type="invalid">
                        {formErrors.penalty_amount}
                      </Form.Control.Feedback>
                      <Form.Text className="text-muted">
                        Montant optionnel pour les retards ou autres pénalités
                      </Form.Text>
                    </Form.Group>
                  </Col>
                </Row>

                {/* Total calculation */}
                {(formData.fee_amount || formData.penalty_amount) && (
                  <Row className="mb-3">
                    <Col md={12}>
                      <div className="p-3 bg-light border rounded">
                        <div className="d-flex justify-content-between align-items-center">
                          <span className="fw-bold">Total à payer:</span>
                          <span className="h5 text-primary mb-0">
                            {formatCurrency(
                              (parseFloat(formData.fee_amount) || 0) + 
                              (parseFloat(formData.penalty_amount) || 0)
                            )}
                          </span>
                        </div>
                        {formData.penalty_amount && parseFloat(formData.penalty_amount) > 0 && (
                          <small className="text-muted">
                            Frais: {formatCurrency(parseFloat(formData.fee_amount) || 0)} + 
                            Pénalité: {formatCurrency(parseFloat(formData.penalty_amount) || 0)}
                          </small>
                        )}
                      </div>
                    </Col>
                  </Row>
                )}

                <Row>
                  <Col md={12}>
                    <Form.Group className="mb-3">
                      <Form.Label>Description</Form.Label>
                      <Form.Control
                        type="text"
                        placeholder="Description optionnelle du frais"
                        value={formData.description}
                        onChange={(e) => handleInputChange("description", e.target.value)}
                        isInvalid={!!formErrors.description}
                      />
                      <Form.Control.Feedback type="invalid">
                        {formErrors.description}
                      </Form.Control.Feedback>
                    </Form.Group>
                  </Col>
                </Row>
              </Card.Body>
            </Card>

            <Card className="mb-4">
              <Card.Header>
                <h5 className="mb-0">Informations de Paiement</h5>
              </Card.Header>
              <Card.Body>
                <Row>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Date de paiement *</Form.Label>
                      <Form.Control
                        type="date"
                        value={formData.payment_date}
                        onChange={(e) => handleInputChange("payment_date", e.target.value)}
                        isInvalid={!!formErrors.payment_date}
                      />
                      <Form.Control.Feedback type="invalid">
                        {formErrors.payment_date}
                      </Form.Control.Feedback>
                    </Form.Group>
                  </Col>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Date de versement</Form.Label>
                      <Form.Control
                        type="date"
                        value={formData.versement_date}
                        onChange={(e) => handleInputChange("versement_date", e.target.value)}
                        isInvalid={!!formErrors.versement_date}
                      />
                      <Form.Control.Feedback type="invalid">
                        {formErrors.versement_date}
                      </Form.Control.Feedback>
                    </Form.Group>
                  </Col>
                </Row>

                <Row>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Mode de paiement *</Form.Label>
                      <Form.Select
                        value={formData.payment_method}
                        onChange={(e) => handleInputChange("payment_method", e.target.value)}
                        isInvalid={!!formErrors.payment_method}
                      >
                        {paymentMethods.map(method => (
                          <option key={method.value} value={method.value}>
                            {method.label}
                          </option>
                        ))}
                      </Form.Select>
                      <Form.Control.Feedback type="invalid">
                        {formErrors.payment_method}
                      </Form.Control.Feedback>
                    </Form.Group>
                  </Col>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>
                        Numéro de référence
                        {(formData.payment_method === "cheque" || formData.payment_method === "transfer") && " *"}
                      </Form.Label>
                      <Form.Control
                        type="text"
                        placeholder={
                          formData.payment_method === "cheque" ? "Numéro de chèque" :
                          formData.payment_method === "transfer" ? "Numéro de virement" :
                          formData.payment_method === "mobile_money" ? "Numéro de transaction" :
                          "Référence optionnelle"
                        }
                        value={formData.reference_number}
                        onChange={(e) => handleInputChange("reference_number", e.target.value)}
                        isInvalid={!!formErrors.reference_number}
                      />
                      <Form.Control.Feedback type="invalid">
                        {formErrors.reference_number}
                      </Form.Control.Feedback>
                    </Form.Group>
                  </Col>
                </Row>

                <Row>
                  <Col md={12}>
                    <Form.Group className="mb-3">
                      <Form.Label>Notes</Form.Label>
                      <Form.Control
                        as="textarea"
                        rows={3}
                        placeholder="Notes optionnelles"
                        value={formData.notes}
                        onChange={(e) => handleInputChange("notes", e.target.value)}
                        isInvalid={!!formErrors.notes}
                      />
                      <Form.Control.Feedback type="invalid">
                        {formErrors.notes}
                      </Form.Control.Feedback>
                    </Form.Group>
                  </Col>
                </Row>
              </Card.Body>
            </Card>
          </Col>

          <Col lg={4}>
            <Card className="position-sticky" style={{ top: "20px" }}>
              <Card.Header>
                <h5 className="mb-0">Résumé</h5>
              </Card.Header>
              <Card.Body>
                {selectedStudent && (
                  <div className="mb-3">
                    <strong>Étudiant:</strong>
                    <br />
                    {selectedStudent.first_name} {selectedStudent.last_name}
                    <br />
                    <small className="text-muted">{selectedStudent.student_number}</small>
                  </div>
                )}

                <div className="mb-3">
                  <strong>Type:</strong>
                  <br />
                  Frais de dossier
                  {formData.penalty_amount && parseFloat(formData.penalty_amount) > 0 && " + Pénalité"}
                </div>

                {(formData.fee_amount || formData.penalty_amount) && (
                  <div className="mb-3">
                    <strong>Détail des montants:</strong>
                    <br />
                    {formData.fee_amount && (
                      <div>
                        Frais: <span className="text-primary fw-bold">
                          {formatCurrency(parseFloat(formData.fee_amount) || 0)}
                        </span>
                      </div>
                    )}
                    {formData.penalty_amount && parseFloat(formData.penalty_amount) > 0 && (
                      <div>
                        Pénalité: <span className="text-danger fw-bold">
                          {formatCurrency(parseFloat(formData.penalty_amount) || 0)}
                        </span>
                      </div>
                    )}
                    <hr className="my-2" />
                    <div>
                      <strong>Total: <span className="h5 text-primary">
                        {formatCurrency(
                          (parseFloat(formData.fee_amount) || 0) + 
                          (parseFloat(formData.penalty_amount) || 0)
                        )}
                      </span></strong>
                    </div>
                  </div>
                )}

                <div className="mb-3">
                  <strong>Mode de paiement:</strong>
                  <br />
                  {paymentMethods.find(m => m.value === formData.payment_method)?.label}
                </div>

                <div className="alert alert-success small">
                  <strong>✓ Statut :</strong> Ce frais sera automatiquement validé après création
                </div>

                <hr />

                <div className="d-grid gap-2">
                  <Button
                    type="submit"
                    variant="primary"
                    disabled={loading || !selectedStudent}
                  >
                    {loading ? (
                      <>
                        <Spinner animation="border" size="sm" className="me-2" />
                        Enregistrement...
                      </>
                    ) : (
                      <>
                        <Save className="me-1" />
                        Enregistrer
                      </>
                    )}
                  </Button>
                  
                  <Button
                    type="button"
                    variant="outline-secondary"
                    onClick={() => navigate("/payments/documentary-fees")}
                  >
                    Annuler
                  </Button>
                </div>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      </Form>
    </Container>
  );
};

export default CreateDocumentaryFee;