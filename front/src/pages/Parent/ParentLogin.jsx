import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
    PersonFill, 
    LockFill, 
    EyeFill, 
    EyeSlashFill,
    Phone
} from 'react-bootstrap-icons';
import logo from '../../images/logo.png'

// Components
import { Card, Button, Input, Alert, LoadingSpinner } from '../../components/UI';

// Utils
import { secureApiEndpoints } from '../../utils/apiMigration';

const ParentLogin = () => {
    const navigate = useNavigate();
    
    // États
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    
    // Données du formulaire
    const [formData, setFormData] = useState({
        phone: '',
        pin_code: ''
    });

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        setLoading(true);

        try {
            // Les parents se connectent uniquement avec téléphone + PIN
            const loginData = {
                phone: formData.phone,
                pin_code: formData.pin_code
            };

            const response = await secureApiEndpoints.parent.login(loginData);

            if (response.success) {
                // Stocker les informations de connexion
                localStorage.setItem('parentToken', response.data.token);
                localStorage.setItem('parentData', JSON.stringify(response.data.parent));
                localStorage.setItem('childrenCount', response.data.children_count);
                
                // Rediriger vers le tableau de bord
                navigate('/parent/dashboard');
            }

        } catch (error) {
            console.error('Erreur de connexion:', error);
            setError(
                error.response?.data?.message || 
                'Erreur de connexion. Veuillez vérifier vos identifiants.'
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-violet-50 via-white to-primary-violet-100 p-4">
            {/* Background Pattern */}
            <div className="absolute inset-0 overflow-hidden">
                <div className="absolute -top-40 -right-40 w-80 h-80 rounded-full bg-primary-violet-100 opacity-50"></div>
                <div className="absolute -bottom-40 -left-40 w-80 h-80 rounded-full bg-primary-violet-50 opacity-50"></div>
            </div>

            <div className="relative w-full max-w-md">
                {/* Logo and Branding */}
                <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-primary-violet to-primary-violet-dark shadow-lg mb-4">
                        <img 
                            src={logo} 
                            style={{objectFit: "cover", width: "70%", height: "70%"}}
                            alt="GSBPL Logo" 
                            className="w-12 h-12 rounded-full"
                        />
                    </div>
                    <h1 className="text-3xl font-bold text-gray-800 mb-2">
                        CPBD
                    </h1>
                    <p className="text-gray-600">
                        Système de Gestion de Collège
                    </p>
                </div>

                {/* Login Card */}
                <Card className="shadow-xl border-0 backdrop-blur-sm bg-white/95">
                    <Card.Content className="p-8">
                        <div className="text-center mb-6">
                            <h2 className="text-2xl font-semibold text-gray-800 mb-2">
                                Espace Parent
                            </h2>
                            <p className="text-gray-600">
                                Suivi scolaire de vos enfants
                            </p>
                        </div>

                        {error && (
                            <Alert 
                                variant="error" 
                                className="mb-6"
                                dismissible
                                onDismiss={() => setError(null)}
                            >
                                {error}
                            </Alert>
                        )}


                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Numéro de téléphone */}
                            <Input
                                label="Numéro de téléphone"
                                type="tel"
                                name="phone"
                                value={formData.phone}
                                onChange={handleInputChange}
                                placeholder="6XXXXXXXX"
                                icon={<Phone size={18} />}
                                iconPosition="left"
                                required
                                disabled={loading}
                            />


                            {/* Code PIN */}
                            <Input
                                label="Code PIN (4 chiffres)"
                                type="text"
                                name="pin_code"
                                value={formData.pin_code}
                                onChange={handleInputChange}
                                placeholder="••••"
                                maxLength="4"
                                pattern="[0-9]{4}"
                                required
                                disabled={loading}
                                className="text-center text-2xl"
                            />

                            <Button
                                type="submit"
                                variant="primary"
                                fullWidth
                                size="lg"
                                disabled={loading || !formData.phone || !formData.pin_code}
                                loading={loading}
                            >
                                {loading ? 'Connexion...' : 'Se connecter'}
                            </Button>
                        </form>

                        <div className="mt-6 text-center">
                            <p className="text-sm text-gray-600">
                                Mot de passe oublié?{' '}
                                <button 
                                    className="text-primary-violet hover:text-primary-violet-dark font-medium transition-colors"
                                    disabled={loading}
                                >
                                    Contacter l'administration
                                </button>
                            </p>
                        </div>

                        {/* Séparateur */}
                        <div className="mt-6 mb-6 flex items-center">
                            <div className="flex-1 border-t border-gray-200"></div>
                            <span className="px-4 text-sm text-gray-500 bg-white">ou</span>
                            <div className="flex-1 border-t border-gray-200"></div>
                        </div>

                        {/* Retour au login administratif */}
                        <div className="text-center">
                            <button
                                type="button"
                                onClick={() => navigate('/login')}
                                className="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center"
                                disabled={loading}
                            >
                                <PersonFill className="mr-2" size={20} />
                                Accès Staff & Enseignants
                            </button>
                            <p className="text-xs text-gray-500 mt-2">
                                Administration et personnel enseignant
                            </p>
                        </div>
                    </Card.Content>
                </Card>

                {/* Footer */}
                <div className="text-center mt-8">
                    <p className="text-sm text-gray-500">
                        ©2025 Collège Polyvalent Bilingue de Douala. Tous droits réservés.
                    </p>
                </div>
            </div>

            {/* Loading Overlay */}
            {loading && (
                <div className="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
                    <div className="bg-white rounded-xl p-6 shadow-xl">
                        <LoadingSpinner text="Connexion en cours..." />
                    </div>
                </div>
            )}
        </div>
    );
};

export default ParentLogin;