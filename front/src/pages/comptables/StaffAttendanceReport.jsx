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
    Alert
} from 'react-bootstrap';
import {
    FiletypePdf,
    Calendar,
    People,
    Clock,
    BarChart,
    Download,
    Search,
    XCircle
} from 'react-bootstrap-icons';
import { secureApi } from '../../utils/apiMigration';
import Swal from 'sweetalert2';

const StaffAttendanceReport = () => {
    const [month, setMonth] = useState(new Date().toISOString().slice(0, 7));
    const [loading, setLoading] = useState(false);
    const [reportData, setReportData] = useState(null);
    const [generating, setGenerating] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    const filteredStaff = reportData?.staff?.filter(employee =>
        employee.name?.toLowerCase().includes(searchTerm.toLowerCase())
    ) || [];

    const loadReport = async () => {
        try {
            setLoading(true);
            setReportData(null); // Reset data first

            const [year, monthNum] = month.split('-');
            const url = `/reports/staff-attendance-calendar?month=${monthNum}&year=${year}`;

            console.log('Calling API:', url);
            const response = await secureApi.get(url);
            console.log('API Response:', response);
            console.log('response.success:', response.success);
            console.log('response.data:', response.data);

            // L'API retourne directement les données sans wrapper success
            if (response && (response.staff || response.data)) {
                const data = response.data || response;
                console.log('Setting report data:', data);
                setReportData(data);
            } else {
                console.error('Response structure unexpected:', response);
                Swal.fire('Erreur', 'Structure de réponse inattendue', 'error');
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
            <Row className="mb-4">
                <Col>
                    <h2 style={{ color: '#007bff' }}>
                        <People className="me-2" />
                        Rapport de Présence du Personnel
                    </h2>
                    <p className="text-muted">
                        Calendrier mensuel des présences (In/Out par jour)
                    </p>
                </Col>
            </Row>

            <Card className="mb-4">
                <Card.Body>
                    <Row>
                        <Col md={4}>
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
                        <Col md={4}>
                            <Form.Group>
                                <Form.Label>
                                    <Search className="me-2" />
                                    Rechercher un employé
                                </Form.Label>
                                <div className="position-relative">
                                    <Form.Control
                                        type="text"
                                        placeholder="Nom de l'employé..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                    {searchTerm && (
                                        <XCircle
                                            className="position-absolute"
                                            style={{ right: '10px', top: '50%', transform: 'translateY(-50%)', cursor: 'pointer', color: '#999' }}
                                            onClick={() => setSearchTerm('')}
                                        />
                                    )}
                                </div>
                            </Form.Group>
                        </Col>
                        <Col md={4} className="d-flex align-items-end">
                            <Button
                                variant="primary"
                                onClick={loadReport}
                                disabled={loading}
                                className="me-2 w-100"
                            >
                                {loading ? 'Chargement...' : (
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
                                    {generating ? 'Génération...' : (
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
                                    {generating ? 'Génération...' : (
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

            {reportData && reportData.staff && Array.isArray(reportData.staff) && (
                <Card>
                    <Card.Header style={{ background: '#007bff', color: 'white' }}>
                        <h5 className="mb-0">
                            Calendrier Mensuel - {reportData.monthName} {reportData.year} ({filteredStaff.length}{searchTerm ? `/${reportData.staff.length}` : ''} employé{filteredStaff.length > 1 ? 's' : ''})
                        </h5>
                    </Card.Header>
                    <Card.Body>
                        <div style={{ overflowX: 'auto' }}>
                            <Table bordered hover size="sm" style={{ minWidth: '2000px' }}>
                                <thead>
                                    <tr>
                                        <th style={{ minWidth: '120px', position: 'sticky', left: 0, background: '#007bff', color: 'white' }}>
                                            Employé
                                        </th>
                                        {reportData.daysHeader && reportData.daysHeader.map((dayInfo, idx) => (
                                            <th key={`day-${idx}`} className="text-center" style={{ background: '#007bff', color: 'white', minWidth: '60px' }}>
                                                <div style={{ fontSize: '0.7rem' }}>{dayInfo.day_name}</div>
                                                <div>{String(dayInfo.day).padStart(2, '0')}</div>
                                            </th>
                                        ))}
                                        <th className="text-center" style={{ background: '#28a745', color: 'white' }}>%</th>
                                        <th className="text-center" style={{ background: '#28a745', color: 'white' }}>Total H</th>
                                        <th className="text-center" style={{ background: '#dc3545', color: 'white' }}>Total Retard</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredStaff.map((employee, empIdx) => (
                                        <tr key={`emp-${employee.id || empIdx}`}>
                                            <td style={{ position: 'sticky', left: 0, background: '#f8f9fa', fontWeight: 'bold' }}>
                                                {employee.name}
                                            </td>
                                            {employee.days && employee.days.map((dayData, dayIdx) => (
                                                <td key={`emp-${empIdx}-day-${dayIdx}`} className="text-center" style={{
                                                    background: dayData?.is_weekend ? '#ffe5e5' : 'white',
                                                    fontSize: '0.7rem'
                                                }}>
                                                    {dayData && dayData.pairs && dayData.pairs.length > 0 ? (
                                                        dayData.pairs.map((pair, pairIdx) => (
                                                            <div key={`pair-${pairIdx}`} style={{ marginBottom: '5px' }}>
                                                                <div style={{ color: '#28a745' }}>In: {pair.in || '--:--'}</div>
                                                                <div style={{ color: '#dc3545' }}>Out: {pair.out || '--:--'}</div>
                                                                {pair.late_minutes > 0 && (
                                                                    <Badge bg="warning">{pair.late_minutes}min</Badge>
                                                                )}
                                                            </div>
                                                        ))
                                                    ) : '-'}
                                                </td>
                                            ))}
                                            <td className="text-center">
                                                <Badge bg={employee.attendance_rate >= 80 ? 'success' : 'warning'}>
                                                    {employee.attendance_rate}%
                                                </Badge>
                                            </td>
                                            <td className="text-center" style={{ fontWeight: 'bold', color: '#007bff' }}>
                                                {employee.total_hours}
                                            </td>
                                            <td className="text-center" style={{ fontWeight: 'bold', color: '#dc3545' }}>
                                                {employee.total_late_formatted || '00:00'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        </div>

                        {filteredStaff.length === 0 && reportData.staff.length > 0 && (
                            <Alert variant="warning" className="text-center">
                                Aucun employé trouvé pour "{searchTerm}"
                            </Alert>
                        )}
                        {reportData.staff.length === 0 && (
                            <Alert variant="info" className="text-center">
                                Aucun personnel trouvé pour cette période
                            </Alert>
                        )}

                        <div className="mt-3" style={{ fontSize: '0.9rem', color: '#666' }}>
                            <strong>Légende:</strong> % = Taux présence | Total H = Total heures |
                            <span style={{ color: '#dc3545', fontWeight: 'bold' }}> Total Retard</span> = Cumul retard mensuel (HH:MM)
                        </div>
                    </Card.Body>
                </Card>
            )}

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
