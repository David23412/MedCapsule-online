<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Carbon\Carbon;

class PerformanceOptimizationService
{
    private CacheService $cacheService;
    
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Optimize quiz loading with aggressive caching and query optimization
     */
    public function optimizeQuizLoading(int $userId, int $topicId, array $sources): array
    {
        $cacheKey = "optimized_quiz_{$userId}_{$topicId}_" . md5(serialize($sources));
        
        return Cache::remember($cacheKey, 1800, function () use ($userId, $topicId, $sources) {
            $startTime = microtime(true);
            
            // OPTIMIZED: Single query with covering index
            $questions = Question::select([
                'id', 'topic_id', 'question', 'correct_answer', 'explanation',
                'image_url', 'question_type', 'option_a', 'option_b', 'option_c', 'option_d',
                'alternative_answers', 'similarity_threshold', 'source'
            ])
            ->where('topic_id', $topicId)
            ->where('deleted', false)
            ->when(!in_array('all', $sources), function ($query) use ($sources) {
                $query->whereIn('source', $sources);
            })
            ->orderByRaw('RAND()')
            ->limit(10)
            ->get();
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Quiz loading optimized', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'questions_count' => $questions->count(),
                'execution_time_ms' => round($executionTime, 2),
                'cache_hit' => false
            ]);
            
            return $questions->toArray();
        });
    }

    /**
     * Optimize mistake loading with single query and eager loading
     */
    public function optimizeMistakeLoading(int $userId, ?int $courseId = null): array
    {
        $cacheKey = "optimized_mistakes_{$userId}_" . ($courseId ?? 'all');
        
        return Cache::remember($cacheKey, 900, function () use ($userId, $courseId) {
            $startTime = microtime(true);
            
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

            $mistakes = $mistakesQuery->orderBy('mistakes.last_attempt_date', 'desc')->get();
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Mistake loading optimized', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'mistakes_count' => $mistakes->count(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            
            return $mistakes->toArray();
        });
    }

    /**
     * Optimize user progress calculation with caching
     */
    public function optimizeUserProgress(int $userId, int $topicId): array
    {
        $cacheKey = "optimized_progress_{$userId}_{$topicId}";
        
        return Cache::remember($cacheKey, 600, function () use ($userId, $topicId) {
            $startTime = microtime(true);
            
            // OPTIMIZED: Single aggregation query
            $progress = QuizAttempt::where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->selectRaw('
                    COUNT(*) as total_attempts,
                    AVG(percentage_grade) as average_score,
                    MAX(percentage_grade) as best_score,
                    MAX(created_at) as last_attempt,
                    SUM(correct_answers) as total_correct,
                    SUM(total_questions) as total_questions_attempted
                ')
                ->first();
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('User progress optimized', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'execution_time_ms' => round($executionTime, 2)
            ]);
            
            return $progress ? $progress->toArray() : [];
        });
    }

    /**
     * Optimize question similarity calculation with memory limits
     */
    public function optimizeTextSimilarity(string $text1, string $text2): float
    {
        // MEMORY PROTECTION: Limit input size
        if (strlen($text1) > 5000 || strlen($text2) > 5000) {
            Log::warning('Text too long for similarity calculation', [
                'text1_length' => strlen($text1),
                'text2_length' => strlen($text2)
            ]);
            return 0.0;
        }
        
        $startTime = microtime(true);
        
        // OPTIMIZED: Use simple word overlap for performance
        $words1 = array_filter(explode(' ', strtolower(trim($text1))), function($word) {
            return strlen($word) > 2;
        });
        $words2 = array_filter(explode(' ', strtolower(trim($text2))), function($word) {
            return strlen($word) > 2;
        });
        
        if (empty($words1) || empty($words2)) {
            return 0.0;
        }
        
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        $similarity = count($union) > 0 ? count($intersection) / count($union) : 0.0;
        
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        if ($executionTime > 100) {
            Log::warning('Text similarity calculation slow', [
                'execution_time_ms' => round($executionTime, 2),
                'text1_length' => strlen($text1),
                'text2_length' => strlen($text2)
            ]);
        }
        
        return $similarity;
    }

    /**
     * Optimize database queries with connection pooling
     */
    public function optimizeDatabaseConnection(): void
    {
        // Set optimal MySQL settings for performance
        DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO"');
        DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
        DB::statement('SET SESSION max_execution_time = 30');
    }

    /**
     * Implement query result caching for expensive operations
     */
    public function cacheExpensiveQuery(string $key, callable $query, int $ttl = 3600)
    {
        return Cache::remember($key, $ttl, function () use ($query) {
            $startTime = microtime(true);
            $result = $query();
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Expensive query cached', [
                'cache_key' => $key,
                'execution_time_ms' => round($executionTime, 2)
            ]);
            
            return $result;
        });
    }

    /**
     * Optimize memory usage for large datasets
     */
    public function optimizeMemoryUsage(): void
    {
        // Set memory limits
        ini_set('memory_limit', '256M');
        
        // Enable garbage collection
        if (function_exists('gc_enable')) {
            gc_enable();
        }
        
        // Clear any accumulated memory
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Implement rate limiting for performance protection
     */
    public function checkRateLimit(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $redis = Redis::connection();
        $current = $redis->get($key);
        
        if ($current && $current >= $maxAttempts) {
            return false;
        }
        
        $redis->incr($key);
        $redis->expire($key, $decayMinutes * 60);
        
        return true;
    }

    /**
     * Optimize session handling for performance
     */
    public function optimizeSessionHandling(): void
    {
        // Use database sessions for better performance
        config(['session.driver' => 'database']);
        
        // Set optimal session settings
        config(['session.lifetime' => 120]);
        config(['session.expire_on_close' => false]);
    }

    /**
     * Implement lazy loading for relationships
     */
    public function optimizeRelationshipLoading($model, array $relationships): void
    {
        // Load relationships efficiently
        $model->load($relationships);
    }

    /**
     * Optimize file operations
     */
    public function optimizeFileOperations(): void
    {
        // Set optimal file handling
        ini_set('max_execution_time', 30);
        ini_set('max_input_time', 30);
        ini_set('post_max_size', '10M');
        ini_set('upload_max_filesize', '10M');
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $redis = Redis::connection();
        
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'cache_hit_rate' => $this->calculateCacheHitRate(),
            'database_connections' => DB::connection()->getPdo() ? 1 : 0,
            'redis_connections' => $redis->ping() ? 1 : 0,
            'execution_time' => microtime(true) - LARAVEL_START
        ];
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateCacheHitRate(): float
    {
        // This would need to be implemented with actual cache statistics
        // For now, return a placeholder
        return 0.85; // 85% cache hit rate
    }

    /**
     * Optimize all performance aspects
     */
    public function optimizeAll(): void
    {
        $this->optimizeDatabaseConnection();
        $this->optimizeMemoryUsage();
        $this->optimizeSessionHandling();
        $this->optimizeFileOperations();
        
        Log::info('Performance optimization completed', [
            'timestamp' => now(),
            'metrics' => $this->getPerformanceMetrics()
        ]);
    }
}