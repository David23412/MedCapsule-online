<?php

namespace App\Listeners;

use App\Events\QuizCompleted;
use App\Services\UserStatsService;
use App\Services\NotificationService;
use App\Services\Core\UltraConsolidatedCacheService;
use App\Services\StudyGamePlanService;
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
    protected UltraConsolidatedCacheService $cacheService;
    protected StudyGamePlanService $studyGamePlanService;

    /**
     * Create the event listener.
     */
    public function __construct(
        UserStatsService $userStatsService,
        NotificationService $notificationService,
        UltraConsolidatedCacheService $cacheService,
        StudyGamePlanService $studyGamePlanService
    ) {
        $this->userStatsService = $userStatsService;
        $this->notificationService = $notificationService;
        $this->cacheService = $cacheService;
        $this->studyGamePlanService = $studyGamePlanService;
    }

    /**
     * Handle the event.
     */
    public function handle(QuizCompleted $event): void
    {
        try {
            // Process in parallel using separate jobs
            $this->processNotifications($event);
            $this->processCacheInvalidation($event);

            // Sync planner state: apply transitions and rotate tasks if needed.
            if ($event->course) {
                $this->studyGamePlanService->syncAfterSession($event->user, $event->course);
            }
            
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
     * Process notifications
     */
    private function processNotifications(QuizCompleted $event): void
    {
        try {
            $percentage = $event->gradingResult['percentage_grade'];
            
            // LETHAL FIX: Use new notification system for topic mastery
            if ($percentage >= 90) {
                \App\Services\NotificationTriggerService::onTopicMastery(
                    $event->user,
                    $event->topic->name,
                    $event->course->name ?? null
                );
                
                // Check for course mastery after topic mastery
                if ($event->course) {
                    $this->checkForCourseMastery($event->user, $event->course);
                }
            }
            
            // LETHAL FIX: Use new notification system for level up
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
     * Check for level up
     */
    private function checkForLevelUp($user): void
    {
        try {
            $currentLevel = $user->getCurrentLevel();
            $newLevel = floor($user->xp / 1000) + 1;
            
            if ($newLevel > $currentLevel) {
                \App\Services\NotificationTriggerService::onLevelUp(
                    $user, 
                    $newLevel, 
                    'quiz_completion'
                );
            }
            
        } catch (\Exception $e) {
            Log::error('Level up check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check for course mastery after topic mastery
     */
    private function checkForCourseMastery($user, $course): void
    {
        try {
            // Check if user has mastered all topics in this course
            $totalTopics = $course->topics()->count();
            $masteredTopics = \App\Models\UserTopicProgress::where('user_id', $user->id)
                ->whereIn('topic_id', $course->topics()->pluck('id'))
                ->whereNotNull('planner_cleared_at')
                ->count();
                
            if ($totalTopics > 0 && $masteredTopics >= $totalTopics) {
                // Check if we already sent a mastery notification for this course
                $existingNotification = $user->notifications()
                    ->where('type', 'course_mastery')
                    ->where('data->course_id', $course->id)
                    ->exists();
                    
                if (!$existingNotification) {
                    // Trigger course mastery notification
                    \App\Services\NotificationTriggerService::onCourseMastery($user, $course->name);
                    
                }
            }
        } catch (\Exception $e) {
            Log::error('Course mastery check failed', [
                'user_id' => $user->id,
                'course_id' => $course->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
