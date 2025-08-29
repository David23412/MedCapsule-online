<?php
// tests/Tinker/debug_abbreviation_expansion.php

/**
 * DEBUG ABBREVIATION EXPANSION
 * Rigorous testing to verify abbreviation expansion is working
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\Engines\TextProcessor;

echo "🔍 DEBUGGING ABBREVIATION EXPANSION\n";
echo "===================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $textProcessor = app(TextProcessor::class);
    echo "✅ TextProcessor loaded successfully\n\n";

    // Test cases for abbreviation expansion
    $testCases = [
        'BP = 120/80 mmHg',
        'HR is 80 bpm',
        'DM patient with BG 200',
        'Pt c/o CP, BP 180/90',
        'MI with STEMI, troponin ↑',
        'SNS increases HR, PNS decreases HR',
        'TCA cycle produces NADH and FADH2',
        'COPD exacerbation, O2 sat 88%'
    ];

    echo "🧪 TESTING ABBREVIATION EXPANSION:\n";
    echo "=================================\n\n";

    foreach ($testCases as $i => $testCase) {
        echo "Test " . ($i + 1) . ": '$testCase'\n";
        echo "Original: $testCase\n";

        // Step 1: Full text processing (bypass cache for debugging)
        $uniqueTestCase = $testCase . '_debug_' . time() . '_' . $i; // Make cache key unique
        $processed = $textProcessor->normalizeText($uniqueTestCase);

        echo "Full Processing Result:\n";
        echo "  Original: " . $processed['original'] . "\n";
        echo "  Cleaned: " . $processed['cleaned'] . "\n";
        echo "  Normalized: " . $processed['normalized'] . "\n";
        echo "  Tokens: " . json_encode($processed['tokens']) . "\n";
        echo "  Abbreviations: " . json_encode($processed['abbreviations']) . "\n";

        // CRITICAL CHECK: Is abbreviation actually expanded in normalized text?
        $originalHasBP = stripos($testCase, 'BP') !== false;
        $normalizedHasBP = stripos($processed['normalized'], 'bp') !== false;
        $normalizedHasBloodPressure = stripos($processed['normalized'], 'blood pressure') !== false;

        echo "  CRITICAL CHECK:\n";
        echo "    Original has 'BP': " . ($originalHasBP ? 'YES' : 'NO') . "\n";
        echo "    Normalized has 'bp': " . ($normalizedHasBP ? 'YES' : 'NO') . "\n";
        echo "    Normalized has 'blood pressure': " . ($normalizedHasBloodPressure ? 'YES' : 'NO') . "\n";

        if ($originalHasBP && $normalizedHasBP && !$normalizedHasBloodPressure) {
            echo "    ❌ FAILURE: Abbreviation NOT expanded in normalized text!\n";
            echo "    🔍 DEBUG: Need to fix advancedNormalization method\n";
        } elseif ($originalHasBP && !$normalizedHasBP && $normalizedHasBloodPressure) {
            echo "    ✅ SUCCESS: Abbreviation properly expanded!\n";
        } else {
            echo "    ⚠️  Mixed result - needs investigation\n";
        }

        echo "\n" . str_repeat("-", 80) . "\n\n";
    }

    // Test specific abbreviation expansion with detailed debugging
    echo "🔧 DETAILED ABBREVIATION EXPANSION TEST:\n";
    echo "==========================================\n";

    $manualTest = "BP = 120/80 mmHg";
    echo "Testing: '$manualTest'\n";

    // Use public method to get processed result
    $fullProcessed = $textProcessor->normalizeText($manualTest);

    echo "DETAILED ANALYSIS:\n";
    echo "  Original: '" . $fullProcessed['original'] . "'\n";
    echo "  Cleaned: '" . $fullProcessed['cleaned'] . "'\n";
    echo "  Normalized: '" . $fullProcessed['normalized'] . "'\n";
    echo "  Tokens: " . json_encode($fullProcessed['tokens']) . "\n";
    echo "  Abbreviations: " . json_encode($fullProcessed['abbreviations']) . "\n";

    // Check if abbreviations array is populated
    if (!empty($fullProcessed['abbreviations'])) {
        echo "  Found abbreviations in metadata:\n";
        foreach ($fullProcessed['abbreviations'] as $abbr) {
            echo "    - " . $abbr['original'] . " -> " . $abbr['expanded'] . "\n";
        }
    } else {
        echo "  No abbreviations found in metadata\n";
    }

    // Check the actual tokens vs normalized text
    $tokens = $fullProcessed['tokens'];
    $normalized = $fullProcessed['normalized'];

    echo "  Token analysis:\n";
    foreach ($tokens as $token) {
        $inNormalized = stripos($normalized, $token) !== false;
        echo "    Token '$token': " . ($inNormalized ? 'FOUND' : 'NOT FOUND') . " in normalized text\n";
    }

    // Check for specific abbreviations
    $abbreviationsToCheck = ['bp', 'hr', 'dm', 'bg', 'mi', 'pt', 'sns', 'pns', 'tca', 'copd', 'o2'];
    echo "  Abbreviation check in normalized text:\n";
    foreach ($abbreviationsToCheck as $abbr) {
        $found = stripos($normalized, $abbr) !== false;
        echo "    '$abbr': " . ($found ? 'PRESENT' : 'NOT PRESENT') . "\n";
    }

    echo "\n" . str_repeat("=", 80) . "\n";
    echo "DEBUGGING COMPLETE\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✨ Debug completed at " . now()->format('Y-m-d H:i:s') . "\n";
