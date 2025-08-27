<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Log pour déboguer l'authentification
        \Log::info('CheckRole middleware', [
            'authenticated' => auth()->check(),
            'user' => auth()->user() ? auth()->user()->toArray() : null,
            'required_roles' => $roles,
            'authorization_header' => $request->header('Authorization'),
            'path' => $request->path()
        ]);
        
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $userRole = auth()->user()->role;
        
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé pour ce rôle'
            ], 403);
        }

        return $next($request);
    }
}
