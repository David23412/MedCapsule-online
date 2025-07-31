<?php

namespace App\Services\Audit\Models;

/**
 * Finding Model
 * 
 * Represents a single audit finding with evidence, metrics, and verification status
 */
class Finding
{
    private string $id;
    private string $severity;
    private string $description;
    private array $evidence = [];
    private array $fileReferences = [];
    private array $metrics = [];
    private bool $isVerified = false;
    private bool $isReproducible = false;
    private string $category;
    private string $component;
    private array $chainTrace = [];
    private string $recommendation = '';
    private int $estimatedHours = 0;

    public function __construct(
        string $id,
        string $severity,
        string $description,
        string $category = '',
        string $component = ''
    ) {
        $this->id = $id;
        $this->severity = $severity;
        $this->description = $description;
        $this->category = $category;
        $this->component = $component;
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getDescription(): string
    {
        return $this->description;
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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function isReproducible(): bool
    {
        return $this->isReproducible;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getChainTrace(): array
    {
        return $this->chainTrace;
    }

    public function getRecommendation(): string
    {
        return $this->recommendation;
    }

    public function getEstimatedHours(): int
    {
        return $this->estimatedHours;
    }

    // Setters
    public function setVerified(bool $verified): void
    {
        $this->isVerified = $verified;
    }

    public function setReproducible(bool $reproducible): void
    {
        $this->isReproducible = $reproducible;
    }

    public function addEvidence(string $type, $data, string $description = ''): void
    {
        $this->evidence[] = [
            'type' => $type,
            'data' => $data,
            'description' => $description,
            'timestamp' => now()->toISOString()
        ];
    }

    public function addFileReference(string $file, int $line, string $context = ''): void
    {
        $this->fileReferences[] = [
            'file' => $file,
            'line' => $line,
            'context' => $context
        ];
    }

    public function addMetric(string $name, $value, string $unit = '', string $description = ''): void
    {
        $this->metrics[] = [
            'name' => $name,
            'value' => $value,
            'unit' => $unit,
            'description' => $description
        ];
    }

    public function setChainTrace(array $trace): void
    {
        $this->chainTrace = $trace;
    }

    public function setRecommendation(string $recommendation): void
    {
        $this->recommendation = $recommendation;
    }

    public function setEstimatedHours(int $hours): void
    {
        $this->estimatedHours = $hours;
    }

    /**
     * Get severity color code for reporting
     */
    public function getSeverityIcon(): string
    {
        return match($this->severity) {
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪'
        };
    }

    /**
     * Get reproducibility icon for reporting
     */
    public function getReproducibilityIcon(): string
    {
        return $this->isReproducible ? '✅' : '❌';
    }

    /**
     * Convert finding to array for reporting
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'severity' => $this->severity,
            'severity_icon' => $this->getSeverityIcon(),
            'description' => $this->description,
            'category' => $this->category,
            'component' => $this->component,
            'evidence' => $this->evidence,
            'file_references' => $this->fileReferences,
            'metrics' => $this->metrics,
            'is_verified' => $this->isVerified,
            'is_reproducible' => $this->isReproducible,
            'reproducibility_icon' => $this->getReproducibilityIcon(),
            'chain_trace' => $this->chainTrace,
            'recommendation' => $this->recommendation,
            'estimated_hours' => $this->estimatedHours
        ];
    }

    /**
     * Create finding from array data
     */
    public static function fromArray(array $data): self
    {
        $finding = new self(
            $data['id'],
            $data['severity'],
            $data['description'],
            $data['category'] ?? '',
            $data['component'] ?? ''
        );

        if (isset($data['evidence'])) {
            foreach ($data['evidence'] as $evidence) {
                $finding->addEvidence(
                    $evidence['type'],
                    $evidence['data'],
                    $evidence['description'] ?? ''
                );
            }
        }

        if (isset($data['file_references'])) {
            foreach ($data['file_references'] as $ref) {
                $finding->addFileReference(
                    $ref['file'],
                    $ref['line'],
                    $ref['context'] ?? ''
                );
            }
        }

        if (isset($data['metrics'])) {
            foreach ($data['metrics'] as $metric) {
                $finding->addMetric(
                    $metric['name'],
                    $metric['value'],
                    $metric['unit'] ?? '',
                    $metric['description'] ?? ''
                );
            }
        }

        if (isset($data['chain_trace'])) {
            $finding->setChainTrace($data['chain_trace']);
        }

        if (isset($data['recommendation'])) {
            $finding->setRecommendation($data['recommendation']);
        }

        if (isset($data['estimated_hours'])) {
            $finding->setEstimatedHours($data['estimated_hours']);
        }

        $finding->setVerified($data['is_verified'] ?? false);
        $finding->setReproducible($data['is_reproducible'] ?? false);

        return $finding;
    }
}