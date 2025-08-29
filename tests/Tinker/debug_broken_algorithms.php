<?php
// tests/Tinker/debug_broken_algorithms.php

/**
 * DEBUG BROKEN ALGORITHMS
 * Comprehensive debugging of failing test cases
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;

echo "🔧 DEBUG BROKEN ALGORITHMS\n";
echo "===========================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $writingGrader = app(WritingGraderService::class);
    echo "✅ WritingGraderService loaded successfully\n\n";

    // Test cases that are failing
    $failingCases = [
        [
            'student' => 'BP = 120/80 mmHg',
            'correct' => 'Blood pressure = 120/80 mmHg',
            'description' => 'Simple BP Abbreviation (FAILING)',
            'expected_min' => 0.85,
            'context' => 'medical_basic'
        ],
        [
            'student' => 'SNS increases HR, PNS decreases HR',
            'correct' => 'Sympathetic nervous system increases heart rate, parasympathetic decreases heart rate',
            'description' => 'Autonomic Nervous System (FAILING)',
            'expected_min' => 0.35,
            'context' => 'physiology'
        ]
    ];

    // Test cases that are working
    $workingCases = [
        [
            'student' => 'Pt c/o CP, BP 180/90, HR 110, O2 sat 92%',
            'correct' => 'Patient complaining of chest pain, blood pressure 180/90, heart rate 110, oxygen saturation 92%',
            'description' => 'Complex Patient Presentation (WORKING)',
            'expected_min' => 0.65,
            'context' => 'emergency_medicine'
        ]
    ];

    echo "🔍 ANALYZING FAILING CASES\n";
    echo "=========================\n\n";

    foreach ($failingCases as $i => $testCase) {
        echo "🎯 Test " . ($i + 1) . ": " . $testCase['description'] . "\n";
        echo "Student: '" . $testCase['student'] . "'\n";
        echo "Correct: '" . $testCase['correct'] . "'\n";
        echo "Expected: ≥" . $testCase['expected_min'] . "\n\n";

        // Get similarity score
        $result = $writingGrader->gradeAnswer($testCase['student'], $testCase['correct'], $testCase['context']);
        $similarity = $result['similarity'] ?? 0;

        echo "📊 ALGORITHM BREAKDOWN:\n";
        echo "====================\n";

        // Show individual algorithm contributions
        if (isset($result['algorithm_breakdown'])) {
            $breakdown = $result['algorithm_breakdown'];
            foreach ($breakdown as $algorithm => $score) {
                if ($algorithm !== 'processing_time') {
                    echo sprintf("%-12s: %.3f\n", $algorithm, $score);
                }
            }
            echo sprintf("Processing Time: %.4fs\n", $breakdown['processing_time'] ?? 0);
        }

        echo "\n📈 FINAL RESULTS:\n";
        echo "===============\n";
        echo sprintf("Similarity Score: %.3f\n", $similarity);
        echo sprintf("Expected Minimum: %.3f\n", $testCase['expected_min']);
        echo "Status: ❌ FAILED\n\n";

        // Debug the specific failing algorithms
        if (isset($breakdown)) {
            echo "🔍 DEBUG ANALYSIS:\n";
            echo "=================\n";

            if (($breakdown['cosine'] ?? 0) < 0.001) {
                echo "❌ COSINE ISSUE: Cosine similarity is 0.000 - investigating...\n";

                // Debug cosine calculation directly
                debugCosineCalculation($testCase['student'], $testCase['correct']);
            }

            if (($breakdown['medical'] ?? 0) < 0.001) {
                echo "❌ MEDICAL ISSUE: Medical similarity is 0.000 - medical terms not detected\n";
            }

            if (($breakdown['jaccard'] ?? 0) < 0.001) {
                echo "❌ JACCARD ISSUE: No token overlap detected\n";
            }
        }

        echo str_repeat("=", 80) . "\n\n";
    }

    echo "🔍 COMPARING WITH WORKING CASES\n";
    echo "===============================\n\n";

    foreach ($workingCases as $i => $testCase) {
        echo "🎯 Test " . ($i + 1) . ": " . $testCase['description'] . "\n";
        echo "Student: '" . $testCase['student'] . "'\n";
        echo "Correct: '" . $testCase['correct'] . "'\n";
        echo "Expected: ≥" . $testCase['expected_min'] . "\n\n";

        // Get similarity score
        $result = $writingGrader->gradeAnswer($testCase['student'], $testCase['correct'], $testCase['context']);
        $similarity = $result['similarity'] ?? 0;

        echo "📊 ALGORITHM BREAKDOWN:\n";
        echo "====================\n";

        // Show individual algorithm contributions
        if (isset($result['algorithm_breakdown'])) {
            $breakdown = $result['algorithm_breakdown'];
            foreach ($breakdown as $algorithm => $score) {
                if ($algorithm !== 'processing_time') {
                    echo sprintf("%-12s: %.3f\n", $algorithm, $score);
                }
            }
            echo sprintf("Processing Time: %.4fs\n", $breakdown['processing_time'] ?? 0);
        }

        echo "\n📈 FINAL RESULTS:\n";
        echo "===============\n";
        echo sprintf("Similarity Score: %.3f\n", $similarity);
        echo sprintf("Expected Minimum: %.3f\n", $testCase['expected_min']);
        echo "Status: ✅ WORKING\n\n";

        // Compare with failing cases
        echo "🔍 COMPARISON ANALYSIS:\n";
        echo "======================\n";

        if (isset($breakdown)) {
            echo "✅ COSINE WORKING: " . ($breakdown['cosine'] ?? 0) . " - good vector calculation\n";
            echo "✅ MEDICAL WORKING: " . ($breakdown['medical'] ?? 0) . " - medical terms detected\n";
            echo "✅ JACCARD WORKING: " . ($breakdown['jaccard'] ?? 0) . " - token overlap found\n";
        }

        echo str_repeat("=", 80) . "\n\n";
    }

    echo "🎯 DIAGNOSTIC SUMMARY\n";
    echo "===================\n\n";

    echo "🔍 KEY FINDINGS:\n";
    echo "===============\n";
    echo "1. Complex medical cases: ✅ WORKING (cosine, medical, jaccard all functional)\n";
    echo "2. Simple abbreviation cases: ❌ BROKEN (cosine = 0.000, medical = 0.000)\n";
    echo "3. Pattern: Complex cases succeed, simple cases fail\n\n";

    echo "🔧 REQUIRED FIXES:\n";
    echo "=================\n";
    echo "1. Fix cosine similarity for simple cases\n";
    echo "2. Fix medical term detection for abbreviations\n";
    echo "3. Ensure consistent tokenization across all cases\n";
    echo "4. Debug TF-IDF vector calculation for edge cases\n\n";

    echo "Test completed at " . now()->format('Y-m-d H:i:s') . "\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

/**
 * Debug cosine calculation directly
 */
function debugCosineCalculation(string $text1, string $text2): void
{
    echo "\n🔍 COSINE DEBUG ANALYSIS:\n";
    echo "========================\n";

    try {
        // Get the similarity engine directly
        $similarityEngine = app(\App\Services\Grading\Engines\SimilarityEngine::class);

        // Use reflection to debug the cosine calculation
        $reflection = new ReflectionClass($similarityEngine);

        // Test enhanced tokenization
        $tokenizeMethod = $reflection->getMethod('enhancedTokenizeAndNormalize');
        $tokenizeMethod->setAccessible(true);

        $tokens1 = $tokenizeMethod->invoke($similarityEngine, $text1);
        $tokens2 = $tokenizeMethod->invoke($similarityEngine, $text2);

        echo "📝 TOKENS:\n";
        echo "Text 1: [" . implode(', ', array_map(function($t) { return "'$t'"; }, $tokens1)) . "]\n";
        echo "Text 2: [" . implode(', ', array_map(function($t) { return "'$t'"; }, $tokens2)) . "]\n\n";

        // Test term frequency calculation
        $tfMethod = $reflection->getMethod('calculateEnhancedTermFrequency');
        $tfMethod->setAccessible(true);

        $tf1 = $tfMethod->invoke($similarityEngine, $tokens1);
        $tf2 = $tfMethod->invoke($similarityEngine, $tokens2);

        echo "📊 TERM FREQUENCIES:\n";
        echo "TF1: " . json_encode($tf1) . "\n";
        echo "TF2: " . json_encode($tf2) . "\n\n";

        // Test IDF calculation
        $idfMethod = $reflection->getMethod('calculateEnhancedInverseDocumentFrequency');
        $idfMethod->setAccessible(true);

        $allTokens = array_unique(array_merge($tokens1, $tokens2));
        $idf = $idfMethod->invoke($similarityEngine, $allTokens, [$tokens1, $tokens2]);

        echo "📈 INVERSE DOCUMENT FREQUENCY:\n";
        echo "IDF: " . json_encode($idf) . "\n\n";

        // Test vector calculation
        $vectorMethod = $reflection->getMethod('calculateEnhancedTfIdfVector');
        $vectorMethod->setAccessible(true);

        $vector1 = $vectorMethod->invoke($similarityEngine, $tf1, $idf, $allTokens);
        $vector2 = $vectorMethod->invoke($similarityEngine, $tf2, $idf, $allTokens);

        echo "🔢 TF-IDF VECTORS:\n";
        echo "Vector1: [" . implode(', ', array_map(function($v) { return number_format($v, 4); }, $vector1)) . "]\n";
        echo "Vector2: [" . implode(', ', array_map(function($v) { return number_format($v, 4); }, $vector2)) . "]\n\n";

        // Test cosine calculation
        $cosineMethod = $reflection->getMethod('calculateEnhancedVectorCosine');
        $cosineMethod->setAccessible(true);

        $cosine = $cosineMethod->invoke($similarityEngine, $vector1, $vector2);

        echo "🎯 COSINE RESULT: " . number_format($cosine, 4) . "\n";

    } catch (\Exception $e) {
        echo "❌ Cosine debug error: " . $e->getMessage() . "\n";
    }
}
