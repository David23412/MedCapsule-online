<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => User::ROLE_STUDENT,
            'correct_answers_count' => 50,
            'total_questions_attempted' => 100,
            'xp' => 5000,
            'study_streak_days' => 7
        ]);

        // Create test course
        $this->course = Course::factory()->create([
            'name' => 'Test Course',
            'price' => 99.99
        ]);
    }

    /** @test */
    public function user_can_calculate_xp_correctly()
    {
        $expectedXP = ($this->user->correct_answers_count * 5) +
                     floor(($this->user->correct_answers_count * 100.0 / $this->user->total_questions_attempted) * 0.02 * $this->user->total_questions_attempted) +
                     ($this->user->study_streak_days * 10);

        $this->assertEquals($expectedXP, $this->user->calculateXP());
    }

    /** @test */
    public function user_can_earn_xp_for_specific_quiz()
    {
        $correctAnswers = 8;
        $totalQuestions = 10;

        $earnedXP = $this->user->earnXP($correctAnswers, $totalQuestions);

        $expectedXP = ($correctAnswers * 5) + floor(($correctAnswers * 100.0 / $totalQuestions) * 0.02 * $totalQuestions);

        $this->assertEquals($expectedXP, $earnedXP);
    }

    /** @test */
    public function user_level_calculation_is_correct()
    {
        // Test various XP levels
        $testCases = [
            0 => 1,      // 0 XP = Level 1
            999 => 1,    // 999 XP = Level 1
            1000 => 2,   // 1000 XP = Level 2
            2500 => 3,   // 2500 XP = Level 3
            5000 => 6,   // 5000 XP = Level 6
            10000 => 11, // 10000 XP = Level 11
        ];

        foreach ($testCases as $xp => $expectedLevel) {
            $this->user->xp = $xp;
            $this->assertEquals($expectedLevel, $this->user->getCurrentLevel(), "Failed for XP: {$xp}");
        }
    }

    /** @test */
    public function user_can_calculate_xp_needed_for_next_level()
    {
        // Test various scenarios
        $testCases = [
            0 => 1000,    // Level 1 needs 1000 more XP
            500 => 500,   // Level 1 needs 500 more XP
            1000 => 1000, // Level 2 needs 1000 more XP for Level 3
            1500 => 500,  // Level 2 needs 500 more XP for Level 3
            5000 => 1000, // Level 6 needs 1000 more XP for Level 7
        ];

        foreach ($testCases as $currentXP => $expectedNeeded) {
            $this->user->xp = $currentXP;
            $this->assertEquals($expectedNeeded, $this->user->getXPForNextLevel(), "Failed for XP: {$currentXP}");
        }
    }

    /** @test */
    public function admin_role_detection_works_correctly()
    {
        // Test admin user
        $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->assertTrue($adminUser->isAdmin());

        // Test tutor user
        $tutorUser = User::factory()->create(['role' => User::ROLE_TUTOR]);
        $this->assertFalse($tutorUser->isAdmin());

        // Test student user
        $studentUser = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->assertFalse($studentUser->isAdmin());
    }

    /** @test */
    public function tutor_role_detection_works_correctly()
    {
        // Test admin user
        $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->assertFalse($adminUser->isTutor());

        // Test tutor user
        $tutorUser = User::factory()->create(['role' => User::ROLE_TUTOR]);
        $this->assertTrue($tutorUser->isTutor());

        // Test student user
        $studentUser = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->assertFalse($studentUser->isTutor());
    }

    /** @test */
    public function student_role_detection_works_correctly()
    {
        // Test admin user
        $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->assertFalse($adminUser->isStudent());

        // Test tutor user
        $tutorUser = User::factory()->create(['role' => User::ROLE_TUTOR]);
        $this->assertFalse($tutorUser->isStudent());

        // Test student user
        $studentUser = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->assertTrue($studentUser->isStudent());
    }

    /** @test */
    public function user_can_enroll_in_course()
    {
        $this->user->enrollInCourse($this->course->id);

        $this->assertTrue($this->user->isEnrolledInCourse($this->course->id));
        $this->assertTrue($this->user->hasAccessToCourse($this->course->id));
    }

    /** @test */
    public function user_can_be_enrolled_in_free_courses_automatically()
    {
        // Create a free course
        $freeCourse = Course::factory()->create([
            'name' => 'Free Course',
            'price' => 0
        ]);

        $enrolledCount = $this->user->enrollInAllFreeCourses();

        $this->assertEquals(1, $enrolledCount);
        $this->assertTrue($this->user->isEnrolledInCourse($freeCourse->id));
    }

    /** @test */
    public function user_can_unenroll_from_course()
    {
        // First enroll
        $this->user->enrollInCourse($this->course->id);
        $this->assertTrue($this->user->isEnrolledInCourse($this->course->id));

        // Then unenroll
        $this->user->unenrollFromCourse($this->course->id);
        $this->assertFalse($this->user->isEnrolledInCourse($this->course->id));
    }

    /** @test */
    public function admin_has_access_to_all_courses()
    {
        $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Even without enrollment, admin should have access
        $this->assertTrue($adminUser->hasAccessToCourse($this->course->id));
        $this->assertTrue($adminUser->isEnrolledInCourse($this->course->id));
    }

    /** @test */
    public function user_course_progress_is_calculated_correctly()
    {
        // Create course enrollment with completion data
        DB::table('course_user')->insert([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
            'completion_percentage' => 75,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $progress = $this->user->getCourseProgressAttribute();

        $this->assertIsArray($progress);
        $this->assertArrayHasKey($this->course->id, $progress);
        $this->assertEquals(75, $progress[$this->course->id]);
    }

    /** @test */
    public function user_formatted_study_time_works_correctly()
    {
        $this->user->study_time_seconds = 3665; // 1 hour, 1 minute, 5 seconds
        $formattedTime = $this->user->getFormattedStudyTimeAttribute();

        $this->assertEquals('61m', $formattedTime);
    }

    /** @test */
    public function user_total_correct_answers_calculation()
    {
        // Create quiz attempts with correct answers
        QuizAttempt::factory()->create([
            'user_id' => $this->user->id,
            'correct_answers' => 8,
            'total_questions' => 10
        ]);

        QuizAttempt::factory()->create([
            'user_id' => $this->user->id,
            'correct_answers' => 7,
            'total_questions' => 10
        ]);

        $totalCorrect = $this->user->getCorrectAnswersFromDatabase();

        $this->assertEquals(15, $totalCorrect);
    }

    /** @test */
    public function user_can_sync_correct_answers_count()
    {
        // Set initial cached count
        $this->user->correct_answers_count = 50;
        $this->user->save();

        // Create quiz attempt that should increase the count
        QuizAttempt::factory()->create([
            'user_id' => $this->user->id,
            'correct_answers' => 10,
            'total_questions' => 10
        ]);

        // Sync should update the cached count
        $wasSynced = $this->user->syncCorrectAnswersCount();

        $this->assertTrue($wasSynced);
        $this->assertEquals(10, $this->user->fresh()->correct_answers_count);
    }

    /** @test */
    public function study_streak_updates_correctly()
    {
        // Set initial streak
        $this->user->study_streak_days = 5;
        $this->user->save();

        // Create quiz attempt for today
        QuizAttempt::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        $newStreak = $this->user->updateStudyStreak();

        // Should maintain or increase streak
        $this->assertGreaterThanOrEqual(5, $newStreak);
    }

    /** @test */
    public function bulk_course_access_check_works()
    {
        $courseIds = [$this->course->id];

        // Without enrollment
        $access = $this->user->hasAccessToCourses($courseIds);
        $this->assertFalse($access[$this->course->id]);

        // After enrollment
        $this->user->enrollInCourse($this->course->id);
        $access = $this->user->hasAccessToCourses($courseIds);
        $this->assertTrue($access[$this->course->id]);
    }

    /** @test */
    public function bulk_pending_payments_check_works()
    {
        $courseIds = [$this->course->id];

        // Should return false for courses with no pending payments
        $pending = $this->user->hasPendingPaymentsForCourses($courseIds);
        $this->assertFalse($pending[$this->course->id]);
    }
}
