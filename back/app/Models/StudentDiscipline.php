<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDiscipline extends Model
{
    protected $table = 'student_discipline';

    protected $fillable = [
        'student_id',
        'sequence_id',
        'trimester_id',
        'delays_justified',
        'delays_unjustified',
        'absences_justified',
        'absences_unjustified',
        'blame_conduct',
        'blame_work',
        'warning_conduct',
        'warning_work',
        'detention_hours',
        'exclusion_days',
        'observations'
    ];

    protected $casts = [
        'delays_justified' => 'integer',
        'delays_unjustified' => 'integer',
        'absences_justified' => 'integer',
        'absences_unjustified' => 'integer',
        'blame_conduct' => 'integer',
        'blame_work' => 'integer',
        'warning_conduct' => 'integer',
        'warning_work' => 'integer',
        'detention_hours' => 'integer',
        'exclusion_days' => 'integer',
    ];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function trimester()
    {
        return $this->belongsTo(Trimester::class);
    }
}
