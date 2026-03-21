<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\NotificationTriggerService;
use App\Services\PerformanceTrackingService;
use Illuminate\Support\Facades\Log;

class SendMistakeReviewReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-mistake-reminders {--force : Force send even if recently sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders to users who have mistakes that need review';

    private PerformanceTrackingService $performanceTrackingService;

    public function __construct(PerformanceTrackingService $performanceTrackingService)
    {
        $this->performanceTrackingService = $performanceTrackingService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to send mistake review reminders...');

        $force = $this->option('force');
        $reminderCount = 0;

        // Get users with unmastered mistakes
        $usersWithMistakes = User::where('role', User::ROLE_STUDENT)->whereHas('mistakes', function ($query) {
            $query->where('mastered', false)
                ->where('skipped', false);
        })->get();

        foreach ($usersWithMistakes as $user) {
            $mistakeCount = $user->mistakes()
                ->where('mastered', false)
                ->where('skipped', false)
                ->count();

            if ($mistakeCount > 0) {
                try {
                    $snapshot = $this->performanceTrackingService->getReminderSnapshot($user);

                    if (!$force && !$snapshot['should_send_mistake_reminder']) {
                        continue;
                    }

                    NotificationTriggerService::onMistakesNeedReview($user, $mistakeCount, [
                        'priority' => $mistakeCount >= 15 ? 'high' : 'normal',
                        'force' => $force,
                    ]);

                    $this->line("Sent reminder to user #{$user->id} ({$user->name}) - {$mistakeCount} mistakes to review");
                    $reminderCount++;

                    Log::info('Mistake review reminder sent', [
                        'user_id' => $user->id,
                        'mistake_count' => $mistakeCount,
                        'command' => 'SendMistakeReviewReminders'
                    ]);
                }
                catch (\Exception $e) {
                    $this->error("Failed to send reminder to user #{$user->id}: " . $e->getMessage());
                    Log::error('Failed to send mistake review reminder', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $this->info("Completed! Sent {$reminderCount} mistake review reminders.");

        return Command::SUCCESS;
    }
}