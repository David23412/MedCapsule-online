<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\NotificationService;

class AssignmentService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new assignment
     */
    public function createAssignment(array $data): Assignment
    {
        // Validate required fields
        $this->validateAssignmentData($data);

        // Set default values
        $data['status'] = $data['status'] ?? Assignment::STATUS_ACTIVE;
        $data['duration_minutes'] = $data['duration_minutes'] ?? 60;

        // Ensure deadline is in the future
        if (isset($data['deadline']) && Carbon::parse($data['deadline'])->isPast()) {
            throw new \InvalidArgumentException('Assignment deadline must be in the future');
        }

        $assignment = Assignment::create($data);

        // Send notification to student
        try {
            $this->notificationService->notifyAssignmentCreated($assignment);
        } catch (\Exception $e) {
            \Log::error('Failed to send assignment creation notification', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage()
            ]);
        }

        return $assignment;
    }

    /**
     * Create bulk assignments for multiple students
     */
    public function createBulkAssignments(int $tutorId, array $studentIds, array $assignmentData): \Illuminate\Support\Collection
    {
        $assignments = collect();

        DB::transaction(function() use ($tutorId, $studentIds, $assignmentData, &$assignments) {
            foreach ($studentIds as $studentId) {
                // Verify tutor-student relationship
                if (!$this->verifyTutorStudentRelationship($tutorId, $studentId)) {
                    throw new \InvalidArgumentException("Invalid tutor-student relationship for student ID: {$studentId}");
                }

                $data = array_merge($assignmentData, [
                    'tutor_id' => $tutorId,
                    'student_id' => $studentId
                ]);

                $assignments->push($this->createAssignment($data));
            }
        });

        return $assignments;
    }

    /**
     * Update an existing assignment
     */
    public function updateAssignment(Assignment $assignment, array $data): Assignment
    {
        // Prevent updating completed assignments
        if ($assignment->status === Assignment::STATUS_COMPLETED && !isset($data['score'])) {
            throw new \InvalidArgumentException('Cannot update completed assignment except for score');
        }

        // Validate deadline changes
        if (isset($data['deadline']) && Carbon::parse($data['deadline'])->isPast()) {
            throw new \InvalidArgumentException('Assignment deadline must be in the future');
        }

        $assignment->update($data);
        return $assignment->fresh();
    }

    /**
     * Complete an assignment with score
     */
    public function completeAssignment(Assignment $assignment, int $score = null): bool
    {
        if (!$assignment->canBeCompleted()) {
            return false;
        }

        $result = $assignment->markAsCompleted($score);
        
        if ($result) {
            // Send completion notifications
            try {
                $this->notificationService->notifyAssignmentCompleted($assignment->fresh());
            } catch (\Exception $e) {
                \Log::error('Failed to send assignment completion notification', [
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Get assignments for a tutor with filtering
     */
    public function getTutorAssignments(int $tutorId, array $filters = []): Collection
    {
        $query = Assignment::with(['student', 'topic', 'course'])
                          ->forTutor($tutorId);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['due_within_hours'])) {
            $query->dueWithin($filters['due_within_hours']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        // Default ordering
        $orderBy = $filters['order_by'] ?? 'deadline';
        $orderDirection = $filters['order_direction'] ?? 'asc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->get();
    }

    /**
     * Get assignments for a student
     */
    public function getStudentAssignments(int $studentId, array $filters = []): Collection
    {
        $query = Assignment::with(['tutor', 'topic', 'course'])
                          ->forStudent($studentId);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        // Default ordering - most urgent first
        $query->orderByRaw("
            CASE 
                WHEN status = 'overdue' THEN 1
                WHEN status = 'active' AND deadline <= NOW() + INTERVAL 24 HOUR THEN 2
                WHEN status = 'active' THEN 3
                WHEN status = 'completed' THEN 4
                ELSE 5
            END
        ")->orderBy('deadline', 'asc');

        return $query->get();
    }

    /**
     * Update overdue assignments automatically
     */
    public function updateOverdueAssignments(): int
    {
        return Assignment::updateOverdueAssignments();
    }

    /**
     * Get assignment analytics for a tutor
     */
    public function getAssignmentAnalytics(int $tutorId): array
    {
        $stats = Assignment::getStatistics($tutorId);
        
        // Additional analytics
        $recentAssignments = Assignment::forTutor($tutorId)
                                     ->where('created_at', '>=', now()->subDays(30))
                                     ->count();

        $upcomingDeadlines = Assignment::forTutor($tutorId)
                                     ->dueWithin(72)
                                     ->count();

        $averageCompletionTime = $this->getAverageCompletionTime($tutorId);

        return array_merge($stats, [
            'recent_assignments' => $recentAssignments,
            'upcoming_deadlines' => $upcomingDeadlines,
            'average_completion_time_hours' => $averageCompletionTime,
            'performance_trends' => $this->getPerformanceTrends($tutorId)
        ]);
    }

    /**
     * Get student performance analytics
     */
    public function getStudentPerformanceAnalytics(int $studentId): array
    {
        $assignments = Assignment::forStudent($studentId)->get();
        
        $totalAssignments = $assignments->count();
        $completedAssignments = $assignments->where('status', Assignment::STATUS_COMPLETED)->count();
        $overdueAssignments = $assignments->where('status', Assignment::STATUS_OVERDUE)->count();
        
        $averageScore = $assignments->whereNotNull('score')->avg('score') ?? 0;
        $completionRate = $totalAssignments > 0 ? ($completedAssignments / $totalAssignments) * 100 : 0;

        return [
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'overdue_assignments' => $overdueAssignments,
            'completion_rate' => round($completionRate, 2),
            'average_score' => round($averageScore, 2),
            'recent_performance' => $this->getRecentPerformance($studentId),
            'subject_performance' => $this->getSubjectPerformance($studentId)
        ];
    }

    /**
     * Delete an assignment (soft delete with validation)
     */
    public function deleteAssignment(Assignment $assignment): bool
    {
        // Prevent deletion of completed assignments
        if ($assignment->status === Assignment::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Cannot delete completed assignments');
        }

        return $assignment->delete();
    }

    /**
     * Extend assignment deadline
     */
    public function extendDeadline(Assignment $assignment, Carbon $newDeadline): Assignment
    {
        if ($newDeadline->isPast()) {
            throw new \InvalidArgumentException('New deadline must be in the future');
        }

        if ($assignment->status === Assignment::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Cannot extend deadline for completed assignment');
        }

        // If assignment was overdue, reactivate it
        if ($assignment->status === Assignment::STATUS_OVERDUE) {
            $assignment->update([
                'deadline' => $newDeadline,
                'status' => Assignment::STATUS_ACTIVE
            ]);
        } else {
            $assignment->update(['deadline' => $newDeadline]);
        }

        return $assignment->fresh();
    }

    /**
     * Private helper methods
     */
    private function validateAssignmentData(array $data): void
    {
        $required = ['tutor_id', 'student_id', 'topic_id', 'course_id', 'title', 'deadline'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing");
            }
        }

        // Verify relationships exist
        if (!User::where('id', $data['tutor_id'])->where('role', 'tutor')->exists()) {
            throw new \InvalidArgumentException('Invalid tutor ID');
        }

        if (!User::where('id', $data['student_id'])->where('role', 'student')->exists()) {
            throw new \InvalidArgumentException('Invalid student ID');
        }

        if (!Topic::find($data['topic_id'])) {
            throw new \InvalidArgumentException('Invalid topic ID');
        }

        if (!Course::find($data['course_id'])) {
            throw new \InvalidArgumentException('Invalid course ID');
        }
    }

    private function verifyTutorStudentRelationship(int $tutorId, int $studentId): bool
    {
        return DB::table('tutor_student_relationships')
                ->where('tutor_id', $tutorId)
                ->where('student_id', $studentId)
                ->exists();
    }

    private function getAverageCompletionTime(int $tutorId): float
    {
        $completedAssignments = Assignment::forTutor($tutorId)
                                        ->completed()
                                        ->whereNotNull('completed_at')
                                        ->get();

        if ($completedAssignments->isEmpty()) {
            return 0;
        }

        $totalHours = $completedAssignments->sum(function($assignment) {
            return $assignment->created_at->diffInHours($assignment->completed_at);
        });

        return round($totalHours / $completedAssignments->count(), 2);
    }

    private function getPerformanceTrends(int $tutorId): array
    {
        $trends = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $completed = Assignment::forTutor($tutorId)
                                  ->whereDate('completed_at', $date)
                                  ->count();
            $trends[$date] = $completed;
        }

        return $trends;
    }

    private function getRecentPerformance(int $studentId): array
    {
        return Assignment::forStudent($studentId)
                        ->where('completed_at', '>=', now()->subDays(30))
                        ->orderBy('completed_at', 'desc')
                        ->limit(10)
                        ->get(['title', 'score', 'completed_at'])
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