<?php

namespace App\Services\Audit\Reports;

/**
 * Reproducibility Report
 * 
 * Contains results of reproducibility testing across findings
 */
class ReproducibilityReport
{
    private float $overallReproducibilityScore;
    private array $stableFindings;
    private array $unstableFindings;
    private array $environmentVariations;
    private array $reproducibilityResults;

    public function __construct(
        float $overallReproducibilityScore,
        array $stableFindings,
        array $unstableFindings,
        array $environmentVariations,
        array $reproducibilityResults
    ) {
        $this->overallReproducibilityScore = $overallReproducibilityScore;
        $this->stableFindings = $stableFindings;
        $this->unstableFindings = $unstableFindings;
        $this->environmentVariations = $environmentVariations;
        $this->reproducibilityResults = $reproducibilityResults;
    }

    public function getOverallReproducibilityScore(): float
    {
        return $this->overallReproducibilityScore;
    }

    public function getStableFindings(): array
    {
        return $this->stableFindings;
    }

    public function getUnstableFindings(): array
    {
        return $this->unstableFindings;
    }

    public function getEnvironmentVariations(): array
    {
        return $this->environmentVariations;
    }

    public function getReproducibilityResults(): array
    {
        return $this->reproducibilityResults;
    }

    public function toArray(): array
    {
        return [
            'overall_reproducibility_score' => $this->overallReproducibilityScore,
            'stable_findings' => array_map(fn($f) => $f->toArray(), $this->stableFindings),
            'unstable_findings' => array_map(fn($f) => $f->toArray(), $this->unstableFindings),
            'environment_variations' => $this->environmentVariations,
            'reproducibility_results' => $this->reproducibilityResults
        ];
    }
}