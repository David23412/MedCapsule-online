<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\NotificationService;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a few random users
        $users = User::take(5)->get();

        // Create notifications for each user using the service
        foreach ($users as $user) {
            // Create various notifications using the service methods
            
            // Create a milestone notification for correct answers
            app(NotificationService::class)->createCorrectAnswersMilestoneNotification(
                $user,
                rand(100, 1000)
            );

            // Create a streak notification
            app(NotificationService::class)->createStudyStreakNotification(
                $user,
                rand(7, 90)
            );
            
            // Create a level up notification
            app(NotificationService::class)->createLevelUpNotification(
                $user,
                rand(2, 10),
                rand(1000, 5000)
            );
            
            // Create a topic mastery notification
            app(NotificationService::class)->createTopicMasteryNotification(
                $user,
                'Anatomy of the Heart'
            );
            
            // Create a review reminder for mistakes
            app(NotificationService::class)->createReviewMistakesNotification(
                $user,
                rand(3, 10)
            );
            
            // Create a few custom info notifications
            for ($i = 0; $i < 3; $i++) {
                app(NotificationService::class)->createNotification(
                    $user->id,
                    'info',
                    'Test Notification ' . ($i + 1),
                    'This is a test notification created by the seeder.',
                    ['test_data' => true, 'index' => $i]
                );
            }
        }
    }
} 