import React, { useState, useEffect } from 'react';
import { Card, Button, Table, Badge, Modal, Row, Col, Alert, Spinner, ProgressBar, Accordion, Form, ButtonGroup } from 'react-bootstrap';
import { CardText, Download, Eye, Printer, ArrowClockwise, Clock, CheckCircle, ExclamationCircle, Calendar, Book, FileEarmarkZip } from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';
import { authService } from '../../services/authService';
import { host } from '../../utils/fetch';

function BulletinManagementNew() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  
  // Structure hiérarchique
  const [hierarchicalData, setHierarchicalData] = useState([]);
  const [loadingHierarchy, setLoadingHierarchy] = useState(true);
  const [selectedSection, setSelectedSection] = useState('');
  const [selectedLevel, setSelectedLevel] = useState('');
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedSeries, setSelectedSeries] = useState('');
  
  // Timeline académique
  const [academicTimeline, setAcademicTimeline] = useState(null);
  
  // Étudiants et statuts des bulletins
  const [studentsData, setStudentsData] = useState([]);
  const [selectedPeriodType, setSelectedPeriodType] = useState('all');
  
  // Navigation temporelle
  const [availablePeriods, setAvailablePeriods] = useState([]);
  const [selectedViewPeriod, setSelectedViewPeriod] = useState('current');
  
  // Modal de preview
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [previewContent, setPreviewContent] = useState('');
  const [previewStudent, setPreviewStudent] = useState(null);
  const [previewBulletinInfo, setPreviewBulletinInfo] = useState(null); // Pour le téléchargement PDF

  // Téléchargement groupé par période
  const [downloadingPeriod, setDownloadingPeriod] = useState(null);

  // 📦 Impression groupée (fusion PDF)
  const [mergingPeriod, setMergingPeriod] = useState(null);
  const [mergeProgress, setMergeProgress] = useState({ percentage: 0, message: '' });

  // Génération et régénération par période
  const [generatingPeriod, setGeneratingPeriod] = useState(null);
  const [regeneratingPeriod, setRegeneratingPeriod] = useState(null);

  // 📊 Progression en temps réel (NOUVELLE VERSION : génération 1 par 1)
  const [progressKey, setProgressKey] = useState(null);
  const [progress, setProgress] = useState(null);
  const [pollingInterval, setPollingInterval] = useState(null);

  // 📊 Progression 1 par 1 (nouvelle méthode)
  const [oneByOneProgress, setOneByOneProgress] = useState({
    current: 0,
    total: 0,
    percentage: 0,
    status: 'idle',
    message: '',
    errors: []
  });

  useEffect(() => {
    fetchHierarchicalStructure();
    fetchAcademicTimeline();
  }, []);

  // 📊 Cleanup polling on unmount
  useEffect(() => {
    return () => {
      if (pollingInterval) {
        clearInterval(pollingInterval);
      }
    };
  }, [pollingInterval]);

  // 📊 Fonction pour récupérer la progression
  const fetchProgress = async (key) => {
    try {
      const response = await secureApi.get(`/bulletins/batch-progress/${key}`);
      if (response && response.success && response.progress) {
        setProgress(response.progress);

        // Si terminé, arrêter le polling
        if (response.progress.status === 'completed') {
          if (pollingInterval) {
            clearInterval(pollingInterval);
            setPollingInterval(null);
          }
          // Actualiser les données après 2 secondes
          setTimeout(() => {
            fetchStudentsData();
            setProgressKey(null);
            setProgress(null);
          }, 2000);
        }
      }
    } catch (error) {
      console.error('Erreur récupération progression:', error);
      // En cas d'erreur, arrêter le polling
      if (pollingInterval) {
        clearInterval(pollingInterval);
        setPollingInterval(null);
      }
    }
  };

  // 📊 Démarrer le polling de progression
  const startProgressPolling = (key) => {
    setProgressKey(key);
    setProgress({
      current: 0,
      total: 0,
      percentage: 0,
      status: 'initializing',
      message: 'Initialisation...'
    });

    // Polling toutes les 3 secondes (pour éviter surcharge)
    const interval = setInterval(() => {
      fetchProgress(key);
    }, 3000);

    setPollingInterval(interval);
  };

  useEffect(() => {
    if (selectedSeries) {
      fetchStudentsData();
    }
  }, [selectedSeries, selectedViewPeriod]);

  const fetchHierarchicalStructure = async () => {
    try {
      setLoadingHierarchy(true);
      const response = await secureApi.get('/bulletins/hierarchical-structure');

      // secureApi returns parsed JSON directly, not wrapped in a data property
      let sectionsData = [];
      if (response && response.success && response.data) {
        sectionsData = response.data;
      } else if (Array.isArray(response)) {
        sectionsData = response;
      } else {
        sectionsData = [];
      }
      setHierarchicalData(sectionsData);
    } catch (error) {
      console.error('Erreur structure hiérarchique:', error);
      setError(`Erreur lors du chargement de la structure: ${error.message}`);
    } finally {
      setLoadingHierarchy(false);
    }
  };

  const fetchAcademicTimeline = async () => {
    try {
      const response = await secureApi.get('/bulletins/academic-timeline');
      // secureApi returns parsed JSON directly
      if (response && response.success && response.data) {
        setAcademicTimeline(response.data);
      } else {
        setAcademicTimeline(null);
      }
    } catch (error) {
      console.error('Erreur timeline:', error);
      setError('Erreur lors du chargement de la timeline');
    }
  };

  const fetchStudentsData = async () => {
    if (!selectedSeries) return;
    
    try {
      setLoading(true);
      // Ajouter le paramètre de période pour la navigation temporelle
      const params = selectedViewPeriod !== 'current' ? `?period=${selectedViewPeriod}` : '';
      const response = await secureApi.get(`/bulletins/students-status/${selectedSeries}${params}`);
      
      // secureApi returns parsed JSON directly
      if (response && response.success) {
        const students = response.students || [];
        setStudentsData(students);
        setAvailablePeriods(response.available_periods || []);
        
        // 🔍 DEBUG: Vérifier les bulletins de HASSIM ACHTA
      } else {
        setStudentsData([]);
        setAvailablePeriods([]);
      }
    } catch (error) {
      console.error('Erreur étudiants:', error);
      setError('Erreur lors du chargement des étudiants');
    } finally {
      setLoading(false);
    }
  };

  const handleSectionChange = (sectionId) => {
    setSelectedSection(sectionId);
    setSelectedLevel('');
    setSelectedClass('');
    setSelectedSeries('');
    setStudentsData([]);
  };

  const handleLevelChange = (levelId) => {
    setSelectedLevel(levelId);
    setSelectedClass('');
    setSelectedSeries('');
    setStudentsData([]);
  };

  const handleClassChange = (classId) => {
    setSelectedClass(classId);
    setSelectedSeries('');
    setStudentsData([]);
  };

  const handleSeriesChange = (seriesId) => {
    setSelectedSeries(seriesId);
  };

  const handleDownloadBulletin = async (bulletinId, studentName, periodType, periodId, studentId = null) => {
    try {
      setLoading(true);
      setError(''); // Clear previous errors
      
      // 🔍 DEBUG: Afficher les paramètres reçus avec plus de détails
      // Utiliser fetch directement pour les téléchargements de fichiers
      const token = authService.getToken();
      const response = await fetch(`${host}/api/bulletins/download/${bulletinId}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf'
        }
      });

      if (!response.ok) {
        if (response.status === 404) {
          // LOGIQUE INTELLIGENTE: Auto-générer le bulletin manquant
          console.warn(`Bulletin ${bulletinId} not found, auto-generating...`);

          try {
            // Récupérer les détails du bulletin manquant depuis l'erreur JSON
            const errorData = await response.json();
            console.log('Missing bulletin details:', errorData);

            if (errorData.student_id && errorData.period_type && errorData.period_identifier) {
              setError('Bulletin manquant. Génération automatique en cours...');

              // Forcer la régénération automatiquement avec les bonnes données
              await secureApi.post('/bulletins/force-regenerate', {
                student_id: errorData.student_id,
                period_type: errorData.period_type,
                period_identifier: errorData.period_identifier
              });

              // Actualiser les données immédiatement
              await fetchStudentsData();

              // Actualiser à nouveau après un délai pour être sûr que le bulletin est généré
              setTimeout(() => {
                fetchStudentsData();
              }, 1500);

              setSuccess('Bulletin généré automatiquement. Vous pouvez maintenant le télécharger.');
              return;
            }
          } catch (genError) {
            console.error('Auto-generation failed:', genError);
            setError('Impossible de générer automatiquement le bulletin. Veuillez utiliser le bouton "Régénérer".');
            return;
          }
        }
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const blob = await response.blob();
      const fileName = `bulletin_${periodType}_${periodId}_${studentName}_${new Date().toISOString().slice(0,10)}.pdf`;
      
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      
      setSuccess(`Bulletin téléchargé: ${fileName}`);
    } catch (error) {
      console.error('Erreur téléchargement:', error);
      setError('Erreur lors du téléchargement');
    } finally {
      setLoading(false);
    }
  };

  const handlePreviewBulletin = async (studentId, studentName, type, periodIdentifier) => {
    try {
      setLoading(true);
      const response = await secureApi.post('/bulletins/preview', {
        student_id: studentId,
        type: type,
        period_identifier: periodIdentifier
      });

      // secureApi returns parsed JSON directly
      if (response && response.success && response.html) {
        setPreviewContent(response.html);
      } else {
        setPreviewContent('<p>Contenu non disponible</p>');
      }
      setPreviewStudent({ id: studentId, name: studentName });
      // Stocker les infos pour le téléchargement PDF
      setPreviewBulletinInfo({
        student_id: studentId,
        type: type,
        period_identifier: periodIdentifier
      });
      setShowPreviewModal(true);
    } catch (error) {
      setError('Erreur lors de la prévisualisation');
    } finally {
      setLoading(false);
    }
  };

  const handleDownloadPdf = async () => {
    if (!previewBulletinInfo) {
      setError('Informations du bulletin manquantes');
      return;
    }

    try {
      setLoading(true);
      setError('');

      // Créer un lien temporaire pour déclencher le téléchargement
      const response = await fetch(`${host}/api/bulletins/download-direct`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authService.getToken()}`
        },
        body: JSON.stringify(previewBulletinInfo)
      });

      if (!response.ok) {
        throw new Error('Erreur lors du téléchargement');
      }

      // Obtenir le blob du PDF
      const blob = await response.blob();

      // Créer un nom de fichier approprié
      const filename = `bulletin_${previewBulletinInfo.type}_${previewBulletinInfo.period_identifier}_student_${previewBulletinInfo.student_id}.pdf`;

      // Créer un lien temporaire et déclencher le téléchargement
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.style.display = 'none';
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);

      setSuccess('Bulletin téléchargé avec succès !');
      setTimeout(() => setSuccess(''), 3000);
    } catch (error) {
      console.error('Error downloading PDF:', error);
      setError('Erreur lors du téléchargement du PDF');
    } finally {
      setLoading(false);
    }
  };

  const handleForceRegenerate = async (studentId, periodType, periodId) => {
    if (window.confirm('Forcer la régénération de ce bulletin ?')) {
      try {
        setLoading(true);
        setError(''); // Clear any previous errors
        
        await secureApi.post('/bulletins/force-regenerate', {
          student_id: studentId,
          period_type: periodType,
          period_identifier: periodId
        });
        
        setSuccess('Bulletin régénéré avec succès');

        // Force reload data to get new bulletin IDs
        await fetchStudentsData();

        // Additional delay to ensure data is updated
        setTimeout(() => {
          fetchStudentsData();
        }, 1500);
        
      } catch (error) {
        console.error('Error during regeneration:', error);
        setError('Erreur lors de la régénération: ' + (error.message || 'Unknown error'));
      } finally {
        setLoading(false);
      }
    }
  };

  const handleDownloadStudentBulletins = async (student) => {
    try {
      setLoading(true);
      setError('');

      // Collecter tous les bulletins disponibles (sequences + trimesters)
      const availableBulletins = [];

      // Vérifier toutes les propriétés de bulletins
      if (student.bulletins) {
        Object.entries(student.bulletins).forEach(([key, bulletin]) => {
          if (bulletin && (bulletin.is_generated || bulletin.bulletin_id)) {
            availableBulletins.push({
              ...bulletin,
              key: key
            });
          }
        });
      }

      if (availableBulletins.length === 0) {
        setError('Aucun bulletin disponible pour cet élève');
        setTimeout(() => setError(''), 3000);
        return;
      }

      if (!window.confirm(`Télécharger les ${availableBulletins.length} bulletin(s) de ${student.first_name} ${student.last_name} ?`)) {
        return;
      }

      // Télécharger chaque bulletin séquentiellement
      let downloadedCount = 0;
      for (const bulletin of availableBulletins) {
        try {
          await handleDownloadBulletin(
            bulletin.bulletin_id,
            `${student.first_name}_${student.last_name}`,
            bulletin.type || bulletin.period_type,
            bulletin.identifier || bulletin.period_identifier,
            student.id
          );
          downloadedCount++;
          // Petite pause entre les téléchargements
          await new Promise(resolve => setTimeout(resolve, 800));
        } catch (err) {
          console.error(`Erreur téléchargement bulletin ${bulletin.key}:`, err);
        }
      }

      setSuccess(`${downloadedCount}/${availableBulletins.length} bulletin(s) téléchargé(s) avec succès`);
      setTimeout(() => setSuccess(''), 3000);

    } catch (error) {
      console.error('Erreur téléchargement bulletins élève:', error);
      setError('Erreur lors du téléchargement des bulletins');
      setTimeout(() => setError(''), 3000);
    } finally {
      setLoading(false);
    }
  };

  // 🆕 NOUVELLE MÉTHODE : Génération 1 par 1 (sans queue)
  const handleGeneratePeriodBulletins = async (period) => {
    if (!selectedSeries || !selectedClass) {
      setError('Veuillez sélectionner une classe et une série');
      return;
    }

    // Filtrer les étudiants qui n'ont PAS encore ce bulletin (côté frontend uniquement pour confirmer)
    const studentsToGenerate = studentsData.filter(student => {
      // Convertir l'objet bulletins en tableau avant d'utiliser .find()
      const bulletinsArray = Object.values(student.bulletins || {});
      const bulletin = bulletinsArray.find(b =>
        b.type === period.type &&
        b.identifier === period.identifier
      );
      return !bulletin || !bulletin.is_generated;
    });

    if (studentsToGenerate.length === 0) {
      setSuccess(`✅ Tous les bulletins pour ${period.label} sont déjà générés !`);
      setTimeout(() => setSuccess(''), 3000);
      return;
    }

    if (!window.confirm(`Générer ${studentsToGenerate.length} bulletin(s) manquant(s) pour ${period.label} ?\n\nGénération BATCH (tous en une seule fois).`)) {
      return;
    }

    setGeneratingPeriod(period.identifier);
    setError('');
    setSuccess('');

    // Afficher progression indéterminée
    setOneByOneProgress({
      current: 0,
      total: studentsToGenerate.length,
      percentage: 0,
      status: 'processing',
      message: `⏳ Génération de ${studentsToGenerate.length} bulletin(s) manquant(s) en cours...`,
      errors: []
    });

    try {
      // 🚀 GÉNÉRATION PAR LOTS (CHUNKS) AVEC TRAITEMENT PARALLÈLE
      // Divise la génération en lots de 20 élèves
      // Dans chaque lot, génère 3 bulletins en parallèle pour optimiser la vitesse
      const CHUNK_SIZE = 20;
      const PARALLEL_REQUESTS = 3; // Nombre de bulletins générés simultanément
      const totalStudents = studentsToGenerate.length;
      let processedCount = 0;
      let generatedCount = 0;
      let allErrors = [];

      setOneByOneProgress({
        current: 0,
        total: totalStudents,
        percentage: 0,
        status: 'processing',
        message: `⏳ Démarrage de la génération de ${totalStudents} bulletin(s)...\n📦 Traitement par lots de ${CHUNK_SIZE} (${PARALLEL_REQUESTS} simultanés)`,
        errors: []
      });

      // Traiter par lots
      for (let i = 0; i < totalStudents; i += CHUNK_SIZE) {
        const chunkStudents = studentsToGenerate.slice(i, i + CHUNK_SIZE);
        const chunkNumber = Math.floor(i / CHUNK_SIZE) + 1;
        const totalChunks = Math.ceil(totalStudents / CHUNK_SIZE);

        // Mettre à jour la progression avant le lot
        setOneByOneProgress(prev => ({
          ...prev,
          message: `⚙️ Lot ${chunkNumber}/${totalChunks} : Génération de ${chunkStudents.length} bulletin(s)...`,
        }));

        // Générer plusieurs bulletins en parallèle dans ce lot
        for (let j = 0; j < chunkStudents.length; j += PARALLEL_REQUESTS) {
          const parallelStudents = chunkStudents.slice(j, j + PARALLEL_REQUESTS);

          // Créer un tableau de promesses pour les requêtes parallèles
          const promises = parallelStudents.map(student =>
            secureApi.post('/bulletins/generate', {
              student_id: student.id,
              bulletin_type: period.type,
              period_identifier: period.identifier,
              force: false
            })
            .then(response => ({
              success: true,
              student,
              response
            }))
            .catch(err => ({
              success: false,
              student,
              error: err
            }))
          );

          // Attendre que toutes les requêtes parallèles se terminent
          const results = await Promise.all(promises);

          // Traiter les résultats
          results.forEach(result => {
            if (result.success) {
              generatedCount++;
            } else {
              allErrors.push({
                student: `${result.student.last_name} ${result.student.first_name}`,
                error: result.error.response?.data?.error || result.error.message
              });
            }
            processedCount++;
          });

          // Mettre à jour la progression après chaque lot parallèle
          const percentage = Math.round((processedCount / totalStudents) * 100);
          setOneByOneProgress({
            current: processedCount,
            total: totalStudents,
            percentage: percentage,
            status: 'processing',
            message: `⚙️ Lot ${chunkNumber}/${totalChunks} : ${processedCount}/${totalStudents} bulletin(s) traités\n✅ ${generatedCount} générés | ❌ ${allErrors.length} erreurs`,
            errors: allErrors
          });
        }

        // Petite pause entre les lots pour éviter de surcharger le serveur
        await new Promise(resolve => setTimeout(resolve, 200));
      }

      // Marquer comme terminé
      const finalMessage = `✅ Génération terminée : ${generatedCount}/${totalStudents} bulletin(s) générés`;
      setOneByOneProgress({
        current: totalStudents,
        total: totalStudents,
        percentage: 100,
        status: allErrors.length === 0 ? 'completed' : 'completed',
        message: finalMessage,
        errors: allErrors
      });

      if (allErrors.length === 0) {
        setSuccess(finalMessage);
      } else {
        setError(`⚠️ ${finalMessage} - ${allErrors.length} erreur(s) - Voir détails ci-dessous`);
      }

      // Recharger les données après 2 secondes
      setTimeout(() => {
        fetchStudentsData();
        setOneByOneProgress({
          current: 0,
          total: 0,
          percentage: 0,
          status: 'idle',
          message: '',
          errors: []
        });
      }, 3000);

    } catch (error) {
      console.error('Erreur génération batch:', error);
      setError(`❌ Erreur ${period.label}: ${error.response?.data?.error || error.message || 'Erreur lors de la génération'}`);
      setOneByOneProgress(prev => ({
        ...prev,
        status: 'failed',
        message: `❌ Erreur: ${error.response?.data?.error || error.message}`
      }));
    } finally {
      setGeneratingPeriod(null);
    }
  };

  // 🚀 NOUVELLE MÉTHODE ULTRA-OPTIMISÉE : Régénération en UN SEUL APPEL
  // Utilise la nouvelle route /batch-generate-trimester-optimized
  // Charge TOUTES les données de la classe EN UNE FOIS (214× plus rapide!)
  const handleRegeneratePeriodBulletins = async (period) => {
    if (!selectedSeries || !selectedClass) {
      setError('Veuillez sélectionner une classe et une série');
      return;
    }

    // Compter les étudiants
    const studentCount = studentsData.filter(student => student.id).length;

    if (studentCount === 0) {
      setError('Aucun étudiant trouvé dans cette classe');
      return;
    }

    // ⚠️ Pour les SÉQUENCES: utiliser la génération un par un avec force=true
    if (period.type === 'sequence') {
      if (!window.confirm(`⚠️ ATTENTION : Régénérer TOUS les ${studentCount} bulletins pour ${period.label} ?\n\nCela remplacera les bulletins existants.`)) {
        return;
      }

      setRegeneratingPeriod(period.identifier);
      setError('');
      setSuccess('');

      // Utiliser la génération un par un pour les séquences
      setOneByOneProgress({
        current: 0,
        total: studentCount,
        percentage: 0,
        status: 'processing',
        message: `🔄 Régénération de ${studentCount} bulletin(s) pour ${period.label}...`,
        errors: []
      });

      try {
        const CHUNK_SIZE = 20;
        const PARALLEL_REQUESTS = 3;
        const totalStudents = studentCount;
        let processedCount = 0;
        let generatedCount = 0;
        let allErrors = [];

        // Traiter par lots
        for (let i = 0; i < totalStudents; i += CHUNK_SIZE) {
          const chunkStudents = studentsData.slice(i, i + CHUNK_SIZE);
          const chunkNumber = Math.floor(i / CHUNK_SIZE) + 1;
          const totalChunks = Math.ceil(totalStudents / CHUNK_SIZE);

          setOneByOneProgress(prev => ({
            ...prev,
            message: `⚙️ Lot ${chunkNumber}/${totalChunks} : Régénération de ${chunkStudents.length} bulletin(s)...`,
          }));

          // Générer plusieurs bulletins en parallèle dans ce lot
          for (let j = 0; j < chunkStudents.length; j += PARALLEL_REQUESTS) {
            const parallelStudents = chunkStudents.slice(j, j + PARALLEL_REQUESTS);

            const promises = parallelStudents.map(student =>
              secureApi.post('/bulletins/generate', {
                student_id: student.id,
                bulletin_type: period.type,
                period_identifier: period.identifier,
                force: true // FORCE pour régénérer
              })
              .then(response => ({
                success: true,
                student,
                response
              }))
              .catch(err => ({
                success: false,
                student,
                error: err
              }))
            );

            const results = await Promise.all(promises);

            results.forEach(result => {
              if (result.success) {
                generatedCount++;
              } else {
                allErrors.push({
                  student: `${result.student.last_name} ${result.student.first_name}`,
                  error: result.error.response?.data?.error || result.error.message
                });
              }
              processedCount++;
            });

            const percentage = Math.round((processedCount / totalStudents) * 100);
            setOneByOneProgress({
              current: processedCount,
              total: totalStudents,
              percentage: percentage,
              status: 'processing',
              message: `⚙️ Lot ${chunkNumber}/${totalChunks} : ${processedCount}/${totalStudents} bulletin(s) traités\n✅ ${generatedCount} régénérés | ❌ ${allErrors.length} erreurs`,
              errors: allErrors
            });
          }

          await new Promise(resolve => setTimeout(resolve, 200));
        }

        const finalMessage = `✅ Régénération terminée : ${generatedCount}/${totalStudents} bulletin(s) régénérés`;
        setOneByOneProgress({
          current: totalStudents,
          total: totalStudents,
          percentage: 100,
          status: allErrors.length === 0 ? 'completed' : 'completed',
          message: finalMessage,
          errors: allErrors
        });

        if (allErrors.length === 0) {
          setSuccess(finalMessage);
        } else {
          setError(`⚠️ ${finalMessage} - ${allErrors.length} erreur(s) - Voir détails ci-dessous`);
        }

        setTimeout(() => {
          fetchStudentsData();
          setOneByOneProgress({
            current: 0,
            total: 0,
            percentage: 0,
            status: 'idle',
            message: '',
            errors: []
          });
        }, 3000);

      } catch (error) {
        console.error('Erreur régénération séquences:', error);
        setError(`❌ Erreur ${period.label}: ${error.response?.data?.error || error.message || 'Erreur lors de la régénération'}`);
        setOneByOneProgress({
          current: 0,
          total: 0,
          percentage: 0,
          status: 'failed',
          message: `❌ Erreur: ${error.response?.data?.error || error.message}`,
          errors: []
        });
      } finally {
        setRegeneratingPeriod(null);
      }
      return;
    }

    // Pour les TRIMESTRES: utiliser la route optimisée
    // Extraire le numéro du trimestre depuis l'identifier (trim1 -> 1, trim2 -> 2, etc.)
    const trimesterNumber = parseInt(period.identifier.replace('trim', ''));

    if (!window.confirm(`⚠️ ATTENTION : Régénérer TOUS les ${studentCount} bulletins pour ${period.label} ?\n\n🚀 NOUVELLE MÉTHODE ULTRA-RAPIDE :\n• Charge toutes les données EN UNE FOIS\n• Génération en ~5-10 secondes (au lieu de 19+ minutes!)\n\nCela remplacera les bulletins existants.`)) {
      return;
    }

    setRegeneratingPeriod(period.identifier);
    setError('');
    setSuccess('');

    try {
      setOneByOneProgress({
        current: 0,
        total: studentCount,
        percentage: 0,
        status: 'processing',
        message: `🚀 RÉGÉNÉRATION ULTRA-RAPIDE de ${studentCount} bulletin(s)...\n⏳ Chargement de toutes les données de la classe en une seule fois...`,
        errors: []
      });

      // 🔥 APPEL À LA NOUVELLE ROUTE OPTIMISÉE
      const startTime = Date.now();
      const response = await secureApi.post('/bulletins/batch-generate-trimester-optimized', {
        series_id: parseInt(selectedSeries),
        trimester_number: trimesterNumber,
        force: true // FORCE pour remplacer les existants
      });

      const duration = ((Date.now() - startTime) / 1000).toFixed(2);

      if (response && response.success) {
        const { generated, total, errors, error_details } = response;

        // Marquer comme terminé
        const finalMessage = `✅ Régénération terminée en ${duration}s : ${generated}/${total} bulletin(s) régénérés`;

        setOneByOneProgress({
          current: total,
          total: total,
          percentage: 100,
          status: errors === 0 ? 'completed' : 'completed',
          message: finalMessage,
          errors: (error_details || []).map(err => ({
            student: err.student,
            error: err.error
          }))
        });

        if (errors === 0) {
          setSuccess(`${finalMessage}\n🚀 Performance: ${(total / parseFloat(duration)).toFixed(1)} bulletins/seconde`);
        } else {
          setError(`⚠️ ${finalMessage} - ${errors} erreur(s) - Voir détails ci-dessous`);
        }

        // Recharger les données après 2 secondes
        setTimeout(() => {
          fetchStudentsData();
          setOneByOneProgress({
            current: 0,
            total: 0,
            percentage: 0,
            status: 'idle',
            message: '',
            errors: []
          });
        }, 3000);
      } else {
        throw new Error(response?.error || 'Erreur inconnue lors de la génération');
      }

    } catch (error) {
      console.error('Erreur régénération batch optimisée:', error);
      const errorMessage = error.response?.data?.error || error.message || 'Erreur lors de la régénération';
      setError(`❌ Erreur ${period.label}: ${errorMessage}`);
      setOneByOneProgress({
        current: 0,
        total: 0,
        percentage: 0,
        status: 'failed',
        message: `❌ Erreur: ${errorMessage}`,
        errors: []
      });
    } finally {
      setRegeneratingPeriod(null);
    }
  };

  const handleDownloadAllBulletins = async () => {
    if (!selectedSeries) {
      setError('Veuillez sélectionner une série');
      return;
    }

    if (!window.confirm('Télécharger tous les bulletins de cette série ?')) {
      return;
    }

    try {
      setLoading(true);

      const token = authService.getToken();
      const response = await fetch(`${host}/api/bulletins/download-all`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/zip'
        },
        body: JSON.stringify({
          series_id: selectedSeries,
          period_type: selectedPeriodType !== 'all' ? selectedPeriodType : null
        })
      });

      if (!response.ok) {
        const errorData = await response.json();
        console.error('Détails de l\'erreur:', errorData);
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      const blob = await response.blob();
      const fileName = `bulletins_${new Date().toISOString().slice(0,10)}.zip`;

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      setSuccess(`Archive téléchargée: ${fileName}`);
    } catch (error) {
      console.error('Erreur téléchargement groupé:', error);
      setError(error.message || 'Erreur lors du téléchargement groupé');
    } finally {
      setLoading(false);
    }
  };

  const handleDownloadPeriodBulletins = async (period) => {
    if (!selectedSeries) {
      setError('Veuillez sélectionner une série');
      return;
    }

    setDownloadingPeriod(period.identifier);
    setError('');
    setSuccess('');

    try {
      const token = authService.getToken();
      const response = await fetch(`${host}/api/bulletins/download-all`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/zip'
        },
        body: JSON.stringify({
          series_id: selectedSeries,
          period_type: period.type,
          period_identifier: period.identifier
        })
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        console.error('Détails de l\'erreur:', errorData);
        throw new Error(errorData.error || `Erreur ${response.status}`);
      }

      const blob = await response.blob();
      const fileName = `bulletins_${period.identifier}_${new Date().toISOString().slice(0,10)}.zip`;

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      setSuccess(`✅ Bulletins de ${period.label} téléchargés avec succès!`);
      setTimeout(() => setSuccess(''), 5000);
    } catch (error) {
      console.error('Erreur téléchargement période:', error);
      setError(`❌ ${error.message || 'Erreur lors du téléchargement'}`);
      setTimeout(() => setError(''), 5000);
    } finally {
      setDownloadingPeriod(null);
    }
  };

  // 📦 Fonction pour fusionner et télécharger les bulletins en un seul PDF
  const handleMergeBulletins = async (period) => {
    if (!selectedSeries) {
      setError('Veuillez sélectionner une série');
      return;
    }

    setMergingPeriod(period.identifier);
    setMergeProgress({ status: 'starting', message: 'Démarrage de la fusion...', percentage: 0 });
    setError('');
    setSuccess('');

    try {
      const token = authService.getToken();

      // Lancer la fusion
      const response = await fetch(`${host}/api/bulletins/merge`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          class_series_id: selectedSeries,
          period_type: period.type,
          period_identifier: period.identifier
        })
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `Erreur ${response.status}`);
      }

      const data = await response.json();
      if (!data.success) {
        throw new Error(data.message || 'Erreur lors du démarrage de la fusion');
      }

      const jobId = data.job_id;
      setSuccess(`Fusion de ${data.bulletin_count} bulletins en cours...`);

      // ⚡ Attendre 3 secondes puis vérifier si terminé (pour mode sync)
      await new Promise(resolve => setTimeout(resolve, 3000));

      // Vérifier si le job est terminé
      const checkResponse = await fetch(`${host}/api/bulletins/merge-progress/${jobId}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      if (checkResponse.ok) {
        const checkData = await checkResponse.json();

        if (checkData.status === 'completed') {
          // Job terminé instantanément (mode sync)
          setMergingPeriod(null);
          setMergeProgress({ status: 'completed', message: checkData.message, percentage: 100 });

          if (checkData.file_id) {
            // ⚡ FIX: Télécharger via fetch avec token, puis créer un Blob URL
            const downloadUrl = `${host}/api/bulletins/merged/${checkData.file_id}/download`;
            try {
              const downloadResponse = await fetch(downloadUrl, {
                headers: { 'Authorization': `Bearer ${token}` }
              });

              if (downloadResponse.ok) {
                const blob = await downloadResponse.blob();
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = checkData.filename || 'bulletins_merged.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);
              } else {
                console.error('Erreur téléchargement:', await downloadResponse.text());
              }
            } catch (downloadError) {
              console.error('Erreur téléchargement PDF:', downloadError);
            }
          }

          setSuccess(`✅ ${checkData.message}`);
          setTimeout(() => setSuccess(''), 5000);
          return;
        }
      }

      // Sinon continuer le polling normal (mode async)
      const progressInterval = setInterval(async () => {
        try {
          const progressResponse = await fetch(`${host}/api/bulletins/merge-progress/${jobId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          });

          if (progressResponse.ok) {
            const progressData = await progressResponse.json();
            setMergeProgress(progressData);

            if (progressData.status === 'completed') {
              clearInterval(progressInterval);
              setMergingPeriod(null);

              // Télécharger automatiquement via fetch + Blob URL (pour envoyer le token)
              if (progressData.file_id) {
                const downloadUrl = `${host}/api/bulletins/merged/${progressData.file_id}/download`;
                try {
                  const downloadResponse = await fetch(downloadUrl, {
                    headers: { 'Authorization': `Bearer ${token}` }
                  });

                  if (downloadResponse.ok) {
                    const blob = await downloadResponse.blob();
                    const blobUrl = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = progressData.filename || 'bulletins_merged.pdf';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(blobUrl);
                  } else {
                    console.error('Erreur téléchargement:', await downloadResponse.text());
                  }
                } catch (downloadError) {
                  console.error('Erreur téléchargement PDF:', downloadError);
                }
              }

              setSuccess(`✅ ${progressData.message}`);
              setTimeout(() => setSuccess(''), 5000);
            } else if (progressData.status === 'failed') {
              clearInterval(progressInterval);
              setMergingPeriod(null);
              throw new Error(progressData.message || 'Erreur lors de la fusion');
            }
          }
        } catch (error) {
          clearInterval(progressInterval);
          setMergingPeriod(null);
          throw error;
        }
      }, 2000); // Vérifier toutes les 2 secondes

    } catch (error) {
      console.error('Erreur fusion:', error);
      setMergingPeriod(null);
      setMergeProgress({});
      setError(error.message || 'Erreur lors de la fusion des bulletins');
      setTimeout(() => setError(''), 10000);
    }
  };

  const getCompletionBadge = (bulletin) => {
    const { completion_percentage, is_generated, status, is_archived } = bulletin;
    
    if (is_archived) {
      return <Badge bg="dark" className="d-flex align-items-center">
        📁 <span className="ms-1">Archivé ({completion_percentage}%)</span>
      </Badge>;
    }
    
    if (is_generated) {
      return <Badge bg="success" className="d-flex align-items-center">
        <CheckCircle className="me-1" size={12} />
        Généré ({completion_percentage}%)
      </Badge>;
    }
    
    if (status === 'future') {
      return <Badge bg="light" text="dark" className="d-flex align-items-center">
        ⏳ <span className="ms-1">Futur ({completion_percentage}%)</span>
      </Badge>;
    }
    
    if (completion_percentage >= 50) {
      return <Badge bg="warning" className="d-flex align-items-center">
        <Clock className="me-1" size={12} />
        Prêt ({completion_percentage}%)
      </Badge>;
    }
    
    return <Badge bg="secondary" className="d-flex align-items-center">
      <ExclamationCircle className="me-1" size={12} />
      Incomplet ({completion_percentage}%)
    </Badge>;
  };

  const getCurrentPeriodBadge = (current, type) => {
    if (!current) return null;
    return (
      <Badge bg="primary" className="ms-2">
        <Calendar className="me-1" size={12} />
        {type === 'sequence' ? 'Séquence' : 'Trimestre'} Actuel: {current.name}
      </Badge>
    );
  };

  const filterStudentsByPeriod = (students) => {
    if (selectedPeriodType === 'all') return students;
    
    return students.filter(student => {
      const bulletins = Object.values(student.bulletins);
      if (selectedPeriodType === 'sequences') {
        return bulletins.some(b => b.type === 'sequence');
      } else if (selectedPeriodType === 'trimesters') {
        return bulletins.some(b => b.type === 'trimester');
      } else if (selectedPeriodType === 'generated') {
        return bulletins.some(b => b.is_generated);
      } else if (selectedPeriodType === 'pending') {
        return bulletins.some(b => !b.is_generated && b.completion_percentage >= 50);
      }
      return true;
    });
  };

  const getSelectedLevels = () => {
    if (!selectedSection || !hierarchicalData) return [];
    const section = hierarchicalData.find(s => s.id.toString() === selectedSection);
    return section ? section.levels : [];
  };

  const getSelectedClasses = () => {
    const levels = getSelectedLevels();
    if (!selectedLevel || !levels) return [];
    const level = levels.find(l => l.id.toString() === selectedLevel);
    return level ? level.school_classes : [];
  };

  const getSelectedSeries = () => {
    const classes = getSelectedClasses();
    if (!selectedClass || !classes) return [];
    const schoolClass = classes.find(c => c.id.toString() === selectedClass);
    return schoolClass ? schoolClass.series : [];
  };

  return (
    <div className="container-fluid">
      {/* Header avec timeline académique */}
      <Row className="mb-4">
        <Col>
          <Card className="border-0 shadow-sm">
            <Card.Header className="bg-primary text-white d-flex align-items-center">
              <Book className="me-2" />
              <h5 className="mb-0">Gestion des Bulletins Scolaires</h5>
            </Card.Header>
            <Card.Body>
              {academicTimeline && (
                <Row>
                  <Col md={6}>
                    <h6>Année Scolaire: {academicTimeline.school_year}</h6>
                    {getCurrentPeriodBadge(academicTimeline.current_sequence, 'sequence')}
                    {getCurrentPeriodBadge(academicTimeline.current_trimester, 'trimester')}
                  </Col>
                  {/* <Col md={6} className="text-end">
                    <div className="d-flex flex-wrap gap-1 justify-content-end">
                      {academicTimeline.sequences?.map(seq => (
                        <Badge 
                          key={seq.id} 
                          bg={seq.is_active ? 'success' : 'secondary'}
                        >
                          Seq {seq.number}
                        </Badge>
                      ))}
                    </div>
                  </Col> */}
                </Row>
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Sélection hiérarchique */}
      <Row className="mb-4">
        <Col>
          <Card>
            <Card.Header className="d-flex justify-content-between align-items-center">
              <h6 className="mb-0">Sélection de la Classe</h6>
              <Button 
                variant="outline-primary" 
                size="sm" 
                onClick={fetchHierarchicalStructure}
                disabled={loadingHierarchy}
              >
                {loadingHierarchy ? (
                  <>
                    <Spinner size="sm" className="me-1" />
                    Chargement...
                  </>
                ) : (
                  <>
                    <ArrowClockwise className="me-1" />
                    Recharger
                  </>
                )}
              </Button>
            </Card.Header>
            <Card.Body>
              {/* Debug info */}
              <div className="mb-2 small text-muted">
                Sections chargées: {hierarchicalData.length} | 
                Section sélectionnée: {selectedSection || 'Aucune'} | 
                Niveau sélectionné: {selectedLevel || 'Aucun'} | 
                Classe sélectionnée: {selectedClass || 'Aucune'} | 
                Série sélectionnée: {selectedSeries || 'Aucune'}
              </div>
              <Row>
                <Col md={3}>
                  <Form.Group>
                    <Form.Label>Section</Form.Label>
                    <Form.Select 
                      value={selectedSection} 
                      onChange={(e) => handleSectionChange(e.target.value)}
                      disabled={loadingHierarchy}
                    >
                      <option value="">
                        {loadingHierarchy ? 'Chargement des sections...' : 'Choisir une section...'}
                      </option>
                      {hierarchicalData.map(section => (
                        <option key={section.id} value={section.id}>
                          {section.name}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
                
                <Col md={3}>
                  <Form.Group>
                    <Form.Label>Niveau</Form.Label>
                    <Form.Select 
                      value={selectedLevel} 
                      onChange={(e) => handleLevelChange(e.target.value)}
                      disabled={!selectedSection}
                    >
                      <option value="">Choisir un niveau...</option>
                      {getSelectedLevels().map(level => (
                        <option key={level.id} value={level.id}>
                          {level.name}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
                
                <Col md={3}>
                  <Form.Group>
                    <Form.Label>Classe</Form.Label>
                    <Form.Select 
                      value={selectedClass} 
                      onChange={(e) => handleClassChange(e.target.value)}
                      disabled={!selectedLevel}
                    >
                      <option value="">Choisir une classe...</option>
                      {getSelectedClasses().map(schoolClass => (
                        <option key={schoolClass.id} value={schoolClass.id}>
                          {schoolClass.name}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
                
                <Col md={3}>
                  <Form.Group>
                    <Form.Label>Série</Form.Label>
                    <Form.Select 
                      value={selectedSeries} 
                      onChange={(e) => handleSeriesChange(e.target.value)}
                      disabled={!selectedClass}
                    >
                      <option value="">Choisir une série...</option>
                      {getSelectedSeries().map(series => (
                        <option key={series.id} value={series.id}>
                          {series.name}
                        </option>
                      ))}
                    </Form.Select>
                  </Form.Group>
                </Col>
              </Row>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Sélecteur de navigation temporelle */}
      {selectedSeries && availablePeriods.length > 0 && (
        <Row className="mb-3">
          <Col>
            <Card>
              <Card.Header className="d-flex justify-content-between align-items-center">
                <h6 className="mb-0">🕐 Navigation Temporelle</h6>
                <Badge bg="info">
                  Vue: {availablePeriods.find(p => p.identifier === selectedViewPeriod)?.name || 'Actuelle'}
                </Badge>
              </Card.Header>
              <Card.Body>
                <Row>
                  <Col>
                    <Form.Label>Période à visualiser :</Form.Label>
                    <Form.Select
                      value={selectedViewPeriod}
                      onChange={(e) => setSelectedViewPeriod(e.target.value)}
                      className="mb-2"
                    >
                      {availablePeriods.map(period => (
                        <option key={period.identifier} value={period.identifier}>
                          {period.status === 'past' && '📁 '}
                          {period.status === 'current' && '▶️ '}
                          {period.status === 'future' && '⏳ '}
                          {period.name}
                          {period.status === 'past' && ' (Archive)'}
                          {period.status === 'current' && ' (En cours)'}
                          {period.status === 'future' && ' (Futur)'}
                        </option>
                      ))}
                    </Form.Select>
                  </Col>
                </Row>
                <div className="d-flex gap-2 flex-wrap">
                  {availablePeriods.filter(p => p.type !== 'view').map(period => (
                    <Button
                      key={period.identifier}
                      variant={selectedViewPeriod === period.identifier ? "primary" : "outline-secondary"}
                      size="sm"
                      onClick={() => setSelectedViewPeriod(period.identifier)}
                      className="d-flex align-items-center"
                    >
                      {period.status === 'past' && <span className="me-1">📁</span>}
                      {period.status === 'current' && <span className="me-1">▶️</span>}
                      {period.status === 'future' && <span className="me-1">⏳</span>}
                      {period.name}
                    </Button>
                  ))}
                </div>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Génération Groupée par Période */}
      {selectedSeries && (
        <Row className="mb-3">
          <Col>
            <Card className="border-success">
              <Card.Header className="bg-success text-white d-flex align-items-center">
                <CardText className="me-2" size={20} />
                <h6 className="mb-0">Génération des Bulletins par Période</h6>
              </Card.Header>
              <Card.Body>
                <p className="text-muted mb-3">
                  Générez tous les bulletins manquants pour une période spécifique de la série sélectionnée.
                </p>
                <Row className="g-3">
                  {[
                    { type: 'sequence', identifier: 'seq1', label: 'Séquence 1', variant: 'outline-success' },
                    { type: 'sequence', identifier: 'seq2', label: 'Séquence 2', variant: 'outline-success' },
                    { type: 'trimester', identifier: 'trim1', label: 'Trimestre 1', variant: 'outline-success' },
                    { type: 'sequence', identifier: 'seq3', label: 'Séquence 3', variant: 'outline-success' },
                    { type: 'sequence', identifier: 'seq4', label: 'Séquence 4', variant: 'outline-success' },
                    { type: 'trimester', identifier: 'trim2', label: 'Trimestre 2', variant: 'outline-success' },
                    { type: 'trimester', identifier: 'trim3', label: 'Trimestre 3', variant: 'outline-success' },
                  ].map((period) => (
                    <Col md={3} key={period.identifier}>
                      <Button
                        variant={period.variant}
                        className="w-100 py-3 d-flex flex-column align-items-center justify-content-center"
                        onClick={() => handleGeneratePeriodBulletins(period)}
                        disabled={generatingPeriod !== null || regeneratingPeriod !== null || downloadingPeriod !== null}
                        style={{ minHeight: '80px' }}
                      >
                        {generatingPeriod === period.identifier ? (
                          <>
                            <Spinner animation="border" className="mb-2" style={{ width: '2rem', height: '2rem' }} />
                            <strong className="text-primary">⚙️ Génération en cours...</strong>
                            <small className="mt-1">Ne fermez pas cette page</small>
                          </>
                        ) : (
                          <>
                            <CardText size={28} className="mb-2" />
                            <strong>{period.label}</strong>
                          </>
                        )}
                      </Button>
                    </Col>
                  ))}
                </Row>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Régénération Groupée par Période */}
      {selectedSeries && (
        <Row className="mb-3">
          <Col>
            <Card className="border-warning">
              <Card.Header className="bg-warning text-dark d-flex align-items-center">
                <ArrowClockwise className="me-2" size={20} />
                <h6 className="mb-0">Régénération des Bulletins par Période</h6>
              </Card.Header>
              <Card.Body>
                <p className="text-muted mb-3">
                  ⚠️ Régénérez TOUS les bulletins d'une période (remplace les bulletins existants).
                </p>
                <Row className="g-3">
                  {[
                    { type: 'sequence', identifier: 'seq1', label: 'Séquence 1', variant: 'outline-warning' },
                    { type: 'sequence', identifier: 'seq2', label: 'Séquence 2', variant: 'outline-warning' },
                    { type: 'trimester', identifier: 'trim1', label: 'Trimestre 1', variant: 'outline-warning' },
                    { type: 'sequence', identifier: 'seq3', label: 'Séquence 3', variant: 'outline-warning' },
                    { type: 'sequence', identifier: 'seq4', label: 'Séquence 4', variant: 'outline-warning' },
                    { type: 'trimester', identifier: 'trim2', label: 'Trimestre 2', variant: 'outline-warning' },
                    { type: 'trimester', identifier: 'trim3', label: 'Trimestre 3', variant: 'outline-warning' },
                  ].map((period) => (
                    <Col md={3} key={period.identifier}>
                      <Button
                        variant={period.variant}
                        className="w-100 py-3 d-flex flex-column align-items-center justify-content-center"
                        onClick={() => handleRegeneratePeriodBulletins(period)}
                        disabled={generatingPeriod !== null || regeneratingPeriod !== null || downloadingPeriod !== null}
                        style={{ minHeight: '80px' }}
                      >
                        {regeneratingPeriod === period.identifier ? (
                          <>
                            <Spinner animation="border" className="mb-2" style={{ width: '2rem', height: '2rem' }} />
                            <strong className="text-warning">🔄 Régénération en cours...</strong>
                            <small className="mt-1">Ne fermez pas cette page</small>
                          </>
                        ) : (
                          <>
                            <ArrowClockwise size={28} className="mb-2" />
                            <strong>{period.label}</strong>
                          </>
                        )}
                      </Button>
                    </Col>
                  ))}
                </Row>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Impression Groupée (PDF Fusionné) */}
      {selectedSeries && (
        <Row className="mb-3">
          <Col>
            <Card className="border-warning">
              <Card.Header className="bg-warning text-dark d-flex align-items-center">
                <Printer className="me-2" size={20} />
                <h6 className="mb-0">Impression Groupée (PDF Fusionné)</h6>
              </Card.Header>
              <Card.Body>
                <p className="text-muted mb-3">
                  <strong>Pour impression directe:</strong> Fusionnez tous les bulletins d'une période en un seul fichier PDF prêt à imprimer.
                </p>
                <Row className="g-3">
                  {[
                    { type: 'sequence', identifier: 'seq1', label: 'Séquence 1', variant: 'warning' },
                    { type: 'sequence', identifier: 'seq2', label: 'Séquence 2', variant: 'warning' },
                    { type: 'trimester', identifier: 'trim1', label: 'Trimestre 1', variant: 'success' },
                    { type: 'sequence', identifier: 'seq3', label: 'Séquence 3', variant: 'warning' },
                    { type: 'sequence', identifier: 'seq4', label: 'Séquence 4', variant: 'warning' },
                    { type: 'trimester', identifier: 'trim2', label: 'Trimestre 2', variant: 'success' },
                    { type: 'trimester', identifier: 'trim3', label: 'Trimestre 3', variant: 'success' },
                  ].map((period) => (
                    <Col md={3} key={`merge-${period.identifier}`}>
                      <Button
                        variant={`outline-${period.variant}`}
                        className="w-100 py-3 d-flex flex-column align-items-center justify-content-center"
                        onClick={() => handleMergeBulletins(period)}
                        disabled={mergingPeriod !== null || generatingPeriod !== null || regeneratingPeriod !== null}
                        style={{ minHeight: '100px' }}
                      >
                        {mergingPeriod === period.identifier ? (
                          <>
                            <Spinner animation="border" size="sm" className="mb-2" />
                            <small>{mergeProgress.message || 'Fusion en cours...'}</small>
                            {mergeProgress.percentage > 0 && (
                              <ProgressBar
                                now={mergeProgress.percentage}
                                className="w-100 mt-2"
                                style={{ height: '5px' }}
                                animated
                              />
                            )}
                          </>
                        ) : (
                          <>
                            <Printer size={28} className="mb-2" />
                            <strong>{period.label}</strong>
                            <small className="text-muted">Fusionner & Imprimer</small>
                          </>
                        )}
                      </Button>
                    </Col>
                  ))}
                </Row>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Téléchargement Groupé par Période */}
      {selectedSeries && (
        <Row className="mb-3">
          <Col>
            <Card className="border-primary">
              <Card.Header className="bg-primary text-white d-flex align-items-center">
                <FileEarmarkZip className="me-2" size={20} />
                <h6 className="mb-0">Téléchargement Groupé des Bulletins par Période</h6>
              </Card.Header>
              <Card.Body>
                <p className="text-muted mb-3">
                  Téléchargez tous les bulletins d'une période spécifique pour la série sélectionnée en un seul fichier ZIP.
                </p>
                <Row className="g-3">
                  {[
                    { type: 'sequence', identifier: 'seq1', label: 'Séquence 1', variant: 'outline-primary' },
                    { type: 'sequence', identifier: 'seq2', label: 'Séquence 2', variant: 'outline-primary' },
                    { type: 'trimester', identifier: 'trim1', label: 'Trimestre 1', variant: 'outline-success' },
                    { type: 'sequence', identifier: 'seq3', label: 'Séquence 3', variant: 'outline-primary' },
                    { type: 'sequence', identifier: 'seq4', label: 'Séquence 4', variant: 'outline-primary' },
                    { type: 'trimester', identifier: 'trim2', label: 'Trimestre 2', variant: 'outline-success' },
                    { type: 'trimester', identifier: 'trim3', label: 'Trimestre 3', variant: 'outline-success' },
                  ].map((period) => (
                    <Col md={3} key={period.identifier}>
                      <Button
                        variant={period.variant}
                        className="w-100 py-3 d-flex flex-column align-items-center justify-content-center"
                        onClick={() => handleDownloadPeriodBulletins(period)}
                        disabled={generatingPeriod !== null || regeneratingPeriod !== null || downloadingPeriod !== null}
                        style={{ minHeight: '80px' }}
                      >
                        {downloadingPeriod === period.identifier ? (
                          <>
                            <Spinner animation="border" size="sm" className="mb-2" />
                            <small>Téléchargement...</small>
                          </>
                        ) : (
                          <>
                            <FileEarmarkZip size={28} className="mb-2" />
                            <strong>{period.label}</strong>
                          </>
                        )}
                      </Button>
                    </Col>
                  ))}
                </Row>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Filtres des périodes */}
      {selectedSeries && (
        <Row className="mb-3">
          <Col>
            <ButtonGroup>
              <Button
                variant={selectedPeriodType === 'all' ? 'primary' : 'outline-primary'}
                onClick={() => setSelectedPeriodType('all')}
              >
                Tous
              </Button>
              <Button
                variant={selectedPeriodType === 'sequences' ? 'primary' : 'outline-primary'}
                onClick={() => setSelectedPeriodType('sequences')}
              >
                Séquences
              </Button>
              <Button
                variant={selectedPeriodType === 'trimesters' ? 'primary' : 'outline-primary'}
                onClick={() => setSelectedPeriodType('trimesters')}
              >
                Trimestres
              </Button>
              <Button
                variant={selectedPeriodType === 'generated' ? 'success' : 'outline-success'}
                onClick={() => setSelectedPeriodType('generated')}
              >
                Générés
              </Button>
              <Button
                variant={selectedPeriodType === 'pending' ? 'warning' : 'outline-warning'}
                onClick={() => setSelectedPeriodType('pending')}
              >
                En attente
              </Button>
            </ButtonGroup>
          </Col>
        </Row>
      )}

      {/* Messages d'alerte */}
      {error && <Alert variant="danger" onClose={() => setError('')} dismissible>{error}</Alert>}
      {success && <Alert variant="success" onClose={() => setSuccess('')} dismissible>{success}</Alert>}

      {/* 📊 Barre de progression en temps réel (NOUVELLE VERSION - 1 par 1) */}
      {oneByOneProgress.status !== 'idle' && (
        <Alert
          variant={oneByOneProgress.status === 'completed' ? 'success' : oneByOneProgress.status === 'failed' ? 'danger' : 'warning'}
          className="mb-3"
          style={{
            fontSize: '1.1rem',
            border: '2px solid',
            boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
          }}
        >
          <div className="d-flex justify-content-between align-items-center mb-3">
            <strong style={{ fontSize: '1.2rem' }}>
              {oneByOneProgress.status === 'processing' && (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  ⚙️ Génération en cours...
                </>
              )}
              {oneByOneProgress.status === 'completed' && '✅ Terminé !'}
              {oneByOneProgress.status === 'failed' && '❌ Erreur'}
            </strong>
            <span className="badge bg-primary" style={{ fontSize: '1rem' }}>
              {oneByOneProgress.current}/{oneByOneProgress.total} bulletins
            </span>
          </div>
          <ProgressBar
            now={oneByOneProgress.percentage}
            label={`${oneByOneProgress.percentage}%`}
            variant={oneByOneProgress.status === 'completed' ? 'success' : oneByOneProgress.status === 'failed' ? 'danger' : 'primary'}
            animated={oneByOneProgress.status === 'processing'}
            striped={oneByOneProgress.status !== 'completed'}
            style={{ height: '30px', fontSize: '1rem' }}
          />
          <div className="mt-2" style={{ whiteSpace: 'pre-line' }}>
            {oneByOneProgress.message}
          </div>
          {oneByOneProgress.status === 'processing' && (
            <div className="mt-2 text-center">
              <small className="text-muted">
                ⏳ Veuillez patienter, ne fermez pas cette page...
              </small>
            </div>
          )}
          {oneByOneProgress.errors.length > 0 && (
            <div className="mt-2">
              <strong className="text-danger">Erreurs ({oneByOneProgress.errors.length}) :</strong>
              <ul className="mb-0 mt-1" style={{ fontSize: '0.85rem' }}>
                {oneByOneProgress.errors.slice(0, 5).map((err, idx) => (
                  <li key={idx}>
                    <strong>{err.student}</strong> : {err.error}
                  </li>
                ))}
                {oneByOneProgress.errors.length > 5 && (
                  <li className="text-muted">
                    ... et {oneByOneProgress.errors.length - 5} autre(s) erreur(s)
                  </li>
                )}
              </ul>
            </div>
          )}
        </Alert>
      )}

      {/* Liste des étudiants avec statuts des bulletins */}
      {selectedSeries && (
        <Row>
          <Col>
            <Card>
              <Card.Header className="d-flex justify-content-between align-items-center">
                <h6 className="mb-0">Étudiants et Statuts des Bulletins</h6>
                <Button 
                  variant="outline-primary" 
                  size="sm" 
                  onClick={() => {
                    fetchStudentsData();
                  }}
                  disabled={loading}
                >
                  <ArrowClockwise className="me-1" />
                  Actualiser & Debug
                </Button>
              </Card.Header>
              <Card.Body>
                {loading ? (
                  <div className="text-center py-4">
                    <Spinner animation="border" role="status">
                      <span className="visually-hidden">Chargement...</span>
                    </Spinner>
                  </div>
                ) : (
                  <div className="table-responsive">
                    <Table striped hover>
                      <thead>
                        <tr>
                          <th>Étudiant</th>
                          <th>Matricule</th>
                          <th>Séq 1</th>
                          <th>Séq 2</th>
                          <th>Comp 1</th>
                          <th>Trim 1</th>
                          <th>Séq 3</th>
                          <th>Séq 4</th>
                          <th>Comp 2</th>
                          <th>Trim 2</th>
                          <th>Comp 3</th>
                          <th>Trim 3</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filterStudentsByPeriod(studentsData).map((student) => (
                          <tr key={student.id}>
                            <td>
                              <div className="d-flex justify-content-between align-items-center">
                                <strong>{student.first_name} {student.last_name}</strong>
                                {student.bulletins && Object.values(student.bulletins).some(b => b && (b.is_generated || b.bulletin_id)) && (
                                  <Button
                                    variant="outline-primary"
                                    size="sm"
                                    onClick={() => handleDownloadStudentBulletins(student)}
                                    title={`Télécharger tous les bulletins de ${student.first_name}`}
                                    className="ms-2"
                                  >
                                    <Download size={12} /> Tous
                                  </Button>
                                )}
                              </div>
                            </td>
                            <td>{student.matricule}</td>
                            
                            {/* Séquence 1 */}
                            <td>
                              {student.bulletins.sequence_1 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.sequence_1)}
                                  {student.bulletins.sequence_1.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.sequence_1.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Séquence 2 */}
                            <td>
                              {student.bulletins.sequence_2 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.sequence_2)}
                                  {student.bulletins.sequence_2.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.sequence_2.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Composition 1 */}
                            <td>
                              {student.bulletins.composition_1 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.composition_1)}
                                  {student.bulletins.composition_1.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.composition_1.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                      variant="info"
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Trimestre 1 */}
                            <td>
                              {student.bulletins.trimester_1 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.trimester_1)}
                                  {student.bulletins.trimester_1.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.trimester_1.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Séquence 3 */}
                            <td>
                              {student.bulletins.sequence_3 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.sequence_3)}
                                  {student.bulletins.sequence_3.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.sequence_3.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Séquence 4 */}
                            <td>
                              {student.bulletins.sequence_4 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.sequence_4)}
                                  {student.bulletins.sequence_4.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.sequence_4.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Composition 2 */}
                            <td>
                              {student.bulletins.composition_2 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.composition_2)}
                                  {student.bulletins.composition_2.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.composition_2.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                      variant="info"
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Trimestre 2 */}
                            <td>
                              {student.bulletins.trimester_2 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.trimester_2)}
                                  {student.bulletins.trimester_2.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.trimester_2.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Composition 3 */}
                            <td>
                              {student.bulletins.composition_3 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.composition_3)}
                                  {student.bulletins.composition_3.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.composition_3.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                      variant="info"
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Trimestre 3 */}
                            <td>
                              {student.bulletins.trimester_3 && (
                                <div className="d-flex flex-column align-items-start">
                                  {getCompletionBadge(student.bulletins.trimester_3)}
                                  {student.bulletins.trimester_3.completion_percentage > 0 && (
                                    <ProgressBar
                                      now={student.bulletins.trimester_3.completion_percentage}
                                      size="sm"
                                      className="mt-1 w-100"
                                      style={{height: '4px'}}
                                    />
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Actions */}
                            <td>
                              <div className="d-flex gap-1 flex-wrap">
                                {Object.entries(student.bulletins).map(([key, bulletin]) => {
                                  // Permettre l'affichage pour tous les bulletins avec données (pas seulement >= 50%)
                                  if (!bulletin.can_preview && bulletin.completion_percentage < 10) return null;
                                  
                                  return (
                                    <div key={key} className="btn-group-vertical btn-group-sm">
                                      {/* Preview - Toujours disponible si can_preview */}
                                      {bulletin.can_preview && (
                                        <Button
                                          variant={bulletin.status === 'future' ? 'outline-secondary' : 
                                                   bulletin.is_archived ? 'outline-dark' : 'outline-info'}
                                          size="sm"
                                          onClick={() => handlePreviewBulletin(
                                            student.id, 
                                            `${student.first_name}_${student.last_name}`,
                                            bulletin.type,
                                            bulletin.identifier
                                          )}
                                          title={`${bulletin.status === 'future' ? 'Aperçu futur' : 
                                                   bulletin.is_archived ? 'Voir archive' : 'Prévisualiser'} ${bulletin.name}`}
                                        >
                                          <Eye size={12} />
                                          {bulletin.status === 'future' && <span className="ms-1">⏳</span>}
                                          {bulletin.is_archived && <span className="ms-1">📁</span>}
                                        </Button>
                                      )}
                                      
                                      {/* Download - Only if is_generated or bulletin_id exists */}
                                      {(bulletin.is_generated || bulletin.bulletin_id) ? (
                                        <Button
                                          variant={bulletin.is_archived ? 'dark' : 'success'}
                                          size="sm"
                                          onClick={() => handleDownloadBulletin(
                                            bulletin.bulletin_id,
                                            `${student.first_name}_${student.last_name}`,
                                            bulletin.type,
                                            bulletin.identifier,
                                            student.id
                                          )}
                                          title={`Télécharger ${bulletin.name}${bulletin.is_archived ? ' (Archive)' : ''}`}
                                          className="d-flex align-items-center gap-1"
                                        >
                                          <Download size={14} />
                                          <span style={{ fontSize: '11px' }}>PDF</span>
                                          {bulletin.is_archived && <span>📁</span>}
                                        </Button>
                                      ) : null}
                                      
                                      {/* Force Regenerate - Seulement pour les périodes actuelles */}
                                      {bulletin.status === 'current' && (
                                        <Button
                                          variant="outline-warning"
                                          size="sm"
                                          onClick={() => handleForceRegenerate(
                                            student.id,
                                            bulletin.type,
                                            bulletin.identifier
                                          )}
                                          title={`Forcer régénération ${bulletin.name}`}
                                        >
                                          <ArrowClockwise size={12} />
                                        </Button>
                                      )}
                                    </div>
                                  );
                                })}
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                    
                    {filterStudentsByPeriod(studentsData).length === 0 && (
                      <div className="text-center py-4">
                        <p className="text-muted">Aucun étudiant trouvé pour les critères sélectionnés.</p>
                      </div>
                    )}
                  </div>
                )}
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}

      {/* Modal de prévisualisation */}
      <Modal 
        show={showPreviewModal} 
        onHide={() => setShowPreviewModal(false)} 
        size="xl"
        fullscreen="lg-down"
      >
        <Modal.Header closeButton>
          <Modal.Title>
            <Printer className="me-2" />
            Prévisualisation du Bulletin
            {previewStudent && ` - ${previewStudent.name}`}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body style={{ maxHeight: '70vh', overflow: 'auto' }}>
          {previewContent && (
            <div dangerouslySetInnerHTML={{ __html: previewContent }} />
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button
            variant="primary"
            onClick={handleDownloadPdf}
            disabled={loading || !previewBulletinInfo}
          >
            <Download className="me-2" />
            Télécharger PDF
          </Button>
          <Button variant="secondary" onClick={() => setShowPreviewModal(false)}>
            Fermer
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

export default BulletinManagementNew;