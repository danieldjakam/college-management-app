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
        
        const photoFields = ['photo_url', 'photo', 'user_photo', 'image', 'avatar'];
        
        for (const field of photoFields) {
            const photoValue = staff[field];
            if (photoValue) {
                console.log(`Found photo in field '${field}':`, photoValue);
                
                if (photoValue.startsWith('http')) {
                    return photoValue.replace('127.0.0.1:8000', host);
                } else {
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

    const handlePrint = async () => {
        if (!staffMember || !qrImageUrl) return;

        try {
            setPrinting(true);

            // Convertir les images en base64 pour l'impression
            const staffPhotoUrl = getStaffPhotoUrl(staffMember);
            
            console.log('Preparing images for staff card print:', {
                staffPhotoUrl,
                qrImageUrl,
                staffId: staffMember.id
            });
            
            // Convertir l'image de background et la photo du staff
            const cardBackgroundUrl = `${window.location.origin}/assets/images/card-background-cpb.png`; // Chemin vers ton image de fond
            
            const [staffPhotoBase64, backgroundBase64] = await Promise.all([
                convertImageToBase64(staffPhotoUrl),
                convertImageToBase64(cardBackgroundUrl)
            ]);
            
            console.log('Images converted to base64:', {
                hasStaffPhoto: !!staffPhotoBase64,
                hasBackground: !!backgroundBase64
            });

            // Créer une nouvelle fenêtre pour l'impression
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            if (!printWindow) {
                throw new Error('Popup bloqué. Veuillez autoriser les popups pour imprimer.');
            }

            const staffTypeLabel = getStaffTypeLabel(staffMember.role);

            // Générer le HTML du badge avec ton design en background
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
                            position: relative;
                            background-image: url('${backgroundBase64 || cardBackgroundUrl}');
                            background-size: cover;
                            background-position: center;
                            background-repeat: no-repeat;
                            border-radius: 8px;
                            overflow: hidden;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        }
                        
                        /* Nom de l'utilisateur - à côté de l'icône personne */
                        .staff-name {
                            position: absolute;
                            left: 35px; /* À côté de l'icône personne */
                            top: 52px; /* Aligné avec l'icône */
                            color: white;
                            font-size: 8px;
                            font-weight: bold;
                            text-transform: uppercase;
                            max-width: 120px;
                            line-height: 1.1;
                        }
                        
                        /* Poste - juste en dessous du nom */
                        .staff-role {
                            position: absolute;
                            left: 35px; /* Même alignement que le nom */
                            top: 66px; /* En dessous du nom */
                            color: white;
                            font-size: 7px;
                            font-weight: normal;
                            max-width: 120px;
                            line-height: 1.1;
                        }
                        
                        /* Téléphone personnel - au-dessus du téléphone école */
                        .staff-phone {
                            position: absolute;
                            left: 35px; /* Aligné avec les autres infos */
                            top: 82px; /* Au-dessus du téléphone école */
                            color: white;
                            font-size: 7px;
                            font-weight: normal;
                        }
                        
                        /* Photo dans la zone circulaire */
                        .staff-photo {
                            position: absolute;
                            right: 48px; /* Position dans la zone circulaire */
                            top: 32px; /* Centré verticalement dans le cercle */
                            width: 80px; /* Taille pour s'adapter au cercle */
                            height: 80px;
                            border-radius: 50%;
                            object-fit: cover;
                            border: 3px solid white;
                            background: white;
                        }
                        
                        /* QR Code dans la zone en pointillés */
                        .qr-code {
                            position: absolute;
                            right: 16px; /* Dans la zone carrée en pointillés */
                            bottom: 32px; /* Position en bas à droite */
                            width: 48px; /* Taille pour s'adapter au carré */
                            height: 48px;
                            object-fit: contain;
                            background: white;
                            border-radius: 4px;
                            padding: 2px;
                        }
                        
                        /* ID Badge - petit numéro en haut */
                        .staff-id {
                            position: absolute;
                            right: 16px;
                            top: 16px;
                            color: white;
                            font-size: 6px;
                            font-weight: bold;
                            background: rgba(255,255,255,0.2);
                            padding: 2px 6px;
                            border-radius: 10px;
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
                        <!-- ID Badge -->
                        <div class="staff-id">ID: ${staffMember.id}</div>
                        
                        <!-- Nom de l'utilisateur -->
                        <div class="staff-name">${staffMember.name}</div>
                        
                        <!-- Poste -->
                        <div class="staff-role">${staffTypeLabel}</div>
                        
                        <!-- Téléphone personnel -->
                        <div class="staff-phone">${staffMember.phone || staffMember.telephone || '+237 XXX XXX XXX'}</div>
                        
                        <!-- Photo -->
                        <img src="${staffPhotoBase64 || getStaffPhotoUrl(staffMember)}" alt="Staff Photo" class="staff-photo">
                        
                        <!-- QR Code -->
                        <img 
                            src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(`STAFF_${staffMember.id}`)}&margin=1"
                            alt="QR Code"
                            class="qr-code"
                        />
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
                            • Le QR code permet de scanner la présence<br />
                            • <strong>Assure-toi que l'image de fond est dans : public/assets/images/card-background-cpb.png</strong>
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