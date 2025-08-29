<?php
// tests/Tinker/ultimate_comprehensive_test.php

/**
 * ULTIMATE COMPREHENSIVE TEST
 * Testing ALL scenarios, edge cases, and predictive intelligence
 * Multiple answers, contexts, similarities - extreme rigor
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;

echo "🔥 ULTIMATE COMPREHENSIVE TEST - ALL SCENARIOS\n";
echo "===============================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $enhancedGrader = app(WritingGraderService::class);
    echo "✅ WritingGraderService loaded successfully\n\n";

    // ULTIMATE TEST SCENARIOS - ALL POSSIBLE CASES
    $ultimateScenarios = [
        // 1. BASIC ABBREVIATION SCENARIOS
        [
            'question' => 'What does BP stand for?',
            'correct_answers' => [
                'Blood pressure',
                'Blood Pressure',
                'blood pressure',
                'BP stands for blood pressure'
            ],
            'student_answers' => [
                'BP = Blood pressure',
                'bp means blood pressure',
                'BP',
                'Blood pressure',
                'BP stands for blood pressure',
                'blood pressure abbreviation',
                'BP = 120/80 mmHg',
                'BP is blood pressure',
                'BP: blood pressure'
            ],
            'context' => 'medical_basic',
            'expected_range' => [0.80, 0.95]
        ],

        // 2. COMPLEX MEDICAL ABBREVIATIONS
        [
            'question' => 'Describe the patient presentation',
            'correct_answers' => [
                'Patient complaining of chest pain, blood pressure 180/90, heart rate 110, oxygen saturation 92%',
                'Pt c/o CP, BP 180/90, HR 110, O2 sat 92%'
            ],
            'student_answers' => [
                'Pt c/o CP, BP 180/90, HR 110, O2 sat 92%',
                'Patient with chest pain, BP 180/90, HR 110, O2 sat 92%',
                'pt c/o chest pain, bp 180/90, hr 110, o2 sat 92%',
                'Patient complaining of CP, BP 180/90, HR 110, O2 sat 92%',
                'Pt has CP, BP=180/90, HR=110, O2 sat=92%',
                'Patient: chest pain, BP 180/90, HR 110, O2 92%',
                'CP, BP 180/90, HR 110, O2 sat 92%'
            ],
            'context' => 'emergency_medicine',
            'expected_range' => [0.75, 0.90]
        ],

        // 3. MEDICAL SYNONYMS AND ALTERNATIVES
        [
            'question' => 'What is the function of the heart?',
            'correct_answers' => [
                'The heart pumps blood throughout the body',
                'Cardiac function is to circulate blood',
                'The heart is responsible for blood circulation',
                'Heart function: blood pumping'
            ],
            'student_answers' => [
                'The heart pumps blood',
                'Cardiac muscle contracts to pump blood',
                'Heart circulates blood',
                'Blood pumping organ',
                'The heart is a pump',
                'Cardiac function is pumping',
                'Heart pumps blood around the body',
                'It circulates blood',
                'Blood circulation'
            ],
            'context' => 'cardiology',
            'expected_range' => [0.70, 0.85]
        ],

        // 4. BIOCHEMICAL PATHWAYS
        [
            'question' => 'Describe the TCA cycle',
            'correct_answers' => [
                'The TCA cycle produces NADH and FADH2 in mitochondria',
                'Tricarboxylic acid cycle generates NADH, FADH2',
                'Krebs cycle produces energy molecules NADH and FADH2'
            ],
            'student_answers' => [
                'TCA cycle produces NADH and FADH2',
                'Tricarboxylic acid cycle makes NADH FADH2',
                'Krebs cycle generates NADH, FADH2',
                'TCA produces NADH and FADH2 in mitochondria',
                'Citric acid cycle makes NADH FADH2',
                'TCA cycle: NADH, FADH2 production',
                'Mitochondrial TCA cycle produces NADH and FADH2',
                'TCA = NADH + FADH2 production'
            ],
            'context' => 'biochemistry',
            'expected_range' => [0.85, 0.95]
        ],

        // 5. CLINICAL PRESENTATIONS
        [
            'question' => 'Signs of myocardial infarction',
            'correct_answers' => [
                'Chest pain, diaphoresis, nausea, shortness of breath',
                'CP, diaphoresis, nausea, SOB',
                'Chest pain, sweating, nausea, dyspnea'
            ],
            'student_answers' => [
                'Chest pain, diaphoresis, nausea, SOB',
                'CP, sweating, nausea, shortness of breath',
                'Chest pain, sweating, nausea, dyspnea',
                'CP, diaphoresis, nausea, SOB',
                'Chest pain, sweating, vomiting, shortness of breath',
                'CP, sweating, nausea, SOB',
                'Chest pain, diaphoresis, nausea, dyspnea'
            ],
            'context' => 'cardiology',
            'expected_range' => [0.80, 0.90]
        ],

        // 6. PHARMACOLOGY
        [
            'question' => 'Mechanism of aspirin',
            'correct_answers' => [
                'Aspirin inhibits COX-1 and COX-2 enzymes',
                'ASA blocks cyclooxygenase enzymes',
                'Aspirin prevents prostaglandin synthesis'
            ],
            'student_answers' => [
                'Aspirin inhibits COX enzymes',
                'ASA blocks COX-1 and COX-2',
                'Aspirin prevents prostaglandin formation',
                'COX inhibition by aspirin',
                'Aspirin blocks cyclooxygenase',
                'ASA inhibits prostaglandin synthesis',
                'COX-1 and COX-2 inhibition by aspirin'
            ],
            'context' => 'pharmacology',
            'expected_range' => [0.75, 0.90]
        ],

        // 7. PATHOPHYSIOLOGY
        [
            'question' => 'Pathophysiology of COPD',
            'correct_answers' => [
                'COPD causes airflow limitation due to chronic inflammation',
                'Chronic bronchitis and emphysema lead to airflow obstruction',
                'Airway inflammation and destruction in COPD'
            ],
            'student_answers' => [
                'COPD: chronic inflammation, airflow limitation',
                'Chronic bronchitis + emphysema = airflow obstruction',
                'Airway inflammation and parenchymal destruction',
                'COPD causes chronic inflammation of airways',
                'Airflow limitation from inflammation and destruction',
                'Chronic inflammatory disease of airways',
                'Airway obstruction due to inflammation'
            ],
            'context' => 'pulmonology',
            'expected_range' => [0.70, 0.85]
        ],

        // 8. ENDOCRINOLOGY EMERGENCY
        [
            'question' => 'Signs of DKA',
            'correct_answers' => [
                'Hyperglycemia, ketosis, acidosis, altered mental status',
                'High BG, ketones, low pH, AMS',
                'Diabetic ketoacidosis: hyperglycemia, ketones, acidosis'
            ],
            'student_answers' => [
                'High blood sugar, ketones, acidosis, AMS',
                'Hyperglycemia, ketosis, acidosis, altered mental status',
                'BG high, ketones +, pH low, AMS',
                'Diabetic ketoacidosis: high BG, ketones, acidosis',
                'Hyperglycemia, ketones, acidosis, confusion',
                'High glucose, ketosis, acidemia, AMS',
                'DKA: hyperglycemia, ketones, acidosis, AMS'
            ],
            'context' => 'endocrinology',
            'expected_range' => [0.80, 0.90]
        ],

        // 9. NEUROLOGY
        [
            'question' => 'Autonomic nervous system functions',
            'correct_answers' => [
                'SNS increases HR and BP, PNS decreases HR and BP',
                'Sympathetic increases heart rate, parasympathetic decreases',
                'Autonomic control of cardiac and vascular function'
            ],
            'student_answers' => [
                'SNS ↑ HR, PNS ↓ HR',
                'Sympathetic increases HR, parasympathetic decreases',
                'SNS raises BP and HR, PNS lowers them',
                'Autonomic: sympathetic ↑, parasympathetic ↓',
                'SNS increases cardiac output, PNS decreases',
                'Fight or flight vs rest and digest',
                'SNS: increased HR/BP, PNS: decreased HR/BP'
            ],
            'context' => 'physiology',
            'expected_range' => [0.75, 0.85]
        ],

        // 10. RARE EDGE CASES
        [
            'question' => 'Extremely rare medical condition',
            'correct_answers' => [
                'Takotsubo cardiomyopathy is stress-induced heart failure',
                'Broken heart syndrome: stress cardiomyopathy',
                'Apical ballooning syndrome due to emotional stress'
            ],
            'student_answers' => [
                'Takotsubo cardiomyopathy',
                'Broken heart syndrome',
                'Stress-induced cardiomyopathy',
                'Apical ballooning syndrome',
                'Stress heart failure',
                'Emotional stress cardiomyopathy',
                'Takotsubo syndrome'
            ],
            'context' => 'cardiology',
            'expected_range' => [0.60, 0.80]
        ]
    ];

    echo "🧪 ULTIMATE COMPREHENSIVE TESTING\n";
    echo "=================================\n\n";

    $totalTests = 0;
    $totalPassed = 0;
    $categoryResults = [];

    foreach ($ultimateScenarios as $scenarioIndex => $scenario) {
        echo "🎯 SCENARIO " . ($scenarioIndex + 1) . ": " . $scenario['question'] . "\n";
        echo "Context: " . $scenario['context'] . "\n";
        echo str_repeat("-", 60) . "\n\n";

        $scenarioPassed = 0;
        $scenarioTotal = 0;

        // Test each correct answer against each student answer
        foreach ($scenario['correct_answers'] as $correctIndex => $correctAnswer) {
            echo "📝 CORRECT ANSWER " . ($correctIndex + 1) . ": '" . $correctAnswer . "'\n";
            echo str_repeat("-", 40) . "\n";

            foreach ($scenario['student_answers'] as $studentIndex => $studentAnswer) {
                // Create unique strings to bypass all caching
                $uniqueId = time() . '_' . $scenarioIndex . '_' . $correctIndex . '_' . $studentIndex . '_' . rand(1000, 9999);
                $uniqueStudent = $studentAnswer . '_test_' . $uniqueId;
                $uniqueCorrect = $correctAnswer . '_test_' . $uniqueId;

                // Test the grading system
                $result = $enhancedGrader->gradeAnswer($uniqueStudent, $uniqueCorrect, $scenario['context'], []);

                $similarity = $result['similarity'] ?? 0;
                $expectedMin = $scenario['expected_range'][0];
                $expectedMax = $scenario['expected_range'][1];
                $passed = $similarity >= $expectedMin && $similarity <= $expectedMax;

                $scenarioTotal++;
                if ($passed) {
                    $scenarioPassed++;
                }

                echo sprintf("  Student %d: '%s'\n", $studentIndex + 1, $studentAnswer);
                echo sprintf("    Similarity: %.3f (Expected: %.2f-%.2f) %s\n",
                    $similarity, $expectedMin, $expectedMax,
                    $passed ? '✅' : '❌'
                );

                // Show algorithm breakdown for failed cases or every 5th case
                if (!$passed || ($studentIndex % 5 == 0)) {
                    if (isset($result['algorithm_breakdown'])) {
                        echo "    📊 Breakdown: ";
                        foreach ($result['algorithm_breakdown'] as $alg => $score) {
                            echo sprintf("%s:%.2f ", substr($alg, 0, 3), $score);
                        }
                        echo "\n";
                    }
                }
                echo "\n";
            }
            echo "\n";
        }

        // Scenario results
        $scenarioAccuracy = $scenarioTotal > 0 ? ($scenarioPassed / $scenarioTotal) * 100 : 0;
        $categoryResults[] = [
            'question' => $scenario['question'],
            'context' => $scenario['context'],
            'accuracy' => $scenarioAccuracy,
            'passed' => $scenarioPassed,
            'total' => $scenarioTotal
        ];

        echo "📈 SCENARIO RESULTS: " . $scenarioPassed . "/" . $scenarioTotal . " (" . number_format($scenarioAccuracy, 1) . "%)\n";
        echo str_repeat("=", 60) . "\n\n";

        $totalTests += $scenarioTotal;
        $totalPassed += $scenarioPassed;
    }

    // FINAL COMPREHENSIVE RESULTS
    echo "🎯 ULTIMATE COMPREHENSIVE RESULTS\n";
    echo "=================================\n\n";

    $overallAccuracy = $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0;

    echo "📊 OVERALL PERFORMANCE:\n";
    echo "=====================\n";
    echo sprintf("Total Tests: %d\n", $totalTests);
    echo sprintf("Tests Passed: %d\n", $totalPassed);
    echo sprintf("Overall Accuracy: %.2f%%\n", $overallAccuracy);
    echo str_repeat("=", 50) . "\n\n";

    echo "📋 SCENARIO BREAKDOWN:\n";
    echo "====================\n";
    foreach ($categoryResults as $i => $result) {
        $status = $result['accuracy'] >= 80 ? '✅' : ($result['accuracy'] >= 60 ? '⚠️' : '❌');
        echo sprintf("%s Scenario %d: %.1f%% (%d/%d) - %s\n",
            $status,
            $i + 1,
            $result['accuracy'],
            $result['passed'],
            $result['total']
        );
    }
    echo "\n" . str_repeat("=", 50) . "\n";

    // SUCCESS CRITERIA
    if ($overallAccuracy >= 90) {
        echo "🎉 EXCELLENT - PREDICTIVE INTELLIGENCE ACHIEVED!\n";
        echo "✅ All scenarios handled with high accuracy\n";
        echo "✅ Multiple answers and alternatives working\n";
        echo "✅ Context-aware grading excellent\n";
        echo "✅ Edge cases handled properly\n";
        echo "✅ Predictive intelligence fully functional\n\n";

        echo "🏆 SYSTEM STATUS: MILITARY-GRADE PREDICTIVE AI\n";
        echo "🚀 READY FOR PRODUCTION DEPLOYMENT\n";

    } elseif ($overallAccuracy >= 80) {
        echo "✅ VERY GOOD - Strong predictive intelligence!\n";
        echo "✅ Most scenarios working well\n";
        echo "⚠️ Minor optimization needed for perfection\n";
        echo "✅ Ready for production with minor tuning\n\n";

        echo "🏆 SYSTEM STATUS: PRODUCTION READY WITH TUNING\n";

    } elseif ($overallAccuracy >= 70) {
        echo "⚠️ GOOD - Decent predictive intelligence\n";
        echo "✅ Core functionality working\n";
        echo "❌ Needs significant improvement for edge cases\n";
        echo "🔧 Requires optimization before production\n\n";

        echo "🏆 SYSTEM STATUS: NEEDS OPTIMIZATION\n";

    } else {
        echo "❌ NEEDS WORK - Predictive intelligence requires attention\n";
        echo "❌ Critical scenarios failing\n";
        echo "🔧 Major improvements needed\n\n";

        echo "🏆 SYSTEM STATUS: NOT PRODUCTION READY\n";
        echo "🔧 RECOMMENDATION: Return to development\n";
    }

    echo "\n🎉 Ultimate comprehensive testing complete!\n";
    echo "Tested " . count($ultimateScenarios) . " scenarios with multiple answers each\n";
    echo "Total evaluation combinations: " . $totalTests . "\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed at " . now()->format('Y-m-d H:i:s') . "\n";
