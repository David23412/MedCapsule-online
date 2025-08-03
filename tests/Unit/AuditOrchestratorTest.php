<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Audit\AuditOrchestrator;
use App\Services\Audit\Core\AuditState;
use App\Services\Audit\Components\QualityGateEnforcer;

class AuditOrchestratorTest extends TestCase
{
    private AuditOrchestrator $orchestrator;
    private AuditState $auditState;
    private QualityGateEnforcer $qualityEnforcer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->auditState = new AuditState();
        $this->qualityEnforcer = new QualityGateEnforcer();
        $this->orchestrator = new AuditOrchestrator($this->auditState, $this->qualityEnforcer);
    }

    public function test_all_23_audit_phases_are_defined()
    {
        $phaseSequence = $this->orchestrator->getPhaseSequence();
        
        // Verify we have exactly 23 phases
        $this->assertCount(23, $phaseSequence, 'Should have exactly 23 audit phases defined');
        
        // Verify each phase has required properties
        foreach ($phaseSequence as $phase) {
            $this->assertArrayHasKey('key', $phase);
            $this->assertArrayHasKey('id', $phase);
            $this->assertArrayHasKey('name', $phase);
            $this->assertArrayHasKey('dependencies', $phase);
            $this->assertArrayHasKey('critical', $phase);
            $this->assertArrayHasKey('estimated_minutes', $phase);
            $this->assertArrayHasKey('completed', $phase);
            
            // Verify data types
            $this->assertIsString($phase['key']);
            $this->assertIsInt($phase['id']);
            $this->assertIsString($phase['name']);
            $this->assertIsArray($phase['dependencies']);
            $this->assertIsBool($phase['critical']);
            $this->assertIsInt($phase['estimated_minutes']);
            $this->assertIsBool($phase['completed']);
        }
    }

    public function test_phases_are_properly_sequenced()
    {
        $phaseSequence = $this->orchestrator->getPhaseSequence();
        
        // Verify phases are numbered sequentially from 1 to 23
        $expectedIds = range(1, 23);
        $actualIds = array_column($phaseSequence, 'id');
        
        $this->assertEquals($expectedIds, $actualIds, 'Phase IDs should be sequential from 1 to 23');
        
        // Verify no duplicate phase keys
        $phaseKeys = array_column($phaseSequence, 'key');
        $uniqueKeys = array_unique($phaseKeys);
        $this->assertCount(count($phaseKeys), $uniqueKeys, 'All phase keys should be unique');
    }

    public function test_phase_dependencies_are_valid()
    {
        $phaseSequence = $this->orchestrator->getPhaseSequence();
        $phaseKeys = array_column($phaseSequence, 'key');
        
        foreach ($phaseSequence as $phase) {
            foreach ($phase['dependencies'] as $dependency) {
                $this->assertContains(
                    $dependency, 
                    $phaseKeys, 
                    "Dependency '{$dependency}' for phase '{$phase['key']}' must be a valid phase"
                );
                
                // Find dependency phase and verify it comes before current phase
                $dependencyPhase = array_filter($phaseSequence, fn($p) => $p['key'] === $dependency);
                $dependencyPhase = reset($dependencyPhase);
                
                $this->assertLessThan(
                    $phase['id'],
                    $dependencyPhase['id'],
                    "Dependency '{$dependency}' must come before phase '{$phase['key']}' in sequence"
                );
            }
        }
    }

    public function test_critical_phases_are_identified()
    {
        $criticalPhases = $this->orchestrator->getCriticalPhases();
        
        // Should have critical phases
        $this->assertNotEmpty($criticalPhases, 'Should have critical phases identified');
        
        // Verify critical phases include key components
        $criticalPhaseKeys = array_keys($criticalPhases);
        
        $expectedCriticalPhases = [
            'environment_detection',
            'codebase_structural_analysis',
            'database_architecture_analysis',
            'performance_benchmarking',
            'chaos_injection_testing',
            'security_scanning',
            'infrastructure_assessment',
            'business_logic_validation',
            'dependency_audit'
        ];
        
        foreach ($expectedCriticalPhases as $expectedCritical) {
            $this->assertContains(
                $expectedCritical,
                $criticalPhaseKeys,
                "Phase '{$expectedCritical}' should be marked as critical"
            );
        }
    }

    public function test_total_estimated_time_is_reasonable()
    {
        $totalTime = $this->orchestrator->getTotalEstimatedTime();
        
        // Should be between 15 and 30 hours (900-1800 minutes)
        $this->assertGreaterThan(900, $totalTime, 'Total estimated time should be at least 15 hours');
        $this->assertLessThan(1800, $totalTime, 'Total estimated time should be less than 30 hours');
    }

    public function test_phase_transition_validation()
    {
        // Test valid transition (no dependencies)
        $this->assertTrue(
            $this->orchestrator->validatePhaseTransition('environment_detection'),
            'Should allow transition to phase with no dependencies'
        );
        
        // Test invalid transition (missing dependency)
        $this->assertFalse(
            $this->orchestrator->validatePhaseTransition('performance_benchmarking'),
            'Should reject transition to phase with unmet dependencies'
        );
        
        // Complete dependency and test valid transition
        $this->auditState->markPhaseCompleted('codebase_structural_analysis');
        $this->auditState->markPhaseCompleted('database_architecture_analysis');
        
        $this->assertTrue(
            $this->orchestrator->validatePhaseTransition('performance_benchmarking'),
            'Should allow transition when all dependencies are met'
        );
    }

    public function test_progress_tracking()
    {
        // Initial progress should be 0%
        $progress = $this->orchestrator->trackProgress();
        $this->assertEquals(0.0, $progress->getProgressPercentage());
        $this->assertEquals(1, $progress->getPhaseNumber());
        
        // Complete some phases and check progress
        $this->auditState->markPhaseCompleted('environment_detection');
        $this->auditState->markPhaseCompleted('codebase_structural_analysis');
        
        $progress = $this->orchestrator->trackProgress();
        $expectedPercentage = round((2 / 23) * 100, 2);
        $this->assertEquals($expectedPercentage, $progress->getProgressPercentage());
        $this->assertEquals(3, $progress->getPhaseNumber());
    }

    public function test_phase_names_are_descriptive()
    {
        $phaseSequence = $this->orchestrator->getPhaseSequence();
        
        foreach ($phaseSequence as $phase) {
            // Phase names should be at least 20 characters (descriptive)
            $this->assertGreaterThan(
                20,
                strlen($phase['name']),
                "Phase '{$phase['key']}' name should be descriptive (at least 20 characters)"
            );
            
            // Phase names should not be just the key
            $this->assertNotEquals(
                $phase['key'],
                $phase['name'],
                "Phase name should be different from phase key"
            );
        }
    }
}