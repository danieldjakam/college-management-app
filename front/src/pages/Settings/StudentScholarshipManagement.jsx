import { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Table,
    Button,
    Modal,
    Form,
    Alert,
    Spinner,
    Badge,
    ButtonGroup,
    InputGroup
} from 'react-bootstrap';
import {
    Plus,
    Search,
    Trash,
    Award,
    Person,
    Building,
    CurrencyDollar,
    Check,
    X,
    InfoCircle,
    PeopleFill
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import Swal from 'sweetalert2';

const StudentScholarshipManagement = () => {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    
    const [schoolClasses, setSchoolClasses] = useState([]);
    const [classScholarships, setClassScholarships] = useState([]);
    const [students, setStudents] = useState([]);
    const [selectedClass, setSelectedClass] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [showBulkAssignModal, setShowBulkAssignModal] = useState(false);
    const [selectedStudent, setSelectedStudent] = useState(null);
    const [selectedScholarship, setSelectedScholarship] = useState('');
    const [selectedTranche, setSelectedTranche] = useState('');
    const [assignmentNotes, setAssignmentNotes] = useState('');
    
    const [eligibleStudents, setEligibleStudents] = useState([]);
    const [selectedStudentIds, setSelectedStudentIds] = useState([]);

    useEffect(() => {
        loadInitialData();
    }, []);

    useEffect(() => {
        if (selectedClass) {
            loadStudentsForClass();
            loadScholarshipsForClass();
        }
    }, [selectedClass]);

    const loadInitialData = async () => {
        try {
            setLoading(true);
            const classesResponse = await secureApiEndpoints.schoolClasses.getAll();
            
            if (classesResponse.success) {
                setSchoolClasses(classesResponse.data);
            }
        } catch (error) {
            setError('Erreur lors du chargement des données');
            console.error('Error loading initial data:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadStudentsForClass = async () => {
        try {
            const studentsResponse = await secureApiEndpoints.students.getByClass(selectedClass);
            
            if (studentsResponse.success) {
                // Charger les bourses pour chaque étudiant
                const studentsWithScholarships = await Promise.all(
                    studentsResponse.data.map(async (student) => {
                        try {
                            const scholarshipsResponse = await secureApiEndpoints.studentScholarships.getStudentScholarships(student.id);
                            return {
                                ...student,
                                scholarships: scholarshipsResponse.success ? scholarshipsResponse.data.scholarships : []
                            };
                        } catch (error) {
                            return {
                                ...student,
                                scholarships: []
                            };
                        }
                    })
                );
                
                setStudents(studentsWithScholarships);
            }
        } catch (error) {
            setError('Erreur lors du chargement des étudiants');
            console.error('Error loading students:', error);
        }
    };

    const loadScholarshipsForClass = async () => {
        try {
            const response = await secureApiEndpoints.studentScholarships.getAvailableScholarshipsForClass(selectedClass);
            
            if (response.success) {
                setClassScholarships(response.data);
            }
        } catch (error) {
            setError('Erreur lors du chargement des bourses');
            console.error('Error loading scholarships:', error);
        }
    };

    const handleOpenAssignModal = (student) => {
        setSelectedStudent(student);
        setSelectedScholarship('');
        setSelectedTranche('');
        setAssignmentNotes('');
        setShowAssignModal(true);
        setError('');
        setSuccess('');
    };

    const handleCloseAssignModal = () => {
        setShowAssignModal(false);
        setSelectedStudent(null);
        setSelectedScholarship('');
        setSelectedTranche('');
        setAssignmentNotes('');
    };

    const handleScholarshipChange = (scholarshipId) => {
        setSelectedScholarship(scholarshipId);
        const scholarship = classScholarships.find(s => s.id === parseInt(scholarshipId));
        if (scholarship && scholarship.payment_tranche_id) {
            setSelectedTranche(scholarship.payment_tranche_id);
        } else {
            setSelectedTranche('');
        }
    };

    const handleAssignScholarship = async (e) => {
        e.preventDefault();
        
        if (!selectedScholarship || !selectedTranche) {
            setError('Veuillez sélectionner une bourse et une tranche');
            return;
        }

        try {
            setSaving(true);
            setError('');
            
            const response = await secureApiEndpoints.studentScholarships.assignScholarship({
                student_id: selectedStudent.id,
                class_scholarship_id: selectedScholarship,
                payment_tranche_id: selectedTranche,
                notes: assignmentNotes
            });
            
            if (response.success) {
                setSuccess('Bourse assignée avec succès');
                handleCloseAssignModal();
                await loadStudentsForClass();
                
                Swal.fire({
                    title: 'Succès !',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            } else {
                setError(response.message || 'Erreur lors de l\'assignation');
            }
        } catch (error) {
            setError('Erreur lors de l\'assignation de la bourse');
            console.error('Error assigning scholarship:', error);
        } finally {
            setSaving(false);
        }
    };

    const handleRemoveScholarship = async (scholarshipId) => {
        const result = await Swal.fire({
            title: 'Confirmer la suppression',
            text: 'Êtes-vous sûr de vouloir retirer cette bourse ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Retirer',
            cancelButtonText: 'Annuler'
        });

        if (result.isConfirmed) {
            try {
                const response = await secureApiEndpoints.studentScholarships.removeScholarship(scholarshipId);
                
                if (response.success) {
                    setSuccess('Bourse retirée avec succès');
                    await loadStudentsForClass();
                    
                    Swal.fire({
                        title: 'Retiré !',
                        text: 'La bourse a été retirée avec succès.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                } else {
                    setError(response.message || 'Erreur lors de la suppression');
                }
            } catch (error) {
                setError('Erreur lors de la suppression de la bourse');
                console.error('Error removing scholarship:', error);
            }
        }
    };

    const handleOpenBulkAssignModal = async (scholarship) => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.studentScholarships.getEligibleStudents(scholarship.id);
            
            if (response.success) {
                setEligibleStudents(response.data.eligible_students);
                setSelectedScholarship(scholarship.id);
                setSelectedTranche(scholarship.payment_tranche_id);
                setSelectedStudentIds([]);
                setAssignmentNotes('');
                setShowBulkAssignModal(true);
            }
        } catch (error) {
            setError('Erreur lors du chargement des étudiants éligibles');
        } finally {
            setLoading(false);
        }
    };

    const handleBulkAssign = async (e) => {
        e.preventDefault();
        
        if (selectedStudentIds.length === 0) {
            setError('Veuillez sélectionner au moins un étudiant');
            return;
        }

        try {
            setSaving(true);
            setError('');
            
            const response = await secureApiEndpoints.studentScholarships.bulkAssignScholarship({
                student_ids: selectedStudentIds,
                class_scholarship_id: selectedScholarship,
                payment_tranche_id: selectedTranche,
                notes: assignmentNotes
            });
            
            if (response.success) {
                setSuccess(response.message);
                setShowBulkAssignModal(false);
                await loadStudentsForClass();
                
                Swal.fire({
                    title: 'Succès !',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            } else {
                setError(response.message || 'Erreur lors de l\'assignation en lot');
            }
        } catch (error) {
            setError('Erreur lors de l\'assignation en lot');
            console.error('Error bulk assigning:', error);
        } finally {
            setSaving(false);
        }
    };

    const handleStudentSelection = (studentId, checked) => {
        if (checked) {
            setSelectedStudentIds([...selectedStudentIds, studentId]);
        } else {
            setSelectedStudentIds(selectedStudentIds.filter(id => id !== studentId));
        }
    };

    const handleSelectAllStudents = (checked) => {
        if (checked) {
            setSelectedStudentIds(eligibleStudents.map(s => s.id));
        } else {
            setSelectedStudentIds([]);
        }
    };

    const filteredStudents = students.filter(student =>
        student.full_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        student.student_number.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const formatAmount = (amount) => {
        return parseInt(amount).toLocaleString() + ' FCFA';
    };

    const getClassName = (classId) => {
        const schoolClass = schoolClasses.find(c => c.id === classId);
        return schoolClass ? schoolClass.name : 'Classe inconnue';
    };

    if (loading) {
        return (
            <Container fluid className="py-4">
                <div className="text-center">
                    <Spinner animation="border" role="status">
                        <span className="visually-hidden">Chargement...</span>
                    </Spinner>
                </div>
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
                            <h2>Gestion des Bourses Individuelles</h2>
                            <p className="text-muted">Assignez des bourses spécifiques à des étudiants individuels</p>
                        </div>
                    </div>
                </Col>
            </Row>

            {/* Alerts */}
            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')}>
                    {error}
                </Alert>
            )}
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess('')}>
                    {success}
                </Alert>
            )}

            {/* Class Selection */}
            <Row className="mb-4">
                <Col md={6}>
                    <Form.Group>
                        <Form.Label>Sélectionner une classe</Form.Label>
                        <Form.Select
                            value={selectedClass}
                            onChange={(e) => setSelectedClass(e.target.value)}
                        >
                            <option value="">Choisir une classe...</option>
                            {schoolClasses.map((schoolClass) => (
                                <option key={schoolClass.id} value={schoolClass.id}>
                                    {schoolClass.name}
                                </option>
                            ))}
                        </Form.Select>
                    </Form.Group>
                </Col>
                <Col md={6}>
                    <Form.Group>
                        <Form.Label>Rechercher un étudiant</Form.Label>
                        <InputGroup>
                            <InputGroup.Text>
                                <Search size={16} />
                            </InputGroup.Text>
                            <Form.Control
                                type="text"
                                placeholder="Nom ou numéro d'étudiant..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                disabled={!selectedClass}
                            />
                        </InputGroup>
                    </Form.Group>
                </Col>
            </Row>

            {/* Available Scholarships */}
            {selectedClass && classScholarships.length > 0 && (
                <Row className="mb-4">
                    <Col>
                        <Card>
                            <Card.Header>
                                <h5 className="mb-0">
                                    <Award className="me-2" />
                                    Bourses Disponibles ({classScholarships.length})
                                </h5>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    {classScholarships.map((scholarship) => (
                                        <Col md={6} lg={4} key={scholarship.id} className="mb-3">
                                            <Card className="border-info">
                                                <Card.Body>
                                                    <h6 className="text-info">{scholarship.name}</h6>
                                                    <p className="text-muted small mb-2">
                                                        {scholarship.description || 'Aucune description'}
                                                    </p>
                                                    <p className="mb-2">
                                                        <strong className="text-success">
                                                            {formatAmount(scholarship.amount)}
                                                        </strong>
                                                    </p>
                                                    <p className="small text-muted mb-3">
                                                        Tranche: {scholarship.payment_tranche?.name}
                                                    </p>
                                                    <Button
                                                        variant="outline-primary"
                                                        size="sm"
                                                        onClick={() => handleOpenBulkAssignModal(scholarship)}
                                                    >
                                                        <PeopleFill size={14} className="me-1" />
                                                        Assigner en lot
                                                    </Button>
                                                </Card.Body>
                                            </Card>
                                        </Col>
                                    ))}
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Students Table */}
            {selectedClass && (
                <Row>
                    <Col>
                        <Card>
                            <Card.Header>
                                <h5 className="mb-0">
                                    <Person className="me-2" />
                                    Étudiants de la classe {getClassName(parseInt(selectedClass))} ({filteredStudents.length})
                                </h5>
                            </Card.Header>
                            <Card.Body>
                                {filteredStudents.length === 0 ? (
                                    <div className="text-center py-4">
                                        <p className="text-muted">Aucun étudiant trouvé</p>
                                    </div>
                                ) : (
                                    <Table responsive hover>
                                        <thead>
                                            <tr>
                                                <th>Étudiant</th>
                                                <th>Numéro</th>
                                                <th>Bourses Assignées</th>
                                                <th width="120">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {filteredStudents.map((student) => (
                                                <tr key={student.id}>
                                                    <td>
                                                        <strong>{student.full_name}</strong>
                                                    </td>
                                                    <td>
                                                        <Badge bg="secondary">{student.student_number}</Badge>
                                                    </td>
                                                    <td>
                                                        {student.scholarships.length === 0 ? (
                                                            <em className="text-muted">Aucune bourse</em>
                                                        ) : (
                                                            <div>
                                                                {student.scholarships.map((scholarship) => (
                                                                    <div key={scholarship.id} className="d-flex align-items-center justify-content-between mb-1">
                                                                        <Badge 
                                                                            bg={scholarship.is_used ? 'success' : 'info'}
                                                                            className="me-2"
                                                                        >
                                                                            {scholarship.class_scholarship?.name} - {formatAmount(scholarship.class_scholarship?.amount)}
                                                                            {scholarship.is_used && ' (Utilisée)'}
                                                                        </Badge>
                                                                        {!scholarship.is_used && (
                                                                            <Button
                                                                                variant="outline-danger"
                                                                                size="sm"
                                                                                onClick={() => handleRemoveScholarship(scholarship.id)}
                                                                                title="Retirer la bourse"
                                                                            >
                                                                                <Trash size={12} />
                                                                            </Button>
                                                                        )}
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td>
                                                        <Button
                                                            variant="outline-primary"
                                                            size="sm"
                                                            onClick={() => handleOpenAssignModal(student)}
                                                        >
                                                            <Plus size={14} className="me-1" />
                                                            Assigner
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </Table>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Modal pour assigner une bourse individuelle */}
            <Modal show={showAssignModal} onHide={handleCloseAssignModal} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        Assigner une Bourse à {selectedStudent?.full_name}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleAssignScholarship}>
                    <Modal.Body>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Bourse *</Form.Label>
                                    <Form.Select
                                        value={selectedScholarship}
                                        onChange={(e) => handleScholarshipChange(e.target.value)}
                                        required
                                    >
                                        <option value="">Sélectionner une bourse</option>
                                        {classScholarships.map((scholarship) => (
                                            <option key={scholarship.id} value={scholarship.id}>
                                                {scholarship.name} - {formatAmount(scholarship.amount)}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Tranche</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={classScholarships.find(s => s.id === parseInt(selectedScholarship))?.payment_tranche?.name || ''}
                                        disabled
                                    />
                                </Form.Group>
                            </Col>
                        </Row>

                        <Row>
                            <Col>
                                <Form.Group className="mb-3">
                                    <Form.Label>Notes (optionnel)</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        value={assignmentNotes}
                                        onChange={(e) => setAssignmentNotes(e.target.value)}
                                        placeholder="Notes sur l'attribution de cette bourse..."
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleCloseAssignModal}>
                            <X size={16} className="me-2" />
                            Annuler
                        </Button>
                        <Button variant="primary" type="submit" disabled={saving}>
                            {saving ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Assignation...
                                </>
                            ) : (
                                <>
                                    <Check size={16} className="me-2" />
                                    Assigner la Bourse
                                </>
                            )}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* Modal pour assignation en lot */}
            <Modal show={showBulkAssignModal} onHide={() => setShowBulkAssignModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        Assignation en Lot - {classScholarships.find(s => s.id === selectedScholarship)?.name}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleBulkAssign}>
                    <Modal.Body>
                        <div className="mb-3">
                            <Form.Check
                                type="checkbox"
                                label={`Sélectionner tous (${eligibleStudents.length} étudiants)`}
                                checked={selectedStudentIds.length === eligibleStudents.length && eligibleStudents.length > 0}
                                onChange={(e) => handleSelectAllStudents(e.target.checked)}
                            />
                        </div>

                        <div className="mb-3" style={{maxHeight: '300px', overflowY: 'auto'}}>
                            {eligibleStudents.map((student) => (
                                <Form.Check
                                    key={student.id}
                                    type="checkbox"
                                    label={`${student.full_name} (${student.student_number})`}
                                    checked={selectedStudentIds.includes(student.id)}
                                    onChange={(e) => handleStudentSelection(student.id, e.target.checked)}
                                />
                            ))}
                        </div>

                        <Form.Group className="mb-3">
                            <Form.Label>Notes (optionnel)</Form.Label>
                            <Form.Control
                                as="textarea"
                                rows={3}
                                value={assignmentNotes}
                                onChange={(e) => setAssignmentNotes(e.target.value)}
                                placeholder="Notes sur l'attribution de cette bourse..."
                            />
                        </Form.Group>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowBulkAssignModal(false)}>
                            <X size={16} className="me-2" />
                            Annuler
                        </Button>
                        <Button variant="primary" type="submit" disabled={saving || selectedStudentIds.length === 0}>
                            {saving ? (
                                <>
                                    <Spinner animation="border" size="sm" className="me-2" />
                                    Assignation...
                                </>
                            ) : (
                                <>
                                    <Check size={16} className="me-2" />
                                    Assigner à {selectedStudentIds.length} étudiant(s)
                                </>
                            )}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </Container>
    );
};

export default StudentScholarshipManagement;