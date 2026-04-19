<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Topic;
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
            $validated = $request->validate([
                'course' => 'nullable|string',
                'mode' => 'nullable|string|in:practice,quiz,spaced',
                'autostart' => 'nullable|boolean',
                'question_type' => 'nullable|string|in:all,multiple_choice,written',
                'topic_ids' => 'nullable|array',
                'topic_ids.*' => 'integer|exists:topics,id',
            ]);
            $mode = $validated['mode'] ?? session('learn_mode', 'quiz');
            $courseFilter = $validated['course'] ?? null;
            if ($courseFilter === 'skipped') {
                $courseFilter = null;
            }
            $autoStart = (bool) ($validated['autostart'] ?? false);
            $questionType = $validated['question_type'] ?? 'all';
            $topicIds = collect($validated['topic_ids'] ?? [])
                ->map(fn ($topicId) => (int) $topicId)
                ->filter()
                ->values()
                ->all();

            session(['learn_mode' => $mode]);

            $cachedData = $this->mistakeService->getMistakesForLearning($userId, $mode, $courseFilter, $topicIds, $questionType);

            if ($cachedData === null) {
                return redirect()->route('review.index')
                    ->with('error', 'Error loading questions. Please try again.');
            }

            $totalMistakesCount = (int) ($cachedData['total_mistakes_count'] ?? 0);

            if ($totalMistakesCount === 0) {
                return redirect()->route('review.index')
                    ->with('success', '🎉 Excellent! You have no mistakes to review. Keep up the great work!');
            }

            extract($cachedData);

            $executionTime = (microtime(true) - $startTime) * 1000;

            return view('mistakes.learn', [
                'mistakes' => $mistakes,
                'courses' => $courses,
                'courseFilter' => $courseFilter,
                'currentLearnMode' => $mode,
                'autoStartLearnMode' => $autoStart ? $mode : null,
                'selectedQuestionType' => $questionType,
                'selectedTopicIds' => $topicIds,
                'selectedTopicNames' => !empty($topicIds)
                    ? Topic::whereIn('id', $topicIds)->orderBy('name')->pluck('name')->values()
                    : collect(),
                'selectedTopicMistakeCount' => $mistakes->count(),
                'totalMistakesCount' => $cachedData['total_mistakes_count'] ?? 0,
                'skippedCount' => $cachedData['skipped_count'] ?? 0,
                'courseCounts' => $cachedData['course_counts'] ?? [],
                'topicCounts' => $cachedData['topic_counts'] ?? [],
                'topics' => $cachedData['topics'] ?? collect(),
                'currentUserView' => [
                    'profile_image_url' => auth()->user()->getProfileImageUrl(),
                    'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&size=200&background=007bff&color=ffffff',
                ],
            ]);
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
            $validated = $request->validate([
                'mode' => 'nullable|string|in:quiz,review,random,tutor,study,assignment,practice,spaced,quick',
            ]);
            $mode = $validated['mode'] ?? session('learn_mode', 'quiz');
            $result = $this->mistakeService->getUpdatedMistakesCount($userId, $mode);

            $executionTime = (microtime(true) - $startTime) * 1000;

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
                'mode' => 'nullable|string|in:quiz,review,random,tutor,study,assignment,practice,spaced',
                'selected_answer' => 'required|string|max:1000',
                'time_spent' => 'nullable|integer|min:0|max:3600',
                'confidence' => 'nullable|string|in:guessed,unsure,knew',
            ]);
            
            $mode = $validated['mode'] ?? 'quiz';
            $selectedAnswer = (string) $validated['selected_answer'];
            $timeSpent = (int) ($validated['time_spent'] ?? 0);
            $confidence = $validated['confidence'] ?? null;
            $interactionSignals = [
                'final_selected_answer' => $selectedAnswer,
            ];

            if ($mode === 'spaced' && $confidence === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pick your confidence before answering in spaced mode.',
                ], 422);
            }

            $result = $this->mistakeService->reviewMistake($userId, $questionId, $selectedAnswer, $timeSpent, $mode, $confidence, $interactionSignals);

            $executionTime = (microtime(true) - $startTime) * 1000;

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
