import React, { useState, useEffect } from 'react';
import {
  Container, Row, Col, Card, Table, Spinner, Alert, Badge, ProgressBar
} from 'react-bootstrap';
import { PeopleFill, PersonPlusFill, PersonCheckFill } from 'react-bootstrap-icons';
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
      const result = response.data || response;
      if (result.success) {
        setData(result.data);
      } else {
        setError(result.message || 'Erreur lors du chargement');
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

  return (
    <Container fluid className="py-4">
      <h4 className="mb-1">
        <PeopleFill className="me-2" />
        Statistiques des inscriptions
      </h4>
      <p className="text-muted mb-4">
        Repartition des eleves anciens et nouveaux - Annee scolaire <strong>{school_year}</strong>
      </p>

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
