import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, CardBody, Table, Button, Input, Label, FormGroup, Alert, Spinner, Badge } from 'reactstrap';
import { Search, Download, Award, TrophyFill, StarFill, FileEarmarkPdfFill, PrinterFill, ArrowRepeat, XCircle } from 'react-bootstrap-icons';
import secureApi from '../../utils/api';
import { host } from '../../utils/fetch';

const MENTION_CONFIG = {
  'Excellent': {
    gradient: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
    bg: 'rgba(245, 158, 11, 0.08)',
    border: '#f59e0b',
    color: '#92400e',
    badge: '#f59e0b',
    icon: <TrophyFill />,
    label: 'Excellent',
  },
  'Très bien': {
    gradient: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
    bg: 'rgba(59, 130, 246, 0.08)',
    border: '#3b82f6',
    color: '#1e40af',
    badge: '#3b82f6',
    icon: <StarFill />,
    label: 'Très Bien',
  },
  'Bien': {
    gradient: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
    bg: 'rgba(16, 185, 129, 0.08)',
    border: '#10b981',
    color: '#065f46',
    badge: '#10b981',
    icon: <Award />,
    label: 'Bien',
  },
  'Assez bien': {
    gradient: 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
    bg: 'rgba(139, 92, 246, 0.08)',
    border: '#8b5cf6',
    color: '#5b21b6',
    badge: '#8b5cf6',
    icon: <Award />,
    label: 'Assez Bien',
  },
};

const StatCard = ({ value, label, gradient, icon }) => (
  <div style={{
    background: gradient,
    borderRadius: '16px',
    padding: '20px',
    color: '#fff',
    position: 'relative',
    overflow: 'hidden',
    minHeight: '100px',
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'center',
  }}>
    <div style={{ position: 'absolute', right: '12px', top: '12px', opacity: 0.25, fontSize: '40px' }}>
      {icon}
    </div>
    <div style={{ fontSize: '32px', fontWeight: '800', lineHeight: 1 }}>{value}</div>
    <div style={{ fontSize: '13px', fontWeight: '500', opacity: 0.9, marginTop: '6px' }}>{label}</div>
  </div>
);

const HonorRoll = () => {
  const [sections, setSections] = useState([]);
  const [allLevels, setAllLevels] = useState([]);
  const [allClasses, setAllClasses] = useState([]);
  const [allSeries, setAllSeries] = useState([]);
  const [trimesters, setTrimesters] = useState([]);

  const [selectedSection, setSelectedSection] = useState('');
  const [selectedLevel, setSelectedLevel] = useState('');
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedSeries, setSelectedSeries] = useState('');
  const [selectedPeriodType, setSelectedPeriodType] = useState('trimester');
  const [selectedTrimester, setSelectedTrimester] = useState('');

  const [eligibleStudents, setEligibleStudents] = useState([]);
  const [groupedByMention, setGroupedByMention] = useState({});
  const [statistics, setStatistics] = useState({});
  const [loading, setLoading] = useState(false);
  const [generating, setGenerating] = useState({});
  const [batchGenerating, setBatchGenerating] = useState(false);
  const [merging, setMerging] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => { fetchFilters(); }, []);
  useEffect(() => { if (selectedSection) { setSelectedLevel(''); setSelectedClass(''); setSelectedSeries(''); } }, [selectedSection]);
  useEffect(() => { if (selectedLevel) { setSelectedClass(''); setSelectedSeries(''); } }, [selectedLevel]);
  useEffect(() => { if (selectedClass) { setSelectedSeries(''); } }, [selectedClass]);

  const fetchFilters = async () => {
    try {
      const data = await secureApi.get('/honor-rolls/filters');
      setSections(data.sections || []);
      setAllLevels(data.levels || []);
      setAllClasses(data.classes || []);
      setAllSeries(data.series || []);
      setTrimesters(data.trimesters || []);
      if (data.trimesters?.length > 0) {
        setSelectedTrimester(data.trimesters[0].id.toString());
      }
    } catch (err) {
      setError(`Erreur lors du chargement des filtres: ${err.message || 'Erreur inconnue'}`);
    }
  };

  const fetchEligibleStudents = async () => {
    if (selectedPeriodType === 'trimester' && !selectedTrimester) {
      setError('Veuillez sélectionner un trimestre');
      return;
    }
    if (!selectedSection && !selectedLevel && !selectedClass && !selectedSeries) {
      setError('Veuillez sélectionner au moins un filtre (section, niveau, classe ou série)');
      return;
    }

    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const response = await secureApi.post('/honor-rolls/eligible-students', {
        period_type: selectedPeriodType,
        trimester_id: selectedPeriodType === 'trimester' ? selectedTrimester : null,
        section_id: selectedSection || null,
        level_id: selectedLevel || null,
        class_id: selectedClass || null,
        series_id: selectedSeries || null,
      });

      setEligibleStudents(response.students || []);
      setGroupedByMention(response.grouped_by_mention || {});
      setStatistics(response.statistics || {});

      if (response.students?.length === 0) {
        setError('Aucun élève éligible trouvé avec les critères sélectionnés');
      } else {
        setSuccess(`${response.students.length} élève(s) éligible(s) au tableau d'honneur`);
      }
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des élèves');
    } finally {
      setLoading(false);
    }
  };

  const generateCertificate = async (studentId, average, rank) => {
    if (selectedPeriodType === 'trimester' && !selectedTrimester) {
      setError('Veuillez sélectionner un trimestre');
      return;
    }
    setGenerating({ ...generating, [studentId]: true });
    setError('');

    try {
      const response = await secureApi.post('/honor-rolls/generate-certificate', {
        student_id: studentId,
        period_type: selectedPeriodType,
        trimester_id: selectedPeriodType === 'trimester' ? selectedTrimester : null,
        average, rank,
      });

      if (response.download_url) {
        const token = localStorage.getItem('token');
        const downloadResponse = await fetch(host + response.download_url, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        if (downloadResponse.ok) {
          const blob = await downloadResponse.blob();
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = response.download_url.split('/').pop();
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);
          setSuccess(`Certificat généré avec succès`);
        }
      }
    } catch (err) {
      setError(err.message || 'Erreur lors de la génération du certificat');
    } finally {
      setGenerating({ ...generating, [studentId]: false });
    }
  };

  const batchGenerateAllCertificates = async () => {
    if ((selectedPeriodType === 'trimester' && !selectedTrimester) || eligibleStudents.length === 0) return;
    setBatchGenerating(true);
    setError('');
    setSuccess('');
    try {
      const studentsData = eligibleStudents.map(s => ({ id: s.id, average: s.average, rank: s.rank }));
      const response = await secureApi.post('/honor-rolls/batch-generate', {
        student_ids: eligibleStudents.map(s => s.id),
        students_data: studentsData,
        period_type: selectedPeriodType,
        trimester_id: selectedPeriodType === 'trimester' ? selectedTrimester : null,
      });
      setSuccess(`${response.generated_count} certificats générés avec succès (${response.failed_count} échecs)`);
    } catch (err) {
      setError(err.message || 'Erreur lors de la génération en masse');
    } finally {
      setBatchGenerating(false);
    }
  };

  const mergeAndDownloadCertificates = async () => {
    if (selectedPeriodType === 'trimester' && !selectedTrimester) return;
    setMerging(true);
    setError('');
    setSuccess('');
    try {
      const response = await secureApi.post('/honor-rolls/merge', {
        period_type: selectedPeriodType,
        trimester_id: selectedPeriodType === 'trimester' ? selectedTrimester : null,
        section_id: selectedSection || null,
        level_id: selectedLevel || null,
        class_id: selectedClass || null,
        series_id: selectedSeries || null,
      });
      const downloadUrl = response.download_url || response.direct_download_url;
      if (downloadUrl) {
        const token = localStorage.getItem('token');
        const downloadResponse = await fetch(host + downloadUrl, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        if (downloadResponse.ok) {
          const blob = await downloadResponse.blob();
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = response.filename || 'honor_rolls_merged.pdf';
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);
          setSuccess(`${response.certificate_count} certificats fusionnés et téléchargés !`);
        }
      } else {
        setSuccess(response.message || 'Fusion terminée');
      }
    } catch (err) {
      setError(err.message || 'Erreur lors de la fusion des certificats');
    } finally {
      setMerging(false);
    }
  };

  const resetFilters = () => {
    setSelectedSection('');
    setSelectedLevel('');
    setSelectedClass('');
    setSelectedSeries('');
    setSelectedPeriodType('trimester');
    setSelectedTrimester('');
    setEligibleStudents([]);
    setGroupedByMention({});
    setStatistics({});
    setError('');
    setSuccess('');
  };

  const filteredLevels = allLevels.filter(l => !selectedSection || l.section_id === parseInt(selectedSection));
  const filteredClasses = allClasses.filter(c => !selectedLevel || c.level_id === parseInt(selectedLevel));
  const filteredSeries = allSeries.filter(s => !selectedClass || s.class_id === parseInt(selectedClass));

  return (
    <div style={{ padding: '24px', maxWidth: '1400px', margin: '0 auto' }}>
      {/* Header */}
      <div style={{
        background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%)',
        borderRadius: '20px',
        padding: '32px 36px',
        marginBottom: '24px',
        color: '#fff',
        position: 'relative',
        overflow: 'hidden',
      }}>
        <div style={{ position: 'absolute', right: '30px', top: '50%', transform: 'translateY(-50%)', opacity: 0.15, fontSize: '120px' }}>
          <TrophyFill />
        </div>
        <h2 style={{ fontSize: '28px', fontWeight: '800', margin: 0, position: 'relative', zIndex: 1 }}>
          Tableau d'Honneur
        </h2>
        <p style={{ margin: '6px 0 0', opacity: 0.9, fontSize: '15px', position: 'relative', zIndex: 1 }}>
          Gestion des certificats d'honneur - Moyenne minimale requise : 12/20
        </p>
      </div>

      {/* Filtres */}
      <Card style={{ borderRadius: '16px', border: 'none', boxShadow: '0 1px 3px rgba(0,0,0,0.08)', marginBottom: '24px' }}>
        <CardBody style={{ padding: '24px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '20px' }}>
            <Search size={18} style={{ color: '#6b7280' }} />
            <h6 style={{ margin: 0, fontWeight: '700', color: '#374151' }}>Filtres de recherche</h6>
          </div>

          <Row className="g-3">
            <Col md={2}>
              <FormGroup className="mb-0">
                <Label style={labelStyle}>Période *</Label>
                <Input type="select" bsSize="sm" style={selectStyle}
                  value={selectedPeriodType === 'annual' ? 'annual' : selectedTrimester}
                  onChange={(e) => {
                    const val = e.target.value;
                    if (val === 'annual') { setSelectedPeriodType('annual'); setSelectedTrimester(''); }
                    else { setSelectedPeriodType('trimester'); setSelectedTrimester(val); }
                  }}>
                  <option value="">Choisir...</option>
                  {trimesters.map(t => (
                    <option key={t.id} value={t.id}>Trimestre {t.trimester_number}</option>
                  ))}
                  <option value="annual">Annuel</option>
                </Input>
              </FormGroup>
            </Col>

            <Col md={2}>
              <FormGroup className="mb-0">
                <Label style={labelStyle}>Section</Label>
                <Input type="select" bsSize="sm" style={selectStyle}
                  value={selectedSection} onChange={e => setSelectedSection(e.target.value)}>
                  <option value="">Toutes</option>
                  {sections.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                </Input>
              </FormGroup>
            </Col>

            <Col md={2}>
              <FormGroup className="mb-0">
                <Label style={labelStyle}>Niveau</Label>
                <Input type="select" bsSize="sm" style={selectStyle} disabled={!selectedSection}
                  value={selectedLevel} onChange={e => setSelectedLevel(e.target.value)}>
                  <option value="">Tous</option>
                  {filteredLevels.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
                </Input>
              </FormGroup>
            </Col>

            <Col md={2}>
              <FormGroup className="mb-0">
                <Label style={labelStyle}>Classe</Label>
                <Input type="select" bsSize="sm" style={selectStyle} disabled={!selectedLevel}
                  value={selectedClass} onChange={e => setSelectedClass(e.target.value)}>
                  <option value="">Toutes</option>
                  {filteredClasses.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                </Input>
              </FormGroup>
            </Col>

            <Col md={2}>
              <FormGroup className="mb-0">
                <Label style={labelStyle}>Série</Label>
                <Input type="select" bsSize="sm" style={selectStyle} disabled={!selectedClass}
                  value={selectedSeries} onChange={e => setSelectedSeries(e.target.value)}>
                  <option value="">Toutes</option>
                  {filteredSeries.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                </Input>
              </FormGroup>
            </Col>

            <Col md={2} className="d-flex align-items-end gap-2">
              <Button color="primary" size="sm" className="flex-grow-1"
                style={{ borderRadius: '10px', fontWeight: '600', padding: '8px 16px' }}
                onClick={fetchEligibleStudents}
                disabled={loading || (selectedPeriodType === 'trimester' && !selectedTrimester)}>
                {loading ? <><Spinner size="sm" className="me-1" /> Recherche...</> : <><Search className="me-1" size={14} /> Rechercher</>}
              </Button>
              <Button color="light" size="sm" onClick={resetFilters}
                style={{ borderRadius: '10px', padding: '8px 10px', color: '#6b7280' }}
                title="Réinitialiser">
                <ArrowRepeat size={16} />
              </Button>
            </Col>
          </Row>
        </CardBody>
      </Card>

      {/* Messages */}
      {error && (
        <Alert color="danger" className="mb-3" style={{ borderRadius: '12px', border: 'none' }} fade={false} toggle={() => setError('')}>
          <XCircle className="me-2" />{error}
        </Alert>
      )}
      {success && (
        <Alert color="success" className="mb-3" style={{ borderRadius: '12px', border: 'none' }} fade={false} toggle={() => setSuccess('')}>
          {success}
        </Alert>
      )}

      {/* Statistiques */}
      {eligibleStudents.length > 0 && (
        <Row className="g-3 mb-4">
          <Col xs={6} md>
            <StatCard
              value={statistics.total || 0}
              label="Total éligibles"
              gradient="linear-gradient(135deg, #1f2937 0%, #374151 100%)"
              icon={<TrophyFill />}
            />
          </Col>
          <Col xs={6} md>
            <StatCard
              value={statistics.excellent || 0}
              label="Excellent (16+)"
              gradient={MENTION_CONFIG['Excellent'].gradient}
              icon={<TrophyFill />}
            />
          </Col>
          <Col xs={6} md>
            <StatCard
              value={statistics.tres_bien || 0}
              label="Très Bien (14-16)"
              gradient={MENTION_CONFIG['Très bien'].gradient}
              icon={<StarFill />}
            />
          </Col>
          <Col xs={6} md>
            <StatCard
              value={statistics.bien || 0}
              label="Bien (13-14)"
              gradient={MENTION_CONFIG['Bien'].gradient}
              icon={<Award />}
            />
          </Col>
          <Col xs={6} md>
            <StatCard
              value={statistics.assez_bien || 0}
              label="Assez Bien (12-13)"
              gradient={MENTION_CONFIG['Assez bien'].gradient}
              icon={<Award />}
            />
          </Col>
        </Row>
      )}

      {/* Actions en masse */}
      {eligibleStudents.length > 0 && (
        <div style={{
          display: 'flex',
          gap: '12px',
          marginBottom: '24px',
          flexWrap: 'wrap',
          alignItems: 'center',
          padding: '16px 20px',
          background: '#f9fafb',
          borderRadius: '14px',
          border: '1px solid #e5e7eb',
        }}>
          <Button color="primary" onClick={batchGenerateAllCertificates} disabled={batchGenerating}
            style={{ borderRadius: '10px', fontWeight: '600', padding: '10px 20px' }}>
            {batchGenerating
              ? <><Spinner size="sm" className="me-2" />Génération...</>
              : <><FileEarmarkPdfFill className="me-2" />Générer tous les certificats ({eligibleStudents.length})</>
            }
          </Button>

          <Button color="success" onClick={mergeAndDownloadCertificates} disabled={merging}
            style={{ borderRadius: '10px', fontWeight: '600', padding: '10px 20px' }}>
            {merging
              ? <><Spinner size="sm" className="me-2" />Fusion...</>
              : <><PrinterFill className="me-2" />Fusionner et imprimer</>
            }
          </Button>

          <span style={{ fontSize: '12px', color: '#9ca3af', marginLeft: 'auto' }}>
            Générez d'abord, puis fusionnez en un seul PDF
          </span>
        </div>
      )}

      {/* Liste par mention */}
      {eligibleStudents.length > 0 && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
          {['Excellent', 'Très bien', 'Bien', 'Assez bien'].map((mention) => {
            const students = groupedByMention[mention] || [];
            if (students.length === 0) return null;
            const config = MENTION_CONFIG[mention];

            return (
              <div key={mention} style={{
                borderRadius: '16px',
                overflow: 'hidden',
                border: `1px solid ${config.border}22`,
                background: '#fff',
                boxShadow: '0 1px 3px rgba(0,0,0,0.06)',
              }}>
                {/* Header de mention */}
                <div style={{
                  background: config.gradient,
                  padding: '14px 24px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  color: '#fff',
                }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <span style={{ fontSize: '20px' }}>{config.icon}</span>
                    <span style={{ fontSize: '16px', fontWeight: '700' }}>{config.label}</span>
                  </div>
                  <Badge pill style={{
                    background: 'rgba(255,255,255,0.25)',
                    color: '#fff',
                    fontSize: '13px',
                    padding: '6px 14px',
                    fontWeight: '700',
                  }}>
                    {students.length} élève{students.length > 1 ? 's' : ''}
                  </Badge>
                </div>

                {/* Table */}
                <div style={{ overflowX: 'auto' }}>
                  <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                      <tr style={{ background: config.bg }}>
                        <th style={thStyle}>#</th>
                        <th style={thStyle}>Nom et Prénom(s)</th>
                        <th style={thStyle}>Classe</th>
                        <th style={{ ...thStyle, textAlign: 'center' }}>Moyenne</th>
                        <th style={{ ...thStyle, textAlign: 'center' }}>Rang</th>
                        <th style={{ ...thStyle, textAlign: 'right' }}>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      {students.map((student, idx) => (
                        <tr key={student.id} style={{
                          borderBottom: '1px solid #f3f4f6',
                          transition: 'background 0.15s',
                        }}
                          onMouseEnter={e => e.currentTarget.style.background = config.bg}
                          onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                        >
                          <td style={{ ...tdStyle, color: '#9ca3af', fontWeight: '500', width: '50px' }}>
                            {idx + 1}
                          </td>
                          <td style={tdStyle}>
                            <span style={{ fontWeight: '600', color: '#111827' }}>{student.full_name}</span>
                          </td>
                          <td style={tdStyle}>
                            <span style={{
                              background: '#f3f4f6',
                              padding: '3px 10px',
                              borderRadius: '6px',
                              fontSize: '12px',
                              fontWeight: '600',
                              color: '#4b5563',
                            }}>
                              {student.class}
                            </span>
                          </td>
                          <td style={{ ...tdStyle, textAlign: 'center' }}>
                            <span style={{
                              background: config.badge,
                              color: '#fff',
                              padding: '4px 12px',
                              borderRadius: '20px',
                              fontSize: '13px',
                              fontWeight: '700',
                              display: 'inline-block',
                              minWidth: '60px',
                            }}>
                              {student.average}/20
                            </span>
                          </td>
                          <td style={{ ...tdStyle, textAlign: 'center' }}>
                            <span style={{
                              background: '#f9fafb',
                              border: '1px solid #e5e7eb',
                              padding: '3px 10px',
                              borderRadius: '8px',
                              fontSize: '13px',
                              fontWeight: '700',
                              color: '#374151',
                            }}>
                              {student.rank}<sup>e</sup>
                            </span>
                          </td>
                          <td style={{ ...tdStyle, textAlign: 'right' }}>
                            <button
                              onClick={() => generateCertificate(student.id, student.average, student.rank)}
                              disabled={generating[student.id]}
                              style={{
                                background: generating[student.id] ? '#e5e7eb' : config.gradient,
                                color: '#fff',
                                border: 'none',
                                borderRadius: '8px',
                                padding: '6px 14px',
                                fontSize: '12px',
                                fontWeight: '600',
                                cursor: generating[student.id] ? 'not-allowed' : 'pointer',
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: '6px',
                                transition: 'opacity 0.15s',
                              }}
                              onMouseEnter={e => { if (!generating[student.id]) e.currentTarget.style.opacity = '0.85'; }}
                              onMouseLeave={e => e.currentTarget.style.opacity = '1'}
                            >
                              {generating[student.id]
                                ? <><Spinner size="sm" /> Génération...</>
                                : <><Download size={13} /> Certificat</>
                              }
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* État vide */}
      {!loading && eligibleStudents.length === 0 && !error && (
        <div style={{
          textAlign: 'center',
          padding: '80px 20px',
          background: '#fff',
          borderRadius: '20px',
          border: '2px dashed #e5e7eb',
        }}>
          <div style={{
            width: '80px',
            height: '80px',
            borderRadius: '50%',
            background: 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            margin: '0 auto 20px',
          }}>
            <TrophyFill size={36} style={{ color: '#f59e0b' }} />
          </div>
          <h5 style={{ color: '#374151', fontWeight: '700', marginBottom: '8px' }}>
            Tableau d'honneur
          </h5>
          <p style={{ color: '#9ca3af', maxWidth: '400px', margin: '0 auto', fontSize: '14px' }}>
            Sélectionnez une période et un filtre, puis cliquez sur "Rechercher" pour afficher les élèves éligibles au tableau d'honneur.
          </p>
        </div>
      )}
    </div>
  );
};

const labelStyle = {
  fontSize: '12px',
  fontWeight: '600',
  color: '#6b7280',
  textTransform: 'uppercase',
  letterSpacing: '0.03em',
  marginBottom: '4px',
};

const selectStyle = {
  borderRadius: '10px',
  border: '1px solid #e5e7eb',
  fontSize: '13px',
  padding: '8px 12px',
};

const thStyle = {
  padding: '10px 16px',
  fontSize: '11px',
  fontWeight: '700',
  textTransform: 'uppercase',
  letterSpacing: '0.05em',
  color: '#6b7280',
  borderBottom: '1px solid #e5e7eb',
};

const tdStyle = {
  padding: '12px 16px',
  fontSize: '14px',
  verticalAlign: 'middle',
};

export default HonorRoll;
