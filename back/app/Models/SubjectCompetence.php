<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectCompetence extends Model
{
    protected $fillable = [
        'teacher_id',
        'class_series_id',
        'class_series_subject_id',
        'trimester_id',
        'competence_1',
        'competence_2',
    ];

    /**
     * Relations
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classSeries(): BelongsTo
    {
        return $this->belongsTo(ClassSeries::class);
    }

    public function classSeriesSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSeriesSubject::class);
    }

    public function trimester(): BelongsTo
    {
        return $this->belongsTo(Trimester::class);
    }
}
