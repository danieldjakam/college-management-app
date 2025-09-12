import React, { useState, useEffect } from 'react';
import { Modal, Button, Spinner, Alert, ProgressBar } from 'react-bootstrap';
import { FileEarmarkPdf, People, Download } from 'react-bootstrap-icons';
import { useSchool } from '../contexts/SchoolContext';
import Swal from 'sweetalert2';
import { host } from '../utils/fetch';

const BulkStaffCardGenerator = ({ show, onHide }) => {
    const [loading, setLoading] = useState(false);
    const [generating, setGenerating] = useState(false);
    const [progress, setProgress] = useState(0);
    const [staffData, setStaffData] = useState({ users: [], teachers: [], total: 0 });
    const { schoolSettings } = useSchool();

    // Charger toutes les données du personnel
    useEffect(() => {
        if (show) {
            fetchAllStaff();
        }
    }, [show]);

    const fetchAllStaff = async () => {
        setLoading(true);
        try {
            const [usersResponse, teachersResponse] = await Promise.all([
                fetch(`${host}/api/users/all`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Content-Type': 'application/json'
                    }
                }),
                fetch(`${host}/api/teachers`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Content-Type': 'application/json'
                    }
                })
            ]);

            const usersData = await usersResponse.json();
            const teachersData = await teachersResponse.json();

            console.log('API Response - Users:', usersData);
            console.log('API Response - Teachers:', teachersData);

            // Adapter selon la structure de réponse de l'API
            const users = Array.isArray(usersData) ? usersData : (usersData.data || usersData.users || []);
            const teachers = Array.isArray(teachersData) ? teachersData : (teachersData.data || teachersData.teachers || []);

            console.log('Processed staff data:', {
                users: users.length,
                teachers: teachers.length,
                usersStructure: users[0] || 'No users',
                teachersStructure: teachers[0] || 'No teachers'
            });

            setStaffData({
                users: users,
                teachers: teachers,
                total: users.length + teachers.length
            });

        } catch (error) {
            console.error('Error fetching staff data:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erreur de chargement',
                text: 'Impossible de charger les données du personnel'
            });
        } finally {
            setLoading(false);
        }
    };

    const convertImageToBase64 = async (imageSrc) => {
        return new Promise((resolve) => {
            if (!imageSrc || imageSrc === 'undefined') {
                resolve('');
                return;
            }

            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = function() {
                try {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                    
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
        const photoFields = ['photo_url', 'photo', 'user_photo', 'image', 'avatar'];
        
        for (const field of photoFields) {
            const photoValue = staff[field];
            if (photoValue && photoValue !== 'undefined') {
                if (photoValue.startsWith('http')) {
                    return photoValue.replace('127.0.0.1:8000', host);
                } else {
                    return photoValue.startsWith('/') ? 
                        `${host}${photoValue}` : 
                        `${host}/storage/${photoValue}`;
                }
            }
        }
        
        return `${window.location.origin}/static/media/1.png`;
    };

    const getStaffTypeLabel = (staff) => {
        // Pour les utilisateurs système
        if (staff.role) {
            const roleLabels = {
                'teacher': 'Enseignant',
                'accountant': 'Comptable',
                'comptable_superieur': 'Comptable Supérieur',
                'surveillant_general': 'Surveillant Général',
                'admin': 'Administrateur'
            };
            return roleLabels[staff.role] || staff.role;
        }
        
        // Pour les enseignants vacataires (teachers table)
        return 'Enseignant Vacataire';
    };

    const generateStaffCard = async (staff, index, total) => {
        const staffPhotoUrl = getStaffPhotoUrl(staff);
        const staffTypeLabel = getStaffTypeLabel(staff);
        
        // Obtenir le nom selon la structure des données
        const staffName = staff.name || `${staff.first_name || ''} ${staff.last_name || ''}`.trim();
        const staffEmail = staff.email || 'Non renseigné';
        const staffPhone = staff.phone || staff.telephone || '+237 XXX XXX XXX';
        const staffId = staff.id;

        // Convertir la photo en base64
        const staffPhotoBase64 = await convertImageToBase64(staffPhotoUrl);

        // Mettre à jour le progrès
        const progressPercent = Math.round(((index + 1) / total) * 100);
        setProgress(progressPercent);

        return {
            id: staffId,
            name: staffName,
            email: staffEmail,
            phone: staffPhone,
            role: staffTypeLabel,
            photoBase64: staffPhotoBase64,
            qrData: `STAFF_${staffId}`
        };
    };

    const generateBulkPDF = async () => {
        if (staffData.total === 0) return;

        setGenerating(true);
        setProgress(0);

        try {
            // Combiner tous les membres du personnel
            const allStaff = [...staffData.users, ...staffData.teachers];
            
            console.log('Starting bulk card generation for', allStaff.length, 'staff members');

            // Générer les données pour toutes les cartes
            const cardPromises = allStaff.map((staff, index) => 
                generateStaffCard(staff, index, allStaff.length)
            );

            const cardData = await Promise.all(cardPromises);

            // Créer le HTML pour toutes les cartes, groupées par 8 par page
            const cardsPerPage = 8;
            const pages = [];
            
            for (let i = 0; i < cardData.length; i += cardsPerPage) {
                const pageCards = cardData.slice(i, i + cardsPerPage);
                pages.push(pageCards);
            }
            
            const pagesHtml = pages.map((pageCards, pageIndex) => `
                <div class="page" style="${pageIndex > 0 ? 'page-break-before: always;' : ''}">
                    <div class="cards-grid">
                        ${pageCards.map(card => `
                            <div class="card-container">
                                <!-- ID Badge -->
                                <div class="staff-id">ID: ${card.id}</div>
                                
                                <!-- Nom de l'utilisateur -->
                                <div class="staff-name">${card.name}</div>
                                
                                <!-- Poste -->
                                <div class="staff-role">${card.role}</div>
                                
                                <!-- Téléphone personnel -->
                                <div class="staff-phone">${card.phone}</div>
                                
                                <!-- Photo -->
                                <img src="${card.photoBase64 || getStaffPhotoUrl({})}" alt="Staff Photo" class="staff-photo">
                                
                                <!-- QR Code -->
                                <img 
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(card.qrData)}&margin=1"
                                    alt="QR Code"
                                    class="qr-code"
                                />
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('');

            // Générer le HTML complet du PDF
            const pdfHtml = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Badges Personnel - ${schoolSettings?.school_name || 'École'} (${cardData.length} membres)</title>
                    <style>
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: 'Arial', 'Helvetica', sans-serif;
                            background: #f5f5f5;
                            padding: 10mm;
                        }

                        h1 {
                            text-align: center;
                            margin-bottom: 20px;
                            color: #2c3e50;
                            font-size: 18px;
                            page-break-after: avoid;
                        }

                        .cards-grid {
                            display: grid;
                            grid-template-columns: repeat(2, 1fr);
                            grid-template-rows: repeat(4, 1fr);
                            gap: 8mm;
                            justify-items: center;
                            align-items: center;
                            height: calc(100vh - 80mm);
                            max-height: 250mm;
                        }
                        
                        .card-container {
                            width: 85.6mm;
                            height: 54mm;
                            position: relative;
                            background-image: url('${window.location.origin}/assets/images/card-background-cpb.png');
                            background-size: cover;
                            background-position: center;
                            background-repeat: no-repeat;
                            border-radius: 8px;
                            overflow: hidden;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                            page-break-inside: avoid;
                        }
                        
                        /* Nom de l'utilisateur */
                        .staff-name {
                            position: absolute;
                            left: 35px;
                            top: 52px;
                            color: white;
                            font-size: 8px;
                            font-weight: bold;
                            text-transform: uppercase;
                            max-width: 120px;
                            line-height: 1.1;
                        }
                        
                        /* Poste */
                        .staff-role {
                            position: absolute;
                            left: 35px;
                            top: 66px;
                            color: white;
                            font-size: 7px;
                            font-weight: normal;
                            max-width: 120px;
                            line-height: 1.1;
                        }
                        
                        /* Téléphone personnel */
                        .staff-phone {
                            position: absolute;
                            left: 35px;
                            top: 82px;
                            color: white;
                            font-size: 7px;
                            font-weight: normal;
                        }
                        
                        /* Photo dans la zone circulaire */
                        .staff-photo {
                            position: absolute;
                            right: 48px;
                            top: 32px;
                            width: 80px;
                            height: 80px;
                            border-radius: 50%;
                            object-fit: cover;
                            border: 3px solid white;
                            background: white;
                        }
                        
                        /* QR Code */
                        .qr-code {
                            position: absolute;
                            right: 16px;
                            bottom: 32px;
                            width: 48px;
                            height: 48px;
                            object-fit: contain;
                            background: white;
                            border-radius: 4px;
                            padding: 2px;
                        }
                        
                        /* ID Badge */
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
                                padding: 8mm;
                            }
                            
                            .cards-grid {
                                gap: 6mm;
                                height: auto;
                                max-height: none;
                            }
                            
                            .card-container {
                                page-break-inside: avoid;
                            }

                            /* Forcer une nouvelle page après chaque groupe de 8 cartes */
                            .card-container:nth-child(8n) {
                                page-break-after: always;
                            }

                            /* S'assurer que les 8 cartes tiennent sur une page A4 */
                            .cards-grid {
                                min-height: 240mm;
                                max-height: 240mm;
                            }
                        }
                        
                        @page {
                            size: A4;
                            margin: 10mm;
                        }

                        .summary {
                            text-align: center;
                            margin-bottom: 15px;
                            color: #666;
                            font-size: 12px;
                        }

                        .print-info {
                            position: fixed;
                            top: 10px;
                            right: 10px;
                            background: rgba(0,0,0,0.8);
                            color: white;
                            padding: 10px;
                            border-radius: 5px;
                            font-size: 12px;
                            z-index: 1000;
                        }

                        @media print {
                            .print-info {
                                display: none;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-info">
                        ${cardData.length} badges générés - Utilisez Ctrl+P pour imprimer
                    </div>

                    <h1>BADGES DU PERSONNEL</h1>
                    <div class="summary">
                        ${schoolSettings?.school_name?.toUpperCase() || 'ÉTABLISSEMENT'} - 
                        ${cardData.length} membres du personnel - 
                        ${pages.length} page(s) - 8 badges par page - 
                        Généré le ${new Date().toLocaleDateString('fr-FR')}
                    </div>
                    
                    ${pagesHtml}
                </body>
                </html>
            `;

            // Ouvrir dans une nouvelle fenêtre pour impression
            const printWindow = window.open('', '_blank', 'width=1200,height=800');
            
            if (!printWindow) {
                throw new Error('Popup bloqué. Veuillez autoriser les popups pour imprimer.');
            }

            printWindow.document.write(pdfHtml);
            printWindow.document.close();

            // Permettre l'impression
            printWindow.onload = () => {
                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'PDF Généré avec succès !',
                        text: `${cardData.length} badges du personnel sont prêts à imprimer`,
                        timer: 3000,
                        showConfirmButton: true,
                        confirmButtonText: 'Fermer'
                    });
                }, 1000);
            };

        } catch (error) {
            console.error('Erreur génération PDF:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erreur de génération',
                text: error.message || 'Impossible de générer le PDF des badges'
            });
        } finally {
            setGenerating(false);
            setProgress(0);
        }
    };

    return (
        <Modal 
            show={show} 
            onHide={onHide} 
            centered 
            size="lg"
            backdrop={generating ? 'static' : true}
            keyboard={!generating}
            aria-labelledby="bulk-staff-cards-modal-title"
        >
            <Modal.Header closeButton={!generating}>
                <Modal.Title id="bulk-staff-cards-modal-title">
                    <FileEarmarkPdf className="me-2" />
                    Génération PDF - Badges du Personnel
                </Modal.Title>
            </Modal.Header>
            
            <Modal.Body>
                {loading ? (
                    <div className="text-center py-4">
                        <Spinner animation="border" variant="primary" />
                        <p className="mt-2">Chargement des données du personnel...</p>
                    </div>
                ) : (
                    <div>
                        <Alert variant="info" className="mb-3">
                            <People className="me-2" />
                            <strong>Personnel détecté :</strong>
                            <ul className="mb-0 mt-2">
                                <li><strong>{staffData.users.length}</strong> utilisateurs système (admins, enseignants permanents, comptables)</li>
                                <li><strong>{staffData.teachers.length}</strong> enseignants vacataires</li>
                                <li><strong>Total : {staffData.total}</strong> membres du personnel</li>
                            </ul>
                        </Alert>

                        {generating && (
                            <div className="mb-3">
                                <div className="d-flex justify-content-between align-items-center mb-2">
                                    <span>Génération en cours...</span>
                                    <span>{progress}%</span>
                                </div>
                                <ProgressBar 
                                    now={progress} 
                                    variant="success"
                                    animated 
                                />
                            </div>
                        )}

                        <Alert variant="warning" className="small">
                            <strong>Instructions :</strong><br />
                            • Le PDF contiendra tous les badges au format carte de crédit (85.6 × 54 mm)<br />
                            • <strong>8 cartes par page A4</strong> : 2 colonnes × 4 lignes<br />
                            • Utilisez du papier cartonné pour un meilleur résultat<br />
                            • Les QR codes permettent de scanner la présence<br />
                            • <strong>Assure-toi que l'image de fond est dans : public/assets/images/card-background-cpb.png</strong>
                        </Alert>

                        {staffData.total === 0 && !loading && (
                            <Alert variant="danger">
                                Aucun membre du personnel trouvé. Vérifiez les données.
                            </Alert>
                        )}
                    </div>
                )}
            </Modal.Body>
            
            <Modal.Footer>
                <Button variant="secondary" onClick={onHide} disabled={generating}>
                    Annuler
                </Button>
                
                <Button 
                    variant="primary" 
                    onClick={generateBulkPDF}
                    disabled={loading || generating || staffData.total === 0}
                >
                    {generating ? (
                        <>
                            <Spinner size="sm" className="me-2" />
                            Génération... {progress}%
                        </>
                    ) : (
                        <>
                            <Download className="me-2" />
                            Générer PDF ({staffData.total} badges)
                        </>
                    )}
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default BulkStaffCardGenerator;