import React, { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { 
    HospitalFill, HouseHeartFill, 
    PeopleFill, GearFill, Search, 
    BookFill, FileTextFill,
    BarChartFill, List, CreditCard,
    PersonCircle, BoxArrowRight
} from 'react-bootstrap-icons'
import logo from '../images/logo.png'
import { useAuth } from '../hooks/useAuth';
function Sidebar({ isCollapsed, onToggle }) {
    const [page, setPage] = useState(window.location.href.split('/')[3])
    const navigate = useNavigate();
    const [isMobile, setIsMobile] = useState(window.innerWidth <= 768)
    const [isOpen, setIsOpen] = useState(false)
    const { user, isAuthenticated, logout: authLogout, isLoading } = useAuth();
    
    useEffect(() => {
        const handleResize = () => {
            setIsMobile(window.innerWidth <= 768)
        }
        
        window.addEventListener('resize', handleResize)
        return () => window.removeEventListener('resize', handleResize)
    }, [])
    
    // Les données utilisateur sont directement disponibles via le hook useAuth

    // Navigation sections based on user role
    const getNavigationSections = () => {
        if (!user || !user.role) {
            return [];
        }
        
        const userRole = user.role;
        
        if (userRole === 'admin') {
            return [
                {
                    title: 'Gestion Académique',
                    items: [
                        { name: 'Sections', href: '/sections', icon: <HospitalFill/> },
                        { name: 'Niveaux', href: '/levels', icon: <BookFill/> },
                        { name: 'Classes', href: '/school-classes', icon: <HouseHeartFill/> },
                        { name: 'Enseignants', href: '/teachers', icon: <PeopleFill/> },
                        { name: 'Tranches Paiement', href: '/payment-tranches', icon: <CreditCard/> },
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
                        { name: 'Profil', href: '/profile', icon: <PersonCircle/> },
                        { name: 'Paramètres', href: '/settings', icon: <GearFill/> }
                    ]
                }
            ]
        } else if (userRole === 'accountant') {
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
                        { name: 'Profil', href: '/profile', icon: <PersonCircle/> }
                    ]
                }
            ]
        } else {
            return [
                {
                    title: 'Enseignement',
                    items: [
                        { name: 'Élèves', href: '/students/'+(user.class_id || '1'), icon: <PeopleFill/> },
                        { name: 'Séquences', href: '/seqs', icon: <List/> },
                        { name: 'Trimestres', href: '/trims', icon: <BookFill/> }
                    ]
                },
                {
                    title: 'Outils',
                    items: [
                        { name: 'Rechercher', href: '/search', icon: <Search/> },
                        { name: 'Profil', href: '/profile', icon: <PersonCircle/> }
                    ]
                }
            ]
        }
    }

    const logout = async () => {
        try {
            await authLogout();
            navigate('/login');
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
            // Force logout même en cas d'erreur
            navigate('/login');
        }
    }

    const getUserDisplayName = () => {
        if (isLoading || !user) return '';
        
        if (user.role === 'admin' || user.role === 'accountant') {
            return user.username || user.name || '';
        } else {
            return user.name || '';
        }
    }

    const getUserRole = () => {
        if (isLoading || !user) return '';
        
        switch (user.role) {
            case 'admin': return 'Administrateur';
            case 'accountant': return 'Comptable';
            case 'teacher': return 'Enseignant';
            default: return 'Utilisateur';
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

    if (!isAuthenticated || !user) {
        return null;
    }

    const navigationSections = getNavigationSections();

    // Render the sidebar with proper styling
    return (
        <>
            {/* Mobile overlay */}
            {isMobile && isOpen && (
                <div 
                    className="sidebar-overlay" 
                    onClick={() => setIsOpen(false)}
                    style={{
                        position: 'fixed',
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        backgroundColor: 'rgba(0, 0, 0, 0.5)',
                        zIndex: 999
                    }}
                />
            )}

            {/* Sidebar */}
            <div 
                className={`sidebar ${isCollapsed ? 'collapsed' : ''} ${isMobile && isOpen ? 'mobile-open' : ''}`}
                style={{
                    position: 'fixed',
                    left: 0,
                    top: 0,
                    width: isCollapsed && !isMobile ? '80px' : '280px',
                    height: '100vh',
                    backgroundColor: '#1e293b',
                    color: 'white',
                    zIndex: 1000,
                    transition: 'width 0.3s ease',
                    transform: isMobile && !isOpen ? 'translateX(-100%)' : 'translateX(0)',
                    boxShadow: '2px 0 10px rgba(0, 0, 0, 0.1)',
                    display: 'flex',
                    flexDirection: 'column'
                }}
            >
                {/* Header */}
                <div style={{
                    padding: '20px',
                    borderBottom: '1px solid #334155',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '12px'
                }}>
                    <img 
                        src={logo} 
                        alt="CPBD Logo" 
                        style={{
                            width: '40px',
                            height: '40px',
                            objectFit: 'contain'
                        }}
                    />
                    {(!isCollapsed || isMobile) && (
                        <div>
                            <div style={{ fontSize: '18px', fontWeight: 'bold' }}>CPBD</div>
                            <div style={{ fontSize: '12px', color: '#94a3b8' }}>College Polyvalent Bilingue de Douala</div>
                        </div>
                    )}
                </div>

                {/* Navigation */}
                <div style={{ flex: 1, padding: '20px 0', overflowY: 'auto' }}>
                    {navigationSections.map((section, sectionIndex) => (
                        <div key={sectionIndex} style={{ marginBottom: '30px' }}>
                            {(!isCollapsed || isMobile) && (
                                <div style={{
                                    padding: '0 20px 10px',
                                    fontSize: '12px',
                                    color: '#64748b',
                                    textTransform: 'uppercase',
                                    letterSpacing: '0.05em',
                                    fontWeight: '600'
                                }}>
                                    {section.title}
                                </div>
                            )}
                            {section.items.map((item, itemIndex) => (
                                <Link 
                                    key={itemIndex}
                                    to={item.href} 
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '12px',
                                        padding: '12px 20px',
                                        color: page === item.href.replace('/', '') ? '#60a5fa' : '#e2e8f0',
                                        backgroundColor: page === item.href.replace('/', '') ? '#1e40af20' : 'transparent',
                                        textDecoration: 'none',
                                        transition: 'all 0.2s ease',
                                        borderLeft: page === item.href.replace('/', '') ? '3px solid #60a5fa' : '3px solid transparent'
                                    }}
                                    onClick={() => handleLinkClick(item.href.replace('/', ''))}
                                    onMouseEnter={(e) => {
                                        if (page !== item.href.replace('/', '')) {
                                            e.target.style.backgroundColor = '#334155';
                                        }
                                    }}
                                    onMouseLeave={(e) => {
                                        if (page !== item.href.replace('/', '')) {
                                            e.target.style.backgroundColor = 'transparent';
                                        }
                                    }}
                                >
                                    <div style={{ fontSize: '18px', minWidth: '18px' }}>{item.icon}</div>
                                    {(!isCollapsed || isMobile) && (
                                        <span style={{ fontSize: '14px', fontWeight: '500' }}>{item.name}</span>
                                    )}
                                </Link>
                            ))}
                        </div>
                    ))}
                </div>

                {/* User Profile */}
                <div style={{
                    padding: '20px',
                    borderTop: '1px solid #334155',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '12px'
                }}>
                    <div style={{
                        width: '40px',
                        height: '40px',
                        borderRadius: '50%',
                        backgroundColor: '#60a5fa',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontSize: '16px',
                        fontWeight: 'bold',
                        color: 'white'
                    }}>
                        {getUserInitials()}
                    </div>
                    {(!isCollapsed || isMobile) && (
                        <div style={{ flex: 1 }}>
                            <div style={{ fontSize: '14px', fontWeight: '500' }}>{getUserDisplayName()}</div>
                            <div style={{ fontSize: '12px', color: '#94a3b8' }}>{getUserRole()}</div>
                        </div>
                    )}
                    <button 
                        onClick={logout}
                        title="Se déconnecter"
                        style={{
                            background: 'transparent',
                            border: 'none',
                            color: '#94a3b8',
                            padding: '8px',
                            borderRadius: '6px',
                            cursor: 'pointer',
                            transition: 'all 0.2s ease',
                            fontSize: '16px'
                        }}
                        onMouseEnter={(e) => {
                            e.target.style.backgroundColor = '#334155';
                            e.target.style.color = '#f8fafc';
                        }}
                        onMouseLeave={(e) => {
                            e.target.style.backgroundColor = 'transparent';
                            e.target.style.color = '#94a3b8';
                        }}
                    >
                        <BoxArrowRight size={16} />
                    </button>
                </div>
            </div>
        </>
    );
}

export default Sidebar