<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'tutor' => $user]);
    }

    /**
     * Show student's profile page for tutors
     */
    public function showStudentProfile(Request $request, $studentId)
    {
        $user = auth()->user();
        $student = User::select('id', 'name', 'email', 'university', 'created_at', 'last_active_at', 'total_questions_attempted', 'correct_answers_count', 'study_streak_days', 'xp')->findOrFail($studentId);

        // Verify access permissions
        if (!$user->isAdmin() && !$this->canAccessStudent($user, $student)) {
            abort(403, 'You do not have permission to view this student\'s profile.');
        }

        // REAL-TIME DATA: No caching to ensure 100% accuracy
        $studentData = $this->getComprehensiveStudentData($student);

        // Get all assignments for this student
        $assignments = $this->getStudentAssignments($student);

        return view('tutor.student-profile', [
            'student' => $student,
            'tutor' => $user,
            'data' => $studentData,
            'assignments' => $assignments]);
    }

    /**
     * Get students for dashboard - Optimized with single query
     * Only shows students enrolled in the same courses as the tutor
     */
    private function getStudentsForDashboard(User $user)
    {
        $cacheKey = "dashboard_students_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            $query = User::where('role', User::ROLE_STUDENT);

            if (!$user->isAdmin()) {
                // Get tutor's course IDs
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                
                // Only show students enrolled in the same courses as the tutor
                $query->whereHas('courses', function ($q) use ($tutorCourseIds) {
                    $q->whereIn('courses.id', $tutorCourseIds)
                      ->where('course_user.is_enrolled', true);
                });
            }

            return $query->with([
                'quizAttempts' => function ($query) {
                    $query->latest()->take(10)->with('topic:id,name');
                }
            ])
            ->withCount('quizAttempts')
            ->get()
            ->map(function ($student) {
                return $this->enhanceStudentData($student);
            });
        });
    }

    /**
     * Enhance student data with calculated metrics - Optimized
     */
    private function enhanceStudentData(User $student)
    {
        $quizAttempts = $student->quizAttempts;
        $averageScore = $quizAttempts->avg('score') ?? 0;
        $recentAttempts = $quizAttempts->take(5);
        
        $trend = $this->calculatePerformanceTrend($recentAttempts);
        
        $student->enhanced_metrics = [
            'average_score' => round($averageScore, 1),
            'total_quizzes' => $quizAttempts->count(),
            'performance_trend' => $trend,
            'last_quiz_date' => $recentAttempts->first()?->created_at,
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
            return $student->quizAttempts->count();
        });

        $averageScore = $students->avg(function ($student) {
            return $student->quizAttempts->avg('score') ?? 0;
        });

        $strugglingStudents = $students->filter(function ($student) {
            $avgScore = $student->quizAttempts->avg('score') ?? 0;
            return $avgScore < 60 && $student->quizAttempts->count() > 0;
        })->count();

        $highPerformers = $students->filter(function ($student) {
            $avgScore = $student->quizAttempts->avg('score') ?? 0;
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

        $recent = $recentAttempts->take(2)->avg('score');
        $older = $recentAttempts->skip(2)->avg('score');
        $difference = $recent - $older;

        if ($difference > 5) return 'improving';
        if ($difference < -5) return 'declining';
        return 'stable';
    }

    /**
     * Check if tutor can access specific student
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
            ->where('course_user.is_enrolled', true)
            ->exists();
    }

    /**
     * Get comprehensive student data for profile view - REAL-TIME ACCURACY
     */
    private function getComprehensiveStudentData(User $student): array
    {
        // FRESH DATA: Get real-time quiz attempts without caching
        $quizAttempts = $student->quizAttempts()
            ->with('topic:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        // REAL-TIME CALCULATIONS
        $totalQuestions = $student->total_questions_attempted ?? 0;
        $totalCorrect = $student->correct_answers_count ?? 0;
        $accuracyRate = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 1) : 0;
        
        // Calculate real-time averages
        $averageScore = $quizAttempts->count() > 0 ? round($quizAttempts->avg('score'), 1) : 0;
        $bestScore = $quizAttempts->count() > 0 ? $quizAttempts->max('score') : 0;
        
        // Recent activity (last 30 days)
        $recentActivity = $quizAttempts->where('created_at', '>=', now()->subDays(30))->count();
        
        // Study streak calculation
        $studyStreak = $student->study_streak_days ?? 0;
        
        // Last active calculation
        $lastActive = $student->last_active_at;
        $isCurrentlyActive = $lastActive && $lastActive->gte(now()->subMinutes(15));

        return [
            'overview' => [
                'total_quizzes' => $quizAttempts->count(),
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'accuracy_rate' => $accuracyRate,
                'study_streak' => $studyStreak,
                'total_xp' => $student->xp ?? 0,
                'member_since' => $student->created_at ?? now(),
                'last_active' => $lastActive,
                'is_currently_active' => $isCurrentlyActive,
                'total_questions_attempted' => $totalQuestions,
                'total_correct_answers' => $totalCorrect
            ],
            'recent_quizzes' => $quizAttempts->take(10)->map(function ($attempt) {
                return [
                    'topic' => $attempt->topic->name ?? 'Unknown Topic',
                    'score' => $attempt->score,
                    'date' => $attempt->created_at,
                    'duration' => $attempt->duration_seconds ?? 0,
                    'questions_attempted' => $attempt->questions_attempted ?? 0,
                    'correct_answers' => $attempt->correct_answers ?? 0
                ];
            })->toArray(),
            'performance_by_topic' => $this->getTopicPerformance($quizAttempts),
            'study_patterns' => $this->getStudyPatterns($quizAttempts),
            'real_time_stats' => [
                'last_quiz_date' => $quizAttempts->first()?->created_at,
                'total_study_time' => $quizAttempts->sum('duration_seconds') / 60, // in minutes
                'average_session_time' => $quizAttempts->count() > 0 ? round($quizAttempts->avg('duration_seconds') / 60, 1) : 0,
                'improvement_rate' => $this->calculateImprovementRate($quizAttempts),
                'strength_areas' => $this->getStrengthAreas($quizAttempts),
                'weakness_areas' => $this->getWeaknessAreas($quizAttempts)
            ]
        ];
    }

    /**
     * Get performance breakdown by topic
     */
    private function getTopicPerformance($quizAttempts)
    {
        return $quizAttempts->groupBy('topic.name')
            ->map(function ($attempts, $topicName) {
                return [
                    'topic' => $topicName ?: 'Unknown Topic',
                    'attempts' => $attempts->count(),
                    'average_score' => round($attempts->avg('score'), 1),
                    'best_score' => $attempts->max('score'),
                    'last_attempt' => $attempts->first()->created_at];
            })
            ->sortByDesc('attempts')
            ->values()
            ->toArray();
    }

    /**
     * Get study patterns and insights
     */
    private function getStudyPatterns($quizAttempts)
    {
        $recentAttempts = $quizAttempts->where('created_at', '>=', now()->subDays(30));
        
        return [
            'recent_activity' => $recentAttempts->count(),
            'average_session_time' => round($recentAttempts->avg('duration_seconds') / 60, 1),
            'most_active_day' => $this->getMostActiveDay($recentAttempts),
            'improvement_trend' => $this->calculatePerformanceTrend($recentAttempts->take(10))];
    }

    /**
     * Get most active day of the week
     */
    private function getMostActiveDay($attempts)
    {
        $dayCount = $attempts->groupBy(function ($attempt) {
            return $attempt->created_at->format('l');
        })->map->count()->sortDesc();

        return $dayCount->keys()->first() ?? 'No data';
    }

    /**
     * Calculate improvement rate based on recent vs older performance
     */
    private function calculateImprovementRate($quizAttempts)
    {
        if ($quizAttempts->count() < 2) return 0;
        
        $recentAttempts = $quizAttempts->take(5);
        $olderAttempts = $quizAttempts->skip(5)->take(5);
        
        if ($olderAttempts->count() == 0) return 0;
        
        $recentAverage = $recentAttempts->avg('score');
        $olderAverage = $olderAttempts->avg('score');
        
        return round((($recentAverage - $olderAverage) / $olderAverage) * 100, 1);
    }

    /**
     * Get student's strength areas (topics with >80% average)
     */
    private function getStrengthAreas($quizAttempts)
    {
        return $quizAttempts->groupBy('topic.name')
            ->map(function ($attempts, $topicName) {
                $avgScore = $attempts->avg('score');
                return [
                    'topic' => $topicName ?: 'Unknown Topic',
                    'average_score' => round($avgScore, 1),
                    'attempts' => $attempts->count()
                ];
            })
            ->filter(function ($topic) {
                return $topic['average_score'] >= 80;
            })
            ->sortByDesc('average_score')
            ->values()
            ->take(3)
            ->toArray();
    }

    /**
     * Get student's weakness areas (topics with <60% average)
     */
    private function getWeaknessAreas($quizAttempts)
    {
        return $quizAttempts->groupBy('topic.name')
            ->map(function ($attempts, $topicName) {
                $avgScore = $attempts->avg('score');
                return [
                    'topic' => $topicName ?: 'Unknown Topic',
                    'average_score' => round($avgScore, 1),
                    'attempts' => $attempts->count()
                ];
            })
            ->filter(function ($topic) {
                return $topic['average_score'] < 60 && $topic['attempts'] >= 2;
            })
            ->sortBy('average_score')
            ->values()
            ->take(3)
            ->toArray();
    }

    /**
     * Get all assignments for a student with real-time status
     */
    private function getStudentAssignments(User $student): array
    {
        $assignments = \App\Models\Assignment::where('user_id', $student->id)
            ->with(['topic:id,name', 'course:id,name', 'tutor:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

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
                    'completed_at' => $assignment->completed_at ?? null,
                    'duration_minutes' => $assignment->duration_minutes,
                    'assignment_mode' => $assignment->assignment_mode,
                    'is_overdue' => $assignment->isOverdue(),
                    'is_active' => $assignment->isActive(),
                    'is_completed' => $assignment->status === \App\Models\Assignment::STATUS_COMPLETED,
                    'time_remaining' => $assignment->getTimeRemainingHuman(),
                    'progress_percentage' => $assignment->getProgressPercentage()
                ];
            })->toArray(),
            'completed_count' => $assignments->where('status', \App\Models\Assignment::STATUS_COMPLETED)->count(),
            'active_count' => $assignments->where('status', \App\Models\Assignment::STATUS_ACTIVE)->count(),
            'overdue_count' => $assignments->where('status', \App\Models\Assignment::STATUS_OVERDUE)->count(),
            'total_count' => $assignments->count(),
            'average_score' => $assignments->where('status', \App\Models\Assignment::STATUS_COMPLETED)->avg('score_percentage') ?? 0
        ];
    }
}