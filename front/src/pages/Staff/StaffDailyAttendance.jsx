import React, { useState, useEffect } from 'react';
import { Container, Card, Row, Col, Form, Button, Table, Alert, Spinner, Badge } from 'react-bootstrap';
import { Calendar, Search, People, Check, X, Eye, Download } from 'react-bootstrap-icons';
import { secureApiEndpoints, secureApi } from '../../utils/apiMigration';

function StaffDailyAttendance() {
  const [staffData, setStaffData] = useState([]);
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [selectedRole, setSelectedRole] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [stats, setStats] = useState({ total: 0, present: 0, absent: 0 });
  const [exportLoading, setExportLoading] = useState(false);
  const [roles] = useState([
    { value: 'teacher', label: 'Enseignant' },
    { value: 'accountant', label: 'Comptable' },
    { value: 'admin', label: 'Administrateur' },
    { value: 'secretaire', label: 'Secrétaire' },
    { value: 'surveillant_general', label: 'Surveillant Général' },
    { value: 'comptable_superieur', label: 'Comptable Supérieur' }
  ]);

  useEffect(() => {
    if (selectedDate) {
      fetchAttendanceData();
    }
  }, [selectedDate, selectedRole]);

  const fetchAttendanceData = async () => {
    if (!selectedDate) {
      setError('Veuillez sélectionner une date');
      return;
    }

    setLoading(true);
    setError('');

    try {
      // Construire les paramètres de la requête
      const params = new URLSearchParams({ date: selectedDate });
      
      if (selectedRole) {
        params.append('role', selectedRole);
      }

      const response = await secureApi.get(`/staff-attendance/daily?${params.toString()}`);
      console.log('Réponse staff attendance:', response);
      
      let data = [];
      if (response.success) {
        data = Array.isArray(response.data) ? response.data : [];
      } else if (Array.isArray(response)) {
        data = response;
      }
      
      setStaffData(data);
      
      // Calculer les statistiques
      const total = data.length;
      const present = data.filter(staff => staff.is_present).length;
      const absent = total - present;
      
      setStats({ total, present, absent });
    } catch (error) {
      console.error('Erreur lors du chargement des présences:', error);
      setError('Erreur lors du chargement des données de présence: ' + error.message);
      setStaffData([]);
      setStats({ total: 0, present: 0, absent: 0 });
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    fetchAttendanceData();
  };

  const handleExportPDF = async () => {
    if (!selectedDate) {
      setError('Veuillez sélectionner une date pour l\'export');
      return;
    }

    setExportLoading(true);
    setError('');

    try {
      // Construire les paramètres de la requête pour l'export
      const params = new URLSearchParams({ date: selectedDate });
      
      if (selectedRole) {
        params.append('role', selectedRole);
      }

      const response = await fetch(`${process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000'}/api/staff-attendance/export/pdf?${params.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token') || sessionStorage.getItem('token')}`,
          'Accept': 'application/pdf',
        },
      });

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}: ${response.statusText}`);
      }
      
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      
      const link = document.createElement('a');
      link.href = url;
      link.download = `presences_personnel_${selectedDate}.pdf`;
      document.body.appendChild(link);
      link.click();
      
      window.URL.revokeObjectURL(url);
      document.body.removeChild(link);
      
    } catch (error) {
      console.error('Erreur lors de l\'export PDF:', error);
      setError('Erreur lors de l\'export PDF: ' + error.message);
    } finally {
      setExportLoading(false);
    }
  };

  const getAttendanceBadge = (isPresent) => {
    return isPresent ? (
      <Badge bg="success" className="d-flex align-items-center gap-1">
        <Check size={12} /> Présent
      </Badge>
    ) : (
      <Badge bg="danger" className="d-flex align-items-center gap-1">
        <X size={12} /> Absent
      </Badge>
    );
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  return (
    <Container fluid className="p-4">
      <Row className="mb-4">
        <Col>
          <h2 className="d-flex align-items-center gap-2 mb-0">
            <People className="text-primary" />
            Suivi des Présences Personnel
          </h2>
          <p className="text-muted mb-0">
            Consultez les présences du personnel pour un jour donné
          </p>
        </Col>
      </Row>

      {/* Filtres */}
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0 d-flex align-items-center gap-2">
            <Search /> Filtres de Recherche
          </h5>
        </Card.Header>
        <Card.Body>
          <Form onSubmit={handleSearch}>
            <Row>
              <Col md={6} className="mb-3">
                <Form.Group>
                  <Form.Label>Date</Form.Label>
                  <Form.Control
                    type="date"
                    value={selectedDate}
                    onChange={(e) => setSelectedDate(e.target.value)}
                    required
                  />
                </Form.Group>
              </Col>

              <Col md={6} className="mb-3">
                <Form.Group>
                  <Form.Label>Rôle</Form.Label>
                  <Form.Select
                    value={selectedRole}
                    onChange={(e) => setSelectedRole(e.target.value)}
                  >
                    <option value="">Tous les rôles</option>
                    {roles.map(role => (
                      <option key={role.value} value={role.value}>
                        {role.label}
                      </option>
                    ))}
                  </Form.Select>
                </Form.Group>
              </Col>
            </Row>

            <Row>
              <Col className="d-flex gap-2">
                <Button 
                  type="submit" 
                  variant="primary" 
                  disabled={loading}
                  className="d-flex align-items-center gap-2"
                >
                  {loading ? (
                    <>
                      <Spinner size="sm" /> Chargement...
                    </>
                  ) : (
                    <>
                      <Eye /> Consulter les Présences
                    </>
                  )}
                </Button>
                
                <Button 
                  variant="success" 
                  disabled={exportLoading || !selectedDate}
                  onClick={handleExportPDF}
                  className="d-flex align-items-center gap-2"
                >
                  {exportLoading ? (
                    <>
                      <Spinner size="sm" /> Export...
                    </>
                  ) : (
                    <>
                      <Download /> Exporter PDF
                    </>
                  )}
                </Button>
              </Col>
            </Row>
          </Form>
        </Card.Body>
      </Card>

      {error && (
        <Alert variant="danger" className="mb-4">
          {error}
        </Alert>
      )}

      {/* Statistiques */}
      {staffData.length > 0 && (
        <Card className="mb-4">
          <Card.Header>
            <h5 className="mb-0 d-flex align-items-center gap-2">
              <Calendar /> Résumé - {formatDate(selectedDate)}
            </h5>
          </Card.Header>
          <Card.Body>
            <Row>
              <Col md={4} className="text-center">
                <h3 className="text-primary mb-1">{stats.total}</h3>
                <p className="text-muted mb-0">Total Personnel</p>
              </Col>
              <Col md={4} className="text-center">
                <h3 className="text-success mb-1">{stats.present}</h3>
                <p className="text-muted mb-0">Présents</p>
              </Col>
              <Col md={4} className="text-center">
                <h3 className="text-danger mb-1">{stats.absent}</h3>
                <p className="text-muted mb-0">Absents</p>
              </Col>
            </Row>
            <Row className="mt-3">
              <Col className="text-center">
                <p className="mb-0">
                  Taux de présence: <strong>{stats.total > 0 ? ((stats.present / stats.total) * 100).toFixed(1) : 0}%</strong>
                </p>
              </Col>
            </Row>
          </Card.Body>
        </Card>
      )}

      {/* Tableau des présences */}
      {staffData.length > 0 ? (
        <Card>
          <Card.Header>
            <h5 className="mb-0">Liste des Présences Personnel</h5>
          </Card.Header>
          <Card.Body className="p-0">
            <div className="table-responsive">
              <Table striped hover className="mb-0">
                <thead className="bg-light">
                  <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Type</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Entrées/Sorties</th>
                    <th>Temps Total</th>
                  </tr>
                </thead>
                <tbody>
                  {staffData.map((staff, index) => (
                    <tr key={index}>
                      <td><strong>{staff.last_name || staff.name || '-'}</strong></td>
                      <td>{staff.first_name || '-'}</td>
                      <td>
                        <Badge bg={staff.employment_type === 'P' ? 'primary' : 
                                   staff.employment_type === 'SP' ? 'info' : 'warning'}>
                          {staff.employment_type === 'P' ? 'Permanent (P)' : 
                           staff.employment_type === 'SP' ? 'Semi-permanent (SP)' :
                           staff.employment_type === 'V' ? 'Vacataire (V)' : 'Non défini'}
                        </Badge>
                      </td>
                      <td>
                        <Badge bg="secondary">
                          {staff.role === 'teacher' ? 'Enseignant' :
                           staff.role === 'accountant' ? 'Comptable' :
                           staff.role === 'admin' ? 'Administrateur' :
                           staff.role === 'secretaire' ? 'Secrétaire' :
                           staff.role === 'surveillant_general' ? 'Surveillant' :
                           staff.role === 'comptable_superieur' ? 'Comptable Sup.' :
                           staff.role || 'Personnel'}
                        </Badge>
                      </td>
                      <td>{getAttendanceBadge(staff.is_present)}</td>
                      <td>
                        {staff.entry_exit_pairs && staff.entry_exit_pairs.length > 0 ? (
                          <div className="small">
                            {staff.entry_exit_pairs.map((pair, pairIndex) => (
                              <div key={pairIndex} className="mb-1">
                                <span className="text-success">
                                  {new Date(pair.entry_time).toLocaleTimeString('fr-FR', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                  })}
                                </span>
                                {pair.exit_time ? (
                                  <>
                                    <span className="mx-1">→</span>
                                    <span className="text-danger">
                                      {new Date(pair.exit_time).toLocaleTimeString('fr-FR', {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                      })}
                                    </span>
                                    <span className="text-muted ms-1">({pair.working_hours})</span>
                                  </>
                                ) : (
                                  <span className="text-warning ms-1">(En cours)</span>
                                )}
                              </div>
                            ))}
                          </div>
                        ) : (
                          <span className="text-muted">Aucune donnée</span>
                        )}
                      </td>
                      <td>
                        <strong className="text-primary">
                          {staff.total_working_hours || '0h 0min'}
                        </strong>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            </div>
          </Card.Body>
        </Card>
      ) : !loading && selectedDate && (
        <Card>
          <Card.Body className="text-center py-5">
            <People size={48} className="text-muted mb-3" />
            <h5 className="text-muted">Aucune donnée de présence</h5>
            <p className="text-muted mb-0">
              Aucune donnée de présence trouvée pour la date sélectionnée.
            </p>
          </Card.Body>
        </Card>
      )}
    </Container>
  );
}

export default StaffDailyAttendance;