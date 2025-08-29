<?php
// tests/Tinker/medical_intensive_test.php

/**
 * MEDICAL INTENSIVE TEST - Fix the Critical Medical Issues
 *
 * Specifically targets the failing medical cases:
 * - Medical symptoms: 11.65% (target 85%)
 * - General content: 24.13% (target 80%)
 * - Medical abbreviations expansion issues
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;
use App\Services\WrittenAnswerEvaluationService;

echo "🏥 MEDICAL INTENSIVE TEST - FIXING CRITICAL ISSUES\n";
echo "=================================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    // Initialize services
    $writingGrader = app(WritingGraderService::class);
    $existingService = app(WrittenAnswerEvaluationService::class);

    if (!$writingGrader || !$existingService) {
        throw new Exception("Core services not available");
    }

    echo "✅ All services loaded successfully\n\n";

    // PHASE 1: TEST MEDICAL ABBREVIATION EXPANSION DIRECTLY
    echo "🔧 PHASE 1: TESTING MEDICAL ABBREVIATION EXPANSION\n";
    echo "=================================================\n";

    $medicalAbbreviationTests = [
        [
            'input' => 'BP = 120/80 mmHg',
            'expected_contains' => ['blood', 'pressure'],
            'expansion_map' => ['BP' => 'blood pressure'],
            'description' => 'Basic BP expansion'
        ],
        [
            'input' => 'Pt c/o CP, SOB',
            'expected_contains' => ['patient', 'complaining', 'chest', 'pain', 'shortness', 'breath'],
            'expansion_map' => ['Pt' => 'patient', 'c/o' => 'complaining of', 'CP' => 'chest pain', 'SOB' => 'shortness of breath'],
            'description' => 'Patient symptoms'
        ],
        [
            'input' => 'DM is hyperglycemia',
            'expected_contains' => ['diabetes', 'mellitus'],
            'expansion_map' => ['DM' => 'diabetes mellitus'],
            'description' => 'DM expansion'
        ],
        [
            'input' => 'MI causes chest pain',
            'expected_contains' => ['myocardial', 'infarction'],
            'expansion_map' => ['MI' => 'myocardial infarction'],
            'description' => 'MI expansion'
        ],
        [
            'input' => 'ASA inhibits COX-1/2',
            'expected_contains' => ['aspirin', 'cyclooxygenase'],
            'expansion_map' => ['ASA' => 'aspirin', 'COX-1/2' => 'cyclooxygenase 1 and 2'],
            'description' => 'Medication abbreviations'
        ],
        [
            'input' => 'RA, RV, LA, LV',
            'expected_contains' => ['right', 'atrium', 'ventricle', 'left'],
            'expansion_map' => ['RA' => 'right atrium', 'RV' => 'right ventricle', 'LA' => 'left atrium', 'LV' => 'left ventricle'],
            'description' => 'Anatomy abbreviations'
        ],
    ];

    foreach ($medicalAbbreviationTests as $test) {
        // Test the abbreviation expansion by comparing abbreviated vs expanded text
        $abbreviatedText = $test['input'];
        $expandedText = $abbreviatedText;

        // Apply the expansion mapping
        foreach ($test['expansion_map'] as $abbreviation => $expansion) {
            $expandedText = str_replace($abbreviation, $expansion, $expandedText);
        }

        $result = $writingGrader->gradeAnswer($abbreviatedText, $expandedText, 'medical');

        echo "Test: {$test['description']}\n";
        echo "Abbreviated: '{$abbreviatedText}'\n";
        echo "Expanded: '{$expandedText}'\n";
        echo "Similarity Score: " . number_format($result['similarity'] * 100, 2) . "%\n";

        // Check if the score indicates proper abbreviation understanding
        $score = $result['similarity'] * 100;
        if ($score >= 95) { // High similarity indicates abbreviations were properly expanded
            echo "✅ PASS - Abbreviations properly expanded and matched\n";
        } elseif ($score >= 70) {
            echo "⚠️ PARTIAL - Some abbreviation expansion detected\n";
        } else {
            echo "❌ FAIL - Abbreviations not properly expanded\n";
        }

        // Also test if the processed text contains expanded terms
        if (isset($result['processed_student'])) {
            $processedText = $result['processed_student'];
            $missing = [];
            foreach ($test['expected_contains'] as $expected) {
                if (stripos($processedText, $expected) === false) {
                    $missing[] = $expected;
                }
            }

            if (empty($missing)) {
                echo "✅ VERIFIED - All expected terms found in processed text\n";
            } else {
                echo "❌ VERIFICATION FAILED - Missing in processed text: " . implode(', ', $missing) . "\n";
            }
        }

        echo "\n";
    }

    // PHASE 2: TEST SPECIFIC FAILING CASES
    echo "🎯 PHASE 2: TESTING SPECIFIC FAILING CASES\n";
    echo "==========================================\n";

    $failingCases = [
        [
            'student' => 'Pt c/o CP, SOB',
            'correct' => 'Patient complaining of chest pain, shortness of breath',
            'subject' => 'medical',
            'target' => 85,
            'issue' => 'Medical symptoms abbreviations'
        ],
        [
            'student' => 'Photosynthesis makes food',
            'correct' => 'Photosynthesis produces glucose',
            'subject' => 'general',
            'target' => 80,
            'issue' => 'General content semantic understanding'
        ],
        [
            'student' => 'ASA inhibits COX-1/2',
            'correct' => 'Aspirin inhibits cyclooxygenase 1 and 2',
            'subject' => 'medical',
            'target' => 85,
            'issue' => 'Medical pharmacology abbreviations'
        ],
        [
            'student' => 'RA, RV, LA, LV',
            'correct' => 'Right atrium, right ventricle, left atrium, left ventricle',
            'subject' => 'medical',
            'target' => 90,
            'issue' => 'Medical anatomy abbreviations'
        ],
        [
            'student' => 'Heart pumps blood',
            'correct' => 'Heart circulates blood',
            'subject' => 'general',
            'target' => 85,
            'issue' => 'General biology semantic matching'
        ],
    ];

    $improvements = [];

    foreach ($failingCases as $i => $test) {
        echo "Test " . ($i + 1) . ": {$test['issue']}\n";
        echo "Student: '{$test['student']}'\n";
        echo "Correct: '{$test['correct']}'\n";

        $startTime = microtime(true);
        $result = $writingGrader->gradeAnswer($test['student'], $test['correct'], $test['subject']);
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);

        $score = $result['similarity'] * 100;
        $passed = $score >= $test['target'];
        $status = $passed ? "✅ PASS" : "❌ FAIL";

        echo "Result: {$status} - {$score}% (target: {$test['target']}%) - {$processingTime}ms\n";

        // Compare with existing system
        $existingResult = $existingService->evaluateAnswer($test['student'], $test['correct']);
        $existingScore = $existingResult['similarity'] * 100;

        $improvement = $score - $existingScore;
        $improvementStatus = $improvement > 0 ? "📈 IMPROVED" : ($improvement < 0 ? "📉 WORSE" : "➡️ SAME");

        echo "Comparison: Enhanced {$score}% vs Existing {$existingScore}% ({$improvementStatus} by " . abs($improvement) . "%)\n";

        $improvements[] = [
            'test' => $test['issue'],
            'enhanced_score' => $score,
            'existing_score' => $existingScore,
            'improvement' => $improvement,
            'passed' => $passed
        ];

        echo "\n";
    }

    // PHASE 3: ALGORITHM ANALYSIS
    echo "🔍 PHASE 3: ALGORITHM PERFORMANCE ANALYSIS\n";
    echo "============================================\n";

    $algorithmTests = [
        ['student' => 'BP = 120/80 mmHg', 'correct' => 'Blood pressure = 120/80 mmHg', 'description' => 'Perfect medical abbreviation match'],
        ['student' => 'Pt c/o CP, SOB', 'correct' => 'Patient complaining of chest pain, shortness of breath', 'description' => 'Complex medical abbreviation expansion'],
        ['student' => 'Photosynthesis makes food', 'correct' => 'Photosynthesis produces glucose', 'description' => 'Semantic synonym matching'],
        ['student' => 'Heart pumps blood', 'correct' => 'Heart circulates blood', 'description' => 'Contextual word matching'],
    ];

    foreach ($algorithmTests as $test) {
        echo "Analyzing: {$test['description']}\n";
        echo "Student: '{$test['student']}'\n";
        echo "Correct: '{$test['correct']}'\n";

        $result = $writingGrader->gradeAnswer($test['student'], $test['correct'], 'medical');

        if (isset($result['algorithm_breakdown'])) {
            echo "Algorithm Scores:\n";
            foreach ($result['algorithm_breakdown'] as $algorithm => $score) {
                $percentage = number_format($score * 100, 2);
                echo "  {$algorithm}: {$percentage}%\n";
            }
        }

        echo "Final Score: " . number_format($result['similarity'] * 100, 2) . "%\n";
        echo "Confidence: " . (isset($result['confidence']) ? number_format($result['confidence'] * 100, 2) . "%" : "N/A") . "\n";

        echo "\n";
    }

    // PHASE 4: IMPROVEMENT SUMMARY
    echo "📊 PHASE 4: IMPROVEMENT SUMMARY & NEXT STEPS\n";
    echo "==============================================\n";

    $totalTests = count($improvements);
    $passedTests = count(array_filter($improvements, fn($i) => $i['passed']));
    $positiveImprovements = count(array_filter($improvements, fn($i) => $i['improvement'] > 0));
    $averageImprovement = array_sum(array_column($improvements, 'improvement')) / $totalTests;

    echo "🎯 OVERALL PERFORMANCE:\n";
    echo "Tests Passed: {$passedTests}/{$totalTests} (" . number_format(($passedTests/$totalTests)*100, 1) . "%)\n";
    echo "Positive Improvements: {$positiveImprovements}/{$totalTests} (" . number_format(($positiveImprovements/$totalTests)*100, 1) . "%)\n";
    echo "Average Improvement: " . number_format($averageImprovement, 2) . "% vs existing system\n";

    echo "\n📋 DETAILED RESULTS:\n";
    foreach ($improvements as $improvement) {
        $status = $improvement['passed'] ? "✅" : "❌";
        $improvementIcon = $improvement['improvement'] > 0 ? "📈" : ($improvement['improvement'] < 0 ? "📉" : "➡️");
        echo "{$status} {$improvementIcon} {$improvement['test']}: {$improvement['enhanced_score']}% vs {$improvement['existing_score']}% (Δ" . number_format($improvement['improvement'], 2) . "%)\n";
    }

    echo "\n🎯 CRITICAL ISSUES IDENTIFIED:\n";

    $criticalIssues = [];

    // Check if medical abbreviations are working
    $medicalAbbrTest = $writingGrader->gradeAnswer('Pt c/o CP, SOB', 'Patient complaining of chest pain, shortness of breath', 'medical');
    if (($medicalAbbrTest['similarity'] ?? 0) < 0.7) {
        $criticalIssues[] = "Medical abbreviation expansion not working properly";
    }

    // Check semantic understanding
    $semanticTest = $writingGrader->gradeAnswer('Photosynthesis makes food', 'Photosynthesis produces glucose', 'general');
    if (($semanticTest['similarity'] ?? 0) < 0.6) {
        $criticalIssues[] = "Semantic synonym matching needs improvement";
    }

    if (empty($criticalIssues)) {
        echo "✅ No critical issues detected - system performing well!\n";
    } else {
        foreach ($criticalIssues as $issue) {
            echo "❌ {$issue}\n";
        }
    }

    echo "\n🚀 RECOMMENDED NEXT STEPS:\n";

    if ($averageImprovement < 0) {
        echo "1. 🔧 URGENT: Enhanced system performing worse than existing - debug algorithm weights\n";
        echo "2. 🔍 Analyze why ensemble algorithm is reducing accuracy\n";
        echo "3. 🎯 Adjust medical algorithm boosting to prioritize accuracy over complexity\n";
    } elseif ($passedTests < $totalTests * 0.8) {
        echo "1. 🏥 Focus on medical abbreviation expansion improvement\n";
        echo "2. 📚 Enhance semantic understanding for general content\n";
        echo "3. ⚖️ Fine-tune algorithm ensemble weights\n";
    } else {
        echo "1. 🎉 Great progress! Run full master plan validation to confirm\n";
        echo "2. 📈 Consider adding more medical abbreviations to the database\n";
        echo "3. 🔬 Test edge cases and unusual medical terminology\n";
    }

    echo "\n🏆 FINAL ASSESSMENT:\n";
    if ($passedTests >= $totalTests * 0.8 && $averageImprovement >= 0) {
        echo "✅ EXCELLENT! Medical issues largely resolved. Ready for production!\n";
    } elseif ($passedTests >= $totalTests * 0.6) {
        echo "📈 GOOD! Significant improvement achieved. Minor tuning needed.\n";
    } else {
        echo "⚠️ NEEDS WORK! Major issues remain. Comprehensive debugging required.\n";
    }

    echo "\n🎉 MEDICAL INTENSIVE TEST COMPLETED!\n";
    echo "===================================\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
