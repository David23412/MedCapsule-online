<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateApiInput
{
    public function handle(Request $request, Closure $next)
    {
        // Validate JSON structure for API requests
        if ($request->isJson() && !$request->json()->count()) {
            return response()->json(['error' => 'Invalid JSON'], 400);
        }
        
        return $next($request);
    }
}