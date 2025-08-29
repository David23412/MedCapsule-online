<?php
// tests/Tinker/debug_cosine_detailed.php

/**
 * DETAILED COSINE DEBUG
 * Step-by-step debugging of cosine similarity calculation
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\Engines\SimilarityEngine;

echo "🔧 DETAILED COSINE DEBUG\n";
echo "========================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $similarityEngine = app(SimilarityEngine::class);

    // Test case that should work
    $text1 = 'BP = 120/80 mmHg';
    $text2 = 'Blood pressure = 120/80 mmHg';

    echo "🎯 TESTING CASE:\n";
    echo "Text 1: '$text1'\n";
    echo "Text 2: '$text2'\n\n";

    // STEP 1: Enhanced Tokenization
    echo "📊 STEP 1: ENHANCED TOKENIZATION\n";
    echo "===============================\n";

    $reflection = new ReflectionClass($similarityEngine);
    $tokenizeMethod = $reflection->getMethod('enhancedTokenizeAndNormalize');
    $tokenizeMethod->setAccessible(true);

    $tokens1 = $tokenizeMethod->invoke($similarityEngine, $text1);
    $tokens2 = $tokenizeMethod->invoke($similarityEngine, $text2);

    echo "Tokens 1: [" . implode(', ', array_map(function($t) { return "'$t'"; }, $tokens1)) . "]\n";
    echo "Tokens 2: [" . implode(', ', array_map(function($t) { return "'$t'"; }, $tokens2)) . "]\n\n";

    // STEP 2: Term Frequency Calculation
    echo "📊 STEP 2: TERM FREQUENCY CALCULATION\n";
    echo "=====================================\n";

    $tfMethod = $reflection->getMethod('calculateEnhancedTermFrequency');
    $tfMethod->setAccessible(true);

    $tf1 = $tfMethod->invoke($similarityEngine, $tokens1);
    $tf2 = $tfMethod->invoke($similarityEngine, $tokens2);

    echo "TF1:\n";
    foreach ($tf1 as $term => $freq) {
        echo sprintf("  %-15s: %.6f\n", "'$term'", $freq);
    }
    echo "\nTF2:\n";
    foreach ($tf2 as $term => $freq) {
        echo sprintf("  %-15s: %.6f\n", "'$term'", $freq);
    }
    echo "\n";

    // STEP 3: Create Vocabulary
    echo "📊 STEP 3: CREATE VOCABULARY\n";
    echo "===========================\n";

    $allTokens = array_unique(array_merge($tokens1, $tokens2));
    echo "Vocabulary: [" . implode(', ', array_map(function($t) { return "'$t'"; }, $allTokens)) . "]\n";
    echo "Vocabulary size: " . count($allTokens) . "\n\n";

    // STEP 4: Calculate IDF
    echo "📊 STEP 4: INVERSE DOCUMENT FREQUENCY\n";
    echo "=====================================\n";

    $idfMethod = $reflection->getMethod('calculateEnhancedInverseDocumentFrequency');
    $idfMethod->setAccessible(true);

    $idf = $idfMethod->invoke($similarityEngine, $allTokens, [$tokens1, $tokens2]);

    echo "IDF values:\n";
    foreach ($allTokens as $token) {
        echo sprintf("  %-15s: %.6f\n", "'$token'", $idf[$token] ?? 0);
    }
    echo "\n";

    // STEP 5: Calculate TF-IDF Vectors
    echo "📊 STEP 5: TF-IDF VECTOR CALCULATION\n";
    echo "===================================\n";

    $vectorMethod = $reflection->getMethod('calculateEnhancedTfIdfVector');
    $vectorMethod->setAccessible(true);

    $vector1 = $vectorMethod->invoke($similarityEngine, $tf1, $idf, $allTokens);
    $vector2 = $vectorMethod->invoke($similarityEngine, $tf2, $idf, $allTokens);

    echo "Vector 1: [";
    for ($i = 0; $i < count($vector1); $i++) {
        if ($i > 0) echo ", ";
        echo sprintf("%.6f", $vector1[$i]);
    }
    echo "]\n";

    echo "Vector 2: [";
    for ($i = 0; $i < count($vector2); $i++) {
        if ($i > 0) echo ", ";
        echo sprintf("%.6f", $vector2[$i]);
    }
    echo "]\n\n";

    // STEP 6: Vector Statistics
    echo "📊 STEP 6: VECTOR STATISTICS\n";
    echo "===========================\n";

    $vector1Sum = array_sum($vector1);
    $vector2Sum = array_sum($vector2);
    $vector1Mag = sqrt(array_sum(array_map(function($v) { return $v * $v; }, $vector1)));
    $vector2Mag = sqrt(array_sum(array_map(function($v) { return $v * $v; }, $vector2)));

    echo sprintf("Vector 1 sum: %.6f\n", $vector1Sum);
    echo sprintf("Vector 2 sum: %.6f\n", $vector2Sum);
    echo sprintf("Vector 1 magnitude: %.6f\n", $vector1Mag);
    echo sprintf("Vector 2 magnitude: %.6f\n", $vector2Mag);
    echo "\n";

    // STEP 7: Cosine Calculation
    echo "📊 STEP 7: COSINE CALCULATION\n";
    echo "=============================\n";

    $cosineMethod = $reflection->getMethod('calculateEnhancedVectorCosine');
    $cosineMethod->setAccessible(true);

    $cosine = $cosineMethod->invoke($similarityEngine, $vector1, $vector2);

    echo sprintf("Final cosine similarity: %.6f\n\n", $cosine);

    // STEP 8: Manual Verification
    echo "📊 STEP 8: MANUAL VERIFICATION\n";
    echo "==============================\n";

    // Manual cosine calculation
    $dotProduct = 0;
    for ($i = 0; $i < count($vector1); $i++) {
        $val1 = $vector1[$i] ?? 0;
        $val2 = $vector2[$i] ?? 0;
        $dotProduct += $val1 * $val2;
    }

    $manualCosine = 0;
    if ($vector1Mag > 1e-10 && $vector2Mag > 1e-10) {
        $manualCosine = $dotProduct / ($vector1Mag * $vector2Mag);
        $manualCosine = max(-1.0, min(1.0, $manualCosine));
    }

    echo sprintf("Manual dot product: %.6f\n", $dotProduct);
    echo sprintf("Manual cosine: %.6f\n", $manualCosine);
    echo sprintf("Method result: %.6f\n", $cosine);
    echo sprintf("Match: %s\n\n", abs($manualCosine - $cosine) < 1e-10 ? "✅ YES" : "❌ NO");

    // STEP 9: Analysis
    echo "📊 STEP 9: ANALYSIS & DIAGNOSIS\n";
    echo "===============================\n";

    if ($vector1Mag < 1e-10 || $vector2Mag < 1e-10) {
        echo "❌ DIAGNOSIS: One or both vectors have zero magnitude\n";
        echo "🔧 SOLUTION: Check TF-IDF calculation and tokenization\n";
    } elseif (abs($manualCosine - $cosine) > 1e-10) {
        echo "❌ DIAGNOSIS: Cosine calculation method has a bug\n";
        echo "🔧 SOLUTION: Fix the calculateEnhancedVectorCosine method\n";
    } elseif ($cosine < 0.1) {
        echo "⚠️  DIAGNOSIS: Cosine is very low but calculation is correct\n";
        echo "🔧 SOLUTION: Check if texts are actually similar\n";
    } else {
        echo "✅ DIAGNOSIS: Cosine calculation is working correctly\n";
        echo "🎯 STATUS: Algorithm is functioning properly\n";
    }

    echo "\nTest completed at " . now()->format('Y-m-d H:i:s') . "\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
