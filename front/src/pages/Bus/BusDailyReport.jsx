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
  Alert
} from 'react-bootstrap';
import {
  BarChart,
  FileEarmarkExcel,
  Calendar
} from 'react-bootstrap-icons';
import { apiEndpoints } from '../../utils/api';

const BusDailyReport = () => {
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [report, setReport] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchReport();
  }, [selectedDate]);

  const fetchReport = async () => {
    try {
      setLoading(true);
      const response = await apiEndpoints.getBusTicketDailyReport(selectedDate);
      if (response.success) {
        setReport(response.data);
      }
    } catch (error) {
      console.error('Erreur chargement rapport:', error);
    } finally {
      setLoading(false);
    }
  };

  const exportToExcel = () => {
    if (!report) return;

    const data = [
      ['RAPPORT JOURNALIER TICKETS BUS'],
      [`Date: ${new Date(selectedDate).toLocaleDateString('fr-FR')}`],
      [],
      ['TICKETS ALLER'],
      ['Générés', report.batches.aller?.quantity_generated || 0],
      ['Vendus', report.sales.aller.count],
      ['Restants', report.batches.aller?.quantity_remaining || 0],
      ['Revenus', `${new Intl.NumberFormat('fr-FR').format(report.sales.aller.revenue)} FCFA`],
      [],
      ['TICKETS RETOUR'],
      ['Générés', report.batches.retour?.quantity_generated || 0],
      ['Vendus', report.sales.retour.count],
      ['Restants', report.batches.retour?.quantity_remaining || 0],
      ['Revenus', `${new Intl.NumberFormat('fr-FR').format(report.sales.retour.revenue)} FCFA`],
      [],
      ['TOTAL'],
      ['Total Ventes', report.sales.total_count],
      ['Total Revenus', `${new Intl.NumberFormat('fr-FR').format(report.sales.total_revenue)} FCFA`]
    ];

    const csv = data.map(row => row.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `rapport_bus_${selectedDate}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  };

  if (!report) {
    return (
      <Container className="mt-4">
        <Alert variant="info">Chargement du rapport...</Alert>
      </Container>
    );
  }

  return (
    <Container className="mt-4">
      <Row className="mb-4">
        <Col>
          <h2>
            <BarChart className="me-2" style={{ color: '#17a2b8' }} />
            Rapport Quotidien - Tickets Bus
          </h2>
          <p className="text-muted">
            Vue d'ensemble des ventes et du stock
          </p>
        </Col>
      </Row>

      {/* Sélection de date */}
      <Row className="mb-4">
        <Col md={4}>
          <Card className="shadow-sm">
            <Card.Body>
              <Form.Group>
                <Form.Label>
                  <Calendar className="me-2" />
                  Sélectionner une Date
                </Form.Label>
                <Form.Control
                  type="date"
                  value={selectedDate}
                  onChange={(e) => setSelectedDate(e.target.value)}
                />
              </Form.Group>
            </Card.Body>
          </Card>
        </Col>
        <Col md={8} className="d-flex align-items-end">
          <Button
            variant="success"
            onClick={exportToExcel}
            disabled={!report}
          >
            <FileEarmarkExcel className="me-2" />
            Exporter Excel
          </Button>
        </Col>
      </Row>

      {/* Résumé global */}
      <Row className="mb-4">
        <Col md={4}>
          <Card className="text-center shadow-sm border-primary">
            <Card.Body>
              <h3 className="text-primary">{report.sales.total_count}</h3>
              <small className="text-muted">Total Tickets Vendus</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4}>
          <Card className="text-center shadow-sm border-success">
            <Card.Body>
              <h3 className="text-success">
                {new Intl.NumberFormat('fr-FR').format(report.sales.total_revenue)}
              </h3>
              <small className="text-muted">FCFA Encaissés</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4}>
          <Card className="text-center shadow-sm border-info">
            <Card.Body>
              <h3 className="text-info">
                {((report.batches.aller?.quantity_generated || 0) +
                  (report.batches.retour?.quantity_generated || 0)) - report.sales.total_count}
              </h3>
              <small className="text-muted">Tickets Restants</small>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Détail par type */}
      <Row className="mb-4">
        <Col md={6}>
          <Card className="shadow-sm">
            <Card.Header className="bg-primary text-white">
              <h5 className="mb-0">🚌 Tickets ALLER</h5>
            </Card.Header>
            <Card.Body>
              <Table borderless>
                <tbody>
                  <tr>
                    <td className="fw-bold">Générés:</td>
                    <td className="text-end fs-5">
                      {report.batches.aller?.quantity_generated || 0}
                    </td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Vendus:</td>
                    <td className="text-end fs-5 text-success">
                      {report.sales.aller.count}
                    </td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Restants:</td>
                    <td className="text-end fs-5 text-warning">
                      {report.batches.aller?.quantity_remaining || 0}
                    </td>
                  </tr>
                  <tr className="border-top">
                    <td className="fw-bold">Revenus:</td>
                    <td className="text-end fs-4 text-primary">
                      {new Intl.NumberFormat('fr-FR').format(report.sales.aller.revenue)} FCFA
                    </td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Taux de vente:</td>
                    <td className="text-end">
                      <Badge bg="info">
                        {report.batches.aller?.quantity_generated
                          ? Math.round((report.sales.aller.count / report.batches.aller.quantity_generated) * 100)
                          : 0}%
                      </Badge>
                    </td>
                  </tr>
                </tbody>
              </Table>
            </Card.Body>
          </Card>
        </Col>

        <Col md={6}>
          <Card className="shadow-sm">
            <Card.Header className="bg-success text-white">
              <h5 className="mb-0">🏠 Tickets RETOUR</h5>
            </Card.Header>
            <Card.Body>
              <Table borderless>
                <tbody>
                  <tr>
                    <td className="fw-bold">Générés:</td>
                    <td className="text-end fs-5">
                      {report.batches.retour?.quantity_generated || 0}
                    </td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Vendus:</td>
                    <td className="text-end fs-5 text-success">
                      {report.sales.retour.count}
                    </td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Restants:</td>
                    <td className="text-end fs-5 text-warning">
                      {report.batches.retour?.quantity_remaining || 0}
                    </td>
                  </tr>
                  <tr className="border-top">
                    <td className="fw-bold">Revenus:</td>
                    <td className="text-end fs-4 text-success">
                      {new Intl.NumberFormat('fr-FR').format(report.sales.retour.revenue)} FCFA
                    </td>
                  </tr>
                  <tr>
                    <td className="fw-bold">Taux de vente:</td>
                    <td className="text-end">
                      <Badge bg="info">
                        {report.batches.retour?.quantity_generated
                          ? Math.round((report.sales.retour.count / report.batches.retour.quantity_generated) * 100)
                          : 0}%
                      </Badge>
                    </td>
                  </tr>
                </tbody>
              </Table>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Méthodes de paiement */}
      <Row>
        <Col>
          <Card className="shadow-sm">
            <Card.Header>
              <h5 className="mb-0">💰 Répartition par Méthode de Paiement</h5>
            </Card.Header>
            <Card.Body>
              {Object.keys(report.payment_methods).length === 0 ? (
                <Alert variant="info" className="mb-0">
                  Aucune vente enregistrée
                </Alert>
              ) : (
                <Table hover>
                  <thead>
                    <tr>
                      <th>Méthode</th>
                      <th className="text-end">Nombre</th>
                      <th className="text-end">Montant Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {Object.entries(report.payment_methods).map(([method, data]) => (
                      <tr key={method}>
                        <td>
                          {method === 'cash' && '💵 Espèces'}
                          {method === 'mobile_money' && '📱 Mobile Money'}
                          {method === 'bank_transfer' && '🏦 Virement Bancaire'}
                          {method === 'check' && '📝 Chèque'}
                          {method === 'other' && '➕ Autre'}
                        </td>
                        <td className="text-end fw-bold">{data.count}</td>
                        <td className="text-end fw-bold text-success">
                          {new Intl.NumberFormat('fr-FR').format(data.total)} FCFA
                        </td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot className="table-light">
                    <tr>
                      <th>TOTAL</th>
                      <th className="text-end">{report.sales.total_count}</th>
                      <th className="text-end text-primary">
                        {new Intl.NumberFormat('fr-FR').format(report.sales.total_revenue)} FCFA
                      </th>
                    </tr>
                  </tfoot>
                </Table>
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default BusDailyReport;
