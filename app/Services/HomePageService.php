<?php

namespace App\Services;

use App\Models\Course;
use App\Models\AdminSetting;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * BULLETPROOF Home Page Service - NEVER FAILS ON CACHE ISSUES
 * Features: Auto-healing cache, database change detection, fallback systems
 */
class HomePageService
{
    private const CACHE_VERSION = 'v4_bulletproof_2025';
    private const FALLBACK_TIMEOUT = 300; // 5 minutes fallback cache
    private const HEALTH_CHECK_INTERVAL = 60; // Check cache health every minute

    /**
     * BULLETPROOF: Get home page data with automatic cache recovery
     * NEVER fails - always returns data even if cache is corrupted
     */
    public function getHomePageData(): array
    {
        try {
            // BULLETPROOF: Multi-layer data retrieval with automatic recovery
            $data = $this->getBulletproofHomeData();

            // BULLETPROOF: Validate data integrity
            if (!$this->validateHomeData($data)) {
                Log::warning('Home page data validation failed, forcing refresh');
                Cache::forget($this->getCacheKey('home_data'));
                Cache::forget($this->getCacheKey('statistics'));
                Cache::forget($this->getCacheKey('featured_courses'));
                $data = $this->getBulletproofHomeData(true); // Force fresh data
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('BULLETPROOF: Home page service failed, activating emergency mode', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getEmergencyFallbackData();
        }
    }

    public function getActiveStudentCount(): int
    {
        return Cache::remember('home_active_student_count', 300, function (): int {
            if (AdminSetting::get('fake_students_enabled', false)) {
                return (int) AdminSetting::get('fake_students_count', 0);
            }

            return User::where('role', User::ROLE_STUDENT)->count();
        });
    }

    /**
     * BULLETPROOF: Get data with automatic cache recovery and health monitoring
     */
    private function getBulletproofHomeData(bool $forceFresh = false): array
    {
        $cacheKey = $this->getCacheKey('home_data');

        if ($forceFresh) {
            $data = $this->fetchFreshHomeData();
            Cache::put($cacheKey, $data, 3600);
            return $data;
        }

        // Try cache first.  A cache hit means the TTL hasn't expired, which is
        // all the "health check" that is needed — the key already embeds CACHE_VERSION
        // so stale data from an old code version is automatically bypassed.
        // Eliminated the extra isCacheHealthy() cache-read that previously fired on
        // every request even when the cache was perfectly valid.
        $data = Cache::get($cacheKey);

        if (!$data) {
            $data = $this->fetchFreshHomeData();
            Cache::put($cacheKey, $data, 3600);

            // Also cache components separately for granular recovery
            $this->cacheComponentsSeparately($data);
        }

        return $data;
    }

    /**
     * BULLETPROOF: Fetch fresh data directly from database
     */
    private function fetchFreshHomeData(): array
    {
        // BULLETPROOF: Direct database queries with error handling
        $statistics = $this->getFreshStatistics();
        $featuredCourses = $this->getFreshFeaturedCourses();

        return [
            'statistics' => $statistics,
            'featuredCourses' => $featuredCourses,
            'cache_health' => 'fresh',
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * BULLETPROOF: Get fresh statistics directly from database
     */
    private function getFreshStatistics(): array
    {
        try {
            // BULLETPROOF: Direct database queries with individual error handling
            $quizStats = DB::selectOne("
                SELECT
                    COUNT(CASE WHEN case_type = 'quiz' THEN 1 END) as quizzes,
                    COUNT(CASE WHEN case_type = 'cases' THEN 1 END) as cases,
                    COUNT(CASE WHEN case_type = 'practical' THEN 1 END) as practical
                FROM topics
                WHERE case_type IN ('quiz', 'cases', 'practical')
            ");

            $questionCount = DB::selectOne("SELECT COUNT(*) as total FROM questions")->total ?? 0;

            $enrolledCount = DB::selectOne("
                SELECT COUNT(DISTINCT user_id) as total
                FROM course_user
                WHERE enrolled_at IS NOT NULL
            ")->total ?? 0;

            return [
                'quizzes' => (int) ($quizStats->quizzes ?? 0),
                'cases' => (int) ($quizStats->cases ?? 0),
                'practical' => (int) ($quizStats->practical ?? 0),
                'questions' => (int) $questionCount,
                'enrolled_students' => (int) $enrolledCount
            ];
        } catch (\Exception $e) {
            Log::error('Fresh statistics query failed', ['error' => $e->getMessage()]);
            return $this->getMinimalStatistics();
        }
    }

    /**
     * BULLETPROOF: Get fresh featured courses directly from database
     */
    private function getFreshFeaturedCourses()
    {
        try {
            // BULLETPROOF: Get base course data first (fastest query)
            $courses = Course::select([
                    'id', 'name', 'description', 'image',
                    'color', 'price', 'academic_year'
                ])
                ->where('type', 'medical')
                ->orderBy('name')
                ->limit(12)
                ->get();

            // BULLETPROOF: Precompute stats for all courses in single queries
            $courseIds = $courses->pluck('id')->toArray();

            if (empty($courseIds)) {
                return collect([]);
            }

            // Get topic counts by course using parameterized query
            $topicStats = DB::table('topics')
                ->whereIn('course_id', $courseIds)
                ->groupBy('course_id')
                ->select(
                    'course_id',
                    DB::raw("COUNT(CASE WHEN case_type = 'quiz' THEN 1 END) as quiz_count"),
                    DB::raw("COUNT(CASE WHEN case_type = 'cases' THEN 1 END) as case_count"),
                    DB::raw("COUNT(CASE WHEN case_type = 'practical' THEN 1 END) as practical_count")
                )
                ->get();

            // Get question counts by course using parameterized query
            $questionStats = DB::table('topics as t')
                ->leftJoin('questions as q', 't.id', '=', 'q.topic_id')
                ->whereIn('t.course_id', $courseIds)
                ->groupBy('t.course_id')
                ->select('t.course_id', DB::raw('COUNT(q.id) as question_count'))
                ->get();

            // Get enrollment counts by course using parameterized query
            $enrollmentStats = DB::table('course_user')
                ->whereIn('course_id', $courseIds)
                ->whereNotNull('enrolled_at')
                ->groupBy('course_id')
                ->select('course_id', DB::raw('COUNT(DISTINCT user_id) as enrolled_count'))
                ->get();

            // BULLETPROOF: Map stats to courses efficiently
            $topicStatsMap = collect($topicStats)->keyBy('course_id');
            $questionStatsMap = collect($questionStats)->keyBy('course_id');
            $enrollmentStatsMap = collect($enrollmentStats)->keyBy('course_id');

            return $courses->map(function ($course) use ($topicStatsMap, $questionStatsMap, $enrollmentStatsMap) {
                $topicStat = $topicStatsMap->get($course->id);
                $questionStat = $questionStatsMap->get($course->id);
                $enrollmentStat = $enrollmentStatsMap->get($course->id);

                $course->quiz_count = (int) ($topicStat->quiz_count ?? 0);
                $course->case_count = (int) ($topicStat->case_count ?? 0);
                $course->practical_count = (int) ($topicStat->practical_count ?? 0);
                $course->question_count = (int) ($questionStat->question_count ?? 0);
                $course->enrolled_count = (int) ($enrollmentStat->enrolled_count ?? 0);

                return $course;
            });
        } catch (\Exception $e) {
            Log::error('Fresh featured courses query failed', ['error' => $e->getMessage()]);
            return $this->getMinimalCourses();
        }
    }

    /**
     * BULLETPROOF: Cache components separately for granular recovery
     */
    private function cacheComponentsSeparately(array $data): void
    {
        Cache::put($this->getCacheKey('statistics'), $data['statistics'], 3600);
        Cache::put($this->getCacheKey('featured_courses'), $data['featuredCourses'], 3600);
        Cache::put($this->getCacheKey('health_check'), [
            'last_updated' => now(),
            'data_integrity' => true,
            'version' => self::CACHE_VERSION
        ], self::HEALTH_CHECK_INTERVAL);
    }

    /**
     * BULLETPROOF: Validate data integrity
     */
    private function validateHomeData(array $data): bool
    {
        return isset($data['statistics']) &&
               isset($data['featuredCourses']) &&
               is_array($data['statistics']) &&
               (is_array($data['featuredCourses']) || $data['featuredCourses'] instanceof \Illuminate\Support\Collection);
    }

    /**
     * BULLETPROOF: Emergency fallback data - NEVER FAILS
     */
    private function getEmergencyFallbackData(): array
    {
        // BULLETPROOF: Try to get cached components even if main cache failed
        $statistics = Cache::get($this->getCacheKey('statistics'), []);
        $featuredCourses = Cache::get($this->getCacheKey('featured_courses'), collect([]));

        // BULLETPROOF: If components also failed, return minimal working data
        if (empty($statistics)) {
            $statistics = $this->getMinimalStatistics();
        }

        if ($featuredCourses->isEmpty()) {
            $featuredCourses = $this->getMinimalCourses();
        }

        Log::critical('BULLETPROOF: Emergency fallback activated', [
            'statistics_count' => count($statistics),
            'courses_count' => $featuredCourses->count()
        ]);

        return [
            'statistics' => $statistics,
            'featuredCourses' => $featuredCourses,
            'cache_health' => 'emergency_fallback',
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * BULLETPROOF: Get minimal statistics that always work
     */
    private function getMinimalStatistics(): array
    {
        try {
            return [
                'quizzes' => 0,
                'cases' => 0,
                'practical' => 0,
                'questions' => 0,
                'enrolled_students' => 0
            ];
        } catch (\Exception $e) {
            Log::error('Even minimal statistics failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * BULLETPROOF: Get minimal courses that always work
     */
    private function getMinimalCourses()
    {
        try {
            return collect([]); // Empty collection is better than failure
        } catch (\Exception $e) {
            Log::error('Even minimal courses failed', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * BULLETPROOF: Generate cache key with version for forced invalidation
     */
    private function getCacheKey(string $key): string
    {
        return "home_{$key}_" . self::CACHE_VERSION;
    }

    /**
     * BULLETPROOF: Manually invalidate cache (for admin use or scheduled tasks)
     * This method can be called from admin panels or automated tasks
     */
    public function invalidateCache(): bool
    {
        try {
            // BULLETPROOF: Clear all home page cache keys
            $cacheKeys = [
                $this->getCacheKey('home_data'),
                $this->getCacheKey('statistics'),
                $this->getCacheKey('featured_courses'),
                $this->getCacheKey('health_check'),
                // Also clear old cache keys for good measure
                'home_statistics_nuclear_v3',
                'featured_courses_nuclear_v3'
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }


            return true;
        } catch (\Exception $e) {
            Log::error('Failed to invalidate home page cache', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * BULLETPROOF: Force cache refresh (useful for immediate updates)
     */
    public function forceRefresh(): array
    {
        try {
            $this->invalidateCache();
            return $this->getHomePageData();
        } catch (\Exception $e) {
            Log::error('Force cache refresh failed', ['error' => $e->getMessage()]);
            return $this->getEmergencyFallbackData();
        }
    }

}
