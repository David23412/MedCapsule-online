<?php

namespace App\Observers;

use App\Models\User;
use App\Models\QuizAttempt;
use App\Support\State\UserProgressService;
use Illuminate\Support\Facades\Log;

/**
 * User Progress Observer - Dual-Write Synchronization
 * 
 * Handles synchronization between legacy progress fields and SSOT:
 * - users.correct_answers_count (integer) ↔ UserProgressService
 * - quiz_attempts.correct_answers (integer) ↔ quiz_attempts.score
 * - users.xp, users.level, users.study_streak_days ↔ UserProgressService
 * 
 * @todo Remove quiz_attempts.correct_answers after all reads use score field (target: 2025-08-30)
 * @todo Remove cached user fields after event-driven sync is stable (target: 2025-09-20)
 */
class UserProgressObserver
{
    private UserProgressService $progressService;
    private static array $syncInProgress = [];

    public function __construct(UserProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Handle quiz attempt creation
     */
    public function created(QuizAttempt $quizAttempt): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.user_progress.enabled')) {
            return;
        }

        $this->handleQuizAttemptChange($quizAttempt, 'created');
    }

    /**
     * Handle quiz attempt updates
     */
    public function updating(QuizAttempt $quizAttempt): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.user_progress.enabled')) {
            return;
        }

        $syncKey = "quiz_attempt_{$quizAttempt->id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        // Check for progress-affecting changes
        if ($quizAttempt->isDirty(['score', 'correct_answers', 'total_questions'])) {
            self::$syncInProgress[$syncKey] = true;
            
            try {
                $this->handleQuizAttemptChange($quizAttempt, 'updated');
            } finally {
                unset(self::$syncInProgress[$syncKey]);
            }
        }
    }

    /**
     * Handle user field updates
     */
    public function updating(User $user): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.user_progress.enabled')) {
            return;
        }

        $syncKey = "user_{$user->id}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        // Check for legacy field changes
        if ($user->isDirty(['correct_answers_count', 'xp', 'level', 'study_streak_days'])) {
            self::$syncInProgress[$syncKey] = true;
            
            try {
                $this->handleUserProgressChange($user);
            } finally {
                unset(self::$syncInProgress[$syncKey]);
            }
        }
    }

    /**
     * Handle quiz attempt changes
     */
    private function handleQuizAttemptChange(QuizAttempt $quizAttempt, string $operation): void
    {
        $original = $operation === 'updated' ? $quizAttempt->getOriginal() : [];
        
        // Check for correct_answers vs score inconsistency
        if ($quizAttempt->correct_answers !== null && $quizAttempt->correct_answers !== $quizAttempt->score) {
            Log::warning('SSOT Quiz Attempt Score Inconsistency', [
                'type' => 'SCORE_INCONSISTENCY',
                'model' => 'QuizAttempt',
                'quiz_attempt_id' => $quizAttempt->id,
                'user_id' => $quizAttempt->user_id,
                'score' => $quizAttempt->score,
                'correct_answers' => $quizAttempt->correct_answers,
                'total_questions' => $quizAttempt->total_questions,
                'conflict_resolution' => 'SCORE_TAKES_PRECEDENCE',
                'detected_at' => now()
            ]);

            // Sync correct_answers to match score (SSOT)
            $quizAttempt->correct_answers = $quizAttempt->score;
        }

        Log::info('SSOT Quiz Attempt Progress Change', [
            'type' => 'QUIZ_ATTEMPT_CHANGE',
            'operation' => $operation,
            'model' => 'QuizAttempt',
            'quiz_attempt_id' => $quizAttempt->id,
            'user_id' => $quizAttempt->user_id,
            'topic_id' => $quizAttempt->topic_id,
            'quiz_type' => $quizAttempt->quiz_type,
            'score' => $quizAttempt->score,
            'total_questions' => $quizAttempt->total_questions,
            'percentage_grade' => $quizAttempt->percentage_grade,
            'changed_fields' => $operation === 'updated' ? [
                'score' => [
                    'old' => $original['score'] ?? null,
                    'new' => $quizAttempt->score
                ],
                'correct_answers' => [
                    'old' => $original['correct_answers'] ?? null,
                    'new' => $quizAttempt->correct_answers
                ]
            ] : null,
            'sync_action' => 'UPDATE_USER_PROGRESS',
            'timestamp' => now()
        ]);

        // Update user progress through SSOT service
        if ($operation === 'created') {
            $this->progressService->updateProgressAfterQuiz(
                $quizAttempt->user_id,
                $quizAttempt->score,
                $quizAttempt->total_questions
            );
        }

        // Sync user cached fields
        $this->syncUserCachedFields($quizAttempt->user_id);
    }

    /**
     * Handle user progress field changes
     */
    private function handleUserProgressChange(User $user): void
    {
        $original = $user->getOriginal();
        
        // Get SSOT computed values
        $computedProgress = $this->progressService->getUserProgress($user->id);
        
        // Check for conflicts
        $conflicts = [];
        
        if ($user->isDirty('correct_answers_count') && $user->correct_answers_count !== $computedProgress['total_correct_answers']) {
            $conflicts['correct_answers_count'] = [
                'legacy_value' => $user->correct_answers_count,
                'computed_value' => $computedProgress['total_correct_answers']
            ];
        }
        
        if ($user->isDirty('xp') && $user->xp !== $computedProgress['xp']) {
            $conflicts['xp'] = [
                'legacy_value' => $user->xp,
                'computed_value' => $computedProgress['xp']
            ];
        }
        
        if ($user->isDirty('level') && $user->level !== $computedProgress['level']) {
            $conflicts['level'] = [
                'legacy_value' => $user->level,
                'computed_value' => $computedProgress['level']
            ];
        }

        if (!empty($conflicts)) {
            Log::warning('SSOT User Progress Conflicts Detected', [
                'type' => 'LEGACY_WRITE_CONFLICT',
                'model' => 'User',
                'user_id' => $user->id,
                'conflicts' => $conflicts,
                'original_values' => [
                    'correct_answers_count' => $original['correct_answers_count'] ?? null,
                    'xp' => $original['xp'] ?? null,
                    'level' => $original['level'] ?? null,
                    'study_streak_days' => $original['study_streak_days'] ?? null
                ],
                'conflict_resolution' => 'COMPUTED_TAKES_PRECEDENCE',
                'detected_at' => now()
            ]);
        }

        Log::info('SSOT User Progress Legacy Write', [
            'type' => 'LEGACY_WRITE',
            'model' => 'User',
            'user_id' => $user->id,
            'changed_fields' => [
                'correct_answers_count' => [
                    'old' => $original['correct_answers_count'] ?? null,
                    'new' => $user->correct_answers_count
                ],
                'xp' => [
                    'old' => $original['xp'] ?? null,
                    'new' => $user->xp
                ],
                'level' => [
                    'old' => $original['level'] ?? null,
                    'new' => $user->level
                ],
                'study_streak_days' => [
                    'old' => $original['study_streak_days'] ?? null,
                    'new' => $user->study_streak_days
                ]
            ],
            'has_conflicts' => !empty($conflicts),
            'sync_action' => 'VALIDATE_AGAINST_SSOT',
            'timestamp' => now()
        ]);
    }

    /**
     * Sync user cached fields with SSOT values
     */
    private function syncUserCachedFields(int $userId): void
    {
        if (!in_array('ssot_to_legacy', config('ssot.observers.user_progress.sync_directions'))) {
            return;
        }

        $syncKey = "user_sync_{$userId}";
        
        // Prevent infinite recursion
        if (isset(self::$syncInProgress[$syncKey])) {
            return;
        }

        self::$syncInProgress[$syncKey] = true;
        
        try {
            $synced = $this->progressService->syncUserCachedFields($userId);
            
            if ($synced) {
                Log::info('SSOT User Progress Fields Synced', [
                    'type' => 'SSOT_TO_LEGACY_SYNC',
                    'model' => 'User',
                    'user_id' => $userId,
                    'sync_method' => 'cached_fields_update',
                    'synced_at' => now()
                ]);
            }
        } finally {
            unset(self::$syncInProgress[$syncKey]);
        }
    }

    /**
     * Handle quiz attempt deletion
     */
    public function deleted(QuizAttempt $quizAttempt): void
    {
        if (!config('ssot.sync_enabled') || !config('ssot.observers.user_progress.enabled')) {
            return;
        }

        Log::info('SSOT Quiz Attempt Deleted', [
            'type' => 'QUIZ_ATTEMPT_DELETED',
            'quiz_attempt_id' => $quizAttempt->id,
            'user_id' => $quizAttempt->user_id,
            'score' => $quizAttempt->score,
            'total_questions' => $quizAttempt->total_questions,
            'sync_action' => 'RECALCULATE_USER_PROGRESS',
            'deleted_at' => now()
        ]);

        // Recalculate and sync user progress
        $this->syncUserCachedFields($quizAttempt->user_id);
    }

    /**
     * Verify progress consistency
     */
    public function verifyConsistency(int $userId): array
    {
        return $this->progressService->verifyDataConsistency($userId);
    }

    /**
     * Bulk sync user progress for multiple users
     */
    public function bulkSyncUserProgress(array $userIds): array
    {
        $results = [];
        
        foreach ($userIds as $userId) {
            try {
                $synced = $this->progressService->syncUserCachedFields($userId);
                $results[$userId] = $synced;
            } catch (\Exception $e) {
                $results[$userId] = false;
                
                Log::error('SSOT User Progress Sync Failed', [
                    'type' => 'BULK_SYNC_ERROR',
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'failed_at' => now()
                ]);
            }
        }

        Log::info('SSOT User Progress Bulk Sync Completed', [
            'type' => 'BULK_SYNC_COMPLETED',
            'total_users' => count($userIds),
            'successful_syncs' => count(array_filter($results)),
            'failed_syncs' => count(array_filter($results, fn($r) => !$r)),
            'results' => $results,
            'completed_at' => now()
        ]);

        return $results;
    }
}