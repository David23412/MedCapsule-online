<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class HttpsRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if we should force HTTPS
        if (env('FORCE_HTTPS', false) || config('app.force_https', false) || $this->isNgrokEnvironment($request)) {
            // Force HTTPS for all URLs
            URL::forceScheme('https');
            
            // Set secure cookie settings for HTTPS
            if (!$request->secure()) {
                // Only redirect GET requests, not POST/PUT/DELETE
                if ($request->isMethod('get')) {
                    return redirect()->secure($request->getRequestUri(), 301);
                }
            }
            
            // Set security headers for HTTPS
            $response = $next($request);
            
            if (method_exists($response, 'headers')) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
                $response->headers->set('X-Content-Type-Options', 'nosniff');
                $response->headers->set('X-Frame-Options', 'DENY');
                $response->headers->set('X-XSS-Protection', '1; mode=block');
                $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            }
            
            return $response;
        }
        
        return $next($request);
    }
    
    /**
     * Check if this is an ngrok environment
     */
    private function isNgrokEnvironment(Request $request): bool
    {
        $host = $request->getHost();
        
        // Check if the host contains ngrok
        if (str_contains($host, 'ngrok') || str_contains($host, 'ngrok.io')) {
            return true;
        }
        
        // Check for ngrok headers
        if ($request->hasHeader('X-Forwarded-Proto') && $request->header('X-Forwarded-Proto') === 'https') {
            return true;
        }
        
        // Check for ngrok-specific headers
        if ($request->hasHeader('X-Original-Host') && str_contains($request->header('X-Original-Host'), 'ngrok')) {
            return true;
        }
        
        return false;
    }
}