<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualExam extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'annual_exams';

    protected $fillable = [
        'name',
        'school_year',
    ];
}