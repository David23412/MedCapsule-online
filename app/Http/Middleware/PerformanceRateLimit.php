<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PerformanceRateLimit
{
    /**
     * Handle an incoming request with performance rate limiting
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $userId = auth()->id() ?? 'guest';
        $ipAddress = $request->ip();
        
        // Define rate limits based on type
        $limits = $this->getRateLimits($type);
        
        // Check rate limits
        if (!$this->checkRateLimit($userId, $ipAddress, $type, $limits)) {
            Log::warning('Rate limit exceeded', [
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'type' => $type,
                'limits' => $limits,
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Too many requests. Please slow down.',
                'retry_after' => $this->getRetryAfter($userId, $ipAddress, $type)
            ], 429);
        }
        
        // Add performance headers
        $response = $next($request);
        
        // Add performance monitoring headers
        $response->headers->set('X-Performance-Monitor', 'enabled');
        $response->headers->set('X-Rate-Limit-Remaining', $this->getRemainingRequests($userId, $ipAddress, $type));
        
        return $response;
    }
    
    /**
     * Get rate limits based on request type
     */
    private function getRateLimits(string $type): array
    {
        return match($type) {
            'quiz' => [
                'requests' => 30,  // 30 quiz submissions per minute
                'window' => 60,    // 1 minute window
                'burst' => 5       // Allow 5 burst requests
            ],
            'payment' => [
                'requests' => 10,  // 10 payment attempts per minute
                'window' => 60,    // 1 minute window
                'burst' => 2       // Allow 2 burst requests
            ],
            'auth' => [
                'requests' => 5,   // 5 login attempts per minute
                'window' => 60,    // 1 minute window
                'burst' => 1       // Allow 1 burst request
            ],
            'api' => [
                'requests' => 100, // 100 API calls per minute
                'window' => 60,    // 1 minute window
                'burst' => 10      // Allow 10 burst requests
            ],
            'file_upload' => [
                'requests' => 5,   // 5 file uploads per minute
                'window' => 60,    // 1 minute window
                'burst' => 1       // Allow 1 burst request
            ],
            default => [
                'requests' => 60,  // 60 requests per minute
                'window' => 60,    // 1 minute window
                'burst' => 5       // Allow 5 burst requests
            ]
        };
    }
    
    /**
     * Check if rate limit is exceeded
     */
    private function checkRateLimit(string $userId, string $ipAddress, string $type, array $limits): bool
    {
        $redis = Redis::connection();
        
        // Create rate limit keys
        $userKey = "rate_limit:user:{$userId}:{$type}";
        $ipKey = "rate_limit:ip:{$ipAddress}:{$type}";
        
        // Check user-based rate limit
        $userCount = $redis->get($userKey) ?? 0;
        $ipCount = $redis->get($ipKey) ?? 0;
        
        // Check if limits are exceeded
        if ($userCount >= $limits['requests'] || $ipCount >= $limits['requests']) {
            return false;
        }
        
        // Increment counters
        $pipeline = $redis->pipeline();
        $pipeline->incr($userKey);
        $pipeline->incr($ipKey);
        $pipeline->expire($userKey, $limits['window']);
        $pipeline->expire($ipKey, $limits['window']);
        $pipeline->exec();
        
        return true;
    }
    
    /**
     * Get remaining requests for the current window
     */
    private function getRemainingRequests(string $userId, string $ipAddress, string $type): int
    {
        $redis = Redis::connection();
        $limits = $this->getRateLimits($type);
        
        $userKey = "rate_limit:user:{$userId}:{$type}";
        $ipKey = "rate_limit:ip:{$ipAddress}:{$type}";
        
        $userCount = $redis->get($userKey) ?? 0;
        $ipCount = $redis->get($ipKey) ?? 0;
        
        return max(0, $limits['requests'] - max($userCount, $ipCount));
    }
    
    /**
     * Get retry after time in seconds
     */
    private function getRetryAfter(string $userId, string $ipAddress, string $type): int
    {
        $redis = Redis::connection();
        $limits = $this->getRateLimits($type);
        
        $userKey = "rate_limit:user:{$userId}:{$type}";
        $ipKey = "rate_limit:ip:{$ipAddress}:{$type}";
        
        $userTtl = $redis->ttl($userKey);
        $ipTtl = $redis->ttl($ipKey);
        
        return max($userTtl, $ipTtl, 0);
    }
    
    /**
     * Reset rate limit for a user (useful for admin actions)
     */
    public static function resetRateLimit(string $userId, string $type): void
    {
        $redis = Redis::connection();
        $userKey = "rate_limit:user:{$userId}:{$type}";
        $redis->del($userKey);
        
        Log::info('Rate limit reset', [
            'user_id' => $userId,
            'type' => $type
        ]);
    }
    
    /**
     * Get rate limit statistics
     */
    public static function getRateLimitStats(): array
    {
        $redis = Redis::connection();
        
        $stats = [];
        $types = ['quiz', 'payment', 'auth', 'api', 'file_upload', 'default'];
        
        foreach ($types as $type) {
            $pattern = "rate_limit:*:{$type}";
            $keys = self::scanMatchingKeys($redis, $pattern);
            
            $stats[$type] = [
                'active_limits' => count($keys),
                'total_requests' => array_sum(array_map(fn($key) => $redis->get($key) ?? 0, $keys))
            ];
        }
        
        return $stats;
    }

    private static function scanMatchingKeys($redis, string $pattern, int $count = 100): array
    {
        $matched = [];
        $cursor = null;

        do {
            $keys = $redis->scan($cursor, $pattern, $count);
            if ($keys === false) {
                $keys = [];
            }

            foreach ($keys as $key) {
                $matched[] = $key;
            }
        } while ((int) $cursor !== 0);

        return $matched;
    }
}
