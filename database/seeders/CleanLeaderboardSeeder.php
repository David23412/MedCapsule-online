<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CleanLeaderboardSeeder extends Seeder
{
    public function run(): void
    {
        // Find users with "David" in their name and hide them from leaderboard
        $usersWithDavid = User::where('name', 'LIKE', '%David%')->get();
        
        $hiddenCount = 0;
        
        foreach ($usersWithDavid as $user) {
            // Set show_on_leaderboard to false to hide them
            $user->show_on_leaderboard = false;
            $user->save();
            $hiddenCount++;
            
            $this->command->info("Hidden user '{$user->name}' (ID: {$user->id}) from leaderboard.");
        }
        
        // Also clear any cached leaderboard data
        try {
            // Clear the main leaderboard cache
            \Illuminate\Support\Facades\Cache::forget('profile_leaderboard_top10_v1');
            
            // Clear individual rank caches for these users
            foreach ($usersWithDavid as $user) {
                \Illuminate\Support\Facades\Cache::forget("user_rank_optimized_{$user->id}");
            }
            
            $this->command->info('Cleared leaderboard cache entries.');
        } catch (\Exception $e) {
            $this->command->warn('Could not clear cache: ' . $e->getMessage());
        }
        
        $this->command->info("Successfully hidden {$hiddenCount} users with 'David' in their name from the leaderboard.");
        
        // Show current top 10 leaderboard for verification
        $topUsers = DB::table('users')
            ->select('name', 'xp', 'show_on_leaderboard')
            ->where('role', 3) // students only
            ->where('xp', '>', 0)
            ->where('show_on_leaderboard', true)
            ->orderByDesc('xp')
            ->limit(10)
            ->get();
            
        $this->command->info('Current Top 10 Leaderboard (after cleanup):');
        foreach ($topUsers as $index => $user) {
            $this->command->info(($index + 1) . ". {$user->name} - {$user->xp} XP");
        }
    }
}
