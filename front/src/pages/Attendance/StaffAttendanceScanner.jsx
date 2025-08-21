/**
 * Scanner de présences pour tout le personnel
 * Gestion unifiée des présences: enseignants, comptables, surveillant général
 */

import React, { useState, useEffect, useRef } from 'react';
import { 
    Card, 
    Button, 
    Alert, 
    Container, 
    Row, 
    Col, 
    Table, 
    Badge, 
    Spinner, 
    ButtonGroup, 
    Form,
    Modal,
    ProgressBar,
    Toast,
    ToastContainer,
    Tab,
    Tabs
} from 'react-bootstrap';
import { 
    QrCodeScan, 
    CheckCircleFill, 
    XCircleFill, 
    Calendar, 
    Clock, 
    ArrowRightCircle, 
    ArrowLeftCircle,
    PersonBadge,
    Wifi,
    WifiOff,
    CloudArrowUp,
    CloudArrowDown,
    ExclamationTriangle,
    InfoCircle,
    PersonCheck,
    PersonX,
    People,
    PersonWorkspace,
    ShieldCheck,
    Gear
} from 'react-bootstrap-icons';
import { useAuth } from '../../hooks/useAuth';
import { secureApiEndpoints } from '../../utils/apiMigration';
import QrScanner from 'qr-scanner';
import Swal from 'sweetalert2';

const StaffAttendanceScanner = () => {
    const [isScanning, setIsScanning] = useState(false);
    const [message, setMessage] = useState('');
    const [messageType, setMessageType] = useState('info');
    const [dailyAttendances, setDailyAttendances] = useState([]);
    const [stats, setStats] = useState({});
    const [loading, setLoading] = useState(false);
    const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
    const [selectedStaffType, setSelectedStaffType] = useState('');
    const [showStatsModal, setShowStatsModal] = useState(false);
    const [currentScan, setCurrentScan] = useState(null);
    const [showToast, setShowToast] = useState(false);
    const [toastMessage, setToastMessage] = useState('');

    const videoRef = useRef(null);
    const scannerRef = useRef(null);
    const { user } = useAuth();
    const isOnline = true;

    // Types de personnel avec leurs icônes et couleurs
    const staffTypes = {
        teacher: { 
            label: 'Enseignants', 
            icon: PersonWorkspace, 
            color: 'primary',
            bgColor: 'bg-primary'
        },
        accountant: { 
            label: 'Comptables', 
            icon: PersonCheck, 
            color: 'success',
            bgColor: 'bg-success'
        },
        supervisor: { 
            label: 'Surveillants Généraux', 
            icon: ShieldCheck, 
            color: 'warning',
            bgColor: 'bg-warning'
        },
        admin: { 
            label: 'Administrateurs', 
            icon: Gear, 
            color: 'danger',
            bgColor: 'bg-danger'
        }
    };

    useEffect(() => {
        loadDailyAttendances();
        return () => {
            if (scannerRef.current) {
                scannerRef.current.destroy();
            }
        };
    }, [selectedDate, selectedStaffType]);

    const loadDailyAttendances = async () => {
        try {
            setLoading(true);
            const params = {
                date: selectedDate,
                ...(selectedStaffType && { staff_type: selectedStaffType })
            };

            const response = await secureApiEndpoints.staff.getDailyAttendance(params);
            
            if (response.success) {
                setDailyAttendances(response.data.attendances || []);
                setStats(response.data.stats || {});
            } else {
                setMessage('Erreur lors du chargement des présences');
                setMessageType('danger');
            }
        } catch (error) {
            console.error('Error loading attendances:', error);
            setMessage('Erreur lors du chargement des présences');
            setMessageType('danger');
        } finally {
            setLoading(false);
        }
    };

    const startScanning = async () => {
        try {
            setIsScanning(true);
            setMessage('Démarrage du scanner...');
            setMessageType('info');

            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'environment' } 
            });
            
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                videoRef.current.play();

                scannerRef.current = new QrScanner(
                    videoRef.current,
                    (result) => handleScan(result.data),
                    {
                        highlightScanRegion: true,
                        highlightCodeOutline: true,
                    }
                );

                await scannerRef.current.start();
                setMessage('Scanner prêt - Pointez vers un QR code du personnel');
                setMessageType('success');
            }
        } catch (error) {
            console.error('Erreur caméra:', error);
            setMessage('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
            setMessageType('danger');
            setIsScanning(false);
        }
    };

    const stopScanning = () => {
        if (scannerRef.current) {
            scannerRef.current.destroy();
            scannerRef.current = null;
        }
        
        if (videoRef.current && videoRef.current.srcObject) {
            const tracks = videoRef.current.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            videoRef.current.srcObject = null;
        }
        
        setIsScanning(false);
        setMessage('Scanner arrêté');
        setMessageType('info');
    };

    const handleScan = async (qrCode) => {
        try {
            setMessage('Traitement du scan...');
            setMessageType('info');
            
            const response = await secureApiEndpoints.staff.scanQR({
                staff_qr_code: qrCode,
                supervisor_id: user.id,
                event_type: 'auto'
            });

            if (response.success) {
                const { staff_member, attendance, event_type } = response.data;
                
                setCurrentScan({
                    staffMember: staff_member,
                    attendance: attendance,
                    eventType: event_type
                });

                const eventLabel = event_type === 'entry' ? 'Entrée' : 'Sortie';
                const staffTypeLabel = staffTypes[staff_member.staff_type]?.label || staff_member.role;
                
                setToastMessage(
                    `${eventLabel} enregistrée: ${staff_member.name} (${staffTypeLabel})`
                );
                setShowToast(true);

                // Recharger les données
                loadDailyAttendances();
                
                // Son de succès (optionnel)
                if ('vibrate' in navigator) {
                    navigator.vibrate(200);
                }

            } else {
                setMessage(response.message || 'Erreur lors du scan');
                setMessageType('danger');
                
                if ('vibrate' in navigator) {
                    navigator.vibrate([100, 100, 100]);
                }
            }
        } catch (error) {
            console.error('Erreur scan:', error);
            setMessage('Erreur lors du traitement du scan');
            setMessageType('danger');
        }
    };

    const getStaffTypeIcon = (staffType) => {
        const typeConfig = staffTypes[staffType];
        if (!typeConfig) return People;
        
        const IconComponent = typeConfig.icon;
        return <IconComponent size={16} />;
    };

    const getStaffTypeBadge = (staffType) => {
        const typeConfig = staffTypes[staffType] || { label: staffType, color: 'secondary' };
        return (
            <Badge bg={typeConfig.color} className="d-flex align-items-center gap-1">
                {getStaffTypeIcon(staffType)}
                {typeConfig.label}
            </Badge>
        );
    };

    const formatTime = (timestamp) => {
        return new Date(timestamp).toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getEventBadge = (eventType) => {
        if (eventType === 'entry') {
            return <Badge bg="success"><ArrowRightCircle size={12} className="me-1" />Entrée</Badge>;
        } else if (eventType === 'exit') {
            return <Badge bg="danger"><ArrowLeftCircle size={12} className="me-1" />Sortie</Badge>;
        }
        return <Badge bg="info">{eventType}</Badge>;
    };

    const renderStatsCards = () => {
        return (
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="text-center border-primary">
                        <Card.Body>
                            <h5 className="text-primary">{stats.total_present || 0}</h5>
                            <small className="text-muted">Présents</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center border-danger">
                        <Card.Body>
                            <h5 className="text-danger">{stats.total_absent || 0}</h5>
                            <small className="text-muted">Absents</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center border-warning">
                        <Card.Body>
                            <h5 className="text-warning">{stats.total_late || 0}</h5>
                            <small className="text-muted">En retard</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center border-info">
                        <Card.Body>
                            <h5 className="text-info">
                                {stats.by_staff_type ? Object.keys(stats.by_staff_type).length : 0}
                            </h5>
                            <small className="text-muted">Types présents</small>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        );
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <h2 className="d-flex align-items-center gap-2">
                        <People size={32} className="text-primary" />
                        Personnel Présence
                    </h2>
                    <p className="text-muted">
                        Gestion des présences du personnel - Surveillant Général
                    </p>
                </Col>
            </Row>

            {/* Message d'état */}
            {message && (
                <Alert variant={messageType} className="mb-4">
                    {messageType === 'danger' && <ExclamationTriangle className="me-2" />}
                    {messageType === 'success' && <CheckCircleFill className="me-2" />}
                    {messageType === 'info' && <InfoCircle className="me-2" />}
                    {message}
                </Alert>
            )}

            {/* Statistiques */}
            {renderStatsCards()}

            {/* Contrôles */}
            <Row className="mb-4">
                <Col md={4}>
                    <Form.Group>
                        <Form.Label>Date</Form.Label>
                        <Form.Control
                            type="date"
                            value={selectedDate}
                            onChange={(e) => setSelectedDate(e.target.value)}
                        />
                    </Form.Group>
                </Col>
                <Col md={4}>
                    <Form.Group>
                        <Form.Label>Type de personnel</Form.Label>
                        <Form.Select
                            value={selectedStaffType}
                            onChange={(e) => setSelectedStaffType(e.target.value)}
                        >
                            <option value="">Tous les types</option>
                            {Object.entries(staffTypes).map(([key, config]) => (
                                <option key={key} value={key}>{config.label}</option>
                            ))}
                        </Form.Select>
                    </Form.Group>
                </Col>
                <Col md={4} className="d-flex align-items-end">
                    <ButtonGroup className="w-100">
                        {!isScanning ? (
                            <Button 
                                variant="primary" 
                                onClick={startScanning}
                                className="d-flex align-items-center justify-content-center gap-2"
                            >
                                <QrCodeScan size={20} />
                                Démarrer Scanner
                            </Button>
                        ) : (
                            <Button 
                                variant="danger" 
                                onClick={stopScanning}
                                className="d-flex align-items-center justify-content-center gap-2"
                            >
                                <XCircleFill size={20} />
                                Arrêter Scanner
                            </Button>
                        )}
                    </ButtonGroup>
                </Col>
            </Row>

            {/* Scanner vidéo */}
            {isScanning && (
                <Row className="mb-4">
                    <Col>
                        <Card>
                            <Card.Body className="text-center">
                                <video
                                    ref={videoRef}
                                    style={{
                                        width: '100%',
                                        maxWidth: '500px',
                                        height: 'auto',
                                        borderRadius: '10px'
                                    }}
                                />
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Liste des présences */}
            <Row>
                <Col>
                    <Card>
                        <Card.Header className="d-flex justify-content-between align-items-center">
                            <h5 className="mb-0">
                                <Calendar className="me-2" />
                                Présences du {new Date(selectedDate).toLocaleDateString('fr-FR')}
                            </h5>
                            <Button 
                                variant="outline-primary" 
                                size="sm"
                                onClick={loadDailyAttendances}
                                disabled={loading}
                            >
                                {loading ? <Spinner size="sm" /> : 'Actualiser'}
                            </Button>
                        </Card.Header>
                        <Card.Body>
                            {loading ? (
                                <div className="text-center py-4">
                                    <Spinner animation="border" />
                                    <p className="mt-2">Chargement des présences...</p>
                                </div>
                            ) : dailyAttendances.length === 0 ? (
                                <div className="text-center py-4 text-muted">
                                    <PersonX size={48} className="mb-3" />
                                    <p>Aucune présence enregistrée pour cette date</p>
                                </div>
                            ) : (
                                <Table responsive hover>
                                    <thead>
                                        <tr>
                                            <th>Personnel</th>
                                            <th>Type</th>
                                            <th>Heure</th>
                                            <th>Événement</th>
                                            <th>Retard</th>
                                            <th>Superviseur</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {dailyAttendances.map((attendance) => (
                                            <tr key={attendance.id}>
                                                <td>
                                                    <div className="d-flex align-items-center">
                                                        <PersonBadge className="me-2 text-primary" />
                                                        <div>
                                                            <div className="fw-bold">{attendance.user?.name}</div>
                                                            <small className="text-muted">{attendance.user?.email}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{getStaffTypeBadge(attendance.staff_type)}</td>
                                                <td>
                                                    <Clock className="me-1" size={12} />
                                                    {formatTime(attendance.scanned_at)}
                                                </td>
                                                <td>{getEventBadge(attendance.event_type)}</td>
                                                <td>
                                                    {attendance.late_minutes > 0 ? (
                                                        <Badge bg="warning">+{attendance.late_minutes}min</Badge>
                                                    ) : (
                                                        <Badge bg="success">À l'heure</Badge>
                                                    )}
                                                </td>
                                                <td>
                                                    <small className="text-muted">
                                                        {attendance.supervisor?.name}
                                                    </small>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Toast notifications */}
            <ToastContainer position="top-end" className="p-3">
                <Toast 
                    show={showToast} 
                    onClose={() => setShowToast(false)} 
                    delay={3000} 
                    autohide
                    bg="success"
                >
                    <Toast.Body className="text-white">
                        <CheckCircleFill className="me-2" />
                        {toastMessage}
                    </Toast.Body>
                </Toast>
            </ToastContainer>
        </Container>
    );
};

export default StaffAttendanceScanner;