@extends('layouts.app')

@section('title', 'Gestion des Groupes de Matières')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0">Gestion des Groupes de Matières</h5>
                            <p class="text-sm mb-0">Organisez les matières dans les groupes pour les bulletins</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm" id="saveChangesBtn">
                                <i class="fas fa-save me-1"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <!-- Loading State -->
                    <div id="loadingState" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des matières...</p>
                    </div>

                    <!-- Main Content -->
                    <div id="mainContent" style="display: none;">
                        <div class="row px-4">
                            <!-- Group A -->
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card border border-primary">
                                    <div class="card-header bg-gradient-primary text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-book me-2"></i>
                                            GROUPE A
                                        </h6>
                                        <small>Matières Littéraires</small>
                                    </div>
                                    <div class="card-body p-2" id="groupA" data-group="A" style="min-height: 300px; max-height: 500px; overflow-y: auto;">
                                        <!-- Subjects will be added here -->
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-muted">
                                            <span class="count-badge">0</span> matière(s)
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Group B -->
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card border border-success">
                                    <div class="card-header bg-gradient-success text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calculator me-2"></i>
                                            GROUPE B
                                        </h6>
                                        <small>Matières Scientifiques</small>
                                    </div>
                                    <div class="card-body p-2" id="groupB" data-group="B" style="min-height: 300px; max-height: 500px; overflow-y: auto;">
                                        <!-- Subjects will be added here -->
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-muted">
                                            <span class="count-badge">0</span> matière(s)
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Group C -->
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card border border-warning">
                                    <div class="card-header bg-gradient-warning text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-palette me-2"></i>
                                            GROUPE C
                                        </h6>
                                        <small>Matières Pratiques</small>
                                    </div>
                                    <div class="card-body p-2" id="groupC" data-group="C" style="min-height: 300px; max-height: 500px; overflow-y: auto;">
                                        <!-- Subjects will be added here -->
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-muted">
                                            <span class="count-badge">0</span> matière(s)
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Group D -->
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card border border-info">
                                    <div class="card-header bg-gradient-info text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-ellipsis-h me-2"></i>
                                            GROUPE D
                                        </h6>
                                        <small>Autres Matières</small>
                                    </div>
                                    <div class="card-body p-2" id="groupD" data-group="D" style="min-height: 300px; max-height: 500px; overflow-y: auto;">
                                        <!-- Subjects will be added here -->
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-muted">
                                            <span class="count-badge">0</span> matière(s)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Uncategorized Subjects -->
                        <div class="row px-4" id="uncategorizedSection" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Matières sans groupe</h6>
                                    <p class="mb-2">Ces matières n'ont pas encore été assignées à un groupe. Glissez-les dans un groupe ci-dessus.</p>
                                    <div id="uncategorized" class="d-flex flex-wrap gap-2">
                                        <!-- Uncategorized subjects will be added here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.subject-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 8px;
    cursor: move;
    transition: all 0.3s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.subject-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.subject-item.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.subject-name {
    font-weight: 500;
    font-size: 0.9rem;
    color: #344767;
}

.subject-code {
    font-size: 0.75rem;
    color: #67748e;
}

.drop-zone {
    min-height: 50px;
    transition: background-color 0.3s;
}

.drop-zone.drag-over {
    background-color: #f8f9fa !important;
    border: 2px dashed #5e72e4 !important;
}

.count-badge {
    font-weight: 600;
}

#saveChangesBtn {
    position: relative;
}

#saveChangesBtn.saving {
    pointer-events: none;
    opacity: 0.6;
}
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let subjects = [];
let changes = [];

// Load subjects on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSubjects();
});

async function loadSubjects() {
    try {
        const response = await fetch('/api/subject-groups', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            subjects = result.data.subjects;
            displaySubjects(result.data.grouped);
            initializeSortable();
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('mainContent').style.display = 'block';
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch (error) {
        console.error('Error loading subjects:', error);
        alert('Erreur lors du chargement des matières');
    }
}

function displaySubjects(grouped) {
    // Display grouped subjects
    ['A', 'B', 'C', 'D'].forEach(group => {
        const container = document.getElementById('group' + group);
        container.innerHTML = '';

        const groupSubjects = grouped[group] || [];
        groupSubjects.forEach(subject => {
            container.appendChild(createSubjectElement(subject));
        });

        updateCount(group);
    });

    // Display uncategorized subjects
    const uncategorized = grouped.uncategorized || [];
    if (uncategorized.length > 0) {
        const container = document.getElementById('uncategorized');
        container.innerHTML = '';
        uncategorized.forEach(subject => {
            container.appendChild(createSubjectElement(subject));
        });
        document.getElementById('uncategorizedSection').style.display = 'block';
    }
}

function createSubjectElement(subject) {
    const div = document.createElement('div');
    div.className = 'subject-item';
    div.draggable = true;
    div.dataset.id = subject.id;
    div.dataset.currentGroup = subject.group || '';

    div.innerHTML = `
        <div class="subject-name">${subject.name}</div>
        ${subject.code ? `<div class="subject-code">Code: ${subject.code}</div>` : ''}
    `;

    return div;
}

function initializeSortable() {
    const groups = ['A', 'B', 'C', 'D', 'uncategorized'];

    groups.forEach(groupId => {
        const element = document.getElementById(groupId === 'uncategorized' ? 'uncategorized' : 'group' + groupId);

        new Sortable(element, {
            group: 'subjects',
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function(evt) {
                const subjectId = parseInt(evt.item.dataset.id);
                const oldGroup = evt.item.dataset.currentGroup;
                const newGroup = evt.to.dataset.group;

                if (oldGroup !== newGroup && newGroup) {
                    // Track change
                    const existingChange = changes.find(c => c.id === subjectId);
                    if (existingChange) {
                        existingChange.group = newGroup;
                    } else {
                        changes.push({ id: subjectId, group: newGroup });
                    }

                    evt.item.dataset.currentGroup = newGroup;

                    // Update counts
                    if (oldGroup) updateCount(oldGroup);
                    updateCount(newGroup);

                    console.log(`Moved subject ${subjectId} from ${oldGroup || 'uncategorized'} to ${newGroup}`);
                }
            }
        });
    });
}

function updateCount(group) {
    const container = document.getElementById('group' + group);
    const count = container.querySelectorAll('.subject-item').length;
    const badge = container.closest('.card').querySelector('.count-badge');
    if (badge) {
        badge.textContent = count;
    }
}

// Save changes
document.getElementById('saveChangesBtn').addEventListener('click', async function() {
    if (changes.length === 0) {
        alert('Aucune modification à enregistrer');
        return;
    }

    const btn = this;
    btn.classList.add('saving');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...';

    try {
        const response = await fetch('/api/subject-groups/bulk-update', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ updates: changes })
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ ' + result.message);
            changes = [];
            loadSubjects(); // Reload to refresh
        } else {
            alert('❌ Erreur: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving changes:', error);
        alert('❌ Erreur lors de l\'enregistrement');
    } finally {
        btn.classList.remove('saving');
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer les modifications';
    }
});
</script>
@endpush
@endsection
