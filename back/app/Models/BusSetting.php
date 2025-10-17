<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusSetting extends Model
{
    protected $fillable = [
        'monthly_price',
        'is_active',
        'description'
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Obtenir le tarif actuel du bus
     */
    public static function getCurrentPrice()
    {
        $setting = self::where('is_active', true)->first();
        return $setting ? $setting->monthly_price : 0;
    }

    /**
     * Mettre à jour le tarif du bus
     */
    public static function updatePrice($price, $description = null)
    {
        $setting = self::first();

        if ($setting) {
            $setting->update([
                'monthly_price' => $price,
                'description' => $description
            ]);
        } else {
            $setting = self::create([
                'monthly_price' => $price,
                'is_active' => true,
                'description' => $description
            ]);
        }

        return $setting;
    }
}
