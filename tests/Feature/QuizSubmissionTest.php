<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class QuizSubmissionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $course;
    protected $topic;
    protected $questions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        
        // Create test course
        $this->course = Course::factory()->create([
            'name' => 'Test Course'
        ]);
        
        // Create test topic
        $this->topic = Topic::factory()->create([
            'name' => 'Test Topic',
            'course_id' => $this->course->id
        ]);
        
        // Create test questions
        $this->questions = Question::factory()->count(3)->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'multiple_choice',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'correct_answer' => 'A'
        ]);
    }

    public function test_quiz_submission_works_without_500_error()
    {
        $this->actingAs($this->user);
        
        // Prepare quiz answers
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = 'A'; // All correct answers
        }
        
        // Submit quiz
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'time_taken' => 60,
            'mode' => 'quiz'
        ]);
        
        // Assert no 500 error
        $this->assertNotEquals(500, $response->getStatusCode());
        
        // Assert successful response
        $this->assertTrue($response->isSuccessful() || $response->isRedirection());
        
        // Check that user stats were updated
        $this->user->refresh();
        $this->assertGreaterThan(0, $this->user->correct_answers_count);
        $this->assertGreaterThan(0, $this->user->total_questions_attempted);
    }

    public function test_quiz_submission_with_mixed_answers()
    {
        $this->actingAs($this->user);
        
        // Store initial correct answers count
        $initialCorrectCount = $this->user->correct_answers_count;
        
        // Prepare quiz answers with some correct, some incorrect
        $answers = [];
        $correctCount = 0;
        foreach ($this->questions as $index => $question) {
            if ($index % 2 == 0) {
                $answers[$question->id] = 'A'; // Correct
                $correctCount++;
            } else {
                $answers[$question->id] = 'B'; // Incorrect
            }
        }
        
        // Submit quiz
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'time_taken' => 90,
            'mode' => 'quiz'
        ]);
        
        // Assert no 500 error
        $this->assertNotEquals(500, $response->getStatusCode());
        
        // Check that user stats were updated
        $this->user->refresh();
        $this->assertEquals($correctCount, $this->user->correct_answers_count - $initialCorrectCount);
    }

    public function test_quiz_submission_with_empty_answers()
    {
        $this->actingAs($this->user);
        
        // Prepare quiz answers with some empty answers
        $answers = [];
        foreach ($this->questions as $question) {
            $answers[$question->id] = ''; // Empty answers
        }
        
        // Submit quiz
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'time_taken' => 30,
            'mode' => 'quiz'
        ]);
        
        // Assert no 500 error
        $this->assertNotEquals(500, $response->getStatusCode());
        
        // Check that user stats were updated (even with empty answers)
        $this->user->refresh();
        $this->assertGreaterThan(0, $this->user->total_questions_attempted);
    }
}
