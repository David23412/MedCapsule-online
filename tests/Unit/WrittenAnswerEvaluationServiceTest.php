<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Services\PatternMatcherService;
use App\Services\TextProcessingService;
use App\Services\WrittenAnswerEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class WrittenAnswerEvaluationServiceTest extends TestCase
{
    protected $textProcessingService;
    protected $patternMatcherService;
    protected $evaluationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create real instances of dependencies
        $this->textProcessingService = new TextProcessingService();
        $this->patternMatcherService = new PatternMatcherService();
        
        // Turn off caching for tests
        $this->textProcessingService->setCacheConfig(false);
        $this->patternMatcherService->setCacheConfig(false);
        
        // Create the service with real dependencies
        $this->evaluationService = new WrittenAnswerEvaluationService(
            $this->textProcessingService,
            $this->patternMatcherService
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
        $this->assertArrayHasKey('metrics', $result);
        $this->assertEquals('Exact match with correct answer', $result['reason']);
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
        $this->assertEquals('Similar to correct answer', $result['reason']);
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
        $this->assertEquals('Answer not similar enough to any correct answer', $result['reason']);
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
    public function it_can_evaluate_medical_domain_specific_answers()
    {
        // Test parasympathetic effects
        $result = $this->evaluationService->evaluateAnswer(
            'Parasympathetic stimulation decreases heart rate and increases digestion',
            'The parasympathetic nervous system decreases heart rate and enhances digestive activity'
        );
        
        $this->assertTrue($result['isCorrect']);
        $this->assertGreaterThanOrEqual(0.8, $result['similarity']);
        
        // Test sympathetic effects
        $result = $this->evaluationService->evaluateAnswer(
            'Sympathetic stimulation increases heart rate and blood pressure',
            'The sympathetic nervous system enhances heart rate and elevates blood pressure'
        );
        
        $this->assertTrue($result['isCorrect']);
        $this->assertGreaterThanOrEqual(0.8, $result['similarity']);
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
        
        $this->assertStringContainsString('blood pressure', $normalized);
    }
    
    /** @test */
    public function test_text_processing_service_similarity_metrics()
    {
        $text1 = 'The heart pumps blood throughout the body';
        $text2 = 'The heart circulates blood throughout the body';
        
        // Test Levenshtein similarity
        $levenshtein = $this->textProcessingService->getLevenshteinSimilarity($text1, $text2);
        $this->assertGreaterThan(0.7, $levenshtein);
        
        // Test Jaccard similarity
        $jaccard = $this->textProcessingService->getJaccardSimilarity($text1, $text2);
        $this->assertGreaterThan(0.7, $jaccard);
        
        // Test keyword overlap
        $keyword = $this->textProcessingService->getKeywordOverlapRatio($text1, $text2);
        $this->assertGreaterThan(0.7, $keyword);
    }
    
    /** @test */
    public function it_can_evaluate_question_model_answers()
    {
        // Create a mock Question model
        $question = Mockery::mock(Question::class);
        $question->correct_answer = 'The heart pumps blood throughout the body';
        $question->alternative_answers = ['Blood is pumped by the heart'];
        $question->similarity_threshold = 0.75;
        
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