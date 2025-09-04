<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'evaluation_id',
        'sequence_id',
        'trimester_id',
        'school_year_id',
        'series_subject_id',
        'score',
        'max_score',
        'coefficient',
        'weighted_score',
        'is_absent',
        'is_excused',
        'comment'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'coefficient' => 'decimal:2',
        'weighted_score' => 'decimal:2',
        'is_absent' => 'boolean',
        'is_excused' => 'boolean'
    ];

    /**
     * Relation avec l'étudiant
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec l'évaluation
     */
    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    /**
     * Relation avec la séquence
     */
    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    /**
     * Relation avec le trimestre
     */
    public function trimester()
    {
        return $this->belongsTo(Trimester::class);
    }

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Relation avec la matière de la série
     */
    public function seriesSubject()
    {
        return $this->belongsTo(SeriesSubject::class);
    }

    /**
     * Calculer la note pondérée automatiquement
     */
    protected static function booted()
    {
        static::saving(function ($grade) {
            if ($grade->score !== null && $grade->coefficient !== null) {
                $grade->weighted_score = $grade->score * $grade->coefficient;
            }
        });
    }

    /**
     * Calculer la note sur 20
     */
    public function getScoreOn20()
    {
        if ($this->score === null || $this->max_score === null) {
            return null;
        }

        return round(($this->score / $this->max_score) * 20, 2);
    }

    /**
     * Obtenir la mention basée sur la note
     */
    public function getMention()
    {
        $scoreOn20 = $this->getScoreOn20();
        
        if ($scoreOn20 === null) {
            return null;
        }

        if ($scoreOn20 >= 16) return 'Très Bien';
        if ($scoreOn20 >= 14) return 'Bien';
        if ($scoreOn20 >= 12) return 'Assez Bien';
        if ($scoreOn20 >= 10) return 'Passable';
        
        return 'Insuffisant';
    }

    /**
     * Vérifier si la note est une réussite
     */
    public function isSuccess()
    {
        $scoreOn20 = $this->getScoreOn20();
        return $scoreOn20 !== null && $scoreOn20 >= 10;
    }

    /**
     * Scope pour les notes avec score
     */
    public function scopeWithScore($query)
    {
        return $query->whereNotNull('score');
    }

    /**
     * Scope pour les notes d'absence
     */
    public function scopeAbsent($query)
    {
        return $query->where('is_absent', true);
    }

    /**
     * Scope pour les notes réussies
     */
    public function scopeSuccess($query)
    {
        return $query->whereRaw('(score / max_score) * 20 >= 10');
    }

    /**
     * Obtenir le rang de l'étudiant pour cette évaluation
     */
    public function getRank()
    {
        if ($this->score === null) {
            return null;
        }

        $betterScores = static::where('evaluation_id', $this->evaluation_id)
                             ->whereNotNull('score')
                             ->where('score', '>', $this->score)
                             ->count();

        return $betterScores + 1;
    }
}