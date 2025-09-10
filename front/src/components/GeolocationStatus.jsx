/**
 * Composant d'affichage du statut de géolocalisation
 * Pour le scanner de présence
 */

import React, { useState, useEffect } from 'react';
import { Card, Badge, Spinner, Alert, Button, Modal, Table } from 'react-bootstrap';
import { 
    GeoAlt, 
    GeoAltFill, 
    ExclamationTriangle, 
    CheckCircle, 
    XCircle,
    ShieldCheck,
    ShieldX,
    Bullseye,
    Map
} from 'react-bootstrap-icons';
import geolocationService from '../services/geolocationService';
import SyncZonesButton from './SyncZonesButton';

const GeolocationStatus = ({ onStatusChange, autoTrack = true, showControls = false }) => {
    const [locationStatus, setLocationStatus] = useState(null);
    const [isLoading, setIsLoading] = useState(true); // Commencer en loading
    const [showZonesModal, setShowZonesModal] = useState(false);
    const [realTimeTracking, setRealTimeTracking] = useState(false);
    const [autoCheckDone, setAutoCheckDone] = useState(false);

    useEffect(() => {
        // Auto-vérification de position au chargement
        const performAutoCheck = async () => {
            if (!autoCheckDone) {
                // Afficher le log seulement en développement
                if (process.env.NODE_ENV === 'development') {
                    console.log('🎯 Vérification automatique de position au chargement...');
                }
                setIsLoading(true);
                
                // Vérifier d'abord si des zones sont configurées avant de démarrer la géolocalisation
                try {
                    await geolocationService.loadAuthorizedZones();
                    // Si aucune zone n'est configurée, ne pas faire de vérification automatique
                    if (!geolocationService.AUTHORIZED_ZONES || Object.keys(geolocationService.AUTHORIZED_ZONES).length === 0) {
                        setLocationStatus({
                            isAuthorized: true,
                            message: 'Géolocalisation non configurée - Accès autorisé',
                            zones: [],
                            position: null
                        });
                        setIsLoading(false);
                        setAutoCheckDone(true);
                        return;
                    }
                } catch (error) {
                    // Si erreur de chargement des zones, autoriser par défaut
                    setLocationStatus({
                        isAuthorized: true,
                        message: 'Géolocalisation non configurée - Accès autorisé',
                        zones: [],
                        position: null
                    });
                    setIsLoading(false);
                    setAutoCheckDone(true);
                    return;
                }
                
                await checkCurrentLocation();
                setAutoCheckDone(true);
                
                // Si autoTrack est activé, démarrer le suivi temps réel après la première vérification
                if (autoTrack) {
                    setTimeout(() => {
                        startRealTimeTracking();
                    }, 1000);
                }
            }
        };

        performAutoCheck();
        
        return () => {
            geolocationService.stopTracking();
        };
    }, [autoTrack, autoCheckDone]);

    const checkCurrentLocation = async () => {
        setIsLoading(true);
        try {
            const result = await geolocationService.validateScanLocation();
            setLocationStatus(result);
            
            // Notifier le parent du changement de statut
            if (onStatusChange) {
                onStatusChange(result);
            }
        } catch (error) {
            console.error('Erreur vérification position:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const startRealTimeTracking = () => {
        setRealTimeTracking(true);
        geolocationService.startTracking((data) => {
            if (data.error) {
                setLocationStatus({
                    success: false,
                    message: data.error,
                    error: data.error
                });
            } else {
                // Vérifier que closestZone existe avant d'accéder à ses propriétés
                const closestZone = data.validation?.closestZone;
                let message = '';
                
                if (data.validation.isAuthorized) {
                    message = closestZone 
                        ? `✅ Zone autorisée: ${closestZone.zoneName}`
                        : `✅ Zone autorisée`;
                } else {
                    if (closestZone) {
                        message = `❌ Hors zone autorisée (${closestZone.distance}m de ${closestZone.zoneName})`;
                    } else if (data.validation.error) {
                        message = `❌ ${data.validation.error}`;
                    } else {
                        message = `❌ Hors zone autorisée`;
                    }
                }
                
                const result = {
                    success: data.validation.isAuthorized,
                    position: data.position,
                    validation: data.validation,
                    message: message
                };
                
                setLocationStatus(result);
                
                if (onStatusChange) {
                    onStatusChange(result);
                }
            }
        });
    };

    const stopRealTimeTracking = () => {
        setRealTimeTracking(false);
        geolocationService.stopTracking();
        setLocationStatus(null);
    };

    const getStatusIcon = () => {
        if (isLoading) return <Spinner animation="border" size="sm" />;
        if (!locationStatus) return <GeoAlt className="text-muted" />;
        
        if (locationStatus.success) {
            return <CheckCircle className="text-success" />;
        } else {
            return <XCircle className="text-danger" />;
        }
    };

    const getStatusBadge = () => {
        if (isLoading) return <Badge bg="secondary">Vérification...</Badge>;
        if (!locationStatus) return <Badge bg="secondary">Position non vérifiée</Badge>;
        
        if (locationStatus.success) {
            return <Badge bg="success">Zone autorisée</Badge>;
        } else {
            return <Badge bg="danger">Zone non autorisée</Badge>;
        }
    };

    const getAccuracyInfo = () => {
        if (!locationStatus?.position) return null;
        
        const accuracy = locationStatus.position.accuracy;
        let accuracyColor = 'success';
        let accuracyText = 'Excellente';
        
        if (accuracy > 50) {
            accuracyColor = 'danger';
            accuracyText = 'Faible';
        } else if (accuracy > 20) {
            accuracyColor = 'warning';
            accuracyText = 'Moyenne';
        }
        
        return (
            <small className={`text-${accuracyColor}`}>
                Précision: {accuracy}m ({accuracyText})
            </small>
        );
    };

    return (
        <div className="geolocation-status">
            <Card className="mb-3">
                <Card.Body>
                    <div className="d-flex align-items-center justify-content-between">
                        <div className="d-flex align-items-center">
                            <div className="me-2" style={{ fontSize: '1.2rem' }}>
                                {getStatusIcon()}
                            </div>
                            <div>
                                <strong>Contrôle Géographique</strong>
                                <br />
                                {getStatusBadge()}
                            </div>
                        </div>
                        
                        <div className="text-end">
                            {showControls && (
                                <>
                                    {!realTimeTracking ? (
                                        <Button 
                                            variant="outline-primary" 
                                            size="sm"
                                            onClick={checkCurrentLocation}
                                            disabled={isLoading}
                                        >
                                            <Bullseye className="me-1" />
                                            Vérifier Position
                                        </Button>
                                    ) : (
                                        <Button 
                                            variant="outline-danger" 
                                            size="sm"
                                            onClick={stopRealTimeTracking}
                                        >
                                            Arrêter Suivi
                                        </Button>
                                    )}
                                    
                                    <Button 
                                        variant="outline-secondary" 
                                        size="sm" 
                                        className="ms-2"
                                        onClick={() => setShowZonesModal(true)}
                                    >
                                        <Map className="me-1" />
                                        Zones
                                    </Button>
                                    
                                    <div className="ms-2">
                                        <SyncZonesButton onZonesUpdated={() => {
                                            // Recharger les zones après sync
                                            geolocationService.loadZoneConfig();
                                            // Relancer la vérification de position
                                            if (locationStatus) {
                                                checkCurrentLocation();
                                            }
                                        }} />
                                    </div>
                                </>
                            )}
                            
                            {/* Indicateur de status pour mode automatique */}
                            {!showControls && locationStatus && (
                                <div className="d-flex align-items-center">
                                    {locationStatus.success ? (
                                        <Badge bg="success" className="d-flex align-items-center">
                                            <ShieldCheck className="me-1" />
                                            Zone Autorisée
                                        </Badge>
                                    ) : (
                                        <Badge bg="danger" className="d-flex align-items-center">
                                            <ShieldX className="me-1" />
                                            Hors Zone
                                        </Badge>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                    
                    {locationStatus?.message && (
                        <Alert 
                            variant={locationStatus.success ? 'success' : 'danger'} 
                            className="mt-2 mb-0 small"
                        >
                            <div className="d-flex align-items-center">
                                {locationStatus.success ? (
                                    <ShieldCheck className="me-2" />
                                ) : (
                                    <ShieldX className="me-2" />
                                )}
                                {locationStatus.message}
                            </div>
                            {getAccuracyInfo()}
                        </Alert>
                    )}
                    
                    {locationStatus?.error && (
                        <Alert variant="warning" className="mt-2 mb-0 small">
                            <ExclamationTriangle className="me-2" />
                            {locationStatus.error}
                        </Alert>
                    )}
                </Card.Body>
            </Card>

            {/* Modal des zones autorisées */}
            <Modal show={showZonesModal} onHide={() => setShowZonesModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        <Map className="me-2" />
                        Zones Autorisées pour le Scan
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Table striped bordered hover>
                        <thead>
                            <tr>
                                <th>Zone</th>
                                <th>Statut</th>
                                <th>Rayon</th>
                                <th>Distance</th>
                            </tr>
                        </thead>
                        <tbody>
                            {locationStatus?.validation?.zones?.map((zone, index) => (
                                <tr key={index}>
                                    <td>
                                        <strong>{zone.zoneName}</strong>
                                    </td>
                                    <td>
                                        {zone.isInZone ? (
                                            <Badge bg="success">
                                                <CheckCircle className="me-1" />
                                                Dans la zone
                                            </Badge>
                                        ) : (
                                            <Badge bg="secondary">
                                                Hors zone
                                            </Badge>
                                        )}
                                    </td>
                                    <td>{zone.radius}m</td>
                                    <td>
                                        <span className={zone.isInZone ? 'text-success' : 'text-muted'}>
                                            {zone.distance}m
                                        </span>
                                    </td>
                                </tr>
                            )) || (
                                <tr>
                                    <td colSpan="4" className="text-center text-muted">
                                        Aucune position détectée. Cliquez sur "Vérifier Position" d'abord.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </Table>
                    
                    {locationStatus?.position && (
                        <div className="mt-3">
                            <h6>Position Actuelle:</h6>
                            <small className="text-muted">
                                Latitude: {locationStatus.position.latitude.toFixed(6)}<br />
                                Longitude: {locationStatus.position.longitude.toFixed(6)}<br />
                                Précision: {locationStatus.position.accuracy}m<br />
                                Mise à jour: {new Date(locationStatus.position.timestamp).toLocaleString()}
                            </small>
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowZonesModal(false)}>
                        Fermer
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    );
};

export default GeolocationStatus;