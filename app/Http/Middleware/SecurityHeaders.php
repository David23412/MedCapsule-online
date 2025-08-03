<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        $response->headers->set("Content-Security-Policy", "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");
        $response->headers->set("X-Frame-Options", "DENY");
        $response->headers->set("X-XSS-Protection", "1; mode=block");
        $response->headers->set("X-Content-Type-Options", "nosniff");
        
        return $response;
    }
}