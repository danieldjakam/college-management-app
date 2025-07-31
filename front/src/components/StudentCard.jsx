import React from 'react';
import { useSchool } from '../contexts/SchoolContext';
import defaultPhoto from '../images/1.png';
import cameroonFlag from '../images/carte.jpeg'; // On utilisera le drapeau intégré dans l'image de référence

const StudentCard = ({ student, schoolYear, onPrint }) => {
    const { schoolSettings, getLogoUrl } = useSchool();
    
    const getStudentPhotoUrl = (student) => {
        if (!student.photo) return defaultPhoto;
        
        // Si c'est déjà une URL complète
        if (student.photo.startsWith('http')) {
            return student.photo;
        }
        
        // Si c'est un chemin relatif, construire l'URL complète
        const baseUrl = process.env.REACT_APP_API_URL || 'http://localhost:4000';
        return `${baseUrl}/storage/${student.photo}`;
    };

    const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    const generateMatricule = (student) => {
        if (student.matricule) return student.matricule;
        
        // Générer un matricule basé sur l'année et l'ID
        const year = new Date().getFullYear().toString().slice(-2);
        const id = student.id.toString().padStart(4, '0');
        return `${year}${id}AB`;
    };

    const generateQRCode = (student) => {
        // Pour l'instant, on utilise un QR code générique
        // Dans une implémentation complète, on utiliserait une bibliothèque comme qrcode
        const qrData = `Student: ${student.first_name} ${student.last_name}, ID: ${student.id}, Year: ${schoolYear?.year || new Date().getFullYear()}`;
        
        // QR code placeholder - dans une vraie implémentation, utiliser une lib comme 'qrcode'
        return `https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=${encodeURIComponent(qrData)}`;
    };

    const cardStyle = {
        width: '85.6mm', // Format carte standard
        height: '54mm',
        backgroundColor: '#fff',
        border: '2px solid #e67e22',
        borderRadius: '8px',
        fontFamily: 'Arial, sans-serif',
        fontSize: '9px',
        lineHeight: '1.1',
        overflow: 'hidden',
        position: 'relative',
        boxShadow: '0 2px 10px rgba(0,0,0,0.1)'
    };

    const headerStyle = {
        backgroundColor: '#f8f9fa',
        padding: '4px 8px',
        borderBottom: '1px solid #e67e22',
        textAlign: 'center',
        fontSize: '8px',
        fontWeight: 'bold'
    };

    const bodyStyle = {
        padding: '6px',
        display: 'flex',
        height: 'calc(100% - 30px)'
    };

    const photoStyle = {
        width: '40px',
        height: '45px',
        objectFit: 'cover',
        border: '1px solid #ddd',
        borderRadius: '2px',
        marginRight: '6px'
    };

    const infoStyle = {
        flex: 1,
        fontSize: '8px',
        lineHeight: '1.2'
    };

    const qrStyle = {
        position: 'absolute',
        bottom: '4px',
        right: '4px',
        width: '25px',
        height: '25px'
    };

    const footerStyle = {
        position: 'absolute',
        bottom: '2px',
        left: '4px',
        fontSize: '6px',
        color: '#666'
    };

    return (
        <div style={cardStyle} className="student-card">
            {/* En-tête avec drapeau et nom de l'école */}
            <div style={headerStyle}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <div style={{ fontSize: '6px' }}>
                        🇨🇲 RÉPUBLIQUE DU CAMEROUN<br />
                        Paix - Travail - Patrie
                    </div>
                    <div style={{ textAlign: 'center', flex: 1 }}>
                        <div style={{ fontSize: '10px', fontWeight: 'bold', color: '#2c3e50' }}>
                            {schoolSettings.school_name?.toUpperCase() || 'LYCÉE GANALIS'}
                        </div>
                        <div style={{ fontSize: '8px', color: '#7f8c8d' }}>
                            CARTE D'IDENTITÉ SCOLAIRE
                        </div>
                    </div>
                    <div style={{ width: '20px' }}>
                        {getLogoUrl() && (
                            <img 
                                src={getLogoUrl()} 
                                alt="Logo" 
                                style={{ width: '18px', height: '18px', objectFit: 'contain' }}
                            />
                        )}
                    </div>
                </div>
            </div>

            {/* Corps de la carte */}
            <div style={bodyStyle}>
                {/* Photo de l'élève */}
                <div>
                    <img 
                        src={getStudentPhotoUrl(student)} 
                        alt={`${student.first_name} ${student.last_name}`}
                        style={photoStyle}
                        onError={(e) => {
                            e.target.src = defaultPhoto;
                        }}
                    />
                </div>

                {/* Informations de l'élève */}
                <div style={infoStyle}>
                    <div style={{ fontSize: '7px', marginBottom: '2px', fontWeight: 'bold' }}>
                        ANNÉE SCOLAIRE : {schoolYear?.year || `${new Date().getFullYear()} - ${new Date().getFullYear() + 1}`}
                    </div>
                    
                    <div style={{ marginBottom: '1px' }}>
                        <strong>Nom :</strong> {student.last_name?.toUpperCase() || ''}
                    </div>
                    
                    <div style={{ marginBottom: '1px' }}>
                        <strong>Prénom :</strong> {student.first_name || ''}
                    </div>
                    
                    <div style={{ marginBottom: '1px' }}>
                        <strong>Née le :</strong> {formatDate(student.date_of_birth)} 
                        <strong style={{ marginLeft: '8px' }}>À</strong> {student.place_of_birth || 'DOUALA'}
                    </div>
                    
                    <div style={{ marginBottom: '1px' }}>
                        <strong>Sexe :</strong> {student.gender === 'male' ? 'M' : student.gender === 'female' ? 'F' : 'M'}
                        <strong style={{ marginLeft: '15px' }}>Classe :</strong> {student.class_series?.name || student.current_class || ''}
                    </div>
                    
                    <div style={{ marginBottom: '1px' }}>
                        <strong>Contact:</strong> {student.phone || '***********'}
                    </div>
                </div>

                {/* QR Code */}
                <div style={qrStyle}>
                    <img 
                        src={generateQRCode(student)} 
                        alt="QR Code"
                        style={{ width: '100%', height: '100%', objectFit: 'contain' }}
                    />
                </div>
            </div>

            {/* Matricule et copyright */}
            <div style={footerStyle}>
                <strong>MAT. : {generateMatricule(student)}</strong>
            </div>

            <div style={{ 
                position: 'absolute', 
                bottom: '1px', 
                right: '30px', 
                fontSize: '5px', 
                color: '#aaa' 
            }}>
                SYSTÈME PAO / PROTECTED BY GANALIS SCHOOL / {new Date().getFullYear()} ALL RIGHTS RESERVED
            </div>
        </div>
    );
};

export default StudentCard;