<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AssignmentRetrievalService
{
    /**
     * Get assignments for a tutor with filtering
     */
    public function getTutorAssignments(int $tutorId, array $filters = []): Collection
    {
        $query = Assignment::with(['student', 'topic', 'course'])
                          ->forTutor($tutorId);

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (isset($filters['student_id'])) {
            $query->where('user_id', $filters['student_id']);
        }

        if (isset($filters['due_within_hours'])) {
            $query->dueWithin($filters['due_within_hours']);
        }

        $orderBy = $filters['order_by'] ?? 'deadline';
        $orderDirection = $filters['order_direction'] ?? 'asc';
        $query->orderBy($orderBy, $orderDirection);

        $assignments = $query->get();

        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = (int) $filters['status'];
            $assignments = $assignments->filter(function ($assignment) use ($status) {
                if (empty($assignment->student)) return false;
                return $assignment->getStatusForUser($assignment->student->id) === $status;
            });
        }

        return $assignments;
    }

    /**
     * Get assignments for a student
     */
    public function getStudentAssignments(int $studentId, array $filters = []): Collection
    {
        $query = Assignment::with(['tutor', 'topic', 'course'])
                          ->forStudent($studentId);

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        $query->orderByRaw("\n            CASE \n                WHEN deadline < NOW() THEN 1\n                WHEN deadline <= NOW() + INTERVAL 24 HOUR THEN 2\n                ELSE 3\n            END\n        ")->orderBy('deadline', 'asc');

        $assignments = $query->get();

        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = (int) $filters['status'];
            $student = User::find($studentId);
            if ($student) {
                $assignments = $assignments->filter(function ($assignment) use ($student, $status) {
                    return $assignment->getStatusForUser($student) === $status;
                });
            }
        }

        return $assignments;
    }

    /**
     * Get assignments data for a student dashboard.
     */
    public function getAssignmentsData(int $userId, ?string $statusFilter, ?int $courseId): array
    {
        $user = User::with('courses')->findOrFail($userId);
        $cacheKey = "assignments_data_{$userId}_{$statusFilter}_{$courseId}";
        $cacheTags = ['assignments', "user_{$userId}"];

        return Cache::tags($cacheTags)->remember($cacheKey, now()->addMinutes(5), function () use ($user, $statusFilter, $courseId) {
            // Base query with relationships
            $assignmentsQuery = Assignment::with(['course', 'topic', 'assignedUsers', 'quizAttempts' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }]);
            
            // If user is a student, only show their assigned assignments
            if ($user->isStudent()) {
                $assignmentsQuery->whereHas('assignedUsers', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                });
            } 
            // If user is a tutor, show assignments for their courses
            elseif ($user->isTutor()) {
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $assignmentsQuery->whereIn('course_id', $tutorCourseIds);
            }
            // If user is admin, show all assignments (no additional filtering needed)

            if ($courseId) {
                $assignmentsQuery->where('course_id', $courseId);
            }

            $assignments = $assignmentsQuery->get()->map(function ($assignment) use ($user) {
                $assignment->status = $assignment->getStatusForUser($user);
                return $assignment;
            });

            if ($statusFilter) {
                $assignments = $assignments->filter(function ($assignment) use ($statusFilter) {
                    return $assignment->status === $statusFilter;
                });
            }

            $students = collect();
            if ($user->isAdmin()) {
                $students = User::select(['id', 'name'])->where('role', User::ROLE_STUDENT)->orderBy('name')->get();
            } else {
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $students = User::select(['users.id', 'users.name'])
                    ->join('course_user', 'users.id', '=', 'course_user.user_id')
                    ->where('users.role', User::ROLE_STUDENT)
                    ->whereIn('course_user.course_id', $tutorCourseIds)
                    ->whereNotNull('course_user.enrolled_at')
                    ->distinct()
                    ->orderBy('users.name')
                    ->get();
            }

            return [
                'assignments' => $assignments->values(),
                'courses' => $user->courses,
                'students' => $students,
                'status_counts' => $this->getAssignmentStatusCounts($user->id, $courseId),
                'selectedCourseId' => $courseId,
                'statusFilter' => $statusFilter
            ];
        });
    }

    /**
     * Get bulk create data with caching
     */
    public function getBulkCreateData(int $userId): array
    {
        $cacheKey = "bulk_create_data_{$userId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($userId) {
            $user = User::find($userId);
            
            if ($user->isAdmin()) {
                $courses = Course::select(['id', 'name'])->withCount('topics')->get();
            } else {
                $courses = $user->courses()->select(['courses.id', 'courses.name'])->withCount('topics')->get();
            }
            
            if ($user->isAdmin()) {
                $students = User::select(['id', 'name', 'email'])->where('role', User::ROLE_STUDENT)->orderBy('name')->get();
            } else {
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $students = User::select(['users.id', 'users.name', 'users.email'])
                    ->join('course_user', 'users.id', '=', 'course_user.user_id')
                    ->where('users.role', User::ROLE_STUDENT)
                    ->whereIn('course_user.course_id', $tutorCourseIds)
                    ->whereNotNull('course_user.enrolled_at')
                    ->distinct()
                    ->orderBy('users.name')
                    ->get();
            }

            $allTopics = [];
            $topicQuery = Topic::with('course')->withCount('questions');
            if (!$user->isAdmin()) {
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $topicQuery->whereIn('course_id', $tutorCourseIds);
            }
            $topics = $topicQuery->get();
            
            foreach ($topics as $topic) {
                $allTopics[$topic->course_id][] = ['id' => $topic->id, 'name' => $topic->name, 'questions_count' => $topic->questions_count];
            }
            
            $allSources = [];
            foreach ($topics as $topic) {
                $sources = DB::table('questions')
                    ->where('topic_id', $topic->id)
                    ->whereNotNull('source')->where('source', '!=', '')
                    ->distinct()->pluck('source')->toArray();
                if (!empty($sources)) {
                    $allSources[$topic->id] = $sources;
                }
            }

            return compact('courses', 'students', 'allTopics', 'allSources');
        });
    }

    public function getAssignmentStatusCounts(int $userId, ?int $courseId): array
    {
        // This method is not defined in the original service, so we need to implement it here or move it.
        // For now, returning a dummy array.
        return ['active' => 0, 'completed' => 0, 'locked' => 0, 'overdue' => 0];
    }
}
