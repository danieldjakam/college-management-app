import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
    Plus,
    PencilSquare,
    Trash,
    Download,
    Upload,
    Person,
    Calendar,
    GeoAlt,
    Telephone,
    Envelope,
    Search,
    Grid,
    List,
    ArrowLeft,
    SortAlphaDown,
    GripVertical
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import Swal from 'sweetalert2';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
    useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

// Composant pour les éléments sortables
const SortableStudent = ({ student, handleEdit, handleDelete }) => {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: student.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <tr ref={setNodeRef} style={style} className={isDragging ? 'dragging' : ''}>
            <td>
                <div className="d-flex align-items-center">
                    <div 
                        {...attributes} 
                        {...listeners}
                        className="drag-handle me-2"
                        style={{ cursor: 'grab' }}
                    >
                        <GripVertical size={14} className="text-muted" />
                    </div>
                    <small className="text-muted">
                        N° {student.student_number}
                    </small>
                </div>
            </td>
            <td>
                <div>
                    <div className="fw-medium">
                        {student.full_name || `${student.last_name || student.subname || ''} ${student.first_name || student.name || ''}`}
                    </div>
                </div>
            </td>
            <td>
                <div>
                    {student.date_of_birth && (
                        <div className="d-flex align-items-center small mb-1">
                            <Calendar size={12} className="me-2 flex-shrink-0" />
                            <span>{new Date(student.date_of_birth).toLocaleDateString('fr-FR')}</span>
                        </div>
                    )}
                    {student.place_of_birth && (
                        <div className="d-flex align-items-center small text-muted">
                            <GeoAlt size={12} className="me-2 flex-shrink-0" />
                            <span>{student.place_of_birth}</span>
                        </div>
                    )}
                </div>
            </td>
            <td>
                <span className={`badge ${student.gender === 'M' ? 'bg-info' : 'bg-pink'}`}>
                    {student.gender === 'M' ? 'Masculin' : 'Féminin'}
                </span>
            </td>
            <td>
                <div className="small">
                    {student.parent_name || student.father_name || '-'}
                </div>
            </td>
            <td>
                <div>
                    {student.parent_phone && (
                        <div className="d-flex align-items-center small mb-1">
                            <Telephone size={12} className="me-2 flex-shrink-0" />
                            <span>{student.parent_phone}</span>
                        </div>
                    )}
                    {student.parent_email && (
                        <div className="d-flex align-items-center small text-muted">
                            <Envelope size={12} className="me-2 flex-shrink-0" />
                            <span>{student.parent_email}</span>
                        </div>
                    )}
                </div>
            </td>
            <td>
                <div className="d-flex gap-1">
                    <button
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => handleEdit(student)}
                        title="Modifier"
                    >
                        <PencilSquare size={14} />
                    </button>
                    <button
                        className="btn btn-sm btn-outline-danger"
                        onClick={() => handleDelete(student)}
                        title="Supprimer"
                    >
                        <Trash size={14} />
                    </button>
                </div>
            </td>
        </tr>
    );
};

const SeriesStudents = () => {
    const { seriesId } = useParams();
    const navigate = useNavigate();
    
    const [students, setStudents] = useState([]);
    const [series, setSeries] = useState(null);
    const [schoolYear, setSchoolYear] = useState(null);
    const [schoolYears, setSchoolYears] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [hasCustomOrder, setHasCustomOrder] = useState(false);

    // Sensors pour le drag & drop
    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );
    
    // Filters and search
    const [searchTerm, setSearchTerm] = useState('');
    const [viewMode, setViewMode] = useState('list');
    
    // Modal states
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [selectedStudent, setSelectedStudent] = useState(null);
    
    // Form data
    const [formData, setFormData] = useState({
        first_name: '',
        last_name: '',
        date_of_birth: '',
        place_of_birth: '',
        gender: 'M',
        parent_name: '',
        parent_phone: '',
        parent_email: '',
        address: '',
        class_series_id: seriesId,
        school_year_id: ''
    });
    
    // Import data
    const [importFile, setImportFile] = useState(null);
    const [selectedSchoolYear, setSelectedSchoolYear] = useState('');

    useEffect(() => {
        loadStudents();
        loadSchoolYears();
    }, [seriesId]);

    const loadStudents = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.students.getByClassSeries(seriesId);
            
            if (response.success) {
                setStudents(response.data.students);
                setSeries(response.data.series);
                setSchoolYear(response.data.school_year);
                setFormData(prev => ({
                    ...prev,
                    school_year_id: response.data.school_year?.id || ''
                }));
                
                // Vérifier si les élèves ont un ordre personnalisé
                const hasCustom = response.data.students.some(student => student.order != null);
                setHasCustomOrder(hasCustom);
            } else {
                setError(response.message || 'Erreur lors du chargement des élèves');
            }
        } catch (error) {
            console.error('Error loading students:', error);
            setError('Erreur lors du chargement des élèves');
        } finally {
            setLoading(false);
        }
    };

    const loadSchoolYears = async () => {
        try {
            const response = await secureApiEndpoints.students.getSchoolYears();
            if (response.success) {
                setSchoolYears(response.data);
                const currentYear = response.data.find(year => year.is_current);
                if (currentYear) {
                    setSelectedSchoolYear(currentYear.id);
                }
            }
        } catch (error) {
            console.error('Error loading school years:', error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        try {
            setLoading(true);
            setError('');
            
            const submissionData = {
                ...formData,
                class_series_id: seriesId
            };

            const response = selectedStudent 
                ? await secureApiEndpoints.students.update(selectedStudent.id, submissionData)
                : await secureApiEndpoints.students.create(submissionData);

            if (response.success) {
                setSuccess(response.message || `Élève ${selectedStudent ? 'modifié' : 'créé'} avec succès`);
                resetForm();
                setShowAddModal(false);
                setShowEditModal(false);
                loadStudents();
                
                Swal.fire({
                    title: 'Succès',
                    text: response.message || `Élève ${selectedStudent ? 'modifié' : 'créé'} avec succès`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                setError(response.message || `Erreur lors de ${selectedStudent ? 'la modification' : 'la création'} de l'élève`);
            }
        } catch (error) {
            console.error('Error saving student:', error);
            setError(`Erreur lors de ${selectedStudent ? 'la modification' : 'la création'} de l'élève`);
        } finally {
            setLoading(false);
        }
    };

    const handleEdit = (student) => {
        setSelectedStudent(student);
        setFormData({
            first_name: student.first_name || '',
            last_name: student.last_name || '',
            date_of_birth: student.date_of_birth || '',
            place_of_birth: student.place_of_birth || '',
            gender: student.gender || 'M',
            parent_name: student.parent_name || '',
            parent_phone: student.parent_phone || '',
            parent_email: student.parent_email || '',
            address: student.address || '',
            class_series_id: seriesId,
            school_year_id: student.school_year_id || ''
        });
        setShowEditModal(true);
    };

    const handleDelete = async (student) => {
        const result = await Swal.fire({
            title: 'Confirmer la suppression',
            text: `Êtes-vous sûr de vouloir supprimer l'élève "${student.full_name}" ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        });

        if (result.isConfirmed) {
            try {
                const response = await secureApiEndpoints.students.delete(student.id);
                
                if (response.success) {
                    loadStudents();
                    setSuccess(response.message || 'Élève supprimé avec succès');
                    
                    Swal.fire({
                        title: 'Supprimé',
                        text: response.message || 'Élève supprimé avec succès',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    setError(response.message || 'Erreur lors de la suppression de l\'élève');
                }
            } catch (error) {
                console.error('Error deleting student:', error);
                setError('Erreur lors de la suppression de l\'élève');
            }
        }
    };

    const handleExportCsv = async () => {
        try {
            setLoading(true);
            console.log('Exporting CSV for series:', seriesId);
            
            const blob = await secureApiEndpoints.students.exportCsv(seriesId);
            
            // Créer le lien de téléchargement
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `eleves_${series?.name?.replace(/[^a-zA-Z0-9]/g, '_') || 'serie'}_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            setSuccess('Export CSV réalisé avec succès');
        } catch (error) {
            console.error('Error exporting CSV:', error);
            setError('Erreur lors de l\'export CSV: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const handleExportPdf = async () => {
        try {
            setLoading(true);
            console.log('Exporting PDF for series:', seriesId);
            
            const response = await secureApiEndpoints.students.exportPdf(seriesId);
            
            // Le backend retourne du HTML formaté pour impression
            const htmlContent = await response.text();
            
            // Créer une nouvelle fenêtre avec le HTML formaté
            const printWindow = window.open('', '_blank');
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            
            // Attendre que le contenu soit chargé puis déclencher l'impression
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 500);
            };
            
            setSuccess('Fenêtre d\'impression ouverte. Vous pouvez maintenant imprimer ou sauvegarder en PDF.');
        } catch (error) {
            console.error('Error exporting PDF:', error);
            setError('Erreur lors de l\'export PDF: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const handleImportCsv = async (e) => {
        e.preventDefault();
        
        // Validation détaillée
        if (!importFile) {
            setError('Veuillez sélectionner un fichier CSV');
            return;
        }
        
        if (!selectedSchoolYear) {
            setError('Veuillez sélectionner une année scolaire');
            return;
        }
        
        if (!seriesId) {
            setError('ID de série manquant');
            return;
        }

        // Validation du type de fichier
        const validTypes = ['text/csv', 'application/csv', 'text/plain'];
        const fileExtension = importFile.name.toLowerCase().split('.').pop();
        
        if (!validTypes.includes(importFile.type) && fileExtension !== 'csv') {
            setError('Le fichier doit être au format CSV (.csv)');
            return;
        }

        try {
            setLoading(true);
            setError(''); // Clear any previous errors
            
            const formData = new FormData();
            formData.append('file', importFile);
            formData.append('class_series_id', seriesId);
            formData.append('school_year_id', selectedSchoolYear);

            // Debug log détaillé
            console.log('Import CSV - FormData contents:', {
                file: {
                    name: importFile.name,
                    type: importFile.type,
                    size: importFile.size,
                    lastModified: importFile.lastModified
                },
                class_series_id: seriesId,
                school_year_id: selectedSchoolYear,
                seriesInfo: series?.name,
                schoolYearInfo: schoolYears.find(y => y.id == selectedSchoolYear)?.name
            });

            // Vérifier que FormData contient bien les données
            for (let [key, value] of formData.entries()) {
                console.log('FormData entry:', key, value);
            }

            const response = await secureApiEndpoints.students.importCsv(formData);
            
            if (response.success) {
                setSuccess(response.message);
                setShowImportModal(false);
                setImportFile(null);
                loadStudents();
                
                // Afficher les erreurs s'il y en a
                if (response.data.errors && response.data.errors.length > 0) {
                    Swal.fire({
                        title: 'Import terminé avec des erreurs',
                        html: `
                            <p><strong>${response.data.imported} élève(s) importé(s)</strong></p>
                            <p>Erreurs :</p>
                            <ul style="text-align: left;">
                                ${response.data.errors.map(error => `<li>${error}</li>`).join('')}
                            </ul>
                        `,
                        icon: 'warning'
                    });
                } else {
                    Swal.fire({
                        title: 'Import réussi',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } else {
                setError(response.message || 'Erreur lors de l\'import');
            }
        } catch (error) {
            console.error('Error importing CSV:', error);
            setError('Erreur lors de l\'import CSV');
        } finally {
            setLoading(false);
        }
    };

    // Fonction pour gérer le drag & drop
    const handleDragEnd = async (event) => {
        const { active, over } = event;

        if (active.id !== over.id) {
            const oldIndex = students.findIndex((student) => student.id === active.id);
            const newIndex = students.findIndex((student) => student.id === over.id);

            const newStudents = arrayMove(students, oldIndex, newIndex);
            setStudents(newStudents);
            setHasCustomOrder(true);

            // Mettre à jour l'ordre sur le serveur
            try {
                const studentsWithOrder = newStudents.map((student, index) => ({
                    id: student.id,
                    order: index + 1
                }));

                await secureApiEndpoints.students.reorder({
                    students: studentsWithOrder,
                    class_series_id: parseInt(seriesId),
                    school_year_id: schoolYear?.id
                });

                setSuccess('Ordre des élèves mis à jour avec succès');
                setTimeout(() => setSuccess(''), 3000);
            } catch (error) {
                console.error('Error reordering students:', error);
                setError('Erreur lors de la réorganisation des élèves');
                // Remettre l'ordre original en cas d'erreur
                loadStudents();
            }
        }
    };

    // Fonction pour reclasser par ordre alphabétique
    const handleSortAlphabetically = async () => {
        const result = await Swal.fire({
            title: 'Reclasser par ordre alphabétique',
            text: 'Cette action va réorganiser tous les élèves par ordre alphabétique (Nom + Prénom). Continuer ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, reclasser',
            cancelButtonText: 'Annuler'
        });

        if (result.isConfirmed) {
            try {
                setLoading(true);
                const response = await secureApiEndpoints.students.sortAlphabetically(
                    seriesId, 
                    { school_year_id: schoolYear?.id }
                );

                if (response.success) {
                    setStudents(response.data);
                    setHasCustomOrder(false);
                    setSuccess(response.message);
                    setTimeout(() => setSuccess(''), 3000);
                } else {
                    setError(response.message || 'Erreur lors du reclassement');
                }
            } catch (error) {
                console.error('Error sorting alphabetically:', error);
                setError('Erreur lors du reclassement alphabétique');
            } finally {
                setLoading(false);
            }
        }
    };

    const resetForm = () => {
        setFormData({
            first_name: '',
            last_name: '',
            date_of_birth: '',
            place_of_birth: '',
            gender: 'M',
            parent_name: '',
            parent_phone: '',
            parent_email: '',
            address: '',
            class_series_id: seriesId,
            school_year_id: schoolYear?.id || ''
        });
        setSelectedStudent(null);
    };

    const handleCloseModal = () => {
        setShowAddModal(false);
        setShowEditModal(false);
        setShowImportModal(false);
        resetForm();
        setError('');
        setImportFile(null);
    };

    // Filter students
    const filteredStudents = students.filter(student => {
        // Nom + Prénom (au lieu de Prénom Nom)
        const fullName = `${student.last_name || student.subname || ''} ${student.first_name || student.name || ''}`.toLowerCase();
        const parentName = (student.parent_name || student.father_name || '').toLowerCase();
        return fullName.includes(searchTerm.toLowerCase()) || 
               parentName.includes(searchTerm.toLowerCase()) ||
               (student.student_number || '').toLowerCase().includes(searchTerm.toLowerCase());
    });

    if (loading && students.length === 0) {
        return (
            <div className="container-fluid py-4">
                <div className="text-center py-5">
                    <div className="spinner-border" role="status">
                        <span className="visually-hidden">Chargement des élèves...</span>
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
                        <div className="d-flex align-items-center">
                            <button
                                className="btn btn-outline-secondary me-3"
                                onClick={() => navigate('/school-classes')}
                            >
                                <ArrowLeft size={16} className="me-1" />
                                Retour
                            </button>
                            <div>
                                <h2 className="h4 mb-1">
                                    Élèves - {series?.name}
                                </h2>
                                <p className="text-muted mb-0">
                                    {series?.school_class?.level?.section?.name} - {series?.school_class?.level?.name} - {series?.school_class?.name}
                                    {schoolYear && ` • Année ${schoolYear.name}`}
                                    {filteredStudents.length > 0 && ` • ${filteredStudents.length} élève${filteredStudents.length > 1 ? 's' : ''}`}
                                </p>
                            </div>
                        </div>
                        <div className="d-flex gap-2">
                            <button
                                className="btn btn-outline-success"
                                onClick={handleExportCsv}
                                title="Exporter en CSV"
                            >
                                <Download size={16} className="me-1" />
                                Export CSV
                            </button>
                            <button
                                className="btn btn-outline-danger"
                                onClick={handleExportPdf}
                                title="Exporter en PDF"
                            >
                                <Download size={16} className="me-1" />
                                Export PDF
                            </button>
                            <button
                                className="btn btn-outline-info"
                                onClick={() => setShowImportModal(true)}
                                title="Importer depuis CSV"
                            >
                                <Upload size={16} className="me-1" />
                                Import CSV
                            </button>
                            <button
                                className="btn btn-primary d-flex align-items-center gap-2"
                                onClick={() => setShowAddModal(true)}
                            >
                                <Plus size={16} />
                                Nouvel Élève
                            </button>
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

            {/* Filters */}
            <div className="row mb-4">
                <div className="col-12">
                    <div className="card">
                        <div className="card-body">
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label">Rechercher</label>
                                    <div className="position-relative">
                                        <Search className="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" size={16} />
                                        <input
                                            type="text"
                                            className="form-control ps-5"
                                            placeholder="Rechercher par nom, prénom, parent ou numéro..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="col-md-6 d-flex align-items-end">
                                    <div className="btn-group me-3" role="group">
                                        <button
                                            type="button"
                                            className={`btn ${viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                            onClick={() => setViewMode('grid')}
                                            title="Vue grille"
                                        >
                                            <Grid size={16} />
                                        </button>
                                        <button
                                            type="button"
                                            className={`btn ${viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                            onClick={() => setViewMode('list')}
                                            title="Vue liste"
                                        >
                                            <List size={16} />
                                        </button>
                                    </div>
                                    <button
                                        className="btn btn-outline-secondary me-2"
                                        onClick={() => setSearchTerm('')}
                                    >
                                        Réinitialiser
                                    </button>
                                    {/* {hasCustomOrder && ( */}
                                        <button
                                            className="btn btn-warning"
                                            onClick={handleSortAlphabetically}
                                            title="Reclasser par ordre alphabétique"
                                        >
                                            <SortAlphaDown size={16} className="me-1" />
                                            Ordre alphabétique
                                        </button>
                                    {/* )} */}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Students List */}
            <div className="row">
                <div className="col-12">
                    {filteredStudents.length === 0 ? (
                        <div className="card">
                            <div className="card-body text-center py-5">
                                <Person size={48} className="text-muted mb-3" />
                                <h5 className="text-muted">Aucun élève trouvé</h5>
                                <p className="text-muted mb-4">
                                    {searchTerm 
                                        ? 'Aucun élève ne correspond à vos critères de recherche.'
                                        : 'Commencez par ajouter le premier élève de cette série.'
                                    }
                                </p>
                                <button
                                    className="btn btn-primary"
                                    onClick={() => setShowAddModal(true)}
                                >
                                    <Plus size={16} className="me-2" />
                                    Ajouter un élève
                                </button>
                            </div>
                        </div>
                    ) : viewMode === 'grid' ? (
                        // Vue en grilles (cartes)
                        <div className="row">
                            {filteredStudents.map((student) => (
                                <div key={student.id} className="col-md-6 col-lg-4 mb-4">
                                    <div className="card h-100 hover-card">
                                        <div className="card-body">
                                            <div className="d-flex justify-content-between align-items-start mb-3">
                                                <div className="d-flex align-items-center">
                                                    <div className="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                         style={{ width: '40px', height: '40px' }}>
                                                        <Person className="text-primary" size={20} />
                                                    </div>
                                                    <div>
                                                        <h6 className="card-title mb-1">
                                                            {student.full_name || `${student.last_name || student.subname || ''} ${student.first_name || student.name || ''}`}
                                                        </h6>
                                                        <div className="d-flex align-items-center">
                                                            <small className="text-muted">
                                                                N° {student.student_number}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span className={`badge ${student.gender === 'M' ? 'bg-info' : 'bg-pink'}`}>
                                                    {student.gender === 'M' ? 'M' : 'F'}
                                                </span>
                                            </div>
                                            
                                            <div className="mb-3">
                                                {student.date_of_birth && (
                                                    <div className="d-flex align-items-center mb-2">
                                                        <Calendar size={14} className="text-muted me-2 flex-shrink-0" />
                                                        <small className="text-muted">
                                                            {new Date(student.date_of_birth).toLocaleDateString('fr-FR')}
                                                        </small>
                                                    </div>
                                                )}
                                                {student.place_of_birth && (
                                                    <div className="d-flex align-items-center mb-2">
                                                        <GeoAlt size={14} className="text-muted me-2 flex-shrink-0" />
                                                        <small className="text-muted">{student.place_of_birth}</small>
                                                    </div>
                                                )}
                                                {(student.parent_name || student.father_name) && (
                                                    <div className="d-flex align-items-center mb-2">
                                                        <Person size={14} className="text-muted me-2 flex-shrink-0" />
                                                        <small className="text-muted">
                                                            Parent: {student.parent_name || student.father_name}
                                                        </small>
                                                    </div>
                                                )}
                                            </div>
                                            
                                            <div className="d-flex justify-content-end gap-2 mt-auto">
                                                <button
                                                    className="btn btn-sm btn-outline-primary"
                                                    onClick={() => handleEdit(student)}
                                                    title="Modifier"
                                                >
                                                    <PencilSquare size={14} />
                                                </button>
                                                <button
                                                    className="btn btn-sm btn-outline-danger"
                                                    onClick={() => handleDelete(student)}
                                                    title="Supprimer"
                                                >
                                                    <Trash size={14} />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        // Vue en liste (tableau) avec drag & drop
                        <div className="card">
                            <div className="card-body p-0">
                                <div className="table-responsive">
                                    <DndContext
                                        sensors={sensors}
                                        collisionDetection={closestCenter}
                                        onDragEnd={handleDragEnd}
                                    >
                                        <table className="table table-hover mb-0">
                                            <thead className="table-light">
                                                <tr>
                                                    <th>N°</th>
                                                    <th>Nom & Prénom</th>
                                                    <th>Date/Lieu naissance</th>
                                                    <th>Sexe</th>
                                                    <th>Parent</th>
                                                    <th>Contact</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <SortableContext
                                                items={filteredStudents.map(s => s.id)}
                                                strategy={verticalListSortingStrategy}
                                            >
                                                <tbody>
                                                    {filteredStudents.map((student) => (
                                                        <SortableStudent
                                                            key={student.id}
                                                            student={student}
                                                            handleEdit={handleEdit}
                                                            handleDelete={handleDelete}
                                                        />
                                                    ))}
                                                </tbody>
                                            </SortableContext>
                                        </table>
                                    </DndContext>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Add/Edit Student Modal */}
            {(showAddModal || showEditModal) && (
                <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-lg">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">
                                    {selectedStudent ? 'Modifier l\'élève' : 'Ajouter un élève'}
                                </h5>
                                <button
                                    type="button"
                                    className="btn-close"
                                    onClick={handleCloseModal}
                                ></button>
                            </div>
                            <form onSubmit={handleSubmit}>
                                <div className="modal-body">
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Nom *</label>
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    value={formData.last_name}
                                                    onChange={(e) => setFormData({...formData, last_name: e.target.value})}
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Prénom *</label>
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    value={formData.first_name}
                                                    onChange={(e) => setFormData({...formData, first_name: e.target.value})}
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Date de naissance *</label>
                                                <input
                                                    type="date"
                                                    className="form-control"
                                                    value={formData.date_of_birth}
                                                    onChange={(e) => setFormData({...formData, date_of_birth: e.target.value})}
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Lieu de naissance *</label>
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    value={formData.place_of_birth}
                                                    onChange={(e) => setFormData({...formData, place_of_birth: e.target.value})}
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Sexe *</label>
                                                <select
                                                    className="form-select"
                                                    value={formData.gender}
                                                    onChange={(e) => setFormData({...formData, gender: e.target.value})}
                                                    required
                                                >
                                                    <option value="M">Masculin</option>
                                                    <option value="F">Féminin</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Nom du parent *</label>
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    value={formData.parent_name}
                                                    onChange={(e) => setFormData({...formData, parent_name: e.target.value})}
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Téléphone parent</label>
                                                <input
                                                    type="tel"
                                                    className="form-control"
                                                    value={formData.parent_phone}
                                                    onChange={(e) => setFormData({...formData, parent_phone: e.target.value})}
                                                />
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Email parent</label>
                                                <input
                                                    type="email"
                                                    className="form-control"
                                                    value={formData.parent_email}
                                                    onChange={(e) => setFormData({...formData, parent_email: e.target.value})}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="mb-3">
                                        <label className="form-label">Adresse</label>
                                        <textarea
                                            className="form-control"
                                            rows="3"
                                            value={formData.address}
                                            onChange={(e) => setFormData({...formData, address: e.target.value})}
                                        />
                                    </div>
                                </div>
                                <div className="modal-footer">
                                    <button
                                        type="button"
                                        className="btn btn-secondary"
                                        onClick={handleCloseModal}
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        className="btn btn-primary"
                                        disabled={loading}
                                    >
                                        {loading ? 'Enregistrement...' : (selectedStudent ? 'Modifier' : 'Créer')}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Import CSV Modal */}
            {showImportModal && (
                <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">Importer des élèves (CSV)</h5>
                                <button
                                    type="button"
                                    className="btn-close"
                                    onClick={handleCloseModal}
                                ></button>
                            </div>
                            <form onSubmit={handleImportCsv}>
                                <div className="modal-body">
                                    <div className="mb-3">
                                        <label className="form-label">Année scolaire *</label>
                                        <select
                                            className="form-select"
                                            value={selectedSchoolYear}
                                            onChange={(e) => setSelectedSchoolYear(e.target.value)}
                                            required
                                        >
                                            <option value="">Sélectionner une année</option>
                                            {schoolYears.map(year => (
                                                <option key={year.id} value={year.id}>
                                                    {year.name} {year.is_current ? '(Courante)' : ''}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    
                                    <div className="mb-3">
                                        <label className="form-label">Fichier CSV *</label>
                                        <input
                                            type="file"
                                            className="form-control"
                                            accept=".csv,.txt"
                                            onChange={(e) => setImportFile(e.target.files[0])}
                                            required
                                        />
                                        <div className="form-text">
                                            Le fichier doit contenir les colonnes : Nom, Prénom, Date naissance, Lieu naissance, Sexe, Nom parent, Téléphone parent (optionnel), Email parent (optionnel), Adresse (optionnel)
                                        </div>
                                    </div>
                                </div>
                                <div className="modal-footer">
                                    <button
                                        type="button"
                                        className="btn btn-secondary"
                                        onClick={handleCloseModal}
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        className="btn btn-primary"
                                        disabled={loading}
                                    >
                                        {loading ? 'Import en cours...' : 'Importer'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* CSS for hover effects and drag & drop */}
            <style jsx>{`
                .hover-card {
                    transition: box-shadow 0.2s ease-in-out;
                }
                .hover-card:hover {
                    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
                }
                .bg-pink {
                    background-color: #e91e63 !important;
                }
                .drag-handle {
                    opacity: 0.6;
                    transition: opacity 0.2s ease;
                }
                .drag-handle:hover {
                    opacity: 1;
                }
                .dragging {
                    background-color: rgba(0, 123, 255, 0.1) !important;
                }
                tbody tr:hover .drag-handle {
                    opacity: 1;
                }
            `}</style>
        </div>
    );
};

export default SeriesStudents;