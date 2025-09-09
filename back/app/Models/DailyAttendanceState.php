<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DailyAttendanceState extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_series_id',
        'supervisor_id',
        'attendance_date',
        'entry_state',
        'exit_state',
        'entry_completed_at',
        'exit_completed_at',
        'school_year_id'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'entry_completed_at' => 'datetime',
        'exit_completed_at' => 'datetime',
    ];

    // Relations
    public function classSeries()
    {
        return $this->belongsTo(ClassSeries::class, 'class_series_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeForSeries($query, $seriesId)
    {
        return $query->where('class_series_id', $seriesId);
    }

    public function scopeForSchoolYear($query, $schoolYearId)
    {
        return $query->where('school_year_id', $schoolYearId);
    }

    // Méthodes utilitaires
    public function canDoEntry()
    {
        return $this->entry_state === 'not_done';
    }

    public function canDoExit()
    {
        return $this->entry_state === 'completed' && $this->exit_state === 'not_done';
    }

    // Nouvelles méthodes pour permettre la réouverture/modification
    public function canReopenEntry()
    {
        // Peut rouvrir l'entrée si elle est completed et c'est le même jour
        return $this->entry_state === 'completed' && 
               $this->attendance_date->isToday();
    }

    public function canReopenExit()
    {
        // Peut rouvrir la sortie si elle est completed et c'est le même jour
        return $this->exit_state === 'completed' && 
               $this->attendance_date->isToday();
    }

    public function canModifyEntry()
    {
        // Peut modifier l'entrée si elle est completed le même jour
        return $this->canReopenEntry();
    }

    public function canModifyExit()
    {
        // Peut modifier la sortie si elle est completed le même jour
        return $this->canReopenExit();
    }

    public function isEntryInProgress()
    {
        return $this->entry_state === 'in_progress';
    }

    public function isExitInProgress()
    {
        return $this->exit_state === 'in_progress';
    }

    public function isEntryCompleted()
    {
        return $this->entry_state === 'completed';
    }

    public function isExitCompleted()
    {
        return $this->exit_state === 'completed';
    }

    public function isDayCompleted()
    {
        return $this->entry_state === 'completed' && $this->exit_state === 'completed';
    }

    public function markEntryInProgress($supervisorId = null)
    {
        $this->update([
            'entry_state' => 'in_progress',
            'supervisor_id' => $supervisorId,
        ]);
    }

    public function markEntryCompleted($supervisorId = null)
    {
        $this->update([
            'entry_state' => 'completed',
            'entry_completed_at' => now(),
            'supervisor_id' => $supervisorId,
        ]);
    }

    public function markExitInProgress($supervisorId = null)
    {
        $this->update([
            'exit_state' => 'in_progress',
            'supervisor_id' => $supervisorId ?? $this->supervisor_id,
        ]);
    }

    public function markExitCompleted($supervisorId = null)
    {
        $this->update([
            'exit_state' => 'completed',
            'exit_completed_at' => now(),
            'supervisor_id' => $supervisorId ?? $this->supervisor_id,
        ]);
    }

    /**
     * Obtenir ou créer l'état d'appel pour une série et une date
     */
    public static function getOrCreateForSeriesAndDate($seriesId, $date, $schoolYearId)
    {
        return self::firstOrCreate(
            [
                'class_series_id' => $seriesId,
                'attendance_date' => $date,
                'school_year_id' => $schoolYearId
            ],
            [
                'entry_state' => 'not_done',
                'exit_state' => 'not_done'
            ]
        );
    }

    /**
     * Obtenir les statistiques d'appel pour une date
     */
    public static function getStatsForDate($date, $schoolYearId)
    {
        return self::forDate($date)
            ->forSchoolYear($schoolYearId)
            ->selectRaw('
                COUNT(*) as total_series,
                SUM(CASE WHEN entry_state = "completed" THEN 1 ELSE 0 END) as entries_completed,
                SUM(CASE WHEN exit_state = "completed" THEN 1 ELSE 0 END) as exits_completed,
                SUM(CASE WHEN entry_state = "completed" AND exit_state = "completed" THEN 1 ELSE 0 END) as days_completed
            ')
            ->first();
    }

    /**
     * Vérifier si un appel peut être fait pour une série et un type d'événement
     */
    public static function canTakeAttendance($seriesId, $eventType, $date, $schoolYearId)
    {
        $state = self::getOrCreateForSeriesAndDate($seriesId, $date, $schoolYearId);
        
        if ($eventType === 'entry') {
            return $state->canDoEntry();
        } else {
            return $state->canDoExit();
        }
    }

    /**
     * Obtenir l'état d'appel avec les détails de la série
     */
    public static function getStatesWithSeriesInfo($date, $schoolYearId, $filters = [])
    {
        $query = self::with(['classSeries.schoolClass.level.section', 'supervisor'])
            ->forDate($date)
            ->forSchoolYear($schoolYearId);

        // Appliquer les filtres
        if (isset($filters['section_id'])) {
            $query->whereHas('classSeries.schoolClass.level', function($q) use ($filters) {
                $q->where('section_id', $filters['section_id']);
            });
        }

        if (isset($filters['level_id'])) {
            $query->whereHas('classSeries.schoolClass', function($q) use ($filters) {
                $q->where('level_id', $filters['level_id']);
            });
        }

        if (isset($filters['class_id'])) {
            $query->whereHas('classSeries', function($q) use ($filters) {
                $q->where('class_id', $filters['class_id']);
            });
        }

        if (isset($filters['series_id'])) {
            $query->where('class_series_id', $filters['series_id']);
        }

        return $query->get();
    }
}