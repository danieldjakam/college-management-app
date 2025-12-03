<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de gestion du cache pour les bulletins
 *
 * Fonctionnalités:
 * - Cache les réponses de génération de bulletins
 * - Invalidation automatique avec paramètre ?refresh=1
 * - Cache différencié par student_id et period
 * - TTL configurable (défaut: 1 heure)
 *
 * Usage dans routes/api.php:
 * Route::post('/bulletins/generate', [BulletinController::class, 'generate'])
 *      ->middleware(['auth:api', 'bulletin.cache']);
 */
class BulletinCacheMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $ttl = 3600): Response
    {
        // Désactiver le cache si refresh=1 ou force=1
        if ($request->input('refresh') == 1 || $request->input('force') == 1) {
            \Log::info("🔄 Cache désactivé par paramètre refresh/force");
            return $next($request);
        }

        // Construire une clé de cache unique basée sur les paramètres de la requête
        $cacheKey = $this->buildCacheKey($request);

        // Vérifier si une réponse en cache existe
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);

            \Log::info("💾 Cache HIT pour bulletin - Clé: {$cacheKey}");

            return response()->json([
                'cached' => true,
                'cached_at' => $cachedResponse['cached_at'] ?? now(),
                'data' => $cachedResponse['data']
            ]);
        }

        // Pas de cache, continuer vers le contrôleur
        \Log::info("🔄 Cache MISS pour bulletin - Clé: {$cacheKey}");

        $response = $next($request);

        // Mettre en cache la réponse si succès (status 200)
        if ($response->status() === 200 && $response->getData()) {
            $responseData = json_decode($response->getContent(), true);

            Cache::put($cacheKey, [
                'data' => $responseData,
                'cached_at' => now()->toIso8601String()
            ], $ttl);

            \Log::info("💾 Cache STORED pour bulletin - Clé: {$cacheKey}, TTL: {$ttl}s");
        }

        return $response;
    }

    /**
     * Construire une clé de cache unique basée sur les paramètres de la requête
     *
     * Format: bulletin:{user_id}:{student_id}:{type}:{period}
     */
    private function buildCacheKey(Request $request): string
    {
        $userId = auth()->id() ?? 'guest';
        $studentId = $request->input('student_id', 'unknown');
        $bulletinType = $request->input('bulletin_type', 'unknown');
        $periodId = $request->input('period_identifier', 'unknown');

        return "bulletin:{$userId}:{$studentId}:{$bulletinType}:{$periodId}";
    }

    /**
     * Méthode statique pour invalider le cache d'un bulletin spécifique
     *
     * Usage: BulletinCacheMiddleware::invalidate($studentId, 'sequence', 'seq1');
     */
    public static function invalidate(int $studentId, string $bulletinType, string $periodId): void
    {
        // Invalider pour tous les utilisateurs possibles (wildcard non supporté par Laravel Cache)
        // Alternative: stocker les clés dans Redis avec pattern matching

        $pattern = "bulletin:*:{$studentId}:{$bulletinType}:{$periodId}";

        // Si Redis est utilisé, on peut faire:
        if (config('cache.default') === 'redis') {
            $redis = app('redis')->connection();
            $keys = $redis->keys($pattern);

            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
            }

            \Log::info("🗑️ Cache invalidé pour pattern: {$pattern} ({count($keys)} clés)");
        } else {
            // Fallback: invalider seulement pour l'utilisateur actuel
            $userId = auth()->id() ?? 'guest';
            $cacheKey = "bulletin:{$userId}:{$studentId}:{$bulletinType}:{$periodId}";
            Cache::forget($cacheKey);

            \Log::info("🗑️ Cache invalidé pour clé: {$cacheKey}");
        }
    }

    /**
     * Invalider tous les bulletins d'un étudiant
     */
    public static function invalidateStudent(int $studentId): void
    {
        // Patterns possibles
        $patterns = [
            "bulletin:*:{$studentId}:sequence:*",
            "bulletin:*:{$studentId}:trimester:*",
            "bulletin:*:{$studentId}:annual:*"
        ];

        if (config('cache.default') === 'redis') {
            $redis = app('redis')->connection();

            foreach ($patterns as $pattern) {
                $keys = $redis->keys($pattern);
                foreach ($keys as $key) {
                    Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                }
            }

            \Log::info("🗑️ Tous les bulletins de l'étudiant {$studentId} ont été invalidés");
        } else {
            \Log::warning("⚠️ Invalidation globale nécessite Redis. Utilisez Cache::flush() si nécessaire.");
        }
    }
}
