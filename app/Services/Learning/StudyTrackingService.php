<?php

namespace App\Services\Learning;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudyTrackingService
{
    /** Record the analytics row and counter together, so failed saves can safely retry. */
    public function saveStudyTime(int $userId, int $studyTime): array
    {
        try {
            DB::transaction(function () use ($userId, $studyTime) {
                User::findOrFail($userId)->increment('study_time_seconds', $studyTime);
                $attempt = QuizAttempt::create([
                    'user_id' => $userId,
                    'quiz_type' => 'mistake_review',
                    'study_mode' => 'mistake_review',
                    'topic_id' => null,
                    'score' => 0,
                    'total_questions' => 1,
                    'correct_answers' => 0,
                    'percentage_grade' => 0.0,
                    'duration_seconds' => $studyTime,
                    'question_data' => [
                        ['question_id' => 0, 'user_answer' => null, 'is_correct' => false, 'type' => 'study_time'],
                    ],
                    'source' => 'mistake_review_session',
                    'completed_at' => now(),
                ]);
                if (!$attempt->exists) {
                    throw new \RuntimeException('Study-time record was not saved.');
                }
            });
            return ['success' => true, 'message' => 'Study time recorded'];
        } catch (\Exception $e) {
            Log::error('Error saving study time', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to record study time'];
        }
    }
}
