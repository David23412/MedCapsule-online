<?php
// tests/Tinker/rigorous_scenario_testing.php

/**
 * RIGOROUS SCENARIO TESTING - WRITTEN GRADING SYSTEM
 *
 * Comprehensive testing of context understanding, edge cases, and accuracy
 * Compares performance against previous implementation
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;
use App\Services\WrittenAnswerEvaluationService;

echo "🧪 RIGOROUS SCENARIO TESTING - WRITTEN GRADING SYSTEM\n";
echo "======================================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    // Get services
    $writingGrader = app(WritingGraderService::class);
    $existingService = app(WrittenAnswerEvaluationService::class);

    if (!$writingGrader || !$existingService) {
        throw new Exception("Services not available");
    }

    echo "✅ Services loaded successfully\n\n";

    // Define comprehensive test scenarios
    $testScenarios = [
        // MEDICAL SCENARIOS
        [
            'category' => 'Medical - Abbreviations',
            'question' => 'What is the normal blood pressure range?',
            'submitted' => 'BP = 120/80 mmHg',
            'alternative' => 'Blood pressure = 120/80 mmHg',
            'subject' => 'medical',
            'expected_score' => 0.95,
            'difficulty' => 'Easy'
        ],
        [
            'category' => 'Medical - Symptoms',
            'question' => 'Describe the symptoms of myocardial infarction',
            'submitted' => 'Pt c/o CP, SOB, diaphoresis',
            'alternative' => 'Patient complaining of chest pain, shortness of breath, sweating',
            'subject' => 'medical',
            'expected_score' => 0.90,
            'difficulty' => 'Medium'
        ],
        [
            'category' => 'Medical - Treatment',
            'question' => 'How is hypertension treated?',
            'submitted' => 'Lifestyle modifications, ACE inhibitors, beta blockers',
            'alternative' => 'Diet and exercise changes, angiotensin converting enzyme inhibitors, beta adrenergic blockers',
            'subject' => 'medical',
            'expected_score' => 0.85,
            'difficulty' => 'Medium'
        ],
        [
            'category' => 'Medical - Anatomy',
            'question' => 'Describe the heart structure',
            'submitted' => 'The heart has 4 chambers: RA, RV, LA, LV',
            'alternative' => 'The heart has four chambers: right atrium, right ventricle, left atrium, left ventricle',
            'subject' => 'medical',
            'expected_score' => 0.92,
            'difficulty' => 'Easy'
        ],
        [
            'category' => 'Medical - Pathophysiology',
            'question' => 'Explain diabetes mellitus',
            'submitted' => 'DM is a metabolic disorder characterized by hyperglycemia due to insulin deficiency or resistance',
            'alternative' => 'Diabetes mellitus is a metabolic disease characterized by high blood sugar due to lack of insulin or insulin resistance',
            'subject' => 'medical',
            'expected_score' => 0.88,
            'difficulty' => 'Medium'
        ],

        // GENERAL EDUCATION SCENARIOS
        [
            'category' => 'Science - Basic',
            'question' => 'What is photosynthesis?',
            'submitted' => 'Photosynthesis is the process by which plants make food using sunlight',
            'alternative' => 'Photosynthesis is the process where plants produce glucose using light energy',
            'subject' => 'general',
            'expected_score' => 0.90,
            'difficulty' => 'Easy'
        ],
        [
            'category' => 'History - Basic',
            'question' => 'When did World War II end?',
            'submitted' => 'WWII ended in 1945',
            'alternative' => 'World War Two ended in 1945',
            'subject' => 'general',
            'expected_score' => 0.95,
            'difficulty' => 'Easy'
        ],
        [
            'category' => 'Mathematics - Basic',
            'question' => 'What is 2 + 2?',
            'submitted' => '2 + 2 = 4',
            'alternative' => 'Two plus two equals four',
            'subject' => 'general',
            'expected_score' => 1.00,
            'difficulty' => 'Easy'
        ],

        // EDGE CASES
        [
            'category' => 'Edge Case - Typos',
            'question' => 'What is the capital of France?',
            'submitted' => 'The cpital of France is Paris',
            'alternative' => 'The capital of France is Paris',
            'subject' => 'general',
            'expected_score' => 0.95,
            'difficulty' => 'Easy'
        ],
        [
            'category' => 'Edge Case - Extra Words',
            'question' => 'What is water?',
            'submitted' => 'Water is a chemical compound that consists of two hydrogen atoms and one oxygen atom',
            'alternative' => 'Water consists of two hydrogen atoms and one oxygen atom',
            'subject' => 'general',
            'expected_score' => 0.90,
            'difficulty' => 'Easy'
        ],
        [
            'category' => 'Edge Case - Missing Words',
            'question' => 'Describe the water cycle',
            'submitted' => 'Evaporation, condensation, precipitation',
            'alternative' => 'The water cycle involves evaporation, condensation, and precipitation',
            'subject' => 'general',
            'expected_score' => 0.85,
            'difficulty' => 'Medium'
        ],
        [
            'category' => 'Edge Case - Synonyms',
            'question' => 'What is a doctor?',
            'submitted' => 'A physician who treats patients',
            'alternative' => 'A doctor who treats patients',
            'subject' => 'general',
            'expected_score' => 0.95,
            'difficulty' => 'Easy'
        ],
        [
            'category' => 'Edge Case - Different Structure',
            'question' => 'Explain gravity',
            'submitted' => 'Gravity pulls objects toward the center of the Earth',
            'alternative' => 'Objects are pulled toward Earth\'s center due to gravity',
            'subject' => 'general',
            'expected_score' => 0.85,
            'difficulty' => 'Medium'
        ],

        // COMPLEX MEDICAL SCENARIOS
        [
            'category' => 'Complex Medical - Differential Diagnosis',
            'question' => 'What conditions should be considered in a patient with chest pain?',
            'submitted' => 'MI, PE, pneumothorax, aortic dissection, pericarditis, GERD, musculoskeletal pain',
            'alternative' => 'Myocardial infarction, pulmonary embolism, pneumothorax, aortic dissection, pericarditis, gastroesophageal reflux disease, musculoskeletal chest pain',
            'subject' => 'medical',
            'expected_score' => 0.88,
            'difficulty' => 'Hard'
        ],
        [
            'category' => 'Complex Medical - Pharmacology',
            'question' => 'Describe the mechanism of aspirin',
            'submitted' => 'ASA irreversibly inhibits COX-1 and COX-2, preventing thromboxane A2 synthesis and platelet aggregation',
            'alternative' => 'Aspirin irreversibly inhibits cyclooxygenase 1 and 2 enzymes, preventing thromboxane A2 synthesis and platelet aggregation',
            'subject' => 'medical',
            'expected_score' => 0.85,
            'difficulty' => 'Hard'
        ],

        // CONTEXT UNDERSTANDING
        [
            'category' => 'Context - Implied Meaning',
            'question' => 'Why do we need sleep?',
            'submitted' => 'Sleep is important for brain function and memory consolidation',
            'alternative' => 'Sleep helps the brain function and consolidate memories',
            'subject' => 'general',
            'expected_score' => 0.90,
            'difficulty' => 'Medium'
        ],
        [
            'category' => 'Context - Technical vs Simple',
            'question' => 'What causes infection?',
            'submitted' => 'Infection is caused by pathogenic microorganisms invading host tissues',
            'alternative' => 'Germs cause infections when they enter the body',
            'subject' => 'medical',
            'expected_score' => 0.80,
            'difficulty' => 'Medium'
        ]
    ];

    $results = [];
    $totalTests = count($testScenarios);
    $enhancedPassed = 0;
    $existingPassed = 0;
    $improvements = 0;

    echo "🧪 RUNNING RIGOROUS SCENARIO TESTING\n";
    echo "====================================\n";
    echo "Testing $totalTests comprehensive scenarios\n\n";

    foreach ($testScenarios as $index => $scenario) {
        echo "Test " . ($index + 1) . ": {$scenario['category']} ({$scenario['difficulty']})\n";
        echo "Question: {$scenario['question']}\n";
        echo "Submitted: '{$scenario['submitted']}'\n";
        echo "Alternative: '{$scenario['alternative']}'\n";
        echo "Subject: {$scenario['subject']}\n";
        echo "Expected: " . ($scenario['expected_score'] * 100) . "%\n\n";

        // Test Enhanced System
        $enhancedStart = microtime(true);
        $enhancedResult = $writingGrader->gradeAnswer(
            $scenario['submitted'],
            $scenario['alternative'],
            $scenario['subject']
        );
        $enhancedTime = round((microtime(true) - $enhancedStart) * 1000, 2);

        // Test Existing System
        $existingStart = microtime(true);
        $existingResult = $existingService->evaluateAnswer(
            $scenario['submitted'],
            $scenario['alternative']
        );
        $existingTime = round((microtime(true) - $existingStart) * 1000, 2);

        $enhancedScore = $enhancedResult['similarity'];
        $existingScore = $existingResult['similarity'] ?? 0;
        $improvement = $enhancedScore - $existingScore;

        $enhancedPassedTest = $enhancedScore >= $scenario['expected_score'];
        $existingPassedTest = $existingScore >= $scenario['expected_score'];

        if ($enhancedPassedTest) $enhancedPassed++;
        if ($existingPassedTest) $existingPassed++;
        if ($improvement > 0.05) $improvements++; // Significant improvement

        echo "📊 RESULTS:\n";
        echo "Enhanced System: " . number_format($enhancedScore * 100, 2) . "% (" . ($enhancedPassedTest ? '✅' : '❌') . ")\n";
        echo "Existing System: " . number_format($existingScore * 100, 2) . "% (" . ($existingPassedTest ? '✅' : '❌') . ")\n";
        echo "Improvement: " . ($improvement > 0 ? '+' : '') . number_format($improvement * 100, 2) . "%\n";
        echo "Performance: Enhanced {$enhancedTime}ms vs Existing {$existingTime}ms\n";

        if ($improvement > 0.1) {
            echo "🎉 SIGNIFICANT IMPROVEMENT!\n";
        } elseif ($improvement < -0.1) {
            echo "⚠️ REGRESSION DETECTED!\n";
        }

        echo "\n" . str_repeat("-", 60) . "\n\n";

        $results[] = [
            'scenario' => $scenario,
            'enhanced' => [
                'score' => $enhancedScore,
                'time' => $enhancedTime,
                'passed' => $enhancedPassedTest,
                'confidence' => $enhancedResult['confidence'] ?? 0
            ],
            'existing' => [
                'score' => $existingScore,
                'time' => $existingTime,
                'passed' => $existingPassedTest
            ],
            'improvement' => $improvement,
            'significant_improvement' => $improvement > 0.05
        ];
    }

    // Calculate comprehensive statistics
    $enhancedAccuracy = round(($enhancedPassed / $totalTests) * 100, 2);
    $existingAccuracy = round(($existingPassed / $totalTests) * 100, 2);
    $overallImprovement = round(($enhancedAccuracy - $existingAccuracy), 2);
    $improvementRate = round(($improvements / $totalTests) * 100, 2);

    $avgEnhancedTime = round(array_sum(array_column(array_column($results, 'enhanced'), 'time')) / $totalTests, 2);
    $avgExistingTime = round(array_sum(array_column(array_column($results, 'existing'), 'time')) / $totalTests, 2);
    $avgConfidence = round(array_sum(array_column(array_column($results, 'enhanced'), 'confidence')) / $totalTests * 100, 2);

    echo "📊 COMPREHENSIVE TEST RESULTS SUMMARY\n";
    echo "=====================================\n";
    echo "Total Scenarios Tested: $totalTests\n\n";

    echo "🎯 ACCURACY COMPARISON:\n";
    echo "Enhanced System: {$enhancedAccuracy}% ($enhancedPassed/$totalTests passed)\n";
    echo "Existing System: {$existingAccuracy}% ($existingPassed/$totalTests passed)\n";
    echo "Overall Improvement: " . ($overallImprovement > 0 ? '+' : '') . "{$overallImprovement}%\n";
    echo "Significant Improvements: {$improvementRate}% of tests\n\n";

    echo "⚡ PERFORMANCE COMPARISON:\n";
    echo "Enhanced System: {$avgEnhancedTime}ms average\n";
    echo "Existing System: {$avgExistingTime}ms average\n";
    echo "Performance Change: " . ($avgEnhancedTime < $avgExistingTime ? '-' : '+') . abs($avgEnhancedTime - $avgExistingTime) . "ms\n";
    echo "Average Confidence: {$avgConfidence}%\n\n";

    // Category breakdown
    echo "📈 CATEGORY BREAKDOWN:\n";
    $categories = array_unique(array_column($testScenarios, 'category'));
    foreach ($categories as $category) {
        $categoryResults = array_filter($results, fn($r) => $r['scenario']['category'] === $category);
        $categoryEnhanced = count(array_filter($categoryResults, fn($r) => $r['enhanced']['passed']));
        $categoryExisting = count(array_filter($categoryResults, fn($r) => $r['existing']['passed']));
        $categoryTotal = count($categoryResults);

        echo "$category:\n";
        echo "  Enhanced: " . round(($categoryEnhanced / $categoryTotal) * 100, 1) . "% ($categoryEnhanced/$categoryTotal)\n";
        echo "  Existing: " . round(($categoryExisting / $categoryTotal) * 100, 1) . "% ($categoryExisting/$categoryTotal)\n";
        echo "  Improvement: " . round(((($categoryEnhanced / $categoryTotal) - ($categoryExisting / $categoryTotal)) * 100), 1) . "%\n\n";
    }

    // Difficulty analysis
    echo "🎚️ DIFFICULTY ANALYSIS:\n";
    $difficulties = ['Easy', 'Medium', 'Hard'];
    foreach ($difficulties as $difficulty) {
        $diffResults = array_filter($results, fn($r) => $r['scenario']['difficulty'] === $difficulty);
        if (!empty($diffResults)) {
            $diffEnhanced = count(array_filter($diffResults, fn($r) => $r['enhanced']['passed']));
            $diffTotal = count($diffResults);

            echo "$difficulty:\n";
            echo "  Enhanced Success Rate: " . round(($diffEnhanced / $diffTotal) * 100, 1) . "% ($diffEnhanced/$diffTotal)\n";
            echo "  Average Confidence: " . round(array_sum(array_column(array_column($diffResults, 'enhanced'), 'confidence')) / $diffTotal * 100, 1) . "%\n\n";
        }
    }

    // Top improvements
    echo "🏆 TOP 5 IMPROVEMENTS:\n";
    $sortedByImprovement = $results;
    usort($sortedByImprovement, fn($a, $b) => $b['improvement'] <=> $a['improvement']);

    for ($i = 0; $i < min(5, count($sortedByImprovement)); $i++) {
        $result = $sortedByImprovement[$i];
        if ($result['improvement'] > 0.05) {
            echo ($i + 1) . ". {$result['scenario']['category']}\n";
            echo "   Improvement: +" . number_format($result['improvement'] * 100, 1) . "%\n";
            echo "   Enhanced Score: " . number_format($result['enhanced']['score'] * 100, 1) . "%\n";
            echo "   Existing Score: " . number_format($result['existing']['score'] * 100, 1) . "%\n\n";
        }
    }

    // Performance assessment
    echo "🎯 SYSTEM ASSESSMENT:\n";
    if ($enhancedAccuracy >= 85) {
        echo "✅ EXCELLENT: Enhanced system achieves target accuracy!\n";
    } elseif ($enhancedAccuracy >= 75) {
        echo "✅ GOOD: Enhanced system shows strong performance!\n";
    } else {
        echo "⚠️ NEEDS WORK: Enhanced system below target accuracy\n";
    }

    if ($overallImprovement > 10) {
        echo "🚀 OUTSTANDING: Significant improvement over existing system!\n";
    } elseif ($overallImprovement > 5) {
        echo "✅ GOOD: Moderate improvement over existing system!\n";
    } elseif ($overallImprovement > 0) {
        echo "📈 SLIGHT: Some improvement over existing system\n";
    } else {
        echo "❌ REGRESSION: Performance worse than existing system\n";
    }

    if ($avgEnhancedTime <= 200) {
        echo "⚡ FAST: Performance within acceptable range!\n";
    } else {
        echo "🐌 SLOW: Performance needs optimization\n";
    }

    echo "\n🎉 RIGOROUS SCENARIO TESTING COMPLETED\n";
    echo "========================================\n";
    echo "Ready for production: " . ($enhancedAccuracy >= 75 ? "YES" : "NO") . "\n";
    echo "Significant improvement: " . ($overallImprovement > 5 ? "YES" : "NO") . "\n\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
