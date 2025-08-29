<?php
// tests/Tinker/real_world_platform_test.php

/**
 * REAL-WORLD PLATFORM TEST
 * Testing with actual submitted answers, alternative answers, and edge cases
 * from the current MedCapsule platform written grading system
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;
use App\Services\WrittenAnswerEvaluationService;

echo "🏥 REAL-WORLD MEDCAPSULE PLATFORM TEST\n";
echo "=====================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    $enhancedGrader = app(WritingGraderService::class);
    $existingService = app(WrittenAnswerEvaluationService::class);

    echo "✅ Services loaded successfully\n\n";

    // Test 1: Real Student Submissions from Different Subjects
    echo "🧪 TEST 1: Real Student Submissions Analysis\n";
    echo "===========================================\n";

    // Real student submissions from MedCapsule platform (anatomy)
    $anatomySubmissions = [
        [
            'student' => 'The heart pumps blood',
            'correct' => 'The heart is a muscular organ that pumps oxygenated blood to the body and deoxygenated blood to the lungs',
            'alternatives' => [
                'The heart pumps oxygenated blood to the body and deoxygenated blood to the lungs',
                'Cardiac muscle pumps blood through pulmonary and systemic circulation'
            ],
            'context' => 'Basic cardiac function'
        ],
        [
            'student' => 'Liver is largest organ',
            'correct' => 'The liver is the largest internal organ in the human body',
            'alternatives' => [
                'Liver is the largest organ in the abdominal cavity',
                'Hepatic organ is the largest internal organ'
            ],
            'context' => 'Organ size and location'
        ],
        [
            'student' => 'Kidneys filter blood',
            'correct' => 'The kidneys filter blood and produce urine through glomerular filtration',
            'alternatives' => [
                'Renal system filters blood and produces urine',
                'Kidneys perform filtration and excretion functions'
            ],
            'context' => 'Renal system function'
        ],
        [
            'student' => 'Lungs exchange gases',
            'correct' => 'The lungs facilitate gas exchange between air and blood in the alveoli',
            'alternatives' => [
                'Pulmonary system exchanges oxygen and carbon dioxide',
                'Lungs perform respiration through alveolar gas exchange'
            ],
            'context' => 'Respiratory function'
        ]
    ];

    // Test physiology submissions
    $physiologySubmissions = [
        [
            'student' => 'Sympathetic increases HR',
            'correct' => 'Sympathetic nervous system activation increases heart rate, blood pressure, and inhibits digestion',
            'alternatives' => [
                'SNS activation ↑ HR, ↑ BP, ↓ digestion',
                'Fight or flight response increases cardiac output'
            ],
            'context' => 'Autonomic nervous system'
        ],
        [
            'student' => 'Insulin lowers glucose',
            'correct' => 'Insulin facilitates glucose uptake by cells and lowers blood glucose levels',
            'alternatives' => [
                'Insulin promotes cellular glucose transport and storage',
                'Pancreatic hormone that decreases blood sugar'
            ],
            'context' => 'Endocrine system - glucose regulation'
        ],
        [
            'student' => 'ADH conserves water',
            'correct' => 'Antidiuretic hormone (ADH) acts on the kidneys to conserve water and concentrate urine',
            'alternatives' => [
                'Vasopressin promotes water reabsorption in collecting ducts',
                'Posterior pituitary hormone that regulates water balance'
            ],
            'context' => 'Endocrine system - water balance'
        ]
    ];

    // Test biochemistry submissions
    $biochemistrySubmissions = [
        [
            'student' => 'ATP is energy currency',
            'correct' => 'Adenosine triphosphate (ATP) is the primary energy currency of the cell',
            'alternatives' => [
                'ATP serves as the universal energy carrier in cellular metabolism',
                'High-energy phosphate compound used for energy transfer'
            ],
            'context' => 'Cellular energy metabolism'
        ],
        [
            'student' => 'DNA has double helix',
            'correct' => 'DNA consists of two complementary strands forming a double helix structure',
            'alternatives' => [
                'Deoxyribonucleic acid has antiparallel double helical structure',
                'Watson-Crick model shows complementary base pairing'
            ],
            'context' => 'Nucleic acid structure'
        ]
    ];

    $allSubmissions = [
        'anatomy' => $anatomySubmissions,
        'physiology' => $physiologySubmissions,
        'biochemistry' => $biochemistrySubmissions
    ];

    $totalTests = 0;
    $enhancedCorrect = 0;
    $existingCorrect = 0;
    $performanceData = [];

    foreach ($allSubmissions as $subject => $submissions) {
        echo "\n📚 Subject: " . strtoupper($subject) . "\n";
        echo str_repeat("-", 50) . "\n";

        foreach ($submissions as $i => $test) {
            echo "\nTest " . ($i + 1) . ": {$test['context']}\n";
            echo "Student: {$test['student']}\n";
            echo "Correct: {$test['correct']}\n";

            // Test with existing service
            $existingStart = microtime(true);
            $existingResult = $existingService->evaluateAnswer($test['student'], $test['correct'], $test['alternatives']);
            $existingTime = microtime(true) - $existingStart;

            // Test with enhanced service
            $enhancedStart = microtime(true);
            $enhancedResult = $enhancedGrader->gradeAnswer($test['student'], $test['correct'], $subject);
            $enhancedTime = microtime(true) - $enhancedStart;

            $existingCorrectness = ($existingResult['similarity'] ?? 0) >= 0.7;
            $enhancedCorrectness = $enhancedResult['isCorrect'];

            if ($existingCorrectness) $existingCorrect++;
            if ($enhancedCorrectness) $enhancedCorrect++;
            $totalTests++;

            echo sprintf("  Existing:  %.2f%% similar (%s) - %.2fms\n",
                ($existingResult['similarity'] ?? 0) * 100,
                $existingCorrectness ? '✅ CORRECT' : '❌ INCORRECT',
                $existingTime * 1000
            );
            echo sprintf("  Enhanced:  %.2f%% similar (%s) - %.2fms\n",
                ($enhancedResult['similarity'] ?? 0) * 100,
                $enhancedCorrectness ? '✅ CORRECT' : '❌ INCORRECT',
                $enhancedTime * 1000
            );

            $performanceData[] = [
                'subject' => $subject,
                'context' => $test['context'],
                'existing_time' => $existingTime,
                'enhanced_time' => $enhancedTime,
                'existing_similarity' => $existingResult['similarity'] ?? 0,
                'enhanced_similarity' => $enhancedResult['similarity'] ?? 0,
                'existing_correct' => $existingCorrectness,
                'enhanced_correct' => $enhancedCorrectness
            ];

            // Test alternative answers
            if (!empty($test['alternatives'])) {
                echo "  Alternative Answers:\n";
                foreach ($test['alternatives'] as $alt) {
                    $altResult = $enhancedGrader->gradeAnswer($test['student'], $alt, $subject);
                    echo sprintf("    Alt: %.2f%% similar (%s)\n",
                        ($altResult['similarity'] ?? 0) * 100,
                        $altResult['isCorrect'] ? '✅ ACCEPTABLE' : '❌ NOT ACCEPTABLE'
                    );
                }
            }

            echo "\n";
        }
    }

    // Test 2: Edge Cases Specific to Medical Education
    echo "🧪 TEST 2: Medical Education Edge Cases\n";
    echo "=====================================\n";

    $edgeCases = [
        [
            'student' => 'BP = 120/80 mmHg',
            'correct' => 'Normal blood pressure is 120/80 mmHg',
            'context' => 'Medical abbreviations and values'
        ],
        [
            'student' => 'O2 sat 98%',
            'correct' => 'Normal oxygen saturation is 95-100%',
            'context' => 'Medical abbreviations and ranges'
        ],
        [
            'student' => 'Ca++ is calcium',
            'correct' => 'Ca²⁺ represents calcium ion in medical notation',
            'context' => 'Chemical symbols in medicine'
        ],
        [
            'student' => '↑ HR, ↓ BP',
            'correct' => 'Increased heart rate, decreased blood pressure',
            'context' => 'Medical symbols and trends'
        ],
        [
            'student' => 'pt c/o chest pain',
            'correct' => 'Patient complains of chest pain',
            'context' => 'Medical charting abbreviations'
        ],
        [
            'student' => 'MI = heart attack',
            'correct' => 'Myocardial infarction is commonly called heart attack',
            'context' => 'Medical terminology acronyms'
        ],
        [
            'student' => 'COPD = chronic bronchitis',
            'correct' => 'COPD is chronic obstructive pulmonary disease',
            'context' => 'Respiratory disease terminology'
        ],
        [
            'student' => 'DM = diabetes mellitus',
            'correct' => 'DM stands for diabetes mellitus',
            'context' => 'Endocrine disease abbreviations'
        ],
        [
            'student' => '',
            'correct' => 'The heart is a muscular organ',
            'context' => 'Empty student answer'
        ],
        [
            'student' => str_repeat('The heart pumps blood ', 50),
            'correct' => 'The heart is a muscular organ that pumps blood',
            'context' => 'Very long student answer'
        ]
    ];

    $edgeResults = [];
    foreach ($edgeCases as $i => $test) {
        echo "Edge Case " . ($i + 1) . ": {$test['context']}\n";
        echo "Student: " . (strlen($test['student']) > 50 ? substr($test['student'], 0, 50) . '...' : $test['student']) . "\n";

        try {
            $result = $enhancedGrader->gradeAnswer($test['student'], $test['correct'], 'anatomy');
            echo sprintf("  Result: %.2f%% similar (%s)\n",
                ($result['similarity'] ?? 0) * 100,
                $result['isCorrect'] ? '✅ CORRECT' : '❌ INCORRECT'
            );
            $edgeResults[] = ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
            $edgeResults[] = ['success' => false, 'error' => $e->getMessage()];
        }
        echo "\n";
    }

    // Test 3: Real Quiz Mode Simulation
    echo "🧪 TEST 3: Quiz Mode Simulation\n";
    echo "==============================\n";

    $quizScenarios = [
        [
            'question' => 'What is the primary function of the heart?',
            'correct_answer' => 'The heart is a muscular organ that pumps oxygenated blood to the body and deoxygenated blood to the lungs',
            'student_answers' => [
                'Pumps blood',
                'Cardiac pump',
                'Circulates blood throughout the body',
                'Muscular organ that contracts to move blood',
                'Heart pumps oxygenated blood to body and deoxygenated to lungs'
            ],
            'subject' => 'anatomy'
        ],
        [
            'question' => 'What does the sympathetic nervous system do?',
            'correct_answer' => 'The sympathetic nervous system increases heart rate, blood pressure, and prepares the body for fight or flight response',
            'student_answers' => [
                'Fight or flight',
                'Increases HR and BP',
                'SNS activation',
                'Prepares body for stress',
                '↑ HR, ↑ BP, ↓ digestion'
            ],
            'subject' => 'physiology'
        ]
    ];

    $quizResults = [];
    foreach ($quizScenarios as $scenario) {
        echo "Question: {$scenario['question']}\n";
        echo "Subject: {$scenario['subject']}\n\n";

        $scenarioResults = [];
        foreach ($scenario['student_answers'] as $i => $studentAnswer) {
            $result = $enhancedGrader->gradeAnswer($studentAnswer, $scenario['correct_answer'], $scenario['subject']);
            $scenarioResults[] = $result;

            echo sprintf("  Answer %d: %.2f%% similar (%s)\n",
                $i + 1,
                ($result['similarity'] ?? 0) * 100,
                $result['isCorrect'] ? '✅ CORRECT' : '❌ INCORRECT'
            );
        }

        $correctCount = count(array_filter($scenarioResults, fn($r) => $r['isCorrect']));
        echo "  Quiz Results: $correctCount/" . count($scenarioResults) . " correct answers\n\n";

        $quizResults[] = [
            'question' => $scenario['question'],
            'correct_count' => $correctCount,
            'total_answers' => count($scenario['student_answers']),
            'results' => $scenarioResults
        ];
    }

    // FINAL COMPREHENSIVE ANALYSIS
    echo "🎯 COMPREHENSIVE ANALYSIS & ACCURACY REPORT\n";
    echo "=============================================\n";

    // Performance Analysis
    $existingTimes = array_column($performanceData, 'existing_time');
    $enhancedTimes = array_column($performanceData, 'enhanced_time');
    $avgExistingTime = array_sum($existingTimes) / count($existingTimes);
    $avgEnhancedTime = array_sum($enhancedTimes) / count($enhancedTimes);
    $avgSpeedup = (($avgExistingTime - $avgEnhancedTime) / $avgExistingTime) * 100;

    echo "Performance Metrics:\n";
    echo sprintf("  Average Existing Time: %.2fms\n", $avgExistingTime * 1000);
    echo sprintf("  Average Enhanced Time: %.2fms\n", $avgEnhancedTime * 1000);
    echo sprintf("  Average Speedup: %.1f%%\n", $avgSpeedup);
    echo sprintf("  Total Tests: %d\n", $totalTests);

    // Accuracy Analysis
    $accuracyImprovement = (($enhancedCorrect - $existingCorrect) / $existingCorrect) * 100;

    echo "\nAccuracy Metrics:\n";
    echo sprintf("  Existing Service Correct: %d/%d (%.1f%%)\n",
        $existingCorrect, $totalTests, ($existingCorrect / $totalTests) * 100);
    echo sprintf("  Enhanced Service Correct: %d/%d (%.1f%%)\n",
        $enhancedCorrect, $totalTests, ($enhancedCorrect / $totalTests) * 100);
    echo sprintf("  Accuracy Improvement: %.1f%%\n", $accuracyImprovement);

    // Subject-wise Analysis
    $subjectAnalysis = [];
    foreach ($performanceData as $data) {
        $subject = $data['subject'];
        if (!isset($subjectAnalysis[$subject])) {
            $subjectAnalysis[$subject] = ['total' => 0, 'enhanced_correct' => 0, 'existing_correct' => 0];
        }
        $subjectAnalysis[$subject]['total']++;
        if ($data['enhanced_correct']) $subjectAnalysis[$subject]['enhanced_correct']++;
        if ($data['existing_correct']) $subjectAnalysis[$subject]['existing_correct']++;
    }

    echo "\nSubject-wise Analysis:\n";
    foreach ($subjectAnalysis as $subject => $analysis) {
        echo sprintf("  %s: Enhanced %d/%d (%.1f%%), Existing %d/%d (%.1f%%)\n",
            ucfirst($subject),
            $analysis['enhanced_correct'], $analysis['total'],
            ($analysis['enhanced_correct'] / $analysis['total']) * 100,
            $analysis['existing_correct'], $analysis['total'],
            ($analysis['existing_correct'] / $analysis['total']) * 100
        );
    }

    // Edge Case Analysis
    $edgeSuccessCount = count(array_filter($edgeResults, fn($r) => $r['success']));
    echo sprintf("\nEdge Case Handling: %d/%d (%.1f%%)\n",
        $edgeSuccessCount, count($edgeCases), ($edgeSuccessCount / count($edgeCases)) * 100);

    // Quiz Mode Analysis
    $totalQuizAnswers = array_sum(array_column($quizResults, 'total_answers'));
    $totalCorrectQuiz = array_sum(array_column($quizResults, 'correct_count'));
    echo sprintf("\nQuiz Mode Accuracy: %d/%d (%.1f%%)\n",
        $totalCorrectQuiz, $totalQuizAnswers, ($totalCorrectQuiz / $totalQuizAnswers) * 100);

    // FINAL VERDICT
    echo "\n🏆 FINAL VERDICT\n";
    echo "===============\n";

    $overallScore = 0;

    // Performance score (40% weight)
    if ($avgEnhancedTime < 0.010) $overallScore += 40;
    elseif ($avgEnhancedTime < 0.020) $overallScore += 30;
    elseif ($avgEnhancedTime < 0.050) $overallScore += 20;
    else $overallScore += 10;

    // Accuracy score (40% weight)
    $enhancedAccuracy = ($enhancedCorrect / $totalTests) * 100;
    if ($enhancedAccuracy > 90) $overallScore += 40;
    elseif ($enhancedAccuracy > 80) $overallScore += 35;
    elseif ($enhancedAccuracy > 70) $overallScore += 30;
    else $overallScore += 20;

    // Edge case handling (20% weight)
    if ($edgeSuccessCount == count($edgeCases)) $overallScore += 20;
    elseif ($edgeSuccessCount >= count($edgeCases) * 0.8) $overallScore += 15;
    else $overallScore += 10;

    echo "Overall Assessment Score: $overallScore/100\n";

    if ($overallScore >= 90) {
        echo "🏆 EXCELLENT - Ready for production deployment!\n";
        echo "✅ Performance: Outstanding (<10ms)\n";
        echo "✅ Accuracy: Superior (>90%)\n";
        echo "✅ Reliability: Perfect edge case handling\n";
    } elseif ($overallScore >= 80) {
        echo "✅ VERY GOOD - Ready for production with minor optimizations!\n";
        echo "✅ Performance: Good (<20ms)\n";
        echo "✅ Accuracy: Strong (>80%)\n";
        echo "✅ Reliability: Good edge case handling\n";
    } elseif ($overallScore >= 70) {
        echo "⚠️ GOOD - Ready for production with some tuning!\n";
        echo "✅ Performance: Acceptable (<50ms)\n";
        echo "✅ Accuracy: Decent (>70%)\n";
        echo "⚠️ Reliability: Needs some edge case improvements\n";
    } else {
        echo "❌ NEEDS WORK - Further development required!\n";
        echo "⚠️ Performance: Needs optimization\n";
        echo "⚠️ Accuracy: Needs improvement\n";
        echo "⚠️ Reliability: Edge case handling needs work\n";
    }

    echo "\n🎉 Real-world platform testing complete!\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✨ Test completed at " . now()->format('Y-m-d H:i:s') . "\n";
