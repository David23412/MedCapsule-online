<?php

namespace App\Observers;

use App\Models\Question;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class QuestionObserver
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Question "deleted" event.
     */
    public function deleted(Question $question): void
    {
        try {
            Log::info('Question deleted, clearing related caches', [
                'question_id' => $question->id,
                'topic_id' => $question->topic_id,
                'source' => $question->source
            ]);

            // Clear question-specific caches
            $this->cacheService->clearQuestionCaches($question->id, $question->topic_id);
            
            // Clear random quiz caches since deleted questions might be cached there
            $this->cacheService->clearRandomQuizCaches();

        } catch (\Exception $e) {
            Log::error('Error clearing caches after question deletion', [
                'question_id' => $question->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Question "updated" event.
     */
    public function updated(Question $question): void
    {
        try {
            // If the question was marked as deleted, clear caches
            if ($question->wasChanged('deleted') && $question->deleted) {
                Log::info('Question marked as deleted, clearing related caches', [
                    'question_id' => $question->id,
                    'topic_id' => $question->topic_id
                ]);

                $this->cacheService->clearQuestionCaches($question->id, $question->topic_id);
                $this->cacheService->clearRandomQuizCaches();
            }
        } catch (\Exception $e) {
            Log::error('Error clearing caches after question update', [
                'question_id' => $question->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 