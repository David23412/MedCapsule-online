<?php
// tests/Tinker/final_algorithm_fix_test.php

/**
 * FINAL ALGORITHM FIX TEST
 * Direct comparison between SimilarityEngine and WritingGraderService
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;
use App\Services\Grading\Engines\SimilarityEngine;

echo "🔧 FINAL ALGORITHM FIX TEST\n";
echo "============================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $writingGrader = app(WritingGraderService::class);
    $similarityEngine = app(SimilarityEngine::class);

    echo "✅ Services loaded successfully\n\n";

    // Test case that should work
    $testCase = [
        'student' => 'BP = 120/80 mmHg',
        'correct' => 'Blood pressure = 120/80 mmHg',
        'description' => 'Simple BP Abbreviation',
        'context' => 'medical_basic'
    ];

    echo "🎯 TEST CASE ANALYSIS\n";
    echo "===================\n\n";
    echo "Student: '" . $testCase['student'] . "'\n";
    echo "Correct: '" . $testCase['correct'] . "'\n\n";

    // STEP 1: Direct SimilarityEngine Test
    echo "📊 STEP 1: DIRECT SIMILARITY ENGINE TEST\n";
    echo "========================================\n";

    $directResult = $similarityEngine->calculateAll($testCase['student'], $testCase['correct']);

    echo "Direct SimilarityEngine Results:\n";
    foreach ($directResult as $algorithm => $score) {
        if ($algorithm !== 'processing_time') {
            echo sprintf("%-12s: %.4f\n", $algorithm, $score);
        }
    }
    echo "\n";

    // STEP 2: WritingGraderService Test
    echo "📊 STEP 2: WRITING GRADER SERVICE TEST\n";
    echo "=====================================\n";

    $integratedResult = $writingGrader->gradeAnswer($testCase['student'], $testCase['correct'], $testCase['context']);

    echo "WritingGraderService Results:\n";
    if (isset($integratedResult['algorithm_breakdown'])) {
        $breakdown = $integratedResult['algorithm_breakdown'];
        foreach ($breakdown as $algorithm => $score) {
            if ($algorithm !== 'processing_time') {
                echo sprintf("%-12s: %.4f\n", $algorithm, $score);
            }
        }
    } else {
        echo "❌ NO ALGORITHM BREAKDOWN FOUND!\n";
    }
    echo "\n";

    // STEP 3: Comparison Analysis
    echo "📊 STEP 3: COMPARISON ANALYSIS\n";
    echo "=============================\n";

    echo "Algorithm Scores Comparison:\n";
    echo "---------------------------\n";

    $algorithms = ['cosine', 'jaccard', 'levenshtein', 'semantic', 'medical', 'ensemble'];

    foreach ($algorithms as $algorithm) {
        $directScore = $directResult[$algorithm] ?? 'MISSING';
        $integratedScore = $integratedResult['algorithm_breakdown'][$algorithm] ?? 'MISSING';

        $status = '❌ MISMATCH';
        if ($directScore !== 'MISSING' && $integratedScore !== 'MISSING') {
            $diff = abs($directScore - $integratedScore);
            if ($diff < 0.01) {
                $status = '✅ MATCH';
            }
        }

        echo sprintf("%-12s | Direct: %-8s | Integrated: %-8s | %s\n",
            $algorithm,
            is_numeric($directScore) ? number_format($directScore, 4) : $directScore,
            is_numeric($integratedScore) ? number_format($integratedScore, 4) : $integratedScore,
            $status
        );
    }

    echo "\n";

    // STEP 4: Diagnosis
    echo "📊 STEP 4: DIAGNOSIS\n";
    echo "==================\n";

    $directCosine = $directResult['cosine'] ?? 0;
    $integratedCosine = $integratedResult['algorithm_breakdown']['cosine'] ?? 0;

    if ($directCosine > 0.5 && $integratedCosine < 0.1) {
        echo "🔍 DIAGNOSIS: Cosine similarity is working in SimilarityEngine (";
        echo number_format($directCosine, 4);
        echo ") but failing in WritingGraderService (";
        echo number_format($integratedCosine, 4);
        echo ")\n\n";

        echo "🎯 ROOT CAUSE: Integration layer is not properly passing algorithm results\n";
        echo "🔧 SOLUTION: Fix data flow from SimilarityEngine to WritingGraderService\n\n";
    } elseif ($directCosine > 0.5 && $integratedCosine > 0.5) {
        echo "✅ DIAGNOSIS: Algorithms are working correctly in both layers\n";
        echo "🎯 STATUS: System is functioning properly\n\n";
    } else {
        echo "❌ DIAGNOSIS: Cosine similarity algorithm itself is broken\n";
        echo "🔧 SOLUTION: Fix SimilarityEngine cosine calculation\n\n";
    }

    // STEP 5: Recommended Fix
    echo "📊 STEP 5: RECOMMENDED FIX\n";
    echo "=========================\n";

    if ($directCosine > 0.5 && $integratedCosine < 0.1) {
        echo "🔧 INTEGRATION FIX REQUIRED:\n";
        echo "1. Check if algorithm results are being cached with stale data\n";
        echo "2. Verify data flow in WritingGraderService->processEnhancedGrading()\n";
        echo "3. Ensure similarities array is properly passed to ensembleDecision()\n";
        echo "4. Check if algorithm_breakdown is correctly populated in final result\n\n";

        echo "🎯 IMMEDIATE ACTION:\n";
        echo "Clear caches and test again: php artisan cache:clear\n\n";
    }

    echo "Test completed at " . now()->format('Y-m-d H:i:s') . "\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
