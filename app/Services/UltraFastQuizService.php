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
    private const SCAN_MAX_KEYS = 500;

    private $redis = null;
    private bool $redisAvailable = false;
    private $queryCount = 0;

    public function __construct()
    {
        $this->initializeRedisConnection();
    }

    private function initializeRedisConnection(): void
    {
        try {
            $this->redis = Redis::connection();
            $this->redisAvailable = true;
        } catch (\Throwable $e) {
            $this->redis = null;
            $this->redisAvailable = false;

            Log::warning('UltraFastQuizService: Redis unavailable, continuing without Redis cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function canUseRedis(): bool
    {
        return $this->redisAvailable && $this->redis !== null;
    }

    private function deleteFromRedis(array|string $keys): void
    {
        if (!$this->canUseRedis()) {
            return;
        }

        $keys = array_values(array_filter((array) $keys, static fn ($key) => is_string($key) && $key !== ''));

        if ($keys === []) {
            return;
        }

        try {
            if (count($keys) === 1) {
                $this->redis->del($keys[0]);
            } else {
                $this->redis->del($keys);
            }
        } catch (\Throwable $e) {
            Log::warning('UltraFastQuizService: Redis delete failed', [
                'keys' => $keys,
                'error' => $e->getMessage(),
            ]);
        }
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
                $this->deleteFromRedis([$cacheKey, $cacheKey . ':hot']);
            } else {
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


        return $result;
    }

    /**
     * SINGLE OPTIMIZED QUERY: Get all data in one shot
     */
    private function executeOptimizedQuery(Topic $topic, array $sources): Collection
    {
        $this->incrementQueryCounter();

        $query = Question::select([
                'questions.id', 'questions.topic_id', 'questions.question', 'questions.question_type',
                'questions.option_a', 'questions.option_b', 'questions.option_c', 'questions.option_d', 'questions.option_e',
                'questions.correct_answer', 'questions.explanation', 'questions.image_url', 'questions.source',
                'questions.alternative_answers', 'questions.exam_appearances_count', 'questions.extra_topic'
            ])
            ->whereNotNull('questions.question')
            ->where('questions.question', '!=', '')
            ->when(!in_array('all', $sources), function($q) use ($sources) {
                return $q->whereIn('questions.source', $sources);
            });

        // Add topic filtering logic based on exam vs lecture
        $this->applyTopicFilter($query, $topic);

        return $query->orderBy('questions.id')->get();
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
        if (!$this->canUseRedis()) {
            return null;
        }

        try {
            $data = $this->redis->get($key);
            return $data ? json_decode($data, true) : null;
        } catch (\Throwable $e) {
            Log::warning('Redis get failed', ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function storeInRedis(string $key, array $data, int $ttl): void
    {
        if (!$this->canUseRedis()) {
            return;
        }

        try {
            $this->redis->setex($key, $ttl, json_encode($data));
        } catch (\Throwable $e) {
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
            'ultra_fast:%s:topic_%d:format_2:v_%d:sources_%s',
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
        // Force increment cache version specifically for this topic
        $versionKey = "quiz_questions_version:topic_{$topic->id}";
        Cache::add($versionKey, 1, 86400);
        Cache::increment($versionKey);

        // Scan returns prefixed keys if phpredis is used, so we wildcard prefix too
        $pattern = "*ultra_fast:*:topic_{$topic->id}:*";
        $keys = $this->scanMatchingKeys($pattern);

        if (!empty($keys)) {
            // Strip the implicit Redis prefix if present before passing to del() 
            $cleanKeys = array_map(function($key) {
                return preg_replace('/^.*ultra_fast:/', 'ultra_fast:', $key);
            }, $keys);

            $this->deleteFromRedis($cleanKeys);
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
        if (!$this->canUseRedis()) {
            return [];
        }

        $matched = [];
        $cursor = 0;
        $batchSize = max(1, $count);
        $maxKeys = max(1, (int) config('features.scan_invalidation_max_keys', self::SCAN_MAX_KEYS));

        try {
            do {
                $result = $this->redis->scan($cursor, [
                    'match' => $pattern,
                    'count' => $batchSize,
                ]);

                if ($result === false) {
                    break;
                }

                if (is_array($result) && array_key_exists(0, $result) && array_key_exists(1, $result)) {
                    $cursor = (int) $result[0];
                    $keys = $result[1];
                } else {
                    $cursor = 0;
                    $keys = $result;
                }

                foreach ($this->normalizeRedisKeys(is_array($keys) ? $keys : [$keys]) as $key) {
                    $matched[] = $key;

                    if (count($matched) >= $maxKeys) {
                        return array_values(array_unique($matched));
                    }
                }
            } while ((int) $cursor !== 0);
        } catch (\Throwable $e) {
            Log::warning('UltraFastQuizService: Redis key scan failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        return array_values(array_unique($matched));
    }

    private function normalizeRedisKeys(array $keys): array
    {
        $normalized = [];

        array_walk_recursive($keys, function ($value) use (&$normalized) {
            if (!is_scalar($value)) {
                return;
            }

            $key = trim((string) $value);
            if ($key !== '') {
                $normalized[] = $key;
            }
        });

        return $normalized;
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
                $this->deleteFromRedis([$cacheKey, $cacheKey . ':hot']);
            } else {
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
            ');
            
        $this->applyTopicFilter($query, $topic);
        
        $query->whereNotNull('questions.question')
            ->where('questions.question', '!=', '')
            ->when(!in_array('all', $sources), function($query) use ($sources) {
                return $query->whereIn('questions.source', $sources);
            });

        $mainStats = $query->first();

        // Get source breakdown in a separate optimized query
        $this->incrementQueryCounter();
        $sourceQuery = Question::selectRaw('questions.source, COUNT(*) as count');
        
        $this->applyTopicFilter($sourceQuery, $topic);
        
        $sourceStats = $sourceQuery->whereNotNull('questions.question')
            ->where('questions.question', '!=', '')
            ->when(!in_array('all', $sources), function($query) use ($sources) {
                return $query->whereIn('questions.source', $sources);
            })
            ->groupBy('questions.source')
            ->pluck('count', 'questions.source')
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
                $this->deleteFromRedis([$cacheKey, $cacheKey . ':hot']);
            } else {
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
            return $cached;
        }

        // NUCLEAR QUERY: Get distinct sources in one optimized query
        $this->incrementQueryCounter();
        $query = Question::query();
        $this->applyTopicFilter($query, $topic);
        
        $sources = $query->whereNotNull('questions.question')
            ->where('questions.question', '!=', '')
            ->whereNotNull('questions.source')
            ->where('questions.source', '!=', '')
            ->distinct()
            ->pluck('questions.source')
            ->toArray();

        // Always include 'all' as the first option
        $result = array_merge(['all'], array_unique($sources));

        // CACHE: Store in Redis
        $this->storeInRedis($cacheKey, $result, self::CACHE_TTL);


        return $result;
    }

    /**
     * Apply the correct topic filtering logic including linked questions and extra topics
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Topic $topic
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyTopicFilter(\Illuminate\Database\Eloquent\Builder $query, Topic $topic)
    {
        return $query->where(function($q) use ($topic) {
            $q->where('questions.topic_id', $topic->id);
            if (!$topic->is_exam) {
                $q->orWhereRaw(DB::getDriverName() === 'sqlite'
                    ? "INSTR(',' || REPLACE(questions.extra_topic, ' ', '') || ',', ',' || ? || ',') > 0"
                    : 'FIND_IN_SET(?, REPLACE(questions.extra_topic, " ", "")) > 0', [$topic->id]);
            }
        });
    }
}
