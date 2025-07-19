<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TutorDashboardController extends Controller
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
        
        // Get students with optimized queries
        $students = $this->getStudentsForDashboard($user);
        
        // Get basic analytics
        $analytics = $this->getBasicAnalytics($students);
        
        return view('tutor.dashboard', [
            'students' => $students,
            'analytics' => $analytics,
            'tutor' => $user,
        ]);
    }

    /**
     * Show student's profile page for tutors
     */
    public function showStudentProfile(Request $request, $studentId)
    {
        $user = auth()->user();
        $student = User::findOrFail($studentId);

        // Verify access permissions
        if (!$user->isAdmin() && !$this->canAccessStudent($user, $student)) {
            abort(403, 'You do not have permission to view this student\'s profile.');
        }

        // Get comprehensive student data
        $studentData = $this->getComprehensiveStudentData($student);

        return view('tutor.student-profile', [
            'student' => $student,
            'tutor' => $user,
            'data' => $studentData,
        ]);
    }

    /**
     * Generate grade report for a specific student
     */
    public function generateStudentReport(Request $request, $studentId)
    {
        $user = auth()->user();
        $student = User::findOrFail($studentId);

        if (!$user->isAdmin() && !$this->canAccessStudent($user, $student)) {
            abort(403, 'You do not have permission to generate reports for this student.');
        }

        try {
            $reportData = $this->generateGradeReportData($student);
            
            return response()->json([
                'success' => true,
                'student_name' => $student->name,
                'data' => $reportData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating student report:', [
                'tutor_id' => $user->id,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to generate report for this student.'
            ], 500);
        }
    }

    /**
     * Export grade reports for all assigned students
     */
    public function exportAllReports(Request $request)
    {
        $user = auth()->user();
        
        try {
            $students = $user->isAdmin() 
                ? User::where('role', User::ROLE_STUDENT)->get()
                : $user->assignedStudents()->get();

            $csvData = $this->generateStudentsCSV($students);
            
            $filename = 'grade_reports_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
            
            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            \Log::error('Error exporting all reports:', [
                'tutor_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('tutor.dashboard')
                ->with('error', 'Unable to export reports at this time.');
        }
    }

    /**
     * Get students for dashboard - Optimized with single query
     */
    private function getStudentsForDashboard(User $user)
    {
        $cacheKey = "dashboard_students_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            $query = User::where('role', User::ROLE_STUDENT);

            // Apply tutor filter for non-admins
            if (!$user->isAdmin()) {
                $query->whereHas('assignedTutors', function ($q) use ($user) {
                    $q->where('tutor_id', $user->id);
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
        
        // Calculate performance trend efficiently
        $trend = $this->calculatePerformanceTrend($recentAttempts);
        
        // Add enhanced metrics to student object
        $student->enhanced_metrics = [
            'average_score' => round($averageScore, 1),
            'total_quizzes' => $quizAttempts->count(),
            'performance_trend' => $trend,
            'last_quiz_date' => $recentAttempts->first()?->created_at,
            'is_active' => $student->last_active_at && $student->last_active_at->gte(now()->subDays(3)),
        ];

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

        // Calculate struggling students (average score < 60%)
        $strugglingStudents = $students->filter(function ($student) {
            $avgScore = $student->quizAttempts->avg('score') ?? 0;
            return $avgScore < 60 && $student->quizAttempts->count() > 0;
        })->count();

        // Calculate high performers (average score >= 80%)
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
            'average_score' => round($averageScore, 1), // For backward compatibility
            'engagement_rate' => $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0,
        ];
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
     */
    private function canAccessStudent(User $tutor, User $student): bool
    {
        return $tutor->assignedStudents()->where('student_id', $student->id)->exists();
    }

    /**
     * Generate grade report data for a student - Optimized
     */
    private function generateGradeReportData(User $student)
    {
        $quizAttempts = $student->quizAttempts()
            ->with('topic:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'student_name' => $student->name,
            'student_email' => $student->email,
            'university' => $student->university ?? 'Not specified',
            'total_quizzes' => $quizAttempts->count(),
            'average_score' => round($quizAttempts->avg('score') ?? 0, 1),
            'best_score' => $quizAttempts->max('score') ?? 0,
            'study_streak' => $student->study_streak_days ?? 0,
            'total_xp' => $student->xp ?? 0,
            'last_active' => $student->last_active_at?->format('Y-m-d H:i:s') ?? 'Never',
            'quiz_history' => $quizAttempts->take(10)->map(function ($attempt) {
                return [
                    'topic' => $attempt->topic->name ?? 'Unknown Topic',
                    'score' => $attempt->score,
                    'date' => $attempt->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate CSV for students - Optimized
     */
    private function generateStudentsCSV($students)
    {
        $csv = "Student Name,Email,University,Quiz Average,Total Quizzes,Study Streak,Last Active\n";

        foreach ($students as $student) {
            $avgScore = $student->quizAttempts()->avg('score') ?? 0;
            $quizCount = $student->quizAttempts()->count();
            $lastActive = $student->last_active_at ? $student->last_active_at->format('Y-m-d H:i:s') : 'Never';
            
            $csv .= "\"{$student->name}\",\"{$student->email}\",\"{$student->university}\"," . 
                    number_format($avgScore, 1) . ",{$quizCount},{$student->study_streak_days},\"{$lastActive}\"\n";
        }

        return $csv;
    }

    /**
     * Get comprehensive student data for profile view
     */
    private function getComprehensiveStudentData(User $student): array
    {
        $quizAttempts = $student->quizAttempts()
            ->with('topic:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalQuestions = $student->total_questions_attempted ?? 0;
        $totalCorrect = $student->correct_answers_count ?? 0;
        $accuracyRate = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 1) : 0;

        return [
            'overview' => [
                'total_quizzes' => $quizAttempts->count(),
                'average_score' => round($quizAttempts->avg('score') ?? 0, 1),
                'best_score' => $quizAttempts->max('score') ?? 0,
                'accuracy_rate' => $accuracyRate,
                'study_streak' => $student->study_streak_days ?? 0,
                'total_xp' => $student->xp ?? 0,
                'member_since' => $student->created_at,
                'last_active' => $student->last_active_at,
            ],
            'recent_quizzes' => $quizAttempts->take(10)->map(function ($attempt) {
                return [
                    'topic' => $attempt->topic->name ?? 'Unknown Topic',
                    'score' => $attempt->score,
                    'date' => $attempt->created_at,
                    'duration' => $attempt->duration_seconds ?? 0,
                ];
            })->toArray(),
            'performance_by_topic' => $this->getTopicPerformance($quizAttempts),
            'study_patterns' => $this->getStudyPatterns($quizAttempts),
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
                    'last_attempt' => $attempts->first()->created_at,
                ];
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
            'average_session_time' => round($recentAttempts->avg('duration_seconds') / 60, 1), // in minutes
            'most_active_day' => $this->getMostActiveDay($recentAttempts),
            'improvement_trend' => $this->calculatePerformanceTrend($recentAttempts->take(10)),
        ];
    }

    /**
     * Get most active day of the week
     */
    private function getMostActiveDay($attempts)
    {
        $dayCount = $attempts->groupBy(function ($attempt) {
            return $attempt->created_at->format('l'); // Full day name
        })->map->count()->sortDesc();

        return $dayCount->keys()->first() ?? 'No data';
    }
}