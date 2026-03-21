<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class TutorDashboardViewController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tutor']);
    }

    /**
     * Display tutor dashboard - Optimized Implementation
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Aggressive caching for dashboard data
        $dashboardData = Cache::remember("tutor_dashboard_{$user->id}", 300, function() use ($user) {
            $students = $this->getStudentsForDashboard($user);
            $analytics = $this->getBasicAnalytics($students);
            
            return [
                'students' => $students,
                'analytics' => $analytics
            ];
        });
        
        return view('tutor.dashboard', [
            'students' => $dashboardData['students'],
            'analytics' => $dashboardData['analytics'],
            'tutor' => $user,
            'currentUserView' => [
                'is_admin' => $user->isAdmin(),
                'profile_image_url' => $user->getProfileImageUrl(),
                'has_profile_picture' => (bool) $user->profile_picture_url,
                'profile_initial' => strtoupper(substr($user->name, 0, 1)),
            ],
        ]);
    }

    /**
     * Show student's profile page for tutors - ULTRA-OPTIMIZED
     */
    public function showStudentProfile(Request $request, $studentId)
    {
        $user = auth()->user();

        // Verify access permissions FIRST with optimized query
        if (!$user || (!$user->isAdmin() && !$this->canAccessStudentOptimized($user, $studentId))) {
            if (!auth()->check()) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }
            return redirect()->route('tutor.dashboard')->with('error', 'You do not have permission to view this student\'s profile.');
        }

        // Generate cache key for student profile
        $cacheKey = "student_profile_ultra_{$studentId}_" . md5($studentId . $user->id);
        $cacheDuration = 300; // 5 minutes for real-time accuracy

        // ULTRA-OPTIMIZED: Single query with all relationships eagerly loaded
        $profileData = Cache::remember($cacheKey, $cacheDuration, function () use ($studentId, $user) {
            return $this->getOptimizedStudentProfile($studentId);
        });

        return view('tutor.student-profile', [
            'student' => $profileData['student'],
            'tutor' => $user,
            'data' => $profileData['analytics'],
            'assignments' => $profileData['assignments'],
            'currentUserView' => [
                'is_admin' => $user->isAdmin(),
                'profile_image_url' => $user->getProfileImageUrl(),
                'has_profile_picture' => (bool) $user->profile_picture_url,
                'profile_initial' => strtoupper(substr($user->name, 0, 1)),
            ],
        ]);
    }

    /**
     * Get students for dashboard - Optimized with single query
     * Only shows students enrolled in the same courses as the tutor
     */
    private function getStudentsForDashboard(User $user)
    {
        $cacheKey = "dashboard_students_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            $quizMetricsSubquery = DB::table('quiz_attempts')
                ->selectRaw('
                    user_id,
                    COALESCE(AVG(percentage_grade), 0) as quiz_average_score,
                    COUNT(*) as quiz_attempts_total
                ')
                ->groupBy('user_id');

            $query = User::query()
                ->select('users.*')
                ->leftJoinSub($quizMetricsSubquery, 'quiz_metrics', function ($join) {
                    $join->on('quiz_metrics.user_id', '=', 'users.id');
                })
                ->addSelect([
                    DB::raw('COALESCE(quiz_metrics.quiz_average_score, 0) as quiz_average_score'),
                    DB::raw('COALESCE(quiz_metrics.quiz_attempts_total, 0) as quiz_attempts_total'),
                ])
                ->where('role', User::ROLE_STUDENT);

            if (!$user->isAdmin()) {
                $tutorCourseIds = $user->courses()->pluck('courses.id')->map(fn ($id) => (int) $id)->all();

                if ($tutorCourseIds === []) {
                    return collect();
                }

                $query->join('course_user as shared_enrollments', function ($join) use ($tutorCourseIds) {
                    $join->on('shared_enrollments.user_id', '=', 'users.id')
                        ->where('shared_enrollments.is_enrolled', true)
                        ->whereIn('shared_enrollments.course_id', $tutorCourseIds);
                })->distinct();
            }

            $students = $query->orderBy('users.name')->get();
            $recentScoresByStudent = $this->getRecentAttemptScoresByStudent($students);

            return $students->map(function ($student) use ($recentScoresByStudent) {
                return $this->enhanceStudentData(
                    $student,
                    $recentScoresByStudent[$student->id] ?? []
                );
            });
        });
    }

    /**
     * Enhance student data with calculated metrics - Optimized
     */
    private function enhanceStudentData(User $student, array $recentScores = [])
    {
        $averageScore = round((float) ($student->quiz_average_score ?? 0), 1);
        $totalQuizzes = (int) ($student->quiz_attempts_total ?? 0);
        $trend = $this->calculatePerformanceTrend(collect($recentScores));
        
        $student->enhanced_metrics = [
            'average_score' => $averageScore,
            'score_class' => $this->resolveScoreClass($averageScore),
            'total_quizzes' => $totalQuizzes,
            'performance_trend' => $trend,
            'last_quiz_date' => null,
            'is_active' => $student->last_active_at && $student->last_active_at->gte(now()->subDays(3))];

        return $student;
    }

    /**
     * Get basic analytics for dashboard - Optimized
     */
    private function getBasicAnalytics($students)
    {
        $totalStudents = $students->count();
        $activeStudents = $students->filter(function ($student) {
            return $student->last_active_at && $student->last_active_at->gte(now()->subDays(7));
        })->count();

        $totalQuizzes = $students->sum(function ($student) {
            return $student->enhanced_metrics['total_quizzes'] ?? 0;
        });

        $averageScore = $students->avg(function ($student) {
            return $student->enhanced_metrics['average_score'] ?? 0;
        });

        $strugglingStudents = $students->filter(function ($student) {
            $avgScore = $student->enhanced_metrics['average_score'] ?? 0;
            $totalQuizzes = $student->enhanced_metrics['total_quizzes'] ?? 0;
            return $avgScore < 60 && $totalQuizzes > 0;
        })->count();

        $highPerformers = $students->filter(function ($student) {
            $avgScore = $student->enhanced_metrics['average_score'] ?? 0;
            return $avgScore >= 80;
        })->count();

        return [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'struggling_students' => $strugglingStudents,
            'high_performers' => $highPerformers,
            'total_quizzes' => $totalQuizzes,
            'class_average' => round($averageScore, 1),
            'average_score' => round($averageScore, 1),
            'engagement_rate' => $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0];
    }

    /**
     * Calculate performance trend efficiently
     */
    private function calculatePerformanceTrend($recentAttempts)
    {
        if ($recentAttempts->count() < 2) {
            return 'stable';
        }

        $recent = $recentAttempts->take(2)->avg('percentage_grade');
        $older = $recentAttempts->skip(2)->avg('percentage_grade');
        $difference = $recent - $older;

        if ($difference > 5) return 'improving';
        if ($difference < -5) return 'declining';
        return 'stable';
    }

    private function getRecentAttemptScoresByStudent($students): array
    {
        $studentIds = $students->pluck('id')->filter()->values()->all();

        if (empty($studentIds)) {
            return [];
        }

        $studentIdList = implode(',', array_map('intval', $studentIds));

        $recentAttempts = \DB::select("
            SELECT recent.user_id, recent.percentage_grade
            FROM (
                SELECT
                    qa.user_id,
                    qa.percentage_grade,
                    ROW_NUMBER() OVER (PARTITION BY qa.user_id ORDER BY qa.created_at DESC, qa.id DESC) AS row_num
                FROM quiz_attempts qa
                WHERE qa.user_id IN ({$studentIdList})
            ) AS recent
            WHERE recent.row_num <= 5
            ORDER BY recent.user_id, recent.row_num
        ");

        $groupedScores = [];

        foreach ($recentAttempts as $attempt) {
            $groupedScores[$attempt->user_id][] = (float) ($attempt->percentage_grade ?? 0);
        }

        return $groupedScores;
    }

    private function resolveScoreClass(float $score): string
    {
        if ($score >= 80) {
            return 'excellent';
        }

        if ($score >= 70) {
            return 'good';
        }

        if ($score >= 60) {
            return 'average';
        }

        return 'poor';
    }

    /**
     * Check if tutor can access specific student - ULTRA-OPTIMIZED
     * Tutors can only access students enrolled in their courses
     */
    private function canAccessStudent(User $tutor, User $student): bool
    {
        if ($tutor->isAdmin()) {
            return true;
        }

        // Get tutor's course IDs
        $tutorCourseIds = $tutor->courses()->pluck('courses.id');

        // Check if student is enrolled in any of the tutor's courses
        return $student->courses()
            ->whereIn('courses.id', $tutorCourseIds)
            ->whereNotNull('course_user.enrolled_at')
            ->exists();
    }

    /**
     * ULTRA-OPTIMIZED: Check tutor access with minimal database queries
     */
    private function canAccessStudentOptimized(User $tutor, int $studentId): bool
    {
        if ($tutor->isAdmin()) {
            return true;
        }

        // Single optimized query to check access
        return \DB::table('course_user as cu1')
            ->join('course_user as cu2', 'cu1.course_id', '=', 'cu2.course_id')
            ->where('cu1.user_id', $tutor->id)
            ->where('cu2.user_id', $studentId)
            ->where('cu1.is_enrolled', true)
            ->where('cu2.is_enrolled', true)
            ->exists();
    }

    /**
     * ULTRA-OPTIMIZED: Get complete student profile with single query and eager loading
     * Eliminates ALL N+1 query issues
     */
    private function getOptimizedStudentProfile(int $studentId): array
    {
        // 🔥 SINGLE QUERY WITH ALL RELATIONSHIPS EAGER LOADED 🔥
        $student = User::with([
            // Quiz attempts with all related data
            'quizAttempts' => function ($query) {
                $query->select([
                    'id', 'user_id', 'topic_id', 'percentage_grade', 'created_at',
                    'duration_seconds', 'questions_attempted', 'correct_answers'
                ])
                ->with([
                    'topic:id,name,course_id',
                    'topic.course:id,name'
                ])
                ->latest()
                ->take(50); // Limit for performance
            },

            // Assignments with relationships
            'assignments' => function ($query) {
                $query->select([
                    'id', 'user_id', 'topic_id', 'course_id', 'tutor_id', 'title',
                    'status', 'score_percentage', 'deadline', 'created_at', 'completed_at',
                    'duration_minutes', 'assignment_mode'
                ])
                ->with([
                    'topic:id,name',
                    'course:id,name',
                    'tutor:id,name'
                ])
                ->latest();
            },

            // Mistakes for analysis
            'mistakes' => function ($query) {
                $query->select(['id', 'user_id', 'question_id', 'mastered', 'created_at'])
                ->with([
                    'question:id,topic_id,difficulty',
                    'question.topic:id,name'
                ])
                ->latest()
                ->take(100);
            },

            // Course enrollments
            'courses:id,name'
        ])
        ->select([
            'id', 'name', 'email', 'university', 'created_at', 'last_active_at',
            'total_questions_attempted', 'correct_answers_count', 'study_streak_days', 'xp'
        ])
        ->findOrFail($studentId);

        // Calculate analytics in PHP (no additional queries)
        $analytics = $this->calculateAnalyticsFromLoadedData($student);

        // Format assignments data
        $assignments = $this->formatAssignmentsData($student->assignments);

        return [
            'student' => $student,
            'analytics' => $analytics,
            'assignments' => $assignments
        ];
    }

    /**
     * Calculate all analytics from pre-loaded data (NO additional queries)
     */
    private function calculateAnalyticsFromLoadedData(User $student): array
    {
        $quizAttempts = $student->quizAttempts;

        // Basic calculations
        $totalQuestions = $student->total_questions_attempted ?? 0;
        $totalCorrect = $student->correct_answers_count ?? 0;
        $accuracyRate = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 1) : 0;

        $averageScore = $quizAttempts->count() > 0 ? round($quizAttempts->avg('percentage_grade'), 1) : 0;
        $bestScore = $quizAttempts->count() > 0 ? $quizAttempts->max('percentage_grade') : 0;

        // Recent activity (last 30 days)
        $recentActivity = $quizAttempts->where('created_at', '>=', now()->subDays(30))->count();

        // Study patterns
        $studyPatterns = $this->calculateStudyPatterns($quizAttempts);

        // Performance by topic
        $topicPerformance = $this->calculateTopicPerformance($quizAttempts);

        // Real-time stats
        $realTimeStats = [
            'last_quiz_date' => $quizAttempts->first()?->created_at,
            'total_study_time' => $quizAttempts->sum('duration_seconds') / 60, // in minutes
            'average_session_time' => $quizAttempts->count() > 0 ? round($quizAttempts->avg('duration_seconds') / 60, 1) : 0,
            'improvement_rate' => $this->calculateImprovementRate($quizAttempts),
            'strength_areas' => $this->getStrengthAreas($quizAttempts),
            'weakness_areas' => $this->getWeaknessAreas($quizAttempts)
        ];

        return [
            'overview' => [
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'accuracy_rate' => $accuracyRate,
                'total_quizzes' => $quizAttempts->count(),
                'recent_activity_count' => $recentActivity,
                'study_streak' => $student->study_streak_days ?? 0,
                'total_questions_attempted' => $totalQuestions,
                'total_correct_answers' => $totalCorrect,
                'member_since' => $student->created_at,
                'last_active' => $student->last_active_at
            ],
            'recent_quizzes' => $quizAttempts->take(10)->map(function ($attempt) {
                return [
                    'topic' => $attempt->topic->name ?? 'Unknown Topic',
                    'score' => $attempt->percentage_grade,
                    'date' => $attempt->created_at,
                    'duration' => $attempt->duration_seconds ?? 0,
                    'questions_attempted' => $attempt->questions_attempted ?? 0,
                    'correct_answers' => $attempt->correct_answers ?? 0
                ];
            })->toArray(),
            'performance_by_topic' => $topicPerformance,
            'study_patterns' => $studyPatterns,
            'real_time_stats' => $realTimeStats,
            'performance_metrics' => [
                'average_score_in_completed_quizzes' => $averageScore
            ]
        ];
    }

    /**
     * Calculate study patterns from loaded data
     */
    private function calculateStudyPatterns($quizAttempts)
    {
        $recentAttempts = $quizAttempts->where('created_at', '>=', now()->subDays(30));

        return [
            'recent_activity' => $recentAttempts->count(),
            'average_session_time' => round($recentAttempts->avg('duration_seconds') / 60, 1),
            'most_active_day' => $this->getMostActiveDay($recentAttempts),
            'improvement_trend' => $this->calculatePerformanceTrend($recentAttempts->take(10))
        ];
    }

    /**
     * Calculate performance by topic from loaded data
     */
    private function calculateTopicPerformance($quizAttempts)
    {
        return $quizAttempts->groupBy('topic.name')
            ->map(function ($attempts, $topicName) {
                return [
                    'topic' => $topicName ?: 'Unknown Topic',
                    'attempts' => $attempts->count(),
                    'average_score' => round($attempts->avg('percentage_grade'), 1),
                    'best_score' => $attempts->max('percentage_grade'),
                    'last_attempt' => $attempts->first()->created_at
                ];
            })
            ->sortByDesc('attempts')
            ->values()
            ->toArray();
    }

    /**
     * Format assignments data from loaded relationships
     */
    private function formatAssignmentsData($assignments): array
    {
        return [
            'all_assignments' => $assignments->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'topic' => $assignment->topic->name ?? 'Unknown Topic',
                    'course' => $assignment->course->name ?? 'Unknown Course',
                    'tutor' => $assignment->tutor->name ?? 'Unknown Tutor',
                    'status' => $assignment->status,
                    'score_percentage' => $assignment->score_percentage,
                    'deadline' => $assignment->deadline,
                    'created_at' => $assignment->created_at,
                    'completed_at' => $assignment->completed_at,
                    'duration_minutes' => $assignment->duration_minutes,
                    'assignment_mode' => $assignment->assignment_mode,
                    'is_overdue' => $assignment->deadline && $assignment->deadline->isPast() && $assignment->status !== 'completed',
                    'is_active' => $assignment->status === 'active',
                    'is_completed' => $assignment->status === 'completed',
                    'time_remaining' => $assignment->deadline ? $this->getTimeRemaining($assignment->deadline) : 'No deadline',
                    'progress_percentage' => $assignment->score_percentage ?? 0
                ];
            })->toArray(),
            'completed_count' => $assignments->where('status', 'completed')->count(),
            'active_count' => $assignments->where('status', 'active')->count(),
            'overdue_count' => $assignments->filter(function ($assignment) {
                return $assignment->deadline && $assignment->deadline->isPast() && $assignment->status !== 'completed';
            })->count(),
            'total_count' => $assignments->count(),
            'average_score' => $assignments->where('status', 'completed')->avg('score_percentage') ?? 0
        ];
    }

    /**
     * Get time remaining for assignment
     */
    private function getTimeRemaining($deadline)
    {
        if (!$deadline) return 'No deadline';

        $now = now();
        if ($deadline->isPast()) {
            return 'Overdue';
        }

        $diff = $now->diff($deadline);
        if ($diff->days > 0) {
            return $diff->days . ' days remaining';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hours remaining';
        } else {
            return $diff->i . ' minutes remaining';
        }
    }


}
