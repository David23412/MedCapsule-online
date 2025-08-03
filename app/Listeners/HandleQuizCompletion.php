<?php

namespace App\Listeners;

use App\Events\QuizCompleted;
use App\Services\UserStatsService;
use App\Services\NotificationService;
use App\Services\CacheService;
use App\Models\Mistake;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handle quiz completion side effects asynchronously
 * 
 * This listener handles all the side effects that were previously
 * done synchronously in the grading service, causing performance issues.
 */
class HandleQuizCompletion implements ShouldQueue
{
    use InteractsWithQueue;

    protected UserStatsService $userStatsService;
    protected NotificationService $notificationService;
    protected CacheService $cacheService;

    /**
     * Create the event listener.
     */
    public function __construct(
        UserStatsService $userStatsService,
        NotificationService $notificationService,
        CacheService $cacheService
    ) {
        $this->userStatsService = $userStatsService;
        $this->notificationService = $notificationService;
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the event.
     */
    public function handle(QuizCompleted $event): void
    {
        try {
            // Process in parallel using separate jobs
            $this->processUserStats($event);
            $this->processMistakes($event);
            $this->processNotifications($event);
            $this->processCacheInvalidation($event);
            
        } catch (\Exception $e) {
            Log::error('Quiz completion processing failed', [
                'user_id' => $event->user->id,
                'topic_id' => $event->topic->id,
                'quiz_attempt_id' => $event->quizAttempt->id,
                'error' => $e->getMessage()
            ]);
            
            // Don't fail the main quiz grading if side effects fail
            // Log and continue
        }
    }

    /**
     * Process user statistics update
     */
    private function processUserStats(QuizCompleted $event): void
    {
        try {
            $this->userStatsService->updateAfterQuizAttempt(
                $event->user,
                $event->gradingResult['total_questions'],
                $event->gradingResult['score'],
                $event->quizAttempt->duration_seconds
            );
            
            // Calculate and award XP
            $this->calculateAndAwardXP($event);
            
        } catch (\Exception $e) {
            Log::error('User stats update failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process mistakes recording
     */
    private function processMistakes(QuizCompleted $event): void
    {
        try {
            $incorrectAnswers = collect($event->gradingResult['results'])
                ->where('is_correct', false);
            
            foreach ($incorrectAnswers as $result) {
                Mistake::updateOrCreate(
                    [
                        'user_id' => $event->user->id,
                        'question_id' => $result['question_id']
                    ],
                    [
                        'submitted_answer' => $result['submitted_answer'] ?? 'unanswered',
                        'last_attempt_date' => now()
                    ]
                );
            }
            
        } catch (\Exception $e) {
            Log::error('Mistakes processing failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process notifications
     */
    private function processNotifications(QuizCompleted $event): void
    {
        try {
            $percentage = $event->gradingResult['percentage_grade'];
            
            // Check for achievements and send notifications
            if ($percentage >= 90) {
                $this->notificationService->createTopicMasteryNotification(
                    $event->user,
                    $event->topic->name
                );
            }
            
            // Check for level up
            $this->checkForLevelUp($event->user);
            
        } catch (\Exception $e) {
            Log::error('Notifications processing failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process cache invalidation
     */
    private function processCacheInvalidation(QuizCompleted $event): void
    {
        try {
            // Smart cache invalidation - only clear what's necessary
            $this->cacheService->invalidateUserCaches(
                $event->user->id, 
                $event->topic->id
            );
            
            // Clear topic progress caches
            $this->cacheService->clearTopicProgressCaches(
                $event->topic->id,
                $event->user->id,
                $event->quizAttempt->study_mode
            );
            
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', [
                'user_id' => $event->user->id,
                'topic_id' => $event->topic->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate and award XP
     */
    private function calculateAndAwardXP(QuizCompleted $event): void
    {try {$score = $event->gradingResult['score'];
            $duration = $event->quizAttempt->duration_seconds;
            $percentage = $event->gradingResult['percentage_grade'];
            
            // Calculate XP
            $baseXP = $score * 10;
            $timeBonus = max(0, 50 - floor($duration / 60));
            $accuracyBonus = floor($percentage / 10) * 5;
            $earnedXP = $baseXP + $timeBonus + $accuracyBonus;
            
            // Award XP
            $event->user->increment('xp', $earnedXP);
            
        } catch (\Exception $e) {
            Log::error('XP calculation failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check for level up
     */
    private function checkForLevelUp($user): void
    {
        try {
            $currentLevel = $user->getCurrentLevel();
            $newLevel = floor($user->xp / 1000) + 1;
            
            if ($newLevel > $currentLevel) {
                $this->notificationService->createLevelUpNotification(
                    $user, 
                    $newLevel, 
                    $user->xp
                );
            }
            
        } catch (\Exception $e) {
            Log::error('Level up check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}