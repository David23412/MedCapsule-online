<?php

namespace App\Services\Audit\Core;

/**
 * Phase Result Container
 * 
 * Holds the results from executing a single audit phase
 */
class PhaseResult
{
    private string $phase;
    private array $findings;
    private array $metrics;
    private float $executionTime;
    private string $status;
    private array $errors;

    public function __construct(
        string $phase,
        array $findings = [],
        array $metrics = [],
        float $executionTime = 0.0,
        string $status = 'completed',
        array $errors = []
    ) {
        $this->phase = $phase;
        $this->findings = $findings;
        $this->metrics = $metrics;
        $this->executionTime = $executionTime;
        $this->status = $status;
        $this->errors = $errors;
    }

    public function getPhase(): string
    {
        return $this->phase;
    }

    public function getFindings(): array
    {
        return $this->findings;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getFindingsCount(): int
    {
        return count($this->findings);
    }

    public function getMetricsCount(): int
    {
        return count($this->metrics);
    }

    public function addFinding($finding): void
    {
        $this->findings[] = $finding;
    }

    public function addMetric(string $key, $value): void
    {
        $this->metrics[$key] = $value;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function toArray(): array
    {
        return [
            'phase' => $this->phase,
            'findings' => $this->findings,
            'metrics' => $this->metrics,
            'execution_time' => $this->executionTime,
            'status' => $this->status,
            'errors' => $this->errors,
            'findings_count' => $this->getFindingsCount(),
            'metrics_count' => $this->getMetricsCount(),
            'has_errors' => $this->hasErrors()
        ];
    }
}