import React, { useState, useEffect } from 'react';
import { Save, PencilSquare, XCircle, CheckCircle, InfoCircle } from 'react-bootstrap-icons';
import axios from 'axios';
import { host } from '../../utils/fetch';
import { authService } from '../../services/authService';

const API_URL = `${host}/api`;

export default function SubjectGroupsSettings() {
  const [groups, setGroups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);
  const [editingGroup, setEditingGroup] = useState(null);
  const [formData, setFormData] = useState({
    header: '',
    header_en: '',
    name: '',
    name_en: '',
    description: ''
  });

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
      header: group.header || `GROUPE ${group.code}`,
      header_en: group.header_en || `GROUP ${group.code}`,
      name: group.name,
      name_en: group.name_en || '',
      description: group.description || ''
    });
  };

  const cancelEditing = () => {
    setEditingGroup(null);
    setFormData({ header: '', header_en: '', name: '', name_en: '', description: '' });
  };

  const saveGroup = async (groupId) => {
    if (!formData.header.trim()) {
      setError('L\'en-tête du groupe est obligatoire');
      return;
    }
    if (!formData.name.trim()) {
      setError('Le nom du groupe est obligatoire');
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

  const getColorForGroup = (code) => {
    const colors = {
      A: { color: '#3b82f6', bgColor: '#eff6ff', borderColor: '#3b82f6' },
      B: { color: '#10b981', bgColor: '#f0fdf4', borderColor: '#10b981' },
      C: { color: '#f59e0b', bgColor: '#fffbeb', borderColor: '#f59e0b' },
      D: { color: '#8b5cf6', bgColor: '#f5f3ff', borderColor: '#8b5cf6' }
    };
    return colors[code] || { color: '#6b7280', bgColor: '#f9fafb', borderColor: '#6b7280' };
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
      <div className="mb-4">
        <h2 className="mb-2">⚙️ Configuration des Groupes de Matières</h2>
        <p className="text-muted mb-0">
          Personnalisez les noms des groupes de matières. Ces noms apparaîtront sur tous les bulletins de notes.
        </p>
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
          <strong>Succès !</strong> Le groupe a été mis à jour avec succès.
          <button type="button" className="btn-close" onClick={() => setSuccess(false)}></button>
        </div>
      )}

      {/* Information */}
      <div className="alert alert-info d-flex align-items-start gap-3 mb-4">
        <InfoCircle size={24} className="flex-shrink-0 mt-1" />
        <div>
          <h6 className="mb-2 fw-bold">ℹ️ À propos des groupes</h6>
          <p className="mb-1">Les groupes permettent d'organiser les matières dans les bulletins :</p>
          <ul className="mb-0 small">
            <li><strong>Groupe A :</strong> Traditionnellement les matières littéraires (langues, histoire, géographie)</li>
            <li><strong>Groupe B :</strong> Les matières scientifiques (mathématiques, physique, chimie, SVT)</li>
            <li><strong>Groupe C :</strong> Les matières pratiques (EPS, informatique, arts)</li>
            <li><strong>Groupe D :</strong> Autres matières (ECM, éducation civique, culture nationale)</li>
          </ul>
        </div>
      </div>

      {/* Groupes */}
      <div className="row g-4">
        {groups.map((group) => {
          const colors = getColorForGroup(group.code);
          const isEditing = editingGroup === group.id;

          return (
            <div key={group.id} className="col-12">
              <div
                className="card shadow-sm"
                style={{
                  borderLeft: `6px solid ${colors.borderColor}`,
                  backgroundColor: isEditing ? '#fff' : colors.bgColor
                }}
              >
                <div className="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
                  <div>
                    <h5 className="mb-0 fw-bold" style={{ color: colors.color }}>
                      GROUPE {group.code}
                    </h5>
                    <small className="text-muted">Code du groupe : {group.code} (non modifiable)</small>
                  </div>
                  {!isEditing && (
                    <button
                      onClick={() => startEditing(group)}
                      className="btn btn-outline-primary btn-sm d-flex align-items-center gap-2"
                    >
                      <PencilSquare size={16} />
                      Modifier
                    </button>
                  )}
                </div>

                <div className="card-body">
                  {isEditing ? (
                    // Mode édition
                    <div>
                      <div className="row g-3">
                        <div className="col-md-6">
                          <label className="form-label fw-bold">
                            En-tête du groupe (Français)
                          </label>
                          <input
                            type="text"
                            className="form-control"
                            value={formData.header}
                            onChange={(e) => setFormData({ ...formData, header: e.target.value })}
                            placeholder="Ex: GROUPE A"
                            maxLength={255}
                          />
                          <small className="text-muted">L'en-tête qui apparaîtra sur les bulletins (ex: GROUPE A, SECTION A, etc.)</small>
                        </div>

                        <div className="col-md-6">
                          <label className="form-label fw-bold">En-tête du groupe (Anglais)</label>
                          <input
                            type="text"
                            className="form-control"
                            value={formData.header_en}
                            onChange={(e) => setFormData({ ...formData, header_en: e.target.value })}
                            placeholder="Ex: GROUP A"
                            maxLength={255}
                          />
                          <small className="text-muted">Version anglaise de l'en-tête</small>
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
                            placeholder="Ex: MATIÈRES LITTÉRAIRES"
                            maxLength={255}
                          />
                          <small className="text-muted">Le nom complet du groupe</small>
                        </div>

                        <div className="col-md-6">
                          <label className="form-label fw-bold">Nom du groupe (Anglais)</label>
                          <input
                            type="text"
                            className="form-control"
                            value={formData.name_en}
                            onChange={(e) => setFormData({ ...formData, name_en: e.target.value })}
                            placeholder="Ex: LITERARY SUBJECTS"
                            maxLength={255}
                          />
                          <small className="text-muted">Version anglaise du nom complet</small>
                        </div>

                        <div className="col-12">
                          <label className="form-label fw-bold">Description (optionnel)</label>
                          <textarea
                            className="form-control"
                            rows={2}
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            placeholder="Ex: Groupe A: Français, Anglais, Histoire, Géographie, etc."
                          />
                          <small className="text-muted">Description interne pour votre référence</small>
                        </div>
                      </div>

                      <div className="d-flex gap-2 mt-3">
                        <button
                          onClick={() => saveGroup(group.id)}
                          disabled={saving || !formData.header.trim() || !formData.name.trim()}
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
                        <small className="text-muted">
                          <strong>Ordre d'affichage :</strong> {group.order} |
                          <strong> Statut :</strong> {group.is_active ? '✅ Actif' : '❌ Inactif'} |
                          <strong> Dernière mise à jour :</strong> {new Date(group.updated_at).toLocaleDateString('fr-FR')}
                        </small>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Guide d'utilisation */}
      <div className="mt-5 p-4 bg-light rounded">
        <h5 className="mb-3 fw-bold">📖 Guide d'utilisation</h5>

        <div className="row g-4">
          <div className="col-md-6">
            <h6 className="text-primary fw-bold">✏️ Comment modifier un groupe ?</h6>
            <ol className="small">
              <li>Cliquez sur le bouton <strong>"Modifier"</strong> du groupe souhaité</li>
              <li>Modifiez le <strong>nom français</strong> (obligatoire)</li>
              <li>Modifiez le <strong>nom anglais</strong> (optionnel mais recommandé)</li>
              <li>Ajoutez une <strong>description</strong> si nécessaire</li>
              <li>Cliquez sur <strong>"Enregistrer"</strong></li>
            </ol>
          </div>

          <div className="col-md-6">
            <h6 className="text-primary fw-bold">💡 Conseils</h6>
            <ul className="small">
              <li>Utilisez des <strong>MAJUSCULES</strong> pour les noms de groupes (convention)</li>
              <li>Restez <strong>concis</strong> (max 255 caractères)</li>
              <li>Les noms doivent être <strong>clairs</strong> et <strong>descriptifs</strong></li>
              <li>Pensez aux <strong>2 langues</strong> (français et anglais) pour les établissements bilingues</li>
              <li>Les modifications sont <strong>immédiates</strong> sur tous les bulletins</li>
            </ul>
          </div>

          <div className="col-12">
            <div className="alert alert-warning mb-0">
              <strong>⚠️ Attention :</strong> Les modifications affecteront <strong>tous les bulletins générés après</strong> la modification.
              Les bulletins déjà générés ne seront pas modifiés rétroactivement.
            </div>
          </div>
        </div>
      </div>

      {/* Exemples de noms de groupes */}
      <div className="mt-4 p-4 bg-white border rounded">
        <h6 className="mb-3 fw-bold">💡 Exemples de noms personnalisés</h6>
        <div className="row g-3">
          <div className="col-md-6">
            <div className="small">
              <strong>Pour une école technique :</strong>
              <ul className="mt-2">
                <li>Groupe C : "MATIÈRES TECHNIQUES ET PROFESSIONNELLES"</li>
                <li>Groupe D : "FORMATION PRATIQUE ET STAGES"</li>
              </ul>
            </div>
          </div>
          <div className="col-md-6">
            <div className="small">
              <strong>Pour une école anglophone :</strong>
              <ul className="mt-2">
                <li>Groupe A : "ARTS AND HUMANITIES"</li>
                <li>Groupe B : "SCIENCES AND MATHEMATICS"</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
