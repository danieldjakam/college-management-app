<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Relation avec les élèves (triés par ordre alphabétique)
     */
    public function students()
    {
        return $this->hasMany(Student::class)
                    ->orderByRaw('COALESCE(last_name, name) ASC')
                    ->orderByRaw('COALESCE(first_name, subname) ASC');
    }

    /**
     * Scope pour l'année courante
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope pour les années actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}