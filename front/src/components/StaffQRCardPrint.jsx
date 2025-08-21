import React, { useState } from 'react';
import { Modal, Button, Spinner, Alert } from 'react-bootstrap';
import { Printer, Download } from 'react-bootstrap-icons';
import { useSchool } from '../contexts/SchoolContext';
import Swal from 'sweetalert2';
import { host } from '../utils/fetch';

const StaffQRCardPrint = ({ staffMember, qrImageUrl, show, onHide, onPrintSuccess }) => {
    const [printing, setPrinting] = useState(false);
    const { schoolSettings } = useSchool();

    const convertImageToBase64 = async (imageSrc) => {
        return new Promise((resolve, reject) => {
            if (!imageSrc) {
                resolve('');
                return;
            }

            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                
                try {
                    const dataURL = canvas.toDataURL('image/png');
                    resolve(dataURL);
                } catch (error) {
                    console.error('Error converting image to base64:', error);
                    resolve('');
                }
            };
            
            img.onerror = function() {
                console.error('Error loading image:', imageSrc);
                resolve('');
            };
            
            img.src = imageSrc;
        });
    };

    const getStaffPhotoUrl = (staff) => {
        console.log('Getting staff photo URL for:', staff);
        
        // D'abord, vérifier toutes les propriétés possibles de photo
        const photoFields = ['photo_url', 'photo', 'user_photo', 'image', 'avatar'];
        
        for (const field of photoFields) {
            const photoValue = staff[field];
            if (photoValue) {
                console.log(`Found photo in field '${field}':`, photoValue);
                
                if (photoValue.startsWith('http')) {
                    // URL complète
                    return photoValue.replace('127.0.0.1:8000', host);
                } else {
                    // Chemin relatif
                    const baseUrl = host;
                    const photoUrl = photoValue.startsWith('/') ? 
                        `${baseUrl}${photoValue}` : 
                        `${baseUrl}/storage/${photoValue}`;
                    console.log('Generated photo URL:', photoUrl);
                    return photoUrl;
                }
            }
        }
        
        console.log('No photo found, using default');
        // Image par défaut pour le personnel
        return `${window.location.origin}/static/media/1.png`;
    };

    const getStaffTypeLabel = (role) => {
        const labels = {
            'teacher': 'Enseignant',
            'accountant': 'Comptable',
            'comptable_superieur': 'Comptable Supérieur',
            'surveillant_general': 'Surveillant Général',
            'admin': 'Administrateur'
        };
        return labels[role] || role;
    };

    const getStaffTypeColor = (role) => {
        const colors = {
            'teacher': '#4a4a8a',
            'accountant': '#2e7d32',
            'comptable_superieur': '#1976d2',
            'surveillant_general': '#f57c00',
            'admin': '#d32f2f'
        };
        return colors[role] || '#7f8c8d';
    };

    const adjustBrightness = (hex, percent) => {
        // Supprimer le # si présent
        hex = hex.replace('#', '');
        
        // Convertir en RGB
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        
        // Ajuster la luminosité
        const newR = Math.max(0, Math.min(255, r + (r * percent / 100)));
        const newG = Math.max(0, Math.min(255, g + (g * percent / 100)));
        const newB = Math.max(0, Math.min(255, b + (b * percent / 100)));
        
        // Convertir de nouveau en hex
        return '#' + Math.round(newR).toString(16).padStart(2, '0') + 
                     Math.round(newG).toString(16).padStart(2, '0') + 
                     Math.round(newB).toString(16).padStart(2, '0');
    };

    const handlePrint = async () => {
        if (!staffMember || !qrImageUrl) return;

        try {
            setPrinting(true);

            // Convertir les images en base64 pour l'impression
            const staffPhotoUrl = getStaffPhotoUrl(staffMember);
            const logoUrl = schoolSettings?.logo_url;
            
            console.log('Preparing images for staff card print:', {
                staffPhotoUrl,
                logoUrl,
                qrImageUrl,
                staffId: staffMember.id
            });
            
            const [staffPhotoBase64, logoBase64] = await Promise.all([
                convertImageToBase64(staffPhotoUrl),
                convertImageToBase64(logoUrl)
            ]);
            
            console.log('Images converted to base64:', {
                hasStaffPhoto: !!staffPhotoBase64,
                hasLogo: !!logoBase64
            });

            // Créer une nouvelle fenêtre pour l'impression
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            if (!printWindow) {
                throw new Error('Popup bloqué. Veuillez autoriser les popups pour imprimer.');
            }

            const staffTypeLabel = getStaffTypeLabel(staffMember.role);
            const staffTypeColor = getStaffTypeColor(staffMember.role);

            // Générer le HTML du badge avec le modèle Frame
            const cardHtml = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Badge Personnel - ${staffMember.name}</title>
                    <style>
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: 'Arial', 'Helvetica', sans-serif;
                            background: #f5f5f5;
                            padding: 20px;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            min-height: 100vh;
                        }
                        
                        .badge-container {
                            width: 85.6mm;
                            height: 54mm;
                            background: white;
                            border-radius: 8px;
                            overflow: hidden;
                            position: relative;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                            border: 1px solid #e0e0e0;
                        }
                        
                        /* Header Section */
                        .badge-header {
                            background: ${staffTypeColor};
                            color: white;
                            padding: 6px 12px;
                            text-align: center;
                            font-size: 8px;
                            font-weight: bold;
                            letter-spacing: 2px;
                            text-transform: uppercase;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 8px;
                        }
                        
                        .school-logo {
                            width: 16px;
                            height: 16px;
                            object-fit: contain;
                            filter: brightness(0) invert(1);
                        }
                        
                        /* Tableau principal */
                        .content-table {
                            width: 100%;
                            height: calc(100% - 24px - 16px);
                            border-collapse: collapse;
                            table-layout: fixed;
                        }
                        
                        .content-table td {
                            vertical-align: middle;
                            padding: 6px;
                            border: none;
                        }
                        
                        /* Colonne 1 - Photo (25%) */
                        .photo-cell {
                            width: 25%;
                            text-align: center;
                        }
                        
                        .staff-photo {
                            width: 24mm;
                            height: 30mm;
                            object-fit: cover;
                            border-radius: 4px;
                            border: 1px solid #ddd;
                            background: #f9f9f9;
                        }
                        
                        /* Colonne 2 - Informations (50%) */
                        .info-cell {
                            width: 50%;
                            padding-left: 8px;
                            padding-right: 8px;
                        }
                        
                        .id-number-label {
                            font-size: 6px;
                            color: ${staffTypeColor};
                            font-weight: bold;
                            text-transform: uppercase;
                            margin-bottom: 1px;
                            letter-spacing: 0.5px;
                            display: block;
                        }
                        
                        .id-number {
                            font-size: 10px;
                            color: ${staffTypeColor};
                            font-weight: bold;
                            margin-bottom: 4px;
                            display: block;
                        }
                        
                        .name-label {
                            font-size: 6px;
                            color: ${staffTypeColor};
                            font-weight: bold;
                            text-transform: uppercase;
                            margin-bottom: 1px;
                            letter-spacing: 0.5px;
                            display: block;
                        }
                        
                        .staff-name {
                            font-size: 9px;
                            color: #2c2c2c;
                            font-weight: bold;
                            margin-bottom: 4px;
                            line-height: 1.1;
                            display: block;
                        }
                        
                        .role-label {
                            font-size: 6px;
                            color: ${staffTypeColor};
                            font-weight: bold;
                            text-transform: uppercase;
                            margin-bottom: 1px;
                            letter-spacing: 0.5px;
                            display: block;
                        }
                        
                        .staff-role {
                            font-size: 8px;
                            color: #2c2c2c;
                            font-weight: normal;
                            line-height: 1.1;
                            display: block;
                        }
                        
                        /* Colonne 3 - QR Code (25%) */
                        .qr-cell {
                            width: 25%;
                            text-align: center;
                        }
                        
                        .qr-code {
                            width: 20mm;
                            height: 20mm;
                            object-fit: contain;
                            border: 1px solid #ddd;
                        }
                        
                        /* Footer Section */
                        .badge-footer {
                            position: absolute;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            background: ${adjustBrightness(staffTypeColor, 60)};
                            color: ${staffTypeColor};
                            padding: 3px 12px;
                            text-align: center;
                            font-size: 7px;
                            font-weight: bold;
                            letter-spacing: 3px;
                            text-transform: uppercase;
                        }
                        
                        @media print {
                            body {
                                margin: 0;
                                padding: 10mm;
                            }
                            
                            .badge-container {
                                page-break-inside: avoid;
                            }
                        }
                        
                        @page {
                            size: A4;
                            margin: 10mm;
                        }
                    </style>
                </head>
                <body>
                    <div class="badge-container">
                        <!-- Header -->
                        <div class="badge-header">
                            ${logoBase64 ? `<img src="${logoBase64}" alt="Logo" class="school-logo">` : ''}
                            <span>IDENTIFICATION EMPLOYÉ</span>
                        </div>
                        
                        <!-- Main Content - Tableau 3 colonnes -->
                        <table class="content-table">
                            <tr>
                                <!-- Colonne 1: Photo -->
                                <td class="photo-cell">
                                    <img src="${staffPhotoBase64 || getStaffPhotoUrl(staffMember)}" alt="Staff Photo" class="staff-photo">
                                </td>
                                
                                <!-- Colonne 2: Informations -->
                                <td class="info-cell">
                                    <span class="id-number-label">N° D'IDENTIFICATION</span>
                                    <span class="id-number">${staffMember.id}</span>
                                    
                                    <span class="name-label">NOM</span>
                                    <span class="staff-name">${staffMember.name}</span>
                                    
                                    <span class="role-label">POSTE / EMPLOI</span>
                                    <span class="staff-role">${staffTypeLabel}</span>
                                </td>
                                
                                <!-- Colonne 3: QR Code -->
                                <td class="qr-cell">
                                    <img 
                                        src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(`STAFF_${staffMember.id}`)}&margin=1"
                                        alt="QR Code"
                                        class="qr-code"
                                    />
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Footer -->
                        <div class="badge-footer">
                            ${schoolSettings?.school_name || 'COLLÈGE POLYVALENT BILINGUE DE DOUALA'}
                        </div>
                    </div>
                </body>
                </html>
            `;

            // Écrire le contenu dans la fenêtre d'impression
            printWindow.document.write(cardHtml);
            printWindow.document.close();

            // Attendre que les images se chargent puis imprimer
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                    
                    if (onPrintSuccess) {
                        onPrintSuccess();
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Badge imprimé !',
                        text: `Badge de ${staffMember.name} envoyé à l'imprimante`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }, 1000);
            };

        } catch (error) {
            console.error('Erreur impression badge:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erreur d\'impression',
                text: error.message || 'Impossible d\'imprimer le badge'
            });
        } finally {
            setPrinting(false);
        }
    };

    const handleDownload = () => {
        if (qrImageUrl) {
            const link = document.createElement('a');
            link.href = qrImageUrl;
            link.download = `badge_${staffMember.name.replace(/\s+/g, '_')}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    return (
        <Modal show={show} onHide={onHide} centered size="lg">
            <Modal.Header closeButton>
                <Modal.Title>Imprimer Badge Personnel</Modal.Title>
            </Modal.Header>
            
            <Modal.Body>
                {staffMember && (
                    <div className="text-center">
                        <Alert variant="info" className="mb-3">
                            <strong>Badge pour :</strong> {staffMember.name}<br />
                            <strong>Fonction :</strong> {getStaffTypeLabel(staffMember.role)}<br />
                            <strong>Email :</strong> {staffMember.email}
                        </Alert>
                        
                        {staffMember.photo_url || staffMember.photo ? (
                            <div className="mb-3">
                                <img 
                                    src={getStaffPhotoUrl(staffMember)} 
                                    alt="Photo personnel" 
                                    className="img-fluid border rounded-circle"
                                    style={{ maxWidth: '100px', maxHeight: '100px', objectFit: 'cover' }}
                                />
                            </div>
                        ) : null}
                        
                        {qrImageUrl && (
                            <div className="mb-3">
                                <img 
                                    src={qrImageUrl} 
                                    alt="QR Code" 
                                    className="img-fluid border"
                                    style={{ maxWidth: '200px' }}
                                />
                            </div>
                        )}
                        
                        <Alert variant="warning" className="small">
                            <strong>Instructions :</strong><br />
                            • Le badge sera imprimé au format carte de crédit (85.6 × 54 mm)<br />
                            • Utilisez du papier cartonné pour un meilleur résultat<br />
                            • Le QR code permet de scanner la présence
                        </Alert>
                    </div>
                )}
            </Modal.Body>
            
            <Modal.Footer>
                <Button variant="secondary" onClick={onHide}>
                    Annuler
                </Button>
                
                <Button 
                    variant="outline-primary" 
                    onClick={handleDownload}
                    disabled={!qrImageUrl}
                >
                    <Download className="me-2" />
                    Télécharger QR
                </Button>
                
                <Button 
                    variant="primary" 
                    onClick={handlePrint}
                    disabled={printing || !staffMember || !qrImageUrl}
                >
                    {printing ? (
                        <>
                            <Spinner size="sm" className="me-2" />
                            Impression...
                        </>
                    ) : (
                        <>
                            <Printer className="me-2" />
                            Imprimer Badge
                        </>
                    )}
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default StaffQRCardPrint;