import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Form, Button, Alert, Spinner } from 'react-bootstrap';
import { GearFill, Save, ArrowCounterclockwise, Eye } from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import { host } from '../../utils/fetch';

const CardLayoutSettings = () => {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [settings, setSettings] = useState({
        photo_top: '11',
        photo_right: '8',
        photo_width: '24',
        photo_height: '24',
        info_top: '20.5',
        info_left: '33',
        info_font_size: '4.5',
        info_line_height: '3.8',
        info_max_width: '40',
        info_letter_spacing: '0',
        qr_bottom: '4',
        qr_left_offset: '0',
        qr_width: '14',
        qr_height: '14',
    });

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        try {
            setLoading(true);
            setError(null);

            const response = await secureApiEndpoints.request('/card-layout-settings');

            if (response.success && response.data) {
                // response.data est déjà un objet key-value
                setSettings(prevSettings => ({ ...prevSettings, ...response.data }));
            }
        } catch (err) {
            console.error('Erreur chargement paramètres:', err);
            setError(err.message || 'Erreur de connexion');
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (key, value) => {
        setSettings(prev => ({ ...prev, [key]: value }));
    };

    const handleSave = async () => {
        try {
            setSaving(true);
            setError(null);
            setSuccess(null);

            // Convertir l'objet en tableau pour l'API
            const settingsArray = Object.keys(settings).map(key => ({
                key: key,
                value: settings[key]
            }));

            const response = await secureApiEndpoints.request(
                '/card-layout-settings/update',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ settings: settingsArray })
                }
            );

            if (response.success) {
                setSuccess('Paramètres enregistrés avec succès !');
                setTimeout(() => setSuccess(null), 3000);
            } else {
                throw new Error(response.message || 'Erreur lors de l\'enregistrement');
            }
        } catch (err) {
            console.error('Erreur sauvegarde:', err);
            setError(err.message || 'Erreur lors de l\'enregistrement');
        } finally {
            setSaving(false);
        }
    };

    const handleReset = async () => {
        if (!window.confirm('Réinitialiser tous les paramètres aux valeurs par défaut ?')) {
            return;
        }

        try {
            setSaving(true);
            setError(null);
            setSuccess(null);

            const response = await secureApiEndpoints.request(
                '/card-layout-settings/reset',
                {
                    method: 'POST'
                }
            );

            if (response.success) {
                setSuccess('Paramètres réinitialisés avec succès !');
                await loadSettings(); // Recharger les paramètres
            } else {
                throw new Error(response.message || 'Erreur lors de la réinitialisation');
            }
        } catch (err) {
            console.error('Erreur réinitialisation:', err);
            setError(err.message || 'Erreur lors de la réinitialisation');
        } finally {
            setSaving(false);
        }
    };

    const openPreview = () => {
        // Ouvrir une carte en prévisualisation (utiliser un élève exemple ou le premier disponible)
        alert('Ouvrez la prévisualisation depuis la page de gestion des élèves pour voir les changements en temps réel.');
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <Spinner animation="border" variant="primary" />
                <span className="ms-3">Chargement des paramètres...</span>
            </div>
        );
    }

    return (
        <div className="container-fluid py-4">
            <div className="mb-4">
                <h2>
                    <GearFill className="me-2" />
                    Configuration des Cartes d'Identité
                </h2>
                <p className="text-muted">
                    Ajustez la position des éléments sur les cartes d'identité des élèves
                </p>
            </div>

            {error && (
                <Alert variant="danger" dismissible onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}

            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess(null)}>
                    {success}
                </Alert>
            )}

            <Row>
                {/* Colonne gauche - Aperçu */}
                <Col lg={4}>
                    <Card className="mb-4">
                        <Card.Body>
                            <h5 className="mb-3">Aperçu de la carte</h5>
                            <div
                                style={{
                                    width: '100%',
                                    maxWidth: '320px',
                                    aspectRatio: '85.6 / 54',
                                    border: '2px solid #ddd',
                                    borderRadius: '8px',
                                    backgroundColor: '#fff',
                                    position: 'relative',
                                    margin: '0 auto',
                                    overflow: 'hidden',
                                    boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
                                }}
                            >
                                {/* Image de fond */}
                                <div style={{
                                    position: 'absolute',
                                    top: 0,
                                    left: 0,
                                    width: '100%',
                                    height: '100%',
                                    backgroundColor: '#e9ecef',
                                    backgroundImage: `url(${host}/images/carte-template.png)`,
                                    backgroundSize: 'cover',
                                    backgroundPosition: 'center'
                                }} />

                                {/* Photo élève - cercle avec position dynamique */}
                                <div style={{
                                    position: 'absolute',
                                    top: `${(parseFloat(settings.photo_top) / 54) * 100}%`,
                                    right: `${(parseFloat(settings.photo_right) / 85.6) * 100}%`,
                                    width: `${(parseFloat(settings.photo_width) / 85.6) * 100}%`,
                                    height: `${(parseFloat(settings.photo_height) / 54) * 100}%`,
                                    borderRadius: '50%',
                                    border: '1px solid #2d5a3d',
                                    backgroundColor: '#f0f0f0',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    fontSize: '10px',
                                    color: '#666',
                                    overflow: 'hidden'
                                }}>
                                    Photo
                                </div>

                                {/* Informations texte */}
                                <div style={{
                                    position: 'absolute',
                                    top: `${(parseFloat(settings.info_top) / 54) * 100}%`,
                                    left: `${(parseFloat(settings.info_left) / 85.6) * 100}%`,
                                    fontSize: `${parseFloat(settings.info_font_size) * 0.8}px`,
                                    lineHeight: `${parseFloat(settings.info_line_height) * 0.8}px`,
                                    letterSpacing: `${settings.info_letter_spacing}px`,
                                    maxWidth: `${(parseFloat(settings.info_max_width) / 85.6) * 100}%`,
                                    color: '#000',
                                    fontWeight: '500'
                                }}>
                                    <div>NOM Prénom</div>
                                    <div>MAT123456</div>
                                    <div>Classe 6ème A</div>
                                    <div style={{ fontSize: '0.8em' }}>01/01/2010</div>
                                    <div style={{ fontSize: '0.8em' }}>+237 6XX XX XX XX</div>
                                    <div style={{ fontSize: '0.8em' }}>2024-2025</div>
                                </div>

                                {/* QR Code */}
                                <div style={{
                                    position: 'absolute',
                                    bottom: `${(parseFloat(settings.qr_bottom) / 54) * 100}%`,
                                    left: `calc(50% + ${(parseFloat(settings.qr_left_offset) / 85.6) * 100}%)`,
                                    transform: 'translateX(-50%)',
                                    width: `${(parseFloat(settings.qr_width) / 85.6) * 100}%`,
                                    height: `${(parseFloat(settings.qr_height) / 54) * 100}%`,
                                    backgroundColor: '#000',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    fontSize: '8px',
                                    color: '#fff',
                                    border: '1px solid #000'
                                }}>
                                    QR
                                </div>
                            </div>
                            <div className="mt-3 text-center">
                                <small className="text-muted">Format carte bancaire: 85.6mm × 54mm</small>
                            </div>
                            <div className="mt-3">
                                <Button
                                    variant="outline-primary"
                                    size="sm"
                                    className="w-100"
                                    onClick={openPreview}
                                >
                                    <Eye className="me-2" />
                                    Prévisualiser avec un élève
                                </Button>
                            </div>
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Body>
                            <div className="d-grid gap-2">
                                <Button
                                    variant="success"
                                    onClick={handleSave}
                                    disabled={saving}
                                >
                                    {saving ? (
                                        <>
                                            <Spinner animation="border" size="sm" className="me-2" />
                                            Enregistrement...
                                        </>
                                    ) : (
                                        <>
                                            <Save className="me-2" />
                                            Enregistrer les paramètres
                                        </>
                                    )}
                                </Button>
                                <Button
                                    variant="outline-secondary"
                                    onClick={handleReset}
                                    disabled={saving}
                                >
                                    <ArrowCounterclockwise className="me-2" />
                                    Réinitialiser
                                </Button>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>

                {/* Colonne droite - Paramètres */}
                <Col lg={8}>
                    {/* Photo de l'élève */}
                    <Card className="mb-4">
                        <Card.Header className="bg-primary text-white">
                            <h6 className="mb-0">Photo de l'élève</h6>
                        </Card.Header>
                        <Card.Body>
                            <Row>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Position Top: <strong>{settings.photo_top} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="0"
                                            max="30"
                                            step="0.5"
                                            value={settings.photo_top}
                                            onChange={(e) => handleChange('photo_top', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Position Right: <strong>{settings.photo_right} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="0"
                                            max="30"
                                            step="0.5"
                                            value={settings.photo_right}
                                            onChange={(e) => handleChange('photo_right', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Largeur: <strong>{settings.photo_width} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="15"
                                            max="35"
                                            step="0.5"
                                            value={settings.photo_width}
                                            onChange={(e) => handleChange('photo_width', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Hauteur: <strong>{settings.photo_height} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="15"
                                            max="35"
                                            step="0.5"
                                            value={settings.photo_height}
                                            onChange={(e) => handleChange('photo_height', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>

                    {/* Informations texte */}
                    <Card className="mb-4">
                        <Card.Header className="bg-success text-white">
                            <h6 className="mb-0">Informations texte (Nom, Prénom, Matricule...)</h6>
                        </Card.Header>
                        <Card.Body>
                            <Row>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Position Top: <strong>{settings.info_top} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="0"
                                            max="40"
                                            step="0.5"
                                            value={settings.info_top}
                                            onChange={(e) => handleChange('info_top', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Position Left: <strong>{settings.info_left} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="0"
                                            max="60"
                                            step="0.5"
                                            value={settings.info_left}
                                            onChange={(e) => handleChange('info_left', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Taille de police: <strong>{settings.info_font_size} pt</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="3"
                                            max="8"
                                            step="0.1"
                                            value={settings.info_font_size}
                                            onChange={(e) => handleChange('info_font_size', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Espacement lignes: <strong>{settings.info_line_height} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="2.5"
                                            max="6"
                                            step="0.1"
                                            value={settings.info_line_height}
                                            onChange={(e) => handleChange('info_line_height', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Largeur max: <strong>{settings.info_max_width} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="20"
                                            max="60"
                                            step="1"
                                            value={settings.info_max_width}
                                            onChange={(e) => handleChange('info_max_width', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Espacement lettres: <strong>{settings.info_letter_spacing} px</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="-2"
                                            max="2"
                                            step="0.1"
                                            value={settings.info_letter_spacing}
                                            onChange={(e) => handleChange('info_letter_spacing', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>

                    {/* Code QR */}
                    <Card className="mb-4">
                        <Card.Header className="bg-warning text-dark">
                            <h6 className="mb-0">Code QR</h6>
                        </Card.Header>
                        <Card.Body>
                            <Row>
                                <Col md={4}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Position Bottom: <strong>{settings.qr_bottom} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="0"
                                            max="20"
                                            step="0.5"
                                            value={settings.qr_bottom}
                                            onChange={(e) => handleChange('qr_bottom', e.target.value)}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col md={4}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Décalage Horizontal: <strong>{settings.qr_left_offset} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="-30"
                                            max="30"
                                            step="0.5"
                                            value={settings.qr_left_offset}
                                            onChange={(e) => handleChange('qr_left_offset', e.target.value)}
                                        />
                                        <Form.Text className="text-muted">
                                            Gauche (-) ← → Droite (+)
                                        </Form.Text>
                                    </Form.Group>
                                </Col>
                                <Col md={4}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>
                                            Taille QR: <strong>{settings.qr_width} mm</strong>
                                        </Form.Label>
                                        <Form.Range
                                            min="10"
                                            max="25"
                                            step="0.5"
                                            value={settings.qr_width}
                                            onChange={(e) => {
                                                handleChange('qr_width', e.target.value);
                                                handleChange('qr_height', e.target.value); // Même valeur
                                            }}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </div>
    );
};

export default CardLayoutSettings;
