<?php

namespace App\Services\Learning;

use App\Models\Mistake;
use App\Models\MistakeReviewEvent;
use App\Models\SchedulerCalibrationProfile;
use App\Models\UserSchedulerCalibrationProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SchedulerCalibrationService
{
    private const DEFAULTS = [
        'quiz' => [
            'recall_bias' => 0.0000,
            'stability_scale' => 1.0000,
            'difficulty_scale' => 1.0000,
            'response_sensitivity' => 0.1200,
            'confidence_sensitivity' => 0.1600,
            'overconfidence_penalty' => 0.1800,
            'hesitation_sensitivity' => 0.0800,
            'relearn_scale' => 1.0000,
            'decay_base' => 0.9000,
        ],
        'quick' => [
            'recall_bias' => 0.0200,
            'stability_scale' => 0.9400,
            'difficulty_scale' => 1.0000,
            'response_sensitivity' => 0.1000,
            'confidence_sensitivity' => 0.1200,
            'overconfidence_penalty' => 0.1200,
            'hesitation_sensitivity' => 0.0600,
            'relearn_scale' => 0.9000,
            'decay_base' => 0.8900,
        ],
        'spaced' => [
            'recall_bias' => 0.0100,
            'stability_scale' => 1.0000,
            'difficulty_scale' => 1.0000,
            'response_sensitivity' => 0.1400,
            'confidence_sensitivity' => 0.1800,
            'overconfidence_penalty' => 0.2200,
            'hesitation_sensitivity' => 0.1200,
            'relearn_scale' => 0.7500,
            'decay_base' => 0.9000,
        ],
    ];

    public function getProfile(string $mode, ?int $userId = null): object
    {
        $mode = $this->normalizeMode($mode);
        $defaults = self::DEFAULTS[$mode] ?? self::DEFAULTS['quiz'];

        if (!$this->supportsCalibrationStorage()) {
            return (object) array_merge(['mode' => $mode, 'user_id' => $userId], $defaults);
        }

        $profile = SchedulerCalibrationProfile::query()->firstOrCreate(
            ['mode' => $mode],
            array_merge([
                'total_reviews' => 0,
                'calibration_reviews' => 0,
                'observed_recall_rate' => 0.0,
                'mean_prediction_error' => 0.0,
                'last_calibrated_at' => null,
            ], $defaults)
        );

        $resolved = [
            'mode' => $profile->mode,
            'user_id' => $userId,
            'total_reviews' => (int) $profile->total_reviews,
            'calibration_reviews' => (int) $profile->calibration_reviews,
            'observed_recall_rate' => (float) $profile->observed_recall_rate,
            'mean_prediction_error' => (float) $profile->mean_prediction_error,
            'recall_bias' => (float) $profile->recall_bias,
            'stability_scale' => (float) $profile->stability_scale,
            'difficulty_scale' => (float) $profile->difficulty_scale,
            'response_sensitivity' => (float) $profile->response_sensitivity,
            'confidence_sensitivity' => (float) ($profile->confidence_sensitivity ?? $defaults['confidence_sensitivity']),
            'overconfidence_penalty' => (float) ($profile->overconfidence_penalty ?? $defaults['overconfidence_penalty']),
            'hesitation_sensitivity' => (float) ($profile->hesitation_sensitivity ?? $defaults['hesitation_sensitivity']),
            'relearn_scale' => (float) $profile->relearn_scale,
            'decay_base' => (float) $profile->decay_base,
            'last_calibrated_at' => $profile->last_calibrated_at,
            'calibration_meta' => $profile->calibration_meta,
        ];

        return (object) $this->applyUserConfidenceProfile($resolved, $mode, $userId, $defaults);
    }

    public function profileSnapshot(object $profile): array
    {
        return [
            'mode' => $profile->mode,
            'user_id' => $profile->user_id ?? null,
            'recall_bias' => (float) ($profile->recall_bias ?? 0.0),
            'stability_scale' => (float) ($profile->stability_scale ?? 1.0),
            'difficulty_scale' => (float) ($profile->difficulty_scale ?? 1.0),
            'response_sensitivity' => (float) ($profile->response_sensitivity ?? 0.12),
            'confidence_sensitivity' => (float) ($profile->confidence_sensitivity ?? 0.16),
            'overconfidence_penalty' => (float) ($profile->overconfidence_penalty ?? 0.18),
            'confidence_reliability' => (float) ($profile->confidence_reliability ?? 1.0),
            'hesitation_sensitivity' => (float) ($profile->hesitation_sensitivity ?? 0.10),
            'relearn_scale' => (float) ($profile->relearn_scale ?? 1.0),
            'decay_base' => (float) ($profile->decay_base ?? 0.9),
        ];
    }

    public function recordReviewEvent(
        Mistake $mistake,
        array $before,
        int $rating,
        string $mode,
        ?int $responseSeconds,
        ?string $confidence,
        array $interactionSignals,
        Carbon $reviewedAt,
        ?float $targetRetention,
        object $profile
    ): void {
        if (!$this->supportsCalibrationStorage()) {
            return;
        }

        $lastReviewedAt = $before['last_reviewed_at'] ?? $before['last_attempt_date'] ?? null;
        $elapsedDays = $lastReviewedAt instanceof Carbon
            ? round(max(0.0, $lastReviewedAt->diffInSeconds($reviewedAt) / 86400), 4)
            : null;

        $this->createEvent([
            'mistake_id' => $mistake->id,
            'user_id' => $mistake->user_id,
            'question_id' => $mistake->question_id,
            'review_mode' => $this->normalizeMode($mode),
            'event_type' => MistakeReviewEvent::EVENT_TYPE_REVIEW,
            'rating' => max(1, min(5, $rating)),
            'is_correct' => $rating >= 3,
            'confidence_level' => $this->normalizeConfidence($confidence),
            'confidence_weight' => $this->confidenceWeight($confidence),
            'response_seconds' => $responseSeconds,
            'first_selected_answer' => null,
            'final_selected_answer' => $this->stringOrNull($interactionSignals['final_selected_answer'] ?? null),
            'answer_change_count' => 0,
            'time_to_first_selection' => null,
            'first_confidence_level' => null,
            'confidence_change_count' => 0,
            'elapsed_days_since_last_review' => $elapsedDays,
            'prior_difficulty' => $this->floatOrNull($before['difficulty'] ?? null),
            'prior_stability' => $this->floatOrNull($before['stability'] ?? null),
            'prior_retrievability' => $this->floatOrNull($before['retrievability'] ?? null),
            'prior_interval_days' => $before['interval_days'] ?? null,
            'prior_next_review_at' => $before['next_review_at'] ?? null,
            'resulting_difficulty' => $this->floatOrNull($mistake->difficulty),
            'resulting_stability' => $this->floatOrNull($mistake->stability),
            'resulting_retrievability' => $this->floatOrNull($mistake->retrievability),
            'resulting_interval_days' => (int) ($mistake->interval_days ?? 0),
            'resulting_next_review_at' => $mistake->next_review_at,
            'target_retention' => $targetRetention,
            'decay_base' => (float) ($profile->decay_base ?? 0.9),
            'calibration_snapshot' => $this->profileSnapshot($profile),
            'reviewed_at' => $reviewedAt,
        ]);
    }

    public function recordStateTransitionEvent(
        Mistake $mistake,
        array $before,
        string $mode,
        string $eventType,
        Carbon $reviewedAt
    ): void {
        if (!$this->supportsCalibrationStorage()) {
            return;
        }

        if (!in_array($eventType, [
            MistakeReviewEvent::EVENT_TYPE_FAILURE_INIT,
            MistakeReviewEvent::EVENT_TYPE_SKIP,
        ], true)) {
            return;
        }

        $normalizedMode = $this->normalizeMode($mode);
        $profile = $this->getProfile($normalizedMode, (int) $mistake->user_id);
        $lastReviewedAt = $before['last_reviewed_at'] ?? $before['last_attempt_date'] ?? null;
        $elapsedDays = $lastReviewedAt instanceof Carbon
            ? round(max(0.0, $lastReviewedAt->diffInSeconds($reviewedAt) / 86400), 4)
            : null;

        $this->createEvent([
            'mistake_id' => $mistake->id,
            'user_id' => $mistake->user_id,
            'question_id' => $mistake->question_id,
            'review_mode' => $normalizedMode,
            'event_type' => $eventType,
            'rating' => 1,
            'is_correct' => false,
            'confidence_level' => null,
            'confidence_weight' => null,
            'response_seconds' => null,
            'first_selected_answer' => null,
            'final_selected_answer' => $eventType === MistakeReviewEvent::EVENT_TYPE_FAILURE_INIT
                ? $this->stringOrNull($mistake->submitted_answer ?? null)
                : null,
            'answer_change_count' => 0,
            'time_to_first_selection' => null,
            'first_confidence_level' => null,
            'confidence_change_count' => 0,
            'elapsed_days_since_last_review' => $elapsedDays,
            'prior_difficulty' => $this->floatOrNull($before['difficulty'] ?? null),
            'prior_stability' => $this->floatOrNull($before['stability'] ?? null),
            'prior_retrievability' => $this->floatOrNull($before['retrievability'] ?? null),
            'prior_interval_days' => $before['interval_days'] ?? null,
            'prior_next_review_at' => $before['next_review_at'] ?? null,
            'resulting_difficulty' => $this->floatOrNull($mistake->difficulty),
            'resulting_stability' => $this->floatOrNull($mistake->stability),
            'resulting_retrievability' => $this->floatOrNull($mistake->retrievability),
            'resulting_interval_days' => (int) ($mistake->interval_days ?? 0),
            'resulting_next_review_at' => $mistake->next_review_at,
            'target_retention' => null,
            'decay_base' => (float) ($profile->decay_base ?? 0.9),
            'calibration_snapshot' => $this->profileSnapshot($profile),
            'reviewed_at' => $reviewedAt,
        ]);
    }

    public function maybeRecalibrate(string $mode, ?int $userId = null): void
    {
        if (!$this->supportsCalibrationStorage()) {
            return;
        }

        $mode = $this->normalizeMode($mode);
        $profile = SchedulerCalibrationProfile::query()->firstOrCreate(['mode' => $mode], self::DEFAULTS[$mode] ?? self::DEFAULTS['quiz']);

        if ($profile->last_calibrated_at && $profile->last_calibrated_at->gt(now()->subHours(6))) {
            return;
        }

        $reviewCount = MistakeReviewEvent::query()
            ->where('review_mode', $mode)
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->count();

        if ($reviewCount < 10 || $reviewCount % 5 !== 0) {
            return;
        }

        Cache::lock("scheduler-calibration:{$mode}", 10)->get(function () use ($mode) {
            $this->recalibrateMode($mode);
        });

        if ($userId !== null && $mode === 'spaced') {
            $this->maybeRecalibrateUserConfidence($mode, $userId);
        }
    }

    public function recalibrateMode(string $mode, int $lookbackDays = 120): object
    {
        $mode = $this->normalizeMode($mode);
        $profile = $this->getProfile($mode);

        if (!$this->supportsCalibrationStorage()) {
            return $profile;
        }

        $events = MistakeReviewEvent::query()
            ->where('review_mode', $mode)
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->where('reviewed_at', '>=', now()->subDays($lookbackDays))
            ->orderBy('mistake_id')
            ->orderBy('reviewed_at')
            ->orderBy('id')
            ->limit(5000)
            ->get();

        if ($events->count() < 12) {
            return $profile;
        }

        $metrics = $this->deriveMetrics($events, $profile);
        if ($metrics['sample_size'] < 8) {
            return $profile;
        }
        $quality = $this->assessModeCalibrationQuality($metrics, $mode);
        if ($quality['score'] < 0.18) {
            return $profile;
        }

        $stored = SchedulerCalibrationProfile::query()->firstOrCreate(
            ['mode' => $mode],
            array_merge([
                'total_reviews' => 0,
                'calibration_reviews' => 0,
                'observed_recall_rate' => 0.0,
                'mean_prediction_error' => 0.0,
            ], self::DEFAULTS[$mode] ?? self::DEFAULTS['quiz'])
        );

        $stored->fill([
            'total_reviews' => $events->count(),
            'calibration_reviews' => $metrics['sample_size'],
            'observed_recall_rate' => round($metrics['observed_recall_rate'], 4),
            'mean_prediction_error' => round($metrics['mean_prediction_error'], 4),
            'recall_bias' => round($this->smooth((float) $stored->recall_bias, $metrics['target_recall_bias'], 0.35 * $quality['weight']), 4),
            'stability_scale' => round($this->smooth((float) $stored->stability_scale, $metrics['target_stability_scale'], 0.35 * $quality['weight']), 4),
            'difficulty_scale' => round($this->smooth((float) $stored->difficulty_scale, $metrics['target_difficulty_scale'], 0.30 * $quality['weight']), 4),
            'response_sensitivity' => round($this->smooth((float) $stored->response_sensitivity, $metrics['target_response_sensitivity'], 0.40 * $quality['weight']), 4),
            'confidence_sensitivity' => round($this->smooth((float) ($stored->confidence_sensitivity ?? 0.16), $metrics['target_confidence_sensitivity'], 0.35 * $quality['weight']), 4),
            'overconfidence_penalty' => round($this->smooth((float) ($stored->overconfidence_penalty ?? 0.18), $metrics['target_overconfidence_penalty'], 0.40 * $quality['weight']), 4),
            'hesitation_sensitivity' => round($this->smooth((float) ($stored->hesitation_sensitivity ?? ($mode === 'spaced' ? 0.12 : 0.08)), $metrics['target_hesitation_sensitivity'], 0.25 * $quality['weight']), 4),
            'relearn_scale' => round($this->smooth((float) $stored->relearn_scale, $metrics['target_relearn_scale'], 0.35 * $quality['weight']), 4),
            'decay_base' => round($this->smooth((float) $stored->decay_base, $metrics['target_decay_base'], 0.35 * $quality['weight']), 4),
            'last_calibrated_at' => now(),
            'calibration_meta' => [
                'fast_recall_rate' => round($metrics['fast_recall_rate'], 4),
                'slow_recall_rate' => round($metrics['slow_recall_rate'], 4),
                'repeat_failure_rate' => round($metrics['repeat_failure_rate'], 4),
                'high_confidence_failure_rate' => round($metrics['high_confidence_failure_rate'], 4),
                'low_confidence_success_rate' => round($metrics['low_confidence_success_rate'], 4),
                'high_hesitation_failure_rate' => round($metrics['high_hesitation_failure_rate'], 4),
                'confidence_counts' => $metrics['confidence_counts'],
                'data_quality' => $quality,
                'lookback_days' => $lookbackDays,
            ],
        ]);

        $stored->save();

        return $this->getProfile($mode);
    }

    public function recalibrateUserConfidence(string $mode, int $userId, int $lookbackDays = 180): object
    {
        $mode = $this->normalizeMode($mode);
        $baseProfile = $this->getProfile($mode);

        if (!$this->supportsCalibrationStorage() || $mode !== 'spaced') {
            return (object) [
                'user_id' => $userId,
                'mode' => $mode,
                'total_reviews' => 0,
                'calibration_reviews' => 0,
                'confidence_sensitivity' => (float) ($baseProfile->confidence_sensitivity ?? 0.18),
                'overconfidence_penalty' => (float) ($baseProfile->overconfidence_penalty ?? 0.22),
                'confidence_reliability' => 1.0,
                'high_confidence_accuracy' => 0.0,
                'low_confidence_accuracy' => 0.0,
                'confidence_alignment' => 0.0,
                'calibration_meta' => [],
            ];
        }

        $events = MistakeReviewEvent::query()
            ->where('review_mode', $mode)
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->where('user_id', $userId)
            ->whereNotNull('confidence_level')
            ->where('reviewed_at', '>=', now()->subDays($lookbackDays))
            ->orderBy('reviewed_at')
            ->get();

        if ($events->count() < 5) {
            return $this->getUserConfidenceProfile($mode, $userId);
        }

        $metrics = $this->deriveUserConfidenceMetrics($events, $baseProfile);
        if ($metrics['sample_size'] < 5) {
            return $this->getUserConfidenceProfile($mode, $userId);
        }
        $quality = $this->assessUserConfidenceQuality($metrics);
        if ($quality['score'] < 0.20) {
            return $this->getUserConfidenceProfile($mode, $userId);
        }

        $stored = UserSchedulerCalibrationProfile::query()->firstOrCreate(
            ['user_id' => $userId, 'mode' => $mode],
            [
                'total_reviews' => 0,
                'calibration_reviews' => 0,
                'confidence_sensitivity' => (float) ($baseProfile->confidence_sensitivity ?? 0.18),
                'overconfidence_penalty' => (float) ($baseProfile->overconfidence_penalty ?? 0.22),
                'confidence_reliability' => 1.0,
                'high_confidence_accuracy' => 0.0,
                'low_confidence_accuracy' => 0.0,
                'confidence_alignment' => 0.0,
            ]
        );

        $stored->fill([
            'total_reviews' => $events->count(),
            'calibration_reviews' => $metrics['sample_size'],
            'confidence_sensitivity' => round($this->smooth((float) $stored->confidence_sensitivity, $metrics['target_confidence_sensitivity'], 0.45 * $quality['weight']), 4),
            'overconfidence_penalty' => round($this->smooth((float) $stored->overconfidence_penalty, $metrics['target_overconfidence_penalty'], 0.45 * $quality['weight']), 4),
            'confidence_reliability' => round($this->smooth((float) $stored->confidence_reliability, $metrics['target_confidence_reliability'], 0.40 * $quality['weight']), 4),
            'high_confidence_accuracy' => round($metrics['high_confidence_accuracy'], 4),
            'low_confidence_accuracy' => round($metrics['low_confidence_accuracy'], 4),
            'confidence_alignment' => round($metrics['confidence_alignment'], 4),
            'last_calibrated_at' => now(),
            'calibration_meta' => [
                'high_confidence_sample' => $metrics['high_confidence_sample'],
                'low_confidence_sample' => $metrics['low_confidence_sample'],
                'unsure_confidence_sample' => $metrics['unsure_confidence_sample'],
                'overconfidence_rate' => round($metrics['overconfidence_rate'], 4),
                'underconfidence_success_rate' => round($metrics['underconfidence_success_rate'], 4),
                'data_quality' => $quality,
                'lookback_days' => $lookbackDays,
            ],
        ]);
        $stored->save();

        return $this->getUserConfidenceProfile($mode, $userId);
    }

    private function deriveMetrics(Collection $events, object $profile): array
    {
        $predictions = [];
        $fastOutcomes = [];
        $slowOutcomes = [];
        $repeatFailureOutcomes = [];
        $highConfidenceFailureOutcomes = [];
        $lowConfidenceSuccessOutcomes = [];
        foreach ($events->groupBy('mistake_id') as $mistakeEvents) {
            $ordered = $mistakeEvents->sortBy('reviewed_at')->values();
            $count = $ordered->count();

            for ($index = 1; $index < $count; $index++) {
                /** @var MistakeReviewEvent $previous */
                $previous = $ordered[$index - 1];
                /** @var MistakeReviewEvent $current */
                $current = $ordered[$index];

                if (!$previous->reviewed_at || !$current->reviewed_at || !$previous->resulting_stability) {
                    continue;
                }

                $elapsedDays = max(0.01, $previous->reviewed_at->diffInSeconds($current->reviewed_at) / 86400);
                $predictedRecall = $this->predictRecall((float) $previous->resulting_stability, $elapsedDays);
                $actualRecall = $current->is_correct ? 1.0 : 0.0;
                $predictions[] = [
                    'predicted' => $predictedRecall,
                    'actual' => $actualRecall,
                ];

                if ($current->response_seconds !== null) {
                    if ($current->response_seconds <= 45) {
                        $fastOutcomes[] = $actualRecall;
                    } elseif ($current->response_seconds > 90) {
                        $slowOutcomes[] = $actualRecall;
                    }
                }

                if (!$previous->is_correct) {
                    $repeatFailureOutcomes[] = $actualRecall;
                }

                if ($current->confidence_level === 'knew') {
                    $highConfidenceFailureOutcomes[] = $actualRecall;
                }

                if ($current->confidence_level === 'guessed') {
                    $lowConfidenceSuccessOutcomes[] = $actualRecall;
                }
            }
        }

        $sampleSize = count($predictions);
        $meanPredictionError = $sampleSize > 0
            ? collect($predictions)->avg(fn (array $row) => $row['actual'] - $row['predicted'])
            : 0.0;
        $observedRecallRate = $sampleSize > 0
            ? collect($predictions)->avg(fn (array $row) => $row['actual'])
            : 0.0;
        $fastRecallRate = count($fastOutcomes) > 0 ? array_sum($fastOutcomes) / count($fastOutcomes) : $observedRecallRate;
        $slowRecallRate = count($slowOutcomes) > 0 ? array_sum($slowOutcomes) / count($slowOutcomes) : $observedRecallRate;
        $repeatFailureRate = count($repeatFailureOutcomes) > 0 ? 1 - (array_sum($repeatFailureOutcomes) / count($repeatFailureOutcomes)) : 0.0;
        $highConfidenceFailureRate = count($highConfidenceFailureOutcomes) > 0 ? 1 - (array_sum($highConfidenceFailureOutcomes) / count($highConfidenceFailureOutcomes)) : 0.0;
        $lowConfidenceSuccessRate = count($lowConfidenceSuccessOutcomes) > 0 ? array_sum($lowConfidenceSuccessOutcomes) / count($lowConfidenceSuccessOutcomes) : $observedRecallRate;
        return [
            'sample_size' => $sampleSize,
            'confidence_counts' => [
                'knew' => $events->where('confidence_level', 'knew')->count(),
                'unsure' => $events->where('confidence_level', 'unsure')->count(),
                'guessed' => $events->where('confidence_level', 'guessed')->count(),
            ],
            'observed_recall_rate' => $observedRecallRate,
            'mean_prediction_error' => $meanPredictionError,
            'fast_recall_rate' => $fastRecallRate,
            'slow_recall_rate' => $slowRecallRate,
            'repeat_failure_rate' => $repeatFailureRate,
            'high_confidence_failure_rate' => $highConfidenceFailureRate,
            'low_confidence_success_rate' => $lowConfidenceSuccessRate,
            'high_hesitation_failure_rate' => null,
            'target_recall_bias' => $this->clamp((float) ($profile->recall_bias ?? 0.0) + (-$meanPredictionError * 0.12), -0.08, 0.12),
            'target_stability_scale' => $this->clamp(1.0 + ($meanPredictionError * 0.45), 0.72, 1.20),
            'target_difficulty_scale' => $this->clamp(1.0 + ((0.82 - $observedRecallRate) * 0.45), 0.95, 1.30),
            'target_response_sensitivity' => $this->clamp(0.08 + (($fastRecallRate - $slowRecallRate) * 0.25), 0.06, 0.24),
            'target_confidence_sensitivity' => $this->clamp(0.10 + (($lowConfidenceSuccessRate - $observedRecallRate) * 0.25) + ($highConfidenceFailureRate * 0.18), 0.08, 0.34),
            'target_overconfidence_penalty' => $this->clamp(0.10 + ($highConfidenceFailureRate * 0.35), 0.08, 0.36),
            'target_hesitation_sensitivity' => $this->clamp(0.05 + ($slowRecallRate * 0.12) + ($lowConfidenceSuccessRate * 0.08), 0.04, 0.22),
            'target_relearn_scale' => $this->clamp(1.0 - ($repeatFailureRate * 0.35), 0.65, 1.10),
            'target_decay_base' => $this->clamp(0.90 + ($meanPredictionError * 0.05), 0.84, 0.96),
        ];
    }

    private function deriveUserConfidenceMetrics(Collection $events, object $baseProfile): array
    {
        $highConfidence = $events->where('confidence_level', 'knew')->values();
        $lowConfidence = $events->where('confidence_level', 'guessed')->values();
        $uncertainConfidence = $events->where('confidence_level', 'unsure')->values();

        $sampleSize = $events->count();
        $highConfidenceAccuracy = $highConfidence->count() > 0
            ? (float) $highConfidence->avg(fn (MistakeReviewEvent $event) => $event->is_correct ? 1.0 : 0.0)
            : 0.0;
        $lowConfidenceAccuracy = $lowConfidence->count() > 0
            ? (float) $lowConfidence->avg(fn (MistakeReviewEvent $event) => $event->is_correct ? 1.0 : 0.0)
            : 0.0;
        $unsureAccuracy = $uncertainConfidence->count() > 0
            ? (float) $uncertainConfidence->avg(fn (MistakeReviewEvent $event) => $event->is_correct ? 1.0 : 0.0)
            : max($highConfidenceAccuracy, $lowConfidenceAccuracy);
        $overconfidenceRate = $highConfidence->count() > 0 ? 1 - $highConfidenceAccuracy : 0.0;
        $underconfidenceSuccessRate = $lowConfidence->count() > 0 ? $lowConfidenceAccuracy : 0.0;
        $confidenceAlignment = $sampleSize > 0
            ? max(-1.0, min(1.0, (($highConfidenceAccuracy - $lowConfidenceAccuracy) + (($unsureAccuracy - 0.5) * 0.35))))
            : 0.0;

        $baseConfidenceSensitivity = (float) ($baseProfile->confidence_sensitivity ?? 0.18);
        $baseOverconfidencePenalty = (float) ($baseProfile->overconfidence_penalty ?? 0.22);

        return [
            'sample_size' => $sampleSize,
            'high_confidence_sample' => $highConfidence->count(),
            'low_confidence_sample' => $lowConfidence->count(),
            'unsure_confidence_sample' => $uncertainConfidence->count(),
            'high_confidence_accuracy' => $highConfidenceAccuracy,
            'low_confidence_accuracy' => $lowConfidenceAccuracy,
            'confidence_alignment' => $confidenceAlignment,
            'overconfidence_rate' => $overconfidenceRate,
            'underconfidence_success_rate' => $underconfidenceSuccessRate,
            'target_confidence_sensitivity' => $this->clamp(
                $baseConfidenceSensitivity
                + ($confidenceAlignment * 0.08)
                + ($overconfidenceRate * 0.10)
                + ($underconfidenceSuccessRate * 0.04),
                0.08,
                0.36
            ),
            'target_overconfidence_penalty' => $this->clamp(
                $baseOverconfidencePenalty
                + ($overconfidenceRate * 0.28)
                - (max(0.0, $confidenceAlignment) * 0.05),
                0.10,
                0.42
            ),
            'target_confidence_reliability' => $this->clamp(
                1.0 + ($confidenceAlignment * 0.35) - ($overconfidenceRate * 0.20),
                0.70,
                1.30
            ),
        ];
    }

    private function assessModeCalibrationQuality(array $metrics, string $mode): array
    {
        $sampleFactor = min(1.0, ((int) ($metrics['sample_size'] ?? 0)) / 40);
        $confidenceCounts = collect($metrics['confidence_counts'] ?? []);
        $distinctConfidenceStates = $confidenceCounts->filter(fn (int $count) => $count > 0)->count();
        $confidenceCoverageFactor = $mode === 'spaced'
            ? max(0.20, $distinctConfidenceStates / 3)
            : 1.0;
        $score = round($sampleFactor * $confidenceCoverageFactor, 4);

        return [
            'score' => $score,
            'weight' => max(0.18, $score),
            'sample_factor' => round($sampleFactor, 4),
            'confidence_coverage_factor' => round($confidenceCoverageFactor, 4),
            'distinct_confidence_states' => $distinctConfidenceStates,
            'warnings' => array_values(array_filter([
                (($metrics['sample_size'] ?? 0) < 24) ? 'sample size is still small for stable recalibration' : null,
                ($mode === 'spaced' && $distinctConfidenceStates < 2) ? 'confidence coverage is too narrow for strong spaced recalibration' : null,
            ])),
        ];
    }

    private function assessUserConfidenceQuality(array $metrics): array
    {
        $sampleFactor = min(1.0, ((int) ($metrics['sample_size'] ?? 0)) / 24);
        $highSample = (int) ($metrics['high_confidence_sample'] ?? 0);
        $lowSample = (int) ($metrics['low_confidence_sample'] ?? 0);
        $coverageFactor = min(1.0, min($highSample, $lowSample) / 6);
        $score = round($sampleFactor * max(0.15, $coverageFactor), 4);

        return [
            'score' => $score,
            'weight' => max(0.20, $score),
            'sample_factor' => round($sampleFactor, 4),
            'coverage_factor' => round($coverageFactor, 4),
            'warnings' => array_values(array_filter([
                (($metrics['sample_size'] ?? 0) < 18) ? 'user confidence history is still small' : null,
                ($highSample < 4 || $lowSample < 4) ? 'high/low confidence coverage is too one-sided' : null,
            ])),
        ];
    }

    private function predictRecall(float $stability, float $elapsedDays): float
    {
        $effectiveStability = max(0.15, $stability);

        return $this->clamp(1.0 / (1.0 + ($elapsedDays / (9.0 * $effectiveStability))), 0.05, 0.99);
    }

    private function smooth(float $current, float $target, float $weight): float
    {
        return $current + (($target - $current) * $weight);
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    private function floatOrNull(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeConfidence(?string $confidence): ?string
    {
        return match ($confidence) {
            'guessed', 'unsure', 'knew' => $confidence,
            default => null,
        };
    }

    private function confidenceWeight(?string $confidence): ?float
    {
        return match ($this->normalizeConfidence($confidence)) {
            'guessed' => 0.75,
            'unsure' => 1.0,
            'knew' => 1.2,
            default => null,
        };
    }

    private function normalizeMode(string $mode): string
    {
        return match ($mode) {
            'quick' => 'quick',
            'spaced' => 'spaced',
            default => 'quiz',
        };
    }

    private static ?bool $calibrationStorageSupported = null;

    private function supportsCalibrationStorage(): bool
    {
        if (self::$calibrationStorageSupported === null) {
            self::$calibrationStorageSupported = Schema::hasTable('scheduler_calibration_profiles')
                && Schema::hasTable('mistake_review_events');
        }

        return self::$calibrationStorageSupported;
    }

    private function maybeRecalibrateUserConfidence(string $mode, int $userId): void
    {
        if (!$this->supportsUserCalibrationStorage()) {
            return;
        }

        $stored = UserSchedulerCalibrationProfile::query()->firstOrCreate(
            ['user_id' => $userId, 'mode' => $mode],
            [
                'total_reviews' => 0,
                'calibration_reviews' => 0,
                'confidence_sensitivity' => self::DEFAULTS[$mode]['confidence_sensitivity'] ?? 0.18,
                'overconfidence_penalty' => self::DEFAULTS[$mode]['overconfidence_penalty'] ?? 0.22,
                'confidence_reliability' => 1.0,
                'high_confidence_accuracy' => 0.0,
                'low_confidence_accuracy' => 0.0,
                'confidence_alignment' => 0.0,
            ]
        );

        if ($stored->last_calibrated_at && $stored->last_calibrated_at->gt(now()->subHours(6))) {
            return;
        }

        $reviewCount = MistakeReviewEvent::query()
            ->where('review_mode', $mode)
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->where('user_id', $userId)
            ->whereNotNull('confidence_level')
            ->count();

        if ($reviewCount < 12 || $reviewCount % 4 !== 0) {
            return;
        }

        Cache::lock("scheduler-calibration:{$mode}:user:{$userId}", 10)->get(function () use ($mode, $userId) {
            $this->recalibrateUserConfidence($mode, $userId);
        });
    }

    private function getUserConfidenceProfile(string $mode, int $userId): object
    {
        $defaults = self::DEFAULTS[$mode] ?? self::DEFAULTS['quiz'];

        if (!$this->supportsUserCalibrationStorage()) {
            return (object) [
                'user_id' => $userId,
                'mode' => $mode,
                'total_reviews' => 0,
                'calibration_reviews' => 0,
                'confidence_sensitivity' => (float) ($defaults['confidence_sensitivity'] ?? 0.18),
                'overconfidence_penalty' => (float) ($defaults['overconfidence_penalty'] ?? 0.22),
                'confidence_reliability' => 1.0,
                'high_confidence_accuracy' => 0.0,
                'low_confidence_accuracy' => 0.0,
                'confidence_alignment' => 0.0,
                'last_calibrated_at' => null,
                'calibration_meta' => [],
            ];
        }

        $profile = UserSchedulerCalibrationProfile::query()->firstOrCreate(
            ['user_id' => $userId, 'mode' => $mode],
            [
                'total_reviews' => 0,
                'calibration_reviews' => 0,
                'confidence_sensitivity' => (float) ($defaults['confidence_sensitivity'] ?? 0.18),
                'overconfidence_penalty' => (float) ($defaults['overconfidence_penalty'] ?? 0.22),
                'confidence_reliability' => 1.0,
                'high_confidence_accuracy' => 0.0,
                'low_confidence_accuracy' => 0.0,
                'confidence_alignment' => 0.0,
            ]
        );

        return (object) [
            'user_id' => $profile->user_id,
            'mode' => $profile->mode,
            'total_reviews' => (int) $profile->total_reviews,
            'calibration_reviews' => (int) $profile->calibration_reviews,
            'confidence_sensitivity' => (float) $profile->confidence_sensitivity,
            'overconfidence_penalty' => (float) $profile->overconfidence_penalty,
            'confidence_reliability' => (float) $profile->confidence_reliability,
            'high_confidence_accuracy' => (float) $profile->high_confidence_accuracy,
            'low_confidence_accuracy' => (float) $profile->low_confidence_accuracy,
            'confidence_alignment' => (float) $profile->confidence_alignment,
            'last_calibrated_at' => $profile->last_calibrated_at,
            'calibration_meta' => $profile->calibration_meta ?? [],
        ];
    }

    private function applyUserConfidenceProfile(array $resolved, string $mode, ?int $userId, array $defaults): array
    {
        $resolved['confidence_reliability'] = 1.0;

        if ($mode !== 'spaced' || $userId === null || !$this->supportsUserCalibrationStorage()) {
            return $resolved;
        }

        $userProfile = $this->getUserConfidenceProfile($mode, $userId);
        $resolved['confidence_sensitivity'] = (float) $userProfile->confidence_sensitivity;
        $resolved['overconfidence_penalty'] = (float) $userProfile->overconfidence_penalty;
        $resolved['confidence_reliability'] = (float) ($userProfile->confidence_reliability ?? 1.0);
        $resolved['user_confidence_profile'] = [
            'calibration_reviews' => (int) ($userProfile->calibration_reviews ?? 0),
            'high_confidence_accuracy' => (float) ($userProfile->high_confidence_accuracy ?? 0.0),
            'low_confidence_accuracy' => (float) ($userProfile->low_confidence_accuracy ?? 0.0),
            'confidence_alignment' => (float) ($userProfile->confidence_alignment ?? 0.0),
        ];

        return $resolved;
    }

    private static ?bool $userCalibrationStorageSupported = null;

    private function supportsUserCalibrationStorage(): bool
    {
        if (self::$userCalibrationStorageSupported === null) {
            self::$userCalibrationStorageSupported = Schema::hasTable('user_scheduler_calibration_profiles');
        }

        return self::$userCalibrationStorageSupported;
    }

    private function createEvent(array $attributes): void
    {
        MistakeReviewEvent::query()->create($attributes);
    }
}
