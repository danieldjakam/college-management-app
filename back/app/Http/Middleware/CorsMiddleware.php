<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        $allowedOrigins = [
            'http://admin.cpb-douala.com',
            'https://admin.cpb-douala.com',
            'http://localhost:3006',
            'http://localhost:3000'
        ];
        
        $origin = $request->header('Origin');
        
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, Accept');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response->setStatusCode(200);
        }
        
        return $response;
    }
}