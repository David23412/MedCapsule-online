<?php

namespace App\Services;

use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Quiz Performance Service - Handles quiz performance tracking and optimization
 */
class QuizPerformanceService
{
    /**
     * Execute a quiz-related operation while recording lightweight timing data.
     *
     * Tracking must never break the request path; callers use this wrapper in
     * quiz and tutor endpoints where a missing metrics backend should not turn
     * into a user-visible failure.
     *
     * @template TReturn
     * @param callable(): TReturn $callback
     * @return TReturn
     */
    public static function trackQuizGeneration(int $topicId, ?int $userId, callable $callback)
    {
        $startedAt = microtime(true);

        try {
            return $callback();
        } finally {
            $durationMs = (microtime(true) - $startedAt) * 1000;

            try {
                app(self::class)->track('quiz_generation_ms', $durationMs);

                Log::debug('Quiz operation completed', [
                    'topic_id' => $topicId,
                    'user_id' => $userId,
                    'duration_ms' => round($durationMs, 2),
                    'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                ]);
            } catch (\Throwable $trackingError) {
                Log::warning('Quiz performance tracking failed', [
                    'topic_id' => $topicId,
                    'user_id' => $userId,
                    'error' => $trackingError->getMessage(),
                ]);
            }
        }
    }

    /**
     * Track a performance metric
     *
     * @param string $metric
     * @param float $value
     * @return void
     */
    public function track(string $metric, float $value): void
    {
        $cacheKey = "performance_metrics:" . date('Y-m-d');
        $metrics = Cache::get($cacheKey, []);

        if (!isset($metrics[$metric])) {
            $metrics[$metric] = [];
        }

        $metrics[$metric][] = [
            'value' => $value,
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];

        // Keep only last 100 entries per metric to prevent cache bloat
        if (count($metrics[$metric]) > 100) {
            $metrics[$metric] = array_slice($metrics[$metric], -100);
        }

        Cache::put($cacheKey, $metrics, 86400); // Cache for 24 hours

    }

    /**
     * Get performance statistics
     *
     * @param string|null $date
     * @return array
     */
    public function getPerformanceStats(?string $date = null): array
    {
        $date = $date ?: date('Y-m-d');
        $cacheKey = "performance_metrics:{$date}";
        $metrics = Cache::get($cacheKey, []);

        $stats = [];
        foreach ($metrics as $metric => $values) {
            $numericValues = array_column($values, 'value');
            $stats[$metric] = [
                'count' => count($values),
                'average' => count($numericValues) > 0 ? array_sum($numericValues) / count($numericValues) : 0,
                'min' => count($numericValues) > 0 ? min($numericValues) : 0,
                'max' => count($numericValues) > 0 ? max($numericValues) : 0,
                'latest' => end($values)
            ];
        }

        return $stats;
    }

    /**
     * Clear performance metrics cache
     *
     * @return void
     */
    public function clearPerformanceCache(): void
    {
        $cacheKey = "performance_metrics:" . date('Y-m-d');
        Cache::forget($cacheKey);

    }

    /**
     * Get system performance health
     *
     * @return array
     */
    public function getSystemHealth(): array
    {
        return [
            'memory_usage' => [
                'current' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'limit' => ini_get('memory_limit')
            ],
            'cache_status' => [
                'redis_connected' => $this->checkRedisConnection(),
                'cache_size' => $this->getCacheSize()
            ],
            'system_load' => [
                'load_average' => sys_getloadavg(),
                'uptime' => $this->getSystemUptime()
            ],
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Check Redis connection status
     *
     * @return bool
     */
    private function checkRedisConnection(): bool
    {
        try {
            Cache::store('redis')->get('health_check');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get approximate cache size
     *
     * @return int
     */
    private function getCacheSize(): int
    {
        try {
            $redis = Cache::store('redis')->getRedis();
            $info = $redis->info('memory');
            return $info['used_memory'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get system uptime
     *
     * @return string
     */
    private function getSystemUptime(): string
    {
        if (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime -p');
            return trim($uptime) ?: 'Unknown';
        }
        return 'Unknown';
    }

    /**
     * Track quiz completion performance
     *
     * @param User $user
     * @param Topic $topic
     * @param Course $course
     * @param float $duration
     * @param int $score
     * @param int $totalQuestions
     * @return void
     */
    public function trackQuizCompletion(User $user, Topic $topic, Course $course, float $duration, int $score, int $totalQuestions): void
    {
        $this->track('quiz_completion_time', $duration);
        $this->track('quiz_score_percentage', ($score / $totalQuestions) * 100);

    }

    /**
     * Get performance alerts
     *
     * @return array
     */
    public function getPerformanceAlerts(): array
    {
        $alerts = [];
        $stats = $this->getPerformanceStats();

        // Check for slow quiz completions
        if (isset($stats['quiz_completion_time']) && $stats['quiz_completion_time']['average'] > 300) { // 5 minutes
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Average quiz completion time is high: ' . round($stats['quiz_completion_time']['average'], 2) . 's',
                'metric' => 'quiz_completion_time'
            ];
        }

        // Check for low quiz scores
        if (isset($stats['quiz_score_percentage']) && $stats['quiz_score_percentage']['average'] < 50) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Average quiz score is low: ' . round($stats['quiz_score_percentage']['average'], 2) . '%',
                'metric' => 'quiz_score_percentage'
            ];
        }

        return $alerts;
    }

    /**
     * Optimize quiz performance based on metrics
     *
     * @return array
     */
    public function optimizePerformance(): array
    {
        $stats = $this->getPerformanceStats();
        $recommendations = [];

        if (isset($stats['quiz_completion_time']) && $stats['quiz_completion_time']['average'] > 180) {
            $recommendations[] = 'Consider reducing question count or implementing pagination for long quizzes';
        }

        if (isset($stats['memory_usage']) && $stats['memory_usage']['average'] > 50 * 1024 * 1024) { // 50MB
            $recommendations[] = 'High memory usage detected. Consider implementing lazy loading for quiz questions';
        }

        return $recommendations;
    }
}
