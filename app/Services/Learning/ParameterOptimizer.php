<?php

namespace App\Services\Learning;

use App\Models\MistakeReviewEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ParameterOptimizer
{
    private const LOOKBACK_DAYS = 180;

    private const MIN_USER_REVIEW_COUNTS = [
        'quiz' => 12,
        'quick' => 12,
        'spaced' => 12,
    ];

    public function getOptimizedParameters(int $userId, string $mode = 'spaced'): array
    {
        $mode = $this->normalizeMode($mode);
        $cacheKey = "optimized_params:{$userId}:{$mode}";

        return Cache::remember($cacheKey, 3600, function () use ($userId, $mode) {
            if ($this->fetchReviewCount($userId, $mode, self::LOOKBACK_DAYS) < (self::MIN_USER_REVIEW_COUNTS[$mode] ?? 12)) {
                return $this->currentLiveParameters($userId, $mode);
            }

            $cached = $this->getCachedOptimization($userId, $mode);
            if ($cached !== null) {
                return $cached;
            }

            return $this->optimizeParameters($userId, $mode, self::LOOKBACK_DAYS);
        });
    }

    public function refreshModeOptimization(string $mode = 'spaced', int $lookbackDays = self::LOOKBACK_DAYS): array
    {
        $mode = $this->normalizeMode($mode);
        $calibrationService = app(SchedulerCalibrationService::class);
        $calibrationService->recalibrateMode($mode, $lookbackDays);

        return $this->snapshotFromProfile($calibrationService->getProfile($mode));
    }

    public function scheduleOptimization(int $userId, string $mode = 'spaced'): void
    {
        $mode = $this->normalizeMode($mode);
        Cache::forget("optimized_params:{$userId}:{$mode}");

        if (!DB::getSchemaBuilder()->hasTable('scheduler_parameter_optimizations')) {
            return;
        }

        DB::table('scheduler_parameter_optimizations')
            ->where('user_id', $userId)
            ->where('mode', $mode)
            ->delete();
    }

    protected function optimizeParameters(int $userId, string $mode, int $lookbackDays): array
    {
        $calibrationService = app(SchedulerCalibrationService::class);
        $calibrationService->recalibrateMode($mode, $lookbackDays);

        if ($mode === 'spaced' && $this->fetchConfidenceReviewCount($userId, $lookbackDays) >= 12) {
            $calibrationService->recalibrateUserConfidence($mode, $userId, $lookbackDays);
        }

        $parameters = $this->currentLiveParameters($userId, $mode);
        $sampleSize = $this->fetchReviewCount($userId, $mode, $lookbackDays);
        $lossMetric = $this->resolveLossMetric($mode, $userId, $lookbackDays);

        $this->storeOptimization($userId, $mode, $parameters, $sampleSize, $lossMetric);

        return $parameters;
    }

    protected function currentLiveParameters(int $userId, string $mode): array
    {
        $profile = app(SchedulerCalibrationService::class)->getProfile($mode, $mode === 'spaced' ? $userId : null);

        return $this->snapshotFromProfile($profile);
    }

    protected function snapshotFromProfile(object $profile): array
    {
        return [
            'mode' => (string) ($profile->mode ?? 'quiz'),
            'user_id' => $profile->user_id ?? null,
            'recall_bias' => (float) ($profile->recall_bias ?? 0.0),
            'stability_scale' => (float) ($profile->stability_scale ?? 1.0),
            'difficulty_scale' => (float) ($profile->difficulty_scale ?? 1.0),
            'response_sensitivity' => (float) ($profile->response_sensitivity ?? 0.12),
            'confidence_sensitivity' => (float) ($profile->confidence_sensitivity ?? 0.16),
            'overconfidence_penalty' => (float) ($profile->overconfidence_penalty ?? 0.18),
            'hesitation_sensitivity' => (float) ($profile->hesitation_sensitivity ?? 0.0),
            'relearn_scale' => (float) ($profile->relearn_scale ?? 1.0),
            'decay_base' => (float) ($profile->decay_base ?? 0.9),
            'confidence_reliability' => (float) ($profile->confidence_reliability ?? 1.0),
            'total_reviews' => (int) ($profile->total_reviews ?? 0),
            'calibration_reviews' => (int) ($profile->calibration_reviews ?? 0),
            'observed_recall_rate' => (float) ($profile->observed_recall_rate ?? 0.0),
            'mean_prediction_error' => (float) ($profile->mean_prediction_error ?? 0.0),
            'last_calibrated_at' => $profile->last_calibrated_at ?? null,
        ];
    }

    protected function getCachedOptimization(int $userId, string $mode): ?array
    {
        if (!DB::getSchemaBuilder()->hasTable('scheduler_parameter_optimizations')) {
            return null;
        }

        $cached = DB::table('scheduler_parameter_optimizations')
            ->where('user_id', $userId)
            ->where('mode', $mode)
            ->where('optimized_at', '>=', now()->subDays(30))
            ->orderBy('optimized_at', 'desc')
            ->first();

        if (!$cached || !$cached->parameters) {
            return null;
        }

        $decoded = json_decode($cached->parameters, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function fetchReviewCount(int $userId, string $mode, int $lookbackDays): int
    {
        if (!DB::getSchemaBuilder()->hasTable('mistake_review_events')) {
            return 0;
        }

        return (int) DB::table('mistake_review_events')
            ->where('user_id', $userId)
            ->where('review_mode', $mode)
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->where('reviewed_at', '>=', now()->subDays($lookbackDays))
            ->count();
    }

    protected function fetchConfidenceReviewCount(int $userId, int $lookbackDays): int
    {
        if (!DB::getSchemaBuilder()->hasTable('mistake_review_events')) {
            return 0;
        }

        return (int) DB::table('mistake_review_events')
            ->where('user_id', $userId)
            ->where('review_mode', 'spaced')
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->whereNotNull('confidence_level')
            ->where('reviewed_at', '>=', now()->subDays($lookbackDays))
            ->count();
    }

    protected function resolveLossMetric(string $mode, int $userId, int $lookbackDays): float
    {
        $backtest = app(SchedulerBacktestService::class)->backtest(
            $mode,
            $lookbackDays,
            $mode === 'spaced' ? $userId : null
        );

        return round((float) ($backtest['brier_score'] ?? $backtest['mean_abs_error'] ?? 0.0), 4);
    }

    protected function storeOptimization(int $userId, string $mode, array $parameters, int $sampleSize, float $lossMetric): void
    {
        if (!DB::getSchemaBuilder()->hasTable('scheduler_parameter_optimizations')) {
            return;
        }

        DB::table('scheduler_parameter_optimizations')->insert([
            'user_id' => $userId,
            'mode' => $mode,
            'parameters' => json_encode($parameters),
            'sample_size' => $sampleSize,
            'log_loss' => $lossMetric,
            'optimized_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function normalizeMode(string $mode): string
    {
        return match ($mode) {
            'quick' => 'quick',
            'spaced' => 'spaced',
            default => 'quiz',
        };
    }
}
