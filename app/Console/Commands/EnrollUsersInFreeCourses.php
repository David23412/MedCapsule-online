<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnrollUsersInFreeCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enroll:free-courses {--force : Force re-enrollment even if already enrolled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enroll all users in all free courses (price = 0 or null)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting enrollment of all users in free courses...');

        // Get all free courses
        $freeCourses = Course::where(function($query) {
            $query->where('price', 0)
                  ->orWhereNull('price');
        })->get();

        if ($freeCourses->isEmpty()) {
            $this->error('❌ No free courses found!');
            return 1;
        }

        $this->info("📚 Found {$freeCourses->count()} free courses:");
        foreach ($freeCourses as $course) {
            $this->line("  - Course {$course->id}: {$course->name} (Price: {$course->price})");
        }

        // Get all users
        $users = User::all();
        $this->info("👥 Found {$users->count()} users to process");

        $totalEnrollments = 0;
        $skippedEnrollments = 0;

        $force = $this->option('force');

        foreach ($users as $user) {
            $this->info("🔄 Processing user {$user->id}: {$user->name} ({$user->email})");

            foreach ($freeCourses as $course) {
                // Check if already enrolled
                $existingEnrollment = DB::table('course_user')
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();

                if ($existingEnrollment && $existingEnrollment->is_enrolled && !$force) {
                    $this->line("  ⏭️  Already enrolled in course {$course->id} ({$course->name})");
                    $skippedEnrollments++;
                    continue;
                }

                try {
                    // Enroll or update enrollment
                    DB::table('course_user')->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'course_id' => $course->id
                        ],
                        [
                            'is_enrolled' => true,
                            'enrolled_at' => $existingEnrollment->enrolled_at ?? now(),
                            'updated_at' => now()
                        ]
                    );

                    $this->line("  ✅ Enrolled in course {$course->id} ({$course->name})");
                    $totalEnrollments++;

                    Log::info('User enrolled in free course via command', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'course_id' => $course->id,
                        'course_name' => $course->name,
                        'forced' => $force
                    ]);

                } catch (\Exception $e) {
                    $this->error("  ❌ Failed to enroll user {$user->id} in course {$course->id}: {$e->getMessage()}");
                    Log::error('Failed to enroll user in free course', [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $this->info("🎉 Enrollment process completed!");
        $this->info("📊 Summary:");
        $this->info("  - Total enrollments processed: {$totalEnrollments}");
        $this->info("  - Enrollments skipped: {$skippedEnrollments}");
        $this->info("  - Total potential enrollments: " . ($users->count() * $freeCourses->count()));

        // Verify the enrollment worked
        $this->info("🔍 Verifying enrollment...");
        $verificationStats = [];
        foreach ($freeCourses as $course) {
            $enrolledCount = DB::table('course_user')
                ->where('course_id', $course->id)
                ->where('is_enrolled', true)
                ->count();
            $verificationStats[$course->id] = $enrolledCount;
        }

        $this->info("📈 Final enrollment counts:");
        foreach ($verificationStats as $courseId => $count) {
            $course = $freeCourses->find($courseId);
            $this->line("  - Course {$courseId} ({$course->name}): {$count} enrolled users");
        }

        Log::info('Bulk free course enrollment completed', [
            'total_users' => $users->count(),
            'free_courses' => $freeCourses->count(),
            'enrollments_processed' => $totalEnrollments,
            'enrollments_skipped' => $skippedEnrollments,
            'final_stats' => $verificationStats
        ]);

        return 0;
    }
}
