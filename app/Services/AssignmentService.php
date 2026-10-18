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
use App\Services\AssignmentCreationService;
use App\Services\AssignmentCompletionService;
use App\Services\AssignmentRetrievalService;
use App\Services\AssignmentLifecycleService;

class AssignmentService
{
    private NotificationService $notificationService;
        private AssignmentCreationService $assignmentCreationService;
    private AssignmentCompletionService $assignmentCompletionService;
    private AssignmentRetrievalService $assignmentRetrievalService;
    private AssignmentLifecycleService $assignmentLifecycleService;

    public function __construct(
        NotificationService $notificationService,
        AssignmentCreationService $assignmentCreationService,
        AssignmentCompletionService $assignmentCompletionService,
        AssignmentRetrievalService $assignmentRetrievalService,
        AssignmentLifecycleService $assignmentLifecycleService
    ) {
        $this->notificationService = $notificationService;
        $this->assignmentCreationService = $assignmentCreationService;
        $this->assignmentCompletionService = $assignmentCompletionService;
        $this->assignmentRetrievalService = $assignmentRetrievalService;
        $this->assignmentLifecycleService = $assignmentLifecycleService;
    }

    public function createAssignment(array $data): Assignment
    {
        return $this->assignmentCreationService->createAssignment($data);
    }

    public function updateAssignment(Assignment $assignment, array $data): Assignment
    {
        return $this->assignmentCreationService->updateAssignment($assignment, $data);
    }

    public function createBulkAssignments(array $data, int $tutorId): int
    {
        return $this->assignmentCreationService->createBulkAssignments($data, $tutorId);
    }


    /**
     * Complete an assignment with score
     */
    public function completeAssignment(Assignment $assignment, array $answers = [], ?int $score = null): bool
    {
        return $this->assignmentCompletionService->completeAssignment($assignment, $answers, $score);
    }

    /**
     * Get assignments for a tutor with filtering
     */
    public function getTutorAssignments(int $tutorId, array $filters = []): Collection
    {
        return $this->assignmentRetrievalService->getTutorAssignments($tutorId, $filters);
    }

    /**
     * Get assignments for a student
     */
    public function getStudentAssignments(int $studentId, array $filters = []): Collection
    {
        return $this->assignmentRetrievalService->getStudentAssignments($studentId, $filters);
    }

    /**
     * Delete an assignment (soft delete with validation)
     */
    public function deleteAssignment(Assignment $assignment): bool
    {
        return $this->assignmentLifecycleService->deleteAssignment($assignment);
    }

    /**
     * Extend the deadline for an assignment
     */
    public function extendDeadline(Assignment $assignment, Carbon $newDeadline): bool
    {
        return $this->assignmentLifecycleService->extendDeadline($assignment, $newDeadline);
    }

    
    /**
     * Auto-submit assignment when time limit expires
     */
    public function autoSubmitAssignment(Assignment $assignment, array $answers = []): bool
    {
        return $this->assignmentCompletionService->autoSubmitAssignment($assignment, $answers);
    }


    
    /**
     * Get assignments data for a student dashboard.
     */
    public function getAssignmentsData(int $userId, ?string $statusFilter, ?int $courseId): array
    {
        return $this->assignmentRetrievalService->getAssignmentsData($userId, $statusFilter, $courseId);
    }

    /**
     * Get bulk create data with caching
     */
    public function getBulkCreateData(int $userId): array
    {
        return $this->assignmentRetrievalService->getBulkCreateData($userId);
    }


    public function getCounterData(int $userId): array
    {
        return [
            'success' => true,
            'status_counts' => $this->assignmentRetrievalService->getAssignmentStatusCounts($userId, null),
        ];
    }

    public function refreshStatuses(int $userId): array
    {
        // Status is derived from deadlines and attempts. Refresh cached views,
        // rather than writing the obsolete status column or creating attempts.
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags(['assignments'])->flush();
        }

        return $this->getCounterData($userId);
    }

}
