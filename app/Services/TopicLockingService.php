<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopicLockingService
{
    /**
     * Lock a topic for students
     */
    public function lockTopic(int $topicId, int $tutorId, string $reason = null): bool
    {
        try {
            $topic = Topic::findOrFail($topicId);
            
            // Verify tutor has permission to lock this topic
            if (!$this->canTutorLockTopic($tutorId, $topicId)) {
                throw new \UnauthorizedAccessException('Tutor does not have permission to lock this topic');
            }

            $topic->update([
                'is_locked' => true,
                'locked_by_tutor_id' => $tutorId,
                'lock_reason' => $reason,
                'locked_at' => now()
            ]);

            Log::info('Topic locked', [
                'topic_id' => $topicId,
                'tutor_id' => $tutorId,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to lock topic', [
                'topic_id' => $topicId,
                'tutor_id' => $tutorId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Unlock a topic
     */
    public function unlockTopic(int $topicId, int $tutorId): bool
    {
        try {
            $topic = Topic::findOrFail($topicId);
            
            // Verify tutor has permission to unlock this topic
            if (!$this->canTutorUnlockTopic($tutorId, $topicId)) {
                throw new \UnauthorizedAccessException('Tutor does not have permission to unlock this topic');
            }

            $topic->update([
                'is_locked' => false,
                'locked_by_tutor_id' => null,
                'lock_reason' => null,
                'locked_at' => null,
                'unlocked_at' => now()
            ]);

            Log::info('Topic unlocked', [
                'topic_id' => $topicId,
                'tutor_id' => $tutorId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to unlock topic', [
                'topic_id' => $topicId,
                'tutor_id' => $tutorId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a topic is locked for a specific student
     */
    public function isTopicLocked(int $topicId, int $studentId = null): bool
    {
        $topic = Topic::find($topicId);
        
        if (!$topic) {
            return false;
        }

        // If topic is globally locked
        if ($topic->is_locked) {
            return true;
        }

        // Check for student-specific locks (if implemented)
        if ($studentId) {
            return $this->isTopicLockedForStudent($topicId, $studentId);
        }

        return false;
    }

    /**
     * Get lock status and details for a topic
     */
    public function getTopicLockStatus(int $topicId): array
    {
        $topic = Topic::with('lockedByTutor')->find($topicId);
        
        if (!$topic) {
            return ['exists' => false];
        }

        return [
            'exists' => true,
            'is_locked' => $topic->is_locked,
            'locked_by_tutor_id' => $topic->locked_by_tutor_id,
            'locked_by_tutor_name' => $topic->lockedByTutor?->name,
            'lock_reason' => $topic->lock_reason ?? null,
            'locked_at' => $topic->locked_at,
            'can_access' => !$topic->is_locked
        ];
    }

    /**
     * Bulk lock multiple topics
     */
    public function bulkLockTopics(array $topicIds, int $tutorId, string $reason = null): array
    {
        $results = [
            'successful' => [],
            'failed' => []
        ];

        DB::transaction(function() use ($topicIds, $tutorId, $reason, &$results) {
            foreach ($topicIds as $topicId) {
                if ($this->lockTopic($topicId, $tutorId, $reason)) {
                    $results['successful'][] = $topicId;
                } else {
                    $results['failed'][] = $topicId;
                }
            }
        });

        return $results;
    }

    /**
     * Bulk unlock multiple topics
     */
    public function bulkUnlockTopics(array $topicIds, int $tutorId): array
    {
        $results = [
            'successful' => [],
            'failed' => []
        ];

        DB::transaction(function() use ($topicIds, $tutorId, &$results) {
            foreach ($topicIds as $topicId) {
                if ($this->unlockTopic($topicId, $tutorId)) {
                    $results['successful'][] = $topicId;
                } else {
                    $results['failed'][] = $topicId;
                }
            }
        });

        return $results;
    }

    /**
     * Get all locked topics for a tutor
     */
    public function getLockedTopicsForTutor(int $tutorId): Collection
    {
        return Topic::with(['course', 'lockedByTutor'])
                   ->where('is_locked', true)
                   ->where('locked_by_tutor_id', $tutorId)
                   ->orderBy('locked_at', 'desc')
                   ->get();
    }

    /**
     * Get all locked topics in a course
     */
    public function getLockedTopicsInCourse(int $courseId): Collection
    {
        return Topic::with('lockedByTutor')
                   ->where('course_id', $courseId)
                   ->where('is_locked', true)
                   ->orderBy('display_order')
                   ->get();
    }

    /**
     * Get accessible topics for a student
     */
    public function getAccessibleTopicsForStudent(int $studentId, int $courseId = null): Collection
    {
        $query = Topic::with('course')
                     ->where('is_locked', false);

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        // Filter by courses the student has access to
        $query->whereHas('course.users', function($q) use ($studentId) {
            $q->where('users.id', $studentId);
        });

        return $query->orderBy('display_order')->get();
    }

    /**
     * Lock all topics in a course
     */
    public function lockCourseTopics(int $courseId, int $tutorId, string $reason = null): array
    {
        $topicIds = Topic::where('course_id', $courseId)->pluck('id')->toArray();
        
        return $this->bulkLockTopics($topicIds, $tutorId, $reason);
    }

    /**
     * Unlock all topics in a course
     */
    public function unlockCourseTopics(int $courseId, int $tutorId): array
    {
        $topicIds = Topic::where('course_id', $courseId)
                        ->where('is_locked', true)
                        ->pluck('id')
                        ->toArray();
        
        return $this->bulkUnlockTopics($topicIds, $tutorId);
    }

    /**
     * Get locking statistics for a tutor
     */
    public function getLockingStatistics(int $tutorId): array
    {
        $totalLocked = Topic::where('locked_by_tutor_id', $tutorId)->count();
        $recentlyLocked = Topic::where('locked_by_tutor_id', $tutorId)
                              ->where('locked_at', '>=', now()->subDays(7))
                              ->count();

        $locksByCourse = Topic::select('courses.name as course_name', DB::raw('COUNT(*) as locked_count'))
                             ->join('courses', 'topics.course_id', '=', 'courses.id')
                             ->where('topics.locked_by_tutor_id', $tutorId)
                             ->where('topics.is_locked', true)
                             ->groupBy('courses.id', 'courses.name')
                             ->get()
                             ->toArray();

        return [
            'total_locked_topics' => $totalLocked,
            'recently_locked' => $recentlyLocked,
            'locks_by_course' => $locksByCourse,
            'lock_activity' => $this->getLockActivity($tutorId)
        ];
    }

    /**
     * Check if student can access topic (considering all restrictions)
     */
    public function canStudentAccessTopic(int $studentId, int $topicId): array
    {
        $topic = Topic::with('course')->find($topicId);
        
        if (!$topic) {
            return [
                'can_access' => false,
                'reason' => 'Topic not found'
            ];
        }

        // Check if topic is locked
        if ($topic->is_locked) {
            return [
                'can_access' => false,
                'reason' => 'Topic is locked by tutor',
                'locked_by' => $topic->lockedByTutor?->name,
                'lock_reason' => $topic->lock_reason
            ];
        }

        // Check if student has access to the course
        $hasAccess = DB::table('course_user')
                      ->where('user_id', $studentId)
                      ->where('course_id', $topic->course_id)
                      ->exists();

        if (!$hasAccess) {
            return [
                'can_access' => false,
                'reason' => 'No access to course'
            ];
        }

        return [
            'can_access' => true,
            'reason' => null
        ];
    }

    /**
     * Private helper methods
     */
    private function canTutorLockTopic(int $tutorId, int $topicId): bool
    {
        // Check if tutor has students enrolled in the course containing this topic
        return DB::table('topics')
                ->join('courses', 'topics.course_id', '=', 'courses.id')
                ->join('course_user', 'courses.id', '=', 'course_user.course_id')
                ->join('tutor_student_relationships', 'course_user.user_id', '=', 'tutor_student_relationships.student_id')
                ->where('topics.id', $topicId)
                ->where('tutor_student_relationships.tutor_id', $tutorId)
                ->exists();
    }

    private function canTutorUnlockTopic(int $tutorId, int $topicId): bool
    {
        $topic = Topic::find($topicId);
        
        // Tutor can unlock if they locked it or if they have permission
        return $topic && (
            $topic->locked_by_tutor_id === $tutorId || 
            $this->canTutorLockTopic($tutorId, $topicId)
        );
    }

    private function isTopicLockedForStudent(int $topicId, int $studentId): bool
    {
        // This could be extended for student-specific locks
        // For now, we only have global topic locks
        return false;
    }

    private function getLockActivity(int $tutorId): array
    {
        $activity = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $locked = Topic::where('locked_by_tutor_id', $tutorId)
                          ->whereDate('locked_at', $date)
                          ->count();
            $unlocked = Topic::where('locked_by_tutor_id', $tutorId)
                            ->whereDate('unlocked_at', $date)
                            ->count();
            
            $activity[$date] = [
                'locked' => $locked,
                'unlocked' => $unlocked
            ];
        }

        return $activity;
    }
}