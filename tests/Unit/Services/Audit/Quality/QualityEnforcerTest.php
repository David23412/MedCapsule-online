<?php

namespace Tests\Unit\Services\Audit\Quality;

use Tests\TestCase;
use App\Services\Audit\Quality\QualityEnforcer;
use App\Services\Audit\Quality\EvidenceValidator;
use App\Services\Audit\Quality\SpeculativeFindingDetector;
use App\Services\Audit\Quality\EvidenceQualityScorer;
use App\Services\Audit\Quality\ReportDowngrader;
use App\Services\Audit\Quality\AccountabilityMetricsTracker;
use App\Services\Audit\Models\Finding;
use Mockery;

class QualityEnforcerTest extends TestCase
{
    private QualityEnforcer $qualityEnforcer;
    private $mockEvidenceValidator;
    private $mockSpeculationDetector;
    private $mockQualityScorer;
    private $mockReportDowngrader;
    private $mockAccountabilityTracker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockEvidenceValidator = Mockery::mock(EvidenceValidator::class);
        $this->mockSpeculationDetector = Mockery::mock(SpeculativeFindingDetector::class);
        $this->mockQualityScorer = Mockery::mock(EvidenceQualityScorer::class);
        $this->mockReportDowngrader = Mockery::mock(ReportDowngrader::class);
        $this->mockAccountabilityTracker = Mockery::mock(AccountabilityMetricsTracker::class);
        
        $this->qualityEnforcer = new QualityEnforcer(
            $this->mockEvidenceValidator,
            $this->mockSpeculationDetector,
            $this->mockQualityScorer,
            $this->mockReportDowngrader,
            $this->mockAccountabilityTracker
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_enforces_quality_with_100_percent_evidence_validation()
    {
        // Arrange
        $findings = [
            $this->createMockFinding('finding-1'),
            $this->createMockFinding('finding-2')
        ];

        $this->mockEvidenceValidator
            ->shouldReceive('validateFindingEvidence')
            ->times(2)
            ->andReturn([
                'finding_id' => 'finding-1',
                'is_valid' => true,
                'validation_score' => 85.0,
                'evidence_completeness' => 90.0
            ], [
                'finding_id' => 'finding-2',
                'is_valid' => false,
                'validation_score' => 45.0,
                'evidence_completeness' => 50.0
            ]);

        $this->mockSpeculationDetector
            ->shouldReceive('analyzeForSpeculation')
            ->once()
            ->andReturn([
                'finding_id' => 'finding-1',
                'is_speculative' => false,
                'speculation_confidence' => 20.0
            ]);

        $this->mockQualityScorer
            ->shouldReceive('calculateEvidenceQualityScore')
            ->once()
            ->andReturn([
                'finding_id' => 'finding-1',
                'overall_score' => 85.0,
                'quality_grade' => 'B+'
            ]);

        $this->mockReportDowngrader
            ->shouldReceive('assessAndDowngradeReport')
            ->once()
            ->andReturn([
                'processed_findings' => [$findings[0]],
                'downgrade_result' => [
                    'original_findings_count' => 1,
                    'final_findings_count' => 1
                ]
            ]);

        $this->mockAccountabilityTracker
            ->shouldReceive('trackAccountabilityMetrics')
            ->once()
            ->andReturn([
                'overall_accountability_score' => 92.0,
                'accountability_grade' => 'A'
            ]);

        // Act
        $result = $this->qualityEnforcer->enforceQualityStandards($findings);

        // Assert
        $this->assertArrayHasKey('validated_findings', $result);
        $this->assertArrayHasKey('rejected_findings', $result);
        $this->assertArrayHasKey('quality_metrics', $result);
        $this->assertArrayHasKey('accountability_metrics', $result);
        
        $this->assertCount(1, $result['validated_findings']);
        $this->assertCount(1, $result['rejected_findings']);
        $this->assertEquals(100.0, $result['quality_metrics']['validation_coverage']);
    }

    /** @test */
    public function it_detects_and_removes_speculative_findings()
    {
        // Arrange
        $finding = $this->createMockFinding('speculative-finding');
        $findings = [$finding];

        $this->mockEvidenceValidator
            ->shouldReceive('validateFindingEvidence')
            ->once()
            ->andReturn([
                'finding_id' => 'speculative-finding',
                'is_valid' => true,
                'validation_score' => 75.0
            ]);

        $this->mockSpeculationDetector
            ->shouldReceive('analyzeForSpeculation')
            ->once()
            ->andReturn([
                'finding_id' => 'speculative-finding',
                'is_speculative' => true,
                'speculation_confidence' => 85.0,
                'speculation_indicators' => ['Contains speculative language', 'Low evidence strength']
            ]);

        $this->mockQualityScorer
            ->shouldReceive('calculateEvidenceQualityScore')
            ->never(); // Should not score speculative findings

        $this->mockReportDowngrader
            ->shouldReceive('assessAndDowngradeReport')
            ->once()
            ->andReturn([
                'processed_findings' => [],
                'downgrade_result' => [
                    'original_findings_count' => 0,
                    'final_findings_count' => 0
                ]
            ]);

        $this->mockAccountabilityTracker
            ->shouldReceive('trackAccountabilityMetrics')
            ->once()
            ->andReturn([
                'overall_accountability_score' => 88.0
            ]);

        // Act
        $result = $this->qualityEnforcer->enforceQualityStandards($findings);

        // Assert
        $this->assertCount(0, $result['validated_findings']);
        $this->assertArrayHasKey('speculation_analysis', $result);
        $this->assertTrue($result['speculation_analysis']['speculative-finding']['is_speculative']);
    }

    /** @test */
    public function it_calculates_evidence_quality_scores()
    {
        // Arrange
        $finding = $this->createMockFinding('quality-finding');
        $findings = [$finding];

        $this->mockEvidenceValidator
            ->shouldReceive('validateFindingEvidence')
            ->once()
            ->andReturn([
                'finding_id' => 'quality-finding',
                'is_valid' => true,
                'validation_score' => 90.0
            ]);

        $this->mockSpeculationDetector
            ->shouldReceive('analyzeForSpeculation')
            ->once()
            ->andReturn([
                'finding_id' => 'quality-finding',
                'is_speculative' => false,
                'speculation_confidence' => 15.0
            ]);

        $this->mockQualityScorer
            ->shouldReceive('calculateEvidenceQualityScore')
            ->once()
            ->andReturn([
                'finding_id' => 'quality-finding',
                'overall_score' => 92.5,
                'quality_grade' => 'A',
                'dimension_scores' => [
                    'completeness' => ['score' => 95.0],
                    'accuracy' => ['score' => 90.0],
                    'relevance' => ['score' => 92.0]
                ]
            ]);

        $this->mockReportDowngrader
            ->shouldReceive('assessAndDowngradeReport')
            ->once()
            ->andReturn([
                'processed_findings' => [$finding],
                'downgrade_result' => [
                    'original_findings_count' => 1,
                    'final_findings_count' => 1
                ]
            ]);

        $this->mockAccountabilityTracker
            ->shouldReceive('trackAccountabilityMetrics')
            ->once()
            ->andReturn([
                'overall_accountability_score' => 95.0
            ]);

        // Act
        $result = $this->qualityEnforcer->enforceQualityStandards($findings);

        // Assert
        $this->assertArrayHasKey('quality_scores', $result);
        $this->assertEquals(92.5, $result['quality_scores']['quality-finding']['overall_score']);
        $this->assertEquals('A', $result['quality_scores']['quality-finding']['quality_grade']);
    }

    /** @test */
    public function it_downgrades_low_quality_sections()
    {
        // Arrange
        $lowQualityFinding = $this->createMockFinding('low-quality-finding');
        $findings = [$lowQualityFinding];

        $this->mockEvidenceValidator
            ->shouldReceive('validateFindingEvidence')
            ->once()
            ->andReturn([
                'finding_id' => 'low-quality-finding',
                'is_valid' => true,
                'validation_score' => 65.0
            ]);

        $this->mockSpeculationDetector
            ->shouldReceive('analyzeForSpeculation')
            ->once()
            ->andReturn([
                'finding_id' => 'low-quality-finding',
                'is_speculative' => false,
                'speculation_confidence' => 35.0
            ]);

        $this->mockQualityScorer
            ->shouldReceive('calculateEvidenceQualityScore')
            ->once()
            ->andReturn([
                'finding_id' => 'low-quality-finding',
                'overall_score' => 55.0,
                'quality_grade' => 'D'
            ]);

        $downgradedFinding = clone $lowQualityFinding;
        $downgradedFinding->setSeverity('low'); // Downgraded from 'medium'

        $this->mockReportDowngrader
            ->shouldReceive('assessAndDowngradeReport')
            ->once()
            ->andReturn([
                'processed_findings' => [$downgradedFinding],
                'downgrade_result' => [
                    'original_findings_count' => 1,
                    'final_findings_count' => 1,
                    'downgraded_findings' => [[
                        'finding_id' => 'low-quality-finding',
                        'original_severity' => 'medium',
                        'new_severity' => 'low',
                        'reason' => 'Quality score below acceptable threshold'
                    ]],
                    'removed_findings' => []
                ]
            ]);

        $this->mockAccountabilityTracker
            ->shouldReceive('trackAccountabilityMetrics')
            ->once()
            ->andReturn([
                'overall_accountability_score' => 78.0
            ]);

        // Act
        $result = $this->qualityEnforcer->enforceQualityStandards($findings);

        // Assert
        $this->assertArrayHasKey('downgrade_result', $result);
        $this->assertCount(1, $result['downgrade_result']['downgraded_findings']);
        $this->assertEquals('low', $result['processed_findings'][0]->getSeverity());
    }

    /** @test */
    public function it_tracks_accountability_metrics()
    {
        // Arrange
        $findings = [$this->createMockFinding('test-finding')];

        $this->mockEvidenceValidator
            ->shouldReceive('validateFindingEvidence')
            ->once()
            ->andReturn([
                'finding_id' => 'test-finding',
                'is_valid' => true,
                'validation_score' => 80.0
            ]);

        $this->mockSpeculationDetector
            ->shouldReceive('analyzeForSpeculation')
            ->once()
            ->andReturn([
                'finding_id' => 'test-finding',
                'is_speculative' => false
            ]);

        $this->mockQualityScorer
            ->shouldReceive('calculateEvidenceQualityScore')
            ->once()
            ->andReturn([
                'finding_id' => 'test-finding',
                'overall_score' => 80.0
            ]);

        $this->mockReportDowngrader
            ->shouldReceive('assessAndDowngradeReport')
            ->once()
            ->andReturn([
                'processed_findings' => $findings,
                'downgrade_result' => []
            ]);

        $expectedAccountabilityMetrics = [
            'overall_accountability_score' => 87.5,
            'accountability_grade' => 'B+',
            'quality_metrics' => [
                'evidence_validation' => [
                    'validation_rate' => 100.0,
                    'validation_accuracy' => 80.0
                ],
                'speculation_detection' => [
                    'speculation_detection_rate' => 0.0,
                    'detection_accuracy' => 100.0
                ]
            ],
            'accountability_violations' => [],
            'compliance_scores' => [
                'evidence_validation' => 100.0,
                'speculation_detection' => 100.0,
                'quality_scoring' => 100.0
            ]
        ];

        $this->mockAccountabilityTracker
            ->shouldReceive('trackAccountabilityMetrics')
            ->once()
            ->andReturn($expectedAccountabilityMetrics);

        // Act
        $result = $this->qualityEnforcer->enforceQualityStandards($findings);

        // Assert
        $this->assertArrayHasKey('accountability_metrics', $result);
        $this->assertEquals(87.5, $result['accountability_metrics']['overall_accountability_score']);
        $this->assertEquals('B+', $result['accountability_metrics']['accountability_grade']);
        $this->assertEmpty($result['accountability_metrics']['accountability_violations']);
    }

    /** @test */
    public function it_handles_mixed_quality_findings_correctly()
    {
        // Arrange
        $highQualityFinding = $this->createMockFinding('high-quality');
        $lowQualityFinding = $this->createMockFinding('low-quality');
        $speculativeFinding = $this->createMockFinding('speculative');
        $findings = [$highQualityFinding, $lowQualityFinding, $speculativeFinding];

        // High quality finding - should pass through
        $this->mockEvidenceValidator
            ->shouldReceive('validateFindingEvidence')
            ->with($highQualityFinding)
            ->once()
            ->andReturn([
                'finding_id' => 'high-quality',
                'is_valid' => true,
                'validation_score' => 95.0
            ]);

        $this->mockSpeculationDetector
            ->shouldReceive('analyzeForSpeculation')
            ->with($highQualityFinding)
            ->once()
            ->andReturn([
                'finding_id' => 'high-quality',
                'is_speculative' => false,
                'speculation_confidence' => 10.0
            ]);

        $this->mockQualityScorer
            ->shouldReceive('calculateEvidenceQualityScore')
            ->with($highQualityFinding)
            ->once()
            ->andReturn([
                'finding_id' => 'high-quality',
                'overall_score' => 95.0,
                'quality_grade' => 'A+'
            ]);

        // Low quality finding - should be downgraded
        $this->mockEvidenceValidator
            ->shouldReceive('validateFindingEvidence')
            ->with($lowQualityFinding)
            ->once()
            ->andReturn([
                'finding_id' => 'low-quality',
                'is_valid' => true,
                'validation_score' => 60.0
            ]);

        $this->mockSpeculationDetector
            ->shouldReceive('analyzeForSpeculation')
            ->with($lowQualityFinding)
            ->once()
            ->andReturn([
                'finding_id' => 'low-quality',
                'is_speculative' => false,
                'speculation_confidence' => 40.0
            ]);

        $this->mockQualityScorer
            ->shouldReceive('calculateEvidenceQualityScore')
            ->with($lowQualityFinding)
            ->once()
            ->andReturn([
                'finding_id' => 'low-quality',
                'overall_score' => 55.0,
                'quality_grade' => 'D'
            ]);

        // Speculative finding - should be rejected
        $this->mockEvidenceValidator
            ->shouldReceive('validateFindingEvidence')
            ->with($speculativeFinding)
            ->once()
            ->andReturn([
                'finding_id' => 'speculative',
                'is_valid' => true,
                'validation_score' => 70.0
            ]);

        $this->mockSpeculationDetector
            ->shouldReceive('analyzeForSpeculation')
            ->with($speculativeFinding)
            ->once()
            ->andReturn([
                'finding_id' => 'speculative',
                'is_speculative' => true,
                'speculation_confidence' => 80.0
            ]);

        // Quality scorer should not be called for speculative finding

        $downgradedLowQuality = clone $lowQualityFinding;
        $downgradedLowQuality->setSeverity('info');

        $this->mockReportDowngrader
            ->shouldReceive('assessAndDowngradeReport')
            ->once()
            ->andReturn([
                'processed_findings' => [$highQualityFinding, $downgradedLowQuality],
                'downgrade_result' => [
                    'original_findings_count' => 2,
                    'final_findings_count' => 2,
                    'downgraded_findings' => [[
                        'finding_id' => 'low-quality',
                        'original_severity' => 'medium',
                        'new_severity' => 'info'
                    ]],
                    'removed_findings' => []
                ]
            ]);

        $this->mockAccountabilityTracker
            ->shouldReceive('trackAccountabilityMetrics')
            ->once()
            ->andReturn([
                'overall_accountability_score' => 82.0,
                'accountability_grade' => 'B'
            ]);

        // Act
        $result = $this->qualityEnforcer->enforceQualityStandards($findings);

        // Assert
        $this->assertCount(2, $result['validated_findings']); // High quality + downgraded low quality
        $this->assertCount(1, $result['rejected_findings']); // Speculative finding
        $this->assertCount(2, $result['processed_findings']); // Final processed findings
        
        // Verify quality enforcement metrics
        $this->assertEquals(100.0, $result['quality_metrics']['validation_coverage']);
        $this->assertGreaterThan(0, $result['quality_metrics']['speculation_rejection_rate']);
        $this->assertGreaterThan(0, $result['quality_metrics']['quality_downgrade_rate']);
    }

    private function createMockFinding(string $id): Finding
    {
        $finding = Mockery::mock(Finding::class);
        $finding->shouldReceive('getId')->andReturn($id);
        $finding->shouldReceive('getSeverity')->andReturn('medium');
        $finding->shouldReceive('setSeverity')->andReturnSelf();
        $finding->shouldReceive('getDescription')->andReturn('Test finding description');
        $finding->shouldReceive('setDescription')->andReturnSelf();
        $finding->shouldReceive('getRecommendation')->andReturn('Test recommendation');
        $finding->shouldReceive('setRecommendation')->andReturnSelf();
        $finding->shouldReceive('getEvidence')->andReturn([
            'static_analysis' => [
                'analysis_result' => 'Test analysis',
                'confidence_level' => 85
            ]
        ]);
        $finding->shouldReceive('getFileReferences')->andReturn([
            ['file' => 'test.php', 'line' => 42, 'context' => 'test context']
        ]);
        $finding->shouldReceive('getMetrics')->andReturn([
            ['name' => 'complexity', 'value' => 15, 'unit' => 'score']
        ]);
        $finding->shouldReceive('getCategory')->andReturn('maintainability');
        $finding->shouldReceive('getComponent')->andReturn('TestComponent');
        
        return $finding;
    }
}