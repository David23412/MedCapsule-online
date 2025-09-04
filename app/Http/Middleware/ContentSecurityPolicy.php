<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Updated CSP to allow YouTube embeds, API calls, and confetti web workers
        $csp = "default-src 'self' blob:; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com; " .
               "style-src 'self' 'unsafe-inline'; " .
               "img-src 'self' data: blob: https:; " .
               "font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; " .
               "frame-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com; " .
               "connect-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com https://www.googleapis.com https://fonts.googleapis.com https://fonts.gstatic.com; " .
               "worker-src 'self' blob:;";

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        return $response;
    }
}
