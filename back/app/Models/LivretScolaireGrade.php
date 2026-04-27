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
        'trim1',
        'trim2',
        'trim3',
    ];

    protected $casts = [
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
