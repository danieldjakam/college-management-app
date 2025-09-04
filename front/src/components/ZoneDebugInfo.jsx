/**
 * Composant de débogage pour voir l'état des zones
 */

import React, { useState, useEffect } from 'react';
import { Card, Button, Badge, Table } from 'react-bootstrap';
import { Bug, Eye, Database, Trash } from 'react-bootstrap-icons';
import geolocationService from '../services/geolocationService';

const ZoneDebugInfo = () => {
    const [showDebug, setShowDebug] = useState(false);
    const [localStorageData, setLocalStorageData] = useState(null);
    const [serviceZones, setServiceZones] = useState({});

    const loadDebugInfo = () => {
        // Charger depuis localStorage
        const stored = localStorage.getItem('geolocation_zones');
        setLocalStorageData(stored);
        
        // Charger depuis le service
        setServiceZones(geolocationService.AUTHORIZED_ZONES);
    };

    useEffect(() => {
        if (showDebug) {
            loadDebugInfo();
        }
    }, [showDebug]);

    const clearLocalStorage = () => {
        localStorage.removeItem('geolocation_zones');
        loadDebugInfo();
        alert('LocalStorage nettoyé ! Actualisez la page.');
    };

    const forceReload = () => {
        geolocationService.loadZoneConfig();
        loadDebugInfo();
        alert('Zones rechargées depuis localStorage');
    };

    if (!showDebug) {
        return (
            <Button 
                variant="outline-secondary" 
                size="sm"
                onClick={() => setShowDebug(true)}
                className="mb-3"
            >
                <Bug className="me-1" />
                Debug Zones
            </Button>
        );
    }

    return (
        <Card className="mb-3 border-warning">
            <Card.Header className="bg-warning text-dark">
                <div className="d-flex justify-content-between align-items-center">
                    <span>
                        <Bug className="me-2" />
                        Debug Info - Zones Géolocalisées
                    </span>
                    <Button 
                        variant="close" 
                        onClick={() => setShowDebug(false)}
                    />
                </div>
            </Card.Header>
            <Card.Body>
                <div className="mb-3">
                    <h6>LocalStorage Content:</h6>
                    <div className="bg-light p-2 rounded" style={{ fontFamily: 'monospace', fontSize: '12px' }}>
                        {localStorageData ? (
                            <pre style={{ maxHeight: '200px', overflow: 'auto' }}>
                                {JSON.stringify(JSON.parse(localStorageData), null, 2)}
                            </pre>
                        ) : (
                            <Badge bg="danger">Vide (aucune donnée)</Badge>
                        )}
                    </div>
                </div>

                <div className="mb-3">
                    <h6>Service Zones (en mémoire):</h6>
                    {Object.keys(serviceZones).length > 0 ? (
                        <Table striped bordered size="sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Statut</th>
                                    <th>Coords</th>
                                </tr>
                            </thead>
                            <tbody>
                                {Object.entries(serviceZones).map(([id, zone]) => (
                                    <tr key={id}>
                                        <td>{id}</td>
                                        <td>{zone.name}</td>
                                        <td>
                                            <Badge bg={zone.enabled ? 'success' : 'secondary'}>
                                                {zone.enabled ? 'ON' : 'OFF'}
                                            </Badge>
                                        </td>
                                        <td style={{ fontSize: '10px' }}>
                                            {zone.latitude?.toFixed(4)}, {zone.longitude?.toFixed(4)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    ) : (
                        <Badge bg="danger">Aucune zone en mémoire</Badge>
                    )}
                </div>

                <div className="d-flex gap-2">
                    <Button 
                        variant="info" 
                        size="sm"
                        onClick={loadDebugInfo}
                    >
                        <Eye className="me-1" />
                        Actualiser
                    </Button>
                    <Button 
                        variant="warning" 
                        size="sm"
                        onClick={forceReload}
                    >
                        <Database className="me-1" />
                        Recharger Zones
                    </Button>
                    <Button 
                        variant="danger" 
                        size="sm"
                        onClick={clearLocalStorage}
                    >
                        <Trash className="me-1" />
                        Vider Cache
                    </Button>
                </div>

                <hr />
                <small className="text-muted">
                    <strong>Note:</strong> Si les zones ne se synchronisent pas, videz le cache et recréez vos zones.
                </small>
            </Card.Body>
        </Card>
    );
};

export default ZoneDebugInfo;