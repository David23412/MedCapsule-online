<?php

namespace App\Services\Audit\Models;

/**
 * Validation Result Model
 * 
 * Represents the result of evidence validation for a finding
 */
class ValidationResult
{
    private bool $isValid;
    private array $errors;
    private float $qualityScore;
    private array $warnings = [];

    public function __construct(bool $isValid, array $errors = [], float $qualityScore = 0.0)
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->qualityScore = $qualityScore;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getQualityScore(): float
    {
        return $this->qualityScore;
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'quality_score' => $this->qualityScore
        ];
    }
}