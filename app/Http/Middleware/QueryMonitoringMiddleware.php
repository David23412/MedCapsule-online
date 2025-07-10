<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\QueryMonitoringService;
use Illuminate\Support\Facades\Log;

class QueryMonitoringMiddleware
{
    private QueryMonitoringService $queryMonitor;

    public function __construct(QueryMonitoringService $queryMonitor)
    {
        $this->queryMonitor = $queryMonitor;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip monitoring for certain routes to avoid noise
        if ($this->shouldSkipMonitoring($request)) {
            return $next($request);
        }

        try {
            $response = $next($request);
            
            // Log performance summary after request completes
            $this->queryMonitor->logPerformanceSummary();
            
            return $response;
        } catch (\Exception $e) {
            // Log performance summary even on exceptions
            $this->queryMonitor->logPerformanceSummary();
            throw $e;
        }
    }

    /**
     * Determine if monitoring should be skipped for this request
     */
    private function shouldSkipMonitoring(Request $request): bool
    {
        $skipRoutes = [
            '/health',
            '/metrics',
            '/debug',
            '/telescope',
            '/horizon'
        ];

        $skipPatterns = [
            '/api/health',
            '/api/metrics',
            '/_debugbar',
            '/storage/',
            '/favicon.ico'
        ];

        $path = $request->path();

        // Skip exact matches
        if (in_array('/' . $path, $skipRoutes)) {
            return true;
        }

        // Skip pattern matches
        foreach ($skipPatterns as $pattern) {
            if (str_starts_with('/' . $path, $pattern)) {
                return true;
            }
        }

        // Skip in non-production environments if configured
        if (config('app.debug') && !config('app.monitor_queries_in_debug', false)) {
            return true;
        }

        return false;
    }
} 