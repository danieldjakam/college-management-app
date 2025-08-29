import React, { useState, useEffect } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Form,
    Table,
    Alert,
    Spinner,
    Badge,
    ProgressBar
} from 'react-bootstrap';
import {
    Calendar,
    People,
    BarChartFill,
    Download,
    Search,
    Filter,
    ChevronLeft,
    ChevronRight,
    Clock,
    CheckCircle,
    XCircle,
    InfoCircle,
    FiletypePdf,
    FileEarmarkExcel
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { extractErrorMessage } from '../../utils/errorHandler';
import { authService } from '../../services/authService';
import { host } from '../../utils/fetch';

const StaffAttendanceReport = () => {
    const [attendanceData, setAttendanceData] = useState([]);
    const [monthlyStats, setMonthlyStats] = useState({});
    const [selectedMonth, setSelectedMonth] = useState(() => {
        const now = new Date();
        return `${now.getFullYear()}-${(now.getMonth() + 1).toString().padStart(2, '0')}`;
    });
    const [selectedRole, setSelectedRole] = useState('');
    const [selectedDepartment, setSelectedDepartment] = useState('');
    const [attendanceStatus, setAttendanceStatus] = useState('all'); // all, present, absent, partial
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    const roles = [
        { value: 'teacher', label: 'Enseignant' },
        { value: 'accountant', label: 'Comptable' },
        { value: 'admin', label: 'Administrateur' },
        { value: 'secretaire', label: 'Secrétaire' },
        { value: 'surveillant_general', label: 'Surveillant Général' },
        { value: 'comptable_superieur', label: 'Comptable Supérieur' }
    ];

    useEffect(() => {
        if (selectedMonth) {
            loadStaffAttendanceReport();
        }
    }, [selectedMonth, selectedRole, selectedDepartment, attendanceStatus]);

    const loadStaffAttendanceReport = async () => {
        try {
            setLoading(true);
            setError('');

            const params = new URLSearchParams({
                month: selectedMonth
            });

            if (selectedRole) params.append('role', selectedRole);
            if (selectedDepartment) params.append('department', selectedDepartment);
            if (attendanceStatus !== 'all') params.append('status', attendanceStatus);

            const response = await fetch(`${host}/api/reports/staff-attendance-monthly?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`Erreur ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                setAttendanceData(data.data.staff_attendance || []);
                setMonthlyStats(data.data.monthly_stats || {});
                setSuccess(`${data.data.staff_attendance?.length || 0} personnel analysé(s)`);
            } else {
                setError(data.message || 'Erreur lors du chargement des données');
            }
        } catch (error) {
            console.error('Erreur lors du chargement du rapport:', error);
            setError(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    const exportToPdf = async () => {
        try {
            const params = new URLSearchParams({
                month: selectedMonth
            });

            if (selectedRole) params.append('role', selectedRole);
            if (selectedDepartment) params.append('department', selectedDepartment);
            if (attendanceStatus !== 'all') params.append('status', attendanceStatus);

            const response = await fetch(`${host}/api/reports/staff-attendance-monthly/export-pdf?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/pdf',
                },
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `rapport_presence_personnel_${selectedMonth}.pdf`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } else {
                setError('Erreur lors de l\'export PDF');
            }
        } catch (error) {
            setError('Erreur lors de l\'export PDF: ' + error.message);
        }
    };

    const exportToExcel = async () => {
        try {
            const params = new URLSearchParams({
                month: selectedMonth
            });

            if (selectedRole) params.append('role', selectedRole);
            if (selectedDepartment) params.append('department', selectedDepartment);
            if (attendanceStatus !== 'all') params.append('status', attendanceStatus);

            const response = await fetch(`${host}/api/reports/staff-attendance-monthly/export-excel?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authService.getToken()}`,
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                },
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `rapport_presence_personnel_${selectedMonth}.xlsx`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } else {
                setError('Erreur lors de l\'export Excel');
            }
        } catch (error) {
            setError('Erreur lors de l\'export Excel: ' + error.message);
        }
    };

    const goToPreviousMonth = () => {
        const currentDate = new Date(selectedMonth + '-01');
        currentDate.setMonth(currentDate.getMonth() - 1);
        setSelectedMonth(`${currentDate.getFullYear()}-${(currentDate.getMonth() + 1).toString().padStart(2, '0')}`);
    };

    const goToNextMonth = () => {
        const currentDate = new Date(selectedMonth + '-01');
        currentDate.setMonth(currentDate.getMonth() + 1);
        setSelectedMonth(`${currentDate.getFullYear()}-${(currentDate.getMonth() + 1).toString().padStart(2, '0')}`);
    };

    const goToCurrentMonth = () => {
        const now = new Date();
        setSelectedMonth(`${now.getFullYear()}-${(now.getMonth() + 1).toString().padStart(2, '0')}`);
    };

    const getAttendanceStatusBadge = (stats) => {
        const attendanceRate = stats.total_days > 0 ? (stats.present_days / stats.total_days) * 100 : 0;
        
        if (attendanceRate >= 95) {
            return <Badge bg="success"><CheckCircle className="me-1" size={12} />Excellent</Badge>;
        } else if (attendanceRate >= 85) {
            return <Badge bg="info"><InfoCircle className="me-1" size={12} />Bon</Badge>;
        } else if (attendanceRate >= 70) {
            return <Badge bg="warning"><Clock className="me-1" size={12} />Moyen</Badge>;
        } else {
            return <Badge bg="danger"><XCircle className="me-1" size={12} />Faible</Badge>;
        }
    };

    const formatTime = (minutes) => {
        if (!minutes) return '0h 0min';
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours}h ${mins}min`;
    };

    const formatMonthYear = (monthStr) => {
        const date = new Date(monthStr + '-01');
        return date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 className="d-flex align-items-center gap-2">
                                <People className="text-primary" />
                                Rapport de Présence du Personnel
                            </h2>
                            <p className="text-muted">
                                Statistiques et suivi mensuel de la présence du personnel
                            </p>
                        </div>
                        <div className="d-flex gap-2">
                            <Button
                                variant="outline-success"
                                onClick={exportToExcel}
                                disabled={loading || attendanceData.length === 0}
                            >
                                <FileEarmarkExcel className="me-2" />
                                Excel
                            </Button>
                            <Button
                                variant="danger"
                                onClick={exportToPdf}
                                disabled={loading || attendanceData.length === 0}
                            >
                                <FiletypePdf className="me-2" />
                                PDF
                            </Button>
                        </div>
                    </div>
                </Col>
            </Row>

            {/* Filtres */}
            <Card className="mb-4">
                <Card.Header>
                    <h5 className="mb-0 d-flex align-items-center gap-2">
                        <Filter /> Filtres et Période
                    </h5>
                </Card.Header>
                <Card.Body>
                    <Row>
                        {/* Sélection du mois */}
                        <Col md={3} className="mb-3">
                            <Form.Group>
                                <Form.Label>Mois</Form.Label>
                                <div className="d-flex align-items-center gap-2">
                                    <Button 
                                        variant="outline-primary" 
                                        size="sm"
                                        onClick={goToPreviousMonth}
                                    >
                                        <ChevronLeft />
                                    </Button>
                                    
                                    <Form.Control
                                        type="month"
                                        value={selectedMonth}
                                        onChange={(e) => setSelectedMonth(e.target.value)}
                                        size="sm"
                                        style={{ width: '140px' }}
                                    />
                                    
                                    <Button 
                                        variant="outline-primary" 
                                        size="sm"
                                        onClick={goToNextMonth}
                                    >
                                        <ChevronRight />
                                    </Button>
                                    
                                    <Button 
                                        variant="outline-secondary" 
                                        size="sm"
                                        onClick={goToCurrentMonth}
                                    >
                                        Aujourd'hui
                                    </Button>
                                </div>
                            </Form.Group>
                        </Col>

                        {/* Filtre par rôle */}
                        <Col md={2} className="mb-3">
                            <Form.Group>
                                <Form.Label>Rôle</Form.Label>
                                <Form.Select
                                    value={selectedRole}
                                    onChange={(e) => setSelectedRole(e.target.value)}
                                    size="sm"
                                >
                                    <option value="">Tous les rôles</option>
                                    {roles.map(role => (
                                        <option key={role.value} value={role.value}>
                                            {role.label}
                                        </option>
                                    ))}
                                </Form.Select>
                            </Form.Group>
                        </Col>

                        {/* Filtre par statut de présence */}
                        <Col md={2} className="mb-3">
                            <Form.Group>
                                <Form.Label>Statut de Présence</Form.Label>
                                <Form.Select
                                    value={attendanceStatus}
                                    onChange={(e) => setAttendanceStatus(e.target.value)}
                                    size="sm"
                                >
                                    <option value="all">Tous</option>
                                    <option value="excellent">Excellent (≥95%)</option>
                                    <option value="good">Bon (≥85%)</option>
                                    <option value="average">Moyen (≥70%)</option>
                                    <option value="poor">Faible (&lt;70%)</option>
                                </Form.Select>
                            </Form.Group>
                        </Col>

                        <Col md={3} className="mb-3 d-flex align-items-end">
                            <Button 
                                variant="primary"
                                onClick={loadStaffAttendanceReport}
                                disabled={loading}
                                className="d-flex align-items-center gap-2"
                            >
                                {loading ? <Spinner size="sm" /> : <Search />}
                                Actualiser
                            </Button>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            {/* Alerts */}
            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')}>
                    {error}
                </Alert>
            )}
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess('')}>
                    {success}
                </Alert>
            )}

            {/* Statistiques mensuelles */}
            {monthlyStats.total_staff > 0 && (
                <Card className="mb-4">
                    <Card.Header>
                        <h5 className="mb-0 d-flex align-items-center gap-2">
                            <BarChartFill /> Statistiques - {formatMonthYear(selectedMonth)}
                        </h5>
                    </Card.Header>
                    <Card.Body>
                        <Row>
                            <Col md={2} className="text-center">
                                <h3 className="text-primary mb-1">{monthlyStats.total_staff}</h3>
                                <p className="text-muted mb-0">Personnel Total</p>
                            </Col>
                            <Col md={2} className="text-center">
                                <h3 className="text-success mb-1">{monthlyStats.avg_attendance_rate}%</h3>
                                <p className="text-muted mb-0">Taux Moyen</p>
                            </Col>
                            <Col md={2} className="text-center">
                                <h3 className="text-info mb-1">{monthlyStats.total_working_days}</h3>
                                <p className="text-muted mb-0">Jours Ouvrables</p>
                            </Col>
                            <Col md={2} className="text-center">
                                <h3 className="text-warning mb-1">{formatTime(monthlyStats.avg_working_hours)}</h3>
                                <p className="text-muted mb-0">Temps Moyen/Jour</p>
                            </Col>
                            <Col md={2} className="text-center">
                                <h3 className="text-success mb-1">{monthlyStats.excellent_attendance}</h3>
                                <p className="text-muted mb-0">Excellent (≥95%)</p>
                            </Col>
                            <Col md={2} className="text-center">
                                <h3 className="text-danger mb-1">{monthlyStats.poor_attendance}</h3>
                                <p className="text-muted mb-0">Faible (&lt;70%)</p>
                            </Col>
                        </Row>
                    </Card.Body>
                </Card>
            )}

            {/* Tableau détaillé */}
            <Card>
                <Card.Header>
                    <h5 className="mb-0">Détail par Personnel</h5>
                </Card.Header>
                <Card.Body>
                    {loading ? (
                        <div className="text-center py-4">
                            <Spinner animation="border" />
                            <p className="mt-2">Chargement des données...</p>
                        </div>
                    ) : attendanceData.length === 0 ? (
                        <div className="text-center py-4">
                            <People size={48} className="text-muted mb-3" />
                            <h5 className="text-muted">Aucune donnée disponible</h5>
                            <p className="text-muted">
                                Aucune donnée de présence trouvée pour les critères sélectionnés.
                            </p>
                        </div>
                    ) : (
                        <div className="table-responsive">
                            <Table striped hover>
                                <thead className="table-dark">
                                    <tr>
                                        <th>Personnel</th>
                                        <th>Rôle</th>
                                        <th>Jours Travaillés</th>
                                        <th>Jours Présents</th>
                                        <th>Taux de Présence</th>
                                        <th>Temps Total</th>
                                        <th>Temps Moyen/Jour</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {attendanceData.map((staff, index) => {
                                        const attendanceRate = staff.stats.total_days > 0 ? 
                                            (staff.stats.present_days / staff.stats.total_days) * 100 : 0;
                                        
                                        return (
                                            <tr key={index}>
                                                <td>
                                                    <strong>{staff.name}</strong>
                                                    <br />
                                                    <small className="text-muted">{staff.email}</small>
                                                </td>
                                                <td>
                                                    <Badge bg="secondary">
                                                        {roles.find(r => r.value === staff.role)?.label || staff.role}
                                                    </Badge>
                                                </td>
                                                <td className="text-center">{staff.stats.total_days}</td>
                                                <td className="text-center">{staff.stats.present_days}</td>
                                                <td>
                                                    <div>
                                                        <div className="d-flex justify-content-between">
                                                            <span>{attendanceRate.toFixed(1)}%</span>
                                                        </div>
                                                        <ProgressBar 
                                                            now={attendanceRate} 
                                                            variant={
                                                                attendanceRate >= 95 ? 'success' :
                                                                attendanceRate >= 85 ? 'info' :
                                                                attendanceRate >= 70 ? 'warning' : 'danger'
                                                            }
                                                            size="sm"
                                                        />
                                                    </div>
                                                </td>
                                                <td className="text-center">
                                                    {formatTime(staff.stats.total_working_minutes)}
                                                </td>
                                                <td className="text-center">
                                                    {formatTime(staff.stats.avg_working_minutes_per_day)}
                                                </td>
                                                <td>{getAttendanceStatusBadge(staff.stats)}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </Table>
                        </div>
                    )}
                </Card.Body>
            </Card>
        </Container>
    );
};

export default StaffAttendanceReport;