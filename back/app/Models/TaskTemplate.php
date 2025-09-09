<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'description',
        'category',
        'priority',
        'estimated_duration',
        'default_checklist',
        'points',
        'difficulty_level',
        'created_by',
        'is_active'
    ];

    protected $casts = [
        'default_checklist' => 'array',
        'is_active' => 'boolean'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Créer une tâche à partir du template
     */
    public function createTask($assignedTo, $assignedBy, $dueDate = null)
    {
        return Task::create([
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'points' => $this->points,
            'difficulty_level' => $this->difficulty_level,
            'checklist' => $this->default_checklist,
            'created_by' => $assignedBy,
            'assigned_to' => $assignedTo,
            'assigned_by' => $assignedBy,
            'due_date' => $dueDate,
            'status' => 'pending'
        ]);
    }
}