<?php

namespace App\Console\Commands;

use App\Services\Learning\SchedulerCalibrationService;
use Illuminate\Console\Command;

class CalibrateMistakeSchedulerCommand extends Command
{
    protected $signature = 'mistakes:calibrate-scheduler {--mode=all : quiz, quick, spaced, or all} {--days=120 : Lookback window in days} {--user= : Optional user id for spaced confidence calibration}';

    protected $description = 'Recalibrate spaced repetition scheduler parameters from recorded review outcomes.';

    public function handle(SchedulerCalibrationService $calibrationService): int
    {
        $modeOption = (string) $this->option('mode');
        $lookbackDays = max(30, (int) $this->option('days'));
        $userId = $this->option('user') !== null ? max(1, (int) $this->option('user')) : null;
        $modes = $modeOption === 'all' ? ['quiz', 'quick', 'spaced'] : [$modeOption];

        foreach ($modes as $mode) {
            $profile = $calibrationService->recalibrateMode($mode, $lookbackDays);

            $this->line(sprintf(
                '%s: reviews=%d calibrated=%d recall_bias=%.4f stability_scale=%.4f response_sensitivity=%.4f confidence_sensitivity=%.4f decay_base=%.4f',
                $profile->mode,
                (int) ($profile->total_reviews ?? 0),
                (int) ($profile->calibration_reviews ?? 0),
                (float) ($profile->recall_bias ?? 0.0),
                (float) ($profile->stability_scale ?? 1.0),
                (float) ($profile->response_sensitivity ?? 0.12),
                (float) ($profile->confidence_sensitivity ?? 0.16),
                (float) ($profile->decay_base ?? 0.9)
            ));

            $quality = data_get($profile->calibration_meta, 'data_quality', []);
            if (!empty($quality)) {
                $this->line(sprintf(
                    '  quality: score=%s distinct_confidence=%d',
                    isset($quality['score']) ? number_format((float) $quality['score'], 4) : 'n/a',
                    (int) ($quality['distinct_confidence_states'] ?? 0)
                ));

                foreach (($quality['warnings'] ?? []) as $warning) {
                    $this->line('  warning: ' . $warning);
                }
            }

            if ($mode === 'spaced' && $userId !== null) {
                $userProfile = $calibrationService->recalibrateUserConfidence($mode, $userId, $lookbackDays);
                $this->line(sprintf(
                    'user %d spaced confidence: calibrated=%d confidence_sensitivity=%.4f overconfidence_penalty=%.4f reliability=%.4f alignment=%.4f',
                    $userId,
                    (int) ($userProfile->calibration_reviews ?? 0),
                    (float) ($userProfile->confidence_sensitivity ?? 0.18),
                    (float) ($userProfile->overconfidence_penalty ?? 0.22),
                    (float) ($userProfile->confidence_reliability ?? 1.0),
                    (float) ($userProfile->confidence_alignment ?? 0.0)
                ));

                $userQuality = data_get($userProfile->calibration_meta, 'data_quality', []);
                if (!empty($userQuality)) {
                    $this->line(sprintf(
                        '  user confidence quality: score=%s',
                        isset($userQuality['score']) ? number_format((float) $userQuality['score'], 4) : 'n/a'
                    ));

                    foreach (($userQuality['warnings'] ?? []) as $warning) {
                        $this->line('  warning: ' . $warning);
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
