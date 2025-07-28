<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'school_year',
    ];

    /**
     * Get the classes for the section
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'section');
    }

    /**
     * Get the subjects for the section
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'section');
    }

    /**
     * Get the domains for the section
     */
    public function domains()
    {
        return $this->hasMany(Domain::class, 'section');
    }

    /**
     * Get the competences for the section
     */
    public function competences()
    {
        return $this->hasMany(Competence::class, 'section');
    }

    /**
     * Get the activities for the section
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'section');
    }

    /**
     * Get the sub competences for the section
     */
    public function subCompetences()
    {
        return $this->hasMany(SubCompetence::class, 'section');
    }

    /**
     * Get total number of classes in this section
     */
    public function getTotalClassAttribute()
    {
        return $this->classes()->count();
    }
}