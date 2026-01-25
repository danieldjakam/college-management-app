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
        // Si les rôles sont passés comme une chaîne séparée par des virgules, les diviser
        $allowedRoles = [];
        foreach ($roles as $role) {
            if (strpos($role, ',') !== false) {
                $allowedRoles = array_merge($allowedRoles, explode(',', $role));
            } else {
                $allowedRoles[] = $role;
            }
        }

        // Normaliser et dédupliquer (évite les doublons après modifications)
        $allowedRoles = array_values(array_unique(array_filter(array_map(function ($r) {
            return trim((string) $r);
        }, $allowedRoles))));

        // Log pour déboguer l'authentification
        \Log::info('CheckRole middleware', [
            'authenticated' => auth()->check(),
            'user' => auth()->user() ? auth()->user()->toArray() : null,
            'required_roles' => $allowedRoles,
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

        // Alias: comptable = accountant
        if ($userRole === 'comptable') {
            $userRole = 'accountant';
        }

        // Si la route autorise accountant, le rôle comptable doit aussi passer.
        // (Le mapping ci-dessus suffit, mais on garde aussi la compatibilité inverse.)
        if (in_array('accountant', $allowedRoles) && !in_array('comptable', $allowedRoles)) {
            $allowedRoles[] = 'comptable';
        }

        if (!in_array($userRole, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé pour ce rôle'
            ], 403);
        }

        return $next($request);
    }
}
