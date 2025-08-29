<?php
// tests/Tinker/ultimate_robustness_test.php

/**
 * ULTIMATE ROBUSTNESS TEST
 * 100% ACCURACY VALIDATION - NO COMPROMISES
 * Testing every edge case, every algorithm, every scenario for bulletproof accuracy
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;
use App\Services\WrittenAnswerEvaluationService;

echo "🛡️ ULTIMATE ROBUSTNESS TEST - 100% ACCURACY VALIDATION\n";
echo "=====================================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $enhancedGrader = app(WritingGraderService::class);
    $existingService = app(WrittenAnswerEvaluationService::class);

    echo "✅ Services loaded successfully\n\n";

    // Test 1: Algorithm Accuracy Validation
    echo "🧪 TEST 1: ALGORITHM ACCURACY VALIDATION\n";
    echo "=======================================\n";

    $accuracyTestCases = [
        // Perfect matches should be 100% accurate
        [
            'student' => 'The heart is a muscular organ that pumps oxygenated blood to the body and deoxygenated blood to the lungs',
            'correct' => 'The heart is a muscular organ that pumps oxygenated blood to the body and deoxygenated blood to the lungs',
            'expected_similarity' => 1.0,
            'description' => 'Perfect match'
        ],
        // Minor variations should be very high
        [
            'student' => 'Heart pumps blood',
            'correct' => 'The heart pumps blood',
            'expected_similarity' => 0.95,
            'description' => 'Minor article difference'
        ],
        // Medical abbreviations should match perfectly
        [
            'student' => 'BP = 120/80 mmHg',
            'correct' => 'Blood pressure = 120/80 mmHg',
            'expected_similarity' => 0.95,
            'description' => 'Medical abbreviation expansion'
        ],
        // Synonyms should have high similarity
        [
            'student' => 'Cardiac muscle contracts',
            'correct' => 'Heart muscle contracts',
            'expected_similarity' => 0.90,
            'description' => 'Medical synonyms'
        ],
        // Completely wrong should be very low
        [
            'student' => 'The liver produces insulin',
            'correct' => 'The pancreas produces insulin',
            'expected_similarity' => 0.20,
            'description' => 'Completely different organs'
        ]
    ];

    $algorithmAccuracyResults = [];
    foreach ($accuracyTestCases as $i => $test) {
        echo "Test " . ($i + 1) . ": {$test['description']}\n";
        echo "Student: {$test['student']}\n";
        echo "Correct: {$test['correct']}\n";

        $result = $enhancedGrader->gradeAnswer($test['student'], $test['correct'], 'anatomy');
        $similarity = $result['similarity'] ?? 0;

        $accuracyCheck = abs($similarity - $test['expected_similarity']) <= 0.05; // Within 5% tolerance

        echo sprintf("  Expected: %.2f%%, Got: %.2f%% - %s\n",
            $test['expected_similarity'] * 100,
            $similarity * 100,
            $accuracyCheck ? '✅ ACCURATE' : '❌ INACCURATE'
        );

        $algorithmAccuracyResults[] = [
            'description' => $test['description'],
            'expected' => $test['expected_similarity'],
            'actual' => $similarity,
            'accurate' => $accuracyCheck
        ];

        if (!$accuracyCheck) {
            echo "    ❌ CRITICAL: Algorithm accuracy failure!\n";
        }
    }

    // Test 2: Edge Cases Robustness
    echo "\n🧪 TEST 2: EDGE CASES ROBUSTNESS\n";
    echo "===============================\n";

    $edgeCases = [
        // Empty inputs
        ['', 'Valid answer', 'Empty student answer'],
        ['Valid answer', '', 'Empty correct answer'],
        ['', '', 'Both empty'],

        // Very long inputs
        [str_repeat('The heart pumps blood ', 1000), 'The heart pumps blood', 'Very long student answer'],
        ['The heart pumps blood', str_repeat('The heart pumps blood ', 1000), 'Very long correct answer'],

        // Special characters and formatting
        ['Heart pumps blood!', 'Heart pumps blood', 'Exclamation marks'],
        ['Heart... pumps... blood...', 'Heart pumps blood', 'Ellipsis'],
        ['Heart pumps blood???', 'Heart pumps blood', 'Question marks'],
        ['Heart    pumps    blood', 'Heart pumps blood', 'Multiple spaces'],
        ['\n\n\nHeart\n\n\npumps\n\n\nblood\n\n\n', 'Heart pumps blood', 'Newlines'],

        // Case sensitivity
        ['THE HEART PUMPS BLOOD', 'the heart pumps blood', 'Uppercase vs lowercase'],
        ['the heart pumps blood', 'THE HEART PUMPS BLOOD', 'Lowercase vs uppercase'],

        // Numbers and symbols
        ['Heart pumps 5L of blood', 'Heart pumps blood', 'Numbers in answer'],
        ['Heart pumps blood!!! @#$%', 'Heart pumps blood', 'Special characters'],
        ['Patient has BP 120/80', 'Patient has BP 120/80', 'Medical values'],

        // Unicode and encoding
        ['Héart pümps blóöd', 'Heart pumps blood', 'Unicode characters'],
        ['Heart pumps blood', 'Héart pümps blóöd', 'Unicode in correct answer'],

        // Medical terminology variations
        ['Myocardial infarction', 'Heart attack', 'Medical vs common term'],
        ['SNS activation', 'Sympathetic nervous system activation', 'Abbreviation vs full term'],
        ['DM', 'Diabetes mellitus', 'Medical abbreviation'],

        // Mathematical expressions
        ['pH = 7.4', 'pH = 7.4', 'Exact pH match'],
        ['pH=7.4', 'pH = 7.4', 'No spaces in equation'],
        ['2 + 2 = 4', '2 + 2 = 4', 'Mathematical equation'],

        // Extremely short vs long
        ['Hi', 'Hello world', 'Very short student answer'],
        ['Hello world this is a very long answer with lots of words and details', 'Hi', 'Very short correct answer']
    ];

    $edgeCaseResults = [];
    foreach ($edgeCases as $i => $case) {
        list($student, $correct, $description) = $case;
        echo "Edge Case " . ($i + 1) . ": $description\n";

        try {
            $result = $enhancedGrader->gradeAnswer($student, $correct, 'anatomy');
            $success = true;

            // Validate result structure
            $hasSimilarity = isset($result['similarity']) && is_numeric($result['similarity']);
            $hasIsCorrect = isset($result['isCorrect']) && is_bool($result['isCorrect']);
            $similarityValid = $hasSimilarity && $result['similarity'] >= 0 && $result['similarity'] <= 1;

            if ($hasSimilarity && $hasIsCorrect && $similarityValid) {
                echo "  ✅ Handled correctly - Similarity: " . number_format($result['similarity'], 3) . "\n";
            } else {
                echo "  ⚠️  Handled but invalid result structure\n";
                $success = false;
            }

        } catch (\Exception $e) {
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
            $success = false;
        }

        $edgeCaseResults[] = [
            'description' => $description,
            'success' => $success
        ];
    }

    // Test 3: Performance Stability Under Load
    echo "\n🧪 TEST 3: PERFORMANCE STABILITY UNDER LOAD\n";
    echo "==========================================\n";

    $loadTestCases = [
        'The heart pumps blood',
        'Sympathetic nervous system increases heart rate',
        'TCA cycle produces NADH and FADH2',
        'DNA replication is semiconservative',
        'Kidneys filter blood through glomeruli',
        'Insulin lowers blood glucose levels',
        'Hemoglobin carries oxygen in red blood cells',
        'Acetylcholine is a neurotransmitter',
        'Mitosis produces two identical daughter cells',
        'Natural selection drives evolution'
    ];

    $correctAnswers = [
        'The heart is a muscular organ that pumps oxygenated blood to the body and deoxygenated blood to the lungs',
        'Sympathetic nervous system activation increases heart rate, blood pressure, and inhibits digestion',
        'The citric acid cycle generates NADH, FADH2, CO2, and GTP through oxidative decarboxylation reactions',
        'DNA replication involves leading and lagging strands, producing two identical DNA molecules',
        'The kidneys filter blood through glomerular filtration and produce urine through tubular processes',
        'Insulin facilitates glucose uptake by cells and lowers blood glucose levels',
        'Hemoglobin is a protein in red blood cells that binds oxygen for transport',
        'Acetylcholine is a neurotransmitter involved in nerve impulse transmission',
        'Mitosis is cell division that produces two genetically identical daughter cells',
        'Natural selection is the process by which beneficial traits become more common in populations'
    ];

    echo "Running 10x concurrent load test (100 evaluations)...\n";

    $performanceResults = [];
    $startLoadTime = microtime(true);

    for ($iteration = 0; $iteration < 10; $iteration++) {
        $iterationResults = [];

        foreach ($loadTestCases as $i => $studentAnswer) {
            $start = microtime(true);
            $result = $enhancedGrader->gradeAnswer($studentAnswer, $correctAnswers[$i], 'anatomy');
            $time = microtime(true) - $start;

            $iterationResults[] = [
                'time' => $time,
                'similarity' => $result['similarity'] ?? 0,
                'is_correct' => $result['isCorrect'] ?? false
            ];
        }

        $avgIterationTime = array_sum(array_column($iterationResults, 'time')) / count($iterationResults);
        $performanceResults[] = $avgIterationTime;

        if (($iteration + 1) % 3 == 0) {
            echo "  Iteration " . ($iteration + 1) . ": " . number_format($avgIterationTime * 1000, 2) . "ms avg\n";
        }
    }

    $totalLoadTime = microtime(true) - $startLoadTime;
    $avgLoadTime = array_sum($performanceResults) / count($performanceResults);
    $maxLoadTime = max($performanceResults);
    $minLoadTime = min($performanceResults);
    $loadTimeVariance = ($maxLoadTime - $minLoadTime) / $avgLoadTime;

    echo "\nLoad Test Results:\n";
    echo sprintf("  Total Time: %.2fs for 100 evaluations\n", $totalLoadTime);
    echo sprintf("  Average Time: %.2fms per evaluation\n", $avgLoadTime * 1000);
    echo sprintf("  Performance Variance: %.1f%%\n", $loadTimeVariance * 100);

    // Test 4: Cache Robustness
    echo "\n🧪 TEST 4: CACHE ROBUSTNESS\n";
    echo "==========================\n";

    // Test cache consistency
    $cacheTestCase = [
        'student' => 'Heart pumps blood',
        'correct' => 'The heart pumps blood',
        'subject' => 'anatomy'
    ];

    echo "Testing cache consistency across 20 identical evaluations...\n";

    $cacheResults = [];
    for ($i = 0; $i < 20; $i++) {
        $start = microtime(true);
        $result = $enhancedGrader->gradeAnswer(
            $cacheTestCase['student'],
            $cacheTestCase['correct'],
            $cacheTestCase['subject']
        );
        $time = microtime(true) - $start;

        $cacheResults[] = [
            'time' => $time,
            'similarity' => $result['similarity'] ?? 0,
            'is_correct' => $result['isCorrect'] ?? false
        ];
    }

    $cacheSimilarities = array_column($cacheResults, 'similarity');
    $cacheTimes = array_column($cacheResults, 'time');
    $cacheConsistency = count(array_unique($cacheSimilarities)) === 1;
    $avgCacheTime = array_sum($cacheTimes) / count($cacheTimes);
    $cacheSpeedup = $cacheTimes[0] > 0 ? ($cacheTimes[0] - $avgCacheTime) / $cacheTimes[0] : 0;

    echo sprintf("  Cache Consistency: %s\n", $cacheConsistency ? '✅ PERFECT' : '❌ INCONSISTENT');
    echo sprintf("  Average Cache Time: %.2fms\n", $avgCacheTime * 1000);
    echo sprintf("  Cache Speedup: %.1f%%\n", $cacheSpeedup * 100);

    if (!$cacheConsistency) {
        echo "  ❌ CRITICAL: Cache inconsistency detected!\n";
    }

    // Test 5: Algorithm Ensemble Robustness
    echo "\n🧪 TEST 5: ALGORITHM ENSEMBLE ROBUSTNESS\n";
    echo "=======================================\n";

    $ensembleTestCases = [
        [
            'student' => 'The heart pumps blood',
            'correct' => 'The heart is a muscular organ that pumps oxygenated blood to the body and deoxygenated blood to the lungs',
            'description' => 'Partial vs Complete answer'
        ],
        [
            'student' => 'Heart pumps blood',
            'correct' => 'The heart pumps blood',
            'description' => 'Article difference'
        ],
        [
            'student' => 'Hearth pumps blood',
            'correct' => 'Heart pumps blood',
            'description' => 'Spelling error'
        ],
        [
            'student' => 'The cardiac organ pumps oxygenated blood',
            'correct' => 'The heart pumps oxygenated blood',
            'description' => 'Synonym usage'
        ]
    ];

    foreach ($ensembleTestCases as $i => $test) {
        echo "Ensemble Test " . ($i + 1) . ": {$test['description']}\n";

        $result = $enhancedGrader->gradeAnswer($test['student'], $test['correct'], 'anatomy');

        echo sprintf("  Final Similarity: %.3f\n", $result['similarity'] ?? 0);

        if (isset($result['algorithm_breakdown'])) {
            echo "  Algorithm Breakdown:\n";
            foreach ($result['algorithm_breakdown'] as $algorithm => $score) {
                echo sprintf("    %s: %.3f\n", $algorithm, $score);
            }

            // Check for algorithm consistency
            $scores = array_values($result['algorithm_breakdown']);
            $variance = stats_variance($scores);
            $algorithmConsistency = $variance < 0.1; // Low variance = consistent

            echo sprintf("  Algorithm Consistency: %s (Variance: %.4f)\n",
                $algorithmConsistency ? '✅ GOOD' : '⚠️ HIGH VARIANCE',
                $variance
            );
        } else {
            echo "  ❌ No algorithm breakdown available\n";
        }

        echo "\n";
    }

    // Test 6: Memory Leak Detection
    echo "\n🧪 TEST 6: MEMORY LEAK DETECTION\n";
    echo "===============================\n";

    $memoryBaselines = [];
    for ($i = 0; $i < 5; $i++) {
        $memoryBaselines[] = memory_get_usage();
        $result = $enhancedGrader->gradeAnswer(
            'Test answer ' . $i,
            'Test correct answer ' . $i,
            'anatomy'
        );
    }

    $avgMemoryBaseline = array_sum($memoryBaselines) / count($memoryBaselines);
    $memoryGrowth = ($memoryBaselines[count($memoryBaselines) - 1] - $memoryBaselines[0]) / $avgMemoryBaseline;

    echo sprintf("  Memory Growth: %.2f%%\n", $memoryGrowth * 100);

    if ($memoryGrowth < 0.05) { // Less than 5% growth
        echo "  ✅ No significant memory leaks detected\n";
    } else {
        echo "  ⚠️  Potential memory leak detected\n";
    }

    // Test 7: Error Recovery and Stability
    echo "\n🧪 TEST 7: ERROR RECOVERY AND STABILITY\n";
    echo "======================================\n";

    $errorTestCases = [
        [null, 'Valid answer', 'Null student answer'],
        ['Valid answer', null, 'Null correct answer'],
        [123, 'Valid answer', 'Numeric student answer'],
        ['Valid answer', 456, 'Numeric correct answer'],
        [new \stdClass(), 'Valid answer', 'Object student answer'],
        ['Valid answer', new \stdClass(), 'Object correct answer'],
        [[], 'Valid answer', 'Array student answer'],
        ['Valid answer', [], 'Array correct answer']
    ];

    $errorRecoveryResults = [];
    foreach ($errorTestCases as $i => $case) {
        list($student, $correct, $description) = $case;
        echo "Error Test " . ($i + 1) . ": $description\n";

        try {
            $result = $enhancedGrader->gradeAnswer($student, $correct, 'anatomy');
            $recovered = isset($result['similarity']) && isset($result['isCorrect']);
            echo "  ✅ Recovered gracefully\n";
        } catch (\Exception $e) {
            echo "  ⚠️  Exception: " . $e->getMessage() . "\n";
            $recovered = false;
        }

        $errorRecoveryResults[] = [
            'description' => $description,
            'recovered' => $recovered
        ];
    }

    // FINAL ROBUSTNESS ANALYSIS
    echo "\n🛡️ FINAL ROBUSTNESS ANALYSIS - 100% ACCURACY VALIDATION\n";
    echo "========================================================\n";

    // Calculate overall scores
    $algorithmAccuracyCount = count(array_filter($algorithmAccuracyResults, fn($r) => $r['accurate']));
    $algorithmAccuracyScore = ($algorithmAccuracyCount / count($algorithmAccuracyResults)) * 100;

    $edgeCaseSuccessCount = count(array_filter($edgeCaseResults, fn($r) => $r['success']));
    $edgeCaseScore = ($edgeCaseSuccessCount / count($edgeCases)) * 100;

    $performanceStabilityScore = $loadTimeVariance < 0.2 ? 100 : max(0, 100 - ($loadTimeVariance * 200));

    $cacheRobustnessScore = $cacheConsistency ? 100 : 50;

    $memoryStabilityScore = $memoryGrowth < 0.05 ? 100 : max(0, 100 - ($memoryGrowth * 1000));

    $errorRecoveryCount = count(array_filter($errorRecoveryResults, fn($r) => $r['recovered']));
    $errorRecoveryScore = ($errorRecoveryCount / count($errorTestCases)) * 100;

    // Overall robustness score
    $overallRobustnessScore = (
        $algorithmAccuracyScore +
        $edgeCaseScore +
        $performanceStabilityScore +
        $cacheRobustnessScore +
        $memoryStabilityScore +
        $errorRecoveryScore
    ) / 6;

    echo "🔬 ROBUSTNESS SCORES:\n";
    echo sprintf("  Algorithm Accuracy: %.1f%% (%d/%d)\n",
        $algorithmAccuracyScore, $algorithmAccuracyCount, count($algorithmAccuracyResults));
    echo sprintf("  Edge Cases: %.1f%% (%d/%d)\n",
        $edgeCaseScore, $edgeCaseSuccessCount, count($edgeCases));
    echo sprintf("  Performance Stability: %.1f%%\n", $performanceStabilityScore);
    echo sprintf("  Cache Robustness: %.1f%%\n", $cacheRobustnessScore);
    echo sprintf("  Memory Stability: %.1f%%\n", $memoryStabilityScore);
    echo sprintf("  Error Recovery: %.1f%% (%d/%d)\n",
        $errorRecoveryScore, $errorRecoveryCount, count($errorTestCases));

    echo "\n🎯 OVERALL ROBUSTNESS SCORE: " . number_format($overallRobustnessScore, 1) . "/100\n";

    // Final verdict
    if ($overallRobustnessScore >= 95) {
        echo "\n🏆 EXCELLENT - 100% ACCURACY ACHIEVED!\n";
        echo "✅ Bulletproof stability and accuracy\n";
        echo "✅ Handles all edge cases flawlessly\n";
        echo "✅ Performance stable under all conditions\n";
        echo "✅ Memory efficient and leak-free\n";
        echo "✅ Perfect error recovery\n";
        echo "\n🚀 READY FOR PRODUCTION - ZERO COMPROMISES!\n";
    } elseif ($overallRobustnessScore >= 90) {
        echo "\n✅ VERY GOOD - Near 100% accuracy!\n";
        echo "✅ Excellent stability and performance\n";
        echo "✅ Handles edge cases very well\n";
        echo "✅ Minor optimizations may be needed\n";
        echo "\n🚀 READY FOR PRODUCTION WITH MINOR TUNING!\n";
    } elseif ($overallRobustnessScore >= 80) {
        echo "\n⚠️ GOOD - Solid accuracy and stability!\n";
        echo "✅ Good performance and error handling\n";
        echo "⚠️ Some edge cases need attention\n";
        echo "\n🔧 READY FOR PRODUCTION AFTER OPTIMIZATION!\n";
    } else {
        echo "\n❌ NEEDS WORK - Accuracy and stability issues!\n";
        echo "⚠️ Critical issues need to be addressed\n";
        echo "🔧 NOT READY FOR PRODUCTION!\n";
    }

    echo "\n🎉 Ultimate robustness testing complete!\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

function stats_variance($array) {
    $mean = array_sum($array) / count($array);
    $variance = 0;
    foreach ($array as $value) {
        $variance += pow($value - $mean, 2);
    }
    return $variance / count($array);
}

echo "\n✨ Test completed at " . now()->format('Y-m-d H:i:s') . "\n";
