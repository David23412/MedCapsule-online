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
    protected $description = 'Verify that all users are enrolled in all free courses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== FREE COURSE ENROLLMENT VERIFICATION ===');
        
        // Get all free courses
        $freeCourses = Course::where(function($query) {
            $query->where('price', 0)
                  ->orWhereNull('price');
        })->get();
        
        $this->info("Found {$freeCourses->count()} free courses:");
        foreach ($freeCourses as $course) {
            $this->line("- {$course->name} (ID: {$course->id}, Price: " . ($course->price ?? 'NULL') . ")");
        }
        
        $this->newLine();
        
        // Get all users
        $users = User::all();
        $this->info("Found {$users->count()} users:");
        
        $allCorrect = true;
        
        foreach ($users as $user) {
            $this->line("Checking user: {$user->name} ({$user->email})");
            
            foreach ($freeCourses as $course) {
                $enrollment = DB::table('course_user')
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();
                
                if (!$enrollment) {
                    $this->error("  ✗ NOT enrolled in {$course->name}");
                    $allCorrect = false;
                } elseif (!$enrollment->is_enrolled) {
                    $this->error("  ✗ Enrolled but status is 'inactive' for {$course->name}");
                    $allCorrect = false;
                } else {
                    $this->line("  ✓ Enrolled in {$course->name} (active)");
                }
            }
            $this->newLine();
        }
        
        if ($allCorrect) {
            $this->info('✅ All users are properly enrolled in all free courses!');
        } else {
            $this->error('❌ Some enrollment issues found. Run the backfill script to fix.');
        }
        
        return $allCorrect ? 0 : 1;
    }
} 