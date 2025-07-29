import React, { useState, useEffect } from 'react';
import { 
    Person, 
    PencilSquare, 
    Key, 
    Shield, 
    Envelope, 
    Calendar,
    Building,
    Check,
    X,
    Eye,
    EyeSlash,
    Telephone,
    GeoAlt
} from 'react-bootstrap-icons';
import { useAuth } from '../../hooks/useAuth';
import { secureApiEndpoints } from '../../utils/apiMigration';
import Swal from 'sweetalert2';

const UserProfile = () => {
    const { user, updateUser } = useAuth();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [activeTab, setActiveTab] = useState('profile');
    
    // School year data
    const [availableYears, setAvailableYears] = useState([]);
    const [currentWorkingYear, setCurrentWorkingYear] = useState(null);
    const [yearLoading, setYearLoading] = useState(false);
    
    // Profile form data
    const [profileData, setProfileData] = useState({
        name: '',
        email: '',
        phone: '',
        address: ''
    });
    
    // Password form data
    const [passwordData, setPasswordData] = useState({
        current_password: '',
        new_password: '',
        confirm_password: ''
    });
    
    const [showPasswords, setShowPasswords] = useState({
        current: false,
        new: false,
        confirm: false
    });
    
    const [isEditing, setIsEditing] = useState(false);

    useEffect(() => {
        if (user) {
            setProfileData({
                name: user.name || '',
                email: user.email || '',
                phone: user.phone || '',
                address: user.address || ''
            });
            
            // Load school year data for admin and accountant
            if (user.role === 'admin' || user.role === 'accountant') {
                loadSchoolYearData();
            }
        }
    }, [user]);

    const loadSchoolYearData = async () => {
        try {
            setYearLoading(true);
            
            // Load available years
            const yearsResponse = await secureApiEndpoints.schoolYears.getActiveYears();
            setAvailableYears(yearsResponse.data || []);
            
            // Load current working year
            const workingYearResponse = await secureApiEndpoints.schoolYears.getUserWorkingYear();
            setCurrentWorkingYear(workingYearResponse.data || null);
        } catch (error) {
            console.error('Error loading school year data:', error);
        } finally {
            setYearLoading(false);
        }
    };

    const handleProfileSubmit = async (e) => {
        e.preventDefault();
        
        try {
            setLoading(true);
            setError('');
            
            const response = await secureApiEndpoints.auth.updateProfile(profileData);
            
            if (response.success) {
                setSuccess('Profil mis à jour avec succès');
                setIsEditing(false);
                
                // Update user in auth context
                updateUser(response.data.user);
                
                Swal.fire({
                    title: 'Succès',
                    text: 'Votre profil a été mis à jour avec succès',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                setError(response.message || 'Erreur lors de la mise à jour du profil');
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            setError('Erreur lors de la mise à jour du profil');
        } finally {
            setLoading(false);
        }
    };

    const handlePasswordSubmit = async (e) => {
        e.preventDefault();
        
        // Validation
        if (passwordData.new_password !== passwordData.confirm_password) {
            setError('Les nouveaux mots de passe ne correspondent pas');
            return;
        }
        
        if (passwordData.new_password.length < 6) {
            setError('Le nouveau mot de passe doit contenir au moins 6 caractères');
            return;
        }
        
        try {
            setLoading(true);
            setError('');
            
            const response = await secureApiEndpoints.auth.changePassword({
                current_password: passwordData.current_password,
                new_password: passwordData.new_password
            });
            
            if (response.success) {
                setSuccess('Mot de passe modifié avec succès');
                setPasswordData({
                    current_password: '',
                    new_password: '',
                    confirm_password: ''
                });
                
                Swal.fire({
                    title: 'Succès',
                    text: 'Votre mot de passe a été modifié avec succès',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                setError(response.message || 'Erreur lors de la modification du mot de passe');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            setError('Erreur lors de la modification du mot de passe');
        } finally {
            setLoading(false);
        }
    };

    const handleWorkingYearChange = async (yearId) => {
        try {
            setYearLoading(true);
            setError('');
            
            const response = await secureApiEndpoints.schoolYears.setUserWorkingYear(yearId);
            
            if (response.success) {
                setCurrentWorkingYear(response.data);
                setSuccess('Année de travail mise à jour avec succès');
                
                Swal.fire({
                    title: 'Succès',
                    text: 'Votre année de travail a été mise à jour avec succès',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                setError(response.message || 'Erreur lors de la mise à jour de l\'année de travail');
            }
        } catch (error) {
            console.error('Error updating working year:', error);
            setError('Erreur lors de la mise à jour de l\'année de travail');
        } finally {
            setYearLoading(false);
        }
    };

    const togglePasswordVisibility = (field) => {
        setShowPasswords(prev => ({
            ...prev,
            [field]: !prev[field]
        }));
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'Non disponible';
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    if (!user) {
        return (
            <div className="container-fluid py-4">
                <div className="text-center py-5">
                    <div className="spinner-border" role="status">
                        <span className="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid py-4">
            {/* Header */}
            <div className="row mb-4">
                <div className="col-12">
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 className="h4 mb-1">Mon Profil</h2>
                            <p className="text-muted mb-0">
                                Gérez vos informations personnelles et vos paramètres de sécurité
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Alerts */}
            {error && (
                <div className="row mb-3">
                    <div className="col-12">
                        <div className="alert alert-danger alert-dismissible fade show" role="alert">
                            {error}
                            <button 
                                type="button" 
                                className="btn-close" 
                                onClick={() => setError('')}
                                aria-label="Close"
                            ></button>
                        </div>
                    </div>
                </div>
            )}
            {success && (
                <div className="row mb-3">
                    <div className="col-12">
                        <div className="alert alert-success alert-dismissible fade show" role="alert">
                            {success}
                            <button 
                                type="button" 
                                className="btn-close" 
                                onClick={() => setSuccess('')}
                                aria-label="Close"
                            ></button>
                        </div>
                    </div>
                </div>
            )}

            <div className="row">
                {/* Sidebar */}
                <div className="col-md-3 mb-4">
                    <div className="card">
                        <div className="card-body text-center">
                            <div className="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                                 style={{ width: '80px', height: '80px' }}>
                                <Person className="text-primary" size={40} />
                            </div>
                            <h5 className="card-title">{user.name}</h5>
                            <p className="text-muted small mb-2">{user.email}</p>
                            <div className="d-flex justify-content-center gap-1 mb-2">
                                <span className={`badge ${user.is_active ? 'bg-success' : 'bg-secondary'}`}>
                                    {user.is_active ? 'Actif' : 'Inactif'}
                                </span>
                                <span className="badge bg-primary">
                                    {user.role === 'admin' ? 'Admin' : 
                                     user.role === 'accountant' ? 'Comptable' : 
                                     user.role === 'teacher' ? 'Enseignant' : 'Utilisateur'}
                                </span>
                            </div>
                            {user.username && (
                                <small className="text-muted">@{user.username}</small>
                            )}
                        </div>
                    </div>

                    {/* Navigation */}
                    <div className="list-group mt-3">
                        <button
                            className={`list-group-item list-group-item-action d-flex align-items-center ${
                                activeTab === 'profile' ? 'active' : ''
                            }`}
                            onClick={() => setActiveTab('profile')}
                        >
                            <Person size={16} className="me-2" />
                            Informations personnelles
                        </button>
                        <button
                            className={`list-group-item list-group-item-action d-flex align-items-center ${
                                activeTab === 'security' ? 'active' : ''
                            }`}
                            onClick={() => setActiveTab('security')}
                        >
                            <Shield size={16} className="me-2" />
                            Sécurité
                        </button>
                        {(user.role === 'admin' || user.role === 'accountant') && (
                            <button
                                className={`list-group-item list-group-item-action d-flex align-items-center ${
                                    activeTab === 'working-year' ? 'active' : ''
                                }`}
                                onClick={() => setActiveTab('working-year')}
                            >
                                <Calendar size={16} className="me-2" />
                                Année de travail
                            </button>
                        )}
                    </div>
                </div>

                {/* Main Content */}
                <div className="col-md-9">
                    {activeTab === 'profile' && (
                        <div className="card">
                            <div className="card-header d-flex justify-content-between align-items-center">
                                <h5 className="mb-0">
                                    <Person size={20} className="me-2" />
                                    Informations personnelles
                                </h5>
                                {!isEditing && (
                                    <button
                                        className="btn btn-outline-primary btn-sm"
                                        onClick={() => setIsEditing(true)}
                                    >
                                        <PencilSquare size={14} className="me-1" />
                                        Modifier
                                    </button>
                                )}
                            </div>
                            <div className="card-body">
                                {isEditing ? (
                                    <form onSubmit={handleProfileSubmit}>
                                        <div className="row">
                                            <div className="col-md-6">
                                                <div className="mb-3">
                                                    <label className="form-label">Nom complet *</label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        value={profileData.name}
                                                        onChange={(e) => setProfileData({
                                                            ...profileData,
                                                            name: e.target.value
                                                        })}
                                                        required
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-md-6">
                                                <div className="mb-3">
                                                    <label className="form-label">Email *</label>
                                                    <input
                                                        type="email"
                                                        className="form-control"
                                                        value={profileData.email}
                                                        onChange={(e) => setProfileData({
                                                            ...profileData,
                                                            email: e.target.value
                                                        })}
                                                        required
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div className="row">
                                            <div className="col-md-6">
                                                <div className="mb-3">
                                                    <label className="form-label">Téléphone</label>
                                                    <input
                                                        type="tel"
                                                        className="form-control"
                                                        value={profileData.phone}
                                                        onChange={(e) => setProfileData({
                                                            ...profileData,
                                                            phone: e.target.value
                                                        })}
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-md-6">
                                                <div className="mb-3">
                                                    <label className="form-label">Adresse</label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        value={profileData.address}
                                                        onChange={(e) => setProfileData({
                                                            ...profileData,
                                                            address: e.target.value
                                                        })}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div className="d-flex gap-2">
                                            <button
                                                type="submit"
                                                className="btn btn-primary"
                                                disabled={loading}
                                            >
                                                {loading ? (
                                                    <>
                                                        <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                                        Mise à jour...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Check size={16} className="me-1" />
                                                        Enregistrer
                                                    </>
                                                )}
                                            </button>
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary"
                                                onClick={() => {
                                                    setIsEditing(false);
                                                    setProfileData({
                                                        name: user.name || '',
                                                        email: user.email || '',
                                                        phone: user.phone || '',
                                                        address: user.address || ''
                                                    });
                                                }}
                                            >
                                                <X size={16} className="me-1" />
                                                Annuler
                                            </button>
                                        </div>
                                    </form>
                                ) : (
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label text-muted">Nom complet</label>
                                                <p className="fw-medium">{user.name}</p>
                                            </div>
                                            <div className="mb-3">
                                                <label className="form-label text-muted">Téléphone</label>
                                                <p className="fw-medium">
                                                    <Telephone size={16} className="me-1" />
                                                    {user.phone || 'Non renseigné'}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label text-muted">Email</label>
                                                <p className="fw-medium">
                                                    <Envelope size={16} className="me-1" />
                                                    {user.email}
                                                </p>
                                            </div>
                                            <div className="mb-3">
                                                <label className="form-label text-muted">Adresse</label>
                                                <p className="fw-medium">
                                                    <GeoAlt size={16} className="me-1" />
                                                    {user.address || 'Non renseignée'}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-12">
                                            <hr />
                                            <div className="row">
                                                <div className="col-md-4">
                                                    <div className="mb-3">
                                                        <label className="form-label text-muted">Date de création</label>
                                                        <p className="fw-medium">
                                                            <Calendar size={16} className="me-1" />
                                                            {formatDate(user.created_at)}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="col-md-4">
                                                    <div className="mb-3">
                                                        <label className="form-label text-muted">Rôle</label>
                                                        <p className="fw-medium">
                                                            <Building size={16} className="me-1" />
                                                            {user.role === 'admin' ? 'Administrateur' : 
                                                             user.role === 'accountant' ? 'Comptable' : 
                                                             user.role === 'teacher' ? 'Enseignant' : 'Utilisateur'}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="col-md-4">
                                                    <div className="mb-3">
                                                        <label className="form-label text-muted">Nom d'utilisateur</label>
                                                        <p className="fw-medium">
                                                            <Person size={16} className="me-1" />
                                                            {user.username || 'Non défini'}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            {/* Informations supplémentaires selon le rôle */}
                                            {(user.role === 'admin' || user.role === 'accountant') && (
                                                <div className="row mt-3">
                                                    <div className="col-12">
                                                        <div className="alert alert-info">
                                                            <strong>Informations d'accès :</strong>
                                                            <ul className="mb-0 mt-2">
                                                                <li>Accès complet aux fonctionnalités du système</li>
                                                                <li>Gestion des années scolaires</li>
                                                                <li>Configuration de l'année de travail personnalisée</li>
                                                                {user.role === 'admin' && <li>Administration complète du système</li>}
                                                                {user.role === 'accountant' && <li>Gestion financière et comptable</li>}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                            
                                            {user.role === 'teacher' && (
                                                <div className="row mt-3">
                                                    <div className="col-12">
                                                        <div className="alert alert-success">
                                                            <strong>Profil Enseignant :</strong>
                                                            <ul className="mb-0 mt-2">
                                                                <li>Gestion des élèves de vos classes</li>
                                                                <li>Suivi pédagogique et évaluations</li>
                                                                <li>Accès aux outils d'enseignement</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                            
                                            {/* Afficher l'année de travail si définie */}
                                            {(user.role === 'admin' || user.role === 'accountant') && currentWorkingYear && (
                                                <div className="row mt-3">
                                                    <div className="col-12">
                                                        <div className="card bg-light">
                                                            <div className="card-body">
                                                                <h6 className="card-title mb-2">
                                                                    <Calendar size={18} className="me-2 text-primary" />
                                                                    Année de travail actuelle
                                                                </h6>
                                                                <p className="card-text mb-0">
                                                                    <strong>{currentWorkingYear.name}</strong>
                                                                    <span className="text-muted ms-2">
                                                                        ({new Date(currentWorkingYear.start_date).toLocaleDateString('fr-FR')} - 
                                                                        {new Date(currentWorkingYear.end_date).toLocaleDateString('fr-FR')})
                                                                    </span>
                                                                </p>
                                                                <small className="text-muted">
                                                                    Cette année est utilisée pour l'affichage des données et l'ajout de nouveaux éléments.
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {activeTab === 'security' && (
                        <div className="card">
                            <div className="card-header">
                                <h5 className="mb-0">
                                    <Key size={20} className="me-2" />
                                    Changer le mot de passe
                                </h5>
                            </div>
                            <div className="card-body">
                                <form onSubmit={handlePasswordSubmit}>
                                    <div className="mb-3">
                                        <label className="form-label">Mot de passe actuel *</label>
                                        <div className="input-group">
                                            <input
                                                type={showPasswords.current ? "text" : "password"}
                                                className="form-control"
                                                value={passwordData.current_password}
                                                onChange={(e) => setPasswordData({
                                                    ...passwordData,
                                                    current_password: e.target.value
                                                })}
                                                required
                                            />
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary"
                                                onClick={() => togglePasswordVisibility('current')}
                                            >
                                                {showPasswords.current ? <EyeSlash size={16} /> : <Eye size={16} />}
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div className="mb-3">
                                        <label className="form-label">Nouveau mot de passe *</label>
                                        <div className="input-group">
                                            <input
                                                type={showPasswords.new ? "text" : "password"}
                                                className="form-control"
                                                value={passwordData.new_password}
                                                onChange={(e) => setPasswordData({
                                                    ...passwordData,
                                                    new_password: e.target.value
                                                })}
                                                required
                                                minLength="6"
                                            />
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary"
                                                onClick={() => togglePasswordVisibility('new')}
                                            >
                                                {showPasswords.new ? <EyeSlash size={16} /> : <Eye size={16} />}
                                            </button>
                                        </div>
                                        <small className="text-muted">Au moins 6 caractères</small>
                                    </div>
                                    
                                    <div className="mb-3">
                                        <label className="form-label">Confirmer le nouveau mot de passe *</label>
                                        <div className="input-group">
                                            <input
                                                type={showPasswords.confirm ? "text" : "password"}
                                                className="form-control"
                                                value={passwordData.confirm_password}
                                                onChange={(e) => setPasswordData({
                                                    ...passwordData,
                                                    confirm_password: e.target.value
                                                })}
                                                required
                                            />
                                            <button
                                                type="button"
                                                className="btn btn-outline-secondary"
                                                onClick={() => togglePasswordVisibility('confirm')}
                                            >
                                                {showPasswords.confirm ? <EyeSlash size={16} /> : <Eye size={16} />}
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <button
                                        type="submit"
                                        className="btn btn-primary"
                                        disabled={loading}
                                    >
                                        {loading ? (
                                            <>
                                                <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                                Modification...
                                            </>
                                        ) : (
                                            <>
                                                <Key size={16} className="me-1" />
                                                Changer le mot de passe
                                            </>
                                        )}
                                    </button>
                                </form>
                            </div>
                        </div>
                    )}

                    {activeTab === 'working-year' && (user.role === 'admin' || user.role === 'accountant') && (
                        <div className="card">
                            <div className="card-header">
                                <h5 className="mb-0">
                                    <Calendar size={20} className="me-2" />
                                    Année de travail
                                </h5>
                            </div>
                            <div className="card-body">
                                <div className="row">
                                    <div className="col-md-8">
                                        <p className="text-muted mb-3">
                                            Choisissez l'année scolaire dans laquelle vous souhaitez travailler. 
                                            Cette sélection sera mémorisée et utilisée dans toutes les fonctionnalités du système.
                                        </p>
                                        
                                        <div className="mb-3">
                                            <label className="form-label">Année scolaire actuelle</label>
                                            <div className="p-3 bg-light rounded">
                                                {currentWorkingYear ? (
                                                    <div className="d-flex align-items-center">
                                                        <Calendar size={18} className="me-2 text-primary" />
                                                        <strong>{currentWorkingYear.name}</strong>
                                                        <span className="ms-2 text-muted">
                                                            ({new Date(currentWorkingYear.start_date).toLocaleDateString('fr-FR')} - 
                                                            {new Date(currentWorkingYear.end_date).toLocaleDateString('fr-FR')})
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted">Aucune année sélectionnée</span>
                                                )}
                                            </div>
                                        </div>
                                        
                                        <div className="mb-3">
                                            <label className="form-label">Changer d'année de travail</label>
                                            <select 
                                                className="form-select" 
                                                value={currentWorkingYear?.id || ''}
                                                onChange={(e) => handleWorkingYearChange(e.target.value)}
                                                disabled={yearLoading}
                                            >
                                                <option value="">Sélectionner une année...</option>
                                                {availableYears.map(year => (
                                                    <option key={year.id} value={year.id}>
                                                        {year.name} ({new Date(year.start_date).toLocaleDateString('fr-FR')} - 
                                                        {new Date(year.end_date).toLocaleDateString('fr-FR')})
                                                        {year.is_current && ' (Année courante)'}
                                                    </option>
                                                ))}
                                            </select>
                                            {yearLoading && (
                                                <div className="mt-2">
                                                    <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                                    Mise à jour en cours...
                                                </div>
                                            )}
                                        </div>
                                        
                                        <div className="alert alert-info">
                                            <strong>Note:</strong> Le changement d'année de travail affectera:
                                            <ul className="mb-0 mt-2">
                                                <li>Les statistiques et rapports affichés</li>
                                                <li>Les listes d'élèves et leurs données</li>
                                                <li>Les calculs de paiements et frais</li>
                                                <li>Toutes les fonctionnalités liées aux données académiques</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div className="col-md-4">
                                        <div className="bg-light p-3 rounded">
                                            <h6 className="fw-bold mb-3">Années disponibles</h6>
                                            {availableYears.length > 0 ? (
                                                <ul className="list-unstyled">
                                                    {availableYears.map(year => (
                                                        <li key={year.id} className="mb-2">
                                                            <div className="d-flex align-items-center">
                                                                <Calendar size={14} className="me-2 text-muted" />
                                                                <span className="small">
                                                                    {year.name}
                                                                    {year.is_current && (
                                                                        <span className="badge bg-primary ms-1" style={{ fontSize: '0.6em' }}>
                                                                            Courante
                                                                        </span>
                                                                    )}
                                                                </span>
                                                            </div>
                                                        </li>
                                                    ))}
                                                </ul>
                                            ) : (
                                                <p className="text-muted small">Aucune année disponible</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default UserProfile;