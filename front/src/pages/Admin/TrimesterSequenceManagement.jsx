import React, { useState, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { 
    Card, Row, Col, Button, Alert, Modal, Form, Table,
    Badge, ProgressBar, Tabs, Tab, Spinner
} from 'react-bootstrap';
import { 
    Calendar, Clock, Play, Pause, Plus, Pencil,
    CheckCircle, XCircle, Gear, BarChart, Trash2, CheckSquare
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import TrimesterAcademicView from '../../components/TrimesterAcademicView';

const TrimesterSequenceManagement = () => {
    const { user } = useAuth();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    // États des données
    const [schoolYear, setSchoolYear] = useState(null);
    const [trimesters, setTrimesters] = useState([]);
    const [sequences, setSequences] = useState([]);
    const [evaluations, setEvaluations] = useState([]);

    // États des modals
    const [showCreateTrimester, setShowCreateTrimester] = useState(false);
    const [showEditTrimester, setShowEditTrimester] = useState(false);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [showCreateSequences, setShowCreateSequences] = useState(false);
    const [selectedTrimester, setSelectedTrimester] = useState(null);

    // États des formulaires
    const [trimesterForm, setTrimesterForm] = useState({
        name: '',
        number: 1,
        start_date: '',
        end_date: '',
        is_active: true
    });
    
    const [editingTrimester, setEditingTrimester] = useState(null);
    const [forceDelete, setForceDelete] = useState(false);
    const [deleteInfo, setDeleteInfo] = useState(null);

    useEffect(() => {
        loadData();
    }, []);

    const checkAndCreateMissingCompositions = async () => {
        try {
            console.log('=== Vérification des compositions manquantes ===');
            let compositionsCreated = 0;

            for (const trimester of trimesters) {
                // Vérifier si ce trimestre a des séquences normales terminées
                const trimesterSequences = sequences.filter(seq => 
                    seq.trimester_id === trimester.id && 
                    !seq.is_composition
                );

                // Vérifier si une composition existe déjà pour ce trimestre
                const existingComposition = sequences.find(seq => 
                    seq.trimester_id === trimester.id && 
                    seq.is_composition === true
                );

                // Si des séquences existent mais pas de composition, et que toutes les séquences sont terminées
                const allSequencesCompleted = trimesterSequences.length > 0 && 
                    trimesterSequences.every(seq => seq.is_completed);

                if (trimesterSequences.length > 0 && !existingComposition && allSequencesCompleted) {
                    console.log(`Création de composition manquante pour trimestre ${trimester.number}`);
                    try {
                        await createMissingComposition(trimester);
                        compositionsCreated++;
                    } catch (error) {
                        console.error(`Erreur création composition pour trimestre ${trimester.number}:`, error);
                    }
                }
            }

            if (compositionsCreated > 0) {
                setSuccess(`✨ ${compositionsCreated} composition(s) manquante(s) créée(s) automatiquement !`);
                await loadData(); // Recharger les données
                setTimeout(() => setSuccess(null), 5000);
            }
        } catch (error) {
            console.error('Erreur vérification compositions manquantes:', error);
        }
    };

    const loadData = async () => {
        try {
            setLoading(true);
            setError(null);

            // DEBUG LOGS
            console.log('=== DEBUG loadData ===');

            // Charger année scolaire courante
            console.log('Chargement année scolaire courante...');
            const yearResponse = await secureApiEndpoints.schoolYears.getCurrent();
            console.log('yearResponse:', yearResponse);
            console.log('yearResponse.data:', yearResponse.data);
            
            // CORRECTION: Si yearResponse.data est un tableau, prendre le premier élément
            const schoolYearData = Array.isArray(yearResponse.data) ? yearResponse.data[0] : yearResponse.data;
            console.log('schoolYearData after fix:', schoolYearData);
            setSchoolYear(schoolYearData);

            // Charger trimestres existants
            console.log('Chargement trimestres avec school_year_id:', schoolYearData.id);
            const trimestersResponse = await secureApiEndpoints.trimesters.getAll({
                school_year_id: schoolYearData.id
            });
            console.log('trimestersResponse:', trimestersResponse);
            setTrimesters(trimestersResponse.data || []);

            // Charger séquences
            console.log('Chargement séquences...');
            const sequencesResponse = await secureApiEndpoints.sequences.getAll({
                school_year_id: schoolYearData.id
            });
            console.log('sequencesResponse:', sequencesResponse);
            const allSequences = sequencesResponse.data || [];
            console.log('All sequences loaded:', allSequences);
            
            // Debug: compter les compositions
            const compositions = allSequences.filter(seq => seq.is_composition);
            console.log('Compositions found:', compositions.length, compositions);
            
            setSequences(allSequences);

            // Charger évaluations
            console.log('Chargement évaluations...');
            try {
                const evaluationsResponse = await secureApiEndpoints.evaluations.getAll({
                    school_year_id: schoolYearData.id
                });
                console.log('evaluationsResponse:', evaluationsResponse);
                setEvaluations(evaluationsResponse.data || []);
            } catch (evalError) {
                console.warn('Erreur chargement évaluations (non critique):', evalError);
                setEvaluations([]);
            }

            console.log('=== FIN DEBUG loadData ===');

            // Vérifier et créer les compositions manquantes après chargement
            if (trimesters.length > 0 && allSequences.length > 0) {
                setTimeout(() => {
                    checkAndCreateMissingCompositions();
                }, 1000); // Délai pour s'assurer que les états sont bien mis à jour
            }

        } catch (error) {
            console.error('ERREUR chargement:', error);
            console.error('Error message:', error.message);
            console.error('Stack trace:', error.stack);
            setError('Erreur lors du chargement des données');
        } finally {
            setLoading(false);
        }
    };

    const handleCreateTrimester = async () => {
        try {
            setError(null);
            
            // DEBUG LOGS
            console.log('=== DEBUG handleCreateTrimester ===');
            console.log('trimesterForm:', trimesterForm);
            console.log('schoolYear:', schoolYear);
            console.log('schoolYear.id:', schoolYear?.id);
            
            const trimesterData = {
                ...trimesterForm,
                school_year_id: schoolYear.id
            };
            
            console.log('trimesterData final:', trimesterData);
            console.log('Appel API secureApiEndpoints.trimesters.create...');

            const response = await secureApiEndpoints.trimesters.create(trimesterData);
            console.log('Réponse création manuelle:', response);
            
            setSuccess('Trimestre créé avec succès');
            setShowCreateTrimester(false);
            setTrimesterForm({
                name: '',
                number: 1,
                start_date: '',
                end_date: '',
                is_active: true
            });
            
            await loadData();
            setTimeout(() => setSuccess(null), 3000);

        } catch (error) {
            console.error('ERREUR création trimestre manuelle:', error);
            console.error('Error message:', error.message);
            console.error('Stack trace:', error.stack);
            setError('Erreur lors de la création du trimestre');
        }
    };

    const handleActivateTrimester = async (trimesterId) => {
        try {
            setError(null);
            await secureApiEndpoints.trimesters.activate(trimesterId);
            setSuccess('Trimestre activé avec succès');
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur activation:', error);
            setError('Erreur lors de l\'activation du trimestre');
        }
    };

    const handleActivateSequence = async (sequenceId) => {
        try {
            setError(null);
            await secureApiEndpoints.sequences.activate(sequenceId);
            setSuccess('Séquence activée avec succès');
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur activation séquence:', error);
            setError('Erreur lors de l\'activation de la séquence');
        }
    };

    const handleActivateComposition = async (compositionId) => {
        try {
            setError(null);
            
            // Activer la composition et déverrouiller
            await secureApiEndpoints.sequences.activate(compositionId);
            
            setSuccess('🎉 Composition activée ! Les enseignants peuvent maintenant saisir les notes de composition.');
            await loadData();
            setTimeout(() => setSuccess(null), 5000);
        } catch (error) {
            console.error('Erreur activation composition:', error);
            setError('Erreur lors de l\'activation de la composition');
        }
    };

    const createMissingComposition = async (trimester) => {
        try {
            console.log(`=== Création de Composition ${trimester.number} manquante ===`);
            
            // Créer la composition manquante
            const compositionName = `Composition ${trimester.number}`;
            const compositionSequenceData = {
                name: compositionName,
                number: trimester.number === 1 ? 3 : trimester.number === 2 ? 5 : 6, // Numéros : 3, 5, 6 pour les compositions
                trimester_id: trimester.id,
                school_year_id: trimester.school_year_id,
                start_date: new Date(new Date(trimester.end_date).getTime() - 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 14 jours avant la fin
                end_date: new Date(new Date(trimester.end_date).getTime() - 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 3 jours avant la fin
                is_active: false, // VERROUILLÉE par défaut
                is_current: false,
                is_completed: false,
                is_composition: true, // Marquer comme composition
                is_locked: true // Verrouillée par défaut
            };
            
            console.log('Données composition à créer:', compositionSequenceData);
            const createdComposition = await secureApiEndpoints.sequences.create(compositionSequenceData);
            console.log('Composition créée:', createdComposition);
            
            return createdComposition.data || createdComposition;
        } catch (error) {
            console.error('Erreur création composition manquante:', error);
            throw error;
        }
    };

    const checkAndActivateComposition = async (completedSequenceId) => {
        try {
            // Récupérer la séquence terminée pour connaître son trimestre
            const completedSequence = sequences.find(seq => seq.id === completedSequenceId);
            if (!completedSequence) return;

            // Récupérer le trimestre correspondant
            const trimester = trimesters.find(t => t.id === completedSequence.trimester_id);
            if (!trimester) return;

            // Vérifier toutes les séquences NORMALES de ce trimestre (exclure les compositions)
            const trimesterSequences = sequences.filter(seq => 
                seq.trimester_id === trimester.id && 
                !seq.is_composition // Exclure les compositions de la vérification
            );
            const allSequencesCompleted = trimesterSequences.every(seq => 
                seq.id === completedSequenceId || seq.is_completed
            );

            if (allSequencesCompleted && trimesterSequences.length > 0) {
                console.log(`=== Toutes les séquences du trimestre ${trimester.number} sont terminées ===`);
                
                // Vérifier si la composition existe déjà
                let compositionSequences = sequences.filter(seq => 
                    seq.trimester_id === trimester.id && 
                    seq.is_composition === true
                );

                // Si aucune composition n'existe, la créer
                if (compositionSequences.length === 0) {
                    console.log(`Aucune composition trouvée pour le trimestre ${trimester.number}, création...`);
                    try {
                        const newComposition = await createMissingComposition(trimester);
                        console.log('Nouvelle composition créée:', newComposition);
                        
                        // Recharger les données pour avoir la nouvelle composition
                        await loadData();
                        
                        // Récupérer la nouvelle composition dans les données mises à jour
                        compositionSequences = sequences.filter(seq => 
                            seq.trimester_id === trimester.id && 
                            seq.is_composition === true
                        );
                        
                        setSuccess(`✨ Composition ${trimester.number} créée automatiquement et prête à être activée !`);
                    } catch (createError) {
                        console.error('Erreur lors de la création de composition:', createError);
                        setError(`Erreur lors de la création de Composition ${trimester.number}`);
                        return;
                    }
                }

                // Maintenant activer les compositions non actives
                const inactiveCompositions = compositionSequences.filter(comp => !comp.is_active);
                
                for (const compositionSeq of inactiveCompositions) {
                    try {
                        // Utiliser la méthode activate au lieu de update
                        await secureApiEndpoints.sequences.activate(compositionSeq.id);
                        
                        console.log(`Composition ${trimester.number} activée automatiquement`);
                        setSuccess(`🎉 Toutes les séquences terminées ! Composition ${trimester.number} maintenant disponible pour la saisie des notes.`);
                    } catch (compositionError) {
                        console.error('Erreur activation composition:', compositionError);
                        setError(`Erreur lors de l'activation de Composition ${trimester.number}`);
                    }
                }
                
                // Recharger les données après activation
                await loadData();
            }
        } catch (error) {
            console.error('Erreur vérification composition:', error);
            setError('Erreur lors de la vérification des compositions');
        }
    };

    const handleMarkSequenceCompleted = async (sequenceId) => {
        try {
            setError(null);
            await secureApiEndpoints.sequences.markCompleted(sequenceId);
            
            // Vérifier si toutes les séquences du trimestre sont terminées pour déverrouiller la composition
            await checkAndActivateComposition(sequenceId);
            
            setSuccess('Séquence marquée comme terminée');
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur marquage terminé:', error);
            setError('Erreur lors du marquage comme terminé');
        }
    };

    const handleMarkSequenceIncomplete = async (sequenceId) => {
        try {
            setError(null);
            await secureApiEndpoints.sequences.markIncomplete(sequenceId);
            setSuccess('Séquence marquée comme non terminée');
            await loadData();
            setTimeout(() => setSuccess(null), 3000);
        } catch (error) {
            console.error('Erreur marquage non terminé:', error);
            setError('Erreur lors du marquage comme non terminé');
        }
    };

    const handleEditTrimester = (trimester) => {
        setEditingTrimester(trimester);
        setTrimesterForm({
            name: trimester.name,
            number: trimester.number,
            start_date: trimester.start_date ? trimester.start_date.split('T')[0] : '',
            end_date: trimester.end_date ? trimester.end_date.split('T')[0] : '',
            is_active: trimester.is_active
        });
        setShowEditTrimester(true);
    };

    const handleUpdateTrimester = async () => {
        try {
            setError(null);
            
            console.log('=== DEBUG handleUpdateTrimester ===');
            console.log('editingTrimester:', editingTrimester);
            console.log('trimesterForm:', trimesterForm);
            
            const response = await secureApiEndpoints.trimesters.update(editingTrimester.id, trimesterForm);
            console.log('Réponse mise à jour:', response);
            
            setSuccess('Trimestre mis à jour avec succès');
            setShowEditTrimester(false);
            setEditingTrimester(null);
            setTrimesterForm({
                name: '',
                number: 1,
                start_date: '',
                end_date: '',
                is_active: true
            });
            
            await loadData();
            setTimeout(() => setSuccess(null), 3000);

        } catch (error) {
            console.error('ERREUR mise à jour trimestre:', error);
            setError(error.message || 'Erreur lors de la mise à jour du trimestre');
        }
    };

    const handleDeleteTrimester = (trimester) => {
        setSelectedTrimester(trimester);
        setForceDelete(false);
        setDeleteInfo(null);
        setShowDeleteConfirm(true);
    };

    const confirmDeleteTrimester = async () => {
        try {
            setError(null);
            
            console.log('=== DEBUG confirmDeleteTrimester ===');
            console.log('selectedTrimester:', selectedTrimester);
            console.log('forceDelete:', forceDelete);
            
            const response = await secureApiEndpoints.trimesters.delete(selectedTrimester.id, { force: forceDelete });
            console.log('Réponse suppression:', response);
            
            setSuccess(response.message || 'Trimestre supprimé avec succès');
            setShowDeleteConfirm(false);
            setSelectedTrimester(null);
            setForceDelete(false);
            setDeleteInfo(null);
            
            await loadData();
            setTimeout(() => setSuccess(null), 4000);

        } catch (error) {
            console.error('ERREUR suppression trimestre:', error);
            
            // Si l'erreur contient des informations sur les dépendances, les afficher
            if (error.message && error.message.includes('séquence(s)') && !forceDelete) {
                // Simuler les données de dépendance pour l'affichage (à défaut de les récupérer du backend)
                const sequencesMatch = error.message.match(/(\d+)\s+séquence\(s\)/);
                const evaluationsMatch = error.message.match(/(\d+)\s+évaluation\(s\)/);
                
                setDeleteInfo({
                    sequences_count: sequencesMatch ? parseInt(sequencesMatch[1]) : 0,
                    evaluations_count: evaluationsMatch ? parseInt(evaluationsMatch[1]) : 0,
                    can_force_delete: true
                });
            } else {
                setError(error.message || 'Erreur lors de la suppression du trimestre');
            }
        }
    };

    const generateDefaultTrimesters = async () => {
        try {
            setError(null);
            setLoading(true);

            // DEBUG LOGS
            console.log('=== DEBUG generateDefaultTrimesters ===');
            console.log('schoolYear:', schoolYear);
            console.log('schoolYear.id:', schoolYear?.id);
            console.log('schoolYear.start_date:', schoolYear?.start_date);
            console.log('schoolYear.end_date:', schoolYear?.end_date);

            // Vérifier les trimestres existants pour cette année scolaire
            console.log('Appel getAll avec school_year_id:', schoolYear.id);
            const existingTrimesters = await secureApiEndpoints.trimesters.getAll({
                school_year_id: schoolYear.id
            });
            console.log('existingTrimesters response:', existingTrimesters);

            // Vérifier et corriger les dates
            let startYear, endYear;
            
            if (schoolYear.start_date && schoolYear.end_date) {
                startYear = new Date(schoolYear.start_date).getFullYear();
                endYear = new Date(schoolYear.end_date).getFullYear();
            } else {
                // Dates par défaut si les dates de l'année scolaire sont manquantes
                const currentYear = new Date().getFullYear();
                startYear = currentYear;
                endYear = currentYear + 1;
                console.warn('Dates de l\'année scolaire manquantes, utilisation des dates par défaut');
            }
            
            console.log('startYear:', startYear, 'endYear:', endYear);
            
            // Vérifier que les années ne sont pas NaN
            if (isNaN(startYear) || isNaN(endYear)) {
                throw new Error('Impossible de déterminer les années de début et fin');
            }
            
            const defaultTrimesters = [
                {
                    name: 'Premier Trimestre',
                    number: 1,
                    start_date: `${startYear}-09-01`,
                    end_date: `${startYear}-12-15`,
                    is_active: true
                },
                {
                    name: 'Deuxième Trimestre', 
                    number: 2,
                    start_date: `${endYear}-01-08`,
                    end_date: `${endYear}-04-05`,
                    is_active: true
                },
                {
                    name: 'Troisième Trimestre',
                    number: 3, 
                    start_date: `${endYear}-04-22`,
                    end_date: `${endYear}-07-12`,
                    is_active: true
                }
            ];

            // Créer seulement les trimestres qui n'existent pas encore
            console.log('defaultTrimesters à créer:', defaultTrimesters);
            for (const trimesterData of defaultTrimesters) {
                const exists = existingTrimesters.data.some(t => t.number === trimesterData.number);
                console.log(`Trimestre ${trimesterData.number} exists:`, exists);
                if (!exists) {
                    const dataToSend = {
                        ...trimesterData,
                        school_year_id: schoolYear.id
                    };
                    console.log('Données à envoyer pour création:', dataToSend);
                    console.log('Appel API secureApiEndpoints.trimesters.create...');
                    
                    try {
                        const createResponse = await secureApiEndpoints.trimesters.create(dataToSend);
                        console.log('Réponse création:', createResponse);
                    } catch (createError) {
                        console.error('ERREUR lors de la création du trimestre:', createError);
                        console.error('Stack trace:', createError.stack);
                        throw createError; // Re-throw pour que le catch principal le capture
                    }
                }
            }

            // Activer le premier trimestre
            await loadData();
            if (trimesters.length > 0) {
                await handleActivateTrimester(trimesters[0].id);
            }

            setSuccess('Trimestres par défaut créés et configurés');
            setTimeout(() => setSuccess(null), 4000);

        } catch (error) {
            console.error('Erreur génération trimestres:', error);
            setError('Erreur lors de la génération des trimestres');
        } finally {
            setLoading(false);
        }
    };

    const generateSequencesForTrimester = async (trimester) => {
        try {
            setError(null);
            
            // Logique académique camerounaise
            let sequences = [];
            
            if (trimester.number === 1) {
                // Trimestre 1: Séquence 1, Séquence 2 + Composition 1
                const duration = (new Date(trimester.end_date) - new Date(trimester.start_date)) / 3; // 3 périodes
                
                // Séquence 1
                sequences.push({
                    name: 'Séquence 1',
                    number: 1,
                    trimester_id: trimester.id,
                    school_year_id: trimester.school_year_id,
                    start_date: trimester.start_date,
                    end_date: new Date(new Date(trimester.start_date).getTime() + duration).toISOString().split('T')[0],
                    is_active: true,
                    is_current: true
                });
                
                // Séquence 2
                sequences.push({
                    name: 'Séquence 2',
                    number: 2,
                    trimester_id: trimester.id,
                    school_year_id: trimester.school_year_id,
                    start_date: new Date(new Date(trimester.start_date).getTime() + duration).toISOString().split('T')[0],
                    end_date: new Date(new Date(trimester.start_date).getTime() + 2 * duration).toISOString().split('T')[0],
                    is_active: false,
                    is_current: false
                });
                
            } else if (trimester.number === 2) {
                // Trimestre 2: Séquence 3, Séquence 4 + Composition 2
                const duration = (new Date(trimester.end_date) - new Date(trimester.start_date)) / 3;
                
                // Séquence 3
                sequences.push({
                    name: 'Séquence 3',
                    number: 3,
                    trimester_id: trimester.id,
                    school_year_id: trimester.school_year_id,
                    start_date: trimester.start_date,
                    end_date: new Date(new Date(trimester.start_date).getTime() + duration).toISOString().split('T')[0],
                    is_active: false,
                    is_current: false
                });
                
                // Séquence 4
                sequences.push({
                    name: 'Séquence 4',
                    number: 4,
                    trimester_id: trimester.id,
                    school_year_id: trimester.school_year_id,
                    start_date: new Date(new Date(trimester.start_date).getTime() + duration).toISOString().split('T')[0],
                    end_date: new Date(new Date(trimester.start_date).getTime() + 2 * duration).toISOString().split('T')[0],
                    is_active: false,
                    is_current: false
                });
            }
            // Trimestre 3: Pas de séquences, seulement la composition 3

            // Créer les séquences
            for (const sequenceData of sequences) {
                await secureApiEndpoints.sequences.create(sequenceData);
            }

            // Créer la COMPOSITION comme une SÉQUENCE spéciale (verrouillée par défaut)
            const compositionName = `Composition ${trimester.number}`;
            const compositionSequenceData = {
                name: compositionName,
                number: trimester.number === 1 ? 3 : trimester.number === 2 ? 5 : 6, // Numéros : 3, 5, 6 pour les compositions
                trimester_id: trimester.id,
                school_year_id: trimester.school_year_id,
                start_date: new Date(new Date(trimester.end_date).getTime() - 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 14 jours avant la fin
                end_date: new Date(new Date(trimester.end_date).getTime() - 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 3 jours avant la fin
                is_active: false, // VERROUILLÉE par défaut
                is_current: false,
                is_completed: false,
                is_composition: true, // Marquer comme composition pour la différencier
                is_locked: true // Propriété pour indiquer qu'elle est verrouillée
            };
            
            // Créer la composition comme SÉQUENCE
            try {
                await secureApiEndpoints.sequences.create(compositionSequenceData);
                console.log(`Composition ${trimester.number} créée comme séquence`);
            } catch (compositionError) {
                console.error('Erreur création composition:', compositionError);
                // Ne pas faire échouer tout le processus si la composition échoue
            }

            setSuccess(`${sequences.length} séquence(s) et 1 composition générées pour ${trimester.name}`);
            await loadData();
            setTimeout(() => setSuccess(null), 4000);

        } catch (error) {
            console.error('Erreur génération séquences:', error);
            setError('Erreur lors de la génération des séquences et compositions');
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'Non définie';
        return new Date(dateString).toLocaleDateString('fr-FR');
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <div className="text-center">
                    <Spinner animation="border" variant="primary" />
                    <p className="mt-2">Chargement...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid p-4">
            {/* En-tête */}
            <Row className="mb-4">
                <Col>
                    <Card className="bg-dark text-white">
                        <Card.Body>
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 className="mb-1">
                                        <Gear className="me-2" />
                                        Gestion des Trimestres & Séquences
                                    </h4>
                                    <p className="mb-0 opacity-75">
                                        Configuration académique - {schoolYear?.name}
                                    </p>
                                </div>
                                <div className="d-flex gap-2">
                                    {trimesters.length === 0 ? (
                                        <Button 
                                            variant="success" 
                                            onClick={generateDefaultTrimesters}
                                        >
                                            <Plus className="me-1" />
                                            Générer Trimestres
                                        </Button>
                                    ) : (
                                        <>
                                            <Button 
                                                variant="outline-light" 
                                                onClick={checkAndCreateMissingCompositions}
                                                title="Vérifier et créer les compositions manquantes"
                                            >
                                                <CheckSquare className="me-1" />
                                                Vérifier Compositions
                                            </Button>
                                            <Button 
                                                variant="outline-light" 
                                                onClick={() => setShowCreateTrimester(true)}
                                            >
                                                <Plus className="me-1" />
                                                Nouveau Trimestre
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Messages */}
            {error && (
                <Alert variant="danger" dismissible onClose={() => setError(null)}>
                    <XCircle className="me-2" />
                    {error}
                </Alert>
            )}
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess(null)}>
                    <CheckCircle className="me-2" />
                    {success}
                </Alert>
            )}

            {/* Onglets */}
            <Tabs defaultActiveKey="academic-view" className="mb-4">
                
                {/* Vue Académique (nouvelle) */}
                <Tab eventKey="academic-view" title={
                    <span>
                        <BarChart className="me-1" />
                        Vue Académique
                    </span>
                }>
                    <TrimesterAcademicView
                        trimesters={trimesters}
                        sequences={sequences}
                        evaluations={evaluations}
                        loading={loading}
                        currentUser={user}
                        onActivateSequence={handleActivateSequence}
                        onCompleteSequence={handleMarkSequenceCompleted}
                        onActivateTrimester={handleActivateTrimester}
                    />
                </Tab>
                
                {/* Onglet Trimestres (gestion) */}
                <Tab eventKey="trimesters" title={
                    <span>
                        <Calendar className="me-1" />
                        Gestion Trimestres ({trimesters.length})
                    </span>
                }>
                    {trimesters.length === 0 ? (
                        <Card>
                            <Card.Body className="text-center p-5">
                                <Calendar size={48} className="text-muted mb-3" />
                                <h5 className="text-muted">Aucun trimestre configuré</h5>
                                <p className="text-muted mb-4">
                                    Générez les trimestres par défaut pour l'année {schoolYear?.name}
                                </p>
                                <Button variant="primary" onClick={generateDefaultTrimesters}>
                                    <Plus className="me-2" />
                                    Générer les 3 trimestres
                                </Button>
                            </Card.Body>
                        </Card>
                    ) : (
                        <Row>
                            {trimesters.map((trimester) => (
                                <Col lg={4} key={trimester.id} className="mb-4">
                                    <Card className={trimester.is_current ? 'border-success' : ''}>
                                        <Card.Header className={trimester.is_current ? 'bg-success text-white' : 'bg-light'}>
                                            <div className="d-flex justify-content-between align-items-center">
                                                <h5 className="mb-0">
                                                    {trimester.name}
                                                    {trimester.is_current && (
                                                        <Badge bg="light" text="success" className="ms-2">Actuel</Badge>
                                                    )}
                                                </h5>
                                                <small>Trimestre {trimester.number}</small>
                                            </div>
                                        </Card.Header>
                                        <Card.Body>
                                            <div className="mb-3">
                                                <small className="text-muted">Période</small>
                                                <div>
                                                    <Calendar className="me-1" />
                                                    {formatDate(trimester.start_date)} - {formatDate(trimester.end_date)}
                                                </div>
                                            </div>

                                            <div className="mb-3">
                                                <small className="text-muted">Séquences</small>
                                                <div>
                                                    {trimester.sequences?.length > 0 ? (
                                                        <Badge bg="info">{trimester.sequences.length} séquence(s)</Badge>
                                                    ) : (
                                                        <Button 
                                                            size="sm" 
                                                            variant="outline-primary"
                                                            onClick={() => generateSequencesForTrimester(trimester)}
                                                        >
                                                            <Plus className="me-1" />
                                                            Générer séquences
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="d-flex justify-content-between align-items-center">
                                                <div>
                                                    {trimester.is_current ? (
                                                        <Badge bg="success">Trimestre actuel</Badge>
                                                    ) : (
                                                        <Button 
                                                            size="sm" 
                                                            variant="outline-success"
                                                            onClick={() => handleActivateTrimester(trimester.id)}
                                                        >
                                                            <Play className="me-1" />
                                                            Activer
                                                        </Button>
                                                    )}
                                                </div>
                                                
                                                <div className="d-flex gap-1">
                                                    <Button 
                                                        size="sm" 
                                                        variant="outline-primary"
                                                        onClick={() => handleEditTrimester(trimester)}
                                                        title="Modifier le trimestre"
                                                    >
                                                        <Pencil size={16} />
                                                    </Button>
                                                    {!trimester.is_current && (
                                                        <Button 
                                                            size="sm" 
                                                            variant="outline-danger"
                                                            onClick={() => handleDeleteTrimester(trimester)}
                                                            title="Supprimer le trimestre"
                                                        >
                                                            <Trash2 size={16} />
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        </Card.Body>
                                    </Card>
                                </Col>
                            ))}
                        </Row>
                    )}
                </Tab>

                {/* Onglet Séquences */}
                <Tab eventKey="sequences" title={
                    <span>
                        <Clock className="me-1" />
                        Séquences ({sequences.length})
                    </span>
                }>
                    {sequences.length === 0 ? (
                        <Card>
                            <Card.Body className="text-center p-5">
                                <Clock size={48} className="text-muted mb-3" />
                                <h5 className="text-muted">Aucune séquence configurée</h5>
                                <p className="text-muted">
                                    Créez d'abord les trimestres pour générer automatiquement les séquences
                                </p>
                            </Card.Body>
                        </Card>
                    ) : (
                        <Card>
                            <Card.Body>
                                <div className="table-responsive">
                                    <Table hover>
                                        <thead className="table-dark">
                                            <tr>
                                                <th>Séquence</th>
                                                <th>Trimestre</th>
                                                <th>Période</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {sequences.map((sequence) => (
                                                <tr key={sequence.id}>
                                                    <td>
                                                        <strong>
                                                            {sequence.is_composition && '🏆 '}
                                                            {sequence.name}
                                                        </strong>
                                                        <div className="small text-muted">
                                                            {sequence.is_composition ? 'Composition' : `Séquence #${sequence.number}`}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <Badge bg="secondary" text="white">
                                                            {sequence.trimester?.name}
                                                        </Badge>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            {formatDate(sequence.start_date)} <br />
                                                            {formatDate(sequence.end_date)}
                                                        </small>
                                                    </td>
                                                    <td>
                                                        {sequence.is_locked ? (
                                                            <Badge bg="danger">Verrouillée</Badge>
                                                        ) : sequence.is_current ? (
                                                            <Badge bg="success">En cours</Badge>
                                                        ) : sequence.is_completed ? (
                                                            <Badge bg="info">Terminé</Badge>
                                                        ) : sequence.is_active ? (
                                                            <Badge bg="primary">Disponible</Badge>
                                                        ) : (
                                                            <Badge bg="secondary">Programmé</Badge>
                                                        )}
                                                    </td>
                                                    <td>
                                                        <div className="d-flex gap-1">
                                                            {sequence.is_composition ? (
                                                                /* Logique spéciale pour les COMPOSITIONS */
                                                                sequence.is_locked ? (
                                                                    /* Composition verrouillée : peut être activée manuellement */
                                                                    <>
                                                                        <Button 
                                                                            size="sm" 
                                                                            variant="success"
                                                                            onClick={() => handleActivateComposition(sequence.id)}
                                                                            title="Activer cette composition pour les enseignants"
                                                                        >
                                                                            <Play size={14} className="me-1" />
                                                                            Activer
                                                                        </Button>
                                                                        <small className="text-danger align-self-center ms-1">🔒</small>
                                                                    </>
                                                                ) : sequence.is_active ? (
                                                                    /* Composition active : afficher les options de gestion */
                                                                    <>
                                                                        <Button 
                                                                            size="sm" 
                                                                            variant="info"
                                                                            onClick={() => handleMarkSequenceCompleted(sequence.id)}
                                                                            title="Clôturer cette composition"
                                                                        >
                                                                            <CheckSquare size={14} className="me-1" />
                                                                            Clôturer
                                                                        </Button>
                                                                        <Button 
                                                                            size="sm" 
                                                                            variant="outline-warning"
                                                                            onClick={() => handleActivateComposition(sequence.id)}
                                                                            title="Réactiver/Relancer cette composition"
                                                                        >
                                                                            <Play size={14} />
                                                                        </Button>
                                                                        <small className="text-success align-self-center ms-1">✅</small>
                                                                    </>
                                                                ) : sequence.is_completed ? (
                                                                    /* Composition terminée : peut être réouverte */
                                                                    <>
                                                                        <Button 
                                                                            size="sm" 
                                                                            variant="outline-success"
                                                                            onClick={() => handleActivateComposition(sequence.id)}
                                                                            title="Réactiver cette composition"
                                                                        >
                                                                            <Play size={14} className="me-1" />
                                                                            Réactiver
                                                                        </Button>
                                                                        <Button 
                                                                            size="sm" 
                                                                            variant="outline-warning"
                                                                            onClick={() => handleMarkSequenceIncomplete(sequence.id)}
                                                                            title="Réouvrir (marquer comme programmé)"
                                                                        >
                                                                            <XCircle size={14} />
                                                                        </Button>
                                                                    </>
                                                                ) : (
                                                                    /* Composition programmée : peut être activée */
                                                                    <Button 
                                                                        size="sm" 
                                                                        variant="outline-success"
                                                                        onClick={() => handleActivateComposition(sequence.id)}
                                                                        title="Activer cette composition"
                                                                    >
                                                                        <Play size={14} className="me-1" />
                                                                        Activer
                                                                    </Button>
                                                                )
                                                            ) : (
                                                                /* Logique pour les SÉQUENCES normales */
                                                                sequence.is_current ? (
                                                                    /* Séquence en cours : peut être marquée comme terminée */
                                                                    <Button 
                                                                        size="sm" 
                                                                        variant="outline-info"
                                                                        onClick={() => handleMarkSequenceCompleted(sequence.id)}
                                                                        title="Marquer comme terminé"
                                                                    >
                                                                        <CheckSquare size={14} />
                                                                    </Button>
                                                                ) : sequence.is_completed ? (
                                                                    /* Séquence terminée : peut être réouverte */
                                                                    <Button 
                                                                        size="sm" 
                                                                        variant="outline-warning"
                                                                        onClick={() => handleMarkSequenceIncomplete(sequence.id)}
                                                                        title="Réouvrir (marquer comme programmé)"
                                                                    >
                                                                        <XCircle size={14} />
                                                                    </Button>
                                                                ) : (
                                                                    /* Séquence programmée : peut être activée ou marquée terminée directement */
                                                                    <>
                                                                        <Button 
                                                                            size="sm" 
                                                                            variant="outline-success"
                                                                            onClick={() => handleActivateSequence(sequence.id)}
                                                                            title="Activer cette séquence"
                                                                        >
                                                                            <Play size={14} />
                                                                        </Button>
                                                                        <Button 
                                                                            size="sm" 
                                                                            variant="outline-info"
                                                                            onClick={() => handleMarkSequenceCompleted(sequence.id)}
                                                                            title="Marquer directement comme terminé"
                                                                        >
                                                                            <CheckSquare size={14} />
                                                                        </Button>
                                                                    </>
                                                                )
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </Table>
                                </div>
                            </Card.Body>
                        </Card>
                    )}
                </Tab>
            </Tabs>

            {/* Modal Création Trimestre */}
            <Modal show={showCreateTrimester} onHide={() => setShowCreateTrimester(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Créer un nouveau trimestre</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Nom du trimestre</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={trimesterForm.name}
                                        onChange={(e) => setTrimesterForm({
                                            ...trimesterForm,
                                            name: e.target.value
                                        })}
                                        placeholder="ex: Premier Trimestre"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Numéro</Form.Label>
                                    <Form.Select
                                        value={trimesterForm.number}
                                        onChange={(e) => setTrimesterForm({
                                            ...trimesterForm,
                                            number: parseInt(e.target.value)
                                        })}
                                    >
                                        <option value={1}>1er Trimestre</option>
                                        <option value={2}>2e Trimestre</option>
                                        <option value={3}>3e Trimestre</option>
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Date de début</Form.Label>
                                    <Form.Control
                                        type="date"
                                        value={trimesterForm.start_date}
                                        onChange={(e) => setTrimesterForm({
                                            ...trimesterForm,
                                            start_date: e.target.value
                                        })}
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Date de fin</Form.Label>
                                    <Form.Control
                                        type="date"
                                        value={trimesterForm.end_date}
                                        onChange={(e) => setTrimesterForm({
                                            ...trimesterForm,
                                            end_date: e.target.value
                                        })}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Form.Check
                            type="checkbox"
                            label="Trimestre actif"
                            checked={trimesterForm.is_active}
                            onChange={(e) => setTrimesterForm({
                                ...trimesterForm,
                                is_active: e.target.checked
                            })}
                        />
                    </Form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowCreateTrimester(false)}>
                        Annuler
                    </Button>
                    <Button variant="primary" onClick={handleCreateTrimester}>
                        Créer Trimestre
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Modal de modification de trimestre */}
            <Modal show={showEditTrimester} onHide={() => setShowEditTrimester(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>Modifier le Trimestre</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Row>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Nom du trimestre</Form.Label>
                                <Form.Control
                                    type="text"
                                    value={trimesterForm.name}
                                    onChange={(e) => setTrimesterForm({ ...trimesterForm, name: e.target.value })}
                                    placeholder="Ex: Premier Trimestre"
                                />
                            </Form.Group>
                        </Col>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Numéro</Form.Label>
                                <Form.Select
                                    value={trimesterForm.number}
                                    onChange={(e) => setTrimesterForm({ ...trimesterForm, number: parseInt(e.target.value) })}
                                >
                                    <option value={1}>Trimestre 1</option>
                                    <option value={2}>Trimestre 2</option>
                                    <option value={3}>Trimestre 3</option>
                                </Form.Select>
                            </Form.Group>
                        </Col>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Date de début</Form.Label>
                                <Form.Control
                                    type="date"
                                    value={trimesterForm.start_date}
                                    onChange={(e) => setTrimesterForm({ ...trimesterForm, start_date: e.target.value })}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Date de fin</Form.Label>
                                <Form.Control
                                    type="date"
                                    value={trimesterForm.end_date}
                                    onChange={(e) => setTrimesterForm({ ...trimesterForm, end_date: e.target.value })}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={12}>
                            <Form.Group className="mb-3">
                                <Form.Check
                                    type="switch"
                                    id="edit-is-active"
                                    label="Trimestre actif"
                                    checked={trimesterForm.is_active}
                                    onChange={(e) => setTrimesterForm({ ...trimesterForm, is_active: e.target.checked })}
                                />
                                <Form.Text className="text-muted">
                                    Les trimestres inactifs n'apparaissent pas dans l'interface des professeurs
                                </Form.Text>
                            </Form.Group>
                        </Col>
                    </Row>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowEditTrimester(false)}>
                        Annuler
                    </Button>
                    <Button variant="primary" onClick={handleUpdateTrimester}>
                        Mettre à jour
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Modal de confirmation de suppression */}
            <Modal show={showDeleteConfirm} onHide={() => setShowDeleteConfirm(false)}>
                <Modal.Header closeButton>
                    <Modal.Title className="text-danger">
                        <Trash2 className="me-2" />
                        Confirmer la suppression
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {selectedTrimester && (
                        <div>
                            <Alert variant="warning">
                                <XCircle className="me-2" />
                                <strong>Attention !</strong> Cette action est irréversible.
                            </Alert>
                            
                            <p>
                                Êtes-vous sûr de vouloir supprimer le trimestre <strong>"{selectedTrimester.name}"</strong> ?
                            </p>

                            {deleteInfo && (
                                <Alert variant="danger" className="mb-3">
                                    <div className="d-flex align-items-start">
                                        <XCircle className="me-2 mt-1" />
                                        <div>
                                            <strong>Ce trimestre contient des données :</strong>
                                            <ul className="mb-2 mt-1">
                                                {deleteInfo.sequences_count > 0 && (
                                                    <li>{deleteInfo.sequences_count} séquence(s)</li>
                                                )}
                                                {deleteInfo.evaluations_count > 0 && (
                                                    <li>{deleteInfo.evaluations_count} évaluation(s)</li>
                                                )}
                                            </ul>
                                            <Form.Check
                                                type="checkbox"
                                                id="force-delete-checkbox"
                                                label={
                                                    <span>
                                                        <strong className="text-danger">Supprimer quand même</strong> 
                                                        <small className="d-block text-muted">
                                                            Toutes les séquences, évaluations et notes seront définitivement perdues
                                                        </small>
                                                    </span>
                                                }
                                                checked={forceDelete}
                                                onChange={(e) => setForceDelete(e.target.checked)}
                                                className="mt-2"
                                            />
                                        </div>
                                    </div>
                                </Alert>
                            )}

                            <small className="text-muted">
                                Note : Vous ne pouvez pas supprimer le trimestre actuellement actif.
                            </small>
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => {
                        setShowDeleteConfirm(false);
                        setForceDelete(false);
                        setDeleteInfo(null);
                    }}>
                        Annuler
                    </Button>
                    <Button 
                        variant="danger" 
                        onClick={confirmDeleteTrimester}
                        disabled={deleteInfo && !forceDelete}
                    >
                        <Trash2 className="me-2" />
                        {forceDelete ? 'Supprimer avec tout le contenu' : 'Supprimer définitivement'}
                    </Button>
                </Modal.Footer>
            </Modal>

            {/* Guide d'utilisation */}
            {trimesters.length > 0 && (
                <Alert variant="success" className="mt-4">
                    <h6>
                        <CheckCircle className="me-2" />
                        Système configuré !
                    </h6>
                    <p className="mb-2">
                        Les trimestres sont configurés. Les professeurs peuvent maintenant :
                    </p>
                    <ul className="mb-0 small">
                        <li>Voir leurs trimestres selon leurs classes assignées</li>
                        <li>Accéder aux séquences en cours pour la saisie de notes</li>
                        <li>Suivre la progression des évaluations</li>
                    </ul>
                </Alert>
            )}
        </div>
    );
};

export default TrimesterSequenceManagement;