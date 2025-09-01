<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',              // L'utilisateur (enseignant, comptable, SG)
        'supervisor_id',        // Le surveillant qui enregistre
        'school_year_id',
        'attendance_date',
        'scanned_at',
        'scanned_qr_code',      // Le code QR exact qui a été scanné
        'is_present',
        'event_type',           // entry, exit, auto
        'staff_type',          // teacher, accountant, supervisor, admin
        'work_hours',
        'late_minutes',
        'early_departure_minutes',
        'notes'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'scanned_at' => 'datetime',
        'is_present' => 'boolean',
        'work_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'early_departure_minutes' => 'integer'
    ];

    /**
     * Relation avec l'utilisateur (membre du personnel)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le superviseur qui a enregistré la présence
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    /**
     * Relation avec l'enseignant (si applicable)
     */
    public function teacher()
    {
        return $this->hasOneThrough(
            Teacher::class,
            User::class,
            'id',           // Clé étrangère sur users table
            'user_id',      // Clé étrangère sur teachers table
            'user_id',      // Clé locale sur staff_attendances table
            'id'            // Clé locale sur users table
        );
    }

    /**
     * Scope pour filtrer par date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    /**
     * Scope pour filtrer par utilisateur
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour filtrer par type de personnel
     */
    public function scopeForStaffType($query, $staffType)
    {
        return $query->where('staff_type', $staffType);
    }

    /**
     * Scope pour filtrer par année scolaire
     */
    public function scopeForSchoolYear($query, $schoolYearId)
    {
        return $query->where('school_year_id', $schoolYearId);
    }

    /**
     * Scope pour les présents
     */
    public function scopePresent($query)
    {
        return $query->where('is_present', true);
    }

    /**
     * Scope pour les absents
     */
    public function scopeAbsent($query)
    {
        return $query->where('is_present', false);
    }

    /**
     * Scope pour les entrées
     */
    public function scopeEntries($query)
    {
        return $query->where('event_type', 'entry');
    }

    /**
     * Scope pour les sorties
     */
    public function scopeExits($query)
    {
        return $query->where('event_type', 'exit');
    }

    /**
     * Scope pour les retards
     */
    public function scopeLate($query)
    {
        return $query->where('late_minutes', '>', 0);
    }

    /**
     * Scope pour les départs anticipés
     */
    public function scopeEarlyDeparture($query)
    {
        return $query->where('early_departure_minutes', '>', 0);
    }

    /**
     * Calculer le temps de travail effectif
     */
    public function getEffectiveWorkHoursAttribute()
    {
        if (!$this->work_hours) {
            return 0;
        }

        $effectiveHours = $this->work_hours;

        // Déduire les minutes de retard
        if ($this->late_minutes > 0) {
            $effectiveHours -= ($this->late_minutes / 60);
        }

        // Déduire les minutes de départ anticipé
        if ($this->early_departure_minutes > 0) {
            $effectiveHours -= ($this->early_departure_minutes / 60);
        }

        return max(0, round($effectiveHours, 2));
    }

    /**
     * Obtenir le statut de ponctualité
     */
    public function getPunctualityStatusAttribute()
    {
        if ($this->late_minutes > 0 && $this->early_departure_minutes > 0) {
            return 'late_and_early';
        } elseif ($this->late_minutes > 0) {
            return 'late';
        } elseif ($this->early_departure_minutes > 0) {
            return 'early_departure';
        } else {
            return 'punctual';
        }
    }

    /**
     * Obtenir le libellé du type de personnel
     */
    public function getStaffTypeLabel()
    {
        $labels = [
            'teacher' => 'Enseignant',
            'accountant' => 'Comptable',
            'supervisor' => 'Surveillant Général',
            'admin' => 'Administrateur'
        ];

        return $labels[$this->staff_type] ?? $this->staff_type;
    }

    /**
     * Obtenir le pourcentage de présence pour un utilisateur sur une période
     */
    public static function getAttendanceRate($userId, $startDate, $endDate)
    {
        $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $presentDays = self::forUser($userId)
            ->forDateRange($startDate, $endDate)
            ->present()
            ->distinct('attendance_date')
            ->count();

        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }

    /**
     * Obtenir les statistiques d'un membre du personnel pour une période
     */
    public static function getStaffStats($userId, $startDate, $endDate)
    {
        $attendances = self::forUser($userId)
            ->forDateRange($startDate, $endDate)
            ->get();

        $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $presentDays = $attendances->where('is_present', true)->unique('attendance_date')->count();
        $absentDays = $totalDays - $presentDays;
        $lateDays = $attendances->where('late_minutes', '>', 0)->count();
        $earlyDepartureDays = $attendances->where('early_departure_minutes', '>', 0)->count();
        $totalWorkHours = $attendances->sum('work_hours');
        $averageLateMinutes = $attendances->where('late_minutes', '>', 0)->avg('late_minutes') ?: 0;

        return [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'early_departure_days' => $earlyDepartureDays,
            'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0,
            'punctuality_rate' => $presentDays > 0 ? round((($presentDays - $lateDays) / $presentDays) * 100, 2) : 0,
            'total_work_hours' => round($totalWorkHours, 2),
            'average_late_minutes' => round($averageLateMinutes, 1)
        ];
    }

    /**
     * Obtenir les statistiques par type de personnel
     */
    public static function getStaffTypeStats($staffType, $startDate, $endDate)
    {
        $attendances = self::forStaffType($staffType)
            ->forDateRange($startDate, $endDate)
            ->get();

        $totalStaff = $attendances->unique('user_id')->count();
        $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $presentDays = $attendances->where('is_present', true)->count();
        $absentDays = ($totalStaff * $totalDays) - $presentDays;

        return [
            'staff_type' => $staffType,
            'total_staff' => $totalStaff,
            'total_possible_days' => $totalStaff * $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'present_count' => $attendances->where('is_present', true)->unique('user_id')->count(),
            'late_count' => $attendances->where('late_minutes', '>', 0)->unique('user_id')->count(),
            'attendance_rate' => $totalStaff > 0 && $totalDays > 0 ?
                round(($presentDays / ($totalStaff * $totalDays)) * 100, 2) : 0
        ];
    }

}

