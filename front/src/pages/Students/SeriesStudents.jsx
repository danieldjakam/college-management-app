import React, { useState, useEffect, useRef } from 'react';
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
    GripVertical,
    Camera,
    X,
    Image,
    CashCoin,
    Printer,
    ArrowRightCircle,
    FileEarmarkPdf
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { useAuth } from '../../hooks/useAuth';
import { useSchool } from '../../contexts/SchoolContext';
import { authService } from '../../services/authService';
import ImportExportButton from '../../components/ImportExportButton';
import StudentsExportButton from '../../components/StudentsExportButton';
import BulkPhotoUpload from '../../components/BulkPhotoUpload';
import StudentCardPrint from '../../components/StudentCardPrint';
import StudentCard from '../../components/StudentCard';
import StudentTransfer from '../../components/StudentTransfer';
import StudentTransferWithinClass from '../../components/StudentTransferWithinClass';
import StudentActionsDropdown from '../../components/StudentActionsDropdown';
import Swal from 'sweetalert2';
import html2canvas from 'html2canvas';
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
import { host } from '../../utils/fetch';

// Composant pour afficher les photos d'élèves avec fallback
const StudentPhoto = ({ student, size = 40, className = "" }) => {
    const [imageError, setImageError] = useState(false);
    
    if (!student.photo_url || imageError) {
        return (
            <div 
                className={`bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center ${className}`}
                style={{ width: `${size}px`, height: `${size}px` }}
            >
                <Person className="text-primary" size={Math.floor(size * 0.5)} />
            </div>
        );
    }
    
    return (
        <img 
            src={student.photo_url} 
            alt={student.full_name || `${student.last_name} ${student.first_name}`}
            className={`rounded-circle ${className}`}
            style={{ width: `${size}px`, height: `${size}px`, objectFit: 'cover' }}
            onError={() => setImageError(true)}
        />
    );
};

// Composant pour les éléments sortables
const SortableStudent = ({ student, handleEdit, handleDelete, handlePrintCard, handlePreviewCard, handleTransferStudent, handleTransferWithinClass, handleViewStudent, handleViewPayments, handleApplyEmployeeDiscount, handleStatusChange, navigate, userRole }) => {
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
                    <StudentPhoto student={student} size={32} className="me-2" />
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
                    {student.student_status === 'old' && (
                        <span className="badge bg-success bg-opacity-25 text-success small mt-1">
                            Ancien élève
                        </span>
                    )}
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
                <select 
                    className={`form-select form-select-sm ${student.student_status === 'old' ? 'text-success' : 'text-primary'}`}
                    value={student.student_status || 'new'}
                    onChange={(e) => handleStatusChange(student.id, e.target.value)}
                    style={{ minWidth: '100px' }}
                >
                    <option value="new">Nouveau</option>
                    <option value="old">Ancien</option>
                </select>
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
                <div className="text-center">
                    {student.has_scholarship_enabled ? (
                        <span className="badge bg-success bg-opacity-25 text-success">
                            <CashCoin size={12} className="me-1" />
                            Activée
                        </span>
                    ) : (
                        <span className="badge bg-secondary bg-opacity-25 text-muted">
                            Désactivée
                        </span>
                    )}
                </div>
            </td>
            <td>
                <StudentActionsDropdown
                    student={student}
                    onPrintCard={handlePrintCard}
                    onPreviewCard={handlePreviewCard}
                    onTransfer={handleTransferStudent}
                    onTransferWithinClass={handleTransferWithinClass}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    onViewPayments={handleViewPayments}
                    onViewStudent={handleViewStudent}
                    onApplyEmployeeDiscount={handleApplyEmployeeDiscount}
                    userRole={userRole}
                />
            </td>
        </tr>
    );
};

const SeriesStudents = () => {
    const { seriesId } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    const { schoolSettings, formatCurrency } = useSchool();
    
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
    const [statusFilter, setStatusFilter] = useState('all');
    const [viewMode, setViewMode] = useState('list');
    
    // Modal states
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [showCardPrint, setShowCardPrint] = useState(false);
    const [showTransferModal, setShowTransferModal] = useState(false);
    const [showTransferWithinClassModal, setShowTransferWithinClassModal] = useState(false);
    const [showStudentsListModal, setShowStudentsListModal] = useState(false);
    const [showEmployeeDiscountModal, setShowEmployeeDiscountModal] = useState(false);
    const [selectedStudent, setSelectedStudent] = useState(null);
    const [newStudentForCard, setNewStudentForCard] = useState(null);
    const [studentToTransfer, setStudentToTransfer] = useState(null);

    // Employee discount form state
    const [employeeDiscountForm, setEmployeeDiscountForm] = useState({
        amount: '',
        reason: ''
    });
    
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
        mother_name: '',
        mother_phone: '',
        address: '',
        class_series_id: seriesId,
        student_status: 'new',
        arrival_trimester: '',
        newcomer_discount_reason: '',
        has_scholarship_enabled: false,
    });
    
    // Photo states
    const [studentPhoto, setStudentPhoto] = useState(null);
    const [photoPreview, setPhotoPreview] = useState('');
    const [showCamera, setShowCamera] = useState(false);
    const [cameraStream, setCameraStream] = useState(null);
    
    // Import data
    const [importFile, setImportFile] = useState(null);
    const [selectedSchoolYear, setSelectedSchoolYear] = useState('');

    // Ref pour le rendu hors écran des cartes
    const cardContainerRef = useRef(null);

    useEffect(() => {
        loadStudents();
        loadSchoolYears();
    }, [seriesId]);

    // Cleanup camera stream on unmount
    useEffect(() => {
        return () => {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
        };
    }, [cameraStream]);

    const loadStudents = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.students.getByClassSeries(seriesId);
            
            if (response.success) {
                setStudents(response.data.students);
                setSeries(response.data.series);
                setSchoolYear(response.data.school_year);
                // L'année scolaire est maintenant gérée automatiquement par le backend
                
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

    const downloadStudentsListPdf = async () => {
        try {
            setLoading(true);
            setError('');

            const response = await fetch(`${host}/api/students/export/pdf/${seriesId}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/pdf'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const pdfBlob = await response.blob();
            const blobUrl = window.URL.createObjectURL(pdfBlob);
            
            // Créer un lien de téléchargement
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = `liste_eleves_${series?.name || 'serie'}_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1000);
            setSuccess('Liste des élèves téléchargée avec succès');
            
        } catch (error) {
            console.error('Error downloading students list PDF:', error);
            setError('Erreur lors du téléchargement de la liste PDF');
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

        // Validation côté client
        const requiredFields = ['first_name', 'last_name', 'date_of_birth', 'place_of_birth', 'gender', 'parent_name'];
        const missingFields = requiredFields.filter(field => !formData[field] || formData[field].trim() === '');

        if (missingFields.length > 0) {
            setError(`Champs obligatoires manquants: ${missingFields.map(field => {
                const labels = {
                    first_name: 'Prénom',
                    last_name: 'Nom',
                    date_of_birth: 'Date de naissance',
                    place_of_birth: 'Lieu de naissance',
                    gender: 'Sexe',
                    parent_name: 'Nom du parent'
                };
                return labels[field];
            }).join(', ')}`);
            return;
        }

        // VALIDATION : Motif obligatoire si trimestre 2 ou 3
        if ((formData.arrival_trimester === '2' || formData.arrival_trimester === '3') &&
            (!formData.newcomer_discount_reason || formData.newcomer_discount_reason.trim() === '')) {
            setError('Le motif de réduction est obligatoire pour les élèves arrivés au trimestre 2 ou 3');
            return;
        }

        // L'année scolaire est maintenant gérée automatiquement par le backend

        if (!seriesId) {
            setError('Série de classe manquante');
            return;
        }
        
        // Debug: afficher les données avant envoi
        console.log('Form data before submission:', formData);
        console.log('School year:', schoolYear);
        console.log('Series ID:', seriesId);
        
        try {
            setLoading(true);
            setError('');
            
            // Préparer les données finales (school_year_id géré automatiquement par le backend)
            const finalFormData = {
                ...formData,
                class_series_id: parseInt(seriesId)
            };
            
            // Validation finale des IDs
            if (!finalFormData.class_series_id || isNaN(finalFormData.class_series_id)) {
                setError('ID de série de classe invalide');
                return;
            }
            
            console.log('Final form data:', finalFormData);
            
            let response;

            // Debug pour l'édition
            if (selectedStudent) {
                console.log('Editing student:', selectedStudent.id);
                console.log('Has new photo:', !!studentPhoto);
                console.log('Student photo type:', typeof studentPhoto);
                console.log('Student photo:', studentPhoto);
            }
            
            // Si on a une photo, utiliser FormData, sinon utiliser JSON
            if (studentPhoto) {
                // Créer FormData pour inclure la photo
                const formDataToSend = new FormData();
                
                // Ajouter tous les champs du formulaire 
                Object.keys(finalFormData).forEach(key => {
                    const value = finalFormData[key];

                    // Champs à toujours envoyer (même vides) pour permettre l'effacement côté backend
                    const alwaysSend = ['student_status', 'arrival_trimester', 'newcomer_discount_reason'];

                    // S'assurer que les valeurs ne sont pas undefined ou null
                    if (value !== undefined && value !== null && value !== '') {
                        formDataToSend.append(key, String(value));
                    } else if (
                        ['first_name', 'last_name', 'date_of_birth', 'place_of_birth', 'gender', 'parent_name', 'class_series_id'].includes(key) ||
                        alwaysSend.includes(key)
                    ) {
                        // Pour les champs requis (et ceux à effacer), envoyer une chaîne vide
                        formDataToSend.append(key, '');
                    }
                });
                
                // Ajouter la photo
                formDataToSend.append('photo', studentPhoto);
                
                // Debug: afficher le contenu de FormData
                console.log('FormData contents:');
                for (let [key, value] of formDataToSend.entries()) {
                    console.log(`${key}:`, value);
                }
                
                response = selectedStudent 
                    ? await secureApiEndpoints.students.updateWithPhoto(selectedStudent.id, formDataToSend)
                    : await secureApiEndpoints.students.createWithPhoto(formDataToSend);
            } else {
                
                // Pas de photo, utiliser l'API normale avec JSON
                const jsonData = {
                    ...formData,
                    class_series_id: parseInt(seriesId)
                };
                
                
                response = selectedStudent 
                    ? await secureApiEndpoints.students.update(selectedStudent.id, jsonData)
                    : await secureApiEndpoints.students.create(jsonData);
            }

            if (response.success) {
                setSuccess(response.message || `Élève ${selectedStudent ? 'modifié' : 'créé'} avec succès`);
                resetForm();
                setShowAddModal(false);
                setShowEditModal(false);
                loadStudents();
                
                // Si c'est une création d'élève (pas une modification), proposer l'impression de la carte
                if (!selectedStudent && response.data) {
                    // Enrichir les données de l'élève créé avec les infos de la série
                    const enrichedStudent = {
                        ...response.data,
                        class_series: series,
                        current_class: series?.name
                    };
                    
                    setNewStudentForCard(enrichedStudent);
                    
                    Swal.fire({
                        title: 'Élève créé avec succès !',
                        text: 'Voulez-vous imprimer sa carte scolaire maintenant ?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '🖨️ Imprimer la carte',
                        cancelButtonText: 'Plus tard',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            setShowCardPrint(true);
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Succès',
                        text: response.message || `Élève ${selectedStudent ? 'modifié' : 'créé'} avec succès`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } else {
                setError(response.message || `Erreur lors de ${selectedStudent ? 'la modification' : 'la création'} de l'élève`);
            }
        } catch (error) {
            console.error('Error saving student:', error);
            console.error('Error details:', error.message);
            
            // Essayer d'extraire le message d'erreur détaillé
            let errorMessage = `Erreur lors de ${selectedStudent ? 'la modification' : 'la création'} de l'élève`;
            
            if (error.message) {
                // Si c'est une erreur de validation avec détails
                try {
                    const errorData = JSON.parse(error.message);
                    if (errorData.errors) {
                        const validationErrors = Object.entries(errorData.errors)
                            .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                            .join('\n');
                        errorMessage += `\n\nDétails:\n${validationErrors}`;
                    }
                } catch (parseError) {
                    // Si ce n'est pas du JSON, utiliser le message tel quel
                    errorMessage += `\n\nDétails: ${error.message}`;
                }
            }
            
            setError(errorMessage);
        } finally {
            setLoading(false);
        }
    };

    const handlePrintCard = (student) => {
        // Enrichir les données de l'élève avec les infos de la série
        const enrichedStudent = {
            ...student,
            class_series: series,
            current_class: series?.name
        };

        setNewStudentForCard(enrichedStudent);
        setShowCardPrint(true);
    };

    const handlePreviewCard = async (student) => {
        try {
            // Récupérer l'année scolaire actuelle
            const currentYear = new Date().getFullYear();
            const academicYear = `${currentYear}-${currentYear + 1}`;

            // Ouvrir la prévisualisation dans un nouvel onglet
            await secureApiEndpoints.studentCards.previewCard(student.id, academicYear);
        } catch (error) {
            console.error('Erreur lors de la prévisualisation:', error);
            setError(error.message || 'Erreur lors de la prévisualisation de la carte');
            setTimeout(() => setError(''), 5000);
        }
    };

    const handleTransferStudent = (student) => {
        // Enrichir les données de l'élève avec les infos de la série actuelle
        const enrichedStudent = {
            ...student,
            class_series: series,
            class_series_id: series?.id,
            current_class: series?.name
        };
        
        setStudentToTransfer(enrichedStudent);
        setShowTransferModal(true);
    };

    const handleTransferWithinClass = (student) => {
        // Enrichir les données de l'élève pour le transfert au sein de la classe
        const enrichedStudent = {
            ...student,
            class_series: series,
            class_series_id: series?.id,
            current_class: series?.name
        };
        
        setStudentToTransfer(enrichedStudent);
        setShowTransferWithinClassModal(true);
    };

    const handleTransferSuccess = (transferredStudent, newClassInfo) => {
        // Recharger la liste des étudiants pour refléter le changement
        loadStudents();
        
        // Optionnel: message de confirmation déjà géré dans le composant StudentTransfer
        console.log(`Élève transféré vers ${newClassInfo.className} - ${newClassInfo.seriesName}`);
    };

    const handleViewStudent = (student) => {
        // Naviguer vers la page détaillée de l'élève ou ouvrir un modal
        console.log('Voir élève:', student);
        // navigate(`/students/${student.id}`); // Si vous avez une page dédiée
    };

    const handleViewPayments = (student) => {
        // Naviguer vers la page des paiements de l'élève
        navigate(`/student-payment/${student.id}`);
    };

    const handleApplyEmployeeDiscount = (student) => {
        setSelectedStudent(student);
        setEmployeeDiscountForm({ amount: '', reason: '' });
        setShowEmployeeDiscountModal(true);
    };

    const handleSubmitEmployeeDiscount = async () => {
        // Validation
        if (!employeeDiscountForm.amount || parseFloat(employeeDiscountForm.amount) <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Veuillez saisir un montant valide'
            });
            return;
        }

        if (!employeeDiscountForm.reason || employeeDiscountForm.reason.trim() === '') {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Le motif est obligatoire'
            });
            return;
        }

        try {
            const data = {
                student_id: selectedStudent.id,
                amount: parseFloat(employeeDiscountForm.amount),
                reason: employeeDiscountForm.reason.trim()
            };

            const response = await secureApiEndpoints.manualDiscounts.upsert(data);

            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: 'Réduction employé appliquée avec succès',
                    timer: 2000
                });

                setShowEmployeeDiscountModal(false);
                setEmployeeDiscountForm({ amount: '', reason: '' });
                setSelectedStudent(null);

                // Recharger la liste des étudiants
                loadStudents();
            } else {
                throw new Error(response.message || 'Erreur lors de l\'enregistrement');
            }
        } catch (error) {
            console.error('Error applying employee discount:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: error.message || 'Erreur lors de l\'application de la réduction'
            });
        }
    };

    const handleEdit = (student) => {
        setSelectedStudent(student);
        
        // Convertir la date au format YYYY-MM-DD pour l'input HTML
        let dateOfBirth = '';
        if (student.date_of_birth) {
            try {
                const date = new Date(student.date_of_birth);
                if (!isNaN(date.getTime())) {
                    dateOfBirth = date.toISOString().split('T')[0];
                }
            } catch (error) {
                console.error('Error parsing date:', error);
            }
        }
        
        setFormData({
            first_name: student.first_name || '',
            last_name: student.last_name || '',
            date_of_birth: dateOfBirth,
            place_of_birth: student.place_of_birth || '',
            gender: student.gender || 'M',
            parent_name: student.parent_name || '',
            parent_phone: student.parent_phone || '',
            parent_email: student.parent_email || '',
            mother_name: student.mother_name || '',
            mother_phone: student.mother_phone || '',
            address: student.address || '',
            class_series_id: seriesId,
            student_status: student.student_status || 'new',
            arrival_trimester: student.arrival_trimester ? String(student.arrival_trimester) : '',
            newcomer_discount_reason: student.newcomer_discount_reason || '',
            has_scholarship_enabled: student.has_scholarship_enabled || false,
            // school_year_id géré automatiquement par le backend
        });
        
        // Charger la photo existante
        if (student.photo_url) {
            setPhotoPreview(student.photo_url);
        } else {
            setPhotoPreview('');
        }
        setStudentPhoto(null); // Reset file input for new photo
        
        setShowEditModal(true);
    };

    const handleStatusChange = async (studentId, newStatus) => {
        try {
            const response = await secureApiEndpoints.students.updateStatus(studentId, newStatus);
            
            if (response.success) {
                // Mettre à jour l'état local
                setStudents(prevStudents => 
                    prevStudents.map(student => 
                        student.id === studentId 
                            ? { ...student, student_status: newStatus }
                            : student
                    )
                );
                
                setSuccess(`Statut mis à jour: ${newStatus === 'old' ? 'Ancien élève' : 'Nouvel élève'}`);
                setTimeout(() => setSuccess(''), 3000);
            } else {
                setError(response.message || 'Erreur lors de la mise à jour du statut');
            }
        } catch (error) {
            setError('Erreur lors de la mise à jour du statut');
            console.error('Error updating student status:', error);
        }
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
            // class_series_id et school_year_id gérés automatiquement par la nouvelle route

            // Debug log détaillé
            console.log('Import CSV - FormData contents:', {
                file: {
                    name: importFile.name,
                    type: importFile.type,
                    size: importFile.size,
                    lastModified: importFile.lastModified
                },
                seriesIdFromUrl: seriesId,
                seriesInfo: series?.name,
                schoolYearInfo: schoolYears.find(y => y.id == selectedSchoolYear)?.name
            });

            // Vérifier que FormData contient bien les données
            for (let [key, value] of formData.entries()) {
                console.log('FormData entry:', key, value);
            }

            const response = await secureApiEndpoints.students.importCsv(formData, seriesId);
            
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
                // Gérer les erreurs de validation du backend
                if (response.errors) {
                    let errorMessage = 'Erreurs de validation:\n';
                    Object.keys(response.errors).forEach(field => {
                        errorMessage += `• ${field}: ${response.errors[field].join(', ')}\n`;
                    });
                    setError(errorMessage);
                } else {
                    setError(response.message || 'Erreur lors de l\'import');
                }
            }
        } catch (error) {
            console.error('Error importing CSV:', error);
            
            // Gestion d'erreur plus détaillée
            let errorMessage = 'Erreur lors de l\'import CSV';
            
            if (error.message) {
                errorMessage += ': ' + error.message;
            }
            
            // Si c'est une erreur réseau
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                errorMessage = 'Impossible de se connecter au serveur. Vérifiez votre connexion.';
            }
            
            // Si c'est une erreur de parsing JSON
            if (error.message.includes('JSON')) {
                errorMessage = 'Erreur de format de réponse du serveur.';
            }
            
            setError(errorMessage);
        } finally {
            setLoading(false);
        }
    };

    // Fonction pour télécharger un modèle CSV
    const handleDownloadTemplate = () => {
        const headers = [
            'Nom',
            'Prénom', 
            'Date de naissance (DD/MM/YYYY)',
            'Lieu de naissance',
            'Sexe (M/F)',
            'Nom du parent',
            'Téléphone parent (optionnel)',
            'Email parent (optionnel)',
            'Adresse (optionnel)'
        ];
        
        const sampleData = [
            [
                'NGUEME',
                'Jean',
                '15/03/2010',
                'Douala',
                'M',
                'NGUEME Paul',
                '690123456',
                'parent@email.com',
                'Bonanjo, Douala'
            ],
            [
                'TCHOUA',
                'Marie',
                '20/08/2009',
                'Yaoundé',
                'F',
                'TCHOUA Pierre',
                '691234567',
                'marie.parent@email.com',
                'Melen, Yaoundé'
            ]
        ];
        
        // Créer le contenu CSV
        const csvContent = [headers, ...sampleData]
            .map(row => row.map(field => `"${field}"`).join(';'))
            .join('\n');
        
        // Créer le blob et télécharger
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'modele_import_eleves.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        setSuccess('Modèle CSV téléchargé avec succès');
    };

    // Fonction pour prévisualiser le fichier CSV avant import
    const handleFilePreview = (file) => {
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const csvText = e.target.result;
                const lines = csvText.split('\n').filter(line => line.trim());
                
                if (lines.length < 2) {
                    setError('Le fichier CSV doit contenir au moins une ligne d\'en-têtes et une ligne de données');
                    return;
                }
                
                const headers = lines[0].split(';').map(h => h.replace(/"/g, '').trim());
                const expectedHeaders = ['Nom', 'Prénom', 'Date de naissance', 'Lieu de naissance', 'Sexe', 'Nom du parent'];
                
                // Vérifier les en-têtes obligatoires
                const missingHeaders = expectedHeaders.filter(expected => 
                    !headers.some(header => 
                        header.toLowerCase().includes(expected.toLowerCase()) ||
                        expected.toLowerCase().includes(header.toLowerCase())
                    )
                );
                
                if (missingHeaders.length > 0) {
                    setError(`En-têtes manquants dans le CSV: ${missingHeaders.join(', ')}`);
                    return;
                }
                
                console.log('Prévisualisation CSV:', {
                    totalLines: lines.length,
                    headers: headers,
                    dataLines: lines.length - 1,
                    firstDataLine: lines[1]?.split(';').map(d => d.replace(/"/g, '').trim())
                });
                
                setError(''); // Clear any previous errors
                setSuccess(`Fichier CSV valide: ${lines.length - 1} ligne(s) de données détectée(s)`);
                
            } catch (error) {
                setError('Erreur lors de la lecture du fichier CSV: ' + error.message);
            }
        };
        
        reader.onerror = () => {
            setError('Erreur lors de la lecture du fichier');
        };
        
        reader.readAsText(file, 'UTF-8');
    };

    // Fonctions pour la gestion des photos
    const handlePhotoUpload = (e) => {
        const file = e.target.files[0];
        if (file) {
            // Validation du type de fichier
            if (!file.type.startsWith('image/')) {
                setError('Veuillez sélectionner un fichier image valide');
                return;
            }
            
            // Validation de la taille (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                setError('La taille de l\'image ne doit pas dépasser 5MB');
                return;
            }
            
            setStudentPhoto(file);
            
            // Créer un aperçu
            const reader = new FileReader();
            reader.onload = (e) => {
                setPhotoPreview(e.target.result);
            };
            reader.readAsDataURL(file);
            
            setError(''); // Clear any errors
        }
    };

    const startCamera = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: 640, 
                    height: 480,
                    facingMode: 'user' // Utiliser la caméra frontale si disponible
                } 
            });
            setCameraStream(stream);
            setShowCamera(true);
        } catch (error) {
            console.error('Erreur d\'accès à la caméra:', error);
            setError('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
        }
    };

    const stopCamera = () => {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            setCameraStream(null);
        }
        setShowCamera(false);
    };

    const capturePhoto = () => {
        const video = document.getElementById('cameraVideo');
        const canvas = document.createElement('canvas');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0);
        
        // Convertir en blob
        canvas.toBlob((blob) => {
            const file = new File([blob], 'photo_eleve.jpg', { type: 'image/jpeg' });
            setStudentPhoto(file);
            setPhotoPreview(canvas.toDataURL());
            stopCamera();
        }, 'image/jpeg', 0.8);
    };

    const removePhoto = () => {
        setStudentPhoto(null);
        setPhotoPreview('');
        setError('');
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
                    class_series_id: parseInt(seriesId)
                    // school_year_id géré automatiquement par le backend
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
                    {} // school_year_id géré automatiquement par le backend
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

    // Fonction utilitaire pour convertir une image en base64
    const convertImageToBase64 = async (student) => {
        try {
            const defaultSvg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjUwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjUwMCIgZmlsbD0iI2NjYyIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjQwIiBmaWxsPSIjNjY2IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+UGFzIGRlIHBob3RvPC90ZXh0Pjwvc3ZnPg==';

            // Si l'élève n'a pas de photo dans la base de données, retourner directement l'image par défaut
            // Cela évite de faire un appel API inutile qui retournera 404
            if (!student || !student.id || !student.photo) {
                return defaultSvg;
            }

            // Utiliser la nouvelle route API pour récupérer la photo avec les bons headers CORS
            const response = await fetch(`${host}/api/students/${student.id}/photo`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'image/*'
                }
            });

            if (!response.ok) {
                return defaultSvg;
            }

            // Convertir la réponse en blob puis en base64
            const blob = await response.blob();
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result);
                reader.onerror = () => {
                    console.error('Error reading blob as base64');
                    resolve(defaultSvg);
                };
                reader.readAsDataURL(blob);
            });

        } catch (error) {
            console.error('Error loading student photo:', error);
            return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjUwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjUwMCIgZmlsbD0iI2NjYyIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjQwIiBmaWxsPSIjNjY2IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+UGFzIGRlIHBob3RvPC90ZXh0Pjwvc3ZnPg==';
        }
    };

    // Fonction pour générer les cartes des élèves en PDF (10 par page - 2x5)
    const generateStudentCardsPDF = async () => {
        if (students.length === 0) {
            Swal.fire({
                title: 'Aucun élève',
                text: 'Il n\'y a aucun élève dans cette classe.',
                icon: 'warning'
            });
            return;
        }

        try {
            setLoading(true);

            // Compter les élèves avec photos pour info
            const studentsWithPhotos = students.filter(s => s.photo).length;
            const studentsWithoutPhotos = students.length - studentsWithPhotos;

            console.log(`📊 Génération de ${students.length} cartes: ${studentsWithPhotos} avec photo, ${studentsWithoutPhotos} sans photo (API calls économisés: ${studentsWithoutPhotos})`);

            Swal.fire({
                title: 'Génération en cours...',
                text: `Chargement des photos (${studentsWithPhotos} photo(s) à charger)...`,
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Précharger toutes les images en base64
            // Optimisation: les élèves sans photo retournent immédiatement l'image par défaut sans appel API
            const studentsWithBase64Photos = await Promise.all(
                students.map(async (student, index) => {
                    const photoBase64 = await convertImageToBase64(student);

                    // Mettre à jour la progression tous les 10 étudiants
                    if ((index + 1) % 10 === 0 || index === students.length - 1) {
                        Swal.update({
                            text: `Chargement des photos... ${index + 1}/${students.length}`
                        });
                    }

                    return {
                        ...student,
                        photoBase64
                    };
                })
            );

            Swal.update({
                text: `Génération des cartes pour ${students.length} élève(s)...`
            });

            // Importer jsPDF dynamiquement
            const { jsPDF } = await import('jspdf');

            const cardDataUrls = [];

            // Générer chaque carte et la convertir en image
            for (let i = 0; i < studentsWithBase64Photos.length; i++) {
                const student = studentsWithBase64Photos[i];

                // Enrichir les données de l'élève
                const enrichedStudent = {
                    ...student,
                    class_series: series,
                    current_class: series?.name
                };

                // Créer un conteneur temporaire pour cette carte
                const cardElement = document.createElement('div');
                cardElement.style.position = 'absolute';
                cardElement.style.left = '-9999px';
                cardElement.style.top = '-9999px';
                cardElement.style.width = '1920px';
                cardElement.style.height = '1080px';
                cardElement.className = 'student-card-temp';
                document.body.appendChild(cardElement);

                // Générer le QR code pour l'élève
                const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent('STUDENT_ID_' + student.id)}&margin=0`;

                // Template basé sur "PERSONNEL (1).png"
                const cardHtml = `
                    <div style="width: 1920px; height: 1080px; position: relative; background: #F5F1E8; font-family: Arial, sans-serif; overflow: hidden;">
                        <!-- Cercles de couleur aux coins -->
                        <div style="position: absolute; top: -150px; left: -150px; width: 300px; height: 300px; background: #2D7F3E; border-radius: 50%; opacity: 0.8;"></div>
                        <div style="position: absolute; top: -150px; right: -150px; width: 300px; height: 300px; background: #E53935; border-radius: 50%; opacity: 0.8;"></div>
                        <div style="position: absolute; bottom: -150px; left: -150px; width: 300px; height: 300px; background: #FFC107; border-radius: 50%; opacity: 0.8;"></div>

                        <!-- En-tête République du Cameroun -->
                        <div style="text-align: center; padding-top: 20px; margin-bottom: 15px;">
                            <div style="font-size: 42px; font-weight: bold; color: #000; letter-spacing: 2px;">
                                🇨🇲 RÉPUBLIQUE DU CAMEROUN 🇨🇲
                            </div>
                            <div style="font-size: 38px; font-weight: bold; color: #000; margin-top: 5px; letter-spacing: 1px;">
                                PAIX – TRAVAIL – PATRIE
                            </div>
                        </div>

                        <!-- Corps principal -->
                        <div style="display: flex; padding: 0 60px; margin-top: 30px;">
                            <!-- Colonne gauche: Logo + Infos -->
                            <div style="flex: 1; padding-right: 40px;">
                                <!-- Logo CPBD -->
                                <div style="display: flex; align-items: center; margin-bottom: 25px;">
                                    <div style="width: 120px; height: 120px; background: white; border: 3px solid #7B2D8E; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px; font-size: 18px; font-weight: bold; color: #7B2D8E;">
                                        CPBD
                                    </div>
                                    <div style="font-size: 32px; font-weight: bold; font-style: italic; line-height: 1.2;">
                                        Collège Polyvalent<br>
                                        Bilingue de Douala
                                    </div>
                                </div>

                                <!-- Titre CARTE SCOLAIRE -->
                                <div style="font-size: 90px; font-weight: bold; color: #000; margin: 30px 0 40px 0; line-height: 1;">
                                    CARTE<br>SCOLAIRE
                                </div>

                                <!-- Champs d'information -->
                                <div style="font-size: 32px; line-height: 1.8; color: #000;">
                                    <div style="display: flex; margin-bottom: 12px;">
                                        <span style="font-weight: bold; min-width: 280px;">NOM:</span>
                                        <span>${(student.last_name || '').toUpperCase()}</span>
                                    </div>
                                    <div style="display: flex; margin-bottom: 12px;">
                                        <span style="font-weight: bold; min-width: 280px;">PRÉNOM:</span>
                                        <span>${student.first_name || ''}</span>
                                    </div>
                                    <div style="display: flex; margin-bottom: 12px;">
                                        <span style="font-weight: bold; min-width: 280px;">MATRICULE:</span>
                                        <span>${student.student_number || student.matricule || ''}</span>
                                    </div>
                                    <div style="display: flex; margin-bottom: 12px;">
                                        <span style="font-weight: bold; min-width: 280px;">CLASSE:</span>
                                        <span>${series?.name || ''}</span>
                                    </div>
                                    <div style="display: flex; margin-bottom: 12px;">
                                        <span style="font-weight: bold; min-width: 280px;">NÉ(E) LE:</span>
                                        <span>${student.date_of_birth ? new Date(student.date_of_birth).toLocaleDateString('fr-FR') : ''}</span>
                                    </div>
                                    <div style="display: flex; margin-bottom: 12px;">
                                        <span style="font-weight: bold; min-width: 280px;">PARENT/TUTEUR:</span>
                                        <span style="font-size: 28px;">${student.parent_name || ''}</span>
                                    </div>
                                    <div style="display: flex; margin-bottom: 12px;">
                                        <span style="font-weight: bold; min-width: 280px;">ANNÉE SCOLAIRE:</span>
                                        <span>${schoolYear?.name || new Date().getFullYear() + '-' + (new Date().getFullYear() + 1)}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne droite: Photo circulaire -->
                            <div style="flex: 0 0 550px; display: flex; align-items: center; justify-content: center; margin-top: 80px;">
                                <div style="width: 500px; height: 500px; border-radius: 50%; border: 8px solid #2D7F3E; overflow: hidden; background: linear-gradient(to bottom, #87CEEB 0%, #87CEEB 60%, #90EE90 60%, #228B22 100%); display: flex; align-items: center; justify-content: center;">
                                    <img src="${student.photoBase64}"
                                         style="width: 100%; height: 100%; object-fit: cover;"
                                         crossorigin="anonymous" />
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div style="position: absolute; bottom: 40px; left: 60px; right: 60px; display: flex; justify-content: space-between; align-items: flex-end;">
                            <!-- Coordonnées -->
                            <div style="font-size: 26px; line-height: 1.5; color: #000;">
                                <div style="margin-bottom: 5px;">Phone: +237678469398 / +237688552219</div>
                                <div style="margin-bottom: 5px; font-weight: bold;">website: www.cpbd-douala.com</div>
                                <div style="display: flex; align-items: center;">
                                    <span style="font-size: 40px; margin-right: 10px;">📍</span>
                                    <span style="font-weight: bold;">YASSA ENTRÉE PLANET VOYAGE</span>
                                </div>
                            </div>

                            <!-- QR Code + Signature -->
                            <div style="text-align: center;">
                                <div style="font-size: 28px; font-weight: bold; margin-bottom: 10px;">SCAN ME</div>
                                <div style="width: 220px; height: 220px; border: 3px solid #000; border-radius: 10px; padding: 10px; background: white; margin-bottom: 15px;">
                                    <img src="${qrCodeUrl}" style="width: 100%; height: 100%; object-fit: contain;" />
                                </div>
                                <div style="font-size: 24px; font-weight: bold; line-height: 1.3;">
                                    Signature<br>Directeur
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                cardElement.innerHTML = cardHtml;

                // Attendre que le DOM soit mis à jour (réduit à 50ms pour plus de rapidité)
                await new Promise(resolve => setTimeout(resolve, 50));

                // Convertir en canvas
                const canvas = await html2canvas(cardElement, {
                    scale: 1,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: null,
                    width: 1920,
                    height: 1080,
                    logging: false
                });

                const dataUrl = canvas.toDataURL('image/png', 0.95);
                cardDataUrls.push(dataUrl);

                // Nettoyer
                document.body.removeChild(cardElement);

                // Mettre à jour la progression
                if ((i + 1) % 5 === 0 || i === studentsWithBase64Photos.length - 1) {
                    Swal.update({
                        text: `Génération en cours... ${i + 1}/${studentsWithBase64Photos.length} cartes`
                    });
                }
            }

            // Créer le PDF avec les cartes
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4',
            });

            const pageWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm

            // Calculer les dimensions basées sur le ratio 1920x1080 (16:9)
            const cardRatio = 1920 / 1080; // Ratio largeur/hauteur = 1.778

            // Calculer les dimensions pour 10 cartes par page (2x5 - 2 colonnes, 5 lignes)
            const reducedMargin = 5;
            const reducedSpacing = 3;

            const availableHeight = pageHeight - 2 * reducedMargin - 4 * reducedSpacing;
            const availableWidth = pageWidth - 2 * reducedMargin - reducedSpacing;

            const cardHeight = availableHeight / 5; // 5 lignes par page
            const cardWidth = availableWidth / 2; // 2 colonnes par page

            // Vérifier si le ratio 1920x1080 peut être respecté
            const targetCardWidth = cardHeight * cardRatio;

            let finalCardWidth, finalCardHeight;
            if (targetCardWidth <= cardWidth) {
                finalCardWidth = targetCardWidth;
                finalCardHeight = cardHeight;
            } else {
                finalCardWidth = cardWidth;
                finalCardHeight = cardWidth / cardRatio;
            }

            // Positions pour 10 cartes par page (grille 2x5)
            const centerOffsetX = (pageWidth - (2 * finalCardWidth + reducedSpacing)) / 2;
            const centerOffsetY = (pageHeight - (5 * finalCardHeight + 4 * reducedSpacing)) / 2;

            const positions = [
                // Ligne 1
                { x: centerOffsetX, y: centerOffsetY },
                { x: centerOffsetX + finalCardWidth + reducedSpacing, y: centerOffsetY },
                // Ligne 2
                { x: centerOffsetX, y: centerOffsetY + finalCardHeight + reducedSpacing },
                { x: centerOffsetX + finalCardWidth + reducedSpacing, y: centerOffsetY + finalCardHeight + reducedSpacing },
                // Ligne 3
                { x: centerOffsetX, y: centerOffsetY + 2 * (finalCardHeight + reducedSpacing) },
                { x: centerOffsetX + finalCardWidth + reducedSpacing, y: centerOffsetY + 2 * (finalCardHeight + reducedSpacing) },
                // Ligne 4
                { x: centerOffsetX, y: centerOffsetY + 3 * (finalCardHeight + reducedSpacing) },
                { x: centerOffsetX + finalCardWidth + reducedSpacing, y: centerOffsetY + 3 * (finalCardHeight + reducedSpacing) },
                // Ligne 5
                { x: centerOffsetX, y: centerOffsetY + 4 * (finalCardHeight + reducedSpacing) },
                { x: centerOffsetX + finalCardWidth + reducedSpacing, y: centerOffsetY + 4 * (finalCardHeight + reducedSpacing) },
            ];

            // Ajouter les cartes au PDF
            for (let i = 0; i < cardDataUrls.length; i++) {
                // Nouvelle page après chaque 10 cartes
                if (i > 0 && i % 10 === 0) {
                    pdf.addPage();
                }

                const positionIndex = i % 10;
                const position = positions[positionIndex];

                pdf.addImage(
                    cardDataUrls[i],
                    'PNG',
                    position.x,
                    position.y,
                    finalCardWidth,
                    finalCardHeight
                );
            }

            // Télécharger le PDF
            const filename = `cartes_eleves_${series?.name?.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`;
            pdf.save(filename);

            Swal.fire({
                title: 'Succès !',
                html: `PDF généré avec succès !<br>${cardDataUrls.length} carte(s) sur ${Math.ceil(cardDataUrls.length / 10)} page(s) A4<br>Format: 10 cartes par page (2×5)`,
                icon: 'success',
                timer: 3000
            });

        } catch (error) {
            console.error('Erreur génération PDF:', error);
            Swal.fire({
                title: 'Erreur',
                text: `Erreur lors de la génération du PDF: ${error.message}`,
                icon: 'error'
            });
        } finally {
            setLoading(false);
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
            mother_name: '',
            mother_phone: '',
            address: '',
            class_series_id: seriesId,
            student_status: 'new',
            arrival_trimester: '',
            newcomer_discount_reason: '',
            has_scholarship_enabled: false,
            // school_year_id géré automatiquement par le backend
        });
        setSelectedStudent(null);
        setStudentPhoto(null);
        setPhotoPreview('');
        stopCamera(); // Arrêter la caméra si elle est active
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
        
        const matchesSearch = fullName.includes(searchTerm.toLowerCase()) || 
               parentName.includes(searchTerm.toLowerCase()) ||
               (student.student_number || '').toLowerCase().includes(searchTerm.toLowerCase());
        
        const matchesStatus = statusFilter === 'all' || 
            (statusFilter === 'new' && (student.student_status === 'new' || !student.student_status)) ||
            (statusFilter === 'old' && student.student_status === 'old');
        
        return matchesSearch && matchesStatus;
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
                            <div className="d-flex gap-2">
                                <StudentsExportButton
                                    seriesId={seriesId}
                                    seriesName={series?.name || ''}
                                />
                                <ImportExportButton
                                    title="Élèves"
                                    apiBasePath="/api/students"
                                    onImportSuccess={loadStudents}
                                    templateFileName="template_eleves.csv"
                                    seriesId={seriesId}
                                />
                                <BulkPhotoUpload
                                    onUploadSuccess={loadStudents}
                                />
                                <button
                                    className="btn btn-info d-flex align-items-center gap-2 me-2"
                                    onClick={() => setShowStudentsListModal(true)}
                                >
                                    <List size={16} />
                                    Liste des élèves
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
                                <div className="col-md-3">
                                    <label className="form-label">Statut</label>
                                    <select
                                        className="form-select"
                                        value={statusFilter}
                                        onChange={(e) => setStatusFilter(e.target.value)}
                                    >
                                        <option value="all">Tous les élèves</option>
                                        <option value="new">Nouveaux élèves</option>
                                        <option value="old">Anciens élèves</option>
                                    </select>
                                </div>
                                <div className="col-md-3 d-flex align-items-end">
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
                                            className="btn btn-warning me-2"
                                            onClick={handleSortAlphabetically}
                                            title="Reclasser par ordre alphabétique"
                                        >
                                            <SortAlphaDown size={16} className="me-1" />
                                            Ordre alphabétique
                                        </button>
                                    {/* )} */}
                                    <button
                                        className="btn btn-danger"
                                        onClick={generateStudentCardsPDF}
                                        title="Télécharger les cartes des élèves en PDF (10 par page)"
                                        disabled={loading || students.length === 0}
                                    >
                                        <FileEarmarkPdf size={16} className="me-1" />
                                        Cartes PDF
                                    </button>
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
                                                    <StudentPhoto student={student} size={40} className="me-3" />
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
                                                
                                                {/* Indicateur de bourse */}
                                                <div className="d-flex align-items-center">
                                                    <CashCoin size={14} className="text-muted me-2 flex-shrink-0" />
                                                    <small>
                                                        {student.has_scholarship_enabled ? (
                                                            <span className="badge bg-success bg-opacity-25 text-success">
                                                                Bourse activée
                                                            </span>
                                                        ) : (
                                                            <span className="badge bg-secondary bg-opacity-25 text-muted">
                                                                Bourse désactivée
                                                            </span>
                                                        )}
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div className="d-flex justify-content-end mt-auto">
                                                <StudentActionsDropdown
                                                    student={student}
                                                    onPrintCard={handlePrintCard}
                                                    onTransfer={handleTransferStudent}
                    onTransferWithinClass={handleTransferWithinClass}
                                                    onEdit={handleEdit}
                                                    onDelete={handleDelete}
                                                    onViewPayments={handleViewPayments}
                                                    onViewStudent={handleViewStudent}
                                                    onApplyEmployeeDiscount={handleApplyEmployeeDiscount}
                                                    userRole={user?.role}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        // Vue en liste (tableau) avec drag & drop
                        <div className="card" style={{ overflow: 'visible' }}>
                            <div className="card-body p-0">
                                <div className="table-responsive"  style={{ overflow: 'visible' }}>
                                    <DndContext
                                        sensors={sensors}
                                        collisionDetection={closestCenter}
                                        onDragEnd={handleDragEnd}
                                    >
                                        <table className="table table-hover mb-0">
                                            <thead className="table-light">
                                                <tr>
                                                    <th>Photo & N°</th>
                                                    <th>Nom & Prénom</th>
                                                    <th>Date/Lieu naissance</th>
                                                    <th>Sexe</th>
                                                    <th>Statut</th>
                                                    <th>Parent</th>
                                                    <th>Contact</th>
                                                    <th>Bourse</th>
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
                                                            handlePrintCard={handlePrintCard}
                                                            handlePreviewCard={handlePreviewCard}
                                                            handleTransferStudent={handleTransferStudent}
                                                            handleTransferWithinClass={handleTransferWithinClass}
                                                            handleViewStudent={handleViewStudent}
                                                            handleViewPayments={handleViewPayments}
                                                            handleApplyEmployeeDiscount={handleApplyEmployeeDiscount}
                                                            handleStatusChange={handleStatusChange}
                                                            navigate={navigate}
                                                            userRole={user?.role}
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
                                    {/* Section Photo */}
                                    <div className="row mb-4">
                                        <div className="col-12">
                                            <label className="form-label">Photo de l'élève</label>
                                            <div className="d-flex flex-column align-items-center">
                                                {/* Aperçu de la photo */}
                                                <div className="mb-3 position-relative" style={{ width: '150px', height: '150px' }}>
                                                    {photoPreview ? (
                                                        <>
                                                            <img 
                                                                src={photoPreview} 
                                                                alt="Aperçu photo élève" 
                                                                className="img-thumbnail border-2 shadow-sm"
                                                                style={{ width: '150px', height: '150px', objectFit: 'cover' }}
                                                            />
                                                            <button
                                                                type="button"
                                                                className="btn btn-sm btn-danger position-absolute"
                                                                onClick={removePhoto}
                                                                style={{ top: '-8px', right: '-8px' }}
                                                                title="Supprimer la photo"
                                                            >
                                                                <X size={12} />
                                                            </button>
                                                        </>
                                                    ) : (
                                                        <div 
                                                            className="d-flex flex-column align-items-center justify-content-center border border-dashed border-2 rounded h-100"
                                                            style={{ backgroundColor: '#f8f9fa' }}
                                                        >
                                                            <Person size={48} className="text-muted mb-2" />
                                                            <small className="text-muted text-center">Aucune photo</small>
                                                        </div>
                                                    )}
                                                </div>
                                                
                                                {/* Boutons d'action photo */}
                                                <div className="d-flex gap-2 mb-3">
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-primary btn-sm"
                                                        onClick={startCamera}
                                                    >
                                                        <Camera size={16} className="me-1" />
                                                        Capturer
                                                    </button>
                                                    <label className="btn btn-outline-secondary btn-sm" htmlFor="photoUpload">
                                                        <Image size={16} className="me-1" />
                                                        Choisir fichier
                                                    </label>
                                                    <input
                                                        id="photoUpload"
                                                        type="file"
                                                        accept="image/*"
                                                        onChange={handlePhotoUpload}
                                                        style={{ display: 'none' }}
                                                    />
                                                </div>
                                                
                                                {/* Modal caméra */}
                                                {showCamera && (
                                                    <div className="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
                                                         style={{ backgroundColor: 'rgba(0,0,0,0.8)', zIndex: 9999 }}>
                                                        <div className="bg-white p-4 rounded">
                                                            <div className="d-flex justify-content-between align-items-center mb-3">
                                                                <h5 className="mb-0">Capture Photo</h5>
                                                                <button
                                                                    type="button"
                                                                    className="btn-close"
                                                                    onClick={stopCamera}
                                                                ></button>
                                                            </div>
                                                            <video
                                                                id="cameraVideo"
                                                                autoPlay
                                                                playsInline
                                                                ref={(video) => {
                                                                    if (video && cameraStream) {
                                                                        video.srcObject = cameraStream;
                                                                    }
                                                                }}
                                                                style={{ width: '400px', height: '300px' }}
                                                                className="border rounded mb-3"
                                                            />
                                                            <div className="d-flex justify-content-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-primary"
                                                                    onClick={capturePhoto}
                                                                >
                                                                    <Camera size={16} className="me-1" />
                                                                    Capturer
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-secondary"
                                                                    onClick={stopCamera}
                                                                >
                                                                    Annuler
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>

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
                                        <div className="col-md-4">
                                            <div className="mb-3">
                                                <label className="form-label">Sexe *</label>
                                                <select
                                                    className="form-select"
                                                    value={formData.gender}
                                                    onChange={(e) => setFormData({...formData, gender: e.target.value})}
                                                    required
                                                >
                                                    <option value="">Sélectionner</option>
                                                    <option value="M">Masculin</option>
                                                    <option value="F">Féminin</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div className="col-md-4">
                                            <div className="mb-3">
                                                <label className="form-label">Statut</label>
                                                <select
                                                    className="form-select"
                                                    value={formData.student_status || 'new'}
                                                    onChange={(e) => {
                                                        const newStatus = e.target.value;
                                                        setFormData(prev => ({
                                                            ...prev,
                                                            student_status: newStatus,
                                                            // Si on passe en "ancien", on efface les infos de réduction
                                                            ...(newStatus !== 'new'
                                                                ? { arrival_trimester: '', newcomer_discount_reason: '' }
                                                                : {}),
                                                        }));
                                                    }}
                                                >
                                                    <option value="new">Nouveau élève</option>
                                                    <option value="old">Ancien élève</option>
                                                </select>
                                            </div>
                                        </div>

                                        {/* Réduction scolarité (nouveau élève) */}
                                        {(formData.student_status || 'new') === 'new' && (
                                            <>
                                                <div className="col-md-4">
                                                    <div className="mb-3">
                                                        <label className="form-label">Trimestre d'arrivée (pour réduction manuelle)</label>
                                                        <select
                                                            className="form-select"
                                                            value={formData.arrival_trimester || ''}
                                                            onChange={(e) => setFormData({ ...formData, arrival_trimester: e.target.value })}
                                                        >
                                                            <option value="">Aucun</option>
                                                            <option value="2">2e trimestre</option>
                                                            <option value="3">3e trimestre</option>
                                                        </select>
                                                        <small className="text-muted">
                                                            Le montant de réduction sera saisi manuellement lors du paiement
                                                        </small>
                                                    </div>
                                                </div>
                                            </>
                                        )}
                                        <div className="col-md-4">
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

                                    {/* Informations mère */}
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Nom de la mère</label>
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    value={formData.mother_name}
                                                    onChange={(e) => setFormData({...formData, mother_name: e.target.value})}
                                                />
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Téléphone mère</label>
                                                <input
                                                    type="tel"
                                                    className="form-control"
                                                    value={formData.mother_phone}
                                                    onChange={(e) => setFormData({...formData, mother_phone: e.target.value})}
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

                                    {/* Motif réduction (texte libre) - OBLIGATOIRE si trimestre 2 ou 3 */}
                                    {(formData.student_status || 'new') === 'new' && (
                                        <div className="mb-3">
                                            <label className="form-label">
                                                Motif réduction scolarité (texte libre)
                                                {(formData.arrival_trimester === '2' || formData.arrival_trimester === '3') && (
                                                    <span className="text-danger"> *</span>
                                                )}
                                            </label>
                                            <textarea
                                                className="form-control"
                                                rows="2"
                                                value={formData.newcomer_discount_reason || ''}
                                                onChange={(e) => setFormData({ ...formData, newcomer_discount_reason: e.target.value })}
                                                placeholder="Ex: Nouveau élève arrivé au 2e trimestre..."
                                                required={(formData.arrival_trimester === '2' || formData.arrival_trimester === '3')}
                                            />
                                            {(formData.arrival_trimester === '2' || formData.arrival_trimester === '3') && (
                                                <small className="text-danger">
                                                    Le motif est obligatoire pour les arrivées au trimestre 2 ou 3
                                                </small>
                                            )}
                                        </div>
                                    )}
                                    
                                    <div className="mb-3">
                                        <div className="form-check">
                                            <input
                                                className="form-check-input"
                                                type="checkbox"
                                                id="scholarshipEnabled"
                                                checked={formData.has_scholarship_enabled}
                                                onChange={(e) => setFormData({...formData, has_scholarship_enabled: e.target.checked})}
                                            />
                                            <label className="form-check-label" htmlFor="scholarshipEnabled">
                                                <strong>Activer les bourses pour cet élève</strong>
                                                <br />
                                                <small className="text-muted">
                                                    Si coché, l'élève pourra bénéficier des bourses configurées pour sa classe lors des paiements
                                                </small>
                                            </label>
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
                                        <div className="d-flex justify-content-between align-items-center mb-2">
                                            <label className="form-label mb-0">Fichier CSV *</label>
                                            <button
                                                type="button"
                                                className="btn btn-sm btn-outline-info"
                                                onClick={handleDownloadTemplate}
                                                title="Télécharger le modèle CSV"
                                            >
                                                <Download size={14} className="me-1" />
                                                Modèle CSV
                                            </button>
                                        </div>
                                        <input
                                            type="file"
                                            className="form-control"
                                            accept=".csv,.txt"
                                            onChange={(e) => {
                                                const file = e.target.files[0];
                                                setImportFile(file);
                                                if (file) {
                                                    handleFilePreview(file);
                                                }
                                            }}
                                            required
                                        />
                                        <div className="form-text">
                                            Le fichier doit contenir les colonnes : Nom, Prénom, Date naissance, Lieu naissance, Sexe, Nom parent, Téléphone parent (optionnel), Email parent (optionnel), Adresse (optionnel)
                                            <br />
                                            <small className="text-muted">💡 Cliquez sur "Modèle CSV" pour télécharger un exemple de format</small>
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
            <style>{`
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

            {/* Modal d'impression de carte scolaire */}
            {showCardPrint && newStudentForCard && (
                <StudentCardPrint
                    student={newStudentForCard}
                    schoolYear={schoolYear}
                    show={showCardPrint}
                    onHide={() => {
                        setShowCardPrint(false);
                        setNewStudentForCard(null);
                    }}
                    onPrintSuccess={() => {
                        console.log('Carte imprimée avec succès');
                    }}
                />
            )}

            {/* Modal de transfert d'élève */}
            {showTransferModal && studentToTransfer && (
                <StudentTransfer
                    student={studentToTransfer}
                    show={showTransferModal}
                    onHide={() => {
                        setShowTransferModal(false);
                        setStudentToTransfer(null);
                    }}
                    onTransferSuccess={handleTransferSuccess}
                />
            )}

            {/* Modal de transfert au sein de la même classe */}
            {showTransferWithinClassModal && studentToTransfer && (
                <StudentTransferWithinClass
                    student={studentToTransfer}
                    show={showTransferWithinClassModal}
                    onHide={() => {
                        setShowTransferWithinClassModal(false);
                        setStudentToTransfer(null);
                    }}
                    onTransferSuccess={handleTransferSuccess}
                />
            )}

            {/* Modal Réduction employé */}
            {showEmployeeDiscountModal && selectedStudent && (
                <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content">
                            <div className="modal-header bg-warning bg-opacity-10">
                                <h5 className="modal-title">
                                    <span className="text-warning me-2">💰</span>
                                    Réduction employé - {selectedStudent.first_name} {selectedStudent.last_name}
                                </h5>
                                <button
                                    type="button"
                                    className="btn-close"
                                    onClick={() => {
                                        setShowEmployeeDiscountModal(false);
                                        setEmployeeDiscountForm({ amount: '', reason: '' });
                                        setSelectedStudent(null);
                                    }}
                                ></button>
                            </div>
                            <div className="modal-body">
                                <div className="alert alert-info">
                                    <small>
                                        <strong>Information :</strong> Cette réduction sera appliquée aux frais de scolarité uniquement (pas à l'inscription).
                                    </small>
                                </div>

                                <div className="mb-3">
                                    <label className="form-label">
                                        Montant de la réduction (FCFA) <span className="text-danger">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        placeholder="Ex: 50000"
                                        min="0"
                                        step="1000"
                                        value={employeeDiscountForm.amount}
                                        onChange={(e) => setEmployeeDiscountForm({
                                            ...employeeDiscountForm,
                                            amount: e.target.value
                                        })}
                                    />
                                </div>

                                <div className="mb-3">
                                    <label className="form-label">
                                        Motif de la réduction <span className="text-danger">*</span>
                                    </label>
                                    <textarea
                                        className="form-control"
                                        rows="3"
                                        placeholder="Ex: Réduction employé - Parent travaillant à l'établissement"
                                        value={employeeDiscountForm.reason}
                                        onChange={(e) => setEmployeeDiscountForm({
                                            ...employeeDiscountForm,
                                            reason: e.target.value
                                        })}
                                    />
                                    <small className="text-muted">
                                        Le motif apparaîtra sur les reçus et dans l'historique de paiement.
                                    </small>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button
                                    type="button"
                                    className="btn btn-secondary"
                                    onClick={() => {
                                        setShowEmployeeDiscountModal(false);
                                        setEmployeeDiscountForm({ amount: '', reason: '' });
                                        setSelectedStudent(null);
                                    }}
                                >
                                    Annuler
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-warning"
                                    onClick={handleSubmitEmployeeDiscount}
                                >
                                    Appliquer la réduction
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal Liste des élèves */}
            {showStudentsListModal && (
                <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-xl">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">
                                    <List className="me-2" />
                                    Liste des élèves - {series?.name}
                                </h5>
                                <button
                                    type="button"
                                    className="btn-close"
                                    onClick={() => setShowStudentsListModal(false)}
                                ></button>
                            </div>
                            <div className="modal-body">
                                <div className="table-responsive">
                                    <table className="table table-striped table-hover">
                                        <thead className="table-dark">
                                            <tr>
                                                <th>Numéro</th>
                                                <th>Nom et Prénom</th>
                                                <th>Date de naissance</th>
                                                <th>Nom du père avec numéro</th>
                                                <th>Nom de la mère avec numéro</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {students.map((student, index) => (
                                                <tr key={student.id}>
                                                    <td>
                                                        <span className="badge bg-primary">
                                                            {student.student_number || `N°${index + 1}`}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div className="d-flex align-items-center">
                                                            <StudentPhoto student={student} size={32} className="me-2" />
                                                            <div>
                                                                <div className="fw-bold">
                                                                    {student.last_name || student.subname} {student.first_name || student.name}
                                                                </div>
                                                                {student.gender && (
                                                                    <small className={`text-${student.gender === 'M' ? 'info' : 'pink'}`}>
                                                                        {student.gender === 'M' ? 'Masculin' : 'Féminin'}
                                                                    </small>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        {student.date_of_birth ? (
                                                            <div>
                                                                <Calendar size={14} className="me-1 text-muted" />
                                                                {new Date(student.date_of_birth).toLocaleDateString('fr-FR')}
                                                                {student.place_of_birth && (
                                                                    <div>
                                                                        <small className="text-muted">
                                                                            <GeoAlt size={12} className="me-1" />
                                                                            {student.place_of_birth}
                                                                        </small>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <span className="text-muted">Non renseigné</span>
                                                        )}
                                                    </td>
                                                    <td>
                                                        {student.parent_name || student.father_name ? (
                                                            <div>
                                                                <div className="fw-medium">
                                                                    <Person size={14} className="me-1 text-primary" />
                                                                    {student.parent_name || student.father_name}
                                                                </div>
                                                                {student.parent_phone && (
                                                                    <div>
                                                                        <small className="text-success">
                                                                            <Telephone size={12} className="me-1" />
                                                                            {student.parent_phone}
                                                                        </small>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <span className="text-muted">Non renseigné</span>
                                                        )}
                                                    </td>
                                                    <td>
                                                        {student.mother_name ? (
                                                            <div>
                                                                <div className="fw-medium">
                                                                    <Person size={14} className="me-1 text-danger" />
                                                                    {student.mother_name}
                                                                </div>
                                                                {student.mother_phone && (
                                                                    <div>
                                                                        <small className="text-success">
                                                                            <Telephone size={12} className="me-1" />
                                                                            {student.mother_phone}
                                                                        </small>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <span className="text-muted">Non renseigné</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {students.length === 0 && (
                                    <div className="text-center py-4">
                                        <Person size={48} className="text-muted mb-3" />
                                        <p className="text-muted">Aucun élève trouvé dans cette série.</p>
                                    </div>
                                )}
                            </div>
                            <div className="modal-footer">
                                <div className="me-auto">
                                    <small className="text-muted">
                                        Total: {students.length} élève{students.length > 1 ? 's' : ''}
                                    </small>
                                </div>
                                <button
                                    type="button"
                                    className="btn btn-danger me-2"
                                    onClick={downloadStudentsListPdf}
                                    disabled={loading || students.length === 0}
                                >
                                    <Download size={16} className="me-2" />
                                    Télécharger PDF
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-secondary"
                                    onClick={() => setShowStudentsListModal(false)}
                                >
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default SeriesStudents;