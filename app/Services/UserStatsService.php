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
        // Atomic increments for performance and safety
        $user->increment('total_questions_attempted', $questionsAttempted);
        $user->increment('correct_answers_count', $correctAnswers);
        $user->increment('study_time_seconds', $durationSeconds);

        // Handle study streak and last study date
        $today = Carbon::today();
        $lastStudyDate = $user->last_study_date ? Carbon::parse($user->last_study_date) : null;

        if (!$lastStudyDate || !$lastStudyDate->isSameDay($today)) {
            // If last study was yesterday, increment streak; otherwise, reset to 1
            if ($lastStudyDate && $lastStudyDate->diffInDays($today) === 1) {
                $user->study_streak_days = ($user->study_streak_days ?? 0) + 1;
            } else {
                $user->study_streak_days = 1;
            }
            $user->last_study_date = $today;
            $user->save();
        }

        // Optional: Log for debugging
        // Log::info('User stats updated', [
        //     'user_id' => $user->id,
        //     'questionsAttempted' => $questionsAttempted,
        //     'correctAnswers' => $correctAnswers,
        //     'durationSeconds' => $durationSeconds,
        //     'study_streak_days' => $user->study_streak_days,
        //     'last_study_date' => $user->last_study_date,
        // ]);
    }
} 