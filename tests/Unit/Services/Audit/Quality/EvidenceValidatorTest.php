<?php

namespace Tests\Unit\Services\Audit\Quality;

use Tests\TestCase;
use App\Services\Audit\Quality\EvidenceValidator;
use App\Services\Audit\Models\Finding;
use Mockery;

class EvidenceValidatorTest extends TestCase
{
    private EvidenceValidator $evidenceValidator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evidenceValidator = new EvidenceValidator();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_validates_complete_evidence_successfully()
    {
        // Arrange
        $finding = $this->createFindingWithCompleteEvidence();

        // Act
        $result = $this->evidenceValidator->validateFindingEvidence($finding);

        // Assert
        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThanOrEqual(70.0, $result['validation_score']);
        $this->assertGreaterThanOrEqual(80.0, $result['evidence_completeness']);
        $this->assertTrue($result['reproducibility_confirmed']);
        $this->assertEmpty($result['evidence_gaps']);
        $this->assertNull($result['rejection_reason']);
    }

    /** @test */
    public function it_rejects_evidence_with_missing_file_references()
    {
        // Arrange
        $finding = $this->createFindingWithoutFileReferences();

        // Act
        $result = $this->evidenceValidator->validateFindingEvidence($finding);

        // Assert
        $this->assertFalse($result['is_valid']);
        $this->assertContains('Missing file references', $result['evidence_gaps']);
        $this->assertStringContains('file references', $result['rejection_reason']);
    }

    /** @test */
    public function it_rejects_evidence_without_metrics()
    {
        // Arrange
        $finding = $this->createFindingWithoutMetrics();

        // Act
        $result = $this->evidenceValidator->validateFindingEvidence($finding);

        // Assert
        $this->assertFalse($result['is_valid']);
        $this->assertContains('Missing quantifiable metrics', $result['evidence_gaps']);
        $this->assertStringContains('metrics', $result['rejection_reason']);
    }

    /** @test */
    public function it_validates_evidence_existence_correctly()
    {
        // Arrange
        $findingWithEvidence = $this->createFindingWithCompleteEvidence();
        $findingWithoutEvidence = $this->createFindingWithoutEvidence();

        // Act
        $resultWithEvidence = $this->evidenceValidator->validateFindingEvidence($findingWithEvidence);
        $resultWithoutEvidence = $this->evidenceValidator->validateFindingEvidence($findingWithoutEvidence);

        // Assert
        $this->assertTrue($resultWithEvidence['validation_details']['existence']['has_evidence']);
        $this->assertGreaterThan(0, $resultWithEvidence['validation_details']['existence']['evidence_count']);
        
        $this->assertFalse($resultWithoutEvidence['validation_details']['existence']['has_evidence']);
        $this->assertEquals(0, $resultWithoutEvidence['validation_details']['existence']['evidence_count']);
    }

    /** @test */
    public function it_validates_evidence_quality_dimensions()
    {
        // Arrange
        $highQualityFinding = $this->createHighQualityFinding();
        $lowQualityFinding = $this->createLowQualityFinding();

        // Act
        $highQualityResult = $this->evidenceValidator->validateFindingEvidence($highQualityFinding);
        $lowQualityResult = $this->evidenceValidator->validateFindingEvidence($lowQualityFinding);

        // Assert
        $this->assertGreaterThan(80.0, $highQualityResult['validation_details']['quality']['average_quality_score']);
        $this->assertTrue($highQualityResult['validation_details']['quality']['meets_quality_threshold']);
        
        $this->assertLessThan(80.0, $lowQualityResult['validation_details']['quality']['average_quality_score']);
        $this->assertFalse($lowQualityResult['validation_details']['quality']['meets_quality_threshold']);
    }

    /** @test */
    public function it_validates_file_references_format()
    {
        // Arrange
        $findingWithValidReferences = $this->createFindingWithValidFileReferences();
        $findingWithInvalidReferences = $this->createFindingWithInvalidFileReferences();

        // Act
        $validResult = $this->evidenceValidator->validateFindingEvidence($findingWithValidReferences);
        $invalidResult = $this->evidenceValidator->validateFindingEvidence($findingWithInvalidReferences);

        // Assert
        $this->assertTrue($validResult['validation_details']['file_references']['all_references_valid']);
        $this->assertEmpty($validResult['validation_details']['file_references']['invalid_references']);
        
        $this->assertFalse($invalidResult['validation_details']['file_references']['all_references_valid']);
        $this->assertNotEmpty($invalidResult['validation_details']['file_references']['invalid_references']);
    }

    /** @test */
    public function it_validates_metrics_format()
    {
        // Arrange
        $findingWithValidMetrics = $this->createFindingWithValidMetrics();
        $findingWithInvalidMetrics = $this->createFindingWithInvalidMetrics();

        // Act
        $validResult = $this->evidenceValidator->validateFindingEvidence($findingWithValidMetrics);
        $invalidResult = $this->evidenceValidator->validateFindingEvidence($findingWithInvalidMetrics);

        // Assert
        $this->assertTrue($validResult['validation_details']['metrics']['all_metrics_valid']);
        $this->assertEmpty($validResult['validation_details']['metrics']['invalid_metrics']);
        
        $this->assertFalse($invalidResult['validation_details']['metrics']['all_metrics_valid']);
        $this->assertNotEmpty($invalidResult['validation_details']['metrics']['invalid_metrics']);
    }

    /** @test */
    public function it_calculates_reproducibility_score_correctly()
    {
        // Arrange
        $reproducibleFinding = $this->createReproducibleFinding();
        $nonReproducibleFinding = $this->createNonReproducibleFinding();

        // Act
        $reproducibleResult = $this->evidenceValidator->validateFindingEvidence($reproducibleFinding);
        $nonReproducibleResult = $this->evidenceValidator->validateFindingEvidence($nonReproducibleFinding);

        // Assert
        $this->assertGreaterThanOrEqual(70.0, $reproducibleResult['validation_details']['reproducibility']['reproducibility_score']);
        $this->assertTrue($reproducibleResult['validation_details']['reproducibility']['is_reproducible']);
        
        $this->assertLessThan(70.0, $nonReproducibleResult['validation_details']['reproducibility']['reproducibility_score']);
        $this->assertFalse($nonReproducibleResult['validation_details']['reproducibility']['is_reproducible']);
    }

    /** @test */
    public function it_handles_validation_errors_gracefully()
    {
        // Arrange
        $malformedFinding = $this->createMalformedFinding();

        // Act
        $result = $this->evidenceValidator->validateFindingEvidence($malformedFinding);

        // Assert
        $this->assertFalse($result['is_valid']);
        $this->assertStringContains('failed', $result['rejection_reason']);
        $this->assertArrayHasKey('finding_id', $result);
        $this->assertArrayHasKey('validation_timestamp', $result);
    }

    private function createFindingWithCompleteEvidence(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('complete-evidence-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => [
                'analysis_result' => 'Detailed analysis result',
                'tool_used' => 'PHPStan',
                'confidence_level' => 90
            ],
            'code_inspection' => [
                'code_location' => 'app/Models/User.php:42',
                'issue_description' => 'Missing validation',
                'severity_justification' => 'High impact on security'
            ]
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => 'app/Models/User.php', 'line' => 42, 'context' => 'public function validate() { // missing implementation }']
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => 'complexity', 'value' => 15, 'unit' => 'score']
        ]);
        $finding->shouldReceive('getRecommendation')->andReturn('Add proper validation');
        $finding->shouldReceive('getCategory')->andReturn('security');
        $finding->shouldReceive('getComponent')->andReturn('UserModel');
        
        return $finding;
    }

    private function createFindingWithoutFileReferences(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('no-file-refs-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => ['analysis_result' => 'Some analysis']
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => 'test', 'value' => 1, 'unit' => 'count']
        ]);
        $finding->shouldReceive('getRecommendation')->andReturn('Fix issue');
        $finding->shouldReceive('getCategory')->andReturn('maintainability');
        $finding->shouldReceive('getComponent')->andReturn('TestComponent');
        
        return $finding;
    }

    private function createFindingWithoutMetrics(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('no-metrics-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => ['analysis_result' => 'Some analysis']
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => 'test.php', 'line' => 1, 'context' => 'test context']
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([]);
        $finding->shouldReceive('getRecommendation')->andReturn('Fix issue');
        $finding->shouldReceive('getCategory')->andReturn('performance');
        $finding->shouldReceive('getComponent')->andReturn('TestComponent');
        
        return $finding;
    }

    private function createFindingWithoutEvidence(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('no-evidence-finding');
        $finding->shouldReceive('getEvidence')->andReturn([]);
        $finding->shouldReceive('getFileReferences')->andReturn([]);
        $finding->shouldReceive('getMetrics')->andReturn([]);
        $finding->shouldReceive('getRecommendation')->andReturn('');
        $finding->shouldReceive('getCategory')->andReturn('');
        $finding->shouldReceive('getComponent')->andReturn('');
        
        return $finding;
    }

    private function createHighQualityFinding(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('high-quality-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => [
                'analysis_result' => 'Comprehensive analysis with detailed findings',
                'tool_used' => 'PHPStan',
                'confidence_level' => 95,
                'verified' => true,
                'cross_validated' => true
            ]
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => 'app/test.php', 'line' => 42, 'context' => 'detailed context information']
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => 'complexity', 'value' => 20, 'unit' => 'score']
        ]);
        $finding->shouldReceive('getRecommendation')->andReturn('Detailed recommendation');
        $finding->shouldReceive('getCategory')->andReturn('security');
        $finding->shouldReceive('getComponent')->andReturn('TestComponent');
        
        return $finding;
    }

    private function createLowQualityFinding(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('low-quality-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'user_report' => [
                'description' => 'Brief description',
                'confidence_level' => 40
            ]
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => 'test.php', 'line' => 1, 'context' => 'minimal context']
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => 'test', 'value' => 1, 'unit' => 'count']
        ]);
        $finding->shouldReceive('getRecommendation')->andReturn('Fix');
        $finding->shouldReceive('getCategory')->andReturn('other');
        $finding->shouldReceive('getComponent')->andReturn('Unknown');
        
        return $finding;
    }

    private function createFindingWithValidFileReferences(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('valid-refs-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => ['analysis_result' => 'test']
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => 'app/Models/User.php', 'line' => 42, 'context' => 'public function validate() { return true; }'],
            ['file' => 'app/Controllers/UserController.php', 'line' => 15, 'context' => '$user->validate();']
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => 'test', 'value' => 1, 'unit' => 'count']
        ]);
        $finding->shouldReceive('getRecommendation')->andReturn('Test');
        $finding->shouldReceive('getCategory')->andReturn('test');
        $finding->shouldReceive('getComponent')->andReturn('Test');
        
        return $finding;
    }

    private function createFindingWithInvalidFileReferences(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('invalid-refs-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => ['analysis_result' => 'test']
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => '', 'line' => 42, 'context' => 'test'], // Invalid: empty file
            ['file' => 'test.php', 'line' => -1, 'context' => 'test'], // Invalid: negative line
            ['file' => 'test.php', 'line' => 1, 'context' => ''] // Invalid: empty context
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => 'test', 'value' => 1, 'unit' => 'count']
        ]);
        $finding->shouldReceive('getRecommendation')->andReturn('Test');
        $finding->shouldReceive('getCategory')->andReturn('test');
        $finding->shouldReceive('getComponent')->andReturn('Test');
        
        return $finding;
    }

    private function createFindingWithValidMetrics(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('valid-metrics-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => ['analysis_result' => 'test']
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => 'test.php', 'line' => 1, 'context' => 'test context']
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => 'complexity', 'value' => 15, 'unit' => 'score'],
            ['name' => 'coverage', 'value' => 85.5, 'unit' => 'percent']
        ]);
        $finding->shouldReceive('getRecommendation')->andReturn('Test');
        $finding->shouldReceive('getCategory')->andReturn('test');
        $finding->shouldReceive('getComponent')->andReturn('Test');
        
        return $finding;
    }

    private function createFindingWithInvalidMetrics(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('invalid-metrics-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => ['analysis_result' => 'test']
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => 'test.php', 'line' => 1, 'context' => 'test context']
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => '', 'value' => 15, 'unit' => 'score'], // Invalid: empty name
            ['name' => 'test', 'value' => 'invalid', 'unit' => 'score'], // Invalid: non-numeric value
            ['name' => 'test2', 'value' => 10, 'unit' => ''] // Invalid: empty unit
        ]);
        $finding->shouldReceive('getRecommendation')->andReturn('Test');
        $finding->shouldReceive('getCategory')->andReturn('test');
        $finding->shouldReceive('getComponent')->andReturn('Test');
        
        return $finding;
    }

    private function createReproducibleFinding(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('reproducible-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => [
                'analysis_result' => 'Reproducible analysis',
                'tool_used' => 'PHPStan',
                'reproducible' => true,
                'test_conditions' => 'PHP 8.1, PHPStan 1.0'
            ]
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => 'app/test.php', 'line' => 42, 'context' => 'reproducible context']
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => 'complexity', 'value' => 15, 'unit' => 'score']
        ]);
        $finding->shouldReceive('getRecommendation')->andReturn('Test');
        $finding->shouldReceive('getCategory')->andReturn('test');
        $finding->shouldReceive('getComponent')->andReturn('Test');
        
        return $finding;
    }

    private function createNonReproducibleFinding(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('non-reproducible-finding');
        $finding->shouldReceive('getEvidence')->andReturn([
            'user_report' => [
                'description' => 'Non-reproducible user report',
                'reproducible' => false
            ]
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([]);
        $finding->shouldReceive('getMetrics')->andReturn([]);
        $finding->shouldReceive('getRecommendation')->andReturn('Test');
        $finding->shouldReceive('getCategory')->andReturn('test');
        $finding->shouldReceive('getComponent')->andReturn('Test');
        
        return $finding;
    }

    private function createMalformedFinding(): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn('malformed-finding');
        $finding->shouldReceive('getEvidence')->andThrow(new \Exception('Malformed evidence data'));
        $finding->shouldReceive('getFileReferences')->andReturn([]);
        $finding->shouldReceive('getMetrics')->andReturn([]);
        $finding->shouldReceive('getRecommendation')->andReturn('');
        $finding->shouldReceive('getCategory')->andReturn('');
        $finding->shouldReceive('getComponent')->andReturn('');
        
        return $finding;
    }
}