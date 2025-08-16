<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Http\Requests\RandomQuizRequest;
use App\Services\QuizService;
use App\Services\QuizCacheService;
use App\Services\QuizPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

/**
 * Optimized Random Quiz Controller
 * Handles random quiz generation with maximum performance
 */
class OptimizedRandomQuizController extends Controller
{
    protected $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    /**
     * Show random quiz page
     */
    public function showRandomQuiz(Request $request)
    {
        // Handle unauthenticated users - create a guest user or use null
        $user = auth()->user();
        if (!$user) {
            // Create a guest user object for unauthenticated access
            $user = new \stdClass();
            $user->id = 0;
            $user->name = 'Guest';
            $user->role = 'guest';
            // Add any other properties that might be needed
            $user->exists = true;
        }
        
        // BUSINESS LOGIC FIX: Proper time validation - make all parameters optional
        $validated = $request->validate([
            'question_limit' => 'nullable|integer|min:1|max:50',
            'time_limit' => 'nullable|numeric|min:0.1|max:180', // 0.1-180 minutes max (allows 10 seconds)
            'selected_courses' => 'nullable|array',
            'selected_courses.*' => 'integer|exists:courses,id'
        ]);
        
        // Set defaults
        $questionCount = $validated['question_limit'] ?? 10;
        $durationMinutes = $validated['time_limit'] ?? 15;
        $selectedCourseIds = $validated['selected_courses'] ?? [];
        
        // If no courses selected, get all available courses
        if (empty($selectedCourseIds)) {
            if ($user->role === 'admin') {
                $selectedCourseIds = \App\Models\Course::pluck('id')->toArray();
            } else {
                // Get enrolled courses
                $enrolledCourseIds = \App\Models\Course::whereHas('users', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->pluck('id')->toArray();
                
                // Get free courses
                $freeCourseIds = \App\Models\Course::where(function($query) {
                    $query->where('price', 0)->orWhereNull('price');
                })->pluck('id')->toArray();
                
                $selectedCourseIds = array_unique(array_merge($enrolledCourseIds, $freeCourseIds));
            }
        }
        
        // Generate a random quiz for display
        
        // Build options for quiz generation
        $options = [
            'sources' => ['all'],
            'course_ids' => $selectedCourseIds,
            'difficulty' => 'mixed'
        ];
        
        $result = $this->quizService->generateRandomQuiz($user, $questionCount, $options);
        
        if (!$result['success']) {
            return redirect()->back()->with('error', 'Unable to generate random quiz. Please try again.');
        }
        
        // If we got fewer questions than requested, adjust the count
        $actualQuestionCount = count($result['questions']);
        if ($actualQuestionCount < $questionCount) {
            Log::info("Requested {$questionCount} questions but only {$actualQuestionCount} available");
        }
        
        // Get selected courses for navigation
        $selectedCourses = collect();
        if (!empty($selectedCourseIds)) {
            $selectedCourses = \App\Models\Course::whereIn('id', $selectedCourseIds)->get();
        }
        
        // Fallback to first available course if none selected
        if ($selectedCourses->isEmpty()) {
            $selectedCourses = \App\Models\Course::take(1)->get();
            if ($selectedCourses->isEmpty()) {
                // Create a fallback course with proper attributes
                $fallbackCourse = new \App\Models\Course();
                $fallbackCourse->id = 1;
                $fallbackCourse->name = 'General';
                $fallbackCourse->description = 'General course for random quizzes';
                $fallbackCourse->exists = true; // Mark as existing to avoid database queries
                $selectedCourses = collect([$fallbackCourse]);
            }
        }
        
        // Convert array questions to objects for the view
        $questions = collect($result['questions'])->map(function($question) {
            if (is_array($question)) {
                return (object) $question;
            }
            return $question;
        });
        
        $viewData = [
            'questions' => $questions,
            'selectedCourses' => $selectedCourses,
            'duration_minutes' => $durationMinutes,
            'requested_questions' => $questionCount,
            'actual_questions' => $actualQuestionCount
        ];
        
        // Force return as HTML response, not JSON
        try {
            $response = response()->view('quiz.random_quiz', $viewData);
            $response->header('Content-Type', 'text/html');
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('View rendering error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Return error as HTML
            return response('<h1>View Rendering Error</h1><pre>' . $e->getMessage() . '</pre>', 500, ['Content-Type' => 'text/html']);
        }
    }

    /**
     * Generate random quiz with ultra-fast performance
     */
    public function generateRandomQuiz(RandomQuizRequest $request): Response
    {
        // If this is a direct browser request, return the view instead of JSON
        if ($request->expectsJson() === false && $request->header('Accept') !== 'application/json') {
            return $this->showRandomQuiz($request);
        }
        
        // Handle guest users
        $user = auth()->user();
        if (!$user) {
            $user = new \stdClass();
            $user->id = 0;
            $user->name = 'Guest';
            $user->role = 'guest';
            $user->exists = true;
        }
        
        return QuizPerformanceService::trackQuizGeneration(0, $user->id, function () use ($request, $user) {
            try {
                $validatedData = $request->getValidatedWithDefaults();
                
                $result = $this->quizService->generateRandomQuiz(
                    $user,
                    $validatedData['question_count'] ?? 10,
                    $validatedData
                );

                return response()->json([
                    'success' => true,
                    'data' => $result,
                    'cache_key' => 'nuclear_bypass_' . time(),
                    'performance' => [
                        'cached' => false,
                        'generation_type' => 'nuclear_fresh',
                        'validation_type' => 'nuclear_database_check'
                    ]
                ]);

            } catch (\Exception $e) {
                Log::error('Random quiz generation failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Random quiz generation failed. Please try again.',
                    'error_code' => 'RANDOM_QUIZ_FAILED'
                ], 500);
            }
        });
    }

    /**
     * Grade random quiz with optimized performance
     */
    public function gradeRandomQuiz(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                $user = new \stdClass();
                $user->id = 0;
                $user->name = 'Guest';
                $user->role = 'guest';
                $user->exists = true;
            }
            
            // BUSINESS LOGIC FIX: Enhanced validation for scoring
            $validated = $request->validate([
                'answers' => 'required|array|min:1|max:50',
                'answers.*' => 'nullable|string|max:2000',
                'time_taken' => 'required|integer|min:1|max:10800', // 1 second to 3 hours
                'mode' => 'nullable|string|in:quiz,tutor,random',
                'quiz_duration' => 'nullable|integer|min:1|max:10800', // Expected quiz duration
                'question_count' => 'nullable|integer|min:1|max:50'
            ]);

            // BUSINESS LOGIC FIX: Validate scoring logic inputs
            $answers = $validated['answers'];
            $timeTaken = $validated['time_taken'];
            $mode = $validated['mode'] ?? 'random';
            $expectedDuration = $validated['quiz_duration'] ?? null;
            $expectedQuestionCount = $validated['question_count'] ?? count($answers);
            
            // BUSINESS LOGIC FIX: Validate answer count matches expected questions
            if (count($answers) !== $expectedQuestionCount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Answer count does not match expected question count',
                    'error_code' => 'ANSWER_COUNT_MISMATCH'
                ], 400);
            }
            
            // BUSINESS LOGIC FIX: Validate time taken is reasonable
            if ($expectedDuration && $timeTaken > ($expectedDuration * 60 * 1.5)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Time taken exceeds reasonable limits',
                    'error_code' => 'TIME_EXCEEDED'
                ], 400);
            }
            
            // BUSINESS LOGIC FIX: Validate minimum time per question (prevent instant submissions)
            $minTimePerQuestion = 5; // 5 seconds minimum per question
            if ($timeTaken < (count($answers) * $minTimePerQuestion)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submission too fast. Please take time to read questions.',
                    'error_code' => 'SUBMISSION_TOO_FAST'
                ], 400);
            }

            // Get the questions to determine topics
            $questionIds = array_keys($answers);
            $questions = \App\Models\Question::whereIn('id', $questionIds)
                ->with(['topic', 'topic.course'])
                ->get();

            if ($questions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid questions found for grading'
                ], 400);
            }

            // For random quiz, we need to handle multi-topic grading
            // Create a synthetic topic for random quiz context
            $syntheticTopic = new \App\Models\Topic();
            $syntheticTopic->id = 0; // Use 0 to indicate synthetic topic
            $syntheticTopic->name = 'Random Quiz';
            $syntheticTopic->course_id = 0;
            $syntheticTopic->exists = true;
            
            // Create a synthetic course for random quiz context
            $syntheticCourse = new \App\Models\Course();
            $syntheticCourse->id = 0; // Use 0 to indicate synthetic course
            $syntheticCourse->name = 'Random Quiz';
            $syntheticCourse->exists = true;

            // Use the GradingService to grade the quiz
            $gradingResult = app(\App\Services\GradingService::class)->gradeQuiz(
                $user,
                $answers,
                $timeTaken,
                $mode,
                $syntheticTopic,
                $syntheticCourse
            );

            // Ensure the response has the correct field names for the frontend
            $gradingResult['total'] = $gradingResult['totalQuestions'];
            $gradingResult['message'] = $gradingResult['encouragement'] ?? 'Quiz completed!';

            // 🔔 CRITICAL NOTIFICATION INTEGRATION - Random Quiz Completion
            \App\Services\NotificationTriggerService::onQuizCompleted(auth()->user(), [
                'score' => $gradingResult['score'] ?? 0,
                'total_questions' => $gradingResult['totalQuestions'] ?? 1,
                'percentage' => $gradingResult['percentageGrade'] ?? 0,
                'course_name' => 'Random Quiz',
                'topic_name' => 'Mixed Topics',
                'user_quiz_count' => $this->getUserQuizCount(auth()->id()),
                'quiz_type' => 'random'
            ]);

            return response()->json($gradingResult);

        } catch (\Exception $e) {
            Log::error('Random quiz grading failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Random quiz grading failed. Please try again.',
                'error_code' => 'RANDOM_QUIZ_GRADING_FAILED'
            ], 500);
        }
    }

    /**
     * Get user's total quiz count for first attempt detection
     */
    private function getUserQuizCount(int $userId): int
    {
        $cacheKey = "user_quiz_count_{$userId}";
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($userId) {
            return \Illuminate\Support\Facades\DB::table('quiz_attempts')
                ->where('user_id', $userId)
                ->count();
        });
    }

    /**
     * Get random quiz statistics
     */
    public function getRandomQuizStats(): JsonResponse
    {
        try {
            $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('random_quiz_stats', [
                'user_id' => auth()->id()
            ]);
            
            $stats = Cache::remember($cacheKey, 600, function () {
                return $this->quizService->getRandomQuizStats(auth()->id());
            });

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Random quiz stats failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load quiz statistics'
            ], 500);
        }
    }
}