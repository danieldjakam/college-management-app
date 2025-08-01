import React, { useState, useEffect, useRef } from 'react';
import { Card, Button, Alert, Container, Row, Col, Table, Badge, Spinner, ButtonGroup } from 'react-bootstrap';
import { QrCodeScan, CheckCircleFill, XCircleFill, Calendar, Clock, ArrowRightCircle, ArrowLeftCircle } from 'react-bootstrap-icons';
import { useAuth } from '../../hooks/useAuth';
import { secureApiEndpoints } from '../../utils/apiMigration';
import QrScanner from 'qr-scanner';

const AttendanceScanner = () => {
  const [isScanning, setIsScanning] = useState(false);
  const [message, setMessage] = useState('');
  const [messageType, setMessageType] = useState('');
  const [todayAttendances, setTodayAttendances] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [scannerError, setScannerError] = useState('');
  const [eventType, setEventType] = useState('entry'); // 'entry' ou 'exit'
  
  const { user } = useAuth();
  const videoRef = useRef(null);
  const qrScannerRef = useRef(null);

  useEffect(() => {
    loadTodayAttendances();
    return () => {
      if (qrScannerRef.current) {
        qrScannerRef.current.destroy();
      }
    };
  }, []);

  const loadTodayAttendances = async () => {
    try {
      setIsLoading(true);
      const response = await secureApiEndpoints.supervisors.getDailyAttendance({
        supervisor_id: user.id
      });

      if (response.success) {
        setTodayAttendances(response.data.attendances || []);
      } else {
        console.error('Erreur API:', response.message);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des présences:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const startScanner = async () => {
    try {
      setScannerError('');
      setIsScanning(true);
      
      if (videoRef.current) {
        qrScannerRef.current = new QrScanner(
          videoRef.current,
          (result) => handleScanResult(result.data),
          {
            onDecodeError: error => {
              console.log('Scan error:', error);
            },
            highlightScanRegion: true,
            highlightCodeOutline: true,
          }
        );
        
        await qrScannerRef.current.start();
      }
    } catch (error) {
      console.error('Erreur lors du démarrage du scanner:', error);
      setScannerError('Impossible d\'accéder à la caméra. Vérifiez les autorisations.');
      setIsScanning(false);
    }
  };

  const stopScanner = () => {
    if (qrScannerRef.current) {
      qrScannerRef.current.stop();
      qrScannerRef.current.destroy();
      qrScannerRef.current = null;
    }
    setIsScanning(false);
  };

  const handleScanResult = async (qrCode) => {
    try {
      stopScanner();
      setIsLoading(true);
      
      const response = await secureApiEndpoints.supervisors.scanQR({
        student_qr_code: qrCode,
        supervisor_id: user.id,
        event_type: eventType
      });
      
      if (response.success) {
        const eventIcon = eventType === 'entry' ? '🟢' : '🔴';
        const eventLabel = eventType === 'entry' ? 'entrée' : 'sortie';
        setMessage(`${eventIcon} ${response.data.event_label} de ${response.data.student_name} enregistrée à ${response.data.marked_at}`);
        setMessageType('success');
        loadTodayAttendances(); // Recharger la liste
      } else {
        setMessage(`❌ ${response.message}`);
        setMessageType('danger');
      }
    } catch (error) {
      setMessage('❌ Erreur lors de l\'enregistrement de la présence');
      setMessageType('danger');
      console.error('Erreur scan:', error);
    } finally {
      setIsLoading(false);
      // Auto-clear message after 5 seconds
      setTimeout(() => {
        setMessage('');
        setMessageType('');
      }, 5000);
    }
  };

  const formatTime = (timeString) => {
    if (!timeString) return '';
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <Container fluid className="py-4">
      <Row>
        <Col>
          <h2 className="mb-4">
            <QrCodeScan className="me-2" />
            Scanner de Présences
          </h2>
        </Col>
      </Row>

      {/* Scanner Section */}
      <Row className="mb-4">
        <Col lg={6}>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <QrCodeScan className="me-2" />
                Scanner QR
              </h5>
            </Card.Header>
            <Card.Body>
              {scannerError && (
                <Alert variant="danger" className="mb-3">
                  {scannerError}
                </Alert>
              )}
              
              {message && (
                <Alert variant={messageType} className="mb-3">
                  {message}
                </Alert>
              )}

              {/* Event Type Selection */}
              <div className="mb-4">
                <h6 className="mb-3">Type d'événement :</h6>
                <ButtonGroup className="w-100">
                  <Button
                    variant={eventType === 'entry' ? 'success' : 'outline-success'}
                    onClick={() => setEventType('entry')}
                    disabled={isScanning}
                  >
                    <ArrowRightCircle className="me-2" />
                    Entrée
                  </Button>
                  <Button
                    variant={eventType === 'exit' ? 'danger' : 'outline-danger'}
                    onClick={() => setEventType('exit')}
                    disabled={isScanning}
                  >
                    <ArrowLeftCircle className="me-2" />
                    Sortie
                  </Button>
                </ButtonGroup>
              </div>

              <div className="text-center mb-3">
                {!isScanning ? (
                  <Button 
                    variant="primary" 
                    size="lg"
                    onClick={startScanner}
                    disabled={isLoading}
                  >
                    {isLoading ? (
                      <>
                        <Spinner size="sm" className="me-2" />
                        Traitement...
                      </>
                    ) : (
                      <>
                        <QrCodeScan className="me-2" />
                        Démarrer le Scanner
                      </>
                    )}
                  </Button>
                ) : (
                  <Button 
                    variant="danger" 
                    size="lg"
                    onClick={stopScanner}
                  >
                    <XCircleFill className="me-2" />
                    Arrêter le Scanner
                  </Button>
                )}
              </div>

              {/* Video preview */}
              <div className="scanner-container" style={{ position: 'relative', maxWidth: '400px', margin: '0 auto' }}>
                <video
                  ref={videoRef}
                  style={{
                    width: '100%',
                    height: 'auto',
                    border: '2px solid #dee2e6',
                    borderRadius: '8px',
                    display: isScanning ? 'block' : 'none'
                  }}
                />
                {!isScanning && (
                  <div 
                    style={{
                      width: '100%',
                      height: '300px',
                      border: '2px dashed #dee2e6',
                      borderRadius: '8px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      backgroundColor: '#f8f9fa'
                    }}
                  >
                    <div className="text-center text-muted">
                      <QrCodeScan size={48} />
                      <p className="mt-2 mb-0">Cliquez sur "Démarrer le Scanner"</p>
                    </div>
                  </div>
                )}
              </div>
            </Card.Body>
          </Card>
        </Col>

        {/* Today's Attendance List */}
        <Col lg={6}>
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <Calendar className="me-2" />
                Présences d'Aujourd'hui
                <Badge bg="primary" className="ms-2">
                  {todayAttendances.length}
                </Badge>
              </h5>
            </Card.Header>
            <Card.Body>
              {isLoading ? (
                <div className="text-center py-4">
                  <Spinner />
                  <p className="mt-2 text-muted">Chargement...</p>
                </div>
              ) : todayAttendances.length > 0 ? (
                <div style={{ maxHeight: '400px', overflowY: 'auto' }}>
                  <Table striped hover size="sm">
                    <thead>
                      <tr>
                        <th>Élève</th>
                        <th>Classe</th>
                        <th>Type</th>
                        <th>Heure</th>
                        <th>Statut</th>
                      </tr>
                    </thead>
                    <tbody>
                      {todayAttendances.map((attendance, index) => (
                        <tr key={index}>
                          <td>{attendance.student?.full_name || 'N/A'}</td>
                          <td>{attendance.school_class?.name || 'N/A'}</td>
                          <td>
                            <Badge bg={attendance.event_type === 'entry' ? 'success' : 'danger'}>
                              {attendance.event_type === 'entry' ? (
                                <>
                                  <ArrowRightCircle size={12} className="me-1" />
                                  Entrée
                                </>
                              ) : (
                                <>
                                  <ArrowLeftCircle size={12} className="me-1" />
                                  Sortie
                                </>
                              )}
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
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </div>
              ) : (
                <div className="text-center py-4 text-muted">
                  <Calendar size={48} />
                  <p className="mt-2 mb-0">Aucune présence enregistrée aujourd'hui</p>
                </div>
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Instructions */}
      <Row>
        <Col>
          <Card className="border-info">
            <Card.Header className="bg-info text-white">
              <h6 className="mb-0">Instructions d'utilisation - Entrées/Sorties</h6>
            </Card.Header>
            <Card.Body>
              <ol className="mb-0">
                <li><strong>Choisissez le type :</strong> "Entrée" pour l'arrivée ou "Sortie" pour le départ</li>
                <li>Cliquez sur "Démarrer le Scanner" pour activer la caméra</li>
                <li>Dirigez la caméra vers le code QR du badge de l'élève</li>
                <li>Le système vérifiera automatiquement les conditions (entrée avant sortie)</li>
                <li>Les parents recevront une notification WhatsApp automatique</li>
                <li>Consultez la liste des entrées/sorties du jour dans le panneau de droite</li>
              </ol>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default AttendanceScanner;