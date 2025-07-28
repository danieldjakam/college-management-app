<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competence extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'com';

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
        'section',
        'school_year',
    ];

    /**
     * Get the section that owns the competence
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section');
    }

    /**
     * Get the sub competences for the competence
     */
    public function subCompetences()
    {
        return $this->hasMany(SubCompetence::class, 'comId');
    }
}