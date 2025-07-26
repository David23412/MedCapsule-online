<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Services\GradingService;
use App\Services\QuizService;
use App\Services\NotificationService;
use App\Services\UserStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

class QuizControllerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Course $course;
    protected Topic $topic;
    protected Question $question;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create([
            'level' => 1,
            'xp' => 0,
            'role' => 'student'
        ]);
        
        $this->course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);
        
        // Enroll user in course
        $this->course->users()->attach($this->user->id);
        
        // Create questions
        $this->question = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'multiple_choice',
            'correct_answer' => 'A',
            'option_a' => 'Correct Answer',
            'option_b' => 'Wrong Answer 1',
            'option_c' => 'Wrong Answer 2',
            'option_d' => 'Wrong Answer 3'
        ]);
        
        Question::factory()->count(4)->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'multiple_choice'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_completes_quiz_flow_from_start_to_grade()
    {
        // Arrange - Start quiz
        $this->actingAs($this->user);
        
        $startResponse = $this->get("/quiz/{$this->course->id}/{$this->topic->id}");
        $startResponse->assertStatus(200);
        $startResponse->assertViewIs('solve_quiz');
        
        // Extract questions from the view
        $viewData = $startResponse->viewData('questions');
        $this->assertNotEmpty($viewData);
        
        // Act - Grade quiz
        $answers = [$this->question->id => 'A']; // Correct answer
        $gradeResponse = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'duration' => 120
        ]);
        
        // Assert
        $gradeResponse->assertStatus(200);
        $gradeResponse->assertJson([
            'success' => true,
            'score' => 1,
            'total_questions' => 1,
            'correct_answers' => 1,
            'incorrect_answers' => 0,
            'grade_percentage' => 100.0
        ]);
        
        // Verify database records
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'course_id' => $this->course->id,
            'score' => 1,
            'total_questions' => 1,
            'correct_answers' => 1,
            'incorrect_answers' => 0,
            'grade_percentage' => 100.0
        ]);
    }

    /** @test */
    public function it_completes_tutor_mode_flow_with_immediate_feedback()
    {
        // Arrange
        $this->actingAs($this->user);
        
        // Start tutor quiz
        $startResponse = $this->get("/quiz/{$this->course->id}/{$this->topic->id}/tutor");
        $startResponse->assertStatus(200);
        $startResponse->assertViewIs('solve_quiz');
        
        // Act - Check individual answer
        $checkResponse = $this->post("/quiz/tutor/check/{$this->question->id}", [
            'answer' => 'A' // Correct answer
        ]);
        
        // Assert
        $checkResponse->assertStatus(200);
        $checkResponse->assertJson([
            'correct' => true,
            'feedback' => 'Correct!'
        ]);
        
        // Check wrong answer
        $wrongResponse = $this->post("/quiz/tutor/check/{$this->question->id}", [
            'answer' => 'B' // Wrong answer
        ]);
        
        $wrongResponse->assertStatus(200);
        $wrongResponse->assertJson([
            'correct' => false,
            'feedback' => 'Incorrect. The correct answer is: A'
        ]);
    }

    /** @test */
    public function it_validates_quiz_access_integration()
    {
        // Arrange - Unauthorized user
        $unauthorizedUser = User::factory()->create(['role' => 'student']);
        $this->actingAs($unauthorizedUser);
        
        // Act - Try to access quiz
        $response = $this->get("/quiz/{$this->course->id}/{$this->topic->id}");
        
        // Assert
        $response->assertStatus(403);
        $response->assertSee('not enrolled');
        
        // Test with admin user
        $adminUser = User::factory()->create(['role' => 'admin']);
        $this->actingAs($adminUser);
        
        $adminResponse = $this->get("/quiz/{$this->course->id}/{$this->topic->id}");
        $adminResponse->assertStatus(200);
    }

    /** @test */
    public function it_delivers_notifications_after_quiz_completion()
    {
        // Arrange
        $this->actingAs($this->user);
        
        // Set user XP to trigger level up
        $this->user->update(['xp' => 950]);
        
        // Act
        $answers = [$this->question->id => 'A'];
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'duration' => 120
        ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'level_up' => true,
            'new_level' => 2
        ]);
        
        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'level_up'
        ]);
    }

    /** @test */
    public function it_updates_user_statistics_after_quiz()
    {
        // Arrange
        $this->actingAs($this->user);
        $initialStats = [
            'total_questions_attempted' => $this->user->total_questions_attempted,
            'correct_answers_count' => $this->user->correct_answers_count,
            'xp' => $this->user->xp
        ];
        
        // Act
        $answers = [$this->question->id => 'A'];
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'duration' => 120
        ]);
        
        // Assert
        $response->assertStatus(200);
        
        // Verify user stats were updated
        $this->user->refresh();
        $this->assertGreaterThan($initialStats['total_questions_attempted'], $this->user->total_questions_attempted);
        $this->assertGreaterThan($initialStats['correct_answers_count'], $this->user->correct_answers_count);
        $this->assertGreaterThan($initialStats['xp'], $this->user->xp);
    }

    /** @test */
    public function it_integrates_payment_verification()
    {
        // Arrange - Create paid course
        $paidCourse = Course::factory()->create(['requires_payment' => true]);
        $paidTopic = Topic::factory()->create(['course_id' => $paidCourse->id]);
        
        $this->actingAs($this->user);
        
        // Act - Try to access paid quiz without payment
        $response = $this->get("/quiz/{$paidCourse->id}/{$paidTopic->id}");
        
        // Assert
        $response->assertStatus(402); // Payment Required
        $response->assertSee('payment required');
        
        // Test with payment verification
        // This would require mocking the payment verification service
        // and creating a payment record for the user
    }

    /** @test */
    public function it_handles_cache_stale_behavior()
    {
        // Arrange
        $this->actingAs($this->user);
        
        // Mock cache to return stale data
        Cache::shouldReceive('get')
            ->andReturn(null); // Cache miss
        Cache::shouldReceive('put')
            ->andReturn(true);
        
        // Act
        $response = $this->get("/quiz/{$this->course->id}/{$this->topic->id}");
        
        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('solve_quiz');
        
        // Verify questions are still loaded from database
        $viewData = $response->viewData('questions');
        $this->assertNotEmpty($viewData);
    }

    /** @test */
    public function it_handles_user_inactive_scenarios()
    {
        // Arrange - Inactive user
        $inactiveUser = User::factory()->create([
            'role' => 'student',
            'last_active_at' => now()->subDays(30)
        ]);
        
        $this->actingAs($inactiveUser);
        
        // Act
        $response = $this->get("/quiz/{$this->course->id}/{$this->topic->id}");
        
        // Assert
        $response->assertStatus(200); // Should still work
        $response->assertViewIs('solve_quiz');
        
        // Verify user activity was updated
        $inactiveUser->refresh();
        $this->assertNotNull($inactiveUser->last_activity_at);
    }

    /** @test */
    public function it_handles_quiz_with_written_questions()
    {
        // Arrange
        $writtenQuestion = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'written',
            'correct_answer' => 'The heart pumps blood'
        ]);
        
        $this->actingAs($this->user);
        
        // Act
        $answers = [$writtenQuestion->id => 'The heart pumps blood'];
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'duration' => 180
        ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 1,
            'correct_answers' => 1
        ]);
    }

    /** @test */
    public function it_handles_mixed_question_types_in_single_quiz()
    {
        // Arrange
        $writtenQuestion = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'written',
            'correct_answer' => 'The heart pumps blood'
        ]);
        
        $this->actingAs($this->user);
        
        // Act
        $answers = [
            $this->question->id => 'A', // Multiple choice
            $writtenQuestion->id => 'The heart pumps blood' // Written
        ];
        
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'duration' => 240
        ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 2,
            'total_questions' => 2,
            'correct_answers' => 2
        ]);
    }

    /** @test */
    public function it_handles_quiz_timeout_scenarios()
    {
        // Arrange
        $this->actingAs($this->user);
        
        // Act - Submit quiz with very long duration (potential timeout)
        $answers = [$this->question->id => 'A'];
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'duration' => 3600 // 1 hour (unrealistic)
        ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 1
        ]);
        
        // Verify attempt was recorded with the long duration
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $this->user->id,
            'duration_seconds' => 3600
        ]);
    }

    /** @test */
    public function it_handles_concurrent_quiz_attempts()
    {
        // Arrange
        $this->actingAs($this->user);
        
        // Act - Submit multiple quiz attempts simultaneously
        $answers = [$this->question->id => 'A'];
        
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
                'answers' => $answers,
                'duration' => 120 + $i
            ]);
        }
        
        // Assert
        foreach ($responses as $response) {
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
        }
        
        // Verify all attempts were recorded
        $attemptCount = QuizAttempt::where('user_id', $this->user->id)
            ->where('topic_id', $this->topic->id)
            ->count();
        $this->assertEquals(3, $attemptCount);
    }

    /** @test */
    public function it_handles_malformed_quiz_data()
    {
        // Arrange
        $this->actingAs($this->user);
        
        // Act - Submit malformed data
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => 'invalid_data',
            'duration' => 'not_a_number'
        ]);
        
        // Assert
        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors(['answers', 'duration']);
    }

    /** @test */
    public function it_handles_nonexistent_questions()
    {
        // Arrange
        $this->actingAs($this->user);
        
        // Act - Submit answer for non-existent question
        $answers = [99999 => 'A']; // Non-existent question ID
        $response = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'duration' => 120
        ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 0,
            'total_questions' => 0
        ]);
    }

    /** @test */
    public function it_handles_quiz_with_no_questions()
    {
        // Arrange - Create topic with no questions
        $emptyTopic = Topic::factory()->create(['course_id' => $this->course->id]);
        $this->course->users()->attach($this->user->id);
        
        $this->actingAs($this->user);
        
        // Act
        $response = $this->get("/quiz/{$this->course->id}/{$emptyTopic->id}");
        
        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('solve_quiz');
        
        $viewData = $response->viewData('questions');
        $this->assertEmpty($viewData);
    }

    /** @test */
    public function it_preserves_quiz_session_state()
    {
        // Arrange
        $this->actingAs($this->user);
        
        // Start quiz
        $startResponse = $this->get("/quiz/{$this->course->id}/{$this->topic->id}");
        $startResponse->assertStatus(200);
        
        // Extract session data
        $sessionData = session()->all();
        $this->assertArrayHasKey('quiz_start_time', $sessionData);
        $this->assertArrayHasKey('current_quiz', $sessionData);
        
        // Act - Grade quiz
        $answers = [$this->question->id => 'A'];
        $gradeResponse = $this->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", [
            'answers' => $answers,
            'duration' => 120
        ]);
        
        // Assert
        $gradeResponse->assertStatus(200);
        
        // Verify session was cleaned up
        $this->assertNull(session('quiz_start_time'));
        $this->assertNull(session('current_quiz'));
    }
} 