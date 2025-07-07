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
    
    /**
     * Get user's topic progress with caching
     */
    public function getUserTopicProgress(int $userId, int $topicId, string $studyMode = 'quiz'): array
    {
        $cacheKey = "user_{$userId}_topic_{$topicId}_progress_{$studyMode}";
        
        return Cache::remember($cacheKey, self::HOUR_IN_SECONDS, function () use ($userId, $topicId, $studyMode) {
            return QuizAttempt::getTopicProgress($userId, $topicId, $studyMode);
        });
    }
    
    /**
     * Get user's common mistakes with caching
     */
    public function getUserCommonMistakes(int $userId, int $topicId, int $limit = 10): array
    {
        $cacheKey = "user_{$userId}_topic_{$topicId}_mistakes";
        
        return Cache::remember($cacheKey, self::HOUR_IN_SECONDS, function () use ($userId, $topicId, $limit) {
            return QuizAttempt::getCommonMistakes($userId, $topicId, $limit)->toArray();
        });
    }
    
    /**
     * Get user's mastery statistics with caching
     */
    public function getUserMasteryStats(int $userId): array
    {
        $cacheKey = "user_{$userId}_mastery_stats";
        
        return Cache::remember($cacheKey, self::HOUR_IN_SECONDS, function () use ($userId) {
            return [
                'total_mistakes' => Mistake::where('user_id', $userId)->count(),
                'mastered_count' => Mistake::where('user_id', $userId)->where('correct_streak', '>=', 4)->count(),
                'in_progress_count' => Mistake::where('user_id', $userId)->where('correct_streak', '<', 4)->count()
            ];
        });
    }
    
    /**
     * Clear user's topic progress cache
     */
    public function clearUserTopicProgressCache(int $userId, int $topicId): void
    {
        $modes = ['quiz', 'tutor'];
        foreach ($modes as $mode) {
            Cache::forget("user_{$userId}_topic_{$topicId}_progress_{$mode}");
        }
    }
    
    /**
     * Clear user's mistakes cache
     */
    public function clearUserMistakesCache(int $userId, int $topicId): void
    {
        Cache::forget("user_{$userId}_topic_{$topicId}_mistakes");
        Cache::forget("user_{$userId}_mastery_stats");
    }
    
    /**
     * Clear all caches for a user
     */
    public function clearAllUserCaches(int $userId): void
    {
        // Clear topic progress caches
        $topics = Topic::pluck('id');
        foreach ($topics as $topicId) {
            $this->clearUserTopicProgressCache($userId, $topicId);
        }
        
        // Clear mistakes caches
        Cache::forget("user_{$userId}_mastery_stats");
        
        // Clear any topic-specific mistake caches
        foreach ($topics as $topicId) {
            Cache::forget("user_{$userId}_topic_{$topicId}_mistakes");
        }
    }

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
} 