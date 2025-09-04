/**
 * Scanner de présences personnel avec contrôle géolocalisé
 * Version améliorée avec validation de zone obligatoire
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
    XCircle,
    GeoAlt,
    ShieldX
} from 'react-bootstrap-icons';
import { useAuth } from '../../hooks/useAuth';
import { secureApiEndpoints } from '../../utils/apiMigration';
import QrScanner from 'qr-scanner';
import Swal from 'sweetalert2';
import geolocationService from '../../services/geolocationService';
import GeolocationStatus from '../../components/GeolocationStatus';
import QRTestDebug from '../../components/QRTestDebug';
import audioService from '../../services/audioService';

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
.geolocation-checking {
    border: 2px solid #ffc107;
    background-color: #fff3cd;
}
.geolocation-blocked {
    border: 2px solid #dc3545;
    background-color: #f8d7da;
    animation: pulse 2s infinite;
}
.geolocation-blocked video {
    filter: grayscale(100%) opacity(0.5);
    pointer-events: none;
}
.geolocation-approved {
    border: 2px solid #28a745;
    background-color: #d4edda;
}
`;

const StaffAttendanceScannerGeolocated = () => {
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
    
    // États géolocalisation
    const [locationValidation, setLocationValidation] = useState(null);
    const [isLocationRequired, setIsLocationRequired] = useState(true);
    const [showLocationSettings, setShowLocationSettings] = useState(false);
    const [activeTab, setActiveTab] = useState('scanner');
    
    // États audio
    const [audioEnabled, setAudioEnabled] = useState(true);
    const [audioVolume, setAudioVolume] = useState(0.7);

    const videoRef = useRef(null);
    const scannerRef = useRef(null);
    const lastScanTime = useRef(0);
    const { user } = useAuth();
    const isOnline = true;

    // Types de personnel avec leurs icônes et couleurs
    const staffTypes = {
        teacher: { 
            label: 'Enseignant', 
            icon: PersonWorkspace, 
            color: 'primary',
            bgColor: '#e3f2fd'
        },
        accountant: { 
            label: 'Comptable', 
            icon: PersonBadge, 
            color: 'success',
            bgColor: '#f3e5f5'
        },
        supervisor: { 
            label: 'Surveillant', 
            icon: PersonCheck, 
            color: 'warning',
            bgColor: '#fff3e0'
        },
        admin: { 
            label: 'Administrateur', 
            icon: Gear, 
            color: 'danger',
            bgColor: '#ffebee'
        }
    };

    useEffect(() => {
        loadDailyAttendances();
        loadAttendanceStats();
        
        // Configurer le service audio
        audioService.setEnabled(audioEnabled);
        audioService.setVolume(audioVolume);
        
        // Ajouter les styles CSS
        if (!document.getElementById('scanner-styles')) {
            const styleElement = document.createElement('style');
            styleElement.id = 'scanner-styles';
            styleElement.textContent = styles;
            document.head.appendChild(styleElement);
        }

        return () => {
            stopScanning();
        };
    }, [selectedDate, selectedStaffType]);

    const handleLocationStatusChange = (status) => {
        setLocationValidation(status);
        
        if (status && !status.success) {
            // Si pas dans une zone autorisée, arrêter le scan
            if (isScanning) {
                stopScanning();
                setMessage('Scanner arrêté - Position non autorisée');
                setMessageType('warning');
            }
        }
    };

    const loadDailyAttendances = async () => {
        try {
            setLoading(true);
            const filters = {
                date: selectedDate,
                staff_type: selectedStaffType || undefined
            };

            const response = await secureApiEndpoints.staff.getDailyAttendances(filters);
            
            if (response.success) {
                const attendances = Array.isArray(response.data) ? response.data : [];
                setDailyAttendances(attendances);
            } else {
                console.error('Erreur chargement présences:', response.message);
                setDailyAttendances([]);
            }
        } catch (error) {
            console.error('Erreur chargement présences:', error);
            setDailyAttendances([]);
        } finally {
            setLoading(false);
        }
    };

    const loadAttendanceStats = async () => {
        try {
            const response = await secureApiEndpoints.staff.getAttendanceStats({
                date: selectedDate
            });
            
            if (response.success) {
                setStats(response.data || {});
            }
        } catch (error) {
            console.error('Erreur chargement stats:', error);
            setStats({});
        }
    };

    const validateLocationBeforeScan = async () => {
        if (!isLocationRequired) {
            return { success: true, message: 'Géolocalisation désactivée' };
        }

        try {
            const validation = await geolocationService.validateScanLocation();
            setLocationValidation(validation);
            
            if (!validation.success) {
                Swal.fire({
                    title: 'Position non autorisée',
                    text: validation.message,
                    icon: 'error',
                    confirmButtonText: 'Compris',
                    showCancelButton: true,
                    cancelButtonText: 'Voir les zones',
                    preConfirm: () => {
                        return validation;
                    }
                }).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel) {
                        setShowLocationSettings(true);
                    }
                });
                
                return validation;
            }
            
            return validation;
        } catch (error) {
            const errorValidation = {
                success: false,
                message: `Erreur validation position: ${error.message}`,
                error: error.message
            };
            setLocationValidation(errorValidation);
            return errorValidation;
        }
    };

    const startScanning = async () => {
        try {
            setMessage('Vérification de votre position...');
            setMessageType('info');

            // 1. Valider la géolocalisation AVANT de démarrer le scan
            const locationCheck = await validateLocationBeforeScan();
            
            if (!locationCheck.success) {
                setMessage('Scanner non disponible - ' + locationCheck.message);
                setMessageType('danger');
                return;
            }

            setMessage('Position validée. Démarrage du scanner...');
            setMessageType('success');

            // 2. Démarrer la caméra
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'environment' }
            });
            
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
                
                // 3. Initialiser le scanner QR
                scannerRef.current = new QrScanner(
                    videoRef.current,
                    handleScan,
                    {
                        returnDetailedScanResult: true,
                        highlightScanRegion: true,
                        highlightCodeOutline: true,
                    }
                );
                
                await scannerRef.current.start();
                setIsScanning(true);
                setMessage('Scanner actif - Zone autorisée ✅');
                setMessageType('success');
            }
        } catch (error) {
            console.error('Erreur démarrage scanner:', error);
            setMessage('Impossible de démarrer le scanner: ' + error.message);
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
            
            // Son de détection QR
            audioService.playDetection();
            
            // VÉRIFICATION PRÉVENTIVE DE GÉOLOCALISATION
            if (isLocationRequired && (!locationValidation || !locationValidation.success)) {
                setMessage('🚫 Scan impossible - Vous n\'êtes pas dans une zone autorisée');
                setMessageType('danger');
                
                // Son de blocage géographique
                audioService.playBlocked();
                
                // Vibration d'erreur
                if ('vibrate' in navigator) {
                    navigator.vibrate([200, 100, 200, 100, 200]);
                }
                return;
            }
            
            // PROTECTION CONTRE LES SCANS MULTIPLES
            const currentTime = Date.now();
            const timeSinceLastScan = currentTime - lastScanTime.current;
            
            if (timeSinceLastScan < 5000) {
                return;
            }
            
            if (isProcessingScan) {
                return;
            }
            
            setIsProcessingScan(true);
            lastScanTime.current = currentTime;
            
            setMessage('📱 QR Code détecté ! Vérification...');
            setMessageType('info');
            
            // Vibration pour indiquer la détection
            if ('vibrate' in navigator) {
                navigator.vibrate(100);
            }

            // DOUBLE VÉRIFICATION de la géolocalisation au moment du scan
            if (isLocationRequired) {
                const finalLocationCheck = await geolocationService.validateScanLocation();
                
                if (!finalLocationCheck.success) {
                    setMessage('❌ Scan annulé - Position non autorisée: ' + finalLocationCheck.message);
                    setMessageType('danger');
                    
                    // Vibration d'erreur
                    if ('vibrate' in navigator) {
                        navigator.vibrate([100, 100, 100]);
                    }
                    return;
                }
                
                setLocationValidation(finalLocationCheck);
            }
            
            setMessage('Position OK. Traitement du scan...');
            setMessageType('info');
            
            // Préparer les données à envoyer
            const scanData = {
                staff_qr_code: qrCode.data || qrCode,
                supervisor_id: user.id,
                event_type: 'auto'
            };

            // Ajouter les données de géolocalisation si disponibles
            if (locationValidation && locationValidation.position) {
                scanData.location_data = {
                    latitude: locationValidation.position.latitude,
                    longitude: locationValidation.position.longitude,
                    accuracy: locationValidation.position.accuracy,
                    timestamp: locationValidation.position.timestamp,
                    authorized_zone: locationValidation.validation.closestZone.zoneName,
                    distance_to_zone: locationValidation.validation.closestZone.distance
                };
            }

            // Envoyer le scan avec les données de géolocalisation
            const response = await secureApiEndpoints.staff.scanQR(scanData);

            if (response.success) {
                const { staff_member, attendance, event_type } = response.data;
                
                setCurrentScan({
                    staffMember: staff_member,
                    attendance: attendance,
                    eventType: event_type,
                    scanTime: new Date(),
                    locationData: scanData.location_data
                });

                setTimeout(() => {
                    setCurrentScan(null);
                }, 10000);

                const eventLabel = event_type === 'entry' ? 'Entrée' : 'Sortie';
                const staffTypeLabel = staffTypes[staff_member.staff_type]?.label || staff_member.role;
                
                setToastMessage(
                    `✅ ${eventLabel} enregistrée: ${staff_member.name} (${staffTypeLabel})`
                );
                setShowToast(true);
                setMessage(`✅ Scan réussi - ${eventLabel} de ${staff_member.name}`);
                setMessageType('success');

                // Sons selon le type d'événement
                if (event_type === 'entry') {
                    audioService.playEntry(); // Son montant pour entrée
                } else {
                    audioService.playExit(); // Son descendant pour sortie
                }

                // Recharger les données
                loadDailyAttendances();
                
                // Vibration de succès
                if ('vibrate' in navigator) {
                    navigator.vibrate(200);
                }

            } else {
                setMessage(response.message || 'Erreur lors du scan');
                setMessageType('danger');
                
                // Son d'erreur
                audioService.playError();
                
                if ('vibrate' in navigator) {
                    navigator.vibrate([100, 100, 100]);
                }
            }
        } catch (error) {
            console.error('Erreur scan:', error);
            setMessage('❌ Erreur lors du traitement du scan: ' + error.message);
            setMessageType('danger');
            
            // Son d'erreur
            audioService.playError();
        } finally {
            setIsProcessingScan(false);
        }
    };

    const canStartScan = () => {
        if (!isLocationRequired) return true;
        return locationValidation && locationValidation.success;
    };

    const getScanButtonVariant = () => {
        if (isScanning) return 'danger';
        if (!isLocationRequired) return 'success';
        if (locationValidation && locationValidation.success) return 'success';
        return 'secondary';
    };

    const getScanButtonText = () => {
        if (isScanning) return 'Arrêter Scanner';
        if (!isLocationRequired) return 'Démarrer Scanner';
        if (!locationValidation) return 'Vérifier Position d\'abord';
        if (locationValidation.success) return 'Démarrer Scanner';
        return 'Position non autorisée';
    };

    return (
        <Container fluid className="py-3">
            <Row>
                <Col md={8}>
                    {/* Contrôle géolocalisation - Mode automatique */}
                    <GeolocationStatus 
                        onStatusChange={handleLocationStatusChange}
                        autoTrack={true}
                        showControls={false}
                    />

                    {/* Interface Scanner avec onglets */}
                    <Card className={
                        locationValidation === null 
                            ? 'geolocation-checking' 
                            : locationValidation?.success 
                                ? 'geolocation-approved' 
                                : 'geolocation-blocked'
                    }>
                        <Card.Header className="d-flex justify-content-between align-items-center">
                            <h5 className="mb-0">
                                <QrCodeScan className="me-2" />
                                Scanner de Présence Personnel
                                {locationValidation && (
                                    locationValidation.success ? (
                                        <ShieldCheck className="ms-2 text-success" />
                                    ) : (
                                        <ShieldX className="ms-2 text-danger" />
                                    )
                                )}
                            </h5>
                            <div className="d-flex align-items-center gap-3">
                                <Form.Check
                                    type="switch"
                                    id="location-required"
                                    label="Géolocalisation requise"
                                    checked={isLocationRequired}
                                    onChange={(e) => setIsLocationRequired(e.target.checked)}
                                />
                                <Form.Check
                                    type="switch"
                                    id="audio-enabled"
                                    label="🔊 Sons"
                                    checked={audioEnabled}
                                    onChange={(e) => {
                                        setAudioEnabled(e.target.checked);
                                        audioService.setEnabled(e.target.checked);
                                        // Test audio quand activé
                                        if (e.target.checked) {
                                            audioService.playDetection();
                                        }
                                    }}
                                />
                                {isOnline ? 
                                    <Wifi className="text-success" /> : 
                                    <WifiOff className="text-danger" />
                                }
                            </div>
                        </Card.Header>
                        
                        {/* Onglets */}
                        <Tabs
                            activeKey={activeTab}
                            onSelect={(k) => setActiveTab(k)}
                            className="mb-3"
                            style={{ paddingLeft: '15px', paddingRight: '15px', paddingTop: '10px' }}
                        >
                            <Tab eventKey="scanner" title="Scanner Principal">
                                <Card.Body>
                            {/* Message de status géographique */}
                            {locationValidation !== null && !locationValidation.success && (
                                <Alert variant="danger" className="mb-3">
                                    <div className="d-flex align-items-center">
                                        <ShieldX className="me-2" />
                                        <div>
                                            <strong>🚫 Scan Impossible - Position Non Autorisée</strong>
                                            <br />
                                            <small>
                                                Vous devez être dans une zone autorisée pour scanner. 
                                                {locationValidation.message && <><br />{locationValidation.message}</>}
                                            </small>
                                        </div>
                                    </div>
                                </Alert>
                            )}

                            {locationValidation?.success && (
                                <Alert variant="success" className="mb-3">
                                    <div className="d-flex align-items-center">
                                        <ShieldCheck className="me-2" />
                                        <div>
                                            <strong>✅ Zone Autorisée - Scan Activé</strong>
                                            <br />
                                            <small>{locationValidation.message}</small>
                                        </div>
                                    </div>
                                </Alert>
                            )}

                            {message && (
                                <Alert variant={messageType} className="mb-3">
                                    <div className="d-flex align-items-center">
                                        {messageType === 'success' && <CheckCircleFill className="me-2" />}
                                        {messageType === 'danger' && <XCircleFill className="me-2" />}
                                        {messageType === 'warning' && <ExclamationTriangle className="me-2" />}
                                        {messageType === 'info' && <InfoCircle className="me-2" />}
                                        {message}
                                    </div>
                                </Alert>
                            )}

                            <Row>
                                <Col md={6}>
                                    <div className="video-container position-relative">
                                        <video 
                                            ref={videoRef}
                                            style={{ 
                                                width: '100%', 
                                                height: '300px', 
                                                objectFit: 'cover',
                                                borderRadius: '8px',
                                                backgroundColor: '#f8f9fa'
                                            }}
                                        />
                                        {isProcessingScan && (
                                            <div className="position-absolute top-50 start-50 translate-middle">
                                                <Spinner animation="border" variant="primary" />
                                            </div>
                                        )}
                                        {!isScanning && (
                                            <div className="position-absolute top-50 start-50 translate-middle text-center">
                                                <QrCode size={64} className="text-muted mb-2" />
                                                <p className="text-muted">Caméra inactive</p>
                                            </div>
                                        )}
                                    </div>
                                    
                                    <div className="d-grid mt-3">
                                        <Button 
                                            variant={getScanButtonVariant()}
                                            onClick={isScanning ? stopScanning : startScanning}
                                            disabled={!canStartScan() && !isScanning}
                                            size="lg"
                                        >
                                            {getScanButtonText()}
                                        </Button>
                                    </div>

                                    {!canStartScan() && isLocationRequired && (
                                        <Alert variant="danger" className="mt-2">
                                            <ShieldX className="me-2" />
                                            <strong>Scanner Bloqué</strong>
                                            <br />
                                            <small>
                                                Position géographique non autorisée. Le système détecte automatiquement votre position.
                                                {locationValidation && locationValidation.message && (
                                                    <><br />{locationValidation.message}</>
                                                )}
                                            </small>
                                        </Alert>
                                    )}
                                </Col>

                                <Col md={6}>
                                    {/* Informations du dernier scan */}
                                    {currentScan && (
                                        <Card className="scan-success-card border-success">
                                            <Card.Header className="bg-success text-white">
                                                <CheckCircleFill className="me-2" />
                                                Scan Réussi
                                            </Card.Header>
                                            <Card.Body>
                                                <div className="text-center mb-3">
                                                    <PersonCircle size={48} className="scan-avatar text-success" />
                                                </div>
                                                <h6 className="text-center">{currentScan.staffMember.name}</h6>
                                                <p className="text-center text-muted mb-2">
                                                    {staffTypes[currentScan.staffMember.staff_type]?.label || 'Personnel'}
                                                </p>
                                                <div className="d-flex justify-content-center mb-2">
                                                    {currentScan.eventType === 'entry' ? 
                                                        <Badge bg="success">
                                                            <BoxArrowInRight className="me-1" />
                                                            ENTRÉE
                                                        </Badge> : 
                                                        <Badge bg="warning">
                                                            <BoxArrowRight className="me-1" />
                                                            SORTIE
                                                        </Badge>
                                                    }
                                                </div>
                                                <p className="text-center small text-muted">
                                                    <Clock className="me-1" />
                                                    {currentScan.scanTime.toLocaleTimeString()}
                                                </p>
                                                
                                                {/* Informations géolocalisation */}
                                                {currentScan.locationData && (
                                                    <div className="mt-2 p-2 bg-light rounded">
                                                        <small className="text-muted">
                                                            <GeoAlt className="me-1" />
                                                            Zone: {currentScan.locationData.authorized_zone}
                                                            <br />
                                                            Distance: {currentScan.locationData.distance_to_zone}m
                                                            <br />
                                                            Précision: {currentScan.locationData.accuracy}m
                                                        </small>
                                                    </div>
                                                )}
                                            </Card.Body>
                                        </Card>
                                    )}

                                    {/* Statistiques rapides */}
                                    <Row className="g-2 mt-3">
                                        <Col xs={6}>
                                            <Card className="text-center">
                                                <Card.Body>
                                                    <People size={24} className="text-primary mb-1" />
                                                    <h6>{stats.total_present || 0}</h6>
                                                    <small className="text-muted">Présents</small>
                                                </Card.Body>
                                            </Card>
                                        </Col>
                                        <Col xs={6}>
                                            <Card className="text-center">
                                                <Card.Body>
                                                    <PersonCheck size={24} className="text-success mb-1" />
                                                    <h6>{stats.total_entries || 0}</h6>
                                                    <small className="text-muted">Entrées</small>
                                                </Card.Body>
                                            </Card>
                                        </Col>
                                    </Row>
                                </Col>
                            </Row>
                                </Card.Body>
                            </Tab>
                            
                            <Tab eventKey="debug" title="🐛 Debug QR">
                                <div className="p-3">
                                    <QRTestDebug />
                                    
                                    {/* Test Audio */}
                                    <Card className="mt-3 border-info">
                                        <Card.Header className="bg-info text-white">
                                            🔊 Test Audio
                                        </Card.Header>
                                        <Card.Body>
                                            <Row className="g-2">
                                                <Col xs={6} sm={4}>
                                                    <Button 
                                                        variant="outline-primary" 
                                                        size="sm" 
                                                        className="w-100"
                                                        onClick={() => audioService.playDetection()}
                                                    >
                                                        🎵 Détection
                                                    </Button>
                                                </Col>
                                                <Col xs={6} sm={4}>
                                                    <Button 
                                                        variant="outline-success" 
                                                        size="sm" 
                                                        className="w-100"
                                                        onClick={() => audioService.playEntry()}
                                                    >
                                                        📈 Entrée
                                                    </Button>
                                                </Col>
                                                <Col xs={6} sm={4}>
                                                    <Button 
                                                        variant="outline-warning" 
                                                        size="sm" 
                                                        className="w-100"
                                                        onClick={() => audioService.playExit()}
                                                    >
                                                        📉 Sortie
                                                    </Button>
                                                </Col>
                                                <Col xs={6} sm={4}>
                                                    <Button 
                                                        variant="outline-danger" 
                                                        size="sm" 
                                                        className="w-100"
                                                        onClick={() => audioService.playError()}
                                                    >
                                                        ❌ Erreur
                                                    </Button>
                                                </Col>
                                                <Col xs={6} sm={4}>
                                                    <Button 
                                                        variant="outline-dark" 
                                                        size="sm" 
                                                        className="w-100"
                                                        onClick={() => audioService.playBlocked()}
                                                    >
                                                        🚫 Bloqué
                                                    </Button>
                                                </Col>
                                                <Col xs={6} sm={4}>
                                                    <Button 
                                                        variant="outline-secondary" 
                                                        size="sm" 
                                                        className="w-100"
                                                        onClick={() => audioService.testAllSounds()}
                                                    >
                                                        🎼 Tous
                                                    </Button>
                                                </Col>
                                            </Row>
                                            
                                            <Alert variant="info" className="mt-3 mb-0">
                                                <strong>Test des notifications audio :</strong><br />
                                                <small>
                                                    • Détection - Son court à 600Hz<br />
                                                    • Entrée - Son montant à 700Hz<br />
                                                    • Sortie - Son descendant à 500Hz<br />
                                                    • Erreur - Double bip grave<br />
                                                    • Bloqué - Triple bip décroissant
                                                </small>
                                            </Alert>
                                        </Card.Body>
                                    </Card>
                                </div>
                            </Tab>
                        </Tabs>
                    </Card>
                </Col>

                <Col md={4}>
                    {/* Filtres et liste des présences */}
                    <Card>
                        <Card.Header>
                            <Calendar className="me-2" />
                            Présences du Jour
                        </Card.Header>
                        <Card.Body>
                            <Form.Group className="mb-3">
                                <Form.Label>Date</Form.Label>
                                <Form.Control
                                    type="date"
                                    value={selectedDate}
                                    onChange={(e) => setSelectedDate(e.target.value)}
                                />
                            </Form.Group>

                            <Form.Group className="mb-3">
                                <Form.Label>Type de Personnel</Form.Label>
                                <Form.Select
                                    value={selectedStaffType}
                                    onChange={(e) => setSelectedStaffType(e.target.value)}
                                >
                                    <option value="">Tous</option>
                                    {Object.entries(staffTypes).map(([key, type]) => (
                                        <option key={key} value={key}>{type.label}</option>
                                    ))}
                                </Form.Select>
                            </Form.Group>

                            {loading ? (
                                <div className="text-center">
                                    <Spinner animation="border" />
                                </div>
                            ) : (
                                <div style={{ maxHeight: '400px', overflowY: 'auto' }}>
                                    {!Array.isArray(dailyAttendances) || dailyAttendances.length === 0 ? (
                                        <p className="text-muted text-center">Aucune présence enregistrée</p>
                                    ) : (
                                        dailyAttendances.map((attendance, index) => (
                                            <div key={index} className="border-bottom pb-2 mb-2">
                                                <div className="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong>{attendance.staff_name}</strong>
                                                        <br />
                                                        <Badge bg={staffTypes[attendance.staff_type]?.color || 'secondary'}>
                                                            {staffTypes[attendance.staff_type]?.label || attendance.staff_type}
                                                        </Badge>
                                                    </div>
                                                    <div className="text-end">
                                                        {attendance.entry_time && (
                                                            <Badge bg="success" className="d-block mb-1">
                                                                <BoxArrowInRight className="me-1" />
                                                                {new Date(`2000-01-01 ${attendance.entry_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                            </Badge>
                                                        )}
                                                        {attendance.exit_time && (
                                                            <Badge bg="warning">
                                                                <BoxArrowRight className="me-1" />
                                                                {new Date(`2000-01-01 ${attendance.exit_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
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
                >
                    <Toast.Header>
                        <CheckCircleFill className="text-success me-2" />
                        <strong className="me-auto">Scan Réussi</strong>
                    </Toast.Header>
                    <Toast.Body>{toastMessage}</Toast.Body>
                </Toast>
            </ToastContainer>
        </Container>
    );
};

export default StaffAttendanceScannerGeolocated;