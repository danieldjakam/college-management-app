<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LivretScolaireGrade extends Model
{
    protected $fillable = [
        'student_id',
        'class_series_subject_id',
        'school_year_id',
        'modified_by',
        'ev1', 'ev2', 'comp1',
        'ev3', 'ev4', 'comp2',
        'comp3',
        'trim1',
        'trim2',
        'trim3',
    ];

    protected $casts = [
        'ev1' => 'decimal:2',
        'ev2' => 'decimal:2',
        'comp1' => 'decimal:2',
        'ev3' => 'decimal:2',
        'ev4' => 'decimal:2',
        'comp2' => 'decimal:2',
        'comp3' => 'decimal:2',
        'trim1' => 'decimal:2',
        'trim2' => 'decimal:2',
        'trim3' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classSeriesSubject()
    {
        return $this->belongsTo(ClassSeriesSubject::class);
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
