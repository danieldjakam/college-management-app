<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_id',
        'class_id',
        'sub_com_id',
        'tag_name',
        'value',
        'school_year',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    /**
     * Get the student that owns the note
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the class that owns the note
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the sub competence that owns the note
     */
    public function subCompetence()
    {
        return $this->belongsTo(SubCompetence::class, 'sub_com_id');
    }
}