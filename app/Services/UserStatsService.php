<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UserStatsService
{
    /**
     * Update user stats after a quiz attempt.
     *
     * @param User $user
     * @param int $questionsAttempted
     * @param int $correctAnswers
     * @param int $durationSeconds
     * @return void
     */
    public function updateAfterQuizAttempt(User $user, int $questionsAttempted, int $correctAnswers, int $durationSeconds): void
    {
        try {
            // Update progress through SSOT service
            $progressService = app(\App\Support\State\UserProgressService::class);
            $progressService->updateProgressAfterQuiz($user->id, $correctAnswers, $questionsAttempted);
            
            // Check for correct answers milestone using SSOT service
            $progressService = app(\App\Support\State\UserProgressService::class);
            $newTotal = $progressService->getTotalCorrectAnswers($user->id);
            $oldTotal = $newTotal - $correctAnswers;
            
            // Check if we crossed a 100-multiple milestone
            $oldMilestone = floor($oldTotal / 100);
            $newMilestone = floor($newTotal / 100);
            
            if ($newMilestone > $oldMilestone) {
                \App\Services\NotificationTriggerService::onCorrectAnswersMilestone($user, $newMilestone * 100);
            }

            // Handle study streak and last study date
            $today = Carbon::today();
            $lastStudyDate = $user->last_study_date ? Carbon::parse($user->last_study_date) : null;

            if (!$lastStudyDate || !$lastStudyDate->isSameDay($today)) {
                // SSOT: Get current streak from UserProgressService for calculation
                $progressService = app(\App\Support\State\UserProgressService::class);
                $currentStreak = $progressService->getUserProgress($user->id)['study_streak_days'];
                
                // Update streak through user model (this will be handled by observers)
                if ($lastStudyDate && $lastStudyDate->diffInDays($today) === 1) {
                    $user->study_streak_days = $currentStreak + 1;
                } else {
                    $user->study_streak_days = 1;
                }
                $user->last_study_date = $today;

                $user->save();
            }

            // SSOT: Get updated streak for logging
            $progressService = app(\App\Support\State\UserProgressService::class);
            $updatedStreak = $progressService->getUserProgress($user->id)['study_streak_days'];

            Log::info('User stats updated successfully', [
                'user_id' => $user->id,
                'questionsAttempted' => $questionsAttempted,
                'correctAnswers' => $correctAnswers,
                'durationSeconds' => $durationSeconds,
                'study_streak_days' => $updatedStreak,
                'last_study_date' => $user->last_study_date,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user stats after quiz attempt', [
                'user_id' => $user->id,
                'questionsAttempted' => $questionsAttempted,
                'correctAnswers' => $correctAnswers,
                'durationSeconds' => $durationSeconds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception to trigger transaction rollback
            throw $e;
        }
    }
} 