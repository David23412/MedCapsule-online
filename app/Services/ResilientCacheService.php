<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class ResilientCacheService
{
    private const MAX_RETRIES = 2;
    private const RETRY_DELAY_MS = 50;
    
    /**
     * Safe cache get with fallback
     */
    public static function safeGet(string $key, $default = null, array $context = [])
    {
        return self::executeWithRetry(function () use ($key) {
            return Cache::get($key);
        }, $default, 'cache_get', array_merge($context, ['key' => $key]));
    }

    /**
     * Safe cache put with error handling
     */
    public static function safePut(string $key, $value, $ttl = null, array $context = []): bool
    {
        $result = self::executeWithRetry(function () use ($key, $value, $ttl) {
            if ($ttl !== null) {
                return Cache::put($key, $value, $ttl);
            }
            return Cache::put($key, $value);
        }, false, 'cache_put', array_merge($context, ['key' => $key]));

        return $result === true;
    }

    /**
     * Safe cache remember with fallback
     */
    public static function safeRemember(string $key, $ttl, callable $callback, $fallback = null, array $context = [])
    {
        return self::executeWithRetry(function () use ($key, $ttl, $callback) {
            return Cache::remember($key, $ttl, $callback);
        }, $fallback, 'cache_remember', array_merge($context, ['key' => $key]));
    }

    /**
     * Safe cache forget
     */
    public static function safeForget(string $key, array $context = []): bool
    {
        $result = self::executeWithRetry(function () use ($key) {
            return Cache::forget($key);
        }, false, 'cache_forget', array_merge($context, ['key' => $key]));

        return $result === true;
    }

    /**
     * Safe cache flush
     */
    public static function safeFlush(array $context = []): bool
    {
        $result = self::executeWithRetry(function () {
            return Cache::flush();
        }, false, 'cache_flush', $context);

        return $result === true;
    }

    /**
     * Batch cache operations with error handling
     */
    public static function safeBatch(array $operations, array $context = []): array
    {
        $results = [];
        $failures = 0;

        foreach ($operations as $operation => $params) {
            try {
                switch ($operation) {
                    case 'put':
                        $results[$params['key']] = self::safePut($params['key'], $params['value'], $params['ttl'] ?? null);
                        break;
                    case 'get':
                        $results[$params['key']] = self::safeGet($params['key'], $params['default'] ?? null);
                        break;
                    case 'forget':
                        $results[$params['key']] = self::safeForget($params['key']);
                        break;
                    default:
                        $results[$operation] = false;
                        $failures++;
                }
            } catch (Exception $e) {
                $results[$operation] = false;
                $failures++;
                
                Log::warning('Batch cache operation failed', [
                    'operation' => $operation,
                    'params' => $params,
                    'error' => $e->getMessage(),
                    'context' => $context
                ]);
            }
        }

        if ($failures > 0) {
            Log::warning('Cache batch operation had failures', [
                'total_operations' => count($operations),
                'failures' => $failures,
                'success_rate' => ((count($operations) - $failures) / count($operations)) * 100,
                'context' => $context
            ]);
        }

        return $results;
    }

    /**
     * Cache with fallback to database
     */
    public static function cacheWithDbFallback(string $key, callable $dbQuery, int $ttl = 3600, $default = null)
    {
        // Try cache first
        $cached = self::safeGet($key);
        if ($cached !== null) {
            return $cached;
        }

        // Try database
        try {
            $result = $dbQuery();
            if ($result !== null && $result !== false) {
                // Cache the result
                self::safePut($key, $result, $ttl);
                return $result;
            }
        } catch (Exception $e) {
            Log::error('Database fallback failed in cache service', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }

        return $default;
    }

    /**
     * Execute cache operation with retry logic
     */
    private static function executeWithRetry(callable $operation, $fallback, string $operationType, array $context = [])
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                return $operation();
            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;
                
                Log::warning('Cache operation failed', [
                    'operation' => $operationType,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'context' => $context
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
                    continue;
                }
            }
        }

        // All retries exhausted
        Log::error('Cache operation failed after all retries', [
            'operation' => $operationType,
            'attempts' => $attempt,
            'final_error' => $lastException->getMessage(),
            'context' => $context
        ]);

        // For certain operations, we should try alternative stores
        if (in_array($operationType, ['cache_get', 'cache_remember'])) {
            $fileResult = self::tryFileSystemFallback($context['key'] ?? 'unknown', $fallback);
            if ($fileResult !== null) {
                return $fileResult;
            }
        }

        return $fallback;
    }

    /**
     * File system fallback for critical cache data
     */
    private static function tryFileSystemFallback(string $key, $fallback)
    {
        try {
            $filename = storage_path('cache_fallback/' . md5($key) . '.json');
            
            if (file_exists($filename)) {
                $data = json_decode(file_get_contents($filename), true);
                
                // Check if not expired (24 hours)
                if (isset($data['timestamp']) && (time() - $data['timestamp']) < 86400) {
                    Log::info('Using file system cache fallback', ['key' => $key]);
                    return $data['value'];
                }
            }
        } catch (Exception $e) {
            Log::warning('File system cache fallback failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }

        return $fallback;
    }

    /**
     * Store critical data in file system as backup
     */
    public static function backupToFileSystem(string $key, $value): void
    {
        try {
            $dir = storage_path('cache_fallback');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = $dir . '/' . md5($key) . '.json';
            $data = [
                'key' => $key,
                'value' => $value,
                'timestamp' => time()
            ];

            file_put_contents($filename, json_encode($data));
        } catch (Exception $e) {
            // Don't log file system backup failures unless debug mode
            if (config('app.debug')) {
                Log::debug('File system backup failed', [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Health check for cache system
     */
    public static function healthCheck(): array
    {
        $testKey = 'health_check_' . time();
        $testValue = 'test_value_' . uniqid();
        
        try {
            $startTime = microtime(true);
            
            // Test write
            $writeSuccess = self::safePut($testKey, $testValue, 60);
            
            // Test read
            $readValue = self::safeGet($testKey);
            
            // Test delete
            $deleteSuccess = self::safeForget($testKey);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            $isHealthy = $writeSuccess && ($readValue === $testValue) && $deleteSuccess;
            
            return [
                'status' => $isHealthy ? 'healthy' : 'degraded',
                'write_success' => $writeSuccess,
                'read_success' => $readValue === $testValue,
                'delete_success' => $deleteSuccess,
                'response_time_ms' => round($duration, 2),
                'timestamp' => now()
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()
            ];
        }
    }

    /**
     * Clean up old file system cache backups
     */
    public static function cleanupFileSystemBackups(): int
    {
        $cleaned = 0;
        
        try {
            $dir = storage_path('cache_fallback');
            if (!is_dir($dir)) {
                return 0;
            }

            $files = glob($dir . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                
                // Remove files older than 7 days
                if (isset($data['timestamp']) && (time() - $data['timestamp']) > 604800) {
                    unlink($file);
                    $cleaned++;
                }
            }
        } catch (Exception $e) {
            Log::warning('Cache cleanup failed', ['error' => $e->getMessage()]);
        }

        return $cleaned;
    }

    /**
     * Get cache statistics
     */
    public static function getStatistics(): array
    {
        try {
            return [
                'driver' => config('cache.default'),
                'health' => self::healthCheck(),
                'file_backups' => count(glob(storage_path('cache_fallback/*.json'))),
                'timestamp' => now()
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'timestamp' => now()
            ];
        }
    }
}