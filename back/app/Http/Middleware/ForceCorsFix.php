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
            $response = response()->json([], 200);
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
        $origin = $request->header('Origin');
        
        // Liste des origines autorisées  
        $allowedOrigins = [
            'http://admin.cpb-douala.com',
            'https://admin.cpb-douala.com',
            'http://admin1.cpb-douala.com',  // Backend domain
            'https://admin1.cpb-douala.com', // Backend HTTPS
            'http://localhost:3006',
            'http://localhost:3000'
        ];
        
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, Accept');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        
        return $response;
    }
}