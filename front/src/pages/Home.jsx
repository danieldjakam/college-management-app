import React, { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom';
import { 
    Plus, 
    People, 
    Book, 
    Award,
    PencilSquare,
    Trash,
    ArrowRight
} from 'react-bootstrap-icons';
import Swal from 'sweetalert2';

// Components
import { 
    Card, 
    Button, 
    Alert, 
    LoadingSpinner, 
    Modal, 
    StatsCard 
} from '../components/UI';
import AddSection from './Sections.jsx/AddSection';
import EditSection from './Sections.jsx/EditSection';

// Utils
import { sectionTraductions } from '../local/section';
import { useApi } from '../hooks/useApi';
import { apiEndpoints } from '../utils/api';
import { getLang } from '../utils/lang';

function Home() {
    const { execute, loading } = useApi();
    const [sections, setSections] = useState([]);
    const [stats, setStats] = useState({});
    const [error, setError] = useState('');
    const [idAddSection, setIsAddSection] = useState(false);
    const [id, setId] = useState('');
    const [idEditSection, setIsEditSection] = useState(false);
    const [loadingDel, setLoadingDel] = useState(false);

    const navigate = useNavigate();
    
    // Redirect non-admin users
    useEffect(() => {
        if (sessionStorage.stat !== 'ad' && sessionStorage.stat !== 'comp') {
            navigate('/students/' + sessionStorage.classId);
        }
    }, [navigate]);


    useEffect(() => {
        const fetchStats = async () => {
            try {
                // Fetch statistics for dashboard
                // This would be a real API call in production
                setStats({
                    totalStudents: 1250,
                    totalTeachers: 45,
                    totalClasses: 28,
                    totalSections: sections.length
                });
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        };
        fetchSections();
        fetchStats();
    }, [sections.length, execute]);

    const fetchSections = async () => {
        try {
            const data = await apiEndpoints.getAllSections();
            setSections(data || []);
        } catch (error) {
            setError('Erreur lors du chargement des sections');
        }
    };


    const deleteSection = (sectionId) => {
        Swal.fire({
            title: 'Confirmez la suppression !',
            text: 'Cette action est irréversible !',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then(async (result) => {
            if (result.isConfirmed) {
                setLoadingDel(true);
                try {
                    await execute(() => apiEndpoints.deleteSection(sectionId));
                    Swal.fire('Supprimé !', 'La section a été supprimée.', 'success');
                    fetchSections(); // Refresh sections
                } catch (error) {
                    setError('Erreur lors de la suppression');
                }
                setLoadingDel(false);
            }
        });
    };

    const chooseSection = (sectionId, sectionName) => {
        sessionStorage.setItem('section_id', sectionId);
        navigate('/classBySection/' + sectionName);
    };

    return (
        <div className="animate-fade-in">
            {/* Header */}
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-800 mb-2">
                    Tableau de Bord
                </h1>
                <p className="text-gray-600">
                    Bienvenue dans le système de gestion scolaire GSBPL
                </p>
            </div>

            {/* Stats Cards */}
            <div className="stats-grid mb-8">
                <StatsCard
                    title="Total Élèves"
                    value={stats.totalStudents || 0}
                    icon={<People />}
                    change={8.2}
                    changeType="increase"
                    changeLabel="ce mois"
                    color="primary"
                />
                <StatsCard
                    title="Enseignants"
                    value={stats.totalTeachers || 0}
                    icon={<Award />}
                    change={3.1}
                    changeType="increase"
                    changeLabel="ce mois"
                    color="success"
                />
                <StatsCard
                    title="Classes"
                    value={stats.totalClasses || 0}
                    icon={<Book />}
                    color="info"
                />
                <StatsCard
                    title="Sections"
                    value={sections.length}
                    icon={<Book />}
                    color="warning"
                />
            </div>

            {/* Error Alert */}
            {error && (
                <Alert variant="error" dismissible onDismiss={() => setError('')}>
                    {error}
                </Alert>
            )}

            {/* Sections Management */}
            <Card className="mb-6">
                <Card.Header>
                    <div className="flex justify-between items-center">
                        <div>
                            <Card.Title>Sections Académiques</Card.Title>
                            <Card.Subtitle>
                                Gérez les sections de votre établissement
                            </Card.Subtitle>
                        </div>
                        <Button
                            variant="primary"
                            onClick={() => setIsAddSection(true)}
                            icon={<Plus size={16} />}
                        >
                            {sectionTraductions[getLang()].addSection}
                        </Button>
                    </div>
                </Card.Header>

                <Card.Content>
                    {loading ? (
                        <div className="flex justify-center py-12">
                            <LoadingSpinner text="Chargement des sections..." />
                        </div>
                    ) : sections.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {sections.map((section) => (
                                <Card key={section.id} className="group">
                                    <Card.Content>
                                        <div className="flex items-start justify-between mb-4">
                                            <div className="flex-1">
                                                <h3 className="text-lg font-semibold text-gray-800 mb-1">
                                                    {section.name}
                                                </h3>
                                                <p className="text-sm text-gray-500 mb-2">
                                                    Type: {section.type}
                                                </p>
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <People size={14} className="mr-1" />
                                                    {section.total_class} classe{section.total_class > 1 ? 's' : ''}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex gap-2">
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                onClick={() => chooseSection(section.id, section.name)}
                                                icon={<ArrowRight size={14} />}
                                                className="flex-1"
                                            >
                                                Voir Classes
                                            </Button>
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                onClick={() => {
                                                    setId(section.id);
                                                    setIsEditSection(true);
                                                }}
                                                icon={<PencilSquare size={14} />}
                                            >
                                                Éditer
                                            </Button>
                                            <Button
                                                variant="error"
                                                size="sm"
                                                onClick={() => deleteSection(section.id)}
                                                disabled={loadingDel}
                                                icon={<Trash size={14} />}
                                            >
                                                Supprimer
                                            </Button>
                                        </div>
                                    </Card.Content>
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <Book size={48} className="mx-auto text-gray-400 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                Aucune section trouvée
                            </h3>
                            <p className="text-gray-500 mb-4">
                                Commencez par créer votre première section académique.
                            </p>
                            <Button
                                variant="primary"
                                onClick={() => setIsAddSection(true)}
                                icon={<Plus size={16} />}
                            >
                                Créer une section
                            </Button>
                        </div>
                    )}
                </Card.Content>
            </Card>

            {/* Modals */}
            <Modal
                isOpen={idAddSection}
                onClose={() => setIsAddSection(false)}
                title="Ajouter une Section"
                size="lg"
            >
                <AddSection
                    error={error}
                    setError={setError}
                    setIsAddSection={setIsAddSection}
                    onSuccess={fetchSections}
                />
            </Modal>

            <Modal
                isOpen={idEditSection}
                onClose={() => setIsEditSection(false)}
                title="Modifier la Section"
                size="lg"
            >
                <EditSection
                    error={error}
                    setError={setError}
                    id={id}
                    setIsEditSection={setIsEditSection}
                    onSuccess={fetchSections}
                />
            </Modal>
        </div>
    );
}

export default Home;