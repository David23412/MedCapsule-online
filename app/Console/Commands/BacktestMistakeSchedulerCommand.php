<?php

namespace App\Console\Commands;

use App\Services\Learning\SchedulerBacktestService;
use Illuminate\Console\Command;

class BacktestMistakeSchedulerCommand extends Command
{
    protected $signature = 'mistakes:backtest-scheduler {--mode=all : quiz, quick, spaced, or all} {--days=180 : Lookback window in days} {--user= : Optional user id filter}';

    protected $description = 'Backtest scheduler recall predictions against historical review outcomes.';

    public function handle(SchedulerBacktestService $backtestService): int
    {
        $modeOption = (string) $this->option('mode');
        $lookbackDays = max(30, (int) $this->option('days'));
        $userId = $this->option('user') !== null ? max(1, (int) $this->option('user')) : null;
        $modes = $modeOption === 'all' ? ['quiz', 'quick', 'spaced'] : [$modeOption];

        foreach ($modes as $mode) {
            $report = $backtestService->backtest($mode, $lookbackDays, $userId);

            $this->line(sprintf(
                '%s: samples=%d mae=%s brier=%s predicted=%s observed=%s',
                $report['mode'],
                (int) $report['sample_size'],
                $report['mean_abs_error'] !== null ? number_format((float) $report['mean_abs_error'], 4) : 'n/a',
                $report['brier_score'] !== null ? number_format((float) $report['brier_score'], 4) : 'n/a',
                $report['predicted_recall_rate'] !== null ? number_format((float) $report['predicted_recall_rate'], 4) : 'n/a',
                $report['observed_recall_rate'] !== null ? number_format((float) $report['observed_recall_rate'], 4) : 'n/a'
            ));

            foreach (($report['confidence_segments'] ?? []) as $confidence => $segment) {
                $this->line(sprintf(
                    '  confidence[%s]: count=%d accuracy=%s predicted=%s mae=%s',
                    $confidence,
                    (int) ($segment['count'] ?? 0),
                    $segment['accuracy'] !== null ? number_format((float) $segment['accuracy'], 4) : 'n/a',
                    $segment['predicted'] !== null ? number_format((float) $segment['predicted'], 4) : 'n/a',
                    $segment['mean_abs_error'] !== null ? number_format((float) $segment['mean_abs_error'], 4) : 'n/a'
                ));
            }

            $dataQuality = $report['data_quality'] ?? [];
            $this->line(sprintf(
                '  quality: %s score=%s distinct_confidence=%d',
                (string) ($dataQuality['sufficiency'] ?? 'unknown'),
                isset($dataQuality['quality_score']) ? number_format((float) $dataQuality['quality_score'], 4) : 'n/a',
                (int) ($dataQuality['distinct_confidence_states'] ?? 0)
            ));

            foreach (($dataQuality['warnings'] ?? []) as $warning) {
                $this->line('  warning: ' . $warning);
            }
        }

        return self::SUCCESS;
    }
}
