<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'category',
        'created_by',
        'assigned_to',
        'assigned_by',
        'due_date',
        'started_at',
        'completed_at',
        'progress',
        'requires_approval',
        'approved_by',
        'approved_at',
        'is_recurring',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_end_date',
        'notification_sent',
        'last_reminder_sent',
        'reminder_count',
        'points',
        'difficulty_level',
        'attachments',
        'notes',
        'checklist',
        'is_template'
    ];

    protected $casts = [
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'recurrence_end_date' => 'date',
        'last_reminder_sent' => 'datetime',
        'attachments' => 'array',
        'checklist' => 'array',
        'requires_approval' => 'boolean',
        'is_recurring' => 'boolean',
        'notification_sent' => 'boolean',
        'is_template' => 'boolean'
    ];

    /**
     * Relations
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function histories()
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function dependencies()
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function dependentTasks()
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_task_id');
    }

    public function assignees()
    {
        return $this->hasMany(TaskAssignee::class);
    }

    public function additionalAssignees()
    {
        return $this->belongsToMany(User::class, 'task_assignees')
            ->withPivot('status', 'started_at', 'completed_at', 'progress')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(7))
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('assigned_to', $userId)
            ->orWhereHas('assignees', function($q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }

    public function scopeRequiringApproval($query)
    {
        return $query->where('requires_approval', true)
            ->whereNull('approved_at');
    }

    /**
     * Méthodes utilitaires
     */
    public function isOverdue()
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }

    public function canBeStarted()
    {
        // Vérifier les dépendances
        $blockedByDependencies = $this->dependencies()
            ->whereHas('dependsOnTask', function($query) {
                $query->where('status', '!=', 'completed');
            })
            ->exists();

        return !$blockedByDependencies && $this->status === 'pending';
    }

    public function start()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'progress' => 0
        ]);

        $this->logHistory('status_changed', 'Tâche démarrée');
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress' => 100
        ]);

        $this->logHistory('status_changed', 'Tâche complétée');
        $this->awardPoints();
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled'
        ]);

        $this->logHistory('status_changed', 'Tâche annulée');
    }

    public function updateProgress($progress)
    {
        $this->update(['progress' => min(100, max(0, $progress))]);
        
        if ($progress >= 100 && $this->status !== 'completed') {
            $this->complete();
        }
    }

    public function approve($userId)
    {
        if ($this->requires_approval) {
            $this->update([
                'approved_by' => $userId,
                'approved_at' => now()
            ]);

            $this->logHistory('approved', 'Tâche approuvée');
        }
    }

    protected function awardPoints()
    {
        // Logique pour attribuer les points à l'utilisateur
        $user = $this->assignedTo;
        if ($user) {
            // Ajouter les points au score de l'utilisateur
            // Cette logique peut être personnalisée selon vos besoins
            \DB::table('users')->where('id', $user->id)
                ->increment('task_points', $this->points);
        }
    }

    public function logHistory($action, $description = null)
    {
        TaskHistory::create([
            'task_id' => $this->id,
            'user_id' => auth()->id() ?? $this->assigned_to,
            'action' => $action,
            'description' => $description,
            'old_values' => $this->getOriginal(),
            'new_values' => $this->getAttributes()
        ]);
    }

    public function createRecurrence()
    {
        if (!$this->is_recurring || !$this->recurrence_type) {
            return null;
        }

        $nextDate = $this->calculateNextRecurrenceDate();
        
        if ($nextDate && (!$this->recurrence_end_date || $nextDate <= $this->recurrence_end_date)) {
            $newTask = $this->replicate();
            $newTask->due_date = $nextDate;
            $newTask->status = 'pending';
            $newTask->progress = 0;
            $newTask->started_at = null;
            $newTask->completed_at = null;
            $newTask->approved_at = null;
            $newTask->approved_by = null;
            $newTask->notification_sent = false;
            $newTask->reminder_count = 0;
            $newTask->save();

            return $newTask;
        }

        return null;
    }

    protected function calculateNextRecurrenceDate()
    {
        $interval = $this->recurrence_interval ?? 1;
        $baseDate = $this->due_date ?? now();

        switch ($this->recurrence_type) {
            case 'daily':
                return $baseDate->addDays($interval);
            case 'weekly':
                return $baseDate->addWeeks($interval);
            case 'monthly':
                return $baseDate->addMonths($interval);
            case 'yearly':
                return $baseDate->addYears($interval);
            default:
                return null;
        }
    }

    /**
     * Obtenir le badge de priorité
     */
    public function getPriorityBadgeAttribute()
    {
        $badges = [
            'critical' => ['color' => 'danger', 'icon' => '🔴', 'text' => 'Critique'],
            'high' => ['color' => 'warning', 'icon' => '🟠', 'text' => 'Élevée'],
            'normal' => ['color' => 'info', 'icon' => '🟢', 'text' => 'Normale'],
            'low' => ['color' => 'secondary', 'icon' => '⚪', 'text' => 'Faible']
        ];

        return $badges[$this->priority] ?? $badges['normal'];
    }

    /**
     * Obtenir le badge de statut
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => ['color' => 'secondary', 'icon' => '⏳', 'text' => 'En attente'],
            'in_progress' => ['color' => 'primary', 'icon' => '🔄', 'text' => 'En cours'],
            'completed' => ['color' => 'success', 'icon' => '✅', 'text' => 'Terminée'],
            'cancelled' => ['color' => 'dark', 'icon' => '❌', 'text' => 'Annulée'],
            'overdue' => ['color' => 'danger', 'icon' => '⚠️', 'text' => 'En retard']
        ];

        $status = $this->isOverdue() ? 'overdue' : $this->status;
        return $badges[$status] ?? $badges['pending'];
    }

    /**
     * Obtenir les statistiques de performance
     */
    public function getPerformanceStats()
    {
        $estimatedDuration = $this->estimated_duration ?? 60; // minutes
        $actualDuration = null;
        
        if ($this->started_at && $this->completed_at) {
            $actualDuration = $this->started_at->diffInMinutes($this->completed_at);
        }

        return [
            'estimated_duration' => $estimatedDuration,
            'actual_duration' => $actualDuration,
            'efficiency' => $actualDuration ? round(($estimatedDuration / $actualDuration) * 100, 2) : null,
            'completed_on_time' => $this->completed_at && $this->due_date ? 
                $this->completed_at <= $this->due_date : null
        ];
    }
}