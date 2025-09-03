import React, { useState, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { Alert, Card, Row, Col, ProgressBar } from 'react-bootstrap';
import { LoadingSpinner } from '../../components/UI';

const Trimesters = () => {
    const { user } = useAuth();
    const [loading, setLoading] = useState(true);
    const [trimesters, setTrimesters] = useState([]);

    useEffect(() => {
        // Simuler le chargement des trimestres
        setTimeout(() => {
            setTrimesters([
                { 
                    id: 1, 
                    name: "Premier Trimestre", 
                    period: "Septembre - Décembre", 
                    status: "En cours",
                    progress: 65,
                    sequences: ["Séquence 1", "Séquence 2"]
                },
                { 
                    id: 2, 
                    name: "Deuxième Trimestre", 
                    period: "Janvier - Mars", 
                    status: "À venir",
                    progress: 0,
                    sequences: ["Séquence 3", "Séquence 4"]
                },
                { 
                    id: 3, 
                    name: "Troisième Trimestre", 
                    period: "Avril - Juin", 
                    status: "À venir",
                    progress: 0,
                    sequences: ["Séquence 5", "Séquence 6"]
                },
            ]);
            setLoading(false);
        }, 1000);
    }, []);

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <LoadingSpinner text="Chargement des trimestres..." size="lg" />
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
                                    <h4 className="mb-1">Gestion des Trimestres</h4>
                                    <p className="text-muted mb-0">
                                        Suivi des évaluations par trimestre pour l'année scolaire 2025-2026
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
                {trimesters.map((trimester) => (
                    <Col lg={4} key={trimester.id} className="mb-4">
                        <Card className={trimester.status === 'En cours' ? 'border-success' : ''}>
                            <Card.Body>
                                <div className="mb-3">
                                    <h5 className="card-title d-flex justify-content-between align-items-center">
                                        {trimester.name}
                                        <span className={`badge ${trimester.status === 'En cours' ? 'bg-success' : 'bg-secondary'}`}>
                                            {trimester.status}
                                        </span>
                                    </h5>
                                    <p className="text-muted small mb-3">{trimester.period}</p>
                                </div>

                                <div className="mb-3">
                                    <div className="d-flex justify-content-between align-items-center mb-2">
                                        <span className="small">Progression</span>
                                        <span className="small">{trimester.progress}%</span>
                                    </div>
                                    <ProgressBar 
                                        now={trimester.progress} 
                                        variant={trimester.status === 'En cours' ? 'success' : 'secondary'}
                                    />
                                </div>

                                <div className="mb-3">
                                    <h6 className="small text-muted mb-2">Séquences incluses:</h6>
                                    <div>
                                        {trimester.sequences.map((seq, index) => (
                                            <span key={index} className="badge bg-light text-dark me-1 mb-1">
                                                {seq}
                                            </span>
                                        ))}
                                    </div>
                                </div>

                                <div className="text-end">
                                    {trimester.status === 'En cours' ? (
                                        <button className="btn btn-sm btn-success">
                                            Gérer les notes
                                        </button>
                                    ) : (
                                        <button className="btn btn-sm btn-outline-secondary" disabled>
                                            Non disponible
                                        </button>
                                    )}
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                ))}
            </Row>

            <Alert variant="success" className="mt-4">
                <h6>Gestion des Trimestres</h6>
                <p className="mb-0">
                    Chaque trimestre regroupe 2 séquences. Vous pouvez consulter les moyennes 
                    trimestrielles de vos élèves et générer des bulletins de notes pour 
                    chacune de vos matières assignées.
                </p>
            </Alert>
        </div>
    );
};

export default Trimesters;