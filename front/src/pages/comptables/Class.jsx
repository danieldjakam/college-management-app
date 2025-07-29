import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
    HouseHeartFill,
    PeopleFill,
    Search,
    Grid,
    List,
    InfoCircle
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';

const AccountantClasses = () => {
    const navigate = useNavigate();
    
    const [classes, setClasses] = useState([]);
    const [groupedClasses, setGroupedClasses] = useState({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [viewMode, setViewMode] = useState('grid');
    const [stats, setStats] = useState({
        total_students: 0,
        total_classes: 0,
        total_series: 0
    });

    useEffect(() => {
        loadClasses();
        loadDashboard();
    }, []);

    const loadClasses = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.accountant.getClasses();
            
            if (response.success) {
                setClasses(response.data.classes);
                setGroupedClasses(response.data.grouped_classes);
            } else {
                setError(response.message || 'Erreur lors du chargement des classes');
            }
        } catch (error) {
            console.error('Error loading classes:', error);
            setError('Erreur lors du chargement des classes');
        } finally {
            setLoading(false);
        }
    };

    const loadDashboard = async () => {
        try {
            const response = await secureApiEndpoints.accountant.dashboard();
            if (response.success) {
                setStats(response.data);
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    };

    // Filtrer les classes selon la recherche
    const filteredClasses = classes.filter(classe =>
        classe.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        classe.level?.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        classe.level?.section?.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    if (loading && classes.length === 0) {
        return (
            <div className="container-fluid py-4">
                <div className="text-center py-5">
                    <div className="spinner-border" role="status">
                        <span className="visually-hidden">Chargement des classes...</span>
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
                        <div>
                            <h2 className="h4 mb-1">Gestion des Classes</h2>
                            <p className="text-muted mb-0">
                                Visualisation des classes et gestion des élèves (Mode Comptable)
                            </p>
                        </div>
                        <div className="d-flex align-items-center gap-2">
                            <span className="badge bg-info">
                                <InfoCircle size={14} className="me-1" />
                                Lecture seule
                            </span>
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

            {/* Stats Cards */}
            <div className="row mb-4">
                <div className="col-md-3">
                    <div className="card bg-primary text-white">
                        <div className="card-body">
                            <div className="d-flex justify-content-between">
                                <div>
                                    <div className="fs-2 fw-bold">{stats.total_students}</div>
                                    <div>Élèves Total</div>
                                </div>
                                <PeopleFill size={40} className="opacity-75" />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-3">
                    <div className="card bg-success text-white">
                        <div className="card-body">
                            <div className="d-flex justify-content-between">
                                <div>
                                    <div className="fs-2 fw-bold">{stats.total_classes}</div>
                                    <div>Classes</div>
                                </div>
                                <HouseHeartFill size={40} className="opacity-75" />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-3">
                    <div className="card bg-warning text-white">
                        <div className="card-body">
                            <div className="d-flex justify-content-between">
                                <div>
                                    <div className="fs-2 fw-bold">{stats.total_series}</div>
                                    <div>Séries</div>
                                </div>
                                <Grid size={40} className="opacity-75" />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-3">
                    <div className="card bg-info text-white">
                        <div className="card-body">
                            <div className="d-flex justify-content-between">
                                <div>
                                    <div className="fs-6 fw-bold">{stats.current_year}</div>
                                    <div>Année Scolaire</div>
                                </div>
                                <InfoCircle size={40} className="opacity-75" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Filters */}
            <div className="row mb-4">
                <div className="col-12">
                    <div className="card">
                        <div className="card-body">
                            <div className="row g-3">
                                <div className="col-md-8">
                                    <label className="form-label">Rechercher</label>
                                    <div className="position-relative">
                                        <Search className="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" size={16} />
                                        <input
                                            type="text"
                                            className="form-control ps-5"
                                            placeholder="Rechercher par nom de classe, niveau ou section..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="col-md-4 d-flex align-items-end">
                                    <div className="btn-group w-100" role="group">
                                        <button
                                            type="button"
                                            className={`btn ${viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                            onClick={() => setViewMode('grid')}
                                        >
                                            <Grid size={16} className="me-1" />
                                            Grille
                                        </button>
                                        <button
                                            type="button"
                                            className={`btn ${viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                            onClick={() => setViewMode('list')}
                                        >
                                            <List size={16} className="me-1" />
                                            Liste
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Classes Grid */}
            {viewMode === 'grid' ? (
                <div className="row">
                    {filteredClasses.length === 0 ? (
                        <div className="col-12">
                            <div className="card">
                                <div className="card-body text-center py-5">
                                    <HouseHeartFill size={48} className="text-muted mb-3" />
                                    <h5 className="text-muted">Aucune classe trouvée</h5>
                                    <p className="text-muted">
                                        {searchTerm 
                                            ? 'Aucune classe ne correspond à vos critères de recherche.'
                                            : 'Aucune classe disponible pour le moment.'
                                        }
                                    </p>
                                </div>
                            </div>
                        </div>
                    ) : (
                        filteredClasses.map((classe) => (
                            <div key={classe.id} className="col-md-6 col-lg-4 mb-4">
                                <div className="card h-100 hover-card">
                                    <div className="card-body">
                                        <div className="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 className="card-title mb-1">{classe.name}</h6>
                                                <small className="text-muted">
                                                    {classe.level?.section?.name} - {classe.level?.name}
                                                </small>
                                            </div>
                                            <span className="badge bg-primary">
                                                {classe.series_count || 0} série{(classe.series_count || 0) > 1 ? 's' : ''}
                                            </span>
                                        </div>
                                        
                                        <div className="d-flex justify-content-between align-items-center mt-auto">
                                            <small className="text-muted">
                                                {classe.total_students || 0} élève{(classe.total_students || 0) > 1 ? 's' : ''}
                                            </small>
                                            <Link
                                                to={`/class-comp/${classe.id}`}
                                                className="btn btn-primary btn-sm"
                                            >
                                                Voir les séries
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            ) : (
                // Liste view
                <div className="row">
                    <div className="col-12">
                        <div className="card">
                            <div className="card-body p-0">
                                <div className="table-responsive">
                                    <table className="table table-hover mb-0">
                                        <thead className="table-light">
                                            <tr>
                                                <th>Classe</th>
                                                <th>Section</th>
                                                <th>Niveau</th>
                                                <th>Séries</th>
                                                <th>Élèves</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {filteredClasses.map((classe) => (
                                                <tr key={classe.id}>
                                                    <td className="fw-medium">{classe.name}</td>
                                                    <td>{classe.level?.section?.name}</td>
                                                    <td>{classe.level?.name}</td>
                                                    <td>
                                                        <span className="badge bg-light text-dark">
                                                            {classe.series_count || 0} série{(classe.series_count || 0) > 1 ? 's' : ''}
                                                        </span>
                                                    </td>
                                                    <td>{classe.total_students || 0} élève{(classe.total_students || 0) > 1 ? 's' : ''}</td>
                                                    <td>
                                                        <Link
                                                            to={`/class-comp/${classe.id}`}
                                                            className="btn btn-sm btn-primary"
                                                        >
                                                            Voir les séries
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* CSS for hover effects */}
            <style jsx>{`
                .hover-card {
                    transition: box-shadow 0.2s ease-in-out;
                }
                .hover-card:hover {
                    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
                }
            `}</style>
        </div>
    );
};

export default AccountantClasses;