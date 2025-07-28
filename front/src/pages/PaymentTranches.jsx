import React, { useState, useEffect } from 'react';
import { 
    Plus, 
    PencilSquare, 
    Trash,
    GripVertical,
    CheckCircle,
    XCircle
} from 'react-bootstrap-icons';
import Swal from 'sweetalert2';

// Components
import { 
    Card, 
    Button, 
    Alert, 
    LoadingSpinner, 
    Modal,
    Input
} from '../components/UI';

// Hooks
import { useApi } from '../hooks/useApi';
import { apiEndpoints } from '../utils/api';

const PaymentTranches = () => {
    const { execute, loading } = useApi();
    const [tranches, setTranches] = useState([]);
    const [error, setError] = useState('');
    const [isAddModal, setIsAddModal] = useState(false);
    const [isEditModal, setIsEditModal] = useState(false);
    const [editingTranche, setEditingTranche] = useState(null);
    const [draggedItem, setDraggedItem] = useState(null);

    const [formData, setFormData] = useState({
        name: '',
        description: '',
        order: 0
    });

    useEffect(() => {
        loadTranches();
    }, []);

    const loadTranches = async () => {
        try {
            const data = await execute(() => apiEndpoints.getAllPaymentTranches());
            setTranches(data.data || []);
        } catch (err) {
            setError('Erreur lors du chargement des tranches');
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        try {
            if (editingTranche) {
                await execute(() => apiEndpoints.updatePaymentTranche(editingTranche.id, formData));
                setIsEditModal(false);
                setEditingTranche(null);
            } else {
                await execute(() => apiEndpoints.addPaymentTranche(formData));
                setIsAddModal(false);
            }
            
            resetForm();
            loadTranches();
            
            Swal.fire({
                title: 'Succès',
                text: `Tranche ${editingTranche ? 'modifiée' : 'créée'} avec succès`,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        } catch (err) {
            setError(`Erreur lors de ${editingTranche ? 'la modification' : 'la création'} de la tranche`);
        }
    };

    const handleEdit = (tranche) => {
        setEditingTranche(tranche);
        setFormData({
            name: tranche.name,
            description: tranche.description || '',
            order: tranche.order
        });
        setIsEditModal(true);
    };

    const handleDelete = async (tranche) => {
        const result = await Swal.fire({
            title: 'Confirmez la suppression',
            text: `Êtes-vous sûr de vouloir supprimer la tranche "${tranche.name}" ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        });

        if (result.isConfirmed) {
            try {
                await execute(() => apiEndpoints.deletePaymentTranche(tranche.id));
                loadTranches();
                
                Swal.fire({
                    title: 'Supprimée',
                    text: 'La tranche a été supprimée avec succès',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (err) {
                setError('Erreur lors de la suppression de la tranche');
            }
        }
    };

    const resetForm = () => {
        setFormData({
            name: '',
            description: '',
            order: 0
        });
        setEditingTranche(null);
    };

    const handleCloseModal = () => {
        setIsAddModal(false);
        setIsEditModal(false);
        resetForm();
        setError('');
    };

    // Drag and Drop handlers
    const handleDragStart = (e, tranche) => {
        setDraggedItem(tranche);
        e.dataTransfer.effectAllowed = 'move';
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    };

    const handleDrop = async (e, targetTranche) => {
        e.preventDefault();
        
        if (!draggedItem || draggedItem.id === targetTranche.id) {
            setDraggedItem(null);
            return;
        }

        const newTranches = [...tranches];
        const draggedIndex = newTranches.findIndex(t => t.id === draggedItem.id);
        const targetIndex = newTranches.findIndex(t => t.id === targetTranche.id);

        // Réorganiser les tranches
        newTranches.splice(draggedIndex, 1);
        newTranches.splice(targetIndex, 0, draggedItem);

        // Mettre à jour les ordres
        const reorderedTranches = newTranches.map((tranche, index) => ({
            ...tranche,
            order: index + 1
        }));

        setTranches(reorderedTranches);
        setDraggedItem(null);

        try {
            const reorderData = {
                tranches: reorderedTranches.map(t => ({
                    id: t.id,
                    order: t.order
                }))
            };
            
            await execute(() => apiEndpoints.reorderPaymentTranches(reorderData));
            
            Swal.fire({
                title: 'Succès',
                text: 'Ordre des tranches mis à jour',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        } catch (err) {
            setError('Erreur lors de la réorganisation des tranches');
            loadTranches(); // Recharger les données originales
        }
    };

    return (
        <div className="animate-fade-in">
            {/* Header */}
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-800 mb-2">
                    Gestion des Tranches de Paiement
                </h1>
                <p className="text-gray-600">
                    Configurez les tranches de paiement qui seront utilisées lors de la création des classes
                </p>
            </div>

            {/* Error Alert */}
            {error && (
                <Alert variant="error" className="mb-6" dismissible onDismiss={() => setError('')}>
                    {error}
                </Alert>
            )}

            {/* Main Content */}
            <Card>
                <Card.Header>
                    <div className="flex justify-between items-center">
                        <div>
                            <Card.Title>Tranches de Paiement</Card.Title>
                            <Card.Subtitle>
                                Glissez-déposez pour réorganiser l'ordre des tranches
                            </Card.Subtitle>
                        </div>
                        <Button
                            variant="primary"
                            onClick={() => setIsAddModal(true)}
                            icon={<Plus size={16} />}
                        >
                            Ajouter une Tranche
                        </Button>
                    </div>
                </Card.Header>

                <Card.Content>
                    {loading ? (
                        <div className="flex justify-center py-12">
                            <LoadingSpinner text="Chargement des tranches..." />
                        </div>
                    ) : tranches.length > 0 ? (
                        <div className="space-y-4">
                            {tranches.map((tranche) => (
                                <div
                                    key={tranche.id}
                                    draggable
                                    onDragStart={(e) => handleDragStart(e, tranche)}
                                    onDragOver={handleDragOver}
                                    onDrop={(e) => handleDrop(e, tranche)}
                                    className={`
                                        border rounded-lg p-4 bg-white shadow-sm hover:shadow-md transition-shadow cursor-move
                                        ${draggedItem?.id === tranche.id ? 'opacity-50' : ''}
                                    `}
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-4">
                                            <GripVertical className="text-gray-400" size={20} />
                                            <div className="flex items-center space-x-3">
                                                <div className="bg-primary-violet text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">
                                                    {tranche.order}
                                                </div>
                                                <div>
                                                    <h3 className="font-semibold text-gray-900">
                                                        {tranche.name}
                                                    </h3>
                                                    {tranche.description && (
                                                        <p className="text-sm text-gray-600">
                                                            {tranche.description}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center space-x-3">
                                            <div className="flex items-center">
                                                {tranche.is_active ? (
                                                    <CheckCircle className="text-green-500" size={20} />
                                                ) : (
                                                    <XCircle className="text-red-500" size={20} />
                                                )}
                                                <span className={`ml-2 text-sm ${tranche.is_active ? 'text-green-600' : 'text-red-600'}`}>
                                                    {tranche.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </div>
                                            
                                            <div className="flex space-x-2">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    onClick={() => handleEdit(tranche)}
                                                    icon={<PencilSquare size={14} />}
                                                >
                                                    Modifier
                                                </Button>
                                                <Button
                                                    variant="error"
                                                    size="sm"
                                                    onClick={() => handleDelete(tranche)}
                                                    icon={<Trash size={14} />}
                                                >
                                                    Supprimer
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <div className="text-gray-400 mb-4">
                                <Plus size={48} className="mx-auto" />
                            </div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                Aucune tranche configurée
                            </h3>
                            <p className="text-gray-500 mb-4">
                                Commencez par créer votre première tranche de paiement.
                            </p>
                            <Button
                                variant="primary"
                                onClick={() => setIsAddModal(true)}
                                icon={<Plus size={16} />}
                            >
                                Créer une Tranche
                            </Button>
                        </div>
                    )}
                </Card.Content>
            </Card>

            {/* Add/Edit Modal */}
            <Modal
                isOpen={isAddModal || isEditModal}
                onClose={handleCloseModal}
                title={editingTranche ? 'Modifier la Tranche' : 'Ajouter une Tranche'}
                size="lg"
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Input
                        label="Nom de la tranche"
                        type="text"
                        value={formData.name}
                        onChange={(e) => setFormData({...formData, name: e.target.value})}
                        placeholder="Ex: Inscription, 1ère Tranche, etc."
                        required
                    />

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Description (optionnelle)
                        </label>
                        <textarea
                            value={formData.description}
                            onChange={(e) => setFormData({...formData, description: e.target.value})}
                            placeholder="Description de la tranche..."
                            rows={3}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-violet focus:border-transparent"
                        />
                    </div>

                    <Input
                        label="Ordre"
                        type="number"
                        value={formData.order}
                        onChange={(e) => setFormData({...formData, order: parseInt(e.target.value)})}
                        min="1"
                        required
                    />

                    <div className="flex justify-end space-x-3 pt-4">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={handleCloseModal}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="submit"
                            variant="primary"
                            loading={loading}
                        >
                            {editingTranche ? 'Modifier' : 'Créer'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
};

export default PaymentTranches;