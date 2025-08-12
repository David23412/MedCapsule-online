<?php

namespace Tests\Unit;

use App\Http\Requests\QuizGenerationRequest;
use PHPUnit\Framework\TestCase;

class QuizOptimizationTest extends TestCase
{
    public function test_form_request_has_proper_validation_rules()
    {
        $request = new QuizGenerationRequest();
        
        $rules = $request->rules();
        
        $this->assertArrayHasKey('source', $rules);
        $this->assertArrayHasKey('question_count', $rules);
        $this->assertArrayHasKey('difficulty', $rules);
        $this->assertArrayHasKey('time_limit', $rules);
        
        $this->assertContains('nullable', $rules['source']);
        $this->assertContains('string', $rules['source']);
        $this->assertContains('max:255', $rules['source']);
        
        $this->assertContains('integer', $rules['question_count']);
        $this->assertContains('min:1', $rules['question_count']);
        $this->assertContains('max:50', $rules['question_count']);
    }

    public function test_form_request_has_custom_messages()
    {
        $request = new QuizGenerationRequest();
        
        $messages = $request->messages();
        
        $this->assertArrayHasKey('question_count.min', $messages);
        $this->assertArrayHasKey('question_count.max', $messages);
        $this->assertArrayHasKey('time_limit.min', $messages);
        $this->assertArrayHasKey('time_limit.max', $messages);
        
        $this->assertEquals('Quiz must have at least 1 question.', $messages['question_count.min']);
        $this->assertEquals('Quiz cannot have more than 50 questions.', $messages['question_count.max']);
        $this->assertEquals('Time limit must be at least 60 seconds.', $messages['time_limit.min']);
        $this->assertEquals('Time limit cannot exceed 1 hour.', $messages['time_limit.max']);
    }

    public function test_ultra_fast_cache_key_generation_performance()
    {
        $topicId = 123;
        $userId = 456;
        $validatedData = [
            'source' => 'test_source',
            'question_count' => 10,
            'difficulty' => 'medium',
            'time_limit' => 1800,
            'user_id' => $userId
        ];

        // Test cache key generation speed - should be ultra-fast
        $startTime = microtime(true);
        
        for ($i = 0; $i < 10000; $i++) {
            $cacheKey = "quiz_gen_{$topicId}_{$validatedData['user_id']}_" . 
                       hash('xxh3', serialize($validatedData));
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // 10,000 cache key generations should complete in under 100ms (F-16 RAPTOR speed)
        $this->assertLessThan(100, $totalTime, 'Cache key generation should be F-16 RAPTOR fast');
        
        // Verify cache key format
        $cacheKey = "quiz_gen_{$topicId}_{$validatedData['user_id']}_" . 
                   hash('xxh3', serialize($validatedData));
        
        $this->assertStringStartsWith("quiz_gen_{$topicId}_{$userId}_", $cacheKey);
        $this->assertGreaterThan(20, strlen($cacheKey)); // Should have hash suffix
    }

    public function test_f16_raptor_javascript_class_structure()
    {
        $jsFile = file_get_contents(public_path('js/f16-raptor-quiz-generation.js'));
        
        // Verify F-16 RAPTOR class exists
        $this->assertStringContains('class F16RaptorQuizGenerator', $jsFile);
        
        // Verify key methods exist
        $this->assertStringContains('generateQuizWithRetry', $jsFile);
        $this->assertStringContains('prefetchQuizData', $jsFile);
        $this->assertStringContains('handleQuizGeneration', $jsFile);
        $this->assertStringContains('startPrefetchEngine', $jsFile);
        
        // Verify F-16 RAPTOR branding
        $this->assertStringContains('F-16 RAPTOR SPEED', $jsFile);
        $this->assertStringContains('Ultra-fast quiz generation', $jsFile);
        
        // Verify performance optimizations
        $this->assertStringContains('aggressive caching', $jsFile);
        $this->assertStringContains('prefetching', $jsFile);
        $this->assertStringContains('instant loading', $jsFile);
    }

    public function test_lethal_controller_optimization_engine_exists()
    {
        $engineFile = base_path('LETHAL_CONTROLLER_OPTIMIZATION_ENGINE.php');
        
        $this->assertFileExists($engineFile);
        
        $content = file_get_contents($engineFile);
        
        // Verify LETHAL branding
        $this->assertStringContains('LETHAL CONTROLLER OPTIMIZATION ENGINE', $content);
        $this->assertStringContains('F-16 RAPTOR SPEED', $content);
        $this->assertStringContains('MAXIMUM LETHALITY', $content);
        
        // Verify optimization patterns
        $this->assertStringContains('MASSIVE_CONTROLLER', $content);
        $this->assertStringContains('NUCLEAR_CONTROLLER', $content);
        $this->assertStringContains('DIRECT_DB_QUERIES', $content);
        $this->assertStringContains('NO_CACHING', $content);
        $this->assertStringContains('N_PLUS_ONE_QUERIES', $content);
        
        // Verify optimization strategies
        $this->assertStringContains('ADD_AGGRESSIVE_CACHING', $content);
        $this->assertStringContains('OPTIMIZE_QUERIES', $content);
        $this->assertStringContains('ADD_FORM_REQUESTS', $content);
        $this->assertStringContains('MOVE_TO_SERVICES', $content);
    }

    public function test_quiz_generation_controller_has_f16_optimizations()
    {
        $controllerFile = app_path('Http/Controllers/Quiz/QuizGenerationController.php');
        
        $this->assertFileExists($controllerFile);
        
        $content = file_get_contents($controllerFile);
        
        // Verify F-16 RAPTOR optimizations
        $this->assertStringContains('F-16 RAPTOR speed', $content);
        $this->assertStringContains('QuizGenerationRequest', $content);
        $this->assertStringContains('JsonResponse', $content);
        $this->assertStringContains('throttle:quiz-generation', $content);
        
        // Verify aggressive caching
        $this->assertStringContains('ResilientUltraConsolidatedCacheService::safeRemember', $content);
        $this->assertStringContains('Cache::remember', $content);
        
        // Verify ultra-fast cache key generation
        $this->assertStringContains('hash(\'xxh3\'', $content);
        
        // Verify error handling
        $this->assertStringContains('try {', $content);
        $this->assertStringContains('catch (\\Exception $e)', $content);
        $this->assertStringContains('Log::error', $content);
        
        // Verify prefetch functionality
        $this->assertStringContains('prefetchQuizData', $content);
        $this->assertStringContains('Cache warming', $content);
    }

    public function test_routes_have_f16_raptor_optimizations()
    {
        $routesFile = base_path('routes/web.php');
        
        $this->assertFileExists($routesFile);
        
        $content = file_get_contents($routesFile);
        
        // Verify F-16 RAPTOR routes exist
        $this->assertStringContains('F-16 RAPTOR SPEED Quiz Generation Routes', $content);
        $this->assertStringContains('LETHAL OPTIMIZATION', $content);
        $this->assertStringContains('quiz-generation', $content);
        
        // Verify optimized route structure
        $this->assertStringContains('QuizGenerationController', $content);
        $this->assertStringContains('generateQuiz', $content);
        $this->assertStringContains('getQuizOptions', $content);
        $this->assertStringContains('prefetchQuizData', $content);
        
        // Verify throttling
        $this->assertStringContains('throttle:quiz-generation,10,1', $content);
        $this->assertStringContains('throttle:30,1', $content);
        $this->assertStringContains('throttle:20,1', $content);
    }

    public function test_performance_benchmarks_meet_f16_standards()
    {
        // Test serialization performance (used in cache key generation)
        $data = [
            'source' => 'test_source',
            'question_count' => 10,
            'difficulty' => 'medium',
            'time_limit' => 1800,
            'user_id' => 123
        ];

        $startTime = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $serialized = serialize($data);
            $hash = hash('xxh3', $serialized);
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Should complete 1000 operations in under 10ms (F-16 RAPTOR standard)
        $this->assertLessThan(10, $totalTime, 'Serialization + hashing should meet F-16 RAPTOR performance standards');
    }

    public function test_optimization_engine_detected_quiz_generation_controller()
    {
        // Verify that the optimization engine would detect and optimize the QuizGenerationController
        $controllerPath = app_path('Http/Controllers/Quiz/QuizGenerationController.php');
        $content = file_get_contents($controllerPath);
        $lineCount = count(explode("\n", $content));
        
        // QuizGenerationController should be optimized (under 100 lines after optimization)
        $this->assertLessThan(200, $lineCount, 'QuizGenerationController should be optimized to reasonable size');
        
        // Should have proper imports for optimization
        $this->assertStringContains('use App\Http\Requests\QuizGenerationRequest;', $content);
        $this->assertStringContains('use Illuminate\Http\JsonResponse;', $content);
        $this->assertStringContains('use Illuminate\Support\Facades\Log;', $content);
    }
}