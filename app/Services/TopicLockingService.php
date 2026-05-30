<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TopicLockingService
{
    /**
     * Lock a topic - SIMPLIFIED VERSION
     *
     * @param Topic $topic The topic to lock
     * @param User $tutor The tutor locking the topic
     * @param string|null $reason The reason for locking (optional) - IGNORED
     * @return bool Whether the lock was successful
     * @throws \InvalidArgumentException If the user doesn't have permission
     */
    public function lock(Topic $topic, User $tutor, ?string $reason = null): bool
    {
        // Validate tutor permission
        $this->validateTutorPermission($topic, $tutor);
        
        // Check if topic is already locked
        if ($topic->is_locked) {
            return true; // Already locked
        }
        
        try {
            DB::beginTransaction();
            
            // Lock the topic - ONLY UPDATE is_locked
            $topic->update([
                'is_locked' => true
            ]);
            
            // Invalidate cache
            $this->invalidateCache($topic);
            
            // Log the action
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to lock topic', [
                'topic_id' => $topic->id,
                'tutor_id' => $tutor->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Unlock a topic - SIMPLIFIED VERSION
     *
     * @param Topic $topic The topic to unlock
     * @param User $tutor The tutor unlocking the topic
     * @return bool Whether the unlock was successful
     * @throws \InvalidArgumentException If the user doesn't have permission
     */
    public function unlock(Topic $topic, User $tutor): bool
    {
        // Validate tutor permission
        $this->validateTutorPermission($topic, $tutor);
        
        // Check if topic is already unlocked
        if (!$topic->is_locked) {
            return true; // Already unlocked
        }
        
        try {
            DB::beginTransaction();
            
            // Unlock the topic - ONLY UPDATE is_locked
            $topic->update([
                'is_locked' => false
            ]);
            
            // Invalidate cache
            $this->invalidateCache($topic);
            
            // Log the action
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to unlock topic', [
                'topic_id' => $topic->id,
                'tutor_id' => $tutor->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Lock multiple topics at once
     *
     * @param array $topicIds Array of topic IDs to lock
     * @param User $tutor The tutor locking the topics
     * @param string|null $reason The reason for locking (optional)
     * @return array Results of the operation with success/failure for each topic
     */
    public function bulkLock(array $topicIds, User $tutor, ?string $reason = null): array
    {
        $results = [];
        
        // Get all topics at once for efficiency
        $topics = Topic::whereIn('id', $topicIds)->get();
        
        // Check if tutor has permission for all topics
        $courseIds = $topics->pluck('course_id')->unique();
        foreach ($courseIds as $courseId) {
            if (!$this->tutorHasCoursePermission($tutor, $courseId)) {
                throw new \InvalidArgumentException('You do not have permission to lock topics in one or more courses');
            }
        }
        
        // Process each topic
        foreach ($topics as $topic) {
            try {
                $success = $this->lock($topic, $tutor, $reason);
                $results[$topic->id] = [
                    'success' => $success,
                    'message' => $success ? 'Topic locked successfully' : 'Topic was already locked'
                ];
            } catch (\Exception $e) {
                $results[$topic->id] = [
                    'success' => false,
                    'message' => 'Failed to lock topic: ' . $e->getMessage()
                ];
            }
        }
        
        // Check for topics that weren't found
        $foundTopicIds = $topics->pluck('id')->toArray();
        foreach ($topicIds as $topicId) {
            if (!in_array($topicId, $foundTopicIds)) {
                $results[$topicId] = [
                    'success' => false,
                    'message' => 'Topic not found'
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Unlock multiple topics at once
     *
     * @param array $topicIds Array of topic IDs to unlock
     * @param User $tutor The tutor unlocking the topics
     * @return array Results of the operation with success/failure for each topic
     */
    public function bulkUnlock(array $topicIds, User $tutor): array
    {
        $results = [];
        
        // Get all topics at once for efficiency
        $topics = Topic::whereIn('id', $topicIds)->get();
        
        // Check if tutor has permission for all topics
        $courseIds = $topics->pluck('course_id')->unique();
        foreach ($courseIds as $courseId) {
            if (!$this->tutorHasCoursePermission($tutor, $courseId)) {
                throw new \InvalidArgumentException('You do not have permission to unlock topics in one or more courses');
            }
        }
        
        // Process each topic
        foreach ($topics as $topic) {
            try {
                $success = $this->unlock($topic, $tutor);
                $results[$topic->id] = [
                    'success' => $success,
                    'message' => $success ? 'Topic unlocked successfully' : 'Topic was already unlocked'
                ];
            } catch (\Exception $e) {
                $results[$topic->id] = [
                    'success' => false,
                    'message' => 'Failed to unlock topic: ' . $e->getMessage()
                ];
            }
        }
        
        // Check for topics that weren't found
        $foundTopicIds = $topics->pluck('id')->toArray();
        foreach ($topicIds as $topicId) {
            if (!in_array($topicId, $foundTopicIds)) {
                $results[$topicId] = [
                    'success' => false,
                    'message' => 'Topic not found'
                ];
            }
        }
        
        return $results;
    }

    public function lockSubject(Course $course, string $subject, User $admin): int
    {
        return $this->setSubjectLockState($course, $subject, $admin, true);
    }

    public function unlockSubject(Course $course, string $subject, User $admin): int
    {
        return $this->setSubjectLockState($course, $subject, $admin, false);
    }

    private function setSubjectLockState(Course $course, string $subject, User $admin, bool $locked): int
    {
        $subject = trim($subject);

        if ($subject === '') {
            throw new \InvalidArgumentException('Subject is required.');
        }

        if (!$this->tutorHasCoursePermission($admin, (int) $course->id)) {
            throw new \InvalidArgumentException('You do not have permission to manage subjects in this course');
        }

        try {
            return DB::transaction(function () use ($course, $subject, $locked) {
                $topics = Topic::query()
                    ->where('course_id', $course->id)
                    ->forSubject($subject)
                    ->get();

                if ($topics->isEmpty()) {
                    return 0;
                }

                $updatedCount = Topic::query()
                    ->whereIn('id', $topics->pluck('id'))
                    ->update(['is_locked' => $locked]);

                foreach ($topics as $topic) {
                    $topic->is_locked = $locked;
                    $this->invalidateCache($topic);
                }

                return $updatedCount;
            });
        } catch (\Exception $e) {
            Log::error('Failed to update subject lock state', [
                'course_id' => $course->id,
                'subject' => $subject,
                'locked' => $locked,
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
    
    /**
     * Get all locked topics for a course
     *
     * @param Course $course The course to check
     * @return Collection Collection of locked topics
     */
    public function getLockedTopics(Course $course): Collection
    {
        return $course->topics()
            ->where('is_locked', true)
            ->with('lockedByTutor')
            ->get();
    }
    
    /**
     * Get all topics locked by a specific tutor
     *
     * @param User $tutor The tutor who locked the topics
     * @return Collection Collection of topics locked by the tutor
     */
    public function getTopicsLockedByTutor(User $tutor): Collection
    {
        return Topic::where('locked_by_tutor_id', $tutor->id)
            ->where('is_locked', true)
            ->with('course')
            ->get();
    }
    
    /**
     * Check if a topic is locked
     *
     * @param Topic $topic The topic to check
     * @return bool Whether the topic is locked
     */
    public function isLocked(Topic $topic): bool
    {
        return (bool) $topic->is_locked;
    }
    
    /**
     * Get lock information for a topic - SIMPLIFIED VERSION
     *
     * @param Topic $topic The topic to get lock information for
     * @return array|null Lock information or null if not locked
     */
    public function getLockInfo(Topic $topic): ?array
    {
        if (!$topic->is_locked) {
            return null;
        }
        
        return [
            'is_locked' => true,
            'locked_at' => now()->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Validate that a tutor has permission to lock/unlock a topic
     *
     * @param Topic $topic The topic to check
     * @param User $tutor The tutor to check
     * @return bool Whether the tutor has permission
     * @throws \InvalidArgumentException If the user doesn't have permission
     */
    private function validateTutorPermission(Topic $topic, User $tutor): bool
    {
        // Admin users can lock/unlock any topic
        if ($tutor->isAdmin()) {
            return true;
        }
        
        // Check if user is a tutor
        if (!$tutor->isTutor()) {
            throw new \InvalidArgumentException('Only tutors and admins can lock/unlock topics');
        }
        
        // Check if tutor has permission for this course
        if (!$this->tutorHasCoursePermission($tutor, $topic->course_id)) {
            throw new \InvalidArgumentException('You do not have permission to lock/unlock topics in this course');
        }
        
        return true;
    }
    
    /**
     * Check if a tutor has permission for a course
     *
     * @param User $tutor The tutor to check
     * @param int $courseId The course ID to check
     * @return bool Whether the tutor has permission
     */
    private function tutorHasCoursePermission(User $tutor, int $courseId): bool
    {
        // Admin users have permission for all courses
        if ($tutor->isAdmin()) {
            return true;
        }
        
        // Check if tutor is enrolled in the course
        return DB::table('course_user')
            ->where('user_id', $tutor->id)
            ->where('course_id', $courseId)
            ->whereNotNull('enrolled_at')
            ->exists();
    }
    
    /**
     * Invalidate cache for a topic
     *
     * @param Topic $topic The topic to invalidate cache for
     */
    private function invalidateCache(Topic $topic): void
    {
        // PHASE 2: Use cache tags for targeted invalidation
        // This clears all user-specific progress and topic data caches at once
        try {
            Cache::tags(['topic', "topic:{$topic->id}", "course:{$topic->course_id}"])->flush();
        } catch (\BadMethodCallException $e) {
            // Some cache drivers do not support tags; explicit keys below still cover local/file caches.
        }
        
        // Manual cleanup for drivers that don't support tags (like file)
        $cacheKeys = [
            "topic:{$topic->id}:questions_count",
            "course:{$topic->course_id}:topics",
            "course_categorization_counts_{$topic->course_id}"
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Invalidate course categorization cache when that optional service exists.
        if (class_exists(CourseTypeService::class)) {
            app(CourseTypeService::class)->invalidateCache($topic->course);
        }
    }
}
