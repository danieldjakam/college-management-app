import React, { useState } from 'react';
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Form,
    Table,
    Badge,
    Alert,
    Spinner
} from 'react-bootstrap';
import {
    FiletypePdf,
    Calendar,
    People,
    Clock,
    BarChart,
    Download
} from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';
import Swal from 'sweetalert2';

const StaffAttendanceReport = () => {
    const [month, setMonth] = useState(new Date().toISOString().slice(0, 7)); // Format YYYY-MM
    const [loading, setLoading] = useState(false);
    const [reportData, setReportData] = useState(null);
    const [generating, setGenerating] = useState(false);
    const [viewMode, setViewMode] = useState('calendar'); // Toujours en mode calendrier

    const loadReport = async () => {
        try {
            setLoading(true);

            const [year, monthNum] = month.split('-');
            const url = `/reports/staff-attendance-calendar?month=${monthNum}&year=${year}`;

            const response = await secureApi.get(url);

            if (response.success || response.data) {
                setReportData(response.data || response);
            } else {
                Swal.fire('Erreur', response.message || 'Impossible de charger le rapport', 'error');
            }
        } catch (error) {
            console.error('Erreur chargement rapport:', error);
            Swal.fire('Erreur', 'Erreur lors du chargement du rapport', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleGeneratePdf = async () => {
        try {
            setGenerating(true);

            const token = localStorage.getItem('auth_token');

            if (!token) {
                Swal.fire('Erreur', 'Vous devez être connecté pour générer le rapport', 'error');
                return;
            }

            const [year, monthNum] = month.split('-');
            let url = `${secureApi.baseURL}/reports/staff-attendance-calendar/export-pdf?month=${monthNum}&year=${year}&token=${token}`;

            const link = document.createElement('a');
            link.href = url;
            link.download = `rapport_personnel_calendrier_${month}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire('Succès', 'Rapport PDF généré avec succès', 'success');
        } catch (error) {
            console.error('Erreur génération PDF:', error);
            Swal.fire('Erreur', 'Erreur lors de la génération du PDF', 'error');
        } finally {
            setGenerating(false);
        }
    };

    const handleGenerateExcel = async () => {
        try {
            setGenerating(true);

            const token = localStorage.getItem('auth_token');

            if (!token) {
                Swal.fire('Erreur', 'Vous devez être connecté pour générer le rapport', 'error');
                return;
            }

            const [year, monthNum] = month.split('-');
            let url = `${secureApi.baseURL}/reports/staff-attendance-calendar/export-excel?month=${monthNum}&year=${year}&token=${token}`;

            const link = document.createElement('a');
            link.href = url;
            link.download = `rapport_personnel_calendrier_${month}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire('Succès', 'Rapport Excel généré avec succès', 'success');
        } catch (error) {
            console.error('Erreur génération Excel:', error);
            Swal.fire('Erreur', 'Erreur lors de la génération du fichier Excel', 'error');
        } finally {
            setGenerating(false);
        }
    };

    return (
        <Container fluid className="py-4">
            {/* En-tête */}
            <Row className="mb-4">
                <Col>
                    <div className="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 style={{ color: '#007bff' }}>
                                <People className="me-2" />
                                Rapport de Présence du Personnel
                            </h2>
                            <p className="text-muted">
                                Calendrier mensuel des présences (In/Out par jour)
                            </p>
                        </div>
                    </div>
                </Col>
            </Row>

            {/* Filtres */}
            <Card className="mb-4">
                <Card.Body>
                    <Row>
                        <Col md={6}>
                            <Form.Group>
                                <Form.Label>
                                    <Calendar className="me-2" />
                                    Mois
                                </Form.Label>
                                <Form.Control
                                    type="month"
                                    value={month}
                                    onChange={(e) => setMonth(e.target.value)}
                                />
                            </Form.Group>
                        </Col>
                        <Col md={6} className="d-flex align-items-end">
                            <Button
                                variant="primary"
                                onClick={loadReport}
                                disabled={loading}
                                className="me-2 w-100"
                            >
                                {loading ? (
                                    <>
                                        <Spinner animation="border" size="sm" className="me-2" />
                                        Chargement...
                                    </>
                                ) : (
                                    <>
                                        <BarChart className="me-2" />
                                        Afficher Rapport
                                    </>
                                )}
                            </Button>
                        </Col>
                    </Row>

                    {reportData && (
                        <Row className="mt-3">
                            <Col md={12} className="d-flex justify-content-end gap-2">
                                <Button
                                    variant="success"
                                    onClick={handleGenerateExcel}
                                    disabled={generating}
                                >
                                    {generating ? (
                                        <>
                                            <Spinner animation="border" size="sm" className="me-2" />
                                            Génération...
                                        </>
                                    ) : (
                                        <>
                                            <Download className="me-2" />
                                            Exporter Excel
                                        </>
                                    )}
                                </Button>
                                <Button
                                    variant="danger"
                                    onClick={handleGeneratePdf}
                                    disabled={generating}
                                >
                                    {generating ? (
                                        <>
                                            <Spinner animation="border" size="sm" className="me-2" />
                                            Génération...
                                        </>
                                    ) : (
                                        <>
                                            <FiletypePdf className="me-2" />
                                            Exporter PDF
                                        </>
                                    )}
                                </Button>
                            </Col>
                        </Row>
                    )}
                </Card.Body>
            </Card>

            {/* Tableau calendrier */}
            {reportData && reportData.staff && (
                <Card>
                    <Card.Header style={{ background: '#007bff', color: 'white' }}>
                        <h5 className="mb-0">
                            Calendrier Mensuel - {reportData.monthName} {reportData.year} ({reportData.staff.length} employés)
                        </h5>
                    </Card.Header>
                    <Card.Body style={{
                        overflowX: 'auto',
                        overflowY: 'visible',
                        WebkitOverflowScrolling: 'touch',
                        scrollbarWidth: 'thin',
                        maxWidth: '100%'
                    }}>
                        <style>{`
                            .table-wrapper {
                                overflow-x: auto !important;
                                overflow-y: visible;
                                padding-bottom: 15px;
                            }
                            .table-wrapper::-webkit-scrollbar {
                                height: 14px;
                                width: 14px;
                            }
                            .table-wrapper::-webkit-scrollbar-track {
                                background: #e0e0e0;
                                border-radius: 8px;
                            }
                            .table-wrapper::-webkit-scrollbar-thumb {
                                background: #666;
                                border-radius: 8px;
                                border: 2px solid #e0e0e0;
                            }
                            .table-wrapper::-webkit-scrollbar-thumb:hover {
                                background: #333;
                            }
                            .calendar-compact th {
                                font-size: 0.75rem !important;
                                padding: 6px 4px !important;
                                white-space: nowrap;
                            }
                            .calendar-compact td {
                                font-size: 0.7rem !important;
                                padding: 6px 4px !important;
                            }
                            .calendar-compact .time-in,
                            .calendar-compact .time-out {
                                font-size: 0.65rem !important;
                                line-height: 1.3;
                            }
                        `}</style>
                        <div className="table-wrapper" style={{
                            overflowX: 'auto',
                            paddingBottom: '20px',
                            display: 'block',
                            width: '100%'
                        }}>
                        <Table bordered hover size="sm" className="calendar-compact" style={{
                            fontSize: '0.65rem',
                            minWidth: '2000px',
                            marginBottom: '0'
                        }}>
                            <thead style={{ position: 'sticky', top: 0, background: '#fff', zIndex: 10 }}>
                                <tr>
                                    <th style={{ minWidth: '120px', maxWidth: '120px', position: 'sticky', left: 0, background: '#007bff', color: 'white', zIndex: 20, fontSize: '0.75rem', padding: '6px' }}>
                                        Employé
                                    </th>
                                    {reportData.daysHeader && reportData.daysHeader.map((dayInfo, idx) => (
                                        <th key={idx} className="text-center" style={{ minWidth: '60px', maxWidth: '60px', width: '60px', background: '#007bff', color: 'white', padding: '4px' }}>
                                            <div style={{ fontSize: '0.65rem', lineHeight: '1.2' }}>{dayInfo.day_name}</div>
                                            <div style={{ fontSize: '0.75rem', fontWeight: 'bold' }}>{String(dayInfo.day).padStart(2, '0')}</div>
                                        </th>
                                    ))}
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '45px', maxWidth: '45px', padding: '4px', fontSize: '0.7rem' }}>%</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '65px', maxWidth: '65px', padding: '4px', fontSize: '0.7rem' }}>Total H</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>W</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>P</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>A</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>L</th>
                                    <th className="text-center" style={{ background: '#28a745', color: 'white', minWidth: '40px', maxWidth: '40px', padding: '4px', fontSize: '0.7rem' }}>HD</th>
                                    <th className="text-center" style={{ background: '#dc3545', color: 'white', minWidth: '65px', maxWidth: '65px', padding: '4px', fontSize: '0.7rem' }}>Total Retard</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reportData.staff.map((employee, index) => (
                                    <tr key={employee.id || index}>
                                        <td style={{
                                            position: 'sticky',
                                            left: 0,
                                            background: '#f8f9fa',
                                            fontWeight: 'bold',
                                            zIndex: 5,
                                            fontSize: '0.7rem',
                                            padding: '6px',
                                            minWidth: '120px',
                                            maxWidth: '120px'
                                        }}>
                                            {employee.name}
                                        </td>
                                        {employee.days && employee.days.map((dayData, dayIndex) => {
                                            const isWeekend = dayData && dayData.is_weekend;
                                            return (
                                                <td key={dayIndex} className="text-center" style={{
                                                    background: isWeekend ? '#ffe5e5' : 'white',
                                                    padding: '4px 2px',
                                                    fontSize: '0.65rem',
                                                    lineHeight: '1.3',
                                                    minWidth: '60px',
                                                    maxWidth: '60px',
                                                    verticalAlign: 'top'
                                                }}>
                                                    {dayData && dayData.pairs && dayData.pairs.length > 0 ? (
                                                        dayData.pairs.map((pair, pairIndex) => (
                                                            <div key={pairIndex} style={{
                                                                marginBottom: pairIndex < dayData.pairs.length - 1 ? '8px' : '0',
                                                                padding: '2px',
                                                                border: '1px solid #ddd',
                                                                borderRadius: '3px',
                                                                backgroundColor: '#f8f9fa'
                                                            }}>
                                                                <div className="time-in" style={{ color: '#28a745', fontWeight: 'bold', fontSize: '0.65rem' }}>
                                                                    In: {pair.in || '--:--'}
                                                                </div>
                                                                <div className="time-out" style={{ color: '#dc3545', fontWeight: 'bold', fontSize: '0.65rem' }}>
                                                                    Out: {pair.out || '--:--'}
                                                                </div>
                                                                {pair.late_minutes > 0 && (
                                                                    <div>
                                                                        <Badge bg="warning" style={{ fontSize: '0.55rem', padding: '1px 3px' }}>
                                                                            {pair.late_minutes}min
                                                                        </Badge>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ))
                                                    ) : (
                                                        <span style={{ color: '#ccc', fontSize: '0.7rem' }}>-</span>
                                                    )}
                                                </td>
                                            );
                                        })}
                                        <td className="text-center" style={{ fontWeight: 'bold', padding: '4px', fontSize: '0.7rem' }}>
                                            <Badge bg={
                                                employee.attendance_rate >= 80 ? 'success' :
                                                employee.attendance_rate >= 50 ? 'warning' : 'danger'
                                            } style={{ fontSize: '0.65rem' }}>
                                                {employee.attendance_rate}%
                                            </Badge>
                                        </td>
                                        <td className="text-center" style={{ fontWeight: 'bold', color: '#007bff', padding: '4px', fontSize: '0.7rem' }}>
                                            {employee.total_hours}
                                        </td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{employee.working_days}</td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{employee.present_days}</td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{employee.absent_days}</td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{employee.late_days}</td>
                                        <td className="text-center" style={{ padding: '4px', fontSize: '0.7rem' }}>{employee.half_days}</td>
                                        <td className="text-center" style={{ fontWeight: 'bold', color: '#dc3545', padding: '4px', fontSize: '0.7rem' }}>
                                            {employee.total_late_formatted || '00:00'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                        </div>

                        {reportData.staff.length === 0 && (
                            <Alert variant="info" className="text-center">
                                Aucun personnel trouvé pour cette période
                            </Alert>
                        )}

                        <div className="mt-3" style={{ fontSize: '0.8rem', color: '#666' }}>
                            <strong>Légende:</strong> % = Taux présence | Total H = Total heures travaillées | W = Working days | P = Present | A = Absent | L = Late days | HD = Half Day | <span style={{ color: '#dc3545', fontWeight: 'bold' }}>Total Retard</span> = Cumul retard mensuel (HH:MM)
                        </div>
                    </Card.Body>
                </Card>
            )}

            {/* Message initial */}
            {!reportData && !loading && (
                <Alert variant="info" className="text-center">
                    <Clock size={48} className="mb-3" />
                    <p>Sélectionnez un mois et cliquez sur "Afficher Rapport" pour voir les données</p>
                </Alert>
            )}
        </Container>
    );
};

export default StaffAttendanceReport;
