<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendInitialLeaderboardNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:notify {--top=10 : How many top users to notify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send one-time notifications to users about their leaderboard position';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $topN = intval($this->option('top'));
        $this->info("Sending notifications to top $topN users on the leaderboard...");
        
        // Get the top N users
        $leaderboard = DB::table('users')
            ->select(
                'users.id',
                'users.name',
                'users.total_questions_attempted',
                'users.correct_answers_count',
                'users.xp'
            )
            ->whereRaw('users.total_questions_attempted > 0')
            ->orderBy('xp', 'desc')
            ->limit($topN)
            ->get();
        
        if ($leaderboard->isEmpty()) {
            $this->error('No users with XP found.');
            return Command::FAILURE;
        }
        
        $notificationCount = 0;
        
        // Process each user's rank
        foreach ($leaderboard as $index => $userData) {
            $currentRank = $index + 1;
            $userId = $userData->id;
            
            try {
                $user = User::find($userId);
                if ($user) {
                    // For initial notification, we're pretending they moved up
                    // This creates a positive notification experience
                    $oldRank = $currentRank + 1;
                    
                    $notificationService->createLeaderboardRankNotification(
                        $user,
                        $oldRank, 
                        $currentRank
                    );
                    $notificationCount++;
                    
                    $this->info("Sent notification to {$user->name}: Rank #{$currentRank}");
                }
            } catch (\Exception $e) {
                Log::error('Failed to send initial leaderboard notification', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
                $this->error("Failed to send notification to user ID $userId: {$e->getMessage()}");
            }
        }
        
        $this->info("Sent $notificationCount leaderboard notifications.");
        return Command::SUCCESS;
    }
} 