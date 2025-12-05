<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'group',
        'section_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'section_id' => 'integer'
    ];

    /**
     * Obtenir le nom complet du groupe
     */
    public function getGroupNameAttribute()
    {
        if (!isset($this->group)) {
            return null;
        }

        // Si la relation est déjà chargée (eager loading), l'utiliser
        if ($this->relationLoaded('subjectGroup') && $this->subjectGroup) {
            return $this->subjectGroup->name;
        }

        // Sinon, récupérer le nom du groupe dynamiquement depuis la table subject_groups
        $subjectGroup = \App\Models\SubjectGroup::where('code', $this->group)->first();

        return $subjectGroup ? $subjectGroup->name : $this->group; // Fallback to code if group not found
    }

    /**
     * Relation avec le groupe de matières
     */
    public function subjectGroup()
    {
        return $this->belongsTo(SubjectGroup::class, 'group', 'code');
    }

    /**
     * Relation avec la section
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Relation avec les séries de classe (matières enseignées)
     */
    public function classSeries()
    {
        return $this->belongsToMany(ClassSeries::class, 'class_series_subjects')
                    ->withPivot('coefficient', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Relation avec les séries actives uniquement
     */
    public function activeClassSeries()
    {
        return $this->belongsToMany(ClassSeries::class, 'class_series_subjects')
                    ->withPivot('coefficient', 'is_active')
                    ->wherePivot('is_active', true)
                    ->withTimestamps();
    }

    /**
     * Relation avec les enseignants
     */
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects')
                    ->withPivot('class_series_id', 'school_year_id', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Relation avec les assignations enseignant-matière
     */
    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    /**
     * Scope pour les matières actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtenir le coefficient pour une série donnée
     */
    public function getCoefficientForSeries($seriesId)
    {
        $pivot = $this->classSeries()->where('class_series_id', $seriesId)->first();
        return $pivot ? $pivot->pivot->coefficient : null;
    }

    /**
     * Vérifier si la matière est utilisée dans au moins une série
     */
    public function isUsedInSeries()
    {
        return $this->classSeries()->exists();
    }

    /**
     * Obtenir les enseignants pour une série et année données
     */
    public function getTeachersForSeriesAndYear($seriesId, $yearId)
    {
        return $this->teachers()
                    ->wherePivot('class_series_id', $seriesId)
                    ->wherePivot('school_year_id', $yearId)
                    ->wherePivot('is_active', true)
                    ->get();
    }
}