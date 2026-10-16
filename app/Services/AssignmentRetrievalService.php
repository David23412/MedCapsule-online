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
    private const DASHBOARD_STATUS_ACTIVE = 'active';
    private const DASHBOARD_STATUS_COMPLETED = 'completed';
    private const DASHBOARD_STATUS_OVERDUE = 'overdue';

    /**
     * Get assignments for a tutor with filtering.
     * Uses assignedUsers (many-to-many) since there's no single student relation.
     */
    public function getTutorAssignments(int $tutorId, array $filters = []): Collection
    {
        $tutor = User::find($tutorId);
        $tutorCourseIds = $tutor ? $tutor->courses()->pluck('courses.id')->all() : [];

        $query = Assignment::with(['assignedUsers', 'topic', 'course', 'attempts'])
                          ->whereIn('course_id', $tutorCourseIds);

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (isset($filters['student_id'])) {
            $query->whereHas('assignedUsers', function ($q) use ($filters) {
                $q->where('users.id', $filters['student_id']);
            });
        }

        if (isset($filters['due_within_hours'])) {
            $query->dueWithin($filters['due_within_hours']);
        }

        $orderBy = $filters['order_by'] ?? 'deadline';
        $orderDirection = $filters['order_direction'] ?? 'asc';
        $query->orderBy($orderBy, $orderDirection);

        $assignments = $query->get();

        if (isset($filters['status']) && $filters['status'] !== '' && isset($filters['student_id'])) {
            $status = (int) $filters['status'];
            $student = User::find($filters['student_id']);
            if ($student) {
                $assignments = $assignments->filter(function ($assignment) use ($student, $status) {
                    return $assignment->getStatusForUser($student) === $status;
                });
            }
        }

        return $assignments;
    }

    /**
     * Get assignments for a student.
     * Uses assignedUsers (many-to-many) to find the student's assignments.
     */
    public function getStudentAssignments(int $studentId, array $filters = []): Collection
    {
        $query = Assignment::with(['topic', 'course', 'attempts'])
                          ->whereHas('assignedUsers', function ($q) use ($studentId) {
                              $q->where('users.id', $studentId);
                          });

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        $query->orderByRaw("
            CASE 
                WHEN deadline < NOW() THEN 1
                WHEN deadline <= NOW() + INTERVAL 24 HOUR THEN 2
                ELSE 3
            END
        ")->orderBy('deadline', 'asc');

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
            $assignmentsQuery = $this->buildDashboardAssignmentsQuery($user, $courseId, true);
            $this->applyDashboardStatusFilter($assignmentsQuery, $statusFilter);

            $assignments = $assignmentsQuery
                ->orderBy('assignments.deadline')
                ->get()
                ->map(function ($assignment) use ($user) {
                    $assignment->display_status = $assignment->getStatusForUser($user);
                    $assignment->display_status_string = $this->mapDashboardStatusString($assignment);
                    $assignment->status = $assignment->display_status_string;

                    return $assignment;
                });

            $students = collect();
            if ($user->isAdmin()) {
                $students = User::select(['id', 'name'])->where('role', User::ROLE_STUDENT)->orderBy('name')->get();
            } else {
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $students = User::select(['users.id', 'users.name'])
                    ->join('course_user', 'users.id', '=', 'course_user.user_id')
                    ->where('users.role', User::ROLE_STUDENT)
                    ->whereIn('course_user.course_id', $tutorCourseIds)
                    ->where('course_user.is_enrolled', true)
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
                $students = User::select(['id', 'name', 'username', 'email'])->where('role', User::ROLE_STUDENT)->orderBy('name')->get();
            } else {
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $students = User::select(['users.id', 'users.name', 'users.username', 'users.email'])
                    ->join('course_user', 'users.id', '=', 'course_user.user_id')
                    ->where('users.role', User::ROLE_STUDENT)
                    ->whereIn('course_user.course_id', $tutorCourseIds)
                    ->where('course_user.is_enrolled', true)
                    ->distinct()
                    ->orderBy('users.name')
                    ->get();
            }

            $allTopics = [];
            $topicQuery = Topic::query()
                ->select('id', 'name', 'course_id')
                ->withCount('questions');
            if (!$user->isAdmin()) {
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $topicQuery->whereIn('course_id', $tutorCourseIds);
            }
            $topics = $topicQuery->get();
            
            foreach ($topics as $topic) {
                $allTopics[$topic->course_id][] = ['id' => $topic->id, 'name' => $topic->name, 'questions_count' => $topic->questions_count];
            }
            
            $allSources = [];
            $topicIds = $topics->pluck('id')->all();

            if (!empty($topicIds)) {
                $sourceRows = DB::table('questions')
                    ->select('topic_id', 'source')
                    ->whereIn('topic_id', $topicIds)
                    ->whereNotNull('source')
                    ->where('source', '!=', '')
                    ->distinct()
                    ->orderBy('topic_id')
                    ->orderBy('source')
                    ->get();

                foreach ($sourceRows as $row) {
                    $allSources[$row->topic_id][] = $row->source;
                }
            }

            return compact('courses', 'students', 'allTopics', 'allSources');
        });
    }

    public function getAssignmentStatusCounts(int $userId, ?int $courseId): array
    {
        $user = User::with('courses')->findOrFail($userId);
        $baseQuery = $this->buildDashboardAssignmentsQuery($user, $courseId, false);

        $active = (clone $baseQuery);
        $this->applyDashboardStatusFilter($active, self::DASHBOARD_STATUS_ACTIVE);

        $completed = (clone $baseQuery);
        $this->applyDashboardStatusFilter($completed, self::DASHBOARD_STATUS_COMPLETED);

        $overdue = (clone $baseQuery);
        $this->applyDashboardStatusFilter($overdue, self::DASHBOARD_STATUS_OVERDUE);

        return [
            'active' => $active->count(),
            'completed' => $completed->count(),
            'locked' => 0,
            'overdue' => $overdue->count(),
        ];
    }

    private function buildDashboardAssignmentsQuery(User $user, ?int $courseId, bool $withRelations): \Illuminate\Database\Eloquent\Builder
    {
        $attemptAggregate = DB::table('quiz_attempts')
            ->selectRaw('assignment_id, COUNT(*) as attempt_count, SUM(score) as score_sum, SUM(total_questions) as total_questions_sum')
            ->where('user_id', $user->id)
            ->groupBy('assignment_id');

        $query = Assignment::query()
            ->select('assignments.*')
            ->leftJoin('assignment_user as personal_assignment', function ($join) use ($user) {
                $join->on('personal_assignment.assignment_id', '=', 'assignments.id')
                    ->where('personal_assignment.user_id', $user->id);
            })
            ->addSelect(DB::raw('COALESCE(personal_assignment.extended_deadline, assignments.deadline) as effective_deadline'))
            ->leftJoinSub($attemptAggregate, 'user_attempt_aggregate', function ($join) {
                $join->on('user_attempt_aggregate.assignment_id', '=', 'assignments.id');
            })
            ->addSelect(DB::raw('COALESCE(user_attempt_aggregate.attempt_count, 0) as user_attempt_count'))
            ->addSelect(DB::raw('COALESCE(user_attempt_aggregate.score_sum, 0) as user_score_sum'))
            ->addSelect(DB::raw('COALESCE(user_attempt_aggregate.total_questions_sum, 0) as user_total_questions_sum'));

        if ($withRelations) {
            $query->with([
                'course',
                'topic',
                'assignedUsers',
                'quizAttempts' => function ($attemptQuery) use ($user) {
                    $attemptQuery->where('user_id', $user->id);
                },
            ]);
        }

        if ($user->isStudent()) {
            $query->whereHas('assignedUsers', function ($assignmentUserQuery) use ($user) {
                $assignmentUserQuery->where('users.id', $user->id);
            });
        } elseif ($user->isTutor()) {
            $query->whereIn('assignments.course_id', $user->courses->pluck('id'));
        }

        if ($courseId) {
            $query->where('assignments.course_id', $courseId);
        }

        return $query;
    }

    private function applyDashboardStatusFilter(\Illuminate\Database\Eloquent\Builder $query, ?string $statusFilter): void
    {
        if (!$statusFilter) {
            return;
        }

        $completionSql = 'COALESCE(user_attempt_aggregate.total_questions_sum, 0) > 0 AND (COALESCE(user_attempt_aggregate.score_sum, 0) * 100.0 / NULLIF(user_attempt_aggregate.total_questions_sum, 0)) >= 50';
        $deadlineExpiredSql = 'COALESCE(personal_assignment.extended_deadline, assignments.deadline) < ?';

        if ($statusFilter === self::DASHBOARD_STATUS_COMPLETED) {
            $query->whereRaw($completionSql);
            return;
        }

        if ($statusFilter === self::DASHBOARD_STATUS_OVERDUE) {
            $query->whereRaw("NOT ({$completionSql})")
                ->whereRaw($deadlineExpiredSql, [now()]);
            return;
        }

        if ($statusFilter === self::DASHBOARD_STATUS_ACTIVE) {
            $query->whereRaw("NOT ({$completionSql})")
                ->whereRaw("NOT ({$deadlineExpiredSql})", [now()])
                ->whereRaw('COALESCE(user_attempt_aggregate.attempt_count, 0) = 0');
        }
    }

    private function mapDashboardStatusString(Assignment $assignment): string
    {
        $score = (float) ($assignment->user_score_sum ?? 0);
        $totalQuestions = (float) ($assignment->user_total_questions_sum ?? 0);
        $attemptCount = (int) ($assignment->user_attempt_count ?? 0);

        if ($totalQuestions > 0 && (($score * 100.0) / $totalQuestions) >= 50) {
            return self::DASHBOARD_STATUS_COMPLETED;
        }

        if ($assignment->effective_deadline && now()->gt($assignment->effective_deadline)) {
            return self::DASHBOARD_STATUS_OVERDUE;
        }

        if ($attemptCount > 0) {
            return 'locked';
        }

        return self::DASHBOARD_STATUS_ACTIVE;
    }
}
