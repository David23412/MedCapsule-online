<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Components\Contracts\AuditComponentInterface;
use Exception;

/**
 * 🚀 CRITICAL REFACTOR: Component Registry
 * 
 * Registry for audit components with lazy loading
 * Extracted from God class AuditOrchestrator
 */
class ComponentRegistry
{
    private array $components = [];
    private array $componentMap = [
        'environment_detection' => FileScanner::class,
        'codebase_structural_analysis' => CodeAnalyzer::class,
        'database_architecture_analysis' => DatabaseProbe::class,
        'performance_benchmarking' => PerformanceBenchmarker::class,
        'chaos_injection_testing' => \App\Services\Audit\ChaosInjector::class,
        'security_scanning' => SecurityScanner::class,
        'frontend_analysis' => FrontendAnalyzer::class,
        'infrastructure_assessment' => InfrastructureAssessor::class,
        'testing_analysis' => TestingAnalyzer::class,
        'business_logic_validation' => BusinessLogicValidator::class,
        'dependency_audit' => DependencyAuditor::class,
        'differential_integrity_analysis' => DifferentialIntegrityAnalyzer::class,
        'interdependency_tracking' => InterdependencyTracker::class,
        'heuristic_mining' => HeuristicMiner::class,
        'side_effect_detection' => SideEffectSentinel::class,
        'fail_safe_simulation' => FailSafeSimulator::class,
        'domain_score_calculation' => DomainScoreCalculator::class,
        'reproducibility_audit' => ReproducibilityAuditor::class,
        'cognitive_regression_check' => CognitiveRegressionChecker::class,
        'report_integrity_seal' => ReportIntegritySeal::class,
        'quality_gate_enforcement' => QualityGateEnforcer::class,
        'progress_tracking' => \App\Services\Audit\Reports\ProgressReport::class,
        'final_validation' => \App\Services\Audit\Quality\QualityEnforcer::class
    ];

    /**
     * Get component instance (lazy loaded)
     */
    public function getComponent(string $phase): AuditComponentInterface
    {
        if (!isset($this->components[$phase])) {
            if (!isset($this->componentMap[$phase])) {
                throw new Exception("No component registered for phase: {$phase}");
            }
            
            $componentClass = $this->componentMap[$phase];
            $this->components[$phase] = app($componentClass);
        }
        
        return $this->components[$phase];
    }

    /**
     * Register custom component
     */
    public function registerComponent(string $phase, string $componentClass): void
    {
        $this->componentMap[$phase] = $componentClass;
        
        // Clear cached instance if exists
        unset($this->components[$phase]);
    }

    /**
     * Get all registered phases
     */
    public function getRegisteredPhases(): array
    {
        return array_keys($this->componentMap);
    }
}