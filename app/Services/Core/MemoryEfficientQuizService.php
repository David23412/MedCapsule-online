<?php

namespace App\Services\Core;

use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Memory-Efficient Quiz Service
 * 
 * Replaces the memory-intensive approach of loading entire question
 * collections with streaming, chunked processing, and lazy loading.
 */
class MemoryEfficientQuizService
{
    private const CHUNK_SIZE = 100;
    private const MAX_MEMORY_USAGE = 128 * 1024 * 1024; // 128MB
    
    /**
     * Get questions using lazy loading for memory efficiency
     * 
     * @param Topic $topic
     * @param array $sources
     * @param int|null $limit
     * @return LazyCollection
     */
    public function getQuestionsLazy(Topic $topic, array $sources = ['all'], ?int $limit = null): LazyCollection
    {
        $query = $this->buildQuestionsQuery($topic, $sources);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        // Return lazy collection for memory efficiency
        return $query->lazy(self::CHUNK_SIZE);
    }
    
    /**
     * Process questions in chunks to manage memory
     * 
     * @param Topic $topic
     * @param array $sources
     * @param callable $processor
     * @param int $chunkSize
     * @return array
     */
    public function processQuestionsInChunks(
        Topic $topic, 
        array $sources, 
        callable $processor, 
        int $chunkSize = self::CHUNK_SIZE
    ): array {
        $results = [];
        $processedCount = 0;
        
        $query = $this->buildQuestionsQuery($topic, $sources);
        
        $query->chunk($chunkSize, function (Collection $questions) use ($processor, &$results, &$processedCount) {
            // Check memory usage before processing chunk
            if (memory_get_usage() > self::MAX_MEMORY_USAGE) {
                Log::warning('Memory usage high, triggering garbage collection', [
                    'memory_usage' => memory_get_usage(true),
                    'processed_count' => $processedCount
                ]);
                
                gc_collect_cycles();
            }
            
            // Process chunk
            $chunkResults = $processor($questions);
            $results = array_merge($results, $chunkResults);
            $processedCount += $questions->count();
            
            // Log progress for large datasets
            if ($processedCount % 1000 === 0) {
                Log::info('Question processing progress', [
                    'processed' => $processedCount,
                    'memory_usage' => memory_get_usage(true)
                ]);
            }
        });
        
        return $results;
    }
    
    /**
     * Generate quiz questions with memory management
     * 
     * @param Topic $topic
     * @param array $sources
     * @param int $maxQuestions
     * @param User|null $user
     * @return array
     */
    public function generateQuizQuestionsEfficiently(
        Topic $topic, 
        array $sources = ['all'], 
        int $maxQuestions = 50,
        ?User $user = null
    ): array {
        try {
            // Get total count first to determine strategy
            $totalCount = $this->getQuestionsCount($topic, $sources);
            
            if ($totalCount === 0) {
                return [
                    'success' => false,
                    'message' => 'No questions available for this quiz.',
                    'questions' => collect([])
                ];
            }
            
            // Choose strategy based on dataset size
            if ($totalCount <= $maxQuestions * 2) {
                // Small dataset - load all and shuffle
                $questions = $this->getQuestionsSmallDataset($topic, $sources, $maxQuestions);
            } else {
                // Large dataset - use sampling
                $questions = $this->getQuestionsLargeDataset($topic, $sources, $maxQuestions);
            }
            
            Log::info('Quiz questions generated efficiently', [
                'topic_id' => $topic->id,
                'total_available' => $totalCount,
                'questions_selected' => $questions->count(),
                'memory_usage' => memory_get_usage(true)
            ]);
            
            return [
                'success' => true,
                'questions' => $questions,
                'total_questions' => $questions->count(),
                'sources' => $sources
            ];
            
        } catch (\Exception $e) {
            Log::error('Efficient question generation failed', [
                'topic_id' => $topic->id,
                'error' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true)
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while generating quiz questions.',
                'questions' => collect([])
            ];
        }
    }
    
    /**
     * Get questions count efficiently
     * 
     * @param Topic $topic
     * @param array $sources
     * @return int
     */
    private function getQuestionsCount(Topic $topic, array $sources): int
    {
        $query = Question::where('topic_id', $topic->id)
            ->whereNotNull('question')
            ->where('question', '!=', '');
            
        if (!in_array('all', $sources)) {
            $query->whereIn('source', $sources);
        }
        
        return $query->count();
    }
    
    /**
     * Handle small datasets (load all and shuffle)
     * 
     * @param Topic $topic
     * @param array $sources
     * @param int $maxQuestions
     * @return Collection
     */
    private function getQuestionsSmallDataset(Topic $topic, array $sources, int $maxQuestions): Collection
    {
        $questions = $this->buildQuestionsQuery($topic, $sources)
            ->get()
            ->shuffle()
            ->take($maxQuestions);
            
        return $questions;
    }
    
    /**
     * Handle large datasets (use sampling)
     * 
     * @param Topic $topic
     * @param array $sources
     * @param int $maxQuestions
     * @return Collection
     */
    private function getQuestionsLargeDataset(Topic $topic, array $sources, int $maxQuestions): Collection
    {
        // Use database-level random sampling for large datasets
        $questions = $this->buildQuestionsQuery($topic, $sources)
            ->inRandomOrder()
            ->limit($maxQuestions)
            ->get();
            
        return $questions;
    }
    
    /**
     * Build optimized questions query
     * 
     * @param Topic $topic
     * @param array $sources
     * @return Builder
     */
    private function buildQuestionsQuery(Topic $topic, array $sources): Builder
    {
        $query = Question::select([
                'id', 'topic_id', 'question', 'question_type',
                'option_a', 'option_b', 'option_c', 'option_d',
                'correct_answer', 'explanation', 'image_url', 'source',
                'alternative_answers', 'similarity_threshold'
            ])
            ->where('topic_id', $topic->id)
            ->whereNotNull('question')
            ->where('question', '!=', '');
            
        // Apply source filtering
        if (!in_array('all', $sources)) {
            $query->whereIn('source', $sources);
        }
        
        return $query;
    }
    
    /**
     * Stream questions for real-time processing
     * 
     * @param Topic $topic
     * @param array $sources
     * @param callable $processor
     * @return void
     */
    public function streamQuestions(Topic $topic, array $sources, callable $processor): void
    {
        $this->buildQuestionsQuery($topic, $sources)
            ->lazy(50) // Process 50 at a time
            ->each(function ($question) use ($processor) {
                $processor($question);
                
                // Memory management
                if (memory_get_usage() > self::MAX_MEMORY_USAGE) {
                    gc_collect_cycles();
                }
            });
    }
    
    /**
     * Get random questions with memory efficiency
     * 
     * @param User $user
     * @param int $questionCount
     * @param array $options
     * @return array
     */
    public function generateRandomQuizEfficiently(User $user, int $questionCount = 10, array $options = []): array
    {
        try {
            $courseIds = $options['course_ids'] ?? [];
            $sources = $options['sources'] ?? ['all'];
            $excludeTopics = $options['exclude_topics'] ?? [];
            
            // Build base query
            $query = Question::select([
                    'id', 'topic_id', 'question', 'question_type',
                    'option_a', 'option_b', 'option_c', 'option_d',
                    'correct_answer', 'explanation', 'image_url', 'source',
                    'alternative_answers', 'similarity_threshold'
                ])
                ->whereNotNull('question')
                ->where('question', '!=', '');
            
            // Apply filters
            if (!empty($courseIds)) {
                $query->whereHas('topic', function($q) use ($courseIds) {
                    $q->whereIn('course_id', $courseIds);
                });
            }
            
            if (!in_array('all', $sources)) {
                $query->whereIn('source', $sources);
            }
            
            if (!empty($excludeTopics)) {
                $query->whereNotIn('topic_id', $excludeTopics);
            }
            
            // Get total count for strategy decision
            $totalCount = $query->count();
            
            if ($totalCount === 0) {
                return [
                    'success' => false,
                    'message' => 'No questions available for the specified criteria.',
                    'questions' => []
                ];
            }
            
            // Use efficient sampling
            $questions = $query->inRandomOrder()
                ->limit($questionCount)
                ->get();
            
            Log::info('Random quiz generated efficiently', [
                'user_id' => $user->id,
                'total_available' => $totalCount,
                'questions_selected' => $questions->count(),
                'memory_usage' => memory_get_usage(true)
            ]);
            
            return [
                'success' => true,
                'questions' => $questions,
                'total_questions' => $questions->count(),
                'topics_covered' => $questions->pluck('topic_id')->unique()->values()->toArray()
            ];
            
        } catch (\Exception $e) {
            Log::error('Efficient random quiz generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true)
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while generating the random quiz.',
                'questions' => []
            ];
        }
    }
    
    /**
     * Monitor memory usage during operations
     * 
     * @param string $operation
     * @return array
     */
    public function getMemoryStats(string $operation = 'unknown'): array
    {
        return [
            'operation' => $operation,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'memory_percentage' => round((memory_get_usage(true) / $this->parseMemoryLimit()) * 100, 2)
        ];
    }
    
    /**
     * Parse memory limit string to bytes
     * 
     * @return int
     */
    private function parseMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $limit;
        }
    }
    
    /**
     * Cleanup memory and optimize garbage collection
     * 
     * @return void
     */
    public function optimizeMemory(): void
    {
        // Force garbage collection
        gc_collect_cycles();
        
        // Log memory status
        Log::info('Memory optimization completed', [
            'memory_usage_before' => memory_get_usage(true),
            'memory_usage_after' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]);
    }
}