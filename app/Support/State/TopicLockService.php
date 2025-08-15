<?php

namespace App\Support\State;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Single Source of Truth for Topic Locking State
 * 
 * Replaces split brain logic between:
 * - topics.is_locked (boolean)
 * - topics.locked_at (datetime)
 * - topics.unlocked_at (datetime)
 * - topics.locked_by_tutor_id (foreign key)
 */
class TopicLockService
{
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Get definitive topic lock status
     * 
     * @param Topic $topic
     * @return array
     */
    public function getTopicLockStatus(Topic $topic): array
    {
        \Log::debug('SSOT TopicLockService::getTopicLockStatus called', [
            'topic_id' => $topic->id,
            'topic_name' => $topic->name,
            'course_id' => $topic->course_id,
            'called_at' => now()
        ]);
        
        $cacheKey = "topic_lock_status_{$topic->id}_v" . ($topic->updated_at?->timestamp ?? 0);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($topic) {
            // Priority 1: Check timestamp-based locking (most authoritative)
            $lockedAt = $topic->locked_at;
            $unlockedAt = $topic->unlocked_at;
            
            $isLocked = false;
            $lockReason = null;
            $lockedBy = null;
            $lockMethod = 'none';
            
            if ($lockedAt && $unlockedAt) {
                // Both timestamps exist - compare them
                $isLocked = $lockedAt->isAfter($unlockedAt);
                $lockMethod = 'timestamp_comparison';
            } elseif ($lockedAt && !$unlockedAt) {
                // Only locked_at exists - topic is locked
                $isLocked = true;
                $lockMethod = 'locked_at_only';
            } elseif (!$lockedAt && $unlockedAt) {
                // Only unlocked_at exists - topic is unlocked
                $isLocked = false;
                $lockMethod = 'unlocked_at_only';
            } else {
                // No timestamps - check legacy boolean field
                $isLocked = (bool) $topic->is_locked;
                $lockMethod = 'legacy_boolean';
            }
            
            // Get lock details if locked
            if ($isLocked) {
                $lockReason = $topic->lock_reason ?? 'Topic locked by tutor';
                $lockedBy = $topic->locked_by_tutor_id;
            }
            
            return [
                'is_locked' => $isLocked,
                'lock_reason' => $lockReason,
                'locked_by_tutor_id' => $lockedBy,
                'locked_at' => $lockedAt,
                'unlocked_at' => $unlockedAt,
                'lock_method' => $lockMethod,
                'computed_at' => now()
            ];
        });
    }

    /**
     * Check if topic is locked (simple boolean)
     */
    public function isTopicLocked(Topic $topic): bool
    {
        return $this->getTopicLockStatus($topic)['is_locked'];
    }

    /**
     * Get lock reason
     */
    public function getLockReason(Topic $topic): ?string
    {
        return $this->getTopicLockStatus($topic)['lock_reason'];
    }

    /**
     * Get who locked the topic
     */
    public function getLockedBy(Topic $topic): ?int
    {
        return $this->getTopicLockStatus($topic)['locked_by_tutor_id'];
    }

    /**
     * Lock topic with proper state management
     */
    public function lockTopic(Topic $topic, int $tutorId, string $reason = null): bool
    {
        return DB::transaction(function () use ($topic, $tutorId, $reason) {
            $lockTime = now();
            
            $topic->update([
                'locked_at' => $lockTime,
                'locked_by_tutor_id' => $tutorId,
                'lock_reason' => $reason ?? 'Topic locked by tutor',
                'unlocked_at' => null // Clear any previous unlock timestamp
            ]);
            
            // Clear cache
            $this->clearTopicLockCache($topic);
            
            logger()->info('Topic locked via TopicLockService', [
                'topic_id' => $topic->id,
                'topic_name' => $topic->name,
                'locked_by_tutor_id' => $tutorId,
                'lock_reason' => $reason,
                'locked_at' => $lockTime,
                'lock_method' => 'service_lock'
            ]);
            
            return true;
        });
    }

    /**
     * Unlock topic with proper state management
     */
    public function unlockTopic(Topic $topic, int $tutorId): bool
    {
        return DB::transaction(function () use ($topic, $tutorId) {
            $unlockTime = now();
            
            $topic->update([
                'unlocked_at' => $unlockTime,
                'lock_reason' => null // Clear lock reason when unlocking
            ]);
            
            // Clear cache
            $this->clearTopicLockCache($topic);
            
            logger()->info('Topic unlocked via TopicLockService', [
                'topic_id' => $topic->id,
                'topic_name' => $topic->name,
                'unlocked_by_tutor_id' => $tutorId,
                'unlocked_at' => $unlockTime,
                'previous_locked_at' => $topic->locked_at,
                'unlock_method' => 'service_unlock'
            ]);
            
            return true;
        });
    }

    /**
     * Toggle topic lock status
     */
    public function toggleTopicLock(Topic $topic, int $tutorId, string $reason = null): bool
    {
        $lockStatus = $this->getTopicLockStatus($topic);
        
        if ($lockStatus['is_locked']) {
            return $this->unlockTopic($topic, $tutorId);
        } else {
            return $this->lockTopic($topic, $tutorId, $reason);
        }
    }

    /**
     * Get locked topics for a tutor
     */
    public function getTutorLockedTopics(int $tutorId, int $courseId = null): array
    {
        $cacheKey = "tutor_locked_topics_{$tutorId}" . ($courseId ? "_{$courseId}" : '');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tutorId, $courseId) {
            $query = Topic::where('locked_by_tutor_id', $tutorId)
                ->whereNotNull('locked_at')
                ->where(function ($q) {
                    $q->whereNull('unlocked_at')
                      ->orWhereColumn('locked_at', '>', 'unlocked_at');
                });
                
            if ($courseId) {
                $query->where('course_id', $courseId);
            }
            
            $topics = $query->with(['course'])->get();
            
            return $topics->map(function ($topic) {
                $lockStatus = $this->getTopicLockStatus($topic);
                
                return [
                    'topic_id' => $topic->id,
                    'topic_name' => $topic->name,
                    'course_name' => $topic->course->name ?? 'Unknown',
                    'course_id' => $topic->course_id,
                    'lock_status' => $lockStatus,
                    'locked_duration' => $lockStatus['locked_at'] ? 
                        $lockStatus['locked_at']->diffForHumans() : null
                ];
            })->toArray();
        });
    }

    /**
     * Get all locked topics in a course
     */
    public function getCourseLockedTopics(int $courseId): array
    {
        $cacheKey = "course_locked_topics_{$courseId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId) {
            $topics = Topic::where('course_id', $courseId)
                ->whereNotNull('locked_at')
                ->where(function ($q) {
                    $q->whereNull('unlocked_at')
                      ->orWhereColumn('locked_at', '>', 'unlocked_at');
                })
                ->with(['lockedByTutor'])
                ->get();
                
            return $topics->map(function ($topic) {
                $lockStatus = $this->getTopicLockStatus($topic);
                
                return [
                    'topic_id' => $topic->id,
                    'topic_name' => $topic->name,
                    'lock_status' => $lockStatus,
                    'locked_by_name' => $topic->lockedByTutor->name ?? 'Unknown',
                    'locked_duration' => $lockStatus['locked_at'] ? 
                        $lockStatus['locked_at']->diffForHumans() : null
                ];
            })->toArray();
        });
    }

    /**
     * Check if user can access topic (considering lock status)
     */
    public function canUserAccessTopic(Topic $topic, User $user): array
    {
        $lockStatus = $this->getTopicLockStatus($topic);
        
        // Admins can always access
        if ($user->isAdmin()) {
            return [
                'can_access' => true,
                'access_reason' => 'admin_override',
                'lock_status' => $lockStatus
            ];
        }
        
        // Tutors can access topics they locked
        if ($user->isTutor() && $lockStatus['locked_by_tutor_id'] === $user->id) {
            return [
                'can_access' => true,
                'access_reason' => 'tutor_owner',
                'lock_status' => $lockStatus
            ];
        }
        
        // Students cannot access locked topics
        if ($lockStatus['is_locked']) {
            return [
                'can_access' => false,
                'access_reason' => 'topic_locked',
                'lock_status' => $lockStatus
            ];
        }
        
        return [
            'can_access' => true,
            'access_reason' => 'topic_unlocked',
            'lock_status' => $lockStatus
        ];
    }

    /**
     * Bulk lock multiple topics
     */
    public function bulkLockTopics(array $topicIds, int $tutorId, string $reason = null): array
    {
        $results = [];
        
        DB::transaction(function () use ($topicIds, $tutorId, $reason, &$results) {
            $lockTime = now();
            
            foreach ($topicIds as $topicId) {
                $topic = Topic::find($topicId);
                if ($topic) {
                    $topic->update([
                        'locked_at' => $lockTime,
                        'locked_by_tutor_id' => $tutorId,
                        'lock_reason' => $reason ?? 'Bulk locked by tutor',
                        'unlocked_at' => null
                    ]);
                    
                    $this->clearTopicLockCache($topic);
                    $results[$topicId] = true;
                } else {
                    $results[$topicId] = false;
                }
            }
        });
        
        logger()->info('Bulk topic lock operation', [
            'topic_ids' => $topicIds,
            'locked_by_tutor_id' => $tutorId,
            'lock_reason' => $reason,
            'results' => $results,
            'locked_at' => now()
        ]);
        
        return $results;
    }

    /**
     * Bulk unlock multiple topics
     */
    public function bulkUnlockTopics(array $topicIds, int $tutorId): array
    {
        $results = [];
        
        DB::transaction(function () use ($topicIds, $tutorId, &$results) {
            $unlockTime = now();
            
            foreach ($topicIds as $topicId) {
                $topic = Topic::find($topicId);
                if ($topic) {
                    $topic->update([
                        'unlocked_at' => $unlockTime,
                        'lock_reason' => null
                    ]);
                    
                    $this->clearTopicLockCache($topic);
                    $results[$topicId] = true;
                } else {
                    $results[$topicId] = false;
                }
            }
        });
        
        logger()->info('Bulk topic unlock operation', [
            'topic_ids' => $topicIds,
            'unlocked_by_tutor_id' => $tutorId,
            'results' => $results,
            'unlocked_at' => now()
        ]);
        
        return $results;
    }

    /**
     * Sync legacy is_locked field with computed lock status
     */
    public function syncLegacyLockField(Topic $topic): bool
    {
        $lockStatus = $this->getTopicLockStatus($topic);
        $computedLocked = $lockStatus['is_locked'];
        
        if ($topic->is_locked !== $computedLocked) {
            $oldValue = $topic->is_locked;
            $topic->update(['is_locked' => $computedLocked]);
            
            logger()->warning('Legacy is_locked field synced with computed value', [
                'topic_id' => $topic->id,
                'topic_name' => $topic->name,
                'old_is_locked' => $oldValue,
                'new_is_locked' => $computedLocked,
                'locked_at' => $topic->locked_at,
                'unlocked_at' => $topic->unlocked_at,
                'lock_method' => $lockStatus['lock_method'],
                'synced_at' => now()
            ]);
            
            return true;
        }
        
        return false; // No sync needed
    }

    /**
     * Verify data consistency between legacy and computed lock status
     */
    public function verifyLockConsistency(int $courseId = null): array
    {
        $query = Topic::query();
        if ($courseId) {
            $query->where('course_id', $courseId);
        }
        
        $topics = $query->get();
        $inconsistencies = [];
        
        foreach ($topics as $topic) {
            $lockStatus = $this->getTopicLockStatus($topic);
            $computedLocked = $lockStatus['is_locked'];
            
            if ($topic->is_locked !== $computedLocked) {
                $inconsistencies[] = [
                    'topic_id' => $topic->id,
                    'topic_name' => $topic->name,
                    'legacy_is_locked' => $topic->is_locked,
                    'computed_is_locked' => $computedLocked,
                    'locked_at' => $topic->locked_at,
                    'unlocked_at' => $topic->unlocked_at,
                    'lock_method' => $lockStatus['lock_method']
                ];
            }
        }
        
        return [
            'consistent' => empty($inconsistencies),
            'total_checked' => $topics->count(),
            'inconsistencies_count' => count($inconsistencies),
            'inconsistencies' => $inconsistencies,
            'checked_at' => now()
        ];
    }

    /**
     * Get topic lock statistics
     */
    public function getTopicLockStats(int $courseId = null): array
    {
        $cacheKey = "topic_lock_stats" . ($courseId ? "_{$courseId}" : '');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseId) {
            $query = Topic::query();
            if ($courseId) {
                $query->where('course_id', $courseId);
            }
            
            $topics = $query->get();
            $totalTopics = $topics->count();
            $lockedTopics = 0;
            $lockMethods = [];
            
            foreach ($topics as $topic) {
                $lockStatus = $this->getTopicLockStatus($topic);
                if ($lockStatus['is_locked']) {
                    $lockedTopics++;
                    $method = $lockStatus['lock_method'];
                    $lockMethods[$method] = ($lockMethods[$method] ?? 0) + 1;
                }
            }
            
            return [
                'total_topics' => $totalTopics,
                'locked_topics' => $lockedTopics,
                'unlocked_topics' => $totalTopics - $lockedTopics,
                'lock_percentage' => $totalTopics > 0 ? ($lockedTopics / $totalTopics) * 100 : 0,
                'lock_methods' => $lockMethods,
                'computed_at' => now()
            ];
        });
    }

    /**
     * Clear topic lock cache
     */
    public function clearTopicLockCache(Topic $topic): void
    {
        // Clear specific topic cache
        Cache::forget("topic_lock_status_{$topic->id}_v" . ($topic->updated_at?->timestamp ?? 0));
        
        // Clear related caches
        if ($topic->locked_by_tutor_id) {
            Cache::forget("tutor_locked_topics_{$topic->locked_by_tutor_id}");
            Cache::forget("tutor_locked_topics_{$topic->locked_by_tutor_id}_{$topic->course_id}");
        }
        
        Cache::forget("course_locked_topics_{$topic->course_id}");
        Cache::forget("topic_lock_stats");
        Cache::forget("topic_lock_stats_{$topic->course_id}");
    }

    /**
     * Bulk lock status check for multiple topics
     */
    public function getBulkTopicLockStatus(array $topicIds): array
    {
        $results = [];
        $topics = Topic::whereIn('id', $topicIds)->get()->keyBy('id');
        
        foreach ($topicIds as $topicId) {
            if (isset($topics[$topicId])) {
                $results[$topicId] = $this->getTopicLockStatus($topics[$topicId]);
            }
        }
        
        return $results;
    }
}