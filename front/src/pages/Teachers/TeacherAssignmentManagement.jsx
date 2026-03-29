import React, { useState, useEffect } from 'react';
import { Button, Modal, Form, Alert, Badge, Card, Table, Row, Col, Tabs, Tab } from 'react-bootstrap';
import { PlusCircle, PersonFill, JournalBookmarkFill, Trash2, Calendar, KeyFill, Eye, EyeSlash, Download, FileEarmarkPdf } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { host } from '../../utils/fetch';
import Swal from 'sweetalert2';

const TeacherAssignmentManagement = () => {
    const [loading, setLoading] = useState(false);
    const [teachers, setTeachers] = useState([]);
    const [seriesSubjects, setSeriesSubjects] = useState([]);
    const [schoolYears, setSchoolYears] = useState([]);
    const [assignments, setAssignments] = useState([]);
    const [mainTeachers, setMainTeachers] = useState([]);
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [showMainTeacherModal, setShowMainTeacherModal] = useState(false);
    const [selectedTeacher, setSelectedTeacher] = useState(null);
    const [selectedSchoolYear, setSelectedSchoolYear] = useState('current');
    const [availableSubjects, setAvailableSubjects] = useState([]);
    const [availableClasses, setAvailableClasses] = useState([]);
    const [assignmentData, setAssignmentData] = useState({
        class_series_subject_id: ''
    });
    const [mainTeacherData, setMainTeacherData] = useState({
        class_series_id: ''
    });
    const [activeTab, setActiveTab] = useState('assignments');
    const [showCredentialsModal, setShowCredentialsModal] = useState(false);
    const [credentialsData, setCredentialsData] = useState({ username: '', password: '' });
    const [showPassword, setShowPassword] = useState(false);
    const [showDownloadModal, setShowDownloadModal] = useState(false);
    const [downloadData, setDownloadData] = useState({ default_password: '', login_url: 'http://admin.cpb-douala.com' });
    const [downloading, setDownloading] = useState(false);

    useEffect(() => {
        loadInitialData();
    }, []);

    const loadYearData = async () => {
        try {
            const yearId = selectedSchoolYear === 'current' ? null : selectedSchoolYear;

            // Construire les paramètres sans school_year_id si yearId est null
            const assignmentParams = { active: true };
            const mainTeacherParams = { active: true };

            if (yearId !== null) {
                assignmentParams.school_year_id = yearId;
                mainTeacherParams.school_year_id = yearId;
            }

            console.log('📊 Loading year data with params:', assignmentParams);

            const [assignmentsRes, mainTeachersRes] = await Promise.all([
                secureApiEndpoints.teacherAssignments.getAll(assignmentParams),
                secureApiEndpoints.mainTeachers.getAll(mainTeacherParams)
            ]);

            console.log('✅ Assignments loaded:', assignmentsRes);
            console.log('✅ Main teachers loaded:', mainTeachersRes);

            if (assignmentsRes.success) {
                console.log('📋 Total assignments:', assignmentsRes.data.length);
                console.log('📋 First assignment structure:', assignmentsRes.data[0]);
                setAssignments(assignmentsRes.data);
            }
            if (mainTeachersRes.success) setMainTeachers(mainTeachersRes.data);

        } catch (error) {
            console.error('Erreur lors du chargement des données de l\'année:', error);
        }
    };

    useEffect(() => {
        if (selectedSchoolYear) {
            loadYearData();
        }
    }, [selectedSchoolYear]);

    const loadInitialData = async () => {
        try {
            setLoading(true);
            const [teachersRes, schoolYearsRes, seriesSubjectsRes] = await Promise.all([
                secureApiEndpoints.teachers.getAll({ active: true, with_details: true }),
                secureApiEndpoints.schoolYears.getActiveYears(),
                secureApiEndpoints.seriesSubjects.getAll({ active: true })
            ]);

            if (teachersRes.success) setTeachers(teachersRes.data);
            if (schoolYearsRes.success) setSchoolYears(schoolYearsRes.data);
            if (seriesSubjectsRes.success) setSeriesSubjects(seriesSubjectsRes.data);

        } catch (error) {
            console.error('Erreur lors du chargement des données:', error);
            Swal.fire('Erreur', 'Impossible de charger les données', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleShowAssignModal = async (teacher) => {
        setSelectedTeacher(teacher);
        
        try {
            const yearId = selectedSchoolYear === 'current' ? null : selectedSchoolYear;
            const params = {};
            if (yearId !== null) {
                params.school_year_id = yearId;
            }
            const availableRes = await secureApiEndpoints.teacherAssignments.getAvailableSubjects(teacher.id, params);

            if (availableRes.success) {
                setAvailableSubjects(availableRes.data);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des matières disponibles:', error);
        }

        setAssignmentData({ class_series_subject_id: '' });
        setShowAssignModal(true);
    };

    const handleShowMainTeacherModal = async () => {
        try {
            const yearId = selectedSchoolYear === 'current' ? null : selectedSchoolYear;
            const params = {};
            if (yearId !== null) {
                params.school_year_id = yearId;
            }
            const [availableTeachersRes, availableClassesRes] = await Promise.all([
                secureApiEndpoints.mainTeachers.getAvailableTeachers(params),
                secureApiEndpoints.mainTeachers.getClassesWithoutMainTeacher(params)
            ]);

            if (availableTeachersRes.success) setTeachers(prev => prev.filter(t => 
                availableTeachersRes.data.some(at => at.id === t.id)
            ));
            if (availableClassesRes.success) setAvailableClasses(availableClassesRes.data);

        } catch (error) {
            console.error('Erreur lors du chargement des données:', error);
        }

        setMainTeacherData({ class_series_id: '' });
        setShowMainTeacherModal(true);
    };

    const handleAssignSubject = async (e) => {
        e.preventDefault();
        
        if (!selectedTeacher || !assignmentData.class_series_subject_id) {
            Swal.fire('Erreur', 'Veuillez sélectionner une matière', 'error');
            return;
        }

        try {
            const yearId = selectedSchoolYear === 'current' ? null : selectedSchoolYear;
            const data = {
                teacher_id: selectedTeacher.id,
                class_series_subject_id: assignmentData.class_series_subject_id
            };
            if (yearId !== null) {
                data.school_year_id = yearId;
            }
            const response = await secureApiEndpoints.teacherAssignments.create(data);

            if (response.success) {
                Swal.fire('Succès!', response.message || 'Enseignant affecté avec succès', 'success');
                setShowAssignModal(false);
                loadYearData();
            } else {
                Swal.fire('Erreur', response.message || 'Erreur lors de l\'affectation', 'error');
            }
        } catch (error) {
            console.error('Erreur lors de l\'affectation:', error);
            let errorMessage = 'Une erreur est survenue';
            
            if (error.message) {
                errorMessage = error.message;
            } else if (error.response && error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
            }
            
            Swal.fire('Erreur', errorMessage, 'error');
        }
    };

    const handleAssignMainTeacher = async (e) => {
        e.preventDefault();

        if (!selectedTeacher || !mainTeacherData.class_series_id) {
            Swal.fire('Erreur', 'Veuillez sélectionner un enseignant et une série de classe', 'error');
            return;
        }

        try {
            const yearId = selectedSchoolYear === 'current' ? null : selectedSchoolYear;
            const data = {
                teacher_id: selectedTeacher.id,
                class_series_id: mainTeacherData.class_series_id
            };
            if (yearId !== null) {
                data.school_year_id = yearId;
            }
            const response = await secureApiEndpoints.mainTeachers.create(data);

            if (response.success) {
                Swal.fire('Succès!', response.message || 'Professeur principal désigné avec succès', 'success');
                setShowMainTeacherModal(false);
                loadYearData();
            } else {
                Swal.fire('Erreur', response.message || 'Erreur lors de la désignation', 'error');
            }
        } catch (error) {
            console.error('Erreur lors de la désignation:', error);
            let errorMessage = 'Une erreur est survenue';
            
            if (error.message) {
                errorMessage = error.message;
            } else if (error.response && error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
            }
            
            Swal.fire('Erreur', errorMessage, 'error');
        }
    };

    const handleRemoveAssignment = async (assignmentId) => {
        try {
            const result = await Swal.fire({
                title: 'Confirmation',
                text: 'Êtes-vous sûr de vouloir supprimer cette affectation ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#d33'
            });

            if (result.isConfirmed) {
                const response = await secureApiEndpoints.teacherAssignments.delete(assignmentId);
                if (response.success) {
                    Swal.fire('Supprimé!', response.message || 'Affectation supprimée avec succès', 'success');
                    loadYearData();
                }
            }
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            let errorMessage = 'Une erreur est survenue';
            
            if (error.message) {
                errorMessage = error.message;
            } else if (error.response && error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
            }
            
            Swal.fire('Erreur', errorMessage, 'error');
        }
    };

    const handleRemoveMainTeacher = async (mainTeacherId) => {
        try {
            const result = await Swal.fire({
                title: 'Confirmation',
                text: 'Êtes-vous sûr de vouloir retirer ce professeur principal ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, retirer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#d33'
            });

            if (result.isConfirmed) {
                const response = await secureApiEndpoints.mainTeachers.delete(mainTeacherId);
                if (response.success) {
                    Swal.fire('Retiré!', response.message || 'Professeur principal retiré avec succès', 'success');
                    loadYearData();
                }
            }
        } catch (error) {
            console.error('Erreur lors du retrait:', error);
            let errorMessage = 'Une erreur est survenue';
            
            if (error.message) {
                errorMessage = error.message;
            } else if (error.response && error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
            }
            
            Swal.fire('Erreur', errorMessage, 'error');
        }
    };

    const handleShowCredentialsModal = (teacher) => {
        setSelectedTeacher(teacher);
        setCredentialsData({
            username: teacher.user?.username || '',
            password: ''
        });
        setShowPassword(false);
        setShowCredentialsModal(true);
    };

    const handleUpdateCredentials = async (e) => {
        e.preventDefault();

        if (!selectedTeacher) return;

        const hasUser = !!selectedTeacher.user;
        const data = {};

        if (!hasUser) {
            // Création de compte : username et password obligatoires
            if (!credentialsData.username || !credentialsData.password) {
                Swal.fire('Erreur', 'Le nom d\'utilisateur et le mot de passe sont obligatoires pour créer un compte', 'error');
                return;
            }
            data.username = credentialsData.username;
            data.password = credentialsData.password;
        } else {
            // Mise à jour : au moins un champ modifié
            if (credentialsData.username && credentialsData.username !== selectedTeacher.user?.username) {
                data.username = credentialsData.username;
            }
            if (credentialsData.password) {
                data.password = credentialsData.password;
            }

            if (!data.username && !data.password) {
                Swal.fire('Info', 'Aucune modification détectée', 'info');
                return;
            }
        }

        try {
            const response = await secureApiEndpoints.teacherAssignments.updateCredentials(selectedTeacher.id, data);

            if (response.success) {
                Swal.fire('Succès!', response.message, 'success');
                setShowCredentialsModal(false);
                // Mettre à jour le user localement
                const newUsername = data.username || selectedTeacher.user?.username;
                setTeachers(prev => prev.map(t =>
                    t.id === selectedTeacher.id
                        ? { ...t, user: { ...(t.user || {}), id: response.data?.user_id, username: newUsername } }
                        : t
                ));
            } else {
                Swal.fire('Erreur', response.message || 'Erreur lors de la mise à jour', 'error');
            }
        } catch (error) {
            console.error('Erreur mise à jour identifiants:', error);
            const errorMessage = error.message || error.response?.data?.message || 'Une erreur est survenue';
            Swal.fire('Erreur', errorMessage, 'error');
        }
    };

    const handleDownloadCredentialsPDF = async (e) => {
        e.preventDefault();
        setDownloading(true);

        try {
            const token = localStorage.getItem('token') || sessionStorage.getItem('token');
            const response = await fetch(`${host}/api/teacher-assignments/download-credentials-pdf`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/pdf',
                },
                body: JSON.stringify({
                    default_password: downloadData.default_password || null,
                    login_url: downloadData.login_url || null,
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => null);
                throw new Error(errorData?.message || `Erreur ${response.status}`);
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `Identifiants_Enseignants_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(link);
            link.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(link);

            setShowDownloadModal(false);
            Swal.fire('Succès!', 'PDF des identifiants téléchargé avec succès', 'success');
        } catch (error) {
            console.error('Erreur téléchargement PDF:', error);
            Swal.fire('Erreur', error.message || 'Erreur lors du téléchargement', 'error');
        } finally {
            setDownloading(false);
        }
    };

    const getTeacherAssignments = (teacherId) => {
        console.log(`🔍 Looking for assignments for teacher ${teacherId}. Total assignments in state: ${assignments.length}`);
        if (assignments.length > 0) {
            console.log('First assignment:', assignments[0]);
            console.log('First assignment teacher_id type:', typeof assignments[0].teacher_id);
            console.log('Searching teacher_id type:', typeof teacherId);
        }
        const filtered = assignments.filter(a => {
            const match = a.teacher_id === teacherId;
            if (a.teacher_id === teacherId || a.teacher_id == teacherId) {
                console.log(`✅ Match found: assignment ${a.id} for teacher ${teacherId}`);
            }
            return match;
        });
        console.log(`👨‍🏫 Teacher ${teacherId} has ${filtered.length} assignments out of ${assignments.length} total`);
        if (filtered.length > 0) {
            console.log('Filtered assignments:', filtered);
        }
        return filtered;
    };

    const getTeacherMainClasses = (teacherId) => {
        return mainTeachers.filter(mt => mt.teacher_id === teacherId);
    };

    const getCurrentSchoolYear = () => {
        if (selectedSchoolYear === 'current') {
            const currentYear = schoolYears.find(y => y.is_current);
            return currentYear ? currentYear.name : 'Année courante';
        }
        const year = schoolYears.find(y => y.id === parseInt(selectedSchoolYear));
        return year ? year.name : 'Année sélectionnée';
    };

    if (loading) {
        return (
            <div className="container-fluid py-4">
                <div className="text-center">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid py-4">
            {/* Header */}
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Affectation des Enseignants</h2>
                    <p className="text-muted">Gérez les affectations des enseignants aux matières configurées et désignez les professeurs principaux</p>
                </div>
                <div className="d-flex gap-2">
                    <Button
                        variant="outline-danger"
                        onClick={() => setShowDownloadModal(true)}
                    >
                        <FileEarmarkPdf className="me-2" />
                        PDF Identifiants
                    </Button>
                    <Button
                        variant="success"
                        onClick={handleShowMainTeacherModal}
                    >
                        <PlusCircle className="me-2" />
                        Désigner Professeur Principal
                    </Button>
                </div>
            </div>

            {/* School Year Selector */}
            <Card className="mb-4">
                <Card.Body>
                    <Row className="align-items-center">
                        <Col md={6}>
                            <Form.Group>
                                <Form.Label className="d-flex align-items-center">
                                    <Calendar className="me-2" />
                                    Année scolaire
                                </Form.Label>
                                <Form.Select
                                    value={selectedSchoolYear}
                                    onChange={(e) => setSelectedSchoolYear(e.target.value)}
                                >
                                    <option value="current">Année courante</option>
                                    {schoolYears.map((year) => (
                                        <option key={year.id} value={year.id}>
                                            {year.name} {year.is_current && '(Courante)'}
                                        </option>
                                    ))}
                                </Form.Select>
                            </Form.Group>
                        </Col>
                        <Col md={6}>
                            <Alert variant="info" className="mb-0">
                                <strong>Année sélectionnée:</strong> {getCurrentSchoolYear()}
                            </Alert>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            {/* Content Tabs */}
            <Card>
                <Card.Body>
                    <Tabs activeKey={activeTab} onSelect={setActiveTab} className="mb-3">
                        <Tab eventKey="assignments" title="Affectations Matières">
                            {console.log('📚 Rendering teachers:', teachers.length, 'Total assignments:', assignments.length)}
                            <div className="row g-4">
                                {teachers.map((teacher) => {
                                    const teacherAssignments = getTeacherAssignments(teacher.id);
                                    const mainClasses = getTeacherMainClasses(teacher.id);

                                    return (
                                        <div key={teacher.id} className="col-md-6 col-lg-4">
                                            <Card className="h-100">
                                                <Card.Header className="d-flex justify-content-between align-items-center">
                                                    <div className="d-flex align-items-center">
                                                        <PersonFill className="text-primary me-2" />
                                                        <div>
                                                            <strong>{teacher.full_name || `${teacher.last_name} ${teacher.first_name}`}</strong>
                                                            {mainClasses.length > 0 && (
                                                                <div>
                                                                    <Badge bg="success" className="ms-1">Prof. Principal</Badge>
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="d-flex gap-1">
                                                        <Button
                                                            variant="outline-warning"
                                                            size="sm"
                                                            onClick={() => handleShowCredentialsModal(teacher)}
                                                            title="Modifier identifiants"
                                                        >
                                                            <KeyFill size={14} />
                                                        </Button>
                                                        <Button
                                                            variant="outline-primary"
                                                            size="sm"
                                                            onClick={() => handleShowAssignModal(teacher)}
                                                        >
                                                            <PlusCircle size={14} className="me-1" />
                                                            Affecter
                                                        </Button>
                                                    </div>
                                                </Card.Header>
                                                <Card.Body>
                                                    <div className="mb-2">
                                                        <small className="text-muted">
                                                            <strong>Téléphone:</strong> {teacher.phone_number}
                                                        </small>
                                                        {teacher.user?.username && (
                                                            <div>
                                                                <small className="text-muted">
                                                                    <strong>Utilisateur:</strong> {teacher.user.username}
                                                                </small>
                                                            </div>
                                                        )}
                                                    </div>
                                                    
                                                    {/* Affectations matières */}
                                                    {teacherAssignments.length > 0 ? (
                                                        <div className="mb-3">
                                                            <small className="text-muted mb-2 d-block">
                                                                <strong>Matières assignées:</strong>
                                                            </small>
                                                            {teacherAssignments.map((assignment) => (
                                                                <div key={assignment.id} className="mb-2 p-2 bg-light rounded">
                                                                    <div className="d-flex justify-content-between align-items-start">
                                                                        <div>
                                                                            <strong className="text-primary">
                                                                                {assignment.series_subject?.subject?.name}
                                                                            </strong>
                                                                            <br />
                                                                            <small className="text-muted">
                                                                                {assignment.series_subject?.class_series?.name}
                                                                                {assignment.series_subject?.class_series?.school_class?.level &&
                                                                                    ` (${assignment.series_subject.class_series.school_class.level.name})`
                                                                                }
                                                                            </small>
                                                                            <br />
                                                                            <Badge bg="secondary">
                                                                                Coeff: {assignment.series_subject?.coefficient}
                                                                            </Badge>
                                                                        </div>
                                                                        <Button
                                                                            variant="outline-danger"
                                                                            size="sm"
                                                                            onClick={() => handleRemoveAssignment(assignment.id)}
                                                                        >
                                                                            <Trash2 size={12} />
                                                                        </Button>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <div className="text-center text-muted py-2">
                                                            <JournalBookmarkFill size={20} className="mb-1" />
                                                            <br />
                                                            <small>Aucune matière assignée</small>
                                                        </div>
                                                    )}

                                                    {/* Classes principales */}
                                                    {mainClasses.length > 0 && (
                                                        <div>
                                                            <small className="text-muted mb-2 d-block">
                                                                <strong>Professeur principal de:</strong>
                                                            </small>
                                                            {mainClasses.map((mainClass) => (
                                                                <div key={mainClass.id} className="mb-2 p-2 bg-success bg-opacity-10 rounded">
                                                                    <div className="d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <strong className="text-success">
                                                                                {mainClass.class_series?.name || mainClass.school_class?.name}
                                                                            </strong>
                                                                            {(mainClass.class_series?.school_class?.level || mainClass.school_class?.level) && (
                                                                                <span className="text-muted ms-1">
                                                                                    ({mainClass.class_series?.school_class?.level?.name || mainClass.school_class?.level?.name})
                                                                                </span>
                                                                            )}
                                                                        </div>
                                                                        <Button
                                                                            variant="outline-danger"
                                                                            size="sm"
                                                                            onClick={() => handleRemoveMainTeacher(mainClass.id)}
                                                                        >
                                                                            <Trash2 size={12} />
                                                                        </Button>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </Card.Body>
                                            </Card>
                                        </div>
                                    );
                                })}
                            </div>
                        </Tab>

                        <Tab eventKey="summary" title="Résumé">
                            <Row>
                                <Col md={6}>
                                    <Card>
                                        <Card.Header>
                                            <h5>Statistiques des Affectations</h5>
                                        </Card.Header>
                                        <Card.Body>
                                            <div className="d-flex justify-content-between py-2">
                                                <span>Enseignants actifs:</span>
                                                <Badge bg="primary">{teachers.length}</Badge>
                                            </div>
                                            <div className="d-flex justify-content-between py-2">
                                                <span>Affectations matières:</span>
                                                <Badge bg="info">{assignments.length}</Badge>
                                            </div>
                                            <div className="d-flex justify-content-between py-2">
                                                <span>Professeurs principaux:</span>
                                                <Badge bg="success">{mainTeachers.length}</Badge>
                                            </div>
                                            <div className="d-flex justify-content-between py-2">
                                                <span>Matières configurées:</span>
                                                <Badge bg="secondary">{seriesSubjects.length}</Badge>
                                            </div>
                                        </Card.Body>
                                    </Card>
                                </Col>
                            </Row>
                        </Tab>
                    </Tabs>
                </Card.Body>
            </Card>

            {/* Modal d'affectation matière */}
            <Modal show={showAssignModal} onHide={() => setShowAssignModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        Affecter une matière à {selectedTeacher?.full_name || `${selectedTeacher?.last_name} ${selectedTeacher?.first_name}`}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleAssignSubject}>
                    <Modal.Body>
                        <Form.Group className="mb-3">
                            <Form.Label>Matière disponible <span className="text-danger">*</span></Form.Label>
                            <Form.Select
                                value={assignmentData.class_series_subject_id}
                                onChange={(e) => setAssignmentData(prev => ({ ...prev, class_series_subject_id: e.target.value }))}
                                required
                            >
                                <option value="">Sélectionner une matière configurée...</option>
                                {availableSubjects.map((classSeriesSubject) => (
                                    <option key={classSeriesSubject.id} value={classSeriesSubject.id}>
                                        {classSeriesSubject.subject?.name} - {classSeriesSubject.class_series?.name}
                                        {classSeriesSubject.class_series?.school_class?.level && ` (${classSeriesSubject.class_series.school_class.level.name})`}
                                        - Coeff: {classSeriesSubject.coefficient}
                                    </option>
                                ))}
                            </Form.Select>
                            {availableSubjects.length === 0 && (
                                <Form.Text className="text-warning">
                                    Aucune matière disponible pour cet enseignant. Assurez-vous que les matières sont configurées dans les séries.
                                </Form.Text>
                            )}
                        </Form.Group>
                        
                        <Alert variant="info">
                            <strong>Information:</strong> Seules les matières préalablement configurées pour les séries apparaissent ici. 
                            Pour ajouter de nouvelles matières, utilisez d'abord la "Configuration Série-Matières".
                        </Alert>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowAssignModal(false)}>
                            Annuler
                        </Button>
                        <Button 
                            variant="primary" 
                            type="submit"
                            disabled={!assignmentData.class_series_subject_id || availableSubjects.length === 0}
                        >
                            <PlusCircle className="me-2" />
                            Affecter
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* Modal modification/création identifiants */}
            <Modal show={showCredentialsModal} onHide={() => setShowCredentialsModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>
                        <KeyFill className="me-2" />
                        {selectedTeacher?.user
                            ? `Identifiants de ${selectedTeacher?.full_name || `${selectedTeacher?.last_name} ${selectedTeacher?.first_name}`}`
                            : `Créer un compte pour ${selectedTeacher?.full_name || `${selectedTeacher?.last_name} ${selectedTeacher?.first_name}`}`
                        }
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleUpdateCredentials}>
                    <Modal.Body>
                        {!selectedTeacher?.user && (
                            <Alert variant="warning" className="mb-3">
                                Cet enseignant n'a pas encore de compte utilisateur. Remplissez les champs ci-dessous pour lui en créer un.
                            </Alert>
                        )}

                        <Form.Group className="mb-3">
                            <Form.Label>Nom d'utilisateur <span className="text-danger">*</span></Form.Label>
                            <Form.Control
                                type="text"
                                value={credentialsData.username}
                                onChange={(e) => setCredentialsData(prev => ({ ...prev, username: e.target.value }))}
                                placeholder="Nom d'utilisateur"
                                minLength={3}
                                required={!selectedTeacher?.user}
                            />
                            {selectedTeacher?.user && (
                                <Form.Text className="text-muted">
                                    Actuel : <strong>{selectedTeacher?.user?.username}</strong>
                                </Form.Text>
                            )}
                        </Form.Group>

                        <Form.Group className="mb-3">
                            <Form.Label>
                                {selectedTeacher?.user ? 'Nouveau mot de passe' : 'Mot de passe'}
                                {!selectedTeacher?.user && <span className="text-danger"> *</span>}
                            </Form.Label>
                            <div className="d-flex gap-2">
                                <Form.Control
                                    type={showPassword ? 'text' : 'password'}
                                    value={credentialsData.password}
                                    onChange={(e) => setCredentialsData(prev => ({ ...prev, password: e.target.value }))}
                                    placeholder={selectedTeacher?.user ? 'Laisser vide pour ne pas changer' : 'Mot de passe'}
                                    minLength={4}
                                    required={!selectedTeacher?.user}
                                />
                                <Button
                                    variant="outline-secondary"
                                    onClick={() => setShowPassword(!showPassword)}
                                    type="button"
                                >
                                    {showPassword ? <EyeSlash size={16} /> : <Eye size={16} />}
                                </Button>
                            </div>
                        </Form.Group>

                        <Alert variant="info" className="mb-0">
                            {selectedTeacher?.user
                                ? 'Vous pouvez modifier le nom d\'utilisateur, le mot de passe, ou les deux.'
                                : 'Un compte avec le rôle "enseignant" sera créé automatiquement.'
                            }
                        </Alert>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowCredentialsModal(false)}>
                            Annuler
                        </Button>
                        <Button
                            variant={selectedTeacher?.user ? 'warning' : 'success'}
                            type="submit"
                        >
                            <KeyFill className="me-2" />
                            {selectedTeacher?.user ? 'Enregistrer' : 'Créer le compte'}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* Modal désignation professeur principal */}
            <Modal show={showMainTeacherModal} onHide={() => setShowMainTeacherModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        Désigner un Professeur Principal
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleAssignMainTeacher}>
                    <Modal.Body>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Enseignant <span className="text-danger">*</span></Form.Label>
                                    <Form.Select
                                        value={selectedTeacher?.id || ''}
                                        onChange={(e) => {
                                            const teacher = teachers.find(t => t.id === parseInt(e.target.value));
                                            setSelectedTeacher(teacher);
                                        }}
                                        required
                                    >
                                        <option value="">Sélectionner un enseignant...</option>
                                        {teachers.map((teacher) => (
                                            <option key={teacher.id} value={teacher.id}>
                                                {teacher.full_name || `${teacher.last_name} ${teacher.first_name}`}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Série de classe <span className="text-danger">*</span></Form.Label>
                                    <Form.Select
                                        value={mainTeacherData.class_series_id}
                                        onChange={(e) => setMainTeacherData(prev => ({ ...prev, class_series_id: e.target.value }))}
                                        required
                                    >
                                        <option value="">Sélectionner une série...</option>
                                        {availableClasses.map((classSeries) => (
                                            <option key={classSeries.id} value={classSeries.id}>
                                                {classSeries.name} {classSeries.school_class?.level && `(${classSeries.school_class.level.name})`}
                                            </option>
                                        ))}
                                    </Form.Select>
                                    <Form.Text className="text-muted">
                                        Ex: 6ème A, 6ème B, 5ème ESP, etc.
                                    </Form.Text>
                                </Form.Group>
                            </Col>
                        </Row>
                        
                        <Alert variant="warning">
                            <strong>Important:</strong> Un enseignant ne peut être professeur principal que d'une seule série par année scolaire.
                            Une série ne peut avoir qu'un seul professeur principal.
                        </Alert>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowMainTeacherModal(false)}>
                            Annuler
                        </Button>
                        <Button
                            variant="success"
                            type="submit"
                            disabled={!selectedTeacher || !mainTeacherData.class_series_id}
                        >
                            <PersonFill className="me-2" />
                            Désigner
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* Modal téléchargement PDF identifiants */}
            <Modal show={showDownloadModal} onHide={() => setShowDownloadModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>
                        <FileEarmarkPdf className="me-2" />
                        Télécharger les identifiants
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleDownloadCredentialsPDF}>
                    <Modal.Body>
                        <Form.Group className="mb-3">
                            <Form.Label>Mot de passe par défaut</Form.Label>
                            <Form.Control
                                type="text"
                                value={downloadData.default_password}
                                onChange={(e) => setDownloadData(prev => ({ ...prev, default_password: e.target.value }))}
                                placeholder="Ex: cpb2026"
                            />
                            <Form.Text className="text-muted">
                                Si renseigné, ce mot de passe sera appliqué à TOUS les comptes enseignants et affiché dans le PDF.
                            </Form.Text>
                        </Form.Group>

                        <Form.Group className="mb-3">
                            <Form.Label>Lien de connexion</Form.Label>
                            <Form.Control
                                type="text"
                                value={downloadData.login_url}
                                onChange={(e) => setDownloadData(prev => ({ ...prev, login_url: e.target.value }))}
                                placeholder="http://admin.cpb-douala.com"
                            />
                        </Form.Group>

                        <Alert variant="warning">
                            <strong>Attention :</strong> Si vous renseignez un mot de passe par défaut, il sera appliqué à tous les enseignants qui ont un compte.
                            Laissez vide pour simplement exporter la liste des noms d'utilisateur sans modifier les mots de passe.
                        </Alert>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowDownloadModal(false)}>
                            Annuler
                        </Button>
                        <Button
                            variant="danger"
                            type="submit"
                            disabled={downloading}
                        >
                            {downloading ? (
                                <>
                                    <span className="spinner-border spinner-border-sm me-2" />
                                    Génération...
                                </>
                            ) : (
                                <>
                                    <Download className="me-2" />
                                    Télécharger PDF
                                </>
                            )}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </div>
    );
};

export default TeacherAssignmentManagement;