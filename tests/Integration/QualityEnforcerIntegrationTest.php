<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\Audit\Quality\QualityEnforcer;
use App\Services\Audit\Quality\EvidenceValidator;
use App\Services\Audit\Quality\SpeculativeFindingDetector;
use App\Services\Audit\Quality\EvidenceQualityScorer;
use App\Services\Audit\Quality\ReportDowngrader;
use App\Services\Audit\Quality\AccountabilityMetricsTracker;
use App\Services\Audit\Models\Finding;

class QualityEnforcerIntegrationTest extends TestCase
{
    private QualityEnforcer $qualityEnforcer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create real instances for integration testing
        $evidenceValidator = new EvidenceValidator();
        $speculationDetector = new SpeculativeFindingDetector();
        $qualityScorer = new EvidenceQualityScorer();
        $reportDowngrader = new ReportDowngrader();
        $accountabilityTracker = new AccountabilityMetricsTracker();
        
        $this->qualityEnforcer = new QualityEnforcer(
            $evidenceValidator,
            $speculationDetector,
            $qualityScorer,
            $reportDowngrader,
            $accountabilityTracker
        );
    }

    /** @test */
    public function it_processes_mixed_quality_findings_end_to_end()
    {
        // Arrange - Create test findings with different quality levels
        $findings = [
            $this->createHighQualityFinding(),
            $this->createLowQualityFinding(),
            $this->createSpeculativeFinding(),
            $this->createIncompleteEvidenceFinding()
        ];

        // Act
        $result = $this->qualityEnforcer->enforceQualityStandards($findings);

        // Assert - Verify the quality enforcement pipeline worked
        $this->assertArrayHasKey('validated_findings', $result);
        $this->assertArrayHasKey('rejected_findings', $result);
        $this->assertArrayHasKey('processed_findings', $result);
        $this->assertArrayHasKey('quality_metrics', $result);
        $this->assertArrayHasKey('accountability_metrics', $result);

        // Verify 100% validation coverage
        $this->assertEquals(100.0, $result['quality_metrics']['validation_coverage']);

        // Verify some findings were processed (high quality should pass through)
        $this->assertGreaterThan(0, count($result['processed_findings']));

        // Verify some findings were rejected (speculative and incomplete should be rejected)
        $this->assertGreaterThan(0, count($result['rejected_findings']));

        // Verify accountability metrics are tracked
        $this->assertArrayHasKey('overall_accountability_score', $result['accountability_metrics']);
        $this->assertArrayHasKey('accountability_grade', $result['accountability_metrics']);

        echo "\n=== Quality Enforcer Integration Test Results ===\n";
        echo "Original findings: " . count($findings) . "\n";
        echo "Validated findings: " . count($result['validated_findings']) . "\n";
        echo "Rejected findings: " . count($result['rejected_findings']) . "\n";
        echo "Final processed findings: " . count($result['processed_findings']) . "\n";
        echo "Validation coverage: " . $result['quality_metrics']['validation_coverage'] . "%\n";
        echo "Overall accountability score: " . $result['accountability_metrics']['overall_accountability_score'] . "\n";
        echo "Accountability grade: " . $result['accountability_metrics']['accountability_grade'] . "\n";
        echo "=== End Results ===\n";
    }

    /** @test */
    public function it_enforces_evidence_validation_requirements()
    {
        // Arrange - Create findings with missing evidence components
        $findings = [
            $this->createFindingWithoutFileReferences(),
            $this->createFindingWithoutMetrics(),
            $this->createFindingWithoutEvidence()
        ];

        // Act
        $result = $this->qualityEnforcer->enforceQualityStandards($findings);

        // Assert - All should be rejected due to missing evidence
        $this->assertEquals(3, count($result['rejected_findings']));
        $this->assertEquals(0, count($result['processed_findings']));

        // Verify rejection reasons include evidence gaps
        foreach ($result['rejected_findings'] as $rejection) {
            $this->assertArrayHasKey('validation_result', $rejection);
            $this->assertArrayHasKey('evidence_gaps', $rejection['validation_result']);
            $this->assertNotEmpty($rejection['validation_result']['evidence_gaps']);
        }
    }

    /** @test */
    public function it_detects_and_removes_speculative_findings()
    {
        // Arrange - Create findings with speculative characteristics
        $findings = [
            $this->createSpeculativeFinding(),
            $this->createFindingWithUncertainLanguage(),
            $this->createHighQualityFinding() // Control - should not be flagged as speculative
        ];

        // Act
        $result = $this->qualityEnforcer->enforceQualityStandards($findings);

        // Assert - Speculative findings should be identified and handled
        $this->assertArrayHasKey('speculation_analysis', $result);
        
        $speculativeCount = 0;
        foreach ($result['speculation_analysis'] as $analysis) {
            if ($analysis['is_speculative']) {
                $speculativeCount++;
            }
        }
        
        $this->assertGreaterThan(0, $speculativeCount);
        $this->assertLessThan(count($findings), count($result['processed_findings'])); // Some should be filtered out
    }

    private function createHighQualityFinding(): Finding
    {
        return new class implements Finding {
            public function getId(): string { return 'high-quality-finding'; }
            public function getSeverity(): string { return 'high'; }
            public function setSeverity(string $severity): self { return $this; }
            public function getDescription(): string { 
                return 'This finding has comprehensive evidence with detailed analysis and concrete metrics.'; 
            }
            public function setDescription(string $description): self { return $this; }
            public function getRecommendation(): string { 
                return 'Implement the recommended security fix with proper validation.'; 
            }
            public function setRecommendation(string $recommendation): self { return $this; }
            public function getEvidence(): array {
                return [
                    'static_analysis' => [
                        'analysis_result' => 'Comprehensive static analysis detected security vulnerability',
                        'tool_used' => 'PHPStan',
                        'confidence_level' => 95,
                        'verified' => true,
                        'cross_validated' => true
                    ],
                    'code_inspection' => [
                        'code_location' => 'app/Http/Controllers/UserController.php:42',
                        'issue_description' => 'Missing input validation allows SQL injection',
                        'severity_justification' => 'Direct database query with user input'
                    ]
                ];
            }
            public function getFileReferences(): array {
                return [
                    [
                        'file' => 'app/Http/Controllers/UserController.php',
                        'line' => 42,
                        'context' => 'DB::select("SELECT * FROM users WHERE id = " . $request->id);'
                    ]
                ];
            }
            public function getMetrics(): array {
                return [
                    ['name' => 'security_risk_score', 'value' => 9.5, 'unit' => 'score'],
                    ['name' => 'affected_endpoints', 'value' => 3, 'unit' => 'count']
                ];
            }
            public function getCategory(): string { return 'security'; }
            public function getComponent(): string { return 'UserController'; }
        };
    }

    private function createLowQualityFinding(): Finding
    {
        return new class implements Finding {
            public function getId(): string { return 'low-quality-finding'; }
            public function getSeverity(): string { return 'medium'; }
            public function setSeverity(string $severity): self { return $this; }
            public function getDescription(): string { 
                return 'This code might have issues.'; 
            }
            public function setDescription(string $description): self { return $this; }
            public function getRecommendation(): string { 
                return 'Fix it.'; 
            }
            public function setRecommendation(string $recommendation): self { return $this; }
            public function getEvidence(): array {
                return [
                    'user_report' => [
                        'description' => 'User said it seems slow',
                        'confidence_level' => 30
                    ]
                ];
            }
            public function getFileReferences(): array {
                return [
                    ['file' => 'app/test.php', 'line' => 1, 'context' => 'test']
                ];
            }
            public function getMetrics(): array {
                return [
                    ['name' => 'performance', 'value' => 1, 'unit' => 'score']
                ];
            }
            public function getCategory(): string { return 'performance'; }
            public function getComponent(): string { return 'Unknown'; }
        };
    }

    private function createSpeculativeFinding(): Finding
    {
        return new class implements Finding {
            public function getId(): string { return 'speculative-finding'; }
            public function getSeverity(): string { return 'medium'; }
            public function setSeverity(string $severity): self { return $this; }
            public function getDescription(): string { 
                return 'This code might potentially have issues that could possibly cause problems.'; 
            }
            public function setDescription(string $description): self { return $this; }
            public function getRecommendation(): string { 
                return 'Maybe consider looking into this issue.'; 
            }
            public function setRecommendation(string $recommendation): self { return $this; }
            public function getEvidence(): array {
                return [
                    'assumption' => [
                        'description' => 'Based on theoretical analysis',
                        'confidence_level' => 20
                    ]
                ];
            }
            public function getFileReferences(): array { return []; }
            public function getMetrics(): array { return []; }
            public function getCategory(): string { return 'maintainability'; }
            public function getComponent(): string { return 'Unknown'; }
        };
    }

    private function createIncompleteEvidenceFinding(): Finding
    {
        return new class implements Finding {
            public function getId(): string { return 'incomplete-evidence-finding'; }
            public function getSeverity(): string { return 'low'; }
            public function setSeverity(string $severity): self { return $this; }
            public function getDescription(): string { return 'Finding with incomplete evidence'; }
            public function setDescription(string $description): self { return $this; }
            public function getRecommendation(): string { return 'Fix the issue'; }
            public function setRecommendation(string $recommendation): self { return $this; }
            public function getEvidence(): array { return []; } // No evidence
            public function getFileReferences(): array { return []; } // No file references
            public function getMetrics(): array { return []; } // No metrics
            public function getCategory(): string { return 'other'; }
            public function getComponent(): string { return 'Unknown'; }
        };
    }

    private function createFindingWithoutFileReferences(): Finding
    {
        return new class implements Finding {
            public function getId(): string { return 'no-file-refs-finding'; }
            public function getSeverity(): string { return 'medium'; }
            public function setSeverity(string $severity): self { return $this; }
            public function getDescription(): string { return 'Finding without file references'; }
            public function setDescription(string $description): self { return $this; }
            public function getRecommendation(): string { return 'Add file references'; }
            public function setRecommendation(string $recommendation): self { return $this; }
            public function getEvidence(): array {
                return [
                    'static_analysis' => ['analysis_result' => 'Some analysis']
                ];
            }
            public function getFileReferences(): array { return []; } // Missing file references
            public function getMetrics(): array {
                return [['name' => 'test', 'value' => 1, 'unit' => 'count']];
            }
            public function getCategory(): string { return 'maintainability'; }
            public function getComponent(): string { return 'TestComponent'; }
        };
    }

    private function createFindingWithoutMetrics(): Finding
    {
        return new class implements Finding {
            public function getId(): string { return 'no-metrics-finding'; }
            public function getSeverity(): string { return 'medium'; }
            public function setSeverity(string $severity): self { return $this; }
            public function getDescription(): string { return 'Finding without metrics'; }
            public function setDescription(string $description): self { return $this; }
            public function getRecommendation(): string { return 'Add metrics'; }
            public function setRecommendation(string $recommendation): self { return $this; }
            public function getEvidence(): array {
                return [
                    'static_analysis' => ['analysis_result' => 'Some analysis']
                ];
            }
            public function getFileReferences(): array {
                return [['file' => 'test.php', 'line' => 1, 'context' => 'test context']];
            }
            public function getMetrics(): array { return []; } // Missing metrics
            public function getCategory(): string { return 'performance'; }
            public function getComponent(): string { return 'TestComponent'; }
        };
    }

    private function createFindingWithoutEvidence(): Finding
    {
        return new class implements Finding {
            public function getId(): string { return 'no-evidence-finding'; }
            public function getSeverity(): string { return 'low'; }
            public function setSeverity(string $severity): self { return $this; }
            public function getDescription(): string { return 'Finding without evidence'; }
            public function setDescription(string $description): self { return $this; }
            public function getRecommendation(): string { return 'Add evidence'; }
            public function setRecommendation(string $recommendation): self { return $this; }
            public function getEvidence(): array { return []; } // Missing evidence
            public function getFileReferences(): array { return []; }
            public function getMetrics(): array { return []; }
            public function getCategory(): string { return 'other'; }
            public function getComponent(): string { return 'Unknown'; }
        };
    }

    private function createFindingWithUncertainLanguage(): Finding
    {
        return new class implements Finding {
            public function getId(): string { return 'uncertain-language-finding'; }
            public function getSeverity(): string { return 'medium'; }
            public function setSeverity(string $severity): self { return $this; }
            public function getDescription(): string { 
                return 'This code seems to have issues that might potentially cause problems. It appears unclear and probably needs attention.'; 
            }
            public function setDescription(string $description): self { return $this; }
            public function getRecommendation(): string { 
                return 'Perhaps consider reviewing this code as it could potentially be improved.'; 
            }
            public function setRecommendation(string $recommendation): self { return $this; }
            public function getEvidence(): array {
                return [
                    'code_inspection' => [
                        'description' => 'Uncertain analysis with low confidence',
                        'confidence_level' => 40
                    ]
                ];
            }
            public function getFileReferences(): array {
                return [['file' => 'app/uncertain.php', 'line' => 10, 'context' => 'uncertain code context']];
            }
            public function getMetrics(): array {
                return [['name' => 'uncertainty', 'value' => 5, 'unit' => 'score']];
            }
            public function getCategory(): string { return 'maintainability'; }
            public function getComponent(): string { return 'UncertainComponent'; }
        };
    }
}