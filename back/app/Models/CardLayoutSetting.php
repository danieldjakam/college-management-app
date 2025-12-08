<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CardLayoutSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'description',
    ];

    /**
     * Récupérer tous les paramètres sous forme de tableau key-value
     */
    public static function getAllSettings()
    {
        return Cache::remember('card_layout_settings', 3600, function () {
            return self::pluck('setting_value', 'setting_key')->toArray();
        });
    }

    /**
     * Mettre à jour un paramètre
     */
    public static function updateSetting($key, $value)
    {
        self::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );
        
        Cache::forget('card_layout_settings');
    }

    /**
     * Récupérer un paramètre spécifique
     */
    public static function getSetting($key, $default = null)
    {
        $settings = self::getAllSettings();
        return $settings[$key] ?? $default;
    }
}
