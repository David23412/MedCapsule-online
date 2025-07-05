<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\PatternMatcherService;
use App\Services\TextProcessingService;
use App\Services\WrittenAnswerEvaluationService;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create services - with caching disabled for predictable test behavior
$textProcessor = new TextProcessingService();
$patternMatcher = new PatternMatcherService();
$evaluationService = new WrittenAnswerEvaluationService($textProcessor, $patternMatcher);

// Turn off caching for testing purposes
$textProcessor->setCacheConfig(false);
$patternMatcher->setCacheConfig(false);
$evaluationService->setCacheConfig(false);

/**
 * Test function to evaluate answers with a focus on reliability
 * This is designed specifically to verify that incorrect answers are not graded as correct
 */
function testReliability($evaluationService, $submittedAnswer, $correctAnswer, $testName, $explanation, $expectedToBeCorrect = false, $threshold = 0.85)
{
    echo "\n===================================================================\n";
    echo "RELIABILITY TEST: " . $testName . "\n";
    echo "===================================================================\n";
    echo "SUBMITTED: \"$submittedAnswer\"\n";
    echo "CORRECT:   \"$correctAnswer\"\n";
    echo "EXPLANATION: $explanation\n";
    echo "EXPECTED RESULT: " . ($expectedToBeCorrect ? "Should be CORRECT" : "Should be INCORRECT") . "\n\n";
    
    $result = $evaluationService->evaluateAnswer($submittedAnswer, $correctAnswer, [], $threshold);
    
    echo "ACTUAL RESULT: " . ($result['isCorrect'] ? "\033[32mCORRECT" : "\033[31mINCORRECT") . "\033[0m\n";
    echo "SIMILARITY: " . number_format($result['similarity'] * 100, 2) . "%\n";
    echo "THRESHOLD: " . number_format($threshold * 100, 2) . "%\n";
    echo "REASON: " . $result['reason'] . "\n\n";
    
    if (isset($result['courseTopic'])) {
        echo "DETECTED COURSE TOPIC: " . $result['courseTopic'] . "\n";
    }
    
    if (isset($result['metrics'])) {
        echo "KEY METRICS:\n";
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
    
    // Check if the result matches our expectations
    $passedTest = ($result['isCorrect'] === $expectedToBeCorrect);
    
    echo "\nTEST OUTCOME: " . ($passedTest ? "\033[32mPASSED" : "\033[31mFAILED") . "\033[0m - " . 
         ($passedTest ? "System correctly " . ($expectedToBeCorrect ? "accepted" : "rejected") . " the answer" : 
                       "System incorrectly " . ($expectedToBeCorrect ? "rejected" : "accepted") . " the answer") . "\n";
    
    echo "===================================================================\n";
    
    return $passedTest;
}

echo "\n\033[1mRELIABILITY EVALUATION TESTS - FOCUSED ON PREVENTING FALSE POSITIVES\033[0m\n";
echo "\nThis test suite focuses on verifying that incorrect answers are properly identified\n";
echo "and not incorrectly marked as correct.\n";

$totalTests = 0;
$passedTests = 0;

// ===== CATEGORY 1: Subtle Contradictions =====
echo "\n\033[1m===== CATEGORY 1: SUBTLE CONTRADICTIONS =====\033[0m\n";

// Test 1.1: Parasympathetic with contradictory heart rate effect
$totalTests++;
$result = testReliability(
    $evaluationService,
    "The parasympathetic nervous system increases heart rate and increases digestive activity",
    "The parasympathetic nervous system decreases heart rate and increases digestive activity",
    "Parasympathetic Contradiction - Heart Rate",
    "Answer contains a subtle contradiction - parasympathetic increases rather than decreases heart rate",
    false
);
$passedTests += $result ? 1 : 0;

// Test 1.2: Sympathetic with contradictory digestion effect
$totalTests++;
$result = testReliability(
    $evaluationService,
    "The sympathetic nervous system increases heart rate and increases digestive activity",
    "The sympathetic nervous system increases heart rate and decreases digestive activity",
    "Sympathetic Contradiction - Digestion",
    "Answer contains a subtle contradiction - sympathetic increases rather than decreases digestion",
    false
);
$passedTests += $result ? 1 : 0;

// Test 1.3: Reversed cardiac cycle phases
$totalTests++;
$result = testReliability(
    $evaluationService,
    "During cardiac diastole, the ventricles contract and eject blood into the aorta and pulmonary trunk",
    "During cardiac systole, the ventricles contract and eject blood into the aorta and pulmonary trunk",
    "Cardiac Cycle Phase Reversal",
    "Answer incorrectly states diastole is when ventricles contract (should be systole)",
    false
);
$passedTests += $result ? 1 : 0;

// Test 1.4: Reversed filtration direction
$totalTests++;
$result = testReliability(
    $evaluationService,
    "In glomerular filtration, blood passes from Bowman's capsule into the glomerular capillaries",
    "In glomerular filtration, blood components pass from glomerular capillaries into Bowman's capsule",
    "Reversed Filtration Direction",
    "Answer reverses the direction of filtration (from Bowman's to capillaries instead of vice versa)",
    false
);
$passedTests += $result ? 1 : 0;

// ===== CATEGORY 2: Partial Information with Missing Critical Concepts =====
echo "\n\033[1m===== CATEGORY 2: PARTIAL INFORMATION WITH MISSING CRITICAL CONCEPTS =====\033[0m\n";

// Test 2.1: Enzyme function missing substrate
$totalTests++;
$result = testReliability(
    $evaluationService,
    "DNA polymerase functions in DNA replication",
    "DNA polymerase catalyzes the addition of nucleotides to the growing DNA strand during replication, using the template strand as a guide",
    "Enzyme Function - Missing Mechanism",
    "Answer states the enzyme but fails to explain its critical mechanism",
    false
);
$passedTests += $result ? 1 : 0;

// Test 2.2: Bone structure missing key components
$totalTests++;
$result = testReliability(
    $evaluationService,
    "Haversian systems are found in compact bone",
    "Haversian systems (osteons) in compact bone consist of concentric lamellae surrounding a central Haversian canal containing blood vessels and nerves",
    "Bone Structure - Missing Components",
    "Answer identifies location but misses critical structural components",
    false
);
$passedTests += $result ? 1 : 0;

// Test 2.3: Heart function without circulation
$totalTests++;
$result = testReliability(
    $evaluationService,
    "The heart is a muscular organ located in the thoracic cavity",
    "The heart pumps blood throughout the body, delivering oxygen and nutrients to tissues and removing waste products",
    "Heart Function - Missing Function",
    "Answer describes location but completely misses the actual function",
    false
);
$passedTests += $result ? 1 : 0;

// Test 2.4: TCA cycle without key products
$totalTests++;
$result = testReliability(
    $evaluationService,
    "The TCA cycle occurs in the mitochondrial matrix",
    "The TCA cycle occurs in the mitochondrial matrix and produces NADH, FADH2, and CO2 while oxidizing acetyl-CoA",
    "TCA Cycle - Missing Products",
    "Answer identifies location but misses critical products of the pathway",
    false
);
$passedTests += $result ? 1 : 0;

// ===== CATEGORY 3: Correct but Vague Answers =====
echo "\n\033[1m===== CATEGORY 3: CORRECT BUT VAGUE ANSWERS =====\033[0m\n";

// Test 3.1: Vague heart description (borderline case, likely should pass)
$totalTests++;
$result = testReliability(
    $evaluationService,
    "The heart circulates blood in the body",
    "The heart pumps blood throughout the body, delivering oxygen and nutrients to tissues and removing waste products",
    "Heart Function - Vague but Correct",
    "Answer is technically correct but vague (borderline case that should pass)",
    true
);
$passedTests += $result ? 1 : 0;

// Test 3.2: Vague neurotransmitter function (borderline case)
$totalTests++;
$result = testReliability(
    $evaluationService,
    "Acetylcholine is involved in neural signaling",
    "Acetylcholine is a neurotransmitter that binds to nicotinic and muscarinic receptors, mediating synaptic transmission at neuromuscular junctions and in the parasympathetic nervous system",
    "Neurotransmitter Function - Vague",
    "Answer is technically correct but misses specific roles and mechanisms",
    false
);
$passedTests += $result ? 1 : 0;

// ===== CATEGORY 4: High Lexical Similarity but Wrong Concepts =====
echo "\n\033[1m===== CATEGORY 4: HIGH LEXICAL SIMILARITY BUT WRONG CONCEPTS =====\033[0m\n";

// Test 4.1: Similar wording but wrong site
$totalTests++;
$result = testReliability(
    $evaluationService,
    "Protein synthesis occurs in the mitochondria, where amino acids are assembled into protein chains according to mRNA templates",
    "Protein synthesis occurs in the ribosomes, where amino acids are assembled into protein chains according to mRNA templates",
    "Protein Synthesis - Wrong Location",
    "Answer has high lexical similarity but incorrectly states location (mitochondria vs. ribosomes)",
    false
);
$passedTests += $result ? 1 : 0;

// Test 4.2: Similar wording but wrong direction
$totalTests++;
$result = testReliability(
    $evaluationService,
    "In the nephron, blood is filtered as it moves from the distal tubule to the glomerulus",
    "In the nephron, blood is filtered as it moves through the glomerulus into Bowman's capsule and then the proximal tubule",
    "Nephron Function - Wrong Direction",
    "Answer has similar wording but describes completely incorrect direction of flow",
    false
);
$passedTests += $result ? 1 : 0;

// ===== CATEGORY 5: Answers Using Medical Abbreviations =====
echo "\n\033[1m===== CATEGORY 5: ANSWERS USING MEDICAL ABBREVIATIONS =====\033[0m\n";

// Test 5.1: Correct use of abbreviations (should pass)
$totalTests++;
$result = testReliability(
    $evaluationService,
    "The SNS increases HR, dilates pupils, and inhibits GI motility",
    "The sympathetic nervous system increases heart rate, dilates pupils, and decreases gastrointestinal activity",
    "Correct Abbreviation Usage",
    "Answer correctly uses standard medical abbreviations and should be recognized",
    true
);
$passedTests += $result ? 1 : 0;

// Test 5.2: Abbreviations with wrong concepts
$totalTests++;
$result = testReliability(
    $evaluationService,
    "The PNS increases HR and inhibits GI motility",
    "The parasympathetic nervous system decreases heart rate and increases gastrointestinal activity",
    "Abbreviations with Wrong Concepts",
    "Answer uses correct abbreviations but describes wrong physiological effects",
    false
);
$passedTests += $result ? 1 : 0;

// ===== CATEGORY 6: Different Correct Answers (Should Recognize Alternative Phrasings) =====
echo "\n\033[1m===== CATEGORY 6: DIFFERENT CORRECT ANSWERS (SHOULD RECOGNIZE ALTERNATIVE PHRASINGS) =====\033[0m\n";

// Test 6.1: Passive voice construction (should pass)
$totalTests++;
$result = testReliability(
    $evaluationService,
    "Blood is pumped throughout the body by the heart, which delivers nutrients to tissues",
    "The heart pumps blood throughout the body, delivering oxygen and nutrients to tissues and removing waste products",
    "Passive Voice Construction",
    "Answer uses passive voice but conveys the same information correctly",
    true
);
$passedTests += $result ? 1 : 0;

// Test 6.2: Different but correct phrasing (should pass)
$totalTests++;
$result = testReliability(
    $evaluationService,
    "The cardiac muscle contracts rhythmically to propel blood through the circulatory system, supplying oxygen and nutrients to body tissues",
    "The heart pumps blood throughout the body, delivering oxygen and nutrients to tissues and removing waste products",
    "Alternative Correct Phrasing",
    "Answer uses different but medically correct terminology to convey the same concept",
    true
);
$passedTests += $result ? 1 : 0;

// ===== CATEGORY 7: Completely Different Domain Answers =====
echo "\n\033[1m===== CATEGORY 7: COMPLETELY DIFFERENT DOMAIN ANSWERS =====\033[0m\n";

// Test 7.1: Wrong system entirely
$totalTests++;
$result = testReliability(
    $evaluationService,
    "The respiratory system exchanges oxygen and carbon dioxide between the air and blood",
    "The heart pumps blood throughout the body, delivering oxygen and nutrients to tissues and removing waste products",
    "Wrong System - Respiratory vs Cardiovascular",
    "Answer describes a completely different body system",
    false
);
$passedTests += $result ? 1 : 0;

// Test 7.2: Wrong scientific field
$totalTests++;
$result = testReliability(
    $evaluationService,
    "In the Krebs cycle, acetyl-CoA is oxidized to generate NADH, FADH2, and ATP",
    "DNA replication involves unwinding of the double helix and synthesis of complementary strands by DNA polymerase",
    "Wrong Scientific Field - Metabolism vs Genetics",
    "Answer describes a completely different biological process from a different field",
    false
);
$passedTests += $result ? 1 : 0;

// ===== SUMMARY =====
echo "\n\033[1m===== RELIABILITY TEST SUMMARY =====\033[0m\n";
echo "Total tests run: $totalTests\n";
echo "Tests passed: $passedTests\n";
echo "Success rate: " . number_format(($passedTests / $totalTests) * 100, 2) . "%\n";

if ($passedTests == $totalTests) {
    echo "\n\033[32mALL RELIABILITY TESTS PASSED!\033[0m The system correctly identifies incorrect answers and does not grade them as correct.\n";
} else {
    echo "\n\033[31mSOME RELIABILITY TESTS FAILED.\033[0m The system needs improvement to reliably identify all incorrect answers.\n";
    echo "Please review the test results to identify areas for improvement.\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n"; 