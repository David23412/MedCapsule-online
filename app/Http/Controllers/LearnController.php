<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Services\Learning\MistakeManagementService;
use App\Services\Learning\StudyTrackingService;
use App\Services\StudentViewDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LearnController extends Controller
{
    protected $mistakeService;
    protected $studyTrackingService;
    protected StudentViewDataService $studentViewDataService;

    public function __construct(
        MistakeManagementService $mistakeService,
        StudyTrackingService $studyTrackingService,
        StudentViewDataService $studentViewDataService
    ) {
        $this->middleware(['auth']);
        $this->mistakeService = $mistakeService;
        $this->studyTrackingService = $studyTrackingService;
        $this->studentViewDataService = $studentViewDataService;
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

            $selectedTopicNames = !empty($topicIds)
                ? Topic::whereIn('id', $topicIds)->orderBy('name')->pluck('name')->values()
                : collect();

            $sessionNow = now();
            $dueNowCount = $mistakes->filter(function ($mistake) use ($sessionNow) {
                return !(bool) $mistake->skipped
                    && (!$mistake->next_review_at || $mistake->next_review_at->lte($sessionNow));
            })->count();
            $highRiskCount = $mistakes->filter(function ($mistake) {
                return !(bool) $mistake->skipped
                    && (float) ($mistake->current_retrievability ?? $mistake->retrievability ?? 0.9) <= 0.55;
            })->count();
            $stabilizingCount = $mistakes->filter(function ($mistake) {
                return !(bool) $mistake->skipped
                    && (float) ($mistake->stability ?? 0) >= 1.5
                    && (float) ($mistake->current_retrievability ?? $mistake->retrievability ?? 0.9) > 0.55;
            })->count();
            $upNextMistake = $mistakes
                ->filter(fn ($mistake) => !(bool) $mistake->skipped && $mistake->next_review_at)
                ->sortBy('next_review_at')
                ->first();

            $learnPageView = [
                'selected_topic_names' => $selectedTopicNames,
                'is_topic_focused_session' => $selectedTopicNames->isNotEmpty(),
                'topic_session_title' => $selectedTopicNames->count() === 1
                    ? $selectedTopicNames->first() . ' Mistake Session'
                    : 'Focused Topic Mistake Session',
                'session_loaded_count' => $mistakes->count(),
                'coverage_percent' => $totalMistakesCount > 0 ? (int) round(($mistakes->count() / $totalMistakesCount) * 100) : 0,
                'active_course_name' => !empty($courseFilter)
                    ? (optional(collect($courses ?? [])->firstWhere('id', (int) $courseFilter))->name ?? 'Custom Filter')
                    : 'All Courses',
                'due_now_count' => $dueNowCount,
                'high_risk_count' => $highRiskCount,
                'stabilizing_count' => $stabilizingCount,
                'average_interval_days' => round($mistakes->avg(fn ($mistake) => (float) ($mistake->interval_days ?? 0)) ?? 0, 1),
                'average_retrievability' => (int) round(($mistakes->avg(fn ($mistake) => (float) ($mistake->current_retrievability ?? $mistake->retrievability ?? 0.9)) ?? 0.9) * 100),
                'queue_lead_text' => $dueNowCount > 0
                    ? "{$dueNowCount} due now"
                    : ($upNextMistake ? 'Next review ' . $upNextMistake->next_review_at->diffForHumans() : 'Queue is clear'),
            ];

            return view('mistakes.learn', [
                'mistakes' => $mistakes,
                'courses' => $courses,
                'courseFilter' => $courseFilter,
                'currentLearnMode' => $mode,
                'autoStartLearnMode' => $autoStart ? $mode : null,
                'selectedQuestionType' => $questionType,
                'selectedTopicIds' => $topicIds,
                'selectedTopicNames' => $selectedTopicNames,
                'selectedTopicMistakeCount' => $mistakes->count(),
                'totalMistakesCount' => $cachedData['total_mistakes_count'] ?? 0,
                'skippedCount' => $cachedData['skipped_count'] ?? 0,
                'courseCounts' => $cachedData['course_counts'] ?? [],
                'topicCounts' => $cachedData['topic_counts'] ?? [],
                'topics' => $cachedData['topics'] ?? collect(),
                'learnPageView' => $learnPageView,
                'currentUserView' => $this->studentViewDataService->buildCurrentUserView(auth()->user()),
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
                'first_selected_answer' => 'nullable|string|max:1000',
                'answer_change_count' => 'nullable|integer|min:0|max:50',
                'time_to_first_selection' => 'nullable|integer|min:0|max:3600',
                'first_confidence' => 'nullable|string|in:guessed,unsure,knew',
                'confidence_change_count' => 'nullable|integer|min:0|max:50',
            ]);
            
            $mode = $validated['mode'] ?? 'quiz';
            $selectedAnswer = (string) $validated['selected_answer'];
            $timeSpent = (int) ($validated['time_spent'] ?? 0);
            $confidence = $validated['confidence'] ?? null;
            $interactionSignals = [
                'first_selected_answer' => $validated['first_selected_answer'] ?? null,
                'final_selected_answer' => $selectedAnswer,
                'answer_change_count' => (int) ($validated['answer_change_count'] ?? 0),
                'time_to_first_selection' => isset($validated['time_to_first_selection'])
                    ? (int) $validated['time_to_first_selection']
                    : null,
                'first_confidence' => $validated['first_confidence'] ?? null,
                'final_confidence' => $confidence,
                'confidence_change_count' => (int) ($validated['confidence_change_count'] ?? 0),
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
