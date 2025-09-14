import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Container, Row, Col, Card, Table, Badge, Button, Alert, Spinner } from 'react-bootstrap';
import { useAuth } from '../../hooks/useAuth';
import { secureApi } from '../../utils/apiMigration';
import { ArrowLeft, Calculator, GraphUp, People } from 'react-bootstrap-icons';

const DSDetails = () => {
    const { trimesterId } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    const [dsDetails, setDsDetails] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadDSDetails();
    }, [trimesterId]);

    const loadDSDetails = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await secureApi.get(`/trimesters/${trimesterId}/ds-details`);
            setDsDetails(response.data);
        } catch (err) {
            console.error('Erreur lors du chargement des détails DS:', err);
            setError(err.message || 'Erreur lors du chargement des données');
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <Container fluid className="py-4">
                <div className="text-center py-5">
                    <Spinner animation="border" variant="primary" size="lg" />
                    <div className="mt-3">
                        <h5>Chargement des détails DS...</h5>
                        <p className="text-muted">Calcul des moyennes en cours</p>
                    </div>
                </div>
            </Container>
        );
    }

    if (error || dsDetails?.error) {
        return (
            <Container fluid className="py-4">
                <Row className="mb-4">
                    <Col>
                        <Button variant="outline-secondary" onClick={() => navigate('/trimesters')} className="mb-3">
                            <ArrowLeft className="me-2" />
                            Retour aux trimestres
                        </Button>
                    </Col>
                </Row>
                <Alert variant="warning" className="text-center py-5">
                    <h5>Aucune donnée disponible</h5>
                    <p>{error || dsDetails?.error || 'Aucun élève trouvé avec des notes dans les séquences requises.'}</p>
                    <Button variant="primary" onClick={loadDSDetails} className="mt-3">
                        Réessayer
                    </Button>
                </Alert>
            </Container>
        );
    }

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <Button variant="outline-secondary" onClick={() => navigate('/trimesters')} className="mb-3">
                                <ArrowLeft className="me-2" />
                                Retour aux trimestres
                            </Button>
                            <h2 className="mb-2">
                                📊 Détails du Calcul DS {dsDetails?.trimester?.number}
                            </h2>
                            <p className="text-muted fs-5">
                                <Calculator className="me-2" />
                                <strong>Formule:</strong> (Séquence 1 + Séquence 2) ÷ 2 pour chaque matière, puis moyenne pondérée
                            </p>
                        </div>
                        <div className="text-end">
                            <div className="fs-6 text-muted">Professeur: <strong>{user?.name}</strong></div>
                            <div className="fs-6 text-muted">
                                Séquences: <strong>{dsDetails?.sequences?.map(seq => seq.name).join(', ')}</strong>
                            </div>
                        </div>
                    </div>
                </Col>
            </Row>

            {/* Statistiques */}
            {dsDetails?.class_statistics && (
                <Row className="mb-4">
                    <Col md={3}>
                        <Card className="text-center border-primary">
                            <Card.Body>
                                <People size={32} className="text-primary mb-2" />
                                <h3 className="text-primary">{dsDetails.class_statistics.count}</h3>
                                <p className="mb-0 text-muted">Élèves</p>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center border-success">
                            <Card.Body>
                                <GraphUp size={32} className="text-success mb-2" />
                                <h3 className="text-success">{dsDetails.class_statistics.average}/20</h3>
                                <p className="mb-0 text-muted">Moyenne Classe</p>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center border-info">
                            <Card.Body>
                                <div className="fs-1 text-info mb-2">📈</div>
                                <h3 className="text-info">{dsDetails.class_statistics.max}/20</h3>
                                <p className="mb-0 text-muted">Maximum</p>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center border-warning">
                            <Card.Body>
                                <div className="fs-1 text-warning mb-2">📉</div>
                                <h3 className="text-warning">{dsDetails.class_statistics.min}/20</h3>
                                <p className="mb-0 text-muted">Minimum</p>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Tableau des élèves */}
            <Row>
                <Col>
                    <Card>
                        <Card.Header className="bg-primary text-white">
                            <h4 className="mb-0">👥 Calcul DS par Élève ({dsDetails?.students_ds?.length || 0} élèves)</h4>
                        </Card.Header>
                        <Card.Body className="p-0">
                            {dsDetails?.students_ds && dsDetails.students_ds.length > 0 ? (
                                <div className="table-responsive" style={{ maxHeight: '70vh', overflowY: 'auto' }}>
                                    <Table striped hover className="mb-0">
                                        <thead className="table-dark sticky-top">
                                            <tr>
                                                <th width="5%" className="text-center">#</th>
                                                <th width="25%">Élève</th>
                                                <th width="15%" className="text-center">DS Général</th>
                                                <th width="55%">Détail par Matière (Séq1 + Séq2) ÷ 2</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {dsDetails.students_ds.map((student, index) => (
                                                <tr key={student.student_id} className="align-middle">
                                                    <td className="text-center fw-bold fs-6">
                                                        {index + 1}
                                                    </td>
                                                    <td>
                                                        <div className="fw-bold fs-6 text-primary">{student.student_name}</div>
                                                        <small className="text-muted">N° {student.student_number}</small>
                                                    </td>
                                                    <td className="text-center">
                                                        <Badge 
                                                            bg={student.ds_average >= 10 ? 'success' : 'danger'} 
                                                            className="fs-5 px-3 py-2"
                                                        >
                                                            {student.ds_average}/20
                                                        </Badge>
                                                    </td>
                                                    <td>
                                                        <div className="row g-2">
                                                            {student.subjects.map((subj, idx) => (
                                                                <div key={idx} className="col-lg-6 col-12 mb-2">
                                                                    <div className="card border-0 bg-light h-100">
                                                                        <div className="card-body p-3">
                                                                            <div className="d-flex justify-content-between align-items-center">
                                                                                <div className="flex-grow-1">
                                                                                    <div className="fw-bold text-dark">{subj.subject}</div>
                                                                                    <small className="text-muted">Coefficient: {subj.coefficient}</small>
                                                                                </div>
                                                                                <div className="text-end">
                                                                                    <div className="d-flex align-items-center gap-1 flex-wrap justify-content-end">
                                                                                        {subj.sequence_averages && subj.sequence_averages.length >= 2 ? (
                                                                                            <>
                                                                                                <span className="badge bg-info fs-6">{subj.sequence_averages[0]?.toFixed(1)}</span>
                                                                                                <span className="text-muted">+</span>
                                                                                                <span className="badge bg-info fs-6">{subj.sequence_averages[1]?.toFixed(1)}</span>
                                                                                                <span className="text-muted">÷2=</span>
                                                                                            </>
                                                                                        ) : (
                                                                                            <span className="text-muted me-2">
                                                                                                {subj.sequence_averages?.join(' + ') || 'N/A'} =
                                                                                            </span>
                                                                                        )}
                                                                                        <Badge 
                                                                                            bg={subj.ds_average >= 10 ? 'success' : 'danger'} 
                                                                                            className="fs-6"
                                                                                        >
                                                                                            {subj.ds_average}/20
                                                                                        </Badge>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </Table>
                                </div>
                            ) : (
                                <div className="text-center py-5">
                                    <h5 className="text-muted">Aucun élève trouvé</h5>
                                    <p className="text-muted">
                                        Aucun élève n'a de notes dans les séquences requises pour ce trimestre.
                                    </p>
                                </div>
                            )}
                        </Card.Body>
                        <Card.Footer className="bg-light">
                            <div className="text-center text-muted">
                                <small>
                                    💡 <strong>Formule DS :</strong> Pour chaque matière = (Note Séquence 1 + Note Séquence 2) ÷ 2, 
                                    puis moyenne pondérée avec les coefficients des matières
                                </small>
                            </div>
                        </Card.Footer>
                    </Card>
                </Col>
            </Row>

            {/* Style CSS */}
            <style jsx>{`
                .sticky-top {
                    position: sticky !important;
                    top: 0 !important;
                    z-index: 10 !important;
                }
                
                .card:hover {
                    transition: all 0.2s ease;
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .badge {
                    font-size: 0.85em !important;
                }
                
                .fs-5.badge {
                    font-size: 1.1em !important;
                }
                
                .fs-6.badge {
                    font-size: 0.9em !important;
                }
                
                .table th,
                .table td {
                    padding: 1rem !important;
                    vertical-align: middle !important;
                }
                
                @media (max-width: 768px) {
                    .col-lg-6 {
                        flex: 0 0 100% !important;
                    }
                    
                    .d-flex.gap-1 {
                        flex-direction: column !important;
                        align-items: center !important;
                    }
                }
            `}</style>
        </Container>
    );
};

export default DSDetails;