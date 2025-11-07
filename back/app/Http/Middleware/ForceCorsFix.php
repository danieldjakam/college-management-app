<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceCorsFix
{
    public function handle(Request $request, Closure $next)
    {
        // Gérer les requêtes OPTIONS en premier
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
        } else {
            // Process the request
            try {
                $response = $next($request);
            } catch (\Exception $e) {
                // En cas d'erreur, créer une réponse d'erreur avec CORS
                $response = response()->json([
                    'success' => false,
                    'message' => 'Erreur serveur',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        // Toujours ajouter les en-têtes CORS
        $origin = $request->header('Origin') ?: $request->header('HTTP_ORIGIN');

        // Liste des origines autorisées
        $allowedOrigins = [
            'http://admin.cpb-douala.com',   // Frontend production
            'http://admin1.cpb-douala.com',  // Backend production
            'https://admin.cpb-douala.com',  // Frontend production (HTTPS)
            'https://admin1.cpb-douala.com', // Backend production (HTTPS)
            'http://localhost:3000',         // Frontend dev
            'http://127.0.0.1:3000',        // Frontend dev (IP)
            'http://localhost:3006',         // Frontend dev (port alternatif)
            'http://127.0.0.1:3006',        // Frontend dev (port alternatif - IP)
            'http://localhost:3001',         // Frontend dev (autre port)
            'http://127.0.0.1:3001',        // Frontend dev (autre port - IP)
            'http://localhost:3007',         // Frontend dev (autre port)
            'http://127.0.0.1:3007'         // Frontend dev (autre port - IP)
        ];

        if ($origin && in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, Accept');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } else {
            // Si aucune origine valide n'est trouvée, on peut essayer une approche plus permissive pour production
            // Mais seulement si on est en production et que l'origine vient d'un domaine connu
            if ($origin && (str_contains($origin, 'admin.cpb-douala.com') || str_contains($origin, 'admin1.cpb-douala.com'))) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, Accept');
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }
        }

        return $response;
    }
}
