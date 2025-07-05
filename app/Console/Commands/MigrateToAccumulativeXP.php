<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class MigrateToAccumulativeXP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xp:migrate-to-accumulative';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate users to the new accumulative XP system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Migrating users to accumulative XP system...');
        
        $users = User::all();
        $updatedCount = 0;
        
        foreach ($users as $user) {
            // Calculate XP for each user using the old formula
            $calculatedXP = $user->calculateXP();
            
            // Compare with current value
            if ($user->xp === null) {
                // User has no XP yet, set it to the calculated value
                $user->xp = $calculatedXP;
                $user->save();
                
                $this->line("Set initial XP for user #{$user->id} ({$user->name}): {$calculatedXP}");
                $updatedCount++;
            } else if ($user->xp < $calculatedXP) {
                // Current XP is less than what it should be according to their stats
                // Update to the calculated value to ensure they don't lose progress
                $oldXP = $user->xp;
                $user->xp = $calculatedXP;
                $user->save();
                
                $this->line("Updated user #{$user->id} ({$user->name}) XP: {$oldXP} → {$calculatedXP}");
                $updatedCount++;
            } else {
                // Current XP is already greater than or equal to the calculated value
                // This could be due to accumulated XP from multiple quizzes
                // We keep their current value to preserve progress
                $this->line("Kept existing XP for user #{$user->id} ({$user->name}): {$user->xp}");
            }
        }
        
        $this->info("Completed! Updated XP for {$updatedCount} out of {$users->count()} users.");
        
        return Command::SUCCESS;
    }
} 