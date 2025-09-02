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
    Gear,
    PersonCircle,
    QrCode,
    BoxArrowInRight,
    BoxArrowRight,
    XCircle
} from 'react-bootstrap-icons';
import { useAuth } from '../../hooks/useAuth';
import { secureApiEndpoints } from '../../utils/apiMigration';
import QrScanner from 'qr-scanner';
import Swal from 'sweetalert2';

// Styles pour les animations
const styles = `
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
.scan-success-card {
    animation: fadeIn 0.5s ease-in-out;
}
.scan-avatar {
    animation: pulse 0.8s ease-in-out;
}
`;

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
    const [isProcessingScan, setIsProcessingScan] = useState(false);

    const videoRef = useRef(null);
    const scannerRef = useRef(null);
    const lastScanTime = useRef(0);
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
        },
        bibliothecaire: { 
            label: 'Bibliothécaires', 
            icon: PersonBadge, 
            color: 'info',
            bgColor: 'bg-info'
        },
        secretaire: { 
            label: 'Secrétaires', 
            icon: PersonCheck, 
            color: 'secondary',
            bgColor: 'bg-secondary'
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
            // PROTECTION CONTRE LES SCANS MULTIPLES
            const currentTime = Date.now();
            const timeSinceLastScan = currentTime - lastScanTime.current;
            
            // Empêcher les scans dans un délai de 3 secondes
            if (timeSinceLastScan < 3000) {
                console.log('Scan ignoré - trop récent:', timeSinceLastScan + 'ms');
                return;
            }
            
            // Empêcher les scans multiples si un scan est déjà en cours
            if (isProcessingScan) {
                console.log('Scan ignoré - traitement en cours');
                return;
            }
            
            setIsProcessingScan(true);
            lastScanTime.current = currentTime;
            
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
                    eventType: event_type,
                    scanTime: new Date()  // Ajouter l'heure exacte du scan
                });

                // Auto-masquer après 10 secondes
                setTimeout(() => {
                    setCurrentScan(null);
                }, 10000);

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
        } finally {
            // Remettre à zéro l'état de traitement
            setIsProcessingScan(false);
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

    // Fonction pour regrouper les présences par personne
    const groupAttendancesByPerson = () => {
        const grouped = {};
        
        dailyAttendances.forEach(attendance => {
            const userId = attendance.user?.id;
            if (!userId) return;
            
            if (!grouped[userId]) {
                grouped[userId] = {
                    user: attendance.user,
                    staff_type: attendance.staff_type,
                    late_minutes: 0,
                    entries: [],
                    exits: [],
                    supervisor: attendance.supervisor
                };
            }
            
            if (attendance.event_type === 'entry') {
                grouped[userId].entries.push(attendance);
                // Prendre le retard de la première entrée
                if (attendance.late_minutes > 0 && grouped[userId].late_minutes === 0) {
                    grouped[userId].late_minutes = attendance.late_minutes;
                }
            } else if (attendance.event_type === 'exit') {
                grouped[userId].exits.push(attendance);
            }
        });
        
        return Object.values(grouped);
    };

    return (
        <Container fluid className="py-4">
            <style>{styles}</style>
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
                    {isProcessingScan && <Spinner size="sm" className="me-2" />}
                    {message}
                    {isProcessingScan && <span className="ms-2 text-muted">(Traitement en cours...)</span>}
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

            {/* Informations du personnel scanné */}
            {currentScan && (
                <Row className="mb-4">
                    <Col>
                        <Card className="border-success shadow-sm scan-success-card">
                            <Card.Header className="bg-success text-white">
                                <h5 className="mb-0">
                                    <CheckCircleFill className="me-2" />
                                    Personnel Scanné - {currentScan.eventType === 'entry' ? 'Entrée' : 'Sortie'}
                                </h5>
                            </Card.Header>
                            <Card.Body>
                                <Row className="align-items-center">
                                    <Col md={3} className="text-center">
                                        <div className="position-relative">
                                            <div className={`rounded-circle ${staffTypes[currentScan.staffMember.staff_type]?.bgColor || 'bg-secondary'} d-flex align-items-center justify-content-center mx-auto scan-avatar`} 
                                                 style={{ width: '80px', height: '80px' }}>
                                                {React.createElement(staffTypes[currentScan.staffMember.staff_type]?.icon || PersonCircle, 
                                                    { size: 40, className: 'text-white' })}
                                            </div>
                                            <Badge 
                                                bg={staffTypes[currentScan.staffMember.staff_type]?.color || 'secondary'} 
                                                className="position-absolute top-0 start-100 translate-middle"
                                            >
                                                {staffTypes[currentScan.staffMember.staff_type]?.label || currentScan.staffMember.role}
                                            </Badge>
                                        </div>
                                    </Col>
                                    <Col md={6}>
                                        <h4 className="mb-2">{currentScan.staffMember.name}</h4>
                                        <p className="text-muted mb-1">
                                            <PersonBadge className="me-2" />
                                            Rôle: {currentScan.staffMember.role}
                                        </p>
                                        <p className="text-muted mb-1">
                                            <QrCode className="me-2" />
                                            Code QR: {currentScan.staffMember.expected_qr}
                                        </p>
                                        <p className="text-muted mb-0">
                                            <Clock className="me-2" />
                                            Scanné à: {currentScan.scanTime ? currentScan.scanTime.toLocaleTimeString('fr-FR') : 'N/A'}
                                        </p>
                                    </Col>
                                    <Col md={3} className="text-center">
                                        <div className={`alert alert-${currentScan.eventType === 'entry' ? 'success' : 'warning'} mb-0`}>
                                            <h5 className="mb-1">
                                                {currentScan.eventType === 'entry' ? (
                                                    <BoxArrowInRight className="me-2" />
                                                ) : (
                                                    <BoxArrowRight className="me-2" />
                                                )}
                                                {currentScan.eventType === 'entry' ? 'ENTRÉE' : 'SORTIE'}
                                            </h5>
                                            <small>
                                                {currentScan.attendance?.late_minutes > 0 && currentScan.eventType === 'entry' && (
                                                    <span className="text-danger">
                                                        Retard: {currentScan.attendance.late_minutes} min
                                                    </span>
                                                )}
                                            </small>
                                        </div>
                                        <Button 
                                            variant="outline-secondary" 
                                            size="sm" 
                                            className="mt-2"
                                            onClick={() => setCurrentScan(null)}
                                        >
                                            <XCircle className="me-1" />
                                            Fermer
                                        </Button>
                                    </Col>
                                </Row>
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
                                            <th className="text-center">Entrée(s)</th>
                                            <th className="text-center">Sortie(s)</th>
                                            <th>Retard</th>
                                            <th>Superviseur</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {groupAttendancesByPerson().map((personData) => (
                                            <tr key={personData.user.id}>
                                                <td>
                                                    <div className="d-flex align-items-center">
                                                        <PersonBadge className="me-2 text-primary" />
                                                        <div>
                                                            <div className="fw-bold">{personData.user.name}</div>
                                                            <small className="text-muted">{personData.user.email}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{getStaffTypeBadge(personData.staff_type)}</td>
                                                <td className="text-center">
                                                    {personData.entries.length > 0 ? (
                                                        <div>
                                                            {personData.entries.map((entry, index) => (
                                                                <Badge key={index} bg="success" className="me-1 mb-1">
                                                                    <ArrowRightCircle size={12} className="me-1" />
                                                                    {formatTime(entry.scanned_at)}
                                                                </Badge>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted">-</span>
                                                    )}
                                                </td>
                                                <td className="text-center">
                                                    {personData.exits.length > 0 ? (
                                                        <div>
                                                            {personData.exits.map((exit, index) => (
                                                                <Badge key={index} bg="danger" className="me-1 mb-1">
                                                                    <ArrowLeftCircle size={12} className="me-1" />
                                                                    {formatTime(exit.scanned_at)}
                                                                </Badge>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted">-</span>
                                                    )}
                                                </td>
                                                <td>
                                                    {personData.late_minutes > 0 ? (
                                                        <Badge bg="warning">+{personData.late_minutes}min</Badge>
                                                    ) : (
                                                        <Badge bg="success">À l'heure</Badge>
                                                    )}
                                                </td>
                                                <td>
                                                    <small className="text-muted">
                                                        {personData.supervisor?.name}
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