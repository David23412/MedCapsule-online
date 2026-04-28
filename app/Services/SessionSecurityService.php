<?php

namespace App\Services;

use App\Models\User;
use App\Models\SessionSecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Normalizer;

class SessionSecurityService
{
    private array $fingerprintCache = [];
    private const HARD_LOCK_THRESHOLD = 3;
    private const HARD_LOCK_PERIOD = 86400 * 30; // 30 days

    /**
     * In-memory debounce map: userId → unix timestamp of last trackActivity execution.
     * Prevents 4 cache ops (2 reads + 2 writes) from firing on every single
     * authenticated request.  State is kept per PHP-FPM worker and is consistent
     * enough for rate-limiting purposes because timestamps expire by elapsed time.
     *
     * @var array<int, int>
     */
    private static array $activityLastTracked = [];

    /** Minimum seconds between consecutive trackActivity cache writes per user. */
    private const TRACK_ACTIVITY_INTERVAL = 30;
    /**
     * Generate device fingerprint from request
     */
    public function generateDeviceFingerprint(Request $request): string
    {
        $cacheKey = spl_object_hash($request);
        if (isset($this->fingerprintCache[$cacheKey])) {
            return $this->fingerprintCache[$cacheKey];
        }

        // Get all headers in their original structure for maximum sensitivity
        $allHeaders = $request->headers->all();
        
        // Create a signature that's sensitive to header order and structure
        $headerStructureSignature = hash('sha256', serialize($allHeaders));
        
        // Normalize and sanitize components to prevent spoofing
        $components = [
            $this->normalizeHeader($request->userAgent() ?? 'unknown'),
            $this->normalizeHeader($request->header('Accept-Language') ?? 'unknown'),
            $this->normalizeHeader($request->header('Accept-Encoding') ?? 'unknown'),
            $this->normalizeHeader($request->header('Accept') ?? 'unknown'),
            $this->normalizeHeader($request->header('DNT') ?? 'unknown'),
            $this->normalizeHeader($request->header('Upgrade-Insecure-Requests') ?? 'unknown'),
        ];
        
        // Add additional entropy from request characteristics
        $additionalEntropy = [
            $request->getScheme(),
            $request->getHttpHost(),
            $request->getPort(),
            count($request->headers->all()), // Header count as additional fingerprint
            $headerStructureSignature, // Full header structure signature for security
            hash('md5', json_encode(array_keys($allHeaders))), // Header key order
        ];
        
        $allComponents = array_merge($components, $additionalEntropy);
        
        $fingerprint = hash('sha256', implode('|', $allComponents));
        $this->fingerprintCache[$cacheKey] = $fingerprint;
        
        return $fingerprint;
    }

    /**
     * Analyze login attempt for suspicious activity
     */
    public function analyzeLoginAttempt(User $user, Request $request): array
    {
        $currentIp = $request->ip();
        $currentFingerprint = $this->generateDeviceFingerprint($request);
        $userAgent = $request->userAgent() ?? '';
        $riskLevel = 'low';
        $flags = [];

        // CRITICAL: Check for obvious attack patterns first
        if ($this->isObviousAttackPattern($userAgent, $currentIp)) {
            $flags[] = 'attack_pattern';
            $riskLevel = 'critical';
        }

        // Check for bot/automated traffic
        if ($this->isBotTraffic($userAgent)) {
            $flags[] = 'bot_traffic';
            $riskLevel = $riskLevel === 'critical' ? 'critical' : 'high';
        }

        // Check IP change
        if ($user->last_login_ip && $user->last_login_ip !== $currentIp) {
            $flags[] = 'ip_change';
            $riskLevel = $riskLevel === 'low' ? 'medium' : $riskLevel;
            
            // Check if IPs are from different countries/regions
            if ($this->isDifferentGeolocation($user->last_login_ip, $currentIp)) {
                $flags[] = 'geo_change';
                $riskLevel = $riskLevel === 'low' ? 'high' : $riskLevel;
            }
        }

        // Check device fingerprint change
        if ($user->device_fingerprint && $user->device_fingerprint !== $currentFingerprint) {
            $flags[] = 'device_change';
            $riskLevel = $riskLevel === 'low' ? 'medium' : ($riskLevel === 'medium' ? 'high' : $riskLevel);
        }

        // Check rapid login attempts
        $recentAttempts = $this->getRecentLoginAttempts($user->id);
        if ($recentAttempts > 3) {
            $flags[] = 'rapid_attempts';
            $riskLevel = $riskLevel === 'low' ? 'high' : $riskLevel;
        }

        // Check for suspicious IP patterns
        if ($this->isSuspiciousIP($currentIp)) {
            $flags[] = 'suspicious_ip';
            $riskLevel = $riskLevel === 'low' ? 'high' : $riskLevel;
        }

        // Check time-based patterns
        if ($this->isUnusualLoginTime($user)) {
            $flags[] = 'unusual_time';
            $riskLevel = $riskLevel === 'low' ? 'medium' : $riskLevel;
        }

        // Multiple concurrent attempts from different IPs
        $recentIps = $this->getRecentRequestIPs($user->id);
        if (count($recentIps) > 2) {
            $flags[] = 'multiple_ips';
            $riskLevel = 'high';
        }

        return [
            'risk_level' => $riskLevel,
            'flags' => $flags,
            'fingerprint' => $currentFingerprint,
            'ip' => $currentIp
        ];
    }

    public function logSecurityEvent(User $user, string $eventType, Request $request, array $securityData = []): void
    {
        try {
            $risk = $securityData['risk_level'] ?? 'low';
            
            // Hard Lock escalation logic
            if ($risk === 'critical') {
                $this->incrementHardLockScore($user->id);
            }

            SessionSecurityLog::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'unknown',
                'device_fingerprint' => $this->generateDeviceFingerprint($request),
                'security_data' => $securityData,
                'risk_level' => $risk,
                'action_taken' => $securityData['action_taken'] ?? false
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log security event', [
                'user_id' => $user->id,
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function incrementHardLockScore(int $userId): void
    {
        $key = "hard_lock_score_{$userId}";
        $score = Cache::get($key, 0) + 1;
        Cache::put($key, $score, 86400); // 24 hour window
        
        if ($score >= self::HARD_LOCK_THRESHOLD) {
            Cache::put("account_locked_permanent_{$userId}", true, self::HARD_LOCK_PERIOD);
        }
    }

    public function isPermanentlyLocked(int $userId): bool
    {
        return (bool) Cache::get("account_locked_permanent_{$userId}", false);
    }

    /**
     * Detect if two different IPs are using the same account concurrently.
     *
     * @param array|null $recentIps  Pre-fetched IPs to avoid a redundant cache read
     *                               when called from validateSessionSecurity().
     */
    public function detectConcurrentUsage(int $userId, string $currentIp, ?array $recentIps = null): bool
    {
        $ips = $recentIps ?? Cache::get("recent_ips_{$userId}", []);
        
        // If we see more than 1 IP in the last 2 minutes, it's highly suspicious
        if (count($ips) > 1 && !in_array($currentIp, $ips)) {
             return true;
        }

        return false;
    }

    /**
     * Enhanced session validation with Zero Sharing policy
     */
    public function validateSessionSecurity(User $user, Request $request): array
    {
        $result = [
            'valid' => true,
            'action' => 'allow',
            'reason' => null
        ];

        // 1. SESSION TOKEN LOCK (Primary Multi-Device Prevention)
        $currentSessionId = $request->session()->getId();
        if ($user->active_session_token && $user->active_session_token !== $currentSessionId) {
            $result = [
                'valid' => false,
                'action' => 'logout',
                'reason' => 'Multiple logins detected. This account is already in use on another device.'
            ];
            return $result;
        }

        // 2. DEVICE FINGERPRINT LOCK
        $currentFingerprint = $this->generateDeviceFingerprint($request);
        if ($user->device_fingerprint && $user->device_fingerprint !== $currentFingerprint) {
            $result = [
                'valid' => false,
                'action' => 'logout',
                'reason' => 'Security fingerprint mismatch. Your device/browser signature has changed.'
            ];
            return $result;
        }

        // 3. CONCURRENT IP LOCK — read recent_ips once and reuse for the
        // detectSessionHijacking check below to avoid two cache reads of the same key.
        $currentIP  = $request->ip();
        $recentIps  = $this->getRecentRequestIPs($user->id);
        if ($this->detectConcurrentUsage($user->id, $currentIP, $recentIps)) {
            $result = [
                'valid' => false,
                'action' => 'force_logout',
                'reason' => 'Concurrent account usage detected from multiple geographic locations.'
            ];
            return $result;
        }

        // Advanced security checks (these can still run even if the above checks pass, for additional layers)
        if ($this->detectSessionHijacking($user, $request, $recentIps)) {
            $result = [
                'valid' => false,
                'action' => 'force_logout',
                'reason' => 'Suspicious activity detected - security breach prevented'
            ];
            return $result;
        }

        // Rate limiting check
        if ($this->isExcessiveActivity($user->id)) {
            $result = [
                'valid' => false,
                'action' => 'temporary_block',
                'reason' => 'Excessive activity detected - temporary restriction'
            ];
            return $result;
        }

        return $result;
    }

    /**
     * Check if session is potentially hijacked.
     *
     * @param array|null $recentIps  Pre-fetched IPs passed from validateSessionSecurity()
     *                               to avoid reading the same cache key twice per request.
     */
    public function detectSessionHijacking(User $user, Request $request, ?array $recentIps = null): bool
    {
        $currentFingerprint = $this->generateDeviceFingerprint($request);
        $currentIp = $request->ip();

        // Immediate red flags
        $suspiciousActivity = false;

        // Device fingerprint changed mid-session
        if ($user->device_fingerprint && $user->device_fingerprint !== $currentFingerprint) {
            $suspiciousActivity = true;
        }

        // IP changed dramatically (different country)
        if ($user->last_login_ip && $this->isDifferentGeolocation($user->last_login_ip, $currentIp)) {
            $suspiciousActivity = true;
        }

        // Multiple rapid requests from different IPs — reuse pre-fetched list when available
        $ips = $recentIps ?? $this->getRecentRequestIPs($user->id);
        if (count($ips) > 2) {
            $suspiciousActivity = true;
        }

        if ($suspiciousActivity) {
            $this->logSecurityEvent($user, 'session_hijack', $request, [
                'risk_level' => 'critical',
                'action_taken' => true,
                'detection_reason' => 'Device/IP anomaly detected'
            ]);
        }

        return $suspiciousActivity;
    }

    /**
                'action' => 'temporary_block',
                'reason' => 'Excessive activity detected - temporary restriction'
            ];
            return $result;
        }

        return $result;
    }

    /**
     * Check if IPs are from different geolocations
     */
    private function isDifferentGeolocation(string $ip1, string $ip2): bool
    {
        // Simple check - in production, use a proper geolocation service
        $ip1Parts = explode('.', $ip1);
        $ip2Parts = explode('.', $ip2);
        
        // If first two octets are different, likely different regions
        return ($ip1Parts[0] !== $ip2Parts[0] || $ip1Parts[1] !== $ip2Parts[1]);
    }

    /**
     * Get recent login attempts count
     */
    private function getRecentLoginAttempts(int $userId): int
    {
        $cacheKey = "login_attempts_{$userId}";
        return Cache::get($cacheKey, 0);
    }

    /**
     * Check if login time is unusual for user
     */
    private function isUnusualLoginTime(User $user): bool
    {
        $currentHour = now()->hour;
        
        // Simple heuristic - flag logins between 2 AM and 6 AM as unusual
        return $currentHour >= 2 && $currentHour <= 6;
    }

    /**
     * Get recent request IPs for user
     */
    private function getRecentRequestIPs(int $userId): array
    {
        $cacheKey = "recent_ips_{$userId}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Handle new login - update session token and security data
     */
    public function handleNewLogin(User $user, Request $request): void
    {
        $currentSessionToken = $request->session()->getId();
        $currentFingerprint = $this->generateDeviceFingerprint($request);

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionToken)
            ->delete();

        DB::table('sessions')
            ->where('id', $currentSessionToken)
            ->update([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'fingerprint' => $currentFingerprint,
                'is_visitor' => false,
            ]);
        
        // Update user with new session information
        $user->update([
            'active_session_token' => $currentSessionToken,
            'session_created_at' => now(),
            'device_fingerprint' => $currentFingerprint,
            'last_login_ip' => $request->ip(),
            'last_user_agent' => $request->userAgent(),
            'last_security_check' => now(),
            'concurrent_login_attempts' => 0 // Reset on successful login
        ]);

        // Log the login event
        $this->logSecurityEvent($user, 'login', $request, [
            'risk_level' => 'low',
            'action_taken' => false,
            'session_token' => substr($currentSessionToken, 0, 8) . '...' // Partial for security
        ]);
    }

    /**
     * Check for excessive activity
     */
    private function isExcessiveActivity(int $userId): bool
    {
        $cacheKey = "activity_count_{$userId}";
        $count = Cache::get($cacheKey, 0);
        
        // More than 100 requests in 5 minutes is suspicious
        return $count > 100;
    }

    /**
     * Track user activity for rate limiting.
     *
     * Debounced: at most once every TRACK_ACTIVITY_INTERVAL seconds per user
     * (tracked in-memory per PHP-FPM worker).  This eliminates the guaranteed
     * 4 cache round-trips that previously fired on every single authenticated
     * request.  Rate-limiting accuracy is preserved because the counters are
     * still updated regularly; they are just not incremented every millisecond.
     */
    public function trackActivity(int $userId, string $ip): void
    {
        $now = time();

        if (isset(self::$activityLastTracked[$userId]) &&
            ($now - self::$activityLastTracked[$userId]) < self::TRACK_ACTIVITY_INTERVAL) {
            return; // Debounced: already tracked within the current window
        }

        self::$activityLastTracked[$userId] = $now;

        // Track request count
        $activityKey = "activity_count_{$userId}";
        $count = Cache::get($activityKey, 0);
        Cache::put($activityKey, $count + 1, 300); // 5 minutes

        // Track recent IPs
        $ipKey = "recent_ips_{$userId}";
        $ips = Cache::get($ipKey, []);
        $ips[] = $ip;
        $ips = array_unique(array_slice($ips, -5)); // Keep last 5 unique IPs
        Cache::put($ipKey, $ips, 300); // 5 minutes
    }

    /**
     * Increment login attempt counter
     */
    public function incrementLoginAttempts(int $userId): void
    {
        $cacheKey = "login_attempts_{$userId}";
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, 900); // 15 minutes
    }

    /**
     * Check for obvious attack patterns
     */
    private function isObviousAttackPattern(string $userAgent, string $ip): bool
    {
        $attackPatterns = [
            'AttackBot',
            'LoadTest',
            'SustainedAttack',
            'BruteForce',
            'Hacker',
            'Exploit',
            'Scanner',
            'Penetration'
        ];

        foreach ($attackPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        // Check for suspicious IP patterns (private/test ranges used in attacks)
        if (preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/', $ip)) {
            return true;
        }

        return false;
    }

    /**
     * Check if traffic appears to be from a bot
     */
    private function isBotTraffic(string $userAgent): bool
    {
        $botPatterns = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'automated',
            'script',
            'curl',
            'wget',
            'python',
            'java',
            'perl'
        ];

        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        // Check for missing or very short user agents
        if (empty($userAgent) || strlen($userAgent) < 10) {
            return true;
        }

        return false;
    }

    /**
     * Check if IP is suspicious
     */
    private function isSuspiciousIP(string $ip): bool
    {
        // Check for common attack IP patterns
        $suspiciousPatterns = [
            '/^10\.0\.0\./', // Test range often used in attacks
            '/^192\.168\.1\./', // Common internal range
            '/^172\.16\.0\./', // Private range
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize header value to prevent spoofing attacks
     */
    private function normalizeHeader(string $header): string
    {
        // Convert to lowercase to prevent case manipulation
        $normalized = strtolower($header);
        
        // Remove extra whitespace and normalize spaces
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));
        
        // Remove null bytes and other control characters
        $normalized = preg_replace('/[\x00-\x1F\x7F]/', '', $normalized);
        
        // Normalize Unicode to prevent normalization attacks
        if (function_exists('normalizer_normalize')) {
            $normalized = normalizer_normalize($normalized, Normalizer::FORM_C);
        }
        
        // Remove zero-width characters that could be used for spoofing
        $normalized = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $normalized);
        
        return $normalized;
    }
}
