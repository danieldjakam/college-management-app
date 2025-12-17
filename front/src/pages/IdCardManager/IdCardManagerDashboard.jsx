import React, { useState, useEffect } from 'react';
import { Card, Row, Col, ProgressBar, Spinner, Alert, Table, Badge } from 'react-bootstrap';
import { PersonBadge, Image, Printer, People, Grid3x3Gap } from 'react-bootstrap-icons';
import { useNavigate } from 'react-router-dom';
import { secureApiEndpoints } from '../../utils/apiMigration';

const IdCardManagerDashboard = () => {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [dashboardData, setDashboardData] = useState(null);

    useEffect(() => {
        loadDashboardData();
    }, []);

    const loadDashboardData = async () => {
        try {
            setLoading(true);
            setError(null);

            const response = await secureApiEndpoints.request('/id-card-manager/dashboard');

            if (response.success) {
                setDashboardData(response.data);
            } else {
                throw new Error(response.message || 'Erreur lors du chargement des données');
            }
        } catch (err) {
            console.error('Erreur chargement dashboard:', err);
            setError(err.message || 'Erreur de connexion');
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="text-center p-5">
                <Spinner animation="border" variant="primary" />
                <p className="mt-3">Chargement du tableau de bord...</p>
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

    const { global_stats, classes_stats, working_year } = dashboardData;

    return (
        <div className="container-fluid p-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <Card className="bg-primary text-white">
                        <Card.Body>
                            <div className="d-flex align-items-center">
                                <PersonBadge size={50} className="me-3" />
                                <div>
                                    <h3 className="mb-1">Gestion des Cartes d'Identité</h3>
                                    <p className="mb-0">
                                        Année Scolaire: {working_year?.name || 'N/A'}
                                    </p>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Statistiques globales */}
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="h-100">
                        <Card.Body className="text-center">
                            <People size={40} className="text-primary mb-2" />
                            <h2 className="mb-1">{global_stats.total_students}</h2>
                            <p className="text-muted mb-0">Élèves Total</p>
                        </Card.Body>
                    </Card>
                </Col>

                <Col md={3}>
                    <Card className="h-100">
                        <Card.Body className="text-center">
                            <Image size={40} className="text-success mb-2" />
                            <h2 className="mb-1">{global_stats.students_with_photo}</h2>
                            <p className="text-muted mb-0">Avec Photo</p>
                        </Card.Body>
                    </Card>
                </Col>

                <Col md={3}>
                    <Card className="h-100">
                        <Card.Body className="text-center">
                            <PersonBadge size={40} className="text-warning mb-2" />
                            <h2 className="mb-1">{global_stats.students_without_photo}</h2>
                            <p className="text-muted mb-0">Sans Photo</p>
                        </Card.Body>
                    </Card>
                </Col>

                <Col md={3}>
                    <Card className="h-100">
                        <Card.Body className="text-center">
                            <Grid3x3Gap size={40} className="text-info mb-2" />
                            <h2 className="mb-1">{global_stats.total_classes}</h2>
                            <p className="text-muted mb-0">Classes</p>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Barre de progression globale */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Body>
                            <h5 className="mb-3">Progression Globale</h5>
                            <ProgressBar
                                now={global_stats.percentage_with_photo}
                                label={`${global_stats.percentage_with_photo}%`}
                                variant={
                                    global_stats.percentage_with_photo >= 80 ? 'success' :
                                    global_stats.percentage_with_photo >= 50 ? 'warning' : 'danger'
                                }
                                style={{ height: '30px', fontSize: '16px' }}
                            />
                            <p className="text-muted mt-2 mb-0">
                                {global_stats.students_with_photo} élèves sur {global_stats.total_students} ont leur photo
                            </p>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Statistiques par classe */}
            <Row>
                <Col>
                    <Card>
                        <Card.Header className="bg-light d-flex justify-content-between align-items-center">
                            <h5 className="mb-0">Statistiques par Classe</h5>
                            <Badge bg="secondary">{classes_stats.length} classes</Badge>
                        </Card.Header>
                        <Card.Body className="p-0">
                            <Table responsive hover className="mb-0">
                                <thead className="table-light">
                                    <tr>
                                        <th>Classe</th>
                                        <th className="text-center">Total Élèves</th>
                                        <th className="text-center">Avec Photo</th>
                                        <th className="text-center">Sans Photo</th>
                                        <th>Progression</th>
                                        <th className="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {classes_stats.map((classStat) => (
                                        <tr key={classStat.class_id}>
                                            <td>
                                                <strong>{classStat.class_name}</strong>
                                            </td>
                                            <td className="text-center">
                                                <Badge bg="primary">{classStat.total_students}</Badge>
                                            </td>
                                            <td className="text-center">
                                                <Badge bg="success">{classStat.students_with_photo}</Badge>
                                            </td>
                                            <td className="text-center">
                                                <Badge bg="warning">{classStat.students_without_photo}</Badge>
                                            </td>
                                            <td style={{ width: '30%' }}>
                                                <ProgressBar
                                                    now={classStat.percentage_with_photo}
                                                    label={`${classStat.percentage_with_photo}%`}
                                                    variant={
                                                        classStat.percentage_with_photo >= 80 ? 'success' :
                                                        classStat.percentage_with_photo >= 50 ? 'warning' : 'danger'
                                                    }
                                                />
                                            </td>
                                            <td className="text-center">
                                                <button
                                                    className="btn btn-sm btn-primary"
                                                    onClick={() => navigate(`/id-card-manager/classes/${classStat.class_id}/students`)}
                                                >
                                                    <PersonBadge className="me-1" />
                                                    Gérer
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </div>
    );
};

export default IdCardManagerDashboard;
