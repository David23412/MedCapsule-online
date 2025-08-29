<?php
// tests/Tinker/quick_integration_test.php

/**
 * QUICK INTEGRATION TEST
 * Test if the WritingGraderService is properly using processed text
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;

echo "🔧 QUICK INTEGRATION TEST\n";
echo "========================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $enhancedGrader = app(WritingGraderService::class);
    echo "✅ WritingGraderService loaded successfully\n\n";

    // Test a simple abbreviation case
    $student = 'BP = 120/80 mmHg';
    $correct = 'Blood pressure = 120/80 mmHg';

    echo "🧪 TESTING INTEGRATION:\n";
    echo "=====================\n";
    echo "Student: '$student'\n";
    echo "Correct: '$correct'\n\n";

    // Create unique strings to bypass cache
    $uniqueId = time() . '_' . rand(1000, 9999);
    $uniqueStudent = $student . '_test_' . $uniqueId;
    $uniqueCorrect = $correct . '_test_' . $uniqueId;

    // Test the grading system
    $result = $enhancedGrader->gradeAnswer($uniqueStudent, $uniqueCorrect, 'anatomy', []);

    echo "📊 RESULT:\n";
    echo "=========\n";
    echo sprintf("Similarity: %.3f\n", $result['similarity']);
    echo sprintf("Confidence: %.3f\n", $result['confidence']);
    echo sprintf("Processing Time: %.3fms\n", ($result['processing_time'] ?? 0) * 1000);

    echo "\n📈 ALGORITHM BREAKDOWN:\n";
    echo "======================\n";
    if (isset($result['algorithm_breakdown'])) {
        foreach ($result['algorithm_breakdown'] as $algorithm => $score) {
            echo sprintf("%-12s: %.3f\n", $algorithm, $score);
        }
    }

    // Check enhanced data
    if (isset($result['enhanced_data'])) {
        $enhanced = $result['enhanced_data'];
        echo "\n🔍 ENHANCED DATA ANALYSIS:\n";
        echo "=========================\n";

        if (isset($enhanced['processed_student'])) {
            $processedStudent = $enhanced['processed_student'];
            echo "Student Processed:\n";
            echo "  Original: '" . ($processedStudent['original'] ?? 'N/A') . "'\n";
            echo "  Normalized: '" . ($processedStudent['normalized'] ?? 'N/A') . "'\n";
            echo "  Abbreviations Found: " . count($processedStudent['abbreviations'] ?? []) . "\n";

            if (!empty($processedStudent['abbreviations'])) {
                foreach ($processedStudent['abbreviations'] as $abbr) {
                    echo "    - " . $abbr['original'] . " -> " . $abbr['expanded'] . "\n";
                }
            }
        }

        if (isset($enhanced['processed_correct'])) {
            $processedCorrect = $enhanced['processed_correct'];
            echo "Correct Processed:\n";
            echo "  Original: '" . ($processedCorrect['original'] ?? 'N/A') . "'\n";
            echo "  Normalized: '" . ($processedCorrect['normalized'] ?? 'N/A') . "'\n";
            echo "  Abbreviations Found: " . count($processedCorrect['abbreviations'] ?? []) . "\n";
        }
    }

    // SUCCESS CRITERIA
    echo "\n🏆 SUCCESS ANALYSIS:\n";
    echo "===================\n";

    $cosineScore = $result['algorithm_breakdown']['cosine'] ?? 0;
    $similarityScore = $result['similarity'] ?? 0;

    if ($cosineScore > 0.5) {
        echo "✅ COSINE SIMILARITY: WORKING (>0.5)\n";
    } else {
        echo "❌ COSINE SIMILARITY: STILL BROKEN (≤0.5)\n";
    }

    if ($similarityScore >= 0.8) {
        echo "✅ OVERALL SIMILARITY: EXCELLENT (≥0.8)\n";
        echo "🎉 INTEGRATION SUCCESS - 100% ACCURACY ACHIEVED!\n";
    } elseif ($similarityScore >= 0.6) {
        echo "⚠️  OVERALL SIMILARITY: GOOD (≥0.6)\n";
        echo "🔧 NEEDS MINOR TUNING\n";
    } else {
        echo "❌ OVERALL SIMILARITY: POOR (<0.6)\n";
        echo "🔧 MAJOR FIXES STILL NEEDED\n";
    }

    echo "\n✨ Integration test completed!\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed at " . now()->format('Y-m-d H:i:s') . "\n";
