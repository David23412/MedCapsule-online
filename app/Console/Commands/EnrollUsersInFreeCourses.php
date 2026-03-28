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
    protected $description = 'Backfill survey-unlocked users into survey-unlockable courses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting survey-unlocked course access backfill...');

        $freeCourses = Course::query()
            ->surveyUnlockable()
            ->get();

        if ($freeCourses->isEmpty()) {
            $this->error('❌ No survey-unlockable courses found.');
            return 1;
        }

        $this->info("📚 Found {$freeCourses->count()} survey-unlockable courses:");
        foreach ($freeCourses as $course) {
            $this->line("  - Course {$course->id}: {$course->name} ({$course->normalizedAccessType()})");
        }

        $users = User::query()
            ->where('role', '!=', User::ROLE_ADMIN)
            ->get();
        $this->info("👥 Found {$users->count()} users to process");

        $totalEnrollments = 0;
        $eligibleUsers = 0;
        $skippedUsers = 0;

        foreach ($users as $user) {
            $this->info("🔄 Processing user {$user->id}: {$user->name} ({$user->email})");

            if (!$user->hasUnlockedFreeCourses()) {
                $this->line('  ⏭️  Survey not unlocked; skipping');
                $skippedUsers++;
                continue;
            }

            $eligibleUsers++;

            try {
                $enrolledCount = $user->ensureFreeCourseAccess();
                $totalEnrollments += $enrolledCount;

                $this->line("  ✅ Ensured access for {$enrolledCount} course(s)");

                Log::info('Survey-unlocked course access backfill completed for user', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'enrollment_count' => $enrolledCount,
                    'forced' => (bool) $this->option('force'),
                ]);
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to ensure access for user {$user->id}: {$e->getMessage()}");
                Log::error('Failed to ensure survey-unlocked course access', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info('🎉 Backfill completed.');
        $this->info("📊 Summary:");
        $this->info("  - Eligible users: {$eligibleUsers}");
        $this->info("  - Users skipped (locked): {$skippedUsers}");
        $this->info("  - Course enrollments repaired: {$totalEnrollments}");

        $verificationStats = [];
        foreach ($freeCourses as $course) {
            $enrolledCount = DB::table('course_user')
                ->where('course_id', $course->id)
                ->where('is_enrolled', true)
                ->count();
            $verificationStats[$course->id] = $enrolledCount;
        }

        $this->info('📈 Final active enrollment counts:');
        foreach ($verificationStats as $courseId => $count) {
            $course = $freeCourses->find($courseId);
            $this->line("  - Course {$courseId} ({$course->name}): {$count} enrolled users");
        }

        Log::info('Survey-unlocked course access backfill completed', [
            'total_users' => $users->count(),
            'eligible_users' => $eligibleUsers,
            'survey_unlockable_courses' => $freeCourses->count(),
            'enrollments_processed' => $totalEnrollments,
            'users_skipped' => $skippedUsers,
            'final_stats' => $verificationStats
        ]);

        return 0;
    }
}
