<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CourseCacheBuster
{
    /**
     * Handle an incoming request.
     * 
     * This middleware ensures course data is ALWAYS fresh by
     * adding cache-busting headers and checking for stale data.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Add cache-busting headers for course-related requests
        if ($this->isCourseRelatedRequest($request)) {
            // Force browser to not cache course data
            $response = $next($request);
            
            return $response->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT',
                'X-Course-Cache-Buster' => time(),
            ]);
        }
        
        return $next($request);
    }
    
    /**
     * Check if this is a course-related request
     */
    private function isCourseRelatedRequest(Request $request): bool
    {
        $path = $request->path();
        
        return str_contains($path, 'courses') || 
               str_contains($path, 'topics') ||
               $request->routeIs('courses.*');
    }
}