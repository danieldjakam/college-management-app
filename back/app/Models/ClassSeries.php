<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSeries extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'name',
        'code',
        'capacity',
        'is_active'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Relation avec la classe mère
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Relation avec les étudiants
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'class_series_id');
    }

    /**
     * Scope pour les séries actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtenir le nom complet de la série
     */
    public function getFullNameAttribute()
    {
        return $this->schoolClass->name . ' ' . $this->name;
    }
}