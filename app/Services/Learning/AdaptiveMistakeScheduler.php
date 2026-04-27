<?php

namespace App\Services\Learning;

use App\Models\Mistake;
use Carbon\Carbon;

class AdaptiveMistakeScheduler
{
    private FsrsScheduler $fsrsScheduler;

    public function __construct(?FsrsScheduler $fsrsScheduler = null)
    {
        $this->fsrsScheduler = $fsrsScheduler ?? app(FsrsScheduler::class);
    }

    public function applyReview(
        Mistake $mistake,
        int $rating,
        string $mode = 'quiz',
        ?int $responseSeconds = null,
        ?string $confidence = null,
        array $interactionSignals = []
    ): void {
        $normalizedMode = $mistake->normalizeReviewMode($mode);

        if ($normalizedMode === 'spaced') {
            $this->fsrsScheduler->applyReview(
                $mistake,
                $rating,
                $normalizedMode,
                $responseSeconds,
                $confidence,
                $interactionSignals
            );

            return;
        }

        $this->applyClassicReview($mistake, $rating >= 3, $normalizedMode);
    }

    public function initializeFromFailure(Mistake $mistake, ?string $submittedAnswer = null): void
    {
        $this->fsrsScheduler->initializeFromFailure($mistake, $submittedAnswer);
    }

    public function applySkip(Mistake $mistake, string $mode = 'quiz'): void
    {
        $normalizedMode = $mistake->normalizeReviewMode($mode);

        if ($normalizedMode === 'spaced') {
            $this->fsrsScheduler->applySkip($mistake, $normalizedMode);
            return;
        }

        $now = now();
        $mistake->last_attempt_date = $now;
        $mistake->mastered = false;
        $mistake->skipped = true;
        $mistake->setAdaptiveColumnValue('last_skipped_at', $now);
        $mistake->setAdaptiveColumnValue('skip_count', max(0, (int) ($mistake->skip_count ?? 0)) + 1);
        $mistake->setAdaptiveColumnValue('consecutive_skip_count', max(0, (int) ($mistake->consecutive_skip_count ?? 0)) + 1);
        $mistake->setAdaptiveColumnValue('skip_until', $now->copy()->addHours($normalizedMode === 'quick' ? 4 : 12));
    }

    private function applyClassicReview(Mistake $mistake, bool $isCorrect, string $mode): void
    {
        $now = now();
        $mistake->last_attempt_date = $now;
        $mistake->setAdaptiveColumnValue('last_reviewed_at', $now);
        $mistake->setAdaptiveColumnValue('review_count', max(0, (int) ($mistake->review_count ?? 0)) + 1);
        $mistake->skipped = false;
        $mistake->setAdaptiveColumnValue('skip_until', null);
        $mistake->setAdaptiveColumnValue('consecutive_skip_count', 0);

        if ($isCorrect) {
            $mistake->correct_streak = (int) $mistake->correct_streak + 1;
            if ($mode === 'quick') {
                $mistake->quick_correct_count = (int) ($mistake->quick_correct_count ?? 0) + 1;
            } else {
                $mistake->quiz_correct_count = (int) ($mistake->quiz_correct_count ?? 0) + 1;
            }
            $mistake->mastered = $mistake->isMastered($mode);
        } else {
            $mistake->correct_streak = 0;
            $mistake->mastered = false;
        }
    }
}
