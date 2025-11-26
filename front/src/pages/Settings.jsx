import React, { useState } from 'react';
import { Container, Row, Col, Card, Nav } from 'react-bootstrap';
import { GearFill, Building, Award, Calendar, FileEarmarkText, GeoAlt, Grid3x3GapFill } from 'react-bootstrap-icons';
import SchoolSettings from './Settings/SchoolSettings';
import ClassScholarships from './Settings/ClassScholarships';
import GeolocationZoneSettingsV2 from './Settings/GeolocationZoneSettingsV2';
import GeolocationQuickAccess from '../components/GeolocationQuickAccess';
import SubjectGroupsManagement from './Admin/SubjectGroupsManagement';

function Settings() {
    const [activeTab, setActiveTab] = useState('school-settings');

    const tabs = [
        {
            key: 'school-settings',
            title: 'Paramètres Généraux',
            icon: <Building size={16} className="me-2" />,
            component: <SchoolSettings />
        },
        {
            key: 'scholarships',
            title: 'Bourses par Classe',
            icon: <Award size={16} className="me-2" />,
            component: <ClassScholarships />
        },
        {
            key: 'subject-groups',
            title: 'Groupes de Matières',
            icon: <Grid3x3GapFill size={16} className="me-2" />,
            component: <SubjectGroupsManagement />
        },
        {
            key: 'geolocation-zones',
            title: 'Zones Géolocalisées',
            icon: <GeoAlt size={16} className="me-2" />,
            component: <GeolocationZoneSettingsV2 />
        }
    ];

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex align-items-center mb-3">
                        <GearFill size={24} className="me-3 text-primary" />
                        <div>
                            <h2 className="mb-0">Paramètres de l'Application</h2>
                            <p className="text-muted mb-0">Configuration et gestion des paramètres du système</p>
                        </div>
                    </div>
                </Col>
            </Row>

            {/* Navigation Tabs */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Body className="p-0">
                            <Nav variant="tabs" className="border-0">
                                {tabs.map((tab) => (
                                    <Nav.Item key={tab.key}>
                                        <Nav.Link
                                            active={activeTab === tab.key}
                                            onClick={() => setActiveTab(tab.key)}
                                            className="d-flex align-items-center px-4 py-3"
                                            style={{ cursor: 'pointer' }}
                                        >
                                            {tab.icon}
                                            {tab.title}
                                        </Nav.Link>
                                    </Nav.Item>
                                ))}
                            </Nav>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Accès rapide géolocalisation */}
            <Row className="mb-4">
                <Col lg={4}>
                    <GeolocationQuickAccess />
                </Col>
            </Row>

            {/* Tab Content */}
            <Row>
                <Col>
                    {tabs.find(tab => tab.key === activeTab)?.component}
                </Col>
            </Row>
        </Container>
    );
}

export default Settings