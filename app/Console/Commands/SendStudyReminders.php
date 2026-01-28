<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserTopicProgress;
use App\Models\QuizAttempt;
use App\Services\NotificationTriggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendStudyReminders extends Command
{
    protected $signature = 'notifications:send-reminders';
    protected $description = 'Send study reminders, weak topic alerts, and streak encouragements';

    public function handle()
    {
        $this->info('🔔 Starting notification reminders...');

        $this->sendWeakTopicReminders();
        $this->sendMistakeReviewReminders();
        $this->sendStudyStreakEncouragements();
        $this->sendInactivityReminders();

        $this->info('✅ All reminders sent successfully!');
        return 0;
    }

    /**
     * Send weak topic reminders to users
     */
    private function sendWeakTopicReminders()
    {
        $this->info('📊 Checking for weak topics...');

        $users = User::where('role', 'student')
            ->whereHas('topicProgress')
            ->get();

        $remindersSent = 0;

        foreach ($users as $user) {
            // Get topics with less than 50% grade
            $weakTopics = UserTopicProgress::where('user_id', $user->id)
                ->where('percentage_grade', '<', 50)
                ->where('percentage_grade', '>', 0) // Has attempted
                ->with('topic')
                ->get()
                ->map(function ($progress) {
                    return [
                        'id' => $progress->topic_id,
                        'name' => $progress->topic->name ?? 'Unknown Topic',
                        'grade' => $progress->percentage_grade
                    ];
                })
                ->toArray();

            if (count($weakTopics) >= 3) {
                // Only send if user has 3+ weak topics
                NotificationTriggerService::onWeakTopicReminder($user, $weakTopics);
                $remindersSent++;
            }
        }

        $this->info("✅ Sent {$remindersSent} weak topic reminders");
    }

    /**
     * Send mistake review reminders
     */
    private function sendMistakeReviewReminders()
    {
        $this->info('❌ Checking for mistakes to review...');

        $users = User::where('role', 'student')
            ->whereHas('quizAttempts')
            ->get();

        $remindersSent = 0;

        foreach ($users as $user) {
            // Count incorrect answers from recent quizzes (last 7 days)
            $mistakeCount = DB::table('quiz_attempts')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->whereRaw('JSON_LENGTH(question_data) > 0')
                ->get()
                ->sum(function ($attempt) {
                    $questions = json_decode($attempt->question_data, true) ?? [];
                    return collect($questions)->where('is_correct', false)->count();
                });

            if ($mistakeCount >= 10) {
                // Send reminder if 10+ mistakes
                NotificationTriggerService::onMistakesNeedReview($user, $mistakeCount);
                $remindersSent++;
            }
        }

        $this->info("✅ Sent {$remindersSent} mistake review reminders");
    }

    /**
     * Send study streak encouragements
     */
    private function sendStudyStreakEncouragements()
    {
        $this->info('🔥 Checking study streaks...');

        $users = User::where('role', 'student')->get();
        $encouragementsSent = 0;

        foreach ($users as $user) {
            $streakDays = $this->calculateStreak($user);

            if ($streakDays > 0) {
                // Send milestone notifications for specific days
                NotificationTriggerService::onStudyStreak($user, $streakDays);
                $encouragementsSent++;
            }
        }

        $this->info("✅ Sent {$encouragementsSent} streak encouragements");
    }

    /**
     * Send inactivity reminders to users who haven't studied recently
     */
    private function sendInactivityReminders()
    {
        $this->info('💤 Checking for inactive users...');

        $inactiveUsers = User::where('role', 'student')
            ->whereDoesntHave('quizAttempts', function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subDays(3));
            })
            ->whereHas('quizAttempts') // Has studied before
            ->get();

        $remindersSent = 0;

        foreach ($inactiveUsers as $user) {
            $lastActivity = QuizAttempt::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastActivity) {
                $daysInactive = Carbon::parse($lastActivity->created_at)->diffInDays(Carbon::now());

                if ($daysInactive >= 3 && $daysInactive <= 7) {
                    // Send reminder for 3-7 days inactive
                    NotificationTriggerService::onSuccess($user, 
                        "Hey! We miss you! It's been {$daysInactive} days. Come back and keep your streak alive! 🔥"
                    );
                    $remindersSent++;
                } elseif ($daysInactive > 7) {
                    // Send stronger reminder for 7+ days
                    NotificationTriggerService::onStreakReset($user);
                    $remindersSent++;
                }
            }
        }

        $this->info("✅ Sent {$remindersSent} inactivity reminders");
    }

    /**
     * Calculate user's current study streak
     */
    private function calculateStreak(User $user): int
    {
        $attempts = QuizAttempt::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($attempt) {
                return Carbon::parse($attempt->created_at)->format('Y-m-d');
            });

        if ($attempts->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = Carbon::now();

        // Check consecutive days
        while (true) {
            $dateKey = $currentDate->format('Y-m-d');
            
            if ($attempts->has($dateKey)) {
                $streak++;
                $currentDate->subDay();
            } else {
                break;
            }

            // Prevent infinite loop
            if ($streak > 365) {
                break;
            }
        }

        return $streak;
    }
}
