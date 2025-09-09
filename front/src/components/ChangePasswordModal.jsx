import React, { useState } from 'react';
import { Modal, Button, Form, Alert, InputGroup } from 'react-bootstrap';
import { Eye, EyeSlash, Lock } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../utils/apiMigration';
import Swal from 'sweetalert2';

const ChangePasswordModal = ({ show, onHide }) => {
    const [formData, setFormData] = useState({
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [showPasswords, setShowPasswords] = useState({
        current: false,
        new: false,
        confirm: false
    });

    const handleClose = () => {
        setFormData({
            current_password: '',
            new_password: '',
            new_password_confirmation: ''
        });
        setError('');
        setShowPasswords({ current: false, new: false, confirm: false });
        onHide();
    };

    const togglePasswordVisibility = (field) => {
        setShowPasswords(prev => ({
            ...prev,
            [field]: !prev[field]
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');

        // Validation côté client
        if (!formData.current_password) {
            setError('Le mot de passe actuel est requis');
            return;
        }

        if (!formData.new_password) {
            setError('Le nouveau mot de passe est requis');
            return;
        }

        if (formData.new_password.length < 8) {
            setError('Le nouveau mot de passe doit contenir au moins 8 caractères');
            return;
        }

        if (formData.new_password !== formData.new_password_confirmation) {
            setError('La confirmation du mot de passe ne correspond pas');
            return;
        }

        if (formData.current_password === formData.new_password) {
            setError('Le nouveau mot de passe doit être différent du mot de passe actuel');
            return;
        }

        try {
            setLoading(true);
            
            const response = await secureApiEndpoints.users.changePassword(formData);

            if (response.success) {
                Swal.fire({
                    title: 'Succès !',
                    text: 'Votre mot de passe a été modifié avec succès',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
                handleClose();
            } else {
                setError(response.message || 'Erreur lors du changement de mot de passe');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            setError('Erreur de connexion. Veuillez réessayer.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal show={show} onHide={handleClose} centered>
            <Modal.Header closeButton>
                <Modal.Title>
                    <Lock className="me-2 text-primary" />
                    Changer le mot de passe
                </Modal.Title>
            </Modal.Header>
            
            <Form onSubmit={handleSubmit}>
                <Modal.Body>
                    {error && (
                        <Alert variant="danger" className="mb-3">
                            {error}
                        </Alert>
                    )}

                    <Form.Group className="mb-3">
                        <Form.Label>Mot de passe actuel *</Form.Label>
                        <InputGroup>
                            <Form.Control
                                type={showPasswords.current ? "text" : "password"}
                                value={formData.current_password}
                                onChange={(e) => setFormData(prev => ({
                                    ...prev,
                                    current_password: e.target.value
                                }))}
                                required
                                disabled={loading}
                            />
                            <Button
                                variant="outline-secondary"
                                onClick={() => togglePasswordVisibility('current')}
                                disabled={loading}
                            >
                                {showPasswords.current ? <EyeSlash /> : <Eye />}
                            </Button>
                        </InputGroup>
                    </Form.Group>

                    <Form.Group className="mb-3">
                        <Form.Label>Nouveau mot de passe *</Form.Label>
                        <InputGroup>
                            <Form.Control
                                type={showPasswords.new ? "text" : "password"}
                                value={formData.new_password}
                                onChange={(e) => setFormData(prev => ({
                                    ...prev,
                                    new_password: e.target.value
                                }))}
                                required
                                minLength={8}
                                disabled={loading}
                            />
                            <Button
                                variant="outline-secondary"
                                onClick={() => togglePasswordVisibility('new')}
                                disabled={loading}
                            >
                                {showPasswords.new ? <EyeSlash /> : <Eye />}
                            </Button>
                        </InputGroup>
                        <Form.Text className="text-muted">
                            Le mot de passe doit contenir au moins 8 caractères
                        </Form.Text>
                    </Form.Group>

                    <Form.Group className="mb-3">
                        <Form.Label>Confirmer le nouveau mot de passe *</Form.Label>
                        <InputGroup>
                            <Form.Control
                                type={showPasswords.confirm ? "text" : "password"}
                                value={formData.new_password_confirmation}
                                onChange={(e) => setFormData(prev => ({
                                    ...prev,
                                    new_password_confirmation: e.target.value
                                }))}
                                required
                                disabled={loading}
                            />
                            <Button
                                variant="outline-secondary"
                                onClick={() => togglePasswordVisibility('confirm')}
                                disabled={loading}
                            >
                                {showPasswords.confirm ? <EyeSlash /> : <Eye />}
                            </Button>
                        </InputGroup>
                    </Form.Group>

                    <div className="bg-light p-3 rounded">
                        <h6 className="mb-2">Conseils pour un mot de passe sécurisé :</h6>
                        <ul className="small mb-0">
                            <li>Au moins 8 caractères</li>
                            <li>Mélangez majuscules et minuscules</li>
                            <li>Incluez des chiffres</li>
                            <li>Ajoutez des caractères spéciaux (!, @, #, etc.)</li>
                            <li>Évitez les mots courants ou informations personnelles</li>
                        </ul>
                    </div>
                </Modal.Body>
                
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleClose} disabled={loading}>
                        Annuler
                    </Button>
                    <Button 
                        variant="primary" 
                        type="submit" 
                        disabled={loading}
                    >
                        {loading ? 'Modification...' : 'Modifier le mot de passe'}
                    </Button>
                </Modal.Footer>
            </Form>
        </Modal>
    );
};

export default ChangePasswordModal;