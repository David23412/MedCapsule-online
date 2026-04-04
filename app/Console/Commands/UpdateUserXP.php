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
        
        $updatedCount = 0;
        $totalCount = 0;
        
        User::whereNull('xp')->chunk(500, function ($users) use (&$updatedCount, &$totalCount) {
            foreach ($users as $user) {
                $totalCount++;
                $calculatedXP = $user->calculateXP();
                
                $user->xp = $calculatedXP;
                $user->save();
                
                $this->line("Updated user #{$user->id} ({$user->name}) XP: 0 → {$calculatedXP}");
                $updatedCount++;
            }
        });
        
        $this->info("Completed! Updated XP for {$updatedCount} users.");
        
        return 0;
    }
} 