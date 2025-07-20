<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NgrokCsrfFix
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For ngrok development, we need to handle CSRF more flexibly
        if (str_contains($request->getHost(), 'ngrok') || str_contains($request->getHost(), 'ngrok-free.app')) {
            // Add ngrok-specific headers
            $request->headers->set('X-Forwarded-Proto', 'https');
            $request->headers->set('X-Forwarded-Host', $request->getHost());
            
            // Ensure CSRF token is properly set
            if (!$request->hasHeader('X-CSRF-TOKEN') && $request->has('_token')) {
                $request->headers->set('X-CSRF-TOKEN', $request->input('_token'));
            }
        }

        return $next($request);
    }
}