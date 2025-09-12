import React, { useState, useEffect } from 'react';
import { 
    Container, Row, Col, Card, Form, Button, Alert, 
    ListGroup, Badge, Modal, Spinner
} from 'react-bootstrap';
import { 
    Bell, Send, People, PersonFill, BookHalf, ExclamationTriangleFill,
    InfoCircle, CheckCircleFill, Trash, ChatDots
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import './ParentNotifications.css';

const ParentNotifications = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    
    // Données
    const [parents, setParents] = useState([]);
    const [students, setStudents] = useState([]);
    const [classes, setClasses] = useState([]);
    const [notifications, setNotifications] = useState([]);
    const [stats, setStats] = useState(null);
    
    // Formulaire
    const [formData, setFormData] = useState({
        title: '',
        message: '',
        type: 'general',
        priority: 'normal',
        send_to: 'all',
        parent_id: '',
        student_id: '',
        send_to_class: ''
    });
    
    // Données pour notifications rapides d'absence
    const [subjects, setSubjects] = useState([
        'Français', 'Mathématiques', 'Anglais', 'Histoire-Géographie', 
        'Sciences', 'Éducation Physique', 'Arts Plastiques', 'Musique'
    ]);
    
    // Modal d'absence rapide
    const [showAbsenceModal, setShowAbsenceModal] = useState(false);
    const [absenceType, setAbsenceType] = useState('course'); // 'course' ou 'establishment'
    const [selectedSubject, setSelectedSubject] = useState('');
    const [selectedStudent, setSelectedStudent] = useState('');
    const [customMessage, setCustomMessage] = useState('');
    
    // Modal de confirmation
    const [showConfirm, setShowConfirm] = useState(false);
    const [pendingSubmit, setPendingSubmit] = useState(null);
    
    useEffect(() => {
        loadInitialData();
    }, []);
    
    const loadInitialData = async () => {
        try {
            setLoading(true);
            const [parentsRes, studentsRes, classesRes, statsRes] = await Promise.all([
                secureApiEndpoints.admin.notifications.getParents(),
                secureApiEndpoints.admin.notifications.getStudents(),
                secureApiEndpoints.admin.notifications.getClasses(),
                secureApiEndpoints.admin.notifications.stats()
            ]);
            
            if (parentsRes.success) setParents(parentsRes.data);
            if (studentsRes.success) setStudents(studentsRes.data);
            if (classesRes.success) setClasses(classesRes.data);
            if (statsRes.success) setStats(statsRes.data);
            
            loadNotifications();
        } catch (err) {
            setError('Erreur lors du chargement des données');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };
    
    const loadNotifications = async () => {
        try {
            const response = await secureApiEndpoints.admin.notifications.index();
            if (response.success) {
                setNotifications(response.data.data || []);
            }
        } catch (err) {
            console.error('Erreur chargement notifications:', err);
        }
    };
    
    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Validation
        if (!formData.title.trim() || !formData.message.trim()) {
            setError('Le titre et le message sont obligatoires');
            return;
        }
        
        // Préparer les données
        let submitData = {
            title: formData.title,
            message: formData.message,
            type: formData.type,
            priority: formData.priority
        };
        
        // Déterminer les destinataires
        if (formData.send_to === 'all') {
            submitData.send_to_all = true;
        } else if (formData.send_to === 'class' && formData.send_to_class) {
            submitData.send_to_class = formData.send_to_class;
        } else if (formData.send_to === 'parent' && formData.parent_id) {
            submitData.parent_id = formData.parent_id;
        } else if (formData.send_to === 'student' && formData.student_id) {
            submitData.student_id = formData.student_id;
        } else {
            setError('Veuillez sélectionner les destinataires');
            return;
        }
        
        setPendingSubmit(submitData);
        setShowConfirm(true);
    };
    
    const confirmSend = async () => {
        try {
            setLoading(true);
            setShowConfirm(false);
            
            const response = await secureApiEndpoints.admin.notifications.store(pendingSubmit);
            
            if (response.success) {
                setSuccess(response.message);
                // Réinitialiser le formulaire
                setFormData({
                    title: '',
                    message: '',
                    type: 'general',
                    priority: 'normal',
                    send_to: 'all',
                    parent_id: '',
                    student_id: '',
                    send_to_class: ''
                });
                // Recharger les stats et notifications
                loadInitialData();
            } else {
                setError(response.message || 'Erreur lors de l\'envoi');
            }
        } catch (err) {
            setError('Erreur lors de l\'envoi de la notification');
            console.error(err);
        } finally {
            setLoading(false);
            setPendingSubmit(null);
        }
    };
    
    const handleDelete = async (id) => {
        if (window.confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
            try {
                const response = await secureApiEndpoints.admin.notifications.destroy(id);
                if (response.success) {
                    setSuccess('Notification supprimée avec succès');
                    loadNotifications();
                }
            } catch (err) {
                setError('Erreur lors de la suppression');
                console.error(err);
            }
        }
    };
    
    const getPriorityBadge = (priority) => {
        const variants = {
            low: 'success',
            normal: 'info',
            high: 'warning',
            urgent: 'danger'
        };
        return <Badge bg={variants[priority] || 'secondary'}>{priority}</Badge>;
    };
    
    const getTypeIcon = (type) => {
        const icons = {
            general: <InfoCircle />,
            attendance: <ExclamationTriangleFill className="text-warning" />,
            academic: <BookHalf />,
            behavior: <ExclamationTriangleFill className="text-danger" />,
            payment: <InfoCircle className="text-info" />,
            event: <Bell />
        };
        return icons[type] || <Bell />;
    };
    
    // Fonctions pour les notifications d'absence rapide
    const generateAbsenceMessage = (student, type, subject = null) => {
        const studentName = typeof student === 'object' ? student.name : 
                          students.find(s => s.id === student)?.name || 'votre enfant';
        const today = new Date().toLocaleDateString('fr-FR');
        
        if (type === 'course' && subject) {
            return {
                title: `Absence en cours - ${subject}`,
                message: `Bonjour,\n\nNous vous informons que ${studentName} était absent(e) au cours de ${subject} aujourd'hui (${today}).\n\nNous vous remercions de bien vouloir justifier cette absence.\n\nCordialement,\nL'Administration du Collège Polyvalent Bilingue de Douala`
            };
        } else if (type === 'establishment') {
            return {
                title: `Absence dans l'établissement`,
                message: `Bonjour,\n\nNous vous informons que ${studentName} était absent(e) de l'établissement aujourd'hui (${today}).\n\nAucun cours n'a été suivi par l'élève. Nous vous remercions de bien vouloir justifier cette absence et nous assurer de sa présence dès demain.\n\nCordialement,\nL'Administration du Collège Polyvalent Bilingue de Douala`
            };
        }
        return { title: '', message: '' };
    };
    
    const handleQuickAbsence = (type) => {
        setAbsenceType(type);
        setSelectedSubject('');
        setSelectedStudent('');
        setCustomMessage('');
        setShowAbsenceModal(true);
    };
    
    const sendAbsenceNotification = async () => {
        if (!selectedStudent) {
            setError('Veuillez sélectionner un élève');
            return;
        }
        
        if (absenceType === 'course' && !selectedSubject) {
            setError('Veuillez sélectionner une matière pour l\'absence en cours');
            return;
        }
        
        try {
            setLoading(true);
            
            const student = students.find(s => s.id === selectedStudent);
            const { title, message } = generateAbsenceMessage(student, absenceType, selectedSubject);
            const finalMessage = customMessage.trim() || message;
            
            const notificationData = {
                title,
                message: finalMessage,
                type: 'attendance',
                priority: 'high',
                student_id: selectedStudent
            };
            
            const response = await secureApiEndpoints.admin.notifications.store(notificationData);
            
            if (response.success) {
                setSuccess(`Notification d'absence envoyée avec succès aux parents de ${student.name}`);
                setShowAbsenceModal(false);
                loadInitialData();
            } else {
                setError(response.message || 'Erreur lors de l\'envoi');
            }
        } catch (err) {
            setError('Erreur lors de l\'envoi de la notification');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };
    
    return (
        <Container fluid className="py-4">
            {error && (
                <Alert variant="danger" dismissible onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}
            
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess(null)}>
                    {success}
                </Alert>
            )}
            
            {/* Statistiques */}
            {stats && (
                <Row className="mb-4">
                    <Col md={3}>
                        <Card className="text-center border-primary">
                            <Card.Body>
                                <h3 className="text-primary">{stats.total}</h3>
                                <small className="text-muted">Total envoyées</small>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center border-warning">
                            <Card.Body>
                                <h3 className="text-warning">{stats.unread}</h3>
                                <small className="text-muted">Non lues</small>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center border-danger">
                            <Card.Body>
                                <h3 className="text-danger">{stats.by_priority?.urgent || 0}</h3>
                                <small className="text-muted">Urgentes</small>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="text-center border-info">
                            <Card.Body>
                                <h3 className="text-info">{stats.today}</h3>
                                <small className="text-muted">Aujourd'hui</small>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}
            
            {/* Boutons rapides pour notifications d'absence */}
            <Row className="mb-4">
                <Col>
                    <Card>
                        <Card.Header className="bg-warning text-dark">
                            <h5 className="mb-0">
                                <ExclamationTriangleFill className="me-2" />
                                Notifications d'absence rapides
                            </h5>
                        </Card.Header>
                        <Card.Body>
                            <Row>
                                <Col md={6} className="mb-3">
                                    <Button 
                                        variant="outline-warning" 
                                        size="lg" 
                                        className="w-100"
                                        onClick={() => handleQuickAbsence('course')}
                                    >
                                        <BookHalf className="me-2" />
                                        Absence en cours
                                        <small className="d-block text-muted mt-1">
                                            Élève absent à un cours spécifique
                                        </small>
                                    </Button>
                                </Col>
                                <Col md={6} className="mb-3">
                                    <Button 
                                        variant="outline-danger" 
                                        size="lg" 
                                        className="w-100"
                                        onClick={() => handleQuickAbsence('establishment')}
                                    >
                                        <ExclamationTriangleFill className="me-2" />
                                        Absence établissement
                                        <small className="d-block text-muted mt-1">
                                            Élève absent toute la journée
                                        </small>
                                    </Button>
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row>
                {/* Formulaire d'envoi */}
                <Col lg={6}>
                    <Card>
                        <Card.Header className="bg-primary text-white">
                            <h5 className="mb-0">
                                <Send className="me-2" />
                                Envoyer une notification
                            </h5>
                        </Card.Header>
                        <Card.Body>
                            <Alert variant="info" className="mb-3">
                                <ChatDots className="me-2" />
                                <strong>📱 Intégration WhatsApp :</strong> Les notifications sont automatiquement envoyées aux parents via WhatsApp en plus du système interne.
                            </Alert>
                            <Form onSubmit={handleSubmit}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Titre *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={formData.title}
                                        onChange={(e) => setFormData({...formData, title: e.target.value})}
                                        placeholder="Ex: Réunion parents-professeurs"
                                        required
                                    />
                                </Form.Group>
                                
                                <Form.Group className="mb-3">
                                    <Form.Label>Message *</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={4}
                                        value={formData.message}
                                        onChange={(e) => setFormData({...formData, message: e.target.value})}
                                        placeholder="Entrez votre message..."
                                        required
                                    />
                                </Form.Group>
                                
                                <Row>
                                    <Col md={6}>
                                        <Form.Group className="mb-3">
                                            <Form.Label>Type</Form.Label>
                                            <Form.Select
                                                value={formData.type}
                                                onChange={(e) => setFormData({...formData, type: e.target.value})}
                                            >
                                                <option value="general">Général</option>
                                                <option value="attendance">Présence</option>
                                                <option value="academic">Académique</option>
                                                <option value="behavior">Comportement</option>
                                                <option value="payment">Paiement</option>
                                                <option value="event">Événement</option>
                                            </Form.Select>
                                        </Form.Group>
                                    </Col>
                                    <Col md={6}>
                                        <Form.Group className="mb-3">
                                            <Form.Label>Priorité</Form.Label>
                                            <Form.Select
                                                value={formData.priority}
                                                onChange={(e) => setFormData({...formData, priority: e.target.value})}
                                            >
                                                <option value="low">Basse</option>
                                                <option value="normal">Normale</option>
                                                <option value="high">Haute</option>
                                                <option value="urgent">Urgente</option>
                                            </Form.Select>
                                        </Form.Group>
                                    </Col>
                                </Row>
                                
                                <Form.Group className="mb-3">
                                    <Form.Label>Envoyer à</Form.Label>
                                    <Form.Select
                                        value={formData.send_to}
                                        onChange={(e) => setFormData({...formData, send_to: e.target.value})}
                                    >
                                        <option value="all">Tous les parents</option>
                                        <option value="class">Une classe</option>
                                        <option value="parent">Un parent spécifique</option>
                                        <option value="student">Parents d'un élève</option>
                                    </Form.Select>
                                </Form.Group>
                                
                                {formData.send_to === 'class' && (
                                    <Form.Group className="mb-3">
                                        <Form.Label>Sélectionner la classe</Form.Label>
                                        <Form.Select
                                            value={formData.send_to_class}
                                            onChange={(e) => setFormData({...formData, send_to_class: e.target.value})}
                                            required
                                        >
                                            <option value="">-- Choisir une classe --</option>
                                            {classes.map(cls => (
                                                <option key={cls.id} value={cls.id}>
                                                    {cls.name}
                                                </option>
                                            ))}
                                        </Form.Select>
                                    </Form.Group>
                                )}
                                
                                {formData.send_to === 'parent' && (
                                    <Form.Group className="mb-3">
                                        <Form.Label>Sélectionner le parent</Form.Label>
                                        <Form.Select
                                            value={formData.parent_id}
                                            onChange={(e) => setFormData({...formData, parent_id: e.target.value})}
                                            required
                                        >
                                            <option value="">-- Choisir un parent --</option>
                                            {parents.map(parent => (
                                                <option key={parent.id} value={parent.id}>
                                                    {parent.name} ({parent.phone})
                                                </option>
                                            ))}
                                        </Form.Select>
                                    </Form.Group>
                                )}
                                
                                {formData.send_to === 'student' && (
                                    <Form.Group className="mb-3">
                                        <Form.Label>Sélectionner l'élève</Form.Label>
                                        <Form.Select
                                            value={formData.student_id}
                                            onChange={(e) => setFormData({...formData, student_id: e.target.value})}
                                            required
                                        >
                                            <option value="">-- Choisir un élève --</option>
                                            {students.map(student => (
                                                <option key={student.id} value={student.id}>
                                                    {student.name} - {student.class}
                                                </option>
                                            ))}
                                        </Form.Select>
                                    </Form.Group>
                                )}
                                
                                <div className="d-grid">
                                    <Button 
                                        variant="primary" 
                                        type="submit"
                                        disabled={loading}
                                    >
                                        {loading ? (
                                            <>
                                                <Spinner animation="border" size="sm" className="me-2" />
                                                Envoi en cours...
                                            </>
                                        ) : (
                                            <>
                                                <Send className="me-2" />
                                                Envoyer la notification
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </Form>
                        </Card.Body>
                    </Card>
                </Col>
                
                {/* Historique des notifications */}
                <Col lg={6}>
                    <Card>
                        <Card.Header className="bg-secondary text-white">
                            <h5 className="mb-0">
                                <Bell className="me-2" />
                                Notifications récentes
                            </h5>
                        </Card.Header>
                        <Card.Body className="p-0">
                            <ListGroup variant="flush" style={{maxHeight: '600px', overflowY: 'auto'}}>
                                {notifications.length === 0 ? (
                                    <ListGroup.Item className="text-center text-muted py-4">
                                        <Bell size={32} className="mb-2" />
                                        <p>Aucune notification envoyée</p>
                                    </ListGroup.Item>
                                ) : (
                                    notifications.map(notification => (
                                        <ListGroup.Item key={notification.id}>
                                            <div className="d-flex justify-content-between align-items-start">
                                                <div className="flex-grow-1">
                                                    <div className="d-flex align-items-center mb-1">
                                                        {getTypeIcon(notification.type)}
                                                        <h6 className="mb-0 ms-2">{notification.title}</h6>
                                                        {getPriorityBadge(notification.priority)}
                                                    </div>
                                                    <p className="mb-1 text-muted small">
                                                        {notification.message}
                                                    </p>
                                                    <div className="d-flex justify-content-between align-items-center">
                                                        <small className="text-muted">
                                                            {notification.parent && (
                                                                <>
                                                                    <PersonFill className="me-1" />
                                                                    {notification.parent.first_name} {notification.parent.last_name}
                                                                </>
                                                            )}
                                                            {notification.student && (
                                                                <>
                                                                    {' - '}
                                                                    <BookHalf className="me-1" />
                                                                    {notification.student.first_name} {notification.student.last_name}
                                                                </>
                                                            )}
                                                        </small>
                                                        <div>
                                                            {!notification.is_read && (
                                                                <Badge bg="warning" className="me-2">Non lu</Badge>
                                                            )}
                                                            {notification.whatsapp_sent && (
                                                                <Badge bg="success" className="me-2" title={`WhatsApp envoyé le ${new Date(notification.whatsapp_sent_at).toLocaleString('fr-FR')}`}>
                                                                    📱 WhatsApp
                                                                </Badge>
                                                            )}
                                                            {notification.whatsapp_sent === false && notification.whatsapp_sent_at && (
                                                                <Badge bg="danger" className="me-2" title="Échec envoi WhatsApp">
                                                                    📱 ❌
                                                                </Badge>
                                                            )}
                                                            <Button
                                                                variant="link"
                                                                size="sm"
                                                                className="text-danger p-0"
                                                                onClick={() => handleDelete(notification.id)}
                                                            >
                                                                <Trash />
                                                            </Button>
                                                        </div>
                                                    </div>
                                                    <small className="text-muted">
                                                        {new Date(notification.created_at).toLocaleDateString('fr-FR')}
                                                    </small>
                                                </div>
                                            </div>
                                        </ListGroup.Item>
                                    ))
                                )}
                            </ListGroup>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
            
            {/* Modal de confirmation */}
            <Modal show={showConfirm} onHide={() => setShowConfirm(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Confirmer l'envoi</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <p>Êtes-vous sûr de vouloir envoyer cette notification ?</p>
                    {pendingSubmit && (
                        <Alert variant={pendingSubmit.priority === 'urgent' ? 'danger' : 'info'}>
                            <strong>Destinataires :</strong> {
                                pendingSubmit.send_to_all ? 'Tous les parents' :
                                pendingSubmit.send_to_class ? 'Parents de la classe sélectionnée' :
                                pendingSubmit.parent_id ? 'Parent sélectionné' :
                                pendingSubmit.student_id ? 'Parents de l\'élève sélectionné' : 'Non défini'
                            }
                        </Alert>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowConfirm(false)}>
                        Annuler
                    </Button>
                    <Button variant="primary" onClick={confirmSend}>
                        <Send className="me-2" />
                        Confirmer l'envoi
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Modal pour notification d'absence rapide */}
            <Modal show={showAbsenceModal} onHide={() => setShowAbsenceModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        <ExclamationTriangleFill className="me-2 text-warning" />
                        Notification d'absence - {absenceType === 'course' ? 'Cours spécifique' : 'Établissement'}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        {/* Sélection de l'élève */}
                        <Form.Group className="mb-3">
                            <Form.Label>Élève absent *</Form.Label>
                            <Form.Select
                                value={selectedStudent}
                                onChange={(e) => {
                                    setSelectedStudent(e.target.value);
                                    // Générer automatiquement le message quand un élève est sélectionné
                                    if (e.target.value && (absenceType === 'establishment' || selectedSubject)) {
                                        const student = students.find(s => s.id === e.target.value);
                                        const { message } = generateAbsenceMessage(student, absenceType, selectedSubject);
                                        setCustomMessage(message);
                                    }
                                }}
                                required
                            >
                                <option value="">-- Sélectionner un élève --</option>
                                {students.map(student => (
                                    <option key={student.id} value={student.id}>
                                        {student.name} - {student.class}
                                    </option>
                                ))}
                            </Form.Select>
                        </Form.Group>

                        {/* Sélection de la matière (si absence en cours) */}
                        {absenceType === 'course' && (
                            <Form.Group className="mb-3">
                                <Form.Label>Matière/Cours *</Form.Label>
                                <Form.Select
                                    value={selectedSubject}
                                    onChange={(e) => {
                                        setSelectedSubject(e.target.value);
                                        // Générer automatiquement le message
                                        if (e.target.value && selectedStudent) {
                                            const student = students.find(s => s.id === selectedStudent);
                                            const { message } = generateAbsenceMessage(student, absenceType, e.target.value);
                                            setCustomMessage(message);
                                        }
                                    }}
                                    required
                                >
                                    <option value="">-- Sélectionner une matière --</option>
                                    {subjects.map(subject => (
                                        <option key={subject} value={subject}>
                                            {subject}
                                        </option>
                                    ))}
                                </Form.Select>
                            </Form.Group>
                        )}

                        {/* Aperçu du message automatique */}
                        {selectedStudent && (absenceType === 'establishment' || selectedSubject) && (
                            <Alert variant="info">
                                <h6>📝 Aperçu du message automatique :</h6>
                                <small>
                                    <strong>Titre :</strong> {generateAbsenceMessage(
                                        students.find(s => s.id === selectedStudent), 
                                        absenceType, 
                                        selectedSubject
                                    ).title}
                                </small>
                            </Alert>
                        )}

                        {/* Message personnalisé */}
                        <Form.Group className="mb-3">
                            <Form.Label>Message personnalisé</Form.Label>
                            <Form.Control
                                as="textarea"
                                rows={6}
                                value={customMessage}
                                onChange={(e) => setCustomMessage(e.target.value)}
                                placeholder="Le message sera généré automatiquement, mais vous pouvez le personnaliser ici..."
                            />
                            <Form.Text className="text-muted">
                                💡 Un message professionnel sera généré automatiquement. Vous pouvez le modifier si nécessaire.
                                <br />
                                📱 Le message sera envoyé via le système interne ET WhatsApp.
                            </Form.Text>
                        </Form.Group>
                    </Form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowAbsenceModal(false)}>
                        <i className="bi bi-x-lg me-1"></i>
                        Annuler
                    </Button>
                    <Button 
                        variant="warning" 
                        onClick={sendAbsenceNotification}
                        disabled={loading || !selectedStudent || (absenceType === 'course' && !selectedSubject)}
                    >
                        {loading ? (
                            <>
                                <Spinner animation="border" size="sm" className="me-2" />
                                Envoi en cours...
                            </>
                        ) : (
                            <>
                                <Send className="me-2" />
                                Envoyer notification d'absence
                            </>
                        )}
                    </Button>
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default ParentNotifications;