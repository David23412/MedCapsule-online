<?php

namespace App\Services\Audit\Models;

/**
 * Concrete Finding Implementation
 * 
 * Concrete implementation of the Finding interface for audit findings
 */
class ConcreteFinding implements Finding
{
    private string $id;
    private string $severity;
    private string $description;
    private string $recommendation;
    private array $evidence;
    private array $fileReferences;
    private array $metrics;
    private string $category;
    private string $component;

    public function __construct(
        string $id,
        string $severity = 'medium',
        string $description = '',
        string $category = 'general',
        string $component = 'unknown',
        string $recommendation = '',
        array $evidence = [],
        array $fileReferences = [],
        array $metrics = []
    ) {
        $this->id = $id;
        $this->severity = $severity;
        $this->description = $description;
        $this->category = $category;
        $this->component = $component;
        $this->recommendation = $recommendation;
        $this->evidence = $evidence;
        $this->fileReferences = $fileReferences;
        $this->metrics = $metrics;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getRecommendation(): string
    {
        return $this->recommendation;
    }

    public function setRecommendation(string $recommendation): self
    {
        $this->recommendation = $recommendation;
        return $this;
    }

    public function getEvidence(): array
    {
        return $this->evidence;
    }

    public function getFileReferences(): array
    {
        return $this->fileReferences;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function addEvidence(string $type, array $evidenceData, string $description = ''): self
    {
        // 🚀 PERFORMANCE BEAST: Store evidence in format expected by validator
        $this->evidence[] = [
            'type' => $type,
            'data' => $evidenceData,
            'description' => $description ?: "Evidence of type: {$type}"
        ];
        return $this;
    }

    public function addFileReference(string $file, int $line, string $context): self
    {
        $this->fileReferences[] = [
            'file' => $file,
            'line' => $line,
            'context' => $context
        ];
        return $this;
    }

    public function addMetric(string $name, $value, string $unit): self
    {
        $this->metrics[] = [
            'name' => $name,
            'value' => $value,
            'unit' => $unit
        ];
        return $this;
    }

    public function setEstimatedHours(float $hours): self
    {
        $this->addMetric('estimated_hours', $hours, 'hours');
        return $this;
    }

    public function setVerified(bool $verified): self
    {
        $this->addEvidence('verification', ['verified' => $verified]);
        return $this;
    }

    public function setReproducible(bool $reproducible): self
    {
        $this->addEvidence('reproducibility', ['reproducible' => $reproducible]);
        return $this;
    }

    public function getReproducibilityIcon(): string
    {
        // Check if reproducibility evidence exists
        foreach ($this->evidence as $evidence) {
            if ($evidence['type'] === 'reproducibility' && isset($evidence['data']['reproducible'])) {
                return $evidence['data']['reproducible'] ? '✅' : '❌';
            }
        }
        return '❓'; // Unknown reproducibility
    }

    public function getEstimatedHours(): float
    {
        // Look for estimated_hours metric
        foreach ($this->metrics as $metric) {
            if ($metric['name'] === 'estimated_hours') {
                return (float) $metric['value'];
            }
        }
        return 0.0; // Default if no estimate
    }
}