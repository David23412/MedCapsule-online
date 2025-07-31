<?php

namespace App\Services\Audit\Models;

/**
 * Commit Diff Report Model
 * 
 * Represents the analysis results from comparing two Git commits
 */
class CommitDiffReport
{
    private array $modifiedFiles;
    private array $behavioralChanges;
    private array $configurationChanges;
    private float $riskScore;

    public function __construct(
        array $modifiedFiles,
        array $behavioralChanges,
        array $configurationChanges,
        float $riskScore
    ) {
        $this->modifiedFiles = $modifiedFiles;
        $this->behavioralChanges = $behavioralChanges;
        $this->configurationChanges = $configurationChanges;
        $this->riskScore = $riskScore;
    }

    public function getModifiedFiles(): array
    {
        return $this->modifiedFiles;
    }

    public function getBehavioralChanges(): array
    {
        return $this->behavioralChanges;
    }

    public function getConfigurationChanges(): array
    {
        return $this->configurationChanges;
    }

    public function getRiskScore(): float
    {
        return $this->riskScore;
    }

    public function toArray(): array
    {
        return [
            'modified_files' => $this->modifiedFiles,
            'behavioral_changes' => $this->behavioralChanges,
            'configuration_changes' => $this->configurationChanges,
            'risk_score' => $this->riskScore
        ];
    }
}