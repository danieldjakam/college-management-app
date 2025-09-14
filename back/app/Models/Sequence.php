<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'trimester_id',
        'school_year_id',
        'start_date',
        'end_date',
        'is_active',
        'is_current',
        'is_completed',
        'is_composition',
        'is_locked'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_current' => 'boolean',
        'is_completed' => 'boolean',
        'is_composition' => 'boolean',
        'is_locked' => 'boolean'
    ];

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
     * Relation avec les évaluations
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Relation avec les notes
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Scope pour les séquences actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour la séquence courante
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Obtenir la séquence courante
     */
    public static function getCurrentSequence()
    {
        return static::where('is_current', true)
                     ->where('is_active', true)
                     ->first();
    }

    /**
     * Vérifier si la séquence est en cours
     */
    public function isInProgress()
    {
        $now = now()->toDateString();
        return $now >= $this->start_date->toDateString() && $now <= $this->end_date->toDateString();
    }

    /**
     * Obtenir le nom complet de la séquence
     */
    public function getFullNameAttribute()
    {
        return $this->name . ' - ' . $this->trimester->name;
    }

    /**
     * Calculer le pourcentage de progression de la séquence
     */
    public function getProgressPercentage()
    {
        $now = now();
        $start = $this->start_date;
        $end = $this->end_date;

        if ($now < $start) {
            return 0;
        }
        
        if ($now > $end) {
            return 100;
        }

        $totalDays = $start->diffInDays($end);
        $elapsedDays = $start->diffInDays($now);

        return round(($elapsedDays / $totalDays) * 100, 2);
    }

    /**
     * Vérifier si les évaluations peuvent être saisies
     */
    public function canAcceptEvaluations()
    {
        return $this->is_active && ($this->isInProgress() || $this->is_current);
    }
}