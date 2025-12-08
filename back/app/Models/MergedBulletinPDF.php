<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MergedBulletinPDF extends Model
{
    protected $table = 'merged_bulletin_p_d_f_s';

    protected $fillable = [
        'class_series_id',
        'period_type',
        'period_identifier',
        'file_path',
        'filename',
        'bulletin_count',
        'file_size',
        'status'
    ];

    protected $casts = [
        'bulletin_count' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relation avec ClassSeries
     */
    public function classSeries()
    {
        return $this->belongsTo(ClassSeries::class);
    }

    /**
     * Obtenir l'URL de téléchargement
     */
    public function getDownloadUrlAttribute()
    {
        return "/api/bulletins/download-merged/{$this->id}";
    }

    /**
     * Obtenir la taille formatée
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }
}
