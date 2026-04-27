<?php

namespace App\Services\Learning;

use App\Models\Mistake;
use App\Models\MistakeReviewEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FsrsScheduler
{
    public const STATE_NEW = 'new';
    public const STATE_LEARNING = 'learning';
    public const STATE_REVIEW = 'review';
    public const STATE_RELEARNING = 'relearning';
    public const STATE_SUSPENDED = 'suspended';

    public const GRADE_AGAIN = 1;
    public const GRADE_HARD = 2;
    public const GRADE_GOOD = 3;
    public const GRADE_EASY = 4;

    private const REQUEST_RETENTION = 0.90;
    private const MAX_INTERVAL_DAYS = 36500;
    private const LEARNING_DAY_START_HOUR = 4;
    private const LEARNING_STEPS_MINUTES = [1, 10];
    private const RELEARNING_STEPS_MINUTES = [10];
    private const DEFAULT_WEIGHTS = [
        0.212, 1.2931, 2.3065, 8.2956, 6.4133, 0.8334, 3.0194, 0.001, 1.8722, 0.1666,
        0.796, 1.4835, 0.0614, 0.2629, 1.6483, 0.6014, 1.8729, 0.5425, 0.0912, 0.0658,
        0.1542,
    ];

    private SchedulerCalibrationService $calibrationService;

    public function __construct(?SchedulerCalibrationService $calibrationService = null)
    {
        $this->calibrationService = $calibrationService ?? app(SchedulerCalibrationService::class);
    }

    public function applyReview(
        Mistake $mistake,
        int $rating,
        string $mode = 'spaced',
        ?int $responseSeconds = null,
        ?string $confidence = null,
        array $interactionSignals = [],
        ?Carbon $reviewedAt = null,
        bool $recordTelemetry = true
    ): void {
        $normalizedMode = $mistake->normalizeReviewMode($mode);
        $now = ($reviewedAt ?? now())->copy();
        $grade = $this->normalizeGrade($rating);

        $priorLastAttempt = $this->resolveLastReviewTimestamp($mistake);
        $difficulty = $this->resolveDifficulty($mistake);
        $stability = $this->resolveStability($mistake);
        $retrievability = $this->calculateRetrievabilityValue($mistake, $stability, $now);
        $before = $this->buildReviewSnapshot($mistake, $difficulty, $stability, $retrievability);

        $mistake->last_attempt_date = $now;
        $mistake->setAdaptiveColumnValue('last_reviewed_at', $now);
        $mistake->setAdaptiveColumnValue('review_count', max(0, (int) ($mistake->review_count ?? 0)) + 1);
        $mistake->setAdaptiveColumnValue('last_review_rating', $grade);
        $mistake->setAdaptiveColumnValue('last_review_confidence', $this->normalizeConfidence($confidence));
        $mistake->setAdaptiveColumnValue('last_response_seconds', $responseSeconds);
        $mistake->setAdaptiveColumnValue('hesitation_score', $this->resolveHesitationScore($mistake, $responseSeconds, $interactionSignals));

        $state = $this->currentState($mistake, $priorLastAttempt ? $priorLastAttempt->copy() : null);

        if ($state === self::STATE_LEARNING || $state === self::STATE_NEW) {
            $this->applyLearningReview($mistake, $grade, $now, $difficulty, $stability);
        } elseif ($state === self::STATE_RELEARNING) {
            $this->applyRelearningReview($mistake, $grade, $now, $difficulty, $stability);
        } else {
            $this->applyReviewCardReview($mistake, $grade, $now, $difficulty, $stability, $retrievability);
        }

        $profile = $this->calibrationService->getProfile($normalizedMode, (int) $mistake->user_id);

        if ($recordTelemetry) {
            $this->calibrationService->recordReviewEvent(
                $mistake,
                before: $before,
                rating: $grade,
                mode: $normalizedMode,
                responseSeconds: $responseSeconds,
                confidence: $this->normalizeConfidence($confidence),
                interactionSignals: $interactionSignals,
                reviewedAt: $now,
                targetRetention: self::REQUEST_RETENTION,
                profile: $profile,
            );
            $this->calibrationService->maybeRecalibrate($normalizedMode, (int) $mistake->user_id);
        }
    }

    public function initializeFromFailure(
        Mistake $mistake,
        ?string $submittedAnswer = null,
        ?Carbon $reviewedAt = null,
        bool $recordTelemetry = true
    ): void {
        $now = ($reviewedAt ?? now())->copy();
        $before = $this->buildReviewSnapshot(
            $mistake,
            $this->resolveDifficulty($mistake),
            $this->resolveStability($mistake),
            $this->calculateRetrievability($mistake, $now)
        );

        if ($submittedAnswer !== null) {
            $mistake->submitted_answer = $submittedAnswer;
        }

        $mistake->last_attempt_date = $now;
        $mistake->correct_streak = 0;
        $mistake->mastered = false;
        $mistake->skipped = false;
        $mistake->setAdaptiveColumnValue('last_reviewed_at', $now);
        $mistake->setAdaptiveColumnValue('last_review_rating', self::GRADE_AGAIN);
        $mistake->setAdaptiveColumnValue('last_review_confidence', null);
        $mistake->setAdaptiveColumnValue('last_response_seconds', null);
        $mistake->setAdaptiveColumnValue('hesitation_score', 0.0);
        $mistake->setAdaptiveColumnValue('lapse_count', max(0, (int) ($mistake->lapse_count ?? 0)) + 1);
        $mistake->setAdaptiveColumnValue('difficulty', $this->initDifficulty(self::GRADE_AGAIN));
        $mistake->setAdaptiveColumnValue('difficulty_baseline', $this->initDifficulty(self::GRADE_EASY));
        $mistake->setAdaptiveColumnValue('stability', $this->initStability(self::GRADE_AGAIN));
        $mistake->setAdaptiveColumnValue('retrievability', 1.0);
        $mistake->setAdaptiveColumnValue('interval_days', 0);
        $mistake->setAdaptiveColumnValue('ease_factor', $this->easeFactorFromDifficulty($this->initDifficulty(self::GRADE_AGAIN)));
        $mistake->setAdaptiveColumnValue('skip_until', null);
        $mistake->setAdaptiveColumnValue('consecutive_skip_count', 0);
        $mistake->setAdaptiveColumnValue('learning_state', self::STATE_LEARNING);
        $mistake->setAdaptiveColumnValue('same_day_relearn_step', 1);
        $mistake->setAdaptiveColumnValue('same_day_relearn_count', 0);
        $mistake->setAdaptiveColumnValue('next_review_at', $now);

        if ($recordTelemetry) {
            $this->calibrationService->recordStateTransitionEvent(
                $mistake,
                before: $before,
                mode: 'spaced',
                eventType: MistakeReviewEvent::EVENT_TYPE_FAILURE_INIT,
                reviewedAt: $now,
            );
        }
    }

    public function applySkip(Mistake $mistake, string $mode = 'spaced', ?Carbon $reviewedAt = null, bool $recordTelemetry = true): void
    {
        $now = ($reviewedAt ?? now())->copy();
        $before = $this->buildReviewSnapshot(
            $mistake,
            $this->resolveDifficulty($mistake),
            $this->resolveStability($mistake),
            $this->calculateRetrievability($mistake, $now)
        );

        $consecutiveSkipCount = max(0, (int) ($mistake->consecutive_skip_count ?? 0)) + 1;
        $skipDays = min(14, max(1, 1 << min(3, $consecutiveSkipCount - 1)));
        $skipUntil = $this->alignReviewDate($now, $skipDays);

        $mistake->last_attempt_date = $now;
        $mistake->mastered = false;
        $mistake->skipped = true;
        $mistake->setAdaptiveColumnValue('last_skipped_at', $now);
        $mistake->setAdaptiveColumnValue('skip_count', max(0, (int) ($mistake->skip_count ?? 0)) + 1);
        $mistake->setAdaptiveColumnValue('consecutive_skip_count', $consecutiveSkipCount);
        $mistake->setAdaptiveColumnValue('skip_until', $skipUntil);
        $mistake->setAdaptiveColumnValue('next_review_at', $skipUntil);

        if ($recordTelemetry) {
            $this->calibrationService->recordStateTransitionEvent(
                $mistake,
                before: $before,
                mode: $mode,
                eventType: MistakeReviewEvent::EVENT_TYPE_SKIP,
                reviewedAt: $now,
            );
        }
    }

    public function rebuildStateFromHistory(Mistake $mistake, Collection $events, ?Carbon $asOf = null): void
    {
        $asOf = ($asOf ?? now())->copy();
        $this->resetForReplay($mistake, $asOf);

        if ($events->isEmpty()) {
            $this->applyConservativeFallback($mistake, $asOf);
            return;
        }

        $initialized = false;

        foreach ($events->sortBy(fn (MistakeReviewEvent $event) => optional($event->reviewed_at)->timestamp ?? 0) as $event) {
            $reviewedAt = $event->reviewed_at instanceof Carbon ? $event->reviewed_at->copy() : Carbon::parse($event->reviewed_at);

            if ($event->event_type === MistakeReviewEvent::EVENT_TYPE_FAILURE_INIT) {
                $this->initializeFromFailure($mistake, $event->final_selected_answer, $reviewedAt, false);
                $initialized = true;
                continue;
            }

            if (!$initialized) {
                $this->initializeFromFailure($mistake, $mistake->submitted_answer, $reviewedAt->copy()->subSecond(), false);
                $initialized = true;
            }

            if ($event->event_type === MistakeReviewEvent::EVENT_TYPE_SKIP) {
                $this->applySkip($mistake, 'spaced', $reviewedAt, false);
                continue;
            }

            if ($event->event_type !== MistakeReviewEvent::EVENT_TYPE_REVIEW) {
                continue;
            }

            $grade = $this->gradeFromHistoryEvent($event);
            $this->applyReview(
                $mistake,
                $grade,
                'spaced',
                $event->response_seconds,
                $event->confidence_level,
                [],
                $reviewedAt,
                false
            );
        }

        if ($mistake->skipped && $mistake->skip_until instanceof Carbon && $mistake->skip_until->lte($asOf)) {
            $mistake->skipped = false;
            $mistake->setAdaptiveColumnValue('skip_until', null);
        }
    }

    public function currentState(Mistake $mistake, ?Carbon $asOf = null): string
    {
        $asOf ??= now();

        if ($mistake->skipped && $mistake->skip_until instanceof Carbon && $mistake->skip_until->gt($asOf)) {
            return self::STATE_SUSPENDED;
        }

        $state = strtolower(trim((string) ($mistake->learning_state ?? '')));

        return match ($state) {
            self::STATE_LEARNING => self::STATE_LEARNING,
            'relearn', self::STATE_RELEARNING => self::STATE_RELEARNING,
            self::STATE_REVIEW => self::STATE_REVIEW,
            default => ((int) ($mistake->review_count ?? 0) > 0 || (int) ($mistake->interval_days ?? 0) > 0)
                ? self::STATE_REVIEW
                : self::STATE_NEW,
        };
    }

    public function calculateRetrievability(Mistake $mistake, ?Carbon $asOf = null): float
    {
        return $this->calculateRetrievabilityValue($mistake, $this->resolveStability($mistake), $asOf ?? now());
    }

    public static function forgettingCurve(float $elapsedDays, float $stability): float
    {
        $elapsedDays = max(0.0, $elapsedDays);
        $stability = max(0.1, $stability);

        return max(0.0, min(1.0, pow(1 + self::factor() * $elapsedDays / $stability, self::decay())));
    }

    private function applyLearningReview(Mistake $mistake, int $grade, Carbon $now, float $difficulty, float $stability): void
    {
        $nextDifficulty = $this->nextDifficulty($difficulty, $grade);
        $nextStability = $this->nextShortTermStability($stability, $grade);
        $currentStep = max(1, (int) ($mistake->same_day_relearn_step ?? 1));

        if ($grade === self::GRADE_EASY) {
            $goodInterval = $this->nextIntervalDays($this->nextShortTermStability($stability, self::GRADE_GOOD), (int) ($mistake->id ?? 0));
            $easyInterval = max(
                $this->nextIntervalDays($nextStability, (int) ($mistake->id ?? 0)),
                $goodInterval + 1
            );
            $this->graduateToReview($mistake, $now, $nextDifficulty, $nextStability, $easyInterval);
            return;
        }

        if ($grade === self::GRADE_GOOD && $currentStep >= count(self::LEARNING_STEPS_MINUTES)) {
            $intervalDays = $this->nextIntervalDays($nextStability, (int) ($mistake->id ?? 0));
            $this->graduateToReview($mistake, $now, $nextDifficulty, $nextStability, $intervalDays);
            return;
        }

        $nextStep = $grade === self::GRADE_GOOD ? min(count(self::LEARNING_STEPS_MINUTES), $currentStep + 1) : $currentStep;
        $delayMinutes = $grade === self::GRADE_HARD
            ? max(5, (int) round(self::LEARNING_STEPS_MINUTES[min($currentStep - 1, count(self::LEARNING_STEPS_MINUTES) - 1)] * 1.5))
            : self::LEARNING_STEPS_MINUTES[max(0, $nextStep - 1)];

        if ($grade === self::GRADE_AGAIN) {
            $nextStep = 1;
        }

        $mistake->correct_streak = $grade >= self::GRADE_GOOD ? ((int) $mistake->correct_streak + 1) : 0;
        $mistake->mastered = false;
        $mistake->skipped = false;
        $mistake->setAdaptiveColumnValue('difficulty', $nextDifficulty);
        $mistake->setAdaptiveColumnValue('stability', $nextStability);
        $mistake->setAdaptiveColumnValue('retrievability', 1.0);
        $mistake->setAdaptiveColumnValue('interval_days', 0);
        $mistake->setAdaptiveColumnValue('ease_factor', $this->easeFactorFromDifficulty($nextDifficulty));
        $mistake->setAdaptiveColumnValue('skip_until', null);
        $mistake->setAdaptiveColumnValue('consecutive_skip_count', 0);
        $mistake->setAdaptiveColumnValue('learning_state', self::STATE_LEARNING);
        $mistake->setAdaptiveColumnValue('same_day_relearn_step', $nextStep);
        $mistake->setAdaptiveColumnValue('next_review_at', $now->copy()->addMinutes($delayMinutes));
    }

    private function applyRelearningReview(Mistake $mistake, int $grade, Carbon $now, float $difficulty, float $stability): void
    {
        $nextDifficulty = $this->nextDifficulty($difficulty, $grade);
        $nextStability = $this->nextShortTermStability($stability, $grade);

        if ($grade >= self::GRADE_GOOD) {
            $goodInterval = $this->nextIntervalDays($this->nextShortTermStability($stability, self::GRADE_GOOD), (int) ($mistake->id ?? 0));
            $selectedInterval = $grade === self::GRADE_EASY
                ? max($this->nextIntervalDays($nextStability, (int) ($mistake->id ?? 0)), $goodInterval + 1)
                : $goodInterval;

            $this->graduateToReview($mistake, $now, $nextDifficulty, $nextStability, $selectedInterval);
            return;
        }

        $delayMinutes = $grade === self::GRADE_HARD
            ? max(15, (int) round(self::RELEARNING_STEPS_MINUTES[0] * 1.5))
            : self::RELEARNING_STEPS_MINUTES[0];

        $mistake->correct_streak = 0;
        $mistake->mastered = false;
        $mistake->skipped = false;
        $mistake->setAdaptiveColumnValue('difficulty', $nextDifficulty);
        $mistake->setAdaptiveColumnValue('stability', $nextStability);
        $mistake->setAdaptiveColumnValue('retrievability', 1.0);
        $mistake->setAdaptiveColumnValue('interval_days', 0);
        $mistake->setAdaptiveColumnValue('ease_factor', $this->easeFactorFromDifficulty($nextDifficulty));
        $mistake->setAdaptiveColumnValue('skip_until', null);
        $mistake->setAdaptiveColumnValue('consecutive_skip_count', 0);
        $mistake->setAdaptiveColumnValue('learning_state', self::STATE_RELEARNING);
        $mistake->setAdaptiveColumnValue('same_day_relearn_step', 1);
        $mistake->setAdaptiveColumnValue('next_review_at', $now->copy()->addMinutes($delayMinutes));
    }

    private function applyReviewCardReview(
        Mistake $mistake,
        int $grade,
        Carbon $now,
        float $difficulty,
        float $stability,
        float $retrievability
    ): void {
        $nextDifficulty = $this->nextDifficulty($difficulty, $grade);

        if ($grade === self::GRADE_AGAIN) {
            $nextStability = $this->nextForgetStability($difficulty, $stability, $retrievability);
            $mistake->correct_streak = 0;
            $mistake->mastered = false;
            $mistake->skipped = false;
            $mistake->setAdaptiveColumnValue('lapse_count', max(0, (int) ($mistake->lapse_count ?? 0)) + 1);
            $mistake->setAdaptiveColumnValue('difficulty', $nextDifficulty);
            $mistake->setAdaptiveColumnValue('stability', $nextStability);
            $mistake->setAdaptiveColumnValue('retrievability', 1.0);
            $mistake->setAdaptiveColumnValue('interval_days', 0);
            $mistake->setAdaptiveColumnValue('ease_factor', $this->easeFactorFromDifficulty($nextDifficulty));
            $mistake->setAdaptiveColumnValue('skip_until', null);
            $mistake->setAdaptiveColumnValue('consecutive_skip_count', 0);
            $mistake->setAdaptiveColumnValue('learning_state', self::STATE_RELEARNING);
            $mistake->setAdaptiveColumnValue('same_day_relearn_step', 1);
            $mistake->setAdaptiveColumnValue('same_day_relearn_count', max(0, (int) ($mistake->same_day_relearn_count ?? 0)) + 1);
            $mistake->setAdaptiveColumnValue('next_review_at', $now->copy()->addMinutes(self::RELEARNING_STEPS_MINUTES[0]));
            return;
        }

        $nextStability = $this->nextRecallStability($difficulty, $stability, $retrievability, $grade);
        $hardInterval = $this->nextIntervalDays($this->nextRecallStability($difficulty, $stability, $retrievability, self::GRADE_HARD), (int) ($mistake->id ?? 0));
        $goodInterval = $this->nextIntervalDays($this->nextRecallStability($difficulty, $stability, $retrievability, self::GRADE_GOOD), (int) ($mistake->id ?? 0));
        $easyInterval = $this->nextIntervalDays($this->nextRecallStability($difficulty, $stability, $retrievability, self::GRADE_EASY), (int) ($mistake->id ?? 0));
        $hardInterval = min($hardInterval, $goodInterval);
        $goodInterval = max($goodInterval, $hardInterval + 1);
        $easyInterval = max($easyInterval, $goodInterval + 1);

        $intervalDays = match ($grade) {
            self::GRADE_HARD => $hardInterval,
            self::GRADE_GOOD => $goodInterval,
            self::GRADE_EASY => $easyInterval,
            default => 1,
        };

        $mistake->correct_streak = (int) $mistake->correct_streak + 1;
        $mistake->mastered = false;
        $mistake->skipped = false;
        $mistake->setAdaptiveColumnValue('difficulty', $nextDifficulty);
        $mistake->setAdaptiveColumnValue('stability', $nextStability);
        $mistake->setAdaptiveColumnValue('retrievability', 1.0);
        $mistake->setAdaptiveColumnValue('interval_days', $intervalDays);
        $mistake->setAdaptiveColumnValue('ease_factor', $this->easeFactorFromDifficulty($nextDifficulty));
        $mistake->setAdaptiveColumnValue('skip_until', null);
        $mistake->setAdaptiveColumnValue('consecutive_skip_count', 0);
        $mistake->setAdaptiveColumnValue('learning_state', self::STATE_REVIEW);
        $mistake->setAdaptiveColumnValue('same_day_relearn_step', 0);
        $mistake->setAdaptiveColumnValue('same_day_relearn_count', 0);
        $mistake->setAdaptiveColumnValue('next_review_at', $this->alignReviewDate($now, $intervalDays));
    }

    private function graduateToReview(Mistake $mistake, Carbon $now, float $difficulty, float $stability, int $intervalDays): void
    {
        $mistake->correct_streak = (int) $mistake->correct_streak + 1;
        $mistake->mastered = false;
        $mistake->skipped = false;
        $mistake->setAdaptiveColumnValue('difficulty', $difficulty);
        $mistake->setAdaptiveColumnValue('stability', $stability);
        $mistake->setAdaptiveColumnValue('retrievability', 1.0);
        $mistake->setAdaptiveColumnValue('interval_days', max(1, $intervalDays));
        $mistake->setAdaptiveColumnValue('ease_factor', $this->easeFactorFromDifficulty($difficulty));
        $mistake->setAdaptiveColumnValue('skip_until', null);
        $mistake->setAdaptiveColumnValue('consecutive_skip_count', 0);
        $mistake->setAdaptiveColumnValue('learning_state', self::STATE_REVIEW);
        $mistake->setAdaptiveColumnValue('same_day_relearn_step', 0);
        $mistake->setAdaptiveColumnValue('same_day_relearn_count', 0);
        $mistake->setAdaptiveColumnValue('next_review_at', $this->alignReviewDate($now, max(1, $intervalDays)));
    }

    private function resetForReplay(Mistake $mistake, Carbon $now): void
    {
        $mistake->correct_streak = 0;
        $mistake->mastered = false;
        $mistake->skipped = false;
        $mistake->setAdaptiveColumnValue('review_count', 0);
        $mistake->setAdaptiveColumnValue('lapse_count', 0);
        $mistake->setAdaptiveColumnValue('interval_days', 0);
        $mistake->setAdaptiveColumnValue('difficulty', $this->initDifficulty(self::GRADE_AGAIN));
        $mistake->setAdaptiveColumnValue('difficulty_baseline', $this->initDifficulty(self::GRADE_EASY));
        $mistake->setAdaptiveColumnValue('stability', $this->initStability(self::GRADE_AGAIN));
        $mistake->setAdaptiveColumnValue('retrievability', 1.0);
        $mistake->setAdaptiveColumnValue('skip_until', null);
        $mistake->setAdaptiveColumnValue('consecutive_skip_count', 0);
        $mistake->setAdaptiveColumnValue('learning_state', null);
        $mistake->setAdaptiveColumnValue('same_day_relearn_step', 0);
        $mistake->setAdaptiveColumnValue('same_day_relearn_count', 0);
        $mistake->setAdaptiveColumnValue('last_review_rating', null);
        $mistake->setAdaptiveColumnValue('last_review_confidence', null);
        $mistake->setAdaptiveColumnValue('last_response_seconds', null);
        $mistake->setAdaptiveColumnValue('hesitation_score', 0.0);
        $mistake->setAdaptiveColumnValue('next_review_at', $now);
        $mistake->setAdaptiveColumnValue('last_reviewed_at', $now);
        $mistake->last_attempt_date = $now;
    }

    private function applyConservativeFallback(Mistake $mistake, Carbon $asOf): void
    {
        if ($mistake->skipped && $mistake->skip_until instanceof Carbon && $mistake->skip_until->gt($asOf)) {
            $mistake->setAdaptiveColumnValue('learning_state', self::STATE_SUSPENDED);
            $mistake->setAdaptiveColumnValue('next_review_at', $mistake->skip_until);
            return;
        }

        $mistake->mastered = false;
        $mistake->skipped = false;
        $mistake->setAdaptiveColumnValue('difficulty', $this->initDifficulty(self::GRADE_AGAIN));
        $mistake->setAdaptiveColumnValue('difficulty_baseline', $this->initDifficulty(self::GRADE_EASY));
        $mistake->setAdaptiveColumnValue('stability', $this->initStability(self::GRADE_AGAIN));
        $mistake->setAdaptiveColumnValue('retrievability', 1.0);
        $mistake->setAdaptiveColumnValue('interval_days', 0);
        $mistake->setAdaptiveColumnValue('learning_state', self::STATE_LEARNING);
        $mistake->setAdaptiveColumnValue('same_day_relearn_step', 1);
        $mistake->setAdaptiveColumnValue('next_review_at', $asOf);
    }

    private function gradeFromHistoryEvent(MistakeReviewEvent $event): int
    {
        if (!$event->is_correct) {
            return self::GRADE_AGAIN;
        }

        return match ($this->normalizeConfidence($event->confidence_level)) {
            'guessed' => self::GRADE_HARD,
            'knew' => self::GRADE_EASY,
            default => self::GRADE_GOOD,
        };
    }

    private function normalizeGrade(int $rating): int
    {
        return max(self::GRADE_AGAIN, min(self::GRADE_EASY, $rating));
    }

    private function normalizeConfidence(?string $confidence): ?string
    {
        return match ($confidence) {
            'guessed', 'unsure', 'knew' => $confidence,
            default => null,
        };
    }

    private function resolveDifficulty(Mistake $mistake): float
    {
        $stored = $mistake->difficulty;

        if ($stored === null) {
            return $this->initDifficulty(self::GRADE_AGAIN);
        }

        $value = (float) $stored;

        if ($value > 0.0 && $value <= 1.0) {
            return round(1.0 + ($value * 9.0), 2);
        }

        return round(max(1.0, min(10.0, $value)), 2);
    }

    private function resolveStability(Mistake $mistake): float
    {
        return round(max(0.1, (float) ($mistake->stability ?? $this->initStability(self::GRADE_AGAIN))), 2);
    }

    private function calculateRetrievabilityValue(Mistake $mistake, float $stability, Carbon $asOf): float
    {
        $lastReviewedAt = $this->resolveLastReviewTimestamp($mistake);

        if (!$lastReviewedAt) {
            return 1.0;
        }

        $elapsedDays = max(0.0, $lastReviewedAt->diffInSeconds($asOf) / 86400);

        return round(self::forgettingCurve($elapsedDays, $stability), 4);
    }

    private function resolveLastReviewTimestamp(Mistake $mistake): ?Carbon
    {
        $lastReviewedAt = $mistake->last_reviewed_at instanceof Carbon
            ? $mistake->last_reviewed_at
            : ($mistake->last_reviewed_at ? Carbon::parse($mistake->last_reviewed_at) : null);

        if ($lastReviewedAt instanceof Carbon) {
            return $lastReviewedAt;
        }

        return $mistake->last_attempt_date instanceof Carbon
            ? $mistake->last_attempt_date
            : ($mistake->last_attempt_date ? Carbon::parse($mistake->last_attempt_date) : null);
    }

    private function buildReviewSnapshot(Mistake $mistake, float $difficulty, float $stability, float $retrievability): array
    {
        return [
            'difficulty' => $difficulty,
            'stability' => $stability,
            'retrievability' => $retrievability,
            'interval_days' => (int) ($mistake->interval_days ?? 0),
            'next_review_at' => $mistake->next_review_at,
            'last_attempt_date' => $mistake->last_attempt_date,
            'last_reviewed_at' => $mistake->last_reviewed_at,
        ];
    }

    private function initDifficulty(int $grade): float
    {
        return round($this->constrainDifficulty(
            self::weights()[4] - exp(self::weights()[5] * ($grade - 1)) + 1
        ), 2);
    }

    private function initStability(int $grade): float
    {
        return round(max(self::weights()[$grade - 1], 0.1), 2);
    }

    private function nextDifficulty(float $difficulty, int $grade): float
    {
        $delta = -self::weights()[6] * ($grade - 3);
        $next = $difficulty + ($delta * (10 - $difficulty) / 9);

        return round($this->constrainDifficulty($this->meanReversion($this->initDifficulty(self::GRADE_EASY), $next)), 2);
    }

    private function nextRecallStability(float $difficulty, float $stability, float $retrievability, int $grade): float
    {
        $hardPenalty = $grade === self::GRADE_HARD ? self::weights()[15] : 1.0;
        $easyBonus = $grade === self::GRADE_EASY ? self::weights()[16] : 1.0;
        $next = $stability * (
            1
            + exp(self::weights()[8])
            * (11 - $difficulty)
            * pow($stability, -self::weights()[9])
            * (exp((1 - $retrievability) * self::weights()[10]) - 1)
            * $hardPenalty
            * $easyBonus
        );

        return round(max(0.1, $next), 2);
    }

    private function nextForgetStability(float $difficulty, float $stability, float $retrievability): float
    {
        $sMin = $stability / exp(self::weights()[17] * self::weights()[18]);
        $next = min(
            self::weights()[11]
            * pow($difficulty, -self::weights()[12])
            * (pow($stability + 1, self::weights()[13]) - 1)
            * exp((1 - $retrievability) * self::weights()[14]),
            $sMin
        );

        return round(max(0.1, $next), 2);
    }

    private function nextShortTermStability(float $stability, int $grade): float
    {
        $stabilityIncrease = exp(self::weights()[17] * ($grade - 3 + self::weights()[18])) * pow($stability, -self::weights()[19]);
        if ($grade >= self::GRADE_GOOD) {
            $stabilityIncrease = max($stabilityIncrease, 1.0);
        }

        return round(max(0.1, $stability * $stabilityIncrease), 2);
    }

    private function nextIntervalDays(float $stability, int $mistakeId): int
    {
        $rawInterval = $stability / self::factor() * (pow(self::REQUEST_RETENTION, 1 / self::decay()) - 1);
        $fuzzedInterval = $this->applyFuzz($rawInterval, $mistakeId);

        return max(1, min(self::MAX_INTERVAL_DAYS, (int) round($fuzzedInterval)));
    }

    private function applyFuzz(float $intervalDays, int $mistakeId): float
    {
        if ($intervalDays < 2.5) {
            return $intervalDays;
        }

        $rounded = (int) round($intervalDays);
        $minimum = max(2, (int) round($rounded * 0.95 - 1));
        $maximum = (int) round($rounded * 1.05 + 1);
        $span = max(1, $maximum - $minimum + 1);
        $seed = crc32($mistakeId . ':' . $rounded);

        return $minimum + ($seed % $span);
    }

    private function easeFactorFromDifficulty(float $difficulty): float
    {
        $normalized = ($difficulty - 1.0) / 9.0;

        return round(max(1.3, min(3.2, 3.2 - ($normalized * 1.9))), 2);
    }

    private function alignReviewDate(Carbon $reviewedAt, int $intervalDays): Carbon
    {
        return $reviewedAt->copy()
            ->subHours(self::LEARNING_DAY_START_HOUR)
            ->startOfDay()
            ->addDays(max(1, $intervalDays))
            ->addHours(self::LEARNING_DAY_START_HOUR);
    }

    private function meanReversion(float $initial, float $current): float
    {
        return (self::weights()[7] * $initial) + ((1 - self::weights()[7]) * $current);
    }

    private function constrainDifficulty(float $difficulty): float
    {
        return min(max($difficulty, 1.0), 10.0);
    }

    private function resolveHesitationScore(Mistake $mistake, ?int $responseSeconds, array $interactionSignals): float
    {
        $responseSeconds = max(0, (int) ($responseSeconds ?? 0));
        $answerChanges = max(0, (int) ($interactionSignals['answer_change_count'] ?? 0));
        $confidenceChanges = max(0, (int) ($interactionSignals['confidence_change_count'] ?? 0));
        $firstSelectionDelay = max(0, (int) ($interactionSignals['time_to_first_selection'] ?? 0));
        $baseline = max(0.0, min(1.0, ((float) ($mistake->hesitation_score ?? 0.0)) * 0.4));

        $signal = min(
            1.0,
            ($baseline)
            + min(0.45, $responseSeconds / 120)
            + min(0.20, $firstSelectionDelay / 90)
            + min(0.20, $answerChanges * 0.10)
            + min(0.15, $confidenceChanges * 0.08)
        );

        return round($signal, 4);
    }

    private static function decay(): float
    {
        return -self::weights()[20];
    }

    private static function factor(): float
    {
        return pow(0.9, 1 / self::decay()) - 1;
    }

    private static function weights(): array
    {
        return self::DEFAULT_WEIGHTS;
    }
}
