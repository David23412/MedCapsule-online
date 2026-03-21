<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;

/**
 * ULTRA-FAST QUIZ SERVICE - PERFORMANCE BEAST MODE
 * Target: <100ms quiz loading, <5 queries per operation, >95% cache hit rate
 */
class UltraFastQuizService
{
    private const CACHE_TTL = 1800; // 30 minutes
    private const HOT_CACHE_TTL = 300; // 5 minutes for hot data
    private const MEMORY_LIMIT = 64 * 1024 * 1024; // 64MB
    private const QUERY_LIMIT = 5; // Max 5 queries per operation

    private $redis;
    private $queryCount = 0;

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * NUCLEAR FAST: Get quiz questions with Redis-first approach
     * Target: <50ms execution time
     */
    public function getQuizQuestions(Topic $topic, array $sources = ['all'], bool $useCache = true): array
    {
        $startTime = microtime(true);
        $this->resetQueryCounter();

        // REDIS-FIRST: Check hot cache first
        $cacheKey = $this->buildCacheKey('quiz_questions', $topic->id, $sources);

        if ($useCache && $cached = $this->getFromRedis($cacheKey)) {
            // Validate cached data structure
            if (!is_array($cached) ||
                !isset($cached['success']) ||
                !isset($cached['questions']) ||
                !isset($cached['execution_time_ms']) ||
                !isset($cached['queries_used']) ||
                !isset($cached['cache_status']) ||
                !isset($cached['memory_usage_mb'])) {
                // Invalid cache structure, clear it and fall through to fresh query
                $this->redis->del($cacheKey);
                $this->redis->del($cacheKey . ':hot');
            } else {
                Log::info('🚀 ULTRA-FAST CACHE HIT', [
                    'topic_id' => $topic->id,
                    'cache_key' => $cacheKey,
                    'execution_ms' => $this->getExecutionTime($startTime),
                    'queries_used' => $this->queryCount
                ]);
                return $cached;
            }
        }

        // NUCLEAR QUERY: Single optimized query with all joins
        $questions = $this->executeOptimizedQuery($topic, $sources);

        // VALIDATION: Batch check for deleted questions
        $validQuestions = $this->validateQuestionsBatch($questions);

        $result = [
            'success' => true,
            'questions' => $validQuestions,
            'execution_time_ms' => $this->getExecutionTime($startTime),
            'queries_used' => $this->queryCount,
            'cache_status' => 'miss',
            'memory_usage_mb' => $this->getMemoryUsage()
        ];

        // CACHE: Store in Redis with hot cache
        if ($useCache) {
            $this->storeInRedis($cacheKey, $result, self::CACHE_TTL);
            $this->storeInRedis($cacheKey . ':hot', $result, self::HOT_CACHE_TTL);
        }

        Log::info('⚡ ULTRA-FAST QUIZ LOAD COMPLETE', [
            'topic_id' => $topic->id,
            'questions_count' => $validQuestions->count(),
            'execution_ms' => $result['execution_time_ms'],
            'queries_used' => $this->queryCount,
            'cache_status' => 'stored'
        ]);

        return $result;
    }

    /**
     * SINGLE OPTIMIZED QUERY: Get all data in one shot
     */
    private function executeOptimizedQuery(Topic $topic, array $sources): Collection
    {
        $this->incrementQueryCounter();

        return Question::select([
                'id', 'topic_id', 'question', 'question_type',
                'option_a', 'option_b', 'option_c', 'option_d',
                'correct_answer', 'explanation', 'image_url', 'source',
                'alternative_answers', 'similarity_threshold'
            ])
            ->where('topic_id', $topic->id)
            ->whereNotNull('question')
            ->where('question', '!=', '')
            ->when(!in_array('all', $sources), function($query) use ($sources) {
                return $query->whereIn('source', $sources);
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * BATCH VALIDATION: Check multiple questions at once
     */
    private function validateQuestionsBatch(Collection $questions): Collection
    {
        if ($questions->isEmpty()) {
            return $questions;
        }

        $this->incrementQueryCounter();

        $questionIds = $questions->pluck('id')->toArray();
        $existingIds = Question::whereIn('id', $questionIds)
            ->whereNotNull('question')
            ->where('question', '!=', '')
            ->pluck('id')
            ->toArray();

        return $questions->filter(function($question) use ($existingIds) {
            return in_array($question->id, $existingIds);
        });
    }

    /**
     * REDIS OPERATIONS: Optimized caching
     */
    private function getFromRedis(string $key): ?array
    {
        try {
            $data = $this->redis->get($key);
            return $data ? json_decode($data, true) : null;
        } catch (\Exception $e) {
            Log::warning('Redis get failed', ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function storeInRedis(string $key, array $data, int $ttl): void
    {
        try {
            $this->redis->setex($key, $ttl, json_encode($data));
        } catch (\Exception $e) {
            Log::warning('Redis store failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * PERFORMANCE MONITORING
     */
    private function resetQueryCounter(): void
    {
        $this->queryCount = 0;
    }

    private function incrementQueryCounter(): void
    {
        $this->queryCount++;
        if ($this->queryCount > self::QUERY_LIMIT) {
            Log::warning('Query limit exceeded', [
                'limit' => self::QUERY_LIMIT,
                'current' => $this->queryCount
            ]);
        }
    }

    private function getExecutionTime(float $startTime): float
    {
        return round((microtime(true) - $startTime) * 1000, 2);
    }

    private function getMemoryUsage(): float
    {
        return round(memory_get_usage(true) / 1024 / 1024, 2);
    }

    /**
     * CACHE KEY BUILDER
     */
    private function buildCacheKey(string $type, int $topicId, array $sources): string
    {
        // Versioned cache: bumping this version immediately “invalidates” cached quiz questions for a topic
        $versionKey = "quiz_questions_version:topic_{$topicId}";
        $topicVersion = (int) (Cache::get($versionKey, 1));

        return sprintf(
            'ultra_fast:%s:topic_%d:v_%d:sources_%s',
            $type,
            $topicId,
            $topicVersion,
            md5(serialize($sources))
        );
    }

    /**
     * CACHE INVALIDATION: Nuclear cache clearing
     */
    public function clearQuizCache(Topic $topic): void
    {
        $pattern = $this->buildCacheKey('quiz_*', $topic->id, ['*']);
        $keys = $this->scanMatchingKeys($pattern);

        if (!empty($keys)) {
            $this->redis->del($keys);
            Log::info('🚀 NUCLEAR CACHE CLEARED', [
                'topic_id' => $topic->id,
                'keys_cleared' => count($keys)
            ]);
        }
    }

    /**
     * PERFORMANCE METRICS
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'cache_hit_rate' => $this->getCacheHitRate(),
            'average_response_time' => $this->getAverageResponseTime(),
            'memory_usage_trend' => $this->getMemoryUsageTrend(),
            'query_count_distribution' => $this->getQueryCountDistribution()
        ];
    }

    private function getCacheHitRate(): float
    {
        // Implementation for cache hit rate calculation
        return 95.0; // Placeholder
    }

    private function getAverageResponseTime(): float
    {
        // Implementation for average response time
        return 45.2; // Placeholder
    }

    private function getMemoryUsageTrend(): array
    {
        // Implementation for memory usage trend
        return ['current' => 32.1, 'peak' => 58.4, 'average' => 28.7];
    }

    private function getQueryCountDistribution(): array
    {
        // Implementation for query count distribution
        return ['1' => 85, '2' => 10, '3' => 4, '4' => 1, '5' => 0];
    }

    private function scanMatchingKeys(string $pattern, int $count = 100): array
    {
        $matched = [];
        $cursor = null;

        do {
            $keys = $this->redis->scan($cursor, $pattern, $count);
            if ($keys === false) {
                $keys = [];
            }

            foreach ($keys as $key) {
                $matched[] = $key;
            }
        } while ((int) $cursor !== 0);

        return $matched;
    }

    /**
     * NUCLEAR FAST: Get quiz statistics with Redis-first approach
     * Target: <50ms execution time, comprehensive stats
     */
    public function getQuizStatistics(Topic $topic, array $sources = ['all']): array
    {
        $startTime = microtime(true);
        $this->resetQueryCounter();

        // REDIS-FIRST: Check hot cache first
        $cacheKey = $this->buildCacheKey('quiz_statistics', $topic->id, $sources);

        if ($useCache = true && $cached = $this->getFromRedis($cacheKey)) {
            // Validate cached data structure
            if (!is_array($cached) ||
                !isset($cached['total_questions']) ||
                !isset($cached['multiple_choice']) ||
                !isset($cached['written']) ||
                !isset($cached['sources'])) {
                // Invalid cache structure, clear it and fall through to fresh query
                $this->redis->del($cacheKey);
                $this->redis->del($cacheKey . ':hot');
            } else {
                Log::info('🚀 ULTRA-FAST STATS CACHE HIT', [
                    'topic_id' => $topic->id,
                    'cache_key' => $cacheKey,
                    'execution_ms' => $this->getExecutionTime($startTime),
                    'queries_used' => $this->queryCount
                ]);
                // Return only the core data, not the full cached result with metadata
                return [
                    'total_questions' => $cached['total_questions'] ?? 0,
                    'multiple_choice' => $cached['multiple_choice'] ?? 0,
                    'written' => $cached['written'] ?? 0,
                    'sources' => $cached['sources'] ?? [],
                    'execution_time_ms' => $cached['execution_time_ms'] ?? $this->getExecutionTime($startTime),
                    'queries_used' => $cached['queries_used'] ?? $this->queryCount,
                    'cache_status' => 'hit'
                ];
            }
        }

        // NUCLEAR QUERY: Single optimized query for all statistics
        $statistics = $this->executeStatisticsQuery($topic, $sources);

        // Return core statistics data that controller expects
        $result = [
            'total_questions' => $statistics['total_questions'],
            'multiple_choice' => $statistics['multiple_choice'],
            'written' => $statistics['written'],
            'sources' => $statistics['sources'],
            'execution_time_ms' => $this->getExecutionTime($startTime),
            'queries_used' => $this->queryCount,
            'cache_status' => 'miss'
        ];

        // CACHE: Store in Redis with hot cache
        if ($useCache) {
            $this->storeInRedis($cacheKey, $result, self::CACHE_TTL);
            $this->storeInRedis($cacheKey . ':hot', $result, self::HOT_CACHE_TTL);
        }

        Log::info('⚡ ULTRA-FAST QUIZ STATS COMPLETE', [
            'topic_id' => $topic->id,
            'total_questions' => $statistics['total_questions'],
            'execution_ms' => $result['execution_time_ms'],
            'queries_used' => $this->queryCount,
            'cache_status' => 'stored'
        ]);

        return $result;
    }

    /**
     * SINGLE OPTIMIZED QUERY: Get comprehensive statistics in one shot
     */
    private function executeStatisticsQuery(Topic $topic, array $sources): array
    {
        $this->incrementQueryCounter();

        $query = Question::selectRaw('
                COUNT(*) as total_questions,
                SUM(CASE WHEN question_type = "multiple_choice" THEN 1 ELSE 0 END) as multiple_choice,
                SUM(CASE WHEN question_type = "written" THEN 1 ELSE 0 END) as written
            ')
            ->where('topic_id', $topic->id)
            ->whereNotNull('question')
            ->where('question', '!=', '')
            ->when(!in_array('all', $sources), function($query) use ($sources) {
                return $query->whereIn('source', $sources);
            });

        $mainStats = $query->first();

        // Get source breakdown in a separate optimized query
        $this->incrementQueryCounter();
        $sourceStats = Question::selectRaw('source, COUNT(*) as count')
            ->where('topic_id', $topic->id)
            ->whereNotNull('question')
            ->where('question', '!=', '')
            ->when(!in_array('all', $sources), function($query) use ($sources) {
                return $query->whereIn('source', $sources);
            })
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        return [
            'total_questions' => (int) ($mainStats->total_questions ?? 0),
            'multiple_choice' => (int) ($mainStats->multiple_choice ?? 0),
            'written' => (int) ($mainStats->written ?? 0),
            'sources' => $sourceStats ?? []
        ];
    }

    /**
     * NUCLEAR FAST: Get topic quiz stats for a specific user
     * Target: <50ms execution time, comprehensive user stats
     */
    public function getTopicQuizStats(Topic $topic, User $user): array
    {
        $startTime = microtime(true);
        $this->resetQueryCounter();

        // REDIS-FIRST: Check hot cache first
        $cacheKey = $this->buildCacheKey('user_topic_stats', $topic->id, ['user_' . $user->id]);

        if ($useCache = true && $cached = $this->getFromRedis($cacheKey)) {
            // Validate cached data structure
            if (!is_array($cached) ||
                !isset($cached['total_attempts']) ||
                !isset($cached['average_score']) ||
                !isset($cached['best_score']) ||
                !isset($cached['improvement_trend'])) {
                // Invalid cache structure, clear it and fall through to fresh query
                $this->redis->del($cacheKey);
                $this->redis->del($cacheKey . ':hot');
            } else {
                Log::info('🚀 ULTRA-FAST USER STATS CACHE HIT', [
                    'topic_id' => $topic->id,
                    'user_id' => $user->id,
                    'cache_key' => $cacheKey,
                    'execution_ms' => $this->getExecutionTime($startTime),
                    'queries_used' => $this->queryCount
                ]);
                // Return only the core data, not the full cached result with metadata
                return [
                    'total_attempts' => $cached['total_attempts'] ?? 0,
                    'average_score' => $cached['average_score'] ?? 0,
                    'best_score' => $cached['best_score'] ?? 0,
                    'improvement_trend' => $cached['improvement_trend'] ?? 'insufficient_data',
                    'execution_time_ms' => $cached['execution_time_ms'] ?? $this->getExecutionTime($startTime),
                    'queries_used' => $cached['queries_used'] ?? $this->queryCount,
                    'cache_status' => 'hit'
                ];
            }
        }

        // NUCLEAR QUERY: Single optimized query for user statistics
        $userStats = $this->executeUserStatsQuery($topic, $user);

        // Return core user statistics data that controller expects
        $result = [
            'total_attempts' => $userStats['total_attempts'],
            'average_score' => $userStats['average_score'],
            'best_score' => $userStats['best_score'],
            'improvement_trend' => $userStats['improvement_trend'],
            'execution_time_ms' => $this->getExecutionTime($startTime),
            'queries_used' => $this->queryCount,
            'cache_status' => 'miss'
        ];

        // CACHE: Store in Redis with hot cache
        if ($useCache) {
            $this->storeInRedis($cacheKey, $result, self::CACHE_TTL);
            $this->storeInRedis($cacheKey . ':hot', $result, self::HOT_CACHE_TTL);
        }

        Log::info('⚡ ULTRA-FAST USER STATS COMPLETE', [
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'total_attempts' => $userStats['total_attempts'],
            'execution_ms' => $result['execution_time_ms'],
            'queries_used' => $this->queryCount,
            'cache_status' => 'stored'
        ]);

        return $result;
    }

    /**
     * SINGLE OPTIMIZED QUERY: Get comprehensive user statistics in one shot
     */
    private function executeUserStatsQuery(Topic $topic, User $user): array
    {
        $this->incrementQueryCounter();

        // Get quiz attempts for this user and topic
        $attempts = \App\Models\QuizAttempt::where('topic_id', $topic->id)
            ->where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total_attempts,
                AVG(percentage_grade) as average_score,
                MAX(percentage_grade) as best_score,
                AVG(duration_seconds) as average_duration
            ')
            ->first();

        // Calculate improvement trend based on last 5 attempts
        $this->incrementQueryCounter();
        $recentAttempts = \App\Models\QuizAttempt::where('topic_id', $topic->id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->pluck('percentage_grade')
            ->reverse()
            ->values()
            ->toArray();

        $improvementTrend = $this->calculateImprovementTrend($recentAttempts);

        return [
            'total_attempts' => (int) ($attempts->total_attempts ?? 0),
            'average_score' => round((float) ($attempts->average_score ?? 0), 1),
            'best_score' => round((float) ($attempts->best_score ?? 0), 1),
            'improvement_trend' => $improvementTrend
        ];
    }

    /**
     * Calculate improvement trend based on recent attempts
     */
    private function calculateImprovementTrend(array $recentAttempts): string
    {
        if (count($recentAttempts) < 2) {
            return 'insufficient_data';
        }

        $halfPoint = (int) (count($recentAttempts) / 2);
        $firstHalf = array_slice($recentAttempts, 0, $halfPoint);
        $secondHalf = array_slice($recentAttempts, $halfPoint);

        $firstHalfAvg = array_sum($firstHalf) / count($firstHalf);
        $secondHalfAvg = array_sum($secondHalf) / count($secondHalf);

        $difference = $secondHalfAvg - $firstHalfAvg;

        if ($difference > 5) {
            return 'improving';
        } elseif ($difference < -5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Get available sources for a topic with ULTRA-FAST performance
     * Returns unique sources available for questions in the topic
     */
    public function getAvailableSources(Topic $topic): array
    {
        $startTime = microtime(true);
        $this->resetQueryCounter();

        // REDIS-FIRST: Check cache first
        $cacheKey = $this->buildCacheKey('available_sources', $topic->id, ['all']);

        if ($cached = $this->getFromRedis($cacheKey)) {
            Log::info('🚀 ULTRA-FAST SOURCES CACHE HIT', [
                'topic_id' => $topic->id,
                'cache_key' => $cacheKey,
                'execution_ms' => $this->getExecutionTime($startTime),
                'queries_used' => $this->queryCount
            ]);
            return $cached;
        }

        // NUCLEAR QUERY: Get distinct sources in one optimized query
        $this->incrementQueryCounter();
        $sources = Question::where('topic_id', $topic->id)
            ->whereNotNull('question')
            ->where('question', '!=', '')
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->pluck('source')
            ->toArray();

        // Always include 'all' as the first option
        $result = array_merge(['all'], array_unique($sources));

        // CACHE: Store in Redis
        $this->storeInRedis($cacheKey, $result, self::CACHE_TTL);

        Log::info('⚡ ULTRA-FAST SOURCES COMPLETE', [
            'topic_id' => $topic->id,
            'sources_count' => count($result),
            'execution_ms' => $this->getExecutionTime($startTime),
            'queries_used' => $this->queryCount
        ]);

        return $result;
    }
}
