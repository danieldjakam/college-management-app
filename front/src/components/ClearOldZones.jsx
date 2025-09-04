/**
 * Composant pour nettoyer les anciennes zones par défaut
 * et ne garder que les zones créées par l'admin
 */

import React from 'react';
import { Button, Alert } from 'react-bootstrap';
import { Trash, ArrowClockwise } from 'react-bootstrap-icons';
import Swal from 'sweetalert2';

const ClearOldZones = () => {
    
    const clearDefaultZones = () => {
        Swal.fire({
            title: 'Nettoyer les zones par défaut ?',
            html: `
                <p>Cette action va supprimer les zones par défaut :</p>
                <ul class="text-start">
                    <li>École - Bâtiment Principal</li>
                    <li>Administration</li>
                    <li>Bibliothèque</li>
                </ul>
                <p><strong>Seules vos zones personnalisées seront conservées.</strong></p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Nettoyer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                // Récupérer les zones actuelles
                const saved = localStorage.getItem('geolocation_zones');
                let zones = {};
                
                if (saved) {
                    zones = JSON.parse(saved);
                    
                    // Supprimer les zones par défaut
                    delete zones.school_main;
                    delete zones.school_admin;
                    delete zones.school_library;
                    
                    // Sauvegarder les zones nettoyées
                    localStorage.setItem('geolocation_zones', JSON.stringify(zones));
                    
                    Swal.fire(
                        'Nettoyé !',
                        'Les zones par défaut ont été supprimées. Actualisez la page.',
                        'success'
                    ).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire(
                        'Info',
                        'Aucune zone à nettoyer.',
                        'info'
                    );
                }
            }
        });
    };
    
    const resetAllZones = () => {
        Swal.fire({
            title: 'Réinitialiser toutes les zones ?',
            text: 'Cette action supprimera TOUTES les zones (défaut + personnalisées).',
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Tout supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                // Supprimer complètement les zones
                localStorage.removeItem('geolocation_zones');
                
                Swal.fire(
                    'Réinitialisé !',
                    'Toutes les zones ont été supprimées. Actualisez la page.',
                    'success'
                ).then(() => {
                    window.location.reload();
                });
            }
        });
    };

    return (
        <div className="mb-3">
            <Alert variant="info">
                <strong>Problème avec les anciennes zones ?</strong>
                <p className="mb-2">
                    Si vous voyez encore les zones par défaut (École, Administration, Bibliothèque) 
                    avec des distances incorrectes, utilisez ces boutons pour nettoyer.
                </p>
                <div className="d-flex gap-2">
                    <Button 
                        variant="warning" 
                        size="sm"
                        onClick={clearDefaultZones}
                    >
                        <Trash className="me-1" />
                        Nettoyer Zones Défaut
                    </Button>
                    <Button 
                        variant="danger" 
                        size="sm"
                        onClick={resetAllZones}
                    >
                        <ArrowClockwise className="me-1" />
                        Réinitialiser Tout
                    </Button>
                </div>
            </Alert>
        </div>
    );
};

export default ClearOldZones;