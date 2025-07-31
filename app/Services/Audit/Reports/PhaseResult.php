<?php

namespace App\Services\Audit\Reports;

use App\Services\Audit\Models\Finding;

/**
 * Phase Result Model
 * 
 * Represents the results from a single audit phase
 */
class PhaseResult
{
    private string $phase;
    private array $findings;
    private array $metrics;
    private float $executionTime;
    private bool $successful;
    private array $errors = [];

    public function __construct(
        string $phase,
        array $findings = [],
        array $metrics = [],
        float $executionTime = 0.0,
        bool $successful = true
    ) {
        $this->phase = $phase;
        $this->findings = $findings;
        $this->metrics = $metrics;
        $this->executionTime = $executionTime;
        $this->successful = $successful;
    }

    public function getPhase(): string
    {
        return $this->phase;
    }

    public function getFindings(): array
    {
        return $this->findings;
    }

    public function addFinding(Finding $finding): void
    {
        $this->findings[] = $finding;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function addMetric(string $name, $value, string $unit = ''): void
    {
        $this->metrics[] = [
            'name' => $name,
            'value' => $value,
            'unit' => $unit
        ];
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function setExecutionTime(float $time): void
    {
        $this->executionTime = $time;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function setSuccessful(bool $successful): void
    {
        $this->successful = $successful;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
        $this->successful = false;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFindingsBySeverity(): array
    {
        $bySeverity = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => []
        ];

        foreach ($this->findings as $finding) {
            $severity = $finding->getSeverity();
            if (isset($bySeverity[$severity])) {
                $bySeverity[$severity][] = $finding;
            }
        }

        return $bySeverity;
    }

    public function toArray(): array
    {
        return [
            'phase' => $this->phase,
            'findings' => array_map(fn($finding) => $finding->toArray(), $this->findings),
            'metrics' => $this->metrics,
            'execution_time' => $this->executionTime,
            'successful' => $this->successful,
            'errors' => $this->errors,
            'findings_by_severity' => $this->getFindingsBySeverity()
        ];
    }
}