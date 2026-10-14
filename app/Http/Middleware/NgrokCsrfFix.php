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
        if (
            app()->environment(['local', 'testing']) &&
            config('app.enable_ngrok_compat', false) &&
            (
                str_contains($request->getHost(), 'ngrok') ||
                str_contains($request->getHost(), 'ngrok-free.app') ||
                str_contains($request->getHost(), 'ngrok-free.dev')
            )
        ) {
            // Add ngrok-specific headers
            if (! $request->headers->has('X-Forwarded-Proto')) {
                $request->headers->set('X-Forwarded-Proto', 'https');
            }

            if (! $request->headers->has('X-Forwarded-Host')) {
                $request->headers->set('X-Forwarded-Host', $request->getHost());
            }
            
            // Ensure CSRF token is properly set
            if (!$request->hasHeader('X-CSRF-TOKEN') && $request->has('_token')) {
                $request->headers->set('X-CSRF-TOKEN', $request->input('_token'));
            }
        }

        return $next($request);
    }
}
