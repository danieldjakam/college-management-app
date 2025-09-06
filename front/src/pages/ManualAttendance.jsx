/**
 * Page d'appel manuel des étudiants par classe
 * Système temporaire pour faire l'appel en attendant les cartes QR des étudiants
 */

import React, { useState, useEffect } from 'react';
import { 
    Container, 
    Row, 
    Col, 
    Card, 
    Button, 
    Table, 
    Form, 
    Badge, 
    Alert, 
    Spinner,
    Modal,
    ListGroup,
    ButtonGroup
} from 'react-bootstrap';
import { 
    ListCheck, 
    CheckCircleFill, 
    XCircleFill, 
    People, 
    Clock,
    Calendar,
    BookFill,
    PersonCheck,
    PersonX,
    Save,
    Eye,
    FileText
} from 'react-bootstrap-icons';
import { secureApiEndpoints, secureApi } from '../utils/apiMigration';
import Swal from 'sweetalert2';

// Définition temporaire des API attendance directement dans le composant
const attendanceAPI = {
    saveManualAttendance: (data) => secureApi.post('/attendance/manual', data),
    getDailyAttendanceByClass: (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        return secureApi.get(`/attendance/class-daily${queryString ? '?' + queryString : ''}`);
    },
    getClassAttendanceStats: (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        return secureApi.get(`/attendance/class-stats${queryString ? '?' + queryString : ''}`);
    }
};

const ManualAttendance = () => {
    const [classes, setClasses] = useState([]);
    const [selectedClass, setSelectedClass] = useState(null);
    const [students, setStudents] = useState([]);
    const [attendance, setAttendance] = useState({});
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
    const [attendanceStats, setAttendanceStats] = useState({});
    const [showStatsModal, setShowStatsModal] = useState(false);
    const [loadingStats, setLoadingStats] = useState(false);

    // Charger toutes les classes au démarrage
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
                Swal.fire('Erreur', 'Impossible de charger les classes', 'error');
            }
        } catch (error) {
            console.error('Erreur chargement classes:', error);
            Swal.fire('Erreur', 'Erreur lors du chargement des classes', 'error');
        } finally {
            setLoading(false);
        }
    };

    const loadStudents = async (classId) => {
        try {
            setLoading(true);
            setStudents([]);
            setAttendance({});

            // Charger les étudiants de la classe
            const response = await secureApiEndpoints.schoolClasses.getStudents(classId);
            if (response.success) {
                const studentsData = response.data || [];
                setStudents(studentsData);

                // Charger les présences existantes pour la date sélectionnée
                await loadExistingAttendance(classId, studentsData);
            } else {
                Swal.fire('Erreur', 'Impossible de charger les étudiants', 'error');
            }
        } catch (error) {
            console.error('Erreur chargement étudiants:', error);
            Swal.fire('Erreur', 'Erreur lors du chargement des étudiants', 'error');
        } finally {
            setLoading(false);
        }
    };

    const loadExistingAttendance = async (classId, studentsData) => {
        try {
            // Appel API pour récupérer les présences existantes
            const response = await attendanceAPI.getDailyAttendanceByClass({
                class_id: classId,
                date: selectedDate
            });

            if (response.success && response.data) {
                const existingAttendance = {};
                response.data.forEach(record => {
                    existingAttendance[record.student_id] = record.is_present;
                });
                setAttendance(existingAttendance);
            } else {
                // Aucune présence existante, initialiser avec null (non défini)
                const initialAttendance = {};
                studentsData.forEach(student => {
                    initialAttendance[student.id] = null;
                });
                setAttendance(initialAttendance);
            }
        } catch (error) {
            console.error('Erreur chargement présences existantes:', error);
            // Initialiser avec null en cas d'erreur
            const initialAttendance = {};
            studentsData.forEach(student => {
                initialAttendance[student.id] = null;
            });
            setAttendance(initialAttendance);
        }
    };

    const selectClass = (classData) => {
        setSelectedClass(classData);
        loadStudents(classData.id);
    };

    const markAttendance = (studentId, isPresent) => {
        setAttendance(prev => ({
            ...prev,
            [studentId]: isPresent
        }));
    };

    const markAllPresent = () => {
        const newAttendance = {};
        students.forEach(student => {
            newAttendance[student.id] = true;
        });
        setAttendance(prev => ({ ...prev, ...newAttendance }));
    };

    const markAllAbsent = () => {
        const newAttendance = {};
        students.forEach(student => {
            newAttendance[student.id] = false;
        });
        setAttendance(prev => ({ ...prev, ...newAttendance }));
    };

    const resetAttendance = () => {
        const resetAttendance = {};
        students.forEach(student => {
            resetAttendance[student.id] = null;
        });
        setAttendance(prev => ({ ...prev, ...resetAttendance }));
    };

    const saveAttendance = async () => {
        try {
            setSaving(true);

            // Filtrer seulement les étudiants avec un statut défini
            const attendanceData = [];
            Object.entries(attendance).forEach(([studentId, isPresent]) => {
                if (isPresent !== null) {
                    attendanceData.push({
                        student_id: parseInt(studentId),
                        is_present: isPresent,
                        attendance_date: selectedDate
                    });
                }
            });

            if (attendanceData.length === 0) {
                Swal.fire('Attention', 'Aucune présence à enregistrer', 'warning');
                return;
            }

            const response = await attendanceAPI.saveManualAttendance({
                class_id: selectedClass.id,
                attendance_date: selectedDate,
                attendance_records: attendanceData
            });

            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès !',
                    text: `Présences enregistrées pour ${attendanceData.length} étudiant(s)`,
                    timer: 2000,
                    showConfirmButton: false
                });

                // Recharger les données pour afficher les mises à jour
                loadStudents(selectedClass.id);
            } else {
                Swal.fire('Erreur', response.message || 'Erreur lors de l\'enregistrement', 'error');
            }
        } catch (error) {
            console.error('Erreur sauvegarde présences:', error);
            Swal.fire('Erreur', 'Erreur lors de l\'enregistrement des présences', 'error');
        } finally {
            setSaving(false);
        }
    };

    const loadAttendanceStats = async () => {
        if (!selectedClass) return;

        try {
            setLoadingStats(true);
            const response = await attendanceAPI.getClassAttendanceStats({
                class_id: selectedClass.id,
                date: selectedDate
            });

            if (response.success) {
                setAttendanceStats(response.data);
                setShowStatsModal(true);
            } else {
                Swal.fire('Erreur', 'Impossible de charger les statistiques', 'error');
            }
        } catch (error) {
            console.error('Erreur chargement statistiques:', error);
            Swal.fire('Erreur', 'Erreur lors du chargement des statistiques', 'error');
        } finally {
            setLoadingStats(false);
        }
    };

    const getAttendanceIcon = (studentId) => {
        const status = attendance[studentId];
        if (status === true) {
            return <CheckCircleFill className="text-success" size={18} />;
        } else if (status === false) {
            return <XCircleFill className="text-danger" size={18} />;
        } else {
            return <span className="text-muted">-</span>;
        }
    };

    const getAttendanceCount = () => {
        const present = Object.values(attendance).filter(status => status === true).length;
        const absent = Object.values(attendance).filter(status => status === false).length;
        const total = students.length;
        return { present, absent, total };
    };

    const { present, absent, total } = getAttendanceCount();

    return (
        <Container fluid className="py-4">
            <Row className="mb-4">
                <Col>
                    <div className="d-flex align-items-center mb-3">
                        <ListCheck size={32} className="me-3 text-primary" />
                        <div>
                            <h2 className="mb-1">Appel Manuel des Classes</h2>
                            <p className="text-muted mb-0">
                                Système temporaire pour faire l'appel en attendant les cartes QR des étudiants
                            </p>
                        </div>
                    </div>

                    <Row className="mb-3">
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>Date de l'appel</Form.Label>
                                <Form.Control 
                                    type="date" 
                                    value={selectedDate}
                                    onChange={(e) => {
                                        setSelectedDate(e.target.value);
                                        if (selectedClass) {
                                            loadStudents(selectedClass.id);
                                        }
                                    }}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={9}>
                            {selectedClass && (
                                <div className="d-flex align-items-end">
                                    <div className="me-3">
                                        <Badge bg="info" className="fs-6">
                                            <People className="me-1" />
                                            {selectedClass.name}
                                        </Badge>
                                    </div>
                                    <div className="d-flex gap-2">
                                        <Badge bg="success" className="d-flex align-items-center">
                                            <PersonCheck className="me-1" size={14} />
                                            {present} présent{present > 1 ? 's' : ''}
                                        </Badge>
                                        <Badge bg="danger" className="d-flex align-items-center">
                                            <PersonX className="me-1" size={14} />
                                            {absent} absent{absent > 1 ? 's' : ''}
                                        </Badge>
                                        <Badge bg="secondary" className="d-flex align-items-center">
                                            <People className="me-1" size={14} />
                                            {total} total
                                        </Badge>
                                    </div>
                                </div>
                            )}
                        </Col>
                    </Row>
                </Col>
            </Row>

            {!selectedClass ? (
                <Row>
                    <Col>
                        <Card>
                            <Card.Header className="d-flex align-items-center justify-content-between">
                                <h5 className="mb-0">
                                    <BookFill className="me-2" />
                                    Sélectionner une classe
                                </h5>
                            </Card.Header>
                            <Card.Body>
                                {loading ? (
                                    <div className="text-center py-5">
                                        <Spinner animation="border" />
                                        <p className="mt-2">Chargement des classes...</p>
                                    </div>
                                ) : classes.length === 0 ? (
                                    <Alert variant="warning">
                                        Aucune classe trouvée
                                    </Alert>
                                ) : (
                                    <Row>
                                        {classes.map((classData) => (
                                            <Col md={4} lg={3} key={classData.id} className="mb-3">
                                                <Card 
                                                    className="h-100"
                                                    style={{ 
                                                        cursor: 'pointer',
                                                        transition: 'all 0.2s ease'
                                                    }}
                                                    onMouseEnter={(e) => {
                                                        e.currentTarget.style.transform = 'translateY(-2px)';
                                                        e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                                                    }}
                                                    onMouseLeave={(e) => {
                                                        e.currentTarget.style.transform = 'translateY(0)';
                                                        e.currentTarget.style.boxShadow = '';
                                                    }}
                                                    onClick={() => selectClass(classData)}
                                                >
                                                    <Card.Body className="text-center">
                                                        <BookFill size={24} className="text-primary mb-2" />
                                                        <h6 className="card-title mb-1">{classData.name}</h6>
                                                        <small className="text-muted">
                                                            {classData.level?.name} - {classData.section?.name}
                                                        </small>
                                                        <div className="mt-2">
                                                            <Badge bg="outline-secondary">
                                                                <People size={14} className="me-1" />
                                                                {classData.students_count || 0} étudiant{(classData.students_count || 0) > 1 ? 's' : ''}
                                                            </Badge>
                                                        </div>
                                                    </Card.Body>
                                                </Card>
                                            </Col>
                                        ))}
                                    </Row>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            ) : (
                <Row>
                    <Col>
                        <Card>
                            <Card.Header className="d-flex align-items-center justify-content-between">
                                <div className="d-flex align-items-center">
                                    <h5 className="mb-0">
                                        <People className="me-2" />
                                        Appel - {selectedClass.name}
                                    </h5>
                                    <Badge bg="info" className="ms-3">
                                        <Calendar className="me-1" size={14} />
                                        {new Date(selectedDate).toLocaleDateString('fr-FR')}
                                    </Badge>
                                </div>
                                <div className="d-flex gap-2">
                                    <Button 
                                        variant="outline-secondary" 
                                        size="sm"
                                        onClick={() => setSelectedClass(null)}
                                    >
                                        ← Retour aux classes
                                    </Button>
                                    <Button 
                                        variant="outline-info" 
                                        size="sm"
                                        onClick={loadAttendanceStats}
                                        disabled={loadingStats}
                                    >
                                        <Eye className="me-1" size={14} />
                                        {loadingStats ? 'Chargement...' : 'Statistiques'}
                                    </Button>
                                </div>
                            </Card.Header>
                            <Card.Body>
                                {loading ? (
                                    <div className="text-center py-5">
                                        <Spinner animation="border" />
                                        <p className="mt-2">Chargement des étudiants...</p>
                                    </div>
                                ) : (
                                    <>
                                        {/* Actions rapides */}
                                        <div className="mb-3">
                                            <ButtonGroup size="sm">
                                                <Button 
                                                    variant="success" 
                                                    onClick={markAllPresent}
                                                >
                                                    <CheckCircleFill className="me-1" />
                                                    Tous présents
                                                </Button>
                                                <Button 
                                                    variant="danger" 
                                                    onClick={markAllAbsent}
                                                >
                                                    <XCircleFill className="me-1" />
                                                    Tous absents
                                                </Button>
                                                <Button 
                                                    variant="outline-secondary" 
                                                    onClick={resetAttendance}
                                                >
                                                    Réinitialiser
                                                </Button>
                                            </ButtonGroup>
                                            <Button 
                                                variant="primary" 
                                                className="ms-3" 
                                                onClick={saveAttendance}
                                                disabled={saving || Object.values(attendance).every(status => status === null)}
                                            >
                                                {saving ? (
                                                    <>
                                                        <Spinner animation="border" size="sm" className="me-1" />
                                                        Enregistrement...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Save className="me-1" />
                                                        Enregistrer les présences
                                                    </>
                                                )}
                                            </Button>
                                        </div>

                                        {/* Liste des étudiants */}
                                        <Table responsive hover>
                                            <thead className="table-light">
                                                <tr>
                                                    <th style={{ width: '60px' }}>N°</th>
                                                    <th>Nom et Prénoms</th>
                                                    <th style={{ width: '120px' }} className="text-center">Statut</th>
                                                    <th style={{ width: '200px' }} className="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {students.map((student, index) => (
                                                    <tr key={student.id}>
                                                        <td className="align-middle">
                                                            <Badge bg="light" text="dark" className="fs-6">
                                                                {index + 1}
                                                            </Badge>
                                                        </td>
                                                        <td className="align-middle">
                                                            <div>
                                                                <strong>{student.last_name} {student.first_name}</strong>
                                                                {student.middle_name && (
                                                                    <div className="small text-muted">{student.middle_name}</div>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="text-center align-middle">
                                                            <div className="d-flex align-items-center justify-content-center">
                                                                {getAttendanceIcon(student.id)}
                                                                <span className="ms-2">
                                                                    {attendance[student.id] === true ? 'Présent' : 
                                                                     attendance[student.id] === false ? 'Absent' : 
                                                                     'Non défini'}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td className="text-center align-middle">
                                                            <ButtonGroup size="sm">
                                                                <Button 
                                                                    variant={attendance[student.id] === true ? 'success' : 'outline-success'}
                                                                    onClick={() => markAttendance(student.id, true)}
                                                                >
                                                                    <CheckCircleFill className="me-1" size={14} />
                                                                    Présent
                                                                </Button>
                                                                <Button 
                                                                    variant={attendance[student.id] === false ? 'danger' : 'outline-danger'}
                                                                    onClick={() => markAttendance(student.id, false)}
                                                                >
                                                                    <XCircleFill className="me-1" size={14} />
                                                                    Absent
                                                                </Button>
                                                            </ButtonGroup>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </Table>
                                    </>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Modal des statistiques */}
            <Modal show={showStatsModal} onHide={() => setShowStatsModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        <FileText className="me-2" />
                        Statistiques de présence - {selectedClass?.name}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {attendanceStats && (
                        <Row>
                            <Col md={6}>
                                <Card className="text-center border-success">
                                    <Card.Body>
                                        <h4 className="text-success">{attendanceStats.total_present || 0}</h4>
                                        <small className="text-muted">Présents</small>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={6}>
                                <Card className="text-center border-danger">
                                    <Card.Body>
                                        <h4 className="text-danger">{attendanceStats.total_absent || 0}</h4>
                                        <small className="text-muted">Absents</small>
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowStatsModal(false)}>
                        Fermer
                    </Button>
                </Modal.Footer>
            </Modal>

        </Container>
    );
};

export default ManualAttendance;