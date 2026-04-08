<?php

namespace App\Services\Learning;

use App\Models\MistakeReviewEvent;
use App\Models\SchedulerCalibrationProfile;
use App\Models\User;
use App\Models\UserSchedulerCalibrationProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchedulerCalibrationCoverageService
{
    private static ?bool $hasCalibrationProfiles = null;
    private static ?bool $hasReviewEvents = null;
    private static ?bool $hasUserCalibrationProfiles = null;

    public function __construct(
        private SchedulerBacktestService $schedulerBacktestService,
    ) {
    }

    private function hasCalibrationProfiles(): bool
    {
        if (self::$hasCalibrationProfiles === null) {
            self::$hasCalibrationProfiles = Schema::hasTable('scheduler_calibration_profiles');
        }

        return self::$hasCalibrationProfiles;
    }

    private function hasReviewEvents(): bool
    {
        if (self::$hasReviewEvents === null) {
            self::$hasReviewEvents = Schema::hasTable('mistake_review_events');
        }

        return self::$hasReviewEvents;
    }

    private function hasUserCalibrationProfiles(): bool
    {
        if (self::$hasUserCalibrationProfiles === null) {
            self::$hasUserCalibrationProfiles = Schema::hasTable('user_scheduler_calibration_profiles');
        }

        return self::$hasUserCalibrationProfiles;
    }

    public function buildDashboard(): array
    {
        return [
            'mode_reports' => $this->buildModeReports(),
            'spaced_summary' => $this->buildSpacedSummary(),
            'user_coverage' => $this->buildUserCoverage(),
        ];
    }

    private function buildModeReports(): array
    {
        $reports = [];

        foreach (['quiz', 'quick', 'spaced'] as $mode) {
            $profile = $this->hasCalibrationProfiles()
                ? SchedulerCalibrationProfile::query()->firstWhere('mode', $mode)
                : null;
            $backtest = $this->schedulerBacktestService->backtest($mode, 365);

            $reports[] = [
                'mode' => $mode,
                'sample_size' => (int) ($backtest['sample_size'] ?? 0),
                'mean_abs_error' => $backtest['mean_abs_error'],
                'brier_score' => $backtest['brier_score'],
                'observed_recall_rate' => $backtest['observed_recall_rate'],
                'predicted_recall_rate' => $backtest['predicted_recall_rate'],
                'quality' => $backtest['data_quality'] ?? [],
                'profile' => $profile,
            ];
        }

        return $reports;
    }

    private function buildSpacedSummary(): array
    {
        if (!$this->hasReviewEvents()) {
            return [
                'total_reviews' => 0,
                'users_with_reviews' => 0,
                'users_with_calibrated_profiles' => 0,
                'confidence_counts' => [],
            ];
        }

        $confidenceCounts = MistakeReviewEvent::query()
            ->where('review_mode', 'spaced')
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->whereNotNull('confidence_level')
            ->select('confidence_level', DB::raw('COUNT(*) as aggregate_count'))
            ->groupBy('confidence_level')
            ->pluck('aggregate_count', 'confidence_level')
            ->map(fn ($count) => (int) $count)
            ->all();

        return [
            'total_reviews' => MistakeReviewEvent::query()->where('review_mode', 'spaced')->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)->count(),
            'users_with_reviews' => MistakeReviewEvent::query()->where('review_mode', 'spaced')->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)->distinct('user_id')->count('user_id'),
            'users_with_calibrated_profiles' => $this->hasUserCalibrationProfiles()
                ? UserSchedulerCalibrationProfile::query()->where('mode', 'spaced')->where('calibration_reviews', '>', 0)->count()
                : 0,
            'confidence_counts' => [
                'knew' => (int) ($confidenceCounts['knew'] ?? 0),
                'unsure' => (int) ($confidenceCounts['unsure'] ?? 0),
                'guessed' => (int) ($confidenceCounts['guessed'] ?? 0),
            ],
        ];
    }

    private function buildUserCoverage(): Collection
    {
        if (!$this->hasReviewEvents()) {
            return collect();
        }

        $reviewStats = MistakeReviewEvent::query()
            ->where('review_mode', 'spaced')
            ->where('event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->selectRaw("
                user_id,
                COUNT(*) as total_reviews,
                SUM(CASE WHEN confidence_level = 'knew' THEN 1 ELSE 0 END) as knew_count,
                SUM(CASE WHEN confidence_level = 'unsure' THEN 1 ELSE 0 END) as unsure_count,
                SUM(CASE WHEN confidence_level = 'guessed' THEN 1 ELSE 0 END) as guessed_count,
                MAX(reviewed_at) as last_reviewed_at
            ")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $profiles = $this->hasUserCalibrationProfiles()
            ? UserSchedulerCalibrationProfile::query()
                ->where('mode', 'spaced')
                ->get()
                ->keyBy('user_id')
            : collect();

        return User::query()
            ->whereIn('id', $reviewStats->keys())
            ->get(['id', 'name', 'email'])
            ->map(function (User $user) use ($reviewStats, $profiles) {
                $stats = $reviewStats->get($user->id);
                $profile = $profiles->get($user->id);
                $knewCount = (int) ($stats->knew_count ?? 0);
                $unsureCount = (int) ($stats->unsure_count ?? 0);
                $guessedCount = (int) ($stats->guessed_count ?? 0);
                $totalReviews = (int) ($stats->total_reviews ?? 0);
                $derivedQuality = $this->deriveUserQuality($totalReviews, $knewCount, $guessedCount);
                $profileQuality = data_get($profile?->calibration_meta, 'data_quality');
                $qualityScore = (float) ($profileQuality['score'] ?? $derivedQuality['score']);

                return [
                    'user' => $user,
                    'total_reviews' => $totalReviews,
                    'knew_count' => $knewCount,
                    'unsure_count' => $unsureCount,
                    'guessed_count' => $guessedCount,
                    'quality_score' => round($qualityScore, 4),
                    'sufficiency' => $this->resolveSufficiency($qualityScore),
                    'calibration_reviews' => (int) ($profile->calibration_reviews ?? 0),
                    'confidence_reliability' => $profile ? round((float) $profile->confidence_reliability, 4) : null,
                    'last_calibrated_at' => $profile?->last_calibrated_at,
                    'last_reviewed_at' => $stats->last_reviewed_at,
                ];
            })
            ->sortBy([
                ['quality_score', 'asc'],
                ['total_reviews', 'desc'],
            ])
            ->values()
            ->take(20);
    }

    private function deriveUserQuality(int $totalReviews, int $knewCount, int $guessedCount): array
    {
        $sampleFactor = min(1.0, $totalReviews / 24);
        $coverageFactor = min(1.0, min($knewCount, $guessedCount) / 6);
        $score = round($sampleFactor * max(0.15, $coverageFactor), 4);

        return [
            'score' => $score,
            'sufficiency' => $this->resolveSufficiency($score),
        ];
    }

    private function resolveSufficiency(float $score): string
    {
        return match (true) {
            $score >= 0.70 => 'strong',
            $score >= 0.40 => 'moderate',
            $score >= 0.20 => 'weak',
            default => 'insufficient',
        };
    }
}
