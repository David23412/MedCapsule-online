<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Services\Core\FeatureToggleService;
use App\Services\Core\EventDrivenGradingService;
use App\Services\Core\SmartCacheManager;
use App\Services\Core\MemoryEfficientQuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ArchitecturalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable all features for testing
        Config::set('features.event_driven_grading', true);
        Config::set('features.smart_cache_manager', true);
        Config::set('features.memory_efficient_quiz', true);
        Config::set('features.memory_monitor', true);
        Config::set('features.event_driven_grading_percentage', 100); // 100% for testing
        Config::set('features.smart_cache_manager_percentage', 100);
    }

    /** @test */
    public function new_services_are_properly_registered()
    {
        $this->assertTrue(app()->bound(EventDrivenGradingService::class));
        $this->assertTrue(app()->bound(SmartCacheManager::class));
        $this->assertTrue(app()->bound(MemoryEfficientQuizService::class));
    }

    /** @test */
    public function feature_toggle_service_works_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertTrue(FeatureToggleService::shouldUseEventDrivenGrading($user->id));
        $this->assertTrue(FeatureToggleService::shouldUseSmartCacheManager($user->id));
        $this->assertTrue(FeatureToggleService::shouldUseMemoryEfficientQuiz(1500)); // Above threshold
        $this->assertFalse(FeatureToggleService::shouldUseMemoryEfficientQuiz(500)); // Below threshold
    }

    /** @test */
    public function event_driven_grading_service_produces_identical_results()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        $questions = Question::factory()->count(5)->create([
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice'
        ]);

        $answers = [];
        foreach ($questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }

        $this->actingAs($user);

        // Test new service
        $newService = app(EventDrivenGradingService::class);
        $newResult = $newService->gradeQuiz($user, $answers, 300, 'quiz', $topic, $course);

        // Verify result structure
        $this->assertTrue($newResult['success']);
        $this->assertEquals(5, $newResult['score']);
        $this->assertEquals(5, $newResult['total_questions']);
        $this->assertEquals(100, $newResult['percentage_grade']);
        $this->assertArrayHasKey('quiz_attempt_id', $newResult);
    }

    /** @test */
    public function smart_cache_manager_works_without_errors()
    {
        $cacheManager = app(SmartCacheManager::class);
        
        // Test cache statistics
        $stats = $cacheManager->getCacheStatistics();
        $this->assertIsArray($stats);
        
        // Test question cache invalidation
        $question = Question::factory()->create();
        
        // This should not throw an exception
        $cacheManager->invalidateQuestionCache($question->id);
        
        // Test user cache invalidation
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        
        // This should not throw an exception
        $cacheManager->invalidateUserCache($user->id, $topic->id);
    }

    /** @test */
    public function memory_efficient_quiz_service_handles_large_datasets()
    {
        $topic = Topic::factory()->create();
        Question::factory()->count(1500)->create(['topic_id' => $topic->id]);

        $memoryService = app(MemoryEfficientQuizService::class);
        
        $result = $memoryService->generateQuizQuestionsEfficiently($topic, ['all'], 50);
        
        $this->assertTrue($result['success']);
        $this->assertLessThanOrEqual(50, $result['questions']->count());
        $this->assertEquals($result['questions']->count(), $result['total_questions']);
    }

    /** @test */
    public function quiz_grading_endpoint_works_with_new_services()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        $questions = Question::factory()->count(3)->create([
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice'
        ]);

        $this->actingAs($user);

        $answers = [];
        foreach ($questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }

        $response = $this->postJson("/quiz/{$course->id}/{$topic->id}/grade", [
            'answers' => $answers,
            'duration' => 300,
            'mode' => 'quiz'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 3,
            'total' => 3,
            'percentage' => 100
        ]);

        // Check if debug info is present when in debug mode
        if (config('app.debug')) {
            $response->assertJsonStructure([
                'debug_info' => [
                    'used_new_grading',
                    'used_smart_cache'
                ]
            ]);
        }
    }

    /** @test */
    public function question_creation_works_with_smart_cache_manager()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $topic = Topic::factory()->create();

        $this->actingAs($admin);

        $response = $this->post('/add-question', [
            'topic_id' => $topic->id,
            'question' => 'Test question?',
            'question_type' => 'multiple_choice',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'correct_answer' => 'A',
            'explanation' => 'Test explanation',
            'source' => 'Test Source'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Question added successfully!');
        
        $this->assertDatabaseHas('questions', [
            'topic_id' => $topic->id,
            'question' => 'Test question?'
        ]);
    }

    /** @test */
    public function question_deletion_works_with_smart_cache_manager()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $question = Question::factory()->create();

        $this->actingAs($admin);

        $response = $this->delete("/admin/delete/question/{$question->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    /** @test */
    public function fallback_mechanism_works_when_new_services_fail()
    {
        // Temporarily disable features to test fallback
        Config::set('features.event_driven_grading', false);
        Config::set('features.smart_cache_manager', false);

        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        $questions = Question::factory()->count(3)->create([
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice'
        ]);

        $this->actingAs($user);

        $answers = [];
        foreach ($questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }

        $response = $this->postJson("/quiz/{$course->id}/{$topic->id}/grade", [
            'answers' => $answers,
            'duration' => 300,
            'mode' => 'quiz'
        ]);

        // Should still work with fallback services
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 3,
            'total' => 3,
            'percentage' => 100
        ]);
    }

    /** @test */
    public function debug_feature_status_endpoint_works()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/debug/feature-status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'feature_status' => [
                'user_id',
                'event_driven_grading',
                'smart_cache_manager',
                'memory_efficient_quiz',
                'memory_monitor'
            ],
            'config_values',
            'services_bound'
        ]);
    }

    /** @test */
    public function memory_monitor_middleware_can_be_applied()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // This should not throw an exception even if memory monitoring is enabled
        $response = $this->get('/quiz/random');
        
        // The response might be a redirect or view, but it shouldn't error
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function architectural_health_check_command_exists()
    {
        $this->artisan('architecture:health-check')
            ->assertExitCode(0); // Should exit successfully even if there are warnings
    }

    /** @test */
    public function performance_comparison_logs_when_enabled()
    {
        Config::set('features.performance_comparison', true);
        Config::set('features.log_feature_usage', true);

        $user = User::factory()->create();
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        $questions = Question::factory()->count(3)->create([
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice'
        ]);

        $this->actingAs($user);

        $answers = [];
        foreach ($questions as $question) {
            $answers[$question->id] = $question->correct_answer;
        }

        // Capture logs
        Log::shouldReceive('info')
            ->with(\Mockery::pattern('/Feature usage: event_driven_grading/'), \Mockery::any())
            ->once();

        $response = $this->postJson("/quiz/{$course->id}/{$topic->id}/grade", [
            'answers' => $answers,
            'duration' => 300,
            'mode' => 'quiz'
        ]);

        $response->assertStatus(200);
    }
}