<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use PDOException;

class DatabaseService
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 100;
    private const CIRCUIT_BREAKER_THRESHOLD = 5;
    private const CIRCUIT_BREAKER_TIMEOUT = 300; // 5 minutes

    /**
     * Execute a database query with bulletproof error handling
     */
    public static function safeQuery(callable $query, $fallbackValue = null, array $context = [])
    {
        if (!self::isDatabaseAvailable()) {
            Log::critical('Database circuit breaker is open - refusing query', $context);
            return $fallbackValue;
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                return $query();
            } catch (QueryException $e) {
                $lastException = $e;
                $attempt++;
                
                Log::warning('Database query failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql() ?? 'Unknown',
                    'context' => $context
                ]);

                if (self::isRetriableError($e)) {
                    if ($attempt < self::MAX_RETRIES) {
                        // Non-blocking exponential backoff
                        $backoffDelay = self::RETRY_DELAY_MS * pow(2, $attempt - 1);
                        $startTime = microtime(true);
                        while ((microtime(true) - $startTime) * 1000 < $backoffDelay) {
                            if (function_exists('sched_yield')) {
                                sched_yield();
                            }
                        }
                        continue;
                    }
                } else {
                    // Non-retriable error, break immediately
                    break;
                }
            } catch (PDOException $e) {
                $lastException = $e;
                
                Log::error('PDO connection error', [
                    'error' => $e->getMessage(),
                    'context' => $context
                ]);
                
                self::markDatabaseUnavailable();
                return $fallbackValue;
            } catch (\Exception $e) {
                $lastException = $e;
                
                Log::error('Unexpected database error', [
                    'error' => $e->getMessage(),
                    'context' => $context
                ]);
                
                break;
            }
        }

        // All retries exhausted
        self::incrementFailureCount();
        
        Log::error('Database query failed after all retries', [
            'attempts' => $attempt,
            'final_error' => $lastException->getMessage(),
            'context' => $context
        ]);

        return $fallbackValue;
    }

    /**
     * Execute a database transaction with bulletproof handling
     */
    public static function safeTransaction(callable $transaction, array $context = [])
    {
        if (!self::isDatabaseAvailable()) {
            Log::critical('Database unavailable - refusing transaction', $context);
            return ['success' => false, 'error' => 'Database temporarily unavailable'];
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                DB::beginTransaction();
                
                $result = $transaction();
                
                DB::commit();
                
                return ['success' => true, 'data' => $result];
                
            } catch (QueryException $e) {
                DB::rollBack();
                $lastException = $e;
                $attempt++;
                
                Log::warning('Transaction failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'context' => $context
                ]);

                if (self::isRetriableError($e) && $attempt < self::MAX_RETRIES) {
                    // Non-blocking exponential backoff
                    $backoffDelay = self::RETRY_DELAY_MS * pow(2, $attempt - 1);
                    $startTime = microtime(true);
                    while ((microtime(true) - $startTime) * 1000 < $backoffDelay) {
                        if (function_exists('sched_yield')) {
                            sched_yield();
                        }
                    }
                    continue;
                } else {
                    break;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $lastException = $e;
                
                Log::error('Transaction exception', [
                    'error' => $e->getMessage(),
                    'context' => $context
                ]);
                
                break;
            }
        }

        self::incrementFailureCount();
        
        return [
            'success' => false, 
            'error' => 'Transaction failed: ' . ($lastException->getMessage() ?? 'Unknown error')
        ];
    }

    /**
     * Get count with fallback to cached value
     */
    public static function safeCount(string $table, array $conditions = [], int $fallback = 0): int
    {
        $cacheKey = "safe_count_{$table}_" . md5(serialize($conditions));
        
        $count = self::safeQuery(function () use ($table, $conditions) {
            $query = DB::table($table);
            foreach ($conditions as $field => $value) {
                $query->where($field, $value);
            }
            return $query->count();
        }, null, ['table' => $table, 'conditions' => $conditions]);

        if ($count !== null) {
            // Cache successful result for 5 minutes
            Cache::put($cacheKey, $count, 300);
            return $count;
        }

        // Try to get cached value
        $cachedCount = Cache::get($cacheKey);
        if ($cachedCount !== null) {
            Log::info('Using cached count due to database failure', [
                'table' => $table,
                'cached_value' => $cachedCount
            ]);
            return $cachedCount;
        }

        return $fallback;
    }

    /**
     * Check if database is available (circuit breaker pattern)
     */
    private static function isDatabaseAvailable(): bool
    {
        $circuitBreakerKey = 'db_circuit_breaker';
        $failureCountKey = 'db_failure_count';
        
        $isOpen = Cache::get($circuitBreakerKey, false);
        if ($isOpen) {
            return false;
        }

        $failureCount = Cache::get($failureCountKey, 0);
        if ($failureCount >= self::CIRCUIT_BREAKER_THRESHOLD) {
            self::markDatabaseUnavailable();
            return false;
        }

        return true;
    }

    /**
     * Mark database as unavailable (open circuit breaker)
     */
    private static function markDatabaseUnavailable(): void
    {
        Cache::put('db_circuit_breaker', true, self::CIRCUIT_BREAKER_TIMEOUT);
        
        Log::critical('Database circuit breaker opened - marking database as unavailable', [
            'timeout_seconds' => self::CIRCUIT_BREAKER_TIMEOUT
        ]);
    }

    /**
     * Increment failure count
     */
    private static function incrementFailureCount(): void
    {
        $failureCountKey = 'db_failure_count';
        $currentCount = Cache::get($failureCountKey, 0);
        $newCount = $currentCount + 1;
        
        Cache::put($failureCountKey, $newCount, 3600); // 1 hour TTL
        
        if ($newCount >= self::CIRCUIT_BREAKER_THRESHOLD) {
            self::markDatabaseUnavailable();
        }
    }

    /**
     * Check if error is retriable
     */
    private static function isRetriableError(QueryException $e): bool
    {
        $retriableErrors = [
            'server has gone away',
            'lost connection',
            'deadlock',
            'lock wait timeout',
            'connection refused',
            'too many connections'
        ];

        $errorMessage = strtolower($e->getMessage());
        
        foreach ($retriableErrors as $retriableError) {
            if (str_contains($errorMessage, $retriableError)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Health check for database
     */
    public static function healthCheck(): array
    {
        try {
            $startTime = microtime(true);
            DB::select('SELECT 1');
            $duration = (microtime(true) - $startTime) * 1000;
            
            // Reset failure count on successful health check
            Cache::forget('db_failure_count');
            Cache::forget('db_circuit_breaker');
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($duration, 2),
                'timestamp' => now()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()
            ];
        }
    }

    /**
     * Execute raw SQL with safety measures
     */
    public static function safeRawQuery(string $sql, array $bindings = [], $fallback = null)
    {
        return self::safeQuery(function () use ($sql, $bindings) {
            return DB::select($sql, $bindings);
        }, $fallback, ['sql' => $sql, 'bindings' => $bindings]);
    }

    /**
     * Safe pagination with fallback
     */
    public static function safePaginate(string $table, int $perPage = 15, array $conditions = [], array $fallbackData = [])
    {
        $result = self::safeQuery(function () use ($table, $perPage, $conditions) {
            $query = DB::table($table);
            foreach ($conditions as $field => $value) {
                $query->where($field, $value);
            }
            return $query->paginate($perPage);
        }, null, ['table' => $table, 'per_page' => $perPage]);

        if ($result !== null) {
            return $result;
        }

        // Return fallback pagination structure
        return new \Illuminate\Pagination\LengthAwarePaginator(
            collect($fallbackData),
            count($fallbackData),
            $perPage,
            1,
            ['path' => request()->url()]
        );
    }
}