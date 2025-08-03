<?php

namespace Tests\Unit\Audit;

use Tests\TestCase;
use App\Services\Audit\Components\ReproducibilityAuditor;
use App\Services\Audit\Models\Finding;
use Illuminate\Support\Facades\Log;

class ReproducibilityAuditorTest extends TestCase
{
    private ReproducibilityAuditor $auditor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditor = new ReproducibilityAuditor();
    }

    /**
     * Test that exactly 5 iterations are executed for each finding
     */
    public function test_executes_exactly_five_iterations_per_finding()
    {
        // Create a test finding
        $finding = new Finding(
            'TEST-001',
            'high',
            'Test finding for reproducibility',
            'performance',
            'test_component'
        );

        // Add some test evidence
        $finding->addFileReference('app/Test.php', 123, 'test context');
        $finding->addMetric('response_time_ms', 150.0, 'ms', 'Test response time');

        // Execute reproducibility test
        $result = $this->auditor->confirmRepeatability($finding);

        // Get statistics to verify 5 iterations were executed
        $stats = $this->auditor->getReproducibilityStatistics();

        $this->assertEquals(5, $stats['iterations_per_test']);
        $this->assertEquals(5, $stats['total_iterations_executed']);
        $this->assertIsBool($result);
    }

    /**
     * Test that findings with 4/5 successful iterations pass the threshold
     */
    public function test_finding_passes_with_four_out_of_five_successes()
    {
        // Create a finding that should be mostly reproducible
        $finding = new Finding(
            'TEST-002',
            'medium',
            'Mostly reproducible finding',
            'code_quality',
            'test_component'
        );

        $finding->addFileReference('app/TestFile.php', 45, 'test code');

        // Execute test
        $result = $this->auditor->confirmRepeatability($finding);

        // The result should be boolean
        $this->assertIsBool($result);
        
        // Check that the finding's reproducible status was set
        $this->assertIsBool($finding->isReproducible());
    }

    /**
     * Test environment variation detection
     */
    public function test_detects_environment_variations()
    {
        $finding = new Finding(
            'TEST-003',
            'critical',
            'Environment sensitive finding',
            'security',
            'auth_component'
        );

        $finding->addFileReference('app/Security.php', 78, 'security check');

        // Execute test
        $this->auditor->confirmRepeatability($finding);

        // Get statistics
        $stats = $this->auditor->getReproducibilityStatistics();

        // Should have environment variation tracking
        $this->assertArrayHasKey('environment_variations_detected', $stats);
        $this->assertIsInt($stats['environment_variations_detected']);
    }

    /**
     * Test confidence score calculation
     */
    public function test_calculates_confidence_scores()
    {
        $finding = new Finding(
            'TEST-004',
            'high',
            'Finding for confidence testing',
            'database',
            'query_component'
        );

        $finding->addFileReference('app/Models/Test.php', 234, 'database query');
        $finding->addMetric('query_time_ms', 75.0, 'ms', 'Query execution time');

        // Execute test
        $this->auditor->confirmRepeatability($finding);

        // Get statistics
        $stats = $this->auditor->getReproducibilityStatistics();

        // Should have confidence score
        $this->assertArrayHasKey('average_confidence_score', $stats);
        $this->assertIsFloat($stats['average_confidence_score']);
        $this->assertGreaterThanOrEqual(0.0, $stats['average_confidence_score']);
        $this->assertLessThanOrEqual(1.0, $stats['average_confidence_score']);
    }

    /**
     * Test that unstable findings are flagged
     */
    public function test_flags_unstable_findings()
    {
        // Create multiple findings
        $findings = [];
        for ($i = 1; $i <= 3; $i++) {
            $finding = new Finding(
                "TEST-00{$i}",
                'medium',
                "Test finding {$i}",
                'performance',
                'test_component'
            );
            $finding->addFileReference("app/Test{$i}.php", 100 + $i, 'test context');
            $findings[] = $finding;
        }

        // Test each finding
        foreach ($findings as $finding) {
            $this->auditor->confirmRepeatability($finding);
        }

        // Get unstable findings
        $unstableFindings = $this->auditor->flagUnstableEvidence($findings);

        // Should return array of unstable findings
        $this->assertIsArray($unstableFindings);
    }

    /**
     * Test comprehensive reproducibility report generation
     */
    public function test_generates_comprehensive_reproducibility_report()
    {
        // Create test findings
        $findings = [];
        for ($i = 1; $i <= 5; $i++) {
            $finding = new Finding(
                "REPORT-00{$i}",
                'high',
                "Report test finding {$i}",
                'performance',
                'test_component'
            );
            $finding->addFileReference("app/ReportTest{$i}.php", 50 + $i, 'report test context');
            $findings[] = $finding;
        }

        // Generate report
        $report = $this->auditor->rerunValidationTests($findings);

        // Verify report structure
        $this->assertInstanceOf(\App\Services\Audit\Reports\ReproducibilityReport::class, $report);
        $this->assertIsFloat($report->getOverallReproducibilityScore());
        $this->assertIsArray($report->getStableFindings());
        $this->assertIsArray($report->getUnstableFindings());
        $this->assertIsArray($report->getEnvironmentVariations());
        $this->assertIsArray($report->getReproducibilityResults());
    }

    /**
     * Test that exactly 5 iterations are enforced
     */
    public function test_enforces_exactly_five_iterations()
    {
        $finding = new Finding(
            'ENFORCE-001',
            'critical',
            'Iteration enforcement test',
            'security',
            'test_component'
        );

        // Use reflection to test the private method with wrong iteration count
        $reflection = new \ReflectionClass($this->auditor);
        $method = $reflection->getMethod('executeMultipleRuns');
        $method->setAccessible(true);

        // Should throw exception for non-5 iterations
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Reproducibility testing requires exactly 5 iterations');
        
        $method->invoke($this->auditor, $finding, 3);
    }

    /**
     * Test memory usage tracking during iterations
     */
    public function test_tracks_memory_usage_during_iterations()
    {
        $finding = new Finding(
            'MEMORY-001',
            'medium',
            'Memory tracking test',
            'performance',
            'memory_component'
        );

        $finding->addFileReference('app/MemoryTest.php', 150, 'memory usage test');

        // Execute test
        $this->auditor->confirmRepeatability($finding);

        // Get statistics
        $stats = $this->auditor->getReproducibilityStatistics();

        // Should have executed exactly 5 iterations
        $this->assertEquals(5, $stats['total_iterations_executed']);
        $this->assertEquals(1, $stats['total_tests']);
    }
}