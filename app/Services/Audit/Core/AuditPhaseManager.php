<?php

namespace App\Services\Audit\Core;

use App\Services\Audit\Components\ComponentRegistry;
use App\Services\Audit\Core\AuditState;
use App\Services\Audit\Core\PhaseResult;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 CRITICAL REFACTOR: Audit Phase Manager
 * 
 * Manages execution of audit phases in proper sequence
 * Extracted from God class AuditOrchestrator
 */
class AuditPhaseManager
{
    private ComponentRegistry $componentRegistry;
    private array $auditPhases = [
        'environment_detection',
        'codebase_structural_analysis',
        'database_architecture_analysis',
        'performance_benchmarking',
        'chaos_injection_testing',
        'security_scanning',
        'frontend_analysis',
        'infrastructure_assessment',
        'testing_analysis',
        'business_logic_validation',
        'dependency_audit',
        'differential_integrity_analysis',
        'interdependency_tracking',
        'heuristic_mining',
        'side_effect_detection',
        'fail_safe_simulation',
        'domain_score_calculation',
        'reproducibility_audit',
        'cognitive_regression_check',
        'report_integrity_seal',
        'quality_gate_enforcement',
        'progress_tracking',
        'final_validation'
    ];

    public function __construct(ComponentRegistry $componentRegistry)
    {
        $this->componentRegistry = $componentRegistry;
    }

    /**
     * Execute all audit phases in sequence
     */
    public function executeAllPhases(AuditState $state): array
    {
        $results = [];
        
        foreach ($this->auditPhases as $phase) {
            try {
                Log::info("🔍 Executing audit phase: {$phase}");
                
                $component = $this->componentRegistry->getComponent($phase);
                $result = $component->execute($state);
                
                $results[$phase] = new PhaseResult($phase, $result, true);
                
                // Update state with phase results
                $state->addPhaseResult($phase, $result);
                
            } catch (Exception $e) {
                Log::error("❌ Phase {$phase} failed", [
                    'error' => $e->getMessage()
                ]);
                
                $results[$phase] = new PhaseResult($phase, [], false, $e->getMessage());
            }
        }
        
        return $results;
    }

    /**
     * Execute specific phase
     */
    public function executePhase(string $phase, AuditState $state): PhaseResult
    {
        if (!in_array($phase, $this->auditPhases)) {
            throw new Exception("Unknown audit phase: {$phase}");
        }
        
        $component = $this->componentRegistry->getComponent($phase);
        $result = $component->execute($state);
        
        return new PhaseResult($phase, $result, true);
    }

    /**
     * Get available phases
     */
    public function getAvailablePhases(): array
    {
        return $this->auditPhases;
    }
}