<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Redis Cache Service for MedCapsule
 * Handles all caching operations with intelligent invalidation
 */
class RedisCacheService
{
    private const DEFAULT_TTL = 3600; // 1 hour
    private const USER_DATA_TTL = 1800; // 30 minutes
    private const QUIZ_DATA_TTL = 900; // 15 minutes
    private const COURSE_DATA_TTL = 3600; // 1 hour
    private const ANALYTICS_TTL = 21600; // 6 hours

    /**
     * Check if Redis is available and working
     */
    public function isAvailable(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            Log::warning('Redis is not available', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get cache key with prefix
     */
    private function getCacheKey(string $key): string
    {
        return "medcapsule:{$key}";
    }

    /**
     * Get user-specific cache key
     */
    private function getUserCacheKey(string $key, int $userId): string
    {
        return "medcapsule:user:{$userId}:{$key}";
    }

    /**
     * Cache user profile data
     */
    public function cacheUserProfile(int $userId, array $data): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->getUserCacheKey('profile', $userId);
            Redis::setex($key, self::USER_DATA_TTL, json_encode($data));

            Log::info('User profile cached', ['user_id' => $userId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache user profile', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached user profile
     */
    public function getCachedUserProfile(int $userId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $key = $this->getUserCacheKey('profile', $userId);
            $data = Redis::get($key);

            if ($data) {
                return json_decode($data, true);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get cached user profile', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache course data
     */
    public function cacheCourse(int $courseId, array $data): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->getCacheKey("course:{$courseId}");
            Redis::setex($key, self::COURSE_DATA_TTL, json_encode($data));

            Log::info('Course cached', ['course_id' => $courseId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache course', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached course data
     */
    public function getCachedCourse(int $courseId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $key = $this->getCacheKey("course:{$courseId}");
            $data = Redis::get($key);

            if ($data) {
                return json_decode($data, true);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get cached course', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache quiz data
     */
    public function cacheQuiz(int $quizId, array $data): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->getCacheKey("quiz:{$quizId}");
            Redis::setex($key, self::QUIZ_DATA_TTL, json_encode($data));

            Log::info('Quiz cached', ['quiz_id' => $quizId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache quiz', [
                'quiz_id' => $quizId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached quiz data
     */
    public function getCachedQuiz(int $quizId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $key = $this->getCacheKey("quiz:{$quizId}");
            $data = Redis::get($key);

            if ($data) {
                return json_decode($data, true);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get cached quiz', [
                'quiz_id' => $quizId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache user quiz attempts
     */
    public function cacheUserQuizAttempts(int $userId, array $attempts): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->getUserCacheKey('quiz_attempts', $userId);
            Redis::setex($key, self::QUIZ_DATA_TTL, json_encode($attempts));

            Log::info('User quiz attempts cached', ['user_id' => $userId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache user quiz attempts', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached user quiz attempts
     */
    public function getCachedUserQuizAttempts(int $userId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $key = $this->getUserCacheKey('quiz_attempts', $userId);
            $data = Redis::get($key);

            if ($data) {
                return json_decode($data, true);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get cached user quiz attempts', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache analytics data
     */
    public function cacheAnalytics(string $type, array $data): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->getCacheKey("analytics:{$type}");
            Redis::setex($key, self::ANALYTICS_TTL, json_encode($data));

            Log::info('Analytics cached', ['type' => $type]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache analytics', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached analytics data
     */
    public function getCachedAnalytics(string $type): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $key = $this->getCacheKey("analytics:{$type}");
            $data = Redis::get($key);

            if ($data) {
                return json_decode($data, true);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get cached analytics', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache user progress data
     */
    public function cacheUserProgress(int $userId, int $courseId, array $progress): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->getUserCacheKey("progress:{$courseId}", $userId);
            Redis::setex($key, self::USER_DATA_TTL, json_encode($progress));

            Log::info('User progress cached', [
                'user_id' => $userId,
                'course_id' => $courseId
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache user progress', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached user progress
     */
    public function getCachedUserProgress(int $userId, int $courseId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $key = $this->getUserCacheKey("progress:{$courseId}", $userId);
            $data = Redis::get($key);

            if ($data) {
                return json_decode($data, true);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get cached user progress', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Clear user cache
     */
    public function clearUserCache(int $userId): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $pattern = $this->getUserCacheKey('*', $userId);
            $keys = Redis::keys($pattern);

            if (!empty($keys)) {
                Redis::del($keys);
            }

            Log::info('User cache cleared', ['user_id' => $userId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear user cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear course cache
     */
    public function clearCourseCache(int $courseId): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->getCacheKey("course:{$courseId}");
            Redis::del($key);

            Log::info('Course cache cleared', ['course_id' => $courseId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear course cache', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear quiz cache
     */
    public function clearQuizCache(int $quizId): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->getCacheKey("quiz:{$quizId}");
            Redis::del($key);

            Log::info('Quiz cache cleared', ['quiz_id' => $quizId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear quiz cache', [
                'quiz_id' => $quizId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear analytics cache
     */
    public function clearAnalyticsCache(string $type): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->getCacheKey("analytics:{$type}");
            Redis::del($key);

            Log::info('Analytics cache cleared', ['type' => $type]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear analytics cache', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        if (!$this->isAvailable()) {
            return ['status' => 'unavailable'];
        }

        try {
            $info = Redis::info();
            $keys = Redis::keys('medcapsule:*');

            return [
                'status' => 'available',
                'total_keys' => count($keys),
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 'N/A',
                'uptime_days' => $info['uptime_in_days'] ?? 'N/A',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Warm up cache with frequently accessed data
     */
    public function warmUpCache(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            // This would be called by a scheduled job to warm up cache
            Log::info('Cache warm-up started');

            // TODO: Implement cache warming logic for popular data
            // - Popular courses
            // - Frequently accessed quizzes
            // - Common user data

            Log::info('Cache warm-up completed');
            return true;
        } catch (\Exception $e) {
            Log::error('Cache warm-up failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
