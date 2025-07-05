<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\PatternMatcherService;
use App\Services\TextProcessingService;
use App\Services\WrittenAnswerEvaluationService;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create services
$textProcessor = new TextProcessingService();
$patternMatcher = new PatternMatcherService();
$evaluationService = new WrittenAnswerEvaluationService($textProcessor, $patternMatcher);

// Turn off caching for testing
$textProcessor->setCacheConfig(false);
$patternMatcher->setCacheConfig(false);
$evaluationService->setCacheConfig(false);

// Enhanced testing function that measures performance
function testEnhancedAnswer($evaluationService, $submittedAnswer, $correctAnswer, $title, $category = null, $alternativeAnswers = [], $threshold = 0.85) {
    echo "\n==================================================================\n";
    echo "TEST: " . $title . "\n";
    if ($category) {
        echo "CATEGORY: " . strtoupper($category) . "\n";
    }
    echo "==================================================================\n";
    echo "SUBMITTED: \"$submittedAnswer\"\n";
    echo "CORRECT:   \"$correctAnswer\"\n\n";

    // Measure performance
    $startTime = microtime(true);
    
    // Evaluate the answer
    $result = $evaluationService->evaluateAnswer($submittedAnswer, $correctAnswer, $alternativeAnswers, $threshold);
    
    // Calculate execution time
    $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
    
    // Print results
    echo "RESULT: " . ($result['isCorrect'] ? "\033[32mCORRECT" : "\033[31mINCORRECT") . "\033[0m\n";
    echo "EXECUTION TIME: " . number_format($executionTime, 2) . " ms\n";
    echo "SIMILARITY: " . number_format($result['similarity'] * 100, 2) . "%\n";
    echo "DETECTED COURSE TOPIC: " . ($result['courseTopic'] ?? 'Not detected') . "\n";
    echo "REASON: " . $result['reason'] . "\n";
    echo "FEEDBACK: " . $result['feedback'] . "\n\n";
    
    if (isset($result['metrics'])) {
        echo "METRICS:\n";
        if (isset($result['metrics']['levenshtein'])) {
            echo "  • Levenshtein similarity: " . number_format($result['metrics']['levenshtein'] * 100, 2) . "%\n";
        }
        if (isset($result['metrics']['jaccard'])) {
            echo "  • Jaccard similarity: " . number_format($result['metrics']['jaccard'] * 100, 2) . "%\n";
        }
        if (isset($result['metrics']['keyword'])) {
            echo "  • Keyword overlap: " . number_format($result['metrics']['keyword'] * 100, 2) . "%\n";
        }
        if (isset($result['metrics']['conceptual'])) {
            echo "  • Conceptual similarity: " . number_format($result['metrics']['conceptual'] * 100, 2) . "%\n";
        }
        if (isset($result['metrics']['hasContradictions'])) {
            echo "  • Contains contradictions: " . ($result['metrics']['hasContradictions'] ? "YES" : "No") . "\n";
        }
    }
    
    if (isset($result['domainBoost']) && $result['domainBoost'] > 0) {
        echo "  • Domain boost: " . number_format($result['domainBoost'] * 100, 2) . "%\n";
    }
    
    echo "==================================================================\n";
    
    return [
        'result' => $result,
        'executionTime' => $executionTime
    ];
}

echo "\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ENHANCED MEDICAL EDUCATION ANSWER EVALUATION TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test 1: Medical Symbol Processing
echo "\n\033[1m== MEDICAL SYMBOL PROCESSING TESTS ==\033[0m\n";

$testResults = [];

// Blood pressure with symbols
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "The SNS activation causes ↑ HR, ↑ BP, and mydriasis",
    "Sympathetic nervous system activation causes increased heart rate, elevated blood pressure, and pupillary dilation",
    "Medical Symbols - Arrows",
    "symbols"
);

// Chemical symbols
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "Na+ and K+ are the primary ions involved in maintaining the resting membrane potential",
    "Sodium and potassium ions are the primary ions involved in maintaining the resting membrane potential",
    "Medical Symbols - Chemical Symbols",
    "symbols"
);

// Lab values with standard notations
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "Normal serum K+ is 3.5-5.0 mEq/L and Na+ is 135-145 mEq/L",
    "Normal serum potassium is 3.5-5.0 milliequivalents per liter and sodium is 135-145 milliequivalents per liter",
    "Medical Symbols - Lab Values",
    "symbols"
);

// Blood pressure notation
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "Normal BP is 120/80 mmHg",
    "Normal blood pressure is systolic 120 diastolic 80 millimeters of mercury",
    "Medical Symbols - Blood Pressure Notation",
    "symbols"
);

// Test 2: Performance Optimization Tests
echo "\n\033[1m== PERFORMANCE OPTIMIZATION TESTS ==\033[0m\n";

// Simple exact match (should be very fast)
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "The heart pumps blood throughout the body",
    "The heart pumps blood throughout the body",
    "Performance - Exact Match",
    "performance"
);

// Long answer with contradictions (tests early exit)
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "The parasympathetic nervous system increases heart rate, dilates pupils, and decreases digestive activity, while the sympathetic nervous system has the opposite effects, causing relaxation and preparing the body for rest and digestion.",
    "The sympathetic nervous system increases heart rate, dilates pupils, and decreases digestive activity, while the parasympathetic nervous system has the opposite effects, causing relaxation and preparing the body for rest and digestion.",
    "Performance - Early Contradiction Detection",
    "performance"
);

// Pattern critical match
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "Parasympathetic stimulation decreases heart rate via vagal nerve activation and acetylcholine release",
    "The parasympathetic nervous system decreases heart rate through vagal nerve activation and the release of acetylcholine at the sinoatrial node",
    "Performance - Pattern Critical Question",
    "performance"
);

// Test 3: Enhanced Student Feedback Tests
echo "\n\033[1m== ENHANCED STUDENT FEEDBACK TESTS ==\033[0m\n";

// Anatomy feedback
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "The ulnar nerve runs medial to the brachial artery",
    "The ulnar nerve runs posterior to the medial epicondyle and is vulnerable to compression at the cubital tunnel",
    "Feedback - Anatomy Domain",
    "feedback"
);

// Physiology feedback
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "Systolic is when the heart contracts, after diastole",
    "During systole, the ventricles contract, forcing blood into the pulmonary artery and aorta, while the atrioventricular valves close to prevent backflow",
    "Feedback - Physiology Domain",
    "feedback"
);

// Biochemistry feedback
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "Glycolysis produces 4 ATP and converts glucose to acetyl-CoA",
    "Glycolysis is an anaerobic process that converts glucose to pyruvate, yielding 2 ATP and 2 NADH molecules per glucose molecule",
    "Feedback - Biochemistry Domain",
    "feedback"
);

// Histology feedback
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "Epithelial tissue forms layers that cover organs",
    "Simple columnar epithelium consists of a single layer of column-shaped cells with nuclei at a similar level near the base, and is found lining the digestive tract where it facilitates absorption and secretion",
    "Feedback - Histology Domain",
    "feedback"
);

// Test 4: Domain-specific tests
echo "\n\033[1m== DOMAIN-SPECIFIC EVALUATION TESTS ==\033[0m\n";

// Anatomy with technical terminology
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "The brachial plexus roots emerge from C5-T1 and form trunks, divisions, cords, and branches",
    "The brachial plexus is formed by the ventral rami of spinal nerves C5 through T1, which form upper, middle, and lower trunks, then anterior and posterior divisions, followed by lateral, posterior, and medial cords, finally giving rise to terminal branches",
    "Domain - Anatomical Terminology",
    "domain"
);

// Physiology with mechanisms
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "Tubular reabsorption occurs through active transport and passive diffusion mechanisms",
    "In the kidneys, tubular reabsorption involves both active transport (for glucose, amino acids, and ions) and passive diffusion (for water and urea), with the majority of filtrate being reabsorbed in the proximal tubule",
    "Domain - Physiological Mechanisms",
    "domain"
);

// Biochemistry pathways
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "The TCA cycle oxidizes acetyl-CoA, generating NADH, FADH2, and CO2",
    "The tricarboxylic acid (TCA) cycle, also known as the Krebs cycle, oxidizes acetyl-CoA derived from carbohydrates, fats, and proteins to generate reducing equivalents (NADH and FADH2), GTP, and carbon dioxide",
    "Domain - Biochemical Pathways",
    "domain"
);

// Histology cell types
$testResults[] = testEnhancedAnswer(
    $evaluationService,
    "Cardiac muscle cells are striated, uninucleated, and connected by intercalated discs",
    "Cardiac muscle tissue consists of striated muscle cells that are uninucleated, branched, and connected by intercalated discs containing gap junctions for electrical coupling and desmosomes for mechanical stability",
    "Domain - Histological Cell Types",
    "domain"
);

// Performance summary
$performanceData = [];
foreach ($testResults as $index => $result) {
    $category = isset($result['result']['courseTopic']) ? $result['result']['courseTopic'] : 'general';
    $key = ($index + 1) . '. ' . substr($result['result']['reason'], 0, 30) . '...';
    $performanceData[$key] = $result['executionTime'];
}

echo "\n\033[1m== PERFORMANCE SUMMARY ==\033[0m\n";
echo "Average execution time: " . number_format(array_sum($performanceData) / count($performanceData), 2) . " ms\n\n";

echo "Test completion times (milliseconds):\n";
foreach ($performanceData as $test => $time) {
    echo str_pad($test, 40) . ": " . number_format($time, 2) . " ms\n";
}

echo "\nAll enhanced evaluation tests completed successfully.\n"; 