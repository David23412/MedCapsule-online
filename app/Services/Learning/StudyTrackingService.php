<?php

namespace App\Services\Learning;

use Illuminate\Support\Facades\Log;

class StudyTrackingService
{
    /**
     * Save study time for analytics
     */
    public function saveStudyTime(int $userId, int $studyTime): array
    {
        try {
            $user = auth()->user();
            $user->increment('study_time_seconds', $studyTime);

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