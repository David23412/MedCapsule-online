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
        
        // AGGRESSIVE NOTIFICATION CLEANUP - KEEP SYSTEM FAST
        $schedule->command('notifications:cleanup --all')
                ->daily()
                ->at('02:00')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/notification-cleanup.log'));
        
        // HOURLY PERFORMANCE OPTIMIZATION - CLEAN ACTIVE USERS
        $schedule->command('notifications:cleanup')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground();
        
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
            
        // REAL-TIME ASSIGNMENT SYSTEM - No scheduled tasks needed!
        // Status updates happen INSTANTLY via model accessors and API endpoints
        
        // Keep only essential background tasks for cleanup
        // Lock overdue assignments daily (24-hour grace period) - for cleanup only
        $schedule->command('assignments:lock-overdue --grace-hours=24')
                ->daily()
                ->at('02:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/assignment-locking.log'));
        
        // Expire time-limited assignments daily - for cleanup only
        $schedule->command('assignments:expire-time-limited')
                ->daily()
                ->at('02:30')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/assignment-expiration.log'));
                
        // Send assignment reminders at different intervals
        $schedule->command('assignments:send-reminders')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/assignment-reminders.log'));
                
        // Check for course mastery achievements daily
        $schedule->command('courses:check-mastery')
                ->daily()
                ->at('06:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/course-mastery.log'));
                
        // Send mistake review reminders daily
        $schedule->command('notifications:send-mistake-reminders')
                ->daily()
                ->at('10:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/mistake-reminders.log'));

        // 🚀 AUTOMATIC ENROLLMENT MAINTENANCE - Keep all users enrolled in free courses
        $schedule->command('enroll:free-courses --force')
                ->weekly()
                ->sundays()
                ->at('03:30')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/free-course-enrollment.log'));}

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
        Commands\CleanupNotifications::class,
        Commands\TestNotificationSystem::class,
        Commands\LockOverdueAssignments::class,
        Commands\ExpireTimeLimitedAssignments::class,
        Commands\BladeStressTestCommand::class,
        Commands\ModelStressTestEngine::class,
        Commands\ModelStressMonitor::class,
        Commands\SendAssignmentReminders::class,
        Commands\CheckCourseMastery::class,
        Commands\SendMistakeReviewReminders::class,
        Commands\TestAllNotifications::class,
        Commands\NuclearNotificationOptimization::class,
        Commands\EnrollUsersInFreeCourses::class, // 🚀 AUTOMATIC FREE COURSE ENROLLMENT
    ];
}
