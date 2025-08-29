<?php
// tests/Tinker/emergency_debug.php

/**
 * EMERGENCY DEBUG - Force enhanced system execution
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;

echo "🚨 EMERGENCY DEBUG MODE ACTIVATED\n";
echo "==================================\n\n";

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

try {
    $writingGrader = app(WritingGraderService::class);
    echo "✅ WritingGraderService loaded\n\n";

    // Test with medical content to trigger enhanced processing
    echo "🚨 TESTING ENHANCED PROCESSING WITH MEDICAL CONTENT\n";
    echo "==================================================\n";

    $student = "Pt c/o CP SOB tachycardia";
    $correct = "Patient complaining of chest pain, shortness of breath, and tachycardia";

    echo "Student: '{$student}'\n";
    echo "Correct: '{$correct}'\n";
    echo "Subject: medical\n\n";

    $result = $writingGrader->gradeAnswer($student, $correct, 'medical');

    echo "🎯 RESULT:\n";
    echo "Similarity: " . number_format($result['similarity'] * 100, 2) . "%\n";
    echo "Confidence: " . (isset($result['confidence']) ? number_format($result['confidence'] * 100, 2) . "%" : "N/A") . "\n";
    echo "Reason: " . ($result['reason'] ?? 'N/A') . "\n";

    if (isset($result['algorithm_breakdown'])) {
        echo "\n📊 Algorithm Breakdown:\n";
        foreach ($result['algorithm_breakdown'] as $algorithm => $score) {
            $percentage = number_format($score * 100, 2);
            echo "  {$algorithm}: {$percentage}%\n";
        }
    }

    echo "\n✅ EMERGENCY DEBUG COMPLETED!\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
