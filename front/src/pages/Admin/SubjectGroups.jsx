import React, { useState, useEffect } from 'react';
import { GripVertical, Save, ExclamationTriangle, ArrowRight } from 'react-bootstrap-icons';
import axios from 'axios';
import { host } from '../../utils/fetch';
import { authService } from '../../services/authService';

const API_URL = `${host}/api`;

export default function SubjectGroups() {
  const [subjects, setSubjects] = useState([]);
  const [groupedSubjects, setGroupedSubjects] = useState({
    A: [],
    B: [],
    C: [],
    D: [],
    uncategorized: []
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);
  const [changes, setChanges] = useState([]);
  const [draggedSubject, setDraggedSubject] = useState(null);
  const [availableGroups, setAvailableGroups] = useState([]);

  useEffect(() => {
    fetchGroups();
    fetchSubjects();
  }, []);

  const fetchGroups = async () => {
    try {
      const response = await axios.get(`${API_URL}/subject-groups/groups`, {
        headers: authService.getHeaders()
      });

      if (response.data.success) {
        const groups = response.data.data.map(g => ({
          code: g.code,
          name: g.name,
          color: g.color || '#6b7280',
          bgColor: g.bg_color || '#f9fafb',
          description: g.description || ''
        }));
        setAvailableGroups(groups);
      }
    } catch (err) {
      console.error('Erreur lors du chargement des groupes:', err);
      // Fallback sur les groupes par défaut
      setAvailableGroups([
        { code: 'A', name: 'MATIÈRES LITTÉRAIRES', color: '#3b82f6', bgColor: '#eff6ff', description: 'Langues, Histoire, Géographie, etc.' },
        { code: 'B', name: 'MATIÈRES SCIENTIFIQUES', color: '#10b981', bgColor: '#f0fdf4', description: 'Mathématiques, Physique, SVT, etc.' },
        { code: 'C', name: 'MATIÈRES PRATIQUES', color: '#f59e0b', bgColor: '#fffbeb', description: 'EPS, Informatique, Arts, etc.' },
        { code: 'D', name: 'AUTRES MATIÈRES', color: '#8b5cf6', bgColor: '#f5f3ff', description: 'ECM, Éducation Civique, etc.' }
      ]);
    }
  };

  const fetchSubjects = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await axios.get(`${API_URL}/subject-groups`, {
        headers: authService.getHeaders()
      });

      if (response.data.success) {
        setSubjects(response.data.data.subjects);
        setGroupedSubjects(response.data.data.grouped);
      }
    } catch (err) {
      console.error('Erreur lors du chargement:', err);
      setError('Erreur lors du chargement des matières. Vérifiez votre connexion.');
    } finally {
      setLoading(false);
    }
  };

  const handleDragStart = (e, subject, sourceGroup) => {
    setDraggedSubject({ subject, sourceGroup });
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', e.currentTarget);
  };

  const handleDragOver = (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  };

  const handleDrop = (e, targetGroup) => {
    e.preventDefault();

    if (!draggedSubject) return;

    const { subject, sourceGroup } = draggedSubject;

    // Si c'est le même groupe, on ne fait rien
    if (sourceGroup === targetGroup) {
      setDraggedSubject(null);
      return;
    }

    // Retirer du groupe source
    const newGroupedSubjects = { ...groupedSubjects };
    newGroupedSubjects[sourceGroup] = newGroupedSubjects[sourceGroup].filter(s => s.id !== subject.id);

    // Ajouter au groupe cible
    newGroupedSubjects[targetGroup] = [...(newGroupedSubjects[targetGroup] || []), subject];

    setGroupedSubjects(newGroupedSubjects);

    // Enregistrer le changement
    const newChange = {
      id: subject.id,
      group: targetGroup === 'uncategorized' ? null : targetGroup,
      name: subject.name,
      sourceGroup,
      targetGroup
    };

    setChanges(prev => {
      const filtered = prev.filter(c => c.id !== subject.id);
      return [...filtered, newChange];
    });

    setDraggedSubject(null);
  };

  const moveSubject = (subject, sourceGroup, targetGroup) => {
    if (sourceGroup === targetGroup) return;

    const newGroupedSubjects = { ...groupedSubjects };
    newGroupedSubjects[sourceGroup] = newGroupedSubjects[sourceGroup].filter(s => s.id !== subject.id);
    newGroupedSubjects[targetGroup] = [...(newGroupedSubjects[targetGroup] || []), subject];

    setGroupedSubjects(newGroupedSubjects);

    const newChange = {
      id: subject.id,
      group: targetGroup === 'uncategorized' ? null : targetGroup,
      name: subject.name,
      sourceGroup,
      targetGroup
    };

    setChanges(prev => {
      const filtered = prev.filter(c => c.id !== subject.id);
      return [...filtered, newChange];
    });
  };

  const saveChanges = async () => {
    if (changes.length === 0) {
      alert('Aucune modification à enregistrer');
      return;
    }

    try {
      setSaving(true);
      setError(null);

      const updates = changes.map(change => ({
        id: change.id,
        group: change.group
      }));

      const response = await axios.post(
        `${API_URL}/subject-groups/bulk-update`,
        { updates },
        {
          headers: authService.getHeaders()
        }
      );

      if (response.data.success) {
        setSuccess(true);
        setChanges([]);
        setTimeout(() => setSuccess(false), 3000);
        fetchSubjects();
      }
    } catch (err) {
      console.error('Erreur lors de l\'enregistrement:', err);
      setError('Erreur lors de l\'enregistrement. Veuillez réessayer.');
    } finally {
      setSaving(false);
    }
  };

  const undoChange = (changeId) => {
    const change = changes.find(c => c.id === changeId);
    if (!change) return;

    // Remettre la matière dans son groupe d'origine
    const newGroupedSubjects = { ...groupedSubjects };
    const subject = subjects.find(s => s.id === changeId);

    if (subject) {
      newGroupedSubjects[change.targetGroup] = newGroupedSubjects[change.targetGroup].filter(s => s.id !== changeId);
      newGroupedSubjects[change.sourceGroup] = [...(newGroupedSubjects[change.sourceGroup] || []), subject];
      setGroupedSubjects(newGroupedSubjects);
    }

    // Retirer le changement de la liste
    setChanges(prev => prev.filter(c => c.id !== changeId));
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
          <h2 className="mb-1">Gestion des Groupes de Matières</h2>
          <p className="text-muted mb-0">
            Glissez-déposez les matières pour les organiser par groupe. Les groupes sont utilisés dans les bulletins.
          </p>
        </div>
        <button
          onClick={saveChanges}
          disabled={saving || changes.length === 0}
          className="btn btn-primary d-flex align-items-center gap-2"
        >
          <Save size={18} />
          {saving ? 'Enregistrement...' : `Enregistrer ${changes.length > 0 ? `(${changes.length})` : ''}`}
        </button>
      </div>

      {/* Messages */}
      {error && (
        <div className="alert alert-danger d-flex align-items-center gap-2 mb-4">
          <ExclamationTriangle size={20} />
          {error}
        </div>
      )}

      {success && (
        <div className="alert alert-success mb-4">
          ✓ Modifications enregistrées avec succès !
        </div>
      )}

      {/* Changements en attente */}
      {changes.length > 0 && (
        <div className="alert alert-info mb-4">
          <h6 className="fw-bold mb-2">Changements en attente ({changes.length})</h6>
          <div className="d-flex flex-wrap gap-2">
            {changes.map(change => (
              <span key={change.id} className="badge bg-info text-dark d-flex align-items-center gap-2">
                {change.name}
                <ArrowRight size={12} />
                Groupe {change.group || 'Sans groupe'}
                <button
                  type="button"
                  className="btn-close btn-close-white"
                  style={{ fontSize: '10px' }}
                  onClick={() => undoChange(change.id)}
                ></button>
              </span>
            ))}
          </div>
        </div>
      )}

      {/* Groupes */}
      <div className="row g-3">
        {availableGroups.map(group => (
          <div key={group.code} className="col-md-6 col-lg-3">
            <div
              className="card h-100"
              style={{
                borderTop: `4px solid ${group.color}`,
                backgroundColor: group.bgColor
              }}
              onDragOver={handleDragOver}
              onDrop={(e) => handleDrop(e, group.code)}
            >
              <div className="card-header" style={{ backgroundColor: group.color, color: 'white' }}>
                <h6 className="mb-0 fw-bold">GROUPE {group.code}</h6>
                <small>{group.name}</small>
                <div className="small opacity-75 mt-1">{group.description}</div>
              </div>
              <div className="card-body" style={{ minHeight: '400px', maxHeight: '600px', overflowY: 'auto' }}>
                <div className="mb-2 text-muted small fw-bold">
                  {groupedSubjects[group.code]?.length || 0} matière(s)
                </div>
                {groupedSubjects[group.code]?.map(subject => (
                  <div
                    key={subject.id}
                    draggable
                    onDragStart={(e) => handleDragStart(e, subject, group.code)}
                    className="d-flex align-items-center bg-white border rounded p-2 mb-2 shadow-sm"
                    style={{ cursor: 'grab' }}
                  >
                    <div className="me-2">
                      <GripVertical size={16} className="text-muted" />
                    </div>
                    <div className="flex-grow-1">
                      <div className="fw-medium">{subject.name}</div>
                      {subject.code && <small className="text-muted">Code: {subject.code}</small>}
                    </div>
                    {/* Boutons rapides pour déplacer */}
                    <div className="dropdown">
                      <button
                        className="btn btn-sm btn-outline-secondary dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        style={{ fontSize: '10px' }}
                      >
                        Déplacer
                      </button>
                      <ul className="dropdown-menu">
                        {availableGroups.filter(g => g.code !== group.code).map(g => (
                          <li key={g.code}>
                            <button
                              className="dropdown-item"
                              onClick={() => moveSubject(subject, group.code, g.code)}
                            >
                              Vers Groupe {g.code}
                            </button>
                          </li>
                        ))}
                        <li><hr className="dropdown-divider" /></li>
                        <li>
                          <button
                            className="dropdown-item"
                            onClick={() => moveSubject(subject, group.code, 'uncategorized')}
                          >
                            Retirer du groupe
                          </button>
                        </li>
                      </ul>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        ))}

        {/* Matières sans groupe */}
        <div className="col-12">
          <div
            className="card"
            style={{ borderTop: '4px solid #6b7280' }}
            onDragOver={handleDragOver}
            onDrop={(e) => handleDrop(e, 'uncategorized')}
          >
            <div className="card-header bg-secondary text-white">
              <h6 className="mb-0 fw-bold">MATIÈRES SANS GROUPE</h6>
              <small>Ces matières n'apparaîtront pas dans les bulletins tant qu'elles ne sont pas assignées à un groupe</small>
            </div>
            <div className="card-body" style={{ maxHeight: '300px', overflowY: 'auto' }}>
              <div className="mb-2 text-muted small fw-bold">
                {groupedSubjects.uncategorized?.length || 0} matière(s)
              </div>
              <div className="row">
                {groupedSubjects.uncategorized?.map(subject => (
                  <div key={subject.id} className="col-md-3 mb-2">
                    <div
                      draggable
                      onDragStart={(e) => handleDragStart(e, subject, 'uncategorized')}
                      className="d-flex align-items-center bg-white border rounded p-2 shadow-sm"
                      style={{ cursor: 'grab' }}
                    >
                      <div className="me-2">
                        <GripVertical size={16} className="text-muted" />
                      </div>
                      <div className="flex-grow-1">
                        <div className="fw-medium small">{subject.name}</div>
                        {subject.code && <small className="text-muted" style={{ fontSize: '10px' }}>Code: {subject.code}</small>}
                      </div>
                      <div className="dropdown">
                        <button
                          className="btn btn-sm btn-outline-primary dropdown-toggle"
                          type="button"
                          data-bs-toggle="dropdown"
                          style={{ fontSize: '10px' }}
                        >
                          +
                        </button>
                        <ul className="dropdown-menu">
                          {availableGroups.map(g => (
                            <li key={g.code}>
                              <button
                                className="dropdown-item"
                                onClick={() => moveSubject(subject, 'uncategorized', g.code)}
                              >
                                Vers Groupe {g.code}
                              </button>
                            </li>
                          ))}
                        </ul>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Instructions */}
      <div className="mt-4 p-4 bg-light rounded">
        <h6 className="fw-bold mb-3">📖 Instructions</h6>
        <div className="row">
          <div className="col-md-6">
            <h6 className="text-primary">Comment déplacer une matière ?</h6>
            <ul className="small">
              <li><strong>Méthode 1 :</strong> Glissez-déposez la matière d'un groupe à un autre</li>
              <li><strong>Méthode 2 :</strong> Utilisez le bouton "Déplacer" pour choisir le groupe de destination</li>
            </ul>
          </div>
          <div className="col-md-6">
            <h6 className="text-primary">À quoi servent les groupes ?</h6>
            <ul className="small">
              <li><strong>Groupe A :</strong> Matières littéraires (Français, Anglais, Histoire...)</li>
              <li><strong>Groupe B :</strong> Matières scientifiques (Maths, Physique, SVT...)</li>
              <li><strong>Groupe C :</strong> Matières pratiques (EPS, Informatique, Arts...)</li>
              <li><strong>Groupe D :</strong> Autres matières (ECM, Éducation Civique...)</li>
            </ul>
          </div>
        </div>
        <div className="alert alert-warning mt-3 mb-0">
          <strong>⚠️ Important :</strong> N'oubliez pas de cliquer sur "Enregistrer" pour sauvegarder vos modifications. Les bulletins utiliseront automatiquement cette nouvelle configuration.
        </div>
      </div>
    </div>
  );
}
