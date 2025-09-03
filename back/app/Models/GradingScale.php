<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingScale extends Model
{
    protected $fillable = [
        'school_year_id',
        'level_id',
        'grade_code',
        'grade_label',
        'min_score',
        'max_score',
        'appreciation',
        'color_code',
        'passing_threshold',
        'is_passing_grade',
        'is_active',
        'order'
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'passing_threshold' => 'decimal:2',
        'is_passing_grade' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer'
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

    public function scopeGlobal($query)
    {
        return $query->whereNull('level_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('max_score', 'desc');
    }

    // Méthodes utilitaires
    public function getScoreRange()
    {
        return "{$this->min_score} - {$this->max_score}";
    }

    public function isScoreInRange($score)
    {
        return $score >= $this->min_score && $score <= $this->max_score;
    }

    // Méthodes statiques
    public static function getGradeForScore($score, $schoolYearId, $levelId = null)
    {
        $query = static::active()
            ->forYear($schoolYearId)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->ordered();

        // Chercher d'abord une échelle spécifique au niveau
        if ($levelId) {
            $grade = $query->clone()->forLevel($levelId)->first();
            if ($grade) {
                return $grade;
            }
        }

        // Sinon chercher une échelle globale
        return $query->global()->first();
    }

    public static function getDefaultGradingScale()
    {
        return [
            ['code' => 'TB', 'label' => 'Très Bien', 'min' => 18, 'max' => 20, 'color' => '#28a745', 'appreciation' => 'Excellent travail, félicitations !'],
            ['code' => 'B', 'label' => 'Bien', 'min' => 16, 'max' => 17.99, 'color' => '#20c997', 'appreciation' => 'Bon travail, continue ainsi !'],
            ['code' => 'AB', 'label' => 'Assez Bien', 'min' => 14, 'max' => 15.99, 'color' => '#17a2b8', 'appreciation' => 'Travail satisfaisant, peut mieux faire.'],
            ['code' => 'P', 'label' => 'Passable', 'min' => 12, 'max' => 13.99, 'color' => '#ffc107', 'appreciation' => 'Travail moyen, des efforts sont nécessaires.'],
            ['code' => 'M', 'label' => 'Médiocre', 'min' => 10, 'max' => 11.99, 'color' => '#fd7e14', 'appreciation' => 'Travail insuffisant, beaucoup d\'efforts à fournir.'],
            ['code' => 'I', 'label' => 'Insuffisant', 'min' => 0, 'max' => 9.99, 'color' => '#dc3545', 'appreciation' => 'Travail très insuffisant, redoublement d\'efforts requis.']
        ];
    }

    public static function createDefaultScale($schoolYearId, $levelId = null)
    {
        $defaultScale = self::getDefaultGradingScale();
        
        foreach ($defaultScale as $index => $scale) {
            self::create([
                'school_year_id' => $schoolYearId,
                'level_id' => $levelId,
                'grade_code' => $scale['code'],
                'grade_label' => $scale['label'],
                'min_score' => $scale['min'],
                'max_score' => $scale['max'],
                'appreciation' => $scale['appreciation'],
                'color_code' => $scale['color'],
                'is_passing_grade' => $scale['code'] !== 'I', // Seul "I" n'est pas passant
                'is_active' => true,
                'order' => $index
            ]);
        }
    }
}
