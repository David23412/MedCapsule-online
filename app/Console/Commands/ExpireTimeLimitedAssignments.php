<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;
use Illuminate\Support\Facades\Log;

class ExpireTimeLimitedAssignments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'assignments:expire-time-limited 
                           {--dry-run : Show what would be expired without actually expiring}';

    /**
     * The console command description.
     */
    protected $description = 'Expire assignments that have exceeded their time limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info("Looking for time-limited assignments that have expired...");
        
        // Find active assignments with time limits that have been exceeded
        $expiredAssignments = Assignment::where('status', Assignment::STATUS_ACTIVE)
            ->whereNotNull('duration_minutes')
            ->whereNotNull('started_at')
            ->with(['student:id,name', 'topic:id,name', 'course:id,name'])
            ->get()
            ->filter(function ($assignment) {
                return $assignment->hasTimeLimitExpired();
            });
        
        if ($expiredAssignments->isEmpty()) {
            $this->info('No time-limited assignments found that need to be expired.');
            return 0;
        }
        
        $this->info("Found {$expiredAssignments->count()} assignments to expire:");
        
        $expiredCount = 0;
        $failedCount = 0;
        
        foreach ($expiredAssignments as $assignment) {
            $minutesOverLimit = now()->diffInMinutes($assignment->started_at) - $assignment->duration_minutes;
            
            $this->line(sprintf(
                "- Assignment #%d: %s (%s) - Student: %s - %d minutes over limit",
                $assignment->id,
                $assignment->topic->name ?? 'Unknown Topic',
                $assignment->course->name ?? 'Unknown Course',
                $assignment->student->name ?? 'Unknown Student',
                $minutesOverLimit
            ));
            
            if (!$dryRun) {
                try {
                    if ($assignment->markAsExpired()) {
                        $expiredCount++;
                        Log::info('Time-limited assignment expired by scheduled command', [
                            'assignment_id' => $assignment->id,
                            'student_id' => $assignment->student_id,
                            'topic_id' => $assignment->topic_id,
                            'duration_minutes' => $assignment->duration_minutes,
                            'started_at' => $assignment->started_at,
                            'minutes_over_limit' => $minutesOverLimit
                        ]);
                    } else {
                        $failedCount++;
                        $this->warn("  Failed to expire assignment #{$assignment->id}");
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->error("  Error expiring assignment #{$assignment->id}: {$e->getMessage()}");
                    Log::error('Failed to expire assignment in scheduled command', [
                        'assignment_id' => $assignment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        if ($dryRun) {
            $this->info("\nDry run completed. {$expiredAssignments->count()} assignments would be expired.");
        } else {
            $this->info("\nExpiration completed:");
            $this->info("- Successfully expired: {$expiredCount}");
            if ($failedCount > 0) {
                $this->warn("- Failed to expire: {$failedCount}");
            }
        }
        
        return 0;
    }
}