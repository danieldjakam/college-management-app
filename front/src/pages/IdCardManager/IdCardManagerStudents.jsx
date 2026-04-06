import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Card, Row, Col, Table, Button, Spinner, Alert, Modal, Form, Badge, Image as BSImage, ProgressBar } from 'react-bootstrap';
import {
    ArrowLeft, PersonBadge, Image as ImageIcon, Upload, Printer, Eye,
    CheckCircleFill, XCircleFill, Download
} from 'react-bootstrap-icons';
import { useParams, useNavigate } from 'react-router-dom';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { host } from '../../utils/fetch';

const IdCardManagerStudents = () => {
    const { classId } = useParams();
    const navigate = useNavigate();

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [classData, setClassData] = useState(null);
    const [students, setStudents] = useState([]);
    const [selectedStudent, setSelectedStudent] = useState(null);
    const [showPhotoModal, setShowPhotoModal] = useState(false);
    const [photoFile, setPhotoFile] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [academicYear, setAcademicYear] = useState('2025-2026');

    // État pour la génération async
    const [generating, setGenerating] = useState(false);
    const [progress, setProgress] = useState(null);
    const pollingRef = useRef(null);

    useEffect(() => {
        loadClassStudents();
        return () => {
            if (pollingRef.current) clearInterval(pollingRef.current);
        };
    }, [classId]);

    const loadClassStudents = async () => {
        try {
            setLoading(true);
            setError(null);

            const response = await secureApiEndpoints.request(`/id-card-manager/classes/${classId}/students`);

            if (response.success) {
                setClassData(response.data.class);
                setStudents(response.data.students);
            } else {
                throw new Error(response.message || 'Erreur lors du chargement des élèves');
            }
        } catch (err) {
            console.error('Erreur chargement élèves:', err);
            setError(err.message || 'Erreur de connexion');
        } finally {
            setLoading(false);
        }
    };

    const handlePhotoUpload = (student) => {
        setSelectedStudent(student);
        setShowPhotoModal(true);
        setPhotoFile(null);
    };

    const handlePhotoChange = (e) => {
        if (e.target.files && e.target.files[0]) {
            setPhotoFile(e.target.files[0]);
        }
    };

    const submitPhotoUpdate = async () => {
        if (!photoFile || !selectedStudent) return;

        try {
            setUploading(true);

            const formData = new FormData();
            formData.append('photo', photoFile);

            const response = await secureApiEndpoints.request(
                `/id-card-manager/students/${selectedStudent.id}/update-photo`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {}
                }
            );

            if (response.success) {
                alert('Photo mise à jour avec succès !');
                setShowPhotoModal(false);
                setPhotoFile(null);
                setSelectedStudent(null);
                loadClassStudents();
            } else {
                throw new Error(response.message || 'Erreur lors de la mise à jour');
            }
        } catch (err) {
            console.error('Erreur upload photo:', err);
            alert(err.message || 'Erreur lors de l\'upload');
        } finally {
            setUploading(false);
        }
    };

    const handlePreviewCard = async (student) => {
        try {
            const response = await secureApiEndpoints.request(
                `/student-cards/student/${student.id}/preview`,
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ academic_year: academicYear })
                }
            );

            if (response.success && response.html) {
                const previewWindow = window.open('', '_blank');
                if (previewWindow) {
                    previewWindow.document.write(response.html);
                    previewWindow.document.close();
                } else {
                    alert('Impossible d\'ouvrir la fenêtre de prévisualisation. Veuillez autoriser les popups.');
                }
            } else {
                alert('Aucun contenu de prévisualisation reçu.');
            }
        } catch (err) {
            console.error('Erreur prévisualisation carte:', err);
            alert('Erreur lors de la prévisualisation: ' + (err.message || 'Erreur inconnue'));
        }
    };

    const handlePrintCard = async (student) => {
        try {
            const response = await fetch(
                `${host}/api/student-cards/student/${student.id}/generate`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    },
                    body: JSON.stringify({ academic_year: academicYear })
                }
            );

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `carte_${student.matricule}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                throw new Error('Erreur lors de la génération du PDF');
            }
        } catch (err) {
            console.error('Erreur impression carte:', err);
            alert('Erreur lors de l\'impression');
        }
    };

    // Polling pour suivre la progression
    const startPolling = useCallback((progressKey) => {
        if (pollingRef.current) clearInterval(pollingRef.current);

        pollingRef.current = setInterval(async () => {
            try {
                const response = await fetch(
                    `${host}/api/student-cards/progress/${progressKey}`,
                    {
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('token')}`
                        }
                    }
                );
                const data = await response.json();

                if (data.success && data.progress) {
                    setProgress(data.progress);

                    if (data.progress.status === 'completed' || data.progress.status === 'failed') {
                        clearInterval(pollingRef.current);
                        pollingRef.current = null;

                        if (data.progress.status === 'completed') {
                            // Télécharger automatiquement
                            const dlResponse = await fetch(
                                `${host}/api/student-cards/download/${progressKey}`,
                                {
                                    headers: {
                                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                                    }
                                }
                            );

                            if (dlResponse.ok) {
                                const blob = await dlResponse.blob();
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = data.progress.file_name || `cartes_classe.pdf`;
                                document.body.appendChild(a);
                                a.click();
                                window.URL.revokeObjectURL(url);
                                document.body.removeChild(a);
                            }

                            setTimeout(() => {
                                setGenerating(false);
                                setProgress(null);
                            }, 3000);
                        } else {
                            setTimeout(() => {
                                setGenerating(false);
                            }, 5000);
                        }
                    }
                }
            } catch (err) {
                console.error('Erreur polling progression:', err);
            }
        }, 1500);
    }, []);

    // Génération async des cartes de toute la classe
    const handlePrintClassCards = async () => {
        try {
            setGenerating(true);
            setProgress({ status: 'queued', percentage: 0, message: 'Lancement...' });

            const response = await fetch(
                `${host}/api/student-cards/class/${classId}/generate-async`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    },
                    body: JSON.stringify({ academic_year: academicYear })
                }
            );

            const data = await response.json();

            if (data.success && data.progress_key) {
                startPolling(data.progress_key);
            } else {
                throw new Error(data.message || 'Erreur lors du lancement');
            }
        } catch (err) {
            console.error('Erreur génération cartes:', err);
            alert('Erreur: ' + (err.message || 'Erreur inconnue'));
            setGenerating(false);
            setProgress(null);
        }
    };

    const getProgressVariant = () => {
        if (!progress) return 'primary';
        if (progress.status === 'completed') return 'success';
        if (progress.status === 'failed') return 'danger';
        return 'primary';
    };

    if (loading) {
        return (
            <div className="text-center p-5">
                <Spinner animation="border" variant="primary" />
                <p className="mt-3">Chargement des élèves...</p>
            </div>
        );
    }

    if (error) {
        return (
            <Alert variant="danger" className="m-4">
                <h5>Erreur</h5>
                <p>{error}</p>
            </Alert>
        );
    }

    const studentsWithPhoto = students.filter(s => s.has_photo).length;
    const studentsWithoutPhoto = students.length - studentsWithPhoto;

    return (
        <div className="container-fluid p-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Body>
                            <div className="d-flex justify-content-between align-items-center">
                                <div className="d-flex align-items-center">
                                    <Button
                                        variant="light"
                                        className="me-3"
                                        onClick={() => navigate('/id-card-manager/dashboard')}
                                    >
                                        <ArrowLeft size={20} />
                                    </Button>
                                    <div>
                                        <h3 className="mb-1">{classData?.name}</h3>
                                        <p className="text-muted mb-0">
                                            {students.length} élève{students.length > 1 ? 's' : ''} •
                                            <span className="text-success ms-2">{studentsWithPhoto} avec photo</span> •
                                            <span className="text-warning ms-2">{studentsWithoutPhoto} sans photo</span>
                                        </p>
                                    </div>
                                </div>
                                <Button
                                    variant="primary"
                                    size="lg"
                                    onClick={handlePrintClassCards}
                                    disabled={studentsWithPhoto === 0 || generating}
                                >
                                    {generating ? (
                                        <>
                                            <Spinner as="span" animation="border" size="sm" className="me-2" />
                                            Génération...
                                        </>
                                    ) : (
                                        <>
                                            <Printer className="me-2" />
                                            Imprimer Toutes les Cartes
                                        </>
                                    )}
                                </Button>
                            </div>

                            {/* Barre de progression */}
                            {generating && progress && (
                                <div className="mt-3">
                                    <div className="d-flex justify-content-between align-items-center mb-1">
                                        <small className="text-muted">{progress.message}</small>
                                        <small className="fw-bold">{progress.percentage || 0}%</small>
                                    </div>
                                    <ProgressBar
                                        now={progress.percentage || 0}
                                        variant={getProgressVariant()}
                                        animated={progress.status !== 'completed' && progress.status !== 'failed'}
                                        striped={progress.status !== 'completed' && progress.status !== 'failed'}
                                    />
                                    {progress.with_photo !== undefined && (
                                        <small className="text-muted mt-1 d-block">
                                            {progress.with_photo} avec photo, {progress.without_photo} sans photo
                                            {progress.duration_seconds && ` — ${progress.duration_seconds}s`}
                                        </small>
                                    )}
                                    {progress.status === 'completed' && (
                                        <Alert variant="success" className="mt-2 mb-0 py-2">
                                            PDF généré et téléchargé avec succès !
                                        </Alert>
                                    )}
                                    {progress.status === 'failed' && (
                                        <Alert variant="danger" className="mt-2 mb-0 py-2">
                                            Erreur: {progress.message}
                                        </Alert>
                                    )}
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Table des élèves */}
            <Row>
                <Col>
                    <Card>
                        <Card.Header className="bg-light">
                            <h5 className="mb-0">Liste des Élèves</h5>
                        </Card.Header>
                        <Card.Body className="p-0">
                            <Table responsive hover className="mb-0">
                                <thead className="table-light">
                                    <tr>
                                        <th>Matricule</th>
                                        <th>Nom Complet</th>
                                        <th>Série</th>
                                        <th className="text-center">Photo</th>
                                        <th className="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {students.map((student) => (
                                        <tr key={student.id}>
                                            <td><strong>{student.matricule}</strong></td>
                                            <td>{student.full_name}</td>
                                            <td>
                                                <Badge bg="info">{student.class_series}</Badge>
                                            </td>
                                            <td className="text-center">
                                                {student.has_photo ? (
                                                    <CheckCircleFill className="text-success" size={24} />
                                                ) : (
                                                    <XCircleFill className="text-danger" size={24} />
                                                )}
                                            </td>
                                            <td className="text-center">
                                                <Button
                                                    variant="outline-primary"
                                                    size="sm"
                                                    className="me-2"
                                                    onClick={() => handlePhotoUpload(student)}
                                                >
                                                    <Upload className="me-1" />
                                                    Photo
                                                </Button>
                                                <Button
                                                    variant="outline-info"
                                                    size="sm"
                                                    className="me-2"
                                                    onClick={() => handlePreviewCard(student)}
                                                    disabled={!student.has_photo}
                                                >
                                                    <Eye className="me-1" />
                                                    Prévisualiser
                                                </Button>
                                                <Button
                                                    variant="outline-success"
                                                    size="sm"
                                                    onClick={() => handlePrintCard(student)}
                                                    disabled={!student.has_photo}
                                                >
                                                    <Printer className="me-1" />
                                                    Imprimer
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Modal Upload Photo */}
            <Modal show={showPhotoModal} onHide={() => setShowPhotoModal(false)} centered>
                <Modal.Header closeButton>
                    <Modal.Title>
                        <Upload className="me-2" />
                        Modifier la Photo - {selectedStudent?.full_name}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {selectedStudent?.has_photo && selectedStudent.photo && (
                        <div className="text-center mb-3">
                            <p className="text-muted">Photo actuelle:</p>
                            <BSImage
                                src={`${host}/storage/${selectedStudent.photo}`}
                                alt={selectedStudent.full_name}
                                style={{ maxWidth: '200px', maxHeight: '200px' }}
                                rounded
                            />
                        </div>
                    )}

                    <Form.Group>
                        <Form.Label>Nouvelle Photo</Form.Label>
                        <Form.Control
                            type="file"
                            accept="image/jpeg,image/png,image/jpg"
                            onChange={handlePhotoChange}
                        />
                        <Form.Text className="text-muted">
                            Formats acceptés: JPG, JPEG, PNG (max 2MB)
                        </Form.Text>
                    </Form.Group>

                    {photoFile && (
                        <Alert variant="info" className="mt-3 mb-0">
                            <strong>Fichier sélectionné:</strong> {photoFile.name}
                        </Alert>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowPhotoModal(false)} disabled={uploading}>
                        Annuler
                    </Button>
                    <Button
                        variant="primary"
                        onClick={submitPhotoUpdate}
                        disabled={!photoFile || uploading}
                    >
                        {uploading ? (
                            <>
                                <Spinner as="span" animation="border" size="sm" className="me-2" />
                                Upload en cours...
                            </>
                        ) : (
                            <>
                                <Upload className="me-2" />
                                Enregistrer
                            </>
                        )}
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    );
};

export default IdCardManagerStudents;
