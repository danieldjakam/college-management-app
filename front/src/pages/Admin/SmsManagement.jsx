import React, { useState, useEffect, useCallback } from 'react';
import {
  Container, Row, Col, Card, CardBody, Button, Input, Label, FormGroup,
  Alert, Spinner, Badge, Table, Nav, NavItem, NavLink, TabContent, TabPane,
  Modal, ModalHeader, ModalBody, ModalFooter
} from 'reactstrap';
import {
  Phone, Send, Gear, ArrowClockwise, CheckCircle, XCircle,
  CreditCard2Back, Clock, People, Search
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';

const SmsManagement = () => {
  const [activeTab, setActiveTab] = useState('send');
  const [loading, setLoading] = useState(false);
  const [alert, setAlert] = useState(null);

  // Send SMS state
  const [sendTo, setSendTo] = useState('all');
  const [message, setMessage] = useState('');
  const [classes, setClasses] = useState([]);
  const [selectedClass, setSelectedClass] = useState('');
  const [selectedStudent, setSelectedStudent] = useState('');
  const [students, setStudents] = useState([]);
  const [sending, setSending] = useState(false);

  // History state
  const [history, setHistory] = useState([]);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [historyFilter, setHistoryFilter] = useState({ type: '', status: '' });

  // Stats state
  const [stats, setStats] = useState(null);
  const [statsLoading, setStatsLoading] = useState(false);

  // Settings state
  const [settings, setSettings] = useState({
    sms_notifications_enabled: false,
    nexah_sms_user: '',
    nexah_sms_password: '',
    nexah_sms_sender_id: 'CPB DOUALA',
  });
  const [settingsLoading, setSettingsLoading] = useState(false);
  const [testPhone, setTestPhone] = useState('');
  const [testModal, setTestModal] = useState(false);
  const [testing, setTesting] = useState(false);

  const showAlert = (type, msg) => {
    setAlert({ type, message: msg });
    setTimeout(() => setAlert(null), 5000);
  };

  // Load classes for selector
  useEffect(() => {
    const loadClasses = async () => {
      try {
        const res = await secureApiEndpoints.request('/notifications/classes');
        if (res?.success) setClasses(res.data || []);
      } catch (e) { console.error(e); }
    };
    loadClasses();
  }, []);

  // Load students when class selected
  useEffect(() => {
    if (sendTo === 'class' && selectedClass) {
      const loadStudents = async () => {
        try {
          const res = await secureApiEndpoints.request(`/notifications/students?class_series_id=${selectedClass}`);
          if (res?.success) setStudents(res.data || []);
        } catch (e) { console.error(e); }
      };
      loadStudents();
    }
  }, [sendTo, selectedClass]);

  // Load settings
  const loadSettings = useCallback(async () => {
    try {
      const res = await secureApiEndpoints.request('/school-settings');
      if (res?.success && res.data) {
        setSettings({
          sms_notifications_enabled: res.data.sms_notifications_enabled || false,
          nexah_sms_user: res.data.nexah_sms_user || '',
          nexah_sms_password: res.data.nexah_sms_password || '',
          nexah_sms_sender_id: res.data.nexah_sms_sender_id || 'CPB DOUALA',
        });
      }
    } catch (e) { console.error(e); }
  }, []);

  useEffect(() => { loadSettings(); }, [loadSettings]);

  // Send SMS
  const handleSend = async () => {
    if (!message.trim()) {
      showAlert('warning', 'Veuillez saisir un message');
      return;
    }
    setSending(true);
    try {
      const body = { message, send_to: sendTo };
      if (sendTo === 'class') body.class_series_id = parseInt(selectedClass);
      if (sendTo === 'student') body.student_id = parseInt(selectedStudent);

      const res = await secureApiEndpoints.request('/sms/send', {
        method: 'POST',
        body: JSON.stringify(body),
      });

      if (res?.success) {
        showAlert('success', res.message || 'SMS envoyes avec succes');
        setMessage('');
      } else {
        showAlert('danger', res?.message || 'Erreur lors de l\'envoi');
      }
    } catch (e) {
      showAlert('danger', e.message || 'Erreur lors de l\'envoi');
    } finally {
      setSending(false);
    }
  };

  // Load history
  const loadHistory = useCallback(async () => {
    setHistoryLoading(true);
    try {
      let url = '/sms/history?per_page=100';
      if (historyFilter.type) url += `&type=${historyFilter.type}`;
      if (historyFilter.status) url += `&status=${historyFilter.status}`;
      const res = await secureApiEndpoints.request(url);
      if (res?.success) setHistory(res.data?.data || []);
    } catch (e) { console.error(e); }
    finally { setHistoryLoading(false); }
  }, [historyFilter]);

  // Load stats
  const loadStats = useCallback(async () => {
    setStatsLoading(true);
    try {
      const res = await secureApiEndpoints.request('/sms/stats');
      if (res?.success) setStats(res.data);
    } catch (e) { console.error(e); }
    finally { setStatsLoading(false); }
  }, []);

  useEffect(() => {
    if (activeTab === 'history') loadHistory();
    if (activeTab === 'stats') loadStats();
  }, [activeTab, loadHistory, loadStats]);

  // Save settings
  const handleSaveSettings = async () => {
    setSettingsLoading(true);
    try {
      const res = await secureApiEndpoints.request('/school-settings', {
        method: 'PUT',
        body: JSON.stringify(settings),
      });
      if (res?.success) {
        showAlert('success', 'Parametres SMS sauvegardes');
      } else {
        showAlert('danger', res?.message || 'Erreur de sauvegarde');
      }
    } catch (e) {
      showAlert('danger', e.message);
    } finally {
      setSettingsLoading(false);
    }
  };

  // Test connection
  const handleTestConnection = async () => {
    setLoading(true);
    try {
      const res = await secureApiEndpoints.request('/sms/test-connection');
      if (res?.success) {
        showAlert('success', res.message);
      } else {
        showAlert('danger', res?.message || 'Echec de connexion');
      }
    } catch (e) {
      showAlert('danger', e.message);
    } finally {
      setLoading(false);
    }
  };

  // Send test SMS
  const handleSendTest = async () => {
    if (!testPhone.trim()) return;
    setTesting(true);
    try {
      const res = await secureApiEndpoints.request('/sms/test', {
        method: 'POST',
        body: JSON.stringify({ phone: testPhone }),
      });
      if (res?.success) {
        showAlert('success', 'SMS de test envoye');
        setTestModal(false);
        setTestPhone('');
      } else {
        showAlert('danger', res?.message || 'Echec envoi test');
      }
    } catch (e) {
      showAlert('danger', e.message);
    } finally {
      setTesting(false);
    }
  };

  const charCount = message.length;
  const smsCount = Math.ceil(charCount / 160) || 0;

  const typeLabels = {
    manual: 'Manuel',
    payment_reminder: 'Rappel paiement',
    test: 'Test',
    general: 'General',
  };

  return (
    <Container fluid className="py-3">
      {/* Header */}
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 className="mb-1 d-flex align-items-center gap-2">
            <Phone size={24} /> SMS Parents (Nexah)
          </h4>
          <small className="text-muted">Envoyez des SMS aux parents d'eleves via Nexah BulkSMS</small>
        </div>
        {stats?.balance?.success && (
          <Badge color="info" className="p-2" style={{ fontSize: '0.9rem' }}>
            <CreditCard2Back className="me-1" />
            Credit: {stats.balance.credit} SMS
          </Badge>
        )}
      </div>

      {alert && (
        <Alert color={alert.type} toggle={() => setAlert(null)} className="mb-3">
          {alert.message}
        </Alert>
      )}

      {/* Tabs */}
      <Nav tabs className="mb-3">
        {[
          { id: 'send', label: 'Envoyer SMS', icon: <Send size={14} /> },
          { id: 'history', label: 'Historique', icon: <Clock size={14} /> },
          { id: 'stats', label: 'Statistiques', icon: <People size={14} /> },
          { id: 'settings', label: 'Configuration', icon: <Gear size={14} /> },
        ].map(tab => (
          <NavItem key={tab.id}>
            <NavLink
              className={activeTab === tab.id ? 'active' : ''}
              onClick={() => setActiveTab(tab.id)}
              style={{ cursor: 'pointer' }}
            >
              {tab.icon} <span className="ms-1">{tab.label}</span>
            </NavLink>
          </NavItem>
        ))}
      </Nav>

      <TabContent activeTab={activeTab}>
        {/* TAB: Send SMS */}
        <TabPane tabId="send">
          <Row>
            <Col md={8}>
              <Card>
                <CardBody>
                  <h5 className="mb-3">Nouveau message SMS</h5>

                  <FormGroup>
                    <Label>Envoyer a</Label>
                    <Input type="select" value={sendTo} onChange={e => setSendTo(e.target.value)}>
                      <option value="all">Tous les parents</option>
                      <option value="class">Une classe</option>
                      <option value="student">Un eleve</option>
                    </Input>
                  </FormGroup>

                  {sendTo === 'class' && (
                    <FormGroup>
                      <Label>Classe</Label>
                      <Input type="select" value={selectedClass} onChange={e => setSelectedClass(e.target.value)}>
                        <option value="">-- Choisir une classe --</option>
                        {classes.map(c => (
                          <option key={c.id} value={c.id}>
                            {c.school_class?.name} - {c.name} ({c.student_count} eleves)
                          </option>
                        ))}
                      </Input>
                    </FormGroup>
                  )}

                  {sendTo === 'student' && (
                    <>
                      <FormGroup>
                        <Label>Classe</Label>
                        <Input type="select" value={selectedClass} onChange={e => { setSelectedClass(e.target.value); setSelectedStudent(''); }}>
                          <option value="">-- Choisir une classe --</option>
                          {classes.map(c => (
                            <option key={c.id} value={c.id}>
                              {c.school_class?.name} - {c.name}
                            </option>
                          ))}
                        </Input>
                      </FormGroup>
                      {selectedClass && (
                        <FormGroup>
                          <Label>Eleve</Label>
                          <Input type="select" value={selectedStudent} onChange={e => setSelectedStudent(e.target.value)}>
                            <option value="">-- Choisir un eleve --</option>
                            {students.map(s => (
                              <option key={s.id} value={s.id}>
                                {s.first_name} {s.last_name} - {s.parent_phone || s.mother_phone || 'Pas de contact'}
                              </option>
                            ))}
                          </Input>
                        </FormGroup>
                      )}
                    </>
                  )}

                  <FormGroup>
                    <Label>Message</Label>
                    <Input
                      type="textarea"
                      rows={5}
                      value={message}
                      onChange={e => setMessage(e.target.value)}
                      maxLength={640}
                      placeholder="Saisissez votre message ici...&#10;&#10;Variables: {eleve} = nom de l'eleve, {classe} = nom de la classe"
                    />
                    <small className={`text-${charCount > 480 ? 'danger' : 'muted'}`}>
                      {charCount}/640 caracteres | {smsCount} SMS par destinataire
                    </small>
                  </FormGroup>

                  <Button
                    color="primary"
                    onClick={handleSend}
                    disabled={sending || !message.trim()}
                    className="d-flex align-items-center gap-2"
                  >
                    {sending ? <Spinner size="sm" /> : <Send />}
                    {sending ? 'Envoi en cours...' : 'Envoyer les SMS'}
                  </Button>
                </CardBody>
              </Card>
            </Col>

            <Col md={4}>
              <Card className="mb-3">
                <CardBody>
                  <h6>Modeles rapides</h6>
                  {[
                    {
                      label: 'Rappel de paiement',
                      text: 'CPB DOUALA - Cher parent de {eleve} ({classe}), nous vous rappelons que les frais de scolarite sont attendus. Merci de regulariser. La Direction.'
                    },
                    {
                      label: 'Reunion parents',
                      text: 'CPB DOUALA - Cher parent de {eleve}, une reunion de parents est prevue le [DATE] a [HEURE]. Votre presence est requise. La Direction.'
                    },
                    {
                      label: 'Information generale',
                      text: 'CPB DOUALA - Information importante: [VOTRE MESSAGE]. Cordialement, La Direction.'
                    },
                  ].map((tpl, i) => (
                    <Button
                      key={i}
                      size="sm"
                      color="outline-secondary"
                      className="d-block w-100 text-start mb-2"
                      onClick={() => setMessage(tpl.text)}
                    >
                      {tpl.label}
                    </Button>
                  ))}
                </CardBody>
              </Card>

              <Card>
                <CardBody>
                  <h6>Info</h6>
                  <ul className="small mb-0" style={{ paddingLeft: '1.2rem' }}>
                    <li>1 SMS = 160 caracteres max</li>
                    <li>Les SMS sont envoyes au pere ET a la mere si les 2 numeros sont renseignes</li>
                    <li>Utilisez <code>{'{eleve}'}</code> et <code>{'{classe}'}</code> pour personnaliser</li>
                    <li>Rappels automatiques: 1 jour avant chaque deadline de tranche</li>
                  </ul>
                </CardBody>
              </Card>
            </Col>
          </Row>
        </TabPane>

        {/* TAB: History */}
        <TabPane tabId="history">
          <Card>
            <CardBody>
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h5 className="mb-0">Historique des SMS</h5>
                <Button size="sm" color="outline-primary" onClick={loadHistory}>
                  <ArrowClockwise /> Actualiser
                </Button>
              </div>

              <Row className="mb-3">
                <Col md={3}>
                  <Input type="select" bsSize="sm" value={historyFilter.type}
                    onChange={e => setHistoryFilter(p => ({ ...p, type: e.target.value }))}>
                    <option value="">Tous les types</option>
                    <option value="manual">Manuel</option>
                    <option value="payment_reminder">Rappel paiement</option>
                    <option value="test">Test</option>
                  </Input>
                </Col>
                <Col md={3}>
                  <Input type="select" bsSize="sm" value={historyFilter.status}
                    onChange={e => setHistoryFilter(p => ({ ...p, status: e.target.value }))}>
                    <option value="">Tous les statuts</option>
                    <option value="success">Succes</option>
                    <option value="failed">Echoue</option>
                  </Input>
                </Col>
              </Row>

              {historyLoading ? (
                <div className="text-center py-4"><Spinner /></div>
              ) : history.length === 0 ? (
                <p className="text-muted text-center py-4">Aucun SMS envoye</p>
              ) : (
                <div style={{ maxHeight: '500px', overflowY: 'auto' }}>
                  <Table size="sm" striped hover responsive>
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Destinataire</th>
                        <th>Eleve</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Message</th>
                      </tr>
                    </thead>
                    <tbody>
                      {history.map(log => (
                        <tr key={log.id}>
                          <td className="text-nowrap small">{new Date(log.created_at).toLocaleString('fr-FR')}</td>
                          <td className="small">{log.phone}</td>
                          <td className="small">{log.student ? `${log.student.first_name} ${log.student.last_name}` : '-'}</td>
                          <td><Badge color="secondary" className="small">{typeLabels[log.type] || log.type}</Badge></td>
                          <td>
                            {log.status === 'success' ? (
                              <Badge color="success"><CheckCircle size={12} /> OK</Badge>
                            ) : (
                              <Badge color="danger"><XCircle size={12} /> Echec</Badge>
                            )}
                          </td>
                          <td className="small" style={{ maxWidth: '300px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                            {log.message}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </div>
              )}
            </CardBody>
          </Card>
        </TabPane>

        {/* TAB: Stats */}
        <TabPane tabId="stats">
          {statsLoading ? (
            <div className="text-center py-4"><Spinner /></div>
          ) : stats ? (
            <>
              <Row className="mb-4">
                {[
                  { label: 'Total SMS', value: stats.total, color: 'primary' },
                  { label: 'Envoyes', value: stats.success, color: 'success' },
                  { label: 'Echoues', value: stats.failed, color: 'danger' },
                  { label: "Aujourd'hui", value: stats.today, color: 'info' },
                ].map((s, i) => (
                  <Col md={3} key={i}>
                    <Card>
                      <CardBody className="text-center">
                        <h3 className={`text-${s.color} mb-1`}>{s.value}</h3>
                        <small className="text-muted">{s.label}</small>
                      </CardBody>
                    </Card>
                  </Col>
                ))}
              </Row>

              {stats.balance?.success && (
                <Card className="mb-3">
                  <CardBody>
                    <h5 className="d-flex align-items-center gap-2">
                      <CreditCard2Back /> Solde Nexah
                    </h5>
                    <Row>
                      <Col md={4}>
                        <h2 className="text-primary">{stats.balance.credit}</h2>
                        <small className="text-muted">SMS restants</small>
                      </Col>
                      <Col md={4}>
                        <p className="mb-0">{stats.balance.account_expiry || '-'}</p>
                        <small className="text-muted">Expiration compte</small>
                      </Col>
                      <Col md={4}>
                        <p className="mb-0">{stats.balance.balance_expiry || '-'}</p>
                        <small className="text-muted">Expiration credit</small>
                      </Col>
                    </Row>
                  </CardBody>
                </Card>
              )}

              {stats.by_type?.length > 0 && (
                <Card>
                  <CardBody>
                    <h5>Par type</h5>
                    <Table size="sm" striped>
                      <thead>
                        <tr><th>Type</th><th>Total</th><th>Succes</th><th>Taux</th></tr>
                      </thead>
                      <tbody>
                        {stats.by_type.map((t, i) => (
                          <tr key={i}>
                            <td>{typeLabels[t.type] || t.type}</td>
                            <td>{t.count}</td>
                            <td>{t.success_count}</td>
                            <td>{t.count > 0 ? Math.round((t.success_count / t.count) * 100) : 0}%</td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  </CardBody>
                </Card>
              )}
            </>
          ) : (
            <p className="text-muted text-center py-4">Chargement...</p>
          )}
        </TabPane>

        {/* TAB: Settings */}
        <TabPane tabId="settings">
          <Row>
            <Col md={6}>
              <Card>
                <CardBody>
                  <h5 className="mb-3">Configuration Nexah SMS</h5>

                  <FormGroup check className="mb-3">
                    <Input
                      type="checkbox"
                      id="smsEnabled"
                      checked={settings.sms_notifications_enabled}
                      onChange={e => setSettings(p => ({ ...p, sms_notifications_enabled: e.target.checked }))}
                    />
                    <Label check htmlFor="smsEnabled" className="fw-bold">
                      Activer les notifications SMS
                    </Label>
                  </FormGroup>

                  <FormGroup>
                    <Label>Utilisateur Nexah</Label>
                    <Input
                      type="text"
                      value={settings.nexah_sms_user}
                      onChange={e => setSettings(p => ({ ...p, nexah_sms_user: e.target.value }))}
                      placeholder="Votre identifiant Nexah"
                    />
                  </FormGroup>

                  <FormGroup>
                    <Label>Mot de passe Nexah</Label>
                    <Input
                      type="password"
                      value={settings.nexah_sms_password}
                      onChange={e => setSettings(p => ({ ...p, nexah_sms_password: e.target.value }))}
                      placeholder="Votre mot de passe Nexah"
                    />
                  </FormGroup>

                  <FormGroup>
                    <Label>Sender ID</Label>
                    <Input
                      type="text"
                      value={settings.nexah_sms_sender_id}
                      onChange={e => setSettings(p => ({ ...p, nexah_sms_sender_id: e.target.value }))}
                      placeholder="CPB DOUALA"
                    />
                    <small className="text-muted">Nom qui apparait comme expediteur (max 11 caracteres)</small>
                  </FormGroup>

                  <div className="d-flex gap-2">
                    <Button color="primary" onClick={handleSaveSettings} disabled={settingsLoading}>
                      {settingsLoading ? <Spinner size="sm" /> : 'Sauvegarder'}
                    </Button>
                    <Button color="outline-info" onClick={handleTestConnection} disabled={loading}>
                      {loading ? <Spinner size="sm" /> : 'Tester la connexion'}
                    </Button>
                    <Button color="outline-success" onClick={() => setTestModal(true)}>
                      Envoyer un test
                    </Button>
                  </div>
                </CardBody>
              </Card>
            </Col>

            <Col md={6}>
              <Card>
                <CardBody>
                  <h5>Rappels automatiques</h5>
                  <p className="text-muted small">
                    Le systeme envoie automatiquement des SMS de rappel aux parents dont la tranche de paiement
                    arrive a echeance dans 1 jour. Les rappels sont envoyes chaque matin a 7h00.
                  </p>
                  <p className="text-muted small">
                    Les deadlines sont configurees dans <strong>Gestion des Tranches</strong> (champ "Date limite").
                    Seuls les parents dont l'enfant n'a pas encore paye la tranche concernee recevront le SMS.
                  </p>
                  <hr />
                  <h6>Commande manuelle</h6>
                  <code className="d-block bg-light p-2 rounded small">
                    php artisan sms:payment-reminders --dry-run
                  </code>
                  <small className="text-muted">Simuler les rappels sans envoyer</small>
                  <br />
                  <code className="d-block bg-light p-2 rounded small mt-2">
                    php artisan sms:payment-reminders --days=3
                  </code>
                  <small className="text-muted">Rappeler 3 jours avant au lieu de 1</small>
                </CardBody>
              </Card>
            </Col>
          </Row>
        </TabPane>
      </TabContent>

      {/* Test SMS Modal */}
      <Modal isOpen={testModal} toggle={() => setTestModal(false)}>
        <ModalHeader toggle={() => setTestModal(false)}>Envoyer un SMS de test</ModalHeader>
        <ModalBody>
          <FormGroup>
            <Label>Numero de telephone</Label>
            <Input
              type="text"
              value={testPhone}
              onChange={e => setTestPhone(e.target.value)}
              placeholder="6XXXXXXXX"
            />
            <small className="text-muted">Numero camerounais (le prefixe 237 est ajoute automatiquement)</small>
          </FormGroup>
        </ModalBody>
        <ModalFooter>
          <Button color="secondary" onClick={() => setTestModal(false)}>Annuler</Button>
          <Button color="primary" onClick={handleSendTest} disabled={testing || !testPhone.trim()}>
            {testing ? <Spinner size="sm" /> : 'Envoyer le test'}
          </Button>
        </ModalFooter>
      </Modal>
    </Container>
  );
};

export default SmsManagement;
