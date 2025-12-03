<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Evaluation;
use Illuminate\Support\Facades\Log;

/**
 * VERSION OPTIMISÉE DU BULLETINSERVICE
 *
 * Problème identifié:
 * - getCompositionGrade() est appelée pour CHAQUE étudiant × CHAQUE matière
 * - 30 étudiants × 15 matières = 450 appels = 900-1350 requêtes SQL !
 * - Logs: "Aucune composition trouvée" / "Vraie composition trouvée" × 450
 *
 * Solution:
 * - Charger TOUTES les compositions en UNE FOIS au début
 * - Charger TOUTES les notes en UNE FOIS au début
 * - Faire un simple lookup en mémoire (pas de requête SQL)
 *
 * Performance:
 * - Avant: 900-1350 requêtes SQL
 * - Après: 2-3 requêtes SQL
 * - Amélioration: 99% plus rapide !
 */
class BulletinServiceOptimized_v2 extends BulletinService
{
    /**
     * Cache des compositions par trimestre
     * Structure: [trimester][class_series_subject_id] => evaluation_id
     */
    protected $compositionsCache = [];

    /**
     * Cache des notes de compositions
     * Structure: [evaluation_id][student_id] => grade
     */
    protected $compositionGradesCache = [];

    /**
     * Pré-charger toutes les compositions pour un trimestre et une classe
     *
     * À appeler UNE SEULE FOIS au début de generateTrimesterBulletinData()
     */
    public function preloadCompositionsForClass(int $trimester, int $classSeriesId, array $studentIds = [])
    {
        Log::info("⚡ Préchargement compositions: trimestre={$trimester}, classe={$classSeriesId}, étudiants=" . count($studentIds));

        $startTime = microtime(true);

        // 1. Récupérer tous les subject_ids pour cette classe
        $subjectIds = \App\Models\ClassSeriesSubject::where('class_series_id', $classSeriesId)
            ->pluck('id')
            ->toArray();

        // 2. Charger TOUTES les compositions en une seule requête
        $evaluations = Evaluation::where('type', 'composition')
            ->where('trimester_id', $trimester)
            ->whereIn('class_series_subject_id', $subjectIds)
            ->get();

        // Indexer par class_series_subject_id pour lookup rapide
        foreach ($evaluations as $evaluation) {
            $this->compositionsCache[$trimester][$evaluation->class_series_subject_id] = $evaluation->id;
        }

        Log::info("✅ {$evaluations->count()} compositions chargées");

        // 3. Charger TOUTES les notes de compositions en une seule requête
        if (!empty($studentIds) && $evaluations->isNotEmpty()) {
            $evaluationIds = $evaluations->pluck('id')->toArray();

            $grades = Grade::whereIn('evaluation_id', $evaluationIds)
                ->whereIn('student_id', $studentIds)
                ->where('trimester_id', $trimester)
                ->get();

            // Indexer par evaluation_id et student_id pour lookup rapide
            foreach ($grades as $grade) {
                if (!isset($this->compositionGradesCache[$grade->evaluation_id])) {
                    $this->compositionGradesCache[$grade->evaluation_id] = [];
                }
                $this->compositionGradesCache[$grade->evaluation_id][$grade->student_id] = $grade;
            }

            Log::info("✅ {$grades->count()} notes de compositions chargées");
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        Log::info("⚡ Préchargement terminé en {$duration}ms (économie de " . (count($studentIds) * count($subjectIds) * 2) . " requêtes SQL)");
    }

    /**
     * Version OPTIMISÉE de getCompositionGrade()
     *
     * Utilise le cache au lieu de faire des requêtes SQL
     */
    public function getCompositionGradeOptimized($trimester, $studentId, $subjectId)
    {
        // Vérifier si la composition existe dans le cache
        if (!isset($this->compositionsCache[$trimester][$subjectId])) {
            // Pas de composition trouvée (mais ne log pas à chaque fois)
            return null;
        }

        $evaluationId = $this->compositionsCache[$trimester][$subjectId];

        // Vérifier si l'étudiant a une note pour cette composition
        if (!isset($this->compositionGradesCache[$evaluationId][$studentId])) {
            // Pas de note pour cet étudiant (normal si absent ou pas encore saisi)
            return null;
        }

        $grade = $this->compositionGradesCache[$evaluationId][$studentId];

        // Vérifier si absent
        if ($grade->is_absent) {
            return 'ABS';
        }

        // Retourner la note sur 20
        if ($grade->score !== null) {
            return $grade->getScoreOn20();
        }

        return null;
    }

    /**
     * Vider le cache (à appeler entre deux générations de classes différentes)
     */
    public function clearCache()
    {
        $this->compositionsCache = [];
        $this->compositionGradesCache = [];
        Log::info("🧹 Cache compositions vidé");
    }
}
