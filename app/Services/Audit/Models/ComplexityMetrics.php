<?php

namespace App\Services\Audit\Models;

/**
 * Complexity Metrics Model
 * 
 * Represents code complexity measurements and maintainability metrics
 */
class ComplexityMetrics
{
    private int $cyclomaticComplexity;
    private float $maintainabilityIndex;
    private int $linesOfCode;
    private int $methodCount;
    private int $classCount;
    private int $functionCount;
    private array $evidenceTrace;

    public function __construct(
        int $cyclomaticComplexity,
        float $maintainabilityIndex,
        int $linesOfCode,
        int $methodCount = 0,
        int $classCount = 0,
        int $functionCount = 0,
        array $evidenceTrace = []
    ) {
        $this->cyclomaticComplexity = $cyclomaticComplexity;
        $this->maintainabilityIndex = $maintainabilityIndex;
        $this->linesOfCode = $linesOfCode;
        $this->methodCount = $methodCount;
        $this->classCount = $classCount;
        $this->functionCount = $functionCount;
        $this->evidenceTrace = $evidenceTrace;
    }

    public function getCyclomaticComplexity(): int
    {
        return $this->cyclomaticComplexity;
    }

    public function getMaintainabilityIndex(): float
    {
        return $this->maintainabilityIndex;
    }

    public function getLinesOfCode(): int
    {
        return $this->linesOfCode;
    }

    public function getMethodCount(): int
    {
        return $this->methodCount;
    }

    public function getClassCount(): int
    {
        return $this->classCount;
    }

    public function getFunctionCount(): int
    {
        return $this->functionCount;
    }

    public function getEvidenceTrace(): array
    {
        return $this->evidenceTrace;
    }

    /**
     * Get complexity rating based on cyclomatic complexity
     */
    public function getComplexityRating(): string
    {
        if ($this->cyclomaticComplexity <= 5) {
            return 'Low';
        } elseif ($this->cyclomaticComplexity <= 10) {
            return 'Moderate';
        } elseif ($this->cyclomaticComplexity <= 20) {
            return 'High';
        } else {
            return 'Very High';
        }
    }

    /**
     * Get maintainability rating
     */
    public function getMaintainabilityRating(): string
    {
        if ($this->maintainabilityIndex >= 85) {
            return 'Excellent';
        } elseif ($this->maintainabilityIndex >= 65) {
            return 'Good';
        } elseif ($this->maintainabilityIndex >= 20) {
            return 'Moderate';
        } else {
            return 'Poor';
        }
    }

    /**
     * Calculate technical debt estimate in hours
     */
    public function getTechnicalDebtHours(): int
    {
        $debtHours = 0;
        
        // Add hours based on complexity
        if ($this->cyclomaticComplexity > 20) {
            $debtHours += ($this->cyclomaticComplexity - 20) * 0.5;
        }
        
        // Add hours based on maintainability
        if ($this->maintainabilityIndex < 20) {
            $debtHours += (20 - $this->maintainabilityIndex) * 0.2;
        }
        
        // Add hours based on size
        if ($this->linesOfCode > 500) {
            $debtHours += ($this->linesOfCode - 500) * 0.01;
        }
        
        return (int) ceil($debtHours);
    }

    public function toArray(): array
    {
        return [
            'cyclomatic_complexity' => $this->cyclomaticComplexity,
            'maintainability_index' => $this->maintainabilityIndex,
            'lines_of_code' => $this->linesOfCode,
            'method_count' => $this->methodCount,
            'class_count' => $this->classCount,
            'function_count' => $this->functionCount,
            'complexity_rating' => $this->getComplexityRating(),
            'maintainability_rating' => $this->getMaintainabilityRating(),
            'technical_debt_hours' => $this->getTechnicalDebtHours(),
            'evidence_trace' => $this->evidenceTrace
        ];
    }
}