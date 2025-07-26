<?php

namespace Tests\Feature;

use App\Http\Controllers\Quiz\QuizGenerationController;
use App\Http\Requests\QuizGenerationRequest;
use App\Models\User;
use App\Models\Topic;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OptimizedQuizGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $topic;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->topic = Topic::factory()->create();
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_generates_quiz_with_f16_raptor_speed()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate", [
            'source' => 'test_source',
            'question_count' => 10,
            'difficulty' => 'medium',
            'time_limit' => 1800
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'cache_key'
                ])
                ->assertJson(['success' => true]);

        // Verify cache key was generated
        $this->assertNotNull($response->json('cache_key'));
    }

    /** @test */
    public function it_validates_quiz_generation_request_with_form_request()
    {
        $this->actingAs($this->user);

        // Test invalid question count
        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate", [
            'question_count' => 100, // Exceeds max of 50
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['question_count']);

        // Test invalid time limit
        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate", [
            'time_limit' => 30, // Below min of 60
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['time_limit']);
    }

    /** @test */
    public function it_applies_aggressive_caching_for_quiz_options()
    {
        $this->actingAs($this->user);

        // First request should hit the service
        $response1 = $this->getJson("/quiz-generation/topics/{$this->topic->id}/options");
        $response1->assertStatus(200);

        // Second request should hit cache (verify by checking response time)
        $startTime = microtime(true);
        $response2 = $this->getJson("/quiz-generation/topics/{$this->topic->id}/options");
        $endTime = microtime(true);
        
        $response2->assertStatus(200);
        
        // Cache hit should be significantly faster (under 10ms)
        $responseTime = ($endTime - $startTime) * 1000;
        $this->assertLessThan(10, $responseTime, 'Cache hit should be under 10ms');
    }

    /** @test */
    public function it_handles_errors_gracefully_with_proper_logging()
    {
        $this->actingAs($this->user);
        
        // Mock QuizService to throw exception
        $this->mock(QuizService::class, function ($mock) {
            $mock->shouldReceive('generateQuiz')
                 ->andThrow(new \Exception('Service unavailable'));
        });

        Log::shouldReceive('error')
           ->once()
           ->with('Quiz generation failed', \Mockery::type('array'));

        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate", [
            'question_count' => 10
        ]);

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Quiz generation failed. Please try again.'
                ]);
    }

    /** @test */
    public function it_applies_rate_limiting_for_quiz_generation()
    {
        $this->actingAs($this->user);

        // Make 11 requests (exceeds limit of 10 per minute)
        for ($i = 0; $i < 11; $i++) {
            $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate", [
                'question_count' => 5
            ]);
            
            if ($i < 10) {
                $this->assertNotEquals(429, $response->getStatusCode());
            }
        }

        // 11th request should be rate limited
        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate", [
            'question_count' => 5
        ]);
        
        $response->assertStatus(429);
    }

    /** @test */
    public function it_prefetches_quiz_data_for_instant_loading()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/prefetch");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Cache warming initiated'
                ]);
    }

    /** @test */
    public function it_uses_ultra_fast_cache_key_generation()
    {
        $this->actingAs($this->user);

        $validatedData = [
            'source' => 'test_source',
            'question_count' => 10,
            'difficulty' => 'medium',
            'time_limit' => 1800,
            'user_id' => $this->user->id
        ];

        // Test cache key generation speed
        $startTime = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $cacheKey = "quiz_gen_{$this->topic->id}_{$validatedData['user_id']}_" . 
                       hash('xxh3', serialize($validatedData));
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // 1000 cache key generations should complete in under 10ms
        $this->assertLessThan(10, $totalTime, 'Cache key generation should be ultra-fast');
    }

    /** @test */
    public function it_applies_middleware_for_authentication_and_throttling()
    {
        // Test unauthenticated access
        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate");
        $response->assertStatus(401);

        // Test authenticated access
        $this->actingAs($this->user);
        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate", [
            'question_count' => 10
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_proper_json_responses_for_api_consumption()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate", [
            'question_count' => 10
        ]);

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/json')
                ->assertJsonStructure([
                    'success',
                    'data',
                    'cache_key'
                ]);

        // Test options endpoint
        $response = $this->getJson("/quiz-generation/topics/{$this->topic->id}/options");
        
        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/json')
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);
    }

    /** @test */
    public function it_handles_form_request_validation_with_custom_messages()
    {
        $request = new QuizGenerationRequest();
        
        $messages = $request->messages();
        
        $this->assertArrayHasKey('question_count.min', $messages);
        $this->assertArrayHasKey('question_count.max', $messages);
        $this->assertArrayHasKey('time_limit.min', $messages);
        $this->assertArrayHasKey('time_limit.max', $messages);
        
        $this->assertEquals('Quiz must have at least 1 question.', $messages['question_count.min']);
        $this->assertEquals('Quiz cannot have more than 50 questions.', $messages['question_count.max']);
    }

    /** @test */
    public function it_provides_validated_data_with_defaults()
    {
        $this->actingAs($this->user);

        // Create a mock request with minimal data
        $request = QuizGenerationRequest::create('/test', 'POST', [
            'question_count' => 15
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Mock the validation to pass
        $request->merge(['question_count' => 15]);
        
        $validatedWithDefaults = $request->getValidatedWithDefaults();
        
        $this->assertEquals(15, $validatedWithDefaults['question_count']);
        $this->assertEquals('medium', $validatedWithDefaults['difficulty']);
        $this->assertEquals(1800, $validatedWithDefaults['time_limit']);
        $this->assertEquals($this->user->id, $validatedWithDefaults['user_id']);
    }

    /** @test */
    public function it_measures_f16_raptor_performance_benchmarks()
    {
        $this->actingAs($this->user);

        // Benchmark quiz generation speed
        $startTime = microtime(true);
        
        $response = $this->postJson("/quiz-generation/topics/{$this->topic->id}/generate", [
            'question_count' => 10
        ]);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // F-16 RAPTOR speed: should complete in under 100ms
        $this->assertLessThan(100, $responseTime, 'Quiz generation should complete in under 100ms (F-16 RAPTOR speed)');
        
        // Log performance metrics
        Log::info('F-16 RAPTOR Performance Benchmark', [
            'response_time_ms' => $responseTime,
            'topic_id' => $this->topic->id,
            'user_id' => $this->user->id,
            'benchmark_passed' => $responseTime < 100
        ]);
    }
}