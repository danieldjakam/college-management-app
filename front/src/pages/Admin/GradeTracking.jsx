import React, { useState, useEffect, useMemo } from 'react';
import {
    Container, Row, Col, Card, Table, Badge, Form, Button,
    Spinner, Alert, ProgressBar, InputGroup
} from 'react-bootstrap';
import {
    FileEarmarkPdf, Search, CheckCircleFill,
    ExclamationTriangleFill, XCircleFill, PersonFill,
    TelephoneFill, FilterCircle
} from 'react-bootstrap-icons';
import Swal from 'sweetalert2';
import { host } from '../../utils/fetch';

const GradeTracking = () => {
    const [loading, setLoading] = useState(true);
    const [teachers, setTeachers] = useState([]);
    const [sequences, setSequences] = useState([]);
    const [selectedSequence, setSelectedSequence] = useState('');
    const [stats, setStats] = useState({ total: 0, complete: 0, partial: 0, not_started: 0 });
    const [searchQuery, setSearchQuery] = useState('');
    const [filterStatus, setFilterStatus] = useState('all');
    const [downloading, setDownloading] = useState(false);

    const token = localStorage.getItem('token');

    const fetchData = async (sequenceId = null) => {
        setLoading(true);
        try {
            const params = sequenceId ? `?sequence_id=${sequenceId}` : '';
            const response = await fetch(`${host}/api/grade-tracking${params}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const result = await response.json();

            if (result.success) {
                setTeachers(result.data.teachers);
                setSequences(result.data.sequences);
                setStats(result.data.stats);
                if (!selectedSequence && result.data.current_sequence) {
                    setSelectedSequence(result.data.current_sequence.id.toString());
                }
            } else {
                Swal.fire('Erreur', result.message || 'Impossible de charger les données', 'error');
            }
        } catch (error) {
            console.error('Error fetching grade tracking:', error);
            Swal.fire('Erreur', 'Erreur de connexion au serveur', 'error');
        }
        setLoading(false);
    };

    useEffect(() => {
        fetchData();
    }, []);

    const handleSequenceChange = (e) => {
        const value = e.target.value;
        setSelectedSequence(value);
        fetchData(value);
    };

    const handleDownloadPDF = async () => {
        setDownloading(true);
        try {
            const response = await fetch(`${host}/api/grade-tracking/download-pdf`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/pdf'
                },
                body: JSON.stringify({
                    sequence_id: selectedSequence || undefined,
                    filter_status: filterStatus !== 'all' ? filterStatus : undefined
                })
            });

            if (!response.ok) throw new Error('Erreur lors du téléchargement');

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Suivi_Saisie_Notes_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();

            Swal.fire('Succès', 'PDF téléchargé avec succès', 'success');
        } catch (error) {
            console.error('Download error:', error);
            Swal.fire('Erreur', 'Impossible de télécharger le PDF', 'error');
        }
        setDownloading(false);
    };

    const filteredTeachers = useMemo(() => {
        return teachers.filter(teacher => {
            const matchesSearch = !searchQuery ||
                teacher.full_name.toLowerCase().includes(searchQuery.toLowerCase());
            const matchesStatus = filterStatus === 'all' || teacher.status === filterStatus;
            return matchesSearch && matchesStatus;
        });
    }, [teachers, searchQuery, filterStatus]);

    const getStatusBadge = (status) => {
        switch (status) {
            case 'complete':
                return <Badge bg="success"><CheckCircleFill className="me-1" />Complet</Badge>;
            case 'partial':
                return <Badge bg="warning" text="dark"><ExclamationTriangleFill className="me-1" />En cours</Badge>;
            case 'not_started':
                return <Badge bg="danger"><XCircleFill className="me-1" />Non commencé</Badge>;
            default:
                return null;
        }
    };

    const getProgressVariant = (pct) => {
        if (pct >= 100) return 'success';
        if (pct > 0) return 'warning';
        return 'danger';
    };

    if (loading) {
        return (
            <Container className="py-4 text-center">
                <Spinner animation="border" variant="primary" />
                <p className="mt-2">Chargement du suivi des notes...</p>
            </Container>
        );
    }

    return (
        <Container fluid className="py-3">
            <style>{`
                details > summary::-webkit-details-marker { display: none; }
                details > summary { list-style: none; }
                details[open] > summary { border-bottom: 1px solid rgba(0,0,0,.125); }
            `}</style>
            <Row className="mb-3">
                <Col>
                    <h4 className="mb-0">Suivi de la Saisie des Notes</h4>
                    <small className="text-muted">Contrôle de la saisie des notes par enseignant</small>
                </Col>
                <Col xs="auto">
                    <Button
                        variant="danger"
                        onClick={handleDownloadPDF}
                        disabled={downloading || filteredTeachers.length === 0}
                    >
                        {downloading ? (
                            <><Spinner size="sm" className="me-1" />Génération...</>
                        ) : (
                            <><FileEarmarkPdf className="me-1" />Télécharger PDF</>
                        )}
                    </Button>
                </Col>
            </Row>

            {/* Filters */}
            <Card className="mb-3">
                <Card.Body className="py-2">
                    <Row className="align-items-center g-2">
                        <Col md={4}>
                            <InputGroup size="sm">
                                <InputGroup.Text><Search /></InputGroup.Text>
                                <Form.Control
                                    placeholder="Rechercher un enseignant..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup size="sm">
                                <InputGroup.Text>Séquence</InputGroup.Text>
                                <Form.Select value={selectedSequence} onChange={handleSequenceChange}>
                                    {sequences.map(seq => (
                                        <option key={seq.id} value={seq.id}>
                                            {seq.name} {seq.is_current ? '(actuelle)' : ''}
                                        </option>
                                    ))}
                                </Form.Select>
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup size="sm">
                                <InputGroup.Text><FilterCircle /></InputGroup.Text>
                                <Form.Select value={filterStatus} onChange={(e) => setFilterStatus(e.target.value)}>
                                    <option value="all">Tous les statuts</option>
                                    <option value="complete">Complet</option>
                                    <option value="partial">En cours</option>
                                    <option value="not_started">Non commencé</option>
                                </Form.Select>
                            </InputGroup>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            {/* Stats */}
            <Row className="mb-3 g-2">
                <Col xs={6} md={3}>
                    <Card className="text-center border-primary">
                        <Card.Body className="py-2">
                            <h3 className="mb-0 text-primary">{stats.total}</h3>
                            <small className="text-muted">Enseignants</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col xs={6} md={3}>
                    <Card className="text-center border-success">
                        <Card.Body className="py-2">
                            <h3 className="mb-0 text-success">{stats.complete}</h3>
                            <small className="text-muted">Complet</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col xs={6} md={3}>
                    <Card className="text-center border-warning">
                        <Card.Body className="py-2">
                            <h3 className="mb-0 text-warning">{stats.partial}</h3>
                            <small className="text-muted">En cours</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col xs={6} md={3}>
                    <Card className="text-center border-danger">
                        <Card.Body className="py-2">
                            <h3 className="mb-0 text-danger">{stats.not_started}</h3>
                            <small className="text-muted">Non commencé</small>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Teachers list */}
            {filteredTeachers.length === 0 ? (
                <Alert variant="info" className="text-center">
                    Aucun enseignant trouvé pour les critères sélectionnés.
                </Alert>
            ) : (
                <div>
                    {filteredTeachers.map((teacher) => {
                        const pct = teacher.summary.total > 0
                            ? Math.round((teacher.summary.complete / teacher.summary.total) * 100)
                            : 0;

                        return (
                            <details key={teacher.teacher_id} className="card mb-2">
                                <summary className="card-header py-2" style={{ cursor: 'pointer', listStyle: 'none' }}>
                                    <div className="d-flex justify-content-between align-items-center">
                                        <div>
                                            <PersonFill className="me-2 text-primary" />
                                            <strong>{teacher.full_name}</strong>
                                            {teacher.phone_number && (
                                                <small className="text-muted ms-2">
                                                    <TelephoneFill className="me-1" size={10} />
                                                    {teacher.phone_number}
                                                </small>
                                            )}
                                        </div>
                                        <div className="d-flex align-items-center gap-2">
                                            <ProgressBar
                                                now={pct}
                                                variant={getProgressVariant(pct)}
                                                style={{ width: '100px', height: '8px' }}
                                            />
                                            <small className="text-muted" style={{ minWidth: '35px' }}>{pct}%</small>
                                            {getStatusBadge(teacher.status)}
                                            <Badge bg="secondary" pill>
                                                {teacher.summary.complete}/{teacher.summary.total}
                                            </Badge>
                                        </div>
                                    </div>
                                </summary>
                                <Table striped hover size="sm" className="mb-0">
                                    <thead>
                                        <tr>
                                            <th>Classe</th>
                                            <th>Matière</th>
                                            <th className="text-center">Effectif</th>
                                            <th className="text-center">Notes saisies</th>
                                            <th className="text-center">Progression</th>
                                            <th className="text-center">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {teacher.assignments.map((assignment, i) => {
                                            const assignPct = assignment.total_students > 0
                                                ? Math.round((assignment.grades_entered / assignment.total_students) * 100)
                                                : 0;
                                            return (
                                                <tr key={i}>
                                                    <td>{assignment.class_name}</td>
                                                    <td>{assignment.subject_name}</td>
                                                    <td className="text-center">{assignment.total_students}</td>
                                                    <td className="text-center">
                                                        {assignment.grades_entered} / {assignment.total_students}
                                                    </td>
                                                    <td className="text-center">
                                                        <ProgressBar
                                                            now={assignPct}
                                                            variant={getProgressVariant(assignPct)}
                                                            style={{ height: '6px' }}
                                                        />
                                                    </td>
                                                    <td className="text-center">
                                                        {getStatusBadge(assignment.status)}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </Table>
                            </details>
                        );
                    })}
                </div>
            )}
        </Container>
    );
};

export default GradeTracking;
