<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Performance Optimization Middleware
 * Handles resource hints, compression, and caching headers
 */
class PerformanceOptimization
{
    /**
     * Pre-joined resource-hints Link header value.
     *
     * Built once at class-load time as a compile-time constant instead of
     * reconstructing the string from an array on every single request.
     */
    private const RESOURCE_HINTS_HEADER =
        '<https://fonts.googleapis.com>; rel=dns-prefetch, ' .
        '<https://cdn.jsdelivr.net>; rel=dns-prefetch, ' .
        '<https://cdnjs.cloudflare.com>; rel=dns-prefetch, ' .
        '<https://fonts.gstatic.com>; rel=preconnect; crossorigin, ' .
        '<https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css>; rel=preload; as=style, ' .
        '<https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap>; rel=preload; as=style';

    /**
     * O(1) lookup set for static asset extensions.
     *
     * Replacing the previous foreach-with-string-concatenation loop
     * (12 iterations × string allocation per request) with a single
     * pathinfo() call + isset() on this constant map.
     */
    private const STATIC_ASSET_EXTENSIONS = [
        'css'   => true, 'js'    => true,
        'png'   => true, 'jpg'   => true, 'jpeg' => true,
        'gif'   => true, 'svg'   => true, 'ico'  => true,
        'woff'  => true, 'woff2' => true,
        'ttf'   => true, 'eot'   => true,
    ];

    /**
     * Handle an incoming request with NUCLEAR performance optimizations
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            // Resource hints — constant string, zero per-request allocation
            $response->header('Link', self::RESOURCE_HINTS_HEADER);

            // Aggressive caching for static assets — O(1) extension check
            if ($this->isStaticAsset($request)) {
                $response->header('Cache-Control', 'public, max-age=31536000, immutable');
                $response->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            }

            // Compression hint
            $response->header('Vary', 'Accept-Encoding');

            // NOTE: Security headers (X-Content-Type-Options, X-Frame-Options) and
            // X-Response-Time are intentionally omitted here.
            //
            // • HttpsRedirectMiddleware already sets X-Content-Type-Options and
            //   X-Frame-Options: DENY on every HTTPS response.  Setting them again
            //   here is redundant, and previously overrode DENY with the weaker
            //   SAMEORIGIN — a security regression.
            //
            // • X-Response-Time is set by PerformanceMonitoring with a more accurate
            //   value (measured from the start of the web-middleware chain rather
            //   than from LARAVEL_START which includes bootstrapping time).
        }

        return $response;
    }

    /**
     * Check if request is for a static asset — O(1) via pathinfo + constant map.
     */
    private function isStaticAsset(Request $request): bool
    {
        $ext = pathinfo($request->path(), PATHINFO_EXTENSION);
        return isset(self::STATIC_ASSET_EXTENSIONS[$ext]);
    }
}