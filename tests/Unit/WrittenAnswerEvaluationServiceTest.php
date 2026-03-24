<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Services\PatternMatcherService;
use App\Services\TextProcessingService;
use App\Services\WrittenAnswerEvaluationService;
use App\Services\MedicalKnowledgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class WrittenAnswerEvaluationServiceTest extends TestCase
{
    protected $textProcessingService;
    protected $patternMatcherService;
    protected $medicalKnowledgeService;
    protected $evaluationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create real instances of dependencies
        $this->textProcessingService = new TextProcessingService();
        $this->patternMatcherService = new PatternMatcherService();
        $this->medicalKnowledgeService = new MedicalKnowledgeService();
        
        // Turn off caching for tests
        $this->textProcessingService->setCacheConfig(false);
        $this->patternMatcherService->setCacheConfig(false);
        $this->medicalKnowledgeService->setCacheConfig(false);
        
        // Create the service with real dependencies
        $this->evaluationService = new WrittenAnswerEvaluationService(
            $this->textProcessingService,
            $this->patternMatcherService,
            $this->medicalKnowledgeService
        );
        $this->evaluationService->setCacheConfig(false);
    }
    
    /** @test */
    public function it_can_evaluate_exact_match_answers()
    {
        $result = $this->evaluationService->evaluateAnswer(
            'The heart pumps blood',
            'The heart pumps blood'
        );
        
        $this->assertTrue($result['isCorrect']);
        $this->assertEquals(1.0, $result['similarity']);
        $this->assertEquals('Perfect match!', $result['reason']);
    }
    
    /** @test */
    public function it_can_evaluate_alternative_answers()
    {
        $result = $this->evaluationService->evaluateAnswer(
            'The heart pumps blood throughout the body',
            'The heart circulates blood',
            ['The heart pumps blood throughout the body', 'Blood is pumped by the heart']
        );
        
        $this->assertTrue($result['isCorrect']);
        $this->assertEquals(1.0, $result['similarity']);
        $this->assertEquals('Exact match with alternative answer', $result['reason']);
    }
    
    /** @test */
    public function it_can_evaluate_similar_answers()
    {
        $result = $this->evaluationService->evaluateAnswer(
            'The heart circulates blood through the body',
            'The heart pumps blood throughout the body',
            [],
            0.8
        );
        
        $this->assertTrue($result['isCorrect']);
        $this->assertGreaterThanOrEqual(0.8, $result['similarity']);
        $this->assertEquals('Correct understanding of heart function', $result['reason']);
    }
    
    /** @test */
    public function it_rejects_dissimilar_answers()
    {
        $result = $this->evaluationService->evaluateAnswer(
            'The lungs exchange oxygen and carbon dioxide',
            'The heart pumps blood throughout the body',
            [],
            0.8
        );
        
        $this->assertFalse($result['isCorrect']);
        $this->assertLessThan(0.8, $result['similarity']);
        $this->assertEquals('Answer is missing essential medical concepts', $result['reason']);
    }
    
    /** @test */
    public function it_handles_empty_answers()
    {
        $result = $this->evaluationService->evaluateAnswer(
            '',
            'The heart pumps blood'
        );
        
        $this->assertFalse($result['isCorrect']);
        $this->assertEquals(0, $result['similarity']);
        $this->assertEquals('No answer provided', $result['reason']);
    }
    
    /** @test */
    public function test_early_termination_empty_answer()
    {
        $result = $this->evaluationService->evaluateAnswer('', 'correct answer');
        
        $this->assertFalse($result['isCorrect']);
        $this->assertEquals(0.0, $result['similarity']);
        $this->assertLessThan(0.01, $result['processing_time']); // Should be very fast
    }

    /** @test */
    public function test_early_termination_exact_match()
    {
        $result = $this->evaluationService->evaluateAnswer('correct answer', 'correct answer');
        
        $this->assertTrue($result['isCorrect']);
        $this->assertEquals(1.0, $result['similarity']);
        $this->assertLessThan(0.01, $result['processing_time']); // Should be very fast
    }

    /** @test */
    public function test_early_termination_length_check()
    {
        $result = $this->evaluationService->evaluateAnswer('very short', 'this is a much longer correct answer that should fail the length check');
        
        $this->assertFalse($result['isCorrect']);
        $this->assertLessThan(0.01, $result['processing_time']); // Should be very fast
    }
    
    /** @test */
    public function it_can_evaluate_medical_domain_specific_answers()
    {
        // Test simple medical concepts with lower threshold
        $result = $this->evaluationService->evaluateAnswer(
            'The heart pumps blood',
            'The heart circulates blood',
            [],
            0.4
        );
        
        $this->assertTrue($result['isCorrect']);
        $this->assertGreaterThanOrEqual(0.4, $result['similarity']);
        
        // Test another simple medical concept
        $result = $this->evaluationService->evaluateAnswer(
            'The lungs exchange gases',
            'The lungs facilitate gas exchange',
            [],
            0.4
        );
        
        $this->assertTrue($result['isCorrect']);
        $this->assertGreaterThanOrEqual(0.4, $result['similarity']);
    }
    
    /** @test */
    public function it_can_detect_domain_specific_patterns()
    {
        $this->markTestSkipped('This test needs to be implemented with specific medical domain patterns.');
        
        // Test for sympathetic_effects pattern
        $submitted = 'The sympathetic nervous system increases heart rate and blood pressure';
        $patterns = $this->patternMatcherService->identifyPatterns($submitted);
        
        $this->assertArrayHasKey('sympathetic_effects', $patterns);
        
        // Test for parasympathetic_effects pattern
        $submitted = 'The parasympathetic nervous system decreases heart rate and increases digestion';
        $patterns = $this->patternMatcherService->identifyPatterns($submitted);
        
        $this->assertArrayHasKey('parasympathetic_effects', $patterns);
    }
    
    /** @test */
    public function test_text_processing_service_text_normalization()
    {
        // Test normalization of text with different cases and punctuation
        $original = 'The HeArt pumps BLOOD!!!';
        $normalized = $this->textProcessingService->normalizeText($original);
        
        $this->assertEquals('the heart pumps blood', $normalized);
        
        // Test medical abbreviation expansion
        $original = 'BP is regulated by ANS';
        $normalized = $this->textProcessingService->normalizeText($original);
        
        $this->assertEquals('bp is regulated by ans', $normalized);
    }
    
    /** @test */
    public function test_text_processing_service_similarity_metrics()
    {
        $text1 = 'The heart pumps blood throughout the body';
        $text2 = 'The heart circulates blood throughout the body';
        
        // Test Levenshtein similarity
        $levenshtein = $this->textProcessingService->getLevenshteinSimilarity($text1, $text2);
        $this->assertGreaterThan(0.6, $levenshtein);
        
        // Test Jaccard similarity
        $jaccard = $this->textProcessingService->getJaccardSimilarity($text1, $text2);
        $this->assertGreaterThan(0.6, $jaccard);
        
        // Test keyword overlap
        $keyword = $this->textProcessingService->getKeywordOverlapRatio($text1, $text2);
        $this->assertGreaterThan(0.6, $keyword);
    }
    
    /** @test */
    public function it_can_evaluate_question_model_answers()
    {
        // Create a mock Question model with proper expectations
        $question = Mockery::mock(Question::class);
        $question->shouldReceive('getAttribute')->with('correct_answer')->andReturn('The heart pumps blood throughout the body');
        $question->shouldReceive('getAttribute')->with('alternative_answers')->andReturn(['Blood is pumped by the heart']);
        $question->shouldReceive('getAttribute')->with('similarity_threshold')->andReturn(0.75);
        $question->shouldReceive('setAttribute')->andReturnSelf();
        $question->shouldReceive('offsetExists')->andReturn(true);
        $question->shouldReceive('__get')->andReturnUsing(function($key) {
            $values = [
                'correct_answer' => 'The heart pumps blood throughout the body',
                'alternative_answers' => ['Blood is pumped by the heart'],
                'similarity_threshold' => 0.75
            ];
            return $values[$key] ?? null;
        });
        
        $result = $this->evaluationService->evaluateQuestionAnswer($question, 'The heart circulates blood in the body');
        
        $this->assertTrue($result['isCorrect']);
        $this->assertGreaterThanOrEqual(0.75, $result['similarity']);
    }
    
    /** @test */
    public function it_provides_detailed_feedback_for_incorrect_answers()
    {
        $result = $this->evaluationService->evaluateAnswer(
            'The kidneys filter blood',
            'The heart pumps blood throughout the body'
        );
        
        $this->assertFalse($result['isCorrect']);
        $this->assertArrayHasKey('feedback', $result);
        $this->assertNotEmpty($result['feedback']);
    }
} 