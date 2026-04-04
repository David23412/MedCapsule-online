<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\QuizAttempt;
use App\Services\PerformanceTrackingService;
use App\Services\SecureFileUploadService;

class ProfileController extends Controller
{
    private static ?array $availableCoursePlannerColumns = null;

    public function __construct(
        private PerformanceTrackingService $performanceTrackingService
    )
    {
        // Apply auth middleware
        $this->middleware(['auth']);
    }


    public function getProgress()
    {
        $user = Auth::user();
        $courseCompletionPercentages = $this->getCourseCompletionPercentages($user->id);

        return response()->json([
            'correct_answers_count' => $user->getTotalCorrectAnswers(),
            'total_questions_attempted' => $user->total_questions_attempted ?? 0,
            'accuracy_rate' => $user->total_questions_attempted > 0 
                ? round(($user->getTotalCorrectAnswers() / $user->total_questions_attempted) * 100, 1) 
                : 0,
            'xp' => (int) ($user->xp ?? 0),
            'level' => (int) floor(((int) ($user->xp ?? 0)) / 1000) + 1,
            'course_completion_percentages' => $courseCompletionPercentages,
        ]);
    }

    private function getCourseCompletionPercentages(int $userId): array
    {
        $totalTopicsByCourse = DB::table('topics')
            ->select('course_id', DB::raw('COUNT(*) as total_topics'))
            ->groupBy('course_id')
            ->pluck('total_topics', 'course_id');

        if ($totalTopicsByCourse->isEmpty()) {
            return [];
        }

        $completedTopicsByCourse = DB::query()
            ->fromSub(function ($query) use ($userId) {
                $query->from('user_topic_progress')
                    ->join('topics', 'user_topic_progress.topic_id', '=', 'topics.id')
                    ->where('user_topic_progress.user_id', $userId)
                    ->select(
                        'topics.course_id',
                        'topics.id as topic_id',
                        DB::raw('MAX(user_topic_progress.percentage_grade) as max_percentage')
                    )
                    ->groupBy('topics.course_id', 'topics.id');
            }, 'topic_progress')
            ->select(
                'course_id',
                DB::raw('SUM(CASE WHEN max_percentage >= 90 THEN 1 ELSE 0 END) as completed_topics')
            )
            ->groupBy('course_id')
            ->pluck('completed_topics', 'course_id');

        return $totalTopicsByCourse
            ->mapWithKeys(function ($totalTopics, $courseId) use ($completedTopicsByCourse) {
                $totalTopics = (int) $totalTopics;
                $completedTopics = (int) ($completedTopicsByCourse[$courseId] ?? 0);
                $percentage = $totalTopics > 0
                    ? (int) round(($completedTopics / $totalTopics) * 100)
                    : 0;

                return [(string) $courseId => $percentage];
            })
            ->all();
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

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $weeklyQuestions = Cache::remember("profile_weekly_questions_{$user->id}_" . now()->format('Y-m-d'), 300, function () use ($user, $weekStart, $weekEnd) {
            return \App\Models\QuizAttempt::where('user_id', $user->id)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->sum('total_questions');
        });

        $weeklyGoal = $user->weekly_goal ?? 50;
        $progressPercent = $weeklyGoal > 0 ? min(100, ($weeklyQuestions / $weeklyGoal) * 100) : 0;
        $progressColor = $progressPercent >= 100 ? '#10b981' :
            ($progressPercent >= 70 ? '#3b82f6' :
            ($progressPercent >= 40 ? '#f59e0b' : '#ef4444'));

        // RUTHLESS OPTIMIZATION: Cached user rank with longer duration
        $userRank = null;
        if ($user->xp > 0 && $user->show_on_leaderboard) {
            $userRank = Cache::remember("user_rank_optimized_{$user->id}", 1800, function() use ($user) {
                return DB::table('users')
                    ->where('role', 3) // 3 = student role (integer)
                    ->where('show_on_leaderboard', true)
                    ->where('xp', '>', $user->xp)
                    ->count() + 1;
            });
        }

        // ULTRA-EFFICIENT: Group by attempt_id - multiple sources from same quiz = ONE point
        // REAL-TIME OPTIMIZATION: Reduced cache time for immediate chart updates
        $performanceData = Cache::remember("performance_data_optimized_{$user->id}", 30, function() use ($user) {
            // FINAL OPTIMIZATION: Pre-compute CASE statement to reduce DB::raw overhead
            $topicCase = DB::raw('CASE
                WHEN quiz_attempts.study_mode = "random" THEN "Random Quiz"
                WHEN quiz_attempts.quiz_type = "random" THEN "Random Quiz"
                WHEN quiz_attempts.topic_id IS NULL THEN "Unknown Topic"
                ELSE COALESCE(topics.name, "Unknown Topic")
            END as topic');
            
            // Get attempts grouped by attempt_id with combined scores
            $groupedAttempts = DB::table('quiz_attempts')
                ->leftJoin('topics', 'quiz_attempts.topic_id', '=', 'topics.id')
                ->where('quiz_attempts.user_id', $user->id)
                ->where('quiz_attempts.quiz_type', '!=', 'mistake_review')
                ->whereNotNull('quiz_attempts.attempt_id') // Only group attempts with attempt_id
                ->select([
                    'quiz_attempts.attempt_id',
                    DB::raw('MAX(quiz_attempts.created_at) as date'),
                    'quiz_attempts.topic_id',
                    'quiz_attempts.study_mode',
                    'quiz_attempts.quiz_type',
                    DB::raw('SUM(quiz_attempts.score) as total_score'),
                    DB::raw('SUM(quiz_attempts.total_questions) as total_questions'),
                    DB::raw('COUNT(*) as attempts_count'),
                    $topicCase
                ])
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
        $chartData = $performanceData
            ->sortBy('date')
            ->values()
            ->map(function($attempt) {
                $attemptDate = Carbon::parse($attempt->date);

                return [
                    'x' => $attemptDate->timestamp * 1000,
                    'y' => round($attempt->accuracy, 1),
                    'topic' => $attempt->topic ?? 'Unknown Topic',
                    'date' => $attemptDate->format('M j, Y g:i A')
                ];
            });

        $weakTopicCoach = $this->performanceTrackingService->getWeakTopicCoach($user, 5);
        
        // RUTHLESS OPTIMIZATION: Extended leaderboard caching - TEMPORARILY DISABLED FOR DEBUGGING
        $leaderboard = Cache::remember('profile_leaderboard_top10_v1', 300, function () {
            return DB::table('users')
                ->select(
                    'users.id',
                    'users.name',
                    'users.correct_answers_count',
                    'users.xp',
                    DB::raw('FLOOR(COALESCE(users.xp, 0) / 1000) + 1 as level')
                )
                ->where('users.xp', '>', 0)
                ->where('users.role', 3)
                ->where('users.show_on_leaderboard', true)
                ->orderByDesc('xp')
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
        });

        $tenthPlaceXp = count($leaderboard) >= 10 ? $leaderboard[9]->xp : 0;
        $currentUserView = [
            'name' => $user->name,
            'role' => $user->role,
            'is_admin' => $user->role === 'admin',
            'profile_image_url' => $user->getProfileImageUrl(),
            'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff',
            'member_since' => $user->created_at->format('F Y'),
            'xp' => (int) ($user->xp ?? 0),
            'weekly_goal' => (int) $weeklyGoal,
            'weekly_questions' => (int) $weeklyQuestions,
            'weekly_progress_percent' => $progressPercent,
            'weekly_progress_color' => $progressColor,
            'correct_answers_count' => (int) ($user->correct_answers_count ?? 0),
            'xp_needed_for_top_ten' => max(0, $tenthPlaceXp - (int) ($user->xp ?? 0)),
            'week_start_label' => $weekStart->format('M d'),
            'week_end_label' => $weekEnd->format('M d'),
            'exam_date' => optional($user->exam_date)->format('Y-m-d'),
            'target_score' => 80,
            'daily_study_minutes' => (int) ($user->daily_study_minutes ?? 90),
            'planner_focus' => $user->planner_focus ?? 'balanced',
            'repair_mode' => $user->repair_mode,
            'mistake_mastery_mode' => $user->mistake_mastery_mode,
            'show_on_leaderboard' => (bool) $user->show_on_leaderboard,
        ];

        return view('profile', compact(
            'accuracyRate',
            'totalQuestions',
            'leaderboard',
            'studyStreak',
            'chartData',
            'userRank',
            'formattedStudyTimes',
            'currentUserView',
            'weakTopicCoach'
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
                    $oldPath = str_starts_with($user->profile_picture_url, 'uploads/')
                        ? substr($user->profile_picture_url, strlen('uploads/'))
                        : $user->profile_picture_url;

                    $candidatePaths = [
                        Storage::disk('secure_uploads')->path($oldPath),
                        storage_path('app/uploads/' . $oldPath),
                    ];

                    foreach ($candidatePaths as $oldFilePath) {
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                            break;
                        }
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


    




    /**
     * Update user's weekly study goal
     */
    public function updateWeeklyGoal(Request $request)
    {
        try {
            $validated = $request->validate([
                'weekly_goal' => 'required|integer|min:10|max:500',
                'exam_date' => 'nullable|date|after_or_equal:today',
                'daily_study_minutes' => 'required|integer|min:30|max:480',
                'planner_focus' => 'required|in:balanced,new_topics,revision,exam_prep',
                'repair_mode' => 'nullable|in:easy,hard,spaced',
                'mistake_mastery_mode' => 'nullable|in:easy,hard',
            ]);

            $user = auth()->user();
            $user->weekly_goal = $validated['weekly_goal'];
            $user->exam_date = $validated['exam_date'] ?? null;
            $user->daily_study_minutes = $validated['daily_study_minutes'];
            $user->planner_focus = $validated['planner_focus'];
            $user->repair_mode = $validated['repair_mode'] ?? null;
            $user->mistake_mastery_mode = $validated['mistake_mastery_mode'] ?? null;
            $user->save();

            Cache::forget("user_profile_optimized_{$user->id}_" . now()->format('Y-m-d-H'));
            Cache::forget("profile_weekly_questions_{$user->id}_" . now()->format('Y-m-d'));

            return response()->json([
                'success' => true,
                'message' => 'Planner settings updated successfully!',
                'weekly_goal' => $user->weekly_goal,
                'exam_date' => optional($user->exam_date)->format('Y-m-d'),
                'target_score' => 80,
                'daily_study_minutes' => $user->daily_study_minutes,
                'planner_focus' => $user->planner_focus,
                'repair_mode' => $user->repair_mode,
                'mistake_mastery_mode' => $user->mistake_mastery_mode,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to update planner settings', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update planner settings. Please try again.'
            ], 500);
        }
    }

    public function updateCoursePlannerSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_id' => 'required|integer|exists:courses,id',
                'planner_exam_date' => 'nullable|date|after_or_equal:today',
                'planner_target_score' => 'nullable|integer|min:50|max:100',
                'planner_daily_study_minutes' => 'nullable|integer|min:15|max:480',
                'planner_focus' => 'nullable|in:balanced,new_topics,revision,exam_prep',
                'planner_preferred_source' => 'nullable|string|max:255',
                'planner_repair_mode' => 'nullable|in:easy,hard,spaced',
                'planner_mistake_mastery_mode' => 'nullable|in:easy,hard',
            ]);

            $user = auth()->user();
            $courseId = (int) $validated['course_id'];

            $enrollment = DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('is_enrolled', true)
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.',
                ], 403);
            }

            $availablePlannerColumns = $this->availableCoursePlannerColumns();

            if (empty($availablePlannerColumns)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course planner overrides are not available on this installation yet.',
                ], 409);
            }

            $requestedUpdates = [];
            foreach ([
                'planner_exam_date',
                'planner_target_score',
                'planner_daily_study_minutes',
                'planner_focus',
                'planner_preferred_source',
                'planner_repair_mode',
                'planner_mistake_mastery_mode',
            ] as $field) {
                if ($request->exists($field)) {
                    $requestedUpdates[$field] = $validated[$field] ?? null;
                }
            }

            $plannerUpdates = array_intersect_key($requestedUpdates, array_flip($availablePlannerColumns));
            $plannerUpdates['updated_at'] = now();

            DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->update($plannerUpdates);

            Cache::forget("user_profile_optimized_{$user->id}_" . now()->format('Y-m-d-H'));
            Cache::forget("profile_weekly_questions_{$user->id}_" . now()->format('Y-m-d'));

            $responsePayload = [
                'success' => true,
                'message' => 'Course planner settings updated successfully!',
                'course_id' => $courseId,
                'overrides' => [
                    'planner_exam_date' => $plannerUpdates['planner_exam_date'] ?? data_get($enrollment, 'planner_exam_date'),
                    'planner_target_score' => $plannerUpdates['planner_target_score'] ?? data_get($enrollment, 'planner_target_score'),
                    'planner_daily_study_minutes' => $plannerUpdates['planner_daily_study_minutes'] ?? data_get($enrollment, 'planner_daily_study_minutes'),
                    'planner_focus' => $plannerUpdates['planner_focus'] ?? data_get($enrollment, 'planner_focus'),
                    'planner_preferred_source' => $plannerUpdates['planner_preferred_source'] ?? data_get($enrollment, 'planner_preferred_source'),
                    'planner_repair_mode' => $plannerUpdates['planner_repair_mode'] ?? data_get($enrollment, 'planner_repair_mode'),
                    'planner_mistake_mastery_mode' => $plannerUpdates['planner_mistake_mastery_mode'] ?? data_get($enrollment, 'planner_mistake_mastery_mode'),
                ],
                'defaults' => [
                    'exam_date' => optional($user->exam_date)->format('Y-m-d'),
                    'target_score' => 80,
                    'daily_study_minutes' => (int) ($user->daily_study_minutes ?? 90),
                    'planner_focus' => (string) ($user->planner_focus ?? 'balanced'),
                    'repair_mode' => $user->repair_mode,
                    'mistake_mastery_mode' => $user->mistake_mastery_mode,
                ],
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($responsePayload);
            }

            return redirect()->back()->with('status', $responsePayload['message']);
        } catch (\Exception $e) {
            Log::error('Failed to update course planner settings', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update course planner settings. Please try again.',
            ], 500);
        }
    }

    private function availableCoursePlannerColumns(): array
    {
        if (self::$availableCoursePlannerColumns !== null) {
            return self::$availableCoursePlannerColumns;
        }

        $plannerColumns = [
            'planner_exam_date',
            'planner_target_score',
            'planner_daily_study_minutes',
            'planner_focus',
            'planner_preferred_source',
            'planner_repair_mode',
            'planner_mistake_mastery_mode',
        ];

        self::$availableCoursePlannerColumns = array_values(array_filter(
            $plannerColumns,
            static fn (string $column): bool => Schema::hasColumn('course_user', $column)
        ));

        return self::$availableCoursePlannerColumns;
    }

    /**
     * Update user's privacy settings (e.g. show on leaderboard)
     */
    public function updatePrivacy(Request $request)
    {
        try {
            $validated = $request->validate([
                'show_on_leaderboard' => 'required|boolean',
            ]);

            $user = auth()->user();
            $user->show_on_leaderboard = $validated['show_on_leaderboard'];
            $user->save();

            // Bust caches
            Cache::forget("user_profile_optimized_{$user->id}_" . now()->format('Y-m-d-H'));
            Cache::forget("user_rank_optimized_{$user->id}");
            Cache::forget('profile_leaderboard_top10_v1'); // Global leaderboard cache

            return response()->json([
                'success' => true,
                'message' => 'Privacy settings updated!',
                'show_on_leaderboard' => (bool) $user->show_on_leaderboard
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update privacy settings', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update privacy settings.'
            ], 500);
        }
    }
}
