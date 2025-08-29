<?php
// tests/Tinker/debug_medical_processing.php

/**
 * DEBUG MEDICAL PROCESSING
 * Debug the enhanced medical text processing pipeline
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\Engines\SimilarityEngine;

echo "🔧 DEBUG MEDICAL PROCESSING\n";
echo "==========================\n\n";

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
            'student' => 'SNS increases HR, PNS decreases HR',
            'correct' => 'Sympathetic nervous system increases heart rate, parasympathetic decreases heart rate',
            'description' => 'Autonomic Nervous System'
        ]
    ];

    echo "🧪 DEBUGGING MEDICAL PROCESSING\n";
    echo "==============================\n\n";

    foreach ($testCases as $i => $testCase) {
        echo "🎯 Test " . ($i + 1) . ": " . $testCase['description'] . "\n";
        echo "Student: '" . $testCase['student'] . "'\n";
        echo "Correct: '" . $testCase['correct'] . "'\n\n";

        // Test the similarity calculation
        $results = $similarityEngine->calculateAll($testCase['student'], $testCase['correct']);

        echo "📊 SIMILARITY RESULTS:\n";
        echo "====================\n";
        foreach ($results as $algorithm => $score) {
            if ($algorithm !== 'processing_time') {
                echo sprintf("%-12s: %.3f\n", $algorithm, $score);
            }
        }
        echo sprintf("Processing Time: %.4fs\n", $results['processing_time']);
        echo "\n";

        // Test enhanced tokenization using reflection
        echo "🔍 ENHANCED TOKENIZATION TEST:\n";
        echo "=============================\n";

        $reflection = new ReflectionClass($similarityEngine);
        $method = $reflection->getMethod('enhancedTokenizeAndNormalize');
        $method->setAccessible(true);

        $enhancedStudentTokens = $method->invoke($similarityEngine, $testCase['student']);
        $enhancedCorrectTokens = $method->invoke($similarityEngine, $testCase['correct']);

        echo "Enhanced Student tokens: " . json_encode($enhancedStudentTokens) . "\n";
        echo "Enhanced Correct tokens: " . json_encode($enhancedCorrectTokens) . "\n\n";

        // Test abbreviation expansion
        $expandMethod = $reflection->getMethod('expandMedicalAbbreviations');
        $expandMethod->setAccessible(true);

        $expandedStudent = $expandMethod->invoke($similarityEngine, $testCase['student']);
        $expandedCorrect = $expandMethod->invoke($similarityEngine, $testCase['correct']);

        echo "🔍 ABBREVIATION EXPANSION TEST:\n";
        echo "===============================\n";
        echo "Original Student: '" . $testCase['student'] . "'\n";
        echo "Expanded Student: '" . $expandedStudent . "'\n\n";
        echo "Original Correct: '" . $testCase['correct'] . "'\n";
        echo "Expanded Correct: '" . $expandedCorrect . "'\n\n";

        echo str_repeat("=", 60) . "\n\n";
    }

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed at " . now()->format('Y-m-d H:i:s') . "\n";
