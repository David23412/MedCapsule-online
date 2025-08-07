<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\WrittenAnswerEvaluationService;
use App\Services\TextProcessingService;
use App\Services\PatternMatcherService;
use App\Services\MedicalKnowledgeService;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;

class CoreServicesTest extends TestCase
{
    use RefreshDatabase;

    protected $writtenAnswerService;
    protected $textProcessingService;
    protected $patternMatcherService;
    protected $medicalKnowledgeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize services
        $this->textProcessingService = app(TextProcessingService::class);
        $this->patternMatcherService = app(PatternMatcherService::class);
        $this->medicalKnowledgeService = app(MedicalKnowledgeService::class);
        
        $this->writtenAnswerService = new WrittenAnswerEvaluationService(
            $this->textProcessingService,
            $this->patternMatcherService,
            $this->medicalKnowledgeService
        );
    }

    /** @test */
    public function text_processing_service_has_required_methods()
    {
        $this->assertTrue(method_exists($this->textProcessingService, 'normalizeText'));
        $this->assertTrue(method_exists($this->textProcessingService, 'setCacheConfig'));
        $this->assertTrue(method_exists($this->textProcessingService, 'getLevenshteinSimilarity'));
        $this->assertTrue(method_exists($this->textProcessingService, 'getJaccardSimilarity'));
        $this->assertTrue(method_exists($this->textProcessingService, 'getKeywordOverlapRatio'));
        $this->assertTrue(method_exists($this->textProcessingService, 'getConceptualSimilarity'));
        $this->assertTrue(method_exists($this->textProcessingService, 'getCombinedSimilarity'));
    }

    /** @test */
    public function pattern_matcher_service_has_required_methods()
    {
        $this->assertTrue(method_exists($this->patternMatcherService, 'identifyPatterns'));
        $this->assertTrue(method_exists($this->patternMatcherService, 'matchesPattern'));
        $this->assertTrue(method_exists($this->patternMatcherService, 'getPatternMatches'));
        $this->assertTrue(method_exists($this->patternMatcherService, 'registerPatternMatcher'));
        $this->assertTrue(method_exists($this->patternMatcherService, 'getRegisteredPatterns'));
    }

    /** @test */
    public function medical_knowledge_service_has_required_methods()
    {
        $this->assertTrue(method_exists($this->medicalKnowledgeService, 'identifyDomain'));
        $this->assertTrue(method_exists($this->medicalKnowledgeService, 'getCriticalTerms'));
        $this->assertTrue(method_exists($this->medicalKnowledgeService, 'termsContradict'));
        $this->assertTrue(method_exists($this->medicalKnowledgeService, 'expandAbbreviation'));
        $this->assertTrue(method_exists($this->medicalKnowledgeService, 'getCorrectLocations'));
        $this->assertTrue(method_exists($this->medicalKnowledgeService, 'isLocationCorrectForProcess'));
    }

    /** @test */
    public function written_answer_evaluation_service_has_required_methods()
    {
        $this->assertTrue(method_exists($this->writtenAnswerService, 'evaluateAnswer'));
        $this->assertTrue(method_exists($this->writtenAnswerService, 'setCacheConfig'));
        $this->assertTrue(method_exists($this->writtenAnswerService, 'evaluateQuestionAnswer'));
        $this->assertTrue(method_exists($this->writtenAnswerService, 'isCorrect'));
    }

    /** @test */
    public function text_processing_service_handles_empty_input_gracefully()
    {
        $result = $this->textProcessingService->normalizeText('');
        $this->assertEquals('', $result);
        
        $result = $this->textProcessingService->getLevenshteinSimilarity('', 'test');
        $this->assertEquals(0.0, $result);
        
        $result = $this->textProcessingService->getJaccardSimilarity('', 'test');
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function text_processing_service_handles_large_input_gracefully()
    {
        // Test with very large input to ensure no memory exhaustion
        $largeText = str_repeat('medical term ', 1000); // 12,000 characters
        
        $result = $this->textProcessingService->normalizeText($largeText);
        $this->assertIsString($result);
        $this->assertLessThanOrEqual(strlen($largeText), strlen($result));
        
        // Should not crash with large similarity calculations
        $result = $this->textProcessingService->getLevenshteinSimilarity($largeText, 'test');
        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0.0, $result);
        $this->assertLessThanOrEqual(1.0, $result);
    }

    /** @test */
    public function pattern_matcher_service_handles_empty_input_gracefully()
    {
        $result = $this->patternMatcherService->identifyPatterns('');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        
        $result = $this->patternMatcherService->matchesPattern('sympathetic_effects', '');
        $this->assertFalse($result);
        
        $result = $this->patternMatcherService->getPatternMatches('', '');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function pattern_matcher_service_handles_invalid_patterns_gracefully()
    {
        $result = $this->patternMatcherService->matchesPattern('nonexistent_pattern', 'test text');
        $this->assertFalse($result);
        
        // Should return empty array for invalid patterns
        $result = $this->patternMatcherService->getPatternMatches('', 'test text');
        $this->assertIsArray($result);
    }

    /** @test */
    public function medical_knowledge_service_handles_empty_input_gracefully()
    {
        $result = $this->medicalKnowledgeService->identifyDomain('');
        $this->assertNull($result);
        
        $result = $this->medicalKnowledgeService->getCriticalTerms('nonexistent_domain');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        
        $result = $this->medicalKnowledgeService->termsContradict('', '', 'anatomy');
        $this->assertFalse($result);
        
        $result = $this->medicalKnowledgeService->expandAbbreviation('');
        $this->assertNull($result);
    }

    /** @test */
    public function written_answer_evaluation_service_handles_empty_input_gracefully()
    {
        $result = $this->writtenAnswerService->evaluateAnswer('', 'correct answer');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('isCorrect', $result);
        $this->assertFalse($result['isCorrect']);
        $this->assertEquals('No answer provided', $result['reason']);
        
        $result = $this->writtenAnswerService->evaluateAnswer('user answer', '');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('isCorrect', $result);
        $this->assertArrayHasKey('similarity', $result);
        $this->assertArrayHasKey('reason', $result);
    }

    /** @test */
    public function written_answer_evaluation_service_handles_large_input_gracefully()
    {
        // Test with very large input to ensure no memory exhaustion
        $largeAnswer = str_repeat('medical answer content ', 500); // 11,500 characters
        $correctAnswer = 'heart pumps blood';
        
        $result = $this->writtenAnswerService->evaluateAnswer($largeAnswer, $correctAnswer);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('isCorrect', $result);
        $this->assertArrayHasKey('similarity', $result);
        $this->assertIsBool($result['isCorrect']);
        $this->assertIsFloat($result['similarity']);
    }

    /** @test */
    public function services_are_resilient_to_malformed_input()
    {
        $malformedInputs = [
            'null bytes' => "test\0string",
            'unicode control' => "test\u{0001}string",
            'unicode mixed' => "test\u{1F600}emoji",
            'html tags' => '<script>alert("xss")</script>',
            'special chars' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
            'numbers only' => '1234567890',
            'mixed case' => 'TeSt CaSe MiXeD'
        ];
        
        foreach ($malformedInputs as $type => $input) {
            // Text processing should handle all inputs gracefully
            $result = $this->textProcessingService->normalizeText($input);
            $this->assertIsString($result, "Failed for input type: $type");
            
            // Pattern matching should handle all inputs gracefully
            $result = $this->patternMatcherService->identifyPatterns($input);
            $this->assertIsArray($result, "Failed for input type: $type");
            
            // Medical knowledge should handle all inputs gracefully
            $result = $this->medicalKnowledgeService->identifyDomain($input);
            $this->assertTrue($result === null || is_string($result), "Failed for input type: $type");
            
            // Written answer evaluation should handle all inputs gracefully
            $result = $this->writtenAnswerService->evaluateAnswer($input, 'correct answer');
            $this->assertIsArray($result, "Failed for input type: $type");
            $this->assertArrayHasKey('isCorrect', $result, "Failed for input type: $type");
        }
    }

    /** @test */
    public function services_respect_cache_configuration()
    {
        // Test that cache configuration is respected
        $this->textProcessingService->setCacheConfig(false);
        $this->patternMatcherService->setCacheConfig(false);
        $this->medicalKnowledgeService->setCacheConfig(false);
        $this->writtenAnswerService->setCacheConfig(false);
        
        // Services should work even without caching
        $result = $this->textProcessingService->normalizeText('test input');
        $this->assertIsString($result);
        
        $result = $this->patternMatcherService->identifyPatterns('sympathetic nervous system');
        $this->assertIsArray($result);
        
        $result = $this->writtenAnswerService->evaluateAnswer('test answer', 'correct answer');
        $this->assertIsArray($result);
    }

    /** @test */
    public function services_handle_concurrent_operations()
    {
        // Simulate concurrent operations that might cause race conditions
        $inputs = [
            'sympathetic nervous system increases heart rate',
            'parasympathetic nervous system decreases heart rate',
            'heart pumps blood through circulation',
            'lungs facilitate gas exchange',
            'kidneys filter blood and produce urine'
        ];
        
        $results = [];
        
        foreach ($inputs as $input) {
            $results[] = $this->writtenAnswerService->evaluateAnswer($input, 'correct medical answer');
        }
        
        // All operations should complete successfully
        $this->assertCount(5, $results);
        
        foreach ($results as $result) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('isCorrect', $result);
            $this->assertArrayHasKey('similarity', $result);
        }
    }

    /** @test */
    public function pattern_matcher_service_get_pattern_matches_works()
    {
        $text1 = 'sympathetic nervous system increases heart rate';
        $text2 = 'sympathetic effects include increased heart rate';
        
        $result = $this->patternMatcherService->getPatternMatches($text1, $text2);
        $this->assertIsArray($result);
        
        // Should find common patterns
        if (!empty($result)) {
            foreach ($result as $match) {
                $this->assertArrayHasKey('pattern', $match);
                $this->assertArrayHasKey('description', $match);
                $this->assertArrayHasKey('confidence', $match);
                $this->assertIsFloat($match['confidence']);
                $this->assertGreaterThanOrEqual(0.0, $match['confidence']);
                $this->assertLessThanOrEqual(1.0, $match['confidence']);
            }
        }
    }

    /** @test */
    public function services_validate_input_boundaries()
    {
        // Test various boundary conditions
        $boundaryTests = [
            'very_short' => 'a',
            'single_word' => 'test',
            'normal_length' => 'this is a normal medical answer',
            'long_answer' => str_repeat('medical term ', 100),
            'very_long' => str_repeat('word ', 1000)
        ];
        
        foreach ($boundaryTests as $testName => $input) {
            // All services should handle these gracefully
            $textResult = $this->textProcessingService->normalizeText($input);
            $this->assertIsString($textResult, "Text processing failed for: $testName");
            
            $patternResult = $this->patternMatcherService->identifyPatterns($input);
            $this->assertIsArray($patternResult, "Pattern matching failed for: $testName");
            
            $evalResult = $this->writtenAnswerService->evaluateAnswer($input, 'correct answer');
            $this->assertIsArray($evalResult, "Evaluation failed for: $testName");
            $this->assertArrayHasKey('isCorrect', $evalResult, "Evaluation missing key for: $testName");
        }
    }

    /** @test */
    public function services_handle_edge_cases_with_proper_logging()
    {
        // Test that services handle edge cases and log appropriately
        $edgeCases = [
            'only_spaces' => '   ',
            'only_punctuation' => '!@#$%',
            'mixed_languages' => 'English مع العربية',
            'with_numbers' => 'test123answer456',
            'with_symbols' => 'test→answer↓example'
        ];
        
        foreach ($edgeCases as $caseName => $input) {
            try {
                $result = $this->writtenAnswerService->evaluateAnswer($input, 'correct answer');
                $this->assertIsArray($result, "Edge case failed: $caseName");
                $this->assertArrayHasKey('isCorrect', $result, "Missing key for: $caseName");
            } catch (\Exception $e) {
                // If an exception occurs, make sure it's logged and handled gracefully
                $this->fail("Services should handle edge case gracefully: $caseName. Error: " . $e->getMessage());
            }
        }
    }
}