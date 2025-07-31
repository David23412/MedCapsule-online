<?php

namespace App\Services\Audit\Models;

/**
 * Provenance Record Model
 * 
 * Represents audit provenance information for integrity verification
 */
class ProvenanceRecord
{
    private string $commitSHA;
    private string $auditHash;
    private string $timestamp;
    private array $auditMetadata;
    private string $integrityProof;

    public function __construct(
        string $commitSHA,
        string $auditHash,
        string $timestamp,
        array $auditMetadata = [],
        string $integrityProof = ''
    ) {
        $this->commitSHA = $commitSHA;
        $this->auditHash = $auditHash;
        $this->timestamp = $timestamp;
        $this->auditMetadata = $auditMetadata;
        $this->integrityProof = $integrityProof;
    }

    public function getCommitSHA(): string
    {
        return $this->commitSHA;
    }

    public function getAuditHash(): string
    {
        return $this->auditHash;
    }

    public function setAuditHash(string $hash): void
    {
        $this->auditHash = $hash;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getAuditMetadata(): array
    {
        return $this->auditMetadata;
    }

    public function getIntegrityProof(): string
    {
        return $this->integrityProof;
    }

    public function toArray(): array
    {
        return [
            'commit_sha' => $this->commitSHA,
            'audit_hash' => $this->auditHash,
            'timestamp' => $this->timestamp,
            'audit_metadata' => $this->auditMetadata,
            'integrity_proof' => $this->integrityProof
        ];
    }
}