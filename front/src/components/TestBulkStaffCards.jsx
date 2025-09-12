import React, { useState } from 'react';
import { Container, Row, Col, Button, Card } from 'react-bootstrap';
import BulkStaffCardGenerator from './BulkStaffCardGenerator';

// Composant de test pour la génération en masse des badges personnel
const TestBulkStaffCards = () => {
    const [showModal, setShowModal] = useState(false);

    return (
        <Container className="py-4">
            <Row className="justify-content-center">
                <Col md={6}>
                    <Card className="text-center">
                        <Card.Header>
                            <h4>Test - Génération Badges Personnel</h4>
                        </Card.Header>
                        <Card.Body>
                            <p>
                                Testez la génération en masse des badges pour tous les membres du personnel.
                                Cette fonctionnalité récupère automatiquement :
                            </p>
                            <ul className="text-start">
                                <li>Tous les utilisateurs système (admins, enseignants, comptables)</li>
                                <li>Tous les enseignants vacataires</li>
                            </ul>
                            <Button 
                                variant="primary" 
                                size="lg"
                                onClick={() => setShowModal(true)}
                            >
                                🎫 Tester la génération PDF
                            </Button>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Modal de génération */}
            <BulkStaffCardGenerator
                show={showModal}
                onHide={() => setShowModal(false)}
            />
        </Container>
    );
};

export default TestBulkStaffCards;