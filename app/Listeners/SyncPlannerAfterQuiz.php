<?php

namespace App\Listeners;

use App\Events\QuizCompleted;
use App\Services\StudyGamePlanService;
use Illuminate\Support\Facades\Log;

class SyncPlannerAfterQuiz
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected StudyGamePlanService $studyGamePlanService
    ) {}

    /**
     * Handle the event synchronously.
     */
    public function handle(QuizCompleted $event): void
    {
        try {
            $studyMode = $event->quizAttempt->study_mode ?? '';
            $sessionType = $event->quizAttempt->session_type ?? '';
            
            // Random quiz never advances planner state
            if ($studyMode === 'random' || $sessionType === 'random_quiz') {
                return; 
            }

            // Preview frontier_topic_quiz tasks MUST still trigger stage transition
            // evaluation — do NOT early-return for preview tasks. The downstream
            // applyPlannerStageTransitions() already handles preview vs. progression
            // correctly by evaluating frontier_topic_quiz regardless of is_preview flag.

            // Fallback to topic->course() if event->course is not explicitly set
            $course = $event->course ?? $event->topic->course()->first();
            if (!$course) {
                return;
            }

            $this->studyGamePlanService->syncAfterSession($event->user, $course);
        } catch (\Exception $e) {
            Log::error('Synchronous planner sync after quiz completion failed', [
                'user_id' => $event->user->id,
                'topic_id' => $event->topic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
