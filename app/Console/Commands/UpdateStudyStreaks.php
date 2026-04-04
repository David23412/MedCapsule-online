<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\QuizAttempt;
use Carbon\Carbon;

class UpdateStudyStreaks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-streaks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all users\' study streak values based on their quiz history';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to update user study streaks...');
        
        $updatedCount = 0;
        $totalCount = 0;
        
        // Bulk-fetch latest attempt dates for all users in a single query
        $latestAttempts = QuizAttempt::selectRaw('user_id, MAX(created_at) as last_attempt_at')
            ->groupBy('user_id')
            ->pluck('last_attempt_at', 'user_id');

        // Bulk-fetch per-day activity for users who need streak calculation
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $activeUserIds = $latestAttempts->filter(function ($lastAt) use ($today, $yesterday) {
            $d = Carbon::parse($lastAt)->startOfDay();
            return $d->equalTo($today) || $d->equalTo($yesterday);
        })->keys()->all();

        // For active users, fetch all distinct activity dates in one query (last 365 days max)
        $activityDates = [];
        if (!empty($activeUserIds)) {
            $rows = QuizAttempt::selectRaw('user_id, DATE(created_at) as activity_date')
                ->whereIn('user_id', $activeUserIds)
                ->where('created_at', '>=', now()->subDays(365))
                ->groupBy('user_id', 'activity_date')
                ->get();
            foreach ($rows as $row) {
                $activityDates[$row->user_id][] = $row->activity_date;
            }
        }

        User::select(['id', 'name', 'study_streak_days'])->chunk(500, function ($users) use (
            $latestAttempts, $activityDates, $today, $yesterday, &$updatedCount, &$totalCount
        ) {
            foreach ($users as $user) {
                $totalCount++;
                $lastAttemptAt = $latestAttempts[$user->id] ?? null;

                if (!$lastAttemptAt) {
                    if ($user->study_streak_days !== 1) {
                        $user->study_streak_days = 1;
                        $user->save();
                        $updatedCount++;
                    }
                    continue;
                }

                $lastAttemptDate = Carbon::parse($lastAttemptAt)->startOfDay();

                if ($lastAttemptDate->equalTo($today) || $lastAttemptDate->equalTo($yesterday)) {
                    // Calculate streak from pre-fetched activity dates
                    $dates = collect($activityDates[$user->id] ?? [])
                        ->map(fn($d) => Carbon::parse($d)->startOfDay())
                        ->sort()
                        ->reverse()
                        ->values();

                    $streak = 0;
                    $checkDate = $today->copy();
                    foreach ($dates as $d) {
                        if ($d->equalTo($checkDate)) {
                            $streak++;
                            $checkDate->subDay();
                        } elseif ($d->lt($checkDate)) {
                            break;
                        }
                    }
                    $streak = max(1, $streak);

                    if ($user->study_streak_days != $streak) {
                        $user->study_streak_days = $streak;
                        $user->save();
                        $updatedCount++;
                    }
                } else {
                    // Last activity > 1 day ago — reset streak
                    if ($user->study_streak_days != 1) {
                        $user->study_streak_days = 1;
                        $user->save();
                        $updatedCount++;
                    }
                }
            }
        });
        
        $this->info("Completed! Updated study streaks for {$updatedCount} out of {$totalCount} users.");
        
        return Command::SUCCESS;
    }
} 