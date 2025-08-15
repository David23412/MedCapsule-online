<?php

namespace App\Services\Audit\Models;

/**
 * Finding Interface
 * 
 * Represents an audit finding with evidence, metrics, and metadata
 */
interface Finding
{
    /**
     * Get unique finding identifier
     */
    public function getId(): string;

    /**
     * Get finding severity level
     */
    public function getSeverity(): string;

    /**
     * Set finding severity level
     */
    public function setSeverity(string $severity): self;

    /**
     * Get finding description
     */
    public function getDescription(): string;

    /**
     * Set finding description
     */
    public function setDescription(string $description): self;

    /**
     * Get finding recommendation
     */
    public function getRecommendation(): string;

    /**
     * Set finding recommendation
     */
    public function setRecommendation(string $recommendation): self;

    /**
     * Get finding evidence
     */
    public function getEvidence(): array;

    /**
     * Get file references
     */
    public function getFileReferences(): array;

    /**
     * Get quantifiable metrics
     */
    public function getMetrics(): array;

    /**
     * Get finding category
     */
    public function getCategory(): string;

    /**
     * Get affected component
     */
    public function getComponent(): string;
}