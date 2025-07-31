<?php

namespace App\Services\Audit\Models;

/**
 * Integrity Result Model
 * 
 * Represents the result of integrity validation
 */
class IntegrityResult
{
    private bool $isValid;
    private array $errors;
    private int $integrityScore;
    private array $warnings;

    public function __construct(
        bool $isValid,
        array $errors = [],
        int $integrityScore = 0,
        array $warnings = []
    ) {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->integrityScore = $integrityScore;
        $this->warnings = $warnings;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getIntegrityScore(): int
    {
        return $this->integrityScore;
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
            'integrity_score' => $this->integrityScore
        ];
    }
}