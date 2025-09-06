import React, { useState, useEffect, useCallback } from 'react';
import { 
    Plus, 
    Search, 
    Eye, 
    Pencil, 
    Trash,
    ToggleOff,
    ToggleOn,
    Grid,
    List,
    Filter,
    BookFill
} from 'react-bootstrap-icons';

// Components
import { Card, Input, Alert, LoadingSpinner, Modal } from '../../components/UI';
import ImportExportButton from '../../components/ImportExportButton';
import { secureApiEndpoints } from '../../utils/apiMigration';

// Hooks
import { useAuth } from '../../hooks/useAuth';
import { Button as BootstrapButton } from 'react-bootstrap';

const Levels = () => {
    const { user } = useAuth();
    const [levels, setLevels] = useState([]);
    const [sections, setSections] = useState([]);
    const [dashboardStats, setDashboardStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    
    // Filters and search
    const [searchTerm, setSearchTerm] = useState('');
    const [filterActive, setFilterActive] = useState('all');
    const [filterSection, setFilterSection] = useState('all');
    const [viewMode, setViewMode] = useState('grid');
    
    // Modal states
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedLevel, setSelectedLevel] = useState(null);
    
    // Form data
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        section_id: '',
        is_active: true,
        order: 0
    });

    // Load data on component mount
    useEffect(() => {
        loadLevels();
        loadSections();
    }, []);

    // Update dashboard stats when levels change
    useEffect(() => {
        if (levels.length > 0) {
            loadDashboard();
        }
    }, [levels]);

    const loadLevels = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.levels.getAll();
            if (response.success) {
                setLevels(response.data);
            } else {
                setError(response.message || 'Erreur lors du chargement des niveaux');
            }
        } catch (error) {
            setError('Erreur lors du chargement des niveaux');
            console.error('Error loading levels:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadSections = async () => {
        try {
            const response = await secureApiEndpoints.sections.getAll();
            if (response.success) {
                setSections(response.data);
            }
        } catch (error) {
            console.error('Error loading sections:', error);
        }
    };

    const loadDashboard = async () => {
        try {
            // Calcul des statistiques à partir des données locales
            const stats = {
                total_levels: levels.length,
                active_levels: levels.filter(l => l.is_active).length,
                inactive_levels: levels.filter(l => !l.is_active).length,
                sections_count: [...new Set(levels.map(l => l.section_id))].length
            };
            setDashboardStats({ stats });
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    };

    const resetForm = () => {
        setFormData({
            name: '',
            description: '',
            section_id: '',
            is_active: true,
            order: 0
        });
        setSelectedLevel(null);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            setError('');
            setSuccess('');

            const response = selectedLevel 
                ? await secureApiEndpoints.levels.update(selectedLevel.id, formData)
                : await secureApiEndpoints.levels.create(formData);

            if (response.success) {
                setSuccess(response.message || (selectedLevel ? 'Niveau mis à jour avec succès' : 'Niveau créé avec succès'));
                resetForm();
                setShowAddModal(false);
                setShowEditModal(false);
                loadLevels();
            } else {
                setError(response.message || 'Erreur lors de la sauvegarde');
            }
        } catch (error) {
            setError('Erreur lors de la sauvegarde du niveau');
            console.error('Error saving level:', error);
        }
    };

    const handleEditLevel = (level) => {
        setSelectedLevel(level);
        setFormData({
            name: level.name,
            description: level.description || '',
            section_id: level.section_id,
            is_active: level.is_active,
            order: level.order || 0
        });
        setShowEditModal(true);
    };

    const handleDeleteLevel = (level) => {
        setSelectedLevel(level);
        setShowDeleteModal(true);
    };

    const confirmDelete = async () => {
        try {
            setError('');
            setSuccess('');

            const response = await secureApiEndpoints.levels.delete(selectedLevel.id);
            if (response.success) {
                setSuccess(response.message || 'Niveau supprimé avec succès');
                setShowDeleteModal(false);
                setSelectedLevel(null);
                loadLevels();
            } else {
                setError(response.message || 'Erreur lors de la suppression');
            }
        } catch (error) {
            setError('Erreur lors de la suppression du niveau');
            console.error('Error deleting level:', error);
        }
    };

    const toggleStatus = async (level) => {
        try {
            const response = await secureApiEndpoints.levels.update(level.id, {
                ...level,
                is_active: !level.is_active
            });
            if (response.success) {
                loadLevels();
                setSuccess(`Niveau ${level.is_active ? 'désactivé' : 'activé'} avec succès`);
            } else {
                setError(response.message || 'Erreur lors de la modification du statut');
            }
        } catch (error) {
            setError('Erreur lors de la modification du statut');
            console.error('Error toggling status:', error);
        }
    };

    // Filter levels based on search and filters
    const filteredLevels = levels.filter(level => {
        const matchesSearch = level.name.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesActive = filterActive === 'all' || 
            (filterActive === 'active' && level.is_active) || 
            (filterActive === 'inactive' && !level.is_active);
        const matchesSection = filterSection === 'all' || level.section_id === parseInt(filterSection);
        
        return matchesSearch && matchesActive && matchesSection;
    });

    const getSectionName = (sectionId) => {
        const section = sections.find(s => s.id === sectionId);
        return section ? section.name : 'Section inconnue';
    };

    if (loading) {
        return (
            <div className="d-flex align-items-center justify-content-center" style={{minHeight: "24rem"}}>
                <LoadingSpinner text="Chargement des niveaux..." size="lg" />
            </div>
        );
    }

    return (
        <div className="levels-page">
            {/* Header */}
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 className="h2 fw-bold text-dark mb-2">
                        Gestion des Niveaux
                    </h1>
                    <p className="text-muted">
                        Bienvenue {user?.name} - Gérez les niveaux de l'établissement
                    </p>
                </div>
                <div className="d-flex gap-2">
                    <ImportExportButton
                        title="Niveaux"
                        apiBasePath="/api/levels"
                        onImportSuccess={loadLevels}
                        templateFileName="template_levels.csv"
                    />
                    
                    <BootstrapButton
                        onClick={() => {
                            resetForm();
                            setShowAddModal(true);
                        }}
                        variant="primary"
                        className="d-flex align-items-center"
                    >
                        <Plus size={16} className="me-2" />
                        Nouveau Niveau
                    </BootstrapButton>
                </div>
            </div>

            {/* Alerts */}
            {error && (
                <Alert variant="error" className="mb-4" dismissible onDismiss={() => setError('')}>
                    {error}
                </Alert>
            )}
            {success && (
                <Alert variant="success" className="mb-4" dismissible onDismiss={() => setSuccess('')}>
                    {success}
                </Alert>
            )}

            {/* Dashboard Stats */}
            {dashboardStats && (
                <div className="row mb-4">
                    <div className="col-md-3">
                        <div className="card p-3">
                            <div className="d-flex justify-content-between align-items-center">
                                <div>
                                    <p className="small text-muted mb-1">Total Niveaux</p>
                                    <h3 className="fw-bold text-primary mb-0">
                                        {dashboardStats.stats.total_levels}
                                    </h3>
                                </div>
                                <div className="bg-primary bg-opacity-10 rounded p-2">
                                    <BookFill className="text-primary" size={24} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-md-3">
                        <div className="card p-3">
                            <div className="d-flex justify-content-between align-items-center">
                                <div>
                                    <p className="small text-muted mb-1">Niveaux Actifs</p>
                                    <h3 className="fw-bold text-success mb-0">
                                        {dashboardStats.stats.active_levels}
                                    </h3>
                                </div>
                                <div className="bg-success bg-opacity-10 rounded p-2">
                                    <ToggleOn className="text-success" size={24} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-md-3">
                        <div className="card p-3">
                            <div className="d-flex justify-content-between align-items-center">
                                <div>
                                    <p className="small text-muted mb-1">Niveaux Inactifs</p>
                                    <h3 className="fw-bold text-danger mb-0">
                                        {dashboardStats.stats.inactive_levels}
                                    </h3>
                                </div>
                                <div className="bg-danger bg-opacity-10 rounded p-2">
                                    <ToggleOff className="text-danger" size={24} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-md-3">
                        <div className="card p-3">
                            <div className="d-flex justify-content-between align-items-center">
                                <div>
                                    <p className="small text-muted mb-1">Sections Utilisées</p>
                                    <h3 className="fw-bold mb-0" style={{color: "#6f42c1"}}>
                                        {dashboardStats.stats.sections_count}
                                    </h3>
                                </div>
                                <div className="rounded p-2" style={{backgroundColor: "rgba(111, 66, 193, 0.1)"}}>
                                    <List style={{color: "#6f42c1"}} size={24} />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Filtres */}
            <div className="row mb-4">
                <div className="col-12">
                    <div className="card">
                        <div className="card-body">
                            <div className="row g-3">
                                <div className="col-md-3">
                                    <label className="form-label">Rechercher</label>
                                    <div className="position-relative">
                                        <Search className="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" size={16} />
                                        <input
                                            type="text"
                                            className="form-control ps-5"
                                            placeholder="Rechercher un niveau..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="col-md-3">
                                    <label className="form-label">Section</label>
                                    <select
                                        className="form-select"
                                        value={filterSection}
                                        onChange={(e) => setFilterSection(e.target.value)}
                                    >
                                        <option value="all">Toutes les sections</option>
                                        {sections.map(section => (
                                            <option key={section.id} value={section.id}>
                                                {section.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="col-md-3">
                                    <label className="form-label">Statut</label>
                                    <select
                                        className="form-select"
                                        value={filterActive}
                                        onChange={(e) => setFilterActive(e.target.value)}
                                    >
                                        <option value="all">Tous les niveaux</option>
                                        <option value="active">Niveaux actifs</option>
                                        <option value="inactive">Niveaux inactifs</option>
                                    </select>
                                </div>
                                <div className="col-md-3">
                                    <label className="form-label">Vue</label>
                                    <div className="btn-group d-flex" role="group">
                                        <button
                                            type="button"
                                            className={`btn ${viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                            onClick={() => setViewMode('grid')}
                                        >
                                            <Grid size={16} />
                                        </button>
                                        <button
                                            type="button"
                                            className={`btn ${viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                            onClick={() => setViewMode('list')}
                                        >
                                            <List size={16} />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Contenu */}
            {viewMode === 'grid' ? (
                <div className="row">
                    {filteredLevels.length === 0 ? (
                        <div className="col-12">
                            <div className="card">
                                <div className="card-body text-center py-5">
                                    <BookFill size={48} className="text-muted mb-3" />
                                    <h5 className="text-muted">Aucun niveau trouvé</h5>
                                    <p className="text-muted">Ajoutez votre premier niveau pour commencer</p>
                                </div>
                            </div>
                        </div>
                    ) : (
                        filteredLevels.map((level) => (
                            <div key={level.id} className="col-md-6 col-lg-4 mb-4">
                                <div className="card h-100">
                                    <div className="card-body">
                                        <div className="d-flex justify-content-between align-items-start mb-3">
                                            <h5 className="card-title mb-0 text-truncate" title={level.name}>
                                                {level.name}
                                            </h5>
                                            <div className="dropdown">
                                                <button
                                                    className="btn btn-sm btn-outline-secondary"
                                                    type="button"
                                                    data-bs-toggle="dropdown"
                                                >
                                                    <Eye size={14} />
                                                </button>
                                                <ul className="dropdown-menu">
                                                    <li>
                                                        <button
                                                            className="dropdown-item"
                                                            onClick={() => handleEditLevel(level)}
                                                        >
                                                            <Pencil size={14} className="me-2" />
                                                            Modifier
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button
                                                            className="dropdown-item"
                                                            onClick={() => toggleStatus(level)}
                                                        >
                                                            {level.is_active ? (
                                                                <>
                                                                    <ToggleOff size={14} className="me-2" />
                                                                    Désactiver
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <ToggleOn size={14} className="me-2" />
                                                                    Activer
                                                                </>
                                                            )}
                                                        </button>
                                                    </li>
                                                    <li><hr className="dropdown-divider" /></li>
                                                    <li>
                                                        <button
                                                            className="dropdown-item text-danger"
                                                            onClick={() => handleDeleteLevel(level)}
                                                        >
                                                            <Trash size={14} className="me-2" />
                                                            Supprimer
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <div className="mb-2">
                                            <small className="text-muted">Section: {getSectionName(level.section_id)}</small>
                                        </div>
                                        
                                        {level.description && (
                                            <p className="card-text text-muted small mb-3">
                                                {level.description.length > 100 
                                                    ? level.description.substring(0, 100) + '...'
                                                    : level.description
                                                }
                                            </p>
                                        )}
                                        
                                        <div className="d-flex justify-content-between align-items-center mt-auto">
                                            <small className="text-muted">Ordre: {level.order}</small>
                                            <span className={`badge ${level.is_active ? 'bg-success' : 'bg-danger'}`}>
                                                {level.is_active ? 'Actif' : 'Inactif'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            ) : (
                <div className="row">
                    <div className="col-12">
                        <div className="card">
                            <div className="card-body">
                                {filteredLevels.length === 0 ? (
                                    <div className="text-center py-5">
                                        <BookFill size={48} className="text-muted mb-3" />
                                        <h5 className="text-muted">Aucun niveau trouvé</h5>
                                        <p className="text-muted">Ajoutez votre premier niveau pour commencer</p>
                                    </div>
                                ) : (
                                    <div className="table-responsive">
                                        <table className="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nom</th>
                                                    <th>Section</th>
                                                    <th>Description</th>
                                                    <th>Ordre</th>
                                                    <th>Statut</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {filteredLevels.map((level) => (
                                                    <tr key={level.id}>
                                                        <td className="fw-bold">{level.name}</td>
                                                        <td className="text-muted">{getSectionName(level.section_id)}</td>
                                                        <td className="text-muted">
                                                            {level.description ? (
                                                                level.description.length > 50 
                                                                    ? level.description.substring(0, 50) + '...'
                                                                    : level.description
                                                            ) : '-'}
                                                        </td>
                                                        <td>{level.order}</td>
                                                        <td>
                                                            <span className={`badge ${level.is_active ? 'bg-success' : 'bg-danger'}`}>
                                                                {level.is_active ? 'Actif' : 'Inactif'}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div className="btn-group btn-group-sm">
                                                                <button
                                                                    className="btn btn-outline-primary"
                                                                    onClick={() => handleEditLevel(level)}
                                                                    title="Modifier"
                                                                >
                                                                    <Pencil size={14} />
                                                                </button>
                                                                <button
                                                                    className={`btn ${level.is_active ? 'btn-outline-warning' : 'btn-outline-success'}`}
                                                                    onClick={() => toggleStatus(level)}
                                                                    title={level.is_active ? 'Désactiver' : 'Activer'}
                                                                >
                                                                    {level.is_active ? <ToggleOff size={14} /> : <ToggleOn size={14} />}
                                                                </button>
                                                                <button
                                                                    className="btn btn-outline-danger"
                                                                    onClick={() => handleDeleteLevel(level)}
                                                                    title="Supprimer"
                                                                >
                                                                    <Trash size={14} />
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal Ajouter/Modifier */}
            <Modal
                isOpen={showAddModal || showEditModal}
                onClose={() => {
                    setShowAddModal(false);
                    setShowEditModal(false);
                    resetForm();
                }}
                title={selectedLevel ? 'Modifier le niveau' : 'Nouveau niveau'}
                size="lg"
            >
                <form onSubmit={handleSubmit}>
                    <div className="row g-3">
                        <div className="col-md-6">
                            <label className="form-label">Nom du niveau *</label>
                            <input
                                type="text"
                                className="form-control"
                                value={formData.name}
                                onChange={(e) => setFormData({...formData, name: e.target.value})}
                                placeholder="Ex: Cours Préparatoire 1"
                                required
                            />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label">Section *</label>
                            <select
                                className="form-select"
                                value={formData.section_id}
                                onChange={(e) => setFormData({...formData, section_id: e.target.value})}
                                required
                            >
                                <option value="">Sélectionner une section</option>
                                {sections.map(section => (
                                    <option key={section.id} value={section.id}>
                                        {section.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-12">
                            <label className="form-label">Description</label>
                            <textarea
                                className="form-control"
                                value={formData.description}
                                onChange={(e) => setFormData({...formData, description: e.target.value})}
                                placeholder="Description optionnelle du niveau"
                                rows={3}
                            />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label">Ordre d'affichage</label>
                            <input
                                type="number"
                                className="form-control"
                                value={formData.order}
                                onChange={(e) => setFormData({...formData, order: parseInt(e.target.value) || 0})}
                                min="0"
                            />
                        </div>
                        <div className="col-md-6 d-flex align-items-end">
                            <div className="form-check">
                                <input
                                    type="checkbox"
                                    className="form-check-input"
                                    id="is_active"
                                    checked={formData.is_active}
                                    onChange={(e) => setFormData({...formData, is_active: e.target.checked})}
                                />
                                <label className="form-check-label" htmlFor="is_active">
                                    Niveau actif
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div className="d-flex gap-2 mt-4">
                        <BootstrapButton
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                setShowAddModal(false);
                                setShowEditModal(false);
                                resetForm();
                            }}
                        >
                            Annuler
                        </BootstrapButton>
                        <BootstrapButton type="submit" variant="primary">
                            {selectedLevel ? 'Modifier' : 'Créer'}
                        </BootstrapButton>
                    </div>
                </form>
            </Modal>

            {/* Modal Supprimer */}
            <Modal
                isOpen={showDeleteModal}
                onClose={() => {
                    setShowDeleteModal(false);
                    setSelectedLevel(null);
                }}
                title="Confirmer la suppression"
            >
                <div className="mb-4">
                    <p>Êtes-vous sûr de vouloir supprimer le niveau <strong>{selectedLevel?.name}</strong> ?</p>
                    <div className="alert alert-warning">
                        <small>Cette action est irréversible.</small>
                    </div>
                </div>
                
                <div className="d-flex gap-2">
                    <BootstrapButton
                        type="button"
                        variant="secondary"
                        onClick={() => {
                            setShowDeleteModal(false);
                            setSelectedLevel(null);
                        }}
                    >
                        Annuler
                    </BootstrapButton>
                    <BootstrapButton
                        type="button"
                        variant="danger"
                        onClick={confirmDelete}
                    >
                        Supprimer
                    </BootstrapButton>
                </div>
            </Modal>
        </div>
    );
};

export default Levels;