<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Services\NotificationService;

class AssignmentCreationService
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
     * Create assignments in bulk for multiple students.
     */
    public function createBulkAssignments(array $data, int $tutorId): int
    {
        return DB::transaction(function() use ($data, $tutorId) {
            $createdCount = 0;
            $assignmentData = $data;
            $assignmentData['tutor_id'] = $tutorId;
            
            // Create the assignment once
            $assignment = $this->createAssignment($assignmentData);
            
            // Attach all students to the assignment
            if (!empty($data['student_ids'])) {
                $assignment->assignedUsers()->syncWithoutDetaching($data['student_ids']);
                $createdCount = count($data['student_ids']);
            }
            
            return $createdCount;
        });
    }

    /**
     * Enhanced validation and helper methods
     */
    private function validateAssignmentData(array $data): void
    {
        $required = ['tutor_id', 'topic_id', 'course_id', 'title', 'deadline'];
        
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
        // Get the tutor user
        $tutor = User::find($data['tutor_id']);
        
        // Validate tutor exists and is a tutor/admin
        if (!$tutor || !in_array($tutor->role, [User::ROLE_TUTOR, User::ROLE_ADMIN])) {
            throw new \InvalidArgumentException("Invalid tutor ID: {$data['tutor_id']}");
        }
        
        // Validate topic exists
        $topic = Topic::find($data['topic_id']);
        if (!$topic) {
            throw new \InvalidArgumentException("Invalid topic ID: {$data['topic_id']}");
        }
        
        // Validate course exists
        $course = Course::find($data['course_id']);
        if (!$course) {
            throw new \InvalidArgumentException("Invalid course ID: {$data['course_id']}");
        }
        
        // For non-admin tutors, verify they are assigned to teach this student in this course
        if (!$tutor->isAdmin()) {
            // Check if tutor is assigned to this student in the course
            $isAssigned = DB::table('tutor_student_relationships')
                ->where('tutor_id', $tutor->id)
                ->where('user_id', $student->id)
                ->exists();
                
            if (!$isAssigned) {
                throw new \InvalidArgumentException("You are not assigned to this student in this course");
            }
            
            // Verify the student is enrolled in the course
            if (!$student->isEnrolledInCourse($course->id)) {
                throw new \InvalidArgumentException("Student is not enrolled in the specified course");
            }
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
                    // Ensure user relationship is loaded
                    if (!$assignment->relationLoaded('user')) {
                        $assignment->load('user');
                    }
                    
                    // Only send notification if we have a valid user
                    if ($assignment->user) {
                        // LETHAL FIX: Use new notification system for assignment creation
                        \App\Services\NotificationTriggerService::onTutorAssignsAssignment(
                            $assignment->user, 
                            $assignment, 
                            $assignment->relationLoaded('tutor') ? $assignment->tutor : null
                        );
                    }
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

    private function clearAssignmentCaches(int $userId): void
    {
        $cacheTags = ['assignments', "user_{$userId}"];
        Cache::tags($cacheTags)->flush();
    }
}
