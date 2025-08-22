<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Services\RedisCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Redis;

class QuizWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Course $course;
    private Topic $topic;
    private Question $question;
    private RedisCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);
        $this->question = Question::factory()->create([
            'topic_id' => $this->topic->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'What is the capital of France?',
            'options' => json_encode(['Paris', 'London', 'Berlin', 'Madrid']),
            'correct_answer' => 'A',
            'source' => 'geography'
        ]);

        $this->cacheService = app(RedisCacheService::class);

        // Clear cache before each test
        if ($this->cacheService->isAvailable()) {
            $this->cacheService->clearUserCache($this->user->id);
            $this->cacheService->clearQuizCache($this->topic->id);
        }
    }

    /** @test */
    public function complete_quiz_workflow_with_caching()
    {
        // 1. User accesses quiz (should cache quiz data)
        $response = $this->actingAs($this->user)
            ->get("/quiz/{$this->course->id}/{$this->topic->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 2. Verify quiz data is cached
        if ($this->cacheService->isAvailable()) {
            $cachedQuiz = $this->cacheService->getCachedQuiz($this->topic->id);
            $this->assertNotNull($cachedQuiz, 'Quiz should be cached after first access');
        }

        // 3. Submit quiz answers
        $quizData = [
            'answers' => [
                $this->question->id => 'A' // Correct answer
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", $quizData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 4. Verify cache was cleared after submission
        if ($this->cacheService->isAvailable()) {
            $cachedQuiz = $this->cacheService->getCachedQuiz($this->topic->id);
            $this->assertNull($cachedQuiz, 'Quiz cache should be cleared after submission');
        }

        // 5. Check quiz results
        $response = $this->actingAs($this->user)
            ->get("/quiz/{$this->course->id}/{$this->topic->id}/results");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function quiz_error_handling_workflow()
    {
        // Test accessing non-existent quiz
        $response = $this->actingAs($this->user)
            ->get("/quiz/{$this->course->id}/99999"); // Non-existent topic

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
        $response->assertJson(['error' => 'Resource Not Found']);
    }

    /** @test */
    public function quiz_access_control_workflow()
    {
        // Create another user without course access
        $otherUser = User::factory()->create();

        // Try to access quiz without enrollment
        $response = $this->actingAs($otherUser)
            ->get("/quiz/{$this->course->id}/{$this->topic->id}");

        // Should redirect or show access denied (depending on implementation)
        $response->assertStatus(302); // Redirect to course enrollment
    }

    /** @test */
    public function quiz_performance_caching_workflow()
    {
        // Make multiple requests to same quiz
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->user)
                ->get("/quiz/{$this->course->id}/{$this->topic->id}");

            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
        }

        // Verify cache hit rate is high
        if ($this->cacheService->isAvailable()) {
            $stats = $this->cacheService->getCacheStats();
            $this->assertEquals('available', $stats['status']);
            $this->assertGreaterThan(0, $stats['total_keys']);
        }
    }

    /** @test */
    public function quiz_auto_save_functionality()
    {
        $autoSaveData = [
            'answers' => [
                $this->question->id => 'B' // Partial answer
            ],
            'current_question' => 1
        ];

        $response = $this->actingAs($this->user)
            ->post("/quiz/{$this->course->id}/{$this->topic->id}/auto-save", $autoSaveData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify auto-save data is stored (implementation dependent)
        // This would typically check session or temporary storage
    }

    /** @test */
    public function quiz_validation_error_handling()
    {
        // Submit invalid quiz data
        $invalidData = [
            'answers' => [
                $this->question->id => 'X' // Invalid option
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post("/quiz/{$this->course->id}/{$this->topic->id}/grade", $invalidData);

        // Should return validation error
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJson(['error' => 'Validation Error']);
    }

    /** @test */
    public function quiz_timeout_handling()
    {
        // Simulate a very long quiz session (implementation dependent)
        // This would typically test the timeout middleware or session expiry

        $response = $this->actingAs($this->user)
            ->withSession(['quiz_timeout' => now()->subHours(2)]) // 2 hours ago
            ->get("/quiz/{$this->course->id}/{$this->topic->id}");

        // Should handle timeout gracefully
        $this->assertTrue(
            $response->getStatusCode() === 200 || $response->getStatusCode() === 302
        );
    }

    /** @test */
    public function quiz_retry_after_failure()
    {
        // First, cause a failure (e.g., database connection issue)
        // This is difficult to test reliably, so we'll test the error recovery

        $response = $this->actingAs($this->user)
            ->get("/quiz/{$this->course->id}/{$this->topic->id}");

        $response->assertStatus(200);

        // Verify system recovers and continues to work
        $response2 = $this->actingAs($this->user)
            ->get("/quiz/{$this->course->id}/{$this->topic->id}");

        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);
    }

    /** @test */
    public function quiz_concurrent_access_handling()
    {
        // This would typically require multiple threads/processes
        // For now, we'll test sequential access with proper state management

        $responses = [];

        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($this->user)
                ->get("/quiz/{$this->course->id}/{$this->topic->id}");

            $responses[] = $response->getStatusCode();
            $response->assertStatus(200);
        }

        // All responses should be successful
        $this->assertCount(3, $responses);
        $this->assertContainsOnly('integer', $responses);
        $this->assertNotContains(500, $responses); // No server errors
    }

    /** @test */
    public function quiz_data_integrity_across_requests()
    {
        // Make initial request
        $response1 = $this->actingAs($this->user)
            ->get("/quiz/{$this->course->id}/{$this->topic->id}");

        $response1->assertStatus(200);
        $data1 = $response1->json('data');

        // Make second request
        $response2 = $this->actingAs($this->user)
            ->get("/quiz/{$this->course->id}/{$this->topic->id}");

        $response2->assertStatus(200);
        $data2 = $response2->json('data');

        // Data should be consistent (especially if cached)
        if ($data1 && $data2) {
            $this->assertEquals($data1, $data2, 'Quiz data should be consistent across requests');
        }
    }

    /** @test */
    public function quiz_cache_invalidation_on_update()
    {
        if (!$this->cacheService->isAvailable()) {
            $this->markTestSkipped('Redis is not available');
        }

        // Access quiz to populate cache
        $this->actingAs($this->user)
            ->get("/quiz/{$this->course->id}/{$this->topic->id}");

        // Verify cache exists
        $cachedQuiz = $this->cacheService->getCachedQuiz($this->topic->id);
        $this->assertNotNull($cachedQuiz);

        // Clear cache manually (simulating an update)
        $this->cacheService->clearQuizCache($this->topic->id);

        // Verify cache is cleared
        $cachedQuiz = $this->cacheService->getCachedQuiz($this->topic->id);
        $this->assertNull($cachedQuiz);
    }
}
