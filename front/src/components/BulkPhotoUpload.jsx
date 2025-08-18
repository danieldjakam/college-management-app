import React, { useState } from 'react';
import { Button, Modal, Alert, ProgressBar, Badge } from 'react-bootstrap';
import { Images, Upload, X, CheckCircle, XCircle } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../utils/apiMigration';

const BulkPhotoUpload = ({ onUploadSuccess }) => {
    const [show, setShow] = useState(false);
    const [files, setFiles] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [uploadResults, setUploadResults] = useState(null);
    const [dragOver, setDragOver] = useState(false);

    const handleClose = () => {
        setShow(false);
        setFiles([]);
        setUploadResults(null);
    };

    const handleFileSelect = (event) => {
        const selectedFiles = Array.from(event.target.files);
        const imageFiles = selectedFiles.filter(file => 
            file.type.startsWith('image/')
        );
        setFiles(imageFiles);
    };

    const handleDrop = (event) => {
        event.preventDefault();
        setDragOver(false);
        
        const droppedFiles = Array.from(event.dataTransfer.files);
        const imageFiles = droppedFiles.filter(file => 
            file.type.startsWith('image/')
        );
        setFiles(imageFiles);
    };

    const handleDragOver = (event) => {
        event.preventDefault();
        setDragOver(true);
    };

    const handleDragLeave = (event) => {
        event.preventDefault();
        setDragOver(false);
    };

    const extractStudentNumber = (filename) => {
        // Extraire le matricule du nom de fichier
        // Supporte: 20240008, 25A00014, etc.
        const match = filename.match(/([0-9]{2}[A-Z]?[0-9]{5,}|\d{8,})/);
        return match ? match[1] : null;
    };

    const handleUpload = async () => {
        if (files.length === 0) return;

        setUploading(true);
        setUploadResults(null);

        try {
            const formData = new FormData();
            files.forEach((file, index) => {
                formData.append(`photos[${index}]`, file);
            });

            const response = await secureApiEndpoints.students.bulkUploadPhotos(formData);

            if (response.success) {
                setUploadResults(response.data);
                if (onUploadSuccess) {
                    onUploadSuccess();
                }
            } else {
                throw new Error(response.message || 'Erreur lors de l\'upload');
            }
        } catch (error) {
            console.error('Error uploading photos:', error);
            setUploadResults({
                total: files.length,
                success: 0,
                errors: files.length,
                details: files.map(file => ({
                    file: file.name,
                    status: 'error',
                    message: 'Erreur lors de l\'upload'
                }))
            });
        } finally {
            setUploading(false);
        }
    };

    return (
        <>
            <Button
                variant="outline-primary"
                className="d-flex align-items-center gap-2"
                onClick={() => setShow(true)}
                title="Upload en lot de photos d'étudiants"
            >
                <Images size={16} />
                Photos en lot
            </Button>

            <Modal show={show} onHide={handleClose} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title className="d-flex align-items-center gap-2">
                        <Images size={20} />
                        Upload en lot de photos d'étudiants
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Alert variant="info" className="mb-3">
                        <strong>Instructions :</strong>
                        <ul className="mb-0 mt-2">
                            <li>Nommez vos photos avec le matricule de l'étudiant (ex: 20240008.jpg)</li>
                            <li>Formats acceptés : JPG, PNG, GIF (max 5MB par photo)</li>
                            <li>Les photos existantes seront remplacées automatiquement</li>
                        </ul>
                    </Alert>

                    {!uploadResults && (
                        <div>
                            <div
                                className={`border border-dashed rounded p-4 text-center mb-3 ${
                                    dragOver ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary'
                                }`}
                                onDrop={handleDrop}
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                            >
                                <Upload size={48} className="text-muted mb-3" />
                                <p className="mb-2">
                                    Glissez-déposez vos photos ici ou cliquez pour sélectionner
                                </p>
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    onChange={handleFileSelect}
                                    className="form-control"
                                />
                            </div>

                            {files.length > 0 && (
                                <div>
                                    <h6>Photos sélectionnées ({files.length})</h6>
                                    <div className="row">
                                        {files.map((file, index) => {
                                            const studentNumber = extractStudentNumber(file.name);
                                            return (
                                                <div key={index} className="col-md-6 col-lg-4 mb-2">
                                                    <div className="card">
                                                        <div className="card-body p-2">
                                                            <div className="d-flex align-items-center gap-2">
                                                                <img
                                                                    src={URL.createObjectURL(file)}
                                                                    alt={file.name}
                                                                    className="rounded"
                                                                    style={{ width: '40px', height: '40px', objectFit: 'cover' }}
                                                                />
                                                                <div className="flex-grow-1">
                                                                    <div className="small fw-bold">{file.name}</div>
                                                                    {studentNumber ? (
                                                                        <Badge bg="success">Matricule: {studentNumber}</Badge>
                                                                    ) : (
                                                                        <Badge bg="warning">Matricule non détecté</Badge>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {uploading && (
                        <div className="text-center py-4">
                            <div className="spinner-border mb-3" role="status">
                                <span className="visually-hidden">Upload en cours...</span>
                            </div>
                            <p>Upload des photos en cours...</p>
                        </div>
                    )}

                    {uploadResults && (
                        <div>
                            <Alert variant={uploadResults.errors === 0 ? 'success' : 'warning'}>
                                <strong>Résultats de l'upload :</strong>
                                <ul className="mb-0 mt-2">
                                    <li>{uploadResults.success} photo(s) uploadée(s) avec succès</li>
                                    <li>{uploadResults.errors} erreur(s)</li>
                                </ul>
                            </Alert>

                            <div className="max-height-300 overflow-auto">
                                {uploadResults.details.map((detail, index) => (
                                    <div key={index} className="d-flex align-items-center gap-2 mb-2 p-2 border rounded">
                                        {detail.status === 'success' ? (
                                            <CheckCircle className="text-success" size={16} />
                                        ) : (
                                            <XCircle className="text-danger" size={16} />
                                        )}
                                        <div className="flex-grow-1">
                                            <div className="fw-bold">{detail.file}</div>
                                            {detail.student_number && (
                                                <div className="small text-muted">
                                                    Matricule: {detail.student_number}
                                                    {detail.student_name && ` - ${detail.student_name}`}
                                                </div>
                                            )}
                                            <div className={`small ${detail.status === 'success' ? 'text-success' : 'text-danger'}`}>
                                                {detail.message}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleClose}>
                        Fermer
                    </Button>
                    {!uploadResults && files.length > 0 && (
                        <Button
                            variant="primary"
                            onClick={handleUpload}
                            disabled={uploading}
                            className="d-flex align-items-center gap-2"
                        >
                            <Upload size={16} />
                            {uploading ? 'Upload en cours...' : `Uploader ${files.length} photo(s)`}
                        </Button>
                    )}
                </Modal.Footer>
            </Modal>
        </>
    );
};

export default BulkPhotoUpload;