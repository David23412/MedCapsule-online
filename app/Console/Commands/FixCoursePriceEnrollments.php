<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Course;

class FixCoursePriceEnrollments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'courses:fix-price-enrollments {course_id?} {--revoke-student-access}';

    /**
     * The console command description.
     */
    protected $description = 'Fix enrollment issues caused by course price changes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $courseId = $this->argument('course_id') ?? 1;
        $course = Course::find($courseId);

        if (!$course) {
            $this->error("Course ID {$courseId} not found!");
            return Command::FAILURE;
        }

        $this->info("🔧 Fixing Course Price Enrollments for: {$course->name}");
        $this->newLine();

        // Check current status
        $this->line("📚 Current Course Status:");
        $this->line("   Name: {$course->name}");
        $this->line("   Price: {$course->price} EGP");
        $this->line("   Access Policy: {$course->normalizedAccessType()}");
        $this->line("   Is Paid: " . ($course->isPaidAccess() ? "YES ✅" : "NO ❌"));
        $this->newLine();

        // Count current enrollments
        $allEnrollments = DB::table('course_user')
            ->where('course_id', $courseId)
            ->where('is_enrolled', true)
            ->count();

        $adminEnrollments = DB::table('course_user as cu')
            ->join('users as u', 'cu.user_id', '=', 'u.id')
            ->where('cu.course_id', $courseId)
            ->where('cu.is_enrolled', true)
            ->where('u.role', 1)
            ->count();

        $studentEnrollments = $allEnrollments - $adminEnrollments;

        $this->line("👥 Current Enrollments:");
        $this->line("   Total: {$allEnrollments}");
        $this->line("   Admins: {$adminEnrollments}");
        $this->line("   Students: {$studentEnrollments}");
        $this->newLine();

        // Clear caches
        $this->line("🧹 Clearing Caches:");
        $cachesToClear = [
            'total_users',
            'total_courses',
            'total_questions',
            'system_stats',
            'course_' . $courseId . '_enrolled_count',
            'course_' . $courseId . '_enrolled_students'
        ];

        $this->withProgressBar($cachesToClear, function ($cacheKey) {
            Cache::forget($cacheKey);
        });
        $this->newLine();

        // Increment cache version
        $currentVersion = Cache::get('courses_cache_version', 1);
        Cache::put('courses_cache_version', $currentVersion + 1, 86400);
        $this->line("✅ Cache version updated");
        $this->newLine();

        // Fix enrollments
        $this->line("🔒 Fixing Enrollments:");

        if ($course->isPaidAccess() && $this->option('revoke-student-access')) {
            $revokedCount = DB::table('course_user as cu')
                ->join('users as u', 'cu.user_id', '=', 'u.id')
                ->where('cu.course_id', $courseId)
                ->where('cu.is_enrolled', true)
                ->where('u.role', '!=', 1) // Not admin
                ->update([
                    'cu.is_enrolled' => false,
                    'cu.updated_at' => now()
                ]);

            if ($revokedCount > 0) {
                $this->info("✅ Revoked access for {$revokedCount} non-admin users");
            } else {
                $this->warn("No incorrect enrollments found to revoke");
            }
        } elseif ($course->isPaidAccess() && !$this->option('revoke-student-access')) {
            $this->warn("This course is PAID but --revoke-student-access flag not provided");
            $this->warn("Use --revoke-student-access to revoke non-admin access");
        } else {
            $this->info("✅ This course is not paid, so revoking student access is not needed");
        }

        $this->newLine();

        // Final verification
        $this->line("🔍 Final Verification:");

        $finalAllEnrollments = DB::table('course_user')
            ->where('course_id', $courseId)
            ->where('is_enrolled', true)
            ->count();

        $finalStudentEnrollments = DB::table('course_user as cu')
            ->join('users as u', 'cu.user_id', '=', 'u.id')
            ->where('cu.course_id', $courseId)
            ->where('cu.is_enrolled', true)
            ->where('u.role', '!=', 1)
            ->count();

        $this->line("   Final total enrollments: {$finalAllEnrollments}");
        $this->line("   Final student enrollments: {$finalStudentEnrollments}");

        if ($course->isPaidAccess()) {
            if ($finalStudentEnrollments === 0) {
                $this->info("SUCCESS: No students have access to paid course ✅");
            } else {
                $this->error("REMAINING ISSUE: {$finalStudentEnrollments} students still have access!");
            }
        } else {
            $this->info("This course is not paid, so student access can remain ✅");
        }

        $this->newLine();
        $this->info("🎉 Course price enrollment fix completed!");

        return Command::SUCCESS;
    }
}
