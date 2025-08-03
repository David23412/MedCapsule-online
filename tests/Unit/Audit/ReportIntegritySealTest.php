<?php

namespace Tests\Unit\Audit;

use Tests\TestCase;
use App\Services\Audit\Components\ReportIntegritySeal;
use App\Services\Audit\Reports\AuditReport;
use App\Services\Audit\Models\ProvenanceRecord;
use App\Services\Audit\Models\IntegrityResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportIntegritySealTest extends TestCase
{
    use RefreshDatabase;

    private ReportIntegritySeal $seal;
    private string $testReportPath;
    private string $testCommitSHA;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seal = new ReportIntegritySeal();
        $this->testCommitSHA = 'a1b2c3d4e5f6789012345678901234567890abcd';
        
        // Create a test report file
        $this->testReportPath = storage_path('app/test_audit_report.md');
        $this->createTestReportFile();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testReportPath)) {
            unlink($this->testReportPath);
        }
        parent::tearDown();
    }

    /** @test */
    public function it_generates_sha256_audit_hash_successfully()
    {
        // Act
        $auditHash = $this->seal->generateAuditHash($this->testCommitSHA, $this->testReportPath);

        // Assert
        $this->assertIsString($auditHash);
        $this->assertEquals(64, strlen($auditHash)); // SHA-256 produces 64 character hex string
        $this->assertMatchesRegularExpression('/^[A-F0-9]{64}$/', $auditHash);
        
        // Verify hash is deterministic for same inputs
        $secondHash = $this->seal->generateAuditHash($this->testCommitSHA, $this->testReportPath);
        $this->assertEquals($auditHash, $secondHash);
    }

    /** @test */
    public function it_generates_different_hashes_for_different_commits()
    {
        // Arrange
        $commitSHA1 = 'a1b2c3d4e5f6789012345678901234567890abcd';
        $commitSHA2 = 'b2c3d4e5f6789012345678901234567890abcdef';

        // Act
        $hash1 = $this->seal->generateAuditHash($commitSHA1, $this->testReportPath);
        $hash2 = $this->seal->generateAuditHash($commitSHA2, $this->testReportPath);

        // Assert
        $this->assertNotEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1));
        $this->assertEquals(64, strlen($hash2));
    }

    /** @test */
    public function it_generates_different_hashes_for_different_report_content()
    {
        // Arrange
        $originalHash = $this->seal->generateAuditHash($this->testCommitSHA, $this->testReportPath);
        
        // Modify report content
        file_put_contents($this->testReportPath, "# Modified Audit Report\n\nThis is different content.");
        
        // Act
        $modifiedHash = $this->seal->generateAuditHash($this->testCommitSHA, $this->testReportPath);

        // Assert
        $this->assertNotEquals($originalHash, $modifiedHash);
        $this->assertEquals(64, strlen($modifiedHash));
    }

    /** @test */
    public function it_throws_exception_for_missing_report_file()
    {
        // Arrange
        $nonExistentPath = '/path/to/nonexistent/report.md';

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Report file not found: {$nonExistentPath}");
        
        $this->seal->generateAuditHash($this->testCommitSHA, $nonExistentPath);
    }

    /** @test */
    public function it_verifies_valid_audit_hash()
    {
        // Arrange
        $this->createReportWithProvenance();
        $validHash = $this->seal->generateAuditHash($this->testCommitSHA, $this->testReportPath);

        // Act
        $isValid = $this->seal->verifyHash($validHash, $this->testReportPath);

        // Assert
        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_rejects_invalid_audit_hash()
    {
        // Arrange
        $this->createReportWithProvenance();
        $invalidHash = 'INVALID_HASH_1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF12';

        // Act
        $isValid = $this->seal->verifyHash($invalidHash, $this->testReportPath);

        // Assert
        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_creates_provenance_record_with_audit_metadata()
    {
        // Arrange
        $mockReport = $this->createMockAuditReport();

        // Act
        $provenanceRecord = $this->seal->createProvenanceRecord($mockReport);

        // Assert
        $this->assertInstanceOf(ProvenanceRecord::class, $provenanceRecord);
        $this->assertNotEmpty($provenanceRecord->getCommitSHA());
        $this->assertNotEmpty($provenanceRecord->getTimestamp());
        $this->assertNotEmpty($provenanceRecord->getIntegrityProof());
        
        $metadata = $provenanceRecord->getAuditMetadata();
        $this->assertArrayHasKey('total_findings', $metadata);
        $this->assertArrayHasKey('evidence_quality_score', $metadata);
        $this->assertArrayHasKey('execution_time', $metadata);
        $this->assertArrayHasKey('phases_completed', $metadata);
        $this->assertArrayHasKey('reproducibility_score', $metadata);
        $this->assertArrayHasKey('chaos_mode_enabled', $metadata);
        $this->assertArrayHasKey('entropy_score', $metadata);
    }

    /** @test */
    public function it_validates_report_integrity_successfully()
    {
        // Arrange
        $this->createReportWithProvenance();

        // Act
        $result = $this->seal->validateReportIntegrity($this->testReportPath);

        // Assert
        $this->assertInstanceOf(IntegrityResult::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertGreaterThanOrEqual(70, $result->getIntegrityScore());
        $this->assertEmpty($result->getErrors());
    }

    /** @test */
    public function it_detects_missing_report_file()
    {
        // Arrange
        $missingPath = '/path/to/missing/report.md';

        // Act
        $result = $this->seal->validateReportIntegrity($missingPath);

        // Assert
        $this->assertFalse($result->isValid());
        $this->assertContains('Report file not found', $result->getErrors());
        $this->assertEquals(0, $result->getIntegrityScore());
    }

    /** @test */
    public function it_handles_corrupted_report_gracefully()
    {
        // Arrange
        file_put_contents($this->testReportPath, "\x00\x01\x02\x03"); // Binary content

        // Act
        $result = $this->seal->validateReportIntegrity($this->testReportPath);

        // Assert
        $this->assertFalse($result->isValid());
        $this->assertLessThan(70, $result->getIntegrityScore());
    }

    /** @test */
    public function it_logs_audit_hash_generation()
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('Audit hash generated', \Mockery::type('array'));

        // Act
        $this->seal->generateAuditHash($this->testCommitSHA, $this->testReportPath);

        // Assert - Mockery will verify the log call
    }

    /** @test */
    public function it_uses_uppercase_hash_format()
    {
        // Act
        $auditHash = $this->seal->generateAuditHash($this->testCommitSHA, $this->testReportPath);

        // Assert
        $this->assertEquals(strtoupper($auditHash), $auditHash);
        $this->assertMatchesRegularExpression('/^[A-F0-9]{64}$/', $auditHash);
    }

    /** @test */
    public function it_includes_file_metadata_in_hash_calculation()
    {
        // Arrange
        $originalHash = $this->seal->generateAuditHash($this->testCommitSHA, $this->testReportPath);
        
        // Wait a moment and touch the file to change modification time
        sleep(1);
        touch($this->testReportPath);
        
        // Act
        $newHash = $this->seal->generateAuditHash($this->testCommitSHA, $this->testReportPath);

        // Assert
        $this->assertNotEquals($originalHash, $newHash);
    }

    private function createTestReportFile(): void
    {
        $reportContent = <<<MD
# Comprehensive Project Audit Report

## Executive Summary
- Overall Readiness Score: 85/100
- Critical Issues: 2
- High Priority Issues: 5

## Detailed Analysis
This is a test audit report with comprehensive findings.

### Security Analysis
- Authentication system reviewed
- Input validation tested
- No critical vulnerabilities found

### Performance Analysis
- Average response time: 120ms
- Memory usage: 45MB
- Database queries optimized

## Recommendations
1. Fix critical authentication issue
2. Optimize database queries
3. Implement caching strategy
MD;

        file_put_contents($this->testReportPath, $reportContent);
    }

    private function createReportWithProvenance(): void
    {
        $reportContent = <<<MD
# Comprehensive Project Audit Report

## Executive Summary
- Overall Readiness Score: 85/100

## 🔐 Audit Provenance
- **SHA of codebase audited**: `{$this->testCommitSHA}`
- **Audit hash**: `ABCD1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890AB`
- **Timestamp**: `2025-01-01T12:00:00Z`

## Detailed Analysis
This is a test report with provenance information.
MD;

        file_put_contents($this->testReportPath, $reportContent);
    }

    /** @test */
    public function it_embeds_git_commit_sha_in_report()
    {
        // Arrange
        $provenanceRecord = new ProvenanceRecord(
            $this->testCommitSHA,
            'ABCD1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890AB',
            '2025-01-01T12:00:00Z',
            ['total_findings' => 15, 'evidence_quality_score' => 92.5],
            'integrity_proof_hash'
        );

        // Act
        $result = $this->seal->embedProvenanceInReport($this->testReportPath, $provenanceRecord);

        // Assert
        $this->assertTrue($result);
        
        $reportContent = file_get_contents($this->testReportPath);
        $this->assertStringContainsString('## 🔐 Audit Provenance', $reportContent);
        $this->assertStringContainsString("**SHA of codebase audited**: `{$this->testCommitSHA}`", $reportContent);
        $this->assertStringContainsString('**Audit hash**: `ABCD1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890AB`', $reportContent);
        $this->assertStringContainsString('**Timestamp**: `2025-01-01T12:00:00Z`', $reportContent);
    }

    /** @test */
    public function it_updates_existing_provenance_section()
    {
        // Arrange
        $this->createReportWithProvenance();
        $newProvenanceRecord = new ProvenanceRecord(
            'b2c3d4e5f6789012345678901234567890abcdef',
            'NEW_HASH_1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890',
            '2025-01-02T12:00:00Z',
            ['total_findings' => 20],
            'new_integrity_proof'
        );

        // Act
        $result = $this->seal->embedProvenanceInReport($this->testReportPath, $newProvenanceRecord);

        // Assert
        $this->assertTrue($result);
        
        $reportContent = file_get_contents($this->testReportPath);
        $this->assertStringContainsString('b2c3d4e5f6789012345678901234567890abcdef', $reportContent);
        $this->assertStringContainsString('NEW_HASH_1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890', $reportContent);
        $this->assertStringContainsString('2025-01-02T12:00:00Z', $reportContent);
        
        // Should not contain old values
        $this->assertStringNotContainsString($this->testCommitSHA, $reportContent);
    }

    /** @test */
    public function it_seals_report_with_complete_provenance()
    {
        // Arrange
        $mockReport = $this->createMockAuditReport();

        // Act
        $result = $this->seal->sealReport($this->testReportPath, $mockReport);

        // Assert
        $this->assertTrue($result);
        
        $reportContent = file_get_contents($this->testReportPath);
        $this->assertStringContainsString('## 🔐 Audit Provenance', $reportContent);
        $this->assertStringContainsString('**SHA of codebase audited**:', $reportContent);
        $this->assertStringContainsString('**Audit hash**:', $reportContent);
        $this->assertStringContainsString('**Total Findings**: 15', $reportContent);
        $this->assertStringContainsString('**Evidence Quality Score**: 92.50', $reportContent);
        $this->assertStringContainsString('Verification Instructions', $reportContent);
    }

    /** @test */
    public function it_handles_missing_report_file_gracefully()
    {
        // Arrange
        $missingPath = '/path/to/missing/report.md';
        $provenanceRecord = new ProvenanceRecord(
            $this->testCommitSHA,
            'test_hash',
            '2025-01-01T12:00:00Z'
        );

        // Act
        $result = $this->seal->embedProvenanceInReport($missingPath, $provenanceRecord);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_includes_verification_instructions_in_provenance()
    {
        // Arrange
        $provenanceRecord = new ProvenanceRecord(
            $this->testCommitSHA,
            'test_hash',
            '2025-01-01T12:00:00Z'
        );

        // Act
        $this->seal->embedProvenanceInReport($this->testReportPath, $provenanceRecord);

        // Assert
        $reportContent = file_get_contents($this->testReportPath);
        $this->assertStringContainsString('### Verification Instructions', $reportContent);
        $this->assertStringContainsString('Check that the commit SHA exists', $reportContent);
        $this->assertStringContainsString('Regenerate the audit hash', $reportContent);
        $this->assertStringContainsString('Compare the regenerated hash', $reportContent);
        $this->assertStringContainsString('Tamper detection:', $reportContent);
    }

    /** @test */
    public function it_verifies_report_integrity_on_load()
    {
        // Arrange
        $this->createReportWithProvenance();

        // Act
        $verificationResult = $this->seal->verifyReportOnLoad($this->testReportPath);

        // Assert
        $this->assertIsArray($verificationResult);
        $this->assertArrayHasKey('is_valid', $verificationResult);
        $this->assertArrayHasKey('integrity_score', $verificationResult);
        $this->assertArrayHasKey('errors', $verificationResult);
        $this->assertArrayHasKey('warnings', $verificationResult);
        $this->assertArrayHasKey('provenance', $verificationResult);
        $this->assertArrayHasKey('verification_details', $verificationResult);
        
        // Should have provenance data
        $this->assertNotNull($verificationResult['provenance']);
        $this->assertEquals($this->testCommitSHA, $verificationResult['provenance']['commit_sha']);
    }

    /** @test */
    public function it_detects_missing_report_file_on_load()
    {
        // Arrange
        $missingPath = '/path/to/missing/report.md';

        // Act
        $verificationResult = $this->seal->verifyReportOnLoad($missingPath);

        // Assert
        $this->assertFalse($verificationResult['is_valid']);
        $this->assertEquals(0, $verificationResult['integrity_score']);
        $this->assertContains('Report file not found', $verificationResult['errors']);
    }

    /** @test */
    public function it_detects_missing_provenance_on_load()
    {
        // Arrange - create report without provenance
        $this->createTestReportFile();

        // Act
        $verificationResult = $this->seal->verifyReportOnLoad($this->testReportPath);

        // Assert
        $this->assertFalse($verificationResult['is_valid']);
        $this->assertContains('No provenance record found in report', $verificationResult['errors']);
        $this->assertContains('Report may not be sealed or may be corrupted', $verificationResult['warnings']);
    }

    /** @test */
    public function it_performs_quick_integrity_check()
    {
        // Arrange
        $this->createReportWithProvenance();

        // Act
        $isValid = $this->seal->quickIntegrityCheck($this->testReportPath);

        // Assert
        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_fails_quick_integrity_check_for_missing_provenance()
    {
        // Arrange - create report without provenance
        $this->createTestReportFile();

        // Act
        $isValid = $this->seal->quickIntegrityCheck($this->testReportPath);

        // Assert
        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_fails_quick_integrity_check_for_missing_file()
    {
        // Arrange
        $missingPath = '/path/to/missing/report.md';

        // Act
        $isValid = $this->seal->quickIntegrityCheck($missingPath);

        // Assert
        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_generates_verification_report()
    {
        // Arrange
        $verificationResult = [
            'is_valid' => true,
            'integrity_score' => 95,
            'errors' => [],
            'warnings' => ['Minor warning'],
            'provenance' => [
                'commit_sha' => $this->testCommitSHA,
                'audit_hash' => 'ABCD1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890AB',
                'timestamp' => '2025-01-01T12:00:00Z'
            ],
            'verification_details' => [
                'verified_at' => '2025-01-01T12:30:00Z',
                'commit_exists' => true,
                'hash_matches' => true
            ]
        ];

        // Act
        $report = $this->seal->generateVerificationReport($verificationResult);

        // Assert
        $this->assertStringContainsString('# Report Integrity Verification', $report);
        $this->assertStringContainsString('**Overall Status**: ✅ VALID', $report);
        $this->assertStringContainsString('**Integrity Score**: 95/100', $report);
        $this->assertStringContainsString('## Verification Details', $report);
        $this->assertStringContainsString('## Provenance Information', $report);
        $this->assertStringContainsString('## ⚠️ Warnings', $report);
        $this->assertStringContainsString($this->testCommitSHA, $report);
    }

    /** @test */
    public function it_generates_verification_report_for_invalid_report()
    {
        // Arrange
        $verificationResult = [
            'is_valid' => false,
            'integrity_score' => 25,
            'errors' => ['Hash verification failed', 'Commit not found'],
            'warnings' => [],
            'provenance' => null,
            'verification_details' => [
                'verified_at' => '2025-01-01T12:30:00Z',
                'commit_exists' => false,
                'hash_matches' => false
            ]
        ];

        // Act
        $report = $this->seal->generateVerificationReport($verificationResult);

        // Assert
        $this->assertStringContainsString('**Overall Status**: ❌ INVALID', $report);
        $this->assertStringContainsString('**Integrity Score**: 25/100', $report);
        $this->assertStringContainsString('## ❌ Errors', $report);
        $this->assertStringContainsString('Hash verification failed', $report);
        $this->assertStringContainsString('Commit not found', $report);
    }

    /** @test */
    public function it_tracks_provenance_metadata()
    {
        // Act
        $result = $this->seal->trackProvenanceMetadata('test_metric', 'test_value');

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_gets_provenance_history()
    {
        // Arrange
        $this->createReportWithProvenance();

        // Act
        $history = $this->seal->getProvenanceHistory($this->testReportPath);

        // Assert
        $this->assertIsArray($history);
        // Should be empty for basic test report, but structure should be correct
        $this->assertIsArray($history);
    }

    /** @test */
    public function it_generates_provenance_summary()
    {
        // Arrange
        $this->createReportWithProvenance();

        // Act
        $summary = $this->seal->generateProvenanceSummary($this->testReportPath);

        // Assert
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_metadata_items', $summary);
        $this->assertArrayHasKey('metadata_types', $summary);
        $this->assertArrayHasKey('key_categories', $summary);
        $this->assertArrayHasKey('last_updated', $summary);
        $this->assertArrayHasKey('commit_sha', $summary);
        $this->assertArrayHasKey('integrity_status', $summary);
        
        $this->assertEquals($this->testCommitSHA, $summary['commit_sha']);
        $this->assertContains($summary['integrity_status'], ['valid', 'invalid']);
    }

    /** @test */
    public function it_handles_missing_report_for_provenance_operations()
    {
        // Arrange
        $missingPath = '/path/to/missing/report.md';

        // Act
        $history = $this->seal->getProvenanceHistory($missingPath);
        $summary = $this->seal->generateProvenanceSummary($missingPath);

        // Assert
        $this->assertEmpty($history);
        $this->assertEquals(0, $summary['total_metadata_items']);
        $this->assertNull($summary['commit_sha']);
    }

    /** @test */
    public function it_detects_tampering_in_missing_report()
    {
        // Arrange
        $missingPath = '/path/to/missing/report.md';

        // Act
        $tamperResult = $this->seal->detectTampering($missingPath);

        // Assert
        $this->assertTrue($tamperResult['is_tampered']);
        $this->assertEquals('critical', $tamperResult['confidence_level']);
        $this->assertNotEmpty($tamperResult['alerts']);
        $this->assertEquals('CRITICAL', $tamperResult['alerts'][0]['type']);
        $this->assertStringContainsString('missing', $tamperResult['alerts'][0]['message']);
    }

    /** @test */
    public function it_detects_tampering_in_report_without_provenance()
    {
        // Arrange - create report without provenance
        $this->createTestReportFile();

        // Act
        $tamperResult = $this->seal->detectTampering($this->testReportPath);

        // Assert
        $this->assertTrue($tamperResult['is_tampered']);
        $this->assertEquals('critical', $tamperResult['confidence_level']);
        $this->assertNotEmpty($tamperResult['alerts']);
        
        $hasProvenanceMissingAlert = false;
        foreach ($tamperResult['alerts'] as $alert) {
            if (strpos($alert['message'], 'Provenance section is missing') !== false) {
                $hasProvenanceMissingAlert = true;
                break;
            }
        }
        $this->assertTrue($hasProvenanceMissingAlert);
    }

    /** @test */
    public function it_detects_clean_report_with_valid_provenance()
    {
        // Arrange
        $this->createReportWithProvenance();

        // Act
        $tamperResult = $this->seal->detectTampering($this->testReportPath);

        // Assert
        $this->assertIsArray($tamperResult);
        $this->assertArrayHasKey('is_tampered', $tamperResult);
        $this->assertArrayHasKey('confidence_level', $tamperResult);
        $this->assertArrayHasKey('alerts', $tamperResult);
        $this->assertArrayHasKey('evidence', $tamperResult);
        $this->assertArrayHasKey('recommendations', $tamperResult);
        $this->assertArrayHasKey('detection_timestamp', $tamperResult);
    }

    /** @test */
    public function it_generates_tamper_alert_report()
    {
        // Arrange
        $tamperResult = [
            'is_tampered' => true,
            'confidence_level' => 'high',
            'alerts' => [
                [
                    'type' => 'CRITICAL',
                    'message' => 'Hash mismatch detected',
                    'severity' => 'critical',
                    'icon' => '🚨'
                ]
            ],
            'evidence' => ['Modified hash detected', 'Timestamp inconsistency'],
            'recommendations' => ['Re-run audit', 'Contact security team'],
            'detection_timestamp' => '2025-01-01T12:00:00Z'
        ];

        // Act
        $alertReport = $this->seal->generateTamperAlert($tamperResult);

        // Assert
        $this->assertStringContainsString('# 🚨 TAMPER DETECTION ALERT', $alertReport);
        $this->assertStringContainsString('**Status**: ❌ TAMPERED', $alertReport);
        $this->assertStringContainsString('**Confidence Level**: HIGH', $alertReport);
        $this->assertStringContainsString('## 🚨 ALERTS', $alertReport);
        $this->assertStringContainsString('## 🔍 EVIDENCE', $alertReport);
        $this->assertStringContainsString('## 📋 RECOMMENDATIONS', $alertReport);
        $this->assertStringContainsString('Hash mismatch detected', $alertReport);
        $this->assertStringContainsString('Modified hash detected', $alertReport);
        $this->assertStringContainsString('Re-run audit', $alertReport);
    }

    /** @test */
    public function it_generates_clean_tamper_alert_report()
    {
        // Arrange
        $tamperResult = [
            'is_tampered' => false,
            'confidence_level' => 'unknown',
            'alerts' => [],
            'evidence' => [],
            'recommendations' => ['✅ Report integrity verified - no tampering detected'],
            'detection_timestamp' => '2025-01-01T12:00:00Z'
        ];

        // Act
        $alertReport = $this->seal->generateTamperAlert($tamperResult);

        // Assert
        $this->assertStringContainsString('**Status**: ✅ CLEAN', $alertReport);
        $this->assertStringContainsString('✅ Report integrity verified', $alertReport);
    }

    private function createMockAuditReport(): AuditReport
    {
        $mockReport = \Mockery::mock(AuditReport::class);
        $mockReport->shouldReceive('getTotalFindings')->andReturn(15);
        $mockReport->shouldReceive('getEvidenceQualityScore')->andReturn(92.5);
        $mockReport->shouldReceive('getExecutionTime')->andReturn(1800); // 30 minutes
        $mockReport->shouldReceive('getPhaseResults')->andReturn(['phase1', 'phase2', 'phase3']);
        $mockReport->shouldReceive('getReproducibilityScore')->andReturn(88.0);
        $mockReport->shouldReceive('getChaosMode')->andReturn(true);
        $mockReport->shouldReceive('getEntropyScore')->andReturn(0.65);

        return $mockReport;
    }
}