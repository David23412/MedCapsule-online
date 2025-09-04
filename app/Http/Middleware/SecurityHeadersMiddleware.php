<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Add the security headers
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        // Updated CSP to allow YouTube embeds, API calls, and confetti web workers
        $response->headers->set('Content-Security-Policy', "default-src 'self' blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; frame-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com; connect-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com https://www.googleapis.com https://fonts.googleapis.com https://fonts.gstatic.com; worker-src 'self' blob:;");
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
