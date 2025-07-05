<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\QuizAttempt;
use Carbon\Carbon;

class UpdateStudyStreaks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-streaks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all users\' study streak values based on their quiz history';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to update user study streaks...');
        
        $users = User::all();
        $updatedCount = 0;
        
        foreach ($users as $user) {
            $this->line("Processing user #{$user->id} ({$user->name})...");
            
            // Get the user's latest quiz attempt
            $lastAttempt = QuizAttempt::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();
                
            // If they have no attempts, set streak to 0
            if (!$lastAttempt) {
                if ($user->study_streak_days !== 0) {
                    $user->study_streak_days = 0;
                    $user->save();
                    $this->line("  No quiz attempts found. Set streak to 0.");
                    $updatedCount++;
                } else {
                    $this->line("  No quiz attempts found. Streak already 0.");
                }
                continue;
            }
            
            $lastAttemptDate = $lastAttempt->created_at->startOfDay();
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            
            // Handle different cases
            if ($lastAttemptDate->equalTo($today)) {
                // They did a quiz today - check for previous days in sequence
                $streak = $this->calculateConsecutiveDays($user);
                if ($user->study_streak_days != $streak) {
                    $oldStreak = $user->study_streak_days;
                    $user->study_streak_days = $streak;
                    $user->save();
                    $this->line("  Updated streak: {$oldStreak} → {$streak} (active today)");
                    $updatedCount++;
                } else {
                    $this->line("  Streak verified as {$streak} (active today)");
                }
            } else if ($lastAttemptDate->equalTo($yesterday)) {
                // Quiz taken yesterday - check for previous days
                $streak = $this->calculateConsecutiveDays($user);
                if ($user->study_streak_days != $streak) {
                    $oldStreak = $user->study_streak_days;
                    $user->study_streak_days = $streak;
                    $user->save();
                    $this->line("  Updated streak: {$oldStreak} → {$streak} (active yesterday)");
                    $updatedCount++;
                } else {
                    $this->line("  Streak verified as {$streak} (active yesterday)");
                }
            } else {
                // Last quiz attempt was more than 1 day ago - streak should be 0 or 1
                $correctStreak = $user->study_streak_days > 1 ? 1 : $user->study_streak_days;
                if ($user->study_streak_days != $correctStreak) {
                    $oldStreak = $user->study_streak_days;
                    $user->study_streak_days = $correctStreak;
                    $user->save();
                    $this->line("  Updated streak: {$oldStreak} → {$correctStreak} (inactive)");
                    $updatedCount++;
                } else {
                    $this->line("  Streak verified as {$correctStreak} (inactive)");
                }
            }
        }
        
        $this->info("Completed! Updated study streaks for {$updatedCount} out of {$users->count()} users.");
        
        return Command::SUCCESS;
    }
    
    /**
     * Calculate how many consecutive days a user has been active
     */
    private function calculateConsecutiveDays(User $user)
    {
        // Start with today - go backwards day by day and see if the user was active
        $streak = 0;
        $currentDate = now()->startOfDay();
        
        while (true) {
            // Check if the user had quiz activity on this day
            $hadActivity = QuizAttempt::where('user_id', $user->id)
                ->whereDate('created_at', $currentDate)
                ->exists();
                
            if (!$hadActivity) {
                break; // Break the streak if no activity on this day
            }
            
            $streak++;
            $currentDate->subDay(); // Go back one day
        }
        
        return max(1, $streak); // Minimum streak is 1 if they've ever done a quiz
    }
} 