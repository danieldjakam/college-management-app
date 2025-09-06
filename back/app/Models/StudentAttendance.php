<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $table = 'student_attendances';

    protected $fillable = [
        'student_id',
        'school_class_id',
        'attendance_date',
        'is_present',
        'school_year_id',
        'marked_by',
        'attendance_type',
        'notes'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'is_present' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relation avec l'étudiant
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Relation avec la classe
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    /**
     * Relation avec l'utilisateur qui a marqué la présence
     */
    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Scope pour filtrer par date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('attendance_date', $date);
    }

    /**
     * Scope pour filtrer par classe
     */
    public function scopeForClass($query, $classId)
    {
        return $query->where('school_class_id', $classId);
    }

    /**
     * Scope pour filtrer par étudiant
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope pour filtrer par année scolaire
     */
    public function scopeForSchoolYear($query, $schoolYearId)
    {
        return $query->where('school_year_id', $schoolYearId);
    }

    /**
     * Scope pour filtrer par type de présence
     */
    public function scopeForAttendanceType($query, $type)
    {
        return $query->where('attendance_type', $type);
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
     * Scope pour une période de dates
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }
}