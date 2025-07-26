<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * LETHAL OPTIMIZED MIDDLEWARE - MAXIMUM EFFICIENCY CONSOLIDATED MIDDLEWARE
 * Combines authentication, throttling, and performance optimizations
 */
class LethalOptimizedMiddleware
{
    /**
     * Handle an incoming request with LETHAL OPTIMIZATION
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        $startTime = microtime(true);
        
        // LETHAL OPTIMIZATION: Ultra-fast authentication check
        if (!$this->checkAuthentication($request, $guards)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // LETHAL OPTIMIZATION: Intelligent rate limiting
        if (!$this->checkRateLimit($request)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }

        // LETHAL OPTIMIZATION: Performance monitoring
        $this->startPerformanceMonitoring($request);

        $response = $next($request);

        // LETHAL OPTIMIZATION: Log performance metrics
        $this->logPerformanceMetrics($request, $startTime);

        return $response;
    }

    /**
     * LETHAL OPTIMIZATION: Ultra-fast authentication check
     */
    private function checkAuthentication(Request $request, array $guards): bool
    {
        // Use cached authentication status for performance
        $cacheKey = 'auth_status_' . session()->getId();
        
        return Cache::remember($cacheKey, 300, function () use ($guards) {
            if (empty($guards)) {
                return Auth::check();
            }

            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * LETHAL OPTIMIZATION: Intelligent rate limiting
     */
    private function checkRateLimit(Request $request): bool
    {
        $key = 'rate_limit_' . $request->ip() . '_' . (auth()->id() ?? 'guest');
        $maxAttempts = auth()->check() ? 120 : 60; // Higher limit for authenticated users
        $decayMinutes = 1;

        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            return false;
        }

        Cache::put($key, $attempts + 1, $decayMinutes * 60);
        return true;
    }

    /**
     * LETHAL OPTIMIZATION: Start performance monitoring
     */
    private function startPerformanceMonitoring(Request $request): void
    {
        $request->attributes->set('performance_start', microtime(true));
        $request->attributes->set('memory_start', memory_get_usage(true));
    }

    /**
     * LETHAL OPTIMIZATION: Log performance metrics
     */
    private function logPerformanceMetrics(Request $request, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000;
        $memoryUsage = memory_get_usage(true) - $request->attributes->get('memory_start', 0);

        // Only log slow requests to avoid log spam
        if ($executionTime > 100) { // Log requests taking more than 100ms
            Log::info('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time_ms' => round($executionTime, 2),
                'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);
        }
    }
}