<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AggressiveRateLimitMiddleware
{
    /**
     * Rate limit configurations by context
     */
    private array $rateLimits = [
        'quiz_submit' => [
            'per_minute' => 10,
            'per_hour' => 100,
            'per_day' => 1000,
        ],
        'payment' => [
            'per_minute' => 5,
            'per_hour' => 20,
            'per_day' => 50,
        ],
        'api' => [
            'per_minute' => 30,
            'per_hour' => 500,
            'per_day' => 2000,
        ],
        'general' => [
            'per_minute' => 60,
            'per_hour' => 1000,
            'per_day' => 5000,
        ],
    ];

    /**
     * Suspicious behavior thresholds
     */
    private array $behaviorThresholds = [
        'rapid_requests' => 20, // requests in 10 seconds
        'large_payloads' => 5,  // >1MB requests per minute
        'failure_rate' => 10,   // failures in 5 minutes
    ];

    /**
     * Progressive blocking durations (in minutes)
     */
    private array $blockingDurations = [1, 5, 10, 20, 60, 120, 240];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $context = 'general'): Response
    {
        $clientId = $this->getClientIdentifier($request);
        $rateLimitKey = "rate_limit:{$context}:{$clientId}";
        
        // Check if client is currently blocked
        if ($this->isBlocked($clientId)) {
            return $this->blockResponse($request, 'Client temporarily blocked');
        }

        // Check rate limits
        $rateLimitViolation = $this->checkRateLimits($rateLimitKey, $context);
        if ($rateLimitViolation) {
            $this->handleRateLimitViolation($request, $clientId, $rateLimitViolation);
            return $this->rateLimitResponse($request, $rateLimitViolation);
        }

        // Check for suspicious behavior
        $suspiciousBehavior = $this->checkSuspiciousBehavior($request, $clientId);
        if ($suspiciousBehavior) {
            $this->handleSuspiciousBehavior($request, $clientId, $suspiciousBehavior);
            return $this->suspiciousResponse($request, $suspiciousBehavior);
        }

        // Track request
        $this->trackRequest($request, $clientId, $context);

        $response = $next($request);

        // Track response for failure analysis
        $this->trackResponse($request, $response, $clientId);

        return $response;
    }

    /**
     * Get unique client identifier
     */
    private function getClientIdentifier(Request $request): string
    {
        // Combine IP, User Agent, and User ID for better tracking
        $components = [
            $request->ip(),
            md5($request->userAgent() ?? 'unknown'),
            auth()->id() ?? 'guest',
        ];

        return md5(implode(':', $components));
    }

    /**
     * Check if client is currently blocked
     */
    private function isBlocked(string $clientId): bool
    {
        $blockKey = "blocked:{$clientId}";
        return Cache::has($blockKey);
    }

    /**
     * Check rate limits for the given context
     */
    private function checkRateLimits(string $rateLimitKey, string $context): ?array
    {
        $limits = $this->rateLimits[$context] ?? $this->rateLimits['general'];

        foreach ($limits as $period => $limit) {
            $periodKey = "{$rateLimitKey}:{$period}";
            $currentCount = Cache::get($periodKey, 0);

            if ($currentCount >= $limit) {
                return [
                    'period' => $period,
                    'limit' => $limit,
                    'current' => $currentCount,
                    'type' => 'rate_limit',
                ];
            }
        }

        return null;
    }

    /**
     * Check for suspicious behavior patterns
     */
    private function checkSuspiciousBehavior(Request $request, string $clientId): ?array
    {
        // Check for rapid requests (20+ in 10 seconds)
        $rapidKey = "rapid:{$clientId}";
        $rapidCount = Cache::get($rapidKey, 0);
        if ($rapidCount >= $this->behaviorThresholds['rapid_requests']) {
            return [
                'type' => 'rapid_requests',
                'count' => $rapidCount,
                'threshold' => $this->behaviorThresholds['rapid_requests'],
            ];
        }

        // Check for large payload abuse
        if ($this->hasLargePayload($request)) {
            $largeKey = "large_payloads:{$clientId}";
            $largeCount = Cache::get($largeKey, 0);
            if ($largeCount >= $this->behaviorThresholds['large_payloads']) {
                return [
                    'type' => 'large_payloads',
                    'count' => $largeCount,
                    'threshold' => $this->behaviorThresholds['large_payloads'],
                ];
            }
        }

        // Check failure rate
        $failureKey = "failures:{$clientId}";
        $failureCount = Cache::get($failureKey, 0);
        if ($failureCount >= $this->behaviorThresholds['failure_rate']) {
            return [
                'type' => 'high_failure_rate',
                'count' => $failureCount,
                'threshold' => $this->behaviorThresholds['failure_rate'],
            ];
        }

        return null;
    }

    /**
     * Check if request has large payload
     */
    private function hasLargePayload(Request $request): bool
    {
        $contentLength = $request->header('Content-Length', 0);
        return $contentLength > 1048576; // 1MB
    }

    /**
     * Track the current request
     */
    private function trackRequest(Request $request, string $clientId, string $context): void
    {
        $rateLimitKey = "rate_limit:{$context}:{$clientId}";
        $limits = $this->rateLimits[$context] ?? $this->rateLimits['general'];

        foreach ($limits as $period => $limit) {
            $periodKey = "{$rateLimitKey}:{$period}";
            $ttl = $this->getTTLForPeriod($period);
            
            Cache::increment($periodKey, 1);
            Cache::expire($periodKey, $ttl);
        }

        // Track rapid requests (10-second window)
        $rapidKey = "rapid:{$clientId}";
        Cache::increment($rapidKey, 1);
        Cache::expire($rapidKey, 10);

        // Track large payloads (1-minute window)
        if ($this->hasLargePayload($request)) {
            $largeKey = "large_payloads:{$clientId}";
            Cache::increment($largeKey, 1);
            Cache::expire($largeKey, 60);
        }
    }

    /**
     * Track response for failure analysis
     */
    private function trackResponse(Request $request, Response $response, string $clientId): void
    {
        // Track failures (4xx and 5xx responses)
        if ($response->getStatusCode() >= 400) {
            $failureKey = "failures:{$clientId}";
            Cache::increment($failureKey, 1);
            Cache::expire($failureKey, 300); // 5 minutes
        }
    }

    /**
     * Handle rate limit violation
     */
    private function handleRateLimitViolation(Request $request, string $clientId, array $violation): void
    {
        Log::warning('Rate limit violation detected', [
            'client_id' => $clientId,
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'violation' => $violation,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ]);

        // Apply progressive blocking for repeated violations
        $this->applyProgressiveBlocking($clientId, 'rate_limit');
    }

    /**
     * Handle suspicious behavior
     */
    private function handleSuspiciousBehavior(Request $request, string $clientId, array $behavior): void
    {
        Log::warning('Suspicious behavior detected', [
            'client_id' => $clientId,
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'behavior' => $behavior,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ]);

        // Apply blocking based on behavior type
        $blockDuration = match($behavior['type']) {
            'rapid_requests' => 5,
            'large_payloads' => 10,
            'high_failure_rate' => 15,
            default => 5,
        };

        $this->blockClient($clientId, $blockDuration);
    }

    /**
     * Apply progressive blocking with exponential backoff
     */
    private function applyProgressiveBlocking(string $clientId, string $reason): void
    {
        $violationKey = "violations:{$clientId}";
        $violationCount = Cache::get($violationKey, 0);
        
        // Increment violation count
        Cache::put($violationKey, $violationCount + 1, 86400); // 24 hours

        // Get blocking duration based on violation count
        $durationIndex = min($violationCount, count($this->blockingDurations) - 1);
        $blockDuration = $this->blockingDurations[$durationIndex];

        $this->blockClient($clientId, $blockDuration, $reason);
    }

    /**
     * Block client for specified duration
     */
    private function blockClient(string $clientId, int $durationMinutes, string $reason = 'policy_violation'): void
    {
        $blockKey = "blocked:{$clientId}";
        $blockData = [
            'reason' => $reason,
            'blocked_at' => now()->toISOString(),
            'duration_minutes' => $durationMinutes,
            'expires_at' => now()->addMinutes($durationMinutes)->toISOString(),
        ];

        Cache::put($blockKey, $blockData, $durationMinutes * 60);

        Log::info('Client blocked', [
            'client_id' => $clientId,
            'reason' => $reason,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * Get TTL for period type
     */
    private function getTTLForPeriod(string $period): int
    {
        return match($period) {
            'per_minute' => 60,
            'per_hour' => 3600,
            'per_day' => 86400,
            default => 3600,
        };
    }

    /**
     * Return rate limit response
     */
    private function rateLimitResponse(Request $request, array $violation): Response
    {
        $retryAfter = $this->getTTLForPeriod($violation['period']);

        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => "Too many requests. Limit: {$violation['limit']} per {$violation['period']}",
            'retry_after' => $retryAfter,
            'current_count' => $violation['current'],
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Return suspicious behavior response
     */
    private function suspiciousResponse(Request $request, array $behavior): Response
    {
        return response()->json([
            'error' => 'Suspicious activity detected',
            'message' => "Request pattern flagged: {$behavior['type']}",
            'code' => 'SUSPICIOUS_BEHAVIOR',
        ], 429);
    }

    /**
     * Return blocked client response
     */
    private function blockResponse(Request $request, string $message): Response
    {
        return response()->json([
            'error' => 'Access temporarily blocked',
            'message' => $message,
            'code' => 'CLIENT_BLOCKED',
        ], 403);
    }
}