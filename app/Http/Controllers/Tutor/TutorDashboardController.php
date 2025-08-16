<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 PERFORMANCE BEAST: Focused Tutor Dashboard Controller
 * Handles: Dashboard display and basic analytics
 * Lines: ~100 (well under 200 limit)
 */
class TutorDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('tutor');
    }

    /**
     * Display tutor dashboard
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Get students for this tutor
            $students = $this->getStudentsForDashboard($user);
            
            // Get basic analytics
            $analytics = $this->getBasicAnalytics($students);
            
            return view('tutor.dashboard', compact('students', 'analytics'));
            
        } catch (\Exception $e) {
            Log::error('Tutor dashboard error: ' . $e->getMessage());
            return view('tutor.dashboard', [
                'students' => collect([]),
                'analytics' => [
                    'total_students' => 0,
                    'active_students' => 0,
                    'avg_performance' => 0,
                    'total_quizzes' => 0
                ]
            ])->with('error', 'Failed to load dashboard data.');
        }
    }

    /**
     * Get students for dashboard
     */
    private function getStudentsForDashboard(User $user)
    {
        // Get students assigned to this tutor
        $students = $user->assignedStudents()
            ->with(['quizAttempts' => function($query) {
                $query->select('user_id', 'score', 'percentage_grade', 'created_at')
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            }])
            ->get();

        // Enhance student data
        return $students->map(function($student) {
            return $this->enhanceStudentData($student);
        });
    }

    /**
     * Enhance student data with performance metrics
     */
    private function enhanceStudentData(User $student)
    {
        $recentAttempts = $student->quizAttempts;
        
        $student->total_quizzes = $recentAttempts->count();
        $student->avg_score = $recentAttempts->avg('percentage_grade') ?? 0;
        $student->last_activity = $recentAttempts->first()?->created_at;
        $student->performance_trend = $this->calculatePerformanceTrend($recentAttempts);
        
        return $student;
    }

    /**
     * Get basic analytics for dashboard
     */
    private function getBasicAnalytics($students)
    {
        $totalStudents = $students->count();
        $activeStudents = $students->filter(function($student) {
            return $student->last_activity && 
                   $student->last_activity->isAfter(now()->subDays(7));
        })->count();
        
        $avgPerformance = $students->avg('avg_score') ?? 0;
        $totalQuizzes = $students->sum('total_quizzes');
        
        return [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'avg_performance' => round($avgPerformance, 1),
            'total_quizzes' => $totalQuizzes
        ];
    }

    /**
     * Calculate performance trend
     */
    private function calculatePerformanceTrend($recentAttempts)
    {
        if ($recentAttempts->count() < 2) {
            return 'stable';
        }
        
        $recent = $recentAttempts->take(3)->avg('percentage_grade');
        $older = $recentAttempts->skip(3)->take(3)->avg('percentage_grade');
        
        if ($recent > $older + 5) {
            return 'improving';
        } elseif ($recent < $older - 5) {
            return 'declining';
        }
        
        return 'stable';
    }
}