<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subname',
        'class_series_id',
        'email',
        'phone_number',
        'birthday',
        'birthday_place',
        'sex',
        'father_name',
        'profession',
        'status',
        'is_new',
        'is_active'
    ];

    protected $casts = [
        'birthday' => 'date',
        'is_new' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Relation avec la série de classe
     */
    public function classSeries()
    {
        return $this->belongsTo(ClassSeries::class, 'class_series_id');
    }

    /**
     * Relation avec la classe via la série
     */
    public function schoolClass()
    {
        return $this->hasOneThrough(SchoolClass::class, ClassSeries::class, 'id', 'id', 'class_series_id', 'class_id');
    }

    /**
     * Scope pour les étudiants actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtenir le nom complet
     */
    public function getFullNameAttribute()
    {
        return $this->name . ' ' . $this->subname;
    }
}