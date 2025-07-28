import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
    PersonFill, 
    LockFill, 
    EyeFill, 
    EyeSlashFill,
    // ShieldLockFill
} from 'react-bootstrap-icons';
import logo from '../images/logo.png'

// Components
import { Card, Button, Input, Alert, LoadingSpinner } from '../components/UI';

// Utils
import { authTraductions } from '../local/login';
// import { host } from '../utils/fetch';
import { getLang } from '../utils/lang';
import { apiEndpoints } from '../utils/api';

const Login = ({ setUser }) => {
    const [data, setData] = useState({
        username: '',
        password: '',
        
        remember: false
    });
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const navigate = useNavigate();

    const handleLogin = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const result = await apiEndpoints.login(data);

            if (result.success) {
                setUser(true);
                sessionStorage.user = result.token;
                sessionStorage.stat = result.status;

                // Redirect based on user role
                if (result.status === 'ad') {
                    navigate('/');
                } else if (result.status === 'comp') {
                    navigate('/class-comp');
                } else {
                    sessionStorage.classId = result.classId;
                    navigate(`/students/${result.classId}`);
                }
            } else {
                setError(result.message || 'Erreur de connexion');
            }
        } catch (error) {
            setError(error.message || 'Une erreur est survenue lors de la connexion à la base de données.');
            console.error('Login error:', error);
        }

        setLoading(false);
    };

    const handleInputChange = (field, value) => {
        setData(prev => ({
            ...prev,
            [field]: value
        }));
        // Clear error when user starts typing
        if (error) setError('');
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
                        {/* <ShieldLockFill className="text-white" size={24} /> */}
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
                                {authTraductions[getLang()].login}
                            </h2>
                            <p className="text-gray-600">
                                Connectez-vous à votre compte
                            </p>
                        </div>

                        {error && (
                            <Alert 
                                variant="error" 
                                className="mb-6"
                                dismissible
                                onDismiss={() => setError('')}
                            >
                                {error}
                            </Alert>
                        )}

                        <form onSubmit={handleLogin} className="space-y-6">
                            <Input
                                label={authTraductions[getLang()].emailOrPseudo}
                                type="text"
                                value={data.username}
                                onChange={(e) => handleInputChange('username', e.target.value)}
                                placeholder="Entrez votre nom d'utilisateur"
                                icon={<PersonFill size={18} />}
                                iconPosition="left"
                                required
                                disabled={loading}
                            />

                            <Input
                                label={authTraductions[getLang()].password}
                                type={showPassword ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) => handleInputChange('password', e.target.value)}
                                placeholder="Entrez votre mot de passe"
                                icon={<LockFill size={18} />}
                                iconPosition="left"
                                required
                                disabled={loading}
                                containerClassName="relative"
                            >
                                <button
                                    type="button"
                                    className="absolute right-3 top-0 transform -translate-y-1/2 "
                                    onClick={() => setShowPassword(!showPassword)}
                                    disabled={loading}
                                >
                                    {showPassword ? (
                                        <EyeSlashFill size={18} />
                                    ) : (
                                        <EyeFill size={18} />
                                    )}
                                </button>
                            </Input>

                            <div className="flex items-center justify-between">
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) => handleInputChange('remember', e.target.checked)}
                                        className="w-4 h-4 text-primary-violet border-gray-300 rounded focus:ring-primary-violet focus:ring-2"
                                        disabled={loading}
                                    />
                                    <span className="ml-2 text-sm text-gray-600">
                                        Se souvenir de moi
                                    </span>
                                </label>
                                
                                <button
                                    type="button"
                                    className="text-sm text-primary-violet hover:text-primary-violet-dark transition-colors"
                                    disabled={loading}
                                >
                                    Mot de passe oublié?
                                </button>
                            </div>

                            <Button
                                type="submit"
                                variant="primary"
                                fullWidth
                                size="lg"
                                disabled={loading || !data.username || !data.password}
                                loading={loading}
                            >
                                {loading 
                                    ? authTraductions[getLang()].logining 
                                    : authTraductions[getLang()].login
                                }
                            </Button>
                        </form>

                        <div className="mt-6 text-center">
                            <p className="text-sm text-gray-600">
                                Problème de connexion?{' '}
                                <button 
                                    className="text-primary-violet hover:text-primary-violet-dark font-medium transition-colors"
                                    disabled={loading}
                                >
                                    Contacter l'administrateur
                                </button>
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

export default Login;