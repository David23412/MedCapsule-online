<?php

namespace App\Services;

use App\Events\StudyStreakUpdated;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
    public function updateAfterQuizAttempt(User $user, int $questionsAttempted, int $correctAnswers, int $durationSeconds, int $xpEarned = 0): void
    {
        try {
            // Atomic single-statement counter update (reduces write fan-out in hot grading path)
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'total_questions_attempted' => DB::raw('total_questions_attempted + ' . (int) $questionsAttempted),
                    'correct_answers_count' => DB::raw('correct_answers_count + ' . (int) $correctAnswers),
                    'xp' => DB::raw('xp + ' . (int) $xpEarned),
                    'updated_at' => now(),
                ]);

            // Keep in-memory model in sync for downstream calculations in this request lifecycle
            $user->total_questions_attempted = (int) ($user->total_questions_attempted ?? 0) + $questionsAttempted;
            $user->correct_answers_count = (int) ($user->correct_answers_count ?? 0) + $correctAnswers;
            $user->xp = (int) ($user->xp ?? 0) + $xpEarned;
            
            // LETHAL FIX: Check for correct answers milestone (every 100 correct answers)
            $newTotal = (int) $user->correct_answers_count;
            $oldTotal = max(0, $newTotal - $correctAnswers);
            
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
                $previousStreak = (int) ($user->study_streak_days ?? 0);

                // If last study was yesterday, increment streak; otherwise, reset to 1
                if ($lastStudyDate && $lastStudyDate->diffInDays($today) === 1) {
                    $user->study_streak_days = ($user->study_streak_days ?? 0) + 1;
                } else {
                    $user->study_streak_days = 1;
                }

                $newStreak = (int) $user->study_streak_days;
                $user->last_study_date = $today;

                $user->save();

                // Trigger event-driven streak notifications when streak actually changes.
                if ($newStreak !== $previousStreak) {
                    event(new StudyStreakUpdated($user, $newStreak, $previousStreak));
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to update user stats after quiz attempt', [
                'user_id' => $user->id,
                'questionsAttempted' => $questionsAttempted,
                'correctAnswers' => $correctAnswers,
                'durationSeconds' => $durationSeconds,
                'xpEarned' => $xpEarned,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception to trigger transaction rollback
            throw $e;
        }
    }
} 