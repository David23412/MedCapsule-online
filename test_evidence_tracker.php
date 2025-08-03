<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Audit\Components\EvidenceTracker;
use App\Services\Audit\Models\Finding;

// Test the Evidence Tracker file/line reference requirement

echo "Testing Evidence Tracker - File/Line Reference Requirement\n";
echo "=========================================================\n\n";

$tracker = new EvidenceTracker();

// Test 1: Finding WITHOUT file references (should be rejected)
echo "Test 1: Finding without file references (should be REJECTED)\n";
$finding1 = new Finding(
    'TEST-001',
    'critical',
    'This is a test finding without file references',
    'test',
    'test_component'
);

$result1 = $tracker->validateFinding($finding1);
echo "Valid: " . ($result1->isValid() ? 'YES' : 'NO') . "\n";
echo "Errors: " . implode(', ', $result1->getErrors()) . "\n";
echo "Quality Score: " . $result1->getQualityScore() . "\n\n";

// Test 2: Finding WITH valid file references (should be accepted)
echo "Test 2: Finding with valid file references (should be ACCEPTED)\n";
$finding2 = new Finding(
    'TEST-002',
    'high',
    'This is a test finding with proper file references',
    'test',
    'test_component'
);

// Add a valid file reference (using this test file itself)
$finding2->addFileReference('test_evidence_tracker.php', 10, 'Test file reference context');

// Add some metrics to improve quality score
$finding2->addMetric('complexity', 5, 'score', 'Cyclomatic complexity');

// Add evidence to avoid speculation detection
$finding2->addEvidence('runtime_log', 'Test log data', 'Runtime evidence');

$result2 = $tracker->validateFinding($finding2);
echo "Valid: " . ($result2->isValid() ? 'YES' : 'NO') . "\n";
echo "Errors: " . implode(', ', $result2->getErrors()) . "\n";
echo "Quality Score: " . $result2->getQualityScore() . "\n\n";

// Test 3: Finding with file reference but missing line number
echo "Test 3: Finding with file reference but missing line number (should be REJECTED)\n";
$finding3 = new Finding(
    'TEST-003',
    'medium',
    'This finding has incomplete file reference',
    'test',
    'test_component'
);

// Manually add incomplete file reference (this would normally be prevented by the addFileReference method)
$finding3->fileReferences = [['file' => 'test.php']]; // Missing line number

$result3 = $tracker->validateFinding($finding3);
echo "Valid: " . ($result3->isValid() ? 'YES' : 'NO') . "\n";
echo "Errors: " . implode(', ', $result3->getErrors()) . "\n";
echo "Quality Score: " . $result3->getQualityScore() . "\n\n";

// Test 4: Finding with non-existent file reference
echo "Test 4: Finding with non-existent file reference (should be REJECTED)\n";
$finding4 = new Finding(
    'TEST-004',
    'low',
    'This finding references a non-existent file',
    'test',
    'test_component'
);

$finding4->addFileReference('non_existent_file.php', 1, 'This file does not exist');

$result4 = $tracker->validateFinding($finding4);
echo "Valid: " . ($result4->isValid() ? 'YES' : 'NO') . "\n";
echo "Errors: " . implode(', ', $result4->getErrors()) . "\n";
echo "Quality Score: " . $result4->getQualityScore() . "\n\n";

// Test 5: Enforce file reference requirement on array of findings
echo "Test 5: Enforce file reference requirement on array of findings\n";
$findings = [$finding1, $finding2, $finding3, $finding4];
$validFindings = $tracker->enforceFileReferenceRequirement($findings);

echo "Original findings: " . count($findings) . "\n";
echo "Valid findings after enforcement: " . count($validFindings) . "\n";
echo "Rejected findings: " . (count($findings) - count($validFindings)) . "\n\n";

// Test 6: Get statistics
echo "Test 6: Evidence quality statistics\n";
$stats = $tracker->getEvidenceStatistics();
print_r($stats);

echo "\nFile reference statistics:\n";
$fileStats = $tracker->getFileReferenceStatistics();
print_r($fileStats);

echo "\nTest completed successfully!\n";