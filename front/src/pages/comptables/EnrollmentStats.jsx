import React, { useState, useEffect } from 'react';
import {
  Container, Row, Col, Card, Table, Spinner, Alert, Badge, ProgressBar
} from 'react-bootstrap';
import { PeopleFill, PersonPlusFill, PersonCheckFill, PrinterFill } from 'react-bootstrap-icons';
import { Button } from 'react-bootstrap';
import { secureApiEndpoints } from '../../utils/apiMigration';

function EnrollmentStats() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await secureApiEndpoints.students.enrollmentStats();
      if (response.success) {
        setData(response.data);
      } else {
        setError(response.message || 'Erreur lors du chargement');
      }
    } catch (err) {
      setError(err.response?.data?.message || err.message || 'Erreur de connexion');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <Container fluid className="py-4 text-center">
        <Spinner animation="border" variant="primary" />
        <p className="mt-2 text-muted">Chargement des statistiques...</p>
      </Container>
    );
  }

  if (error) {
    return (
      <Container fluid className="py-4">
        <Alert variant="danger">{error}</Alert>
      </Container>
    );
  }

  if (!data) return null;

  const { global, by_class, school_year } = data;
  const pctNouveaux = global.total > 0 ? ((global.nouveaux / global.total) * 100).toFixed(1) : 0;
  const pctAnciens = global.total > 0 ? ((global.anciens / global.total) * 100).toFixed(1) : 0;

  const handlePrint = () => {
    const printWindow = window.open('', '_blank');
    const today = new Date().toLocaleDateString('fr-FR');
    const rows = by_class.map(row => {
      const pA = row.total > 0 ? ((row.anciens / row.total) * 100).toFixed(1) : 0;
      const pN = row.total > 0 ? ((row.nouveaux / row.total) * 100).toFixed(1) : 0;
      return `<tr>
        <td style="font-weight:bold;">${row.class_name} <span style="color:#666;">${row.series_name}</span></td>
        <td style="text-align:center;">${row.anciens}</td>
        <td style="text-align:center;">${row.nouveaux}</td>
        <td style="text-align:center;font-weight:bold;">${row.total}</td>
        <td style="text-align:center;">${pA}%</td>
        <td style="text-align:center;">${pN}%</td>
      </tr>`;
    }).join('');

    printWindow.document.write(`<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Etat des Inscriptions - ${school_year}</title>
  <style>
    @page { margin: 10mm 15mm; size: A4 portrait; }
    body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 5mm; }
    .header h2 { margin: 0; font-size: 14pt; color: #009B3A; }
    .header h3 { margin: 2mm 0; font-size: 12pt; }
    .header p { margin: 1mm 0; font-size: 9pt; color: #666; }
    .separator { height: 2px; background: linear-gradient(to right, #009B3A 33%, #CE1126 33%, #CE1126 66%, #FCD116 66%); margin: 4mm 30mm; }
    .summary { display: flex; justify-content: space-around; margin: 6mm 0; }
    .summary-box { text-align: center; padding: 3mm 8mm; border: 1px solid #ddd; border-radius: 3mm; min-width: 60mm; }
    .summary-box .label { font-size: 9pt; color: #666; }
    .summary-box .value { font-size: 20pt; font-weight: bold; }
    .summary-box .pct { font-size: 9pt; }
    .total .value { color: #0d6efd; }
    .anciens .value { color: #198754; }
    .anciens .pct { color: #198754; }
    .nouveaux .value { color: #fd7e14; }
    .nouveaux .pct { color: #fd7e14; }
    table { width: 100%; border-collapse: collapse; margin-top: 5mm; }
    th { background-color: #2c3e50; color: white; padding: 2.5mm 3mm; font-size: 9pt; text-align: center; }
    th:first-child { text-align: left; }
    td { padding: 2mm 3mm; border-bottom: 0.5px solid #ddd; font-size: 9.5pt; }
    tr:nth-child(even) { background-color: #f8f9fa; }
    .table-title { font-size: 11pt; font-weight: bold; margin-top: 6mm; margin-bottom: 2mm; }
    .footer { text-align: center; margin-top: 8mm; font-size: 8pt; color: #999; border-top: 1px solid #ddd; padding-top: 2mm; }
    .bar-container { display: flex; height: 5mm; border-radius: 2mm; overflow: hidden; background: #eee; }
    .bar-green { background-color: #198754; }
    .bar-orange { background-color: #fd7e14; }
  </style>
</head>
<body>
  <div class="header">
    <h2>COLLEGE POLYVALENT BILINGUE DE DOUALA</h2>
    <div class="separator"></div>
    <h3>ETAT DES INSCRIPTIONS</h3>
    <p>Annee scolaire : ${school_year}</p>
  </div>

  <div class="summary">
    <div class="summary-box total">
      <div class="label">Total Eleves</div>
      <div class="value">${global.total}</div>
    </div>
    <div class="summary-box anciens">
      <div class="label">Anciens Eleves</div>
      <div class="value">${global.anciens}</div>
      <div class="pct">${pctAnciens}%</div>
    </div>
    <div class="summary-box nouveaux">
      <div class="label">Nouveaux Eleves</div>
      <div class="value">${global.nouveaux}</div>
      <div class="pct">${pctNouveaux}%</div>
    </div>
  </div>

  <div class="bar-container" style="margin: 0 20mm;">
    <div class="bar-green" style="width: ${pctAnciens}%;"></div>
    <div class="bar-orange" style="width: ${pctNouveaux}%;"></div>
  </div>
  <div style="display:flex;justify-content:space-between;margin:1mm 20mm 0;font-size:8pt;color:#666;">
    <span>Anciens: ${pctAnciens}%</span>
    <span>Nouveaux: ${pctNouveaux}%</span>
  </div>

  <div class="table-title">Repartition par classe</div>
  <table>
    <thead>
      <tr>
        <th>Classe</th>
        <th>Anciens</th>
        <th>Nouveaux</th>
        <th>Total</th>
        <th>% Anciens</th>
        <th>% Nouveaux</th>
      </tr>
    </thead>
    <tbody>
      ${rows}
      <tr style="font-weight:bold;background-color:#e9ecef;border-top:2px solid #333;">
        <td>TOTAL</td>
        <td style="text-align:center;">${global.anciens}</td>
        <td style="text-align:center;">${global.nouveaux}</td>
        <td style="text-align:center;">${global.total}</td>
        <td style="text-align:center;">${pctAnciens}%</td>
        <td style="text-align:center;">${pctNouveaux}%</td>
      </tr>
    </tbody>
  </table>

  <div class="footer">
    Imprime le ${today} - College Polyvalent Bilingue de Douala
  </div>
</body>
</html>`);
    printWindow.document.close();
    setTimeout(() => printWindow.print(), 500);
  };

  return (
    <Container fluid className="py-4">
      <div className="d-flex justify-content-between align-items-start mb-1">
        <div>
          <h4 className="mb-1">
            <PeopleFill className="me-2" />
            Statistiques des inscriptions
          </h4>
          <p className="text-muted mb-4">
            Repartition des eleves anciens et nouveaux - Annee scolaire <strong>{school_year}</strong>
          </p>
        </div>
        <Button variant="outline-primary" onClick={handlePrint}>
          <PrinterFill className="me-2" />
          Imprimer PDF
        </Button>
      </div>

      {/* Global Stats Cards */}
      <Row className="mb-4 g-3">
        <Col md={4}>
          <Card className="shadow-sm border-0 h-100" style={{ borderLeft: '4px solid #0d6efd' }}>
            <Card.Body className="d-flex align-items-center">
              <div className="rounded-circle p-3 me-3" style={{ backgroundColor: '#e7f1ff' }}>
                <PeopleFill size={28} color="#0d6efd" />
              </div>
              <div>
                <div className="text-muted" style={{ fontSize: '0.85rem' }}>Total Eleves</div>
                <div className="fw-bold" style={{ fontSize: '1.8rem', lineHeight: 1 }}>{global.total}</div>
              </div>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4}>
          <Card className="shadow-sm border-0 h-100" style={{ borderLeft: '4px solid #198754' }}>
            <Card.Body className="d-flex align-items-center">
              <div className="rounded-circle p-3 me-3" style={{ backgroundColor: '#e8f5e9' }}>
                <PersonCheckFill size={28} color="#198754" />
              </div>
              <div>
                <div className="text-muted" style={{ fontSize: '0.85rem' }}>Anciens Eleves</div>
                <div className="fw-bold" style={{ fontSize: '1.8rem', lineHeight: 1 }}>{global.anciens}</div>
                <small className="text-success">{pctAnciens}%</small>
              </div>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4}>
          <Card className="shadow-sm border-0 h-100" style={{ borderLeft: '4px solid #fd7e14' }}>
            <Card.Body className="d-flex align-items-center">
              <div className="rounded-circle p-3 me-3" style={{ backgroundColor: '#fff3e0' }}>
                <PersonPlusFill size={28} color="#fd7e14" />
              </div>
              <div>
                <div className="text-muted" style={{ fontSize: '0.85rem' }}>Nouveaux Eleves</div>
                <div className="fw-bold" style={{ fontSize: '1.8rem', lineHeight: 1 }}>{global.nouveaux}</div>
                <small className="text-warning">{pctNouveaux}%</small>
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Global Progress Bar */}
      <Card className="shadow-sm mb-4">
        <Card.Body>
          <div className="d-flex justify-content-between mb-2">
            <span><Badge bg="success" className="me-1">Anciens</Badge> {global.anciens}</span>
            <span><Badge bg="warning" text="dark" className="me-1">Nouveaux</Badge> {global.nouveaux}</span>
          </div>
          <ProgressBar style={{ height: '25px' }}>
            <ProgressBar
              variant="success"
              now={parseFloat(pctAnciens)}
              label={`${pctAnciens}%`}
              key={1}
            />
            <ProgressBar
              variant="warning"
              now={parseFloat(pctNouveaux)}
              label={`${pctNouveaux}%`}
              key={2}
            />
          </ProgressBar>
        </Card.Body>
      </Card>

      {/* By Class Table */}
      <Card className="shadow-sm">
        <Card.Header className="bg-white">
          <h5 className="mb-0">Repartition par classe</h5>
        </Card.Header>
        <Card.Body className="p-0">
          <Table striped hover responsive className="mb-0">
            <thead className="table-dark">
              <tr>
                <th>Classe</th>
                <th className="text-center">Anciens</th>
                <th className="text-center">Nouveaux</th>
                <th className="text-center">Total</th>
                <th style={{ width: '25%' }}>Repartition</th>
              </tr>
            </thead>
            <tbody>
              {by_class.map((row, idx) => {
                const pctA = row.total > 0 ? ((row.anciens / row.total) * 100).toFixed(0) : 0;
                const pctN = row.total > 0 ? ((row.nouveaux / row.total) * 100).toFixed(0) : 0;
                return (
                  <tr key={idx}>
                    <td>
                      <strong>{row.class_name}</strong>{' '}
                      <span className="text-muted">{row.series_name}</span>
                    </td>
                    <td className="text-center">
                      <Badge bg="success" pill>{row.anciens}</Badge>
                    </td>
                    <td className="text-center">
                      <Badge bg="warning" text="dark" pill>{row.nouveaux}</Badge>
                    </td>
                    <td className="text-center fw-bold">{row.total}</td>
                    <td>
                      <ProgressBar style={{ height: '18px' }}>
                        <ProgressBar variant="success" now={parseFloat(pctA)} key={1} />
                        <ProgressBar variant="warning" now={parseFloat(pctN)} key={2} />
                      </ProgressBar>
                    </td>
                  </tr>
                );
              })}
              {by_class.length === 0 && (
                <tr>
                  <td colSpan={5} className="text-center text-muted py-4">
                    Aucun eleve inscrit pour cette annee scolaire.
                  </td>
                </tr>
              )}
            </tbody>
          </Table>
        </Card.Body>
      </Card>
    </Container>
  );
}

export default EnrollmentStats;
