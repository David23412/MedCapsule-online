<?php

namespace Tests\Feature;

use App\Models\{Course, Mistake, Question, Topic, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseQuestionSearchReliabilityTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create();
        $topic = Topic::factory()->forCourse($course)->create();
        $question = Question::factory()->multipleChoice()->forTopic($topic)->create([
            'question' => 'BLOOD pressure and βήτα regulation', 'option_a' => 'Original A',
            'correct_answer' => 'A', 'explanation' => 'Original explanation',
        ]);
        $this->actingAs($admin);
        return [$course, $topic, $question];
    }

    public function test_search_normalizes_case_unicode_and_punctuation_and_validates_input(): void
    {
        [$course, , $question] = $this->fixture();
        foreach (['BLOOD', 'blood-pressure', 'βήτα'] as $query) {
            $this->getJson(route('courses.search-questions', ['course' => $course, 'query' => $query]))
                ->assertOk()->assertJsonPath('questions.0.id', $question->id);
        }
        $question->update(['question_type' => 'mcq']);
        $this->getJson(route('courses.search-questions', ['course' => $course, 'query' => 'blood']))
            ->assertOk()->assertJsonPath('questions.0.correct_answer', 'A: Original A');
        $url = route('courses.search-questions', $course);
        $this->getJson($url.'?query[]=blood')->assertUnprocessable();
        $this->getJson($url.'?query='.str_repeat('a', 201))->assertUnprocessable();
        $this->getJson($url.'?query=b')->assertOk()->assertJsonPath('questions', []);
    }

    public function test_search_cache_tracks_edits_topic_rename_course_move_and_deletion(): void
    {
        [$course, $topic, $question] = $this->fixture();
        $url = route('courses.search-questions', ['course' => $course, 'query' => 'blood']);
        $this->getJson($url)->assertJsonCount(1, 'questions');
        $question->update(['explanation' => 'Updated explanation']);
        $this->getJson($url)->assertJsonPath('questions.0.explanation', 'Updated explanation');
        $topic->update(['name' => 'Updated topic']);
        $this->getJson($url)->assertJsonPath('questions.0.topic_titles.0', 'Updated topic');
        $otherCourse = Course::factory()->premium()->create();
        $otherUrl = route('courses.search-questions', ['course' => $otherCourse, 'query' => 'blood']);
        $this->getJson($otherUrl)->assertJsonCount(0, 'questions');
        $topic->update(['course_id' => $otherCourse->id]);
        $this->getJson($url)->assertJsonCount(0, 'questions');
        $this->getJson($otherUrl)->assertJsonCount(1, 'questions');
        $question->delete();
        $this->getJson($otherUrl)->assertJsonCount(0, 'questions');
    }

    public function test_bookmark_requires_access_and_repeated_click_preserves_review_state(): void
    {
        [$course, , $question] = $this->fixture();
        $student = User::factory()->student()->create();
        $url = route('questions.add-mistake', $question);
        $this->actingAs($student)->postJson($url)->assertForbidden();
        $this->assertDatabaseCount('mistakes', 0);
        $student->courses()->attach($course, ['is_enrolled' => true, 'access_level' => User::COURSE_ACCESS_FULL]);
        $this->actingAs($student->fresh())->postJson($url)->assertOk();
        $mistake = Mistake::where('user_id', $student->id)->firstOrFail();
        $mistake->update(['correct_streak' => 2]);
        $this->postJson($url)->assertOk();
        $this->assertDatabaseCount('mistakes', 1);
        $this->assertSame(2, $mistake->fresh()->correct_streak);
    }

    public function test_partial_edits_preserve_fields_and_empty_correct_options_are_rejected(): void
    {
        [, , $question] = $this->fixture();
        $url = route('quiz.tutor.update-question', $question);
        $payload = ['question' => 'Updated question', 'correct_answer' => 'A'];
        $this->putJson($url, $payload)->assertOk()
            ->assertJsonPath('data.option_a', 'Original A')
            ->assertJsonPath('data.explanation', 'Original explanation');
        $this->putJson($url, [...$payload, 'correct_answer' => 'E', 'option_e' => ' '])
            ->assertUnprocessable()->assertJsonValidationErrors('correct_answer');
        $this->putJson($url, [...$payload, 'option_b' => null])->assertOk()->assertJsonPath('data.option_b', null);
        $question->update(['question_type' => 'written', 'correct_answer' => 'Kidney', 'alternative_answers' => 'Renal']);
        $this->putJson($url, ['question' => 'Written revision', 'correct_answer' => 'Kidney'])
            ->assertOk()->assertJsonPath('data.question_type', 'written')->assertJsonPath('data.alternative_answers', 'Renal');
    }
}
