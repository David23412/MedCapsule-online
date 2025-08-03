<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Models\Question;
use App\Models\Topic;

/**
 * Smart Cache Manager
 * 
 * Replaces the nuclear cache clearing approach with intelligent,
 * targeted cache invalidation and warming strategies.
 */
class SmartCacheManager
{
    private $redis;
    private array $cacheHierarchy;
    
    public function __construct()
    {
        $this->redis = \Illuminate\Support\Facades\Redis::connection();
        $this->initializeCacheHierarchy();
    }
    
    /**
     * Initialize cache hierarchy for smart invalidation
     */
    private function initializeCacheHierarchy(): void
    {
        $this->cacheHierarchy = [
            'question' => [
                'pattern' => 'question:{id}:*',
                'children' => ['topic', 'quiz', 'user_progress']
            ],
            'topic' => [
                'pattern' => 'topic:{id}:*',
                'children' => ['course', 'user_progress']
            ],
            'course' => [
                'pattern' => 'course:{id}:*',
                'children' => ['user_enrollment']
            ],
            'user_progress' => [
                'pattern' => 'user_{id}_*',
                'children' => []
            ]
        ];
    }
    
    /**
     * Smart question cache invalidation
     * 
     * @param int $questionId
     * @return void
     */
    public function invalidateQuestionCache(int $questionId): void
    {
        try {
            // Get question details for targeted invalidation
            $question = Question::select('id', 'topic_id', 'source')
                ->find($questionId);
                
            if (!$question) {
                return;
            }
            
            // Calculate affected cache keys
            $affectedKeys = $this->calculateAffectedKeys('question', $questionId, [
                'topic_id' => $question->topic_id,
                'source' => $question->source
            ]);
            
            // Invalidate in batches to prevent Redis overload
            $this->batchInvalidate($affectedKeys);
            
            // Warm critical caches immediately
            $this->warmCriticalQuestionCaches($questionId, $question->topic_id);
            
            Log::info('Smart question cache invalidation completed', [
                'question_id' => $questionId,
                'topic_id' => $question->topic_id,
                'keys_invalidated' => count($affectedKeys)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Smart cache invalidation failed', [
                'question_id' => $questionId,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to targeted clear (not nuclear)
            $this->fallbackInvalidation($questionId);
        }
    }
    
    /**
     * Calculate affected cache keys based on hierarchy
     * 
     * @param string $type
     * @param int $id
     * @param array $context
     * @return array
     */
    private function calculateAffectedKeys(string $type, int $id, array $context = []): array
    {
        $keys = [];
        
        switch ($type) {
            case 'question':
                $keys = [
                    "question:{$id}:attempts",
                    "question:{$id}:correct_rate",
                    "quiz_questions_{$context['topic_id']}_*",
                    "topic_questions_{$context['topic_id']}_count"
                ];
                
                if ($context['source']) {
                    $keys[] = "source_questions_{$context['topic_id']}_{$context['source']}";
                }
                break;
                
            case 'topic':
                $keys = [
                    "topic:{$id}:*",
                    "quiz_questions_{$id}_*",
                    "topic_progress_{$id}_*"
                ];
                break;
                
            case 'user_progress':
                $keys = [
                    "user_{$id}_topic_*_progress_*",
                    "user_{$id}_mastery_stats"
                ];
                break;
        }
        
        return $keys;
    }
    
    /**
     * Batch invalidate cache keys to prevent Redis overload
     * 
     * @param array $keys
     * @return void
     */
    private function batchInvalidate(array $keys): void
    {
        $batchSize = 50; // Process in batches of 50
        $batches = array_chunk($keys, $batchSize);
        
        foreach ($batches as $batch) {
            $pipeline = $this->redis->pipeline();
            
            foreach ($batch as $key) {
                if (strpos($key, '*') !== false) {
                    // Handle wildcard patterns
                    $matchingKeys = $this->redis->keys($key);
                    foreach ($matchingKeys as $matchingKey) {
                        $pipeline->del($matchingKey);
                    }
                } else {
                    $pipeline->del($key);
                }
            }
            
            $pipeline->exec();
            
            // Small delay between batches to prevent Redis overload
            usleep(1000); // 1ms delay
        }
    }
    
    /**
     * Warm critical caches immediately after invalidation
     * 
     * @param int $questionId
     * @param int $topicId
     * @return void
     */
    private function warmCriticalQuestionCaches(int $questionId, int $topicId): void
    {
        try {
            // Warm question count cache
            $questionCount = Question::where('topic_id', $topicId)->count();
            $this->redis->setex("topic_questions_{$topicId}_count", 3600, $questionCount);
            
            // Warm available sources cache
            $sources = Question::where('topic_id', $topicId)
                ->distinct()
                ->pluck('source')
                ->filter()
                ->values()
                ->toArray();
            $this->redis->setex("topic_{$topicId}_sources", 3600, json_encode($sources));
            
        } catch (\Exception $e) {
            Log::warning('Cache warming failed', [
                'question_id' => $questionId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Fallback invalidation for when smart invalidation fails
     * 
     * @param int $questionId
     * @return void
     */
    private function fallbackInvalidation(int $questionId): void
    {
        try {
            $question = Question::find($questionId);
            if (!$question) {
                return;
            }
            
            // Clear only essential keys
            $essentialKeys = [
                "question:{$questionId}:*",
                "quiz_questions_{$question->topic_id}_*",
                "topic_questions_{$question->topic_id}_count"
            ];
            
            foreach ($essentialKeys as $pattern) {
                $keys = $this->redis->keys($pattern);
                if (!empty($keys)) {
                    $this->redis->del(...$keys);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Fallback invalidation failed', [
                'question_id' => $questionId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Smart user cache invalidation
     * 
     * @param int $userId
     * @param int|null $topicId
     * @return void
     */
    public function invalidateUserCache(int $userId, ?int $topicId = null): void
    {
        try {
            $keys = [];
            
            if ($topicId) {
                // Topic-specific user caches
                $keys = [
                    "user_{$userId}_topic_{$topicId}_progress_*",
                    "user_{$userId}_topic_{$topicId}_mistakes"
                ];
            } else {
                // General user caches
                $keys = [
                    "user_{$userId}_mastery_stats",
                    "user_{$userId}_notifications_*"
                ];
            }
            
            $this->batchInvalidate($keys);
            
        } catch (\Exception $e) {
            Log::error('User cache invalidation failed', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Preemptive cache warming for high-traffic data
     * 
     * @param array $priorities
     * @return void
     */
    public function warmHighPriorityCaches(array $priorities = []): void
    {
        try {
            $defaultPriorities = [
                'popular_topics',
                'recent_questions',
                'user_stats'
            ];
            
            $priorities = array_merge($defaultPriorities, $priorities);
            
            foreach ($priorities as $priority) {
                $this->warmCacheByPriority($priority);
            }
            
        } catch (\Exception $e) {
            Log::error('Cache warming failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Warm cache by priority type
     * 
     * @param string $priority
     * @return void
     */
    private function warmCacheByPriority(string $priority): void
    {
        switch ($priority) {
            case 'popular_topics':
                $this->warmPopularTopicsCache();
                break;
                
            case 'recent_questions':
                $this->warmRecentQuestionsCache();
                break;
                
            case 'user_stats':
                $this->warmUserStatsCache();
                break;
        }
    }
    
    /**
     * Warm popular topics cache
     */
    private function warmPopularTopicsCache(): void
    {
        $popularTopics = Topic::withCount('quizAttempts')
            ->orderBy('quiz_attempts_count', 'desc')
            ->limit(20)
            ->get();
            
        $this->redis->setex('popular_topics', 3600, $popularTopics->toJson());
    }
    
    /**
     * Warm recent questions cache
     */
    private function warmRecentQuestionsCache(): void
    {
        $recentQuestions = Question::select('id', 'topic_id', 'source')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
            
        $this->redis->setex('recent_questions', 1800, $recentQuestions->toJson());
    }
    
    /**
     * Warm user stats cache for active users
     */
    private function warmUserStatsCache(): void
    {
        // This would be called during low-traffic periods
        // Implementation depends on your user activity tracking
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getCacheStatistics(): array
    {
        try {
            $info = $this->redis->info('memory');
            
            return [
                'memory_used' => $info['used_memory_human'] ?? 'unknown',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'unknown',
                'cache_hit_rate' => $this->calculateCacheHitRate(),
                'active_keys' => $this->redis->dbsize()
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to retrieve cache statistics',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate cache hit rate (simplified)
     * 
     * @return float
     */
    private function calculateCacheHitRate(): float
    {
        try {
            $info = $this->redis->info('stats');
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            
            if ($hits + $misses === 0) {
                return 0.0;
            }
            
            return round(($hits / ($hits + $misses)) * 100, 2);
            
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}