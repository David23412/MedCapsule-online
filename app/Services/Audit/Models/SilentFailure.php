<?php

namespace App\Services\Audit\Models;

/**
 * Silent Failure Model
 * 
 * Represents silent system failures that don't trigger obvious errors
 */
class SilentFailure
{
    private string $type;
    private string $severity;
    private string $description;
    private string $impact;
    private string $timestamp;
    private array $metadata;

    public function __construct(
        string $type,
        string $severity,
        string $description,
        string $impact,
        string $timestamp,
        array $metadata = []
    ) {
        $this->type = $type;
        $this->severity = $severity;
        $this->description = $description;
        $this->impact = $impact;
        $this->timestamp = $timestamp;
        $this->metadata = $metadata;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImpact(): string
    {
        return $this->impact;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function requiresImmediateAction(): bool
    {
        return in_array($this->severity, ['critical', 'high']);
    }

    public function getRecommendedAction(): string
    {
        switch ($this->type) {
            case 'database_connection':
                return 'Check database server status and connection configuration';
            case 'cache_system':
                return 'Verify cache server status and configuration';
            case 'queue_system':
                return 'Check queue workers and clear backlog';
            case 'external_service':
                return 'Verify external service status and implement fallback';
            default:
                return 'Investigate system logs and monitor service health';
        }
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'description' => $this->description,
            'impact' => $this->impact,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata,
            'is_critical' => $this->isCritical(),
            'requires_immediate_action' => $this->requiresImmediateAction(),
            'recommended_action' => $this->getRecommendedAction()
        ];
    }
}