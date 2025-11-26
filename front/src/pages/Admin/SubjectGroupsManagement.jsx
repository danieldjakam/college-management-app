import React, { useState, useEffect } from 'react';
import { Save, PencilSquare, XCircle, CheckCircle, InfoCircle, PlusCircle, Trash3, ExclamationTriangle } from 'react-bootstrap-icons';
import axios from 'axios';
import { host } from '../../utils/fetch';
import { authService } from '../../services/authService';

const API_URL = `${host}/api`;

export default function SubjectGroupsManagement() {
  const [groups, setGroups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);
  const [editingGroup, setEditingGroup] = useState(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [deleteConfirm, setDeleteConfirm] = useState(null);

  const [formData, setFormData] = useState({
    code: '',
    header: '',
    header_en: '',
    name: '',
    name_en: '',
    description: '',
    color: '#6b7280',
    bg_color: '#f9fafb'
  });

  // Palette de couleurs prédéfinies
  const colorPalette = [
    { name: 'Bleu', color: '#3b82f6', bgColor: '#eff6ff' },
    { name: 'Vert', color: '#10b981', bgColor: '#f0fdf4' },
    { name: 'Orange', color: '#f59e0b', bgColor: '#fffbeb' },
    { name: 'Violet', color: '#8b5cf6', bgColor: '#f5f3ff' },
    { name: 'Rose', color: '#ec4899', bgColor: '#fdf2f8' },
    { name: 'Cyan', color: '#0891b2', bgColor: '#ecfeff' },
    { name: 'Indigo', color: '#6366f1', bgColor: '#eef2ff' },
    { name: 'Rouge', color: '#ef4444', bgColor: '#fef2f2' },
    { name: 'Jaune', color: '#f59e0b', bgColor: '#fefce8' },
    { name: 'Gris', color: '#6b7280', bgColor: '#f9fafb' }
  ];

  useEffect(() => {
    fetchGroups();
  }, []);

  const fetchGroups = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await axios.get(`${API_URL}/subject-groups/groups`, {
        headers: authService.getHeaders()
      });

      if (response.data.success) {
        setGroups(response.data.data);
      }
    } catch (err) {
      console.error('Erreur lors du chargement:', err);
      setError('Erreur lors du chargement des groupes. Vérifiez votre connexion.');
    } finally {
      setLoading(false);
    }
  };

  const startEditing = (group) => {
    setEditingGroup(group.id);
    setFormData({
      code: group.code,
      header: group.header || `GROUPE ${group.code}`,
      header_en: group.header_en || `GROUP ${group.code}`,
      name: group.name,
      name_en: group.name_en || '',
      description: group.description || '',
      color: group.color || '#6b7280',
      bg_color: group.bg_color || '#f9fafb'
    });
  };

  const cancelEditing = () => {
    setEditingGroup(null);
    resetForm();
  };

  const resetForm = () => {
    setFormData({
      code: '',
      header: '',
      header_en: '',
      name: '',
      name_en: '',
      description: '',
      color: '#6b7280',
      bg_color: '#f9fafb'
    });
  };

  const openCreateModal = () => {
    resetForm();
    setShowCreateModal(true);
  };

  const closeCreateModal = () => {
    setShowCreateModal(false);
    resetForm();
  };

  const createGroup = async () => {
    if (!formData.code.trim() || !formData.header.trim() || !formData.name.trim()) {
      setError('Le code, l\'en-tête et le nom sont obligatoires');
      return;
    }

    try {
      setSaving(true);
      setError(null);

      const response = await axios.post(
        `${API_URL}/subject-groups/groups`,
        formData,
        {
          headers: authService.getHeaders()
        }
      );

      if (response.data.success) {
        setSuccess(true);
        closeCreateModal();
        setTimeout(() => setSuccess(false), 3000);
        fetchGroups();
      }
    } catch (err) {
      console.error('Erreur lors de la création:', err);
      setError(err.response?.data?.message || 'Erreur lors de la création. Veuillez réessayer.');
    } finally {
      setSaving(false);
    }
  };

  const saveGroup = async (groupId) => {
    if (!formData.header.trim() || !formData.name.trim()) {
      setError('L\'en-tête et le nom sont obligatoires');
      return;
    }

    try {
      setSaving(true);
      setError(null);

      const response = await axios.put(
        `${API_URL}/subject-groups/groups/${groupId}`,
        formData,
        {
          headers: authService.getHeaders()
        }
      );

      if (response.data.success) {
        setSuccess(true);
        setEditingGroup(null);
        setTimeout(() => setSuccess(false), 3000);
        fetchGroups();
      }
    } catch (err) {
      console.error('Erreur lors de l\'enregistrement:', err);
      setError(err.response?.data?.message || 'Erreur lors de l\'enregistrement. Veuillez réessayer.');
    } finally {
      setSaving(false);
    }
  };

  const confirmDelete = (group) => {
    setDeleteConfirm(group);
  };

  const deleteGroup = async () => {
    if (!deleteConfirm) return;

    try {
      setSaving(true);
      setError(null);

      const response = await axios.delete(
        `${API_URL}/subject-groups/groups/${deleteConfirm.id}`,
        {
          headers: authService.getHeaders()
        }
      );

      if (response.data.success) {
        setSuccess(true);
        setDeleteConfirm(null);
        setTimeout(() => setSuccess(false), 3000);
        fetchGroups();
      }
    } catch (err) {
      console.error('Erreur lors de la suppression:', err);
      setError(err.response?.data?.message || 'Erreur lors de la suppression. Veuillez réessayer.');
    } finally {
      setSaving(false);
    }
  };

  const selectColor = (color, bgColor) => {
    setFormData({ ...formData, color, bg_color: bgColor });
  };

  if (loading) {
    return (
      <div className="container-fluid mt-4">
        <div className="text-center py-5">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Chargement...</span>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="container-fluid mt-4">
      {/* En-tête */}
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 className="mb-2">🎨 Gestion Complète des Groupes de Matières</h2>
          <p className="text-muted mb-0">
            Créez, modifiez et supprimez des groupes de matières pour organiser vos bulletins.
          </p>
        </div>
        <button
          onClick={openCreateModal}
          className="btn btn-primary d-flex align-items-center gap-2"
        >
          <PlusCircle size={20} />
          Créer un nouveau groupe
        </button>
      </div>

      {/* Messages */}
      {error && (
        <div className="alert alert-danger alert-dismissible fade show" role="alert">
          <strong>Erreur !</strong> {error}
          <button type="button" className="btn-close" onClick={() => setError(null)}></button>
        </div>
      )}

      {success && (
        <div className="alert alert-success alert-dismissible fade show" role="alert">
          <CheckCircle size={20} className="me-2" />
          <strong>Succès !</strong> L'opération a été effectuée avec succès.
          <button type="button" className="btn-close" onClick={() => setSuccess(false)}></button>
        </div>
      )}

      {/* Groupes */}
      <div className="row g-4">
        {groups.map((group) => {
          const isEditing = editingGroup === group.id;
          const groupColor = group.color || '#6b7280';
          const groupBgColor = group.bg_color || '#f9fafb';

          return (
            <div key={group.id} className="col-12">
              <div
                className="card shadow-sm"
                style={{
                  borderLeft: `6px solid ${groupColor}`,
                  backgroundColor: isEditing ? '#fff' : groupBgColor
                }}
              >
                <div className="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
                  <div>
                    <h5 className="mb-0 fw-bold" style={{ color: groupColor }}>
                      GROUPE {group.code}
                    </h5>
                    <small className="text-muted">Code : {group.code}</small>
                  </div>
                  {!isEditing && (
                    <div className="d-flex gap-2">
                      <button
                        onClick={() => startEditing(group)}
                        className="btn btn-outline-primary btn-sm d-flex align-items-center gap-2"
                      >
                        <PencilSquare size={16} />
                        Modifier
                      </button>
                      <button
                        onClick={() => confirmDelete(group)}
                        className="btn btn-outline-danger btn-sm d-flex align-items-center gap-2"
                      >
                        <Trash3 size={16} />
                        Supprimer
                      </button>
                    </div>
                  )}
                </div>

                <div className="card-body">
                  {isEditing ? (
                    // Mode édition
                    <div>
                      <div className="row g-3">
                        <div className="col-md-4">
                          <label className="form-label fw-bold">
                            Code du groupe <span className="text-danger">*</span>
                          </label>
                          <input
                            type="text"
                            className="form-control"
                            value={formData.code}
                            onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                            placeholder="Ex: E, TECH, LIT"
                            maxLength={50}
                          />
                          <small className="text-warning">
                            ⚠️ Modifier le code migrera automatiquement toutes les matières associées
                          </small>
                        </div>

                        <div className="col-md-4">
                          <label className="form-label fw-bold">
                            En-tête (Français) <span className="text-danger">*</span>
                          </label>
                          <input
                            type="text"
                            className="form-control"
                            value={formData.header}
                            onChange={(e) => setFormData({ ...formData, header: e.target.value })}
                            placeholder="Ex: GROUPE E"
                            maxLength={255}
                          />
                        </div>

                        <div className="col-md-4">
                          <label className="form-label fw-bold">En-tête (Anglais)</label>
                          <input
                            type="text"
                            className="form-control"
                            value={formData.header_en}
                            onChange={(e) => setFormData({ ...formData, header_en: e.target.value })}
                            placeholder="Ex: GROUP E"
                            maxLength={255}
                          />
                        </div>

                        <div className="col-md-6">
                          <label className="form-label fw-bold">
                            Nom du groupe (Français) <span className="text-danger">*</span>
                          </label>
                          <input
                            type="text"
                            className="form-control"
                            value={formData.name}
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            placeholder="Ex: MATIÈRES COMPLÉMENTAIRES"
                            maxLength={255}
                          />
                        </div>

                        <div className="col-md-6">
                          <label className="form-label fw-bold">Nom du groupe (Anglais)</label>
                          <input
                            type="text"
                            className="form-control"
                            value={formData.name_en}
                            onChange={(e) => setFormData({ ...formData, name_en: e.target.value })}
                            placeholder="Ex: COMPLEMENTARY SUBJECTS"
                            maxLength={255}
                          />
                        </div>

                        <div className="col-12">
                          <label className="form-label fw-bold">Description</label>
                          <textarea
                            className="form-control"
                            rows={2}
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            placeholder="Description du groupe..."
                          />
                        </div>

                        {/* Sélecteur de couleurs */}
                        <div className="col-12">
                          <label className="form-label fw-bold">Couleur du groupe</label>
                          <div className="d-flex flex-wrap gap-2 mb-2">
                            {colorPalette.map((palette, idx) => (
                              <button
                                key={idx}
                                onClick={() => selectColor(palette.color, palette.bgColor)}
                                className={`btn btn-sm ${formData.color === palette.color ? 'border-3 border-dark' : 'border'}`}
                                style={{
                                  backgroundColor: palette.bgColor,
                                  color: palette.color,
                                  minWidth: '100px'
                                }}
                                title={palette.name}
                              >
                                <div
                                  className="rounded-circle d-inline-block me-2"
                                  style={{
                                    width: '16px',
                                    height: '16px',
                                    backgroundColor: palette.color
                                  }}
                                ></div>
                                {palette.name}
                              </button>
                            ))}
                          </div>
                          <div className="d-flex gap-2 align-items-center mt-2">
                            <div>
                              <label className="form-label small mb-1">Couleur principale</label>
                              <input
                                type="color"
                                className="form-control form-control-color"
                                value={formData.color}
                                onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                              />
                            </div>
                            <div>
                              <label className="form-label small mb-1">Couleur de fond</label>
                              <input
                                type="color"
                                className="form-control form-control-color"
                                value={formData.bg_color}
                                onChange={(e) => setFormData({ ...formData, bg_color: e.target.value })}
                              />
                            </div>
                            <div
                              className="flex-grow-1 p-3 rounded text-center fw-bold"
                              style={{
                                backgroundColor: formData.bg_color,
                                color: formData.color,
                                border: `2px solid ${formData.color}`
                              }}
                            >
                              Aperçu
                            </div>
                          </div>
                        </div>
                      </div>

                      <div className="d-flex gap-2 mt-3">
                        <button
                          onClick={() => saveGroup(group.id)}
                          disabled={saving || !formData.code.trim() || !formData.header.trim() || !formData.name.trim()}
                          className="btn btn-success d-flex align-items-center gap-2"
                        >
                          <Save size={16} />
                          {saving ? 'Enregistrement...' : 'Enregistrer'}
                        </button>
                        <button
                          onClick={cancelEditing}
                          disabled={saving}
                          className="btn btn-outline-secondary d-flex align-items-center gap-2"
                        >
                          <XCircle size={16} />
                          Annuler
                        </button>
                      </div>
                    </div>
                  ) : (
                    // Mode affichage
                    <div>
                      <div className="row mb-3">
                        <div className="col-md-6">
                          <h6 className="text-muted mb-1">En-tête Français</h6>
                          <p className="h5 mb-0 fw-bold">{group.header || `GROUPE ${group.code}`}</p>
                        </div>
                        <div className="col-md-6">
                          <h6 className="text-muted mb-1">En-tête Anglais</h6>
                          <p className="h5 mb-0 fw-bold">{group.header_en || <span className="text-muted">Non défini</span>}</p>
                        </div>
                      </div>

                      <div className="row mb-3">
                        <div className="col-md-6">
                          <h6 className="text-muted mb-1">Nom Français</h6>
                          <p className="h5 mb-0 fw-bold">{group.name}</p>
                        </div>
                        <div className="col-md-6">
                          <h6 className="text-muted mb-1">Nom Anglais</h6>
                          <p className="h5 mb-0 fw-bold">{group.name_en || <span className="text-muted">Non défini</span>}</p>
                        </div>
                      </div>

                      {group.description && (
                        <div className="mb-2">
                          <h6 className="text-muted mb-1">Description</h6>
                          <p className="mb-0">{group.description}</p>
                        </div>
                      )}

                      <div className="mt-3 pt-3 border-top">
                        <div className="d-flex align-items-center gap-3">
                          <div
                            className="px-3 py-1 rounded"
                            style={{
                              backgroundColor: group.bg_color,
                              color: group.color,
                              border: `1px solid ${group.color}`
                            }}
                          >
                            <small className="fw-bold">Couleur</small>
                          </div>
                          <small className="text-muted">
                            <strong>Ordre :</strong> {group.order} |
                            <strong> Statut :</strong> {group.is_active ? '✅ Actif' : '❌ Inactif'}
                          </small>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Modal de création */}
      {showCreateModal && (
        <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog modal-lg modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">✨ Créer un nouveau groupe</h5>
                <button type="button" className="btn-close" onClick={closeCreateModal}></button>
              </div>
              <div className="modal-body">
                <div className="row g-3">
                  <div className="col-md-4">
                    <label className="form-label fw-bold">
                      Code du groupe <span className="text-danger">*</span>
                    </label>
                    <input
                      type="text"
                      className="form-control"
                      value={formData.code}
                      onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                      placeholder="Ex: E, TECH, LIT"
                      maxLength={50}
                    />
                    <small className="text-muted">Code unique (lettres ou chiffres)</small>
                  </div>

                  <div className="col-md-4">
                    <label className="form-label fw-bold">
                      En-tête (Français) <span className="text-danger">*</span>
                    </label>
                    <input
                      type="text"
                      className="form-control"
                      value={formData.header}
                      onChange={(e) => setFormData({ ...formData, header: e.target.value })}
                      placeholder="Ex: GROUPE E"
                      maxLength={255}
                    />
                  </div>

                  <div className="col-md-4">
                    <label className="form-label fw-bold">En-tête (Anglais)</label>
                    <input
                      type="text"
                      className="form-control"
                      value={formData.header_en}
                      onChange={(e) => setFormData({ ...formData, header_en: e.target.value })}
                      placeholder="Ex: GROUP E"
                      maxLength={255}
                    />
                  </div>

                  <div className="col-md-6">
                    <label className="form-label fw-bold">
                      Nom du groupe (Français) <span className="text-danger">*</span>
                    </label>
                    <input
                      type="text"
                      className="form-control"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      placeholder="Ex: MATIÈRES COMPLÉMENTAIRES"
                      maxLength={255}
                    />
                  </div>

                  <div className="col-md-6">
                    <label className="form-label fw-bold">Nom du groupe (Anglais)</label>
                    <input
                      type="text"
                      className="form-control"
                      value={formData.name_en}
                      onChange={(e) => setFormData({ ...formData, name_en: e.target.value })}
                      placeholder="Ex: COMPLEMENTARY SUBJECTS"
                      maxLength={255}
                    />
                  </div>

                  <div className="col-12">
                    <label className="form-label fw-bold">Description</label>
                    <textarea
                      className="form-control"
                      rows={2}
                      value={formData.description}
                      onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                      placeholder="Description du groupe..."
                    />
                  </div>

                  {/* Sélecteur de couleurs */}
                  <div className="col-12">
                    <label className="form-label fw-bold">Couleur du groupe</label>
                    <div className="d-flex flex-wrap gap-2 mb-2">
                      {colorPalette.map((palette, idx) => (
                        <button
                          key={idx}
                          onClick={() => selectColor(palette.color, palette.bgColor)}
                          className={`btn btn-sm ${formData.color === palette.color ? 'border-3 border-dark' : 'border'}`}
                          style={{
                            backgroundColor: palette.bgColor,
                            color: palette.color,
                            minWidth: '100px'
                          }}
                          title={palette.name}
                        >
                          <div
                            className="rounded-circle d-inline-block me-2"
                            style={{
                              width: '16px',
                              height: '16px',
                              backgroundColor: palette.color
                            }}
                          ></div>
                          {palette.name}
                        </button>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={closeCreateModal}>
                  Annuler
                </button>
                <button
                  type="button"
                  className="btn btn-primary"
                  onClick={createGroup}
                  disabled={saving || !formData.code.trim() || !formData.header.trim() || !formData.name.trim()}
                >
                  {saving ? 'Création...' : 'Créer le groupe'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal de confirmation de suppression */}
      {deleteConfirm && (
        <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header bg-danger text-white">
                <h5 className="modal-title">
                  <ExclamationTriangle size={24} className="me-2" />
                  Confirmer la suppression
                </h5>
                <button type="button" className="btn-close btn-close-white" onClick={() => setDeleteConfirm(null)}></button>
              </div>
              <div className="modal-body">
                <p className="mb-2">
                  Êtes-vous sûr de vouloir supprimer le groupe <strong>{deleteConfirm.code}</strong> ?
                </p>
                <p className="text-muted mb-0">
                  <small>
                    <strong>Note :</strong> Vous ne pouvez supprimer un groupe que si aucune matière n'y est assignée.
                  </small>
                </p>
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setDeleteConfirm(null)}>
                  Annuler
                </button>
                <button
                  type="button"
                  className="btn btn-danger"
                  onClick={deleteGroup}
                  disabled={saving}
                >
                  {saving ? 'Suppression...' : 'Supprimer'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Information */}
      <div className="mt-4 alert alert-info">
        <InfoCircle size={20} className="me-2" />
        <strong>💡 Astuce :</strong> Les groupes permettent d'organiser les matières dans les bulletins.
        Vous pouvez créer autant de groupes que nécessaire et personnaliser leurs couleurs.
      </div>
    </div>
  );
}
