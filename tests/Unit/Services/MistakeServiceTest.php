<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Mistake;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\MistakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MistakeServiceTest extends TestCase
{
    use RefreshDatabase;

    private MistakeService $service;
    private User $user;
    private Course $course;
    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MistakeService::class);
        $this->user = User::factory()->student()->create();
        $this->course = Course::create([
            'name' => 'Internal Medicine',
            'description' => 'Core medicine course',
            'type' => 'medical',
            'academic_year' => '2026-1',
            'price' => 0,
        ]);
        $this->topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Heart Failure',
            'case_type' => 'quiz',
            'display_order' => 1,
        ]);
    }

    public function test_it_keeps_mastered_mistakes_visible_in_the_main_mistake_pool(): void
    {
        $question = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'question' => 'What increases preload?',
            'correct_answer' => 'A',
            'source' => 'Assiut',
        ]);

        $mistake = Mistake::recordFailure($this->user->id, $question, 'B');
        $mistake->forceFill([
            'correct_streak' => 2,
            'mastered' => true,
            'last_attempt_date' => now()->subMinute(),
        ])->save();

        $payload = $this->service->getMistakesForUser($this->user->id, null);

        $this->assertCount(1, $payload['mistakes']);
        $this->assertSame($mistake->id, $payload['mistakes']->first()->id);
        $this->assertCount(1, $payload['courses']);
        $this->assertSame($this->course->id, $payload['courses']->first()->id);
    }

    public function test_it_does_not_record_a_mistake_for_a_semantically_correct_written_answer(): void
    {
        $question = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'question_type' => Question::TYPE_WRITTEN,
            'question' => 'Describe the parasympathetic nervous system effect.',
            'correct_answer' => 'The parasympathetic nervous system decreases heart rate and increases digestion',
            'source' => 'Assiut',
        ]);

        $mistake = $this->service->recordMistake(
            $this->user->id,
            $question->id,
            'PNS decreases HR and increases GI activity'
        );

        $this->assertNull($mistake);
        $this->assertDatabaseCount('mistakes', 0);
    }
}
