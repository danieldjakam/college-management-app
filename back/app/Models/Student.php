<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'place_of_birth',
        'gender',
        'parent_name',
        'parent_phone',
        'parent_email',
        'address',
        'class_series_id',
        'school_year_id',
        'student_number',
        'order',
        'is_active',
        // Anciens champs pour compatibilité
        'name',
        'subname',
        'email',
        'phone_number',
        'birthday',
        'birthday_place',
        'sex',
        'father_name',
        'profession',
        'status',
        'is_new'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'birthday' => 'date',
        'is_new' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Relation avec la série de classe
     */
    public function classSeries()
    {
        return $this->belongsTo(ClassSeries::class, 'class_series_id');
    }

    /**
     * Relation avec la classe via la série
     */
    public function schoolClass()
    {
        return $this->hasOneThrough(SchoolClass::class, ClassSeries::class, 'id', 'id', 'class_series_id', 'class_id');
    }

    /**
     * Scope pour les étudiants actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour une année scolaire donnée
     */
    public function scopeForYear($query, $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

    /**
     * Scope pour une série de classe donnée
     */
    public function scopeForSeries($query, $seriesId)
    {
        return $query->where('class_series_id', $seriesId);
    }

    /**
     * Obtenir le nom complet (format: Nom + Prénom)
     */
    public function getFullNameAttribute()
    {
        if ($this->last_name && $this->first_name) {
            return $this->last_name . ' ' . $this->first_name;
        }
        // Fallback pour compatibilité (inverser aussi pour cohérence)
        if ($this->subname && $this->name) {
            return $this->subname . ' ' . $this->name;
        }
        return $this->name ?: '';
    }

    /**
     * Générer un numéro d'élève unique
     */
    public static function generateStudentNumber($year, $seriesId)
    {
        $yearPrefix = date('Y', strtotime($year));
        $lastStudent = self::where('student_number', 'like', $yearPrefix . '%')
                          ->orderBy('student_number', 'desc')
                          ->first();
        
        if ($lastStudent) {
            $lastNumber = intval(substr($lastStudent->student_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $yearPrefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}