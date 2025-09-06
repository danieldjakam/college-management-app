import React from 'react';
import { Badge } from 'react-bootstrap';
import {
    House, Calendar, ChatDots, PersonCircle, BoxArrowRight,
    Bell, People, Trophy, ClockHistory
} from 'react-bootstrap-icons';
import logo from '../images/logo.png';
import './ParentSidebar.css';

const ParentSidebar = ({ 
    activeTab, 
    setActiveTab, 
    parentData, 
    dashboardData, 
    handleLogout 
}) => {
    const getInitials = (firstName, lastName) => {
        return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase();
    };

    return (
        <div className="parent-sidebar">
            {/* Header avec logo et branding */}
            <div className="parent-sidebar-header">
                <div className="parent-sidebar-brand">
                    <div className="parent-sidebar-logo">
                        <img 
                            src={logo} 
                            alt="CPBD Logo" 
                            style={{ width: '100%', height: '100%', objectFit: 'contain' }}
                        />
                    </div>
                    <div>
                        <div className="parent-sidebar-title">CPBD</div>
                        <div className="parent-sidebar-subtitle">Espace Parent</div>
                    </div>
                </div>
            </div>

            {/* Navigation */}
            <div className="parent-sidebar-nav">
                <div className="parent-nav-section">
                    <div className="parent-nav-section-title">Navigation</div>
                    
                    <div className="parent-nav-item">
                        <button
                            className={`parent-nav-link ${activeTab === 'dashboard' ? 'active' : ''}`}
                            onClick={() => setActiveTab('dashboard')}
                        >
                            <House size={20} className="parent-nav-icon" />
                            <span className="parent-nav-text">Accueil</span>
                            {dashboardData?.summary?.total_children > 0 && (
                                <Badge bg="info" className="parent-nav-badge">
                                    {dashboardData.summary.total_children}
                                </Badge>
                            )}
                        </button>
                    </div>

                    <div className="parent-nav-item">
                        <button
                            className={`parent-nav-link ${activeTab === 'calendar' ? 'active' : ''}`}
                            onClick={() => setActiveTab('calendar')}
                        >
                            <Calendar size={20} className="parent-nav-icon" />
                            <span className="parent-nav-text">Calendrier</span>
                        </button>
                    </div>

                    <div className="parent-nav-item">
                        <button
                            className={`parent-nav-link ${activeTab === 'results' ? 'active' : ''}`}
                            onClick={() => setActiveTab('results')}
                        >
                            <Trophy size={20} className="parent-nav-icon" />
                            <span className="parent-nav-text">Résultats</span>
                        </button>
                    </div>

                    <div className="parent-nav-item">
                        <button
                            className={`parent-nav-link ${activeTab === 'schedule' ? 'active' : ''}`}
                            onClick={() => setActiveTab('schedule')}
                        >
                            <ClockHistory size={20} className="parent-nav-icon" />
                            <span className="parent-nav-text">Emploi du temps</span>
                        </button>
                    </div>

                    <div className="parent-nav-item">
                        <button
                            className={`parent-nav-link ${activeTab === 'messages' ? 'active' : ''}`}
                            onClick={() => setActiveTab('messages')}
                        >
                            <ChatDots size={20} className="parent-nav-icon" />
                            <span className="parent-nav-text">Messages</span>
                            {dashboardData?.unread_messages > 0 && (
                                <Badge bg="danger" className="parent-nav-badge">
                                    {dashboardData.unread_messages}
                                </Badge>
                            )}
                        </button>
                    </div>

                    {/* Notifications seulement si il y en a */}
                    {dashboardData?.notifications?.unread_count > 0 && (
                        <div className="parent-nav-item">
                            <button
                                className={`parent-nav-link ${activeTab === 'notifications' ? 'active' : ''}`}
                                onClick={() => setActiveTab('notifications')}
                            >
                                <Bell size={20} className="parent-nav-icon" />
                                <span className="parent-nav-text">Notifications</span>
                                <Badge bg="warning" className="parent-nav-badge">
                                    {dashboardData.notifications.unread_count}
                                </Badge>
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {/* Footer avec profil et déconnexion */}
            <div className="parent-sidebar-footer">
                <div className="parent-user-profile">
                    <div className="parent-user-avatar">
                        {getInitials(parentData?.first_name, parentData?.last_name)}
                    </div>
                    <div className="parent-user-info">
                        <div className="parent-user-name">
                            {parentData?.first_name} {parentData?.last_name}
                        </div>
                        <div className="parent-user-role">Parent</div>
                    </div>
                    <button
                        className="parent-logout-btn"
                        onClick={handleLogout}
                        title="Déconnexion"
                    >
                        <BoxArrowRight size={18} />
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ParentSidebar;