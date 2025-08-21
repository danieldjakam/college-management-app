import { useEffect, useState } from "react";
import {
  Alert,
  Badge,
  Button,
  Card,
  Col,
  Container,
  Form,
  Nav,
  Row,
  Spinner,
  Tab,
  Table,
} from "react-bootstrap";
import { Building, FileText, Printer, Search, CheckSquare } from "react-bootstrap-icons";
import { secureApiEndpoints } from "../../utils/apiMigration";
import SeriesCollectionSummary from "../Reports/SeriesCollectionSummary";

const PaymentReports = () => {
  const [payments, setPayments] = useState([]);
  const [stats, setStats] = useState({});
  const [schoolYear, setSchoolYear] = useState(null);
  const [availableSeries, setAvailableSeries] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const [filters, setFilters] = useState({
    start_date: "",
    end_date: "",
    series_id: "",
  });

  useEffect(() => {
    loadAvailableSeries();
    // Charger les données du jour par défaut
    const today = new Date().toISOString().split("T")[0];
    setFilters((prev) => ({
      ...prev,
      start_date: today,
      end_date: today,
    }));
    loadPaymentStats({ start_date: today, end_date: today });
  }, []);

  const loadAvailableSeries = async () => {
    try {
      // Récupérer les classes avec leurs séries
      const response = await secureApiEndpoints.accountant.getClasses();
      if (response.success) {
        const series = [];

        // La structure est: response.data.classes (array avec les relations)
        response.data.classes.forEach((schoolClass) => {
          if (schoolClass.series && schoolClass.series.length > 0) {
            schoolClass.series.forEach((serie) => {
              series.push({
                id: serie.id,
                name: `${schoolClass.name} - ${serie.name}`,
                class_name: schoolClass.name,
                series_name: serie.name,
              });
            });
          }
        });

        setAvailableSeries(series);
        console.log("Series loaded:", series); // Debug
      } else {
        console.error("Error loading classes:", response.message);
      }
    } catch (error) {
      console.error("Error loading series:", error);
    }
  };

  const loadPaymentStats = async (customFilters = null) => {
    try {
      setLoading(true);
      setError("");

      const queryFilters = customFilters || filters;
      const response = await secureApiEndpoints.payments.getStats(queryFilters);

      if (response.success) {
        setPayments(response.data.recent_payments || []);
        setStats(response.data);
        setSchoolYear(response.data.school_year);
      } else {
        setError(response.message);
      }
    } catch (error) {
      setError("Erreur lors du chargement des données");
      console.error("Error loading payment stats:", error);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (field, value) => {
    setFilters((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const handleSearch = () => {
    if (
      filters.start_date &&
      filters.end_date &&
      filters.start_date > filters.end_date
    ) {
      setError("La date de début doit être antérieure à la date de fin");
      return;
    }
    loadPaymentStats();
  };

  const handleExportPdf = () => {
    const printWindow = window.open("", "_blank");
    const html = generateReportHtml();

    printWindow.document.write(`
            <html>
                <head>
                    <title>État des Paiements</title>
                    <style>
                        @page {
                            size: A4;
                            margin: 1cm;
                        }
                        
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 0;
                            padding: 0;
                            font-size: 11px;
                            line-height: 1.3;
                        }
                        
                        table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            margin: 15px 0;
                            font-size: 10px;
                        }
                        
                        th, td { 
                            border: 1px solid #ddd; 
                            padding: 6px; 
                            text-align: left; 
                        }
                        
                        th { 
                            background-color: #f5f5f5; 
                            font-weight: bold; 
                            font-size: 10px;
                        }
                        
                        .text-center { text-align: center; }
                        .text-right { text-align: right; }
                        
                        .stats-grid { 
                            display: grid; 
                            grid-template-columns: repeat(2, 1fr); 
                            gap: 15px; 
                            margin: 15px 0; 
                        }
                        
                        .stat-card { 
                            border: 1px solid #ddd; 
                            padding: 10px; 
                            text-align: center;
                            font-size: 12px;
                        }
                        
                        .report-header {
                            text-align: center;
                            margin-bottom: 20px;
                            border-bottom: 2px solid #333;
                            padding-bottom: 15px;
                        }
                        
                        .report-header h1 {
                            font-size: 16px;
                            margin: 5px 0;
                        }
                        
                        .report-header h2 {
                            font-size: 14px;
                            margin: 5px 0;
                        }
                        
                        .report-header p {
                            font-size: 11px;
                            margin: 3px 0;
                        }
                        
                        @media print { 
                            body { 
                                margin: 0; 
                                -webkit-print-color-adjust: exact;
                                print-color-adjust: exact;
                            }
                            .no-print { 
                                display: none !important; 
                            }
                        }
                        
                        @media screen {
                            body {
                                background: #f0f0f0;
                                padding: 20px;
                            }
                            
                            .report-container {
                                background: white;
                                max-width: 210mm;
                                margin: 0 auto;
                                padding: 20px;
                                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="report-container">
                        ${html}
                    </div>
                    <div class="no-print" style="text-align: center; margin-top: 30px; background: white; padding: 20px;">
                        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">📄 Imprimer Rapport</button>
                        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">✖️ Fermer</button>
                    </div>
                </body>
            </html>
        `);
    printWindow.document.close();
  };

  const generateReportHtml = () => {
    const selectedSeries = availableSeries.find(
      (s) => s.id == filters.series_id
    );
    const periodText =
      filters.start_date && filters.end_date
        ? `Du ${new Date(filters.start_date).toLocaleDateString(
            "fr-FR"
          )} au ${new Date(filters.end_date).toLocaleDateString("fr-FR")}`
        : "Toutes les périodes";

    return `
            <div class="report">
                <div class="report-header text-center" style="margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px;">
                    <h1>COLLÈGE POLYVALENT BILINGUE DE DOUALA</h1>
                    <h2>État des Paiements</h2>
                    <p><strong>Année scolaire:</strong> ${
                      schoolYear?.name || "N/A"
                    }</p>
                    <p><strong>Période:</strong> ${periodText}</p>
                    ${
                      selectedSeries
                        ? `<p><strong>Série:</strong> ${selectedSeries.name}</p>`
                        : "<p><strong>Série:</strong> Toutes les séries</p>"
                    }
                    <p><strong>Généré le:</strong> ${new Date().toLocaleDateString(
                      "fr-FR"
                    )} à ${new Date().toLocaleTimeString("fr-FR")}</p>
                </div>
                
                <div class="stats-section">
                    <h3>Résumé des Statistiques</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h4>Total des Paiements</h4>
                            <p style="font-size: 24px; color: #007bff;">${
                              stats.total_payments || 0
                            }</p>
                        </div>
                        <div class="stat-card">
                            <h4>Montant Total</h4>
                            <p style="font-size: 24px; color: #28a745;">${parseInt(
                              stats.total_amount || 0
                            ).toLocaleString()} FCFA</p>
                        </div>
                    </div>
                </div>
                
                <div class="payments-section">
                    <h3>Détail des Paiements</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reçu N°</th>
                                <th>Étudiant</th>
                                <th>Classe</th>
                                <th>Montant</th>
                                <th>Mode</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(payments || [])
                              .map(
                                (payment) => `
                                <tr>
                                    <td>${payment.date || "N/A"}</td>
                                    <td>${payment.receipt_number || "N/A"}</td>
                                    <td>${payment.student_name || "N/A"}</td>
                                    <td>${payment.class || "N/A"}</td>
                                    <td class="text-right">${parseInt(
                                      payment.amount || 0
                                    ).toLocaleString()} FCFA</td>
                                    <td>${payment.method || "N/A"}</td>
                                </tr>
                            `
                              )
                              .join("")}
                        </tbody>
                    </table>
                </div>
                
                ${
                  Object.keys(stats.by_tranche || {}).length > 0
                    ? `
                <div class="tranches-section">
                    <h3>Répartition par Tranche</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Tranche</th>
                                <th>Nombre de Paiements</th>
                                <th>Montant Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${Object.entries(stats.by_tranche)
                              .map(
                                ([tranche, data]) => `
                                <tr>
                                    <td>${tranche}</td>
                                    <td class="text-center">${data.count}</td>
                                    <td class="text-right">${parseInt(
                                      data.amount
                                    ).toLocaleString()} FCFA</td>
                                </tr>
                            `
                              )
                              .join("")}
                        </tbody>
                    </table>
                </div>
                `
                    : ""
                }
            </div>
        `;
  };

  const formatAmount = (amount) => {
    return parseInt(amount).toLocaleString() + " FCFA";
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("fr-FR");
  };

  const getPaymentMethodLabel = (method, isRamePhysical = false) => {
    if (isRamePhysical) {
      return "Rame Physique";
    }
    const methods = {
      cash: "Banque",
      card: "Carte",
      transfer: "Virement",
      check: "Chèque",
    };
    return methods[method] || method;
  };

  return (
    <Container fluid className="py-4">
      {/* Header */}
      <Row className="mb-4">
        <Col>
          <h2>États des Paiements</h2>
          <p className="text-muted">
            Consultez et exportez les rapports de paiements
          </p>
        </Col>
      </Row>

      {/* Tabs */}
      <Tab.Container defaultActiveKey="series-summary">
        <Nav variant="pills" className="mb-4">
          <Nav.Item>
            <Nav.Link eventKey="series-summary">
              <Building className="me-2" size={16} />
              Récap par Série
            </Nav.Link>
          </Nav.Item>
          <Nav.Item>
            <Nav.Link eventKey="detailed-payments">
              <FileText className="me-2" size={16} />
              Paiements Détaillés
            </Nav.Link>
          </Nav.Item>
          <Nav.Item>
            <Nav.Link eventKey="payment-listing">
              <Printer className="me-2" size={16} />
              Listing des Paiements
            </Nav.Link>
          </Nav.Item>
          <Nav.Item>
            <Nav.Link eventKey="tranche-lists">
              <CheckSquare className="me-2" size={16} />
              Listes par Tranches
            </Nav.Link>
          </Nav.Item>
        </Nav>

        <Tab.Content>
          <Tab.Pane eventKey="series-summary">
            <SeriesCollectionSummary />
          </Tab.Pane>

          <Tab.Pane eventKey="detailed-payments">
            {/* Contenu existant des paiements détaillés */}

            {/* Filters */}
            <Card className="mb-4">
              <Card.Header>
                <h5 className="mb-0">Filtres de Recherche</h5>
              </Card.Header>
              <Card.Body>
                <Row>
                  <Col md={3}>
                    <Form.Group className="mb-3">
                      <Form.Label>Date de début</Form.Label>
                      <Form.Control
                        type="date"
                        value={filters.start_date}
                        onChange={(e) =>
                          handleFilterChange("start_date", e.target.value)
                        }
                      />
                    </Form.Group>
                  </Col>
                  <Col md={3}>
                    <Form.Group className="mb-3">
                      <Form.Label>Date de fin</Form.Label>
                      <Form.Control
                        type="date"
                        value={filters.end_date}
                        onChange={(e) =>
                          handleFilterChange("end_date", e.target.value)
                        }
                      />
                    </Form.Group>
                  </Col>
                  <Col md={4}>
                    <Form.Group className="mb-3">
                      <Form.Label>Série (optionnel)</Form.Label>
                      <Form.Select
                        value={filters.series_id}
                        onChange={(e) =>
                          handleFilterChange("series_id", e.target.value)
                        }
                      >
                        <option value="">Toutes les séries</option>
                        {availableSeries.map((series) => (
                          <option key={series.id} value={series.id}>
                            {series.name}
                          </option>
                        ))}
                      </Form.Select>
                    </Form.Group>
                  </Col>
                  <Col md={2} className="d-flex align-items-end">
                    <div className="d-flex gap-2 mb-3">
                      <Button
                        variant="primary"
                        onClick={handleSearch}
                        disabled={loading}
                      >
                        {loading ? (
                          <Spinner animation="border" size="sm" />
                        ) : (
                          <Search size={16} />
                        )}
                      </Button>
                      {payments && payments.length > 0 && (
                        <Button variant="success" onClick={handleExportPdf}>
                          <Printer size={16} />
                        </Button>
                      )}
                    </div>
                  </Col>
                </Row>
              </Card.Body>
            </Card>

            {/* Error Alert */}
            {error && (
              <Alert variant="danger" dismissible onClose={() => setError("")}>
                {error}
              </Alert>
            )}

            {/* Statistics Cards */}
            {Object.keys(stats).length > 0 && (
              <Row className="mb-4">
                <Col md={3}>
                  <Card className="text-center">
                    <Card.Body>
                      <h3 className="text-primary">{stats.total_payments}</h3>
                      <p className="text-muted mb-0">Total Paiements</p>
                    </Card.Body>
                  </Card>
                </Col>
                <Col md={3}>
                  <Card className="text-center">
                    <Card.Body>
                      <h3 className="text-success">
                        {formatAmount(stats.total_amount || 0)}
                      </h3>
                      <p className="text-muted mb-0">Montant Total</p>
                    </Card.Body>
                  </Card>
                </Col>
                <Col md={3}>
                  <Card className="text-center">
                    <Card.Body>
                      <h3 className="text-info">
                        {Object.keys(stats.by_method || {}).length}
                      </h3>
                      <p className="text-muted mb-0">Modes de Paiement</p>
                    </Card.Body>
                  </Card>
                </Col>
                <Col md={3}>
                  <Card className="text-center">
                    <Card.Body>
                      <h3 className="text-warning">
                        {Object.keys(stats.by_tranche || {}).length}
                      </h3>
                      <p className="text-muted mb-0">Tranches Touchées</p>
                    </Card.Body>
                  </Card>
                </Col>
              </Row>
            )}

            {/* Payment Details */}
            <Card>
              <Card.Header className="d-flex justify-content-between align-items-center">
                <h5 className="mb-0">Détail des Paiements</h5>
                {payments && payments.length > 0 && (
                  <Badge bg="info">{payments.length} paiement(s)</Badge>
                )}
              </Card.Header>
              <Card.Body>
                {loading ? (
                  <div className="text-center py-4">
                    <Spinner animation="border" role="status">
                      <span className="visually-hidden">Chargement...</span>
                    </Spinner>
                  </div>
                ) : !payments || payments.length === 0 ? (
                  <p className="text-muted text-center py-4">
                    Aucun paiement trouvé pour les critères sélectionnés
                  </p>
                ) : (
                  <Table responsive hover>
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Reçu N°</th>
                        <th>Étudiant</th>
                        <th>Classe</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Tranches Affectées</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(payments || []).map((payment) => (
                        <tr key={payment.id}>
                          <td>{formatDate(payment.payment_date)}</td>
                          <td>
                            <code>{payment.receipt_number || "N/A"}</code>
                          </td>
                          <td>{payment.student_name || "N/A"}</td>
                          <td>{payment.class || "N/A"}</td>
                          <td className="text-end">
                            <strong>{formatAmount(payment.amount)}</strong>
                          </td>
                          <td>
                            <Badge
                              bg={
                                payment.is_rame_physical ? "info" : "secondary"
                              }
                            >
                              {getPaymentMethodLabel(
                                payment.payment_method,
                                payment.is_rame_physical
                              )}
                            </Badge>
                          </td>
                          <td>
                            {payment.payment_details?.map((detail) => (
                              <Badge
                                key={detail.id}
                                bg="primary"
                                className="me-1 mb-1"
                              >
                                {detail.payment_tranche.name}:{" "}
                                {formatAmount(detail.amount_allocated)}
                              </Badge>
                            )) || <span className="text-muted">-</span>}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                )}
              </Card.Body>
            </Card>

            {/* Statistics by Tranche */}
            {Object.keys(stats.by_tranche || {}).length > 0 && (
              <Card className="mt-4">
                <Card.Header>
                  <h5 className="mb-0">Répartition par Tranche de Paiement</h5>
                </Card.Header>
                <Card.Body>
                  <Table responsive>
                    <thead>
                      <tr>
                        <th>Tranche</th>
                        <th>Nombre de Paiements</th>
                        <th>Montant Total</th>
                        <th>Pourcentage</th>
                      </tr>
                    </thead>
                    <tbody>
                      {Object.entries(stats.by_tranche).map(
                        ([tranche, data]) => (
                          <tr key={tranche}>
                            <td>{tranche}</td>
                            <td>{data.count}</td>
                            <td className="text-end">
                              <strong>{formatAmount(data.amount)}</strong>
                            </td>
                            <td>
                              <Badge bg="info">
                                {(
                                  (data.amount / stats.total_amount) *
                                  100
                                ).toFixed(1)}
                                %
                              </Badge>
                            </td>
                          </tr>
                        )
                      )}
                    </tbody>
                  </Table>
                </Card.Body>
              </Card>
            )}
          </Tab.Pane>

          <Tab.Pane eventKey="payment-listing">
            <PaymentListingReport />
          </Tab.Pane>

          <Tab.Pane eventKey="tranche-lists">
            <TrancheListsReport />
          </Tab.Pane>
        </Tab.Content>
      </Tab.Container>
    </Container>
  );
};

// Composant pour le listing des paiements
const PaymentListingReport = () => {
  const [listingData, setListingData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [classes, setClasses] = useState([]);

  const [listingFilters, setListingFilters] = useState({
    start_date: new Date().toISOString().split("T")[0],
    end_date: new Date().toISOString().split("T")[0],
    class_id: "",
  });

  useEffect(() => {
    loadClasses();
  }, []);

  const loadClasses = async () => {
    try {
      const response = await secureApiEndpoints.accountant.getClasses();
      if (response.success && response.data && response.data.classes) {
        // L'API retourne: { success: true, data: { classes: [...], grouped_classes: {...} } }
        setClasses(response.data.classes);
      } else {
        setClasses([]);
      }
    } catch (error) {
      console.error("Erreur lors du chargement des classes:", error);
      setClasses([]); // Assurer que classes est toujours un tableau
    }
  };

  const generateListing = async () => {
    try {
      setLoading(true);
      setError("");

      const response = await secureApiEndpoints.payments.generateListingReport({
        ...listingFilters,
        format: "html"
      });

      if (response.success) {
        setListingData(response.data);
      } else {
        setError(response.message || "Erreur lors de la génération du listing");
      }
    } catch (error) {
      setError("Erreur lors de la génération du listing");
      console.error("Erreur:", error);
    } finally {
      setLoading(false);
    }
  };

  const downloadPDF = async () => {
    try {
      setLoading(true);
      await secureApiEndpoints.payments.downloadListingReportPDF(listingFilters);
    } catch (error) {
      setError("Erreur lors du téléchargement PDF");
      console.error("Erreur:", error);
    } finally {
      setLoading(false);
    }
  };

  const printReport = () => {
    if (listingData?.html) {
      const printWindow = window.open("", "_blank");
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Listing des Paiements</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
            @media print { 
              body { margin: 0; }
              .no-print { display: none !important; }
            }
          </style>
        </head>
        <body>
          ${listingData.html}
        </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
  };

  return (
    <div>
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0">Paramètres du Listing</h5>
        </Card.Header>
        <Card.Body>
          <Row>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Date de début</Form.Label>
                <Form.Control
                  type="date"
                  value={listingFilters.start_date}
                  onChange={(e) =>
                    setListingFilters(prev => ({
                      ...prev,
                      start_date: e.target.value
                    }))
                  }
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Date de fin</Form.Label>
                <Form.Control
                  type="date"
                  value={listingFilters.end_date}
                  onChange={(e) =>
                    setListingFilters(prev => ({
                      ...prev,
                      end_date: e.target.value
                    }))
                  }
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Classe (optionnel)</Form.Label>
                <Form.Select
                  value={listingFilters.class_id}
                  onChange={(e) =>
                    setListingFilters(prev => ({
                      ...prev,
                      class_id: e.target.value
                    }))
                  }
                >
                  <option value="">Toutes les classes</option>
                  {classes.map((classe) => (
                    <option key={classe.id} value={classe.id}>
                      {classe.name}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>&nbsp;</Form.Label>
                <div className="d-grid gap-2">
                  <Button
                    variant="primary"
                    onClick={generateListing}
                    disabled={loading}
                  >
                    {loading ? (
                      <Spinner animation="border" size="sm" />
                    ) : (
                      <>
                        <Search className="me-2" size={16} />
                        Générer
                      </>
                    )}
                  </Button>
                </div>
              </Form.Group>
            </Col>
          </Row>

          {listingData && (
            <Row className="mt-3">
              <Col>
                <div className="d-flex gap-2">
                  <Button variant="success" onClick={downloadPDF} disabled={loading}>
                    <FileText className="me-2" size={16} />
                    Télécharger PDF
                  </Button>
                  <Button variant="info" onClick={printReport}>
                    <Printer className="me-2" size={16} />
                    Imprimer
                  </Button>
                </div>
              </Col>
            </Row>
          )}
        </Card.Body>
      </Card>

      {error && (
        <Alert variant="danger" className="mb-4">
          {error}
        </Alert>
      )}

      {listingData && (
        <Card>
          <Card.Header className="d-flex justify-content-between align-items-center">
            <h5 className="mb-0">Résultats du Listing</h5>
            <div>
              <Badge bg="primary" className="me-2">
                {listingData.summary.total_students} étudiant(s)
              </Badge>
              <Badge bg="success">
                {new Intl.NumberFormat('fr-FR').format(listingData.summary.total_amount)} FCFA
              </Badge>
            </div>
          </Card.Header>
          <Card.Body>
            <div 
              dangerouslySetInnerHTML={{ __html: listingData.html }}
              style={{ 
                maxHeight: '600px', 
                overflowY: 'auto',
                border: '1px solid #dee2e6',
                padding: '15px'
              }}
            />
          </Card.Body>
        </Card>
      )}
    </div>
  );
};

// Composant pour les listes de tranches
const TrancheListsReport = () => {
  const [listingData, setListingData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [classes, setClasses] = useState([]);
  const [series, setSeries] = useState([]);
  const [tranches, setTranches] = useState([]);

  const [filters, setFilters] = useState({
    start_date: new Date().toISOString().split("T")[0],
    end_date: new Date().toISOString().split("T")[0],
    class_id: "",
    series_id: "",
    tranche_ids: []
  });

  useEffect(() => {
    loadClasses();
    loadTranches();
  }, []);

  // Charger les séries quand une classe est sélectionnée
  useEffect(() => {
    if (filters.class_id) {
      loadSeriesForClass(filters.class_id);
    } else {
      setSeries([]);
      setFilters(prev => ({ ...prev, series_id: "" }));
    }
  }, [filters.class_id]);

  const loadClasses = async () => {
    try {
      const response = await secureApiEndpoints.accountant.getClasses();
      if (response.success && response.data && response.data.classes) {
        setClasses(response.data.classes);
      } else {
        setClasses([]);
      }
    } catch (error) {
      console.error("Erreur lors du chargement des classes:", error);
      setClasses([]);
    }
  };

  const loadSeriesForClass = async (classId) => {
    try {
      const response = await secureApiEndpoints.accountant.getClasses();
      if (response.success && response.data && response.data.classes) {
        const selectedClass = response.data.classes.find(cls => cls.id == classId);
        if (selectedClass && selectedClass.series) {
          setSeries(selectedClass.series);
        } else {
          setSeries([]);
        }
      }
    } catch (error) {
      console.error("Erreur lors du chargement des séries:", error);
      setSeries([]);
    }
  };

  const loadTranches = async () => {
    try {
      const response = await secureApiEndpoints.payments.getTranches();
      if (response.success && response.data) {
        setTranches(response.data);
        // Sélectionner toutes les tranches par défaut
        setFilters(prev => ({
          ...prev,
          tranche_ids: response.data.map(t => t.id)
        }));
      } else {
        setTranches([]);
      }
    } catch (error) {
      console.error("Erreur lors du chargement des tranches:", error);
      setTranches([]);
    }
  };

  const handleTrancheToggle = (trancheId) => {
    setFilters(prev => ({
      ...prev,
      tranche_ids: prev.tranche_ids.includes(trancheId)
        ? prev.tranche_ids.filter(id => id !== trancheId)
        : [...prev.tranche_ids, trancheId]
    }));
  };

  const selectAllTranches = () => {
    setFilters(prev => ({
      ...prev,
      tranche_ids: tranches.map(t => t.id)
    }));
  };

  const deselectAllTranches = () => {
    setFilters(prev => ({
      ...prev,
      tranche_ids: []
    }));
  };

  const generateListing = async () => {
    if (filters.tranche_ids.length === 0) {
      setError("Veuillez sélectionner au moins une tranche");
      return;
    }

    try {
      setLoading(true);
      setError("");

      const response = await secureApiEndpoints.payments.generateTrancheListsReport({
        ...filters,
        format: "html"
      });

      if (response.success) {
        setListingData(response.data);
      } else {
        setError(response.message || "Erreur lors de la génération du listing");
      }
    } catch (error) {
      setError("Erreur lors de la génération du listing");
      console.error("Erreur:", error);
    } finally {
      setLoading(false);
    }
  };

  const downloadPDF = async () => {
    if (filters.tranche_ids.length === 0) {
      setError("Veuillez sélectionner au moins une tranche");
      return;
    }

    try {
      setLoading(true);
      await secureApiEndpoints.payments.downloadTrancheListsReportPDF(filters);
    } catch (error) {
      setError("Erreur lors du téléchargement PDF");
      console.error("Erreur:", error);
    } finally {
      setLoading(false);
    }
  };

  const printReport = () => {
    if (listingData?.html) {
      const printWindow = window.open("", "_blank");
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Listes des Tranches</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
            @media print { 
              body { margin: 0; }
              .no-print { display: none !important; }
            }
          </style>
        </head>
        <body>
          ${listingData.html}
        </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
  };

  return (
    <div>
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0">Paramètres des Listes par Tranches</h5>
        </Card.Header>
        <Card.Body>
          <Row>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Date de début</Form.Label>
                <Form.Control
                  type="date"
                  value={filters.start_date}
                  onChange={(e) =>
                    setFilters(prev => ({
                      ...prev,
                      start_date: e.target.value
                    }))
                  }
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Date de fin</Form.Label>
                <Form.Control
                  type="date"
                  value={filters.end_date}
                  onChange={(e) =>
                    setFilters(prev => ({
                      ...prev,
                      end_date: e.target.value
                    }))
                  }
                />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Classe (optionnel)</Form.Label>
                <Form.Select
                  value={filters.class_id}
                  onChange={(e) =>
                    setFilters(prev => ({
                      ...prev,
                      class_id: e.target.value
                    }))
                  }
                >
                  <option value="">Toutes les classes</option>
                  {classes.map((classe) => (
                    <option key={classe.id} value={classe.id}>
                      {classe.name}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group className="mb-3">
                <Form.Label>Série (optionnel)</Form.Label>
                <Form.Select
                  value={filters.series_id}
                  onChange={(e) =>
                    setFilters(prev => ({
                      ...prev,
                      series_id: e.target.value
                    }))
                  }
                  disabled={!filters.class_id}
                >
                  <option value="">Toutes les séries</option>
                  {series.map((serie) => (
                    <option key={serie.id} value={serie.id}>
                      {serie.name}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
          </Row>
          
          <Row>
            <Col md={12}>
              <Form.Group className="mb-3">
                <div className="d-grid gap-2" style={{ maxWidth: '200px' }}>
                  <Button
                    variant="primary"
                    onClick={generateListing}
                    disabled={loading || filters.tranche_ids.length === 0}
                  >
                    {loading ? (
                      <Spinner animation="border" size="sm" />
                    ) : (
                      <>
                        <Search className="me-2" size={16} />
                        Générer
                      </>
                    )}
                  </Button>
                </div>
              </Form.Group>
            </Col>
          </Row>

          {/* Sélection des tranches */}
          <Row>
            <Col>
              <Form.Group className="mb-3">
                <div className="d-flex justify-content-between align-items-center mb-2">
                  <Form.Label><strong>Tranches à inclure :</strong> <small className="text-muted">(Seuls les étudiants ayant payé ces tranches dans la période seront affichés)</small></Form.Label>
                  <div>
                    <Button variant="outline-primary" size="sm" onClick={selectAllTranches} className="me-2">
                      Tout sélectionner
                    </Button>
                    <Button variant="outline-secondary" size="sm" onClick={deselectAllTranches}>
                      Tout désélectionner
                    </Button>
                  </div>
                </div>
                <div style={{ maxHeight: '150px', overflowY: 'auto', border: '1px solid #dee2e6', padding: '10px', borderRadius: '5px' }}>
                  <Row>
                    {tranches.map((tranche) => (
                      <Col md={4} key={tranche.id} className="mb-2">
                        <Form.Check
                          type="checkbox"
                          id={`tranche-${tranche.id}`}
                          label={tranche.name}
                          checked={filters.tranche_ids.includes(tranche.id)}
                          onChange={() => handleTrancheToggle(tranche.id)}
                        />
                      </Col>
                    ))}
                  </Row>
                </div>
                {filters.tranche_ids.length > 0 && (
                  <div className="mt-2">
                    <Badge bg="info">
                      {filters.tranche_ids.length} tranche(s) sélectionnée(s)
                    </Badge>
                  </div>
                )}
              </Form.Group>
            </Col>
          </Row>

          {listingData && (
            <Row className="mt-3">
              <Col>
                <div className="d-flex gap-2">
                  <Button variant="success" onClick={downloadPDF} disabled={loading}>
                    <FileText className="me-2" size={16} />
                    Télécharger PDF
                  </Button>
                  <Button variant="info" onClick={printReport}>
                    <Printer className="me-2" size={16} />
                    Imprimer
                  </Button>
                </div>
              </Col>
            </Row>
          )}
        </Card.Body>
      </Card>

      {error && (
        <Alert variant="danger" className="mb-4">
          {error}
        </Alert>
      )}

      {listingData && (
        <Card>
          <Card.Header className="d-flex justify-content-between align-items-center">
            <h5 className="mb-0">Résultats du Listing par Tranches</h5>
            <div>
              <Badge bg="primary" className="me-2">
                {listingData.summary.total_students} étudiant(s)
              </Badge>
              <Badge bg="success">
                {listingData.summary.selected_tranches.join(', ')}
              </Badge>
            </div>
          </Card.Header>
          <Card.Body>
            <div 
              dangerouslySetInnerHTML={{ __html: listingData.html }}
              style={{ 
                maxHeight: '600px', 
                overflowY: 'auto',
                border: '1px solid #dee2e6',
                padding: '15px'
              }}
            />
          </Card.Body>
        </Card>
      )}
    </div>
  );
};

export default PaymentReports;
