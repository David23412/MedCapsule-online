<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Performance Optimization Middleware
 * Handles resource hints, compression, and caching headers
 */
class PerformanceOptimization
{
    /**
     * Handle an incoming request with NUCLEAR performance optimizations
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // NUCLEAR: Add performance headers for all responses
        if (method_exists($response, 'header')) {
            // Resource hints for faster loading
            $response->header('Link', implode(', ', [
                '<https://fonts.googleapis.com>; rel=dns-prefetch',
                '<https://cdn.jsdelivr.net>; rel=dns-prefetch',
                '<https://cdnjs.cloudflare.com>; rel=dns-prefetch',
                '<https://fonts.gstatic.com>; rel=preconnect; crossorigin',
                '<https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css>; rel=preload; as=style',
                '<https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap>; rel=preload; as=style'
            ]));

            // NUCLEAR: Aggressive caching for static assets
            if ($this->isStaticAsset($request)) {
                $response->header('Cache-Control', 'public, max-age=31536000, immutable');
                $response->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            }

            // NUCLEAR: Compression hints
            $response->header('Vary', 'Accept-Encoding');
            
            // NUCLEAR: Security headers that don't impact performance
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            
            // NUCLEAR: Performance monitoring header
            if (defined('LARAVEL_START')) {
                $response->header('X-Response-Time', round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms');
            }
        }

        return $response;
    }

    /**
     * Check if request is for static asset
     */
    private function isStaticAsset(Request $request): bool
    {
        $path = $request->path();
        $extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
        
        foreach ($extensions as $ext) {
            if (str_ends_with($path, '.' . $ext)) {
                return true;
            }
        }
        
        return false;
    }
}