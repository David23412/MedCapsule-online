<?php
// tests/Tinker/debug_enhanced_system.php

/**
 * DEBUG ENHANCED SYSTEM - Find out why it's performing so poorly
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;
use App\Services\WrittenAnswerEvaluationService;
use App\Services\Grading\Engines\SimilarityEngine;

echo "🔧 DEBUG ENHANCED SYSTEM - WHY IS IT FAILING?\n";
echo "===============================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    // Get services
    $writingGrader = app(WritingGraderService::class);
    $existingService = app(WrittenAnswerEvaluationService::class);

    if (!$writingGrader || !$existingService) {
        throw new Exception("Services not available");
    }

    echo "✅ Services loaded successfully\n\n";

    // Test a simple case that should work
    $student = 'BP = 120/80 mmHg';
    $correct = 'Blood pressure = 120/80 mmHg';
    $subject = 'medical';

    echo "🧪 TESTING SIMPLE CASE:\n";
    echo "Student: '$student'\n";
    echo "Correct: '$correct'\n";
    echo "Subject: $subject\n\n";

    // Test Enhanced System
    echo "🔍 ENHANCED SYSTEM ANALYSIS:\n";
    $enhancedStart = microtime(true);
    $enhancedResult = $writingGrader->gradeAnswer($student, $correct, $subject);
    $enhancedTime = round((microtime(true) - $enhancedStart) * 1000, 2);

    echo "Enhanced Result: " . number_format($enhancedResult['similarity'] * 100, 2) . "%\n";
    echo "Enhanced Confidence: " . number_format(($enhancedResult['confidence'] ?? 0) * 100, 2) . "%\n";
    echo "Enhanced Processing Time: {$enhancedTime}ms\n";

    if (isset($enhancedResult['algorithm_breakdown'])) {
        echo "Algorithm Breakdown:\n";
        foreach ($enhancedResult['algorithm_breakdown'] as $algorithm => $score) {
            echo "  $algorithm: " . number_format($score * 100, 2) . "%\n";
        }
    }

    echo "\n";

    // Test Existing System
    echo "🔍 EXISTING SYSTEM ANALYSIS:\n";
    $existingStart = microtime(true);
    $existingResult = $existingService->evaluateAnswer($student, $correct);
    $existingTime = round((microtime(true) - $existingStart) * 1000, 2);

    echo "Existing Result: " . number_format($existingResult['similarity'] * 100, 2) . "%\n";
    echo "Existing Processing Time: {$existingTime}ms\n";

    if (isset($existingResult['reason'])) {
        echo "Existing Reason: {$existingResult['reason']}\n";
    }

    echo "\n";

    // Test Similarity Engine directly
    echo "🔍 SIMILARITY ENGINE DIRECT TEST:\n";
    $similarityEngine = app(SimilarityEngine::class);

    if ($similarityEngine) {
        $directStart = microtime(true);
        $directResult = $similarityEngine->calculateAll($student, $correct);
        $directTime = round((microtime(true) - $directStart) * 1000, 2);

        echo "Direct Similarity Engine Results:\n";
        foreach ($directResult as $algorithm => $score) {
            echo "  $algorithm: " . number_format($score * 100, 2) . "%\n";
        }
        echo "Direct Processing Time: {$directTime}ms\n";
    } else {
        echo "❌ Could not load SimilarityEngine\n";
    }

    echo "\n";

    // Check if services are properly configured
    echo "🔍 SERVICE CONFIGURATION CHECK:\n";
    echo "Enhanced Service Class: " . get_class($writingGrader) . "\n";
    echo "Existing Service Class: " . get_class($existingService) . "\n";

    // Check if config is loaded
    $gradingConfig = config('grading');
    echo "Grading Config Loaded: " . ($gradingConfig ? 'YES' : 'NO') . "\n";
    if ($gradingConfig) {
        echo "Subjects Configured: " . count($gradingConfig['subjects'] ?? []) . "\n";
        echo "Algorithms Configured: " . count($gradingConfig['similarity']['algorithms'] ?? []) . "\n";
    }

    echo "\n";

    // Performance comparison
    echo "⚡ PERFORMANCE COMPARISON:\n";
    echo "Enhanced System: {$enhancedTime}ms\n";
    echo "Existing System: {$existingTime}ms\n";
    echo "Performance Ratio: " . round($enhancedTime / max($existingTime, 0.1), 2) . "x\n";

    echo "\n";

    // Accuracy comparison
    $enhancedScore = $enhancedResult['similarity'];
    $existingScore = $existingResult['similarity'] ?? 0;

    echo "🎯 ACCURACY COMPARISON:\n";
    echo "Enhanced System: " . number_format($enhancedScore * 100, 2) . "%\n";
    echo "Existing System: " . number_format($existingScore * 100, 2) . "%\n";
    echo "Accuracy Difference: " . ($enhancedScore > $existingScore ? '+' : '') . number_format(($enhancedScore - $existingScore) * 100, 2) . "%\n";

    echo "\n";

    // Diagnosis
    echo "🩺 DIAGNOSIS:\n";
    if ($enhancedScore < 0.3 && $existingScore > 0.8) {
        echo "❌ CRITICAL: Enhanced system is severely underperforming\n";
        echo "🔧 SOLUTION: Algorithm or integration issue detected\n";
    } elseif ($enhancedScore < $existingScore) {
        echo "⚠️ WARNING: Enhanced system performing worse than existing\n";
        echo "🔧 SOLUTION: Need to debug algorithm performance\n";
    } elseif ($enhancedScore > $existingScore + 0.1) {
        echo "✅ SUCCESS: Enhanced system significantly better!\n";
        echo "🎉 STATUS: Ready for production\n";
    } else {
        echo "📊 INFO: Performance comparable to existing system\n";
        echo "🔧 SOLUTION: Fine-tuning may be needed\n";
    }

    echo "\n🎉 DEBUG ANALYSIS COMPLETED\n";
    echo "==========================\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
