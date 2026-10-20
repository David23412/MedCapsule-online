<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearnFlowRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_answer_validation_returns_actionable_errors_without_mutating_progress(): void
    {
        $user = User::factory()->admin()->create();
        $question = Question::factory()->create();
        $this->actingAs($user);
        $requests = [
            ["/learn/check/{$question->id}", [], ['selected_answer', 'confidence']],
            ["/learn/finalize/{$question->id}", [], ['session_successes', 'session_failures', 'best_confidence', 'final_confidence']],
            ["/learn/review/{$question->id}", ['selected_answer' => ['not text']], ['selected_answer']],
            ['/learn/save-time', ['study_time' => -1], ['study_time']],
        ];
        foreach ($requests as [$url, $body, $fields]) {
            $this->postJson($url, $body, ['X-Study-Request' => (string) Str::uuid()])
                ->assertUnprocessable()->assertJsonValidationErrors($fields);
        }
        $this->getJson('/review/learn?mode=invalid')->assertUnprocessable()->assertJsonValidationErrors('mode');
        $this->assertDatabaseCount('quiz_attempts', 0);
        $this->assertDatabaseCount('study_submissions', 0);
    }

    public function test_completed_study_time_can_retry_without_counting_twice(): void
    {
        $user = User::factory()->admin()->create(['study_time_seconds' => 100]);
        $headers = ['X-Study-Request' => (string) Str::uuid()];
        $body = ['study_time' => 180];
        $first = $this->actingAs($user)->postJson('/learn/save-time', $body, $headers)->assertOk()->json();
        $this->postJson('/learn/save-time', $body, $headers)->assertOk()->assertExactJson($first);
        $this->assertSame(280, $user->fresh()->study_time_seconds);
        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertDatabaseHas('quiz_attempts', ['user_id' => $user->id, 'duration_seconds' => 180, 'quiz_type' => 'mistake_review']);
        $this->postJson('/learn/save-time', $body, ['X-Study-Request' => (string) Str::uuid()])->assertOk();
        $this->assertSame(460, $user->fresh()->study_time_seconds);
        $this->assertDatabaseCount('quiz_attempts', 2);
    }

    public function test_failed_analytics_insert_rolls_back_the_counter_and_can_be_retried(): void
    {
        $user = User::factory()->admin()->create(['study_time_seconds' => 100]);
        DB::unprepared("CREATE TRIGGER fail_review_insert BEFORE INSERT ON quiz_attempts BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $headers = ['X-Study-Request' => (string) Str::uuid()];
        $this->actingAs($user)->postJson('/learn/save-time', ['study_time' => 180], $headers)
            ->assertStatus(500)->assertJsonPath('success', false);
        $this->assertSame(100, $user->fresh()->study_time_seconds);
        $this->assertDatabaseCount('study_submissions', 0);
        $this->assertDatabaseCount('quiz_attempts', 0);
        DB::unprepared('DROP TRIGGER fail_review_insert');
        $this->postJson('/learn/save-time', ['study_time' => 180], $headers)->assertOk();
        $this->assertSame(280, $user->fresh()->study_time_seconds);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }
}
