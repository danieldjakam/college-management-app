import React, { useState, useEffect } from 'react';
import { 
  Container, Row, Col, Card, Form, Button, Table, Badge, 
  Alert, Spinner, InputGroup, Dropdown, ButtonGroup 
} from 'react-bootstrap';
import { 
  Calendar, Search, Filter, Download, Printer, 
  CheckCircleFill, XCircleFill, People, Clock,
  FileEarmarkText, CalendarRange
} from 'react-bootstrap-icons';
import { useAuth } from '../../hooks/useAuth';

const AttendanceReports = () => {
  const [attendances, setAttendances] = useState([]);
  const [filteredAttendances, setFilteredAttendances] = useState([]);
  const [classes, setClasses] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [messageType, setMessageType] = useState('');
  
  const [filters, setFilters] = useState({
    startDate: new Date().toISOString().split('T')[0],
    endDate: new Date().toISOString().split('T')[0],
    classId: '',
    status: 'all', // all, present, absent
    searchTerm: ''
  });

  const [summary, setSummary] = useState({
    totalStudents: 0,
    presentCount: 0,
    absentCount: 0,
    presentPercentage: 0
  });

  const { user, token } = useAuth();

  useEffect(() => {
    loadSupervisorClasses();
  }, []);

  useEffect(() => {
    if (filters.startDate && filters.endDate) {
      loadAttendances();
    }
  }, [filters.startDate, filters.endDate, filters.classId]);

  useEffect(() => {
    filterAndSummarizeAttendances();
  }, [attendances, filters.status, filters.searchTerm]);

  const loadSupervisorClasses = async () => {
    try {
      // Le token est déjà disponible depuis useAuth
      const response = await fetch(`http://localhost:4000/api/supervisors/${user.id}/assignments`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();
      if (data.success) {
        setClasses(data.data || []);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des classes:', error);
    }
  };

  const loadAttendances = async () => {
    if (!filters.startDate || !filters.endDate) return;

    try {
      setIsLoading(true);
      // Le token est déjà disponible depuis useAuth
      const params = new URLSearchParams({
        supervisor_id: user.id,
        start_date: filters.startDate,
        end_date: filters.endDate
      });

      if (filters.classId) {
        params.append('class_id', filters.classId);
      }

      const response = await fetch(`http://localhost:4000/api/supervisors/attendance-range?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();
      if (data.success) {
        setAttendances(data.data.attendances || []);
        setMessage('');
      } else {
        setMessage(data.message || 'Erreur lors du chargement');
        setMessageType('danger');
      }
    } catch (error) {
      setMessage('Erreur lors du chargement des présences');
      setMessageType('danger');
      console.error('Erreur:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const filterAndSummarizeAttendances = () => {
    let filtered = [...attendances];

    // Filter by status
    if (filters.status === 'present') {
      filtered = filtered.filter(att => att.is_present);
    } else if (filters.status === 'absent') {
      filtered = filtered.filter(att => !att.is_present);
    }

    // Filter by search term
    if (filters.searchTerm) {
      const searchLower = filters.searchTerm.toLowerCase();
      filtered = filtered.filter(att => 
        att.student?.full_name?.toLowerCase().includes(searchLower) ||
        att.school_class?.name?.toLowerCase().includes(searchLower)
      );
    }

    setFilteredAttendances(filtered);

    // Calculate summary
    const totalStudents = attendances.length;
    const presentCount = attendances.filter(att => att.is_present).length;
    const absentCount = totalStudents - presentCount;
    const presentPercentage = totalStudents > 0 ? ((presentCount / totalStudents) * 100).toFixed(1) : 0;

    setSummary({
      totalStudents,
      presentCount,
      absentCount,
      presentPercentage
    });
  };

  const handleFilterChange = (field, value) => {
    setFilters(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const resetFilters = () => {
    setFilters({
      startDate: new Date().toISOString().split('T')[0],
      endDate: new Date().toISOString().split('T')[0],
      classId: '',
      status: 'all',
      searchTerm: ''
    });
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR');
  };

  const formatTime = (timeString) => {
    if (!timeString) return '';
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const exportToPDF = () => {
    // TODO: Implement PDF export
    setMessage('Fonction d\'export PDF en cours de développement');
    setMessageType('info');
  };

  const printReport = () => {
    // Create a new window with printable content
    const printWindow = window.open('', '_blank');
    const printContent = generatePrintableReport();
    
    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>Rapport de Présences - ${filters.startDate} au ${filters.endDate}</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .summary { margin-bottom: 20px; display: flex; justify-content: space-around; }
            .summary-item { text-align: center; }
            .summary-number { font-size: 24px; font-weight: bold; color: #007bff; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f8f9fa; font-weight: bold; }
            .present { color: #28a745; font-weight: bold; }
            .absent { color: #dc3545; font-weight: bold; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
            @media print {
              body { margin: 0; }
              .no-print { display: none; }
            }
          </style>
        </head>
        <body>
          ${printContent}
        </body>
      </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 250);
  };

  const generatePrintableReport = () => {
    const now = new Date();
    return `
      <div class="header">
        <h1>Groupe Scolaire Bilingue Privé La Semence</h1>
        <h2>Rapport de Présences</h2>
        <p><strong>Période:</strong> ${formatDate(filters.startDate)} - ${formatDate(filters.endDate)}</p>
        <p><strong>Surveillant:</strong> ${user?.name || 'N/A'}</p>
        <p><strong>Généré le:</strong> ${now.toLocaleDateString('fr-FR')} à ${now.toLocaleTimeString('fr-FR')}</p>
      </div>

      <div class="summary">
        <div class="summary-item">
          <div class="summary-number">${summary.totalStudents}</div>
          <div>Total Enregistrements</div>
        </div>
        <div class="summary-item">
          <div class="summary-number" style="color: #28a745">${summary.presentCount}</div>
          <div>Présents</div>
        </div>
        <div class="summary-item">
          <div class="summary-number" style="color: #dc3545">${summary.absentCount}</div>
          <div>Absents</div>
        </div>
        <div class="summary-item">
          <div class="summary-number" style="color: #17a2b8">${summary.presentPercentage}%</div>
          <div>Taux de Présence</div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Élève</th>
            <th>Classe</th>
            <th>Heure</th>
            <th>Statut</th>
            <th>Surveillant</th>
          </tr>
        </thead>
        <tbody>
          ${filteredAttendances.map(attendance => `
            <tr>
              <td>${formatDate(attendance.attendance_date)}</td>
              <td><strong>${attendance.student?.full_name || 'N/A'}</strong></td>
              <td>${attendance.school_class?.name || 'N/A'}</td>
              <td>${formatTime(attendance.scanned_at)}</td>
              <td class="${attendance.is_present ? 'present' : 'absent'}">
                ${attendance.is_present ? '✓ Présent' : '✗ Absent'}
              </td>
              <td>${attendance.supervisor?.name || user?.name || 'N/A'}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>

      <div class="footer">
        <p>Document généré automatiquement par le système de gestion scolaire</p>
        <p>Total: ${filteredAttendances.length} enregistrement(s)</p>
      </div>
    `;
  };

  return (
    <Container fluid className="py-4">
      <Row>
        <Col>
          <h2 className="mb-4">
            <FileEarmarkText className="me-2" />
            Rapports de Présences
          </h2>
        </Col>
      </Row>

      {message && (
        <Row className="mb-3">
          <Col>
            <Alert variant={messageType} onClose={() => setMessage('')} dismissible>
              {message}
            </Alert>
          </Col>
        </Row>
      )}

      {/* Filters Section */}
      <Row className="mb-4">
        <Col>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <Filter className="me-2" />
                Filtres de Recherche
              </h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={3}>
                  <Form.Group className="mb-3">
                    <Form.Label>Date de début</Form.Label>
                    <Form.Control
                      type="date"
                      value={filters.startDate}
                      onChange={(e) => handleFilterChange('startDate', e.target.value)}
                    />
                  </Form.Group>
                </Col>
                <Col md={3}>
                  <Form.Group className="mb-3">
                    <Form.Label>Date de fin</Form.Label>
                    <Form.Control
                      type="date"
                      value={filters.endDate}
                      onChange={(e) => handleFilterChange('endDate', e.target.value)}
                    />
                  </Form.Group>
                </Col>
                <Col md={3}>
                  <Form.Group className="mb-3">
                    <Form.Label>Classe</Form.Label>
                    <Form.Select
                      value={filters.classId}
                      onChange={(e) => handleFilterChange('classId', e.target.value)}
                    >
                      <option value="">Toutes les classes</option>
                      {classes.map(assignment => (
                        <option key={assignment.id} value={assignment.school_class_id}>
                          {assignment.school_class?.name}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
                <Col md={3}>
                  <Form.Group className="mb-3">
                    <Form.Label>Statut</Form.Label>
                    <Form.Select
                      value={filters.status}
                      onChange={(e) => handleFilterChange('status', e.target.value)}
                    >
                      <option value="all">Tous</option>
                      <option value="present">Présents seulement</option>
                      <option value="absent">Absents seulement</option>
                    </Form.Select>
                  </Form.Group>
                </Col>
              </Row>
              <Row>
                <Col md={8}>
                  <Form.Group className="mb-3">
                    <Form.Label>Rechercher un élève</Form.Label>
                    <InputGroup>
                      <InputGroup.Text>
                        <Search />
                      </InputGroup.Text>
                      <Form.Control
                        type="text"
                        placeholder="Nom de l'élève..."
                        value={filters.searchTerm}
                        onChange={(e) => handleFilterChange('searchTerm', e.target.value)}
                      />
                    </InputGroup>
                  </Form.Group>
                </Col>
                <Col md={4} className="d-flex align-items-end">
                  <Button variant="outline-secondary" onClick={resetFilters} className="mb-3">
                    Réinitialiser
                  </Button>
                </Col>
              </Row>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Summary Section */}
      <Row className="mb-4">
        <Col md={3}>
          <Card className="text-center border-primary">
            <Card.Body>
              <People size={32} className="text-primary mb-2" />
              <h4 className="mb-0">{summary.totalStudents}</h4>
              <small className="text-muted">Total Enregistrements</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="text-center border-success">
            <Card.Body>
              <CheckCircleFill size={32} className="text-success mb-2" />
              <h4 className="mb-0">{summary.presentCount}</h4>
              <small className="text-muted">Présents</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="text-center border-danger">
            <Card.Body>
              <XCircleFill size={32} className="text-danger mb-2" />
              <h4 className="mb-0">{summary.absentCount}</h4>
              <small className="text-muted">Absents</small>
            </Card.Body>
          </Card>
        </Col>
        <Col md={3}>
          <Card className="text-center border-info">
            <Card.Body>
              <CalendarRange size={32} className="text-info mb-2" />
              <h4 className="mb-0">{summary.presentPercentage}%</h4>
              <small className="text-muted">Taux de Présence</small>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Actions */}
      <Row className="mb-3">
        <Col className="d-flex justify-content-end">
          <ButtonGroup>
            <Button variant="outline-primary" onClick={printReport}>
              <Printer className="me-2" />
              Imprimer
            </Button>
            <Button variant="outline-success" onClick={exportToPDF}>
              <Download className="me-2" />
              Exporter PDF
            </Button>
          </ButtonGroup>
        </Col>
      </Row>

      {/* Results Table */}
      <Row>
        <Col>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <Calendar className="me-2" />
                Résultats
                <Badge bg="secondary" className="ms-2">
                  {filteredAttendances.length}
                </Badge>
              </h5>
            </Card.Header>
            <Card.Body>
              {isLoading ? (
                <div className="text-center py-4">
                  <Spinner />
                  <p className="mt-2 text-muted">Chargement en cours...</p>
                </div>
              ) : filteredAttendances.length > 0 ? (
                <div className="table-responsive">
                  <Table striped hover>
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Élève</th>
                        <th>Classe</th>
                        <th>Heure</th>
                        <th>Statut</th>
                        <th>Surveillant</th>
                      </tr>
                    </thead>
                    <tbody>
                      {filteredAttendances.map((attendance, index) => (
                        <tr key={index}>
                          <td>{formatDate(attendance.attendance_date)}</td>
                          <td>
                            <strong>{attendance.student?.full_name || 'N/A'}</strong>
                          </td>
                          <td>
                            <Badge bg="light" text="dark">
                              {attendance.school_class?.name || 'N/A'}
                            </Badge>
                          </td>
                          <td>
                            <Clock size={14} className="me-1" />
                            {formatTime(attendance.scanned_at)}
                          </td>
                          <td>
                            <Badge bg={attendance.is_present ? 'success' : 'danger'}>
                              {attendance.is_present ? (
                                <>
                                  <CheckCircleFill size={12} className="me-1" />
                                  Présent
                                </>
                              ) : (
                                <>
                                  <XCircleFill size={12} className="me-1" />
                                  Absent
                                </>
                              )}
                            </Badge>
                          </td>
                          <td>
                            <small className="text-muted">
                              {attendance.supervisor?.name || 'N/A'}
                            </small>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </div>
              ) : (
                <div className="text-center py-4 text-muted">
                  <Calendar size={48} />
                  <p className="mt-2 mb-0">
                    {attendances.length === 0 
                      ? 'Aucune donnée de présence pour la période sélectionnée'
                      : 'Aucun résultat ne correspond aux filtres appliqués'
                    }
                  </p>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default AttendanceReports;