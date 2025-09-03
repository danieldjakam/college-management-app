import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Table, Badge, Button, Spinner, Alert, Form } from 'react-bootstrap';
import { PersonFill, ArrowLeft, Search, Envelope, Telephone, Calendar, Eye } from 'react-bootstrap-icons';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { useAuth } from '../../hooks/useAuth';

const TeacherClassStudents = () => {
    const { classId } = useParams();
    const navigate = useNavigate();
    const location = useLocation();
    const { user } = useAuth();
    
    const [loading, setLoading] = useState(true);
    const [students, setStudents] = useState([]);
    const [filteredStudents, setFilteredStudents] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [classInfo, setClassInfo] = useState(null);
    
    // Récupérer les informations passées via navigation
    const { className, subjectName, subjectId } = location.state || {};

    useEffect(() => {
        if (classId) {
            loadStudents();
        }
    }, [classId]);

    useEffect(() => {
        filterStudents();
    }, [students, searchTerm]);

    const loadStudents = async () => {
        try {
            setLoading(true);
            
            // Charger les étudiants de la classe
            console.log('DEBUG - Chargement des élèves pour la classe:', classId);
            const response = await secureApiEndpoints.students.getByClass(classId, {
                with_details: true,
                include_payment_status: true
            });
            console.log('DEBUG - Réponse API complète:', response);
            
            if (response.success) {
                const studentsData = response.data.students || response.data || [];
                console.log('DEBUG - Données élèves extraites:', studentsData);
                setStudents(studentsData);
                setClassInfo(response.data.class_info || null);
            } else {
                console.error('Erreur API:', response.message);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des étudiants:', error);
        } finally {
            setLoading(false);
        }
    };

    const filterStudents = () => {
        if (!searchTerm.trim()) {
            setFilteredStudents(students);
            return;
        }

        const filtered = students.filter(student => {
            const fullName = `${student.first_name} ${student.last_name}`.toLowerCase();
            const matricule = student.matricule?.toLowerCase() || '';
            const search = searchTerm.toLowerCase();
            
            return fullName.includes(search) || 
                   matricule.includes(search) ||
                   student.parent_phone?.includes(search);
        });
        
        setFilteredStudents(filtered);
    };

    const getStatusBadge = (student) => {
        if (student.is_active === false) {
            return <Badge bg="danger">Inactif</Badge>;
        }
        return <Badge bg="success">Actif</Badge>;
    };

    const getGenderBadge = (gender) => {
        if (gender === 'M' || gender === 'male') {
            return <Badge bg="info">M</Badge>;
        } else if (gender === 'F' || gender === 'female') {
            return <Badge bg="warning">F</Badge>;
        }
        return <Badge bg="secondary">-</Badge>;
    };

    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('fr-FR');
    };

    const handleViewStudentDetails = (studentId) => {
        // Navigation vers détails de l'étudiant (si route existe)
        navigate(`/teacher/student/${studentId}`, {
            state: { 
                returnTo: `/teacher/class/${classId}/students`,
                className,
                subjectName 
            }
        });
    };

    if (loading) {
        return (
            <div className="text-center p-5">
                <Spinner animation="border" variant="primary" />
                <p className="mt-2">Chargement des élèves...</p>
            </div>
        );
    }

    return (
        <div className="container-fluid p-4">
            {/* En-tête avec navigation */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Body>
                            <div className="d-flex align-items-center justify-content-between">
                                <div className="d-flex align-items-center">
                                    <Button 
                                        variant="outline-secondary" 
                                        size="sm" 
                                        className="me-3"
                                        onClick={() => navigate('/teacher/dashboard')}
                                    >
                                        <ArrowLeft className="me-1" />
                                        Retour
                                    </Button>
                                    <div>
                                        <h4 className="mb-1">
                                            Élèves - {className || classInfo?.name || 'Classe inconnue'}
                                        </h4>
                                        {subjectName && (
                                            <small className="text-muted">
                                                Matière: {subjectName}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <Badge bg="primary" className="p-2">
                                    {filteredStudents.length} élève{filteredStudents.length > 1 ? 's' : ''}
                                </Badge>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Barre de recherche */}
            <Row className="mb-3">
                <Col md={6}>
                    <Form.Group>
                        <div className="position-relative">
                            <Form.Control
                                type="text"
                                placeholder="Rechercher un élève (nom, matricule, téléphone parent)..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="ps-5"
                            />
                            <Search 
                                className="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" 
                                size={16} 
                            />
                        </div>
                    </Form.Group>
                </Col>
            </Row>

            {/* Liste des élèves */}
            <Row>
                <Col>
                    <Card>
                        <Card.Header className="bg-light">
                            <h6 className="mb-0">
                                <PersonFill className="me-2" />
                                Liste des Élèves
                            </h6>
                        </Card.Header>
                        <Card.Body className="p-0">
                            {filteredStudents.length === 0 ? (
                                <Alert variant="info" className="m-3">
                                    {searchTerm ? 
                                        'Aucun élève trouvé avec ces critères de recherche.' : 
                                        'Aucun élève dans cette classe.'
                                    }
                                </Alert>
                            ) : (
                                <Table hover responsive className="mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th>Matricule</th>
                                            <th>Nom & Prénom</th>
                                            <th>Genre</th>
                                            <th>Date de naissance</th>
                                            <th>Contact Parent</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredStudents.map((student, index) => (
                                            <tr key={student.id || index}>
                                                <td>
                                                    <code className="bg-light p-1 rounded">
                                                        {student.matricule || '-'}
                                                    </code>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong>
                                                            {student.last_name} {student.first_name}
                                                        </strong>
                                                        {student.parent_name && (
                                                            <>
                                                                <br />
                                                                <small className="text-muted">
                                                                    Parent: {student.parent_name}
                                                                </small>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                                <td>{getGenderBadge(student.gender)}</td>
                                                <td>
                                                    <small>
                                                        {formatDate(student.date_of_birth)}
                                                        {student.age && (
                                                            <><br />({student.age} ans)</>
                                                        )}
                                                    </small>
                                                </td>
                                                <td>
                                                    <div className="d-flex flex-column gap-1">
                                                        {student.parent_phone && (
                                                            <small>
                                                                <Telephone size={12} className="me-1 text-primary" />
                                                                {student.parent_phone}
                                                            </small>
                                                        )}
                                                        {student.parent_email && (
                                                            <small>
                                                                <Envelope size={12} className="me-1 text-info" />
                                                                {student.parent_email}
                                                            </small>
                                                        )}
                                                    </div>
                                                </td>
                                                <td>{getStatusBadge(student)}</td>
                                                <td>
                                                    <Button
                                                        size="sm"
                                                        variant="outline-primary"
                                                        onClick={() => handleViewStudentDetails(student.id)}
                                                    >
                                                        <Eye size={14} />
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

            {/* Informations complémentaires */}
            {classInfo && (
                <Row className="mt-4">
                    <Col>
                        <Card className="border-0 bg-light">
                            <Card.Body className="py-2">
                                <small className="text-muted d-flex align-items-center">
                                    <Calendar size={14} className="me-2" />
                                    Classe: {classInfo.name} - Niveau: {classInfo.level?.name || 'N/A'} - 
                                    Enseignant connecté: {user.name}
                                </small>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}
        </div>
    );
};

export default TeacherClassStudents;