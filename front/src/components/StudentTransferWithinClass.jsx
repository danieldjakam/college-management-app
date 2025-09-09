import React, { useState, useEffect } from 'react';
import { Modal, Button, Form, Alert, Spinner, Badge, Card, ListGroup } from 'react-bootstrap';
import { ArrowRightCircle, PersonFillExclamation, PeopleFill } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../utils/apiMigration';
import Swal from 'sweetalert2';

const StudentTransferWithinClass = ({ student, show, onHide, onTransferSuccess }) => {
    const [availableSeries, setAvailableSeries] = useState([]);
    const [selectedSeriesId, setSelectedSeriesId] = useState('');
    const [loading, setLoading] = useState(false);
    const [loadingSeries, setLoadingSeries] = useState(true);
    const [error, setError] = useState('');
    const [currentClassInfo, setCurrentClassInfo] = useState(null);

    useEffect(() => {
        if (show && student) {
            loadSeriesInSameClass();
            setSelectedSeriesId('');
            setError('');
        }
    }, [show, student]);

    const loadSeriesInSameClass = async () => {
        if (!student?.class_series_id) {
            setError('Élève sans série définie');
            setLoadingSeries(false);
            return;
        }

        try {
            setLoadingSeries(true);
            
            // D'abord obtenir les informations de la série actuelle pour connaître la classe
            const response = await secureApiEndpoints.schoolClasses.getAll();
            
            if (response.success) {
                // Trouver la classe qui contient la série actuelle de l'élève
                let currentClass = null;
                let currentSeries = null;
                
                for (const schoolClass of response.data) {
                    if (schoolClass.series) {
                        const foundSeries = schoolClass.series.find(s => s.id === student.class_series_id);
                        if (foundSeries) {
                            currentClass = schoolClass;
                            currentSeries = foundSeries;
                            break;
                        }
                    }
                }
                
                if (currentClass && currentSeries) {
                    setCurrentClassInfo({
                        className: currentClass.name,
                        currentSeriesName: currentSeries.name,
                        currentSeriesId: currentSeries.id
                    });
                    
                    // Filtrer pour avoir toutes les autres séries de la même classe
                    const otherSeries = currentClass.series.filter(s => 
                        s.id !== student.class_series_id && s.is_active
                    );
                    
                    // Enrichir avec le nombre d'élèves actuels
                    const enrichedSeries = await Promise.all(
                        otherSeries.map(async (series) => {
                            try {
                                // Compter les élèves dans cette série (approximation)
                                return {
                                    ...series,
                                    studentCount: 0 // Sera calculé si nécessaire
                                };
                            } catch (error) {
                                return { ...series, studentCount: 0 };
                            }
                        })
                    );
                    
                    setAvailableSeries(enrichedSeries);
                } else {
                    setError('Impossible de trouver la classe de l\'élève');
                }
            } else {
                setError('Erreur lors du chargement des classes');
            }
        } catch (error) {
            console.error('Error loading series in same class:', error);
            setError('Erreur lors du chargement des séries de la classe');
        } finally {
            setLoadingSeries(false);
        }
    };

    const getSelectedSeriesInfo = () => {
        if (!selectedSeriesId) return null;
        return availableSeries.find(series => series.id === parseInt(selectedSeriesId));
    };

    const handleTransfer = async () => {
        if (!selectedSeriesId) {
            setError('Veuillez sélectionner une série de destination');
            return;
        }

        const selectedSeries = getSelectedSeriesInfo();
        if (!selectedSeries) {
            setError('Série de destination invalide');
            return;
        }

        // Vérification de capacité
        if (selectedSeries.capacity && selectedSeries.studentCount >= selectedSeries.capacity) {
            setError(`La série ${selectedSeries.name} a atteint sa capacité maximale (${selectedSeries.capacity} élèves)`);
            return;
        }

        // Confirmation
        const result = await Swal.fire({
            title: 'Confirmer le transfert de série',
            html: `
                <div class="text-start">
                    <p><strong>Élève :</strong> ${student.first_name} ${student.last_name}</p>
                    <p><strong>Classe :</strong> ${currentClassInfo.className}</p>
                    <hr>
                    <p><strong>Série actuelle :</strong> ${currentClassInfo.currentSeriesName}</p>
                    <p><strong>Nouvelle série :</strong> ${selectedSeries.name}</p>
                    <p><strong>Capacité série :</strong> ${selectedSeries.capacity || 'Non définie'}</p>
                    <hr>
                    <p class="text-info">
                        <small>ℹ️ L'élève reste dans la même classe mais change de série.</small>
                    </p>
                    <p class="text-warning">
                        <small>⚠️ Cette action est irréversible.</small>
                    </p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Transférer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        });

        if (!result.isConfirmed) return;

        try {
            setLoading(true);
            setError('');

            // Utiliser le nouvel endpoint de transfert au sein de la classe
            const response = await secureApiEndpoints.students.transferWithinClass(student.id, selectedSeriesId);

            if (response.success) {
                Swal.fire({
                    title: 'Transfert réussi !',
                    html: `
                        <p><strong>${student.first_name} ${student.last_name}</strong> a été transféré(e) vers :</p>
                        <p><strong>${response.data.transfer_info.class_name}</strong> - <strong>${response.data.transfer_info.to_series}</strong></p>
                        <p><small>Capacité utilisée : ${response.data.transfer_info.capacity_used}/${response.data.transfer_info.capacity_total || '∞'}</small></p>
                    `,
                    icon: 'success',
                    timer: 4000,
                    showConfirmButton: false
                });

                if (onTransferSuccess) {
                    onTransferSuccess(response.data || student, {
                        className: response.data.transfer_info.class_name,
                        seriesName: response.data.transfer_info.to_series,
                        fromSeries: response.data.transfer_info.from_series
                    });
                }
                
                onHide();
            } else {
                setError(response.message || 'Erreur lors du transfert');
            }
        } catch (error) {
            console.error('Error transferring student within class:', error);
            setError('Erreur lors du transfert de série');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal show={show} onHide={onHide} size="lg" centered>
            <Modal.Header closeButton>
                <Modal.Title>
                    <ArrowRightCircle className="me-2 text-primary" />
                    Transfert de série
                </Modal.Title>
            </Modal.Header>
            
            <Modal.Body>
                {error && (
                    <Alert variant="danger" className="mb-3">
                        <PersonFillExclamation className="me-2" />
                        {error}
                    </Alert>
                )}

                {student && (
                    <Card className="mb-3">
                        <Card.Header className="bg-light">
                            <strong>Élève à transférer</strong>
                        </Card.Header>
                        <Card.Body>
                            <p className="mb-1"><strong>Nom :</strong> {student.first_name} {student.last_name}</p>
                            {currentClassInfo && (
                                <>
                                    <p className="mb-1"><strong>Classe actuelle :</strong> {currentClassInfo.className}</p>
                                    <p className="mb-0">
                                        <strong>Série actuelle :</strong> 
                                        <Badge bg="primary" className="ms-2">{currentClassInfo.currentSeriesName}</Badge>
                                    </p>
                                </>
                            )}
                        </Card.Body>
                    </Card>
                )}

                {loadingSeries ? (
                    <div className="text-center my-4">
                        <Spinner animation="border" />
                        <p className="mt-2">Chargement des séries disponibles...</p>
                    </div>
                ) : (
                    <>
                        {availableSeries.length === 0 ? (
                            <Alert variant="warning">
                                <PersonFillExclamation className="me-2" />
                                Aucune autre série disponible dans cette classe pour le transfert.
                            </Alert>
                        ) : (
                            <>
                                <Form.Group className="mb-3">
                                    <Form.Label>
                                        <strong>Sélectionner la série de destination</strong>
                                    </Form.Label>
                                    <Form.Select
                                        value={selectedSeriesId}
                                        onChange={(e) => setSelectedSeriesId(e.target.value)}
                                        disabled={loading}
                                    >
                                        <option value="">-- Choisir une série --</option>
                                        {availableSeries.map((series) => (
                                            <option key={series.id} value={series.id}>
                                                {series.name} (Capacité: {series.capacity || 'Non définie'})
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>

                                {availableSeries.length > 0 && (
                                    <Card>
                                        <Card.Header>
                                            <PeopleFill className="me-2" />
                                            Séries disponibles dans la classe {currentClassInfo?.className}
                                        </Card.Header>
                                        <ListGroup variant="flush">
                                            {availableSeries.map((series) => (
                                                <ListGroup.Item 
                                                    key={series.id} 
                                                    className={selectedSeriesId == series.id ? 'bg-light border-primary' : ''}
                                                >
                                                    <div className="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>{series.name}</strong>
                                                            <br />
                                                            <small className="text-muted">
                                                                Capacité: {series.capacity || 'Non définie'}
                                                            </small>
                                                        </div>
                                                        <div>
                                                            <Badge 
                                                                bg={series.is_active ? 'success' : 'secondary'}
                                                            >
                                                                {series.is_active ? 'Active' : 'Inactive'}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </ListGroup.Item>
                                            ))}
                                        </ListGroup>
                                    </Card>
                                )}
                            </>
                        )}
                    </>
                )}
            </Modal.Body>
            
            <Modal.Footer>
                <Button variant="secondary" onClick={onHide} disabled={loading}>
                    Annuler
                </Button>
                <Button 
                    variant="primary" 
                    onClick={handleTransfer} 
                    disabled={!selectedSeriesId || loading || availableSeries.length === 0}
                >
                    {loading ? (
                        <>
                            <Spinner animation="border" size="sm" className="me-2" />
                            Transfert en cours...
                        </>
                    ) : (
                        <>
                            <ArrowRightCircle className="me-2" />
                            Transférer vers la série
                        </>
                    )}
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default StudentTransferWithinClass;