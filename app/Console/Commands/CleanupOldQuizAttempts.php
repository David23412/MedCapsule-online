<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Log;

class CleanupOldQuizAttempts extends Command
{
    protected $signature = 'quiz:cleanup-attempts {--days=90 : Number of days to keep}';
    protected $description = 'Clean up old quiz attempts to prevent database bloat';

    public function handle()
    {
        $daysToKeep = $this->option('days');
        $this->info("Starting cleanup of quiz attempts older than {$daysToKeep} days...");

        try {
            $cutoffDate = now()->subDays($daysToKeep);
            
            // Get count before deletion for reporting
            $countBefore = QuizAttempt::where('created_at', '<', $cutoffDate)->count();
            
            // Delete old attempts
            $deleted = QuizAttempt::where('created_at', '<', $cutoffDate)->delete();
            
            $this->info("Successfully deleted {$deleted} old quiz attempts.");
            Log::info('Cleaned up old quiz attempts', [
                'deleted_count' => $deleted,
                'days_kept' => $daysToKeep,
                'cutoff_date' => $cutoffDate->toDateTimeString()
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to clean up old quiz attempts: {$e->getMessage()}");
            Log::error('Failed to clean up old quiz attempts', [
                'error' => $e->getMessage(),
                'days_kept' => $daysToKeep
            ]);
            
            return Command::FAILURE;
        }
    }
} 