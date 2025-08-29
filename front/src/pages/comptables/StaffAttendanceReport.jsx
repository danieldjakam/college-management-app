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
    FileEarmarkExcel,
    PersonCheckFill
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
    const [attendanceStatus, setAttendanceStatus] = useState('all'); // all, excellent, good, average, poor
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

    const statusFilters = [
        { value: 'all', label: 'Tous les statuts', color: 'secondary' },
        { value: 'excellent', label: 'Excellent (≥95%)', color: 'success' },
        { value: 'good', label: 'Bon (85-94%)', color: 'info' },
        { value: 'average', label: 'Moyen (70-84%)', color: 'warning' },
        { value: 'poor', label: 'Faible (<70%)', color: 'danger' }
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
                setSuccess(`${data.data.staff_attendance?.length || 0} membre(s) du personnel analysé(s)`);
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
            setLoading(true);
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
                setSuccess('Rapport PDF exporté avec succès');
            } else {
                setError('Erreur lors de l\'export PDF');
            }
        } catch (error) {
            setError('Erreur lors de l\'export PDF: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const exportToExcel = async () => {
        try {
            setLoading(true);
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
                setSuccess('Rapport Excel exporté avec succès');
            } else {
                setError('Erreur lors de l\'export Excel');
            }
        } catch (error) {
            setError('Erreur lors de l\'export Excel: ' + error.message);
        } finally {
            setLoading(false);
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
            return <Badge bg="success" className="d-flex align-items-center gap-1">
                <CheckCircle size={12} />Excellent
            </Badge>;
        } else if (attendanceRate >= 85) {
            return <Badge bg="info" className="d-flex align-items-center gap-1">
                <InfoCircle size={12} />Bon
            </Badge>;
        } else if (attendanceRate >= 70) {
            return <Badge bg="warning" className="d-flex align-items-center gap-1">
                <Clock size={12} />Moyen
            </Badge>;
        } else {
            return <Badge bg="danger" className="d-flex align-items-center gap-1">
                <XCircle size={12} />Faible
            </Badge>;
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

    const getRoleLabel = (role) => {
        const roleObj = roles.find(r => r.value === role);
        return roleObj ? roleObj.label : role;
    };

    return (
        <Container fluid className="py-4">
            {/* Header */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 className="d-flex align-items-center gap-2 mb-1">
                                <PersonCheckFill className="text-primary" />
                                Rapport de Présence du Personnel
                            </h2>
                            <p className="text-muted mb-0">
                                Statistiques et analyse mensuelle de la présence du personnel
                            </p>
                        </div>
                        <div className="d-flex gap-2">
                            <Button
                                variant="outline-success"
                                onClick={exportToExcel}
                                disabled={loading || attendanceData.length === 0}
                                size="sm"
                            >
                                <FileEarmarkExcel className="me-1" />
                                Excel
                            </Button>
                            <Button
                                variant="danger"
                                onClick={exportToPdf}
                                disabled={loading || attendanceData.length === 0}
                                size="sm"
                            >
                                <FiletypePdf className="me-1" />
                                PDF
                            </Button>
                        </div>
                    </div>
                </Col>
            </Row>

            {/* Filtres */}
            <Card className="mb-4 shadow-sm">
                <Card.Header className="bg-light">
                    <h5 className="mb-0 d-flex align-items-center gap-2">
                        <Filter className="text-primary" /> Filtres et Période
                    </h5>
                </Card.Header>
                <Card.Body>
                    <Row>
                        {/* Sélection du mois */}
                        <Col lg={3} md={4} className="mb-3">
                            <Form.Group>
                                <Form.Label className="fw-semibold">
                                    <Calendar className="me-1" />Mois
                                </Form.Label>
                                <div className="d-flex align-items-center gap-1">
                                    <Button 
                                        variant="outline-primary" 
                                        size="sm"
                                        onClick={goToPreviousMonth}
                                        disabled={loading}
                                    >
                                        <ChevronLeft />
                                    </Button>
                                    
                                    <Form.Control
                                        type="month"
                                        value={selectedMonth}
                                        onChange={(e) => setSelectedMonth(e.target.value)}
                                        size="sm"
                                        disabled={loading}
                                        className="mx-1"
                                        style={{ minWidth: '140px' }}
                                    />
                                    
                                    <Button 
                                        variant="outline-primary" 
                                        size="sm"
                                        onClick={goToNextMonth}
                                        disabled={loading}
                                    >
                                        <ChevronRight />
                                    </Button>
                                </div>
                                <Button 
                                    variant="outline-secondary" 
                                    size="sm"
                                    onClick={goToCurrentMonth}
                                    disabled={loading}
                                    className="mt-1 w-100"
                                >
                                    Mois actuel
                                </Button>
                            </Form.Group>
                        </Col>

                        {/* Filtre par rôle */}
                        <Col lg={2} md={4} className="mb-3">
                            <Form.Group>
                                <Form.Label className="fw-semibold">Rôle</Form.Label>
                                <Form.Select
                                    value={selectedRole}
                                    onChange={(e) => setSelectedRole(e.target.value)}
                                    size="sm"
                                    disabled={loading}
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
                        <Col lg={3} md={4} className="mb-3">
                            <Form.Group>
                                <Form.Label className="fw-semibold">Statut de Présence</Form.Label>
                                <Form.Select
                                    value={attendanceStatus}
                                    onChange={(e) => setAttendanceStatus(e.target.value)}
                                    size="sm"
                                    disabled={loading}
                                >
                                    {statusFilters.map(status => (
                                        <option key={status.value} value={status.value}>
                                            {status.label}
                                        </option>
                                    ))}
                                </Form.Select>
                            </Form.Group>
                        </Col>

                        <Col lg={4} className="mb-3 d-flex align-items-end">
                            <Button 
                                variant="primary"
                                onClick={loadStaffAttendanceReport}
                                disabled={loading}
                                className="d-flex align-items-center gap-2 w-100"
                            >
                                {loading ? (
                                    <>
                                        <Spinner size="sm" /> Chargement...
                                    </>
                                ) : (
                                    <>
                                        <Search /> Actualiser le rapport
                                    </>
                                )}
                            </Button>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            {/* Alerts */}
            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')} className="mb-4">
                    <strong>Erreur:</strong> {error}
                </Alert>
            )}
            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess('')} className="mb-4">
                    <strong>Succès:</strong> {success}
                </Alert>
            )}

            {/* Statistiques mensuelles */}
            {monthlyStats.total_staff > 0 && (
                <Card className="mb-4 shadow-sm">
                    <Card.Header className="bg-primary text-white">
                        <h5 className="mb-0 d-flex align-items-center gap-2">
                            <BarChartFill /> Statistiques Globales - {formatMonthYear(selectedMonth)}
                        </h5>
                    </Card.Header>
                    <Card.Body>
                        <Row className="text-center">
                            <Col lg={2} md={4} sm={6} className="mb-3">
                                <div className="border-end">
                                    <h3 className="text-primary mb-1">{monthlyStats.total_staff}</h3>
                                    <p className="text-muted mb-0 small">Personnel Total</p>
                                </div>
                            </Col>
                            <Col lg={2} md={4} sm={6} className="mb-3">
                                <div className="border-end">
                                    <h3 className="text-success mb-1">{monthlyStats.avg_attendance_rate}%</h3>
                                    <p className="text-muted mb-0 small">Taux Moyen</p>
                                </div>
                            </Col>
                            <Col lg={2} md={4} sm={6} className="mb-3">
                                <div className="border-end">
                                    <h3 className="text-info mb-1">{monthlyStats.total_working_days}</h3>
                                    <p className="text-muted mb-0 small">Jours Ouvrables</p>
                                </div>
                            </Col>
                            <Col lg={2} md={4} sm={6} className="mb-3">
                                <div className="border-end">
                                    <h3 className="text-warning mb-1">{formatTime(monthlyStats.avg_working_hours)}</h3>
                                    <p className="text-muted mb-0 small">Temps Moyen/Jour</p>
                                </div>
                            </Col>
                            <Col lg={2} md={4} sm={6} className="mb-3">
                                <div className="border-end">
                                    <h3 className="text-success mb-1">{monthlyStats.excellent_attendance}</h3>
                                    <p className="text-muted mb-0 small">Excellent (≥95%)</p>
                                </div>
                            </Col>
                            <Col lg={2} md={4} sm={6} className="mb-3">
                                <h3 className="text-danger mb-1">{monthlyStats.poor_attendance}</h3>
                                <p className="text-muted mb-0 small">Faible (&lt;70%)</p>
                            </Col>
                        </Row>
                    </Card.Body>
                </Card>
            )}

            {/* Tableau détaillé */}
            <Card className="shadow-sm">
                <Card.Header className="bg-light">
                    <div className="d-flex justify-content-between align-items-center">
                        <h5 className="mb-0">Détail par Personnel</h5>
                        {attendanceData.length > 0 && (
                            <Badge bg="secondary">{attendanceData.length} membre(s)</Badge>
                        )}
                    </div>
                </Card.Header>
                <Card.Body className="p-0">
                    {loading ? (
                        <div className="text-center py-5">
                            <Spinner animation="border" variant="primary" />
                            <p className="mt-3 text-muted">Chargement des données de présence...</p>
                        </div>
                    ) : attendanceData.length === 0 ? (
                        <div className="text-center py-5">
                            <People size={48} className="text-muted mb-3" />
                            <h5 className="text-muted">Aucune donnée disponible</h5>
                            <p className="text-muted">
                                Aucune donnée de présence trouvée pour les critères sélectionnés.
                            </p>
                        </div>
                    ) : (
                        <div className="table-responsive">
                            <Table striped hover className="mb-0">
                                <thead className="table-dark">
                                    <tr>
                                        <th className="text-center">#</th>
                                        <th>Personnel</th>
                                        <th className="text-center">Rôle</th>
                                        <th className="text-center">Jours Travaillés</th>
                                        <th className="text-center">Jours Présents</th>
                                        <th className="text-center">Taux de Présence</th>
                                        <th className="text-center">Temps Total</th>
                                        <th className="text-center">Temps Moyen/Jour</th>
                                        <th className="text-center">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {attendanceData.map((staff, index) => {
                                        const attendanceRate = staff.stats.total_days > 0 ? 
                                            (staff.stats.present_days / staff.stats.total_days) * 100 : 0;
                                        
                                        return (
                                            <tr key={index}>
                                                <td className="text-center fw-bold">{index + 1}</td>
                                                <td>
                                                    <div>
                                                        <strong className="d-block">{staff.name}</strong>
                                                        <small className="text-muted">{staff.email}</small>
                                                    </div>
                                                </td>
                                                <td className="text-center">
                                                    <Badge bg="secondary" className="small">
                                                        {getRoleLabel(staff.role)}
                                                    </Badge>
                                                </td>
                                                <td className="text-center">
                                                    <strong>{staff.stats.total_days}</strong>
                                                </td>
                                                <td className="text-center">
                                                    <strong className="text-success">{staff.stats.present_days}</strong>
                                                </td>
                                                <td>
                                                    <div className="text-center">
                                                        <div className="fw-bold mb-1">
                                                            {attendanceRate.toFixed(1)}%
                                                        </div>
                                                        <ProgressBar 
                                                            now={attendanceRate} 
                                                            variant={
                                                                attendanceRate >= 95 ? 'success' :
                                                                attendanceRate >= 85 ? 'info' :
                                                                attendanceRate >= 70 ? 'warning' : 'danger'
                                                            }
                                                            size="sm"
                                                            style={{ height: '6px' }}
                                                        />
                                                    </div>
                                                </td>
                                                <td className="text-center">
                                                    <strong>{formatTime(staff.stats.total_working_minutes)}</strong>
                                                </td>
                                                <td className="text-center">
                                                    <strong>{formatTime(staff.stats.avg_working_minutes_per_day)}</strong>
                                                </td>
                                                <td className="text-center">
                                                    {getAttendanceStatusBadge(staff.stats)}
                                                </td>
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