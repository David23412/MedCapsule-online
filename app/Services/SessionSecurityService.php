<?php

namespace App\Services;

use App\Models\User;
use App\Models\SessionSecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SessionSecurityService
{
    /**
     * Generate device fingerprint from request
     */
    public function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent() ?? 'unknown',
            $request->header('Accept-Language') ?? 'unknown',
            $request->header('Accept-Encoding') ?? 'unknown',
            $request->header('Accept') ?? 'unknown',
            $request->header('DNT') ?? 'unknown',
            $request->header('Upgrade-Insecure-Requests') ?? 'unknown',
        ];
        
        return hash('sha256', implode('|', $components));
    }

    /**
     * Analyze login attempt for suspicious activity
     */
    public function analyzeLoginAttempt(User $user, Request $request): array
    {
        $currentIp = $request->ip();
        $currentFingerprint = $this->generateDeviceFingerprint($request);
        $riskLevel = 'low';
        $flags = [];

        // Check IP change
        if ($user->last_login_ip && $user->last_login_ip !== $currentIp) {
            $flags[] = 'ip_change';
            $riskLevel = 'medium';
            
            // Check if IPs are from different countries/regions
            if ($this->isDifferentGeolocation($user->last_login_ip, $currentIp)) {
                $flags[] = 'geo_change';
                $riskLevel = 'high';
            }
        }

        // Check device fingerprint change
        if ($user->device_fingerprint && $user->device_fingerprint !== $currentFingerprint) {
            $flags[] = 'device_change';
            $riskLevel = $riskLevel === 'high' ? 'critical' : 'medium';
        }

        // Check rapid login attempts
        $recentAttempts = $this->getRecentLoginAttempts($user->id);
        if ($recentAttempts > 3) {
            $flags[] = 'rapid_attempts';
            $riskLevel = 'high';
        }

        // Check time-based patterns
        if ($this->isUnusualLoginTime($user)) {
            $flags[] = 'unusual_time';
            $riskLevel = $riskLevel === 'low' ? 'medium' : $riskLevel;
        }

        return [
            'risk_level' => $riskLevel,
            'flags' => $flags,
            'fingerprint' => $currentFingerprint,
            'ip' => $currentIp
        ];
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(User $user, string $eventType, Request $request, array $securityData = []): void
    {
        try {
            SessionSecurityLog::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'unknown',
                'device_fingerprint' => $this->generateDeviceFingerprint($request),
                'security_data' => $securityData,
                'risk_level' => $securityData['risk_level'] ?? 'low',
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

    /**
     * Check if session is potentially hijacked
     */
    public function detectSessionHijacking(User $user, Request $request): bool
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

        // Multiple rapid requests from different IPs
        $recentIps = $this->getRecentRequestIPs($user->id);
        if (count($recentIps) > 2) {
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
     * Enhanced session validation with security checks
     */
    public function validateSessionSecurity(User $user, Request $request): array
    {
        $result = [
            'valid' => true,
            'action' => 'allow',
            'reason' => null
        ];

        // Skip validation if user has no active session
        if (!$user->active_session_token) {
            return $result;
        }

        // For testing purposes, we'll simulate session validation
        // In real middleware, this would check against the actual session ID
        $currentFingerprint = $this->generateDeviceFingerprint($request);
        $currentIP = $request->ip();

        // Check if device fingerprint matches (if set)
        if ($user->device_fingerprint && $user->device_fingerprint !== $currentFingerprint) {
            $result = [
                'valid' => false,
                'action' => 'logout',
                'reason' => 'Device fingerprint mismatch - session hijacking detected'
            ];
            return $result;
        }

        // Check for IP changes (basic geolocation check)
        if ($user->last_login_ip && $this->isDifferentGeolocation($user->last_login_ip, $currentIP)) {
            $result = [
                'valid' => false,
                'action' => 'logout',
                'reason' => 'Geographic location change detected'
            ];
            return $result;
        }

        // Advanced security checks
        if ($this->detectSessionHijacking($user, $request)) {
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
     * Track user activity for rate limiting
     */
    public function trackActivity(int $userId, string $ip): void
    {
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
}