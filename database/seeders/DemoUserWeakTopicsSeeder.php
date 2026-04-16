<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserTopicProgress;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoUserWeakTopicsSeeder extends Seeder
{
    public function run(): void
    {
        // Get the demo user
        $demoUser = User::where('email', 'geodavid627@gmail.com')->first();
        
        if (!$demoUser) {
            $this->command->error('Demo user not found. Please run UserSeeder first.');
            return;
        }

        // Clear existing progress for demo user to start fresh
        UserTopicProgress::where('user_id', $demoUser->id)->delete();

        // Get all topics to create weak progress across different levels
        $topics = Topic::all();
        
        if ($topics->isEmpty()) {
            $this->command->error('No topics found in database.');
            return;
        }

        // Create weak topic progress with different levels to show color coding
        $weakTopicsData = [
            // Very weak topics (red) - below 30%
            [
                'topic_ids' => [1, 4, 7, 10, 13], // Various topics across courses
                'percentage_range' => [15, 35],
                'last_attempt_days' => [1, 7]
            ],
            // Weak topics (orange) - 30-50%
            [
                'topic_ids' => [2, 5, 8, 11, 14],
                'percentage_range' => [30, 50],
                'last_attempt_days' => [2, 10]
            ],
            // Getting there topics (yellow) - 50-70%
            [
                'topic_ids' => [3, 6, 9, 12, 15],
                'percentage_range' => [50, 70],
                'last_attempt_days' => [3, 14]
            ],
            // Good topics (light green) - 70-85%
            [
                'topic_ids' => [16, 19, 22, 25, 28],
                'percentage_range' => [70, 85],
                'last_attempt_days' => [1, 5]
            ],
            // Great topics (green) - 85-95%
            [
                'topic_ids' => [17, 20, 23, 26, 29],
                'percentage_range' => [85, 95],
                'last_attempt_days' => [1, 3]
            ],
            // Mastered topics (gold) - 100%
            [
                'topic_ids' => [18, 21, 24, 27, 30],
                'percentage_range' => [100, 100],
                'last_attempt_days' => [1, 7]
            ]
        ];

        $progressCount = 0;
        
        foreach ($weakTopicsData as $category) {
            foreach ($category['topic_ids'] as $topicId) {
                // Check if topic exists
                if (!$topics->contains('id', $topicId)) {
                    continue;
                }

                $percentage = rand($category['percentage_range'][0], $category['percentage_range'][1]);
                $daysAgo = rand($category['last_attempt_days'][0], $category['last_attempt_days'][1]);
                $lastAttemptDate = Carbon::now()->subDays($daysAgo);

                UserTopicProgress::create([
                    'user_id' => $demoUser->id,
                    'topic_id' => $topicId,
                    'percentage_grade' => $percentage,
                    'last_attempt_date' => $lastAttemptDate,
                    'created_at' => $lastAttemptDate,
                    'updated_at' => $lastAttemptDate
                ]);

                $progressCount++;
            }
        }

        // Add some additional random weak topics for variety
        $usedTopicIds = collect(range(1, 30));
        $remainingTopicIds = $topics->pluck('id')->diff($usedTopicIds)->take(10);
        
        foreach ($remainingTopicIds as $topicId) {
            $percentage = rand(10, 60); // Mostly weak topics
            $daysAgo = rand(1, 20);
            $lastAttemptDate = Carbon::now()->subDays($daysAgo);

            UserTopicProgress::create([
                'user_id' => $demoUser->id,
                'topic_id' => $topicId,
                'percentage_grade' => $percentage,
                'last_attempt_date' => $lastAttemptDate,
                'created_at' => $lastAttemptDate,
                'updated_at' => $lastAttemptDate
            ]);

            $progressCount++;
        }

        $this->command->info("Created {$progressCount} weak topic progress entries for demo user 'MedCapsule'.");
        $this->command->info("This will show all color levels: Red (very weak), Orange (weak), Yellow (getting there), Light Green (good), Green (great), Gold (mastered).");
    }
}
