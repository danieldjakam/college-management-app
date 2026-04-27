<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamClass extends Model
{
    protected $fillable = [
        'class_series_id',
        'school_year_id',
    ];

    public function classSeries()
    {
        return $this->belongsTo(ClassSeries::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
