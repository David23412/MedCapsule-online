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
        $data['assignment_mode'] = $data['assignment_mode'] ?? Assignment::MODE_COMPLETE_TOPIC;
        
        // Validate assignment mode
        if (!in_array($data['assignment_mode'], [Assignment::MODE_SOURCE_SPECIFIC, Assignment::MODE_COMPLETE_TOPIC])) {
            throw new \InvalidArgumentException('Invalid assignment mode');
        }
        
        // Validate source for source-specific mode
        if ($data['assignment_mode'] === Assignment::MODE_SOURCE_SPECIFIC && empty($data['assigned_source'])) {
            throw new \InvalidArgumentException('Source must be specified for source-specific assignments');
        }
        
        // Ensure source exists for the topic if specified
        if (!empty($data['assigned_source'])) {
            $this->validateSourceExists($data['topic_id'], $data['assigned_source']);
        }

        // Ensure deadline is in the future
        if (isset($data['deadline']) && Carbon::parse($data['deadline'])->isPast()) {
            throw new \InvalidArgumentException('Assignment deadline must be in the future');
        }
        
        // Validate duration if provided
        if (isset($data['duration_minutes']) && $data['duration_minutes'] <= 0) {
            throw new \InvalidArgumentException('Duration must be a positive number');
        }

        DB::beginTransaction();
        
        try {
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
            
            DB::commit();
            return $assignment;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Create bulk assignments for multiple students
     */
    public function createBulkAssignments(int $tutorId, array $studentIds, array $assignmentData): \Illuminate\Support\Collection
    {
        $assignments = collect();
        $skipExisting = $assignmentData['skip_existing'] ?? false;

        DB::transaction(function() use ($tutorId, $studentIds, $assignmentData, &$assignments, $skipExisting) {
            foreach ($studentIds as $studentId) {
                // Verify tutor-student relationship (skip for admins)
                $currentUser = auth()->user();
                if ($currentUser->role !== 'admin' && !$this->verifyTutorStudentRelationship($tutorId, $studentId)) {
                    throw new \InvalidArgumentException("Invalid tutor-student relationship for student ID: {$studentId}");
                }

                // Check if assignment already exists and skip if requested
                if ($skipExisting && $this->assignmentExists($tutorId, $studentId, $assignmentData)) {
                    continue; // Skip this student
                }

                $data = array_merge($assignmentData, [
                    'tutor_id' => $tutorId,
                    'student_id' => $studentId
                ]);

                // Remove skip_existing from the data before creating assignment
                unset($data['skip_existing']);

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
    public function completeAssignment(Assignment $assignment, array $answers = [], int $score = null): bool
    {
        if (!$assignment->canBeCompleted()) {
            return false;
        }

        // Calculate score if not provided but answers are
        if ($score === null && !empty($answers)) {
            $scoreData = $this->calculateAssignmentScore($assignment, $answers);
            $score = $scoreData['score'];
            $scorePercentage = $scoreData['score_percentage'];
        } else {
            $scorePercentage = null;
        }

        DB::beginTransaction();
        
        try {
            // Update with score percentage if available
            if ($scorePercentage !== null) {
                $assignment->update([
                    'status' => Assignment::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'score' => $score,
                    'score_percentage' => $scorePercentage
                ]);
                $result = true;
            } else {
                $result = $assignment->markAsCompleted($score);
            }
            
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
            
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    
    /**
     * Calculate assignment score based on answers and assignment mode
     */
    public function calculateAssignmentScore(Assignment $assignment, array $answers): array
    {
        if ($assignment->isSourceSpecific()) {
            return $this->calculateSourceSpecificScore($assignment, $answers);
        }
        
        return $this->calculateCompleteTopicScore($assignment, $answers);
    }
    
    /**
     * Calculate score for source-specific assignment
     */
    private function calculateSourceSpecificScore(Assignment $assignment, array $answers): array
    {
        $topic = $assignment->topic;
        $source = $assignment->assigned_source;
        
        // Get questions for the specific source
        $questions = DB::table('questions')
            ->where('topic_id', $topic->id)
            ->where('source', $source)
            ->get();
            
        $totalQuestions = $questions->count();
        $correctAnswers = 0;
        
        foreach ($questions as $question) {
            if (isset($answers[$question->id]) && $answers[$question->id] === $question->correct_answer) {
                $correctAnswers++;
            }
        }
        
        $scorePercentage = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        $score = round($scorePercentage); // Integer score
        
        return [
            'score' => $score,
            'score_percentage' => $scorePercentage,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'source' => $source
        ];
    }
    
    /**
     * Calculate score for complete-topic assignment
     */
    private function calculateCompleteTopicScore(Assignment $assignment, array $answers): array
    {
        $topic = $assignment->topic;
        
        // Get all questions for the topic
        $questions = DB::table('questions')
            ->where('topic_id', $topic->id)
            ->get();
            
        $totalQuestions = $questions->count();
        $correctAnswers = 0;
        
        foreach ($questions as $question) {
            if (isset($answers[$question->id]) && $answers[$question->id] === $question->correct_answer) {
                $correctAnswers++;
            }
        }
        
        $scorePercentage = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        $score = round($scorePercentage); // Integer score
        
        return [
            'score' => $score,
            'score_percentage' => $scorePercentage,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'source' => 'all'
        ];
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
     * Handle assignment expiry due to time limit
     */
    public function handleAssignmentExpiry(Assignment $assignment): void
    {
        if ($assignment->status !== Assignment::STATUS_ACTIVE) {
            return;
        }
        
        DB::transaction(function() use ($assignment) {
            $assignment->markAsExpired();
            
            // Send notification
            try {
                $this->notificationService->notifyAssignmentExpired($assignment->fresh());
            } catch (\Exception $e) {
                \Log::error('Failed to send assignment expiry notification', [
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
    }
    
    /**
     * Auto-submit assignment when time limit expires
     */
    public function autoSubmitAssignment(Assignment $assignment, array $answers = []): bool
    {
        if ($assignment->status !== Assignment::STATUS_ACTIVE) {
            return false;
        }
        
        DB::transaction(function() use ($assignment, $answers) {
            // Calculate score based on current answers
            $scoreData = $this->calculateAssignmentScore($assignment, $answers);
            
            // Mark as expired with partial score
            $assignment->update([
                'status' => Assignment::STATUS_EXPIRED,
                'expired_at' => now(),
                'score' => $scoreData['score'],
                'score_percentage' => $scoreData['score_percentage']
            ]);
            
            // Create assignment attempt record
            DB::table('assignment_attempts')->insert([
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->student_id,
                'student_id' => $assignment->student_id,
                'source' => $assignment->isSourceSpecific() ? $assignment->assigned_source : 'all',
                'questions_answered' => count($answers),
                'correct_answers' => $scoreData['correct_answers'],
                'score_percentage' => $scoreData['score_percentage'],
                'time_spent_seconds' => $assignment->duration_minutes * 60, // Full time was used
                'started_at' => now()->subMinutes($assignment->duration_minutes),
                'completed_at' => now(),
                'status' => 'completed',
                'auto_submitted' => true,
                'answers' => json_encode($answers),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Send notification
            try {
                $this->notificationService->notifyAssignmentAutoSubmitted($assignment->fresh());
            } catch (\Exception $e) {
                \Log::error('Failed to send assignment auto-submit notification', [
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
        
        return true;
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
        if (!User::where('id', $data['tutor_id'])->whereIn('role', ['tutor', 'admin'])->exists()) {
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
    
    /**
     * Validate that a source exists for a topic
     */
    private function validateSourceExists(int $topicId, string $source): void
    {
        $exists = DB::table('questions')
            ->where('topic_id', $topicId)
            ->where('source', $source)
            ->exists();
            
        if (!$exists) {
            throw new \InvalidArgumentException("Source '{$source}' does not exist for this topic");
        }
    }

    private function verifyTutorStudentRelationship(int $tutorId, int $studentId): bool
    {
        return DB::table('tutor_student_relationships')
                ->where('tutor_id', $tutorId)
                ->where('student_id', $studentId)
                ->exists();
    }

    /**
     * Check if a similar assignment already exists (used only for skip_existing convenience feature)
     */
    private function assignmentExists(int $tutorId, int $studentId, array $assignmentData): bool
    {
        // Simple check for any assignment with same tutor, student, and topic
        // This is just for the skip_existing convenience feature - not for blocking duplicates
        return Assignment::where('tutor_id', $tutorId)
                        ->where('student_id', $studentId)
                        ->where('topic_id', $assignmentData['topic_id'])
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
    
    /**
     * Get detailed assignment reports for a tutor
     * 
     * @param User $tutor The tutor to get reports for
     * @param array $filters Optional filters (course_id, student_id, status, date_range)
     * @return array Detailed assignment reports with completion status per source
     */
    public function getAssignmentReports(User $tutor, array $filters = []): array
    {
        // Get assignments with related data
        $query = Assignment::with(['student', 'topic', 'course'])
                          ->forTutor($tutor->id);
        
        // Apply filters
        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }
        
        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }
        
        $assignments = $query->get();
        
        // Process assignments into detailed reports
        $reports = [];
        
        foreach ($assignments as $assignment) {
            $report = [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'student' => [
                    'id' => $assignment->student->id,
                    'name' => $assignment->student->name,
                ],
                'topic' => [
                    'id' => $assignment->topic->id,
                    'title' => $assignment->topic->title,
                ],
                'course' => [
                    'id' => $assignment->course->id,
                    'name' => $assignment->course->name,
                ],
                'deadline' => $assignment->deadline->format('Y-m-d H:i:s'),
                'status' => $assignment->status,
                'assignment_mode' => $assignment->assignment_mode,
                'duration_minutes' => $assignment->duration_minutes,
                'created_at' => $assignment->created_at->format('Y-m-d H:i:s'),
                'completed_at' => $assignment->completed_at ? $assignment->completed_at->format('Y-m-d H:i:s') : null,
                'expired_at' => $assignment->expired_at ? $assignment->expired_at->format('Y-m-d H:i:s') : null,
                'score' => $assignment->score,
                'score_percentage' => $assignment->score_percentage,
            ];
            
            // Get detailed source completion status
            $report['source_details'] = $this->getSourceCompletionDetails($assignment);
            
            // Calculate partial completion for incomplete assignments
            if ($assignment->status !== Assignment::STATUS_COMPLETED) {
                $report['partial_completion'] = $this->calculatePartialCompletion($assignment);
            }
            
            $reports[] = $report;
        }
        
        return $reports;
    }
    
    /**
     * Get detailed source completion status for an assignment
     */
    private function getSourceCompletionDetails(Assignment $assignment): array
    {
        // Get all attempts for this assignment
        $attempts = DB::table('assignment_attempts')
            ->where('assignment_id', $assignment->id)
            ->get();
            
        if ($assignment->isSourceSpecific()) {
            // For source-specific assignments, we only care about the assigned source
            $source = $assignment->assigned_source;
            $sourceAttempt = $attempts->where('source', $source)->first();
            
            return [
                'source' => $source,
                'completed' => $sourceAttempt !== null,
                'score_percentage' => $sourceAttempt ? $sourceAttempt->score_percentage : 0,
                'questions_answered' => $sourceAttempt ? $sourceAttempt->questions_answered : 0,
                'correct_answers' => $sourceAttempt ? $sourceAttempt->correct_answers : 0,
                'time_spent_seconds' => $sourceAttempt ? $sourceAttempt->time_spent_seconds : 0,
            ];
        } else {
            // For complete-topic assignments, get details for all sources
            $topic = $assignment->topic;
            
            // Get all sources for this topic
            $sources = DB::table('questions')
                ->where('topic_id', $topic->id)
                ->select('source')
                ->distinct()
                ->pluck('source')
                ->toArray();
                
            $sourceDetails = [];
            
            foreach ($sources as $source) {
                // Get questions for this source
                $questions = DB::table('questions')
                    ->where('topic_id', $topic->id)
                    ->where('source', $source)
                    ->get();
                    
                // Get attempt for this source
                $sourceAttempt = $attempts->where('source', $source)->first();
                
                $sourceDetails[$source] = [
                    'completed' => $sourceAttempt !== null,
                    'total_questions' => $questions->count(),
                    'questions_answered' => $sourceAttempt ? $sourceAttempt->questions_answered : 0,
                    'correct_answers' => $sourceAttempt ? $sourceAttempt->correct_answers : 0,
                    'score_percentage' => $sourceAttempt ? $sourceAttempt->score_percentage : 0,
                    'time_spent_seconds' => $sourceAttempt ? $sourceAttempt->time_spent_seconds : 0,
                ];
            }
            
            return $sourceDetails;
        }
    }
    
    /**
     * Calculate partial completion percentage for incomplete assignments
     */
    private function calculatePartialCompletion(Assignment $assignment): array
    {
        // Get all attempts for this assignment
        $attempts = DB::table('assignment_attempts')
            ->where('assignment_id', $assignment->id)
            ->get();
            
        if ($attempts->isEmpty()) {
            return [
                'percentage' => 0,
                'questions_answered' => 0,
                'total_questions' => 0,
            ];
        }
        
        if ($assignment->isSourceSpecific()) {
            // For source-specific assignments
            $source = $assignment->assigned_source;
            
            // Get total questions for this source
            $totalQuestions = DB::table('questions')
                ->where('topic_id', $assignment->topic_id)
                ->where('source', $source)
                ->count();
                
            // Get questions answered from attempts
            $questionsAnswered = $attempts->sum('questions_answered');
            
            $percentage = $totalQuestions > 0 ? ($questionsAnswered / $totalQuestions) * 100 : 0;
            
            return [
                'percentage' => round($percentage, 2),
                'questions_answered' => $questionsAnswered,
                'total_questions' => $totalQuestions,
            ];
        } else {
            // For complete-topic assignments
            $totalQuestions = DB::table('questions')
                ->where('topic_id', $assignment->topic_id)
                ->count();
                
            $questionsAnswered = $attempts->sum('questions_answered');
            
            $percentage = $totalQuestions > 0 ? ($questionsAnswered / $totalQuestions) * 100 : 0;
            
            return [
                'percentage' => round($percentage, 2),
                'questions_answered' => $questionsAnswered,
                'total_questions' => $totalQuestions,
            ];
        }
    }
}