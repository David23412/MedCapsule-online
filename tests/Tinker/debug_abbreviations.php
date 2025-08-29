<?php
// tests/Tinker/debug_abbreviations.php

/**
 * DEBUG ABBREVIATION EXPANSION - Find the exact issue
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\Engines\SimilarityEngine;

echo "🔧 DEBUG ABBREVIATION EXPANSION - FINDING THE EXACT ISSUE\n";
echo "=========================================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    // Get the similarity engine
    $similarityEngine = app(SimilarityEngine::class);

    if (!$similarityEngine) {
        throw new Exception("SimilarityEngine not available");
    }

    echo "✅ SimilarityEngine loaded successfully\n\n";

    // Test the abbreviation expansion directly
    echo "🔍 TESTING ABBREVIATION EXPANSION DIRECTLY\n";
    echo "===========================================\n";

    $testCases = [
        'BP' => 'blood pressure',
        'Pt c/o CP, SOB' => 'patient complaining of chest pain, shortness of breath',
        'DM' => 'diabetes mellitus',
        'MI' => 'myocardial infarction',
        'ASA' => 'aspirin',
        'RA, RV, LA, LV' => 'right atrium, right ventricle, left atrium, left ventricle',
    ];

    // Use reflection to access the private method
    $reflection = new ReflectionClass($similarityEngine);
    $method = $reflection->getMethod('expandMedicalAbbreviations');
    $method->setAccessible(true);

    foreach ($testCases as $input => $expected) {
        echo "Test: '$input'\n";
        echo "Expected: '$expected'\n";

        $result = $method->invoke($similarityEngine, $input);
        echo "Actual: '$result'\n";

        $success = stripos($result, $expected) !== false;
        $status = $success ? "✅ PASS" : "❌ FAIL";
        echo "Result: $status\n\n";
    }

    // Test enhanced tokenization
    echo "🔍 TESTING ENHANCED TOKENIZATION\n";
    echo "=================================\n";

    $tokenizeMethod = $reflection->getMethod('enhancedTokenizeAndNormalize');
    $tokenizeMethod->setAccessible(true);

    $tokenizationTests = [
        'BP = 120/80 mmHg' => ['blood', 'pressure'],
        'Pt c/o CP, SOB' => ['patient', 'complaining', 'chest', 'pain', 'shortness', 'breath'],
        'DM is hyperglycemia' => ['diabetes', 'mellitus'],
    ];

    foreach ($tokenizationTests as $input => $expectedTokens) {
        echo "Input: '$input'\n";
        echo "Expected tokens: " . implode(', ', $expectedTokens) . "\n";

        $tokens = $tokenizeMethod->invoke($similarityEngine, $input);
        echo "Actual tokens: " . implode(', ', $tokens) . "\n";

        $foundTokens = [];
        foreach ($expectedTokens as $expected) {
            $found = false;
            foreach ($tokens as $token) {
                if (stripos($token, $expected) !== false) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                $foundTokens[] = $expected;
            }
        }

        $success = count($foundTokens) === count($expectedTokens);
        $status = $success ? "✅ PASS" : "❌ FAIL";
        echo "Result: $status (Found: " . implode(', ', $foundTokens) . ")\n\n";
    }

    // Test the full similarity calculation
    echo "🔍 TESTING FULL SIMILARITY CALCULATION\n";
    echo "=======================================\n";

    $similarityTests = [
        [
            'student' => 'BP = 120/80 mmHg',
            'correct' => 'Blood pressure = 120/80 mmHg',
            'description' => 'Basic abbreviation expansion'
        ],
        [
            'student' => 'Pt c/o CP, SOB',
            'correct' => 'Patient complaining of chest pain, shortness of breath',
            'description' => 'Complex abbreviation expansion'
        ],
    ];

    foreach ($similarityTests as $test) {
        echo "Test: {$test['description']}\n";
        echo "Student: '{$test['student']}'\n";
        echo "Correct: '{$test['correct']}'\n";

        $results = $similarityEngine->calculateAll($test['student'], $test['correct']);

        echo "Algorithm Results:\n";
        foreach ($results as $algorithm => $score) {
            if ($algorithm !== 'processing_time') {
                $percentage = number_format($score * 100, 2);
                echo "  $algorithm: $percentage%\n";
            }
        }
        echo "\n";
    }

    echo "🎉 DEBUG ABBREVIATION EXPANSION COMPLETED!\n";
    echo "==========================================\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
