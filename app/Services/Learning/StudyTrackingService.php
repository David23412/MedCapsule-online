<?php

namespace App\Services\Learning;

use Illuminate\Support\Facades\Log;
use App\Models\QuizAttempt;

class StudyTrackingService
{
    /**
     * Save study time for analytics and create quiz attempt record
     */
    public function saveStudyTime(int $userId, int $studyTime): array
    {
        try {
            $user = auth()->user();
            $user->increment('study_time_seconds', $studyTime);

            // Create quiz attempt record for mistake review session
            QuizAttempt::create([
                'user_id' => $userId,
                'quiz_type' => 'mistake_review',
                'study_mode' => 'mistake_review',
                'topic_id' => null, // Mistake review can span multiple topics
                'score' => 0, // Required field - not applicable for study time tracking
                'total_questions' => 1, // Required field - minimum value for validation
                'correct_answers' => 0, // Not applicable for study time tracking
                'percentage_grade' => 0.0, // Not applicable for study time tracking
                'duration_seconds' => $studyTime,
                'question_data' => [
                    ['question_id' => 0, 'user_answer' => null, 'is_correct' => false, 'type' => 'study_time']
                ], // Required field - placeholder for study time tracking
                'source' => 'mistake_review_session',
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Study time and quiz attempt recorded for mistake review', [
                'user_id' => $userId,
                'study_time' => $studyTime,
                'quiz_type' => 'mistake_review'
            ]);

            return [
                'success' => true,
                'message' => 'Study time recorded'
            ];

        } catch (\Exception $e) {
            Log::error('Error saving study time:', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to record study time'
            ];
        }
    }
}