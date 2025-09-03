import React, { useState, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { Alert, Card, Row, Col } from 'react-bootstrap';
import { LoadingSpinner } from '../../components/UI';

const Sequences = () => {
    const { user } = useAuth();
    const [loading, setLoading] = useState(true);
    const [sequences, setSequences] = useState([]);

    useEffect(() => {
        // Simuler le chargement des séquences
        setTimeout(() => {
            setSequences([
                { id: 1, name: "Séquence 1", period: "Septembre - Octobre", status: "En cours" },
                { id: 2, name: "Séquence 2", period: "Novembre - Décembre", status: "À venir" },
                { id: 3, name: "Séquence 3", period: "Janvier - Février", status: "À venir" },
                { id: 4, name: "Séquence 4", period: "Mars - Avril", status: "À venir" },
                { id: 5, name: "Séquence 5", period: "Avril - Mai", status: "À venir" },
                { id: 6, name: "Séquence 6", period: "Mai - Juin", status: "À venir" },
            ]);
            setLoading(false);
        }, 1000);
    }, []);

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <LoadingSpinner text="Chargement des séquences..." size="lg" />
            </div>
        );
    }

    return (
        <div className="container-fluid p-4">
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Body>
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 className="mb-1">Gestion des Séquences</h4>
                                    <p className="text-muted mb-0">
                                        Suivi des évaluations par séquence pour l'année scolaire 2025-2026
                                    </p>
                                </div>
                                <div className="text-end">
                                    <small className="text-muted">
                                        Connecté en tant que: {user?.name}
                                    </small>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row>
                {sequences.map((sequence) => (
                    <Col md={6} lg={4} key={sequence.id} className="mb-4">
                        <Card className={sequence.status === 'En cours' ? 'border-primary' : ''}>
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 className="card-title">{sequence.name}</h5>
                                        <p className="text-muted small mb-2">{sequence.period}</p>
                                        <span className={`badge ${sequence.status === 'En cours' ? 'bg-primary' : 'bg-secondary'}`}>
                                            {sequence.status}
                                        </span>
                                    </div>
                                    <div className="text-end">
                                        {sequence.status === 'En cours' && (
                                            <button className="btn btn-sm btn-outline-primary">
                                                Voir détails
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                ))}
            </Row>

            <Alert variant="info" className="mt-4">
                <h6>Information</h6>
                <p className="mb-0">
                    Cette section permet de suivre les évaluations par séquence. 
                    Vous pouvez consulter les notes de vos élèves et gérer les évaluations 
                    pour chacune de vos matières assignées.
                </p>
            </Alert>
        </div>
    );
};

export default Sequences;