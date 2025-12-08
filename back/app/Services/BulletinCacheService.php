<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service de cache pour les bulletins et statuts des étudiants
 *
 * Utilise le cache Laravel (file/redis/memcached selon configuration)
 * pour améliorer les performances des requêtes lourdes.
 */
class BulletinCacheService
{
    /**
     * Durée du cache en secondes
     * 5 minutes = 300 secondes (bon compromis entre performance et fraîcheur)
     */
    const CACHE_TTL = 300;

    /**
     * Préfixe pour toutes les clés de cache de bulletins
     */
    const CACHE_PREFIX = 'bulletin_status';

    /**
     * Récupérer le statut des bulletins d'une série depuis le cache
     * Si absent, exécute le callback et met en cache le résultat
     *
     * @param int $classSeriesId ID de la série de classe
     * @param callable $callback Fonction à exécuter si le cache est vide
     * @param string|null $period Période optionnelle (ex: "seq1", "trim1")
     * @return mixed Résultat du cache ou du callback
     */
    public function getOrSetStudentsStatus(int $classSeriesId, callable $callback, ?string $period = null)
    {
        $cacheKey = $this->buildCacheKey($classSeriesId, $period);

        try {
            // Utiliser remember() : récupère du cache ou exécute le callback
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($callback, $cacheKey) {
                Log::info("Cache MISS pour {$cacheKey} - Calcul en cours...");
                return $callback();
            });
        } catch (\Exception $e) {
            // En cas d'erreur de cache, exécuter directement le callback
            Log::warning("Erreur de cache pour {$cacheKey}: {$e->getMessage()}");
            return $callback();
        }
    }

    /**
     * Invalider le cache pour une série de classe spécifique
     * À appeler après modification de notes, génération de bulletin, etc.
     *
     * @param int $classSeriesId ID de la série de classe
     * @param string|null $period Période spécifique à invalider (null = toutes)
     * @return bool Succès de l'invalidation
     */
    public function invalidateSeriesCache(int $classSeriesId, ?string $period = null): bool
    {
        try {
            if ($period) {
                // Invalider uniquement une période spécifique
                $cacheKey = $this->buildCacheKey($classSeriesId, $period);
                Cache::forget($cacheKey);
                Log::info("Cache invalidé: {$cacheKey}");
            } else {
                // Invalider toutes les périodes pour cette série
                $patterns = ['current', 'seq1', 'seq2', 'seq3', 'seq4', 'trim1', 'trim2', 'trim3'];
                foreach ($patterns as $p) {
                    $cacheKey = $this->buildCacheKey($classSeriesId, $p);
                    Cache::forget($cacheKey);
                }
                // Aussi invalider sans période
                Cache::forget($this->buildCacheKey($classSeriesId));
                Log::info("Cache invalidé pour toutes les périodes de la série {$classSeriesId}");
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'invalidation du cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Invalider le cache pour un étudiant spécifique
     * À appeler après modification de notes d'un étudiant
     *
     * @param int $studentId ID de l'étudiant
     * @return bool Succès de l'invalidation
     */
    public function invalidateStudentCache(int $studentId): bool
    {
        try {
            // Récupérer la série de classe de l'étudiant
            $student = \App\Models\Student::find($studentId);
            if ($student && $student->class_series_id) {
                return $this->invalidateSeriesCache($student->class_series_id);
            }
            return false;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'invalidation du cache étudiant: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Vider tout le cache des bulletins
     * Utile pour les opérations de maintenance
     *
     * @return bool Succès du vidage
     */
    public function flushAll(): bool
    {
        try {
            // Utiliser tags si le driver le supporte (redis, memcached)
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags([self::CACHE_PREFIX])->flush();
            } else {
                // Fallback: utiliser un pattern matching (si file driver)
                Cache::flush(); // Attention: vide TOUT le cache
            }
            Log::info("Cache des bulletins vidé complètement");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors du vidage du cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Construire une clé de cache unique
     *
     * @param int $classSeriesId ID de la série
     * @param string|null $period Période optionnelle
     * @return string Clé de cache
     */
    private function buildCacheKey(int $classSeriesId, ?string $period = null): string
    {
        $key = self::CACHE_PREFIX . ":{$classSeriesId}";
        if ($period) {
            $key .= ":{$period}";
        }
        return $key;
    }

    /**
     * Obtenir des statistiques sur le cache
     *
     * @return array Statistiques du cache
     */
    public function getStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'ttl_seconds' => self::CACHE_TTL,
            'ttl_minutes' => self::CACHE_TTL / 60,
        ];
    }
}
