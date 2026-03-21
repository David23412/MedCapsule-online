<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Services\Core\UltraConsolidatedCacheService;

class ProgressCacheInvalidator
{
    /**
     * Lightweight in-process counters for perf instrumentation only.
     */
    private static int $invalidationCalls = 0;
    private static int $broadInvalidationCalls = 0;

    public function __construct(
        private UltraConsolidatedCacheService $cacheService
    ) {
    }

    /**
     * Canonical invalidation path for user/topic progress caches.
     *
     * @param int $userId
     * @param int $topicId
     * @param string|null $mode null => clear quiz+tutor mode-scoped keys
     * @param bool $invalidateBroadHierarchy also run smartInvalidate(user_progress)
     */
    public function invalidateUserTopicProgress(
        int $userId,
        int $topicId,
        ?string $mode = null,
        bool $invalidateBroadHierarchy = true
    ): void {
        self::$invalidationCalls++;

        QuizAttempt::clearProgressCache($userId, $topicId, $mode);

        if ($invalidateBroadHierarchy) {
            self::$broadInvalidationCalls++;
            $this->cacheService->smartInvalidate('user_progress', $userId, ['topic_id' => $topicId]);
        }
    }

    public function resetPerfCounters(): void
    {
        self::$invalidationCalls = 0;
        self::$broadInvalidationCalls = 0;
    }

    public function getPerfCounters(): array
    {
        return [
            'invalidation_calls' => self::$invalidationCalls,
            'broad_invalidation_calls' => self::$broadInvalidationCalls,
        ];
    }
}
