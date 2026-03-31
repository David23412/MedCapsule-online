<?php

namespace App\Services\Learning;

use App\Services\Learning\MistakeManagementService;
use App\Services\Learning\StudyTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LETHAL REFACTOR: Extracted business logic from LearnController
 * This service handles the complex learning operations to reduce controller bloat
 */
class LearnControllerService
{
    protected $mistakeService;
    protected $studyTrackingService;

    public function __construct(
        MistakeManagementService $mistakeService,
        StudyTrackingService $studyTrackingService
    ) {
        $this->mistakeService = $mistakeService;
        $this->studyTrackingService = $studyTrackingService;
    }

    /**
     * Handle the complete mistake learning process
     */
    public function handleMistakeLearning(int $userId, string $mode, ?string $courseFilter): ?array
    {
        $cachedData = $this->mistakeService->getMistakesForLearning($userId, $mode, $courseFilter);

        if ($cachedData === null) {
            return null;
        }

        if (empty($cachedData['mistakes']) || $cachedData['mistakes']->isEmpty()) {
            return ['empty' => true];
        }

        return $cachedData;
    }

    /**
     * Handle mistake review process
     */
    public function handleMistakeReview(int $userId, int $questionId, Request $request): array
    {
        $rating = $request->input('rating');
        $timeSpent = $request->input('time_spent', 0);
        $mode = session('learn_mode', 'quiz');


        $result = $this->mistakeService->reviewMistake($userId, $questionId, $rating, $timeSpent, $mode);

        if ($result['success']) {
            $this->studyTrackingService->recordStudySession($userId, $timeSpent, 'mistake_review');
        }

        return $result;
    }

    /**
     * Handle review session start
     */
    public function handleReviewSessionStart(int $userId, Request $request): array
    {
        $mode = $request->input('mode', 'quiz');
        session(['learn_mode' => $mode]);


        return [
            'success' => true,
            'mode' => $mode,
            'message' => 'Review session started successfully'
        ];
    }

    /**
     * Handle mistake removal
     */
    public function handleMistakeRemoval(int $userId, int $questionId): array
    {

        $result = $this->mistakeService->removeMistake($userId, $questionId);

        if ($result['success']) {
        }

        return $result;
    }

    /**
     * Handle study time saving
     */
    public function handleStudyTimeSaving(int $userId, Request $request): array
    {
        $studyTime = $request->input('study_time', 0);
        $sessionType = $request->input('session_type', 'general');

        if ($studyTime > 0) {
            $this->studyTrackingService->recordStudySession($userId, $studyTime, $sessionType);
            

            return [
                'success' => true,
                'message' => 'Study time saved successfully'
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid study time provided'
        ];
    }
}