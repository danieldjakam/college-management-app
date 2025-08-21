<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'head_teacher_id',
        'is_active',
        'order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    protected $appends = [
        'teachers_count',
        'active_teachers_count'
    ];

    /**
     * Relation avec les enseignants du département
     */
    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }

    /**
     * Relation avec le chef de département
     */
    public function headTeacher()
    {
        return $this->belongsTo(Teacher::class, 'head_teacher_id');
    }

    /**
     * Obtenir les matières enseignées par les enseignants du département
     */
    public function getSubjects()
    {
        return Subject::whereHas('teacherSubjects.teacher', function($query) {
            $query->where('department_id', $this->id);
        })->distinct()->get();
    }

    /**
     * Scope pour les départements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour ordonner par ordre d'affichage
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Obtenir le nombre total d'enseignants
     */
    public function getTeachersCountAttribute()
    {
        return $this->teachers()->count();
    }

    /**
     * Obtenir le nombre d'enseignants actifs
     */
    public function getActiveTeachersCountAttribute()
    {
        return $this->teachers()->where('is_active', true)->count();
    }

    /**
     * Obtenir les enseignants actifs du département
     */
    public function getActiveTeachers()
    {
        return $this->teachers()->where('is_active', true)->get();
    }

    /**
     * Vérifier si un enseignant peut être chef de département
     */
    public function canBeHead(Teacher $teacher)
    {
        return $teacher->department_id === $this->id && $teacher->is_active;
    }

    /**
     * Définir un nouveau chef de département
     */
    public function setHeadTeacher(Teacher $teacher)
    {
        if ($this->canBeHead($teacher)) {
            $this->update(['head_teacher_id' => $teacher->id]);
            return true;
        }
        return false;
    }

    /**
     * Obtenir les statistiques du département
     */
    public function getStats()
    {
        $activeTeachers = $this->getActiveTeachers();
        
        return [
            'total_teachers' => $this->teachers_count,
            'active_teachers' => $this->active_teachers_count,
            'inactive_teachers' => $this->teachers_count - $this->active_teachers_count,
            'has_head' => !is_null($this->head_teacher_id),
            'head_teacher_name' => $this->headTeacher?->full_name,
            'subjects_count' => 0 // Temporaire - à implémenter plus tard
        ];
    }

    /**
     * Obtenir la liste des enseignants avec leurs statistiques
     */
    public function getTeachersWithStats()
    {
        return $this->teachers()
            ->with(['user', 'subjects', 'mainClasses'])
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'full_name' => $teacher->full_name,
                    'email' => $teacher->email,
                    'phone_number' => $teacher->phone_number,
                    'is_active' => $teacher->is_active,
                    'is_head' => $teacher->id === $this->head_teacher_id,
                    'subjects_count' => $teacher->subjects()->count(),
                    'classes_count' => $teacher->mainClasses()->count(),
                    'hire_date' => $teacher->hire_date?->format('d/m/Y')
                ];
            });
    }
}
