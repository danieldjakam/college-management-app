<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'year_school',
        'is_after_compo',
        'is_editable',
    ];

    /**
     * Check if the system is after composition
     */
    public function isAfterCompo(): bool
    {
        return $this->is_after_compo === 'yes';
    }

    /**
     * Check if the system is editable
     */
    public function isEditable(): bool
    {
        return $this->is_editable === 'yes';
    }
}