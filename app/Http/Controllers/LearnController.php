<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Learning\MistakeManagementService;
use App\Services\Learning\StudyTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LearnController extends Controller
{
    protected $mistakeService;
    protected $studyTrackingService;

    public function __construct(
        MistakeManagementService $mistakeService,
        StudyTrackingService $studyTrackingService
    ) {
        $this->middleware(['auth']);
        $this->mistakeService = $mistakeService;
        $this->studyTrackingService = $studyTrackingService;
    }

    /**
     * Show the mistakes that need to be reviewed
     */
    public function learnMistakes(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('learn_mistakes_');
        
        try {
            $userId = auth()->id();
            $mode = session('learn_mode', 'quiz');
            $courseFilter = $request->validate(['course' => 'nullable|integer|exists:courses,id'])['course'] ?? null;
            
            $cachedData = $this->mistakeService->getMistakesForLearning($userId, $mode, $courseFilter);

            if ($cachedData === null) {
                return redirect()->route('review.index')
                    ->with('error', 'Error loading questions. Please try again.');
            }

            if (empty($cachedData['mistakes']) || $cachedData['mistakes']->isEmpty()) {
                return redirect()->route('review.index')
                    ->with('info', 'No mistakes to review! Add some mistakes first.');
            }

            extract($cachedData);

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('Learn mistakes loaded successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'mistakes_count' => $mistakes->count(),
                'courses_count' => $courses->count()
            ]);

            return view('mistakes.learn', compact('mistakes', 'courses', 'courseFilter'));
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('Error in learnMistakes', [
                'request_id' => $requestId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            return redirect()->route('review.index')
                ->with('error', 'An error occurred while loading the learn page.');
        }
    }

    /**
     * Get updated list of mistakes after a review
     */
    public function getUpdatedMistakes(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('get_mistakes_');
        
        try {
            $userId = auth()->id();
            $result = $this->mistakeService->getUpdatedMistakesCount($userId);

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('Updated mistakes retrieved successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'mistakes_count' => $result['mistakes_count']
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('Error getting updated mistakes', [
                'request_id' => $requestId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve mistakes'
            ], 500);
        }
    }

    /**
     * Handle the review (rating) for a mistake.
     */
    public function reviewMistake(Request $request, $questionId)
    {
        $startTime = microtime(true);
        $requestId = uniqid('review_mistake_');
        
        try {
            $userId = auth()->id();
            $validated = $request->validate([
                'mode' => 'nullable|string|in:quiz,review',
                'selected_answer' => 'required|string|max:1000'
            ]);
            
            $mode = $validated['mode'] ?? 'quiz';
            $selectedAnswer = $validated['selected_answer'];

            $result = $this->mistakeService->reviewMistake($userId, $questionId, $selectedAnswer, $mode);

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('Review completed successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'is_correct' => $result['is_correct'] ?? false
            ]);

            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('Error in reviewMistake:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'question_id' => $questionId,
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your review.'
            ], 500);
        }
    }

    /**
     * Start a review session
     */
    public function startReview(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('start_review_');
        
        try {
            $userId = auth()->id();
            $mode = $request->validate(['mode' => 'nullable|string|in:quiz,review'])['mode'] ?? 'quiz';
            
            session(['learn_mode' => $mode]);

            $mistakes = $this->mistakeService->getMistakesForReview($userId);

            if ($mistakes->isEmpty()) {
                return redirect()->route('review.index')
                    ->with('info', 'No mistakes available for review!');
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('Review session started successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'mistakes_count' => $mistakes->count(),
                'mode' => $mode
            ]);

            return view('mistakes.review', compact('mistakes', 'mode'));

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('Error starting review session:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            
            return redirect()->route('review.index')
                ->with('error', 'An error occurred while starting the review session.');
        }
    }

    /**
     * Remove a mistake from the user's list
     */
    public function removeMistake($questionId)
    {
        $startTime = microtime(true);
        $requestId = uniqid('remove_mistake_');
        
        try {
            $userId = auth()->id();
            $result = $this->mistakeService->removeMistake($userId, $questionId);

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('Mistake removal completed', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'question_id' => $questionId,
                'success' => $result['success']
            ]);

            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('Error removing mistake:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'question_id' => $questionId,
                'user_id' => auth()->id(),
                'execution_time_ms' => round($executionTime, 2)
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the mistake.'
            ], 500);
        }
    }

    /**
     * Save study time for analytics
     */
    public function saveStudyTime(Request $request)
    {
        $userId = auth()->id();
        $studyTime = $request->validate(['study_time' => 'nullable|integer|min:0|max:86400'])['study_time'] ?? 0;
        
        $result = $this->studyTrackingService->saveStudyTime($userId, $studyTime);
        
        return response()->json($result, $result['success'] ? 200 : 500);
    }
}