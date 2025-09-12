import React, { useState } from 'react';
import { Container, Row, Col, Card, Button, Alert } from 'react-bootstrap';
import { FileEarmarkPdf, People, Printer } from 'react-bootstrap-icons';
import BulkStaffCardGenerator from '../../components/BulkStaffCardGenerator';

const BulkStaffCards = () => {
    const [showGenerator, setShowGenerator] = useState(false);

    return (
        <Container className="py-4">
            <Row className="justify-content-center">
                <Col md={8}>
                    <Card className="shadow-lg border-0">
                        <Card.Header className="bg-primary text-white">
                            <div className="d-flex align-items-center">
                                <FileEarmarkPdf size={24} className="me-2" />
                                <h4 className="mb-0">Génération Badges Personnel</h4>
                            </div>
                        </Card.Header>
                        
                        <Card.Body className="p-4">
                            <div className="text-center mb-4">
                                <People size={64} className="text-primary mb-3" />
                                <h5 className="text-dark">Génération en masse des badges du personnel</h5>
                                <p className="text-muted">
                                    Générez automatiquement tous les badges du personnel de l'établissement 
                                    au format PDF prêt à imprimer.
                                </p>
                            </div>

                            <Alert variant="info" className="mb-4">
                                <strong>Cette fonctionnalité va :</strong>
                                <ul className="mb-0 mt-2">
                                    <li>Récupérer tous les utilisateurs système (admins, enseignants permanents, comptables, etc.)</li>
                                    <li>Récupérer tous les enseignants vacataires</li>
                                    <li>Générer un badge personnalisé pour chaque membre du personnel</li>
                                    <li>Créer un PDF optimisé pour impression A4 (8 cartes par page : 2×4)</li>
                                    <li>Inclure les QR codes pour le système de présence</li>
                                </ul>
                            </Alert>

                            <div className="d-grid">
                                <Button 
                                    variant="primary" 
                                    size="lg" 
                                    onClick={() => setShowGenerator(true)}
                                    className="py-3"
                                >
                                    <Printer className="me-2" />
                                    Générer les badges du personnel
                                </Button>
                            </div>

                            <div className="text-center mt-3">
                                <small className="text-muted">
                                    Format carte de crédit (85.6 × 54 mm) - 8 badges par page A4 (2×4) - Compatible avec imprimantes standard
                                </small>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Modal de génération */}
            <BulkStaffCardGenerator
                show={showGenerator}
                onHide={() => setShowGenerator(false)}
            />
        </Container>
    );
};

export default BulkStaffCards;