<?php

namespace App\Observers;

use App\Models\Assignment;
use App\Support\State\AssignmentStatusService;
use Illuminate\Support\Facades\Log;

/**
 * Assignment Status Observer - Dual-Write Synchronization
 * 
 * Handles synchronization between legacy assignment status fields and SSOT:
 * - assignments.status_cached (enum) ↔ AssignmentStatusService
 * - Computed status methods ↔ AssignmentStatusService
 * 
 * @todo Remove legacy status methods after all reads migrated to AssignmentStatusService (target: 2025-09-01)
 * @todo Remove status_cached column after performance testing confirms computed status is fast enough (target: 2025-09-15)
 */
class AssignmentStatusObserver
{
    private AssignmentStatusService $statusService;
    private static array $syncInProgress = [];

    public function __construct(AssignmentStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle assignment updates that affect status
     */
    public function updating(Assignment $assignment): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.assignment_status.enabled')) {
            return;
        }

        $syncKey = "assignment_{$assignment->id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        // Check if status-affecting fields changed
        if ($assignment->isDirty(['deadline', 'duration_minutes', 'created_at'])) {
            self::$syncInProgress[$syncKey] = true;
            
            try {
                $this->handleStatusAffectingChange($assignment);
            } finally {
                unset(self::$syncInProgress[$syncKey]);
            }
        }
    }

    /**
     * Handle assignment creation
     */
    public function created(Assignment $assignment): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.assignment_status.enabled')) {
            return;
        }

        $this->syncStatusCache($assignment);
    }

    /**
     * Handle status-affecting field changes
     */
    private function handleStatusAffectingChange(Assignment $assignment): void
    {
        $original = $assignment->getOriginal();
        
        Log::info('SSOT Assignment Status Fields Changed', [
            'type' => 'STATUS_AFFECTING_CHANGE',
            'model' => 'Assignment',
            'assignment_id' => $assignment->id,
            'assignment_title' => $assignment->title,
            'changed_fields' => [
                'deadline' => [
                    'old' => $original['deadline'] ?? null,
                    'new' => $assignment->deadline?->toDateTimeString()
                ],
                'duration_minutes' => [
                    'old' => $original['duration_minutes'] ?? null,
                    'new' => $assignment->duration_minutes
                ],
                'created_at' => [
                    'old' => $original['created_at'] ?? null,
                    'new' => $assignment->created_at?->toDateTimeString()
                ]
            ],
            'sync_action' => 'UPDATE_STATUS_CACHE',
            'timestamp' => now()
        ]);

        // Update cached status
        $this->syncStatusCache($assignment);
    }

    /**
     * Sync computed status to cached field
     */
    private function syncStatusCache(Assignment $assignment): void
    {
        if (!in_array('ssot_to_legacy', config('ssot.observers.assignment_status.sync_directions'))) {
            return;
        }

        $statusData = $this->statusService->getAssignmentStatus($assignment);
        $computedStatus = $statusData['status'];
        
        // Check for conflicts with existing cached status
        if ($assignment->status_cached && $assignment->status_cached !== $computedStatus) {
            Log::warning('SSOT Assignment Status Cache Conflict', [
                'type' => 'STATUS_CACHE_CONFLICT',
                'assignment_id' => $assignment->id,
                'assignment_title' => $assignment->title,
                'cached_status' => $assignment->status_cached,
                'computed_status' => $computedStatus,
                'status_reason' => $statusData['reason'],
                'conflict_resolution' => 'COMPUTED_TAKES_PRECEDENCE',
                'detected_at' => now()
            ]);
        }

        // Update cached status without triggering observer recursion
        $assignment->timestamps = false;
        $assignment->status_cached = $computedStatus;
        $assignment->status_cached_at = now();
        $assignment->save(['timestamps' => false]);
        $assignment->timestamps = true;

        Log::info('SSOT Assignment Status Cache Updated', [
            'type' => 'SSOT_TO_LEGACY_SYNC',
            'model' => 'Assignment',
            'assignment_id' => $assignment->id,
            'assignment_title' => $assignment->title,
            'computed_status' => $computedStatus,
            'status_reason' => $statusData['reason'],
            'time_remaining' => $statusData['time_remaining'] ?? null,
            'cached_at' => now()
        ]);

        // Clear service cache to prevent stale data
        $this->statusService->clearStatusCache($assignment);
    }

    /**
     * Handle legacy status method calls (for deprecation tracking)
     */
    public function trackLegacyStatusCall(Assignment $assignment, string $method, $result): void
    {
        if (!config('ssot.legacy_write_warnings')) {
            return;
        }

        Log::warning('SSOT Legacy Assignment Status Method Called', [
            'type' => 'LEGACY_METHOD_CALL',
            'model' => 'Assignment',
            'assignment_id' => $assignment->id,
            'legacy_method' => $method,
            'legacy_result' => $result,
            'ssot_status' => $this->statusService->getStatus($assignment),
            'deprecation_warning' => "Method {$method} is deprecated. Use AssignmentStatusService instead.",
            'called_at' => now(),
            'stack_trace' => config('ssot.logging.include_stack_trace') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) : null
        ]);
    }

    /**
     * Verify status consistency
     */
    public function verifyConsistency(Assignment $assignment): array
    {
        $ssotStatus = $this->statusService->getAssignmentStatus($assignment);
        $cachedStatus = $assignment->status_cached;
        
        $inconsistencies = [];
        
        if ($cachedStatus && $cachedStatus !== $ssotStatus['status']) {
            $inconsistencies[] = [
                'field' => 'status_cached',
                'cached_value' => $cachedStatus,
                'computed_value' => $ssotStatus['status'],
                'conflict_type' => 'status_mismatch',
                'status_reason' => $ssotStatus['reason']
            ];
        }

        return [
            'consistent' => empty($inconsistencies),
            'inconsistencies' => $inconsistencies,
            'ssot_status' => $ssotStatus,
            'cached_status' => $cachedStatus,
            'cached_at' => $assignment->status_cached_at,
            'checked_at' => now()
        ];
    }

    /**
     * Bulk sync status cache for multiple assignments
     */
    public function bulkSyncStatusCache(array $assignmentIds): array
    {
        $results = [];
        
        foreach ($assignmentIds as $assignmentId) {
            $assignment = Assignment::find($assignmentId);
            if ($assignment) {
                try {
                    $this->syncStatusCache($assignment);
                    $results[$assignmentId] = true;
                } catch (\Exception $e) {
                    $results[$assignmentId] = false;
                    
                    Log::error('SSOT Assignment Status Cache Sync Failed', [
                        'type' => 'BULK_SYNC_ERROR',
                        'assignment_id' => $assignmentId,
                        'error' => $e->getMessage(),
                        'failed_at' => now()
                    ]);
                }
            } else {
                $results[$assignmentId] = false;
            }
        }

        Log::info('SSOT Assignment Status Bulk Sync Completed', [
            'type' => 'BULK_SYNC_COMPLETED',
            'total_assignments' => count($assignmentIds),
            'successful_syncs' => count(array_filter($results)),
            'failed_syncs' => count(array_filter($results, fn($r) => !$r)),
            'results' => $results,
            'completed_at' => now()
        ]);

        return $results;
    }
}