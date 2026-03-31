<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EnhancedCsrfMiddleware
{
    /**
     * CSRF token lifetime in minutes
     */
    const TOKEN_LIFETIME = 60; // 1 hour

    /**
     * Maximum tokens per user
     */
    const MAX_TOKENS_PER_USER = 10;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip CSRF for safe HTTP methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Skip CSRF for API routes
        if ($request->expectsJson() || $request->is('api/*')) {
            return $next($request);
        }

        // Skip for certain routes that don't need CSRF protection
        $skipRoutes = [
            'login',
            'register',
            'logout',
            'password/*',
            'verification/*'
        ];

        $currentRoute = $request->route() ? $request->route()->getName() : $request->path();

        foreach ($skipRoutes as $skipRoute) {
            if (Str::is($skipRoute, $currentRoute)) {
                return $next($request);
            }
        }

        // Validate CSRF token
        if (!$this->validateCsrfToken($request)) {
            Log::warning('CSRF token validation failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'token_provided' => !empty($request->input('_token'))
            ]);

            return $this->csrfErrorResponse($request);
        }

        // Generate new token for next request
        $this->generateNewToken($request);

        return $next($request);
    }

    /**
     * Validate CSRF token
     */
    private function validateCsrfToken(Request $request): bool
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (empty($token)) {
            return false;
        }

        $userId = auth()->id() ?? 'guest';
        $cacheKey = "csrf_token:{$userId}:{$token}";

        // Single cache read — eliminated the previous Cache::has() + Cache::get() double-read.
        $tokenData = Cache::get($cacheKey);

        if ($tokenData === null) {
            return false;
        }

        // Validate token structure
        if (!is_array($tokenData) || !isset($tokenData['created_at'])) {
            return false;
        }

        // Check token age
        $tokenAge = now()->diffInMinutes($tokenData['created_at']);
        if ($tokenAge > self::TOKEN_LIFETIME) {
            Cache::forget($cacheKey);
            return false;
        }

        // Validate user agent (optional but recommended)
        if (isset($tokenData['user_agent']) && $tokenData['user_agent'] !== $request->userAgent()) {
            Log::warning('CSRF token user agent mismatch', [
                'ip' => $request->ip(),
                'user_id' => $userId
            ]);
            return false;
        }

        // Validate IP address (optional but recommended)
        if (isset($tokenData['ip']) && $tokenData['ip'] !== $request->ip()) {
            Log::warning('CSRF token IP mismatch', [
                'user_id' => $userId
            ]);
            return false;
        }

        return true;
    }

    /**
     * Generate new CSRF token
     */
    private function generateNewToken(Request $request): void
    {
        $userId = auth()->id() ?? 'guest';
        $token = Str::random(40);

        // Clean up old tokens for this user
        $this->cleanupOldTokens($userId);

        // Store new token
        $tokenData = [
            'created_at' => now(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'user_id' => $userId
        ];

        $cacheKey = "csrf_token:{$userId}:{$token}";
        Cache::put($cacheKey, $tokenData, now()->addMinutes(self::TOKEN_LIFETIME));

        // Store token in session for forms
        session(['_token' => $token]);
    }

    /**
     * Clean up old tokens to prevent memory leaks
     */
    private function cleanupOldTokens(string $userId): void
    {
        $pattern = "csrf_token:{$userId}:*";

        // Get all tokens for this user
        $keys = Cache::get($pattern, []);

        if (count($keys) >= self::MAX_TOKENS_PER_USER) {
            // Remove oldest tokens
            $tokensToRemove = count($keys) - self::MAX_TOKENS_PER_USER + 1;

            foreach (array_slice($keys, 0, $tokensToRemove) as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Generate CSRF error response
     */
    private function csrfErrorResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'CSRF token validation failed',
                'message' => 'Your session has expired. Please refresh the page and try again.',
                'code' => 'CSRF_TOKEN_EXPIRED'
            ], 419);
        }

        return response()->view('errors.csrf', [], 419);
    }

    /**
     * Get current CSRF token for forms
     */
    public static function getCurrentToken(): string
    {
        return session('_token', '');
    }

    /**
     * Generate CSRF token field for forms
     */
    public static function csrfField(): string
    {
        $token = self::getCurrentToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Generate CSRF meta tag for AJAX requests
     */
    public static function csrfMeta(): string
    {
        $token = self::getCurrentToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}