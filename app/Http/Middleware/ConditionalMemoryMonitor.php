<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Core\FeatureToggleService;
use App\Http\Middleware\MemoryMonitor;
use Symfony\Component\HttpFoundation\Response;

/**
 * Conditional Memory Monitor Middleware
 * 
 * Applies memory monitoring only when feature toggle is enabled for specific routes
 */
class ConditionalMemoryMonitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $routeName = null): Response
    {
        // Determine route name from parameter or current route
        $routeName = $routeName ?? $request->route()?->getName() ?? 'unknown';
        
        // Check if memory monitoring should be applied to this route
        if (FeatureToggleService::shouldUseMemoryMonitor($routeName)) {
            $memoryMonitor = app(MemoryMonitor::class);
            return $memoryMonitor->handle($request, $next);
        }
        
        // Continue without memory monitoring
        return $next($request);
    }
}