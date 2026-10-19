<?php

namespace Tests\Feature;

use App\Http\Controllers\ProfileController;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use App\Services\SecureFileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_settings_return_validation_errors_without_changing_preferences(): void
    {
        $user = User::factory()->student()->create(['weekly_goal' => 50, 'show_on_leaderboard' => true]);
        $this->actingAs($user)->postJson('/profile/update-goal', ['weekly_goal' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors(['weekly_goal', 'daily_study_minutes']);
        $this->postJson('/profile/update-privacy', ['show_on_leaderboard' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('show_on_leaderboard');
        $this->postJson('/profile/update-course-planner-settings', ['course_id' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('course_id');
        $this->assertSame(50, $user->fresh()->weekly_goal);
        $this->assertTrue($user->fresh()->show_on_leaderboard);
    }

    public function test_goal_save_preserves_unrelated_preferences_and_explicit_null_clears_course_overrides(): void
    {
        $user = User::factory()->student()->create(['repair_mode' => 'spaced', 'mistake_mastery_mode' => 'hard', 'exam_date' => now()->addDays(10)->toDateString()]);
        $course = Course::factory()->create();
        $user->courses()->attach($course, ['is_enrolled' => true, 'planner_exam_date' => now()->addDays(5)->toDateString(), 'planner_focus' => 'revision']);
        $this->actingAs($user)->postJson('/profile/update-goal', [
            'weekly_goal' => 100, 'daily_study_minutes' => 90, 'planner_focus' => 'balanced',
        ])->assertOk()->assertJsonPath('repair_mode', 'spaced')->assertJsonPath('mistake_mastery_mode', 'hard')
            ->assertJsonPath('exam_date', now()->addDays(10)->toDateString());
        $this->postJson('/profile/update-course-planner-settings', [
            'course_id' => $course->id, 'planner_exam_date' => null, 'planner_focus' => null,
        ])->assertOk()->assertJsonPath('overrides.planner_exam_date', null)->assertJsonPath('overrides.planner_focus', null);
        $this->assertDatabaseHas('course_user', ['user_id' => $user->id, 'course_id' => $course->id, 'planner_exam_date' => null, 'planner_focus' => null]);
    }

    public function test_profile_totals_and_weekly_progress_stay_fresh_and_legacy_attempts_appear_in_the_chart(): void
    {
        $user = User::factory()->student()->create(['total_questions_attempted' => 10, 'correct_answers_count' => 8]);
        $this->actingAs($user);
        Cache::put("profile_weekly_questions_{$user->id}_" . now()->format('Y-m-d'), 999, 300);
        QuizAttempt::factory()->for($user)->create(['created_at' => now(), 'total_questions' => 10, 'score' => 8, 'duration_seconds' => 120, 'attempt_id' => null]);
        $first = app(ProfileController::class)->show()->getData();
        $this->assertSame(10, $first['currentUserView']['weekly_questions']);
        $this->assertCount(1, $first['chartData']);
        $this->assertSame(80.0, $first['chartData'][0]['y']);
        QuizAttempt::factory()->for($user)->create(['created_at' => now()->addSecond(), 'total_questions' => 5, 'score' => 5, 'duration_seconds' => 60, 'attempt_id' => null]);
        $user->update(['total_questions_attempted' => 15, 'correct_answers_count' => 13]);
        $this->actingAs($user->fresh());
        $second = app(ProfileController::class)->show()->getData();
        $this->assertSame(15, $second['totalQuestions']);
        $this->assertSame(15, $second['currentUserView']['weekly_questions']);
        $this->assertSame('3m', $second['formattedStudyTimes']['total']);
        $this->assertCount(2, $second['chartData']);
    }

    public function test_completion_deduplicates_history_ignores_stale_ids_and_includes_random_questions(): void
    {
        $user = User::factory()->student()->create();
        $course = Course::factory()->create();
        $user->courses()->attach($course, ['is_enrolled' => true]);
        $topic = Topic::factory()->forCourse($course)->create();
        $first = Question::factory()->forTopic($topic)->create(['source' => 'Current']);
        $second = Question::factory()->forTopic($topic)->create(['source' => 'Current']);
        Question::factory()->forTopic($topic)->create(['source' => 'Current']);
        $outside = Question::factory()->create();
        DB::table('user_topic_source_progress')->insert([
            'user_id' => $user->id, 'topic_id' => $topic->id, 'source' => 'Old source name',
            'completed_question_ids' => json_encode([$first->id, 999999, $outside->id]),
        ]);
        QuizAttempt::factory()->for($user)->create([
            'topic_id' => null, 'quiz_type' => 'random', 'study_mode' => 'random',
            'question_data' => [['question_id' => $second->id], ['question_id' => $outside->id]],
        ]);
        $rows = [];
        for ($i = 0; $i < 205; $i++) {
            $rows[] = [
                'user_id' => $user->id, 'topic_id' => $topic->id, 'quiz_type' => 'normal',
                'score' => 1, 'total_questions' => 1, 'percentage_grade' => 100, 'duration_seconds' => 1,
                'question_data' => json_encode([['question_id' => $first->id]]), 'created_at' => now(), 'updated_at' => now(),
            ];
        }
        DB::table('quiz_attempts')->insert($rows);
        $method = new \ReflectionMethod(ProfileController::class, 'getCourseQuestionCompletionWidget');
        $data = $method->invoke(app(ProfileController::class), $user->id);
        $this->assertCount(1, $data['courses']);
        $this->assertSame(3, $data['courses'][0]['total_questions']);
        $this->assertSame(2, $data['courses'][0]['completed_questions']);
        $this->assertSame(66.7, $data['courses'][0]['percentage']);
    }

    public function test_admins_are_not_given_a_student_leaderboard_rank(): void
    {
        $this->actingAs(User::factory()->admin()->create(['xp' => 1000]));
        $this->assertNull(app(ProfileController::class)->show()->getData()['userRank']);
    }

    public function test_rejected_upload_keeps_the_existing_photo(): void
    {
        Storage::fake('secure_uploads');
        $path = 'profile-pictures/old.png';
        Storage::disk('secure_uploads')->put($path, 'existing photo');
        $user = User::factory()->create(['profile_picture_url' => $path]);
        $this->mock(SecureFileUploadService::class)->shouldReceive('processSecureUpload')->once()->andThrow(new \RuntimeException('Rejected'));
        $this->actingAs($user)->post('/profile/update-picture', ['profile_picture' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonPath('success', false);
        Storage::disk('secure_uploads')->assertExists($path);
        $this->assertSame($path, $user->fresh()->profile_picture_url);
    }

    public function test_successful_upload_replaces_the_photo_only_after_saving(): void
    {
        $directory = 'profile-pictures/profile-test-' . bin2hex(random_bytes(6));
        $base = Storage::disk('secure_uploads')->path($directory);
        File::ensureDirectoryExists($base);
        File::put($base . '/old.png', 'existing');
        File::put($base . '/new.png', 'replacement');
        $user = User::factory()->create(['profile_picture_url' => $directory . '/old.png']);
        $this->mock(SecureFileUploadService::class)->shouldReceive('processSecureUpload')->once()->andReturn(['storage_path' => $directory . '/new.png']);
        try {
            $response = $this->actingAs($user)->post('/profile/update-picture', ['profile_picture' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json']);
            $response->assertOk()->assertJsonPath('success', true);
            $this->assertStringContainsString('/uploads/' . $directory . '/new.png?', $response->json('new_url'));
            $this->assertFileDoesNotExist($base . '/old.png');
            $this->assertFileExists($base . '/new.png');
        } finally {
            File::deleteDirectory($base);
        }
    }

    public function test_database_upload_failure_preserves_old_photo_and_removes_the_unused_new_file(): void
    {
        Storage::fake('secure_uploads');
        $disk = Storage::disk('secure_uploads');
        $disk->put('profile-pictures/old.png', 'original');
        $disk->put('profile-pictures/new.png', 'replacement');
        $user = User::factory()->create(['profile_picture_url' => 'profile-pictures/old.png']);
        $this->mock(SecureFileUploadService::class)->shouldReceive('processSecureUpload')->once()
            ->andReturn(['storage_path' => 'profile-pictures/new.png']);
        $this->app['events']->listen('eloquent.updating: ' . User::class, function (User $updated): void {
            if ($updated->isDirty('profile_picture_url')) throw new \RuntimeException('Synthetic database failure');
        });
        $this->actingAs($user)->post('/profile/update-picture', ['profile_picture' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])
            ->assertStatus(500)->assertJsonPath('success', false);
        $disk->assertExists('profile-pictures/old.png');
        $disk->assertMissing('profile-pictures/new.png');
        $this->assertSame('profile-pictures/old.png', $user->fresh()->profile_picture_url);
    }
}
