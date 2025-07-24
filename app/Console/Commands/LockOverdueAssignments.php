<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;
use Illuminate\Support\Facades\Log;

class LockOverdueAssignments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'assignments:lock-overdue 
                           {--grace-hours=24 : Hours of grace period after deadline before locking}
                           {--dry-run : Show what would be locked without actually locking}';

    /**
     * The console command description.
     */
    protected $description = 'Lock assignments that are overdue beyond the grace period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $graceHours = (int) $this->option('grace-hours');
        $dryRun = $this->option('dry-run');
        
        $this->info("Looking for assignments overdue by more than {$graceHours} hours...");
        
        // Find assignments that are overdue beyond grace period
        $overdueAssignments = Assignment::where('status', Assignment::STATUS_OVERDUE)
            ->where('deadline', '<', now()->subHours($graceHours))
            ->with(['student:id,name', 'topic:id,name', 'course:id,name'])
            ->get();
            
        // Also find active assignments that are past deadline + grace period
        $expiredActiveAssignments = Assignment::where('status', Assignment::STATUS_ACTIVE)
            ->where('deadline', '<', now()->subHours($graceHours))
            ->with(['student:id,name', 'topic:id,name', 'course:id,name'])
            ->get();
            
        $allAssignmentsToLock = $overdueAssignments->merge($expiredActiveAssignments);
        
        if ($allAssignmentsToLock->isEmpty()) {
            $this->info('No assignments found that need to be locked.');
            return 0;
        }
        
        $this->info("Found {$allAssignmentsToLock->count()} assignments to lock:");
        
        $lockedCount = 0;
        $failedCount = 0;
        
        foreach ($allAssignmentsToLock as $assignment) {
            $hoursOverdue = now()->diffInHours($assignment->deadline);
            
            $this->line(sprintf(
                "- Assignment #%d: %s (%s) - Student: %s - %d hours overdue",
                $assignment->id,
                $assignment->topic->name ?? 'Unknown Topic',
                $assignment->course->name ?? 'Unknown Course',
                $assignment->student->name ?? 'Unknown Student',
                $hoursOverdue
            ));
            
            if (!$dryRun) {
                try {
                    if ($assignment->lock()) {
                        $lockedCount++;
                        Log::info('Assignment locked by scheduled command', [
                            'assignment_id' => $assignment->id,
                            'student_id' => $assignment->student_id,
                            'topic_id' => $assignment->topic_id,
                            'hours_overdue' => $hoursOverdue,
                            'grace_hours' => $graceHours
                        ]);
                    } else {
                        $failedCount++;
                        $this->warn("  Failed to lock assignment #{$assignment->id}");
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->error("  Error locking assignment #{$assignment->id}: {$e->getMessage()}");
                    Log::error('Failed to lock assignment in scheduled command', [
                        'assignment_id' => $assignment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        if ($dryRun) {
            $this->info("\nDry run completed. {$allAssignmentsToLock->count()} assignments would be locked.");
        } else {
            $this->info("\nLocking completed:");
            $this->info("- Successfully locked: {$lockedCount}");
            if ($failedCount > 0) {
                $this->warn("- Failed to lock: {$failedCount}");
            }
        }
        
        return 0;
    }
}