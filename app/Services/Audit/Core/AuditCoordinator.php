<?php

namespace App\Services\Audit\Core;

use App\Services\Audit\Quality\QualityEnforcer;
use App\Services\Audit\Reports\AuditReport;
use App\Services\Audit\Core\AuditState;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 CRITICAL REFACTOR: Audit Coordinator
 * 
 * Lightweight coordinator that delegates to specialized services
 * Replaces the God class AuditOrchestrator with focused responsibility
 */
class AuditCoordinator
{
    private AuditPhaseManager $phaseManager;
    private QualityEnforcer $qualityEnforcer;
    private AuditState $state;

    public function __construct(
        AuditPhaseManager $phaseManager,
        QualityEnforcer $qualityEnforcer
    ) {
        $this->phaseManager = $phaseManager;
        $this->qualityEnforcer = $qualityEnforcer;
        $this->state = new AuditState();
    }

    /**
     * Execute comprehensive audit with quality enforcement
     */
    public function executeAudit(array $options = []): AuditReport
    {
        $startTime = microtime(true);
        
        try {
            Log::info('🚀 Starting comprehensive audit execution');
            
            // Initialize audit state
            $this->state->initialize($options);
            
            // Execute all phases through phase manager
            $results = $this->phaseManager->executeAllPhases($this->state);
            
            // Enforce quality gates
            $qualityResults = $this->qualityEnforcer->enforceQualityGates($results);
            
            // Generate final report
            $report = new AuditReport($results, $qualityResults, microtime(true) - $startTime);
            
            Log::info('✅ Audit execution completed successfully', [
                'total_phases' => count($results),
                'execution_time' => $report->getExecutionTime(),
                'quality_score' => $qualityResults['overall_score']
            ]);
            
            return $report;
            
        } catch (Exception $e) {
            Log::error('❌ Audit execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get current audit state
     */
    public function getState(): AuditState
    {
        return $this->state;
    }
}