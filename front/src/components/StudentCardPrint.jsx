import React, { useState } from 'react';
import { Modal, Button, Spinner } from 'react-bootstrap';
import { Printer } from 'react-bootstrap-icons';
import StudentCard from './StudentCard';
import { useSchool } from '../contexts/SchoolContext';
import Swal from 'sweetalert2';
import { host } from '../utils/fetch';

const StudentCardPrint = ({ student, schoolYear, show, onHide, onPrintSuccess }) => {
    const [printing, setPrinting] = useState(false);
    const { schoolSettings } = useSchool();

    const convertImageToBase64 = async (imageSrc) => {
        return new Promise((resolve, reject) => {
            if (!imageSrc) {
                console.log('No image source provided');
                resolve('');
                return;
            }

            console.log('Converting image to base64:', imageSrc);

            // Si c'est déjà une image en base64, la retourner directement
            if (imageSrc.startsWith('data:image')) {
                console.log('Image is already base64');
                resolve(imageSrc);
                return;
            }

            const img = new Image();
            img.crossOrigin = 'anonymous';

            img.onload = function() {
                console.log('Image loaded successfully, converting to base64...');
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);

                try {
                    const dataURL = canvas.toDataURL('image/png');
                    console.log('Image converted to base64, length:', dataURL.length);
                    resolve(dataURL);
                } catch (error) {
                    console.error('Error converting image to base64:', error);
                    resolve('');
                }
            };

            img.onerror = function(error) {
                console.error('Error loading image:', imageSrc, error);
                // Essayer de charger l'image sans CORS
                const img2 = new Image();
                img2.onload = function() {
                    console.log('Image loaded without CORS, converting...');
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = img2.width;
                    canvas.height = img2.height;
                    ctx.drawImage(img2, 0, 0);

                    try {
                        const dataURL = canvas.toDataURL('image/png');
                        resolve(dataURL);
                    } catch (error) {
                        console.error('Failed to convert without CORS:', error);
                        resolve('');
                    }
                };
                img2.onerror = function() {
                    console.error('Failed to load image even without CORS');
                    resolve('');
                };
                img2.src = imageSrc;
            };

            // Ajouter un timestamp pour éviter le cache
            const separator = imageSrc.includes('?') ? '&' : '?';
            img.src = imageSrc + separator + 't=' + Date.now();
        });
    };

    const getStudentPhotoUrl = (student) => {
        console.log('Getting student photo URL for:', student);

        if (student.photo_url) {
            console.log('Using photo_url:', student.photo_url);
            return student.photo_url;
        }

        if (student.photo) {
            if (student.photo.startsWith('http')) {
                console.log('Using full URL from photo:', student.photo);
                return student.photo;
            }
            const fullUrl = `${host}/storage/${student.photo}`;
            console.log('Constructed photo URL:', fullUrl);
            return fullUrl;
        }

        // Utiliser l'image par défaut importée
        console.log('Using default photo');
        return null; // On laissera l'image SVG par défaut dans le HTML
    };

    const getLogoUrl = () => {
        console.log('Getting logo URL, schoolSettings:', schoolSettings);

        if (schoolSettings?.logo_url) {
            console.log('Using logo_url:', schoolSettings.logo_url);
            return schoolSettings.logo_url;
        }

        if (schoolSettings?.logo) {
            if (schoolSettings.logo.startsWith('http')) {
                console.log('Using full URL from logo:', schoolSettings.logo);
                return schoolSettings.logo;
            }
            const fullUrl = `${host}/storage/${schoolSettings.logo}`;
            console.log('Constructed logo URL:', fullUrl);
            return fullUrl;
        }

        console.log('No logo found');
        return null;
    };

    const handlePrint = async () => {
        if (!student) return;

        try {
            setPrinting(true);

            // Télécharger le PDF depuis le backend
            console.log('Downloading student card PDF from backend for student:', student.id);

            const response = await fetch(`${host}/api/students/${student.id}/card`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/pdf'
                }
            });

            if (!response.ok) {
                throw new Error('Erreur lors du téléchargement de la carte');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);

            // Ouvrir le PDF dans un nouvel onglet
            const printWindow = window.open(url, '_blank');

            if (!printWindow) {
                // Si le popup est bloqué, télécharger le fichier
                const link = document.createElement('a');
                link.href = url;
                link.download = `carte_scolaire_${student.matricule || student.id}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            }

            setPrinting(false);
            if (onPrintSuccess) {
                onPrintSuccess();
            }

        } catch (error) {
            console.error('Erreur lors de l\'impression:', error);
            setPrinting(false);

            Swal.fire({
                title: 'Erreur d\'impression',
                text: error.message || 'Impossible de télécharger la carte',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    };

    if (!student) return null;

    return (
        <Modal show={show} onHide={onHide} size="lg" centered>
            <Modal.Header closeButton>
                <Modal.Title>
                    <Printer className="me-2" />
                    Carte Scolaire - {student.first_name} {student.last_name}
                </Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="text-center mb-3">
                    <div className="d-inline-block" style={{ transform: 'scale(1.5)', transformOrigin: 'center' }}>
                        <StudentCard
                            student={student}
                            schoolYear={schoolYear}
                        />
                    </div>
                </div>

                <div className="text-center text-muted">
                    <small>
                        📌 Aperçu de la carte scolaire<br />
                        Cliquez sur "Télécharger" pour obtenir le PDF avec photo et logo
                    </small>
                </div>
            </Modal.Body>

            <Modal.Footer>
                <Button variant="secondary" onClick={onHide}>
                    Annuler
                </Button>
                <Button
                    variant="primary"
                    onClick={handlePrint}
                    disabled={printing}
                >
                    {printing ? (
                        <>
                            <Spinner
                                as="span"
                                animation="border"
                                size="sm"
                                role="status"
                                className="me-2"
                            />
                            Génération...
                        </>
                    ) : (
                        <>
                            <Printer className="me-2" />
                            Télécharger la carte PDF
                        </>
                    )}
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default StudentCardPrint;
