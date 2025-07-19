<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TutorDashboardController extends Controller
{
    protected $cacheService;
    
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->middleware(['auth', 'tutor']);
    }

    /**
     * Display tutor dashboard
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // For admins, show all students or filter by tutor if specified
        if ($user->isAdmin()) {
            $tutorId = $request->get('tutor_id');
            $students = $this->getStudentsForAdmin($tutorId);
        } else {
            $students = $this->getStudentsForTutor($user->id);
        }

        return view('tutor.dashboard', [
            'students' => $students,
            'analytics' => $this->getAnalytics($students),
            'recentActivity' => $this->getRecentActivity($students->pluck('id')),
        ]);
    }

    /**
     * Get students for admin with optional tutor filter
     */
    private function getStudentsForAdmin(?int $tutorId)
    {
        $cacheKey = "admin.students" . ($tutorId ? ".tutor_{$tutorId}" : "");
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($tutorId) {
            $query = User::where('role', User::ROLE_STUDENT)
                        ->with(['quizAttempts' => function ($query) {
                            $query->latest()->take(5);
                        }])
                        ->withCount(['quizAttempts', 'courses']);

            if ($tutorId) {
                $query->whereHas('assignedTutors', function ($q) use ($tutorId) {
                    $q->where('tutor_id', $tutorId);
                });
            }

            return $query->get();
        });
    }

    /**
     * Get students for specific tutor with performance metrics
     */
    private function getStudentsForTutor(int $tutorId)
    {
        $cacheKey = "tutor.{$tutorId}.students";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($tutorId) {
            return User::where('role', User::ROLE_STUDENT)
                ->whereHas('assignedTutors', function ($query) use ($tutorId) {
                    $query->where('tutor_id', $tutorId);
                })
                ->with(['quizAttempts' => function ($query) {
                    $query->latest()->take(5);
                }])
                ->withCount(['quizAttempts', 'courses'])
                ->get();
        });
    }

    /**
     * Get analytics for a collection of students
     */
    private function getAnalytics($students)
    {
        $studentIds = $students->pluck('id');
        $cacheKey = "analytics.students." . implode(',', $studentIds->toArray());

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($students) {
            $analytics = [
                'total_students' => $students->count(),
                'active_today' => $students->filter(function ($student) {
                    return $student->last_active_at >= Carbon::today();
                })->count(),
                'course_completion' => $this->calculateCourseCompletion($students),
                'quiz_performance' => $this->calculateQuizPerformance($students),
                'engagement_metrics' => $this->calculateEngagementMetrics($students),
            ];

            return $analytics;
        });
    }

    /**
     * Get recent activity for specified students
     */
    private function getRecentActivity($studentIds)
    {
        $cacheKey = "recent_activity.students." . implode(',', $studentIds->toArray());

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($studentIds) {
            return \App\Models\QuizAttempt::whereIn('user_id', $studentIds)
                ->with(['user:id,name', 'quiz:id,name'])
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($attempt) {
                    return [
                        'type' => 'quiz_attempt',
                        'user' => $attempt->user->name,
                        'description' => "Completed {$attempt->quiz->name} with score {$attempt->score}%",
                        'timestamp' => $attempt->created_at,
                    ];
                });
        });
    }

    /**
     * Calculate course completion metrics
     */
    private function calculateCourseCompletion($students)
    {
        $totalCourses = 0;
        $completedCourses = 0;

        foreach ($students as $student) {
            $courses = $student->courses;
            $totalCourses += $courses->count();
            $completedCourses += $courses->where('pivot.completion_percentage', 100)->count();
        }

        return [
            'total' => $totalCourses,
            'completed' => $completedCourses,
            'completion_rate' => $totalCourses > 0 ? ($completedCourses / $totalCourses) * 100 : 0,
        ];
    }

    /**
     * Calculate quiz performance metrics
     */
    private function calculateQuizPerformance($students)
    {
        $totalAttempts = 0;
        $totalScore = 0;

        foreach ($students as $student) {
            $attempts = $student->quizAttempts;
            $totalAttempts += $attempts->count();
            $totalScore += $attempts->sum('score');
        }

        return [
            'total_attempts' => $totalAttempts,
            'average_score' => $totalAttempts > 0 ? $totalScore / $totalAttempts : 0,
        ];
    }

    /**
     * Calculate engagement metrics
     */
    private function calculateEngagementMetrics($students)
    {
        $activeLastWeek = $students->filter(function ($student) {
            return $student->last_active_at >= Carbon::now()->subWeek();
        })->count();

        return [
            'active_last_week' => $activeLastWeek,
            'engagement_rate' => ($activeLastWeek / $students->count()) * 100,
        ];
    }
} 