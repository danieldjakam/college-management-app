<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthGlobal
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
        // This middleware adds global functionality like JWT and env to request
        // It's equivalent to the auth_global middleware in Node.js
        
        try {
            // Add JWT functionality to request
            $request->merge(['jwt_service' => JWTAuth::class]);
            
            // Add environment variables access
            $request->merge(['env_vars' => config('app')]);
            
            return $next($request);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de configuration globale'
            ], 500);
        }
    }
}