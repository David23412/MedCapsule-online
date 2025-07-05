<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * These schedules are run in a default environment.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        
        // Run notification cleanup daily at midnight
        $schedule->command('notifications:clean --days=14')->daily()->at('00:00');
        
        // Check leaderboard ranks daily and send notifications
        $schedule->command('leaderboard:check-ranks')->daily()->at('08:00');
        
        // Process pending payments every hour
        $schedule->command('payments:process-pending --limit=50')
                ->hourly()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/payment-processing.log'));
        
        // Process expedited payments every 15 minutes
        $schedule->command('payments:process-pending --limit=20 --expedite-only')
                ->everyFifteenMinutes()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/payment-expedited.log'));
        
        // Clean up payment proof images weekly to manage storage
        $schedule->command('payments:cleanup-proofs --limit=200')
                ->weekly()
                ->sundays()
                ->at('02:00')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/payment-cleanup.log'));
        
        // Send daily payment digest to admins
        $schedule->command('payments:send-digest')
                ->dailyAt('09:00')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/payment-digest.log'));
                
        // Summarize old payment logs monthly to prevent database bloat
        $schedule->command('payments:summarize-logs --days=90 --batch=200 --force')
                ->monthly()
                ->sundays()
                ->at('03:00')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/payment-logs-summary.log'));

        // Clean up old quiz attempts weekly
        $schedule->command('quiz:cleanup-attempts')
            ->weekly()
            ->sundays()
            ->at('01:00')
            ->runInBackground()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CheckLeaderboardRanks::class,
        Commands\UpdateUserXP::class,
        Commands\MigrateToAccumulativeXP::class,
        Commands\UpdateStudyStreaks::class,
        Commands\ProcessPendingPayments::class,
        Commands\CleanupPaymentProofs::class,
        Commands\SendPaymentDigest::class,
        Commands\SummarizePaymentLogs::class,
    ];
} 