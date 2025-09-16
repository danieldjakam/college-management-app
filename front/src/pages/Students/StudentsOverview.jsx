import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Badge, Button, Spinner, Alert } from 'react-bootstrap';
import { PeopleFill, HouseHeartFill, ArrowRight } from 'react-bootstrap-icons';
import { useNavigate } from 'react-router-dom';
import { secureApiEndpoints } from '../../utils/apiMigration';

const StudentsOverview = () => {
    const [classes, setClasses] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const navigate = useNavigate();

    useEffect(() => {
        loadClasses();
    }, []);

    const loadClasses = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.schoolClasses.getAll();

            if (response.success) {
                setClasses(response.data || []);
            } else {
                setError('Erreur lors du chargement des classes');
            }
        } catch (error) {
            console.error('Error loading classes:', error);
            setError('Erreur lors du chargement des classes');
        } finally {
            setLoading(false);
        }
    };

    const handleViewSeries = (seriesId, seriesName, className) => {
        navigate(`/students/series/${seriesId}`, {
            state: {
                seriesName,
                className
            }
        });
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '300px' }}>
                <Spinner animation="border" variant="primary" />
                <span className="ms-2">Chargement des classes...</span>
            </div>
        );
    }

    if (error) {
        return (
            <Alert variant="danger" className="m-3">
                {error}
            </Alert>
        );
    }

    return (
        <div className="container-fluid p-4">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <PeopleFill className="me-2" />
                    Gestion des Élèves
                </h2>
                <Badge bg="info" className="fs-6">
                    {classes.length} classe(s) disponible(s)
                </Badge>
            </div>

            <Alert variant="info" className="mb-4">
                <strong>Gestion des élèves par série :</strong> Sélectionnez une classe puis une série pour accéder à la liste complète des élèves.
            </Alert>

            <Row>
                {classes.map((schoolClass) => (
                    <Col key={schoolClass.id} md={6} lg={4} className="mb-4">
                        <Card className="h-100 shadow-sm">
                            <Card.Header className="bg-primary text-white">
                                <h5 className="mb-0">
                                    <HouseHeartFill className="me-2" />
                                    {schoolClass.name}
                                </h5>
                            </Card.Header>
                            <Card.Body>
                                <div className="mb-3">
                                    <Badge bg="secondary" className="me-2">
                                        {schoolClass.series?.length || 0} série(s)
                                    </Badge>
                                    <Badge bg="success">
                                        {schoolClass.series?.reduce((total, series) => total + (series.students_count || 0), 0) || 0} élève(s)
                                    </Badge>
                                </div>

                                {schoolClass.series && schoolClass.series.length > 0 ? (
                                    <div>
                                        <h6 className="text-muted mb-2">Séries disponibles :</h6>
                                        {schoolClass.series.map((series) => (
                                            <div key={series.id} className="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                                <div>
                                                    <strong>{series.name}</strong>
                                                    <br />
                                                    <small className="text-muted">
                                                        {series.students_count || 0} élève(s)
                                                    </small>
                                                </div>
                                                <Button
                                                    variant="outline-primary"
                                                    size="sm"
                                                    onClick={() => handleViewSeries(series.id, series.name, schoolClass.name)}
                                                >
                                                    <ArrowRight className="me-1" />
                                                    Voir
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <Alert variant="warning" className="mb-0">
                                        Aucune série dans cette classe
                                    </Alert>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                ))}
            </Row>

            {classes.length === 0 && (
                <Alert variant="warning" className="text-center">
                    <HouseHeartFill size={48} className="mb-3 text-muted" />
                    <h5>Aucune classe trouvée</h5>
                    <p>Il n'y a actuellement aucune classe configurée dans le système.</p>
                </Alert>
            )}
        </div>
    );
};

export default StudentsOverview;