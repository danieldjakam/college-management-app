<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'section',
        'school_year',
    ];

    /**
     * Get the section that owns the domain
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section');
    }

    /**
     * Get the activities for the domain
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'domainId');
    }

    /**
     * Get the notes by domain
     */
    public function notesByDomain()
    {
        return $this->hasMany(NoteByDomain::class, 'domain_id');
    }
}