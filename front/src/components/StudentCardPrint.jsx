import React, { useState, useEffect } from 'react';
import { Modal, Button, Spinner } from 'react-bootstrap';
import { Printer, Download } from 'react-bootstrap-icons';
import Swal from 'sweetalert2';
import { host } from '../utils/fetch';
import { secureApi } from '../utils/apiMigration';

const StudentCardPrint = ({ student, schoolYear, show, onHide, onPrintSuccess }) => {
    const [printing, setPrinting] = useState(false);
    const [previewHtml, setPreviewHtml] = useState('');
    const [loadingPreview, setLoadingPreview] = useState(false);

    useEffect(() => {
        if (show && student) {
            loadPreview();
        }
    }, [show, student?.id]);

    const loadPreview = async () => {
        if (!student) return;
        try {
            setLoadingPreview(true);
            const academicYear = schoolYear?.name || `${new Date().getFullYear()}-${new Date().getFullYear() + 1}`;
            const response = await secureApi.post(`/student-cards/student/${student.id}/preview`, {
                academic_year: academicYear
            });
            if (response && response.success && response.html) {
                setPreviewHtml(response.html);
            }
        } catch (err) {
            console.error('Erreur chargement apercu:', err);
        } finally {
            setLoadingPreview(false);
        }
    };

    const handlePrint = async () => {
        if (!student) return;

        try {
            setPrinting(true);

            const response = await fetch(`${host}/api/students/${student.id}/card`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/pdf'
                }
            });

            if (!response.ok) {
                throw new Error('Erreur lors du telechargement de la carte');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);

            const printWindow = window.open(url, '_blank');

            if (!printWindow) {
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
            console.error('Erreur:', error);
            setPrinting(false);

            Swal.fire({
                title: 'Erreur',
                text: error.message || 'Impossible de telecharger la carte',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    };

    if (!student) return null;

    return (
        <Modal show={show} onHide={onHide} size="lg" centered>
            <Modal.Header closeButton style={{ background: '#5B2C87', color: 'white' }}>
                <Modal.Title>
                    <Printer className="me-2" />
                    Carte Scolaire - {student.last_name} {student.first_name}
                </Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="text-center mb-3">
                    {loadingPreview ? (
                        <div className="py-5">
                            <Spinner animation="border" variant="primary" />
                            <p className="mt-2 text-muted">Chargement de l'apercu...</p>
                        </div>
                    ) : previewHtml ? (
                        <div
                            className="d-inline-block"
                            style={{ transform: 'scale(1.2)', transformOrigin: 'center top', marginBottom: '40px' }}
                            dangerouslySetInnerHTML={{ __html: previewHtml }}
                        />
                    ) : (
                        <div className="py-4 text-muted">
                            <p>Apercu non disponible. Cliquez sur Telecharger pour generer le PDF.</p>
                        </div>
                    )}
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
                            <Spinner as="span" animation="border" size="sm" role="status" className="me-2" />
                            Generation...
                        </>
                    ) : (
                        <>
                            <Download className="me-2" />
                            Telecharger la carte PDF
                        </>
                    )}
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default StudentCardPrint;
