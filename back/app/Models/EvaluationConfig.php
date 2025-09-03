<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationConfig extends Model
{
    protected $fillable = [
        'school_year_id',
        'level_id',
        'evaluation_mode',
        'ds1_percentage',
        'ds2_percentage',
        'composition_percentage',
        'description',
        'is_active'
    ];

    protected $casts = [
        'ds1_percentage' => 'decimal:2',
        'ds2_percentage' => 'decimal:2',
        'composition_percentage' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relations
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

    public function scopeForLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    // Méthodes utilitaires
    public function getTotalPercentage()
    {
        return $this->ds1_percentage + $this->ds2_percentage + $this->composition_percentage;
    }

    public function isValidPercentage()
    {
        return $this->getTotalPercentage() == 100;
    }

    public function getEvaluationModeLabel()
    {
        return match($this->evaluation_mode) {
            '1ds_1comp' => '1 DS + 1 Composition',
            '2ds_1comp' => '2 DS + 1 Composition',
            default => 'Mode inconnu'
        };
    }

    // Méthodes statiques
    public static function getActiveConfigForLevel($schoolYearId, $levelId)
    {
        return static::active()
            ->forYear($schoolYearId)
            ->forLevel($levelId)
            ->first();
    }

    public static function deactivateOtherConfigs($schoolYearId, $levelId, $excludeId = null)
    {
        $query = static::forYear($schoolYearId)->forLevel($levelId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->update(['is_active' => false]);
    }
}
