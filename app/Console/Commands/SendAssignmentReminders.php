<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;
use App\Services\NotificationTriggerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAssignmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'assignments:send-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send assignment due date and overdue reminders to students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔔 Starting assignment reminder notifications...');
        
        $now = Carbon::now();
        $remindersSent = 0;
        
        // Get all assignments that have due dates and are assigned to students
        $assignments = Assignment::whereNotNull('due_date')
            ->whereNotNull('student_id')
            ->with(['student'])
            ->get();
            
        $this->info("📋 Found {$assignments->count()} assignments with due dates");
        
        foreach ($assignments as $assignment) {
            if (!$assignment->student) {
                continue;
            }
            
            $dueDate = Carbon::parse($assignment->due_date);
            $hoursUntilDue = $now->diffInHours($dueDate, false);
            
            // Skip if assignment is completed
            if ($assignment->status === 'completed') {
                continue;
            }
            
            // Determine if we should send a reminder based on time intervals
            $shouldSendReminder = false;
            $reminderType = '';
            
            if ($hoursUntilDue < 0) {
                // Assignment is overdue
                $hoursOverdue = abs($hoursUntilDue);
                
                if ($hoursOverdue <= 1) {
                    $shouldSendReminder = $this->shouldSendHourlyReminder($assignment, 'overdue_1h');
                    $reminderType = 'overdue_1h';
                } elseif ($hoursOverdue <= 6) {
                    $shouldSendReminder = $this->shouldSendHourlyReminder($assignment, 'overdue_6h');
                    $reminderType = 'overdue_6h';
                } elseif ($hoursOverdue <= 24) {
                    $shouldSendReminder = $this->shouldSendDailyReminder($assignment, 'overdue_24h');
                    $reminderType = 'overdue_24h';
                } else {
                    $shouldSendReminder = $this->shouldSendDailyReminder($assignment, 'overdue_days');
                    $reminderType = 'overdue_days';
                }
            } else {
                // Assignment is due soon
                if ($hoursUntilDue <= 1) {
                    $shouldSendReminder = $this->shouldSendHourlyReminder($assignment, 'due_1h');
                    $reminderType = 'due_1h';
                } elseif ($hoursUntilDue <= 3) {
                    $shouldSendReminder = $this->shouldSendHourlyReminder($assignment, 'due_3h');
                    $reminderType = 'due_3h';
                } elseif ($hoursUntilDue <= 6) {
                    $shouldSendReminder = $this->shouldSend6HourReminder($assignment, 'due_6h');
                    $reminderType = 'due_6h';
                } elseif ($hoursUntilDue <= 12) {
                    $shouldSendReminder = $this->shouldSend12HourReminder($assignment, 'due_12h');
                    $reminderType = 'due_12h';
                } elseif ($hoursUntilDue <= 24) {
                    $shouldSendReminder = $this->shouldSendDailyReminder($assignment, 'due_24h');
                    $reminderType = 'due_24h';
                } elseif ($hoursUntilDue <= 48) {
                    $shouldSendReminder = $this->shouldSendDailyReminder($assignment, 'due_48h');
                    $reminderType = 'due_48h';
                }
            }
            
            if ($shouldSendReminder) {
                try {
                    NotificationTriggerService::onAssignmentOverdueReminder(
                        $assignment->student,
                        $assignment,
                        $hoursUntilDue
                    );
                    
                    // Update the last reminder sent timestamp
                    $assignment->update([
                        'last_reminder_sent' => $now,
                        'last_reminder_type' => $reminderType
                    ]);
                    
                    $remindersSent++;
                    
                    $this->info("✅ Sent {$reminderType} reminder for '{$assignment->title}' to {$assignment->student->name}");
                    
                } catch (\Exception $e) {
                    $this->error("❌ Failed to send reminder for assignment {$assignment->id}: {$e->getMessage()}");
                    Log::error('Assignment reminder failed', [
                        'assignment_id' => $assignment->id,
                        'student_id' => $assignment->student_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        $this->info("🎉 Assignment reminder process complete. Sent {$remindersSent} reminders.");
        
        
        return 0;
    }
    
    /**
     * Check if we should send hourly reminders (for urgent situations)
     */
    private function shouldSendHourlyReminder(Assignment $assignment, string $type): bool
    {
        if (!$assignment->last_reminder_sent) {
            return true;
        }
        
        $lastSent = Carbon::parse($assignment->last_reminder_sent);
        $hoursSinceLastReminder = $lastSent->diffInHours(Carbon::now());
        
        // Send hourly reminders for urgent situations
        return $hoursSinceLastReminder >= 1;
    }
    
    /**
     * Check if we should send 6-hour reminders
     */
    private function shouldSend6HourReminder(Assignment $assignment, string $type): bool
    {
        if (!$assignment->last_reminder_sent || $assignment->last_reminder_type !== $type) {
            return true;
        }
        
        $lastSent = Carbon::parse($assignment->last_reminder_sent);
        $hoursSinceLastReminder = $lastSent->diffInHours(Carbon::now());
        
        // Send every 3 hours for 6-hour window
        return $hoursSinceLastReminder >= 3;
    }
    
    /**
     * Check if we should send 12-hour reminders
     */
    private function shouldSend12HourReminder(Assignment $assignment, string $type): bool
    {
        if (!$assignment->last_reminder_sent || $assignment->last_reminder_type !== $type) {
            return true;
        }
        
        $lastSent = Carbon::parse($assignment->last_reminder_sent);
        $hoursSinceLastReminder = $lastSent->diffInHours(Carbon::now());
        
        // Send every 6 hours for 12-hour window
        return $hoursSinceLastReminder >= 6;
    }
    
    /**
     * Check if we should send daily reminders
     */
    private function shouldSendDailyReminder(Assignment $assignment, string $type): bool
    {
        if (!$assignment->last_reminder_sent || $assignment->last_reminder_type !== $type) {
            return true;
        }
        
        $lastSent = Carbon::parse($assignment->last_reminder_sent);
        $daysSinceLastReminder = $lastSent->diffInDays(Carbon::now());
        
        // Send daily reminders
        return $daysSinceLastReminder >= 1;
    }
}