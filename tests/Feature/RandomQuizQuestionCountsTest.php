<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RandomQuizQuestionCountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_random_quiz_question_counts_include_direct_and_extra_topic_matches(): void
    {
        $student = User::factory()->student()->create();
        $course = Course::factory()->premium()->create();
        $otherCourse = Course::factory()->premium()->create();

        $quizTopic = Topic::factory()->forCourse($course)->quiz()->create();
        $practicalTopic = Topic::factory()->forCourse($course)->practical()->create();
        $caseTopic = Topic::factory()->forCourse($course)->cases()->create();
        $outsideTopic = Topic::factory()->forCourse($otherCourse)->quiz()->create();

        $student->courses()->attach($course->id, [
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'enrolled_at' => now(),
        ]);
        $student = $student->fresh();

        Question::factory()->create([
            'topic_id' => $quizTopic->id,
            'question' => 'Direct quiz question',
            'source' => 'A',
            'extra_topic' => null,
        ]);
        Question::factory()->create([
            'topic_id' => $outsideTopic->id,
            'question' => 'Linked practical question',
            'source' => 'A',
            'extra_topic' => (string) $practicalTopic->id,
        ]);
        Question::factory()->create([
            'topic_id' => $outsideTopic->id,
            'question' => 'Linked case question',
            'source' => 'B',
            'extra_topic' => (string) $caseTopic->id,
        ]);
        Question::factory()->create([
            'topic_id' => $outsideTopic->id,
            'question' => 'Question outside selected course',
            'source' => 'A',
            'extra_topic' => null,
        ]);

        $this->actingAs($student)
            ->getJson('/api/random-quiz-question-counts?' . http_build_query([
                'course_ids' => [$course->id],
                'sources' => ['all'],
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.quiz', 1)
            ->assertJsonPath('counts.practical', 1)
            ->assertJsonPath('counts.cases', 1)
            ->assertJsonPath('counts.all', 3);
    }

    public function test_random_quiz_question_counts_filter_by_source_and_full_access(): void
    {
        $student = User::factory()->student()->create();
        $fullCourse = Course::factory()->premium()->create();
        $previewOnlyCourse = Course::factory()->preview()->create();

        $fullQuizTopic = Topic::factory()->forCourse($fullCourse)->quiz()->create();
        $fullPracticalTopic = Topic::factory()->forCourse($fullCourse)->practical()->create();
        $previewTopic = Topic::factory()->forCourse($previewOnlyCourse)->quiz()->create();

        $student->courses()->attach($fullCourse->id, [
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'enrolled_at' => now(),
        ]);
        $student->courses()->attach($previewOnlyCourse->id, [
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_PREVIEW,
            'enrolled_at' => now(),
        ]);
        $student = $student->fresh();

        Question::factory()->create([
            'topic_id' => $fullQuizTopic->id,
            'question' => 'Selected source quiz question',
            'source' => 'Selected',
        ]);
        Question::factory()->create([
            'topic_id' => $fullPracticalTopic->id,
            'question' => 'Other source practical question',
            'source' => 'Other',
        ]);
        Question::factory()->create([
            'topic_id' => $previewTopic->id,
            'question' => 'Preview course should not count',
            'source' => 'Selected',
        ]);

        $this->actingAs($student)
            ->getJson('/api/random-quiz-question-counts?' . http_build_query([
                'course_ids' => [$fullCourse->id, $previewOnlyCourse->id],
                'sources' => ['Selected'],
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.quiz', 1)
            ->assertJsonPath('counts.practical', 0)
            ->assertJsonPath('counts.cases', 0)
            ->assertJsonPath('counts.all', 1);
    }
}
