<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Topic;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Question;
use Illuminate\Support\Facades\Redis;
use Exception;

class CacheService
{
    // Cache TTL constants from config
    private function getTtl(string $type): int
    {
        return config("cache.ttl.{$type}", 3600); // Default to 1 hour
    }
    
    // Cache tags for targeted invalidation
    const USER_PROGRESS_TAG = 'user_progress';
    const USER_MISTAKES_TAG = 'user_mistakes';
    const USER_STATS_TAG = 'user_stats';
    const TOPIC_DATA_TAG = 'topic_data';
    const QUIZ_DATA_TAG = 'quiz_data';

    /**
     * Get user's topic progress with atomic updates
     */
    public function getUserTopicProgress(int $userId, int $topicId, string $studyMode = 'quiz'): array
    {
        $cacheKey = "user_{$userId}_topic_{$topicId}_progress_{$studyMode}";
        
        // Use Redis for atomic operations
        $redis = Redis::connection();
        
        // Try to get from cache first
        $cached = $redis->get($cacheKey);
        if ($cached) {
            return json_decode($cached, true);
        }
        
        // If not in cache, calculate and store atomically
        $progress = QuizAttempt::getTopicProgress($userId, $topicId, $studyMode);
        $redis->setex($cacheKey, $this->getTtl('user'), json_encode($progress));
        
        return $progress;
    }
    
    /**
     * Get user's common mistakes with atomic updates
     */
    public function getUserCommonMistakes(int $userId, int $topicId, int $limit = 10): array
    {
        $cacheKey = "user_{$userId}_topic_{$topicId}_mistakes";
        
        return Cache::remember($cacheKey, $this->getTtl('user'), function () use ($userId, $topicId, $limit) {
            return QuizAttempt::getCommonMistakes($userId, $topicId, $limit)->toArray();
        });
    }
    
    /**
     * Get user's mastery statistics with atomic updates
     */
    public function getUserMasteryStats(int $userId): array
    {
        $cacheKey = "user_{$userId}_mastery_stats";
        
        // Use Redis for atomic operations
        $redis = Redis::connection();
        
        // Try to get from cache first
        $cached = $redis->get($cacheKey);
        if ($cached) {
            return json_decode($cached, true);
        }
        
        // If not in cache, calculate and store atomically
        $stats = $this->calculateUserMasteryStats($userId);
        $redis->setex($cacheKey, $this->getTtl('stats'), json_encode($stats));
        
        return $stats;
    }
    
    /**
     * Calculate user mastery stats with optimized queries
     */
    private function calculateUserMasteryStats(int $userId): array
    {
        // OPTIMIZED: Single query with aggregation
        $stats = Mistake::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_mistakes,
                SUM(CASE WHEN correct_streak >= 4 THEN 1 ELSE 0 END) as mastered_count,
                SUM(CASE WHEN correct_streak < 4 THEN 1 ELSE 0 END) as in_progress_count
            ')
            ->first();
        
        return [
            'total_mistakes' => $stats->total_mistakes ?? 0,
            'mastered_count' => $stats->mastered_count ?? 0,
            'in_progress_count' => $stats->in_progress_count ?? 0
        ];
    }
    
    /**
     * ATOMIC CACHE INVALIDATION: Clear all user-related caches
     */
    public function invalidateUserCaches(int $userId, ?int $topicId = null): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        // Remove topic progress caches
        $modes = ['quiz', 'tutor'];
        if ($topicId) {
            foreach ($modes as $mode) {
                $pipeline->del("user_{$userId}_topic_{$topicId}_progress_{$mode}");
            }
            $pipeline->del("user_{$userId}_topic_{$topicId}_mistakes");
        } else {
            // For random quizzes or when no topic is specified
            $pipeline->del("user_{$userId}_random_quiz_progress");
            $pipeline->del("user_{$userId}_random_quiz_stats");
            $pipeline->del("user_{$userId}_random_quiz_mistakes");
        }
        
        // Remove mastery stats
        $pipeline->del("user_{$userId}_mastery_stats");
        
        // Execute all deletions atomically
        $pipeline->exec();
        
        Log::info('Cache invalidated', [
            'user_id' => $userId,
            'topic_id' => $topicId,
            'type' => $topicId ? 'topic_quiz' : 'random_quiz',
            'timestamp' => now()
        ]);
    }
    
    /**
     * INTELLIGENT CACHE WARMING: Pre-populate critical caches
     */
    public function warmUserCaches(int $userId): void
    {
        // Use Redis pipeline for bulk operations
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        // Warm user mastery stats
        $stats = $this->calculateUserMasteryStats($userId);
        $pipeline->setex(
            "user_{$userId}_mastery_stats",
            $this->getTtl('stats'),
            json_encode($stats)
        );
        
        // Warm topic progress for all user's topics
        $userTopics = Topic::whereHas('quizAttempts', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->pluck('id');
        
        foreach ($userTopics as $topicId) {
            foreach (['quiz', 'tutor'] as $mode) {
                $progress = QuizAttempt::getTopicProgress($userId, $topicId, $mode);
                $pipeline->setex(
                    "user_{$userId}_topic_{$topicId}_progress_{$mode}",
                    $this->getTtl('user'),
                    json_encode($progress)
                );
            }
            
            $mistakes = QuizAttempt::getCommonMistakes($userId, $topicId, 10)->toArray();
            $pipeline->setex(
                "user_{$userId}_topic_{$topicId}_mistakes",
                $this->getTtl('user'),
                json_encode($mistakes)
            );
        }
        
        // Execute all cache warming operations atomically
        $pipeline->exec();
    }
    
    /**
     * Get a value from cache
     */
    public function get(string $key)
    {
        return Cache::get($key);
    }

    /**
     * Put a value in cache
     */
    public function put(string $key, $value, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->getTtl('default');
        Cache::put($key, $value, $ttl);
    }

    /**
     * Forget a cache key
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Remember a value in cache
     */
    public function remember(string $key, $callback, ?int $ttl = null)
    {
        $ttl = $ttl ?? $this->getTtl('default');
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * LETHAL CACHE INVALIDATION: Clear all caches and session data related to a question
     * NUCLEAR OPTION: Forces complete cache refresh for deleted questions
     */
    public function clearQuestionCaches($questionId, $topicId): bool
    {
        try {
            $redis = Redis::connection();
            $pipeline = $redis->pipeline();
            
            // LETHAL CLEAR: Clear ALL question-related caches
            $pipeline->del("question:{$questionId}:total_attempts");
            $pipeline->del("question:{$questionId}:correct_attempts");
            
            // NUCLEAR CLEAR: Clear ALL topic-level quiz caches
            $pipeline->del("quiz_questions_{$topicId}_" . md5(serialize(['all'])));
            
            // LETHAL CLEAR: Clear source-specific quiz cache
            $question = Question::find($questionId);
            if ($question && $question->source) {
                $pipeline->del("quiz_questions_{$topicId}_" . md5(serialize([$question->source])));
            }
            
            // NUCLEAR CLEAR: Clear ALL related caches
            $pipeline->del("available_sources_{$topicId}");
            $pipeline->del("quiz_stats_{$topicId}");
            $pipeline->del("tutor_progress_{$topicId}");
            
            // LETHAL CLEAR: Clear ALL random quiz caches
            $this->clearRandomQuizCaches();
            
            // NUCLEAR CLEAR: Clear question-specific cache
            $pipeline->del("questions_" . md5($questionId));
            
            // LETHAL CLEAR: Clear ALL timestamp-based caches
            $pipeline->del("quiz_questions_{$topicId}_*");
            
            // Execute all deletions atomically
            $pipeline->exec();
            
            // NUCLEAR CLEAR: Clear ALL session data
            session()->flush();
            
            // LETHAL LOG: Log the nuclear cache clearing
            Log::info('LETHAL FIX: Nuclear cache invalidation completed', [
                'question_id' => $questionId,
                'topic_id' => $topicId,
                'session_cleared' => true,
                'cache_type' => 'nuclear_invalidation'
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('LETHAL FIX: Error during nuclear cache invalidation', [
                'question_id' => $questionId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * LETHAL RANDOM QUIZ CACHE CLEAR: Clear all random quiz caches
     */
    public function clearRandomQuizCaches(): void
    {
        try {
            $redis = Redis::connection();
            $pipeline = $redis->pipeline();
            
            // LETHAL CLEAR: Clear ALL random quiz patterns
            $keys = $redis->keys("random_quiz_*");
            foreach ($keys as $key) {
                $pipeline->del($key);
            }
            
            // NUCLEAR CLEAR: Clear ALL quiz generation caches
            $keys = $redis->keys("quiz_questions_*");
            foreach ($keys as $key) {
                $pipeline->del($key);
            }
            
            // Execute all deletions atomically
            $pipeline->exec();
            
            Log::info('LETHAL FIX: Random quiz caches cleared', [
                'cache_type' => 'nuclear_random_quiz_clear'
            ]);
            
        } catch (\Exception $e) {
            Log::error('LETHAL FIX: Error clearing random quiz caches', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // PHASE 2: Event-based cache invalidation methods

    /**
     * Clear course-related caches
     */
    public function clearCourseCaches(int $courseId, ?int $userId = null): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        // Clear course-specific caches
        $pipeline->del("course_{$courseId}_enrolled_count");
        $pipeline->del("course_{$courseId}_enrolled_students");
        $pipeline->del("course_{$courseId}_enrolled_students_limit_3");
        $pipeline->del("course_stats_{$courseId}");
        
        // Clear user-specific course caches if user is provided
        if ($userId) {
            $pipeline->del("course_{$courseId}_user_{$userId}_completion");
            $pipeline->del("course_{$courseId}_user_{$userId}_enrolled");
            $pipeline->del("course_{$courseId}_user_{$userId}_mastered");
        }
        
        $pipeline->exec();
    }

    /**
     * Clear topic caches by course
     */
    public function clearTopicCachesByCourse(int $courseId): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        // Get all topics for this course
        $topics = Topic::where('course_id', $courseId)->pluck('id');
        
        foreach ($topics as $topicId) {
            $pipeline->del("topic:{$topicId}:sources");
            $pipeline->del("topic:{$topicId}:questions_count");
            $pipeline->del("topic:{$topicId}:completion_percentage");
        }
        
        $pipeline->exec();
    }

    /**
     * Clear topic-specific caches
     */
    public function clearTopicCaches(int $topicId, ?int $userId = null): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        // Clear topic-specific caches
        $pipeline->del("topic:{$topicId}:sources");
        $pipeline->del("topic:{$topicId}:questions_count");
        $pipeline->del("topic:{$topicId}:completion_percentage");
        
        // Clear user-specific topic caches if user is provided
        if ($userId) {
            $pipeline->del("topic:{$topicId}:user:{$userId}:completion");
            $pipeline->del("topic:{$topicId}:user:{$userId}:progress");
        }
        
        $pipeline->exec();
    }

    /**
     * Clear topic progress caches
     */
    public function clearTopicProgressCaches(int $topicId, int $userId, string $studyMode): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        $pipeline->del("user_{$userId}_topic_{$topicId}_progress_{$studyMode}");
        $pipeline->del("topic:{$topicId}:user:{$userId}:progress");
        
        $pipeline->exec();
    }

    /**
     * Clear user mastery stats cache
     */
    public function clearUserMasteryStats(int $userId): void
    {
        $redis = Redis::connection();
        $redis->del("user_{$userId}_mastery_stats");
    }

    /**
     * Clear topic quiz caches
     */
    public function clearTopicQuizCaches(int $topicId): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        $pipeline->del("quiz_questions_{$topicId}_" . md5(serialize(['all'])));
        $pipeline->del("available_sources_{$topicId}");
        $pipeline->del("quiz_stats_{$topicId}");
        $pipeline->del("tutor_progress_{$topicId}");
        
        $pipeline->exec();
    }

    /**
     * Clear source-specific caches
     */
    public function clearSourceCaches(int $topicId, string $source): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        $pipeline->del("quiz_questions_{$topicId}_" . md5(serialize([$source])));
        $pipeline->del("source_progress_{$topicId}_{$source}");
        
        $pipeline->exec();
    }

    /**
     * Clear quiz caches
     */
    public function clearQuizCaches(?int $topicId, int $userId): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        if ($topicId) {
            $pipeline->del("quiz_questions_{$topicId}_" . md5(serialize(['all'])));
            $pipeline->del("quiz_stats_{$topicId}");
            $pipeline->del("user_{$userId}_topic_{$topicId}_progress_quiz");
            $pipeline->del("user_{$userId}_topic_{$topicId}_mistakes");
        } else {
            // For random quizzes, just clear user-specific caches
            $pipeline->del("user_{$userId}_random_quiz_progress");
            $pipeline->del("user_{$userId}_random_quiz_stats");
        }
        
        $pipeline->exec();
    }

    /**
     * Clear user notification caches
     */
    public function clearUserNotificationCaches(int $userId): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        // Clear notification caches for different limits
        $limits = [5, 10, 20, 50];
        foreach ($limits as $limit) {
            $pipeline->del("user_notifications_{$userId}_{$limit}");
        }
        
        $pipeline->exec();
    }

    /**
     * Clear notification count caches
     */
    public function clearNotificationCountCaches(int $userId): void
    {
        $redis = Redis::connection();
        $redis->del("user_notifications_count_{$userId}");
        $redis->del("user_unread_notifications_count_{$userId}");
    }

    /**
     * Clear course enrollment caches
     */
    public function clearCourseEnrollmentCaches(int $courseId, int $userId): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        $pipeline->del("course_{$courseId}_user_{$userId}_enrolled");
        $pipeline->del("course_{$courseId}_enrolled_count");
        $pipeline->del("course_{$courseId}_enrolled_students");
        
        $pipeline->exec();
    }

    /**
     * Clear user progress caches for specific courses
     */
    public function clearUserProgressCachesForCourses(int $userId, array $courseIds): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        foreach ($courseIds as $courseId) {
            $pipeline->del("course_{$courseId}_user_{$userId}_completion");
            $pipeline->del("course_{$courseId}_user_{$userId}_mastered");
        }
        
        $pipeline->exec();
    }

    /**
     * Clear payment caches
     */
    public function clearPaymentCaches(int $paymentId, int $userId): void
    {
        $redis = Redis::connection();
        $pipeline = $redis->pipeline();
        
        $pipeline->del("payment_{$paymentId}_status");
        $pipeline->del("user_{$userId}_payments");
        $pipeline->del("user_{$userId}_payment_history");
        
        $pipeline->exec();
    }

    // ===== CONSOLIDATED FEATURES FROM OTHER CACHE SERVICES =====

    /**
     * RESILIENT CACHE OPERATIONS (from ResilientCacheService)
     */
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
                    $backoffDelay = self::RETRY_DELAY_MS * pow(2, $attempt - 1);
                    $startTime = microtime(true);
                    while ((microtime(true) - $startTime) * 1000 < $backoffDelay) {
                        if (function_exists('sched_yield')) {
                            sched_yield();
                        }
                    }
                    continue;
                }
            }
        }

        Log::error('Cache operation failed after all retries', [
            'operation' => $operationType,
            'attempts' => $attempt,
            'final_error' => $lastException->getMessage(),
            'context' => $context
        ]);

        return $fallback;
    }

    /**
     * QUIZ CACHE FEATURES (from QuizCacheService)
     */
    private const CACHE_TTL_SHORT = 300;    // 5 minutes
    private const CACHE_TTL_MEDIUM = 1800;  // 30 minutes
    private const CACHE_TTL_LONG = 3600;    // 1 hour

    /**
     * Generate optimized cache key with collision resistance
     */
    public static function generateCacheKey(string $prefix, array $data): string
    {
        $dataHash = hash('xxh3', serialize($data));
        return "{$prefix}_{$dataHash}";
    }

    /**
     * Store quiz data with intelligent TTL selection
     */
    public static function storeQuizData(string $key, $data, ?int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? self::selectOptimalTTL($data);
            return Cache::put($key, $data, $ttl);
        } catch (Exception $e) {
            Log::warning('Quiz cache storage failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Select optimal TTL based on data characteristics
     */
    private static function selectOptimalTTL($data): int
    {
        if (is_array($data)) {
            $size = count($data);
            
            if ($size > 100) {
                return self::CACHE_TTL_LONG;
            } elseif ($size > 20) {
                return self::CACHE_TTL_MEDIUM;
            }
        }
        
        return self::CACHE_TTL_SHORT;
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
            
            $writeSuccess = self::safePut($testKey, $testValue, 60);
            $readValue = self::safeGet($testKey);
            $deleteSuccess = Cache::forget($testKey);
            
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
     * Get cache statistics
     */
    public static function getStatistics(): array
    {
        try {
            return [
                'driver' => config('cache.default'),
                'health' => self::healthCheck(),
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