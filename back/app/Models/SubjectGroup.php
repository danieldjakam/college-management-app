<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubjectGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'header',
        'header_en',
        'name',
        'name_en',
        'description',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    /**
     * Get subjects in this group
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'group', 'code');
    }

    /**
     * Scope for active groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered groups
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
