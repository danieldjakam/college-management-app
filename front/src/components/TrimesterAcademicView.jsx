import React, { useState, useEffect } from 'react';
import { 
    Card, Row, Col, Badge, ProgressBar, Button, Alert, Accordion,
    Table, Tab, Tabs, Spinner
} from 'react-bootstrap';
import { 
    Calendar, Clock, Play, Pause, CheckCircle, XCircle, 
    Book, FileText, Bullseye, Award, Eye, Settings, Circle
} from 'react-bootstrap-icons';
import './TrimesterAcademicView.css';

const TrimesterAcademicView = ({ 
    trimesters = [], 
    sequences = [], 
    evaluations = [], 
    loading = false,
    onActivateSequence,
    onCompleteSequence,
    onActivateTrimester,
    currentUser 
}) => {
    const [activeTab, setActiveTab] = useState('overview');

    // Grouper les séquences NORMALES par trimestre (exclure les compositions)
    const getSequencesForTrimester = (trimesterId) => {
        return sequences.filter(seq => 
            seq.trimester_id === trimesterId && 
            !seq.is_composition // Exclure les compositions
        );
    };

    // Obtenir les compositions pour un trimestre (maintenant des séquences)
    const getCompositionsForTrimester = (trimesterId) => {
        return sequences.filter(seq => 
            seq.trimester_id === trimesterId && 
            seq.is_composition === true
        );
    };

    // Calculer le DS pour un trimestre
    const getDSForTrimester = (trimesterNumber) => {
        let dsSequences = [];
        
        if (trimesterNumber === 1) {
            dsSequences = sequences.filter(seq => [1, 2].includes(seq.number));
        } else if (trimesterNumber === 2) {
            dsSequences = sequences.filter(seq => [3, 4].includes(seq.number));
        }
        
        const completedCount = dsSequences.filter(seq => seq.is_completed).length;
        const total = dsSequences.length;
        
        return {
            sequences: dsSequences,
            completedCount,
            total,
            isComplete: completedCount === total && total > 0,
            progress: total > 0 ? (completedCount / total) * 100 : 0
        };
    };

    // Obtenir le statut d'un élément académique
    const getItemStatus = (item, type = 'sequence') => {
        if (type === 'sequence') {
            if (item.is_current) return { status: 'current', label: 'En cours', variant: 'success', icon: Play };
            if (item.is_completed) return { status: 'completed', label: 'Terminée', variant: 'info', icon: CheckCircle };
            return { status: 'scheduled', label: 'Programmée', variant: 'secondary', icon: Clock };
        }
        
        if (type === 'composition') {
            if (item.is_active) return { status: 'active', label: 'Active', variant: 'success', icon: FileText };
            if (item.is_completed) return { status: 'completed', label: 'Terminée', variant: 'info', icon: CheckCircle };
            return { status: 'scheduled', label: 'Programmée', variant: 'secondary', icon: Clock };
        }
        
        return { status: 'unknown', label: 'Inconnu', variant: 'light', icon: XCircle };
    };

    // Obtenir le statut du DS
    const getDSStatus = (dsInfo) => {
        if (dsInfo.isComplete) {
            return { status: 'complete', label: 'DS Calculé', variant: 'success', icon: Award };
        } else if (dsInfo.completedCount > 0) {
            return { status: 'partial', label: 'En cours', variant: 'warning', icon: Clock };
        }
        return { status: 'pending', label: 'En attente', variant: 'secondary', icon: Circle };
    };

    if (loading) {
        return (
            <div className="text-center py-5">
                <Spinner animation="border" variant="primary" />
                <p className="mt-2">Chargement de la structure académique...</p>
            </div>
        );
    }

    return (
        <div className="trimester-academic-view">
            <Tabs 
                activeKey={activeTab} 
                onSelect={(k) => setActiveTab(k)}
                className="mb-4"
            >
                {/* Vue d'ensemble */}
                <Tab eventKey="overview" title={
                    <span><Book className="me-1" />Vue d'ensemble</span>
                }>
                    <Row>
                        {trimesters.map((trimester) => {
                            const trimesterSequences = getSequencesForTrimester(trimester.id);
                            const compositions = getCompositionsForTrimester(trimester.id);
                            const dsInfo = getDSForTrimester(trimester.number);
                            const dsStatus = getDSStatus(dsInfo);
                            
                            return (
                                <Col lg={4} key={trimester.id} className="mb-4">
                                    <Card className={`h-100 ${trimester.is_current ? 'border-success shadow' : 'border-light'}`}>
                                        <Card.Header className={trimester.is_current ? 'bg-success text-white' : 'bg-light'}>
                                            <div className="d-flex justify-content-between align-items-center">
                                                <h5 className="mb-0">
                                                    {trimester.name}
                                                    {trimester.is_current && (
                                                        <Badge bg="light" text="success" className="ms-2">Actuel</Badge>
                                                    )}
                                                </h5>
                                                <small>T{trimester.number}</small>
                                            </div>
                                        </Card.Header>
                                        <Card.Body className="pb-2">
                                            {/* Structure académique */}
                                            <div className="academic-structure mb-3">
                                                {/* Séquences */}
                                                <div className="mb-3">
                                                    <h6 className="text-muted mb-2">
                                                        <Calendar className="me-1" size={14} />
                                                        Séquences ({trimesterSequences.length})
                                                    </h6>
                                                    {trimesterSequences.length > 0 ? (
                                                        <div className="sequence-list">
                                                            {trimesterSequences.map((sequence) => {
                                                                const seqStatus = getItemStatus(sequence, 'sequence');
                                                                return (
                                                                    <div key={sequence.id} className="d-flex justify-content-between align-items-center mb-1">
                                                                        <small className="text-truncate">
                                                                            <seqStatus.icon className="me-1" size={12} />
                                                                            {sequence.name}
                                                                        </small>
                                                                        <Badge bg={seqStatus.variant} className="ms-1">
                                                                            {seqStatus.label}
                                                                        </Badge>
                                                                    </div>
                                                                );
                                                            })}
                                                        </div>
                                                    ) : (
                                                        <small className="text-muted">Aucune séquence</small>
                                                    )}
                                                </div>

                                                {/* DS Info */}
                                                {trimester.number !== 3 && (
                                                    <div className="mb-3">
                                                        <h6 className="text-muted mb-2">
                                                            <Circle className="me-1" size={14} />
                                                            DS{trimester.number}
                                                        </h6>
                                                        <div className="d-flex justify-content-between align-items-center">
                                                            <small>
                                                                <dsStatus.icon className="me-1" size={12} />
                                                                {dsInfo.completedCount}/{dsInfo.total} séquences
                                                            </small>
                                                            <Badge bg={dsStatus.variant}>
                                                                {dsStatus.label}
                                                            </Badge>
                                                        </div>
                                                        {dsInfo.total > 0 && (
                                                            <ProgressBar 
                                                                now={dsInfo.progress} 
                                                                size="sm" 
                                                                className="mt-1"
                                                                variant={dsStatus.variant}
                                                            />
                                                        )}
                                                    </div>
                                                )}

                                                {/* Compositions */}
                                                <div className="mb-3">
                                                    <h6 className="text-muted mb-2">
                                                        <FileText className="me-1" size={14} />
                                                        Composition {trimester.number}
                                                    </h6>
                                                    {compositions.length > 0 ? (
                                                        compositions.map((composition) => {
                                                            const compStatus = getItemStatus(composition, 'sequence'); // Traiter comme une séquence maintenant
                                                            return (
                                                                <div key={composition.id} className="d-flex justify-content-between align-items-center">
                                                                    <small className="text-truncate">
                                                                        <compStatus.icon className="me-1" size={12} />
                                                                        {composition.name}
                                                                    </small>
                                                                    <Badge bg={compStatus.variant}>
                                                                        {compStatus.label}
                                                                    </Badge>
                                                                </div>
                                                            );
                                                        })
                                                    ) : (
                                                        <small className="text-muted">Pas de composition</small>
                                                    )}
                                                </div>
                                            </div>
                                        </Card.Body>
                                        <Card.Footer className="bg-transparent">
                                            <div className="d-flex justify-content-between align-items-center">
                                                <small className="text-muted">
                                                    {new Date(trimester.start_date).toLocaleDateString()} -
                                                    {new Date(trimester.end_date).toLocaleDateString()}
                                                </small>
                                                {!trimester.is_current && onActivateTrimester && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline-success"
                                                        onClick={() => onActivateTrimester(trimester.id)}
                                                    >
                                                        <Play className="me-1" size={12} />
                                                        Activer
                                                    </Button>
                                                )}
                                                {trimester.is_current && (
                                                    <Badge bg="success">
                                                        <CheckCircle className="me-1" size={12} />
                                                        Actif
                                                    </Badge>
                                                )}
                                            </div>
                                        </Card.Footer>
                                    </Card>
                                </Col>
                            );
                        })}
                    </Row>
                </Tab>

                {/* Vue détaillée par trimestre */}
                {trimesters.map((trimester) => (
                    <Tab 
                        key={`trimester-${trimester.number}`}
                        eventKey={`trimester-${trimester.number}`} 
                        title={
                            <span>
                                <Calendar className="me-1" />
                                Trimestre {trimester.number}
                                {trimester.is_current && <Badge bg="success" className="ms-1">Actuel</Badge>}
                            </span>
                        }
                    >
                        <TrimesterDetailView 
                            trimester={trimester}
                            sequences={getSequencesForTrimester(trimester.id)}
                            compositions={getCompositionsForTrimester(trimester.id)}
                            dsInfo={getDSForTrimester(trimester.number)}
                            onActivateSequence={onActivateSequence}
                            onCompleteSequence={onCompleteSequence}
                        />
                    </Tab>
                ))}
            </Tabs>
        </div>
    );
};

// Composant pour la vue détaillée d'un trimestre
const TrimesterDetailView = ({ 
    trimester, 
    sequences, 
    compositions, 
    dsInfo,
    onActivateSequence,
    onCompleteSequence 
}) => {
    // Obtenir le statut d'un élément académique (copié pour ce composant)
    const getItemStatus = (item, type = 'sequence') => {
        if (type === 'sequence') {
            if (item.is_current) return { status: 'current', label: 'En cours', variant: 'success', icon: Play };
            if (item.is_completed) return { status: 'completed', label: 'Terminée', variant: 'info', icon: CheckCircle };
            return { status: 'scheduled', label: 'Programmée', variant: 'secondary', icon: Clock };
        }
        
        if (type === 'composition') {
            if (item.is_active) return { status: 'active', label: 'Active', variant: 'success', icon: FileText };
            if (item.is_completed) return { status: 'completed', label: 'Terminée', variant: 'info', icon: CheckCircle };
            return { status: 'scheduled', label: 'Programmée', variant: 'secondary', icon: Clock };
        }
        
        return { status: 'unknown', label: 'Inconnu', variant: 'light', icon: XCircle };
    };
    
    return (
        <div className="trimester-detail">
            <Row className="mb-4">
                <Col>
                    <Alert variant="info" className="d-flex align-items-center">
                        <Book className="me-2" size={20} />
                        <div>
                            <strong>Structure Académique - {trimester.name}</strong>
                            <p className="mb-0 mt-1">
                                {trimester.number === 3 ? (
                                    "Ce trimestre ne contient que la Composition 3"
                                ) : (
                                    `DS${trimester.number} = (Séquence ${trimester.number === 1 ? '1 + 2' : '3 + 4'}) / 2, puis Trimestre = (DS + Composition) / 2`
                                )}
                            </p>
                        </div>
                    </Alert>
                </Col>
            </Row>

            <Row>
                {/* Séquences */}
                <Col lg={6} className="mb-4">
                    <Card>
                        <Card.Header className="bg-primary text-white">
                            <h6 className="mb-0">
                                <Calendar className="me-2" />
                                Séquences ({sequences.length})
                            </h6>
                        </Card.Header>
                        <Card.Body>
                            {sequences.length > 0 ? (
                                <Table hover size="sm">
                                    <thead>
                                        <tr>
                                            <th>Séquence</th>
                                            <th>Période</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {sequences.map((sequence) => (
                                            <tr key={sequence.id}>
                                                <td>
                                                    <strong>{sequence.name}</strong>
                                                    <br />
                                                    <small className="text-muted">#{sequence.number}</small>
                                                </td>
                                                <td>
                                                    <small>
                                                        {new Date(sequence.start_date).toLocaleDateString()} -
                                                        <br />
                                                        {new Date(sequence.end_date).toLocaleDateString()}
                                                    </small>
                                                </td>
                                                <td>
                                                    {sequence.is_current ? (
                                                        <Badge bg="success">En cours</Badge>
                                                    ) : sequence.is_completed ? (
                                                        <Badge bg="info">Terminée</Badge>
                                                    ) : (
                                                        <Badge bg="secondary">Programmée</Badge>
                                                    )}
                                                </td>
                                                <td>
                                                    <small className="text-muted">Toujours accessible</small>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            ) : (
                                <div className="text-center py-4">
                                    <Clock size={32} className="text-muted mb-2" />
                                    <p className="text-muted">Aucune séquence pour ce trimestre</p>
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </Col>

                {/* DS et Compositions */}
                <Col lg={6}>
                    {/* DS Info */}
                    {trimester.number !== 3 && (
                        <Card className="mb-3">
                            <Card.Header className="bg-warning text-dark">
                                <h6 className="mb-0">
                                    <Circle className="me-2" />
                                    DS{trimester.number} - Devoir Surveillé
                                </h6>
                            </Card.Header>
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center mb-3">
                                    <span>Progression: {dsInfo.completedCount}/{dsInfo.total} séquences</span>
                                    <Badge bg={dsInfo.isComplete ? 'success' : 'warning'}>
                                        {dsInfo.isComplete ? 'Calculable' : 'En attente'}
                                    </Badge>
                                </div>
                                <ProgressBar 
                                    now={dsInfo.progress} 
                                    variant={dsInfo.isComplete ? 'success' : 'warning'}
                                    className="mb-2"
                                />
                                <small className="text-muted">
                                    Le DS ne sera calculé que quand toutes les séquences auront des notes
                                </small>
                            </Card.Body>
                        </Card>
                    )}

                    {/* Compositions */}
                    <Card>
                        <Card.Header className="bg-info text-white">
                            <h6 className="mb-0">
                                <FileText className="me-2" />
                                Composition {trimester.number}
                            </h6>
                        </Card.Header>
                        <Card.Body>
                            {compositions.length > 0 ? (
                                <Table hover size="sm">
                                    <thead>
                                        <tr>
                                            <th>Composition</th>
                                            <th>Période</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {compositions.map((composition) => (
                                            <tr key={composition.id}>
                                                <td>
                                                    <strong>{composition.name}</strong>
                                                    <br />
                                                    <small className="text-muted">#{composition.number}</small>
                                                </td>
                                                <td>
                                                    <small>
                                                        {new Date(composition.start_date).toLocaleDateString()} -
                                                        <br />
                                                        {new Date(composition.end_date).toLocaleDateString()}
                                                    </small>
                                                </td>
                                                <td>
                                                    {composition.is_current ? (
                                                        <Badge bg="success">En cours</Badge>
                                                    ) : composition.is_completed ? (
                                                        <Badge bg="info">Terminée</Badge>
                                                    ) : composition.is_active ? (
                                                        <Badge bg="primary">Disponible</Badge>
                                                    ) : (
                                                        <Badge bg="secondary">Programmée</Badge>
                                                    )}
                                                </td>
                                                <td>
                                                    <small className="text-muted">Toujours accessible</small>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            ) : (
                                <div className="text-center py-3">
                                    <FileText size={24} className="text-muted mb-2" />
                                    <p className="text-muted mb-0">Composition non créée</p>
                                    <small className="text-muted">Elle se créera automatiquement avec les séquences</small>
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </div>
    );
};

export default TrimesterAcademicView;