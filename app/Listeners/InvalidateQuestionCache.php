<?php

namespace App\Listeners;

use App\Events\QuestionDeleted;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidateQuestionCache implements ShouldQueue
{
    use InteractsWithQueue;

    protected $cacheService;

    /**
     * Create the event listener.
     */
    public function __construct(UltraConsolidatedCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the event.
     */
    public function handle(QuestionDeleted $event): void
    {
        try {
            $question = $event->question;
            $topicId = $event->topicId;
            $source = $event->source;

            // Clear question-specific caches
            $this->cacheService->clearQuestionCaches($question->id, $topicId);

            // Clear topic-level quiz caches
            $this->cacheService->clearTopicQuizCaches($topicId);

            // Clear source-specific caches if source is provided
            if ($source) {
                $this->cacheService->clearSourceCaches($topicId, $source);
            }

            Log::info('Question cache invalidated', [
                'question_id' => $question->id,
                'topic_id' => $topicId,
                'source' => $source,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error invalidating question cache', [
                'question_id' => $event->question->id,
                'topic_id' => $event->topicId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 