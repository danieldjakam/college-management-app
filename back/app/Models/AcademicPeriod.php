<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'percentage',
        'order',
        'is_active',
        'school_year_id',
        'description'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'order' => 'integer',
        'is_active' => 'boolean'
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeForYear($query, $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

    public static function validatePercentages($periods, $excludeId = null)
    {
        $total = collect($periods)->sum('percentage');
        
        if ($excludeId) {
            $existing = static::where('school_year_id', $periods[0]['school_year_id'] ?? null)
                            ->where('id', '!=', $excludeId)
                            ->sum('percentage');
            $total += $existing;
        }

        return [
            'total' => $total,
            'is_valid' => $total == 100,
            'difference' => 100 - $total
        ];
    }

    public static function getTotalPercentageForYear($yearId, $excludeId = null)
    {
        $query = static::where('school_year_id', $yearId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->sum('percentage');
    }
}
