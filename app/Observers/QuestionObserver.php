<?php

namespace App\Observers;

use App\Models\Question;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QuestionObserver
{
    protected $cacheService;

    public function __construct(UltraConsolidatedCacheService $cacheService)
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

            // Bump quiz question cache version for this topic so quiz pages reflect changes immediately
            Cache::increment("quiz_questions_version:topic_{$question->topic_id}");

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
            // Any update to the question content/options should invalidate quiz question caches for the topic
            if ($question->wasChanged([
                'question',
                'question_type',
                'option_a',
                'option_b',
                'option_c',
                'option_d',
                'correct_answer',
                'explanation',
                'image_url',
                'source',
            ])) {
                Cache::increment("quiz_questions_version:topic_{$question->topic_id}");
            }

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

    /**
     * Handle the Question "created" event.
     */
    public function created(Question $question): void
    {
        try {
            Cache::increment("quiz_questions_version:topic_{$question->topic_id}");
        } catch (\Exception $e) {
            Log::error('Error bumping quiz cache version after question creation', [
                'question_id' => $question->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 