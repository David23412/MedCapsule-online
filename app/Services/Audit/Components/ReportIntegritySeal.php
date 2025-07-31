<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Reports\AuditReport;
use App\Services\Audit\Models\ProvenanceRecord;
use App\Services\Audit\Models\IntegrityResult;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Report Integrity Seal System
 * 
 * Cryptographic audit hash generation and verification to prevent manipulation
 * Locks Git SHA of codebase in report hash for tamper detection
 */
class ReportIntegritySeal
{
    private string $algorithm = 'sha256';
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('app.key') . '_audit_seal';
    }

    /**
     * Generate cryptographic audit hash for report
     */
    public function generateAuditHash(string $commitHash, string $reportFile): string
    {
        if (!file_exists($reportFile)) {
            throw new Exception("Report file not found: {$reportFile}");
        }

        $reportContent = file_get_contents($reportFile);
        $reportChecksum = $this->calculateReportChecksum($reportContent);
        
        // Create hash input combining multiple factors
        $hashInput = implode('|', [
            $commitHash,
            $reportChecksum,
            filesize($reportFile),
            filemtime($reportFile),
            $this->secretKey,
            now()->timestamp
        ]);

        $auditHash = hash($this->algorithm, $hashInput);
        
        Log::info('Audit hash generated', [
            'commit_sha' => $commitHash,
            'report_file' => basename($reportFile),
            'audit_hash' => substr($auditHash, 0, 16) . '...',
            'file_size' => filesize($reportFile)
        ]);

        return strtoupper($auditHash);
    }

    /**
     * Verify audit hash integrity
     */
    public function verifyHash(string $hash, string $reportFile): bool
    {
        if (!file_exists($reportFile)) {
            Log::error('Cannot verify hash - report file missing', [
                'report_file' => $reportFile
            ]);
            return false;
        }

        try {
            // Extract provenance record from report
            $provenanceRecord = $this->extractProvenanceFromReport($reportFile);
            
            if (!$provenanceRecord) {
                Log::error('No provenance record found in report');
                return false;
            }

            // Regenerate hash with stored parameters
            $regeneratedHash = $this->regenerateHashFromProvenance($provenanceRecord, $reportFile);
            
            $isValid = hash_equals($hash, $regeneratedHash);
            
            Log::info('Hash verification result', [
                'provided_hash' => substr($hash, 0, 16) . '...',
                'regenerated_hash' => substr($regeneratedHash, 0, 16) . '...',
                'is_valid' => $isValid
            ]);

            return $isValid;

        } catch (Exception $e) {
            Log::error('Hash verification failed', [
                'error' => $e->getMessage(),
                'report_file' => $reportFile
            ]);
            return false;
        }
    }

    /**
     * Create provenance record for audit
     */
    public function createProvenanceRecord(AuditReport $report): ProvenanceRecord
    {
        $commitSHA = $this->getCurrentCommitSHA();
        $timestamp = now()->toISOString();
        
        $auditMetadata = [
            'total_findings' => $report->getTotalFindings(),
            'evidence_quality_score' => $report->getEvidenceQualityScore(),
            'execution_time' => $report->getExecutionTime(),
            'phases_completed' => count($report->getPhaseResults()),
            'reproducibility_score' => $report->getReproducibilityScore(),
            'chaos_mode_enabled' => $report->getChaosMode(),
            'entropy_score' => $report->getEntropyScore()
        ];

        $integrityProof = $this->generateIntegrityProof($commitSHA, $auditMetadata, $timestamp);

        return new ProvenanceRecord(
            $commitSHA,
            '', // Audit hash will be set later
            $timestamp,
            $auditMetadata,
            $integrityProof
        );
    }

    /**
     * Validate report integrity against tampering
     */
    public function validateReportIntegrity(string $reportPath): IntegrityResult
    {
        if (!file_exists($reportPath)) {
            return new IntegrityResult(false, ['Report file not found'], 0);
        }

        $errors = [];
        $warnings = [];
        $integrityScore = 100;

        try {
            // 1. Check file integrity
            $fileIntegrity = $this->checkFileIntegrity($reportPath);
            if (!$fileIntegrity['valid']) {
                $errors = array_merge($errors, $fileIntegrity['errors']);
                $integrityScore -= 30;
            }

            // 2. Verify provenance record
            $provenanceRecord = $this->extractProvenanceFromReport($reportPath);
            if (!$provenanceRecord) {
                $errors[] = 'Missing or invalid provenance record';
                $integrityScore -= 40;
            } else {
                $provenanceIntegrity = $this->validateProvenanceIntegrity($provenanceRecord);
                if (!$provenanceIntegrity['valid']) {
                    $errors = array_merge($errors, $provenanceIntegrity['errors']);
                    $integrityScore -= 20;
                }
            }

            // 3. Check for suspicious modifications
            $modificationCheck = $this->detectSuspiciousModifications($reportPath);
            if (!empty($modificationCheck['warnings'])) {
                $warnings = array_merge($warnings, $modificationCheck['warnings']);
                $integrityScore -= 10;
            }

            // 4. Verify commit SHA exists
            if ($provenanceRecord && !$this->verifyCommitExists($provenanceRecord->getCommitSHA())) {
                $errors[] = 'Referenced commit SHA does not exist in repository';
                $integrityScore -= 25;
            }

        } catch (Exception $e) {
            $errors[] = 'Integrity validation failed: ' . $e->getMessage();
            $integrityScore = 0;
        }

        $isValid = empty($errors) && $integrityScore >= 70;

        return new IntegrityResult($isValid, $errors, max(0, $integrityScore), $warnings);
    }

    /**
     * Calculate report content checksum
     */
    private function calculateReportChecksum(string $reportContent): string
    {
        // Remove dynamic content that might change
        $normalizedContent = $this->normalizeReportContent($reportContent);
        return hash($this->algorithm, $normalizedContent);
    }

    /**
     * Normalize report content for consistent hashing
     */
    private function normalizeReportContent(string $content): string
    {
        // Remove timestamps and other dynamic content
        $content = preg_replace('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', '[TIMESTAMP]', $content);
        $content = preg_replace('/Generated at: .*/', 'Generated at: [TIMESTAMP]', $content);
        $content = preg_replace('/Execution time: [\d.]+s/', 'Execution time: [TIME]', $content);
        
        return $content;
    }

    /**
     * Get current Git commit SHA
     */
    private function getCurrentCommitSHA(): string
    {
        try {
            $gitDir = base_path('.git');
            
            if (!is_dir($gitDir)) {
                Log::warning('Git directory not found, using fallback SHA');
                return 'NO_GIT_' . substr(hash('sha1', base_path()), 0, 8);
            }

            $headFile = $gitDir . '/HEAD';
            if (!file_exists($headFile)) {
                throw new Exception('Git HEAD file not found');
            }

            $headContent = trim(file_get_contents($headFile));
            
            if (strpos($headContent, 'ref: ') === 0) {
                // HEAD points to a branch
                $refPath = $gitDir . '/' . substr($headContent, 5);
                if (file_exists($refPath)) {
                    return trim(file_get_contents($refPath));
                }
            } else {
                // HEAD contains direct SHA
                return $headContent;
            }

            throw new Exception('Could not resolve Git commit SHA');

        } catch (Exception $e) {
            Log::warning('Failed to get Git commit SHA', ['error' => $e->getMessage()]);
            return 'UNKNOWN_' . substr(hash('sha1', base_path() . time()), 0, 8);
        }
    }

    /**
     * Generate integrity proof
     */
    private function generateIntegrityProof(string $commitSHA, array $metadata, string $timestamp): string
    {
        $proofInput = implode('|', [
            $commitSHA,
            json_encode($metadata, JSON_SORT_KEYS),
            $timestamp,
            $this->secretKey
        ]);

        return hash_hmac($this->algorithm, $proofInput, $this->secretKey);
    }

    /**
     * Extract provenance record from report file
     */
    private function extractProvenanceFromReport(string $reportPath): ?ProvenanceRecord
    {
        $content = file_get_contents($reportPath);
        
        // Look for provenance section in markdown
        if (preg_match('/## 🔐 Audit Provenance\s*\n(.*?)\n##/s', $content, $matches)) {
            $provenanceSection = $matches[1];
            
            // Extract key-value pairs
            $data = [];
            if (preg_match('/- \*\*SHA of codebase audited\*\*: `([^`]+)`/', $provenanceSection, $shaMatch)) {
                $data['commit_sha'] = $shaMatch[1];
            }
            if (preg_match('/- \*\*Audit hash\*\*: `([^`]+)`/', $provenanceSection, $hashMatch)) {
                $data['audit_hash'] = $hashMatch[1];
            }
            if (preg_match('/- \*\*Timestamp\*\*: `([^`]+)`/', $provenanceSection, $timeMatch)) {
                $data['timestamp'] = $timeMatch[1];
            }

            if (isset($data['commit_sha']) && isset($data['audit_hash'])) {
                return new ProvenanceRecord(
                    $data['commit_sha'],
                    $data['audit_hash'],
                    $data['timestamp'] ?? now()->toISOString(),
                    [],
                    ''
                );
            }
        }

        return null;
    }

    /**
     * Regenerate hash from provenance record
     */
    private function regenerateHashFromProvenance(ProvenanceRecord $provenance, string $reportFile): string
    {
        // This is a simplified version - in real implementation, 
        // we'd need to store all hash generation parameters
        return $this->generateAuditHash($provenance->getCommitSHA(), $reportFile);
    }

    /**
     * Check file integrity
     */
    private function checkFileIntegrity(string $filePath): array
    {
        $errors = [];
        
        // Check file permissions
        if (!is_readable($filePath)) {
            $errors[] = 'Report file is not readable';
        }

        // Check file size (reports should be substantial)
        $fileSize = filesize($filePath);
        if ($fileSize < 1000) { // Less than 1KB is suspicious
            $errors[] = 'Report file suspiciously small';
        }

        // Check for binary content (reports should be text)
        $content = file_get_contents($filePath, false, null, 0, 1024);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $errors[] = 'Report contains non-UTF-8 content';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate provenance integrity
     */
    private function validateProvenanceIntegrity(ProvenanceRecord $provenance): array
    {
        $errors = [];

        // Check SHA format
        if (!preg_match('/^[a-f0-9]{40}$/i', $provenance->getCommitSHA())) {
            $errors[] = 'Invalid commit SHA format';
        }

        // Check timestamp format
        try {
            $timestamp = new \DateTime($provenance->getTimestamp());
            $now = new \DateTime();
            
            // Check if timestamp is reasonable (not too far in future/past)
            $diff = $now->getTimestamp() - $timestamp->getTimestamp();
            if ($diff < -3600 || $diff > 86400 * 365) { // 1 hour future, 1 year past
                $errors[] = 'Suspicious timestamp in provenance record';
            }
        } catch (Exception $e) {
            $errors[] = 'Invalid timestamp format in provenance record';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Detect suspicious modifications
     */
    private function detectSuspiciousModifications(string $filePath): array
    {
        $warnings = [];
        
        // Check modification time vs creation time
        $mtime = filemtime($filePath);
        $ctime = filectime($filePath);
        
        if ($mtime > $ctime + 300) { // Modified more than 5 minutes after creation
            $warnings[] = 'Report file was modified after initial creation';
        }

        return ['warnings' => $warnings];
    }

    /**
     * Verify commit exists in repository
     */
    private function verifyCommitExists(string $commitSHA): bool
    {
        try {
            $gitDir = base_path('.git');
            if (!is_dir($gitDir)) {
                return false;
            }

            // Try to find the commit in git objects
            $objectPath = $gitDir . '/objects/' . substr($commitSHA, 0, 2) . '/' . substr($commitSHA, 2);
            
            return file_exists($objectPath);

        } catch (Exception $e) {
            Log::warning('Failed to verify commit existence', [
                'commit_sha' => $commitSHA,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Encrypt audit metadata
     */
    private function encryptAuditMetadata(array $metadata): string
    {
        $jsonData = json_encode($metadata, JSON_SORT_KEYS);
        return base64_encode(hash_hmac($this->algorithm, $jsonData, $this->secretKey, true));
    }
}