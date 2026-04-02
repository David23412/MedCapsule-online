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
        // Process pending payments every hour
        $schedule->command('payments:process-pending --limit=50')
                ->hourly()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/payment-processing.log'));

        // Send daily payment digest to admins
        $schedule->command('payments:send-digest')
                ->dailyAt('09:00')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/payment-digest.log'));

        // Send assignment reminders at different intervals
        $schedule->command('assignments:send-reminders')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/assignment-reminders.log'));

        // Send mistake review reminders daily
        $schedule->command('notifications:send-mistake-reminders')
                ->daily()
                ->at('10:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/mistake-reminders.log'));

        // Recalibrate spaced repetition from accumulated review outcomes
        $schedule->command('mistakes:calibrate-scheduler --mode=all --days=180')
                ->twiceDaily(3, 15)
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/mistake-scheduler-calibration.log'));

        // Send comprehensive study reminders (weak topics, streaks, inactivity)
        $schedule->command('notifications:send-reminders')
                ->daily()
                ->at('18:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/study-reminders.log'));

        // Keep survey-unlocked enrollments aligned with the current course access policy
        $schedule->command('enroll:free-courses')
                ->weekly()
                ->sundays()
                ->at('03:30')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/free-course-enrollment.log'));

        // Prune expired operational rows and internal telemetry to limit database bloat
        $schedule->command('db:prune-operational-data')
                ->daily()
                ->at('03:45')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/database-prune.log'));
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
        Commands\UpdateUserXP::class,
        Commands\UpdateStudyStreaks::class,
        Commands\ProcessPendingPayments::class,
        Commands\SendPaymentDigest::class,
        Commands\SendAssignmentReminders::class,
        Commands\SendMistakeReviewReminders::class,
        Commands\NuclearNotificationOptimization::class,
        Commands\EnrollUsersInFreeCourses::class,
        Commands\SendStudyReminders::class,
        Commands\ImportQuestionsFromExcel::class,
        Commands\PerfSnapshot::class,
        Commands\ToggleDesignProtection::class,
        Commands\CalibrateMistakeSchedulerCommand::class,
    ];
}
