import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
    Plus, 
    Trash, 
    ArrowLeft,
    CheckCircle,
    AlertCircle
} from 'react-bootstrap-icons';
import Swal from 'sweetalert2';

// Components
import { 
    Card, 
    Button, 
    Alert, 
    LoadingSpinner, 
    Input
} from '../../components/UI';

// Hooks
import { useApi } from '../../hooks/useApi';
import { apiEndpoints } from '../../utils/api';

const CreateClass = () => {
    const navigate = useNavigate();
    const { execute, loading } = useApi();
    
    const [sections, setSections] = useState([]);
    const [levels, setLevels] = useState([]);
    const [paymentTranches, setPaymentTranches] = useState([]);
    const [error, setError] = useState('');
    
    const [formData, setFormData] = useState({
        name: '',
        level_id: '',
        description: '',
        series: [{ name: 'A', capacity: 40 }],
        payment_amounts: []
    });

    useEffect(() => {
        loadInitialData();
    }, []);

    useEffect(() => {
        if (formData.level_id) {
            loadLevelsForSection();
        }
    }, [formData.level_id]);

    const loadInitialData = async () => {
        try {
            const [sectionsData, tranchesData] = await Promise.all([
                execute(() => apiEndpoints.getAllSections()),
                execute(() => apiEndpoints.getAllPaymentTranches())
            ]);
            
            setSections(sectionsData || []);
            setPaymentTranches(tranchesData.data || []);
            
            // Initialiser les montants de paiement
            const initialPaymentAmounts = (tranchesData.data || []).map(tranche => ({
                payment_tranche_id: tranche.id,
                amount_new_students: 0,
                amount_old_students: 0,
                is_required: true
            }));
            
            setFormData(prev => ({
                ...prev,
                payment_amounts: initialPaymentAmounts
            }));
        } catch (err) {
            setError('Erreur lors du chargement des données');
        }
    };

    const loadLevelsForSection = async () => {
        if (!formData.level_id) return;
        
        try {
            // Find selected level to get section_id
            const selectedLevel = levels.find(l => l.id === parseInt(formData.level_id));
            if (selectedLevel) {
                const levelsData = await execute(() => apiEndpoints.getAllLevels(selectedLevel.section_id));
                setLevels(levelsData.data || []);
            }
        } catch (err) {
            setError('Erreur lors du chargement des niveaux');
        }
    };

    const handleSectionChange = async (sectionId) => {
        try {
            const levelsData = await execute(() => apiEndpoints.getAllLevels(sectionId));
            setLevels(levelsData.data || []);
            setFormData(prev => ({ ...prev, level_id: '' }));
        } catch (err) {
            setError('Erreur lors du chargement des niveaux');
        }
    };

    const handleAddSeries = () => {
        const nextLetter = String.fromCharCode(65 + formData.series.length); // A, B, C, etc.
        setFormData(prev => ({
            ...prev,
            series: [...prev.series, { name: nextLetter, capacity: 40 }]
        }));
    };

    const handleRemoveSeries = (index) => {
        if (formData.series.length <= 1) return; // Au moins une série requise
        
        setFormData(prev => ({
            ...prev,
            series: prev.series.filter((_, i) => i !== index)
        }));
    };

    const handleSeriesChange = (index, field, value) => {
        setFormData(prev => ({
            ...prev,
            series: prev.series.map((series, i) => 
                i === index ? { ...series, [field]: value } : series
            )
        }));
    };

    const handlePaymentAmountChange = (trancheId, field, value) => {
        setFormData(prev => ({
            ...prev,
            payment_amounts: prev.payment_amounts.map(amount =>
                amount.payment_tranche_id === trancheId
                    ? { ...amount, [field]: field === 'is_required' ? value : parseFloat(value) || 0 }
                    : amount
            )
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        // Validation
        if (!formData.name.trim()) {
            setError('Le nom de la classe est requis');
            return;
        }
        
        if (!formData.level_id) {
            setError('Veuillez sélectionner un niveau');
            return;
        }
        
        if (formData.series.length === 0) {
            setError('Au moins une série est requise');
            return;
        }

        try {
            await execute(() => apiEndpoints.addSchoolClass(formData));
            
            await Swal.fire({
                title: 'Succès !',
                text: 'La classe a été créée avec succès',
                icon: 'success',
                confirmButtonText: 'OK'
            });
            
            navigate('/classes');
        } catch (err) {
            setError('Erreur lors de la création de la classe');
        }
    };

    const selectedSection = sections.find(s => 
        levels.some(l => l.id === parseInt(formData.level_id) && l.section_id === s.id)
    );

    return (
        <div className="animate-fade-in">
            {/* Header */}
            <div className="mb-8">
                <div className="flex items-center space-x-4 mb-4">
                    <Button
                        variant="secondary"
                        onClick={() => navigate('/classes')}
                        icon={<ArrowLeft size={16} />}
                        size="sm"
                    >
                        Retour
                    </Button>
                    <div>
                        <h1 className="text-3xl font-bold text-gray-800">
                            Créer une Nouvelle Classe
                        </h1>
                        <p className="text-gray-600">
                            Configurez les détails, séries et tarifs de paiement de la classe
                        </p>
                    </div>
                </div>
            </div>

            {/* Error Alert */}
            {error && (
                <Alert variant="error" className="mb-6" dismissible onDismiss={() => setError('')}>
                    {error}
                </Alert>
            )}

            <form onSubmit={handleSubmit} className="space-y-8">
                {/* Informations de base */}
                <Card>
                    <Card.Header>
                        <Card.Title>Informations de Base</Card.Title>
                        <Card.Subtitle>Définissez le nom et le niveau de la classe</Card.Subtitle>
                    </Card.Header>
                    <Card.Content className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Input
                                label="Nom de la classe"
                                type="text"
                                value={formData.name}
                                onChange={(e) => setFormData({...formData, name: e.target.value})}
                                placeholder="Ex: 1ère, Terminale, CP, etc."
                                required
                            />

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Section
                                </label>
                                <select
                                    value=""
                                    onChange={(e) => handleSectionChange(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-violet focus:border-transparent"
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
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Niveau
                                </label>
                                <select
                                    value={formData.level_id}
                                    onChange={(e) => setFormData({...formData, level_id: e.target.value})}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-violet focus:border-transparent"
                                    required
                                    disabled={levels.length === 0}
                                >
                                    <option value="">Sélectionner un niveau</option>
                                    {levels.map(level => (
                                        <option key={level.id} value={level.id}>
                                            {level.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Description (optionnelle)
                                </label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => setFormData({...formData, description: e.target.value})}
                                    placeholder="Description de la classe..."
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-violet focus:border-transparent"
                                />
                            </div>
                        </div>
                    </Card.Content>
                </Card>

                {/* Série/Variantes */}
                <Card>
                    <Card.Header>
                        <div className="flex justify-between items-center">
                            <div>
                                <Card.Title>Séries/Variantes</Card.Title>
                                <Card.Subtitle>Définissez les différentes séries de cette classe (A, B, C, etc.)</Card.Subtitle>
                            </div>
                            <Button
                                type="button"
                                variant="primary"
                                size="sm"
                                onClick={handleAddSeries}
                                icon={<Plus size={16} />}
                            >
                                Ajouter une Série
                            </Button>
                        </div>
                    </Card.Header>
                    <Card.Content>
                        <div className="space-y-4">
                            {formData.series.map((series, index) => (
                                <div key={index} className="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                                    <div className="flex-1">
                                        <Input
                                            label="Nom de la série"
                                            type="text"
                                            value={series.name}
                                            onChange={(e) => handleSeriesChange(index, 'name', e.target.value)}
                                            placeholder="A, B, C, etc."
                                            required
                                        />
                                    </div>
                                    <div className="flex-1">
                                        <Input
                                            label="Capacité"
                                            type="number"
                                            value={series.capacity}
                                            onChange={(e) => handleSeriesChange(index, 'capacity', parseInt(e.target.value))}
                                            min="1"
                                            max="100"
                                            required
                                        />
                                    </div>
                                    {formData.series.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="error"
                                            size="sm"
                                            onClick={() => handleRemoveSeries(index)}
                                            icon={<Trash size={16} />}
                                        >
                                            Supprimer
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    </Card.Content>
                </Card>

                {/* Tarifs de Paiement */}
                <Card>
                    <Card.Header>
                        <Card.Title>Configuration des Tarifs</Card.Title>
                        <Card.Subtitle>Définissez les montants pour chaque tranche de paiement</Card.Subtitle>
                    </Card.Header>
                    <Card.Content>
                        <div className="space-y-6">
                            {paymentTranches.map((tranche) => {
                                const paymentAmount = formData.payment_amounts.find(
                                    p => p.payment_tranche_id === tranche.id
                                );
                                
                                return (
                                    <div key={tranche.id} className="border border-gray-200 rounded-lg p-6">
                                        <div className="flex items-center justify-between mb-4">
                                            <div>
                                                <h3 className="text-lg font-semibold text-gray-900">
                                                    {tranche.name}
                                                </h3>
                                                {tranche.description && (
                                                    <p className="text-sm text-gray-600">
                                                        {tranche.description}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    id={`required-${tranche.id}`}
                                                    checked={paymentAmount?.is_required || false}
                                                    onChange={(e) => handlePaymentAmountChange(tranche.id, 'is_required', e.target.checked)}
                                                    className="w-4 h-4 text-primary-violet border-gray-300 rounded focus:ring-primary-violet"
                                                />
                                                <label htmlFor={`required-${tranche.id}`} className="ml-2 text-sm text-gray-700">
                                                    Obligatoire
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <Input
                                                label="Montant Nouveaux Élèves (FCFA)"
                                                type="number"
                                                value={paymentAmount?.amount_new_students || 0}
                                                onChange={(e) => handlePaymentAmountChange(tranche.id, 'amount_new_students', e.target.value)}
                                                min="0"
                                                step="1000"
                                            />
                                            <Input
                                                label="Montant Anciens Élèves (FCFA)"
                                                type="number"
                                                value={paymentAmount?.amount_old_students || 0}
                                                onChange={(e) => handlePaymentAmountChange(tranche.id, 'amount_old_students', e.target.value)}
                                                min="0"
                                                step="1000"
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </Card.Content>
                </Card>

                {/* Actions */}
                <div className="flex justify-end space-x-4">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => navigate('/classes')}
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        loading={loading}
                        icon={<CheckCircle size={16} />}
                    >
                        Créer la Classe
                    </Button>
                </div>
            </form>
        </div>
    );
};

export default CreateClass;