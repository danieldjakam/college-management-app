import React, { useState, useEffect } from 'react';
import {
    FileEarmarkText,
    Download,
    Search,
    Printer,
    FileText
} from 'react-bootstrap-icons';
import { Card, Button, LoadingSpinner, Alert } from '../../components/UI';
import { host } from '../../utils/fetch';
import { authService } from '../../services/authService';

const ClassFeesSheet = () => {
    const [classes, setClasses] = useState([]); // SchoolClasses (6ème, 5ème, etc.)
    const [series, setSeries] = useState([]); // ClassSeries (6ème A, 6ème B, etc.)
    const [selectedClass, setSelectedClass] = useState(''); // SchoolClass sélectionnée
    const [selectedSeries, setSelectedSeries] = useState(''); // ClassSeries sélectionnée
    const [loading, setLoading] = useState(false);
    const [loadingData, setLoadingData] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [previewData, setPreviewData] = useState(null);

    // Charger les classes au montage
    useEffect(() => {
        loadClasses();
    }, []);

    // Charger les séries quand la classe change
    useEffect(() => {
        if (selectedClass) {
            loadSeries(selectedClass);
        } else {
            setSeries([]);
            setSelectedSeries('');
        }
    }, [selectedClass]);

    // Charger les classes de base (SchoolClass: 6ème, 5ème, etc.)
    const loadClasses = async () => {
        setLoadingData(true);
        try {
            const response = await fetch(`${host}/api/class-fees-sheet/school-classes`, {
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Classes reçues:', data);

            if (data.success && data.classes) {
                setClasses(data.classes);
            } else {
                console.error('Format de données inattendu pour les classes:', data);
                setError('Format de données inattendu pour les classes');
            }
        } catch (error) {
            console.error('Erreur lors du chargement des classes:', error);
            setError('Erreur lors du chargement des classes: ' + error.message);
        } finally {
            setLoadingData(false);
        }
    };

    // Charger les séries pour une classe donnée (ClassSeries: 6ème A, 6ème B, etc.)
    const loadSeries = async (classId) => {
        try {
            const url = `${host}/api/class-fees-sheet/class-series?class_id=${classId}`;
            console.log('Chargement des séries depuis:', url);

            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Séries reçues:', data);

            if (data.success && data.series) {
                setSeries(data.series);
            } else {
                console.error('Format de données inattendu pour les séries:', data);
                setError('Format de données inattendu pour les séries');
            }
        } catch (error) {
            console.error('Erreur lors du chargement des séries:', error);
            setError('Erreur lors du chargement des séries: ' + error.message);
        }
    };

    const loadPreview = async () => {
        if (!selectedSeries) {
            setError('Veuillez sélectionner une série');
            return;
        }

        setLoading(true);
        setError('');

        try {
            const response = await fetch(`${host}/api/class-fees-sheet/${selectedSeries}/data`, {
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Content-Type': 'application/json'
                }
            });
            const result = await response.json();

            if (result.success) {
                setPreviewData(result.data);
                setSuccess('Données chargées avec succès');
            } else {
                setError(result.message || 'Erreur lors du chargement des données');
            }
        } catch (error) {
            console.error('Erreur:', error);
            setError('Erreur lors du chargement des données');
        } finally {
            setLoading(false);
        }
    };

    const downloadPDF = async () => {
        if (!selectedSeries) {
            setError('Veuillez sélectionner une série');
            return;
        }

        setLoading(true);
        setError('');

        try {
            const token = authService.getToken();
            const url = `${host}/api/class-fees-sheet/${selectedSeries}/download`;

            // Télécharger le PDF avec authentification
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                }
            });

            if (!response.ok) {
                throw new Error('Erreur lors du téléchargement du PDF');
            }

            // Récupérer le blob
            const blob = await response.blob();

            // Créer un lien de téléchargement
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;

            // Extraire le nom du fichier depuis le header ou utiliser un nom par défaut
            const contentDisposition = response.headers.get('content-disposition');
            let fileName = 'Fiche_Scolarite.pdf';
            if (contentDisposition) {
                const fileNameMatch = contentDisposition.match(/filename="?(.+)"?/i);
                if (fileNameMatch) {
                    fileName = fileNameMatch[1];
                }
            }

            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);

            setSuccess('PDF téléchargé avec succès !');
        } catch (error) {
            console.error('Erreur:', error);
            setError('Erreur lors du téléchargement du PDF');
        } finally {
            setLoading(false);
        }
    };

    const formatAmount = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0
        }).format(amount);
    };

    return (
        <div className="container-fluid p-4">
            <div className="mb-4">
                <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <FileEarmarkText className="text-primary-violet" size={32} />
                    Fiche de Scolarité par Classe
                </h1>
                <p className="text-gray-600 mt-2">
                    Générez et téléchargez la fiche détaillée des paiements de scolarité par classe
                </p>
            </div>

            {error && (
                <Alert type="error" className="mb-4">
                    {error}
                </Alert>
            )}

            {success && (
                <Alert type="success" className="mb-4">
                    {success}
                </Alert>
            )}

            {/* Filtres */}
            <Card className="mb-4">
                <div className="card-header bg-primary-violet-50 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <Search size={20} />
                        Filtres de Sélection
                        {loadingData && (
                            <span className="text-sm text-gray-500 ml-2">Chargement...</span>
                        )}
                    </h2>
                </div>
                <div className="card-body p-4">
                    {loadingData ? (
                        <div className="flex justify-center items-center py-8">
                            <LoadingSpinner />
                            <span className="ml-3 text-gray-600">Chargement des données...</span>
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {/* Classe */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Classe *
                                    </label>
                                    <select
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-violet focus:border-transparent"
                                        value={selectedClass}
                                        onChange={(e) => {
                                            setSelectedClass(e.target.value);
                                            setSelectedSeries('');
                                            setPreviewData(null);
                                        }}
                                    >
                                        <option value="">Sélectionner une classe</option>
                                        {classes.map(classe => (
                                            <option key={classe.id} value={classe.id}>
                                                {classe.name}
                                                {classe.section && ` - ${classe.section.name}`}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">
                                        Ex: 6ème, 5ème, 4ème...
                                    </p>
                                </div>

                                {/* Série */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Série *
                                    </label>
                                    <select
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-violet focus:border-transparent"
                                        value={selectedSeries}
                                        onChange={(e) => {
                                            setSelectedSeries(e.target.value);
                                            setPreviewData(null);
                                        }}
                                        disabled={!selectedClass || series.length === 0}
                                    >
                                        <option value="">
                                            {selectedClass ? 'Sélectionner une série' : 'Sélectionnez d\'abord une classe'}
                                        </option>
                                        {series.map(s => (
                                            <option key={s.id} value={s.id}>
                                                {s.name}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">
                                        Ex: 6ème A, 6ème B...
                                    </p>
                                </div>
                            </div>

                            <div className="flex gap-3 mt-4">
                                <Button
                                    onClick={loadPreview}
                                    disabled={!selectedSeries || loading}
                                    className="flex items-center gap-2"
                                >
                                    <Search size={18} />
                                    Charger les données
                                </Button>
                                <Button
                                    onClick={downloadPDF}
                                    disabled={!selectedSeries || loading}
                                    variant="success"
                                    className="flex items-center gap-2"
                                >
                                    <Download size={18} />
                                    Télécharger PDF
                                </Button>
                            </div>

                            {/* Informations de débogage */}
                            <div className="mt-4 p-3 bg-gray-50 rounded text-xs text-gray-600">
                                <div>Classes (SchoolClass): {classes.length} chargée(s)</div>
                                <div>Séries (ClassSeries): {series.length} chargée(s)</div>
                                {selectedClass && <div className="text-blue-600">Classe sélectionnée: {classes.find(c => c.id == selectedClass)?.name}</div>}
                                {selectedSeries && <div className="text-green-600">Série sélectionnée: {series.find(s => s.id == selectedSeries)?.name}</div>}
                            </div>
                        </>
                    )}
                </div>
            </Card>

            {/* Aperçu des données */}
            {loading && (
                <div className="flex justify-center items-center py-12">
                    <LoadingSpinner />
                </div>
            )}

            {previewData && !loading && (
                <Card>
                    <div className="card-header bg-primary-violet-50 border-b border-gray-200">
                        <h2 className="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            <FileText size={20} />
                            Aperçu - {previewData.class.name}
                            {previewData.class.section && ` - ${previewData.class.section.name}`}
                        </h2>
                    </div>
                    <div className="card-body p-4">
                        {/* Résumé */}
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div className="bg-blue-50 p-4 rounded-lg">
                                <div className="text-sm text-gray-600">Nombre d'élèves</div>
                                <div className="text-2xl font-bold text-blue-600">
                                    {previewData.nombre_eleves}
                                </div>
                            </div>
                            <div className="bg-green-50 p-4 rounded-lg">
                                <div className="text-sm text-gray-600">Total Payé</div>
                                <div className="text-xl font-bold text-green-600">
                                    {formatAmount(previewData.totals.total_paye)}
                                </div>
                            </div>
                            <div className="bg-red-50 p-4 rounded-lg">
                                <div className="text-sm text-gray-600">Reste à Payer</div>
                                <div className="text-xl font-bold text-red-600">
                                    {formatAmount(previewData.totals.reste_a_payer)}
                                </div>
                            </div>
                            <div className="bg-purple-50 p-4 rounded-lg">
                                <div className="text-sm text-gray-600">Total Bourses</div>
                                <div className="text-xl font-bold text-purple-600">
                                    {formatAmount(previewData.totals.bourse)}
                                </div>
                            </div>
                        </div>

                        {/* Tableau récapitulatif */}
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Type
                                        </th>
                                        <th className="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                            Montant Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {previewData.tranches.map(tranche => (
                                        <tr key={tranche.id}>
                                            <td className="px-3 py-2 text-sm">{tranche.name}</td>
                                            <td className="px-3 py-2 text-sm text-right font-medium">
                                                {formatAmount(previewData.totals.tranches[tranche.id] || 0)}
                                            </td>
                                        </tr>
                                    ))}
                                    <tr>
                                        <td className="px-3 py-2 text-sm">Rabais</td>
                                        <td className="px-3 py-2 text-sm text-right font-medium text-orange-600">
                                            {formatAmount(previewData.totals.rabais)}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td className="px-3 py-2 text-sm">Bourses</td>
                                        <td className="px-3 py-2 text-sm text-right font-medium text-purple-600">
                                            {formatAmount(previewData.totals.bourse)}
                                        </td>
                                    </tr>
                                    <tr className="bg-green-50">
                                        <td className="px-3 py-2 text-sm font-bold">TOTAL PAYÉ</td>
                                        <td className="px-3 py-2 text-sm text-right font-bold text-green-600">
                                            {formatAmount(previewData.totals.total_paye)}
                                        </td>
                                    </tr>
                                    <tr className="bg-red-50">
                                        <td className="px-3 py-2 text-sm font-bold">RESTE À PAYER</td>
                                        <td className="px-3 py-2 text-sm text-right font-bold text-red-600">
                                            {formatAmount(previewData.totals.reste_a_payer)}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-6 p-4 bg-blue-50 rounded-lg">
                            <p className="text-sm text-blue-800">
                                <strong>Note:</strong> Pour télécharger la fiche complète au format PDF avec tous les détails des élèves,
                                cliquez sur le bouton "Télécharger PDF" ci-dessus.
                            </p>
                        </div>
                    </div>
                </Card>
            )}
        </div>
    );
};

export default ClassFeesSheet;
