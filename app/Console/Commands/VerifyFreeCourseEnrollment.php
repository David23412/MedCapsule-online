<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class VerifyFreeCourseEnrollment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:free-courses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify survey-unlocked enrollment state for survey-unlockable courses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== SURVEY-UNLOCKED COURSE ACCESS VERIFICATION ===');
        
        $freeCourses = Course::query()
            ->surveyUnlockable()
            ->get();
        
        $this->info("Found {$freeCourses->count()} survey-unlockable courses:");
        foreach ($freeCourses as $course) {
            $this->line("- {$course->name} (ID: {$course->id}, Access: {$course->normalizedAccessType()})");
        }
        
        $this->newLine();
        
        // Get all users
        $users = User::query()
            ->where('role', '!=', User::ROLE_ADMIN)
            ->get();
        $this->info("Found {$users->count()} users:");
        
        $allCorrect = true;
        
        foreach ($users as $user) {
            $this->line("Checking user: {$user->name} ({$user->email})");
            $shouldHaveSurveyAccess = $user->hasUnlockedFreeCourses();
            
            foreach ($freeCourses as $course) {
                $enrollment = DB::table('course_user')
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();
                
                if ($shouldHaveSurveyAccess && !$enrollment) {
                    $this->error("  ✗ Missing enrollment for {$course->name}");
                    $allCorrect = false;
                } elseif ($shouldHaveSurveyAccess && !(bool) $enrollment->is_enrolled) {
                    $this->error("  ✗ Enrollment exists but is inactive for {$course->name}");
                    $allCorrect = false;
                } elseif (!$shouldHaveSurveyAccess && $enrollment && (bool) $enrollment->is_enrolled) {
                    $this->error("  ✗ Locked user still has active enrollment in {$course->name}");
                    $allCorrect = false;
                } else {
                    $status = $shouldHaveSurveyAccess ? 'active as expected' : 'locked as expected';
                    $this->line("  ✓ {$course->name}: {$status}");
                }
            }
            $this->newLine();
        }
        
        if ($allCorrect) {
            $this->info('✅ Survey-based course access is consistent.');
        } else {
            $this->error('❌ Some survey-based enrollment issues were found. Run the backfill command to repair them.');
        }
        return $allCorrect ? 0 : 1;
    }
}
