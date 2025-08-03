<?php

namespace App\Services\Audit\Reports;

/**
 * Progress Report Model
 * 
 * Represents the current progress of an audit execution
 */
class ProgressReport
{
    private string $currentPhase;
    private int $phaseNumber;
    private int $totalPhases;
    private float $progressPercentage;
    private array $phaseResults;
    private float $elapsedTime;

    public function __construct(
        string $currentPhase,
        int $phaseNumber,
        int $totalPhases,
        float $progressPercentage,
        array $phaseResults = [],
        float $elapsedTime = 0.0
    ) {
        $this->currentPhase = $currentPhase;
        $this->phaseNumber = $phaseNumber;
        $this->totalPhases = $totalPhases;
        $this->progressPercentage = $progressPercentage;
        $this->phaseResults = $phaseResults;
        $this->elapsedTime = $elapsedTime;
    }

    public function getCurrentPhase(): string
    {
        return $this->currentPhase;
    }

    public function getPhaseNumber(): int
    {
        return $this->phaseNumber;
    }

    public function getTotalPhases(): int
    {
        return $this->totalPhases;
    }

    public function getProgressPercentage(): float
    {
        return $this->progressPercentage;
    }

    public function getPhaseResults(): array
    {
        return $this->phaseResults;
    }

    public function getElapsedTime(): float
    {
        return $this->elapsedTime;
    }

    public function getCompletedPhases(): array
    {
        return array_keys($this->phaseResults);
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

    public function toArray(): array
    {
        return [
            'current_phase' => $this->currentPhase,
            'phase_number' => $this->phaseNumber,
            'total_phases' => $this->totalPhases,
            'progress_percentage' => $this->progressPercentage,
            'completed_phases' => $this->getCompletedPhases(),
            'total_findings' => $this->getTotalFindings(),
            'elapsed_time' => $this->elapsedTime
        ];
    }
}