<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Services\NotificationService;

class AssignmentService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new assignment with enhanced error handling and performance optimization
     */
    public function createAssignment(array $data): Assignment
    {
        try {
            // Validate required fields with detailed error messages
            $this->validateAssignmentData($data);

            // Set default values with validation
            $data['duration_minutes'] = $data['duration_minutes'] ?? 60;
            $data['assignment_mode'] = $data['assignment_mode'] ?? Assignment::MODE_COMPLETE_TOPIC;
            
            // Enhanced validation with specific error messages
            $this->validateAssignmentMode($data);
            $this->validateSourceSpecificRequirements($data);
            $this->validateDeadline($data);
            $this->validateDuration($data);

            // Use database transaction with proper error handling
            return DB::transaction(function() use ($data) {
                try {
                    // Filter data to only include valid assignment columns
                    $validData = array_intersect_key($data, array_flip([
                        'topic_id', 'course_id', 'title', 'deadline', 
                        'duration_minutes', 'assignment_mode', 'assigned_source', 'status'
                    ]));
                    
                    $assignment = Assignment::create($validData);
                    
                    // Clear assignment caches immediately after creation
                    $this->clearAssignmentCaches(auth()->id());
                    
                    // Send notification with error isolation
                    $this->sendAssignmentNotification($assignment, 'created');
                    
                    return $assignment;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle database-specific errors
                    \Log::error('Database error creating assignment', [
                        'data' => $data,
                        'error' => $e->getMessage(),
                        'sql_state' => $e->errorInfo[0] ?? null
                    ]);
                    throw new \RuntimeException('Failed to create assignment due to database error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    \Log::error('Unexpected error creating assignment', [
                        'data' => $data,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw new \RuntimeException('Failed to create assignment: ' . $e->getMessage());
                }
            });
        } catch (\InvalidArgumentException $e) {
            // Re-throw validation errors as-is
            throw $e;
        } catch (\Exception $e) {
            // Log and wrap unexpected errors
            \Log::error('Critical error in createAssignment', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Assignment creation failed: ' . $e->getMessage());
        }
    }



    /**
     * Update an existing assignment
     */
    public function updateAssignment(Assignment $assignment, array $data): Assignment
    {
        // Only validate deadline if it's being changed and not empty
        if (isset($data['deadline']) && !empty($data['deadline'])) {
            $newDeadline = Carbon::parse($data['deadline']);
            if ($newDeadline->isPast()) {
                throw new \InvalidArgumentException('Assignment deadline must be in the future');
            }
        }
        
        // Remove empty deadline from update data to keep existing deadline
        if (isset($data['deadline']) && empty($data['deadline'])) {
            unset($data['deadline']);
        }

        $assignment->update($data);
        return $assignment->fresh();
    }

    /**
     * Update user-specific deadline extensions (stored in pivot table)
     */
    public function updateUserExtensions(Assignment $assignment, array $userExtensions): void
    {
        foreach ($userExtensions as $userId => $extensionDeadline) {
            // Update the pivot table with the extended deadline
            $assignment->assignedUsers()->updateExistingPivot($userId, [
                'extended_deadline' => Carbon::parse($extensionDeadline),
                'updated_at' => now()
            ]);
        }
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
                // Since status column was removed, completion is tracked in quiz_attempts
                $result = true;
            } else {
                $result = $assignment->markAsCompleted($score);
            }
            
            if ($result) {
                // LETHAL FIX: Use new notification system for assignment completion
                try {
                    $assignmentData = [
                        'assignment_id' => $assignment->id,
                        'assignment_title' => $assignment->title,
                        'score' => $scorePercentage ?? $assignment->score,
                        'total_questions' => $assignment->total_questions ?? 1,
                        'completed_at' => $assignment->completed_at?->toISOString()
                    ];
                    
                    \App\Services\NotificationTriggerService::onAssignmentCompleted($assignment->user, $assignmentData);
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
            $query->where('user_id', $filters['student_id']);
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

        // Default ordering - most urgent first (based on deadline since status column was removed)
        $query->orderByRaw("
            CASE 
                WHEN deadline < NOW() THEN 1
                WHEN deadline <= NOW() + INTERVAL 24 HOUR THEN 2
                ELSE 3
            END
        ")->orderBy('deadline', 'asc');

        return $query->get();
    }

    /**
     * @deprecated Real-time status updates happen automatically via model accessors
     * Use Assignment::enforceAllStatuses() for manual enforcement if needed
     */
    public function updateOverdueAssignments(): int
    {
        return Assignment::enforceAllStatuses();
    }



    /**
     * Get student performance analytics
     */
    public function getStudentPerformanceAnalytics(int $studentId): array
    {
        $assignments = Assignment::forStudent($studentId)->get();
        
        $totalAssignments = $assignments->count();
        
        // Filter by computed status since status column doesn't exist
        $completedAssignments = $assignments->filter(function($assignment) {
            return $assignment->getCurrentStatusAttribute() === Assignment::STATUS_COMPLETED;
        })->count();
        
        $overdueAssignments = $assignments->filter(function($assignment) {
            return $assignment->getCurrentStatusAttribute() === Assignment::STATUS_OVERDUE;
        })->count();
        
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
        // Since status column was removed, allow deletion
        $result = $assignment->delete();
        
        // Clear assignment caches immediately after deletion
        if ($result) {
            $this->clearAssignmentCaches(auth()->id());
        }
        
        return $result;
    }

    /**
     * Extend assignment deadline
     */
    public function extendDeadline(Assignment $assignment, Carbon $newDeadline): Assignment
    {
        if ($newDeadline->isPast()) {
            throw new \InvalidArgumentException('New deadline must be in the future');
        }

        $oldDeadline = $assignment->deadline;

        // Update deadline (status is computed dynamically)
        $assignment->update(['deadline' => $newDeadline]);

        // 🔥 LETHAL FIX: Dispatch AssignmentDeadlineChanged event
        try {
            event(new \App\Events\AssignmentDeadlineChanged($assignment->fresh(), $oldDeadline, $newDeadline));
        } catch (\Exception $e) {
            \Log::error('Failed to dispatch assignment deadline changed event', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage()
            ]);
        }

        return $assignment->fresh();
    }

    /**
     * Handle assignment expiry due to time limit
     */
    public function handleAssignmentExpiry(Assignment $assignment): void
    {
        // Since status column was removed, expiry is computed dynamically
        if (!$assignment->isExpired()) {
            return;
        }
        
        // LETHAL FIX: Use new notification system for assignment expiry
        try {
            \App\Services\NotificationTriggerService::onAssignmentOverdueReminder(
                $assignment->user, 
                $assignment, 
                0 // 0 hours = overdue
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send assignment expiry notification', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Auto-submit assignment when time limit expires
     */
    public function autoSubmitAssignment(Assignment $assignment, array $answers = []): bool
    {
        if (!$assignment->isActive()) {
            return false;
        }
        
        DB::transaction(function() use ($assignment, $answers) {
            // Calculate score based on current answers
            $scoreData = $this->calculateAssignmentScore($assignment, $answers);
            
            // Create assignment attempt record
            DB::table('assignment_attempts')->insert([
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
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
            
            // LETHAL FIX: Use new notification system for assignment auto-submission
            try {
                $assignmentData = [
                    'assignment_id' => $assignment->id,
                    'assignment_title' => $assignment->title,
                    'score' => $scoreData['score_percentage'],
                    'total_questions' => $scoreData['total_questions'],
                    'auto_submitted' => true
                ];
                \App\Services\NotificationTriggerService::onAssignmentCompleted($assignment->user, $assignmentData);
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
     * Enhanced validation and helper methods
     */
    private function validateAssignmentData(array $data): void
    {
        $required = ['tutor_id', 'user_id', 'topic_id', 'course_id', 'title', 'deadline'];
        
        // Check for missing required fields
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new \InvalidArgumentException('Required fields are missing: ' . implode(', ', $missing));
        }

        // Validate data types
        if (!is_numeric($data['tutor_id']) || $data['tutor_id'] <= 0) {
            throw new \InvalidArgumentException('Tutor ID must be a positive integer');
        }
        
        if (!is_numeric($data['user_id']) || $data['user_id'] <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }
        
        if (!is_numeric($data['topic_id']) || $data['topic_id'] <= 0) {
            throw new \InvalidArgumentException('Topic ID must be a positive integer');
        }
        
        if (!is_numeric($data['course_id']) || $data['course_id'] <= 0) {
            throw new \InvalidArgumentException('Course ID must be a positive integer');
        }

        // Verify relationships exist with optimized queries
        $this->validateRelationshipsExist($data);
    }
    
    private function validateRelationshipsExist(array $data): void
    {
        // Use a single query to validate all relationships
        $validationResults = DB::select("
            SELECT 
                (SELECT COUNT(*) FROM users WHERE id = ? AND role IN ('tutor', 'admin')) as tutor_exists,
                (SELECT COUNT(*) FROM users WHERE id = ? AND role = 'student') as student_exists,
                (SELECT COUNT(*) FROM topics WHERE id = ?) as topic_exists,
                (SELECT COUNT(*) FROM courses WHERE id = ?) as course_exists
        ", [$data['tutor_id'], $data['user_id'], $data['topic_id'], $data['course_id']]);
        
        $result = $validationResults[0];
        
        if ($result->tutor_exists == 0) {
            throw new \InvalidArgumentException("Invalid tutor ID: {$data['tutor_id']}");
        }
        
        if ($result->student_exists == 0) {
            throw new \InvalidArgumentException("Invalid student ID: {$data['user_id']}");
        }
        
        if ($result->topic_exists == 0) {
            throw new \InvalidArgumentException("Invalid topic ID: {$data['topic_id']}");
        }
        
        if ($result->course_exists == 0) {
            throw new \InvalidArgumentException("Invalid course ID: {$data['course_id']}");
        }
    }
    
    private function validateAssignmentMode(array $data): void
    {
        $validModes = [Assignment::MODE_SOURCE_SPECIFIC, Assignment::MODE_COMPLETE_TOPIC];
        if (!in_array($data['assignment_mode'], $validModes)) {
            throw new \InvalidArgumentException(
                'Invalid assignment mode. Must be one of: ' . implode(', ', $validModes)
            );
        }
    }
    
    private function validateSourceSpecificRequirements(array $data): void
    {
        if ($data['assignment_mode'] === Assignment::MODE_SOURCE_SPECIFIC) {
            if (empty($data['assigned_source'])) {
                throw new \InvalidArgumentException('Source must be specified for source-specific assignments');
            }
            
            // Validate source exists for the topic
            $this->validateSourceExists($data['topic_id'], $data['assigned_source']);
        }
    }
    
    private function validateDeadline(array $data): void
    {
        if (isset($data['deadline'])) {
            try {
                $deadline = Carbon::parse($data['deadline']);
                if ($deadline->isPast()) {
                    throw new \InvalidArgumentException('Assignment deadline must be in the future');
                }
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid deadline format: ' . $e->getMessage());
            }
        }
    }
    
    private function validateDuration(array $data): void
    {
        if (isset($data['duration_minutes'])) {
            if (!is_numeric($data['duration_minutes']) || $data['duration_minutes'] <= 0) {
                throw new \InvalidArgumentException('Duration must be a positive number');
            }
            
            if ($data['duration_minutes'] > 480) { // 8 hours max
                throw new \InvalidArgumentException('Duration cannot exceed 480 minutes (8 hours)');
            }
        }
    }
    
    private function sendAssignmentNotification(Assignment $assignment, string $type): void
    {
        try {
            switch ($type) {
                case 'created':
                    // LETHAL FIX: Use new notification system for assignment creation
                    \App\Services\NotificationTriggerService::onTutorAssignsAssignment(
                        $assignment->user, 
                        $assignment, 
                        $assignment->tutor
                    );
                    break;
                case 'completed':
                    // LETHAL FIX: Use new notification system for assignment completion
                    $assignmentData = [
                        'assignment_id' => $assignment->id,
                        'assignment_title' => $assignment->title,
                        'score' => $assignment->score,
                        'total_questions' => $assignment->total_questions ?? 1,
                        'completed_at' => $assignment->completed_at?->toISOString()
                    ];
                    \App\Services\NotificationTriggerService::onAssignmentCompleted($assignment->user, $assignmentData);
                    break;
                case 'expired':
                    // LETHAL FIX: Use new notification system for assignment expiry
                    \App\Services\NotificationTriggerService::onAssignmentOverdueReminder(
                        $assignment->user, 
                        $assignment, 
                        0 // 0 hours = overdue
                    );
                    break;
                case 'auto_submitted':
                    // LETHAL FIX: Use new notification system for auto-submission
                    $assignmentData = [
                        'assignment_id' => $assignment->id,
                        'assignment_title' => $assignment->title,
                        'score' => $assignment->score ?? 0,
                        'total_questions' => $assignment->total_questions ?? 1,
                        'auto_submitted' => true
                    ];
                    \App\Services\NotificationTriggerService::onAssignmentCompleted($assignment->user, $assignmentData);
                    break;
            }
        } catch (\Exception $e) {
            \Log::error("Failed to send assignment {$type} notification", [
                'assignment_id' => $assignment->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            // Don't throw - notification failure shouldn't break assignment creation
        }
    }
    
    private function validateBulkTutorStudentRelationships(int $tutorId, array $studentIds): array
    {
        if (empty($studentIds)) {
            return [];
        }
        
        // Get tutor's course IDs
        $tutor = User::find($tutorId);
        $tutorCourseIds = $tutor->courses()->pluck('courses.id')->toArray();
        
        if (empty($tutorCourseIds)) {
            return [];
        }
        
        // Find students enrolled in tutor's courses
        $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
        $coursePlaceholders = str_repeat('?,', count($tutorCourseIds) - 1) . '?';
        
        $validStudents = DB::select("
            SELECT DISTINCT cu.user_id 
            FROM course_user cu
            JOIN users u ON cu.user_id = u.id
            WHERE cu.user_id IN ({$placeholders})
            AND cu.course_id IN ({$coursePlaceholders})
            AND cu.is_enrolled = 1
            AND u.role = 'student'
        ", array_merge($studentIds, $tutorCourseIds));
        
        return array_column($validStudents, 'user_id');
    }
    
    // REMOVED: getBulkExistingAssignments method - No longer needed
    // Tutors now have full flexibility to create duplicate assignments
    
    private function createSingleAssignmentOptimized(array $data): Assignment
    {
        // Optimized version for bulk operations - skip some validations already done in bulk
        try {
            $assignment = Assignment::create($data);
            
            // Send notification asynchronously for bulk operations
            $this->sendAssignmentNotification($assignment, 'created');
            
            return $assignment;
        } catch (\Exception $e) {
            \Log::error('Failed to create single assignment in bulk operation', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
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
                ->where('user_id', $studentId)
                ->exists();
    }

    // REMOVED: assignmentExists method - No longer needed
    // Tutors now have UNLIMITED FLEXIBILITY to create assignments
    // Same topic, same questions, different resources - ALL ALLOWED!

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
            $query->where('user_id', $filters['student_id']);
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
        
        $assignments = $assignments->load('id');
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
                'status' => $assignment->getCurrentStatusAttribute(),
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
            // Since status column was removed, check if assignment is not completed via quiz attempts
            $report['partial_completion'] = $this->calculatePartialCompletion($assignment);
            
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

    /**
     * Get available courses for the current user
     * Tutors only see their enrolled courses, admins see all
     */
    public function getAvailableCourses(): Collection
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            return Course::select(['id', 'name'])->withCount('topics')->get();
        }
        
        return $user->courses()
            ->select(['courses.id', 'courses.name'])
            ->withCount('topics')
            ->get();
    }

    /**
     * Get available topics for the current user
     * Tutors only see topics from their enrolled courses, admins see all
     */
    public function getAvailableTopics(): Collection
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            return Topic::select(['id', 'name', 'course_id'])
                ->with('course:id,name')
                ->withCount('questions')
                ->get();
        }
        
        $courseIds = $user->courses()->pluck('courses.id');
        
        return Topic::select(['id', 'name', 'course_id'])
            ->with('course:id,name')
            ->whereIn('course_id', $courseIds)
            ->withCount('questions')
            ->get();
    }

    /**
     * Get available students for the current user
     * Tutors only see students from their enrolled courses, admins see all
     */
    public function getAvailableStudents(): Collection
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            return User::select(['id', 'name', 'email'])
                ->where('role', User::ROLE_STUDENT)
                ->orderBy('name')
                ->get();
        }
        
        // Get students enrolled in tutor's courses
        $tutorCourseIds = $user->courses()->pluck('courses.id');
        
        return User::select(['users.id', 'users.name', 'users.email'])
            ->join('course_user', 'users.id', '=', 'course_user.user_id')
            ->where('users.role', User::ROLE_STUDENT)
            ->whereIn('course_user.course_id', $tutorCourseIds)
            ->where('course_user.is_enrolled', true)
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    // ===== CONSOLIDATED ASSIGNMENT MANAGEMENT FEATURES =====

    /**
     * Get assignments data with caching (from AssignmentManagementService)
     * Only shows data from tutor's courses unless admin
     */
    public function getAssignmentsData(int $userId, ?string $status = null, ?int $courseId = null, bool $bypassCache = false): array
    {
        $cacheKey = "assignments_data_{$userId}_{$status}_{$courseId}";
        
        // 🚀 INSTANT REFRESH: Bypass cache if requested or use shorter cache time
        if ($bypassCache) {
            // Clear all related cache keys when bypassing
            $this->clearAssignmentCaches($userId);
            return $this->fetchAssignmentsData($userId, $status, $courseId);
        }
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $status, $courseId) {
            return $this->fetchAssignmentsData($userId, $status, $courseId);
        });
    }


    
    private function fetchAssignmentsData(int $userId, ?string $status = null, ?int $courseId = null): array
    {
        $user = User::find($userId);
        
        // Get assignments - filter by tutor's courses unless admin
        $query = Assignment::with(['course:id,name', 'topic:id,name'])
            ->select(['id', 'course_id', 'topic_id', 'title', 'deadline', 'created_at']);

        if (!$user->isAdmin()) {
            $tutorCourseIds = $user->courses()->pluck('courses.id');
            $query->whereIn('course_id', $tutorCourseIds);
        }

        // Status filtering removed since status column was removed
        // Status is now computed dynamically based on deadline
        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $assignments = $query->orderBy('deadline', 'asc')->get();
        
        // Get courses - only tutor's courses unless admin
        if ($user->isAdmin()) {
            $courses = Course::select(['id', 'name'])->get();
        } else {
            $courses = $user->courses()->select(['courses.id', 'courses.name'])->get();
        }
        
        // Get students - only from tutor's courses unless admin
        if ($user->isAdmin()) {
            $students = User::select(['id', 'name', 'email'])
                ->where('role', User::ROLE_STUDENT)
                ->orderBy('name')
                ->get();
        } else {
            $tutorCourseIds = $user->courses()->pluck('courses.id');
            $students = User::select(['users.id', 'users.name', 'users.email'])
                ->join('course_user', 'users.id', '=', 'course_user.user_id')
                ->where('users.role', User::ROLE_STUDENT)
                ->whereIn('course_user.course_id', $tutorCourseIds)
                ->where('course_user.is_enrolled', true)
                ->distinct()
                ->orderBy('users.name')
                ->get();
        }

        return compact('assignments', 'courses', 'students');
    }

    /**
     * Get bulk create data with caching
     * Only shows courses and students that the tutor has access to
     */
    public function getBulkCreateData(int $userId): array
    {
        $cacheKey = "bulk_create_data_{$userId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($userId) {
            $user = User::find($userId);
            
            // Get courses - only tutor's courses unless admin
            if ($user->isAdmin()) {
                $courses = Course::select(['id', 'name'])
                    ->withCount('topics')
                    ->get();
            } else {
                $courses = $user->courses()
                    ->select(['courses.id', 'courses.name'])
                    ->withCount('topics')
                    ->get();
            }
            
            // Get students - only from tutor's courses unless admin
            if ($user->isAdmin()) {
                $students = User::select(['id', 'name', 'email'])
                    ->where('role', User::ROLE_STUDENT)
                    ->orderBy('name')
                    ->get();
            } else {
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $students = User::select(['users.id', 'users.name', 'users.email'])
                    ->join('course_user', 'users.id', '=', 'course_user.user_id')
                    ->where('users.role', User::ROLE_STUDENT)
                    ->whereIn('course_user.course_id', $tutorCourseIds)
                    ->where('course_user.is_enrolled', true)
                    ->distinct()
                    ->orderBy('users.name')
                    ->get();
            }

            // Get all topics grouped by course_id for instant loading
            $allTopics = [];
            if ($user->isAdmin()) {
                $topics = Topic::with('course')
                    ->withCount('questions')
                    ->get();
            } else {
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $topics = Topic::with('course')
                    ->withCount('questions')
                    ->whereIn('course_id', $tutorCourseIds)
                    ->get();
            }
            
            foreach ($topics as $topic) {
                $allTopics[$topic->course_id][] = [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'questions_count' => $topic->questions_count
                ];
            }
            
            // Get all sources grouped by topic_id for instant loading
            $allSources = [];
            foreach ($topics as $topic) {
                // Get distinct sources for this topic
                $sources = DB::table('questions')
                    ->where('topic_id', $topic->id)
                    ->whereNotNull('source')
                    ->where('source', '!=', '')
                    ->distinct()
                    ->pluck('source')
                    ->toArray();
                
                if (!empty($sources)) {
                    $allSources[$topic->id] = $sources;
                }
            }

            return compact('courses', 'students', 'allTopics', 'allSources');
        });
    }

    /**
     * Create bulk assignments from validated form data
     * This method matches what the controller expects
     */
    public function createBulkAssignments(array $validated, int $tutorId): int
    {
        try {
            $studentIds = $validated['student_ids'];
            
            // Prepare assignment data (remove student_ids from the data)
            $assignmentData = array_merge($validated, [
                'tutor_id' => $tutorId
            ]);
            unset($assignmentData['student_ids']);
            
            $createdCount = 0;
            
            // Create individual assignments for each student using the pivot table approach
            DB::transaction(function() use ($assignmentData, $studentIds, &$createdCount) {
                // First create the assignment template
                $assignment = Assignment::create([
                    'topic_id' => $assignmentData['topic_id'],
                    'course_id' => $assignmentData['course_id'],
                    'title' => $assignmentData['title'],
                    'deadline' => $assignmentData['deadline'],
                    'duration_minutes' => $assignmentData['duration_minutes'] ?? 60,
                    'assignment_mode' => $assignmentData['assignment_mode'],
                    'assigned_source' => $assignmentData['assigned_source'] ?? null
                ]);
                
                // Then assign it to all students via pivot table
                $assignment->assignedUsers()->attach($studentIds);
                
                $createdCount = count($studentIds);
                
                // Send notifications (disabled for now due to notification service issues)
                // foreach ($studentIds as $studentId) {
                //     try {
                //         $this->notificationService->notifyAssignmentCreated($assignment);
                //     } catch (\Exception $e) {
                //         \Log::warning('Failed to send assignment notification', [
                //             'assignment_id' => $assignment->id,
                //             'student_id' => $studentId,
                //             'error' => $e->getMessage()
                //         ]);
                //     }
                // }
            });
            
            // 🚀 INSTANT CACHE INVALIDATION - Clear all assignment-related caches
            \Cache::tags(['assignments'])->flush();
            \Cache::forget("assignments_data_{$tutorId}_null_null");
            
            // Clear cache for all affected students
            foreach ($studentIds as $studentId) {
                \Cache::forget("assignments_data_{$studentId}_null_null");
            }
            
            return $createdCount;
            
        } catch (\Exception $e) {
            \Log::error('Failed to create bulk assignments', [
                'validated' => $validated,
                'tutor_id' => $tutorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to create assignments: ' . $e->getMessage());
        }
    }

    /**
     * Clear assignment-related caches
     */
    public function clearAssignmentCaches(int $userId): void
    {
        Cache::forget("assignments_data_{$userId}__");
        Cache::forget("bulk_create_data_{$userId}");
        Cache::forget("counter_data_{$userId}");
    }
}