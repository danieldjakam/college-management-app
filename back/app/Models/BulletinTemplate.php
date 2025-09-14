<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulletinTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'template_html',
        'css_styles',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    const TYPE_SEQUENCE = 'sequence';
    const TYPE_TRIMESTER = 'trimester';
    const TYPE_ANNUAL = 'annual';
    const TYPE_HONOR_ROLL = 'honor_roll';

    public function bulletinGenerations()
    {
        return $this->hasMany(BulletinGeneration::class, 'template_id');
    }

    public static function getTypes()
    {
        return [
            self::TYPE_SEQUENCE => 'Bulletin de Séquence',
            self::TYPE_TRIMESTER => 'Bulletin de Trimestre',
            self::TYPE_ANNUAL => 'Bulletin Annuel',
            self::TYPE_HONOR_ROLL => 'Tableau d\'Honneur'
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
