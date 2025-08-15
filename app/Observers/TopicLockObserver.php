<?php

namespace App\Observers;

use App\Models\Topic;
use App\Support\State\TopicLockService;
use Illuminate\Support\Facades\Log;

/**
 * Topic Lock Observer - Dual-Write Synchronization
 * 
 * Handles synchronization between legacy lock fields and SSOT:
 * - topics.is_locked (boolean) ↔ TopicLockService
 * - topics.locked_at/unlocked_at (datetime) → TopicLockService computation
 * 
 * @todo Remove topics.is_locked after all reads use TopicLockService (target: 2025-09-10)
 * @todo Update all topic access queries to use timestamp-based logic (target: 2025-09-05)
 */
class TopicLockObserver
{
    private TopicLockService $lockService;
    private static array $syncInProgress = [];

    public function __construct(TopicLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    /**
     * Handle topic updates
     */
    public function updating(Topic $topic): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.topic_lock.enabled')) {
            return;
        }

        $syncKey = "topic_{$topic->id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        // Check for lock-affecting changes
        if ($topic->isDirty(['is_locked', 'locked_at', 'unlocked_at', 'locked_by_tutor_id'])) {
            self::$syncInProgress[$syncKey] = true;
            
            try {
                $this->handleLockChange($topic);
            } finally {
                unset(self::$syncInProgress[$syncKey]);
            }
        }
    }

    /**
     * Handle topic creation
     */
    public function created(Topic $topic): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.topic_lock.enabled')) {
            return;
        }

        $this->syncLockFields($topic);
    }

    /**
     * Handle lock-related field changes
     */
    private function handleLockChange(Topic $topic): void
    {
        $original = $topic->getOriginal();
        
        // Get SSOT computed lock status
        $lockStatus = $this->lockService->getTopicLockStatus($topic);
        $computedLocked = $lockStatus['is_locked'];
        
        // Check for conflicts between legacy is_locked field and computed value
        if ($topic->isDirty('is_locked') && $topic->is_locked !== $computedLocked) {
            Log::warning('SSOT Topic Lock Conflict', [
                'type' => 'LEGACY_WRITE_CONFLICT',
                'model' => 'Topic',
                'topic_id' => $topic->id,
                'topic_name' => $topic->name,
                'course_id' => $topic->course_id,
                'legacy_is_locked' => $topic->is_locked,
                'computed_is_locked' => $computedLocked,
                'locked_at' => $topic->locked_at,
                'unlocked_at' => $topic->unlocked_at,
                'locked_by_tutor_id' => $topic->locked_by_tutor_id,
                'lock_method' => $lockStatus['lock_method'],
                'original_is_locked' => $original['is_locked'] ?? null,
                'original_locked_at' => $original['locked_at'] ?? null,
                'original_unlocked_at' => $original['unlocked_at'] ?? null,
                'conflict_resolution' => 'COMPUTED_TAKES_PRECEDENCE',
                'detected_at' => now()
            ]);
        }

        // Check for timestamp changes
        $timestampChanges = [];
        if ($topic->isDirty('locked_at')) {
            $timestampChanges['locked_at'] = [
                'old' => $original['locked_at'] ?? null,
                'new' => $topic->locked_at?->toDateTimeString()
            ];
        }
        if ($topic->isDirty('unlocked_at')) {
            $timestampChanges['unlocked_at'] = [
                'old' => $original['unlocked_at'] ?? null,
                'new' => $topic->unlocked_at?->toDateTimeString()
            ];
        }

        Log::info('SSOT Topic Lock Change', [
            'type' => 'TOPIC_LOCK_CHANGE',
            'model' => 'Topic',
            'topic_id' => $topic->id,
            'topic_name' => $topic->name,
            'course_id' => $topic->course_id,
            'changed_fields' => array_merge([
                'is_locked' => [
                    'old' => $original['is_locked'] ?? null,
                    'new' => $topic->is_locked
                ],
                'locked_by_tutor_id' => [
                    'old' => $original['locked_by_tutor_id'] ?? null,
                    'new' => $topic->locked_by_tutor_id
                ]
            ], $timestampChanges),
            'computed_lock_status' => $lockStatus,
            'sync_action' => 'VALIDATE_LOCK_CONSISTENCY',
            'timestamp' => now()
        ]);

        // Sync lock fields
        $this->syncLockFields($topic);
    }

    /**
     * Sync lock fields between legacy and SSOT
     */
    private function syncLockFields(Topic $topic): void
    {
        if (!in_array('ssot_to_legacy', config('ssot.observers.topic_lock.sync_directions'))) {
            return;
        }

        // Get computed lock status
        $lockStatus = $this->lockService->getTopicLockStatus($topic);
        $computedLocked = $lockStatus['is_locked'];
        
        // Update legacy is_locked field if different
        if ($topic->is_locked !== $computedLocked) {
            $oldLocked = $topic->is_locked;
            
            // Update without triggering observer recursion
            $topic->timestamps = false;
            $topic->is_locked = $computedLocked;
            $topic->save(['timestamps' => false]);
            $topic->timestamps = true;

            Log::info('SSOT Topic Lock Field Synced', [
                'type' => 'SSOT_TO_LEGACY_SYNC',
                'model' => 'Topic',
                'topic_id' => $topic->id,
                'topic_name' => $topic->name,
                'course_id' => $topic->course_id,
                'old_is_locked' => $oldLocked,
                'new_is_locked' => $computedLocked,
                'locked_at' => $topic->locked_at,
                'unlocked_at' => $topic->unlocked_at,
                'locked_by_tutor_id' => $topic->locked_by_tutor_id,
                'lock_method' => $lockStatus['lock_method'],
                'lock_reason' => $lockStatus['lock_reason'],
                'synced_at' => now()
            ]);
        }
    }

    /**
     * Handle lock action through SSOT service
     */
    public function handleSSOTLockAction(Topic $topic, int $tutorId, string $action, ?string $reason = null): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.topic_lock.enabled')) {
            return;
        }

        $syncKey = "ssot_lock_{$topic->id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        self::$syncInProgress[$syncKey] = true;
        
        try {
            $success = false;
            
            switch ($action) {
                case 'lock':
                    $success = $this->lockService->lockTopic($topic, $tutorId, $reason);
                    break;
                case 'unlock':
                    $success = $this->lockService->unlockTopic($topic, $tutorId);
                    break;
                case 'toggle':
                    $success = $this->lockService->toggleTopicLock($topic, $tutorId, $reason);
                    break;
            }
            
            Log::info('SSOT Topic Lock Action via Service', [
                'type' => 'SSOT_WRITE',
                'model' => 'Topic',
                'topic_id' => $topic->id,
                'topic_name' => $topic->name,
                'course_id' => $topic->course_id,
                'action' => $action,
                'tutor_id' => $tutorId,
                'reason' => $reason,
                'success' => $success,
                'new_lock_status' => $this->lockService->getTopicLockStatus($topic),
                'performed_at' => now()
            ]);
        } finally {
            unset(self::$syncInProgress[$syncKey]);
        }
    }

    /**
     * Handle legacy lock method calls (for deprecation tracking)
     */
    public function trackLegacyLockCall(Topic $topic, string $method, $result): void
    {
        if (!config('ssot.legacy_write_warnings')) {
            return;
        }

        $ssotResult = $this->lockService->isTopicLocked($topic);

        Log::warning('SSOT Legacy Topic Lock Method Called', [
            'type' => 'LEGACY_METHOD_CALL',
            'model' => 'Topic',
            'topic_id' => $topic->id,
            'topic_name' => $topic->name,
            'course_id' => $topic->course_id,
            'legacy_method' => $method,
            'legacy_result' => $result,
            'ssot_result' => $ssotResult,
            'results_match' => $result === $ssotResult,
            'deprecation_warning' => "Method {$method} is deprecated. Use TopicLockService instead.",
            'called_at' => now(),
            'stack_trace' => config('ssot.logging.include_stack_trace') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) : null
        ]);
    }

    /**
     * Verify lock consistency
     */
    public function verifyConsistency(Topic $topic): array
    {
        return $this->lockService->verifyLockConsistency($topic->course_id);
    }

    /**
     * Bulk sync lock fields for multiple topics
     */
    public function bulkSyncLockFields(array $topicIds): array
    {
        $results = [];
        
        foreach ($topicIds as $topicId) {
            $topic = Topic::find($topicId);
            if ($topic) {
                try {
                    $synced = $this->lockService->syncLegacyLockField($topic);
                    $results[$topicId] = $synced;
                } catch (\Exception $e) {
                    $results[$topicId] = false;
                    
                    Log::error('SSOT Topic Lock Sync Failed', [
                        'type' => 'BULK_SYNC_ERROR',
                        'topic_id' => $topicId,
                        'error' => $e->getMessage(),
                        'failed_at' => now()
                    ]);
                }
            } else {
                $results[$topicId] = false;
            }
        }

        Log::info('SSOT Topic Lock Bulk Sync Completed', [
            'type' => 'BULK_SYNC_COMPLETED',
            'total_topics' => count($topicIds),
            'successful_syncs' => count(array_filter($results)),
            'failed_syncs' => count(array_filter($results, fn($r) => !$r)),
            'results' => $results,
            'completed_at' => now()
        ]);

        return $results;
    }

    /**
     * Handle bulk lock operations
     */
    public function handleBulkLockOperation(array $topicIds, int $tutorId, string $action, ?string $reason = null): array
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.topic_lock.enabled')) {
            return [];
        }

        $results = [];
        
        switch ($action) {
            case 'lock':
                $results = $this->lockService->bulkLockTopics($topicIds, $tutorId, $reason);
                break;
            case 'unlock':
                $results = $this->lockService->bulkUnlockTopics($topicIds, $tutorId);
                break;
        }

        Log::info('SSOT Topic Bulk Lock Operation via Service', [
            'type' => 'SSOT_BULK_WRITE',
            'model' => 'Topic',
            'action' => $action,
            'topic_ids' => $topicIds,
            'tutor_id' => $tutorId,
            'reason' => $reason,
            'results' => $results,
            'successful_operations' => count(array_filter($results)),
            'failed_operations' => count(array_filter($results, fn($r) => !$r)),
            'performed_at' => now()
        ]);

        return $results;
    }

    /**
     * Handle topic access check
     */
    public function handleAccessCheck(Topic $topic, int $userId, array $accessResult): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.topic_lock.enabled')) {
            return;
        }

        Log::info('SSOT Topic Access Check', [
            'type' => 'ACCESS_CHECK',
            'model' => 'Topic',
            'topic_id' => $topic->id,
            'topic_name' => $topic->name,
            'course_id' => $topic->course_id,
            'user_id' => $userId,
            'can_access' => $accessResult['can_access'],
            'access_reason' => $accessResult['access_reason'],
            'lock_status' => $accessResult['lock_status'],
            'checked_at' => now()
        ]);
    }
}