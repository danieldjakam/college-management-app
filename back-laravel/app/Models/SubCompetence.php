<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCompetence extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'sub_com';

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
        'slug',
        'section',
        'comId',
        'tags',
        'school_year',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    /**
     * Get the section that owns the sub competence
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section');
    }

    /**
     * Get the competence that owns the sub competence
     */
    public function competence()
    {
        return $this->belongsTo(Competence::class, 'comId');
    }

    /**
     * Get the notes for the sub competence
     */
    public function notes()
    {
        return $this->hasMany(Note::class, 'sub_com_id');
    }
}