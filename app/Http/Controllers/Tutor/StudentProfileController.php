<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 PERFORMANCE BEAST: Focused Student Profile Controller
 * Handles: Student profile display and comprehensive data
 * Lines: ~200 (at 200 limit)
 */
class StudentProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('tutor');
    }

    /**
     * Show student profile
     */
    public function showStudentProfile(Request $request, $studentId)
    {
        try {
            $tutor = auth()->user();
            $student = User::findOrFail($studentId);
            
            // Check access permissions
            if (!$this->canAccessStudent($tutor, $student)) {
                abort(403, 'You do not have access to this student.');
            }
            
            // Get comprehensive student data
            $studentData = $this->getComprehensiveStudentData($student);
            
            return view('tutor.student-profile', compact('student', 'studentData'));
            
        } catch (\Exception $e) {
            Log::error('Student profile error: ' . $e->getMessage());
            return redirect()->route('tutor.dashboard')
                ->with('error', 'Failed to load student profile.');
        }
    }

    /**
     * Check if tutor can access student
     */
    private function canAccessStudent(User $tutor, User $student): bool
    {
        // Check if student is assigned to this tutor
        return $tutor->assignedStudents()->where('users.id', $student->id)->exists();
    }

    /**
     * Get comprehensive student data
     */
    private function getComprehensiveStudentData(User $student): array
    {
        // Get quiz attempts with eager loading
        $quizAttempts = $student->quizAttempts()
            ->with(['topic:id,name', 'topic.course:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return [
            'basic_stats' => [
                'total_quizzes' => $quizAttempts->count(),
                'avg_score' => round($quizAttempts->avg('percentage_grade') ?? 0, 1),
                'best_score' => $quizAttempts->max('percentage_grade') ?? 0,
                'total_questions' => $quizAttempts->sum('total_questions'),
                'correct_answers' => $quizAttempts->sum('correct_answers')
            ],
            'performance_data' => [
                'topic_performance' => $this->getTopicPerformance($quizAttempts),
                'improvement_rate' => $this->calculateImprovementRate($quizAttempts),
                'strength_areas' => $this->getStrengthAreas($quizAttempts),
                'weakness_areas' => $this->getWeaknessAreas($quizAttempts)
            ],
            'study_patterns' => $this->getStudyPatterns($quizAttempts),
            'assignments' => $this->getStudentAssignments($student),
            'recent_activity' => $quizAttempts->take(10)
        ];
    }

    /**
     * Get topic performance breakdown
     */
    private function getTopicPerformance($quizAttempts)
    {
        return $quizAttempts->groupBy('topic.name')
            ->map(function($attempts, $topicName) {
                return [
                    'topic' => $topicName,
                    'attempts' => $attempts->count(),
                    'avg_score' => round($attempts->avg('percentage_grade'), 1),
                    'best_score' => $attempts->max('percentage_grade')
                ];
            })
            ->sortByDesc('avg_score')
            ->values();
    }

    /**
     * Get study patterns
     */
    private function getStudyPatterns($quizAttempts)
    {
        $patterns = [
            'most_active_day' => $this->getMostActiveDay($quizAttempts),
            'study_frequency' => $quizAttempts->count() > 0 ? 
                round($quizAttempts->count() / max(1, $quizAttempts->first()?->created_at?->diffInDays(now()) ?? 1), 2) : 0,
            'avg_session_length' => round($quizAttempts->avg('total_questions') ?? 0, 1)
        ];

        return $patterns;
    }

    /**
     * Get most active day
     */
    private function getMostActiveDay($attempts)
    {
        if ($attempts->isEmpty()) {
            return 'No data';
        }

        $dayCount = $attempts->groupBy(function($attempt) {
            return $attempt->created_at->format('l');
        })->map->count();

        return $dayCount->sortDesc()->keys()->first() ?? 'No data';
    }

    /**
     * Calculate improvement rate
     */
    private function calculateImprovementRate($quizAttempts)
    {
        if ($quizAttempts->count() < 5) {
            return 0;
        }

        $recent = $quizAttempts->take(5)->avg('percentage_grade');
        $older = $quizAttempts->skip(5)->take(5)->avg('percentage_grade');

        return $older > 0 ? round((($recent - $older) / $older) * 100, 1) : 0;
    }

    /**
     * Get strength areas
     */
    private function getStrengthAreas($quizAttempts)
    {
        return $quizAttempts->where('percentage_grade', '>=', 80)
            ->groupBy('topic.name')
            ->map->count()
            ->sortDesc()
            ->take(3)
            ->keys()
            ->toArray();
    }

    /**
     * Get weakness areas
     */
    private function getWeaknessAreas($quizAttempts)
    {
        return $quizAttempts->where('percentage_grade', '<', 60)
            ->groupBy('topic.name')
            ->map->count()
            ->sortDesc()
            ->take(3)
            ->keys()
            ->toArray();
    }

    /**
     * Get student assignments
     */
    private function getStudentAssignments(User $student): array
    {
        $assignments = Assignment::whereHas('assignedUsers', function($query) use ($student) {
            $query->where('user_id', $student->id);
        })
        ->with(['topic:id,name', 'course:id,name'])
        ->orderBy('deadline', 'desc')
        ->limit(10)
        ->get();

        return $assignments->map(function($assignment) use ($student) {
            $attempts = $assignment->attemptsForUser($student->id)->get();
            
            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'topic' => $assignment->topic?->name,
                'course' => $assignment->course?->name,
                'deadline' => $assignment->deadline,
                'status' => $assignment->getStatusForUser($student->id),
                'attempts' => $attempts->count(),
                'best_score' => $attempts->max('percentage_grade') ?? 0
            ];
        })->toArray();
    }
}