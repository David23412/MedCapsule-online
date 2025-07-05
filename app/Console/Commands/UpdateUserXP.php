<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class UpdateUserXP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-xp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all users\' XP values to match calculated values';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to update user XP values...');
        
        $users = User::all();
        $updatedCount = 0;
        
        foreach ($users as $user) {
            // Only update users with null XP values, to avoid overwriting 
            // accumulated XP values that already work correctly
            if ($user->xp === null) {
                $calculatedXP = $user->calculateXP();
                
                $oldXP = $user->xp ?? 0;
                $user->xp = $calculatedXP;
                $user->save();
                
                $this->line("Updated user #{$user->id} ({$user->name}) XP: {$oldXP} → {$calculatedXP}");
                $updatedCount++;
            }
        }
        
        $this->info("Completed! Updated XP for {$updatedCount} out of {$users->count()} users.");
        
        return 0;
    }
} 