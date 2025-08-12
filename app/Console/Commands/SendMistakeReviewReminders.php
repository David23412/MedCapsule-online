<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Mistake;
use App\Events\MistakesNeedReview;
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
        $usersWithMistakes = User::whereHas('mistakes', function ($query) {
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
                    // Get the actual mistakes for the event
                    $mistakes = $user->mistakes()
                        ->where('mastered', false)
                        ->where('skipped', false)
                        ->get();
                    
                    // Fire the event to trigger notification
                    event(new MistakesNeedReview($user, $mistakeCount, $mistakes, []));
                    
                    $this->line("Sent reminder to user #{$user->id} ({$user->name}) - {$mistakeCount} mistakes to review");
                    $reminderCount++;
                    
                    Log::info('Mistake review reminder sent', [
                        'user_id' => $user->id,
                        'mistake_count' => $mistakeCount,
                        'command' => 'SendMistakeReviewReminders'
                    ]);
                } catch (\Exception $e) {
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
