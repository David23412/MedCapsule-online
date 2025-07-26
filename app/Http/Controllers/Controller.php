<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

/**
 * LETHAL FIXED BASE CONTROLLER
 * NO GLOBAL MIDDLEWARE - Controllers apply their own middleware
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // NO CONSTRUCTOR - No global middleware applied
    // Each controller handles its own middleware requirements

    /**
     * Ultra-fast JSON response helper
     */
    protected function jsonResponse(bool $success, string $message, array $data = [], int $status = 200): JsonResponse
    {
        try {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'data' => $data,
                'timestamp' => now()->toISOString()
            ], $status);
        } catch (\Exception $e) {
            Log::error('JSON response failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Response generation failed'], 500);
        }
    }

    /**
     * Ultra-fast caching helper
     */
    protected function cacheRemember(string $key, int $ttl, callable $callback)
    {
        try {
            $cacheKey = 'controller_cache_' . md5($key . '_' . (auth()->id() ?? 'guest'));
            return Cache::remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Cache operation failed', ['error' => $e->getMessage()]);
            return $callback();
        }
    }
}
