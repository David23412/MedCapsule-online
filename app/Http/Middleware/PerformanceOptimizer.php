<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Performance Optimizer Middleware
 * Available as 'performance.optimizer' alias for explicit opt-in on specific routes.
 */
class PerformanceOptimizer
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
