/**
 * Gestion des présences du personnel pour les secrétaires
 * Affichage de la liste du personnel avec leurs présences sur une période donnée
 */

import React, { useState, useEffect } from 'react';
import { 
    Card, 
    Container, 
    Row, 
    Col, 
    Table, 
    Badge, 
    Button,
    Form,
    Spinner,
    Alert,
    ProgressBar,
    Modal,
    ButtonGroup,
    Dropdown,
    InputGroup
} from 'react-bootstrap';
import { 
    People, 
    Calendar, 
    Clock, 
    PersonCheck,
    PersonX,
    Download,
    Filter,
    Search,
    Eye,
    BarChart,
    PersonWorkspace,
    ShieldCheck,
    Gear,
    FileEarmarkText,
    CheckCircle,
    XCircle,
    ExclamationTriangle,
    InfoCircle,
    Printer
} from 'react-bootstrap-icons';
import { useAuth } from '../../hooks/useAuth';
import { useSchool } from '../../contexts/SchoolContext';
import { secureApiEndpoints } from '../../utils/apiMigration';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';

const StaffAttendanceManagement = () => {
    const [staffList, setStaffList] = useState([]);
    const [attendanceData, setAttendanceData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState('');
    const [messageType, setMessageType] = useState('info');
    const [selectedStaff, setSelectedStaff] = useState(null);
    const [showDetailModal, setShowDetailModal] = useState(false);
    const [staffDetails, setStaffDetails] = useState(null);

    // Filtres
    const [startDate, setStartDate] = useState(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
    const [endDate, setEndDate] = useState(new Date());
    const [selectedStaffType, setSelectedStaffType] = useState('');
    const [searchTerm, setSearchTerm] = useState('');

    const { user } = useAuth();
    const { schoolSettings, getLogoUrl } = useSchool();

    // Types de personnel avec leurs configurations
    const staffTypes = {
        teacher: { 
            label: 'Enseignants', 
            icon: PersonWorkspace, 
            color: 'primary',
            bgColor: '#0d6efd'
        },
        accountant: { 
            label: 'Comptables', 
            icon: PersonCheck, 
            color: 'success',
            bgColor: '#198754'
        },
        supervisor: { 
            label: 'Surveillants', 
            icon: ShieldCheck, 
            color: 'warning',
            bgColor: '#ffc107'
        },
        admin: { 
            label: 'Administrateurs', 
            icon: Gear, 
            color: 'danger',
            bgColor: '#dc3545'
        },
        secretaire: { 
            label: 'Secrétaires', 
            icon: FileEarmarkText, 
            color: 'info',
            bgColor: '#0dcaf0'
        }
    };

    useEffect(() => {
        loadStaffList();
    }, []);

    useEffect(() => {
        if (staffList.length > 0) {
            loadAttendanceData();
        }
    }, [startDate, endDate, selectedStaffType, staffList]);

    const loadStaffList = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.staff.getStaffWithQR();
            
            if (response.success) {
                setStaffList(response.data || []);
            } else {
                setMessage('Erreur lors du chargement du personnel');
                setMessageType('danger');
            }
        } catch (error) {
            console.error('Error loading staff:', error);
            setMessage('Erreur lors du chargement du personnel');
            setMessageType('danger');
        } finally {
            setLoading(false);
        }
    };

    const loadAttendanceData = async () => {
        try {
            setLoading(true);
            
            // Charger les données de présence pour chaque membre du personnel
            const attendancePromises = staffList.map(async (staff) => {
                try {
                    const response = await secureApiEndpoints.staff.getStaffReport(staff.id, {
                        start_date: startDate.toISOString().split('T')[0],
                        end_date: endDate.toISOString().split('T')[0]
                    });
                    
                    if (response.success) {
                        return {
                            ...staff,
                            stats: response.data.stats,
                            attendances: response.data.attendances || []
                        };
                    }
                    return {
                        ...staff,
                        stats: {
                            attendance_rate: 0,
                            present_days: 0,
                            absent_days: 0,
                            late_days: 0,
                            total_work_hours: 0
                        },
                        attendances: []
                    };
                } catch (error) {
                    console.error(`Error loading attendance for staff ${staff.id}:`, error);
                    return {
                        ...staff,
                        stats: {
                            attendance_rate: 0,
                            present_days: 0,
                            absent_days: 0,
                            late_days: 0,
                            total_work_hours: 0
                        },
                        attendances: []
                    };
                }
            });

            const results = await Promise.all(attendancePromises);
            setAttendanceData(results);

        } catch (error) {
            console.error('Error loading attendance data:', error);
            setMessage('Erreur lors du chargement des données de présence');
            setMessageType('danger');
        } finally {
            setLoading(false);
        }
    };

    const handleViewDetails = async (staff) => {
        setSelectedStaff(staff);
        setStaffDetails(staff);
        setShowDetailModal(true);
    };

    const getStaffTypeConfig = (staffType) => {
        return staffTypes[staffType] || { 
            label: staffType, 
            color: 'secondary', 
            bgColor: '#6c757d',
            icon: People 
        };
    };

    const getStaffTypeBadge = (staffType) => {
        const config = getStaffTypeConfig(staffType);
        const IconComponent = config.icon;
        return (
            <Badge bg={config.color} className="d-flex align-items-center gap-1">
                <IconComponent size={12} />
                {config.label}
            </Badge>
        );
    };

    const getAttendanceRateColor = (rate) => {
        if (rate >= 90) return 'success';
        if (rate >= 75) return 'warning';
        return 'danger';
    };

    const getAttendanceIcon = (rate) => {
        if (rate >= 90) return <CheckCircle className="text-success" />;
        if (rate >= 75) return <ExclamationTriangle className="text-warning" />;
        return <XCircle className="text-danger" />;
    };

    const filteredAttendanceData = attendanceData.filter(staff => {
        const matchesType = !selectedStaffType || staff.staff_type === selectedStaffType;
        const matchesSearch = !searchTerm || 
            staff.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            staff.email.toLowerCase().includes(searchTerm.toLowerCase());
        return matchesType && matchesSearch;
    });

    const formatDate = (date) => {
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric'
        });
    };

    const exportToCSV = () => {
        if (filteredAttendanceData.length === 0) return;

        const csvHeaders = [
            'Nom',
            'Email',
            'Type',
            'Taux de présence (%)',
            'Jours présents',
            'Jours absents',
            'Retards',
            'Heures travaillées'
        ];

        const csvData = filteredAttendanceData.map(staff => [
            staff.name,
            staff.email,
            getStaffTypeConfig(staff.staff_type).label,
            staff.stats.attendance_rate,
            staff.stats.present_days,
            staff.stats.absent_days,
            staff.stats.late_days,
            staff.stats.total_work_hours
        ]);

        const csvContent = [csvHeaders, ...csvData]
            .map(row => row.map(cell => `"${cell}"`).join(','))
            .join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `presences_personnel_${formatDate(startDate)}_${formatDate(endDate)}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    const handlePrint = () => {
        const printContent = generatePrintContent();
        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.print();
    };

    const generatePrintContent = () => {
        const currentDate = new Date().toLocaleDateString('fr-FR');
        const periodText = `Du ${formatDate(startDate)} au ${formatDate(endDate)}`;
        const schoolName = schoolSettings?.school_name || 'COLLÈGE POLYVALENT BILINGUE DE DOUALA';
        const logoUrl = getLogoUrl();
        
        let tableRows = '';
        filteredAttendanceData.forEach((staff, index) => {
            const config = getStaffTypeConfig(staff.staff_type);
            tableRows += `
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px; text-align: center;">${index + 1}</td>
                    <td style="padding: 8px;">
                        <strong>${staff.name}</strong><br>
                        <small style="color: #666;">${staff.email || 'N/A'}</small>
                    </td>
                    <td style="padding: 8px; text-align: center;">
                        <span style="background: ${config.bgColor}; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                            ${config.label}
                        </span>
                    </td>
                    <td style="padding: 8px; text-align: center;">
                        <strong style="color: ${staff.stats.attendance_rate >= 90 ? '#198754' : staff.stats.attendance_rate >= 75 ? '#ffc107' : '#dc3545'};">
                            ${staff.stats.attendance_rate.toFixed(1)}%
                        </strong>
                    </td>
                    <td style="padding: 8px; text-align: center; color: #198754;">
                        <strong>${staff.stats.present_days}</strong>
                    </td>
                    <td style="padding: 8px; text-align: center; color: ${staff.stats.absent_days > 0 ? '#dc3545' : '#198754'};">
                        <strong>${staff.stats.absent_days}</strong>
                    </td>
                    <td style="padding: 8px; text-align: center; color: ${staff.stats.late_days > 0 ? '#ffc107' : '#198754'};">
                        <strong>${staff.stats.late_days}</strong>
                    </td>
                    <td style="padding: 8px; text-align: center;">
                        <strong>${staff.stats.total_work_hours.toFixed(1)}h</strong>
                    </td>
                </tr>
            `;
        });

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Rapport Présences Personnel</title>
                <style>
                    @media print {
                        @page {
                            margin: 0.5in;
                            size: A4 landscape;
                        }
                        body {
                            font-family: 'Arial', 'Helvetica', sans-serif;
                            font-size: 12px;
                            line-height: 1.3;
                        }
                        .no-print {
                            display: none !important;
                        }
                    }
                    body {
                        font-family: 'Arial', 'Helvetica', sans-serif;
                        margin: 0;
                        padding: 20px;
                        font-size: 12px;
                        line-height: 1.3;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 15px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 15px;
                    }
                    .logo-section {
                        flex-shrink: 0;
                    }
                    .logo {
                        width: 60px;
                        height: 60px;
                        object-fit: contain;
                    }
                    .title-section {
                        flex: 1;
                    }
                    .header h1 {
                        margin: 0;
                        color: #333;
                        font-size: 20px;
                        font-weight: bold;
                    }
                    .header h2 {
                        margin: 5px 0;
                        color: #666;
                        font-size: 16px;
                        font-weight: normal;
                    }
                    .meta-info {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 20px;
                        font-size: 11px;
                        color: #666;
                    }
                    .stats-summary {
                        display: flex;
                        justify-content: space-around;
                        margin-bottom: 20px;
                        text-align: center;
                    }
                    .stats-card {
                        flex: 1;
                        margin: 0 5px;
                        padding: 10px;
                        border: 1px solid #ddd;
                        border-radius: 5px;
                    }
                    .stats-card h3 {
                        margin: 0;
                        font-size: 18px;
                        color: #333;
                    }
                    .stats-card p {
                        margin: 5px 0 0 0;
                        font-size: 10px;
                        color: #666;
                        text-transform: uppercase;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                        font-size: 11px;
                    }
                    th {
                        background-color: #f8f9fa;
                        border: 1px solid #ddd;
                        padding: 10px 8px;
                        text-align: center;
                        font-weight: bold;
                        color: #333;
                    }
                    td {
                        border: 1px solid #ddd;
                        vertical-align: middle;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    .footer {
                        margin-top: 30px;
                        padding-top: 15px;
                        border-top: 1px solid #ddd;
                        font-size: 10px;
                        color: #666;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    ${logoUrl ? `
                        <div class="logo-section">
                            <img src="${logoUrl}" alt="Logo École" class="logo" onerror="this.style.display='none'">
                        </div>
                    ` : ''}
                    <div class="title-section">
                        <h1>${schoolName.toUpperCase()}</h1>
                        <h2>Rapport des Présences du Personnel</h2>
                    </div>
                </div>

                <div class="meta-info">
                    <div><strong>Période:</strong> ${periodText}</div>
                    <div><strong>Généré le:</strong> ${currentDate}</div>
                    <div><strong>Total personnel:</strong> ${filteredAttendanceData.length}</div>
                </div>

                <div class="stats-summary">
                    <div class="stats-card">
                        <h3 style="color: #198754;">${filteredAttendanceData.filter(s => s.stats.attendance_rate >= 90).length}</h3>
                        <p>Excellente présence (≥90%)</p>
                    </div>
                    <div class="stats-card">
                        <h3 style="color: #ffc107;">${filteredAttendanceData.filter(s => s.stats.attendance_rate >= 75 && s.stats.attendance_rate < 90).length}</h3>
                        <p>Présence correcte (75-89%)</p>
                    </div>
                    <div class="stats-card">
                        <h3 style="color: #dc3545;">${filteredAttendanceData.filter(s => s.stats.attendance_rate < 75).length}</h3>
                        <p>Présence insuffisante (&lt;75%)</p>
                    </div>
                    <div class="stats-card">
                        <h3 style="color: #0dcaf0;">${filteredAttendanceData.reduce((sum, s) => sum + s.stats.total_work_hours, 0).toFixed(1)}h</h3>
                        <p>Total heures travaillées</p>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 25%;">Personnel</th>
                            <th style="width: 15%;">Type</th>
                            <th style="width: 12%;">Taux présence</th>
                            <th style="width: 10%;">Présents</th>
                            <th style="width: 10%;">Absences</th>
                            <th style="width: 10%;">Retards</th>
                            <th style="width: 13%;">Heures travaillées</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                </table>

                <div class="footer">
                    <p>Rapport généré automatiquement le ${currentDate} à ${new Date().toLocaleTimeString('fr-FR')}</p>
                    <p>Ce document contient des informations confidentielles - Usage interne uniquement</p>
                </div>
            </body>
            </html>
        `;
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <h2 className="d-flex align-items-center gap-2">
                        <People size={32} className="text-primary" />
                        Présences du Personnel
                    </h2>
                    <p className="text-muted">
                        Suivi des présences du personnel sur une période donnée
                    </p>
                </Col>
            </Row>

            {/* Message d'état */}
            {message && (
                <Alert variant={messageType} className="mb-4" onClose={() => setMessage('')} dismissible>
                    {messageType === 'danger' && <ExclamationTriangle className="me-2" />}
                    {messageType === 'success' && <CheckCircle className="me-2" />}
                    {messageType === 'info' && <InfoCircle className="me-2" />}
                    {message}
                </Alert>
            )}

            {/* Filtres */}
            <Card className="mb-4">
                <Card.Header>
                    <h5 className="mb-0">
                        <Filter className="me-2" />
                        Filtres et Contrôles
                    </h5>
                </Card.Header>
                <Card.Body>
                    <Row>
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>Date de début</Form.Label>
                                <DatePicker
                                    selected={startDate}
                                    onChange={setStartDate}
                                    dateFormat="dd/MM/yyyy"
                                    className="form-control"
                                    placeholderText="Sélectionner une date"
                                />
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>Date de fin</Form.Label>
                                <DatePicker
                                    selected={endDate}
                                    onChange={setEndDate}
                                    dateFormat="dd/MM/yyyy"
                                    className="form-control"
                                    placeholderText="Sélectionner une date"
                                />
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>Type de personnel</Form.Label>
                                <Form.Select
                                    value={selectedStaffType}
                                    onChange={(e) => setSelectedStaffType(e.target.value)}
                                >
                                    <option value="">Tous les types</option>
                                    {Object.entries(staffTypes).map(([key, config]) => (
                                        <option key={key} value={key}>{config.label}</option>
                                    ))}
                                </Form.Select>
                            </Form.Group>
                        </Col>
                        <Col md={3}>
                            <Form.Group>
                                <Form.Label>Rechercher</Form.Label>
                                <InputGroup>
                                    <InputGroup.Text>
                                        <Search size={14} />
                                    </InputGroup.Text>
                                    <Form.Control
                                        type="text"
                                        placeholder="Nom ou email..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                </InputGroup>
                            </Form.Group>
                        </Col>
                    </Row>
                    <Row className="mt-3">
                        <Col>
                            <ButtonGroup>
                                <Button 
                                    variant="outline-primary" 
                                    onClick={loadAttendanceData}
                                    disabled={loading}
                                >
                                    {loading ? <Spinner size="sm" className="me-2" /> : <BarChart className="me-2" />}
                                    Actualiser
                                </Button>
                                <Button 
                                    variant="outline-success" 
                                    onClick={exportToCSV}
                                    disabled={filteredAttendanceData.length === 0}
                                >
                                    <Download className="me-2" />
                                    Exporter CSV
                                </Button>
                                <Button 
                                    variant="outline-info" 
                                    onClick={handlePrint}
                                    disabled={filteredAttendanceData.length === 0}
                                >
                                    <Printer className="me-2" />
                                    Imprimer
                                </Button>
                            </ButtonGroup>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            {/* Statistiques globales */}
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="text-center border-primary">
                        <Card.Body>
                            <h4 className="text-primary">{filteredAttendanceData.length}</h4>
                            <small className="text-muted">Personnel total</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center border-success">
                        <Card.Body>
                            <h4 className="text-success">
                                {filteredAttendanceData.filter(s => s.stats.attendance_rate >= 90).length}
                            </h4>
                            <small className="text-muted">Présence excellente (≥90%)</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center border-warning">
                        <Card.Body>
                            <h4 className="text-warning">
                                {filteredAttendanceData.filter(s => s.stats.attendance_rate >= 75 && s.stats.attendance_rate < 90).length}
                            </h4>
                            <small className="text-muted">Présence correcte (75-89%)</small>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center border-danger">
                        <Card.Body>
                            <h4 className="text-danger">
                                {filteredAttendanceData.filter(s => s.stats.attendance_rate < 75).length}
                            </h4>
                            <small className="text-muted">Présence insuffisante (&lt;75%)</small>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Tableau des présences */}
            <Card>
                <Card.Header className="d-flex justify-content-between align-items-center">
                    <h5 className="mb-0">
                        <Calendar className="me-2" />
                        Présences du {formatDate(startDate)} au {formatDate(endDate)}
                    </h5>
                    <div className="d-flex align-items-center gap-2">
                        <Badge bg="info">
                            {filteredAttendanceData.length} personnel(s)
                        </Badge>
                        <Button 
                            variant="outline-primary" 
                            size="sm"
                            onClick={handlePrint}
                            disabled={filteredAttendanceData.length === 0}
                            title="Imprimer le rapport"
                        >
                            <Printer size={14} />
                        </Button>
                    </div>
                </Card.Header>
                <Card.Body>
                    {loading ? (
                        <div className="text-center py-4">
                            <Spinner animation="border" />
                            <p className="mt-2">Chargement des données...</p>
                        </div>
                    ) : filteredAttendanceData.length === 0 ? (
                        <div className="text-center py-4 text-muted">
                            <PersonX size={48} className="mb-3" />
                            <p>Aucun personnel trouvé avec ces critères</p>
                        </div>
                    ) : (
                        <Table responsive hover>
                            <thead>
                                <tr>
                                    <th>Personnel</th>
                                    <th>Type</th>
                                    <th>Taux de présence</th>
                                    <th>Jours présents</th>
                                    <th>Absences</th>
                                    <th>Retards</th>
                                    <th>Heures travaillées</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredAttendanceData.map((staff) => (
                                    <tr key={staff.id}>
                                        <td>
                                            <div className="d-flex align-items-center">
                                                {getAttendanceIcon(staff.stats.attendance_rate)}
                                                <div className="ms-2">
                                                    <div className="fw-bold">{staff.name}</div>
                                                    <small className="text-muted">{staff.email}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{getStaffTypeBadge(staff.staff_type)}</td>
                                        <td>
                                            <div className="d-flex align-items-center">
                                                <ProgressBar 
                                                    now={staff.stats.attendance_rate} 
                                                    variant={getAttendanceRateColor(staff.stats.attendance_rate)}
                                                    style={{ width: '60px', height: '8px' }}
                                                    className="me-2"
                                                />
                                                <Badge bg={getAttendanceRateColor(staff.stats.attendance_rate)}>
                                                    {staff.stats.attendance_rate.toFixed(1)}%
                                                </Badge>
                                            </div>
                                        </td>
                                        <td>
                                            <Badge bg="success" className="d-flex align-items-center gap-1">
                                                <PersonCheck size={12} />
                                                {staff.stats.present_days}
                                            </Badge>
                                        </td>
                                        <td>
                                            {staff.stats.absent_days > 0 ? (
                                                <Badge bg="danger" className="d-flex align-items-center gap-1">
                                                    <PersonX size={12} />
                                                    {staff.stats.absent_days}
                                                </Badge>
                                            ) : (
                                                <Badge bg="success">0</Badge>
                                            )}
                                        </td>
                                        <td>
                                            {staff.stats.late_days > 0 ? (
                                                <Badge bg="warning" className="d-flex align-items-center gap-1">
                                                    <Clock size={12} />
                                                    {staff.stats.late_days}
                                                </Badge>
                                            ) : (
                                                <Badge bg="success">0</Badge>
                                            )}
                                        </td>
                                        <td>
                                            <Badge bg="info" className="d-flex align-items-center gap-1">
                                                <Clock size={12} />
                                                {staff.stats.total_work_hours.toFixed(1)}h
                                            </Badge>
                                        </td>
                                        <td>
                                            <Button
                                                variant="outline-primary"
                                                size="sm"
                                                onClick={() => handleViewDetails(staff)}
                                            >
                                                <Eye size={12} className="me-1" />
                                                Détails
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    )}
                </Card.Body>
            </Card>

            {/* Modal des détails */}
            <Modal show={showDetailModal} onHide={() => setShowDetailModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        <People className="me-2" />
                        Détails des présences - {selectedStaff?.name}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {staffDetails && (
                        <div>
                            {/* Informations générales */}
                            <Row className="mb-4">
                                <Col md={6}>
                                    <h6>Informations générales</h6>
                                    <p><strong>Nom:</strong> {staffDetails.name}</p>
                                    <p><strong>Email:</strong> {staffDetails.email}</p>
                                    <p><strong>Type:</strong> {getStaffTypeBadge(staffDetails.staff_type)}</p>
                                </Col>
                                <Col md={6}>
                                    <h6>Statistiques de la période</h6>
                                    <p><strong>Taux de présence:</strong> {staffDetails.stats.attendance_rate.toFixed(1)}%</p>
                                    <p><strong>Jours présents:</strong> {staffDetails.stats.present_days}</p>
                                    <p><strong>Jours absents:</strong> {staffDetails.stats.absent_days}</p>
                                    <p><strong>Retards:</strong> {staffDetails.stats.late_days}</p>
                                    <p><strong>Heures travaillées:</strong> {staffDetails.stats.total_work_hours.toFixed(1)}h</p>
                                </Col>
                            </Row>

                            {/* Historique récent */}
                            <h6>Historique récent des présences</h6>
                            {staffDetails.attendances && staffDetails.attendances.length > 0 ? (
                                <Table responsive size="sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Heure</th>
                                            <th>Type</th>
                                            <th>Retard</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {staffDetails.attendances.slice(0, 10).map((attendance, index) => (
                                            <tr key={index}>
                                                <td>{new Date(attendance.attendance_date).toLocaleDateString('fr-FR')}</td>
                                                <td>{new Date(attendance.scanned_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</td>
                                                <td>
                                                    <Badge bg={attendance.event_type === 'entry' ? 'success' : 'danger'}>
                                                        {attendance.event_type === 'entry' ? 'Entrée' : 'Sortie'}
                                                    </Badge>
                                                </td>
                                                <td>
                                                    {attendance.late_minutes > 0 ? (
                                                        <Badge bg="warning">+{attendance.late_minutes}min</Badge>
                                                    ) : (
                                                        <Badge bg="success">À l'heure</Badge>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            ) : (
                                <Alert variant="info">
                                    Aucun enregistrement de présence sur cette période
                                </Alert>
                            )}
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowDetailModal(false)}>
                        Fermer
                    </Button>
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default StaffAttendanceManagement;