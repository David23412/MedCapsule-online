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
     * Create a new assignment with enhanced error handling and performance optimization
     */
    public function createAssignment(array $data): Assignment
    {
        try {
            // Validate required fields with detailed error messages
            $this->validateAssignmentData($data);

            // Set default values with validation
            $data['status'] = $data['status'] ?? Assignment::STATUS_ACTIVE;
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
                    $assignment = Assignment::create($data);
                    
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
     * Create bulk assignments for multiple students with optimized performance and error handling
     * TUTORS HAVE FULL FLEXIBILITY - NO DUPLICATE CONSTRAINTS!
     */
    public function createBulkAssignments(int $tutorId, array $studentIds, array $assignmentData): \Illuminate\Support\Collection
    {
        if (empty($studentIds)) {
            throw new \InvalidArgumentException('Student IDs array cannot be empty');
        }

        if (count($studentIds) > 1000) {
            throw new \InvalidArgumentException('Cannot create more than 1000 assignments at once');
        }

        $assignments = collect();
        $errors = collect();
        $currentUser = auth()->user();

        try {
            // Pre-validate tutor-student relationships in bulk for better performance
            if ($currentUser->role !== 'admin') {
                $validRelationships = $this->validateBulkTutorStudentRelationships($tutorId, $studentIds);
                $invalidStudents = array_diff($studentIds, $validRelationships);
                
                if (!empty($invalidStudents)) {
                    throw new \InvalidArgumentException(
                        'Invalid tutor-student relationships for student IDs: ' . implode(', ', $invalidStudents)
                    );
                }
            }

            // Use chunked processing for large batches
            $chunks = array_chunk($studentIds, 100); // Process in chunks of 100
            
            foreach ($chunks as $chunk) {
                DB::transaction(function() use ($tutorId, $chunk, $assignmentData, &$assignments, &$errors) {
                    foreach ($chunk as $studentId) {
                        try {
                            $data = array_merge($assignmentData, [
                                'tutor_id' => $tutorId,
                                'user_id' => $studentId
                            ]);

                            // Clean up any legacy skip_existing flags
                            unset($data['skip_existing']);

                            $assignment = $this->createSingleAssignmentOptimized($data);
                            $assignments->push($assignment);

                        } catch (\Exception $e) {
                            $errors->push([
                                'student_id' => $studentId,
                                'error' => $e->getMessage()
                            ]);
                            
                            \Log::warning('Failed to create assignment for student in bulk operation', [
                                'tutor_id' => $tutorId,
                                'student_id' => $studentId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                });
            }

            // Log summary of bulk operation
            \Log::info('Bulk assignment creation completed', [
                'tutor_id' => $tutorId,
                'total_students' => count($studentIds),
                'successful_assignments' => $assignments->count(),
                'errors' => $errors->count()
            ]);

            // If there were errors but some assignments were created, log the errors
            if ($errors->isNotEmpty()) {
                \Log::warning('Bulk assignment creation had errors', [
                    'errors' => $errors->toArray()
                ]);
            }

            return $assignments;

        } catch (\Exception $e) {
            \Log::error('Critical error in bulk assignment creation', [
                'tutor_id' => $tutorId,
                'student_count' => count($studentIds),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Bulk assignment creation failed: ' . $e->getMessage());
        }
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
            // Update with score percentage if available and assignment not already completed
            if ($scorePercentage !== null && $assignment->status !== Assignment::STATUS_COMPLETED) {
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

        $oldDeadline = $assignment->deadline;

        // If assignment was overdue, reactivate it
        if ($assignment->status === Assignment::STATUS_OVERDUE) {
            $assignment->update([
                'deadline' => $newDeadline,
                'status' => Assignment::STATUS_ACTIVE
            ]);
        } else {
            $assignment->update(['deadline' => $newDeadline]);
        }

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
                    $this->notificationService->notifyAssignmentCreated($assignment);
                    break;
                case 'completed':
                    $this->notificationService->notifyAssignmentCompleted($assignment);
                    break;
                case 'expired':
                    $this->notificationService->notifyAssignmentExpired($assignment);
                    break;
                case 'auto_submitted':
                    $this->notificationService->notifyAssignmentAutoSubmitted($assignment);
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
        
        $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
        $validStudents = DB::select("
            SELECT user_id 
            FROM tutor_student_relationships 
            WHERE tutor_id = ? AND user_id IN ({$placeholders})
        ", array_merge([$tutorId], $studentIds));
        
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