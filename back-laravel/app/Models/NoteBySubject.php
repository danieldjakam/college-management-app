<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteBySubject extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'notesBySubject';

    protected $fillable = [
        'student_id',
        'exam_id',
        'class_id',
        'subject_id',
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
     * Get the subject that owns the note
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}