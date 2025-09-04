/**
 * Composant de test pour déboguer les QR codes
 */

import React, { useState, useRef } from 'react';
import { Card, Button, Alert, Form } from 'react-bootstrap';
import { Camera, Bug, Lightning } from 'react-bootstrap-icons';
import QrScanner from 'qr-scanner';

const QRTestDebug = () => {
    const [isScanning, setIsScanning] = useState(false);
    const [lastScan, setLastScan] = useState(null);
    const [scanHistory, setScanHistory] = useState([]);
    const [testCode, setTestCode] = useState('STAFF_001');
    const videoRef = useRef(null);
    const scannerRef = useRef(null);

    const startDebugScan = async () => {
        try {
            console.log('🚀 Démarrage du scanner debug...');
            
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'environment' }
            });
            
            console.log('📱 Stream vidéo obtenu:', stream);
            console.log('📱 Pistes vidéo:', stream.getVideoTracks());
            
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
                
                console.log('📺 Vidéo en cours de lecture');
                console.log('📺 Dimensions vidéo:', videoRef.current.videoWidth, 'x', videoRef.current.videoHeight);
                
                scannerRef.current = new QrScanner(
                    videoRef.current,
                    (result) => {
                        console.log('🎯 QR Code détecté (debug):', result);
                        console.log('🎯 Données:', result.data || result);
                        console.log('🎯 Type:', typeof result);
                        console.log('🎯 Résultat complet:', JSON.stringify(result, null, 2));
                        
                        const scanData = {
                            timestamp: new Date().toLocaleTimeString(),
                            data: result.data || result,
                            type: typeof result,
                            raw: result
                        };
                        
                        setLastScan(scanData);
                        setScanHistory(prev => [scanData, ...prev.slice(0, 4)]);
                        
                        // Vibration
                        if ('vibrate' in navigator) {
                            navigator.vibrate(200);
                        }
                    },
                    {
                        returnDetailedScanResult: true,
                        highlightScanRegion: true,
                        highlightCodeOutline: true,
                        maxScansPerSecond: 5,
                        preferredCamera: 'environment'
                    }
                );
                
                console.log('🔧 Scanner QR créé:', scannerRef.current);
                
                scannerRef.current.start().then(() => {
                    console.log('✅ Scanner démarré avec succès');
                    setIsScanning(true);
                }).catch((error) => {
                    console.error('❌ Erreur démarrage scanner:', error);
                    alert('Erreur démarrage scanner: ' + error.message);
                });
                
                // Logs périodiques pour vérifier l'état
                const debugInterval = setInterval(() => {
                    if (scannerRef.current) {
                        console.log('🔄 État scanner:', {
                            isFlashOn: scannerRef.current.isFlashOn(),
                            hasCamera: scannerRef.current.hasCamera,
                            cameras: scannerRef.current._qrEnginePromise ? 'Chargé' : 'Non chargé'
                        });
                    } else {
                        clearInterval(debugInterval);
                    }
                }, 5000);
                
                // Nettoyer l'intervalle après 30 secondes
                setTimeout(() => clearInterval(debugInterval), 30000);
            }
        } catch (error) {
            console.error('❌ Erreur debug scanner:', error);
            alert('Erreur: ' + error.message);
        }
    };

    const stopDebugScan = () => {
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
    };

    const generateTestQR = () => {
        // Créer un QR code de test simple
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = 200;
        canvas.height = 200;
        
        // Fond blanc
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, 200, 200);
        
        // Texte noir
        ctx.fillStyle = 'black';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(testCode, 100, 100);
        
        return canvas.toDataURL();
    };

    return (
        <Card className="mb-3 border-warning">
            <Card.Header className="bg-warning text-dark">
                <Bug className="me-2" />
                Debug QR Scanner
            </Card.Header>
            <Card.Body>
                <div className="row">
                    <div className="col-md-6">
                        <video 
                            ref={videoRef}
                            style={{ 
                                width: '100%', 
                                height: '200px', 
                                objectFit: 'cover',
                                borderRadius: '8px',
                                backgroundColor: '#f8f9fa'
                            }}
                        />
                        
                        <div className="d-grid mt-2">
                            <Button 
                                variant={isScanning ? 'danger' : 'success'}
                                onClick={isScanning ? stopDebugScan : startDebugScan}
                            >
                                <Camera className="me-1" />
                                {isScanning ? 'Arrêter Debug' : 'Démarrer Debug'}
                            </Button>
                        </div>
                    </div>
                    
                    <div className="col-md-6">
                        <h6>Dernier Scan:</h6>
                        {lastScan ? (
                            <Alert variant="success">
                                <strong>Heure:</strong> {lastScan.timestamp}<br />
                                <strong>Contenu:</strong> <code>{lastScan.data}</code><br />
                                <strong>Type:</strong> {lastScan.type}
                            </Alert>
                        ) : (
                            <Alert variant="secondary">Aucun scan détecté</Alert>
                        )}
                        
                        <h6>Historique:</h6>
                        <div style={{ maxHeight: '150px', overflowY: 'auto' }}>
                            {scanHistory.map((scan, index) => (
                                <div key={index} className="small border-bottom py-1">
                                    <strong>{scan.timestamp}:</strong> {scan.data}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
                
                <hr />
                
                <div className="row">
                    <div className="col-md-6">
                        <Form.Group>
                            <Form.Label>QR Code Test:</Form.Label>
                            <Form.Control 
                                type="text"
                                value={testCode}
                                onChange={(e) => setTestCode(e.target.value)}
                                placeholder="STAFF_001"
                            />
                        </Form.Group>
                    </div>
                    <div className="col-md-6">
                        <Button 
                            variant="info" 
                            onClick={() => {
                                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(testCode)}`;
                                window.open(qrUrl, '_blank');
                            }}
                        >
                            <Lightning className="me-1" />
                            Générer QR Test
                        </Button>
                    </div>
                </div>
                
                <Alert variant="info" className="mt-3">
                    <strong>Instructions de débogage:</strong>
                    <ol>
                        <li>Cliquez "Démarrer Debug"</li>
                        <li>Ouvrez la console F12 pour voir les logs détaillés</li>
                        <li>Présentez votre badge QR devant la caméra</li>
                        <li>Regardez dans la console s'il y a des logs de détection</li>
                        <li>Testez aussi avec le QR code généré ci-dessus</li>
                    </ol>
                </Alert>
                
                <Alert variant="warning" className="mt-2">
                    <strong>Si rien ne fonctionne:</strong>
                    <ul className="mb-0">
                        <li>Vérifiez que votre badge QR est net et bien éclairé</li>
                        <li>Essayez de rapprocher/éloigner le badge de la caméra</li>
                        <li>Testez d'abord avec le QR de test généré</li>
                        <li>Vérifiez les logs dans la console (F12)</li>
                    </ul>
                </Alert>
            </Card.Body>
        </Card>
    );
};

export default QRTestDebug;