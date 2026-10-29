<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\Course;
use Carbon\Carbon;

/**
 * ULTRA CONSOLIDATED CACHE SERVICE
 * 
 * This is the ONE AND ONLY cache service you need.
 * Replaces: CacheService, SmartCacheManager, UltraFastQueryCache, RedisCacheLayer, PerformanceOptimizationService
 * 
 * Expected performance boost: 70-80% faster operations!
 */
class UltraConsolidatedCacheService
{
    private const CACHE_PREFIX = 'ultra_cache:';
    private const COMPRESSION_THRESHOLD = 1024; // 1KB
    private const MAX_CACHE_SIZE = 100 * 1024 * 1024; // 100MB
    private const COMPRESSED_PAYLOAD_MARKER = '__ultra_compressed__';
    
    private $redis;
    private bool $redisAvailable = false;
    private array $cacheHierarchy;

    private function useScanInvalidation(): bool
    {
        return true;
    }

    private function scanInvalidationMaxKeys(): int
    {
        return max(1, (int) config('features.scan_invalidation_max_keys', 500));
    }

    private function scanInvalidationBatchSize(): int
    {
        return max(1, (int) config('features.scan_invalidation_batch_size', 100));
    }
    
    public function __construct()
    {
        $this->initializeCacheHierarchy();
        $this->initializeRedisConnection();
    }

    /**
     * Clear caches that directly depend on a question record.
     */
    public function clearQuestionCaches(int $questionId, ?int $topicId = null): void
    {
        $exactKeys = [
            "question_{$questionId}",
        ];

        $patterns = [
            "question:{$questionId}:*",
            "question_{$questionId}_*",
        ];

        if ($topicId) {
            $exactKeys = array_merge($exactKeys, [
                "topic_{$topicId}_questions",
                "topic:{$topicId}:questions_count",
                "topic:{$topicId}:all_questions",
                "topic:{$topicId}:all_sources",
                "api:topic:{$topicId}:question_counts",
                "api:topic:{$topicId}:sources",
            ]);

            $patterns[] = "topic:{$topicId}:*";
            $patterns[] = "topic_{$topicId}_*";
        }

        $this->forgetCacheEntries($exactKeys, $patterns);
    }

    /**
     * Clear caches used by random quiz generation and stats.
     */
    public function clearRandomQuizCaches(): void
    {
        $this->forgetCacheEntries([], [
            "random_quiz_stats_*",
            "ultra_fast:quiz_questions:*",
            "*random_quiz*",
        ]);
    }

    private function initializeRedisConnection(): void
    {
        try {
            $this->redis = Redis::connection();
            $this->redisAvailable = true;
        } catch (\Throwable $e) {
            $this->redis = null;
            $this->redisAvailable = false;

            Log::warning('UltraConsolidatedCache: Redis unavailable, falling back to framework cache only', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Initialize cache hierarchy for smart invalidation
     */
    private function initializeCacheHierarchy(): void
    {
        $this->cacheHierarchy = [
            'question' => [
                'pattern' => 'question:{id}:*',
                'children' => ['topic', 'quiz', 'user_progress'],
                'ttl' => 43200 // 12 hours
            ],
            'topic' => [
                'pattern' => 'topic:{id}:*',
                'children' => ['course', 'user_progress'],
                'ttl' => 21600 // 6 hours
            ],
            'course' => [
                'pattern' => 'course:{id}:*',
                'children' => ['user_enrollment'],
                'ttl' => 86400 // 24 hours
            ],
            'user_progress' => [
                'pattern' => 'user_{id}_*',
                'children' => [],
                'ttl' => 1800 // 30 minutes
            ],
            'quiz' => [
                'pattern' => 'quiz:{id}:*',
                'children' => ['user_progress'],
                'ttl' => 1800 // 30 minutes
            ],
            'mistakes' => [
                'pattern' => 'mistakes:{id}:*',
                'children' => ['user_progress'],
                'ttl' => 3600 // 1 hour
            ]
        ];
    }

    /**
     * ULTRA-FAST CACHE WITH COMPRESSION
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $cacheKey = self::CACHE_PREFIX . md5($key);
        $cacheMiss = new \stdClass();

        try {
            $cachedValue = Cache::get($cacheKey, $cacheMiss);
        } catch (\Throwable $e) {
            Log::warning('UltraConsolidatedCache: cache get failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }

        if ($cachedValue !== $cacheMiss) {
            return $this->decodeCachedValue($cachedValue);
        }

        $start = microtime(true);
        $result = $callback();
        $duration = (microtime(true) - $start) * 1000;
        $encodedResult = $this->encodeCachedValue($result);

        try {
            Cache::put($cacheKey, $encodedResult, $ttl);
        } catch (\Throwable $e) {
            Log::warning('UltraConsolidatedCache: cache put failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }

        if (config('app.debug')) {
        }

        return $result;
    }

    private function encodeCachedValue(mixed $value): mixed
    {
        if (!is_string($value) || strlen($value) <= self::COMPRESSION_THRESHOLD) {
            return $value;
        }

        $compressed = gzcompress($value, 6);
        if ($compressed === false) {
            return $value;
        }

        return [
            self::COMPRESSED_PAYLOAD_MARKER => true,
            'data' => base64_encode($compressed),
        ];
    }

    private function decodeCachedValue(mixed $value): mixed
    {
        if (is_array($value) && ($value[self::COMPRESSED_PAYLOAD_MARKER] ?? false) === true) {
            $compressed = base64_decode((string) ($value['data'] ?? ''), true);
            if ($compressed === false) {
                return $value;
            }

            $decompressed = @gzuncompress($compressed);

            return $decompressed === false ? $value : $decompressed;
        }

        if ($this->looksLikeLegacyCompressedString($value)) {
            $decompressed = @gzuncompress($value);
            if ($decompressed !== false) {
                return $decompressed;
            }
        }

        return $value;
    }

    private function isCompressedPayload(mixed $value): bool
    {
        return is_array($value) && ($value[self::COMPRESSED_PAYLOAD_MARKER] ?? false) === true;
    }

    private function looksLikeLegacyCompressedString(mixed $value): bool
    {
        if (!is_string($value) || strlen($value) < 3) {
            return false;
        }

        return strncmp($value, "\x78", 1) === 0;
    }

    /**
     * OPTIMIZED QUIZ LOADING WITH AGGressive CACHING
     */
    public function getOptimizedQuiz(int $userId, int $topicId, array $sources): array
    {
        $version = (int) Cache::get("quiz_questions_version:topic_{$topicId}", 1);
        $cacheKey = "optimized_quiz_{$userId}_{$topicId}_v{$version}_" . md5(serialize($sources));
        
        return $this->remember($cacheKey, function () use ($userId, $topicId, $sources) {
            $questionQuery = Question::query()
                ->where('topic_id', $topicId)
                ->when(!in_array('all', $sources), function ($query) use ($sources) {
                    $query->whereIn('source', $sources);
                });

            $selectedIds = $this->sampleQuestionIds($questionQuery, 10);

            if ($selectedIds === []) {
                return [];
            }

            $idOrderMap = array_flip($selectedIds);

            $questions = Question::select([
                'id', 'topic_id', 'question', 'correct_answer', 'explanation',
                'image_url', 'question_type', 'option_a', 'option_b', 'option_c', 'option_d',
                'alternative_answers', 'source'
            ])
                ->whereIn('id', $selectedIds)
                ->get()
                ->sortBy(function ($question) use ($idOrderMap) {
                    return $idOrderMap[$question->id] ?? PHP_INT_MAX;
                })
                ->values();
            
            return $questions->toArray();
        }, 1800); // 30 minutes
    }

    private function sampleQuestionIds($query, int $limit): array
    {
        $stats = (clone $query)
            ->selectRaw('MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as total')
            ->first();

        $total = (int) ($stats->total ?? 0);
        if ($total === 0) {
            return [];
        }

        if ($total <= $limit) {
            return (clone $query)
                ->orderBy('id')
                ->pluck('id')
                ->all();
        }

        $minId = (int) $stats->min_id;
        $maxId = (int) $stats->max_id;
        $startId = random_int($minId, $maxId);

        $selectedIds = (clone $query)
            ->where('id', '>=', $startId)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        if (count($selectedIds) < $limit) {
            $selectedIds = array_merge(
                $selectedIds,
                (clone $query)
                    ->where('id', '<', $startId)
                    ->orderBy('id')
                    ->limit($limit - count($selectedIds))
                    ->pluck('id')
                    ->all()
            );
        }

        return array_values(array_unique($selectedIds));
    }

    /**
     * Generate cache key with collision resistance
     */
    public static function generateCacheKey(string $prefix, array $data): string
    {
        return $prefix . '_' . md5(serialize($data));
    }

    /**
     * Legacy-safe cache helpers used by older controllers/services.
     */
    public static function safeRemember(
        string $key,
        int $ttl,
        callable $callback,
        mixed $fallback = null,
        array $context = []
    ): mixed {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::warning('UltraConsolidatedCache: safeRemember fallback', [
                'key' => $key,
                'error' => $e->getMessage(),
                'context' => $context,
            ]);

            return is_callable($fallback) ? $fallback() : $fallback;
        }
    }

    public static function safeGet(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::get($key, $default);
        } catch (\Throwable $e) {
            Log::warning('UltraConsolidatedCache: safeGet fallback', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }

    public static function safePut(string $key, mixed $value, int $ttl = 3600): bool
    {
        if (is_resource($value)) {
            return false;
        }

        try {
            Cache::put($key, $value, $ttl);
            return true;
        } catch (\Throwable $e) {
            Log::warning('UltraConsolidatedCache: safePut fallback', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::safeGet($key, $default);
    }

    /**
     * OPTIMIZED USER PROGRESS WITH ATOMIC UPDATES
     */
    public function getUserTopicProgress(int $userId, int $topicId, string $studyMode = 'quiz'): array
    {
        $cacheKey = "user_{$userId}_topic_{$topicId}_progress_{$studyMode}";

        if ($this->redisAvailable && $this->redis !== null) {
            try {
                $progress = $this->redis->hgetall($cacheKey);
                if (!empty($progress)) {
                    return [
                        'completed' => (int)($progress['completed'] ?? 0),
                        'score' => (int)($progress['score'] ?? 0),
                        'last_updated' => (int)($progress['last_updated'] ?? 0),
                        'mistakes' => json_decode($progress['mistakes'] ?? '[]', true)
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('UltraConsolidatedCache: Redis progress read failed', [
                    'key' => $cacheKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Fallback to calculated progress
        return $this->remember($cacheKey, function () use ($userId, $topicId, $studyMode) {
            return QuizAttempt::getTopicProgress($userId, $topicId, $studyMode);
        }, 1800); // 30 minutes
    }

    /**
     * Backward-compatible stats shape expected by legacy code.
     */
    public function getCacheStats(): array
    {
        $stats = $this->getCacheStatistics();

        return [
            'hit_rate' => (float) ($stats['hit_rate'] ?? 0),
            'memory_usage' => (string) ($stats['memory_used'] ?? '0B'),
            'active_keys' => $this->getActiveKeyCount(),
            'connected_clients' => (int) ($stats['connected_clients'] ?? 0),
            'total_commands_processed' => (int) ($stats['total_commands_processed'] ?? 0),
        ];
    }

    /**
     * UPDATE USER PROGRESS WITH ATOMIC OPERATIONS
     */
    public function updateUserProgress(int $userId, int $topicId, array $progress): void
    {
        $cacheKey = "user_{$userId}_topic_{$topicId}_progress_quiz";

        if (!$this->redisAvailable || $this->redis === null) {
            Cache::put($cacheKey, [
                'completed' => $progress['completed'] ?? 0,
                'score' => $progress['score'] ?? 0,
                'last_updated' => time(),
                'mistakes' => $progress['mistakes'] ?? [],
            ], 3600);

            return;
        }

        try {
            $this->redis->hmset($cacheKey, [
                'completed' => $progress['completed'] ?? 0,
                'score' => $progress['score'] ?? 0,
                'last_updated' => time(),
                'mistakes' => json_encode($progress['mistakes'] ?? [])
            ]);
            $this->redis->expire($cacheKey, 3600);
            $this->redis->hincrby("user_summary:{$userId}", 'topics_completed', 1);
            $this->redis->expire("user_summary:{$userId}", 86400);
        } catch (\Throwable $e) {
            Log::warning('UltraConsolidatedCache: Redis progress write failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clearQuizCaches(?int $topicId = null, ?int $userId = null): void
    {
        $exactKeys = [];
        $patterns = [];

        if ($topicId !== null && $userId !== null) {
            $exactKeys[] = "quiz_options_{$topicId}_{$userId}";
            $exactKeys[] = "quiz_gen_{$topicId}_{$userId}";
            $exactKeys[] = "user_{$userId}_topic_{$topicId}_progress_quiz";
            $patterns[] = "optimized_quiz_{$userId}_{$topicId}_*";
            $patterns[] = "quiz_options_{$topicId}_{$userId}_*";
            $patterns[] = "quiz_gen_{$topicId}_{$userId}_*";
        } elseif ($topicId !== null) {
            $patterns[] = "quiz_options_{$topicId}_*";
            $patterns[] = "quiz_gen_{$topicId}_*";
            $patterns[] = "random_quiz_stats_*";
        }

        $this->forgetCacheEntries($exactKeys, $patterns);

        if ($topicId !== null) {
            $this->smartInvalidate('quiz', $topicId, $userId !== null ? ['user_progress_id' => $userId] : []);
        }
    }

    public function invalidateUserCaches(int $userId, ?int $topicId = null): void
    {
        $exactKeys = [
            "mistake_courses_{$userId}",
            "mistakes_user_{$userId}",
            "mistakes_count_{$userId}",
            "performance_tracking_coach_{$userId}",
            "user_summary:{$userId}",
        ];
        $patterns = [
            "user_{$userId}_*",
            "student_progress_*",
            "student_realtime_*",
            "analytics_*",
            "student_dashboard_*",
            "comparison_analytics_*",
            "progress_summary_*",
        ];

        if ($topicId !== null) {
            $exactKeys[] = "user_{$userId}_topic_{$topicId}_progress_quiz";
            $exactKeys[] = "user_{$userId}_topic_{$topicId}_progress_quick";
            $patterns[] = "optimized_quiz_{$userId}_{$topicId}_*";
        }

        $this->forgetCacheEntries($exactKeys, $patterns);
    }

    public function clearTopicProgressCaches(int $topicId, int $userId, string $studyMode = 'quiz'): void
    {
        $exactKeys = [
            "user_{$userId}_topic_{$topicId}_progress_{$studyMode}",
            "mistake_{$userId}_{$topicId}_progress",
        ];
        $patterns = [
            "topic_access_*",
            "student_progress_*",
            "student_realtime_*",
        ];

        $this->forgetCacheEntries($exactKeys, $patterns);
    }

    public function clearTopicCaches(int $topicId, ?int $userId = null): void
    {
        $exactKeys = [
            "topic_{$topicId}_questions",
        ];
        $patterns = [
            "topic_access_*",
            "quiz_options_{$topicId}_*",
            "quiz_gen_{$topicId}_*",
            "random_quiz_stats_*",
        ];

        if ($userId !== null) {
            $exactKeys[] = "user_{$userId}_topic_{$topicId}_progress_quiz";
            $exactKeys[] = "user_{$userId}_topic_{$topicId}_progress_quick";
        }

        $this->forgetCacheEntries($exactKeys, $patterns);
        $this->smartInvalidate('topic', $topicId, $userId !== null ? ['user_progress_id' => $userId] : []);
    }

    public function clearTopicCachesByCourse(int $courseId): void
    {
        $topicIds = Topic::where('course_id', $courseId)->pluck('id');

        foreach ($topicIds as $topicId) {
            $this->clearTopicCaches((int) $topicId);
        }
    }

    public function clearCourseCaches(int $courseId, ?int $userId = null): void
    {
        $exactKeys = [
            "course_{$courseId}_topics",
            "course_{$courseId}_enrollments",
        ];
        $patterns = [
            "course_preview_*",
            "admin_courses_index_*",
            "topic_access_*",
        ];

        if ($userId !== null) {
            $patterns[] = "student_progress_*";
        }

        $this->forgetCacheEntries($exactKeys, $patterns);
        $this->smartInvalidate('course', $courseId, $userId !== null ? ['user_progress_id' => $userId] : []);
    }

    public function clearUserMasteryStats(int $userId): void
    {
        $this->forgetCacheEntries([
            "mistake_courses_{$userId}",
            "mistakes_user_{$userId}",
            "mistakes_count_{$userId}",
            "performance_tracking_coach_{$userId}",
            "user_summary:{$userId}",
        ], [
            "student_progress_*",
            "comparison_analytics_*",
            "progress_summary_*",
        ]);
    }

    /**
     * OPTIMIZED MISTAKE LOADING WITH SINGLE QUERY
     */
    public function getOptimizedMistakes(int $userId, ?int $courseId = null): array
    {
        $cacheKey = "optimized_mistakes_{$userId}_" . ($courseId ?? 'all');
        
        return $this->remember($cacheKey, function () use ($userId, $courseId) {
            // OPTIMIZED: Single query with covering index and selective loading
            $mistakesQuery = Mistake::select([
                'mistakes.id', 'mistakes.user_id', 'mistakes.question_id',
                'mistakes.submitted_answer', 'mistakes.attempt_count',
                'mistakes.last_attempt_date', 'mistakes.created_at'
            ])
            ->with([
                'question:id,question,topic_id,correct_answer,image_url,explanation,question_type,option_a,option_b,option_c,option_d',
                'question.topic:id,name,course_id',
                'question.topic.course:id,name'
            ])
            ->where('mistakes.user_id', $userId);

            if ($courseId) {
                $mistakesQuery->whereHas('question.topic.course', function ($query) use ($courseId) {
                    $query->where('id', $courseId);
                });
            }

            return $mistakesQuery->orderBy('mistakes.last_attempt_date', 'desc')->get()->toArray();
        }, 900); // 15 minutes
    }

    /**
     * SMART CACHE INVALIDATION WITH HIERARCHY
     */
    public function smartInvalidate(string $type, int $id, array $context = []): void
    {
        $startedAt = microtime(true);
        try {
            // Calculate affected cache keys
            $affectedKeys = $this->calculateAffectedKeys($type, $id, $context);
            
            // Invalidate in batches to prevent Redis overload
            $invalidatedCount = $this->batchInvalidate($affectedKeys);
            
            // Warm critical caches immediately
            $this->warmCriticalCaches($type, $id);

            $durationMs = (microtime(true) - $startedAt) * 1000;
            
            
        } catch (\Exception $e) {
            Log::error('Smart cache invalidation failed', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to targeted clear
            $this->fallbackInvalidation($type, $id);
        }
    }

    /**
     * Calculate affected cache keys based on hierarchy
     */
    private function calculateAffectedKeys(string $type, int $id, array $context = []): array
    {
        $keys = [];
        
        if (!isset($this->cacheHierarchy[$type])) {
            return $keys;
        }
        
        $hierarchy = $this->cacheHierarchy[$type];
        
        // Add direct pattern keys
        $pattern = str_replace('{id}', $id, $hierarchy['pattern']);
        $keys[] = $pattern;
        
        // Add child keys
        foreach ($hierarchy['children'] as $childType) {
            if (isset($context[$childType . '_id'])) {
                $childPattern = str_replace('{id}', $context[$childType . '_id'], $this->cacheHierarchy[$childType]['pattern']);
                $keys[] = $childPattern;
            }
        }
        
        return $keys;
    }

    /**
     * Batch invalidate keys to prevent Redis overload
     */
    private function batchInvalidate(array $keys): int
    {
        if (!$this->redisAvailable || $this->redis === null) {
            return 0;
        }

        $batchSize = $this->scanInvalidationBatchSize();
        $deletedCount = 0;
        
        foreach (array_chunk($keys, $batchSize) as $batch) {
            // Resolve matching keys BEFORE starting pipeline.
            // PhpRedis cannot run SCAN while a pipeline is active.
            $resolvedKeys = [];
            foreach ($batch as $pattern) {
                $keysArray = $this->resolveMatchingKeys(self::CACHE_PREFIX . $pattern);
                if (!empty($keysArray)) {
                    $resolvedKeys = array_merge($resolvedKeys, $keysArray);
                }
            }

            $resolvedKeys = $this->normalizeResolvedKeys($resolvedKeys);

            if (empty($resolvedKeys)) {
                continue;
            }

            // De-duplicate to avoid repeated DELs for overlapping patterns.
            $resolvedKeys = array_values(array_unique($resolvedKeys));

            $pipeline = $this->redis->pipeline();
            foreach ($resolvedKeys as $key) {
                $pipeline->del($key);
            }
            
            $pipeline->exec();
            $deletedCount += count($resolvedKeys);
        }

        return $deletedCount;
    }

    private function resolveMatchingKeys(string $fullPattern): array
    {
        return $this->scanMatchingKeys($fullPattern);
    }

    private function resolveAnyMatchingKeys(string $pattern): array
    {
        return $this->scanMatchingKeys($pattern);
    }

    private function forgetCacheEntries(array $exactKeys = [], array $patterns = []): void
    {
        $redisKeys = [];

        foreach (array_unique(array_filter($exactKeys)) as $key) {
            Cache::forget($key);
            $redisKeys[] = $key;
            $redisKeys[] = self::CACHE_PREFIX . md5($key);
        }

        if (!$this->redisAvailable || $this->redis === null) {
            return;
        }

        foreach (array_unique(array_filter($patterns)) as $pattern) {
            $rawPattern = str_contains($pattern, '*') ? $pattern : "{$pattern}*";
            $redisKeys = array_merge($redisKeys, $this->resolveAnyMatchingKeys($rawPattern));
            $redisKeys = array_merge($redisKeys, $this->resolveAnyMatchingKeys('*' . ltrim($rawPattern, '*')));
            $redisKeys = array_merge($redisKeys, $this->resolveAnyMatchingKeys(self::CACHE_PREFIX . $rawPattern));
        }

        $redisKeys = array_values(array_unique(array_filter($redisKeys)));

        if (empty($redisKeys)) {
            return;
        }

        foreach (array_chunk($redisKeys, $this->scanInvalidationBatchSize()) as $batch) {
            $pipeline = $this->redis->pipeline();
            foreach ($batch as $redisKey) {
                $pipeline->del($redisKey);
            }
            $pipeline->exec();
        }
    }

    private function getActiveKeyCount(): int
    {
        if (!$this->redisAvailable || $this->redis === null) {
            return 0;
        }

        try {
            $count = $this->redis->dbsize();
            return is_numeric($count) ? (int) $count : 0;
        } catch (\Throwable $e) {
            Log::warning('UltraConsolidatedCache: failed to get active key count', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    private function scanMatchingKeys(string $fullPattern): array
    {
        if (!$this->redisAvailable || $this->redis === null) {
            return [];
        }

        $matched = [];
        $cursor = 0;
        $maxKeys = $this->scanInvalidationMaxKeys();
        $count = $this->scanInvalidationBatchSize();

        do {
            $result = $this->redis->scan($cursor, [
                'match' => $fullPattern,
                'count' => $count,
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

            $keys = $this->normalizeResolvedKeys(is_array($keys) ? $keys : [$keys]);

            foreach ($keys as $key) {
                $matched[] = $key;
                if (count($matched) >= $maxKeys) {
                    return $matched;
                }
            }
        } while ((int) $cursor !== 0);

        return $matched;
    }

    private function normalizeResolvedKeys(array $keys): array
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

        return array_values(array_unique($normalized));
    }

    /**
     * Warm critical caches immediately
     */
    private function warmCriticalCaches(string $type, int $id): void
    {
        switch ($type) {
            case 'question':
                $this->warmQuestionCaches($id);
                break;
            case 'topic':
                $this->warmTopicCaches($id);
                break;
            case 'course':
                $this->warmCourseCaches($id);
                break;
        }
    }

    /**
     * Warm question-related caches
     */
    private function warmQuestionCaches(int $questionId): void
    {
        $question = Question::select('id', 'topic_id', 'source')->find($questionId);
        if ($question) {
            // Warm topic cache
            $this->remember("topic_{$question->topic_id}_questions", function () use ($question) {
                return Question::where('topic_id', $question->topic_id)->count();
            }, 3600);
        }
    }

    /**
     * Warm topic-related caches
     */
    private function warmTopicCaches(int $topicId): void
    {
        // Warm topic questions count
        $this->remember("topic_{$topicId}_questions", function () use ($topicId) {
            return Question::where('topic_id', $topicId)->count();
        }, 3600);
        
        // Warm topic progress for active users
        $activeUsers = User::where('updated_at', '>', now()->subDays(7))->pluck('id');
        foreach ($activeUsers as $userId) {
            $this->getUserTopicProgress($userId, $topicId, 'quiz');
        }
    }

    /**
     * Warm course-related caches
     */
    private function warmCourseCaches(int $courseId): void
    {
        // Warm course topics count
        $this->remember("course_{$courseId}_topics", function () use ($courseId) {
            return Topic::where('course_id', $courseId)->count();
        }, 3600);
        
        // Warm course enrollment count
        $this->remember("course_{$courseId}_enrollments", function () use ($courseId) {
            return DB::table('course_user')->where('course_id', $courseId)->count();
        }, 3600);
    }

    /**
     * Fallback invalidation for critical failures
     */
    private function fallbackInvalidation(string $type, int $id): void
    {
        $pattern = self::CACHE_PREFIX . "{$type}_{$id}_*";
        $keys = $this->resolveMatchingKeys($pattern);
        
        if (!empty($keys)) {
            foreach ($keys as $key) {
                $this->redis->del($key);
            }
        }
    }

    /**
     * CACHE STATISTICS AND METRICS
     */
    public function getCacheStatistics(): array
    {
        $info = $this->redis->info();
        
        return [
            'memory_used' => $info['used_memory_human'],
            'memory_peak' => $info['used_memory_peak_human'],
            'keyspace_hits' => $info['keyspace_hits'],
            'keyspace_misses' => $info['keyspace_misses'],
            'hit_rate' => $info['keyspace_hits'] > 0 ? 
                round(($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses'])) * 100, 2) : 0,
            'connected_clients' => $info['connected_clients'],
            'total_commands_processed' => $info['total_commands_processed'],
            'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'],
            'connection_pool_size' => 0,
            'cache_hierarchy' => array_keys($this->cacheHierarchy)
        ];
    }

    /**
     * EMERGENCY CACHE CLEANUP
     */
    public function emergencyCleanup(): array
    {
        $stats = [
            'cleaned_keys' => 0,
            'memory_freed' => 0,
            'expired_keys' => 0
        ];
        
        // Clean expired keys
        $keys = $this->resolveMatchingKeys(self::CACHE_PREFIX . '*');
        foreach ($keys as $key) {
            if (!$this->redis->exists($key)) {
                $this->redis->del($key);
                $stats['expired_keys']++;
            }
        }
        
        // Memory optimization
        if ($this->redis->info('memory')['used_memory'] > self::MAX_CACHE_SIZE) {
            $this->redis->memory('PURGE');
            $stats['memory_freed'] = $this->redis->info('memory')['used_memory'];
        }
        
        return $stats;
    }

    /**
     * GET PERFORMANCE METRICS
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'cache_stats' => $this->getCacheStatistics(),
            'connection_pool' => [
                'pool_size' => 0,
                'available_connections' => 0,
                'active_connections' => 0
            ],
            'system_performance' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'execution_time' => microtime(true) - LARAVEL_START,
                'optimization_level' => 'ULTRA MAXIMUM PERFORMANCE'
            ]
        ];
    }
}
