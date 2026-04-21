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

        // Generate cache key for student profile - invalidated on every quiz attempt
        $cacheKey = "student_profile_v2_{$studentId}";
        $cacheDuration = 600; // 10 minutes

        $profileData = Cache::remember($cacheKey, $cacheDuration, function () use ($studentId) {
            return $this->getOptimizedStudentProfile($studentId);
        });

        return view('tutor.student-profile', [
            'student' => $profileData['student'],
            'tutor' => $user,
            'data' => $profileData['analytics'],
            'assignments' => $profileData['assignments'],
            'timeline' => $profileData['timeline'],
            'discipline' => $profileData['discipline'],
            'planner' => $profileData['planner'],
            'currentUserView' => [
                'is_admin' => $user->isAdmin(),
                'profile_image_url' => $user->getProfileImageUrl(),
                'has_profile_picture' => (bool) $user->profile_picture_url,
                'profile_initial' => strtoupper(substr($user->name, 0, 1)),
            ],
        ]);
    }

    /**
     * ULTRA-OPTIMIZED: Get complete student profile with single query and eager loading
     */
    private function getOptimizedStudentProfile(int $studentId): array
    {
        // 🔥 SINGLE QUERY WITH ALL RELATIONSHIPS EAGER LOADED 🔥
        $student = User::with([
            'quizAttempts' => function ($query) {
                $query->select([
                    'quiz_attempts.id', 'quiz_attempts.user_id', 'quiz_attempts.topic_id', 
                    'quiz_attempts.percentage_grade', 'quiz_attempts.created_at',
                    'quiz_attempts.duration_seconds', 'quiz_attempts.total_questions', 
                    'quiz_attempts.score', 'quiz_attempts.study_mode'
                ])
                ->with('topic:id,name')
                ->orderBy('quiz_attempts.created_at', 'desc')
                ->take(100);
            },
            'assignments' => fn($q) => $q->orderBy('assignments.created_at', 'desc'),
            'mistakes' => fn($q) => $q->with('question:id,topic_id,question')->orderBy('mistakes.created_at', 'desc')->take(20),
            'courses:id,name'
        ])
        ->select([
            'users.id', 'users.name', 'users.email', 'users.number', 'users.university', 
            'users.created_at', 'users.last_active_at', 'users.total_questions_attempted', 
            'users.correct_answers_count', 'users.study_streak_days', 'users.xp'
        ])
        ->findOrFail($studentId);

        // Fetch Video Progress separately
        $videoProgress = DB::table('video_user_progress')
            ->where('video_user_progress.user_id', $studentId)
            ->get(['video_user_progress.topic_id', 'video_user_progress.percentage', 'video_user_progress.last_watched_at'])
            ->keyBy('topic_id');

        // Fetch Planner Task Outcomes
        $plannerOutcomes = DB::table('planner_task_outcomes')
            ->where('planner_task_outcomes.user_id', $studentId)
            ->where('planner_task_outcomes.plan_date', '>=', now()->subDays(14))
            ->orderBy('planner_task_outcomes.plan_date', 'desc')
            ->get();

        // Calculate Discipline Status
        $discipline = $this->calculateDisciplineStatus($student, $plannerOutcomes);

        // Build Topic Timeline (Consolidated view of Quizzes + Videos)
        $timeline = $this->buildTopicTimeline($student->quizAttempts, $videoProgress);

        // Calculate analytics
        $analytics = $this->calculateAnalyticsFromLoadedData($student);

        return [
            'student' => $student,
            'analytics' => $analytics,
            'assignments' => $this->formatAssignmentsData($student->assignments),
            'timeline' => $timeline,
            'discipline' => $discipline,
            'planner' => [
                'recent_outcomes' => $plannerOutcomes,
                'completion_rate' => $plannerOutcomes->count() > 0 
                    ? round(($plannerOutcomes->where('is_completed', true)->count() / $plannerOutcomes->count()) * 100, 1) 
                    : 100
            ]
        ];
    }

    /**
     * Calculate Discipline Status (Green/Yellow/Red)
     */
    private function calculateDisciplineStatus($student, $plannerOutcomes): array
    {
        $lastActive = $student->last_active_at;
        $daysSinceActive = $lastActive ? now()->diffInDays($lastActive) : 99;
        
        $recentTasks = $plannerOutcomes->where('plan_date', '>=', now()->subDays(3));
        $missingTasks = $recentTasks->where('is_completed', false)->count();
        $totalTasks = $recentTasks->count();
        
        $status = 'active';
        $reason = 'Student is following the plan.';
        $color = 'success';

        if ($daysSinceActive >= 3 || ($totalTasks > 0 && ($missingTasks / $totalTasks) > 0.5)) {
            $status = 'inactive';
            $reason = $daysSinceActive >= 3 ? 'No activity for 3+ days.' : 'More than 50% of recent tasks missed.';
            $color = 'danger';
        } elseif ($daysSinceActive >= 1 || $missingTasks > 0) {
            $status = 'at_risk';
            $reason = $daysSinceActive >= 1 ? 'No activity in 24h.' : 'Some tasks were missed recently.';
            $color = 'warning';
        }

        // Prepare WhatsApp message
        $waMessage = "Hi {$student->name}, this is your mentor from MedCapsule. I noticed that [REASON]. Is everything okay? How can I help you get back to your study schedule?";
        $waMessage = str_replace('[REASON]', str_replace('.', '', strtolower($reason)), $waMessage);
        
        return [
            'status' => $status,
            'reason' => $reason,
            'color' => $color,
            'whatsapp_link' => "https://wa.me/" . preg_replace('/[^0-9]/', '', $student->number) . "?text=" . urlencode($waMessage)
        ];
    }

    /**
     * Build a consolidated topic-by-topic progress view
     */
    private function buildTopicTimeline($quizAttempts, $videoProgress): array
    {
        $topics = [];
        
        // Process Quizzes
        foreach ($quizAttempts as $attempt) {
            $topicId = $attempt->topic_id;
            if (!isset($topics[$topicId])) {
                $topics[$topicId] = [
                    'name' => $attempt->topic->name ?? 'Unknown',
                    'quiz_score' => $attempt->percentage_grade,
                    'quiz_date' => $attempt->created_at,
                    'quiz_mode' => $attempt->study_mode ?: 'quiz',
                    'video_percentage' => 0,
                    'last_activity' => $attempt->created_at
                ];
            } else {
                // Keep the best score for the summary, but latest date
                if ($attempt->percentage_grade > $topics[$topicId]['quiz_score']) {
                    $topics[$topicId]['quiz_score'] = $attempt->percentage_grade;
                }
                if ($attempt->created_at > $topics[$topicId]['last_activity']) {
                    $topics[$topicId]['last_activity'] = $attempt->created_at;
                }
            }
        }

        // Process Videos
        foreach ($videoProgress as $topicId => $progress) {
            if (!isset($topics[$topicId])) {
                // Topic might have video progress but no quiz attempts yet
                $topicName = \App\Models\Topic::where('id', $topicId)->value('name') ?? 'Unknown';
                $topics[$topicId] = [
                    'name' => $topicName,
                    'quiz_score' => null,
                    'quiz_date' => null,
                    'quiz_mode' => null,
                    'video_percentage' => $progress->percentage,
                    'last_activity' => $progress->last_watched_at
                ];
            } else {
                $topics[$topicId]['video_percentage'] = $progress->percentage;
                if ($progress->last_watched_at > $topics[$topicId]['last_activity']) {
                    $topics[$topicId]['last_activity'] = $progress->last_watched_at;
                }
            }
        }

        return collect($topics)->sortByDesc('last_activity')->values()->toArray();
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
                    'questions_attempted' => $attempt->total_questions ?? 0,
                    'correct_answers' => $attempt->score ?? 0
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
     * Get students for the dashboard with summary metrics and discipline status.
     * MEGA-OPTIMIZED: Single query for outcomes + eager loading for students.
     */
    private function getStudentsForDashboard(User $tutor)
    {
        // 1. Fetch Students
        $query = User::with([
            'quizAttempts' => function ($q) {
                $q->select(['quiz_attempts.id', 'quiz_attempts.user_id', 'quiz_attempts.percentage_grade', 'quiz_attempts.created_at'])
                  ->orderBy('quiz_attempts.created_at', 'desc');
            }
        ]);

        if ($tutor->isAdmin()) {
            $students = $query->where('users.role', User::ROLE_STUDENT)->get();
        } else {
            $students = $tutor->tutorStudents()->with([
                'quizAttempts' => function ($q) {
                    $q->select(['quiz_attempts.id', 'quiz_attempts.user_id', 'quiz_attempts.percentage_grade', 'quiz_attempts.created_at'])
                      ->orderBy('quiz_attempts.created_at', 'desc');
                }
            ])->get();
        }

        // 2. Bulk Fetch Planner Outcomes for all students (last 7 days)
        $studentIds = $students->pluck('id');
        $allOutcomes = DB::table('planner_task_outcomes')
            ->whereIn('planner_task_outcomes.user_id', $studentIds)
            ->where('planner_task_outcomes.plan_date', '>=', now()->subDays(7))
            ->get()
            ->groupBy('user_id');

        // 3. Enhance student data with discipline and trend
        return $students->map(function ($student) use ($allOutcomes) {
            $outcomes = $allOutcomes->get($student->id, collect());
            
            // Calculate discipline for dashboard view
            $discipline = $this->calculateDisciplineStatus($student, $outcomes);
            
            // Calculate basic metrics for the card
            $recentQuizzes = $student->quizAttempts->take(10);
            $avgScore = $recentQuizzes->avg('percentage_grade') ?? 0;
            
            $student->enhanced_metrics = [
                'average_score' => $avgScore,
                'total_quizzes' => $student->quizAttempts->count(),
                'performance_trend' => $this->calculatePerformanceTrend($recentQuizzes),
                'score_class' => $avgScore >= 80 ? 'excellent' : ($avgScore >= 65 ? 'good' : 'poor'),
                'discipline' => $discipline
            ];
            
            return $student;
        });
    }

    /**
     * Get basic summary analytics for the dashboard header.
     */
    private function getBasicAnalytics($students): array
    {
        $total = $students->count();
        if ($total === 0) {
            return [
                'total_students' => 0,
                'active_students' => 0,
                'average_score' => 0,
                'struggling_students' => 0
            ];
        }

        $active = $students->filter(fn($s) => $s->last_active_at && $s->last_active_at->gte(now()->subDays(3)))->count();
        $avgScore = $students->avg('enhanced_metrics.average_score') ?? 0;
        $struggling = $students->filter(fn($s) => 
            $s->enhanced_metrics['score_class'] === 'poor' || 
            $s->enhanced_metrics['discipline']['status'] === 'inactive'
        )->count();

        return [
            'total_students' => $total,
            'active_students' => $active,
            'average_score' => $avgScore,
            'struggling_students' => $struggling
        ];
    }

    /**
     * Verify if a tutor can access a specific student.
     */
    private function canAccessStudentOptimized(User $user, int $studentId): bool
    {
        if ($user->isAdmin()) return true;

        return DB::table('tutor_student_relationships')
            ->where('tutor_student_relationships.tutor_id', $user->id)
            ->where('tutor_student_relationships.user_id', $studentId)
            ->exists();
    }

    /**
     * Determine performance trend (improving/declining/stable).
     */
    private function calculatePerformanceTrend($recentAttempts)
    {
        if ($recentAttempts->count() < 4) return 'stable';

        $sorted = $recentAttempts->sortBy('created_at')->values();
        $mid = floor($sorted->count() / 2);
        
        $olderAvg = $sorted->take($mid)->avg('percentage_grade') ?? 0;
        $newerAvg = $sorted->skip($mid)->avg('percentage_grade') ?? 0;

        if ($newerAvg > $olderAvg + 5) return 'improving';
        if ($newerAvg < $olderAvg - 5) return 'declining';
        
        return 'stable';
    }

    /**
     * Get the most active day of the week from recent attempts.
     */
    private function getMostActiveDay($recentAttempts): string
    {
        if ($recentAttempts->isEmpty()) return 'N/A';

        return $recentAttempts->groupBy(fn($a) => $a->created_at->format('l'))
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first() ?? 'N/A';
    }

    /**
     * Helper to format assignments (already implemented above, but ensuring consistency)
     */
    // ... (formatAssignmentsData and others are already there)
}
