<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainTeacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'class_series_id',
        'school_year_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Relation avec l'enseignant
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relation avec la série de classe (ex: 6ème A, 6ème B)
     */
    public function classSeries()
    {
        return $this->belongsTo(ClassSeries::class, 'class_series_id');
    }

    /**
     * Alias pour compatibilité - retourne la série
     */
    public function schoolClass()
    {
        return $this->classSeries();
    }

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }
}