<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class StudentProgressController extends Controller
{
    /**
     * Show detailed student progress
     */
    public function show(Request $request, $student_id)
    {
        $student = User::findOrFail($student_id);
        
        // Verify access through middleware
        if (!auth()->user()->isAdmin() && !auth()->user()->assignedStudents()->where('student_id', $student_id)->exists()) {
            abort(403, 'Unauthorized access to student data');
        }

        // Cache key based on student and recent updates
        $cacheKey = "student_progress_{$student_id}_" . $student->updated_at->timestamp;
        
        // Get detailed analytics with 15-minute cache
        $analytics = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($student) {
            $analytics = [
                'overview' => $this->getStudentOverview($student),
                'courseProgress' => $this->getCourseProgress($student),
                'quizPerformance' => $this->getQuizPerformance($student),
                'weeklyActivity' => $this->getWeeklyActivity($student),
                'strengthsWeaknesses' => $this->getStrengthsWeaknesses($student),
                'recentActivity' => $this->getRecentActivity($student),
            ];

            // Verify data integrity
            $this->verifyDataIntegrity($student, $analytics);

            return $analytics;
        });

        // Broadcast update to all tutors assigned to this student
        $this->broadcastProgressUpdate($student, $analytics);

        return view('tutor.student-progress', [
            'student' => $student,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Verify data integrity of analytics
     */
    private function verifyDataIntegrity(User $student, array $analytics): void
    {
        // Verify course completion percentages
        $courseCompletions = $student->courses()
            ->withPivot('completion_percentage')
            ->get()
            ->pluck('pivot.completion_percentage');

        $reportedAverage = $analytics['courseProgress']['average_completion'];
        $actualAverage = $courseCompletions->avg();

        if (abs($reportedAverage - $actualAverage) > 0.1) { // Allow 0.1% difference for floating-point
            \Log::error("Data integrity issue detected for student {$student->id}: Course completion mismatch", [
                'reported' => $reportedAverage,
                'actual' => $actualAverage,
            ]);
            Cache::forget("student_progress_{$student->id}_" . $student->updated_at->timestamp);
        }

        // Verify quiz scores
        $quizScores = $student->quizAttempts()->pluck('score');
        $reportedAverage = $analytics['overview']['average_quiz_score'];
        $actualAverage = $quizScores->avg();

        if (abs($reportedAverage - $actualAverage) > 0.1) {
            \Log::error("Data integrity issue detected for student {$student->id}: Quiz score mismatch", [
                'reported' => $reportedAverage,
                'actual' => $actualAverage,
            ]);
            Cache::forget("student_progress_{$student->id}_" . $student->updated_at->timestamp);
        }
    }

    /**
     * Broadcast progress update to relevant channels
     */
    private function broadcastProgressUpdate(User $student, array $analytics): void
    {
        // Get all tutors assigned to this student
        $assignedTutors = $student->assignedTutors()->pluck('id');

        // Broadcast to each tutor's channel
        foreach ($assignedTutors as $tutorId) {
            event(new \App\Events\StudentProgressUpdated($student, $analytics, $tutorId));
        }
    }

    /**
     * Get real-time updates for a specific student
     */
    public function getRealtimeUpdates(Request $request, $student_id)
    {
        $student = User::findOrFail($student_id);
        
        // Verify access
        if (!auth()->user()->isAdmin() && !auth()->user()->assignedStudents()->where('student_id', $student_id)->exists()) {
            abort(403, 'Unauthorized access to student data');
        }

        // Get latest analytics without caching
        $analytics = [
            'overview' => $this->getStudentOverview($student),
            'courseProgress' => $this->getCourseProgress($student),
            'quizPerformance' => $this->getQuizPerformance($student),
            'weeklyActivity' => $this->getWeeklyActivity($student),
            'strengthsWeaknesses' => $this->getStrengthsWeaknesses($student),
            'recentActivity' => $this->getRecentActivity($student),
        ];

        // Verify data integrity
        $this->verifyDataIntegrity($student, $analytics);

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get student overview metrics
     */
    private function getStudentOverview(User $student): array
    {
        return [
            'total_courses' => $student->courses()->count(),
            'completed_courses' => $student->courses()
                ->wherePivot('completion_percentage', 100)
                ->count(),
            'average_quiz_score' => $student->quizAttempts()
                ->avg('score') ?? 0,
            'total_study_time' => $student->courses()
                ->sum('pivot.time_spent_minutes'),
            'last_active' => $student->last_active_at,
            'join_date' => $student->created_at,
        ];
    }

    /**
     * Get detailed course progress
     */
    private function getCourseProgress(User $student): array
    {
        $courses = $student->courses()
            ->withPivot(['completion_percentage', 'time_spent_minutes', 'last_accessed_at'])
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'completion' => $course->pivot->completion_percentage,
                    'time_spent' => $course->pivot->time_spent_minutes,
                    'last_accessed' => $course->pivot->last_accessed_at,
                    'status' => $this->getCourseStatus($course->pivot->completion_percentage),
                ];
            });

        return [
            'courses' => $courses,
            'average_completion' => $courses->avg('completion'),
            'total_time_spent' => $courses->sum('time_spent'),
        ];
    }

    /**
     * Get detailed quiz performance metrics
     */
    private function getQuizPerformance(User $student): array
    {
        $attempts = $student->quizAttempts()
            ->with('quiz:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        $topicPerformance = $attempts
            ->groupBy('quiz.topic_id')
            ->map(function ($topicAttempts) {
                return [
                    'average_score' => $topicAttempts->avg('score'),
                    'attempts' => $topicAttempts->count(),
                    'improvement' => $this->calculateImprovement($topicAttempts),
                ];
            });

        return [
            'recent_attempts' => $attempts->take(5),
            'topic_performance' => $topicPerformance,
            'overall_average' => $attempts->avg('score') ?? 0,
            'total_attempts' => $attempts->count(),
            'improvement_trend' => $this->calculateOverallImprovement($attempts),
        ];
    }

    /**
     * Get weekly activity data
     */
    private function getWeeklyActivity(User $student): array
    {
        $period = CarbonPeriod::create(now()->subWeeks(4), now());
        $activity = [];

        foreach ($period as $date) {
            $dayKey = $date->format('Y-m-d');
            $activity[$dayKey] = [
                'quiz_attempts' => QuizAttempt::where('user_id', $student->id)
                    ->whereDate('created_at', $date)
                    ->count(),
                'study_time' => $student->courses()
                    ->wherePivot('last_accessed_at', '>=', $date)
                    ->wherePivot('last_accessed_at', '<', $date->copy()->addDay())
                    ->sum('pivot.time_spent_minutes'),
            ];
        }

        return $activity;
    }

    /**
     * Analyze strengths and weaknesses
     */
    private function getStrengthsWeaknesses(User $student): array
    {
        $quizAttempts = $student->quizAttempts()
            ->with(['quiz:id,name,topic_id', 'quiz.topic:id,name'])
            ->get();

        $topicPerformance = $quizAttempts
            ->groupBy('quiz.topic.name')
            ->map(function ($attempts) {
                $avgScore = $attempts->avg('score');
                return [
                    'average_score' => $avgScore,
                    'attempts' => $attempts->count(),
                    'status' => $this->getPerformanceStatus($avgScore),
                ];
            })
            ->sortByDesc('average_score');

        return [
            'strengths' => $topicPerformance->filter(function ($data) {
                return $data['average_score'] >= 80;
            })->take(3),
            'weaknesses' => $topicPerformance->filter(function ($data) {
                return $data['average_score'] < 60;
            })->take(3),
        ];
    }

    /**
     * Get recent activity log
     */
    private function getRecentActivity(User $student): array
    {
        $activities = collect();

        // Add quiz attempts
        $quizAttempts = $student->quizAttempts()
            ->with('quiz:id,name')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($attempt) {
                return [
                    'type' => 'quiz_attempt',
                    'description' => "Completed {$attempt->quiz->name} with score {$attempt->score}%",
                    'timestamp' => $attempt->created_at,
                ];
            });

        // Add course progress
        $courseProgress = $student->courses()
            ->wherePivot('last_accessed_at', '>=', now()->subDays(7))
            ->get()
            ->map(function ($course) {
                return [
                    'type' => 'course_progress',
                    'description' => "Studied {$course->name}",
                    'timestamp' => $course->pivot->last_accessed_at,
                ];
            });

        return $activities->concat($quizAttempts)
            ->concat($courseProgress)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * Calculate improvement trend from attempts
     */
    private function calculateImprovement($attempts): float
    {
        if ($attempts->count() < 2) {
            return 0;
        }

        $firstScore = $attempts->sortBy('created_at')->first()->score;
        $lastScore = $attempts->sortBy('created_at')->last()->score;

        return $lastScore - $firstScore;
    }

    /**
     * Calculate overall improvement trend
     */
    private function calculateOverallImprovement($attempts): array
    {
        if ($attempts->count() < 2) {
            return ['trend' => 0, 'percentage' => 0];
        }

        $monthlyAverages = $attempts
            ->groupBy(function ($attempt) {
                return $attempt->created_at->format('Y-m');
            })
            ->map(function ($monthAttempts) {
                return $monthAttempts->avg('score');
            });

        $firstMonth = $monthlyAverages->first();
        $lastMonth = $monthlyAverages->last();
        $improvement = $lastMonth - $firstMonth;

        return [
            'trend' => $improvement,
            'percentage' => $firstMonth > 0 ? ($improvement / $firstMonth) * 100 : 0,
        ];
    }

    /**
     * Get course status based on completion
     */
    private function getCourseStatus(float $completion): string
    {
        if ($completion >= 100) return 'completed';
        if ($completion >= 75) return 'advanced';
        if ($completion >= 50) return 'intermediate';
        if ($completion >= 25) return 'progressing';
        return 'started';
    }

    /**
     * Get performance status based on score
     */
    private function getPerformanceStatus(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'good';
        if ($score >= 70) return 'satisfactory';
        if ($score >= 60) return 'needs_improvement';
        return 'requires_attention';
    }
} 