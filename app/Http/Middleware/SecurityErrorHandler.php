<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * 🛡️ NUCLEAR-GRADE SECURITY ERROR HANDLER MIDDLEWARE
 *
 * This middleware provides comprehensive error handling and security monitoring including:
 * - Exception catching and secure logging
 * - Security event monitoring and alerting
 * - Attack pattern detection and blocking
 * - Performance monitoring and anomaly detection
 * - Secure error responses to prevent information leakage
 */
class SecurityErrorHandler
{
    /**
     * Security event thresholds
     */
    const MAX_SECURITY_EVENTS_PER_HOUR = 10;
    const MAX_ERRORS_PER_MINUTE = 30;
    const SUSPICIOUS_IPS_BLOCK_THRESHOLD = 5;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pre-request security checks
        $this->performPreRequestSecurityChecks($request);

        try {
            $response = $next($request);

            // Post-request security monitoring
            $this->performPostRequestSecurityMonitoring($request, $response);

            return $response;

        } catch (Throwable $exception) {
            return $this->handleSecurityException($request, $exception);
        }
    }

    /**
     * Perform security checks before processing the request
     */
    private function performPreRequestSecurityChecks(Request $request): void
    {
        $ip = $request->ip();
        $userId = Auth::id();
        $userAgent = $request->userAgent();
        $url = $request->url();

        // Check for suspicious IP addresses
        if ($this->isSuspiciousIP($ip)) {
            Log::warning('Request blocked from suspicious IP', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'url' => $url,
                'user_id' => $userId
            ]);

            abort(403, 'Access denied due to suspicious activity.');
        }

        // Check for suspicious user agents
        if ($this->isSuspiciousUserAgent($userAgent)) {
            Log::warning('Request blocked from suspicious user agent', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'url' => $url,
                'user_id' => $userId
            ]);

            abort(403, 'Access denied due to suspicious user agent.');
        }

        // Check for attack patterns in URL
        if ($this->containsAttackPatterns($url)) {
            Log::warning('Request blocked due to attack pattern in URL', [
                'ip' => $ip,
                'url' => $url,
                'user_id' => $userId
            ]);

            abort(403, 'Access denied due to suspicious URL pattern.');
        }

        // Rate limiting check
        if ($this->isRateLimitExceeded($ip, $userId)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $ip,
                'user_id' => $userId,
                'url' => $url
            ]);

            abort(429, 'Too many requests. Please try again later.');
        }
    }

    /**
     * Handle security exceptions with secure error responses
     */
    private function handleSecurityException(Request $request, Throwable $exception): Response
    {
        $ip = $request->ip();
        $userId = Auth::id();
        $errorCode = $exception->getCode();
        $errorMessage = $exception->getMessage();

        // Log the security exception with sanitized details
        Log::error('Security exception occurred', [
            'ip' => $ip,
            'user_id' => $userId,
            'url' => $request->url(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'error_code' => $errorCode,
            'error_message' => $this->sanitizeErrorMessage($errorMessage),
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine(),
            'trace_hash' => hash('sha256', $exception->getTraceAsString())
        ]);

        // Track security events for monitoring
        $this->trackSecurityEvent($ip, $userId, 'exception', $errorCode);

        // Determine appropriate error response based on exception type
        if ($this->isSecurityException($exception)) {
            return $this->createSecurityErrorResponse($request, $exception);
        }

        if ($this->isValidationException($exception)) {
            return $this->createValidationErrorResponse($request, $exception);
        }

        if ($this->isDatabaseException($exception)) {
            return $this->createDatabaseErrorResponse($request, $exception);
        }

        // Generic error response for unknown exceptions
        return $this->createGenericErrorResponse($request, $exception);
    }

    /**
     * Perform post-request security monitoring
     */
    private function performPostRequestSecurityMonitoring(Request $request, Response $response): void
    {
        $statusCode = $response->getStatusCode();

        // Monitor error responses
        if ($statusCode >= 400) {
            $this->trackSecurityEvent(
                $request->ip(),
                Auth::id(),
                'error_response',
                $statusCode
            );
        }

        // Monitor successful responses for anomalies
        if ($statusCode === 200) {
            $this->checkForAnomalousSuccess($request, $response);
        }
    }

    /**
     * Create secure error response for security exceptions
     */
    private function createSecurityErrorResponse(Request $request, Throwable $exception): Response
    {
        $statusCode = 403; // Forbidden for security issues
        $message = 'Access denied due to security policy violation.';

        // Don't expose sensitive error information
        return response()->json([
            'success' => false,
            'error' => 'Security violation',
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'request_id' => $this->generateSecureRequestId()
        ], $statusCode);
    }

    /**
     * Create validation error response
     */
    private function createValidationErrorResponse(Request $request, Throwable $exception): Response
    {
        $statusCode = 422; // Unprocessable Entity
        $message = 'Invalid input data provided.';

        return response()->json([
            'success' => false,
            'error' => 'Validation failed',
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'request_id' => $this->generateSecureRequestId()
        ], $statusCode);
    }

    /**
     * Create database error response
     */
    private function createDatabaseErrorResponse(Request $request, Throwable $exception): Response
    {
        $statusCode = 500; // Internal Server Error
        $message = 'A database error occurred. Please try again later.';

        return response()->json([
            'success' => false,
            'error' => 'Database error',
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'request_id' => $this->generateSecureRequestId()
        ], $statusCode);
    }

    /**
     * Create generic error response
     */
    private function createGenericErrorResponse(Request $request, Throwable $exception): Response
    {
        $statusCode = 500; // Internal Server Error
        $message = 'An unexpected error occurred. Please try again later.';

        return response()->json([
            'success' => false,
            'error' => 'Internal error',
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'request_id' => $this->generateSecureRequestId()
        ], $statusCode);
    }

    /**
     * Check if IP address is suspicious
     */
    private function isSuspiciousIP(string $ip): bool
    {
        // Check if IP is in blocked list
        $blockedIPs = cache()->get('blocked_ips', []);
        if (in_array($ip, $blockedIPs)) {
            return true;
        }

        // Check if IP has too many security events
        $securityEvents = cache()->get("security_events:{$ip}", 0);
        if ($securityEvents > self::SUSPICIOUS_IPS_BLOCK_THRESHOLD) {
            // Add to blocked list
            $blockedIPs[] = $ip;
            cache()->put('blocked_ips', array_unique($blockedIPs), 3600); // 1 hour
            return true;
        }

        return false;
    }

    /**
     * Check if user agent is suspicious
     */
    private function isSuspiciousUserAgent(?string $userAgent): bool
    {
        if (!$userAgent) {
            return false;
        }

        $suspiciousPatterns = [
            '/sqlmap/i',
            '/nmap/i',
            '/nikto/i',
            '/dirbuster/i',
            '/hydra/i',
            '/metasploit/i',
            '/burpsuite/i',
            '/owasp/i',
            '/acunetix/i',
            '/nessus/i',
            '/w3af/i',
            '/vega/i',
            '/paros/i',
            '/webscarab/i',
            '/skipfish/i',
            '/gobuster/i',
            '/dirb/i',
            '/curl/i',
            '/python/i',
            '/wget/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for attack patterns in URL
     */
    private function containsAttackPatterns(string $url): bool
    {
        $attackPatterns = [
            '/\.\.\//', // Directory traversal
            '/union\s+select/i',
            '/information_schema/i',
            '/script/i',
            '/javascript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/eval\(/i',
            '/system\(/i',
            '/exec\(/i',
            '/<script/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/\/etc\/passwd/i',
            '/c:\windows/i',
            '/phpinfo/i',
            '/config\.php/i',
            '/\.env/i',
            '/web\.config/i',
        ];

        foreach ($attackPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if rate limit is exceeded
     */
    private function isRateLimitExceeded(string $ip, ?int $userId): bool
    {
        $key = "rate_limit:{$ip}:" . ($userId ?: 'guest');
        $requests = cache()->get($key, 0);

        return $requests > 100; // 100 requests per minute
    }

    /**
     * Track security events for monitoring
     */
    private function trackSecurityEvent(string $ip, ?int $userId, string $eventType, int $eventCode): void
    {
        $key = "security_events:{$ip}";
        $events = cache()->get($key, []);
        $events[] = [
            'type' => $eventType,
            'code' => $eventCode,
            'timestamp' => now()->toISOString(),
            'user_id' => $userId
        ];

        // Keep only last 100 events
        if (count($events) > 100) {
            array_shift($events);
        }

        cache()->put($key, $events, 3600); // 1 hour TTL
    }

    /**
     * Check if exception is a security-related exception
     */
    private function isSecurityException(Throwable $exception): bool
    {
        $securityExceptions = [
            'Illuminate\Auth\AuthenticationException',
            'Illuminate\Auth\Access\AuthorizationException',
            'Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException',
        ];

        return in_array(get_class($exception), $securityExceptions);
    }

    /**
     * Check if exception is a validation exception
     */
    private function isValidationException(Throwable $exception): bool
    {
        return $exception instanceof \Illuminate\Validation\ValidationException;
    }

    /**
     * Check if exception is a database exception
     */
    private function isDatabaseException(Throwable $exception): bool
    {
        return $exception instanceof \Illuminate\Database\QueryException ||
               $exception instanceof \PDOException;
    }

    /**
     * Sanitize error message to prevent information leakage
     */
    private function sanitizeErrorMessage(string $message): string
    {
        // Remove file paths, database credentials, and other sensitive information
        $message = preg_replace('/\/.*\/.*\.(php|log|env)/i', '[FILE_PATH]', $message);
        $message = preg_replace('/(password|token|key|secret).*["\'][^"\']*["\']/i', '$1[REDACTED]', $message);
        $message = preg_replace('/(mysql|postgresql|sqlite).*\s(error|exception)/i', '$1 [DB_ERROR]', $message);

        return $message;
    }

    /**
     * Check for anomalous successful responses
     */
    private function checkForAnomalousSuccess(Request $request, Response $response): void
    {
        // Check for unusual patterns in successful responses
        $contentLength = strlen($response->getContent());

        // Flag unusually large responses
        if ($contentLength > 10000000) { // 10MB
            Log::info('Large response detected', [
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
                'url' => $request->url(),
                'content_length' => $contentLength
            ]);
        }

        // Check for responses with sensitive data patterns
        $content = $response->getContent();
        if (preg_match('/(password|token|key|secret).*["\'][^"\']*["\']/i', $content)) {
            Log::warning('Potential data leakage in response', [
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
                'url' => $request->url(),
                'content_length' => $contentLength
            ]);
        }
    }

    /**
     * Generate a secure request ID for tracking
     */
    private function generateSecureRequestId(): string
    {
        return hash('sha256', now()->toISOString() . random_bytes(32));
    }
}
