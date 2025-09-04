<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trimester extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'school_year_id',
        'start_date',
        'end_date',
        'is_active',
        'is_current'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_current' => 'boolean'
    ];

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Relation avec les séquences
     */
    public function sequences()
    {
        return $this->hasMany(Sequence::class);
    }

    /**
     * Relation avec les évaluations
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Scope pour les trimestres actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour le trimestre courant
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Obtenir le trimestre courant
     */
    public static function getCurrentTrimester()
    {
        return static::where('is_current', true)
                     ->where('is_active', true)
                     ->first();
    }

    /**
     * Vérifier si le trimestre est en cours
     */
    public function isInProgress()
    {
        $now = now()->toDateString();
        return $now >= $this->start_date->toDateString() && $now <= $this->end_date->toDateString();
    }

    /**
     * Obtenir les séquences actives de ce trimestre
     */
    public function getActiveSequences()
    {
        return $this->sequences()->active()->orderBy('number')->get();
    }

    /**
     * Calculer le pourcentage de progression du trimestre
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
}