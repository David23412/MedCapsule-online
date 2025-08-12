<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Mistake;
use App\Services\NotificationTriggerService;
use App\Events\MistakesNeedReview;
use Illuminate\Support\Facades\Log;

class TestAllNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test-all {--user-id= : Test specific user only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all notification triggers to ensure they are working properly';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧪 Testing ALL notification triggers...');
        
        $userId = $this->option('user-id');
        $users = $userId ? User::where('id', $userId)->get() : User::limit(3)->get();
        
        $testResults = [];
        
        foreach ($users as $user) {
            $this->line("\n📋 Testing notifications for user: {$user->name} (ID: {$user->id})");
            
            $userResults = [];
            
            // Test 1: Study Streak Notifications
            $this->line("  🔥 Testing study streak notifications...");
            try {
                // Test first quiz notification (streak 1)
                NotificationTriggerService::onStudyStreak($user, 1);
                $userResults['streak_day_1'] = '✅ PASSED';
                $this->line("    ✅ First quiz streak notification sent");
                
                // Test milestone streak notifications
                NotificationTriggerService::onStudyStreak($user, 7);
                $userResults['streak_week_1'] = '✅ PASSED';
                $this->line("    ✅ Week 1 streak notification sent");
                
            } catch (\Exception $e) {
                $userResults['study_streaks'] = '❌ FAILED: ' . $e->getMessage();
                $this->error("    ❌ Study streak notifications failed: " . $e->getMessage());
            }
            
            // Test 2: Course Mastery Notifications
            $this->line("  💎 Testing course mastery notifications...");
            try {
                $course = Course::first();
                if ($course) {
                    NotificationTriggerService::onCourseMastery($user, $course->name);
                    $userResults['course_mastery'] = '✅ PASSED';
                    $this->line("    ✅ Course mastery notification sent for {$course->name}");
                } else {
                    $userResults['course_mastery'] = '⚠️ SKIPPED: No courses available';
                    $this->line("    ⚠️ No courses available for testing");
                }
            } catch (\Exception $e) {
                $userResults['course_mastery'] = '❌ FAILED: ' . $e->getMessage();
                $this->error("    ❌ Course mastery notification failed: " . $e->getMessage());
            }
            
            // Test 3: Topic Mastery Notifications
            $this->line("  🎓 Testing topic mastery notifications...");
            try {
                $topic = Topic::first();
                if ($topic) {
                    NotificationTriggerService::onTopicMastery($user, $topic->name, $topic->course->name ?? null);
                    $userResults['topic_mastery'] = '✅ PASSED';
                    $this->line("    ✅ Topic mastery notification sent for {$topic->name}");
                } else {
                    $userResults['topic_mastery'] = '⚠️ SKIPPED: No topics available';
                    $this->line("    ⚠️ No topics available for testing");
                }
            } catch (\Exception $e) {
                $userResults['topic_mastery'] = '❌ FAILED: ' . $e->getMessage();
                $this->error("    ❌ Topic mastery notification failed: " . $e->getMessage());
            }
            
            // Test 4: Level Up Notifications
            $this->line("  ⚡ Testing level up notifications...");
            try {
                NotificationTriggerService::onLevelUp($user, 5, 'test');
                $userResults['level_up'] = '✅ PASSED';
                $this->line("    ✅ Level up notification sent");
            } catch (\Exception $e) {
                $userResults['level_up'] = '❌ FAILED: ' . $e->getMessage();
                $this->error("    ❌ Level up notification failed: " . $e->getMessage());
            }
            
            // Test 5: Mistake Review Notifications
            $this->line("  ⚠️ Testing mistake review notifications...");
            try {
                $mistakes = $user->mistakes()->limit(5)->get();
                if ($mistakes->count() > 0) {
                    event(new MistakesNeedReview($user, $mistakes->count(), $mistakes, []));
                    $userResults['mistake_review'] = '✅ PASSED';
                    $this->line("    ✅ Mistake review notification sent for {$mistakes->count()} mistakes");
                } else {
                    $userResults['mistake_review'] = '⚠️ SKIPPED: No mistakes available';
                    $this->line("    ⚠️ No mistakes available for testing");
                }
            } catch (\Exception $e) {
                $userResults['mistake_review'] = '❌ FAILED: ' . $e->getMessage();
                $this->error("    ❌ Mistake review notification failed: " . $e->getMessage());
            }
            
            // Test 6: Correct Answers Milestone Notifications
            $this->line("  ✨ Testing correct answers milestone notifications...");
            try {
                NotificationTriggerService::onCorrectAnswersMilestone($user, 100);
                $userResults['correct_answers_milestone'] = '✅ PASSED';
                $this->line("    ✅ Correct answers milestone notification sent");
            } catch (\Exception $e) {
                $userResults['correct_answers_milestone'] = '❌ FAILED: ' . $e->getMessage();
                $this->error("    ❌ Correct answers milestone notification failed: " . $e->getMessage());
            }
            
            // Test 7: Streak Reset Notifications
            $this->line("  🔥 Testing streak reset notifications...");
            try {
                NotificationTriggerService::onStreakReset($user);
                $userResults['streak_reset'] = '✅ PASSED';
                $this->line("    ✅ Streak reset notification sent");
            } catch (\Exception $e) {
                $userResults['streak_reset'] = '❌ FAILED: ' . $e->getMessage();
                $this->error("    ❌ Streak reset notification failed: " . $e->getMessage());
            }
            
            $testResults[$user->name] = $userResults;
        }
        
        // Display comprehensive results
        $this->info("\n📊 COMPREHENSIVE TEST RESULTS:");
        $this->info("=" . str_repeat("=", 80));
        
        foreach ($testResults as $userName => $results) {
            $this->line("\n👤 {$userName}:");
            foreach ($results as $testName => $result) {
                $status = str_contains($result, '✅') ? 'PASS' : (str_contains($result, '⚠️') ? 'SKIP' : 'FAIL');
                $this->line("  {$testName}: {$result}");
            }
        }
        
        // Summary statistics
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $skippedTests = 0;
        
        foreach ($testResults as $results) {
            foreach ($results as $result) {
                $totalTests++;
                if (str_contains($result, '✅')) $passedTests++;
                elseif (str_contains($result, '❌')) $failedTests++;
                elseif (str_contains($result, '⚠️')) $skippedTests++;
            }
        }
        
        $this->info("\n📈 SUMMARY:");
        $this->info("  Total Tests: {$totalTests}");
        $this->info("  ✅ Passed: {$passedTests}");
        $this->info("  ❌ Failed: {$failedTests}");
        $this->info("  ⚠️ Skipped: {$skippedTests}");
        
        if ($failedTests > 0) {
            $this->error("\n❌ Some notification tests failed! Check the logs for details.");
            return Command::FAILURE;
        } else {
            $this->info("\n🎉 All notification tests passed successfully!");
            return Command::SUCCESS;
        }
    }
}
