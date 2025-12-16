<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MergedHonorRollPDF extends Model
{
    protected $table = 'merged_honor_roll_p_d_f_s';

    protected $fillable = [
        'trimester_id',
        'section_id',
        'level_id',
        'class_id',
        'series_id',
        'file_path',
        'filename',
        'certificate_count',
        'file_size',
        'status'
    ];

    protected $casts = [
        'certificate_count' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relation avec Trimester
     */
    public function trimester()
    {
        return $this->belongsTo(Trimester::class);
    }

    /**
     * Relation avec Section
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Relation avec Level
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Relation avec SchoolClass
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Relation avec ClassSeries
     */
    public function classSeries()
    {
        return $this->belongsTo(ClassSeries::class, 'series_id');
    }

    /**
     * Obtenir l'URL de téléchargement
     */
    public function getDownloadUrlAttribute()
    {
        return "/api/honor-rolls/merged/{$this->id}/download";
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
