<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'sequence_id',
        'trimester_id',
        'school_year_id',
        'series_subject_id',
        'teacher_id',
        'date',
        'max_score',
        'coefficient',
        'is_active',
        'description'
    ];

    protected $casts = [
        'date' => 'date',
        'max_score' => 'decimal:2',
        'coefficient' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Types d'évaluations
    const TYPE_INTERROGATION = 'interrogation';
    const TYPE_DEVOIR = 'devoir';
    const TYPE_COMPOSITION = 'composition';
    const TYPE_TP = 'tp';
    const TYPE_CONTROLE = 'controle';

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
     * Relation avec l'enseignant
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relation avec les notes
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Obtenir tous les types d'évaluations disponibles
     */
    public static function getTypes()
    {
        return [
            self::TYPE_INTERROGATION => 'Interrogation écrite',
            self::TYPE_DEVOIR => 'Devoir surveillé',
            self::TYPE_COMPOSITION => 'Composition',
            self::TYPE_TP => 'Travaux pratiques',
            self::TYPE_CONTROLE => 'Contrôle continu'
        ];
    }

    /**
     * Obtenir le libellé du type d'évaluation
     */
    public function getTypeLabel()
    {
        return self::getTypes()[$this->type] ?? 'Non défini';
    }

    /**
     * Scope pour les évaluations actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope par type d'évaluation
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Obtenir la moyenne de classe pour cette évaluation
     */
    public function getClassAverage()
    {
        return $this->grades()
                    ->whereNotNull('score')
                    ->avg('score');
    }

    /**
     * Obtenir le taux de réussite (notes >= 10/20)
     */
    public function getSuccessRate()
    {
        $totalGrades = $this->grades()->whereNotNull('score')->count();
        
        if ($totalGrades == 0) {
            return 0;
        }

        $successGrades = $this->grades()
                             ->whereNotNull('score')
                             ->where('score', '>=', ($this->max_score / 2))
                             ->count();

        return round(($successGrades / $totalGrades) * 100, 2);
    }

    /**
     * Vérifier si l'évaluation peut être modifiée
     */
    public function canBeEdited()
    {
        return $this->is_active && $this->sequence && $this->sequence->canAcceptEvaluations();
    }
}