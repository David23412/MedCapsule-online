<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class TutorModeProgressTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $course;
    protected $topic;
    protected $questions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => 'student'
        ]);
        
        // Create test course
        $this->course = Course::factory()->create([
            'name' => 'Test Course',
            'price' => 0
        ]);
        
        // Create test topic
        $this->topic = Topic::factory()->create([
            'course_id' => $this->course->id,
            'name' => 'Test Topic'
        ]);
        
        // Create test questions with different sources
        $this->questions = collect([
            Question::factory()->create([
                'topic_id' => $this->topic->id,
                'source' => 'Robbins',
                'question' => 'Test question 1',
                'correct_answer' => 'A'
            ]),
            Question::factory()->create([
                'topic_id' => $this->topic->id,
                'source' => 'Robbins',
                'question' => 'Test question 2',
                'correct_answer' => 'B'
            ]),
            Question::factory()->create([
                'topic_id' => $this->topic->id,
                'source' => 'First Aid',
                'question' => 'Test question 3',
                'correct_answer' => 'C'
            ]),
            Question::factory()->create([
                'topic_id' => $this->topic->id,
                'source' => 'First Aid',
                'question' => 'Test question 4',
                'correct_answer' => 'D'
            ])
        ]);
    }

    /** @test */
    public function tutor_mode_creates_quiz_attempt_with_correct_answers()
    {
        $this->actingAs($this->user);
        
        // Simulate tutor mode quiz completion
        $answers = [
            $this->questions[0]->id => 'A', // Correct
            $this->questions[1]->id => 'B', // Correct
            $this->questions[2]->id => 'C', // Correct
            $this->questions[3]->id => 'A'  // Incorrect
        ];
        
        $correctAnswersBySource = [
            'Robbins' => 2,    // 2 out of 2 correct
            'First Aid' => 1   // 1 out of 2 correct
        ];
        
        $response = $this->post(route('quiz.grade', [
            'course' => $this->course->id,
            'topic' => $this->topic->id
        ]), [
            'answers' => $answers,
            'mode' => 'tutor',
            'time_taken' => 120,
            'correct_answers_by_source' => json_encode($correctAnswersBySource),
            'sources' => ['Robbins', 'First Aid']
        ]);
        
        if ($response->status() !== 200) {
            dd('Response status:', $response->status(), 'Response body:', $response->getContent());
        }
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        // Check that quiz attempts were created with correct_answers field
        $robbinsAttempt = QuizAttempt::where([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'study_mode' => 'tutor',
            'source' => 'Robbins'
        ])->first();
        
        $firstAidAttempt = QuizAttempt::where([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'study_mode' => 'tutor',
            'source' => 'First Aid'
        ])->first();
        
        $this->assertNotNull($robbinsAttempt);
        $this->assertNotNull($firstAidAttempt);
        
        // Verify correct_answers field is populated
        $this->assertEquals(2, $robbinsAttempt->correct_answers);
        $this->assertEquals(1, $firstAidAttempt->correct_answers);
        
        // Verify scores match correct_answers
        $this->assertEquals(2, $robbinsAttempt->score);
        $this->assertEquals(1, $firstAidAttempt->score);
        
        // Verify percentages
        $this->assertEquals(100, $robbinsAttempt->percentage_grade);
        $this->assertEquals(50, $firstAidAttempt->percentage_grade);
    }

    /** @test */
    public function source_progress_endpoint_returns_correct_data()
    {
        $this->actingAs($this->user);
        
        // Create quiz attempts with correct_answers
        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'tutor',
            'source' => 'Robbins',
            'score' => 2,
            'total_questions' => 2,
            'percentage_grade' => 100,
            'duration_seconds' => 60,
            'question_data' => [
                ['question_id' => $this->questions[0]->id, 'user_answer' => 'A', 'is_correct' => true],
                ['question_id' => $this->questions[1]->id, 'user_answer' => 'B', 'is_correct' => true]
            ],
            'correct_answers' => 2
        ]);
        
        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'tutor',
            'source' => 'First Aid',
            'score' => 1,
            'total_questions' => 2,
            'percentage_grade' => 50,
            'duration_seconds' => 60,
            'question_data' => [
                ['question_id' => $this->questions[2]->id, 'user_answer' => 'C', 'is_correct' => true],
                ['question_id' => $this->questions[3]->id, 'user_answer' => 'A', 'is_correct' => false]
            ],
            'correct_answers' => 1
        ]);
        
        // Test source progress endpoint
        $response = $this->get("/topics/{$this->topic->id}/source-progress?mode=tutor");
        
        $response->assertStatus(200);
        $data = $response->json();
        
        // The old TopicController doesn't return 'success' field, just check the data structure
        $this->assertArrayHasKey('progress', $data);
        $this->assertArrayHasKey('availableSources', $data);
        
        // Verify Robbins progress
        $this->assertArrayHasKey('Robbins', $data['progress']);
        $this->assertEquals(2, $data['progress']['Robbins']['completed']);
        $this->assertEquals(2, $data['progress']['Robbins']['total_questions_available']);
        $this->assertEquals(100, $data['progress']['Robbins']['percentage']);
        
        // Verify First Aid progress
        $this->assertArrayHasKey('First Aid', $data['progress']);
        $this->assertEquals(1, $data['progress']['First Aid']['completed']);
        $this->assertEquals(2, $data['progress']['First Aid']['total_questions_available']);
        $this->assertEquals(50, $data['progress']['First Aid']['percentage']);
    }

    /** @test */
    public function tutor_mode_progress_updates_source_modal()
    {
        $this->actingAs($this->user);
        
        // Start tutor mode
        $response = $this->get("/quiz/{$this->course->id}/{$this->topic->id}/tutor?sources[]=Robbins&sources[]=First Aid");
        
        $response->assertStatus(200);
        $response->assertViewIs('tutor_mode');
        $response->assertViewHas('selected_sources', ['Robbins', 'First Aid']);
        
        // Complete tutor quiz
        $answers = [
            $this->questions[0]->id => 'A',
            $this->questions[1]->id => 'B',
            $this->questions[2]->id => 'C',
            $this->questions[3]->id => 'A'
        ];
        
        $correctAnswersBySource = [
            'Robbins' => 2,
            'First Aid' => 1
        ];
        
        $gradeResponse = $this->post(route('quiz.grade', [
            'course' => $this->course->id,
            'topic' => $this->topic->id
        ]), [
            'answers' => $answers,
            'mode' => 'tutor',
            'time_taken' => 120,
            'correct_answers_by_source' => json_encode($correctAnswersBySource),
            'sources' => ['Robbins', 'First Aid']
        ]);
        
        $gradeResponse->assertStatus(200);
        $gradeResponse->assertJson(['success' => true]);
        
        // Verify source progress is returned
        $gradeData = $gradeResponse->json();
        $this->assertArrayHasKey('source_progress', $gradeData);
        
        // Test that source progress endpoint returns updated data
        $progressResponse = $this->get("/topics/{$this->topic->id}/source-progress?mode=tutor");
        $progressResponse->assertStatus(200);
        
        $progressData = $progressResponse->json();
        $this->assertEquals(2, $progressData['progress']['Robbins']['completed']);
        $this->assertEquals(1, $progressData['progress']['First Aid']['completed']);
    }
}