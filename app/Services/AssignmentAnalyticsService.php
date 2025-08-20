<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use App\Models\Quiz\QuizAttempt;
use Illuminate\Support\Facades\DB;

class AssignmentAnalyticsService
{
    /**
     * Get student performance analytics
     */
    public function getStudentPerformanceAnalytics(int $studentId): array
    {
        $student = User::find($studentId);
        if (!$student) {
            return []; // Or throw an exception
        }

        $assignments = Assignment::forStudent($studentId)->get();
        $totalAssignments = $assignments->count();
        $completedAssignments = 0;
        $lockedAssignments = 0;

        foreach ($assignments as $assignment) {
            $status = $assignment->getStatusForUser($student);
            if ($status === Assignment::STATUS_COMPLETED) {
                $completedAssignments++;
            }
            if ($status === Assignment::STATUS_LOCKED && $assignment->isOverdue()) {
                $lockedAssignments++;
            }
        }
        
        $averageScore = QuizAttempt::where('user_id', $studentId)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->whereNotNull('completed_at')
            ->avg('percentage_grade') ?? 0;
        $completionRate = $totalAssignments > 0 ? ($completedAssignments / $totalAssignments) * 100 : 0;

        return [
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'overdue_assignments' => $lockedAssignments, // Renamed for clarity
            'completion_rate' => round($completionRate, 2),
            'average_score' => round($averageScore, 2),
            'recent_performance' => $this->getRecentPerformance($studentId),
            'subject_performance' => $this->getSubjectPerformance($studentId)
        ];
    }

    private function getAverageCompletionTime(int $tutorId): float
    {
        $completedAttempts = QuizAttempt::whereIn('assignment_id', Assignment::forTutor($tutorId)->pluck('id'))
            ->whereNotNull('completed_at')
            ->selectRaw('*, TIMESTAMPDIFF(HOUR, started_at, completed_at) as completion_duration_hours')
            ->get();

        if ($completedAttempts->isEmpty()) {
            return 0;
        }

        return round($completedAttempts->avg('completion_duration_hours'), 2);
    }

    private function getPerformanceTrends(int $tutorId): array
    {
        $trends = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $completed = QuizAttempt::whereIn('assignment_id', Assignment::forTutor($tutorId)->pluck('id'))
                                  ->whereDate('completed_at', $date)
                                  ->count();
            $trends[$date] = $completed;
        }

        return $trends;
    }

    private function getRecentPerformance(int $studentId): array
    {
        return QuizAttempt::where('user_id', $studentId)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(30))
            ->with('assignment:id,title')
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($attempt) {
                return [
                    'title' => $attempt->assignment->title,
                    'score' => $attempt->percentage_grade,
                    'completed_at' => $attempt->completed_at->toDateTimeString(),
                ];
            })
            ->toArray();
    }

    private function getSubjectPerformance(int $studentId): array
    {
        return Assignment::select('courses.name as course_name', 
                                DB::raw('AVG(assignments.score) as average_score'),
                                DB::raw('COUNT(*) as total_assignments'))
                        ->join('courses', 'assignments.course_id', '=', 'courses.id')
                        ->forStudent($studentId)
                        ->whereNotNull('score')
                        ->groupBy('courses.id', 'courses.name')
                        ->get()
                        ->toArray();
    }
}
