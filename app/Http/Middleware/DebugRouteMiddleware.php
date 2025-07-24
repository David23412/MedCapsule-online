<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugRouteMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // MAXIMUM SECURITY ROUTE DEBUGGING
        Log::emergency('🔥🔥🔥 ROUTE MIDDLEWARE DEBUG 🔥🔥🔥', [
            'url' => $request->url(),
            'path' => $request->path(),
            'method' => $request->method(),
            'route_name' => $request->route() ? $request->route()->getName() : 'NO_ROUTE',
            'route_params' => $request->route() ? $request->route()->parameters() : 'NO_PARAMS',
            'route_param_names' => $request->route() ? $request->route()->parameterNames() : 'NO_NAMES',
            'all_params' => $request->all(),
            'headers' => $request->headers->all(),
            'server' => $request->server(),
            'timestamp' => now()->toDateTimeString(),
            'memory' => memory_get_usage(true)
        ]);

        $response = $next($request);

        Log::emergency('🔥🔥🔥 ROUTE MIDDLEWARE RESPONSE 🔥🔥🔥', [
            'status_code' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'content_length' => strlen($response->getContent()),
            'content_preview' => substr($response->getContent(), 0, 500)
        ]);

        return $response;
    }
}