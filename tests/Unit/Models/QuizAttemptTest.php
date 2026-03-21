<?php

namespace Tests\Unit\Models;

use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_attempts_when_session_type_column_is_missing(): void
    {
        $user = User::factory()->student()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);

        if (Schema::hasColumn('quiz_attempts', 'session_type')) {
            Schema::table('quiz_attempts', function ($table) {
                $table->dropColumn('session_type');
            });
        }

        $result = QuizAttempt::createWithProgress([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'random',
            'study_mode' => 'random',
            'session_type' => 'random_quiz',
            'source' => null,
            'score' => 4,
            'total_questions' => 5,
            'duration_seconds' => 300,
            'question_data' => [],
            'correct_answers' => 4,
        ], false);

        $this->assertSame(1, QuizAttempt::query()->count());
        $this->assertSame($user->id, $result['attempt']->user_id);
        $this->assertSame($topic->id, $result['attempt']->topic_id);
        $this->assertNull($result['attempt']->getAttribute('session_type'));
    }
}
