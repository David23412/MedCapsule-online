<?php

namespace App\Observers;

use App\Models\Mistake;
use App\Support\State\MistakeMasteryService;
use Illuminate\Support\Facades\Log;

/**
 * Mistake Mastery Observer - Dual-Write Synchronization
 * 
 * Handles synchronization between legacy mastery fields and SSOT:
 * - mistakes.mastered (boolean) ↔ MistakeMasteryService
 * - mistakes.correct_streak (integer) → MistakeMasteryService computation
 * 
 * @todo Remove mistakes.mastered after all reads use MistakeMasteryService (target: 2025-09-05)
 * @todo Update all mistake queries to use streak-based logic (target: 2025-09-01)
 */
class MistakeMasteryObserver
{
    private MistakeMasteryService $masteryService;
    private static array $syncInProgress = [];

    public function __construct(MistakeMasteryService $masteryService)
    {
        $this->masteryService = $masteryService;
    }

    /**
     * Handle mistake updates
     */
    public function updating(Mistake $mistake): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.mistake_mastery.enabled')) {
            return;
        }

        $syncKey = "mistake_{$mistake->id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        // Check for mastery-affecting changes
        if ($mistake->isDirty(['correct_streak', 'mastered'])) {
            self::$syncInProgress[$syncKey] = true;
            
            try {
                $this->handleMasteryChange($mistake);
            } finally {
                unset(self::$syncInProgress[$syncKey]);
            }
        }
    }

    /**
     * Handle mistake creation
     */
    public function created(Mistake $mistake): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.mistake_mastery.enabled')) {
            return;
        }

        $this->syncMasteryFields($mistake);
    }

    /**
     * Handle mastery-related field changes
     */
    private function handleMasteryChange(Mistake $mistake): void
    {
        $original = $mistake->getOriginal();
        
        // Get SSOT computed mastery status
        $masteryStatus = $this->masteryService->getMasteryStatus($mistake, 'quiz');
        $computedMastered = $masteryStatus['is_mastered'];
        
        // Check for conflicts between legacy mastered field and computed value
        if ($mistake->isDirty('mastered') && $mistake->mastered !== $computedMastered) {
            Log::warning('SSOT Mistake Mastery Conflict', [
                'type' => 'LEGACY_WRITE_CONFLICT',
                'model' => 'Mistake',
                'mistake_id' => $mistake->id,
                'user_id' => $mistake->user_id,
                'question_id' => $mistake->question_id,
                'legacy_mastered' => $mistake->mastered,
                'computed_mastered' => $computedMastered,
                'current_streak' => $mistake->correct_streak,
                'required_streak' => $masteryStatus['required_streak'],
                'original_mastered' => $original['mastered'] ?? null,
                'original_streak' => $original['correct_streak'] ?? null,
                'conflict_resolution' => 'COMPUTED_TAKES_PRECEDENCE',
                'detected_at' => now()
            ]);
        }

        // Check for streak changes
        if ($mistake->isDirty('correct_streak')) {
            $oldStreak = $original['correct_streak'] ?? 0;
            $newStreak = $mistake->correct_streak;
            
            Log::info('SSOT Mistake Streak Changed', [
                'type' => 'STREAK_CHANGE',
                'model' => 'Mistake',
                'mistake_id' => $mistake->id,
                'user_id' => $mistake->user_id,
                'question_id' => $mistake->question_id,
                'old_streak' => $oldStreak,
                'new_streak' => $newStreak,
                'streak_delta' => $newStreak - $oldStreak,
                'old_mastered' => $original['mastered'] ?? null,
                'new_computed_mastered' => $computedMastered,
                'mastery_threshold' => $masteryStatus['required_streak'],
                'sync_action' => 'UPDATE_MASTERY_STATUS',
                'timestamp' => now()
            ]);
        }

        Log::info('SSOT Mistake Mastery Change', [
            'type' => 'MASTERY_CHANGE',
            'model' => 'Mistake',
            'mistake_id' => $mistake->id,
            'user_id' => $mistake->user_id,
            'question_id' => $mistake->question_id,
            'changed_fields' => [
                'correct_streak' => [
                    'old' => $original['correct_streak'] ?? null,
                    'new' => $mistake->correct_streak
                ],
                'mastered' => [
                    'old' => $original['mastered'] ?? null,
                    'new' => $mistake->mastered
                ]
            ],
            'computed_mastery' => $masteryStatus,
            'sync_action' => 'VALIDATE_MASTERY_CONSISTENCY',
            'timestamp' => now()
        ]);

        // Sync mastery fields
        $this->syncMasteryFields($mistake);
    }

    /**
     * Sync mastery fields between legacy and SSOT
     */
    private function syncMasteryFields(Mistake $mistake): void
    {
        if (!in_array('ssot_to_legacy', config('ssot.observers.mistake_mastery.sync_directions'))) {
            return;
        }

        // Get computed mastery status for quiz mode (default)
        $masteryStatus = $this->masteryService->getMasteryStatus($mistake, 'quiz');
        $computedMastered = $masteryStatus['is_mastered'];
        
        // Update legacy mastered field if different
        if ($mistake->mastered !== $computedMastered) {
            $oldMastered = $mistake->mastered;
            
            // Update without triggering observer recursion
            $mistake->timestamps = false;
            $mistake->mastered = $computedMastered;
            $mistake->save(['timestamps' => false]);
            $mistake->timestamps = true;

            Log::info('SSOT Mistake Mastery Field Synced', [
                'type' => 'SSOT_TO_LEGACY_SYNC',
                'model' => 'Mistake',
                'mistake_id' => $mistake->id,
                'user_id' => $mistake->user_id,
                'question_id' => $mistake->question_id,
                'old_mastered' => $oldMastered,
                'new_mastered' => $computedMastered,
                'current_streak' => $mistake->correct_streak,
                'required_streak' => $masteryStatus['required_streak'],
                'mastery_mode' => 'quiz',
                'synced_at' => now()
            ]);
        }
    }

    /**
     * Handle answer attempt tracking
     */
    public function trackAnswerAttempt(Mistake $mistake, bool $isCorrect, string $mode = 'quiz'): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.mistake_mastery.enabled')) {
            return;
        }

        $syncKey = "mistake_attempt_{$mistake->id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        self::$syncInProgress[$syncKey] = true;
        
        try {
            $masteryStatus = $this->masteryService->updateMistakeTracking($mistake, $isCorrect, $mode);
            
            Log::info('SSOT Mistake Answer Attempt Tracked', [
                'type' => 'ANSWER_ATTEMPT_TRACKED',
                'model' => 'Mistake',
                'mistake_id' => $mistake->id,
                'user_id' => $mistake->user_id,
                'question_id' => $mistake->question_id,
                'is_correct' => $isCorrect,
                'mode' => $mode,
                'new_streak' => $mistake->correct_streak,
                'mastery_status' => $masteryStatus,
                'tracked_at' => now()
            ]);
        } finally {
            unset(self::$syncInProgress[$syncKey]);
        }
    }

    /**
     * Handle legacy mastery method calls (for deprecation tracking)
     */
    public function trackLegacyMasteryCall(Mistake $mistake, string $method, $result, string $mode = 'quiz'): void
    {
        if (!config('ssot.legacy_write_warnings')) {
            return;
        }

        $ssotResult = $this->masteryService->isMastered($mistake);

        Log::warning('SSOT Legacy Mistake Mastery Method Called', [
            'type' => 'LEGACY_METHOD_CALL',
            'model' => 'Mistake',
            'mistake_id' => $mistake->id,
            'user_id' => $mistake->user_id,
            'question_id' => $mistake->question_id,
            'legacy_method' => $method,
            'legacy_result' => $result,
            'ssot_result' => $ssotResult,
            'mode' => $mode,
            'results_match' => $result === $ssotResult,
            'deprecation_warning' => "Method {$method} is deprecated. Use MistakeMasteryService instead.",
            'called_at' => now(),
            'stack_trace' => config('ssot.logging.include_stack_trace') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) : null
        ]);
    }

    /**
     * Verify mastery consistency
     */
    public function verifyConsistency(Mistake $mistake, string $mode = 'quiz'): array
    {
        return $this->masteryService->verifyMasteryConsistency($mistake->user_id, $mode);
    }

    /**
     * Bulk sync mastery fields for multiple mistakes
     */
    public function bulkSyncMasteryFields(array $mistakeIds, string $mode = 'quiz'): array
    {
        $results = [];
        
        foreach ($mistakeIds as $mistakeId) {
            $mistake = Mistake::find($mistakeId);
            if ($mistake) {
                try {
                    $synced = $this->masteryService->syncLegacyMasteredField($mistake, $mode);
                    $results[$mistakeId] = $synced;
                } catch (\Exception $e) {
                    $results[$mistakeId] = false;
                    
                    Log::error('SSOT Mistake Mastery Sync Failed', [
                        'type' => 'BULK_SYNC_ERROR',
                        'mistake_id' => $mistakeId,
                        'mode' => $mode,
                        'error' => $e->getMessage(),
                        'failed_at' => now()
                    ]);
                }
            } else {
                $results[$mistakeId] = false;
            }
        }

        Log::info('SSOT Mistake Mastery Bulk Sync Completed', [
            'type' => 'BULK_SYNC_COMPLETED',
            'mode' => $mode,
            'total_mistakes' => count($mistakeIds),
            'successful_syncs' => count(array_filter($results)),
            'failed_syncs' => count(array_filter($results, fn($r) => !$r)),
            'results' => $results,
            'completed_at' => now()
        ]);

        return $results;
    }

    /**
     * Handle mistake skipping
     */
    public function handleSkipAction(Mistake $mistake): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.mistake_mastery.enabled')) {
            return;
        }

        Log::info('SSOT Mistake Skip Action', [
            'type' => 'MISTAKE_SKIPPED',
            'model' => 'Mistake',
            'mistake_id' => $mistake->id,
            'user_id' => $mistake->user_id,
            'question_id' => $mistake->question_id,
            'current_streak' => $mistake->correct_streak,
            'was_mastered' => $mistake->mastered,
            'skipped_at' => now()
        ]);

        // Update mastery status after skipping
        $this->syncMasteryFields($mistake);
    }
}