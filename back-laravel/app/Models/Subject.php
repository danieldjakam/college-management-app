<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'over',
        'section',
        'school_year',
    ];

    /**
     * Get the section that owns the subject
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section');
    }

    /**
     * Get the notes by subject
     */
    public function notesBySubject()
    {
        return $this->hasMany(NoteBySubject::class, 'subject_id');
    }
}