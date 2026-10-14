<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Services\NotificationTriggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendAssignmentReminders extends Command
{
    protected $signature = 'assignments:send-reminders';
    protected $description = 'Send assignment due date and overdue reminders to students';

    public function handle(): int
    {
        $sent = 0;
        $failed = 0;
        $now = now();

        Assignment::query()
            ->whereNotNull('deadline')
            ->where('deadline', '<=', $now->copy()->addHours(3))
            ->whereHas('assignedUsers')
            ->with(['assignedUsers', 'attempts'])
            ->chunkById(100, function ($assignments) use ($now, &$sent, &$failed): void {
                foreach ($assignments as $assignment) {
                    foreach ($assignment->assignedUsers as $student) {
                        // Quiz attempts are the current completion contract.
                        if ($assignment->hasUserAttempted($student->id)) {
                            continue;
                        }

                        $deadline = $student->pivot->extended_deadline
                            ? \Carbon\Carbon::parse($student->pivot->extended_deadline)
                            : $assignment->deadline;
                        $hours = $now->diffInHours($deadline, false);
                        if ($hours > 3) {
                            continue;
                        }

                        $type = $hours < 0 ? 'overdue' : ($hours <= 1 ? 'due_1h' : 'due_3h');
                        // Keep separate reminder state for each student and deadline.
                        $key = 'assignment_reminder:'.$assignment->id.':'.$student->id.':'.$deadline->timestamp.':'.$type;
                        $ttl = $hours < 0 ? $now->copy()->addDays(30) : $now->copy()->addHour();

                        try {
                            if (!Cache::add($key, true, $ttl)) {
                                continue;
                            }
                            NotificationTriggerService::onAssignmentOverdueReminder(
                                $student,
                                $assignment,
                                (int) ($hours < 0 ? floor($hours) : ceil($hours))
                            );
                            $sent++;
                        } catch (\Throwable $e) {
                            Cache::forget($key);
                            $failed++;
                            Log::error('Assignment reminder failed', [
                                'assignment_id' => $assignment->id,
                                'user_id' => $student->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            });

        $this->info("Sent {$sent} assignment reminders; {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
