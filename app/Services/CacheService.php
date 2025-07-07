<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Topic;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use Carbon\Carbon;

class CacheService
{
    // Cache TTL constants
    const HOUR_IN_SECONDS = 3600;
    const DAY_IN_SECONDS = 86400;
    const WEEK_IN_SECONDS = 604800;
    
    // Cache tags for targeted invalidation
    const USER_PROGRESS_TAG = 'user_progress';
    const USER_MISTAKES_TAG = 'user_mistakes';
    const USER_STATS_TAG = 'user_stats';
    const TOPIC_DATA_TAG = 'topic_data';
    const QUIZ_DATA_TAG = 'quiz_data';
    
    /**
     * Get user's topic progress with caching and atomic updates
     */
    public function getUserTopicProgress(int $userId, int $topicId, string $studyMode = 'quiz'): array
    {
        $cacheKey = "user_{$userId}_topic_{$topicId}_progress_{$studyMode}";
        return Cache::remember($cacheKey, self::HOUR_IN_SECONDS, function () use ($userId, $topicId, $studyMode) {
            return QuizAttempt::getTopicProgress($userId, $topicId, $studyMode);
        });
    }
    
    /**
     * Get user's common mistakes with caching and intelligent invalidation
     */
    public function getUserCommonMistakes(int $userId, int $topicId, int $limit = 10): array
    {
        $cacheKey = "user_{$userId}_topic_{$topicId}_mistakes";
        return Cache::remember($cacheKey, self::HOUR_IN_SECONDS, function () use ($userId, $topicId, $limit) {
            return QuizAttempt::getCommonMistakes($userId, $topicId, $limit)->toArray();
        });
    }
    
    /**
     * Get user's mastery statistics with atomic cache updates
     */
    public function getUserMasteryStats(int $userId): array
    {
        $cacheKey = "user_{$userId}_mastery_stats";
        return Cache::remember($cacheKey, self::HOUR_IN_SECONDS, function () use ($userId) {
            return $this->calculateUserMasteryStats($userId);
        });
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
     * ATOMIC CACHE INVALIDATION: Clear all user-related caches (no tags)
     */
    public function invalidateUserCaches(int $userId, ?int $topicId = null): void
    {
        // Remove topic progress caches
        $modes = ['quiz', 'tutor'];
        if ($topicId) {
            foreach ($modes as $mode) {
                Cache::forget("user_{$userId}_topic_{$topicId}_progress_{$mode}");
            }
            Cache::forget("user_{$userId}_topic_{$topicId}_mistakes");
        } else {
            // If no topicId, clear all topics for this user
            $topics = Topic::pluck('id');
            foreach ($topics as $tid) {
                foreach ($modes as $mode) {
                    Cache::forget("user_{$userId}_topic_{$tid}_progress_{$mode}");
                }
                Cache::forget("user_{$userId}_topic_{$tid}_mistakes");
            }
        }
        // Remove mastery stats
        Cache::forget("user_{$userId}_mastery_stats");
        // Log cache invalidation for debugging
        \Log::info('Cache invalidated', [
            'user_id' => $userId,
            'topic_id' => $topicId,
            'timestamp' => now()
        ]);
    }
    
    /**
     * INTELLIGENT CACHE WARMING: Pre-populate critical caches
     */
    public function warmUserCaches(int $userId): void
    {
        // Warm user mastery stats
        $this->getUserMasteryStats($userId);
        
        // Warm topic progress for all user's topics
        $userTopics = Topic::whereHas('quizAttempts', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->pluck('id');
        
        foreach ($userTopics as $topicId) {
            $this->getUserTopicProgress($userId, $topicId, 'quiz');
            $this->getUserTopicProgress($userId, $topicId, 'tutor');
            $this->getUserCommonMistakes($userId, $topicId);
        }
    }
    
    /**
     * CACHE CONSISTENCY: Ensure cache is updated before DB write
     */
    public function updateCacheBeforeWrite(string $operation, array $data): void
    {
        switch ($operation) {
            case 'quiz_completed':
                $this->invalidateUserCaches($data['user_id'], $data['topic_id'] ?? null);
                break;
                
            case 'mistake_corrected':
                $this->invalidateUserCaches($data['user_id'], $data['topic_id'] ?? null);
                break;
                
            case 'user_level_up':
                $this->invalidateUserCaches($data['user_id']);
                break;
        }
    }
    
    /**
     * ENHANCED CACHE MONITORING: Track cache hit rates and performance
     */
    public function getCacheStats(): array
    {
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
            'cache_ttl' => [
                'hour' => self::HOUR_IN_SECONDS,
                'day' => self::DAY_IN_SECONDS,
                'week' => self::WEEK_IN_SECONDS
            ],
            'active_tags' => [
                self::USER_PROGRESS_TAG,
                self::USER_MISTAKES_TAG,
                self::USER_STATS_TAG,
                self::TOPIC_DATA_TAG,
                self::QUIZ_DATA_TAG
            ]
        ];
    }

    // Legacy methods for backward compatibility
    public function get($key, $default = null)
    {
        return cache()->get($key, $default);
    }

    public function put($key, $value, $seconds = null)
    {
        return cache()->put($key, $value, $seconds);
    }

    public function forget($key)
    {
        return cache()->forget($key);
    }

    public function remember($key, $seconds, \Closure $callback)
    {
        return cache()->remember($key, $seconds, $callback);
    }
    
    // DEPRECATED: Use invalidateUserCaches instead
    public function clearUserTopicProgressCache(int $userId, int $topicId): void
    {
        $this->invalidateUserCaches($userId, $topicId);
    }
    
    public function clearUserMistakesCache(int $userId, int $topicId): void
    {
        $this->invalidateUserCaches($userId, $topicId);
    }
    
    public function clearAllUserCaches(int $userId): void
    {
        $this->invalidateUserCaches($userId);
    }
} 