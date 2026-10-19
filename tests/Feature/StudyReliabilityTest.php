<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mistake;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudyReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_retried_answer_returns_original_result_without_advancing_twice(): void
    {
        $user = User::factory()->admin()->create();
        $question = Question::factory()->multipleChoice()->create(['correct_answer' => 'A']);
        $mistake = Mistake::create(['user_id' => $user->id, 'question_id' => $question->id, 'submitted_answer' => 'B']);
        $headers = ['X-Study-Request' => (string) Str::uuid()];
        $body = ['selected_answer' => 'A', 'mode' => 'practice', 'time_spent' => 4];
        $first = $this->actingAs($user)->postJson("/learn/review/{$question->id}", $body, $headers)
            ->assertOk()->assertJsonPath('success', true)->json();
        $state = $mistake->fresh()->getAttributes();
        $this->postJson("/learn/review/{$question->id}", $body, $headers)->assertOk()->assertExactJson($first);
        $this->assertSame($state, $mistake->fresh()->getAttributes());
        $this->assertDatabaseCount('study_submissions', 1);
        $this->postJson("/learn/review/{$question->id}", array_replace($body, ['selected_answer' => 'B']), $headers)->assertConflict();
        $this->assertSame($state, $mistake->fresh()->getAttributes());
        $this->postJson("/learn/review/{$question->id}", $body, ['X-Study-Request' => 'invalid'])->assertUnprocessable();
    }

    public function test_submission_keys_are_scoped_to_the_student_and_failed_requests_can_retry(): void
    {
        $users = User::factory()->admin()->count(2)->create();
        $question = Question::factory()->multipleChoice()->create(['correct_answer' => 'A']);
        $headers = ['X-Study-Request' => (string) Str::uuid()];
        $body = ['selected_answer' => 'A', 'mode' => 'practice'];
        $this->actingAs($users[0])->postJson("/learn/review/{$question->id}", $body, $headers)->assertNotFound();
        $this->assertDatabaseCount('study_submissions', 0);
        foreach ($users as $user) {
            Mistake::create(['user_id' => $user->id, 'question_id' => $question->id, 'submitted_answer' => 'B']);
            $this->actingAs($user)->postJson("/learn/review/{$question->id}", $body, $headers)->assertOk();
        }
        $this->assertDatabaseCount('study_submissions', 2);
    }

    public function test_assignment_countdown_uses_an_explicit_timezone(): void
    {
        $user = User::factory()->student()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        Question::factory()->forTopic($topic)->create();
        $user->courses()->attach($course, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
        $assignment = \App\Models\Assignment::factory()->completeTopic()->create([
            'course_id' => $course->id, 'topic_id' => $topic->id, 'deadline' => now()->addHours(38), 'duration_minutes' => 0,
        ]);
        $assignment->assignedUsers()->attach($user->id);
        $this->actingAs($user)->get('/topics/' . $course->id)->assertOk()
            ->assertSee('data-deadline="' . $assignment->deadline->toIso8601String() . '"', false);
    }

    public function test_topic_question_queries_include_linked_ids_without_matching_id_prefixes(): void
    {
        $user = User::factory()->admin()->create();
        $topic = Topic::factory()->create();
        $other = Topic::factory()->create();
        $direct = Question::factory()->forTopic($topic)->create();
        $linked = Question::factory()->forTopic($other)->create(['extra_topic' => ' ' . $topic->id . ' , 999 ']);
        Question::factory()->forTopic($other)->create(['extra_topic' => $topic->id . '0']);
        $this->assertSame(2, $topic->questions_count);
        $this->assertEqualsCanonicalizing([$direct->id, $linked->id], $topic->allQuestions()->pluck('id')->all());
        $this->actingAs($user)->get('/topics/' . $topic->course_id)->assertOk()->assertSee($topic->name);
    }

    public function test_review_paginates_with_stable_order_and_statistics_for_the_entire_filter(): void
    {
        $user = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        $questions = Question::factory()->state(['question_type' => 'written'])->forTopic($topic)->count(53)->create();
        foreach ($questions as $question) {
            Mistake::create(['user_id' => $user->id, 'question_id' => $question->id,
                'submitted_answer' => 'wrong', 'correct_streak' => 0, 'last_attempt_date' => now()->subDays(10)]);
        }
        DB::table('mistakes')->where('user_id', $user->id)->update(['last_attempt_date' => now()->subDays(10)]);
        $other = User::factory()->create();
        Mistake::create(['user_id' => $other->id, 'question_id' => $questions[0]->id, 'submitted_answer' => 'wrong']);
        $data = null;
        \Illuminate\Support\Facades\View::composer('mistakes', function ($view) use (&$data) { $data = $view->getData(); });
        $this->actingAs($user)->get('/review?course=' . $course->id)->assertOk()->assertSee('page=2', false);
        $this->assertCount(50, $data['mistakes']);
        $this->assertSame($questions->last()->id, $data['mistakes']->first()->question_id);
        $this->assertSame(53, $data['filteredMistakesCount']);
        $this->assertSame(53, $data['mistakeStats']['stale_count']);
        $this->assertSame(53, $data['mistakeStats']['written_count']);
        $this->get('/review?course=' . $course->id . '&page=2')->assertOk();
        $this->assertCount(3, $data['mistakes']);
        $this->assertSame($questions->first()->id, $data['mistakes']->last()->question_id);
        $this->assertSame(53, $data['filteredMistakesCount']);
        $this->get('/review?course=' . $course->id . '&page=99')->assertRedirect();
        $this->get('/review/learn')->assertOk()->assertSee($questions->first()->question)->assertSee('js/study-requests.js');
        DB::table('study_submissions')->insert(['user_id' => $user->id, 'request_key' => Str::uuid(), 'fingerprint' => str_repeat('a', 64), 'created_at' => now()->subDays(8)]);
        $this->artisan('db:prune-operational-data')->assertSuccessful();
        $this->assertDatabaseCount('study_submissions', 0);
    }
}
