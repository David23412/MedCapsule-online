<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckLeaderboardRanks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:check-ranks {--notify-all : Notify all users, not just those with rank changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user leaderboard ranks and send notifications for significant changes';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Checking leaderboard ranks...');
        
        // Get current leaderboard data
        $currentLeaderboard = $this->getCurrentLeaderboard();
        
        if ($currentLeaderboard->isEmpty()) {
            $this->error('No users with XP found.');
            return Command::FAILURE;
        }
        
        // Get previous leaderboard data from cache
        $previousLeaderboard = Cache::get('previous_leaderboard', collect([]));
        
        // Track how many notifications will be sent
        $notificationCount = 0;
        
        // Process each user's rank
        foreach ($currentLeaderboard as $index => $user) {
            $currentRank = $index + 1;
            $userId = $user->id;
            
            // Find previous rank
            $previousRank = null;
            if ($previousLeaderboard->isNotEmpty()) {
                $previousUserData = $previousLeaderboard->firstWhere('id', $userId);
                if ($previousUserData) {
                    $previousRank = $previousUserData->rank;
                }
            }
            
            // Store current rank in user object for caching
            $user->rank = $currentRank;
            
            // Skip if no previous rank (new user) and not notifying all
            if ($previousRank === null && !$this->option('notify-all')) {
                continue;
            }
            
            // Determine if notification is needed
            $shouldNotify = false;
            
            if ($this->option('notify-all')) {
                // Notify everyone of their current rank if --notify-all flag is used
                $shouldNotify = true;
                $rankChange = $previousRank ? ($previousRank - $currentRank) : 0;
            } else if ($previousRank !== null) {
                // Calculate rank change
                $rankChange = $previousRank - $currentRank;
                
                // Determine notification criteria:
                // 1. Moved into top 3
                // 2. Moved into top 10 from outside
                // 3. Improved or declined by at least 5 positions
                $shouldNotify = 
                    ($currentRank <= 3 && $previousRank > 3) ||                  // Entered top 3
                    ($currentRank <= 10 && $previousRank > 10) ||                // Entered top 10
                    ($rankChange >= 5 || $rankChange <= -5);                    // Changed by 5+ positions
            }
            
            // Send notification if needed
            if ($shouldNotify) {
                try {
                    $user = User::find($userId);
                    if ($user) {
                        $oldRank = $previousRank ?? $currentRank;
                        $notificationService->createLeaderboardRankNotification(
                            $user,
                            $oldRank, 
                            $currentRank
                        );
                        $notificationCount++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send leaderboard notification', [
                        'user_id' => $userId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Store current leaderboard for next comparison
        Cache::put('previous_leaderboard', $currentLeaderboard, now()->addDays(7));
        
        $this->info("Leaderboard check complete. Sent $notificationCount notifications.");
        return Command::SUCCESS;
    }
    
    /**
     * Get the current leaderboard data
     * 
     * @return \Illuminate\Support\Collection
     */
    private function getCurrentLeaderboard()
    {
        return DB::table('users')
            ->select(
                'users.id',
                'users.name',
                'users.total_questions_attempted',
                'users.correct_answers_count',
                'users.xp'
            )
            ->whereRaw('users.total_questions_attempted > 0')
            ->orderBy('xp', 'desc')
            ->get();
    }
}
