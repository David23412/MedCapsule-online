<?php

namespace App\Observers;

use App\Support\State\VideoProgressService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Video Progress Observer - Dual-Write Synchronization
 * 
 * Handles synchronization between legacy completion fields and SSOT:
 * - video_progress.completed (boolean) ↔ VideoProgressService
 * - video_progress.progress_percentage (decimal) → VideoProgressService computation
 * 
 * @todo Remove video_progress.completed after all reads use VideoProgressService (target: 2025-09-01)
 * @todo Update all video completion queries to use percentage-based logic (target: 2025-08-25)
 */
class VideoProgressObserver
{
    private VideoProgressService $progressService;
    private static array $syncInProgress = [];

    public function __construct(VideoProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Handle video progress updates
     */
    public function updatingVideoProgress($videoProgress): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.video_progress.enabled')) {
            return;
        }

        $syncKey = "video_progress_{$videoProgress->user_id}_{$videoProgress->video_id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        // Check for completion-affecting changes
        if ($this->hasCompletionChanges($videoProgress)) {
            self::$syncInProgress[$syncKey] = true;
            
            try {
                $this->handleCompletionChange($videoProgress);
            } finally {
                unset(self::$syncInProgress[$syncKey]);
            }
        }
    }

    /**
     * Handle video progress creation
     */
    public function createdVideoProgress($videoProgress): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.video_progress.enabled')) {
            return;
        }

        $this->syncCompletionFields($videoProgress);
    }

    /**
     * Check if completion-affecting fields changed
     */
    private function hasCompletionChanges($videoProgress): bool
    {
        return $videoProgress->isDirty(['progress_percentage', 'completed', 'watched_duration_seconds']);
    }

    /**
     * Handle completion-related field changes
     */
    private function handleCompletionChange($videoProgress): void
    {
        $original = $videoProgress->getOriginal();
        $userId = $videoProgress->user_id;
        $videoId = $videoProgress->video_id;
        
        // Get SSOT computed completion status
        $progressData = $this->progressService->getVideoProgress($userId, $videoId);
        $computedCompleted = $progressData['is_completed'];
        
        // Check for conflicts between legacy completed field and computed value
        if ($videoProgress->isDirty('completed') && $videoProgress->completed !== $computedCompleted) {
            Log::warning('SSOT Video Progress Completion Conflict', [
                'type' => 'LEGACY_WRITE_CONFLICT',
                'model' => 'video_progress',
                'user_id' => $userId,
                'video_id' => $videoId,
                'legacy_completed' => $videoProgress->completed,
                'computed_completed' => $computedCompleted,
                'progress_percentage' => $videoProgress->progress_percentage,
                'completion_threshold' => VideoProgressService::COMPLETION_THRESHOLD,
                'original_completed' => $original['completed'] ?? null,
                'original_percentage' => $original['progress_percentage'] ?? null,
                'conflict_resolution' => 'COMPUTED_TAKES_PRECEDENCE',
                'detected_at' => now()
            ]);
        }

        // Check for progress percentage changes
        if ($videoProgress->isDirty('progress_percentage')) {
            $oldPercentage = $original['progress_percentage'] ?? 0;
            $newPercentage = $videoProgress->progress_percentage;
            
            Log::info('SSOT Video Progress Percentage Changed', [
                'type' => 'PROGRESS_PERCENTAGE_CHANGE',
                'model' => 'video_progress',
                'user_id' => $userId,
                'video_id' => $videoId,
                'old_percentage' => $oldPercentage,
                'new_percentage' => $newPercentage,
                'percentage_delta' => $newPercentage - $oldPercentage,
                'old_completed' => $original['completed'] ?? null,
                'new_computed_completed' => $computedCompleted,
                'completion_threshold' => VideoProgressService::COMPLETION_THRESHOLD,
                'sync_action' => 'UPDATE_COMPLETION_STATUS',
                'timestamp' => now()
            ]);
        }

        Log::info('SSOT Video Progress Change', [
            'type' => 'VIDEO_PROGRESS_CHANGE',
            'model' => 'video_progress',
            'user_id' => $userId,
            'video_id' => $videoId,
            'changed_fields' => [
                'progress_percentage' => [
                    'old' => $original['progress_percentage'] ?? null,
                    'new' => $videoProgress->progress_percentage
                ],
                'completed' => [
                    'old' => $original['completed'] ?? null,
                    'new' => $videoProgress->completed
                ],
                'watched_duration_seconds' => [
                    'old' => $original['watched_duration_seconds'] ?? null,
                    'new' => $videoProgress->watched_duration_seconds
                ]
            ],
            'computed_completion' => $progressData,
            'sync_action' => 'VALIDATE_COMPLETION_CONSISTENCY',
            'timestamp' => now()
        ]);

        // Sync completion fields
        $this->syncCompletionFields($videoProgress);
    }

    /**
     * Sync completion fields between legacy and SSOT
     */
    private function syncCompletionFields($videoProgress): void
    {
        if (!in_array('ssot_to_legacy', config('ssot.observers.video_progress.sync_directions'))) {
            return;
        }

        $userId = $videoProgress->user_id;
        $videoId = $videoProgress->video_id;
        
        // Get computed completion status
        $progressData = $this->progressService->getVideoProgress($userId, $videoId);
        $computedCompleted = $progressData['is_completed'];
        
        // Update legacy completed field if different
        if ($videoProgress->completed !== $computedCompleted) {
            $oldCompleted = $videoProgress->completed;
            
            // Update without triggering observer recursion
            DB::table('video_progress')
                ->where('user_id', $userId)
                ->where('video_id', $videoId)
                ->update([
                    'completed' => $computedCompleted,
                    'updated_at' => now()
                ]);

            Log::info('SSOT Video Progress Completion Field Synced', [
                'type' => 'SSOT_TO_LEGACY_SYNC',
                'model' => 'video_progress',
                'user_id' => $userId,
                'video_id' => $videoId,
                'old_completed' => $oldCompleted,
                'new_completed' => $computedCompleted,
                'progress_percentage' => $videoProgress->progress_percentage,
                'completion_threshold' => VideoProgressService::COMPLETION_THRESHOLD,
                'completion_method' => $progressData['completion_method'],
                'synced_at' => now()
            ]);
        }
    }

    /**
     * Handle progress update through SSOT service
     */
    public function handleSSOTProgressUpdate(int $userId, int $videoId, float $progressPercentage, ?int $watchedDurationSeconds = null): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.video_progress.enabled')) {
            return;
        }

        $syncKey = "ssot_update_{$userId}_{$videoId}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        self::$syncInProgress[$syncKey] = true;
        
        try {
            $progressData = $this->progressService->updateVideoProgress($userId, $videoId, $progressPercentage, $watchedDurationSeconds);
            
            Log::info('SSOT Video Progress Updated via Service', [
                'type' => 'SSOT_WRITE',
                'model' => 'video_progress',
                'user_id' => $userId,
                'video_id' => $videoId,
                'progress_percentage' => $progressPercentage,
                'watched_duration_seconds' => $watchedDurationSeconds,
                'is_completed' => $progressData['is_completed'],
                'completion_method' => $progressData['completion_method'],
                'updated_at' => now()
            ]);
        } finally {
            unset(self::$syncInProgress[$syncKey]);
        }
    }

    /**
     * Handle legacy completion method calls (for deprecation tracking)
     */
    public function trackLegacyCompletionCall(int $userId, int $videoId, string $method, $result): void
    {
        if (!config('ssot.legacy_write_warnings')) {
            return;
        }

        $ssotResult = $this->progressService->isVideoCompleted($userId, $videoId);

        Log::warning('SSOT Legacy Video Completion Method Called', [
            'type' => 'LEGACY_METHOD_CALL',
            'model' => 'video_progress',
            'user_id' => $userId,
            'video_id' => $videoId,
            'legacy_method' => $method,
            'legacy_result' => $result,
            'ssot_result' => $ssotResult,
            'results_match' => $result === $ssotResult,
            'deprecation_warning' => "Method {$method} is deprecated. Use VideoProgressService instead.",
            'called_at' => now(),
            'stack_trace' => config('ssot.logging.include_stack_trace') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) : null
        ]);
    }

    /**
     * Verify completion consistency
     */
    public function verifyConsistency(int $userId, ?int $videoId = null): array
    {
        if ($videoId) {
            return $this->progressService->verifyCompletionConsistency($userId);
        } else {
            // Verify specific video
            $progressData = $this->progressService->getVideoProgress($userId, $videoId);
            $legacyRecord = DB::table('video_progress')
                ->where('user_id', $userId)
                ->where('video_id', $videoId)
                ->first();

            $inconsistencies = [];
            
            if ($legacyRecord) {
                $legacyCompleted = (bool) $legacyRecord->completed;
                $computedCompleted = $progressData['is_completed'];
                
                if ($legacyCompleted !== $computedCompleted) {
                    $inconsistencies[] = [
                        'video_id' => $videoId,
                        'legacy_completed' => $legacyCompleted,
                        'computed_completed' => $computedCompleted,
                        'progress_percentage' => (float) $legacyRecord->progress_percentage,
                        'threshold' => VideoProgressService::COMPLETION_THRESHOLD
                    ];
                }
            }

            return [
                'consistent' => empty($inconsistencies),
                'total_checked' => 1,
                'inconsistencies_count' => count($inconsistencies),
                'inconsistencies' => $inconsistencies,
                'checked_at' => now()
            ];
        }
    }

    /**
     * Bulk sync completion fields for multiple video progress records
     */
    public function bulkSyncCompletionFields(array $progressRecords): array
    {
        $results = [];
        
        foreach ($progressRecords as $record) {
            $userId = $record['user_id'];
            $videoId = $record['video_id'];
            $key = "{$userId}_{$videoId}";
            
            try {
                $synced = $this->progressService->syncLegacyCompletedField($userId, $videoId);
                $results[$key] = $synced;
            } catch (\Exception $e) {
                $results[$key] = false;
                
                Log::error('SSOT Video Progress Completion Sync Failed', [
                    'type' => 'BULK_SYNC_ERROR',
                    'user_id' => $userId,
                    'video_id' => $videoId,
                    'error' => $e->getMessage(),
                    'failed_at' => now()
                ]);
            }
        }

        Log::info('SSOT Video Progress Completion Bulk Sync Completed', [
            'type' => 'BULK_SYNC_COMPLETED',
            'total_records' => count($progressRecords),
            'successful_syncs' => count(array_filter($results)),
            'failed_syncs' => count(array_filter($results, fn($r) => !$r)),
            'results' => $results,
            'completed_at' => now()
        ]);

        return $results;
    }

    /**
     * Handle video completion milestone
     */
    public function handleCompletionMilestone(int $userId, int $videoId): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.video_progress.enabled')) {
            return;
        }

        $progressData = $this->progressService->getVideoProgress($userId, $videoId);
        
        if ($progressData['is_completed']) {
            Log::info('SSOT Video Completion Milestone Reached', [
                'type' => 'COMPLETION_MILESTONE',
                'model' => 'video_progress',
                'user_id' => $userId,
                'video_id' => $videoId,
                'progress_percentage' => $progressData['progress_percentage'],
                'watched_duration' => $progressData['watched_duration_seconds'],
                'completion_method' => $progressData['completion_method'],
                'milestone_reached_at' => now()
            ]);

            // Clear related caches
            $this->progressService->clearVideoProgressCache($userId, $videoId);
        }
    }
}