<?php

namespace App\Services\Learning;

use App\Models\MistakeReviewEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SchedulerBacktestService
{
    public function backtest(string $mode, int $lookbackDays = 180, ?int $userId = null): array
    {
        if (!$this->supportsBacktesting()) {
            return $this->emptyReport($mode, $lookbackDays, $userId);
        }

        $events = MistakeReviewEvent::query()
            ->where('review_mode', $this->normalizeMode($mode))
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->where('reviewed_at', '>=', now()->subDays($lookbackDays))
            ->orderBy('mistake_id')
            ->orderBy('reviewed_at')
            ->get();

        if ($events->count() < 2) {
            return $this->emptyReport($mode, $lookbackDays, $userId);
        }

        $samples = $this->buildSamples($events);
        if ($samples->isEmpty()) {
            return $this->emptyReport($mode, $lookbackDays, $userId);
        }

        return [
            'mode' => $this->normalizeMode($mode),
            'user_id' => $userId,
            'lookback_days' => $lookbackDays,
            'sample_size' => $samples->count(),
            'mean_abs_error' => round((float) $samples->avg(fn (array $sample) => abs($sample['error'])), 4),
            'brier_score' => round((float) $samples->avg(fn (array $sample) => ($sample['predicted'] - $sample['actual']) ** 2), 4),
            'mean_prediction_error' => round((float) $samples->avg(fn (array $sample) => $sample['error']), 4),
            'observed_recall_rate' => round((float) $samples->avg(fn (array $sample) => $sample['actual']), 4),
            'predicted_recall_rate' => round((float) $samples->avg(fn (array $sample) => $sample['predicted']), 4),
            'confidence_segments' => $this->confidenceSegments($samples),
            'calibration_buckets' => $this->calibrationBuckets($samples),
            'state_segments' => $this->stateSegments($samples),
            'data_quality' => $this->dataQuality($samples),
        ];
    }

    private function buildSamples(Collection $events): Collection
    {
        $samples = collect();

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
                $predicted = $this->predictRecall(
                    (float) $previous->resulting_stability,
                    (float) ($previous->decay_base ?? 0.9),
                    $elapsedDays
                );
                $actual = $current->is_correct ? 1.0 : 0.0;

                $samples->push([
                    'predicted' => $predicted,
                    'actual' => $actual,
                    'error' => $actual - $predicted,
                    'confidence' => $current->confidence_level,
                    'state' => $this->resolveStateLabel($previous),
                    'elapsed_days' => round($elapsedDays, 4),
                ]);
            }
        }

        return $samples->values();
    }

    private function confidenceSegments(Collection $samples): array
    {
        return collect(['knew', 'unsure', 'guessed'])
            ->mapWithKeys(function (string $confidence) use ($samples) {
                $segment = $samples->where('confidence', $confidence)->values();

                return [$confidence => [
                    'count' => $segment->count(),
                    'accuracy' => $segment->isNotEmpty() ? round((float) $segment->avg('actual'), 4) : null,
                    'predicted' => $segment->isNotEmpty() ? round((float) $segment->avg('predicted'), 4) : null,
                    'mean_abs_error' => $segment->isNotEmpty() ? round((float) $segment->avg(fn (array $sample) => abs($sample['error'])), 4) : null,
                ]];
            })
            ->all();
    }

    private function calibrationBuckets(Collection $samples): array
    {
        $buckets = [
            '0.00-0.39' => [0.00, 0.39],
            '0.40-0.59' => [0.40, 0.59],
            '0.60-0.79' => [0.60, 0.79],
            '0.80-1.00' => [0.80, 1.00],
        ];

        $report = [];

        foreach ($buckets as $label => [$min, $max]) {
            $segment = $samples->filter(fn (array $sample) => $sample['predicted'] >= $min && $sample['predicted'] <= $max)->values();
            $report[$label] = [
                'count' => $segment->count(),
                'predicted' => $segment->isNotEmpty() ? round((float) $segment->avg('predicted'), 4) : null,
                'actual' => $segment->isNotEmpty() ? round((float) $segment->avg('actual'), 4) : null,
            ];
        }

        return $report;
    }

    private function stateSegments(Collection $samples): array
    {
        return $samples->groupBy('state')
            ->map(fn (Collection $segment) => [
                'count' => $segment->count(),
                'accuracy' => round((float) $segment->avg('actual'), 4),
                'predicted' => round((float) $segment->avg('predicted'), 4),
                'mean_abs_error' => round((float) $segment->avg(fn (array $sample) => abs($sample['error'])), 4),
            ])
            ->sortKeys()
            ->all();
    }

    private function dataQuality(Collection $samples): array
    {
        $sampleSize = $samples->count();
        $confidenceCounts = collect(['knew', 'unsure', 'guessed'])
            ->mapWithKeys(fn (string $confidence) => [$confidence => $samples->where('confidence', $confidence)->count()])
            ->all();
        $distinctConfidenceStates = collect($confidenceCounts)->filter(fn (int $count) => $count > 0)->count();
        $qualityScore = min(1.0, ($sampleSize / 30))
            * max(0.20, $distinctConfidenceStates / 3);
        $warnings = [];

        if ($sampleSize < 12) {
            $warnings[] = 'sample size is too small for strong scientific confidence';
        }

        if ($distinctConfidenceStates < 2) {
            $warnings[] = 'confidence coverage is too narrow';
        }

        return [
            'sample_size' => $sampleSize,
            'confidence_counts' => $confidenceCounts,
            'distinct_confidence_states' => $distinctConfidenceStates,
            'quality_score' => round($qualityScore, 4),
            'sufficiency' => match (true) {
                $qualityScore >= 0.70 => 'strong',
                $qualityScore >= 0.40 => 'moderate',
                $qualityScore >= 0.20 => 'weak',
                default => 'insufficient',
            },
            'warnings' => $warnings,
        ];
    }

    private function resolveStateLabel(MistakeReviewEvent $event): string
    {
        $intervalDays = (int) ($event->resulting_interval_days ?? 0);

        if ($intervalDays <= 0) {
            return 'same_day';
        }

        if ($intervalDays <= 2) {
            return 'short_interval';
        }

        if ($intervalDays <= 7) {
            return 'medium_interval';
        }

        return 'long_interval';
    }

    private function predictRecall(float $stability, float $decayBase, float $elapsedDays): float
    {
        $effectiveStability = max(0.15, $stability);

        return round(
            max(0.05, min(0.99, 1.0 / (1.0 + ($elapsedDays / (9.0 * $effectiveStability))))),
            4
        );
    }

    private function normalizeMode(string $mode): string
    {
        return match ($mode) {
            'quick' => 'quick',
            'spaced' => 'spaced',
            default => 'quiz',
        };
    }

    private function supportsBacktesting(): bool
    {
        return Schema::hasTable('mistake_review_events');
    }

    private function emptyReport(string $mode, int $lookbackDays, ?int $userId): array
    {
        return [
            'mode' => $this->normalizeMode($mode),
            'user_id' => $userId,
            'lookback_days' => $lookbackDays,
            'sample_size' => 0,
            'mean_abs_error' => null,
            'brier_score' => null,
            'mean_prediction_error' => null,
            'observed_recall_rate' => null,
            'predicted_recall_rate' => null,
            'confidence_segments' => [],
            'calibration_buckets' => [],
            'state_segments' => [],
            'data_quality' => [
                'sample_size' => 0,
                'confidence_counts' => [],
                'distinct_confidence_states' => 0,
                'quality_score' => 0.0,
                'sufficiency' => 'insufficient',
                'warnings' => ['not enough review history to backtest'],
            ],
        ];
    }
}
