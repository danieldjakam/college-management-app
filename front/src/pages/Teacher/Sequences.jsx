import React, { useState, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { Alert, Card, Row, Col, Button, Spinner, Badge, ProgressBar } from 'react-bootstrap';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { Calendar, Clock, Book, GraphUp } from 'react-bootstrap-icons';

const Sequences = () => {
    const { user } = useAuth();
    const [loading, setLoading] = useState(true);
    const [sequences, setSequences] = useState([]);
    const [currentSequence, setCurrentSequence] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadSequences();
    }, []);

    const loadSequences = async () => {
        try {
            setLoading(true);
            setError(null);
            
            // Charger toutes les séquences
            const sequencesData = await secureApiEndpoints.sequences.getAll();
            
            // Charger la séquence courante
            try {
                const currentData = await secureApiEndpoints.sequences.getCurrent();
                setCurrentSequence(currentData.data);
            } catch (currentError) {
                console.warn('Aucune séquence courante trouvée:', currentError);
            }
            
            setSequences(sequencesData.data || []);
        } catch (error) {
            console.error('Erreur lors du chargement des séquences:', error);
            setError('Impossible de charger les séquences. Veuillez réessayer.');
        } finally {
            setLoading(false);
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    const getSequenceStatus = (sequence) => {
        const now = new Date();
        const startDate = new Date(sequence.start_date);
        const endDate = new Date(sequence.end_date);

        if (sequence.is_current) return { text: 'En cours', variant: 'success' };
        if (now < startDate) return { text: 'À venir', variant: 'secondary' };
        if (now > endDate) return { text: 'Terminée', variant: 'warning' };
        return { text: 'Programmée', variant: 'info' };
    };

    const calculateProgress = (sequence) => {
        const now = new Date();
        const start = new Date(sequence.start_date);
        const end = new Date(sequence.end_date);
        
        if (now < start) return 0;
        if (now > end) return 100;
        
        const total = end - start;
        const elapsed = now - start;
        return Math.round((elapsed / total) * 100);
    };

    if (loading) {
        return (
            <div className="text-center p-5">
                <Spinner animation="border" variant="primary" />
                <p className="mt-2">Chargement des séquences...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="container-fluid p-4">
                <Alert variant="danger">
                    <h6>Erreur</h6>
                    <p className="mb-0">{error}</p>
                    <Button variant="outline-danger" size="sm" className="mt-2" onClick={loadSequences}>
                        Réessayer
                    </Button>
                </Alert>
            </div>
        );
    }

    return (
        <div className="container-fluid p-4">
            {/* En-tête */}
            <Row className="mb-4">
                <Col>
                    <Card className="bg-primary text-white">
                        <Card.Body>
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 className="mb-1">
                                        <Book className="me-2" />
                                        Gestion des Séquences
                                    </h4>
                                    <p className="mb-0 opacity-75">
                                        Suivi des évaluations par séquence - Année scolaire 2025-2026
                                    </p>
                                </div>
                                <div className="text-end">
                                    {currentSequence && (
                                        <div>
                                            <small className="opacity-75">Séquence actuelle</small>
                                            <div className="fw-bold">{currentSequence.name}</div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Séquence courante en évidence */}
            {currentSequence && (
                <Row className="mb-4">
                    <Col>
                        <Alert variant="success" className="border-start border-4">
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 className="mb-1">
                                        <GraphUp className="me-2" />
                                        {currentSequence.name} - {currentSequence.trimester?.name}
                                    </h6>
                                    <p className="mb-0">
                                        <Calendar className="me-1" />
                                        {formatDate(currentSequence.start_date)} → {formatDate(currentSequence.end_date)}
                                    </p>
                                </div>
                                <div>
                                    <ProgressBar 
                                        now={calculateProgress(currentSequence)} 
                                        style={{ width: '200px' }}
                                        label={`${calculateProgress(currentSequence)}%`}
                                    />
                                </div>
                            </div>
                        </Alert>
                    </Col>
                </Row>
            )}

            {/* Grille des séquences */}
            <Row>
                {sequences.map((sequence) => {
                    const status = getSequenceStatus(sequence);
                    const progress = calculateProgress(sequence);
                    
                    return (
                        <Col md={6} lg={4} key={sequence.id} className="mb-4">
                            <Card className={sequence.is_current ? 'border-success shadow-sm' : ''}>
                                <Card.Body>
                                    <div className="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 className="card-title mb-1">
                                                {sequence.name}
                                                {sequence.is_current && (
                                                    <Badge bg="success" className="ms-2">Actuelle</Badge>
                                                )}
                                            </h5>
                                            <p className="text-muted small mb-0">
                                                {sequence.trimester?.name}
                                            </p>
                                        </div>
                                        <Badge bg={status.variant}>{status.text}</Badge>
                                    </div>

                                    <div className="mb-3">
                                        <small className="text-muted d-flex align-items-center mb-1">
                                            <Calendar size={14} className="me-1" />
                                            {formatDate(sequence.start_date)} → {formatDate(sequence.end_date)}
                                        </small>
                                        
                                        {sequence.is_current && (
                                            <div>
                                                <small className="text-muted">Progression</small>
                                                <ProgressBar now={progress} size="sm" />
                                            </div>
                                        )}
                                    </div>

                                    <div className="d-flex justify-content-between align-items-center">
                                        <small className="text-muted">
                                            Séquence #{sequence.number}
                                        </small>
                                        {(sequence.is_current || progress > 0) && (
                                            <Button size="sm" variant="outline-primary">
                                                <Clock size={14} className="me-1" />
                                                Détails
                                            </Button>
                                        )}
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>
                    );
                })}
            </Row>

            {/* Informations */}
            <Alert variant="info" className="mt-4">
                <h6><Book className="me-2" />Système d'évaluation camerounais</h6>
                <Row>
                    <Col md={6}>
                        <ul className="mb-0">
                            <li>6 séquences par année scolaire</li>
                            <li>2 séquences par trimestre</li>
                            <li>Évaluations continues par séquence</li>
                        </ul>
                    </Col>
                    <Col md={6}>
                        <p className="mb-0">
                            <strong>Total séquences configurées :</strong> {sequences.length}<br/>
                            <strong>Utilisateur connecté :</strong> {user?.name}
                        </p>
                    </Col>
                </Row>
            </Alert>
        </div>
    );
};

export default Sequences;