<?php

namespace App\Services\Audit\Models;

/**
 * Anomaly Detection Model
 * 
 * Represents detected anomalies with confidence scoring
 */
class AnomalyDetection
{
    private string $type;
    private string $severity;
    private string $description;
    private float $confidence;
    private string $timestamp;
    private array $metadata;

    public function __construct(
        string $type,
        string $severity,
        string $description,
        float $confidence,
        string $timestamp,
        array $metadata = []
    ) {
        $this->type = $type;
        $this->severity = $severity;
        $this->description = $description;
        $this->confidence = max(0.0, min(1.0, $confidence)); // Clamp between 0 and 1
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

    public function getConfidence(): float
    {
        return $this->confidence;
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
        return $this->severity === 'critical' && $this->confidence >= 0.8;
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.7;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'description' => $this->description,
            'confidence' => $this->confidence,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata,
            'is_critical' => $this->isCritical(),
            'is_high_confidence' => $this->isHighConfidence()
        ];
    }
}