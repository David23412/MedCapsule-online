<?php

namespace Tests\Tinker;

use App\Services\WrittenAnswerEvaluationService;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * PHP Tinker Tests for Written Answer Grading
 * Run with: php artisan tinker --execute="require 'tests/Tinker/WrittenAnswerGradingTests.php'; (new Tests\Tinker\WrittenAnswerGradingTests())->runAllTests();"
 */
class WrittenAnswerGradingTests
{
    private WrittenAnswerEvaluationService $evaluationService;
    private array $testResults = [];

    public function __construct()
    {
        $this->evaluationService = app(WrittenAnswerEvaluationService::class);
        echo "🧪 Written Answer Grading Tests Initialized\n";
        echo "============================================\n\n";
    }

    public function runAllTests(): array
    {
        echo "🚀 Starting Comprehensive Written Answer Grading Tests...\n\n";

        $this->testAnatomyQuestions();
        $this->testPhysiologyQuestions();
        $this->testBiochemistryQuestions();
        $this->testHistologyQuestions();
        $this->testMedicalAbbreviations();
        $this->testEdgeCases();
        $this->testCriticalErrors();
        $this->testIntelligentFeatures(); // NEW: Test the intelligent features

        return $this->generateReport();
    }

    private function testAnatomyQuestions(): void
    {
        echo "🔍 Testing Anatomy Questions\n";
        echo "---------------------------\n";

        $anatomyTests = [
            [
                'name' => 'Heart Anatomy - Perfect Match',
                'submitted' => 'The heart is a muscular organ with four chambers: two atria and two ventricles',
                'correct' => 'The heart is a muscular organ with four chambers: two atria and two ventricles',
                'expected' => true,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Heart Anatomy - Vague but Correct',
                'submitted' => 'The heart pumps blood through the body',
                'correct' => 'The heart is a muscular organ that pumps oxygenated blood to the body and deoxygenated blood to the lungs',
                'expected' => true,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Anatomical Position Terms',
                'submitted' => 'The heart is anterior to the spine',
                'correct' => 'The heart is positioned anterior to the vertebral column',
                'expected' => true,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Wrong Anatomical Direction',
                'submitted' => 'The heart is posterior to the sternum',
                'correct' => 'The heart is posterior to the sternum',
                'expected' => false, // This is actually correct, but let's test edge case
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Bone Classification',
                'submitted' => 'Bones are classified as long, short, flat, and irregular based on their shape',
                'correct' => 'Bones can be classified as long, short, flat, and irregular depending on their shape and function',
                'expected' => true,
                'topic' => 'anatomy'
            ]
        ];

        foreach ($anatomyTests as $test) {
            $this->runSingleTest($test);
        }
        echo "\n";
    }

    private function testPhysiologyQuestions(): void
    {
        echo "🫀 Testing Physiology Questions\n";
        echo "------------------------------\n";

        $physiologyTests = [
            [
                'name' => 'Sympathetic Nervous System',
                'submitted' => 'Sympathetic stimulation increases heart rate and decreases digestion',
                'correct' => 'The sympathetic nervous system increases heart rate, blood pressure, and inhibits digestion',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Parasympathetic Nervous System',
                'submitted' => 'Parasympathetic stimulation decreases heart rate and increases digestion',
                'correct' => 'The parasympathetic nervous system decreases heart rate and enhances digestive activity',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Action Potential - Correct',
                'submitted' => 'An action potential involves depolarization followed by repolarization due to sodium and potassium movement',
                'correct' => 'Action potential consists of depolarization caused by sodium influx and repolarization by potassium efflux',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Cardiac Cycle',
                'submitted' => 'The cardiac cycle consists of systole and diastole phases',
                'correct' => 'The cardiac cycle includes systole (contraction) and diastole (relaxation) of the heart chambers',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Glomerular Filtration',
                'submitted' => 'Blood is filtered from glomerulus into Bowman\'s capsule',
                'correct' => 'Filtration occurs from the glomerulus into Bowman\'s capsule due to pressure differences',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Critical Error - Wrong Direction',
                'submitted' => 'Blood is filtered from Bowman\'s capsule into glomerulus',
                'correct' => 'Filtration occurs from the glomerulus into Bowman\'s capsule',
                'expected' => false,
                'topic' => 'physiology'
            ]
        ];

        foreach ($physiologyTests as $test) {
            $this->runSingleTest($test);
        }
        echo "\n";
    }

    private function testBiochemistryQuestions(): void
    {
        echo "🧬 Testing Biochemistry Questions\n";
        echo "--------------------------------\n";

        $biochemistryTests = [
            [
                'name' => 'TCA Cycle - Complete',
                'submitted' => 'The TCA cycle produces NADH, FADH2, and CO2 through oxidative decarboxylation reactions',
                'correct' => 'The citric acid cycle generates NADH, FADH2, CO2, and GTP through a series of oxidative reactions',
                'expected' => true,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'TCA Cycle - Incomplete',
                'submitted' => 'The TCA cycle is a metabolic pathway',
                'correct' => 'The citric acid cycle generates NADH, FADH2, CO2, and GTP through oxidative decarboxylation',
                'expected' => false,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Protein Synthesis - Correct Location',
                'submitted' => 'Protein synthesis occurs on ribosomes in the cytoplasm',
                'correct' => 'Protein synthesis takes place on ribosomes, which can be free in the cytoplasm or attached to the endoplasmic reticulum',
                'expected' => true,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Protein Synthesis - Wrong Location',
                'submitted' => 'Protein synthesis occurs in the mitochondria',
                'correct' => 'Protein synthesis takes place on ribosomes in the cytoplasm',
                'expected' => false,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Glycolysis',
                'submitted' => 'Glycolysis converts glucose to pyruvate producing ATP and NADH',
                'correct' => 'Glycolysis is the breakdown of glucose to pyruvate with production of ATP and NADH',
                'expected' => true,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'DNA Replication',
                'submitted' => 'DNA replication is semiconservative with leading and lagging strands',
                'correct' => 'DNA replication involves leading and lagging strands, producing two identical DNA molecules',
                'expected' => true,
                'topic' => 'biochemistry'
            ]
        ];

        foreach ($biochemistryTests as $test) {
            $this->runSingleTest($test);
        }
        echo "\n";
    }

    private function testHistologyQuestions(): void
    {
        echo "🧫 Testing Histology Questions\n";
        echo "-----------------------------\n";

        $histologyTests = [
            [
                'name' => 'Epithelial Tissue',
                'submitted' => 'Epithelial tissue is classified as simple or stratified based on cell layers',
                'correct' => 'Epithelial tissues are classified as simple or stratified depending on the number of cell layers',
                'expected' => true,
                'topic' => 'histology'
            ],
            [
                'name' => 'Connective Tissue',
                'submitted' => 'Connective tissue includes loose and dense types with extracellular matrix',
                'correct' => 'Connective tissue is characterized by cells embedded in an extracellular matrix',
                'expected' => true,
                'topic' => 'histology'
            ],
            [
                'name' => 'Muscle Tissue Types',
                'submitted' => 'Muscle tissue includes skeletal, cardiac, and smooth muscle',
                'correct' => 'There are three types of muscle tissue: skeletal, cardiac, and smooth',
                'expected' => true,
                'topic' => 'histology'
            ]
        ];

        foreach ($histologyTests as $test) {
            $this->runSingleTest($test);
        }
        echo "\n";
    }

    private function testMedicalAbbreviations(): void
    {
        echo "🏥 Testing Medical Abbreviations\n";
        echo "-------------------------------\n";

        $abbreviationTests = [
            [
                'name' => 'SNS/PNS Abbreviations - Correct',
                'submitted' => 'SNS increases HR, PNS decreases HR',
                'correct' => 'Sympathetic nervous system increases heart rate, parasympathetic decreases heart rate',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Lab Values',
                'submitted' => 'Na+ 140 mEq/L, K+ 4.5 mEq/L',
                'correct' => 'Sodium 140 milliequivalents per liter, potassium 4.5 milliequivalents per liter',
                'expected' => true,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'BP and HR',
                'submitted' => 'BP 120/80, HR 72 bpm',
                'correct' => 'Blood pressure is 120 over 80, heart rate is 72 beats per minute',
                'expected' => true,
                'topic' => 'physiology'
            ]
        ];

        foreach ($abbreviationTests as $test) {
            $this->runSingleTest($test);
        }
        echo "\n";
    }

    private function testEdgeCases(): void
    {
        echo "⚠️ Testing Edge Cases\n";
        echo "--------------------\n";

        $edgeCaseTests = [
            [
                'name' => 'Empty Answer',
                'submitted' => '',
                'correct' => 'The heart pumps blood',
                'expected' => false,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Very Short Answer',
                'submitted' => 'Heart',
                'correct' => 'The heart is a muscular organ that pumps blood throughout the body',
                'expected' => false,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Completely Wrong Topic',
                'submitted' => 'The liver produces insulin',
                'correct' => 'The pancreas produces insulin',
                'expected' => false,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Spelling Errors',
                'submitted' => 'The hart pumps blod',
                'correct' => 'The heart pumps blood',
                'expected' => false,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Alternative Correct Phrasing',
                'submitted' => 'Cardiac muscle contracts to pump blood',
                'correct' => 'Heart muscle contracts to circulate blood',
                'expected' => true,
                'topic' => 'anatomy'
            ]
        ];

        foreach ($edgeCaseTests as $test) {
            $this->runSingleTest($test);
        }
        echo "\n";
    }

    private function testCriticalErrors(): void
    {
        echo "🚨 Testing Critical Errors\n";
        echo "-------------------------\n";

        $criticalErrorTests = [
            [
                'name' => 'Critical - Wrong Organ Function',
                'submitted' => 'The lungs produce insulin',
                'correct' => 'The pancreas produces insulin',
                'expected' => false,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Critical - Wrong Blood Flow',
                'submitted' => 'Deoxygenated blood goes to the left ventricle',
                'correct' => 'Deoxygenated blood returns to the right ventricle',
                'expected' => false,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Critical - Wrong Molecular Location',
                'submitted' => 'DNA replication occurs in the cytoplasm',
                'correct' => 'DNA replication occurs in the nucleus',
                'expected' => false,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Critical - Contradictory Statements',
                'submitted' => 'The sympathetic nervous system decreases heart rate and increases digestion',
                'correct' => 'The sympathetic nervous system increases heart rate and decreases digestion',
                'expected' => false,
                'topic' => 'physiology'
            ]
        ];

        foreach ($criticalErrorTests as $test) {
            $this->runSingleTest($test);
        }
        echo "\n";
    }

    private function testIntelligentFeatures(): void
    {
        echo "🧠 Testing Intelligent Features\n";
        echo "------------------------------\n";

        $intelligentTests = [
            // Medical Context Tests
            [
                'name' => 'Intelligent - Blood Flow Direction',
                'submitted' => 'Blood flows in vein',
                'correct' => 'Blood flows through veins',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Intelligent - Location Understanding',
                'submitted' => 'Heart in chest',
                'correct' => 'Heart is located in the thorax',
                'expected' => true,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Intelligent - Function Understanding',
                'submitted' => 'Heart pumps blood',
                'correct' => 'Heart functions to circulate blood',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Intelligent - Process Location',
                'submitted' => 'Protein synthesis in ribosome',
                'correct' => 'Protein synthesis occurs in ribosomes',
                'expected' => true,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Intelligent - Quantitative Values',
                'submitted' => 'BP 120/80',
                'correct' => 'Blood pressure is 120 over 80',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Intelligent - Medical Abbreviations',
                'submitted' => 'SNS increases HR, PNS decreases HR',
                'correct' => 'Sympathetic nervous system increases heart rate, parasympathetic decreases heart rate',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Intelligent - Typos in Medical Terms',
                'submitted' => 'Protien synthesis occurs in ribosomes',
                'correct' => 'Protein synthesis occurs in ribosomes',
                'expected' => true,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Intelligent - Typos in Anatomy',
                'submitted' => 'The hart is in the chest',
                'correct' => 'The heart is in the thorax',
                'expected' => true,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Intelligent - Typos in Physiology',
                'submitted' => 'Parasympatheic stimulation decreases HR',
                'correct' => 'Parasympathetic stimulation decreases heart rate',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Intelligent - Context Understanding',
                'submitted' => 'Vein carries blood',
                'correct' => 'Veins carry blood',
                'expected' => true,
                'topic' => 'anatomy'
            ],
            [
                'name' => 'Intelligent - Alternative Phrasing',
                'submitted' => 'Cardiac muscle contracts to move blood',
                'correct' => 'Heart muscle contracts to pump blood',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Intelligent - Process Understanding',
                'submitted' => 'DNA replication happens in nucleus',
                'correct' => 'DNA replication occurs in the nucleus',
                'expected' => true,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Intelligent - Flow Understanding',
                'submitted' => 'Blood from heart to lungs',
                'correct' => 'Blood flows from the heart to the lungs',
                'expected' => true,
                'topic' => 'physiology'
            ],
            [
                'name' => 'Intelligent - Lab Values',
                'submitted' => 'Na+ 140 mEq/L, K+ 4.5 mEq/L',
                'correct' => 'Sodium 140 milliequivalents per liter, potassium 4.5 milliequivalents per liter',
                'expected' => true,
                'topic' => 'biochemistry'
            ],
            [
                'name' => 'Intelligent - Medical Symbols',
                'submitted' => 'BP ↑ in hypertension',
                'correct' => 'Blood pressure increases in hypertension',
                'expected' => true,
                'topic' => 'physiology'
            ],

            // General Knowledge Tests (Non-Medical)
            [
                'name' => 'General - Physics Gravity',
                'submitted' => 'Gravety pulls objects toward earth',
                'correct' => 'Gravity pulls objects toward Earth',
                'expected' => true,
                'topic' => 'physics'
            ],
            [
                'name' => 'General - Chemistry States',
                'submitted' => 'Water can be solid liquid or gas',
                'correct' => 'Water can be solid, liquid, or gas',
                'expected' => true,
                'topic' => 'chemistry'
            ],
            [
                'name' => 'General - Biology Photosynthesis',
                'submitted' => 'Plants use sunlite to make food',
                'correct' => 'Plants use sunlight to make food',
                'expected' => true,
                'topic' => 'biology'
            ],
            [
                'name' => 'General - Geography Continents',
                'submitted' => 'There are seven main continents',
                'correct' => 'There are seven continents',
                'expected' => true,
                'topic' => 'geography'
            ],
            [
                'name' => 'General - History Democracy',
                'submitted' => 'Democracy is rule by the people',
                'correct' => 'Democracy is government by the people',
                'expected' => true,
                'topic' => 'history'
            ],

            // Advanced Edge Cases
            [
                'name' => 'Edge - Complex Typos',
                'submitted' => 'The magnficent schollar won the Nobeel Prize',
                'correct' => 'The magnificent scholar won the Nobel Prize',
                'expected' => true,
                'topic' => 'general'
            ],
            [
                'name' => 'Edge - Word Order Variation',
                'submitted' => 'Quick brown the fox jumps over lazy dog the',
                'correct' => 'The quick brown fox jumps over the lazy dog',
                'expected' => false,
                'topic' => 'general'
            ],
            [
                'name' => 'Edge - Synonyms and Paraphrasing',
                'submitted' => 'The big red automobile moved rapidly down the street',
                'correct' => 'The large crimson car drove quickly along the road',
                'expected' => true,
                'topic' => 'general'
            ],
            [
                'name' => 'Edge - Partial Correctness',
                'submitted' => 'Photosynthesis is a process where plants make food using sunlight',
                'correct' => 'Photosynthesis is the process by which plants convert sunlight into chemical energy',
                'expected' => true,
                'topic' => 'biology'
            ],
            [
                'name' => 'Edge - Technical vs Simple Language',
                'submitted' => 'The Earth goes around the Sun',
                'correct' => 'Earth orbits the Sun in an elliptical path due to gravitational forces',
                'expected' => true,
                'topic' => 'astronomy'
            ],
            [
                'name' => 'Edge - Numbers and Units',
                'submitted' => 'Light travels at 300000000 meters per second',
                'correct' => 'Light travels at 3.00 × 10^8 meters per second',
                'expected' => true,
                'topic' => 'physics'
            ],
            [
                'name' => 'Edge - Abbreviations General',
                'submitted' => 'USA is in North America',
                'correct' => 'United States of America is in North America',
                'expected' => true,
                'topic' => 'geography'
            ],
            [
                'name' => 'Edge - Multiple Meanings',
                'submitted' => 'Bank is a place where money is kept',
                'correct' => 'A bank is a financial institution where money is deposited and lent',
                'expected' => true,
                'topic' => 'economics'
            ],

            // Back-to-Back Testing Scenarios
            [
                'name' => 'Back-to-Back - Evolution vs Creation',
                'submitted' => 'Species change over time through natural selection',
                'correct' => 'Evolution occurs through natural selection over generations',
                'expected' => true,
                'topic' => 'biology'
            ],
            [
                'name' => 'Back-to-Back - Climate Change',
                'submitted' => 'Global warming is caused by greenhouse gases',
                'correct' => 'Climate change results from increased greenhouse gas emissions',
                'expected' => true,
                'topic' => 'environmental_science'
            ],
            [
                'name' => 'Back-to-Back - Economic Systems',
                'submitted' => 'Capitalism is free market system',
                'correct' => 'Capitalism is an economic system based on private ownership and free markets',
                'expected' => true,
                'topic' => 'economics'
            ],
            [
                'name' => 'Back-to-Back - Programming Logic',
                'submitted' => 'If statement checks condition and executes code',
                'correct' => 'An if statement evaluates a condition and executes code if true',
                'expected' => true,
                'topic' => 'computer_science'
            ],
            [
                'name' => 'Back-to-Back - Mathematical Proofs',
                'submitted' => 'Pythagoras theorem states a2 + b2 = c2',
                'correct' => 'Pythagoras\' theorem: the square of the hypotenuse equals sum of squares of other sides',
                'expected' => true,
                'topic' => 'mathematics'
            ]
        ];

        foreach ($intelligentTests as $test) {
            $this->runSingleTest($test);
        }
        echo "\n";
    }

    private function runSingleTest(array $test): void
    {
        try {
            $startTime = microtime(true);

            $result = $this->evaluationService->evaluateAnswer(
                $test['submitted'],
                $test['correct'],
                [], // No alternative answers for these tests
                0.6 // Lower threshold for more forgiveness
            );

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            $isCorrect = $result['isCorrect'];
            $similarity = $result['similarity'];
            $expected = $test['expected'];

            $status = ($isCorrect === $expected) ? '✅ PASS' : '❌ FAIL';
            $expectedText = $expected ? 'Correct' : 'Incorrect';

            echo sprintf(
                "%s | %s (%.2f) | Expected: %s | Time: %dms\n",
                $status,
                $test['name'],
                $similarity,
                $expectedText,
                $processingTime
            );

            if ($isCorrect !== $expected) {
                echo sprintf("   → Submitted: '%s'\n", $test['submitted']);
                echo sprintf("   → Correct:   '%s'\n", $test['correct']);
                echo sprintf("   → Reason:    %s\n", $result['reason'] ?? 'Unknown');
                echo sprintf("   → Feedback:  %s\n", $result['feedback'] ?? 'No feedback');
                echo "\n";
            }

            $this->testResults[] = [
                'name' => $test['name'],
                'topic' => $test['topic'],
                'submitted' => $test['submitted'],
                'correct' => $test['correct'],
                'expected' => $expected,
                'actual' => $isCorrect,
                'similarity' => $similarity,
                'processing_time' => $processingTime,
                'passed' => ($isCorrect === $expected),
                'reason' => $result['reason'] ?? '',
                'feedback' => $result['feedback'] ?? ''
            ];

        } catch (\Exception $e) {
            echo sprintf("💥 ERROR | %s | Exception: %s\n", $test['name'], $e->getMessage());
            $this->testResults[] = [
                'name' => $test['name'],
                'topic' => $test['topic'],
                'error' => $e->getMessage(),
                'passed' => false
            ];
        }
    }

    private function generateReport(): array
    {
        echo "📊 TEST RESULTS SUMMARY\n";
        echo "======================\n\n";

        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($test) => $test['passed'] ?? false));
        $failedTests = $totalTests - $passedTests;

        $accuracy = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;

        echo sprintf("Total Tests: %d\n", $totalTests);
        echo sprintf("Passed:      %d\n", $passedTests);
        echo sprintf("Failed:      %d\n", $failedTests);
        echo sprintf("Accuracy:    %.2f%%\n\n", $accuracy);

        // Group by topic
        $topicStats = [];
        foreach ($this->testResults as $test) {
            if (isset($test['error'])) continue;

            $topic = $test['topic'] ?? 'unknown';
            if (!isset($topicStats[$topic])) {
                $topicStats[$topic] = ['total' => 0, 'passed' => 0];
            }
            $topicStats[$topic]['total']++;
            if ($test['passed']) {
                $topicStats[$topic]['passed']++;
            }
        }

        echo "📈 Performance by Topic:\n";
        foreach ($topicStats as $topic => $stats) {
            $topicAccuracy = $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100, 2) : 0;
            echo sprintf("   %s: %d/%d (%.2f%%)\n", ucfirst($topic), $stats['passed'], $stats['total'], $topicAccuracy);
        }

        // Performance metrics
        $processingTimes = array_filter(array_column($this->testResults, 'processing_time'));
        if (!empty($processingTimes)) {
            $avgTime = round(array_sum($processingTimes) / count($processingTimes), 2);
            $maxTime = max($processingTimes);
            $minTime = min($processingTimes);

            echo "\n⚡ Performance Metrics:\n";
            echo sprintf("   Average Time: %.2fms\n", $avgTime);
            echo sprintf("   Fastest:      %.2fms\n", $minTime);
            echo sprintf("   Slowest:      %.2fms\n", $maxTime);
        }

        // Show failed tests
        $failedTestsList = array_filter($this->testResults, fn($test) => !($test['passed'] ?? false));
        if (!empty($failedTestsList)) {
            echo "\n❌ Failed Tests:\n";
            foreach ($failedTestsList as $test) {
                if (isset($test['error'])) {
                    echo sprintf("   💥 %s: %s\n", $test['name'], $test['error']);
                } else {
                    echo sprintf("   ❌ %s (Topic: %s, Similarity: %.2f)\n", $test['name'], $test['topic'], $test['similarity']);
                    echo sprintf("      → %s\n", $test['reason']);
                }
            }
        }

        echo "\n🎯 Grading Intelligence Assessment:\n";
        if ($accuracy >= 90) {
            echo "   🏆 EXCELLENT: The grading system demonstrates exceptional accuracy and intelligence.\n";
        } elseif ($accuracy >= 80) {
            echo "   ✅ GOOD: The grading system performs well with room for minor improvements.\n";
        } elseif ($accuracy >= 70) {
            echo "   ⚠️ FAIR: The grading system needs significant improvements.\n";
        } else {
            echo "   ❌ POOR: The grading system requires major revisions.\n";
        }

        echo "\n✨ Test completed at " . date('Y-m-d H:i:s') . "\n";

        return [
            'total_tests' => $totalTests,
            'passed' => $passedTests,
            'failed' => $failedTests,
            'accuracy' => $accuracy,
            'topic_stats' => $topicStats,
            'results' => $this->testResults
        ];
    }

    public function runQuickTest(): void
    {
        echo "🧪 Quick Written Answer Test\n";
        echo "===========================\n\n";

        $test = [
            'name' => 'Quick Test - Heart Function',
            'submitted' => 'The heart pumps blood',
            'correct' => 'The heart is a muscular organ that pumps blood throughout the body',
            'expected' => true,
            'topic' => 'anatomy'
        ];

        $this->runSingleTest($test);
        echo "\nQuick test completed!\n";
    }

    public function testCustomAnswer(string $submitted, string $correct, float $threshold = 0.85): array
    {
        echo "🔍 Custom Answer Test\n";
        echo "====================\n";
        echo sprintf("Submitted: '%s'\n", $submitted);
        echo sprintf("Correct:   '%s'\n", $correct);
        echo sprintf("Threshold: %.2f\n\n", $threshold);

        $result = $this->evaluationService->evaluateAnswer($submitted, $correct, [], $threshold);

        echo sprintf("Result:    %s\n", $result['isCorrect'] ? '✅ CORRECT' : '❌ INCORRECT');
        echo sprintf("Similarity: %.2f\n", $result['similarity']);
        echo sprintf("Reason:    %s\n", $result['reason']);
        echo sprintf("Feedback:  %s\n\n", $result['feedback']);

        return $result;
    }
}
