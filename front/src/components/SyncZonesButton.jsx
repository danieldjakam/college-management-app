/**
 * Bouton pour synchroniser les zones depuis l'API
 * Version 2.0 - Utilise l'API backend
 */

import React, { useState } from 'react';
import { Button, Badge } from 'react-bootstrap';
import { CloudDownload, CheckCircle, ExclamationTriangle } from 'react-bootstrap-icons';
import geolocationService from '../services/geolocationService';

const SyncZonesButton = ({ onZonesUpdated }) => {
    const [syncing, setSyncing] = useState(false);
    const [lastSync, setLastSync] = useState(null);
    
    const handleSyncZones = async () => {
        setSyncing(true);
        try {
            console.log('🔄 Démarrage synchronisation zones depuis API...');
            
            // Force recharger depuis l'API
            geolocationService.AUTHORIZED_ZONES = {};
            geolocationService.zonesCache = null;
            geolocationService.cacheExpiry = null;
            
            await geolocationService.loadZoneConfig();
            
            const zoneCount = Object.keys(geolocationService.AUTHORIZED_ZONES).length;
            setLastSync(new Date());
            
            console.log(`✅ ${zoneCount} zone(s) synchronisée(s) depuis l'API`);
            
            // Notifier le parent si une fonction de callback est fournie
            if (onZonesUpdated) {
                onZonesUpdated(geolocationService.AUTHORIZED_ZONES);
            }
            
        } catch (error) {
            console.error('❌ Erreur sync zones:', error);
        } finally {
            setSyncing(false);
        }
    };
    
    const getStatusBadge = () => {
        const zoneCount = Object.keys(geolocationService.AUTHORIZED_ZONES || {}).length;
        
        if (zoneCount === 0) {
            return <Badge bg="danger">Aucune zone</Badge>;
        } else if (zoneCount === 1) {
            return <Badge bg="warning">1 zone</Badge>;
        } else {
            return <Badge bg="success">{zoneCount} zones</Badge>;
        }
    };

    return (
        <div className="d-flex align-items-center gap-2">
            <Button 
                variant="outline-primary"
                size="sm"
                onClick={handleSyncZones}
                disabled={syncing}
            >
                <CloudDownload className="me-1" />
                {syncing ? 'Sync...' : 'Sync Zones'}
            </Button>
            
            {getStatusBadge()}
            
            {lastSync && (
                <small className="text-muted">
                    Sync: {lastSync.toLocaleTimeString()}
                </small>
            )}
        </div>
    );
};

export default SyncZonesButton;