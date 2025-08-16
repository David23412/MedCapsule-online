<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Quiz Performance Service
 * Handles performance tracking and monitoring for quiz operations
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
            'queries_executed' => $queriesAfter - $metrics['queries_before']
        ];

        // Log performance data
        Log::info('Quiz Performance Metrics', array_merge($performance, $additionalData));

        // Clean up
        unset(self::$performanceMetrics[$trackingId]);

        return $performance;
    }

    /**
     * Track quiz generation with performance monitoring
     */
    public static function trackQuizGeneration(int $topicId, int $userId, callable $callback)
    {
        $trackingId = self::startTracking('quiz_generation');
        
        try {
            $result = $callback();
            
            self::endTracking($trackingId, [
                'topic_id' => $topicId,
                'user_id' => $userId,
                'success' => true
            ]);
            
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
     * Monitor a specific operation
     */
    public static function monitor(string $operation): void
    {
        Log::info('Quiz Performance Monitor', [
            'operation' => $operation,
            'timestamp' => now(),
            'memory_usage' => memory_get_usage(true)
        ]);
    }

    /**
     * Track operation with timing
     */
    public static function track(string $operation, ?float $duration = null): void
    {
        $data = [
            'operation' => $operation,
            'timestamp' => now(),
            'memory_usage' => memory_get_usage(true)
        ];
        
        if ($duration !== null) {
            $data['duration_ms'] = round($duration * 1000, 2);
        }
        
        Log::info('Quiz Performance Track', $data);
    }

    /**
     * Get performance analytics
     */
    public static function getPerformanceAnalytics(int $limit = 100): array
    {
        // This would typically query a performance metrics table
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Optimize quiz queries based on performance data
     */
    public static function optimizeQuizQueries(): array
    {
        // This would analyze performance data and suggest optimizations
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Get current query count
     */
    private static function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }
}
