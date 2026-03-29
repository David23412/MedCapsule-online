<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AggressiveRateLimitMiddleware
{
    /**
     * GOLDEN STANDARD: Multiple rate limiting tiers
     */
    const RATE_LIMITS = [
        'quiz_submit' => [
            'per_minute' => 10,
            'per_hour' => 100,
            'per_day' => 1000
        ],
        'payment' => [
            'per_minute' => 5,
            'per_hour' => 20,
            'per_day' => 50
        ],
        'api' => [
            'per_minute' => 30,
            'per_hour' => 500,
            'per_day' => 2000
        ],
        'general' => [
            'per_minute' => 60,
            'per_hour' => 1000,
            'per_day' => 5000
        ]
    ];
    
    /**
     * Suspicious behavior thresholds
     */
    const SUSPICIOUS_THRESHOLDS = [
        'rapid_requests' => 20, // More than 20 requests in 10 seconds
        'failed_attempts' => 10, // More than 10 failed attempts in 5 minutes
        'large_payloads' => 5    // More than 5 large payloads in 1 minute
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'general'): Response
    {
        $identifier = $this->getIdentifier($request);
        $requestId = uniqid('rate_limit_');
        
        // Determine rate limit type based on request
        $rateLimitType = $this->determineRateLimitType($request, $type);
        
        // Check if IP is temporarily blocked
        if ($this->isTemporarilyBlocked($identifier)) {
            return $this->rateLimitResponse('TEMPORARILY_BLOCKED', $requestId, $identifier);
        }
        
        // Check multiple rate limit tiers
        $rateLimitResult = $this->checkMultipleTiers($identifier, $rateLimitType);
        if (!$rateLimitResult['allowed']) {
            $this->logRateLimitViolation($requestId, $identifier, $rateLimitResult['tier'], $rateLimitType);
            return $this->rateLimitResponse($rateLimitResult['tier'], $requestId, $identifier);
        }
        
        // Check for suspicious behavior patterns
        $suspiciousResult = $this->checkSuspiciousBehavior($identifier, $request);
        if ($suspiciousResult['suspicious']) {
            $this->handleSuspiciousBehavior($identifier, $suspiciousResult['patterns'], $requestId);
        }
        
        // Track successful request
        $this->trackRequest($identifier, $rateLimitType, $request);
        
        $response = $next($request);
        
        // Track response status for failure pattern detection
        $this->trackResponseStatus($identifier, $response->getStatusCode(), $requestId);
        
        return $response;
    }
    
    /**
     * Get unique identifier for rate limiting
     */
    private function getIdentifier(Request $request): string
    {
        // Combine IP and User ID for more accurate tracking
        $ip = $request->ip();
        $userId = auth()->id() ?? 'guest';
        return "rate_limit:{$ip}:{$userId}";
    }
    
    /**
     * Determine rate limit type based on request characteristics
     */
    private function determineRateLimitType(Request $request, string $defaultType): string
    {
        $path = $request->path();
        
        // Quiz-related endpoints
        if (str_contains($path, 'quiz') && $request->isMethod('POST')) {
            return 'quiz_submit';
        }
        
        // Payment-related endpoints
        if (str_contains($path, 'payment') || str_contains($path, 'purchase')) {
            return 'payment';
        }
        
        // API endpoints
        if (str_starts_with($path, 'api/')) {
            return 'api';
        }
        
        return $defaultType;
    }
    
    /**
     * Check if identifier is temporarily blocked
     */
    private function isTemporarilyBlocked(string $identifier): bool
    {
        return Cache::has("{$identifier}:blocked");
    }
    
    /**
     * Check multiple rate limit tiers
     */
    private function checkMultipleTiers(string $identifier, string $type): array
    {
        $limits = self::RATE_LIMITS[$type] ?? self::RATE_LIMITS['general'];
        
        // Check per-minute limit
        $minuteKey = "{$identifier}:minute:" . now()->format('Y-m-d-H-i');
        $minuteCount = Cache::get($minuteKey, 0);
        if ($minuteCount >= $limits['per_minute']) {
            return ['allowed' => false, 'tier' => 'PER_MINUTE', 'count' => $minuteCount, 'limit' => $limits['per_minute']];
        }
        
        // Check per-hour limit
        $hourKey = "{$identifier}:hour:" . now()->format('Y-m-d-H');
        $hourCount = Cache::get($hourKey, 0);
        if ($hourCount >= $limits['per_hour']) {
            return ['allowed' => false, 'tier' => 'PER_HOUR', 'count' => $hourCount, 'limit' => $limits['per_hour']];
        }
        
        // Check per-day limit
        $dayKey = "{$identifier}:day:" . now()->format('Y-m-d');
        $dayCount = Cache::get($dayKey, 0);
        if ($dayCount >= $limits['per_day']) {
            return ['allowed' => false, 'tier' => 'PER_DAY', 'count' => $dayCount, 'limit' => $limits['per_day']];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Check for suspicious behavior patterns
     */
    private function checkSuspiciousBehavior(string $identifier, Request $request): array
    {
        $patterns = [];
        
        // Check for rapid requests (burst pattern)
        $rapidKey = "{$identifier}:rapid:" . floor(time() / 10); // 10-second windows
        $rapidCount = Cache::get($rapidKey, 0);
        if ($rapidCount >= self::SUSPICIOUS_THRESHOLDS['rapid_requests']) {
            $patterns[] = 'RAPID_REQUESTS';
        }
        
        // Check for large payload abuse
        $payloadSize = strlen($request->getContent());
        if ($payloadSize > 1048576) { // 1MB
            $largeKey = "{$identifier}:large:" . now()->format('Y-m-d-H-i');
            $largeCount = Cache::get($largeKey, 0);
            if ($largeCount >= self::SUSPICIOUS_THRESHOLDS['large_payloads']) {
                $patterns[] = 'LARGE_PAYLOAD_ABUSE';
            }
        }
        
        return [
            'suspicious' => !empty($patterns),
            'patterns' => $patterns
        ];
    }
    
    /**
     * Handle suspicious behavior
     */
    private function handleSuspiciousBehavior(string $identifier, array $patterns, string $requestId): void
    {
        Log::warning('Suspicious behavior detected', [
            'request_id' => $requestId,
            'identifier' => $identifier,
            'patterns' => $patterns,
            'timestamp' => now()->toISOString()
        ]);
        
        // Increase block time for repeated offenses
        $offenseKey = "{$identifier}:offenses";
        $offenseCount = Cache::get($offenseKey, 0);
        $blockDuration = min(300 * pow(2, $offenseCount), 3600); // Max 1 hour
        
        // Temporarily block the identifier
        Cache::put("{$identifier}:blocked", true, now()->addSeconds($blockDuration));
        Cache::put($offenseKey, $offenseCount + 1, now()->addDay());
        
        Log::warning('Identifier temporarily blocked for suspicious behavior', [
            'identifier' => $identifier,
            'block_duration_seconds' => $blockDuration,
            'offense_count' => $offenseCount + 1,
            'patterns' => $patterns
        ]);
    }
    
    /**
     * Track successful request
     */
    private function trackRequest(string $identifier, string $type, Request $request): void
    {
        $now = now();
        
        // Increment all tier counters
        $minuteKey = "{$identifier}:minute:" . $now->format('Y-m-d-H-i');
        $hourKey = "{$identifier}:hour:" . $now->format('Y-m-d-H');
        $dayKey = "{$identifier}:day:" . $now->format('Y-m-d');
        $rapidKey = "{$identifier}:rapid:" . floor(time() / 10);
        
        Cache::increment($minuteKey, 1);
        Cache::increment($hourKey, 1);
        Cache::increment($dayKey, 1);
        Cache::increment($rapidKey, 1);
        
        // Set expiration times
        Cache::expire($minuteKey, 60);
        Cache::expire($hourKey, 3600);
        Cache::expire($dayKey, 86400);
        Cache::expire($rapidKey, 20);
        
        // Track large payloads
        $payloadSize = strlen($request->getContent());
        if ($payloadSize > 1048576) { // 1MB
            $largeKey = "{$identifier}:large:" . $now->format('Y-m-d-H-i');
            Cache::increment($largeKey, 1);
            Cache::expire($largeKey, 60);
        }
    }
    
    /**
     * Track response status for failure pattern detection
     */
    private function trackResponseStatus(string $identifier, int $statusCode, string $requestId): void
    {
        // Track failed requests (4xx, 5xx)
        if ($statusCode >= 400) {
            $failKey = "{$identifier}:failures:" . floor(time() / 300); // 5-minute windows
            $failCount = Cache::increment($failKey, 1);
            Cache::expire($failKey, 600);
            
            if ($failCount >= self::SUSPICIOUS_THRESHOLDS['failed_attempts']) {
                Log::warning('High failure rate detected', [
                    'request_id' => $requestId,
                    'identifier' => $identifier,
                    'failure_count' => $failCount,
                    'threshold' => self::SUSPICIOUS_THRESHOLDS['failed_attempts']
                ]);
            }
        }
    }
    
    /**
     * Log rate limit violation
     */
    private function logRateLimitViolation(string $requestId, string $identifier, string $tier, string $type): void
    {
        Log::warning('Rate limit exceeded', [
            'request_id' => $requestId,
            'identifier' => $identifier,
            'tier' => $tier,
            'type' => $type,
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Generate rate limit response
     */
    private function rateLimitResponse(string $tier, string $requestId, string $identifier): Response
    {
        $message = match($tier) {
            'PER_MINUTE' => 'Too many requests per minute. Please slow down.',
            'PER_HOUR' => 'Hourly request limit exceeded. Please try again later.',
            'PER_DAY' => 'Daily request limit exceeded. Please try again tomorrow.',
            'TEMPORARILY_BLOCKED' => 'Access temporarily restricted due to suspicious activity.',
            default => 'Rate limit exceeded. Please try again later.'
        };
        
        Log::warning('Rate limit response sent', [
            'request_id' => $requestId,
            'identifier' => $identifier,
            'tier' => $tier,
            'message' => $message
        ]);
        
        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => $message,
            'tier' => $tier,
            'request_id' => $requestId
        ], 429);
    }
}