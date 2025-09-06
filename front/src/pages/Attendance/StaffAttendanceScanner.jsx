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

// Styles pour les animations et QrScanner
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
/* Styles pour QrScanner */
.qr-scanner-region-highlight {
    position: absolute !important;
}
.qr-scanner-region-highlight-svg {
    position: absolute !important;
    box-sizing: border-box !important;
    border: 2px solid #1a73e8 !important;
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
    const [cheatAttempts, setCheatAttempts] = useState(0);
    const [recentScans, setRecentScans] = useState(new Map()); // Map pour stocker les scans récents par QR code

    const videoRef = useRef(null);
    const scannerRef = useRef(null);
    const lastScanTime = useRef(0);
    const canvasRef = useRef(null);
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

    // Nettoyage automatique des anciens scans pour éviter l'accumulation en mémoire
    useEffect(() => {
        const cleanup = setInterval(() => {
            const currentTime = Date.now();
            const CLEANUP_THRESHOLD = 60000; // Nettoyer les entrées plus anciennes que 1 minute
            
            setRecentScans(prev => {
                const cleaned = new Map();
                for (const [qrCode, timestamp] of prev.entries()) {
                    if (currentTime - timestamp < CLEANUP_THRESHOLD) {
                        cleaned.set(qrCode, timestamp);
                    }
                }
                
                if (cleaned.size !== prev.size) {
                    console.log(`🧹 Nettoyage automatique: ${prev.size - cleaned.size} anciens scans supprimés`);
                }
                
                return cleaned;
            });
        }, 30000); // Nettoyage toutes les 30 secondes

        return () => clearInterval(cleanup);
    }, []);

    // ================== DÉTECTION ANTI-TRICHE ==================
    
    /**
     * Détecte si l'image provient d'un écran ou d'un papier physique
     */
    const detectScreenVsPaper = (imageData, width, height) => {
        const data = imageData.data;
        let screenScore = 0;
        let paperScore = 0;
        
        // 1. DÉTECTION DE PIXELS - Recherche de grilles régulières
        const pixelPatterns = analyzePixelPatterns(data, width, height);
        if (pixelPatterns.hasRegularGrid) {
            screenScore += 30;
            console.log('🔍 Grille de pixels détectée (ÉCRAN)');
        } else {
            paperScore += 20;
        }
        
        // 2. ANALYSE DE RÉFLEXION - Zones très brillantes
        const reflectionData = analyzeReflection(data, width, height);
        if (reflectionData.hasSpecularReflection) {
            screenScore += 25;
            console.log('✨ Reflet spéculaire détecté (ÉCRAN)');
        }
        if (reflectionData.hasDiffuseReflection) {
            paperScore += 15;
        }
        
        // 3. ANALYSE DE TEXTURE - Uniformité vs rugosité
        const textureData = analyzeTexture(data, width, height);
        if (textureData.isUniform) {
            screenScore += 20;
            console.log('📱 Surface uniforme détectée (ÉCRAN)');
        }
        if (textureData.hasNaturalVariation) {
            paperScore += 25;
        }
        
        // 4. DÉTECTION DE NETTETÉ EXCESSIVE
        const sharpness = analyzeSharpness(data, width, height);
        if (sharpness.tooSharp) {
            screenScore += 15;
            console.log('🔪 Bords trop nets détectés (ÉCRAN)');
        }
        
        const confidence = Math.abs(screenScore - paperScore) / 100;
        const isScreen = screenScore > paperScore;
        
        console.log(`🎯 Score écran: ${screenScore}, papier: ${paperScore}, confiance: ${confidence.toFixed(2)}`);
        
        return {
            isScreen,
            confidence,
            screenScore,
            paperScore,
            details: {
                pixelPatterns,
                reflectionData,
                textureData,
                sharpness
            }
        };
    };
    
    /**
     * Analyse les patterns de pixels pour détecter les grilles d'écran
     */
    const analyzePixelPatterns = (data, width, height) => {
        let gridDetected = 0;
        const sampleSize = Math.min(width, height, 200); // Échantillon pour performance
        
        // Recherche de patterns réguliers horizontaux et verticaux
        for (let y = 0; y < sampleSize; y += 10) {
            for (let x = 0; x < sampleSize; x += 10) {
                const idx = (y * width + x) * 4;
                const nextXIdx = (y * width + (x + 3)) * 4;
                const nextYIdx = ((y + 3) * width + x) * 4;
                
                if (nextXIdx < data.length && nextYIdx < data.length) {
                    // Recherche de patterns RGB répétitifs
                    const rDiffX = Math.abs(data[idx] - data[nextXIdx]);
                    const gDiffX = Math.abs(data[idx + 1] - data[nextXIdx + 1]);
                    const bDiffX = Math.abs(data[idx + 2] - data[nextXIdx + 2]);
                    
                    const rDiffY = Math.abs(data[idx] - data[nextYIdx]);
                    const gDiffY = Math.abs(data[idx + 1] - data[nextYIdx + 1]);
                    const bDiffY = Math.abs(data[idx + 2] - data[nextYIdx + 2]);
                    
                    // Si les différences suivent un pattern régulier
                    if ((rDiffX < 10 && gDiffX < 10 && bDiffX < 10) || 
                        (rDiffY < 10 && gDiffY < 10 && bDiffY < 10)) {
                        gridDetected++;
                    }
                }
            }
        }
        
        const gridRatio = gridDetected / ((sampleSize / 10) * (sampleSize / 10));
        return {
            hasRegularGrid: gridRatio > 0.5, // Seuil réduit de 0.7 à 0.5 pour être plus sensible
            gridRatio
        };
    };
    
    /**
     * Analyse les reflets pour différencier écran brillant vs papier mat
     */
    const analyzeReflection = (data, width, height) => {
        let brightPixels = 0;
        let veryBrightPixels = 0;
        let totalPixels = 0;
        
        for (let i = 0; i < data.length; i += 16) { // Échantillonage pour performance
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            const brightness = (r + g + b) / 3;
            
            totalPixels++;
            if (brightness > 200) brightPixels++;
            if (brightness > 240) veryBrightPixels++;
        }
        
        const brightRatio = brightPixels / totalPixels;
        const veryBrightRatio = veryBrightPixels / totalPixels;
        
        return {
            hasSpecularReflection: veryBrightRatio > 0.02, // Réduit de 5% à 2% pour être plus sensible
            hasDiffuseReflection: brightRatio > 0.1 && veryBrightRatio < 0.02,
            brightRatio,
            veryBrightRatio
        };
    };
    
    /**
     * Analyse la texture pour détecter l'uniformité d'un écran vs rugosité papier
     */
    const analyzeTexture = (data, width, height) => {
        let variations = 0;
        let totalComparisons = 0;
        
        // Analyse des variations locales
        for (let y = 1; y < height - 1; y += 5) {
            for (let x = 1; x < width - 1; x += 5) {
                const centerIdx = (y * width + x) * 4;
                const rightIdx = (y * width + (x + 1)) * 4;
                const downIdx = ((y + 1) * width + x) * 4;
                
                if (rightIdx < data.length && downIdx < data.length) {
                    const centerBright = (data[centerIdx] + data[centerIdx + 1] + data[centerIdx + 2]) / 3;
                    const rightBright = (data[rightIdx] + data[rightIdx + 1] + data[rightIdx + 2]) / 3;
                    const downBright = (data[downIdx] + data[downIdx + 1] + data[downIdx + 2]) / 3;
                    
                    const variationRight = Math.abs(centerBright - rightBright);
                    const variationDown = Math.abs(centerBright - downBright);
                    
                    variations += variationRight + variationDown;
                    totalComparisons += 2;
                }
            }
        }
        
        const avgVariation = variations / totalComparisons;
        
        return {
            isUniform: avgVariation < 10, // Très peu de variation = écran
            hasNaturalVariation: avgVariation > 20, // Beaucoup de variation = papier
            avgVariation
        };
    };
    
    /**
     * Analyse la netteté pour détecter les bords trop parfaits d'un écran
     */
    const analyzeSharpness = (data, width, height) => {
        let sharpEdges = 0;
        let totalEdges = 0;
        
        // Détection des bords avec filtre Sobel simplifié
        for (let y = 1; y < height - 1; y += 10) {
            for (let x = 1; x < width - 1; x += 10) {
                const idx = (y * width + x) * 4;
                const leftIdx = (y * width + (x - 1)) * 4;
                const rightIdx = (y * width + (x + 1)) * 4;
                
                if (leftIdx >= 0 && rightIdx < data.length) {
                    const center = (data[idx] + data[idx + 1] + data[idx + 2]) / 3;
                    const left = (data[leftIdx] + data[leftIdx + 1] + data[leftIdx + 2]) / 3;
                    const right = (data[rightIdx] + data[rightIdx + 1] + data[rightIdx + 2]) / 3;
                    
                    const edgeStrength = Math.abs(center - left) + Math.abs(center - right);
                    
                    if (edgeStrength > 50) {
                        totalEdges++;
                        if (edgeStrength > 100) sharpEdges++; // Bord très net
                    }
                }
            }
        }
        
        const sharpnessRatio = totalEdges > 0 ? sharpEdges / totalEdges : 0;
        
        return {
            tooSharp: sharpnessRatio > 0.4, // Réduit de 60% à 40% pour être plus sensible
            sharpnessRatio,
            totalEdges,
            sharpEdges
        };
    };
    
    /**
     * Capture une image de la vidéo pour analyse
     */
    const captureVideoFrame = () => {
        if (!videoRef.current || !canvasRef.current) return null;
        
        const video = videoRef.current;
        const canvas = canvasRef.current;
        const ctx = canvas.getContext('2d');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        ctx.drawImage(video, 0, 0);
        
        return ctx.getImageData(0, 0, canvas.width, canvas.height);
    };
    
    // ================== FIN DÉTECTION ANTI-TRICHE ==================

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
            setMessage('');
            setMessageType('info');
            setIsScanning(true);
            
            console.log('🔍 Tentative de démarrage du scanner...');
            
            // Vérifier d'abord les permissions de caméra
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                console.log('✅ Permissions caméra accordées');
                stream.getTracks().forEach(track => track.stop()); // Arrêter le stream de test
            } catch (permError) {
                console.error('❌ Permissions caméra refusées:', permError);
                throw new Error('Permission denied: ' + permError.message);
            }
            
            // Vérifier les caméras disponibles
            const cameras = await QrScanner.listCameras(true);
            console.log('📷 Caméras disponibles:', cameras);
            
            if (cameras.length === 0) {
                throw new Error('Aucune caméra trouvée sur cet appareil');
            }
            
            if (videoRef.current) {
                // Configuration EXACTEMENT identique au scanner élèves
                scannerRef.current = new QrScanner(
                    videoRef.current,
                    (result) => handleScan(result.data),
                    {
                        onDecodeError: error => {
                            // Réduire le bruit des erreurs de décodage
                            // console.log('Scan decode error:', error);
                        },
                        highlightScanRegion: true,
                        highlightCodeOutline: true,
                        preferredCamera: cameras.length > 1 ? 'environment' : cameras[0].id, // Utiliser la première caméra si une seule
                        maxScansPerSecond: 3, // Réduire pour éviter la surcharge
                        returnDetailedScanResult: false,
                    }
                );
                
                console.log('⏳ Démarrage du scanner QR...');
                await scannerRef.current.start();
                console.log('✅ Scanner QR démarré avec succès');
                
                setMessage('Scanner prêt - Pointez vers un QR code du personnel');
                setMessageType('success');
            }
        } catch (error) {
            console.error('❌ Erreur lors du démarrage du scanner:', error);
            console.error('Type d\'erreur:', error.constructor.name);
            console.error('Message:', error.message);
            
            let errorMessage = 'Impossible d\'accéder à la caméra.';
            let debugInfo = '';
            
            if (error.name === 'NotAllowedError' || error.message.includes('Permission denied')) {
                errorMessage = '🚫 Accès à la caméra refusé par le navigateur.';
                debugInfo = 'Cliquez sur l\'icône 🔒 dans la barre d\'adresse et autorisez la caméra.';
            } else if (error.name === 'NotFoundError' || error.message.includes('Camera not found')) {
                errorMessage = '📷 Aucune caméra trouvée.';
                debugInfo = 'Vérifiez qu\'une caméra est connectée et fonctionnelle.';
            } else if (error.name === 'NotSupportedError') {
                errorMessage = '🌐 Votre navigateur ne supporte pas l\'accès à la caméra.';
                debugInfo = 'Essayez avec Chrome, Firefox ou Safari récent.';
            } else if (error.name === 'NotReadableError') {
                errorMessage = '⚠️ Caméra occupée par une autre application.';
                debugInfo = 'Fermez les autres applications utilisant la caméra.';
            } else {
                errorMessage = '🔧 Erreur technique du scanner.';
                debugInfo = `Détails: ${error.message}`;
            }
            
            setMessage(`${errorMessage} ${debugInfo}`);
            setMessageType('danger');
            setIsScanning(false);
        }
    };

    const stopScanning = () => {
        if (scannerRef.current) {
            scannerRef.current.stop();
            scannerRef.current.destroy();
            scannerRef.current = null;
        }
        
        // Réinitialiser le cache des scans récents quand le scanner est arrêté
        setRecentScans(new Map());
        console.log('🧹 Cache des scans récents vidé lors de l\'arrêt du scanner');
        
        setIsScanning(false);
        setMessage('Scanner arrêté');
        setMessageType('info');
    };

    const handleScan = async (qrCode) => {
        try {
            // PROTECTION CONTRE LES SCANS MULTIPLES
            const currentTime = Date.now();
            const timeSinceLastScan = currentTime - lastScanTime.current;
            
            // Empêcher les scans dans un délai de 3 secondes (protection globale)
            if (timeSinceLastScan < 3000) {
                console.log('Scan ignoré - trop récent:', timeSinceLastScan + 'ms');
                return;
            }
            
            // NOUVEAU: Protection contre les scans répétés de la même personne
            const lastScanForThisPerson = recentScans.get(qrCode);
            const COOLDOWN_DURATION = 30000; // 30 secondes de cooldown par personne
            
            if (lastScanForThisPerson && (currentTime - lastScanForThisPerson) < COOLDOWN_DURATION) {
                const remainingTime = Math.ceil((COOLDOWN_DURATION - (currentTime - lastScanForThisPerson)) / 1000);
                console.log(`Scan ignoré - personne déjà scannée récemment. Attendre ${remainingTime}s`);
                
                setMessage(`⏱️ Cette personne a déjà été scannée récemment. Attendez ${remainingTime} secondes.`);
                setMessageType('warning');
                
                // Vibration courte pour indiquer le cooldown
                if ('vibrate' in navigator) {
                    navigator.vibrate([100]);
                }
                
                return;
            }
            
            // Empêcher les scans multiples si un scan est déjà en cours
            if (isProcessingScan) {
                console.log('Scan ignoré - traitement en cours');
                return;
            }
            
            setIsProcessingScan(true);
            lastScanTime.current = currentTime;
            
            // Enregistrer ce scan pour le cooldown
            setRecentScans(prev => new Map(prev).set(qrCode, currentTime));
            
            setMessage('Vérification anti-triche...');
            setMessageType('info');
            
            // ============ VALIDATION ANTI-TRICHE ============
            try {
                console.log('🛡️ Début validation anti-triche');
                
                // Capturer l'image actuelle de la vidéo pour analyse
                const imageData = captureVideoFrame();
                if (!imageData) {
                    console.warn('⚠️ Impossible de capturer l\'image pour validation');
                    // Continuer sans validation si capture échoue
                } else {
                    // Analyser l'image pour détecter écran vs papier
                    const detection = detectScreenVsPaper(imageData, imageData.width, imageData.height);
                    
                    console.log('🔍 Résultat détection:', detection);
                    
                    // Si détection d'écran avec haute confiance, bloquer le scan (seuil réduit pour être plus strict)
                    if (detection.isScreen && detection.confidence > 0.3) {
                        console.error('🚫 TRICHE DÉTECTÉE - Carte affichée sur écran');
                        
                        // Incrémenter le compteur de tentatives de triche
                        setCheatAttempts(prev => prev + 1);
                        
                        setMessage('⚠️ TRICHE DÉTECTÉE: Utilisation d\'un écran interdite. Présentez la carte physique uniquement.');
                        setMessageType('danger');
                        
                        // Vibration pour alerter
                        if ('vibrate' in navigator) {
                            navigator.vibrate([200, 100, 200, 100, 200]);
                        }
                        
                        // Log pour tracking
                        console.log('📊 Détails de la triche:', {
                            screenScore: detection.screenScore,
                            paperScore: detection.paperScore,
                            confidence: detection.confidence,
                            qrCode: qrCode.substring(0, 10) + '...',
                            timestamp: new Date().toISOString(),
                            details: detection.details
                        });
                        
                        return; // Arrêter le traitement
                    }
                    
                    // Si pas de triche détectée, continuer normalement
                    console.log('✅ Validation anti-triche réussie - Carte physique détectée');
                    setMessage('✅ Carte physique validée - Traitement du scan...');
                    setMessageType('success');
                }
            } catch (antiCheatError) {
                console.error('Erreur validation anti-triche:', antiCheatError);
                // Continuer le scan en cas d'erreur de validation
                setMessage('Validation en cours - Traitement du scan...');
                setMessageType('info');
            }
            // ============ FIN VALIDATION ANTI-TRICHE ============
            
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
                            <h5 className="text-info">{stats.total_entries || 0}</h5>
                            <small className="text-muted">Entrées</small>
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
                    {/* Alerte de sécurité compacte */}
                    <div className="w-100">
                        <div className="mb-2">
                            <div className="d-flex justify-content-between align-items-center">
                                <small className="text-muted d-flex align-items-center">
                                    <ShieldCheck size={16} className="me-1 text-success" />
                                    Anti-triche activé
                                </small>
                                <div className="d-flex align-items-center gap-3">
                                    {recentScans.size > 0 && (
                                        <small className="text-info d-flex align-items-center">
                                            <Clock size={14} className="me-1" />
                                            {recentScans.size} scan{recentScans.size > 1 ? 's' : ''} récent{recentScans.size > 1 ? 's' : ''}
                                        </small>
                                    )}
                                    {cheatAttempts > 0 && (
                                        <small className="text-danger d-flex align-items-center">
                                            <ExclamationTriangle size={14} className="me-1" />
                                            {cheatAttempts} triche{cheatAttempts > 1 ? 's' : ''} détectée{cheatAttempts > 1 ? 's' : ''}
                                        </small>
                                    )}
                                </div>
                            </div>
                        </div>
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

            {/* Scanner vidéo - TOUJOURS présent dans le DOM */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Body className="text-center">
                            <div className="scanner-container" style={{ 
                                position: 'relative',
                                maxWidth: '500px',
                                margin: '0 auto'
                            }}>
                                <video
                                    ref={videoRef}
                                    style={{
                                        width: '100%',
                                        height: 'auto',
                                        border: '2px solid #dee2e6',
                                        borderRadius: '10px',
                                        backgroundColor: '#f8f9fa',
                                        display: isScanning ? 'block' : 'none'
                                    }}
                                />
                                {!isScanning && (
                                    <div style={{
                                        width: '100%',
                                        height: '300px',
                                        border: '2px solid #dee2e6',
                                        borderRadius: '10px',
                                        backgroundColor: '#f8f9fa',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        color: '#6c757d'
                                    }}>
                                        <div className="text-center">
                                            <QrCodeScan size={48} className="mb-3" />
                                            <p>Scanner prêt à démarrer</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                            
                            {/* Canvas caché pour l'analyse anti-triche */}
                            <canvas 
                                ref={canvasRef}
                                style={{ display: 'none' }}
                                aria-hidden="true"
                            />
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

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