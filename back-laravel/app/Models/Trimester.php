<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trimester extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'trims';

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
        'seqIds',
        'school_year',
    ];

    protected $casts = [
        'seqIds' => 'array',
    ];

    /**
     * Get the sequences for this trimester
     */
    public function sequences()
    {
        if (!$this->seqIds) {
            return collect();
        }

        return Sequence::whereIn('id', $this->seqIds)->get();
    }
}