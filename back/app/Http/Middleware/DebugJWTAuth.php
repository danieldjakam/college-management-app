<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class DebugJWTAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        \Log::info('=== DEBUG JWT AUTH MIDDLEWARE ===', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Vérifier la présence du header Authorization
        $authHeader = $request->header('Authorization');
        \Log::info('Authorization Header Check', [
            'has_auth_header' => !empty($authHeader),
            'auth_header_format' => $authHeader ? (str_starts_with($authHeader, 'Bearer ') ? 'Bearer Format' : 'Autre Format') : 'Absent',
            'auth_header_length' => $authHeader ? strlen($authHeader) : 0
        ]);

        if (!$authHeader) {
            \Log::error('JWT AUTH - Aucun header Authorization trouvé');
            return response()->json([
                'success' => false,
                'message' => 'Token d\'authentification manquant',
                'error_code' => 'NO_AUTH_HEADER',
                'debug_info' => [
                    'expected_header' => 'Authorization: Bearer <token>',
                    'available_headers' => array_keys($request->headers->all())
                ]
            ], 401);
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            \Log::error('JWT AUTH - Format du header Authorization incorrect', [
                'received_format' => substr($authHeader, 0, 20) . '...'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Format du token incorrect',
                'error_code' => 'INVALID_TOKEN_FORMAT',
                'debug_info' => [
                    'expected_format' => 'Bearer <token>',
                    'received_format' => substr($authHeader, 0, 20) . '...'
                ]
            ], 401);
        }

        $token = substr($authHeader, 7); // Remove 'Bearer '
        
        \Log::info('JWT Token Extraction', [
            'token_length' => strlen($token),
            'token_starts_with' => substr($token, 0, 10) . '...',
            'token_ends_with' => '...' . substr($token, -10)
        ]);

        try {
            // Tenter de valider le token
            $user = JWTAuth::setToken($token)->authenticate();
            
            if (!$user) {
                \Log::error('JWT AUTH - Token valide mais utilisateur non trouvé');
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                    'error_code' => 'USER_NOT_FOUND'
                ], 401);
            }

            \Log::info('JWT AUTH - Authentification réussie', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'token_valid' => true
            ]);

            // Ajouter l'utilisateur à la requête pour les contrôleurs
            $request->attributes->set('authenticated_user', $user);

        } catch (TokenExpiredException $e) {
            \Log::error('JWT AUTH - Token expiré', [
                'exception' => $e->getMessage(),
                'token_preview' => substr($token, 0, 10) . '...'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Token expiré',
                'error_code' => 'TOKEN_EXPIRED'
            ], 401);

        } catch (TokenInvalidException $e) {
            \Log::error('JWT AUTH - Token invalide', [
                'exception' => $e->getMessage(),
                'token_preview' => substr($token, 0, 10) . '...'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
                'error_code' => 'TOKEN_INVALID'
            ], 401);

        } catch (JWTException $e) {
            \Log::error('JWT AUTH - Exception JWT', [
                'exception' => $e->getMessage(),
                'exception_type' => get_class($e),
                'token_preview' => substr($token, 0, 10) . '...'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur d\'authentification JWT',
                'error_code' => 'JWT_EXCEPTION',
                'debug_info' => [
                    'exception_type' => get_class($e),
                    'exception_message' => $e->getMessage()
                ]
            ], 401);

        } catch (\Exception $e) {
            \Log::error('JWT AUTH - Erreur système', [
                'exception' => $e->getMessage(),
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur système d\'authentification',
                'error_code' => 'SYSTEM_ERROR'
            ], 500);
        }

        return $next($request);
    }
}