<?php
// tests/Tinker/debug_cosine_vectors.php

/**
 * DEBUG COSINE VECTORS
 * Debug the TF-IDF vector calculation in cosine similarity
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\Engines\SimilarityEngine;

echo "🔧 DEBUG COSINE VECTORS\n";
echo "========================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $similarityEngine = app(SimilarityEngine::class);
    echo "✅ SimilarityEngine loaded successfully\n\n";

    // Test cases that are failing
    $testCases = [
        [
            'student' => 'Pt c/o CP, BP 180/90, HR 110, O2 sat 92%',
            'correct' => 'Patient complaining of chest pain, blood pressure 180/90, heart rate 110, oxygen saturation 92%',
            'description' => 'Complex Patient Presentation'
        ],
        [
            'student' => 'BP = 120/80 mmHg',
            'correct' => 'Blood pressure = 120/80 mmHg',
            'description' => 'Simple BP Abbreviation (Working)'
        ]
    ];

    echo "🧪 DEBUGGING TF-IDF VECTOR CALCULATION\n";
    echo "=======================================\n\n";

    foreach ($testCases as $i => $testCase) {
        echo "🎯 Test " . ($i + 1) . ": " . $testCase['description'] . "\n";
        echo "Student: '" . $testCase['student'] . "'\n";
        echo "Correct: '" . $testCase['correct'] . "'\n\n";

        // Use reflection to access private methods for debugging
        $reflection = new ReflectionClass($similarityEngine);

        // Test enhanced tokenization
        $tokenizeMethod = $reflection->getMethod('enhancedTokenizeAndNormalize');
        $tokenizeMethod->setAccessible(true);

        $studentTokens = $tokenizeMethod->invoke($similarityEngine, $testCase['student']);
        $correctTokens = $tokenizeMethod->invoke($similarityEngine, $testCase['correct']);

        echo "📝 ENHANCED TOKENS:\n";
        echo "Student: [" . implode(', ', array_map(function($t) { return "'$t'"; }, $studentTokens)) . "]\n";
        echo "Correct: [" . implode(', ', array_map(function($t) { return "'$t'"; }, $correctTokens)) . "]\n\n";

        // Test term frequency calculation
        $tfMethod = $reflection->getMethod('calculateEnhancedTermFrequency');
        $tfMethod->setAccessible(true);

        $studentTf = $tfMethod->invoke($similarityEngine, $studentTokens);
        $correctTf = $tfMethod->invoke($similarityEngine, $correctTokens);

        echo "📊 TERM FREQUENCIES:\n";
        echo "Student TF: " . json_encode($studentTf) . "\n";
        echo "Correct TF: " . json_encode($correctTf) . "\n\n";

        // Test inverse document frequency
        $idfMethod = $reflection->getMethod('calculateEnhancedInverseDocumentFrequency');
        $idfMethod->setAccessible(true);

        $allTokens = array_unique(array_merge($studentTokens, $correctTokens));
        $idf = $idfMethod->invoke($similarityEngine, $allTokens, [$studentTokens, $correctTokens]);

        echo "📈 INVERSE DOCUMENT FREQUENCY:\n";
        echo "IDF: " . json_encode($idf) . "\n\n";

        // Test TF-IDF vector calculation
        $vectorMethod = $reflection->getMethod('calculateEnhancedTfIdfVector');
        $vectorMethod->setAccessible(true);

        $studentVector = $vectorMethod->invoke($similarityEngine, $studentTf, $idf, $allTokens);
        $correctVector = $vectorMethod->invoke($similarityEngine, $correctTf, $idf, $allTokens);

        echo "🔢 TF-IDF VECTORS:\n";
        echo "Student Vector: [" . implode(', ', array_map(function($v) { return number_format($v, 4); }, $studentVector)) . "]\n";
        echo "Correct Vector: [" . implode(', ', array_map(function($v) { return number_format($v, 4); }, $correctVector)) . "]\n\n";

        // Calculate magnitudes
        $studentMagnitude = sqrt(array_sum(array_map(function($v) { return $v * $v; }, $studentVector)));
        $correctMagnitude = sqrt(array_sum(array_map(function($v) { return $v * $v; }, $correctVector)));

        echo "📏 VECTOR MAGNITUDES:\n";
        echo "Student Magnitude: " . number_format($studentMagnitude, 4) . "\n";
        echo "Correct Magnitude: " . number_format($correctMagnitude, 4) . "\n\n";

        // Test cosine similarity calculation
        $cosineMethod = $reflection->getMethod('calculateEnhancedVectorCosine');
        $cosineMethod->setAccessible(true);

        $cosine = $cosineMethod->invoke($similarityEngine, $studentVector, $correctVector);

        echo "🎯 COSINE SIMILARITY CALCULATION:\n";
        echo "Cosine Result: " . number_format($cosine, 4) . "\n\n";

        echo str_repeat("=", 80) . "\n\n";
    }

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed at " . now()->format('Y-m-d H:i:s') . "\n";
