<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AuthAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        // Check if user is an instance of User model (not Teacher)
        if (!($user instanceof User)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Seuls les administrateurs et comptables sont autorisés.'
            ], 403);
        }

        // Check if user has admin or comptable role
        if (!in_array($user->role, ['ad', 'comp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Privilèges administrateur requis.'
            ], 403);
        }

        return $next($request);
    }
}