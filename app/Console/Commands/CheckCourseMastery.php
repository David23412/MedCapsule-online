<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Course;
use App\Models\UserTopicProgress;
use App\Services\NotificationTriggerService;
use Illuminate\Support\Facades\Log;

class CheckCourseMastery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:check-mastery {--user-id= : Check specific user only} {--course-id= : Check specific course only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for course mastery and send notifications when users complete all topics in a course';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting course mastery check...');
        
        $userId = $this->option('user-id');
        $courseId = $this->option('course-id');
        
        $masteryCount = 0;
        
        $userQuery = $userId ? User::where('id', $userId) : User::query();
        
        $userQuery->chunk(500, function ($users) use ($courseId, &$masteryCount) {
            foreach ($users as $user) {
                // Get courses the user is enrolled in
                $enrolledCourses = $courseId 
                    ? $user->courses()->where('courses.id', $courseId)->get()
                    : $user->courses()->get();
                    
                foreach ($enrolledCourses as $course) {
                    // Check if user has mastered all topics in this course
                    $topicIds = $course->topics()->pluck('id');
                    $totalTopics = $topicIds->count();
                    $masteredTopics = UserTopicProgress::where('user_id', $user->id)
                        ->whereIn('topic_id', $topicIds)
                        ->where('percentage_grade', '>=', 90)
                        ->count();
                        
                    if ($totalTopics > 0 && $masteredTopics >= $totalTopics) {
                        // Check if we already sent a mastery notification for this course
                        $existingNotification = $user->notifications()
                            ->where('type', 'course_mastery')
                            ->where('data->course_id', $course->id)
                            ->exists();
                            
                        if (!$existingNotification) {
                            try {
                                NotificationTriggerService::onCourseMastery($user, $course->name);
                                
                                $this->line("Course mastery achieved! User #{$user->id} ({$user->name}) mastered {$course->name}");
                                $masteryCount++;
                                
                            } catch (\Exception $e) {
                                $this->error("Failed to send course mastery notification for user #{$user->id}, course {$course->name}: " . $e->getMessage());
                                Log::error('Failed to send course mastery notification', [
                                    'user_id' => $user->id,
                                    'course_id' => $course->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                }
            }
        });
        
        $this->info("Completed! Found {$masteryCount} new course mastery achievements.");
        
        return Command::SUCCESS;
    }
}
