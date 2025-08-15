<?php

namespace App\Console\Commands;

use App\Services\Audit\Components\ObserverDaemon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Observer Daemon Command
 * 
 * Artisan command to start the 72-hour continuous monitoring
 */
class ObserverDaemonCommand extends Command
{
    protected $signature = 'audit:observer-daemon 
                           {--duration=72 : Monitoring duration in hours}
                           {--interval=30 : Sampling interval in seconds}
                           {--background : Run in background mode}';

    protected $description = 'Start the Observer Daemon for 72-hour continuous production monitoring';

    private ObserverDaemon $observerDaemon;

    public function __construct()
    {
        parent::__construct();
        $this->observerDaemon = new ObserverDaemon();
    }

    public function handle(): int
    {
        $duration = (int) $this->option('duration');
        $interval = (int) $this->option('interval');
        $background = $this->option('background');

        $this->info("🚀 Starting Observer Daemon");
        $this->info("📊 Duration: {$duration} hours");
        $this->info("⚡ Sampling interval: {$interval} seconds");
        $this->info("🔄 Background mode: " . ($background ? 'enabled' : 'disabled'));

        if ($background) {
            $this->info("🌙 Running in background mode - check logs for updates");
            Log::info('Observer Daemon started in background mode', [
                'duration_hours' => $duration,
                'sampling_interval' => $interval
            ]);
        }

        try {
            // Configure daemon settings
            if ($duration !== 72) {
                $this->warn("⚠️ Non-standard duration: {$duration} hours (recommended: 72 hours)");
            }

            // Start monitoring
            $this->observerDaemon->startContinuousMonitoring();

            $this->info("✅ Observer Daemon monitoring completed successfully");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Observer Daemon failed: " . $e->getMessage());
            Log::error('Observer Daemon failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}