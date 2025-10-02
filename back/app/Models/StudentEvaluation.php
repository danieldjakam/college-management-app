<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'class_series_id',
        'school_year_id',
        'teacher_id',
        'entered_by',
        'trimester',
        'evaluation_type',
        'score',
        'is_absent',
        'notes',
        'entered_at'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'is_absent' => 'boolean',
        'entered_at' => 'datetime'
    ];

    /**
     * Relation avec l'élève
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec la matière
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Relation avec la série de classe
     */
    public function classSeries()
    {
        return $this->belongsTo(ClassSeries::class);
    }

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Relation avec l'enseignant
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Relation avec l'utilisateur qui a saisi
     */
    public function enteredByUser()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    /**
     * Scope pour filtrer par trimestre
     */
    public function scopeForTrimester($query, $trimester)
    {
        return $query->where('trimester', $trimester);
    }

    /**
     * Scope pour filtrer par type d'évaluation
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('evaluation_type', $type);
    }

    /**
     * Scope pour filtrer par classe-série
     */
    public function scopeForClassSeries($query, $classSeriesId)
    {
        return $query->where('class_series_id', $classSeriesId);
    }

    /**
     * Scope pour filtrer par matière
     */
    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope pour les élèves présents
     */
    public function scopePresent($query)
    {
        return $query->where('is_absent', false);
    }

    /**
     * Scope pour les élèves absents
     */
    public function scopeAbsent($query)
    {
        return $query->where('is_absent', true);
    }

    /**
     * Calculer si l'élève a réussi (note >= 10)
     */
    public function isPassed()
    {
        if ($this->is_absent || $this->score === null) {
            return false;
        }

        return $this->score >= 10;
    }

    /**
     * Obtenir le libellé du type d'évaluation
     */
    public function getEvaluationTypeLabel()
    {
        $labels = [
            'eval1' => 'EVAL1',
            'eval2' => 'EVAL2',
            'comp' => 'COMP'
        ];

        return $labels[$this->evaluation_type] ?? $this->evaluation_type;
    }

    /**
     * Obtenir les statistiques pour une classe-série, matière et trimestre
     */
    public static function getStatistics($classSeriesId, $subjectId, $trimester, $evaluationType, $schoolYearId)
    {
        $evaluations = self::where('class_series_id', $classSeriesId)
            ->where('subject_id', $subjectId)
            ->where('school_year_id', $schoolYearId)
            ->where('trimester', $trimester)
            ->where('evaluation_type', $evaluationType)
            ->get();

        $present = $evaluations->where('is_absent', false)->where('score', '!=', null);
        $passed = $present->where('score', '>=', 10);
        $failed = $present->where('score', '<', 10);

        $presentCount = $present->count();

        return [
            'effectif_present' => $presentCount,
            'admis' => $passed->count(),
            'echoue' => $failed->count(),
            'taux_reussite' => $presentCount > 0 ? round(($passed->count() / $presentCount) * 100, 2) : 0,
            'taux_echec' => $presentCount > 0 ? round(($failed->count() / $presentCount) * 100, 2) : 0,
        ];
    }
}
