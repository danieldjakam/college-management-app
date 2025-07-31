import React, { useState, useRef } from 'react';
import { Modal, Button, Row, Col, Alert } from 'react-bootstrap';
import { Camera, Upload, X } from 'react-bootstrap-icons';

const PhotoCapture = ({ show, onHide, onPhotoSelected }) => {
    const [mode, setMode] = useState('select'); // 'select', 'camera', 'preview'
    const [photoPreview, setPhotoPreview] = useState(null);
    const [photoFile, setPhotoFile] = useState(null);
    const [isStreaming, setIsStreaming] = useState(false);
    const [error, setError] = useState('');
    const [uploading, setUploading] = useState(false);
    
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const fileInputRef = useRef(null);
    const streamRef = useRef(null);

    // Démarrer la caméra
    const startCamera = async () => {
        try {
            setError('');
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 640, height: 480 }
            });
            
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                streamRef.current = stream;
                setIsStreaming(true);
                setMode('camera');
            }
        } catch (err) {
            setError('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
            console.error('Camera error:', err);
        }
    };

    // Arrêter la caméra
    const stopCamera = () => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach(track => track.stop());
            streamRef.current = null;
        }
        setIsStreaming(false);
    };

    // Prendre une photo avec la caméra
    const capturePhoto = () => {
        if (videoRef.current && canvasRef.current) {
            const canvas = canvasRef.current;
            const video = videoRef.current;
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            
            canvas.toBlob((blob) => {
                const file = new File([blob], 'camera-photo.jpg', { type: 'image/jpeg' });
                setPhotoFile(file);
                setPhotoPreview(URL.createObjectURL(blob));
                setMode('preview');
                stopCamera();
            }, 'image/jpeg', 0.8);
        }
    };

    // Sélectionner un fichier
    const handleFileSelect = (event) => {
        const file = event.target.files[0];
        if (file) {
            if (file.type.startsWith('image/')) {
                setPhotoFile(file);
                setPhotoPreview(URL.createObjectURL(file));
                setMode('preview');
                setError('');
            } else {
                setError('Veuillez sélectionner un fichier image (JPG, PNG).');
            }
        }
    };

    // Upload de la photo
    const uploadPhoto = async () => {
        if (!photoFile) return;
        
        setUploading(true);
        setError('');
        
        try {
            const formData = new FormData();
            formData.append('photo', photoFile);
            
            const response = await fetch('http://localhost:8000/api/upload-photo', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                onPhotoSelected(result.data.url);
                handleClose();
            } else {
                setError(result.message || 'Erreur lors de l\'upload');
            }
        } catch (err) {
            setError('Erreur lors de l\'upload de la photo');
            console.error('Upload error:', err);
        } finally {
            setUploading(false);
        }
    };

    // Fermer et nettoyer
    const handleClose = () => {
        stopCamera();
        setMode('select');
        setPhotoPreview(null);
        setPhotoFile(null);
        setError('');
        setUploading(false);
        onHide();
    };

    // Retour au mode sélection
    const backToSelection = () => {
        setMode('select');
        setPhotoPreview(null);
        setPhotoFile(null);
        setError('');
    };

    return (
        <Modal show={show} onHide={handleClose} size="lg" centered>
            <Modal.Header closeButton>
                <Modal.Title>
                    {mode === 'select' && 'Ajouter une photo'}
                    {mode === 'camera' && 'Prendre une photo'}
                    {mode === 'preview' && 'Aperçu de la photo'}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                {error && <Alert variant="danger">{error}</Alert>}
                
                {mode === 'select' && (
                    <Row className="text-center">
                        <Col md={6} className="mb-3">
                            <div className="d-grid">
                                <Button
                                    variant="primary"
                                    size="lg"
                                    onClick={startCamera}
                                    className="py-4"
                                >
                                    <Camera size={32} className="mb-2 d-block mx-auto" />
                                    Prendre une photo
                                </Button>
                            </div>
                        </Col>
                        <Col md={6} className="mb-3">
                            <div className="d-grid">
                                <Button
                                    variant="outline-primary"
                                    size="lg"
                                    onClick={() => fileInputRef.current?.click()}
                                    className="py-4"
                                >
                                    <Upload size={32} className="mb-2 d-block mx-auto" />
                                    Sélectionner un fichier
                                </Button>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/*"
                                    onChange={handleFileSelect}
                                    style={{ display: 'none' }}
                                />
                            </div>
                        </Col>
                    </Row>
                )}

                {mode === 'camera' && (
                    <div className="text-center">
                        <video
                            ref={videoRef}
                            autoPlay
                            style={{
                                width: '100%',
                                maxWidth: '500px',
                                borderRadius: '8px',
                                marginBottom: '20px'
                            }}
                        />
                        <canvas ref={canvasRef} style={{ display: 'none' }} />
                        <div>
                            <Button
                                variant="success"
                                size="lg"
                                onClick={capturePhoto}
                                className="me-3"
                            >
                                <Camera className="me-2" />
                                Capturer
                            </Button>
                            <Button
                                variant="secondary"
                                onClick={backToSelection}
                            >
                                Annuler
                            </Button>
                        </div>
                    </div>
                )}

                {mode === 'preview' && photoPreview && (
                    <div className="text-center">
                        <img
                            src={photoPreview}
                            alt="Aperçu"
                            style={{
                                width: '100%',
                                maxWidth: '400px',
                                borderRadius: '8px',
                                marginBottom: '20px'
                            }}
                        />
                        <div>
                            <Button
                                variant="success"
                                size="lg"
                                onClick={uploadPhoto}
                                disabled={uploading}
                                className="me-3"
                            >
                                {uploading ? 'Upload...' : 'Utiliser cette photo'}
                            </Button>
                            <Button
                                variant="secondary"
                                onClick={backToSelection}
                                disabled={uploading}
                            >
                                Retour
                            </Button>
                        </div>
                    </div>
                )}
            </Modal.Body>
        </Modal>
    );
};

export default PhotoCapture;