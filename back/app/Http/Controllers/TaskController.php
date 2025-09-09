<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskHistory;
use App\Models\TaskTemplate;
use App\Models\TaskAssignee;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TaskController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Obtenir le dashboard des tâches pour l'utilisateur connecté
     */
    public function dashboard(Request $request)
    {
        try {
            $userId = auth()->id();
            $today = Carbon::today();

            // Statistiques générales
            $stats = [
                'total' => Task::forUser($userId)->count(),
                'pending' => Task::forUser($userId)->pending()->count(),
                'in_progress' => Task::forUser($userId)->inProgress()->count(),
                'completed' => Task::forUser($userId)->completed()->count(),
                'overdue' => Task::forUser($userId)->overdue()->count(),
                'upcoming' => Task::forUser($userId)->upcoming()->count(),
                'requiring_approval' => Task::where('created_by', $userId)
                    ->requiringApproval()->count(),
            ];

            // Tâches du jour
            $todayTasks = Task::forUser($userId)
                ->whereDate('due_date', $today)
                ->with(['creator', 'assignedBy'])
                ->orderBy('priority', 'desc')
                ->get();

            // Tâches urgentes et en retard
            $urgentTasks = Task::forUser($userId)
                ->where(function($query) {
                    $query->where('priority', 'critical')
                        ->orWhere('priority', 'high');
                })
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->with(['creator', 'assignedBy'])
                ->orderBy('due_date')
                ->limit(5)
                ->get();

            // Tâches récemment assignées
            $recentTasks = Task::forUser($userId)
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->with(['creator', 'assignedBy'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Performance de l'utilisateur
            $performance = $this->getUserPerformance($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'today_tasks' => $todayTasks,
                    'urgent_tasks' => $urgentTasks,
                    'recent_tasks' => $recentTasks,
                    'performance' => $performance
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des tâches avec filtres
     */
    public function index(Request $request)
    {
        try {
            $query = Task::with(['creator', 'assignedTo', 'assignedBy', 'comments', 'assignees']);

            // Filtrer par utilisateur si non admin
            if (!auth()->user()->hasRole('admin')) {
                $query->forUser(auth()->id());
            }

            // Filtres
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
            }

            if ($request->has('date_from')) {
                $query->whereDate('due_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('due_date', '<=', $request->date_to);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'due_date');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $tasks = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $tasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des tâches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle tâche
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:critical,high,normal,low',
            'category' => 'required|in:administrative,pedagogical,maintenance,event,urgent,other',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'nullable|date',
            'requires_approval' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_type' => 'required_if:is_recurring,true|in:daily,weekly,monthly,yearly',
            'recurrence_interval' => 'nullable|integer|min:1',
            'recurrence_end_date' => 'nullable|date|after:due_date',
            'points' => 'integer|min:0',
            'difficulty_level' => 'integer|min:1|max:5',
            'checklist' => 'nullable|array',
            'attachments' => 'nullable|array',
            'additional_assignees' => 'nullable|array',
            'additional_assignees.*' => 'exists:users,id',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'exists:tasks,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Créer la tâche
            $taskData = $request->except(['additional_assignees', 'dependencies']);
            $taskData['created_by'] = auth()->id();
            $taskData['assigned_by'] = auth()->id();
            $taskData['status'] = 'pending';

            $task = Task::create($taskData);

            // Ajouter les assignés supplémentaires
            if ($request->has('additional_assignees')) {
                foreach ($request->additional_assignees as $userId) {
                    TaskAssignee::create([
                        'task_id' => $task->id,
                        'user_id' => $userId,
                        'status' => 'pending'
                    ]);
                }
            }

            // Ajouter les dépendances
            if ($request->has('dependencies')) {
                foreach ($request->dependencies as $dependsOnId) {
                    $task->dependencies()->create([
                        'depends_on_task_id' => $dependsOnId
                    ]);
                }
            }

            // Enregistrer dans l'historique
            $task->logHistory('created', 'Tâche créée');

            // Envoyer notification WhatsApp
            $this->sendTaskNotification($task, 'new');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tâche créée avec succès',
                'data' => $task->load(['assignedTo', 'assignees', 'dependencies'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la tâche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une tâche spécifique
     */
    public function show($id)
    {
        try {
            $task = Task::with([
                'creator',
                'assignedTo',
                'assignedBy',
                'approvedBy',
                'comments.user',
                'histories.user',
                'dependencies.dependsOnTask',
                'assignees.user'
            ])->findOrFail($id);

            // Vérifier les permissions
            if (!auth()->user()->hasRole('admin')) {
                if ($task->assigned_to != auth()->id() && 
                    $task->created_by != auth()->id() &&
                    !$task->assignees()->where('user_id', auth()->id())->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès non autorisé'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour une tâche
     */
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        // Vérifier les permissions
        if (!auth()->user()->hasRole('admin') && $task->created_by != auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier cette tâche'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'priority' => 'in:critical,high,normal,low',
            'category' => 'in:administrative,pedagogical,maintenance,event,urgent,other',
            'assigned_to' => 'exists:users,id',
            'due_date' => 'nullable|date',
            'status' => 'in:pending,in_progress,completed,cancelled',
            'progress' => 'integer|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $oldValues = $task->toArray();
            $task->update($request->all());

            // Enregistrer dans l'historique
            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $task->toArray(),
                'description' => 'Tâche mise à jour'
            ]);

            // Si le statut change, envoyer une notification
            if (isset($request->status) && $oldValues['status'] != $request->status) {
                $this->sendTaskNotification($task, 'status_changed');
            }

            return response()->json([
                'success' => true,
                'message' => 'Tâche mise à jour avec succès',
                'data' => $task->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Démarrer une tâche
     */
    public function start($id)
    {
        try {
            $task = Task::findOrFail($id);

            // Vérifier les permissions
            if ($task->assigned_to != auth()->id() && 
                !$task->assignees()->where('user_id', auth()->id())->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas démarrer cette tâche'
                ], 403);
            }

            if (!$task->canBeStarted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette tâche ne peut pas être démarrée (dépendances non terminées)'
                ], 400);
            }

            $task->start();
            $this->sendTaskNotification($task, 'started');

            return response()->json([
                'success' => true,
                'message' => 'Tâche démarrée avec succès',
                'data' => $task->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage de la tâche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminer une tâche
     */
    public function complete($id)
    {
        try {
            $task = Task::findOrFail($id);

            // Vérifier les permissions
            if ($task->assigned_to != auth()->id() && 
                !$task->assignees()->where('user_id', auth()->id())->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas terminer cette tâche'
                ], 403);
            }

            $task->complete();

            // Créer la prochaine occurrence si tâche récurrente
            if ($task->is_recurring) {
                $newTask = $task->createRecurrence();
                if ($newTask) {
                    $this->sendTaskNotification($newTask, 'new');
                }
            }

            $this->sendTaskNotification($task, 'completed');

            return response()->json([
                'success' => true,
                'message' => 'Tâche terminée avec succès',
                'data' => $task->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la complétion de la tâche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour la progression
     */
    public function updateProgress(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'progress' => 'required|integer|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($id);

            // Vérifier les permissions
            if ($task->assigned_to != auth()->id() && 
                !$task->assignees()->where('user_id', auth()->id())->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas modifier cette tâche'
                ], 403);
            }

            $task->updateProgress($request->progress);

            return response()->json([
                'success' => true,
                'message' => 'Progression mise à jour',
                'data' => $task->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la progression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajouter un commentaire
     */
    public function addComment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
            'attachments' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($id);

            $comment = TaskComment::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'comment' => $request->comment,
                'attachments' => $request->attachments
            ]);

            // Notifier les personnes concernées
            $this->notifyTaskComment($task, $comment);

            return response()->json([
                'success' => true,
                'message' => 'Commentaire ajouté',
                'data' => $comment->load('user')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du commentaire',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approuver une tâche
     */
    public function approve($id)
    {
        try {
            $task = Task::findOrFail($id);

            // Vérifier les permissions (seul le créateur ou un admin peut approuver)
            if (!auth()->user()->hasRole('admin') && $task->created_by != auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas approuver cette tâche'
                ], 403);
            }

            $task->approve(auth()->id());
            $this->sendTaskNotification($task, 'approved');

            return response()->json([
                'success' => true,
                'message' => 'Tâche approuvée',
                'data' => $task->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les templates de tâches
     */
    public function getTemplates()
    {
        try {
            $templates = TaskTemplate::where('is_active', true)
                ->with('creator')
                ->orderBy('category')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une tâche depuis un template
     */
    public function createFromTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|exists:task_templates,id',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = TaskTemplate::findOrFail($request->template_id);
            $task = $template->createTask(
                $request->assigned_to,
                auth()->id(),
                $request->due_date
            );

            $task->logHistory('created', 'Tâche créée depuis un template');
            $this->sendTaskNotification($task, 'new');

            return response()->json([
                'success' => true,
                'message' => 'Tâche créée depuis le template',
                'data' => $task->load(['assignedTo', 'assignedBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création depuis le template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques
     */
    public function statistics(Request $request)
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year
            $userId = $request->get('user_id');

            // Si pas admin, forcer l'ID utilisateur connecté
            if (!auth()->user()->hasRole('admin')) {
                $userId = auth()->id();
            }

            $stats = $this->getTaskStatistics($period, $userId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le classement du personnel
     */
    public function leaderboard(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $limit = $request->get('limit', 10);

            $leaderboard = $this->getLeaderboard($period, $limit);

            return response()->json([
                'success' => true,
                'data' => $leaderboard
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du classement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Méthodes privées
     */
    private function getUserPerformance($userId)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        
        $tasks = Task::where('assigned_to', $userId)
            ->where('created_at', '>=', $startOfMonth)
            ->get();

        $completedTasks = $tasks->where('status', 'completed');
        $completedOnTime = $completedTasks->filter(function($task) {
            return !$task->due_date || $task->completed_at <= $task->due_date;
        });

        return [
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $completedTasks->count(),
            'completion_rate' => $tasks->count() > 0 ? 
                round(($completedTasks->count() / $tasks->count()) * 100, 2) : 0,
            'on_time_rate' => $completedTasks->count() > 0 ?
                round(($completedOnTime->count() / $completedTasks->count()) * 100, 2) : 0,
            'total_points' => $completedTasks->sum('points'),
            'average_completion_time' => $this->calculateAverageCompletionTime($completedTasks)
        ];
    }

    private function calculateAverageCompletionTime($tasks)
    {
        $totalMinutes = 0;
        $count = 0;

        foreach ($tasks as $task) {
            if ($task->started_at && $task->completed_at) {
                $totalMinutes += $task->started_at->diffInMinutes($task->completed_at);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count) : 0;
    }

    private function getTaskStatistics($period, $userId = null)
    {
        $query = Task::query();
        
        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        // Définir la période
        switch ($period) {
            case 'day':
                $startDate = Carbon::today();
                break;
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
        }

        $tasks = $query->where('created_at', '>=', $startDate)->get();

        // Statistiques par catégorie
        $byCategory = $tasks->groupBy('category')->map(function($group) {
            return [
                'total' => $group->count(),
                'completed' => $group->where('status', 'completed')->count(),
                'pending' => $group->where('status', 'pending')->count(),
                'in_progress' => $group->where('status', 'in_progress')->count()
            ];
        });

        // Statistiques par priorité
        $byPriority = $tasks->groupBy('priority')->map(function($group) {
            return [
                'total' => $group->count(),
                'completed' => $group->where('status', 'completed')->count(),
                'overdue' => $group->filter(function($task) {
                    return $task->isOverdue();
                })->count()
            ];
        });

        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'total_tasks' => $tasks->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'cancelled' => $tasks->where('status', 'cancelled')->count(),
            'overdue' => $tasks->filter(function($task) {
                return $task->isOverdue();
            })->count(),
            'by_category' => $byCategory,
            'by_priority' => $byPriority,
            'total_points' => $tasks->where('status', 'completed')->sum('points')
        ];
    }

    private function getLeaderboard($period, $limit)
    {
        $startDate = match($period) {
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };

        return User::select('users.*')
            ->selectRaw('COUNT(DISTINCT tasks.id) as completed_tasks')
            ->selectRaw('SUM(tasks.points) as total_points')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, tasks.started_at, tasks.completed_at)) as avg_completion_time')
            ->leftJoin('tasks', function($join) use ($startDate) {
                $join->on('users.id', '=', 'tasks.assigned_to')
                    ->where('tasks.status', '=', 'completed')
                    ->where('tasks.completed_at', '>=', $startDate);
            })
            ->whereIn('users.role', ['teacher', 'accountant', 'bibliothecaire', 'surveillant_general', 'secretary'])
            ->groupBy('users.id')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get();
    }

    private function sendTaskNotification($task, $type)
    {
        try {
            $this->whatsAppService->sendTaskNotification($task, $type);
        } catch (\Exception $e) {
            \Log::warning('Erreur envoi notification tâche', [
                'task_id' => $task->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function notifyTaskComment($task, $comment)
    {
        try {
            // Notifier l'assigné et le créateur
            $usersToNotify = collect([
                $task->assigned_to,
                $task->created_by
            ])->unique()->filter(function($userId) {
                return $userId != auth()->id();
            });

            foreach ($usersToNotify as $userId) {
                $user = User::find($userId);
                if ($user && $user->contact) {
                    $this->whatsAppService->sendTaskCommentNotification($task, $comment, $user);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur notification commentaire', [
                'task_id' => $task->id,
                'comment_id' => $comment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}