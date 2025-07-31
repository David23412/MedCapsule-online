<?php

namespace App\Services\Audit\State;

/**
 * Audit State Management
 * 
 * Tracks current audit execution state, progress, and phase information
 */
class AuditState
{
    private string $currentPhase = '';
    private int $phaseNumber = 0;
    private int $totalPhases = 23;
    private float $progressPercentage = 0.0;
    private array $completedPhases = [];
    private array $failedPhases = [];
    private string $startTime;
    private array $metadata = [];

    public function __construct()
    {
        $this->startTime = now()->toISOString();
    }

    public function setCurrentPhase(string $phase): void
    {
        $this->currentPhase = $phase;
    }

    public function getCurrentPhase(): string
    {
        return $this->currentPhase;
    }

    public function setPhaseNumber(int $number): void
    {
        $this->phaseNumber = $number;
    }

    public function getPhaseNumber(): int
    {
        return $this->phaseNumber;
    }

    public function updateProgress(int $completedPhases, int $totalPhases): void
    {
        $this->progressPercentage = round(($completedPhases / $totalPhases) * 100, 1);
    }

    public function getProgressPercentage(): float
    {
        return $this->progressPercentage;
    }

    public function markPhaseCompleted(string $phase): void
    {
        if (!in_array($phase, $this->completedPhases)) {
            $this->completedPhases[] = $phase;
        }
    }

    public function markPhaseFailed(string $phase): void
    {
        if (!in_array($phase, $this->failedPhases)) {
            $this->failedPhases[] = $phase;
        }
    }

    public function getCompletedPhases(): array
    {
        return $this->completedPhases;
    }

    public function getFailedPhases(): array
    {
        return $this->failedPhases;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function getMetadata(string $key = null)
    {
        if ($key === null) {
            return $this->metadata;
        }
        
        return $this->metadata[$key] ?? null;
    }

    public function toArray(): array
    {
        return [
            'current_phase' => $this->currentPhase,
            'phase_number' => $this->phaseNumber,
            'total_phases' => $this->totalPhases,
            'progress_percentage' => $this->progressPercentage,
            'completed_phases' => $this->completedPhases,
            'failed_phases' => $this->failedPhases,
            'start_time' => $this->startTime,
            'metadata' => $this->metadata
        ];
    }
}