<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'appreciationsNber',
        'section',
        'domainId',
        'school_year',
    ];

    /**
     * Get the section that owns the activity
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section');
    }

    /**
     * Get the domain that owns the activity
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domainId');
    }

    /**
     * Get the notes by domain for this activity
     */
    public function notesByDomain()
    {
        return $this->hasMany(NoteByDomain::class, 'activitieId');
    }
}