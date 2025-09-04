/**
 * Composant d'accès rapide à la configuration géolocalisation
 * À placer dans le dashboard admin ou les paramètres
 */

import React, { useState, useEffect } from 'react';
import { Card, Button, Alert, Spinner } from 'react-bootstrap';
import { GeoAlt, Gear, CheckCircle, XCircle } from 'react-bootstrap-icons';
import { useNavigate } from 'react-router-dom';
import geolocationService from '../services/geolocationService';

const GeolocationQuickAccess = () => {
    const navigate = useNavigate();
    const [zoneStats, setZoneStats] = useState({
        total: 0,
        active: 0,
        inactive: 0,
        hasZones: false
    });
    const [isLoading, setIsLoading] = useState(true);
    
    useEffect(() => {
        const loadZoneStats = async () => {
            try {
                setIsLoading(true);
                const stats = await geolocationService.getZoneStats();
                setZoneStats(stats);
            } catch (error) {
                console.error('Erreur chargement stats zones:', error);
                // Garder les valeurs par défaut (0)
            } finally {
                setIsLoading(false);
            }
        };
        
        loadZoneStats();
    }, []);
    
    const { total: configuredZones, active: activeZones, inactive: inactiveZones } = zoneStats;
    
    const handleConfigureClick = () => {
        navigate('/settings'); // Ira à l'onglet "Zones Géolocalisées"
        // Ou directement : navigate('/geolocation-zones');
    };

    const getStatusVariant = () => {
        if (activeZones === 0) return 'danger';
        if (activeZones < 2) return 'warning';
        return 'success';
    };

    const getStatusMessage = () => {
        if (activeZones === 0) {
            return '❌ Aucune zone configurée - Scan de présence non sécurisé';
        }
        if (activeZones === 1) {
            return '⚠️ Une seule zone active - Recommandé : au moins 2 zones';
        }
        return `✅ ${activeZones} zones actives - Configuration optimale`;
    };

    return (
        <Card className="border-primary mb-3">
            <Card.Header className="bg-primary text-white">
                <div className="d-flex align-items-center">
                    <GeoAlt size={20} className="me-2" />
                    <strong>Contrôle Géolocalisation - Présences</strong>
                </div>
            </Card.Header>
            <Card.Body>
                <Alert variant={getStatusVariant()} className="mb-3">
                    <div className="d-flex align-items-center">
                        {activeZones > 0 ? (
                            <CheckCircle className="me-2" />
                        ) : (
                            <XCircle className="me-2" />
                        )}
                        <strong>{getStatusMessage()}</strong>
                    </div>
                </Alert>

                <div className="mb-3">
                    <p className="text-muted mb-2">
                        Le système de géolocalisation garantit que le personnel ne peut scanner 
                        sa présence que depuis les zones autorisées de l'école.
                    </p>
                    
                    {isLoading ? (
                        <div className="text-center py-3">
                            <Spinner animation="border" size="sm" className="me-2" />
                            <span className="text-muted">Chargement des zones...</span>
                        </div>
                    ) : (
                        <div className="row text-center">
                            <div className="col-4">
                                <div className="border rounded p-2">
                                    <h5 className="text-primary mb-0">{configuredZones}</h5>
                                    <small className="text-muted">Zones totales</small>
                                </div>
                            </div>
                            <div className="col-4">
                                <div className="border rounded p-2">
                                    <h5 className={`text-${getStatusVariant()} mb-0`}>{activeZones}</h5>
                                    <small className="text-muted">Zones actives</small>
                                </div>
                            </div>
                            <div className="col-4">
                                <div className="border rounded p-2">
                                    <h5 className="text-info mb-0">{inactiveZones}</h5>
                                    <small className="text-muted">Zones désactivées</small>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                <div className="d-grid gap-2">
                    <Button 
                        variant="primary" 
                        onClick={handleConfigureClick}
                        className="d-flex align-items-center justify-content-center"
                    >
                        <Gear className="me-2" />
                        {activeZones === 0 ? 'Configurer les Zones' : 'Gérer les Zones'}
                    </Button>
                    
                    {activeZones === 0 && (
                        <small className="text-center text-danger">
                            <strong>Action requise :</strong> Configurez au moins une zone pour sécuriser les scans de présence
                        </small>
                    )}
                </div>
            </Card.Body>
        </Card>
    );
};

export default GeolocationQuickAccess;