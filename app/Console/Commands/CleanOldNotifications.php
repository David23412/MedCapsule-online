<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:clean {--days=30 : The number of days to keep notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old notifications to avoid database clutter';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Deleting notifications older than {$days} days...");
        
        $count = Notification::where('created_at', '<', $cutoffDate)->delete();
        
        $this->info("Successfully deleted {$count} old notifications.");
        
        return Command::SUCCESS;
    }
}
