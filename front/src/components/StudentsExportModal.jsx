import React, { useState } from 'react';
import { Modal, Button, Form, Alert } from 'react-bootstrap';
import { Download, FileText, FileSpreadsheet, FilePdf } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../utils/apiMigration';
import { useSchool } from '../contexts/SchoolContext';

const StudentsExportModal = ({ 
    show, 
    onHide, 
    seriesId = null,
    seriesName = ""
}) => {
    const { schoolSettings } = useSchool();
    const [exportFormat, setExportFormat] = useState('csv');
    const [isLoading, setIsLoading] = useState(false);
    const [alert, setAlert] = useState(null);

    const showAlert = (type, message) => {
        setAlert({ type, message });
        setTimeout(() => setAlert(null), 5000);
    };

    const getStudentName = (student) => {
        // Try different combinations to get the student's full name
        if (student.full_name && student.full_name.trim()) {
            return student.full_name.trim();
        }
        
        // Try last_name + first_name combination
        if (student.last_name && student.first_name) {
            return `${student.last_name} ${student.first_name}`.trim();
        }
        
        // Try first_name + last_name combination
        if (student.first_name && student.last_name) {
            return `${student.first_name} ${student.last_name}`.trim();
        }
        
        // Try subname + name combination
        if (student.subname && student.name) {
            return `${student.subname} ${student.name}`.trim();
        }
        
        // Try name + subname combination
        if (student.name && student.subname) {
            return `${student.name} ${student.subname}`.trim();
        }
        
        // Try individual fields
        if (student.last_name || student.subname) {
            return (student.last_name || student.subname).trim();
        }
        
        if (student.first_name || student.name) {
            return (student.first_name || student.name).trim();
        }
        
        return 'Nom non disponible';
    };


    const addPDFHeader = async (doc) => {
        // Fond d'en-tête coloré
        doc.setFillColor(41, 128, 185); // Bleu professionnel
        doc.rect(0, 0, 210, 50, 'F');
        
        // Nom de l'école en blanc (sans logo)
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(18);
        doc.setFont(undefined, 'bold');
        const schoolName = schoolSettings?.school_name || 'École';
        doc.text(schoolName, 20, 20);
        
        // Sous-titre
        doc.setFontSize(12);
        doc.setFont(undefined, 'normal');
        doc.text('République du Cameroun', 20, 30);
        doc.text('Ministère des Enseignements Secondaires', 20, 38);
        
        // Retour au noir pour le contenu
        doc.setTextColor(0, 0, 0);
        
        return 60; // Position Y après l'en-tête
    };

    const addPDFFooter = (doc, pageNum, totalPages) => {
        const pageHeight = doc.internal.pageSize.height;
        
        // Ligne de séparation du pied de page
        doc.setDrawColor(41, 128, 185);
        doc.setLineWidth(1);
        doc.line(20, pageHeight - 20, 190, pageHeight - 20);
        
        // Informations du pied de page
        doc.setFontSize(8);
        doc.setTextColor(100, 100, 100);
        doc.text(`Page ${pageNum} sur ${totalPages}`, 20, pageHeight - 10);
        
        const date = new Date().toLocaleDateString('fr-FR', { 
            day: '2-digit', 
            month: 'long', 
            year: 'numeric' 
        });
        doc.text(`Généré le ${date}`, 190, pageHeight - 10, { align: 'right' });
    };

    const handleExport = async () => {
        setIsLoading(true);
        try {
            let students = [];
            
            let currentSchoolYear = null;
            
            if (seriesId) {
                // Récupérer les élèves de cette série spécifique
                const response = await secureApiEndpoints.students.getByClassSeries(seriesId);
                if (response.success && response.data) {
                    // L'API retourne response.data.students, pas response.data directement
                    students = response.data.students || [];
                    currentSchoolYear = response.data.school_year; // Récupérer l'année scolaire
                }
            } else {
                // Récupérer tous les élèves
                const response = await secureApiEndpoints.students.getAll();
                if (response.success && response.data) {
                    students = Array.isArray(response.data) ? response.data : [response.data];
                }
            }

            if (!Array.isArray(students) || students.length === 0) {
                showAlert('warning', 'Aucun élève trouvé');
                return;
            }
            
            console.log('Students data structure:', students.slice(0, 2));
            
            // Trier par ordre alphabétique (prénom + nom)
            const sortedStudents = students.sort((a, b) => {
                const nameA = getStudentName(a).toLowerCase();
                const nameB = getStudentName(b).toLowerCase();
                return nameA.localeCompare(nameB);
            });

            const fileName = seriesName ? 
                `liste_eleves_${seriesName}_${new Date().toISOString().split('T')[0]}` :
                `liste_eleves_${new Date().toISOString().split('T')[0]}`;

            if (exportFormat === 'csv') {
                // Export CSV
                let csvContent = "Numero,Nom Prenom,Sexe\n";
                sortedStudents.forEach((student, index) => {
                    const numero = index + 1;
                    const nomPrenom = getStudentName(student);
                    const sexe = student.gender === 'M' ? 'Masculin' : student.gender === 'F' ? 'Féminin' : student.gender || 'Non spécifié';
                    console.log(`Student ${index + 1}:`, {
                        full_name: student.full_name,
                        last_name: student.last_name,
                        first_name: student.first_name,
                        subname: student.subname,
                        name: student.name,
                        finalName: nomPrenom,
                        gender: student.gender
                    });
                    csvContent += `${numero},"${nomPrenom}","${sexe}"\n`;
                });

                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `${fileName}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

            } else if (exportFormat === 'excel') {
                // Import dynamic pour Excel
                const XLSX = await import('xlsx');
                
                // Préparer les données pour Excel
                const excelData = sortedStudents.map((student, index) => {
                    const nomPrenom = getStudentName(student);
                    console.log(`Excel Student ${index + 1}:`, {
                        full_name: student.full_name,
                        last_name: student.last_name,
                        first_name: student.first_name,
                        subname: student.subname,
                        name: student.name,
                        finalName: nomPrenom
                    });
                    return {
                        'Numero': index + 1,
                        'Nom Prenom': nomPrenom,
                        'Sexe': student.gender === 'M' ? 'Masculin' : student.gender === 'F' ? 'Féminin' : student.gender || 'Non spécifié'
                    };
                });

                const ws = XLSX.utils.json_to_sheet(excelData);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Liste Eleves');
                XLSX.writeFile(wb, `${fileName}.xlsx`);

            } else if (exportFormat === 'pdf') {
                try {
                    const jsPDF = (await import('jspdf')).jsPDF;
                    const doc = new jsPDF();
                    
                    // Calculer le nombre total de pages (première page = moins d'élèves, suivantes = plus)
                    const firstPageStudents = 35; // Première page avec en-tête complet
                    const otherPagesStudents = 50; // Pages suivantes sans en-tête école
                    
                    let totalPages = 1;
                    if (sortedStudents.length > firstPageStudents) {
                        const remainingStudents = sortedStudents.length - firstPageStudents;
                        totalPages += Math.ceil(remainingStudents / otherPagesStudents);
                    }
                    let currentPage = 1;
                    
                    // Ajouter l'en-tête de la première page
                    let yPosition = await addPDFHeader(doc);
                    
                    // Titre principal du document
                    doc.setFontSize(18);
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(41, 128, 185);
                    doc.text(`LISTE DES ÉLÈVES`, 105, yPosition + 15, { align: 'center' });
                    
                    if (seriesName) {
                        doc.setFontSize(14);
                        doc.setTextColor(0, 0, 0);
                        doc.text(`Classe: ${seriesName}`, 105, yPosition + 25, { align: 'center' });
                    }
                    
                    // Informations générales
                    doc.setFontSize(10);
                    doc.setTextColor(80, 80, 80);
                    doc.text(`Année scolaire: ${new Date().getFullYear()}/${new Date().getFullYear() + 1}`, 20, yPosition + 35);
                    doc.text(`Total élèves: ${sortedStudents.length}`, 150, yPosition + 35);
                    
                    yPosition += 50;
                    
                    // En-tête du tableau avec style
                    const drawTableHeader = (y) => {
                        // Fond coloré pour l'en-tête
                        doc.setFillColor(41, 128, 185);
                        doc.rect(15, y - 5, 175, 12, 'F');
                        
                        // Bordures de l'en-tête
                        doc.setDrawColor(255, 255, 255);
                        doc.setLineWidth(0.5);
                        doc.line(40, y - 5, 40, y + 7); // Séparation N° | Nom
                        doc.line(150, y - 5, 150, y + 7); // Séparation Nom | Sexe
                        
                        // Texte de l'en-tête en blanc
                        doc.setTextColor(255, 255, 255);
                        doc.setFontSize(11);
                        doc.setFont(undefined, 'bold');
                        doc.text('N°', 27, y + 2, { align: 'center' });
                        doc.text('NOM ET PRÉNOM', 95, y + 2, { align: 'center' });
                        doc.text('SEXE', 170, y + 2, { align: 'center' });
                        
                        return y + 15;
                    };
                    
                    yPosition = drawTableHeader(yPosition);
                    
                    // Variables pour l'alternance des couleurs de lignes
                    let rowColor = true;
                    
                    // Données des élèves
                    for (let index = 0; index < sortedStudents.length; index++) {
                        const student = sortedStudents[index];
                        
                        // Vérifier si nouvelle page nécessaire (logique différente selon la page)
                        const maxYPosition = currentPage === 1 ? 265 : 275; // Plus d'espace sur les pages suivantes
                        if (yPosition > maxYPosition) {
                            // Ajouter le pied de page
                            addPDFFooter(doc, currentPage, totalPages);
                            
                            // Nouvelle page
                            doc.addPage();
                            currentPage++;
                            
                            // Sur les pages suivantes, juste l'en-tête de tableau (pas l'en-tête école)
                            yPosition = 30; // Commencer plus haut
                            yPosition = drawTableHeader(yPosition);
                            rowColor = true; // Reset alternance
                        }
                        
                        const numero = (index + 1).toString();
                        const nomPrenom = getStudentName(student);
                        const sexe = student.gender === 'M' ? 'MASCULIN' : 
                                   student.gender === 'F' ? 'FÉMININ' : 
                                   student.gender || 'NON SPÉCIFIÉ';
                        
                        // Fond alterné pour les lignes (optimisé)
                        if (rowColor) {
                            doc.setFillColor(248, 249, 250);
                            doc.rect(15, yPosition - 2, 175, 8, 'F');
                        }
                        
                        // Bordures des cellules
                        doc.setDrawColor(220, 220, 220);
                        doc.setLineWidth(0.2);
                        doc.line(15, yPosition + 6, 190, yPosition + 6); // Ligne horizontale (ajustée)
                        doc.line(40, yPosition - 2, 40, yPosition + 6); // Séparation N° | Nom
                        doc.line(150, yPosition - 2, 150, yPosition + 6); // Séparation Nom | Sexe
                        
                        // Contenu des cellules
                        doc.setTextColor(0, 0, 0);
                        doc.setFontSize(10);
                        doc.setFont(undefined, 'normal');
                        
                        // Numéro centré
                        doc.text(numero, 27, yPosition + 2, { align: 'center' });
                        
                        // Nom (tronquer si trop long)
                        let displayName = nomPrenom;
                        if (nomPrenom.length > 45) {
                            displayName = nomPrenom.substring(0, 42) + '...';
                        }
                        doc.text(displayName, 45, yPosition + 2);
                        
                        // Sexe avec couleur
                        if (sexe === 'MASCULIN') {
                            doc.setTextColor(52, 152, 219);
                        } else if (sexe === 'FÉMININ') {
                            doc.setTextColor(231, 76, 60);
                        } else {
                            doc.setTextColor(149, 165, 166);
                        }
                        doc.text(sexe, 170, yPosition + 2, { align: 'center' });
                        
                        yPosition += 8; // Réduire l'espacement pour plus d'élèves par page
                        rowColor = !rowColor; // Alterner les couleurs
                    }
                    
                    // Ajouter une ligne de fermeture sous le dernier élève
                    doc.setDrawColor(41, 128, 185);
                    doc.setLineWidth(1);
                    doc.line(15, yPosition, 190, yPosition);
                    
                    // Ajouter le pied de page à la dernière page
                    addPDFFooter(doc, currentPage, totalPages);
                    
                    doc.save(`${fileName}.pdf`);
                } catch (pdfError) {
                    console.error('PDF Error:', pdfError);
                    // Fallback vers CSV si PDF échoue
                    showAlert('warning', 'Erreur PDF, export en CSV à la place');
                    
                    let csvContent = "Numero,Nom Prenom,Sexe\n";
                    sortedStudents.forEach((student, index) => {
                        const numero = index + 1;
                        const nomPrenom = getStudentName(student);
                        const sexe = student.gender === 'M' ? 'Masculin' : student.gender === 'F' ? 'Féminin' : student.gender || 'Non spécifié';
                        console.log(`Fallback CSV Student ${index + 1}:`, {
                            full_name: student.full_name,
                            last_name: student.last_name,
                            first_name: student.first_name,
                            subname: student.subname,
                            name: student.name,
                            finalName: nomPrenom
                        });
                        csvContent += `${numero},"${nomPrenom}","${sexe}"\n`;
                    });

                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', `${fileName}.csv`);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                }
            }

            showAlert('success', `Export ${exportFormat.toUpperCase()} réussi - ${sortedStudents.length} élève(s)`);
        } catch (error) {
            console.error('Export error:', error);
            showAlert('danger', `Erreur lors de l'export: ${error.message}`);
        } finally {
            setIsLoading(false);
        }
    };

    const formatIcons = {
        excel: <FileSpreadsheet className="me-2" />,
        csv: <FileText className="me-2" />,
        pdf: <FilePdf className="me-2" />
    };

    return (
        <Modal show={show} onHide={onHide} size="lg" centered>
            <Modal.Header closeButton>
                <Modal.Title>
                    Exporter la liste des élèves{seriesName ? ` - ${seriesName}` : ''}
                </Modal.Title>
            </Modal.Header>
            
            <Modal.Body>
                {alert && (
                    <Alert variant={alert.type} className="mb-3">
                        {alert.message}
                    </Alert>
                )}

                <div>
                    <Form.Group className="mb-3">
                        <Form.Label>Format d'export</Form.Label>
                        <div className="d-flex gap-2">
                            {['csv', 'excel', 'pdf'].map(format => (
                                <Form.Check
                                    key={format}
                                    type="radio"
                                    id={`format-${format}`}
                                    name="exportFormat"
                                    label={
                                        <span>
                                            {formatIcons[format]}
                                            {format.toUpperCase()}
                                        </span>
                                    }
                                    checked={exportFormat === format}
                                    onChange={() => setExportFormat(format)}
                                    disabled={isLoading}
                                />
                            ))}
                        </div>
                    </Form.Group>


                    <Alert variant="info" className="mb-3">
                        <strong>Contenu de l'export :</strong>
                        <br />• Numéro (1, 2, 3, ...)
                        <br />• Nom et Prénom
                        <br />• Sexe (Masculin/Féminin)
                        <br />• Tri par ordre alphabétique
                    </Alert>

                    <div className="d-grid">
                        <Button 
                            variant="success" 
                            size="lg"
                            onClick={handleExport}
                            disabled={isLoading}
                        >
                            {isLoading ? (
                                <>
                                    <span className="spinner-border spinner-border-sm me-2" />
                                    Export en cours...
                                </>
                            ) : (
                                <>
                                    <Download className="me-2" />
                                    Exporter en {exportFormat.toUpperCase()}
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </Modal.Body>

            <Modal.Footer>
                <Button variant="secondary" onClick={onHide} disabled={isLoading}>
                    Fermer
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default StudentsExportModal;