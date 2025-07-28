<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'exam_id',
        'totalPoints',
        'school_year',
    ];

    protected $casts = [
        'totalPoints' => 'decimal:2',
    ];

    /**
     * Get the student that owns the stat
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the class that owns the stat
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}