<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSystemConfig extends Model
{
    use HasFactory;

    protected $table = 'academic_system_config';

    protected $fillable = [
        'type',
        'periods_count',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'periods_count' => 'integer'
    ];

    public static function getActiveConfig()
    {
        return static::where('is_active', true)->first();
    }

    public static function getDefaultPercentages()
    {
        $config = static::getActiveConfig();
        
        if (!$config) {
            return [];
        }

        $count = $config->periods_count;
        $percentage = 100 / $count;

        $periods = [];
        for ($i = 1; $i <= $count; $i++) {
            $periods[] = [
                'name' => ucfirst($config->type) . " {$i}",
                'percentage' => round($percentage, 2),
                'order' => $i
            ];
        }

        return $periods;
    }
}
