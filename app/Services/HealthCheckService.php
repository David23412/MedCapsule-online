<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthCheckService
{
    private const HEALTH_CHECK_TIMEOUT = 10; // seconds
    private const CRITICAL_SERVICES = [
        'database',
        'cache',
        'storage',
        'quiz_system',
        'payment_system'
    ];

    /**
     * Perform comprehensive health check with caching
     */
    public static function performHealthCheck(): array
    {
        // Cache health check results for 30 seconds to avoid excessive DB queries
        return ResilientUltraConsolidatedCacheService::safeRemember(
            'health_check_status',
            30,
            function () {
                return self::executeHealthCheck();
            },
            ['overall_status' => 'unknown', 'error' => 'Cache fallback'],
            ['operation' => 'health_check']
        );
    }

    /**
     * Execute the actual health check
     */
    private static function executeHealthCheck(): array
    {
        $startTime = microtime(true);
        $healthStatus = [
            'overall_status' => 'healthy',
            'timestamp' => now(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'services' => [],
            'warnings' => [],
            'errors' => [],
            'response_time_ms' => 0
        ];

        $failedServices = 0;
        $warningServices = 0;

        // Check each critical service
        foreach (self::CRITICAL_SERVICES as $service) {
            try {
                $serviceHealth = self::checkService($service);
                $healthStatus['services'][$service] = $serviceHealth;

                if ($serviceHealth['status'] === 'unhealthy') {
                    $failedServices++;
                    $healthStatus['errors'][] = "Service '{$service}' is unhealthy: " . ($serviceHealth['error'] ?? 'Unknown error');
                } elseif ($serviceHealth['status'] === 'degraded') {
                    $warningServices++;
                    $healthStatus['warnings'][] = "Service '{$service}' is degraded: " . ($serviceHealth['warning'] ?? 'Performance issues');
                }
            } catch (Exception $e) {
                $failedServices++;
                $healthStatus['services'][$service] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'response_time_ms' => 0
                ];
                $healthStatus['errors'][] = "Service '{$service}' check failed: " . $e->getMessage();
            }
        }

        // Determine overall status
        if ($failedServices > 0) {
            $healthStatus['overall_status'] = 'unhealthy';
        } elseif ($warningServices > 0) {
            $healthStatus['overall_status'] = 'degraded';
        }

        $healthStatus['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        // Log health check results
        if ($healthStatus['overall_status'] !== 'healthy') {
            Log::warning('Health check detected issues', [
                'status' => $healthStatus['overall_status'],
                'failed_services' => $failedServices,
                'warning_services' => $warningServices,
                'errors' => $healthStatus['errors'],
                'warnings' => $healthStatus['warnings']
            ]);
        }

        return $healthStatus;
    }

    /**
     * Check individual service health
     */
    private static function checkService(string $service): array
    {
        $startTime = microtime(true);
        
        try {
            switch ($service) {
                case 'database':
                    return self::checkDatabase();
                case 'cache':
                    return self::checkCache();
                case 'storage':
                    return self::checkStorage();
                case 'quiz_system':
                    return self::checkQuizSystem();
                case 'payment_system':
                    return self::checkPaymentSystem();
                default:
                    throw new Exception("Unknown service: {$service}");
            }
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Check database health
     */
    private static function checkDatabase(): array
    {
        $startedAt = microtime(true);
        try {
            DB::select('SELECT 1');
            return ['status' => 'healthy', 'response_time_ms' => round((microtime(true) - $startedAt) * 1000, 2)];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage(), 'response_time_ms' => round((microtime(true) - $startedAt) * 1000, 2)];
        }
    }

    /**
     * Check cache health
     */
    private static function checkCache(): array
    {
        return ResilientUltraConsolidatedCacheService::healthCheck();
    }

    /**
     * Check storage health
     */
    private static function checkStorage(): array
    {
        $startTime = microtime(true);
        
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test content';
            
            // Test write
            $writeSuccess = \Storage::disk('public')->put($testFile, $testContent);
            if (!$writeSuccess) {
                throw new Exception('Failed to write test file');
            }
            
            // Test read
            $readContent = \Storage::disk('public')->get($testFile);
            if ($readContent !== $testContent) {
                throw new Exception('File content mismatch');
            }
            
            // Test delete
            $deleteSuccess = \Storage::disk('public')->delete($testFile);
            if (!$deleteSuccess) {
                throw new Exception('Failed to delete test file');
            }
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($duration, 2),
                'disk_space' => self::getDiskSpace()
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Check quiz system health
     */
    private static function checkQuizSystem(): array
    {
        $startTime = microtime(true);
        
        try {
            // Check if questions exist
            $questionCount = DB::table('questions')->count();
            if ($questionCount === 0) {
                return [
                    'status' => 'degraded',
                    'warning' => 'No questions found in database',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ];
            }
            
            // Check if topics exist
            $topicCount = DB::table('topics')->count();
            if ($topicCount === 0) {
                return [
                    'status' => 'degraded',
                    'warning' => 'No topics found in database',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ];
            }
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'questions_count' => $questionCount,
                'topics_count' => $topicCount
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Check payment system health
     */
    private static function checkPaymentSystem(): array
    {
        $startTime = microtime(true);
        
        try {
            // Check if payment methods are configured
            $paymentMethods = config('medcapsule.payment.payment_methods', []);
            $enabledMethods = array_filter($paymentMethods, function($method) {
                return $method['enabled'] ?? false;
            });
            
            if (empty($enabledMethods)) {
                return [
                    'status' => 'degraded',
                    'warning' => 'No payment methods enabled',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ];
            }
            
            // Check recent payment activity
            $recentPayments = DB::table('payments')->where('created_at', '>=', now()->subHours(24))->count();
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'enabled_methods' => count($enabledMethods),
                'recent_payments_24h' => $recentPayments
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Get disk space information
     */
    private static function getDiskSpace(): array
    {
        try {
            $path = storage_path();
            $totalBytes = disk_total_space($path);
            $freeBytes = disk_free_space($path);
            $usedBytes = $totalBytes - $freeBytes;
            $usagePercent = round(($usedBytes / $totalBytes) * 100, 2);
            
            return [
                'total_gb' => round($totalBytes / 1024 / 1024 / 1024, 2),
                'free_gb' => round($freeBytes / 1024 / 1024 / 1024, 2),
                'used_gb' => round($usedBytes / 1024 / 1024 / 1024, 2),
                'usage_percent' => $usagePercent,
                'status' => $usagePercent > 90 ? 'critical' : ($usagePercent > 80 ? 'warning' : 'ok')
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Quick health check for critical path operations
     */
    public static function quickHealthCheck(): bool
    {
        try {
            // Check database connectivity
            $dbResult = DB::select('SELECT 1');
            
            if ($dbResult === null) {
                return false;
            }
            
            // Check cache functionality
            $cacheTest = ResilientUltraConsolidatedCacheService::safePut('health_quick_' . time(), 'test', 60);
            if (!$cacheTest) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Quick health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get system metrics for monitoring
     */
    public static function getSystemMetrics(): array
    {
        try {
            return [
                'memory' => [
                    'usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'limit_mb' => ini_get('memory_limit')
                ],
                'php' => [
                    'version' => PHP_VERSION,
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size')
                ],
                'cache' => ResilientUltraConsolidatedCacheService::getStatistics(),
                'database' => [
                    'driver' => config('database.default'),
                    'health' => self::checkDatabase()
                ],
                'timestamp' => now()
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'timestamp' => now()
            ];
        }
    }

    /**
     * Check if application is in maintenance mode
     */
    public static function isInMaintenanceMode(): bool
    {
        return file_exists(storage_path('framework/maintenance.php'));
    }

    /**
     * Get application readiness status
     */
    public static function getReadinessStatus(): array
    {
        $health = self::performHealthCheck();
        $quick = self::quickHealthCheck();
        
        return [
            'ready' => $health['overall_status'] === 'healthy' && $quick,
            'health' => $health,
            'quick_check' => $quick,
            'maintenance_mode' => self::isInMaintenanceMode(),
            'timestamp' => now()
        ];
    }
}