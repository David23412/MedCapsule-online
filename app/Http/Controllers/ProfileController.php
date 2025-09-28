<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QuizCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\QuizPerformanceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Mistake;
use Carbon\Carbon;
use App\Models\Topic;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Services\SecureFileUploadService;

// TODO: Inject services in constructor
    // TODO: Inject services in constructor
    // TODO: Inject services in constructor
    class ProfileController extends Controller
{
    public function __construct()
    {
        // Don't call parent::__construct() to avoid double auth middleware
        // Controller initialized log ANNIHILATED
        $this->middleware(['auth']);
    }


    public function getProgress()
    {
        $user = Auth::user();
        
        // Optionally sync the count to ensure accuracy (can be disabled for performance)
        // $user->syncCorrectAnswersCount();
        
        return response()->json([
            'correct_answers_count' => $user->getTotalCorrectAnswers(),
            'total_questions_attempted' => $user->total_questions_attempted ?? 0,
            'accuracy_rate' => $user->total_questions_attempted > 0 
                ? round(($user->getTotalCorrectAnswers() / $user->total_questions_attempted) * 100, 1) 
                : 0
        ]);
    }

    public function show()
    {
        $user = Auth::user();
        
        // RUTHLESS OPTIMIZATION: More responsive caching with hourly granularity
        $cacheKey = "user_profile_optimized_{$user->id}_" . now()->format('Y-m-d-H');
        
        $profileData = Cache::remember($cacheKey, 3600, function () use ($user) {
            // Calculate accuracy from user's total attempts and correct answers
            $totalQuestions = $user->total_questions_attempted ?? 0;
            $totalCorrect = $user->getTotalCorrectAnswers();
            $accuracyRate = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 1) : 0;

            // Get the study streak value directly from the user model
            $studyStreak = $user->study_streak_days ?? 0;

            // OPTIMIZED: Single query for study time calculation
            $studyTimeBreakdown = $this->calculateStudyTimeBreakdown($user);

            // Format study times without redundant labels
            $formattedStudyTimes = [
                'total' => $this->formatStudyTime($studyTimeBreakdown['total']),
                'quiz' => $this->formatStudyTime($studyTimeBreakdown['quiz']),
                'tutor' => $this->formatStudyTime($studyTimeBreakdown['tutor']),
                'random_quiz' => $this->formatStudyTime($studyTimeBreakdown['random_quiz']),
                'mistake_review' => $this->formatStudyTime($studyTimeBreakdown['mistake_review'])
            ];

            return compact('accuracyRate', 'totalQuestions', 'studyStreak', 'formattedStudyTimes');
        });

        // Extract cached data
        extract($profileData);

        // RUTHLESS OPTIMIZATION: Cached user rank with longer duration
        $userRank = null;
        if ($user->xp > 0) {
            $userRank = Cache::remember("user_rank_optimized_{$user->id}", 1800, function() use ($user) { 
                return DB::table('users')
                    ->where('total_questions_attempted', '>', 0)
                    ->where('role', 'student')
                    ->where('xp', '>', $user->xp)
                    ->count() + 1; 
            });
        }

        // ULTRA-EFFICIENT: Group by attempt_id - multiple sources from same quiz = ONE point
        // REAL-TIME OPTIMIZATION: Reduced cache time for immediate chart updates
        $performanceData = Cache::remember("performance_data_optimized_{$user->id}", 30, function() use ($user) {
            // Get attempts grouped by attempt_id with combined scores
            $groupedAttempts = DB::table('quiz_attempts')
                ->leftJoin('topics', 'quiz_attempts.topic_id', '=', 'topics.id')
                ->where('quiz_attempts.user_id', $user->id)
                ->where('quiz_attempts.quiz_type', '!=', 'mistake_review')
                ->whereNotNull('quiz_attempts.attempt_id') // Only group attempts with attempt_id
                ->select(
                    'quiz_attempts.attempt_id',
                    DB::raw('MAX(quiz_attempts.created_at) as date'),
                    'quiz_attempts.topic_id',
                    'quiz_attempts.study_mode',
                    'quiz_attempts.quiz_type', // Include quiz_type for random quiz detection
                    DB::raw('SUM(quiz_attempts.score) as total_score'),
                    DB::raw('SUM(quiz_attempts.total_questions) as total_questions'),
                    DB::raw('COUNT(*) as attempts_count'),
                    DB::raw('CASE
                        WHEN quiz_attempts.study_mode = "random" THEN "Random Quiz"
                        WHEN quiz_attempts.quiz_type = "random" THEN "Random Quiz"
                        WHEN quiz_attempts.topic_id IS NULL THEN "Unknown Topic"
                        ELSE COALESCE(topics.name, "Unknown Topic")
                    END as topic')
                )
                ->groupBy('quiz_attempts.attempt_id', 'quiz_attempts.topic_id', 'quiz_attempts.study_mode', 'topics.name', 'quiz_attempts.quiz_type')
                ->orderBy('date', 'desc')
                ->take(7)
                ->get();

            // Calculate combined accuracy for each attempt group
            $finalAttempts = [];
            foreach ($groupedAttempts as $attempt) {
                $combinedAccuracy = $attempt->total_questions > 0
                    ? ($attempt->total_score / $attempt->total_questions) * 100
                    : 0;

                $finalAttempts[] = (object) [
                    'date' => $attempt->date,
                    'accuracy' => round($combinedAccuracy, 1),
                    'topic' => $attempt->topic
                ];
            }

            return collect($finalAttempts);
        });

        // Format data for the chart
        $chartData = $performanceData->map(function($attempt) {
            return [
                'x' => Carbon::parse($attempt->date)->timestamp * 1000,
                'y' => round($attempt->accuracy, 1),
                'topic' => $attempt->topic ?? 'Unknown Topic',
                'date' => Carbon::parse($attempt->date)->format('M j, Y g:i A')
            ];
        });

        // RUTHLESS OPTIMIZATION: Single JOIN query eliminating subqueries
        $weakTopicsData = DB::table('topics')
            ->join('user_topic_progress', 'topics.id', '=', 'user_topic_progress.topic_id')
            ->join('courses', 'topics.course_id', '=', 'courses.id')
            ->where('user_topic_progress.user_id', $user->id)
            ->whereNotNull('user_topic_progress.percentage_grade')
            ->where('user_topic_progress.percentage_grade', '<', 100)
            ->select(
                'topics.id',
                'topics.name',
                'topics.course_id',
                'courses.name as course_name',
                'user_topic_progress.percentage_grade',
                DB::raw('
                    CASE 
                        WHEN user_topic_progress.percentage_grade <= 25 THEN 1
                        WHEN user_topic_progress.percentage_grade <= 49 THEN 2
                        WHEN user_topic_progress.percentage_grade <= 74 THEN 3
                        ELSE 4
                    END as priority
                ')
            )
            ->orderBy('priority')
            ->orderBy('user_topic_progress.percentage_grade')
            ->get();

        // Convert to collection with same structure as original
        $weakTopics = $weakTopicsData->map(function($topic) {
            return (object) [
                'id' => $topic->id,
                'name' => $topic->name,
                'course_id' => $topic->course_id,
                'course' => (object) [
                    'id' => $topic->course_id,
                    'name' => $topic->course_name
                ],
                'userProgress' => collect([(object) ['percentage_grade' => $topic->percentage_grade]])
            ];
        });
        
        // RUTHLESS OPTIMIZATION: Extended leaderboard caching - TEMPORARILY DISABLED FOR DEBUGGING
        $leaderboard = DB::table('users')
            ->select(
                'users.id',
                'users.name',
                'users.total_questions_attempted',
                'users.correct_answers_count',
                'users.xp',
                DB::raw('FLOOR(COALESCE(users.xp, 0) / 1000) + 1 as level')
            )
            ->where('users.total_questions_attempted', '>', 0)
                            ->where('users.role', 3)
            ->orderBy('xp', 'desc')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return (object) [
                    'id' => $user->id,
                    'name' => $user->name,
                    'xp' => $user->xp ?? 0,
                    'level' => $user->level,
                    'correct_answers' => $user->correct_answers_count
                ];
            });

        return view('profile', compact(
            'accuracyRate',
            'totalQuestions',
            'weakTopics',
            'leaderboard',
            'studyStreak',
            'chartData',
            'userRank',
            'formattedStudyTimes'
        ));
    }

    /**
     * Format study time from seconds to a human-readable string
     */
    private function formatStudyTime($seconds)
    {
        if (!$seconds) {
            return "0m";
        }
        
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0) {
            return sprintf("%dh %dm", $hours, $remainingMinutes);
        }
        
        return sprintf("%dm", $minutes);
    }

    /**
     * RUTHLESS OPTIMIZATION: Single query for study time breakdown
     */
    private function calculateStudyTimeBreakdown($user): array
    {
        // PERFORMANCE: Single query with conditional aggregation (4 queries → 1 query)
        $result = QuizAttempt::where('user_id', $user->id)
            ->selectRaw('
                SUM(CASE WHEN quiz_type = "normal" AND study_mode = "quiz" AND topic_id IS NOT NULL THEN duration_seconds ELSE 0 END) as quiz,
                SUM(CASE WHEN study_mode = "tutor" THEN duration_seconds ELSE 0 END) as tutor,
                SUM(CASE WHEN quiz_type = "random" THEN duration_seconds ELSE 0 END) as random_quiz,
                SUM(CASE WHEN quiz_type = "mistake_review" OR study_mode = "mistake_review" THEN duration_seconds ELSE 0 END) as mistake_review,
                SUM(duration_seconds) as total
            ')
            ->first();

        return [
            'quiz' => $result->quiz ?? 0,
            'tutor' => $result->tutor ?? 0,
            'random_quiz' => $result->random_quiz ?? 0,
            'mistake_review' => $result->mistake_review ?? 0,
            'total' => $result->total ?? 0
        ];
    }

    /**
     * COMPLEXITY REDUCTION: Get performance data for charts
     */
    private function getPerformanceData($user): array
    {
        return QuizAttempt::select('created_at as date', 'percentage_grade', 'quiz_type', 'topic_id')
            ->where('user_id', $user->id)
            ->where('quiz_type', '!=', 'mistake_review')
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->get()
            ->sortBy('date')
            ->values()
            ->toArray();
    }

    /**
     * COMPLEXITY REDUCTION: Format performance data for charts
     */
    private function formatChartData($performanceData): array
    {
        return collect($performanceData)->map(function($attempt) {
            return [
                'x' => Carbon::parse($attempt['date'])->timestamp * 1000,
                'y' => round($attempt['percentage_grade'] ?? 0, 1),
                'topic' => $attempt['topic'] ?? 'Unknown Topic',
                'date' => Carbon::parse($attempt['date'])->format('M j, Y g:i A')
            ];
        })->toArray();
    }

    /**
     * Handle profile picture upload with MEGA LETHAL security
     */
    public function updateProfilePicture(Request $request)
    {
        try {
            // 🔒 MEGA LETHAL SECURITY: Use secure file upload service
            $request->validate([
                'profile_picture' => 'required|file|max:5120' // Basic validation, security handled by service
            ]);

            $user = auth()->user();

            if ($request->hasFile('profile_picture')) {
                // Delete old profile picture if exists (secure deletion)
                if ($user->profile_picture_url) {
                    $oldPath = str_replace('storage/', '', $user->profile_picture_url);
                    $oldFilePath = storage_path('app/public/' . $oldPath);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                // 🔒 USE SECURE FILE UPLOAD SERVICE - MEGA LETHAL PROTECTION
                $secureUploadService = app(SecureFileUploadService::class);

                try {
                    $uploadResult = $secureUploadService->processSecureUpload(
                        $request->file('profile_picture'),
                        'images', // category
                        'profile-pictures' // directory
                    );

                    // Update user's profile_picture_url with the full storage path
                    $user->profile_picture_url = $uploadResult['storage_path'];
                    $user->save();

                    $cacheBust = time();

                    \Log::info('Secure profile picture upload completed', [
                        'user_id' => $user->id,
                        'secure_filename' => $uploadResult['secure_name'],
                        'original_filename' => $uploadResult['original_name'],
                        'storage_path' => $uploadResult['storage_path'],
                        'cache_bust' => $cacheBust
                    ]);

                    // Check if it's an AJAX request
                    if (request()->ajax() || request()->wantsJson()) {
                        // Use the User model's consistent URL generation method
                        $user->refresh(); // Refresh to get updated profile_picture_url
                        $correctUrl = $user->getProfileImageUrl(true); // With cache bust

                        return response()->json([
                            'success' => true,
                            'message' => 'Profile picture updated successfully!',
                            'new_url' => $correctUrl,
                            'timestamp' => $cacheBust,
                            'cache_bust' => $cacheBust
                        ]);
                    }

                    return redirect()->back()
                        ->with('success', 'Profile picture updated successfully!')
                        ->with('cache_bust', time());

                } catch (\Exception $e) {
                    \Log::error('Secure profile picture upload failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'original_filename' => $request->file('profile_picture')->getClientOriginalName()
                    ]);

                    if (request()->ajax() || request()->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'File upload failed security validation: ' . $e->getMessage()
                        ], 422);
                    }

                    return redirect()->back()
                        ->with('error', 'File upload failed security validation: ' . $e->getMessage());
                }
            }

            return redirect()->back()->with('error', 'No file uploaded.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()->withErrors($e->validator)->withInput();

        } catch (\Exception $e) {
            \Log::error('Profile picture upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile picture: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update profile picture: ' . $e->getMessage());
        }
    }
}
