<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{User, Question, Topic, Course, QuizAttempt, Mistake};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UltimatePerformanceService
{
    private const CACHE_TTL = 900; // 15 minutes
    private const MEMORY_LIMIT_MB = 512;
    private const CHUNK_SIZE = 1000;

    /**
     * 🚀 OPTIMIZED MISTAKES LOADING
     * Fixes N+1 query problem in mistakes view
     */
    public function getOptimizedMistakes(int $userId, ?int $courseFilter = null, int $perPage = 50): LengthAwarePaginator
    {
        $cacheKey = "optimized_mistakes_{$userId}_{$courseFilter}_{$perPage}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $courseFilter, $perPage) {
            // SINGLE OPTIMIZED QUERY WITH PROPER EAGER LOADING
            $query = Mistake::select([
                'mistakes.id',
                'mistakes.user_id', 
                'mistakes.question_id',
                'mistakes.submitted_answer',
                'mistakes.attempt_count',
                'mistakes.last_attempt_date',
                'mistakes.created_at'
            ])
            ->with([
                'question' => function ($query) {
                    $query->select(['id', 'question', 'topic_id', 'correct_answer', 'explanation', 'image_url'])
                          ->without(['mistakes']); // Prevent reverse relationship loading
                },
                'question.topic' => function ($query) {
                    $query->select(['id', 'name', 'course_id'])
                          ->without(['questions']); // Prevent loading all topic questions
                },
                'question.topic.course' => function ($query) {
                    $query->select(['id', 'name'])
                          ->without(['topics']); // Prevent loading all course topics
                }
            ])
            ->where('mistakes.user_id', $userId);

            // OPTIMIZED COURSE FILTERING
            if ($courseFilter) {
                $query->whereHas('question.topic.course', function ($q) use ($courseFilter) {
                    $q->where('id', $courseFilter);
                });
            }

            // OPTIMIZED ORDERING WITH INDEX
            $query->orderBy('mistakes.last_attempt_date', 'desc')
                  ->orderBy('mistakes.id', 'desc'); // Secondary sort for consistency

            return $query->paginate($perPage);
        });
    }

    /**
     * 🚀 ULTRA-FAST USER STATISTICS
     * Cached and optimized user performance metrics
     */
    public function getOptimizedUserStats(int $userId): array
    {
        $cacheKey = "user_stats_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            // SINGLE QUERY FOR ALL USER STATS
            $stats = DB::select("
                SELECT 
                    u.correct_answers_count,
                    u.total_questions_attempted,
                    u.xp,
                    u.study_streak_days,
                    u.study_time_seconds,
                    COUNT(DISTINCT qa.id) as total_quiz_attempts,
                    COUNT(DISTINCT m.id) as total_mistakes,
                    COALESCE(AVG(qa.score), 0) as average_score,
                    COUNT(DISTINCT qa.topic_id) as topics_attempted,
                    MAX(qa.created_at) as last_quiz_date
                FROM users u
                LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
                LEFT JOIN mistakes m ON u.id = m.user_id
                WHERE u.id = ?
                GROUP BY u.id
            ", [$userId]);

            if (empty($stats)) {
                return $this->getDefaultUserStats();
            }

            $stat = $stats[0];
            
            // CALCULATE DERIVED METRICS
            $accuracyRate = $stat->total_questions_attempted > 0 
                ? ($stat->correct_answers_count / $stat->total_questions_attempted) * 100 
                : 0;

            $currentLevel = floor($stat->xp / 1000) + 1;
            $xpForNextLevel = (ceil($stat->xp / 1000) * 1000) - $stat->xp;

            return [
                'correct_answers' => (int) $stat->correct_answers_count,
                'total_questions' => (int) $stat->total_questions_attempted,
                'accuracy_rate' => round($accuracyRate, 1),
                'xp' => (int) $stat->xp,
                'current_level' => $currentLevel,
                'xp_for_next_level' => $xpForNextLevel,
                'study_streak' => (int) $stat->study_streak_days,
                'study_time_formatted' => $this->formatStudyTime($stat->study_time_seconds),
                'total_quiz_attempts' => (int) $stat->total_quiz_attempts,
                'total_mistakes' => (int) $stat->total_mistakes,
                'average_score' => round($stat->average_score, 1),
                'topics_attempted' => (int) $stat->topics_attempted,
                'last_quiz_date' => $stat->last_quiz_date
            ];
        });
    }

    /**
     * 🚀 OPTIMIZED TOPIC PERFORMANCE
     * Get topic performance with minimal queries
     */
    public function getTopicPerformance(int $userId, int $topicId): array
    {
        $cacheKey = "topic_performance_{$userId}_{$topicId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $topicId) {
            // SINGLE OPTIMIZED QUERY FOR TOPIC PERFORMANCE
            $performance = DB::select("
                SELECT 
                    t.name as topic_name,
                    t.percentage_grade,
                    COUNT(DISTINCT qa.id) as quiz_attempts,
                    COALESCE(AVG(qa.score), 0) as average_score,
                    COALESCE(MAX(qa.score), 0) as best_score,
                    COUNT(DISTINCT m.id) as mistake_count,
                    COUNT(DISTINCT q.id) as total_questions,
                    SUM(CASE WHEN qa.score >= 70 THEN 1 ELSE 0 END) as passing_attempts,
                    MAX(qa.created_at) as last_attempt_date
                FROM topics t
                LEFT JOIN questions q ON t.id = q.topic_id
                LEFT JOIN quiz_attempts qa ON t.id = qa.topic_id AND qa.user_id = ?
                LEFT JOIN mistakes m ON m.user_id = ? AND m.question_id = q.id
                WHERE t.id = ?
                GROUP BY t.id, t.name, t.percentage_grade
            ", [$userId, $userId, $topicId]);

            if (empty($performance)) {
                return $this->getDefaultTopicPerformance();
            }

            $perf = $performance[0];
            
            return [
                'topic_name' => $perf->topic_name,
                'current_grade' => (float) $perf->percentage_grade,
                'quiz_attempts' => (int) $perf->quiz_attempts,
                'average_score' => round($perf->average_score, 1),
                'best_score' => (int) $perf->best_score,
                'mistake_count' => (int) $perf->mistake_count,
                'total_questions' => (int) $perf->total_questions,
                'passing_attempts' => (int) $perf->passing_attempts,
                'pass_rate' => $perf->quiz_attempts > 0 ? round(($perf->passing_attempts / $perf->quiz_attempts) * 100, 1) : 0,
                'mastery_level' => $this->calculateMasteryLevel($perf),
                'last_attempt_date' => $perf->last_attempt_date
            ];
        });
    }

    /**
     * 🚀 MEMORY-OPTIMIZED BULK OPERATIONS
     * Process large datasets without memory exhaustion
     */
    public function processLargeDataset(string $model, callable $processor, array $constraints = []): array
    {
        $modelClass = "App\\Models\\{$model}";
        $results = [];
        $processedCount = 0;
        $memoryPeak = 0;

        // CHUNK PROCESSING TO PREVENT MEMORY EXHAUSTION
        $query = $modelClass::query();
        
        foreach ($constraints as $field => $value) {
            $query->where($field, $value);
        }

        $query->chunk(self::CHUNK_SIZE, function ($records) use ($processor, &$results, &$processedCount, &$memoryPeak) {
            // MONITOR MEMORY USAGE
            $memoryBefore = memory_get_usage(true);
            
            foreach ($records as $record) {
                $result = $processor($record);
                if ($result !== null) {
                    $results[] = $result;
                }
                $processedCount++;
                
                // MEMORY SAFETY CHECK
                $currentMemory = memory_get_usage(true) / 1024 / 1024; // MB
                $memoryPeak = max($memoryPeak, $currentMemory);
                
                if ($currentMemory > self::MEMORY_LIMIT_MB) {
                    Log::warning('Memory usage approaching limit', [
                        'current_mb' => $currentMemory,
                        'limit_mb' => self::MEMORY_LIMIT_MB,
                        'processed_count' => $processedCount
                    ]);
                    
                    // FORCE GARBAGE COLLECTION
                    gc_collect_cycles();
                }
            }
            
            // CLEAR MEMORY AFTER EACH CHUNK
            unset($records);
            gc_collect_cycles();
        });

        return [
            'results' => $results,
            'processed_count' => $processedCount,
            'memory_peak_mb' => round($memoryPeak, 2)
        ];
    }

    /**
     * 🚀 INTELLIGENT CACHE WARMING
     * Pre-load frequently accessed data
     */
    public function warmCriticalCaches(int $userId): void
    {
        $startTime = microtime(true);
        
        try {
            // WARM USER STATISTICS CACHE
            $this->getOptimizedUserStats($userId);
            
            // WARM USER'S ACTIVE COURSES CACHE
            $user = User::find($userId);
            if ($user) {
                $activeCourses = $user->courses()->pluck('id');
                
                foreach ($activeCourses as $courseId) {
                    // WARM COURSE TOPICS CACHE
                    $cacheKey = "course_topics_{$courseId}";
                    Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId) {
                        return Topic::where('course_id', $courseId)
                                   ->select(['id', 'name', 'course_id', 'order', 'percentage_grade'])
                                   ->orderBy('order')
                                   ->get();
                    });
                }
                
                // WARM RECENT MISTAKES CACHE (first page only)
                $this->getOptimizedMistakes($userId, null, 20);
            }
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Cache warming completed', [
                'user_id' => $userId,
                'execution_time_ms' => round($executionTime, 2),
                'caches_warmed' => ['user_stats', 'course_topics', 'recent_mistakes']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Cache warming failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 🚀 SMART CACHE INVALIDATION
     * Invalidate related caches intelligently
     */
    public function invalidateRelatedCaches(string $event, array $data): void
    {
        try {
            switch ($event) {
                case 'quiz_completed':
                    $userId = $data['user_id'];
                    $topicId = $data['topic_id'];
                    
                    // INVALIDATE USER-SPECIFIC CACHES
                    Cache::forget("user_stats_{$userId}");
                    Cache::forget("topic_performance_{$userId}_{$topicId}");
                    Cache::forget("optimized_mistakes_{$userId}*"); // Pattern invalidation
                    
                    // INVALIDATE COURSE-LEVEL CACHES
                    $topic = Topic::find($topicId);
                    if ($topic) {
                        Cache::forget("course_topics_{$topic->course_id}");
                    }
                    break;
                    
                case 'user_updated':
                    $userId = $data['user_id'];
                    Cache::forget("user_stats_{$userId}");
                    Cache::forget("optimized_mistakes_{$userId}*");
                    break;
                    
                case 'question_updated':
                    $questionId = $data['question_id'];
                    $question = Question::find($questionId);
                    if ($question) {
                        Cache::forget("course_topics_{$question->topic->course_id}");
                        // INVALIDATE ALL TOPIC PERFORMANCE CACHES
                        Cache::tags(['topic_performance', "topic_{$question->topic_id}"])->flush();
                    }
                    break;
            }
            
            Log::info('Cache invalidation completed', [
                'event' => $event,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper: Format study time
     */
    private function formatStudyTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }

    /**
     * Helper: Calculate mastery level
     */
    private function calculateMasteryLevel($performance): string
    {
        $grade = (float) $performance->percentage_grade;
        $attempts = (int) $performance->quiz_attempts;
        
        if ($grade >= 90 && $attempts >= 3) return 'Expert';
        if ($grade >= 80 && $attempts >= 2) return 'Proficient';
        if ($grade >= 70 && $attempts >= 1) return 'Competent';
        if ($grade >= 60) return 'Developing';
        
        return 'Beginner';
    }

    /**
     * Helper: Default user stats
     */
    private function getDefaultUserStats(): array
    {
        return [
            'correct_answers' => 0,
            'total_questions' => 0,
            'accuracy_rate' => 0.0,
            'xp' => 0,
            'current_level' => 1,
            'xp_for_next_level' => 1000,
            'study_streak' => 0,
            'study_time_formatted' => '0m',
            'total_quiz_attempts' => 0,
            'total_mistakes' => 0,
            'average_score' => 0.0,
            'topics_attempted' => 0,
            'last_quiz_date' => null
        ];
    }

    /**
     * Helper: Default topic performance
     */
    private function getDefaultTopicPerformance(): array
    {
        return [
            'topic_name' => 'Unknown Topic',
            'current_grade' => 0.0,
            'quiz_attempts' => 0,
            'average_score' => 0.0,
            'best_score' => 0,
            'mistake_count' => 0,
            'total_questions' => 0,
            'passing_attempts' => 0,
            'pass_rate' => 0.0,
            'mastery_level' => 'Beginner',
            'last_attempt_date' => null
        ];
    }
}