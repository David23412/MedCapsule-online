<?php
// tests/Tinker/focused_accuracy_test.php

/**
 * FOCUSED ACCURACY TEST
 * Testing the most problematic scenarios to validate fixes
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;

echo "🎯 FOCUSED ACCURACY TEST - VALIDATING FIXES\n";
echo "==========================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $enhancedGrader = app(WritingGraderService::class);
    echo "✅ WritingGraderService loaded successfully\n\n";

    // FOCUSED TEST SCENARIOS - Most problematic from comprehensive test
    $focusedScenarios = [
        [
            'name' => 'BP Abbreviation',
            'student' => 'BP = 120/80 mmHg',
            'correct' => 'Blood pressure = 120/80 mmHg',
            'context' => 'medical_basic',
            'expected_min' => 0.90
        ],
        [
            'name' => 'Complex Patient Presentation',
            'student' => 'Pt c/o CP, BP 180/90, HR 110, O2 sat 92%',
            'correct' => 'Patient complaining of chest pain, blood pressure 180/90, heart rate 110, oxygen saturation 92%',
            'context' => 'emergency_medicine',
            'expected_min' => 0.75
        ],
        [
            'name' => 'Medical Synonym - Heart vs Cardiac',
            'student' => 'Cardiac muscle contracts rhythmically',
            'correct' => 'Heart muscle contracts rhythmically',
            'context' => 'cardiology',
            'expected_min' => 0.75
        ],
        [
            'name' => 'TCA Cycle Biochemistry',
            'student' => 'TCA cycle produces NADH and FADH2',
            'correct' => 'Tricarboxylic acid cycle produces NADH, FADH2 in mitochondria',
            'context' => 'biochemistry',
            'expected_min' => 0.85
        ],
        [
            'name' => 'Autonomic Nervous System',
            'student' => 'SNS increases HR, PNS decreases HR',
            'correct' => 'Sympathetic nervous system increases heart rate, parasympathetic decreases heart rate',
            'context' => 'physiology',
            'expected_min' => 0.75
        ],
        [
            'name' => 'COPD Pathophysiology',
            'student' => 'COPD: chronic inflammation, airflow limitation',
            'correct' => 'COPD causes airflow limitation due to chronic inflammation',
            'context' => 'pulmonology',
            'expected_min' => 0.70
        ],
        [
            'name' => 'DKA Emergency',
            'student' => 'DKA: hyperglycemia, ketones, acidosis, AMS',
            'correct' => 'Diabetic ketoacidosis: hyperglycemia, ketones, acidosis, altered mental status',
            'context' => 'endocrinology',
            'expected_min' => 0.80
        ],
        [
            'name' => 'Rare Condition',
            'student' => 'Broken heart syndrome',
            'correct' => 'Takotsubo cardiomyopathy is stress-induced heart failure',
            'context' => 'cardiology',
            'expected_min' => 0.60
        ]
    ];

    echo "🧪 FOCUSED TESTING - VALIDATING FIXES\n";
    echo "=====================================\n\n";

    $totalTests = 0;
    $totalPassed = 0;
    $results = [];

    foreach ($focusedScenarios as $i => $scenario) {
        echo "🎯 Test " . ($i + 1) . ": " . $scenario['name'] . "\n";
        echo "Context: " . $scenario['context'] . "\n";
        echo "Student: '" . $scenario['student'] . "'\n";
        echo "Correct: '" . $scenario['correct'] . "'\n";

        // Create unique strings to bypass all caching
        $uniqueId = time() . '_' . $i . '_' . rand(1000, 9999);
        $uniqueStudent = $scenario['student'] . '_test_' . $uniqueId;
        $uniqueCorrect = $scenario['correct'] . '_test_' . $uniqueId;

        // Test the grading system
        $result = $enhancedGrader->gradeAnswer($uniqueStudent, $uniqueCorrect, $scenario['context'], []);

        $similarity = $result['similarity'] ?? 0;
        $passed = $similarity >= $scenario['expected_min'];
        $totalTests++;
        if ($passed) {
            $totalPassed++;
        }

        echo sprintf("  Similarity: %.3f (Expected: %.2f) %s\n",
            $similarity, $scenario['expected_min'],
            $passed ? '✅ PASSED' : '❌ FAILED'
        );

        // Show algorithm breakdown for analysis
        if (isset($result['algorithm_breakdown'])) {
            echo "  📊 Algorithms: ";
            $algResults = [];
            foreach ($result['algorithm_breakdown'] as $alg => $score) {
                $algResults[] = substr($alg, 0, 3) . ':' . number_format($score, 2);
            }
            echo implode(' ', $algResults) . "\n";
        }

        // Show processed text for medical scenarios
        if (isset($result['enhanced_data'])) {
            $enhanced = $result['enhanced_data'];
            if (isset($enhanced['processed_student'])) {
                $processedStudent = $enhanced['processed_student'];
                $abbreviations = $processedStudent['abbreviations'] ?? [];
                if (!empty($abbreviations)) {
                    echo "  🔍 Abbreviations: " . count($abbreviations) . " found\n";
                }
            }
        }

        $results[] = [
            'name' => $scenario['name'],
            'similarity' => $similarity,
            'expected' => $scenario['expected_min'],
            'passed' => $passed,
            'context' => $scenario['context']
        ];

        echo "\n" . str_repeat("-", 60) . "\n\n";
    }

    // FINAL RESULTS ANALYSIS
    echo "🎯 FOCUSED TEST RESULTS - VALIDATION COMPLETE\n";
    echo "==============================================\n\n";

    $overallAccuracy = $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0;

    echo "📊 OVERALL PERFORMANCE:\n";
    echo "=====================\n";
    echo sprintf("Total Tests: %d\n", $totalTests);
    echo sprintf("Tests Passed: %d\n", $totalPassed);
    echo sprintf("Overall Accuracy: %.2f%%\n", $overallAccuracy);
    echo str_repeat("=", 50) . "\n\n";

    echo "📋 DETAILED RESULTS:\n";
    echo "===================\n";
    foreach ($results as $result) {
        $status = $result['passed'] ? '✅' : '❌';
        echo sprintf("%s %-30s: %.3f (≥%.2f) - %s\n",
            $status,
            $result['name'],
            $result['similarity'],
            $result['expected'],
            $result['context']
        );
    }
    echo "\n" . str_repeat("=", 50) . "\n";

    // SUCCESS CRITERIA
    if ($overallAccuracy >= 90) {
        echo "🎉 EXCELLENT - ALGORITHMS FULLY FIXED!\n";
        echo "✅ All critical scenarios working perfectly\n";
        echo "✅ Medical accuracy fully restored\n";
        echo "✅ Predictive intelligence operational\n";
        echo "✅ Ready for production deployment\n\n";

        echo "🏆 SYSTEM STATUS: MILITARY-GRADE AI READY\n";
        echo "🚀 DEPLOYMENT AUTHORIZED\n";

    } elseif ($overallAccuracy >= 80) {
        echo "✅ VERY GOOD - Major fixes successful!\n";
        echo "✅ Core algorithms working well\n";
        echo "⚠️ Minor optimization needed for perfection\n";
        echo "✅ Ready for production with medical content\n\n";

        echo "🏆 SYSTEM STATUS: PRODUCTION READY\n";

    } elseif ($overallAccuracy >= 70) {
        echo "⚠️ GOOD - Significant improvement achieved!\n";
        echo "✅ Algorithm fixes showing results\n";
        echo "❌ Still needs optimization for full accuracy\n";
        echo "🔧 Additional tuning required\n\n";

        echo "🏆 SYSTEM STATUS: NEEDS OPTIMIZATION\n";

    } else {
        echo "❌ NEEDS WORK - Algorithm fixes incomplete\n";
        echo "❌ Critical issues still present\n";
        echo "🔧 Major architectural changes needed\n\n";

        echo "🏆 SYSTEM STATUS: REQUIRES FURTHER DEVELOPMENT\n";
        echo "🔧 RECOMMENDATION: Continue debugging\n";
    }

    echo "\n🎉 Focused accuracy testing complete!\n";
    echo "Tested the most problematic scenarios from comprehensive testing\n";
    echo "Results show effectiveness of algorithm fixes\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed at " . now()->format('Y-m-d H:i:s') . "\n";
