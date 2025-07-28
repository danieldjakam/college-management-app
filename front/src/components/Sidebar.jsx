import React, { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { 
    HospitalFill, HouseHeartFill, 
    PeopleFill, GearFill, Search, 
    BookFill, FileTextFill,
    BarChartFill, List,
    PersonCircle, BoxArrowRight
} from 'react-bootstrap-icons'
import logo from '../images/logo.png'
import { apiEndpoints } from '../utils/api';
import { useApi } from '../hooks/useApi';
function Sidebar({ isCollapsed, onToggle }) {
    const [page, setPage] = useState(window.location.href.split('/')[3])
    const navigate = useNavigate();
    const [userInfos, setUserInfos] = useState({});
    // const [loading, setLoading] = useState(false)
    const [isMobile, setIsMobile] = useState(window.innerWidth <= 768)
    const [isOpen, setIsOpen] = useState(false)
    const { execute, loading } = useApi();
    
    useEffect(() => {
        const handleResize = () => {
            setIsMobile(window.innerWidth <= 768)
        }
        
        window.addEventListener('resize', handleResize)
        return () => window.removeEventListener('resize', handleResize)
    }, [])
    
    useEffect(() => {
        (async () => {
            if (sessionStorage.user !== undefined) {
                // setLoading(true);
                try {
                    const data = await execute(() => apiEndpoints.getAdminOrTeacher());
                    setUserInfos(data || []);
                } catch (error) {
                    console.log('Erreur lors du chargement des informations utilisateur:', error);
                }
                // setLoading(false);
            }
        })()
    }, [execute])

    // Navigation sections based on user role
    const getNavigationSections = () => {
        const userStat = sessionStorage.stat;
        
        if (userStat === 'ad') {
            return [
                {
                    title: 'Gestion Académique',
                    items: [
                        { name: 'Sections', href: '/', icon: <HospitalFill/> },
                        { name: 'Classes', href: '/class', icon: <HouseHeartFill/> },
                        { name: 'Enseignants', href: '/teachers', icon: <PeopleFill/> },
                    ]
                },
                {
                    title: 'Outils',
                    items: [
                        { name: 'Rechercher', href: '/search', icon: <Search/> },
                        { name: 'Documents', href: '/docs', icon: <FileTextFill/> },
                        { name: 'Statistiques', href: '/stats', icon: <BarChartFill/> }
                    ]
                },
                {
                    title: 'Administration',
                    items: [
                        { name: 'Profil', href: '/params', icon: <PersonCircle/> },
                        { name: 'Paramètres', href: '/settings', icon: <GearFill/> }
                    ]
                }
            ]
        } else if (userStat === 'comp') {
            return [
                {
                    title: 'Comptabilité',
                    items: [
                        { name: 'Classes', href: '/class-comp', icon: <HouseHeartFill/> },
                        { name: 'Statistiques', href: '/stats', icon: <BarChartFill/> },
                        { name: 'Rechercher', href: '/search', icon: <Search/> }
                    ]
                },
                {
                    title: 'Rapports',
                    items: [
                        { name: 'Documents', href: '/docs', icon: <FileTextFill/> }
                    ]
                },
                {
                    title: 'Compte',
                    items: [
                        { name: 'Profil', href: '/params-comp', icon: <PersonCircle/> }
                    ]
                }
            ]
        } else {
            return [
                {
                    title: 'Enseignement',
                    items: [
                        { name: 'Élèves', href: '/students/'+sessionStorage.classId, icon: <PeopleFill/> },
                        { name: 'Séquences', href: '/seqs', icon: <List/> },
                        { name: 'Trimestres', href: '/trims', icon: <BookFill/> }
                    ]
                },
                {
                    title: 'Outils',
                    items: [
                        { name: 'Rechercher', href: '/search', icon: <Search/> },
                        { name: 'Profil', href: '/params', icon: <PersonCircle/> }
                    ]
                }
            ]
        }
    }

    const logout = () => {
        sessionStorage.removeItem('stat')
        sessionStorage.removeItem('user')
        navigate('/login')
        window.location.reload()
    }

    const getUserDisplayName = () => {
        if (loading || !userInfos) return '';
        
        const stat = sessionStorage.stat;
        if (stat === 'ad' || stat === 'comp') {
            return userInfos.username || '';
        } else {
            return `${userInfos.name || ''} ${userInfos.subname || ''}`.trim();
        }
    }

    const getUserRole = () => {
        if (loading) return '';
        
        const stat = sessionStorage.stat;
        switch (stat) {
            case 'ad': return 'Administrateur';
            case 'comp': return 'Comptable';
            default: return 'Enseignant';
        }
    }

    const getUserInitials = () => {
        const name = getUserDisplayName();
        if (!name) return 'U';
        
        const words = name.split(' ').filter(word => word.length > 0);
        if (words.length >= 2) {
            return (words[0][0] + words[1][0]).toUpperCase();
        }
        return name[0].toUpperCase();
    }

    const handleLinkClick = (href) => {
        setPage(href);
        if (isMobile) {
            setIsOpen(false);
        }
    }

    if (sessionStorage.user === undefined) {
        return null;
    }

    const navigationSections = getNavigationSections();

    return (
        <>
            {/* Mobile Overlay */}
            {isMobile && isOpen && (
                <div 
                    className="fixed inset-0 bg-black bg-opacity-50 z-40"
                    onClick={() => setIsOpen(false)}
                />
            )}
            
            <div className={`sidebar ${isCollapsed && !isMobile ? 'collapsed' : ''} ${isMobile && isOpen ? 'open' : ''}`}>
                {/* Header */}
                <div className="sidebar-header">
                    <div className="sidebar-brand">
                        <img 
                            src={logo} 
                            alt="CPBD Logo" 
                            className="sidebar-logo"
                        />
                        {(!isCollapsed || isMobile) && (
                            <div>
                                <div className="sidebar-title">CPBD</div>
                                <div className="sidebar-subtitle"> College Polyvalent Bilingue de Douala</div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Navigation */}
                <div className="sidebar-nav">
                    {navigationSections.map((section, sectionIndex) => (
                        <div key={sectionIndex} className="nav-section">
                            {(!isCollapsed || isMobile) && (
                                <div className="nav-section-title">{section.title}</div>
                            )}
                            {section.items.map((item, itemIndex) => (
                                <div key={itemIndex} className="nav-item">
                                    <Link 
                                        to={item.href} 
                                        className={`nav-link ${page === item.href.replace('/', '') ? 'active' : ''}`}
                                        onClick={() => handleLinkClick(item.href.replace('/', ''))}
                                    >
                                        <div className="nav-icon">{item.icon}</div>
                                        {(!isCollapsed || isMobile) && (
                                            <span className="nav-text">{item.name}</span>
                                        )}
                                    </Link>
                                </div>
                            ))}
                        </div>
                    ))}
                </div>

                {/* User Profile */}
                <div className="sidebar-footer">
                    <div className="user-profile">
                        <div className="user-avatar">
                            {getUserInitials()}
                        </div>
                        {(!isCollapsed || isMobile) && (
                            <div className="user-info">
                                <div className="user-name">{getUserDisplayName()}</div>
                                <div className="user-role">{getUserRole()}</div>
                            </div>
                        )}
                        <button 
                            className="btn-secondary btn-sm"
                            onClick={logout}
                            title="Se déconnecter"
                            style={{
                                background: 'transparent',
                                border: 'none',
                                color: 'var(--gray-500)',
                                padding: '0.5rem',
                                borderRadius: 'var(--radius-full)',
                                transition: 'var(--transition-fast)',
                                marginLeft: 'auto'
                            }}
                        >
                            <BoxArrowRight size={16} />
                        </button>
                    </div>
                </div>
            </div>
        </>
    )
}

export default Sidebar