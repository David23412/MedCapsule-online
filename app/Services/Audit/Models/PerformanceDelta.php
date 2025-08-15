<?php

namespace App\Services\Audit\Models;

/**
 * Performance Delta Model
 * 
 * Represents route performance changes with baseline comparison
 */
class PerformanceDelta
{
    private string $route;
    private array $baseline;
    private array $current;
    private array $delta;
    private float $confidence;
    private string $timestamp;

    public function __construct(
        string $route,
        array $baseline,
        array $current,
        array $delta,
        float $confidence,
        string $timestamp
    ) {
        $this->route = $route;
        $this->baseline = $baseline;
        $this->current = $current;
        $this->delta = $delta;
        $this->confidence = max(0.0, min(1.0, $confidence));
        $this->timestamp = $timestamp;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getBaseline(): array
    {
        return $this->baseline;
    }

    public function getCurrent(): array
    {
        return $this->current;
    }

    public function getDelta(): array
    {
        return $this->delta;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function isPerformanceDegradation(): bool
    {
        return $this->delta['percentage'] > 0;
    }

    public function isPerformanceImprovement(): bool
    {
        return $this->delta['percentage'] < 0;
    }

    public function isSignificantChange(): bool
    {
        return abs($this->delta['percentage']) > 25 && $this->confidence >= 0.6;
    }

    public function getSeverity(): string
    {
        $percentage = abs($this->delta['percentage']);
        
        if ($percentage > 100 && $this->confidence >= 0.8) {
            return 'critical';
        } elseif ($percentage > 50 && $this->confidence >= 0.7) {
            return 'high';
        } elseif ($percentage > 25 && $this->confidence >= 0.6) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    public function toArray(): array
    {
        return [
            'route' => $this->route,
            'baseline' => $this->baseline,
            'current' => $this->current,
            'delta' => $this->delta,
            'confidence' => $this->confidence,
            'timestamp' => $this->timestamp,
            'is_degradation' => $this->isPerformanceDegradation(),
            'is_improvement' => $this->isPerformanceImprovement(),
            'is_significant' => $this->isSignificantChange(),
            'severity' => $this->getSeverity()
        ];
    }
}