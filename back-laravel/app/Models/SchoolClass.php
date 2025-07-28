<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'class';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'level',
        'section',
        'inscriptions_olds_students',
        'inscriptions_news_students',
        'first_tranch_news_students',
        'first_tranch_olds_students',
        'second_tranch_news_students',
        'second_tranch_olds_students',
        'third_tranch_news_students',
        'third_tranch_olds_students',
        'graduation',
        'school_id',
        'school_year',
        'teacherId',
        'first_date',
        'last_date',
    ];

    protected $casts = [
        'inscriptions_olds_students' => 'decimal:2',
        'inscriptions_news_students' => 'decimal:2',
        'first_tranch_news_students' => 'decimal:2',
        'first_tranch_olds_students' => 'decimal:2',
        'second_tranch_news_students' => 'decimal:2',
        'second_tranch_olds_students' => 'decimal:2',
        'third_tranch_news_students' => 'decimal:2',
        'third_tranch_olds_students' => 'decimal:2',
        'graduation' => 'decimal:2',
    ];

    /**
     * Get the section that owns the class
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section');
    }

    /**
     * Get the teacher assigned to this class
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherId');
    }

    /**
     * Get the students for the class
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Get the notes for the class
     */
    public function notes()
    {
        return $this->hasMany(Note::class, 'class_id');
    }

    /**
     * Get the notes by subject for the class
     */
    public function notesBySubject()
    {
        return $this->hasMany(NoteBySubject::class, 'class_id');
    }

    /**
     * Get the notes by domain for the class
     */
    public function notesByDomain()
    {
        return $this->hasMany(NoteByDomain::class, 'class_id');
    }

    /**
     * Get the stats for the class
     */
    public function stats()
    {
        return $this->hasMany(Stat::class, 'class_id');
    }
}