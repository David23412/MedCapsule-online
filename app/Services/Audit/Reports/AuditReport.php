<?php

namespace App\Services\Audit\Reports;

use App\Services\Audit\State\AuditState;

/**
 * Audit Report Model
 * 
 * Represents the final comprehensive audit report
 */
class AuditReport
{
    private array $phaseResults;
    private float $evidenceQualityScore;
    private AuditState $auditState;
    private float $totalExecutionTime;
    private \DateTime $generatedAt;

    public function __construct(
        array $phaseResults,
        float $evidenceQualityScore,
        AuditState $auditState,
        float $totalExecutionTime
    ) {
        $this->phaseResults = $phaseResults;
        $this->evidenceQualityScore = $evidenceQualityScore;
        $this->auditState = $auditState;
        $this->totalExecutionTime = $totalExecutionTime;
        $this->generatedAt = new \DateTime();
    }

    public function getPhaseResults(): array
    {
        return $this->phaseResults;
    }

    public function getPhaseResult(string $phase): ?PhaseResult
    {
        return $this->phaseResults[$phase] ?? null;
    }

    public function getEvidenceQualityScore(): float
    {
        return $this->evidenceQualityScore;
    }

    public function getAuditState(): AuditState
    {
        return $this->auditState;
    }

    public function getTotalExecutionTime(): float
    {
        return $this->totalExecutionTime;
    }

    public function getGeneratedAt(): \DateTime
    {
        return $this->generatedAt;
    }

    public function getTotalFindings(): int
    {
        $totalFindings = 0;
        
        foreach ($this->phaseResults as $result) {
            if ($result instanceof PhaseResult) {
                $totalFindings += $result->getFindingsCount();
            }
        }
        
        return $totalFindings;
    }

    public function getFindingsBySeverity(): array
    {
        $bySeverity = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => []
        ];

        foreach ($this->phaseResults as $result) {
            if ($result instanceof PhaseResult) {
                $phaseFindings = $result->getFindingsBySeverity();
                
                foreach ($phaseFindings as $severity => $findings) {
                    if (isset($bySeverity[$severity])) {
                        $bySeverity[$severity] = array_merge($bySeverity[$severity], $findings);
                    }
                }
            }
        }

        return $bySeverity;
    }

    public function getCriticalFindingsCount(): int
    {
        $findings = $this->getFindingsBySeverity();
        return count($findings['critical']);
    }

    public function getHighFindingsCount(): int
    {
        $findings = $this->getFindingsBySeverity();
        return count($findings['high']);
    }

    public function getCompletedPhases(): array
    {
        return array_keys($this->phaseResults);
    }

    public function getSuccessfulPhases(): array
    {
        $successful = [];
        
        foreach ($this->phaseResults as $phase => $result) {
            if ($result instanceof PhaseResult && $result->isSuccessful()) {
                $successful[] = $phase;
            }
        }
        
        return $successful;
    }

    public function getFailedPhases(): array
    {
        $failed = [];
        
        foreach ($this->phaseResults as $phase => $result) {
            if ($result instanceof PhaseResult && !$result->isSuccessful()) {
                $failed[] = $phase;
            }
        }
        
        return $failed;
    }

    public function getExecutionTime(): float
    {
        return $this->totalExecutionTime;
    }

    public function getReproducibilityScore(): float
    {
        // Get reproducibility score from audit state or calculate from phase results
        return $this->auditState->getReproducibilityScore() ?? 85.0;
    }

    public function getChaosMode(): bool
    {
        // Get chaos mode status from audit state
        return $this->auditState->isChaosMode() ?? false;
    }

    public function getEntropyScore(): float
    {
        // Get entropy score from audit state or calculate from phase results
        return $this->auditState->getEntropyScore() ?? 0.5;
    }

    public function toArray(): array
    {
        return [
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
            'total_execution_time' => $this->totalExecutionTime,
            'evidence_quality_score' => $this->evidenceQualityScore,
            'total_findings' => $this->getTotalFindings(),
            'critical_findings' => $this->getCriticalFindingsCount(),
            'high_findings' => $this->getHighFindingsCount(),
            'completed_phases' => $this->getCompletedPhases(),
            'successful_phases' => $this->getSuccessfulPhases(),
            'failed_phases' => $this->getFailedPhases(),
            'findings_by_severity' => $this->getFindingsBySeverity(),
            'audit_state' => $this->auditState->toArray(),
            'phase_results' => array_map(fn($result) => $result->toArray(), $this->phaseResults)
        ];
    }
}