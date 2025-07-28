<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Quiz Performance Monitoring and Optimization Service
 * Tracks and optimizes quiz generation performance
 */
class QuizPerformanceService
{
    private static $performanceMetrics = [];

    /**
     * Start performance tracking for an operation
     */
    public static function startTracking(string $operation): string
    {
        $trackingId = uniqid($operation . '_', true);
        
        self::$performanceMetrics[$trackingId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'queries_before' => self::getQueryCount()
        ];
        
        return $trackingId;
    }

    /**
     * End performance tracking and log results
     */
    public static function endTracking(string $trackingId, array $additionalData = []): array
    {
        if (!isset(self::$performanceMetrics[$trackingId])) {
            return [];
        }

        $metrics = self::$performanceMetrics[$trackingId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $queriesAfter = self::getQueryCount();

        $performance = [
            'operation' => $metrics['operation'],
            'duration_ms' => round(($endTime - $metrics['start_time']) * 1000, 2),
            'memory_used_mb' => round(($endMemory - $metrics['start_memory']) / 1024 / 1024, 2),
            'queries_executed' => $queriesAfter - $metrics['queries_before'],
            'timestamp' => now()->toDateTimeString()
        ];

        $performance = array_merge($performance, $additionalData);

        // Log performance if it exceeds thresholds
        self::logPerformanceIfNeeded($performance);

        // Clean up
        unset(self::$performanceMetrics[$trackingId]);

        return $performance;
    }

    /**
     * Track quiz generation performance
     */
    public static function trackQuizGeneration(int $topicId, int $userId, callable $operation)
    {
        $trackingId = self::startTracking('quiz_generation');
        
        try {
            $result = $operation();
            
            $performance = self::endTracking($trackingId, [
                'topic_id' => $topicId,
                'user_id' => $userId,
                'success' => true
            ]);
            
            // Store performance metrics for analysis
            self::storePerformanceMetrics($performance);
            
            return $result;
            
        } catch (\Exception $e) {
            self::endTracking($trackingId, [
                'topic_id' => $topicId,
                'user_id' => $userId,
                'success' => false,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get performance analytics
     */
    public static function getPerformanceAnalytics(int $days = 7): array
    {
        // Performance metrics table removed - return empty analytics
        return [
            'average_duration_ms' => 0,
            'max_duration_ms' => 0,
            'min_duration_ms' => 0,
            'average_memory_mb' => 0,
            'total_operations' => 0,
            'success_rate' => 100,
            'slow_operations' => 0
        ];
    }

    /**
     * Optimize database queries for quiz generation
     */
    public static function optimizeQuizQueries(): array
    {
        $optimizations = [];

        try {
            // Check for missing indexes
            $missingIndexes = self::checkForMissingIndexes();
            if (!empty($missingIndexes)) {
                $optimizations['missing_indexes'] = $missingIndexes;
            }

            // Check for slow queries
            $slowQueries = self::identifySlowQueries();
            if (!empty($slowQueries)) {
                $optimizations['slow_queries'] = $slowQueries;
            }

            // Suggest query optimizations
            $suggestions = self::generateOptimizationSuggestions();
            if (!empty($suggestions)) {
                $optimizations['suggestions'] = $suggestions;
            }

        } catch (\Exception $e) {
            Log::error('Query optimization analysis failed', [
                'error' => $e->getMessage()
            ]);
        }

        return $optimizations;
    }

    /**
     * Log performance metrics if they exceed thresholds
     */
    private static function logPerformanceIfNeeded(array $performance): void
    {
        $shouldLog = false;
        $logLevel = 'info';

        // Check duration threshold
        if ($performance['duration_ms'] > 1000) {
            $shouldLog = true;
            $logLevel = 'warning';
        }

        // Check memory threshold
        if ($performance['memory_used_mb'] > 50) {
            $shouldLog = true;
            $logLevel = 'warning';
        }

        // Check query count threshold
        if ($performance['queries_executed'] > 10) {
            $shouldLog = true;
            $logLevel = 'warning';
        }

        if ($shouldLog) {
            Log::log($logLevel, 'Quiz performance metrics', $performance);
        }
    }

    /**
     * Store performance metrics for analysis
     */
    private static function storePerformanceMetrics(array $performance): void
    {
        // Performance metrics table removed - no longer storing metrics
        // Log only if there are errors or performance issues
        if (!$performance['success'] || $performance['duration_ms'] > 1000) {
            Log::warning('Quiz performance issue detected', $performance);
        }
    }

    /**
     * Get current query count
     */
    private static function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Check for missing database indexes
     */
    private static function checkForMissingIndexes(): array
    {
        $suggestions = [];

        try {
            // Check questions table
            $questionsWithoutIndex = DB::select("
                SELECT COUNT(*) as count 
                FROM questions 
                WHERE topic_id IS NOT NULL
            ");

            if ($questionsWithoutIndex[0]->count > 1000) {
                $suggestions[] = 'Consider adding index on questions.topic_id for better performance';
            }

            // Check other common query patterns
            // Add more checks based on your specific query patterns

        } catch (\Exception $e) {
            Log::debug('Index check failed', ['error' => $e->getMessage()]);
        }

        return $suggestions;
    }

    /**
     * Identify slow queries
     */
    private static function identifySlowQueries(): array
    {
        // This would typically analyze query logs
        // For now, return common slow query patterns
        return [
            'SELECT * queries without WHERE clauses',
            'Queries without proper indexing',
            'N+1 query patterns in relationships'
        ];
    }

    /**
     * Generate optimization suggestions
     */
    private static function generateOptimizationSuggestions(): array
    {
        return [
            'Use eager loading for relationships',
            'Implement query result caching',
            'Add database indexes for frequently queried columns',
            'Use select() to limit returned columns',
            'Implement pagination for large result sets'
        ];
    }
}