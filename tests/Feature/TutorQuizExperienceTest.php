<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\TutorSourceProgressService;
use App\Services\UltraFastQuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorQuizExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_written_tutor_check_accepts_a_typo_and_can_be_retried_without_duplicate_rewards(): void
    {
        $admin = User::factory()->admin()->create(['xp' => 0]);
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->quiz()->create(['is_exam' => true]);
        $question = Question::factory()->forTopic($topic)->create([
            'question_type' => 'written', 'correct_answer' => 'kidney', 'source' => 'Sample',
        ]);
        $this->actingAs($admin)->get(route('quiz.tutor', [$course, $topic]))->assertOk();
        $this->postJson(route('quiz.tutor.check-answer', $question), ['answer' => 'kidny'])
            ->assertOk()->assertJsonPath('is_correct', true)->assertJsonPath('source_progress.total', 1)
            ->assertJsonPath('source_progress.source', 'Sample');
        $xp = $admin->fresh()->xp;
        $this->postJson(route('quiz.tutor.check-answer', $question), ['answer' => 'kidny'])
            ->assertOk()->assertJsonPath('is_correct', true);
        $this->assertSame($xp, $admin->fresh()->xp);
        $this->assertDatabaseCount('mistakes', 0);
        $key = session('tutor_session_key');
        $this->postJson(route('quiz.tutor.complete-session'), [
            'topic_id' => (string) $topic->id, 'course_id' => (string) $course->id,
            'user_answers' => [$question->id => 'kidny'],
            'results' => ['correct' => 1, 'total' => 1],
        ])->assertOk()->assertSessionHas('tutor_session_questions.completed', true);
        $this->get(route('quiz.tutor', [$course, $topic]))->assertOk();
        $this->assertNotSame($key, session('tutor_session_key'));

    }

    public function test_tutor_totals_match_the_questions_actually_available_in_exam_and_lecture_modes(): void
    {
        $topic = Topic::factory()->quiz()->create(['is_exam' => false]);
        $other = Topic::factory()->quiz()->create();
        Question::factory()->forTopic($topic)->create(['source' => 'Sample']);
        Question::factory()->forTopic($other)->create(['source' => 'Sample', 'extra_topic' => (string) $topic->id]);
        Question::factory()->forTopic($other)->create(['source' => 'Sample', 'extra_topic' => $topic->id . '0']);
        $service = app(TutorSourceProgressService::class);
        $this->assertSame(['Sample' => 2], $service->getTopicSourceTotals($topic));
        $topic->update(['is_exam' => true]);
        $this->assertSame(['Sample' => 1], $service->getTopicSourceTotals($topic));
    }
    public function test_regular_quiz_page_opens_with_written_and_multiple_choice_questions(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->quiz()->create(['is_exam' => true]);
        Question::factory()->multipleChoice()->forTopic($topic)->create(['source' => 'Sample']);
        Question::factory()->forTopic($topic)->create(['question_type' => 'written', 'correct_answer' => 'kidney', 'source' => 'Sample']);
        $this->actingAs($admin)->get(route('quiz.start', [$course, $topic]))->assertOk();
    }

    public function test_regular_exam_load_keeps_option_e_and_excludes_linked_lecture_questions(): void
    {
        $exam = Topic::factory()->quiz()->create(['is_exam' => true]);
        $other = Topic::factory()->quiz()->create();
        $question = Question::factory()->multipleChoice()->forTopic($exam)->create([
            'option_e' => 'The fifth option must be loaded', 'correct_answer' => 'E', 'source' => 'Sample',
        ]);
        Question::factory()->forTopic($other)->create(['extra_topic' => (string) $exam->id, 'source' => 'Sample']);
        $service = app(UltraFastQuizService::class);
        $result = $service->getQuizQuestions($exam, ['all'], false);
        $this->assertCount(1, $result['questions']);
        $this->assertSame($question->option_e, $result['questions']->first()->option_e);
        $this->assertSame(1, $service->getQuizStatistics($exam)['total_questions']);
    }

    public function test_reloading_the_same_tutor_quiz_preserves_its_session_and_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->quiz()->create(['is_exam' => true]);
        Question::factory()->forTopic($topic)->create(['source' => 'Sample']);
        $url = route('quiz.tutor', [$course, $topic]);
        $this->actingAs($admin)->get($url)->assertOk();
        $key = session('tutor_session_key');
        $progress = ['sources' => ['Sample' => ['total' => 1, 'correct' => 1]]];
        $this->withSession(['tutor_progress' => $progress])->get($url)->assertOk();
        $this->assertSame($key, session('tutor_session_key'));
        $this->assertSame($progress, session('tutor_progress'));

        $this->withSession(['tutor_session_questions.completed' => true])->get($url)->assertOk();
        $this->assertNotSame($key, session('tutor_session_key'));
        $this->assertSame(['sources' => []], session('tutor_progress'));
    }

    public function test_changed_question_version_starts_a_fresh_tutor_session(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->quiz()->create(['is_exam' => true]);
        $question = Question::factory()->forTopic($topic)->create(['source' => 'Sample']);
        $url = route('quiz.tutor', [$course, $topic]);
        $this->actingAs($admin)->get($url)->assertOk();
        $key = session('tutor_session_key');
        $question->update(['question' => 'Updated sample question']);
        $this->get($url)->assertOk();
        $this->assertNotSame($key, session('tutor_session_key'));
    }

}
