/**
 * Bouton pour partager les zones admin avec tous les utilisateurs
 */

import React from 'react';
import { Button, Alert } from 'react-bootstrap';
import { Share, CheckCircle } from 'react-bootstrap-icons';
import { shareZonesToAllUsers } from '../utils/zonesSyncFix';
import Swal from 'sweetalert2';

const ShareZonesButton = () => {
    
    const handleShareZones = () => {
        Swal.fire({
            title: 'Partager les zones ?',
            text: 'Cette action rendra vos zones configurées disponibles pour tous les utilisateurs scanners.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Partager',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                try {
                    const sharedZones = shareZonesToAllUsers();
                    const zoneCount = Object.keys(sharedZones).length;
                    
                    Swal.fire({
                        title: 'Zones partagées !',
                        html: `
                            <p><strong>${zoneCount} zone(s)</strong> sont maintenant disponibles pour tous les utilisateurs.</p>
                            <p>Les scanners pourront maintenant valider leur position.</p>
                        `,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                } catch (error) {
                    Swal.fire(
                        'Erreur',
                        'Impossible de partager les zones: ' + error.message,
                        'error'
                    );
                }
            }
        });
    };

    return (
        <div className="mb-3">
            <Alert variant="info">
                <div className="d-flex align-items-center justify-content-between">
                    <div>
                        <strong>💡 Astuce Admin</strong>
                        <p className="mb-0">
                            Après avoir configuré vos zones, partagez-les avec tous les utilisateurs scanners.
                        </p>
                    </div>
                    <Button 
                        variant="success"
                        onClick={handleShareZones}
                        className="ms-3"
                    >
                        <Share className="me-1" />
                        Partager Zones
                    </Button>
                </div>
            </Alert>
        </div>
    );
};

export default ShareZonesButton;