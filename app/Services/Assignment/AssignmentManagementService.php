<?php

namespace App\Services\Assignment;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use App\Models\Question;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AssignmentManagementService
{
    /**
     * Get assignments data with caching
     */
    public function getAssignmentsData(int $userId, ?string $status = null, ?int $courseId = null): array
    {
        $cacheKey = "assignments_data_{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($status, $courseId) {
            $query = Assignment::with(['course:id,name', 'topic:id,name'])
                ->select(['id', 'course_id', 'topic_id', 'title', 'deadline', 'status', 'created_at']);

            if ($status) {
                $query->where('status', $status);
            }
            if ($courseId) {
                $query->where('course_id', $courseId);
            }

            $assignments = $query->orderBy('deadline', 'asc')->get();
            
            $courses = Cache::remember('courses_all', 3600, fn() => 
                Course::select(['id', 'name'])->get()
            );
            $students = Cache::remember('students_non_admin', 3600, fn() => 
                User::select(['id', 'name', 'email'])->where('role', '!=', 'admin')->get()
            );

            return compact('assignments', 'courses', 'students');
        });
    }

    /**
     * Get bulk create data with caching
     */
    public function getBulkCreateData(int $userId): array
    {
        $cacheKey = "bulk_create_data_{$userId}";
        
        return Cache::remember($cacheKey, 1800, function () {
            $courses = Course::select(['id', 'name'])
                ->withCount('topics')
                ->get();
            
            $students = User::select(['id', 'name', 'email'])
                ->where('role', '!=', 'admin')
                ->get();
            
            $allTopics = Topic::select([
                    'id', 'name', 'course_id',
                    DB::raw('(SELECT COUNT(*) FROM questions WHERE topic_id = topics.id) as questions_count')
                ])
                ->get()
                ->groupBy('course_id');
            
            $allSources = Question::select(['topic_id', 'source'])
                ->whereNotNull('source')
                ->where('source', '!=', '')
                ->distinct()
                ->get()
                ->groupBy('topic_id')
                ->map(function ($sources) {
                    return $sources->pluck('source')->unique()->values()->toArray();
                });
            
            return [
                'courses' => $courses,
                'students' => $students,
                'allTopics' => $allTopics ?? collect(),
                'allSources' => $allSources ?? collect()
            ];
        });
    }

    /**
     * 🚀 GENIUS: Create assignments with efficient pivot table integration
     */
    public function createBulkAssignments(array $validated, int $tutorId): int
    {
        $now = now();
        
        // Create the assignment (single record) - NO user_id needed
        $assignmentId = DB::table('assignments')->insertGetId([
            'topic_id' => $validated['topic_id'],
            'course_id' => $validated['course_id'],
            'title' => $validated['title'] ?? null,
            'deadline' => $validated['deadline'],
            'duration_minutes' => $validated['duration_minutes'] === null || $validated['duration_minutes'] === '' ? 0.00 : (float)($validated['duration_minutes']),
            'assignment_mode' => $validated['assignment_mode'],
            'assigned_source' => $validated['assigned_source'] ?? null,
            'status' => Assignment::STATUS_ACTIVE,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 🚀 GENIUS: Assign to selected students via pivot table
        $studentAssignments = [];
        foreach ($validated['student_ids'] as $studentId) {
            $studentAssignments[] = [
                'assignment_id' => $assignmentId,
                'user_id' => $studentId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        // Bulk insert student assignments
        DB::table('assignment_users')->insert($studentAssignments);
        
        $this->clearAssignmentCaches($tutorId);
        
        return 1; // One assignment created
    }

    /**
     * Update assignment
     */
    public function updateAssignment(Assignment $assignment, array $validated): array
    {
        $updateData = [];
        
        if (isset($validated['deadline'])) {
            $updateData['deadline'] = $validated['deadline'];
        }
        
        if (isset($validated['duration_minutes'])) {
            $updateData['duration_minutes'] = $validated['duration_minutes'];
        }
        
        if (isset($validated['status']) && !empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
            
            switch ($updateData['status']) {
                case Assignment::STATUS_ACTIVE:
                    $updateData['completed_at'] = null;
                    $updateData['score_percentage'] = null;
                    break;
                    
                case Assignment::STATUS_COMPLETED:
                    $updateData['completed_at'] = now();
                    break;
            }
        }
        
        $updateData['updated_at'] = now();
        
        if (!empty($updateData)) {
            DB::table('assignments')
                ->where('id', $assignment->id)
                ->update($updateData);
        }

        $this->clearAssignmentCaches(auth()->id());

        return [
            'success' => true,
            'message' => 'Assignment updated successfully!',
            'updated_fields' => array_keys($updateData)
        ];
    }

    /**
     * Delete assignment
     */
    public function deleteAssignment(Assignment $assignment): array
    {
        DB::table('assignments')->where('id', $assignment->id)->delete();
        $this->clearAssignmentCaches(auth()->id());

        return ['success' => true];
    }

    /**
     * Get topics for course with caching
     */
    public function getTopicsForCourse(int $courseId): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "topics_course_{$courseId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($courseId) {
            return Topic::select(['id', 'name'])
                ->where('course_id', $courseId)
                ->withCount('questions')
                ->get();
        });
    }

    /**
     * Get students for course with caching
     */
    public function getStudentsForCourse(int $courseId): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "students_course_{$courseId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($courseId) {
            return DB::table('users')
                ->select(['id', 'name', 'email'])
                ->where('role', '!=', 'admin')
                ->whereExists(function ($query) use ($courseId) {
                    $query->select(DB::raw(1))
                          ->from('course_enrollments')
                          ->whereColumn('course_enrollments.user_id', 'users.id')
                          ->where('course_enrollments.course_id', $courseId);
                })
                ->get();
        });
    }

    /**
     * Extend assignment deadline
     */
    public function extendDeadline(Assignment $assignment, string $newDeadline): array
    {
        DB::table('assignments')
            ->where('id', $assignment->id)
            ->update([
                'deadline' => $newDeadline,
                'status' => Assignment::STATUS_ACTIVE,
                'updated_at' => now()
            ]);

        $this->clearAssignmentCaches(auth()->id());

        return ['success' => true];
    }

    /**
     * Mark assignment as completed
     */
    public function markCompleted(Assignment $assignment, ?float $scorePercentage = null): array
    {
        DB::table('assignments')
            ->where('id', $assignment->id)
            ->update([
                'status' => Assignment::STATUS_COMPLETED,
                'score_percentage' => $scorePercentage,
                'updated_at' => now()
            ]);

        $this->clearAssignmentCaches(auth()->id());

        return ['success' => true];
    }

    /**
     * Get assignment counter data
     */
    public function getCounterData(int $userId): array
    {
        $cacheKey = "assignment_counters_{$userId}";
        
        $counts = Cache::remember($cacheKey, 300, function () use ($userId) {
            return DB::table('assignments')
                ->where('tutor_id', $userId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue
                ')
                ->first();
        });

        return [
            'total' => $counts->total ?? 0,
            'active' => $counts->active ?? 0,
            'overdue' => $counts->overdue ?? 0
        ];
    }

    /**
     * Update overdue assignments
     */
    public function updateOverdue(): array
    {
        $updated = DB::table('assignments')
            ->where('status', Assignment::STATUS_ACTIVE)
            ->where('deadline', '<', now())
            ->update([
                'status' => Assignment::STATUS_OVERDUE,
                'updated_at' => now()
            ]);

        $this->clearAssignmentCaches(auth()->id());

        return ['success' => true, 'updated' => $updated];
    }

    /**
     * Enforce real-time status updates
     */
    public function enforceRealTimeStatus(): array
    {
        $now = now();
        
        $overdueCount = DB::table('assignments')
            ->where('status', Assignment::STATUS_ACTIVE)
            ->where('deadline', '<', $now)
            ->update(['status' => Assignment::STATUS_OVERDUE, 'updated_at' => $now]);

        $expiredCount = DB::table('assignments')
            ->where('status', Assignment::STATUS_ACTIVE)
            ->whereNotNull('duration_minutes')
            ->whereRaw('created_at + INTERVAL duration_minutes MINUTE < ?', [$now])
            ->update(['status' => Assignment::STATUS_EXPIRED, 'updated_at' => $now]);

        $this->clearAssignmentCaches(auth()->id());

        return [
            'success' => true, 
            'overdue_updated' => $overdueCount,
            'expired_updated' => $expiredCount
        ];
    }

    /**
     * 🚀 GENIUS: Update assigned users for an assignment
     */
    public function updateAssignedUsers(Assignment $assignment, array $userIds): array
    {
        try {
            // Remove existing assignments
            DB::table('assignment_users')
                ->where('assignment_id', $assignment->id)
                ->delete();
            
            // Add new assignments
            $now = now();
            $studentAssignments = [];
            foreach ($userIds as $userId) {
                $studentAssignments[] = [
                    'assignment_id' => $assignment->id,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            
            if (!empty($studentAssignments)) {
                DB::table('assignment_users')->insert($studentAssignments);
            }
            
            $this->clearAssignmentCaches(auth()->id());
            
            return [
                'success' => true,
                'message' => 'Assignment users updated successfully!',
                'assigned_count' => count($userIds)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update assignment users: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clear assignment-related caches
     */
    private function clearAssignmentCaches(int $userId): void
    {
        Cache::forget("assignments_data_{$userId}");
        Cache::forget("bulk_create_data_{$userId}");
        Cache::forget("assignment_counters_{$userId}");
    }
}