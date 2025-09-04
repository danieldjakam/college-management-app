/**
 * Panneau d'administration pour configurer les zones géolocalisées
 * Version 2.0 - Utilise l'API backend au lieu du localStorage
 */

import React, { useState, useEffect } from 'react';
import { 
    Container, 
    Card, 
    Button, 
    Form, 
    Table, 
    Modal, 
    Alert, 
    Badge,
    Row,
    Col,
    InputGroup,
    Spinner
} from 'react-bootstrap';
import { 
    Map, 
    PlusCircle, 
    PencilSquare, 
    Trash, 
    GeoAlt, 
    CheckCircle,
    XCircle,
    Bullseye,
    Save,
    Eye
} from 'react-bootstrap-icons';
import { secureApiEndpoints } from '../../utils/apiMigration';
import geolocationService from '../../services/geolocationService';
import Swal from 'sweetalert2';

const GeolocationZoneSettingsV2 = () => {
    const [zones, setZones] = useState([]);
    const [showZoneModal, setShowZoneModal] = useState(false);
    const [editingZone, setEditingZone] = useState(null);
    const [currentLocation, setCurrentLocation] = useState(null);
    const [testingZone, setTestingZone] = useState(null);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    const [zoneForm, setZoneForm] = useState({
        name: '',
        description: '',
        latitude: '',
        longitude: '',
        radius: 100,
        enabled: true
    });

    useEffect(() => {
        loadZones();
        getCurrentLocationForAdmin();
    }, []);

    const loadZones = async () => {
        try {
            setLoading(true);
            const response = await secureApiEndpoints.geolocationZones.getAll();
            
            if (response.success) {
                setZones(response.data || []);
            } else {
                console.error('Erreur chargement zones:', response.message);
                Swal.fire('Erreur', response.message, 'error');
            }
        } catch (error) {
            console.error('Erreur chargement zones:', error);
            Swal.fire('Erreur', 'Impossible de charger les zones', 'error');
        } finally {
            setLoading(false);
        }
    };

    const getCurrentLocationForAdmin = async () => {
        try {
            const position = await geolocationService.getCurrentPosition();
            setCurrentLocation(position);
        } catch (error) {
            console.warn('Position non disponible pour l\'admin:', error);
        }
    };

    const resetForm = () => {
        setZoneForm({
            name: '',
            description: '',
            latitude: '',
            longitude: '',
            radius: 100,
            enabled: true
        });
        setEditingZone(null);
    };

    const openCreateModal = () => {
        resetForm();
        if (currentLocation) {
            setZoneForm(prev => ({
                ...prev,
                latitude: currentLocation.latitude.toFixed(6),
                longitude: currentLocation.longitude.toFixed(6)
            }));
        }
        setShowZoneModal(true);
    };

    const openEditModal = (zone) => {
        setZoneForm({
            name: zone.name,
            description: zone.description || '',
            latitude: zone.latitude,
            longitude: zone.longitude,
            radius: zone.radius,
            enabled: zone.enabled
        });
        setEditingZone(zone);
        setShowZoneModal(true);
    };

    const handleSaveZone = async () => {
        try {
            // Validation côté client
            if (!zoneForm.name.trim()) {
                Swal.fire('Erreur', 'Le nom de la zone est requis', 'error');
                return;
            }

            if (!zoneForm.latitude || !zoneForm.longitude) {
                Swal.fire('Erreur', 'Les coordonnées sont requises', 'error');
                return;
            }

            if (zoneForm.radius < 1 || zoneForm.radius > 10000) {
                Swal.fire('Erreur', 'Le rayon doit être entre 1 et 10000 mètres', 'error');
                return;
            }

            setSaving(true);

            const zoneData = {
                name: zoneForm.name.trim(),
                description: zoneForm.description.trim(),
                latitude: parseFloat(zoneForm.latitude),
                longitude: parseFloat(zoneForm.longitude),
                radius: parseInt(zoneForm.radius),
                enabled: zoneForm.enabled
            };

            let response;
            if (editingZone) {
                response = await secureApiEndpoints.geolocationZones.update(editingZone.id, zoneData);
            } else {
                response = await secureApiEndpoints.geolocationZones.create(zoneData);
            }

            if (response.success) {
                await loadZones();
                await geolocationService.loadZoneConfig(); // Recharger les zones dans le service
                setShowZoneModal(false);
                resetForm();
                
                Swal.fire(
                    'Succès', 
                    editingZone ? 'Zone modifiée avec succès' : 'Zone créée avec succès', 
                    'success'
                );
            } else {
                throw new Error(response.message || 'Erreur lors de la sauvegarde');
            }

        } catch (error) {
            console.error('Erreur sauvegarde zone:', error);
            Swal.fire('Erreur', error.message, 'error');
        } finally {
            setSaving(false);
        }
    };

    const deleteZone = (zone) => {
        Swal.fire({
            title: 'Supprimer la zone ?',
            text: `Voulez-vous vraiment supprimer "${zone.name}" ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await secureApiEndpoints.geolocationZones.delete(zone.id);
                    
                    if (response.success) {
                        await loadZones();
                        await geolocationService.loadZoneConfig(); // Recharger les zones dans le service
                        Swal.fire('Supprimé !', 'La zone a été supprimée.', 'success');
                    } else {
                        throw new Error(response.message);
                    }
                } catch (error) {
                    console.error('Erreur suppression zone:', error);
                    Swal.fire('Erreur', error.message, 'error');
                }
            }
        });
    };

    const toggleZone = async (zone) => {
        try {
            const response = await secureApiEndpoints.geolocationZones.toggleStatus(zone.id);
            
            if (response.success) {
                await loadZones();
                await geolocationService.loadZoneConfig(); // Recharger les zones dans le service
                
                Swal.fire(
                    'Mise à jour',
                    `Zone ${response.data.enabled ? 'activée' : 'désactivée'}`,
                    'success'
                );
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Erreur toggle zone:', error);
            Swal.fire('Erreur', error.message, 'error');
        }
    };

    const testZone = async (zone) => {
        try {
            setTestingZone(zone.id);
            const position = await geolocationService.getCurrentPosition();
            
            const response = await secureApiEndpoints.geolocationZones.validatePosition({
                latitude: position.latitude,
                longitude: position.longitude
            });

            if (response.success) {
                const validation = response.data;
                const zoneResult = validation.zones.find(z => z.id === zone.id);
                
                if (zoneResult) {
                    const message = zoneResult.isInZone 
                        ? `✅ Vous êtes dans la zone "${zone.name}" (${zoneResult.distance}m)`
                        : `❌ Vous êtes hors de la zone "${zone.name}" (${zoneResult.distance}m)`;
                    
                    Swal.fire(
                        'Test de Zone',
                        message,
                        zoneResult.isInZone ? 'success' : 'warning'
                    );
                } else {
                    Swal.fire('Erreur', 'Zone non trouvée dans la validation', 'error');
                }
            } else {
                throw new Error(response.message);
            }
            
        } catch (error) {
            console.error('Erreur test zone:', error);
            Swal.fire('Erreur', error.message, 'error');
        } finally {
            setTestingZone(null);
        }
    };

    const useCurrentLocation = async () => {
        try {
            const position = await geolocationService.getCurrentPosition();
            setZoneForm(prev => ({
                ...prev,
                latitude: position.latitude.toFixed(6),
                longitude: position.longitude.toFixed(6)
            }));
            setCurrentLocation(position);
        } catch (error) {
            Swal.fire('Erreur', 'Impossible d\'obtenir la position actuelle', 'error');
        }
    };

    return (
        <Container fluid>
            <Row className="mb-4">
                <Col>
                    <h3>
                        <Map className="me-2" />
                        Gestion des Zones Géolocalisées
                    </h3>
                    <p className="text-muted">
                        Configurez les zones autorisées pour le scan de présence. 
                        Version 2.0 - Base de données.
                    </p>
                </Col>
            </Row>

            {/* Informations position actuelle */}
            {currentLocation && (
                <Alert variant="info" className="mb-3">
                    <GeoAlt className="me-2" />
                    <strong>Position actuelle:</strong> {currentLocation.latitude.toFixed(6)}, {currentLocation.longitude.toFixed(6)} 
                    (Précision: {currentLocation.accuracy}m)
                </Alert>
            )}

            <Row>
                <Col lg={12}>
                    <Card>
                        <Card.Header className="d-flex justify-content-between align-items-center">
                            <h5 className="mb-0">Zones Configurées</h5>
                            <Button variant="primary" onClick={openCreateModal}>
                                <PlusCircle className="me-1" />
                                Nouvelle Zone
                            </Button>
                        </Card.Header>
                        <Card.Body>
                            {loading ? (
                                <div className="text-center p-4">
                                    <Spinner animation="border" />
                                    <p className="mt-2">Chargement des zones...</p>
                                </div>
                            ) : zones.length === 0 ? (
                                <Alert variant="warning">
                                    Aucune zone configurée. Créez votre première zone pour commencer.
                                </Alert>
                            ) : (
                                <Table responsive striped hover>
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Description</th>
                                            <th>Coordonnées</th>
                                            <th>Rayon</th>
                                            <th>Statut</th>
                                            <th>Créé par</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {zones.map((zone) => (
                                            <tr key={zone.id}>
                                                <td><strong>{zone.name}</strong></td>
                                                <td>{zone.description || '-'}</td>
                                                <td>
                                                    <small>
                                                        {parseFloat(zone.latitude).toFixed(6)}, {parseFloat(zone.longitude).toFixed(6)}
                                                    </small>
                                                </td>
                                                <td>{zone.radius}m</td>
                                                <td>
                                                    <Badge bg={zone.enabled ? 'success' : 'secondary'}>
                                                        {zone.enabled ? 'Activé' : 'Désactivé'}
                                                    </Badge>
                                                </td>
                                                <td>
                                                    <small>{zone.creator?.name || 'Système'}</small>
                                                </td>
                                                <td>
                                                    <div className="d-flex gap-1">
                                                        <Button
                                                            variant="outline-primary"
                                                            size="sm"
                                                            onClick={() => openEditModal(zone)}
                                                            title="Modifier"
                                                        >
                                                            <PencilSquare />
                                                        </Button>
                                                        <Button
                                                            variant="outline-info"
                                                            size="sm"
                                                            onClick={() => testZone(zone)}
                                                            disabled={testingZone === zone.id}
                                                            title="Tester la zone"
                                                        >
                                                            {testingZone === zone.id ? (
                                                                <Spinner animation="border" size="sm" />
                                                            ) : (
                                                                <Eye />
                                                            )}
                                                        </Button>
                                                        <Button
                                                            variant={zone.enabled ? 'outline-warning' : 'outline-success'}
                                                            size="sm"
                                                            onClick={() => toggleZone(zone)}
                                                            title={zone.enabled ? 'Désactiver' : 'Activer'}
                                                        >
                                                            {zone.enabled ? <XCircle /> : <CheckCircle />}
                                                        </Button>
                                                        <Button
                                                            variant="outline-danger"
                                                            size="sm"
                                                            onClick={() => deleteZone(zone)}
                                                            title="Supprimer"
                                                        >
                                                            <Trash />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </Table>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Modal de création/édition */}
            <Modal show={showZoneModal} onHide={() => setShowZoneModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        {editingZone ? 'Modifier la Zone' : 'Nouvelle Zone'}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Nom de la zone *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={zoneForm.name}
                                        onChange={(e) => setZoneForm(prev => ({ ...prev, name: e.target.value }))}
                                        placeholder="Ex: Bureau Principal"
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Rayon (mètres) *</Form.Label>
                                    <Form.Control
                                        type="number"
                                        min="1"
                                        max="10000"
                                        value={zoneForm.radius}
                                        onChange={(e) => setZoneForm(prev => ({ ...prev, radius: parseInt(e.target.value) || 100 }))}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>

                        <Form.Group className="mb-3">
                            <Form.Label>Description</Form.Label>
                            <Form.Control
                                as="textarea"
                                rows={2}
                                value={zoneForm.description}
                                onChange={(e) => setZoneForm(prev => ({ ...prev, description: e.target.value }))}
                                placeholder="Description optionnelle de la zone"
                            />
                        </Form.Group>

                        <Row>
                            <Col md={5}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Latitude *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={zoneForm.latitude}
                                        onChange={(e) => setZoneForm(prev => ({ ...prev, latitude: e.target.value }))}
                                        placeholder="Ex: 5.481275"
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={5}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Longitude *</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={zoneForm.longitude}
                                        onChange={(e) => setZoneForm(prev => ({ ...prev, longitude: e.target.value }))}
                                        placeholder="Ex: 10.459527"
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={2}>
                                <Form.Label>&nbsp;</Form.Label>
                                <div className="d-grid">
                                    <Button variant="outline-secondary" onClick={useCurrentLocation}>
                                        <Bullseye />
                                    </Button>
                                </div>
                            </Col>
                        </Row>

                        <Form.Group className="mb-3">
                            <Form.Check
                                type="switch"
                                id="zone-enabled"
                                label="Zone active"
                                checked={zoneForm.enabled}
                                onChange={(e) => setZoneForm(prev => ({ ...prev, enabled: e.target.checked }))}
                            />
                        </Form.Group>
                    </Form>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowZoneModal(false)}>
                        Annuler
                    </Button>
                    <Button 
                        variant="primary" 
                        onClick={handleSaveZone}
                        disabled={saving}
                    >
                        {saving ? (
                            <>
                                <Spinner animation="border" size="sm" className="me-1" />
                                Sauvegarde...
                            </>
                        ) : (
                            <>
                                <Save className="me-1" />
                                {editingZone ? 'Modifier' : 'Créer'}
                            </>
                        )}
                    </Button>
                </Modal.Footer>
            </Modal>
        </Container>
    );
};

export default GeolocationZoneSettingsV2;