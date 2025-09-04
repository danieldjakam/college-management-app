<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeolocationZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'latitude',
        'longitude',
        'radius',
        'enabled',
        'created_by'
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'enabled' => 'boolean',
        'radius' => 'integer'
    ];

    /**
     * Relation avec l'utilisateur qui a créé la zone
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope pour récupérer seulement les zones actives
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Calculer la distance entre deux points en mètres
     * Utilise la formule de Haversine
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Rayon de la Terre en mètres

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Vérifier si une position est dans cette zone
     */
    public function containsPosition($latitude, $longitude)
    {
        if (!$this->enabled) {
            return false;
        }

        $distance = self::calculateDistance(
            $this->latitude,
            $this->longitude,
            $latitude,
            $longitude
        );

        return $distance <= $this->radius;
    }

    /**
     * Récupérer la zone la plus proche d'une position
     */
    public static function getClosestZone($latitude, $longitude)
    {
        $zones = self::enabled()->get();
        $closestZone = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($zones as $zone) {
            $distance = self::calculateDistance(
                $zone->latitude,
                $zone->longitude,
                $latitude,
                $longitude
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestZone = $zone;
                $closestZone->distance = $distance;
            }
        }

        return $closestZone;
    }
}
