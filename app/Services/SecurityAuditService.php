<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SecurityAuditService
{
    /**
     * Security event types
     */
    const EVENT_TYPES = [
        'LOGIN_ATTEMPT' => 'login_attempt',
        'LOGIN_SUCCESS' => 'login_success',
        'LOGIN_FAILURE' => 'login_failure',
        'LOGOUT' => 'logout',
        'PASSWORD_RESET' => 'password_reset',
        'PASSWORD_CHANGE' => 'password_change',
        'CSRF_VIOLATION' => 'csrf_violation',
        'SQL_INJECTION_ATTEMPT' => 'sql_injection_attempt',
        'XSS_ATTEMPT' => 'xss_attempt',
        'RATE_LIMIT_EXCEEDED' => 'rate_limit_exceeded',
        'SUSPICIOUS_ACTIVITY' => 'suspicious_activity',
        'UNAUTHORIZED_ACCESS' => 'unauthorized_access',
        'FILE_UPLOAD_VIOLATION' => 'file_upload_violation',
        'SESSION_HIJACKING_ATTEMPT' => 'session_hijacking_attempt',
        'BRUTE_FORCE_ATTEMPT' => 'brute_force_attempt',
        'API_KEY_COMPROMISED' => 'api_key_compromised',
        'DATA_BREACH_ATTEMPT' => 'data_breach_attempt'
    ];

    /**
     * Risk levels
     */
    const RISK_LEVELS = [
        'LOW' => 'low',
        'MEDIUM' => 'medium',
        'HIGH' => 'high',
        'CRITICAL' => 'critical'
    ];

    /**
     * Log a security event
     *
     * @param string $eventType
     * @param array $eventData
     * @param string $riskLevel
     * @return bool
     */
    public static function logSecurityEvent(string $eventType, array $eventData = [], string $riskLevel = 'LOW'): bool
    {
        try {
            $eventId = self::generateEventId();
            $userId = auth()->id() ?? 'guest';
            $ip = request()->ip();
            $userAgent = request()->userAgent();

            $eventRecord = [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'risk_level' => $riskLevel,
                'user_id' => $userId,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'event_data' => json_encode($eventData),
                'session_id' => session()->getId(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'timestamp' => now()->toISOString(),
                'created_at' => now()
            ];

            // Log to database
            DB::table('security_audit_logs')->insert($eventRecord);

            // Log to application log with appropriate level
            $logLevel = self::getLogLevel($riskLevel);
            Log::log($logLevel, 'Security Event: ' . $eventType, [
                'event_id' => $eventId,
                'risk_level' => $riskLevel,
                'user_id' => $userId,
                'ip' => $ip,
                'event_data' => $eventData
            ]);

            // Cache for real-time monitoring
            self::cacheSecurityEvent($eventRecord);

            // Check for security thresholds
            self::checkSecurityThresholds($eventType, $userId, $ip);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to log security event', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'risk_level' => $riskLevel
            ]);
            return false;
        }
    }

    /**
     * Generate unique event ID
     */
    private static function generateEventId(): string
    {
        return 'SEC_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Get appropriate log level based on risk
     */
    private static function getLogLevel(string $riskLevel): string
    {
        return match($riskLevel) {
            'CRITICAL' => 'emergency',
            'HIGH' => 'error',
            'MEDIUM' => 'warning',
            'LOW' => 'info',
            default => 'info'
        };
    }

    /**
     * Cache security event for real-time monitoring
     */
    private static function cacheSecurityEvent(array $eventRecord): void
    {
        $cacheKey = 'security_events_recent';
        $events = Cache::get($cacheKey, []);

        // Keep only last 100 events
        array_unshift($events, $eventRecord);
        $events = array_slice($events, 0, 100);

        Cache::put($cacheKey, $events, now()->addHours(24));
    }

    /**
     * Check security thresholds and trigger alerts
     */
    private static function checkSecurityThresholds(string $eventType, string $userId, string $ip): void
    {
        $timeWindow = now()->subMinutes(5);

        // Check for brute force attempts
        if (in_array($eventType, ['login_failure', 'password_reset'])) {
            $failureCount = DB::table('security_audit_logs')
                ->where('event_type', 'login_failure')
                ->where('ip_address', $ip)
                ->where('created_at', '>', $timeWindow)
                ->count();

            if ($failureCount >= 5) {
                self::triggerSecurityAlert('BRUTE_FORCE_DETECTED', [
                    'ip_address' => $ip,
                    'failure_count' => $failureCount,
                    'time_window' => '5 minutes'
                ], 'HIGH');
            }
        }

        // Check for multiple CSRF violations
        if ($eventType === 'csrf_violation') {
            $csrfCount = DB::table('security_audit_logs')
                ->where('event_type', 'csrf_violation')
                ->where('ip_address', $ip)
                ->where('created_at', '>', $timeWindow)
                ->count();

            if ($csrfCount >= 3) {
                self::triggerSecurityAlert('CSRF_ATTACK_DETECTED', [
                    'ip_address' => $ip,
                    'violation_count' => $csrfCount,
                    'time_window' => '5 minutes'
                ], 'HIGH');
            }
        }

        // Check for SQL injection attempts
        if ($eventType === 'sql_injection_attempt') {
            $sqlCount = DB::table('security_audit_logs')
                ->where('event_type', 'sql_injection_attempt')
                ->where('ip_address', $ip)
                ->where('created_at', '>', $timeWindow)
                ->count();

            if ($sqlCount >= 2) {
                self::triggerSecurityAlert('SQL_INJECTION_ATTACK_DETECTED', [
                    'ip_address' => $ip,
                    'attempt_count' => $sqlCount,
                    'time_window' => '5 minutes'
                ], 'CRITICAL');
            }
        }
    }

    /**
     * Trigger security alert
     */
    private static function triggerSecurityAlert(string $alertType, array $alertData, string $riskLevel): void
    {
        $alertId = 'ALERT_' . strtoupper(uniqid()) . '_' . time();

        $alertRecord = [
            'alert_id' => $alertId,
            'alert_type' => $alertType,
            'risk_level' => $riskLevel,
            'alert_data' => json_encode($alertData),
            'status' => 'ACTIVE',
            'created_at' => now()
        ];

        // Store alert
        DB::table('security_alerts')->insert($alertRecord);

        // Log critical alert
        Log::emergency('SECURITY ALERT TRIGGERED: ' . $alertType, [
            'alert_id' => $alertId,
            'risk_level' => $riskLevel,
            'alert_data' => $alertData
        ]);

        // Cache active alerts for dashboard
        $activeAlerts = Cache::get('security_alerts_active', []);
        $activeAlerts[$alertId] = $alertRecord;
        Cache::put('security_alerts_active', $activeAlerts, now()->addHours(24));
    }

    /**
     * Get recent security events
     */
    public static function getRecentEvents(int $limit = 50): array
    {
        return Cache::get('security_events_recent', []);
    }

    /**
     * Get active security alerts
     */
    public static function getActiveAlerts(): array
    {
        return Cache::get('security_alerts_active', []);
    }

    /**
     * Get security statistics
     */
    public static function getSecurityStats(string $timeRange = '24h'): array
    {
        $startTime = match($timeRange) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay()
        };

        $stats = [];

        // Event counts by type
        $eventStats = DB::table('security_audit_logs')
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>', $startTime)
            ->groupBy('event_type')
            ->get();

        $stats['events_by_type'] = $eventStats->pluck('count', 'event_type')->toArray();

        // Risk level distribution
        $riskStats = DB::table('security_audit_logs')
            ->select('risk_level', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>', $startTime)
            ->groupBy('risk_level')
            ->get();

        $stats['risk_distribution'] = $riskStats->pluck('count', 'risk_level')->toArray();

        // Top IPs with security events
        $ipStats = DB::table('security_audit_logs')
            ->select('ip_address', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>', $startTime)
            ->where('risk_level', '!=', 'LOW')
            ->groupBy('ip_address')
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        $stats['top_suspicious_ips'] = $ipStats->pluck('count', 'ip_address')->toArray();

        // Active alerts count
        $stats['active_alerts'] = count(self::getActiveAlerts());

        return $stats;
    }

    /**
     * Resolve security alert
     */
    public static function resolveAlert(string $alertId, string $resolution = 'RESOLVED'): bool
    {
        try {
            DB::table('security_alerts')
                ->where('alert_id', $alertId)
                ->update([
                    'status' => $resolution,
                    'resolved_at' => now()
                ]);

            // Remove from cache
            $activeAlerts = Cache::get('security_alerts_active', []);
            unset($activeAlerts[$alertId]);
            Cache::put('security_alerts_active', $activeAlerts, now()->addHours(24));

            Log::info('Security alert resolved', [
                'alert_id' => $alertId,
                'resolution' => $resolution
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to resolve security alert', [
                'alert_id' => $alertId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Quick methods for common security events
     */
    public static function logLoginAttempt(string $email, bool $success): void
    {
        $eventType = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILURE';
        $riskLevel = $success ? 'LOW' : 'MEDIUM';

        self::logSecurityEvent($eventType, [
            'email' => $email,
            'success' => $success
        ], $riskLevel);
    }

    public static function logCsrfViolation(array $additionalData = []): void
    {
        self::logSecurityEvent('CSRF_VIOLATION', $additionalData, 'HIGH');
    }

    public static function logSqlInjectionAttempt(string $query, array $additionalData = []): void
    {
        self::logSecurityEvent('SQL_INJECTION_ATTEMPT', array_merge([
            'suspicious_query' => $query
        ], $additionalData), 'CRITICAL');
    }

    public static function logRateLimitExceeded(string $type, int $attempts): void
    {
        self::logSecurityEvent('RATE_LIMIT_EXCEEDED', [
            'limit_type' => $type,
            'attempts' => $attempts
        ], 'MEDIUM');
    }

    public static function logUnauthorizedAccess(string $resource, string $action): void
    {
        self::logSecurityEvent('UNAUTHORIZED_ACCESS', [
            'resource' => $resource,
            'action' => $action
        ], 'HIGH');
    }
}