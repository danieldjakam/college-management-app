<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteByDomain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'notesByDomain';

    protected $fillable = [
        'student_id',
        'exam_id',
        'class_id',
        'domain_id',
        'activitieId',
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
     * Get the domain that owns the note
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    /**
     * Get the activity that owns the note
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activitieId');
    }
}