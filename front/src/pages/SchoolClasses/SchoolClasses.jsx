import React, { useState, useEffect } from 'react';
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
    Building,
    Book,
    People,
    Layers
} from 'react-bootstrap-icons';

// Components
import { Card, Button, Input, Alert, LoadingSpinner, Modal } from '../../components/UI';
import { secureApiEndpoints } from '../../utils/apiMigration';

// Hooks
import { useAuth } from '../../hooks/useAuth';

const SchoolClasses = () => {
    const { user } = useAuth();
    const [classes, setClasses] = useState([]);
    const [sections, setSections] = useState([]);
    const [levels, setLevels] = useState([]);
    const [dashboardStats, setDashboardStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    
    // Filters and search
    const [searchTerm, setSearchTerm] = useState('');
    const [filterActive, setFilterActive] = useState('all');
    const [filterSection, setFilterSection] = useState('all');
    const [filterLevel, setFilterLevel] = useState('all');
    const [viewMode, setViewMode] = useState('grid');
    
    // Modal states
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedClass, setSelectedClass] = useState(null);
    
    // Form data
    const [formData, setFormData] = useState({
        name: '',
        level_id: '',
        description: '',
        is_active: true
    });

    // Load data on component mount
    useEffect(() => {
        loadClasses();
        loadSections();
        loadLevels();
        loadDashboard();
    }, []);

    const loadClasses = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.schoolClasses.getAll();
            if (response.success) {
                setClasses(response.data);
            } else {
                setError(response.message || 'Erreur lors du chargement des classes');
            }
        } catch (error) {
            setError('Erreur lors du chargement des classes');
            console.error('Error loading classes:', error);
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

    const loadLevels = async () => {
        try {
            const response = await secureApiEndpoints.levels.getAll();
            if (response.success) {
                setLevels(response.data);
            }
        } catch (error) {
            console.error('Error loading levels:', error);
        }
    };

    const loadDashboard = async () => {
        try {
            const response = await secureApiEndpoints.schoolClasses.getDashboard();
            if (response.success) {
                setDashboardStats(response.data);
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            setError('');
            setSuccess('');

            const response = selectedClass 
                ? await secureApiEndpoints.schoolClasses.update(selectedClass.id, formData)
                : await secureApiEndpoints.schoolClasses.create(formData);

            if (response.success) {
                setSuccess(response.message);
                resetForm();
                setShowAddModal(false);
                setShowEditModal(false);
                loadClasses();
                loadDashboard();
            } else {
                setError(response.message || 'Erreur lors de la sauvegarde');
            }
        } catch (error) {
            setError('Erreur lors de la sauvegarde');
            console.error('Error saving class:', error);
        }
    };

    const handleDelete = async () => {
        if (!selectedClass) return;

        try {
            setError('');
            const response = await secureApiEndpoints.schoolClasses.delete(selectedClass.id);
            
            if (response.success) {
                setSuccess(response.message);
                setShowDeleteModal(false);
                setSelectedClass(null);
                loadClasses();
                loadDashboard();
            } else {
                setError(response.message || 'Erreur lors de la suppression');
            }
        } catch (error) {
            setError('Erreur lors de la suppression');
            console.error('Error deleting class:', error);
        }
    };

    const handleToggleStatus = async (schoolClass) => {
        try {
            const response = await secureApiEndpoints.schoolClasses.toggleStatus(schoolClass.id);
            if (response.success) {
                setSuccess(response.message);
                loadClasses();
                loadDashboard();
            } else {
                setError(response.message || 'Erreur lors de la mise à jour');
            }
        } catch (error) {
            setError('Erreur lors de la mise à jour du statut');
            console.error('Error toggling status:', error);
        }
    };

    const resetForm = () => {
        setFormData({
            name: '',
            level_id: '',
            description: '',
            is_active: true
        });
        setSelectedClass(null);
    };

    const openEditModal = (schoolClass) => {
        setSelectedClass(schoolClass);
        setFormData({
            name: schoolClass.name,
            level_id: schoolClass.level_id.toString(),
            description: schoolClass.description || '',
            is_active: schoolClass.is_active
        });
        setShowEditModal(true);
    };

    const openDeleteModal = (schoolClass) => {
        setSelectedClass(schoolClass);
        setShowDeleteModal(true);
    };

    // Filter classes
    const filteredClasses = classes.filter(schoolClass => {
        const matchesSearch = schoolClass.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            (schoolClass.description && schoolClass.description.toLowerCase().includes(searchTerm.toLowerCase())) ||
                            (schoolClass.level && schoolClass.level.name.toLowerCase().includes(searchTerm.toLowerCase())) ||
                            (schoolClass.level && schoolClass.level.section && schoolClass.level.section.name.toLowerCase().includes(searchTerm.toLowerCase()));
        
        const matchesFilter = filterActive === 'all' || 
                            (filterActive === 'active' && schoolClass.is_active) ||
                            (filterActive === 'inactive' && !schoolClass.is_active);

        const matchesSection = filterSection === 'all' || 
                              (schoolClass.level && schoolClass.level.section_id.toString() === filterSection);

        const matchesLevel = filterLevel === 'all' || 
                           schoolClass.level_id.toString() === filterLevel;
        
        return matchesSearch && matchesFilter && matchesSection && matchesLevel;
    });

    // Get available levels based on section filter
    const getAvailableLevels = () => {
        if (filterSection === 'all') return levels;
        return levels.filter(level => level.section_id.toString() === filterSection);
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-96">
                <LoadingSpinner text="Chargement des classes..." size="lg" />
            </div>
        );
    }

    return (
        <div className="p-6 bg-gray-50 min-h-screen">
            {/* Header */}
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 mb-2">
                        Gestion des Classes
                    </h1>
                    <p className="text-gray-600">
                        Bienvenue {user?.name} - Gérez les classes de l'établissement
                    </p>
                </div>
                <Button
                    onClick={() => {
                        resetForm();
                        setShowAddModal(true);
                    }}
                    className="flex items-center gap-2"
                >
                    <Plus size={16} />
                    Nouvelle Classe
                </Button>
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
                <div className="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Classes</p>
                                <p className="text-2xl font-bold text-blue-600">
                                    {dashboardStats.stats.total_classes}
                                </p>
                            </div>
                            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <Building className="text-blue-600" size={24} />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Classes Actives</p>
                                <p className="text-2xl font-bold text-green-600">
                                    {dashboardStats.stats.active_classes}
                                </p>
                            </div>
                            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <ToggleOn className="text-green-600" size={24} />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Classes Inactives</p>
                                <p className="text-2xl font-bold text-red-600">
                                    {dashboardStats.stats.inactive_classes}
                                </p>
                            </div>
                            <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <ToggleOff className="text-red-600" size={24} />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Avec Étudiants</p>
                                <p className="text-2xl font-bold text-purple-600">
                                    {dashboardStats.stats.classes_with_students}
                                </p>
                            </div>
                            <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <People className="text-purple-600" size={24} />
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Séries</p>
                                <p className="text-2xl font-bold text-orange-600">
                                    {dashboardStats.stats.total_series}
                                </p>
                            </div>
                            <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <Layers className="text-orange-600" size={24} />
                            </div>
                        </div>
                    </Card>
                </div>
            )}

            {/* Filters and Search */}
            <Card className="p-6 mb-8">
                <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div className="flex flex-col md:flex-row gap-4 flex-1">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={16} />
                            <Input
                                type="text"
                                placeholder="Rechercher une classe..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                        
                        <select
                            value={filterActive}
                            onChange={(e) => setFilterActive(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="all">Toutes les classes</option>
                            <option value="active">Classes actives</option>
                            <option value="inactive">Classes inactives</option>
                        </select>

                        <select
                            value={filterSection}
                            onChange={(e) => {
                                setFilterSection(e.target.value);
                                setFilterLevel('all'); // Reset level filter when section changes
                            }}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="all">Toutes les sections</option>
                            {sections.map(section => (
                                <option key={section.id} value={section.id.toString()}>
                                    {section.name}
                                </option>
                            ))}
                        </select>

                        <select
                            value={filterLevel}
                            onChange={(e) => setFilterLevel(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="all">Tous les niveaux</option>
                            {getAvailableLevels().map(level => (
                                <option key={level.id} value={level.id.toString()}>
                                    {level.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            variant={viewMode === 'grid' ? 'primary' : 'outline'}
                            onClick={() => setViewMode('grid')}
                            className="p-2"
                        >
                            <Grid size={16} />
                        </Button>
                        <Button
                            variant={viewMode === 'list' ? 'primary' : 'outline'}
                            onClick={() => setViewMode('list')}
                            className="p-2"
                        >
                            <List size={16} />
                        </Button>
                    </div>
                </div>
            </Card>

            {/* Classes List/Grid */}
            {filteredClasses.length === 0 ? (
                <Card className="p-8 text-center">
                    <p className="text-gray-500 mb-4">Aucune classe trouvée</p>
                    <Button
                        onClick={() => {
                            resetForm();
                            setShowAddModal(true);
                        }}
                        className="flex items-center gap-2 mx-auto"
                    >
                        <Plus size={16} />
                        Créer la première classe
                    </Button>
                </Card>
            ) : viewMode === 'grid' ? (
                // Vue en grille
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    {filteredClasses.map((schoolClass) => (
                        <Card key={schoolClass.id} className="p-4 hover:shadow-md transition-shadow duration-200">
                            <div className="flex justify-between items-start mb-3">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    {schoolClass.name}
                                </h3>
                                <div className="flex items-center gap-1">
                                    <span className={`px-2 py-1 text-xs rounded-full ${
                                        schoolClass.is_active 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-red-100 text-red-800'
                                    }`}>
                                        {schoolClass.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </div>
                            
                            <div className="mb-3 space-y-1">
                                <p className="text-sm text-gray-600">
                                    Section: <span className="font-medium text-blue-600">
                                        {schoolClass.level?.section?.name || 'N/A'}
                                    </span>
                                </p>
                                <p className="text-sm text-gray-600">
                                    Niveau: <span className="font-medium text-purple-600">
                                        {schoolClass.level?.name || 'N/A'}
                                    </span>
                                </p>
                                <p className="text-sm text-gray-600">
                                    Séries: <span className="font-medium text-orange-600">
                                        {schoolClass.series?.length || 0}
                                    </span>
                                </p>
                                {schoolClass.description && (
                                    <p className="text-gray-600 text-sm mt-2">
                                        {schoolClass.description}
                                    </p>
                                )}
                            </div>
                            
                            <div className="flex justify-end items-center">
                                <div className="flex gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleToggleStatus(schoolClass)}
                                        title={schoolClass.is_active ? 'Désactiver' : 'Activer'}
                                    >
                                        {schoolClass.is_active ? <ToggleOn size={16} /> : <ToggleOff size={16} />}
                                    </Button>
                                    
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openEditModal(schoolClass)}
                                        title="Modifier"
                                    >
                                        <Pencil size={16} />
                                    </Button>
                                    
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteModal(schoolClass)}
                                        className="text-red-600 hover:text-red-700"
                                        title="Supprimer"
                                    >
                                        <Trash size={16} />
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    ))}
                </div>
            ) : (
                // Vue en liste
                <div className="space-y-4">
                    {filteredClasses.map((schoolClass) => (
                        <Card key={schoolClass.id} className="p-4 hover:shadow-md transition-shadow duration-200">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-4 flex-1">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-1">
                                            <h3 className="text-lg font-semibold text-gray-900">
                                                {schoolClass.name}
                                            </h3>
                                            <span className={`px-2 py-1 text-xs rounded-full ${
                                                schoolClass.is_active 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-red-100 text-red-800'
                                            }`}>
                                                {schoolClass.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                            <span className="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded">
                                                {schoolClass.level?.section?.name || 'N/A'}
                                            </span>
                                            <span className="text-xs text-purple-600 bg-purple-100 px-2 py-1 rounded">
                                                {schoolClass.level?.name || 'N/A'}
                                            </span>
                                            <span className="text-xs text-orange-600 bg-orange-100 px-2 py-1 rounded">
                                                {schoolClass.series?.length || 0} série{schoolClass.series?.length !== 1 ? 's' : ''}
                                            </span>
                                        </div>
                                        {schoolClass.description && (
                                            <p className="text-gray-600 text-sm">
                                                {schoolClass.description}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                
                                <div className="flex gap-1 ml-4">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleToggleStatus(schoolClass)}
                                        title={schoolClass.is_active ? 'Désactiver' : 'Activer'}
                                    >
                                        {schoolClass.is_active ? <ToggleOn size={16} /> : <ToggleOff size={16} />}
                                    </Button>
                                    
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openEditModal(schoolClass)}
                                        title="Modifier"
                                    >
                                        <Pencil size={16} />
                                    </Button>
                                    
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openDeleteModal(schoolClass)}
                                        className="text-red-600 hover:text-red-700"
                                        title="Supprimer"
                                    >
                                        <Trash size={16} />
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    ))}
                </div>
            )}

            {/* Add/Edit Modal */}
            <Modal
                isOpen={showAddModal || showEditModal}
                onClose={() => {
                    setShowAddModal(false);
                    setShowEditModal(false);
                    resetForm();
                }}
                title={selectedClass ? 'Modifier la Classe' : 'Nouvelle Classe'}
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    <Input
                        label="Nom de la classe"
                        type="text"
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        required
                        placeholder="Ex: 6ème A, CP1 B..."
                    />

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Niveau *
                        </label>
                        <select
                            value={formData.level_id}
                            onChange={(e) => setFormData({ ...formData, level_id: e.target.value })}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="">Sélectionner un niveau</option>
                            {levels.filter(l => l.is_active).map(level => (
                                <option key={level.id} value={level.id.toString()}>
                                    {level.section?.name} - {level.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            placeholder="Description de la classe..."
                            rows={3}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div className="flex items-center">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={formData.is_active}
                            onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                            className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <label htmlFor="is_active" className="ml-2 text-sm text-gray-700">
                            Classe active
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                setShowAddModal(false);
                                setShowEditModal(false);
                                resetForm();
                            }}
                        >
                            Annuler
                        </Button>
                        <Button type="submit">
                            {selectedClass ? 'Modifier' : 'Créer'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Delete Modal */}
            <Modal
                isOpen={showDeleteModal}
                onClose={() => {
                    setShowDeleteModal(false);
                    setSelectedClass(null);
                }}
                title="Confirmer la suppression"
            >
                <div className="space-y-4">
                    <p className="text-gray-700">
                        Êtes-vous sûr de vouloir supprimer la classe <strong>{selectedClass?.name}</strong> ?
                    </p>
                    <p className="text-sm text-red-600">
                        Cette action est irréversible et ne sera possible que si la classe ne contient aucun étudiant.
                    </p>
                    
                    <div className="flex justify-end gap-2 pt-4">
                        <Button
                            variant="outline"
                            onClick={() => {
                                setShowDeleteModal(false);
                                setSelectedClass(null);
                            }}
                        >
                            Annuler
                        </Button>
                        <Button
                            variant="danger"
                            onClick={handleDelete}
                        >
                            Supprimer
                        </Button>
                    </div>
                </div>
            </Modal>
        </div>
    );
};

export default SchoolClasses;