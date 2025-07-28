<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\User;
use App\Models\Teacher;

class AuthUser
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
        try {
            // Extract token from Authorization header
            $token = $request->header('Authorization');
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token d\'autorisation manquant'
                ], 401);
            }

            // Remove 'Bearer ' prefix if present
            if (str_starts_with($token, 'Bearer ')) {
                $token = substr($token, 7);
            }

            // Set the token for JWTAuth
            JWTAuth::setToken($token);
            
            // Try to authenticate user
            $user = JWTAuth::authenticate($token);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 401);
            }

            // Set the authenticated user
            auth()->setUser($user);
            
            return $next($request);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expiré'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur d\'authentification'
            ], 401);
        }
    }
}