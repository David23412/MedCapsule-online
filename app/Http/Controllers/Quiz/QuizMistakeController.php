<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Mistake;
use App\Models\Course;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 💀 NUCLEAR OPTIMIZED QUIZ MISTAKE CONTROLLER 💀
 * Handles mistake display with F-16 RAPTOR SPEED
 */
class QuizMistakeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * 🎯 SMART HYBRID MISTAKES LOADER - FAST CACHE WITH INSTANT INVALIDATION
     * Uses short-lived cache for performance + immediate invalidation for accuracy
     */
    public function showMistakes(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('mistakes_');
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please sign in to review your mistakes.');
        }
        
        try {
            $userId = (int) $user->id;
            $courseFilter = $request->input('course');
            if ($courseFilter === 'skipped') {
                $courseFilter = null;
            }
            
            // SURGICAL OPTIMIZATION: Only log in development
            if (app()->environment('local', 'testing')) {
            }

            // 🎯 SMART HYBRID: Ultra-short cache (10 seconds) for near-instant updates
            // But with immediate invalidation on writes for accuracy
            $courseFilter = $courseFilter ?: ''; // Ensure empty string instead of null
            $cacheKey = "mistakes_smart_{$userId}_{$courseFilter}_v3";
            $cacheKey = trim($cacheKey, '_'); // Remove trailing underscore if no course filter
            $mistakesData = Cache::remember($cacheKey, 10, function() use ($userId, $courseFilter) { // 10 seconds only
                $query = DB::table('mistakes')
                    ->join('questions', 'mistakes.question_id', '=', 'questions.id')
                    ->join('topics', 'questions.topic_id', '=', 'topics.id')
                    ->join('courses', 'topics.course_id', '=', 'courses.id')
                    ->select([
                        'mistakes.id',
                        'mistakes.question_id',
                        'mistakes.submitted_answer',
                        'mistakes.last_attempt_date',
                        'mistakes.correct_streak',
                        'mistakes.skipped',
                        'questions.question',
                        'questions.correct_answer',
                        'questions.image_url',
                        'questions.explanation',
                        'questions.question_type',
                        'questions.option_a',
                        'questions.option_b',
                        'questions.option_c',
                        'questions.option_d',
                        'topics.id as topic_id',
                        'topics.name as topic_name',
                        'courses.id as course_id',
                        'courses.name as course_name'
                    ])
                    ->where('mistakes.user_id', $userId);

                if ($courseFilter) {
                    $query->where('courses.id', $courseFilter);
                }

                return $query->orderByDesc('mistakes.last_attempt_date')->get();
            });

            // 🚀 BATCH PROCESSING: Transform raw data efficiently with proper submitted answer handling
            $mistakes = $mistakesData->map(function($mistake) {
                // Normalize submitted answer for proper display
                $submittedAnswer = $this->normalizeSubmittedAnswer($mistake->submitted_answer, $mistake->skipped);
                
                return (object) [
                    'id' => $mistake->id,
                    'question_id' => $mistake->question_id,
                    'submitted_answer' => $submittedAnswer,
                    'last_attempt_date' => \Carbon\Carbon::parse($mistake->last_attempt_date),
                    'correct_streak' => $mistake->correct_streak,
                    'skipped' => (bool) $mistake->skipped,
                    'question' => (object) [
                        'id' => $mistake->question_id,
                        'question' => $mistake->question,
                        'correct_answer' => $mistake->correct_answer,
                        'image_url' => $mistake->image_url,
                        'explanation' => $mistake->explanation,
                        'question_type' => $mistake->question_type,
                        'option_a' => $mistake->option_a,
                        'option_b' => $mistake->option_b,
                        'option_c' => $mistake->option_c,
                        'option_d' => $mistake->option_d,
                        'topic' => (object) [
                            'id' => $mistake->topic_id,
                            'name' => $mistake->topic_name,
                            'course' => (object) [
                                'id' => $mistake->course_id,
                                'name' => $mistake->course_name
                            ]
                        ]
                    ]
                ];
            });

            // 🎯 SMART HYBRID: Ultra-short cache for supporting data (10 seconds)
            $supportingData = Cache::remember("mistakes_support_smart_{$userId}", 10, function() use ($userId) {
                $coursesWithCounts = DB::table('mistakes')
                    ->join('questions', 'mistakes.question_id', '=', 'questions.id')
                    ->join('topics', 'questions.topic_id', '=', 'topics.id')
                    ->join('courses', 'topics.course_id', '=', 'courses.id')
                    ->where('mistakes.user_id', $userId)
                    ->select('courses.id', 'courses.name', DB::raw('count(*) as mistake_count'))
                    ->groupBy('courses.id', 'courses.name')
                    ->orderBy('courses.name')
                    ->get();

                // Get total and skipped counts in single queries
                $totalCount = DB::table('mistakes')->where('user_id', $userId)->count();
                $skippedCount = DB::table('mistakes')
                    ->where('user_id', $userId)
                    ->where(function($query) {
                        $query->where('skipped', true)
                              ->orWhere('submitted_answer', 'unanswered')
                              ->orWhere('submitted_answer', '')
                              ->orWhereNull('submitted_answer');
                    })
                    ->count();

                // Get recent course efficiently
                $recentCourse = DB::table('quiz_attempts')
                    ->join('topics', 'quiz_attempts.topic_id', '=', 'topics.id')
                    ->join('courses', 'topics.course_id', '=', 'courses.id')
                    ->where('quiz_attempts.user_id', $userId)
                    ->whereNotNull('quiz_attempts.topic_id')
                    ->select('courses.id', 'courses.name')
                    ->orderByDesc('quiz_attempts.created_at')
                    ->first();

                return [
                    'courses' => $coursesWithCounts,
                    'total_count' => $totalCount,
                    'skipped_count' => $skippedCount,
                    'recent_course' => $recentCourse
                ];
            });

            // Transform courses data for view
            $availableCourses = $supportingData['courses']->map(function($course) {
                return (object) [
                    'id' => $course->id,
                    'name' => $course->name,
                    'mistake_count' => $course->mistake_count
                ];
            });

            $recentCourse = $supportingData['recent_course'] ? 
                (object) ['id' => $supportingData['recent_course']->id, 'name' => $supportingData['recent_course']->name] : 
                ($mistakes->isNotEmpty() ? $mistakes->first()->question->topic->course : null);

            // Group mistakes by course for display
            $mistakesByCourse = $mistakes->groupBy('question.topic.course.name');

            $executionTime = (microtime(true) - $startTime) * 1000;

            return view('mistakes', [
                'mistakes' => $mistakes,
                'mistakesByCourse' => $mistakesByCourse,
                'availableCourses' => $availableCourses,
                'selectedCourse' => $courseFilter,
                'recentCourse' => $recentCourse,
                'totalMistakesCount' => $supportingData['total_count'],
                'skippedCount' => $supportingData['skipped_count'],
                'currentUserView' => [
                    'profile_image_url' => $user->getProfileImageUrl(),
                    'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff',
                ],
                'performance_metrics' => [
                    'execution_time_ms' => round($executionTime, 2),
                    'mistakes_count' => $mistakes->count(),
                    'courses_count' => $availableCourses->count(),
                    'cache_status' => 'SMART HYBRID - 10sec + Instant Clear'
                ]
            ]);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in ULTRA-OPTIMIZED showMistakes:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2),
                'trace' => $e->getTraceAsString()
            ]);

            return view('mistakes', [
                'mistakes' => collect([]),
                'mistakesByCourse' => collect([]),
                'availableCourses' => collect([]),
                'selectedCourse' => null,
                'recentCourse' => null,
                'totalMistakesCount' => 0,
                'skippedCount' => 0,
                'currentUserView' => [
                    'profile_image_url' => $user->getProfileImageUrl(),
                    'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff',
                ],
                'error' => 'An error occurred while loading your mistakes. Please try again.'
            ]);
        }
    }

    /**
     * 🎯 NORMALIZE SUBMITTED ANSWER FOR PROPER DISPLAY
     * Ensures consistent handling of unanswered questions and submitted answers
     */
    private function normalizeSubmittedAnswer($submittedAnswer, $isSkipped)
    {
        // Handle unanswered/skipped questions
        if ($isSkipped || 
            $submittedAnswer === 'unanswered' || 
            $submittedAnswer === '' || 
            $submittedAnswer === null) {
            return 'unanswered';
        }

        // Return the actual submitted answer for display
        return $submittedAnswer;
    }

    /**
     * 🎯 SMART CACHE INVALIDATION - Immediately clear specific user caches
     * Called whenever mistakes are modified to ensure instant updates
     */
    public static function clearUserMistakeCaches($userId)
    {
        try {
            // SURGICAL OPTIMIZATION: Only clear specific user cache keys, not all courses
            $cacheKeys = [
                "mistakes_smart_{$userId}_v3",                    // No course filter
                "mistakes_smart_{$userId}_skipped_v3",            // Skipped filter
                "mistakes_support_smart_{$userId}",               // Supporting data cache
            ];

            // SURGICAL OPTIMIZATION: Only get courses this user actually has mistakes in
            $courseIds = DB::table('mistakes')
                ->join('questions', 'mistakes.question_id', '=', 'questions.id')
                ->join('topics', 'questions.topic_id', '=', 'topics.id')
                ->where('mistakes.user_id', $userId)
                ->distinct()
                ->pluck('topics.course_id')
                ->toArray();

            // Clear only relevant course caches (surgical precision)
            foreach ($courseIds as $courseId) {
                $cacheKeys[] = "mistakes_smart_{$userId}_{$courseId}_v3";
            }

            // Clear all surgical cache keys
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }

            // SURGICAL OPTIMIZATION: Reduced logging for production performance
            if (app()->environment('local', 'testing')) {
            }

        } catch (\Exception $e) {
            // SURGICAL OPTIMIZATION: Only log errors in development
            if (app()->environment('local', 'testing')) {
                Log::error("❌ Error in surgical cache invalidation", [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
