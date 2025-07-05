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

/**
 * Test function to evaluate an answer and display results
 */
function testConservativeEvaluation($evaluationService, $submittedAnswer, $correctAnswer, $testName, $alternatives = [], $threshold = 0.85)
{
    echo "\n=================================================================\n";
    echo "TEST CASE: " . $testName . "\n";
    echo "=================================================================\n";
    echo "SUBMITTED: \"$submittedAnswer\"\n";
    echo "CORRECT:   \"$correctAnswer\"\n";
    
    if (!empty($alternatives)) {
        echo "ALTERNATIVES: \n";
        foreach ($alternatives as $alt) {
            echo "  - \"$alt\"\n";
        }
    }
    
    echo "THRESHOLD: $threshold\n\n";
    
    $result = $evaluationService->evaluateAnswer($submittedAnswer, $correctAnswer, $alternatives, $threshold);
    
    echo "RESULT: " . ($result['isCorrect'] ? "\033[32mCORRECT" : "\033[31mINCORRECT") . "\033[0m\n";
    echo "SIMILARITY: " . number_format($result['similarity'] * 100, 2) . "%\n";
    echo "REASON: " . $result['reason'] . "\n";
    echo "FEEDBACK: " . $result['feedback'] . "\n\n";
    
    if (isset($result['metrics'])) {
        echo "DETAILED METRICS:\n";
        
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
        
        if (isset($result['metrics']['weighted'])) {
            echo "  • Weighted similarity: " . number_format($result['metrics']['weighted'] * 100, 2) . "%\n";
        }
        
        if (isset($result['metrics']['hasContradictions'])) {
            echo "  • Has contradictions: " . ($result['metrics']['hasContradictions'] ? "YES" : "No") . "\n";
        }
    }
    
    if (isset($result['rawSimilarity'])) {
        echo "  • Raw similarity: " . number_format($result['rawSimilarity'] * 100, 2) . "%\n";
    }
    
    if (isset($result['domainBoost'])) {
        echo "  • Domain boost: " . number_format($result['domainBoost'] * 100, 2) . "%\n";
    }
    
    if (isset($result['missingWords']) && !empty($result['missingWords'])) {
        echo "\nMISSING KEYWORDS: " . implode(', ', $result['missingWords']) . "\n";
    }
    
    echo "=================================================================\n";
}

// Test cases designed to test the conservative evaluation logic
echo "\nCONSERVATIVE WRITTEN ANSWER EVALUATION TESTS\n";

// Test 1: Basic correct answer (should be marked correct)
testConservativeEvaluation(
    $evaluationService,
    'The heart pumps blood throughout the body',
    'The heart pumps blood throughout the body',
    'Exact Match (should be correct)'
);

// Test 2: Similar answer without contradictions (should be correct if above threshold)
testConservativeEvaluation(
    $evaluationService,
    'The heart circulates blood to all parts of the body',
    'The heart pumps blood throughout the body',
    'Similar Answer Without Contradictions'
);

// Test 3: Answer with missing important concepts (should be marked incorrect)
testConservativeEvaluation(
    $evaluationService,
    'The organ moves fluid in the body',
    'The heart pumps blood throughout the body',
    'Answer Missing Key Concepts'
);

// Test 4: Answer with contradictions (should be marked incorrect regardless of similarity)
testConservativeEvaluation(
    $evaluationService,
    'Parasympathetic stimulation increases heart rate and enhances digestion',
    'Parasympathetic stimulation decreases heart rate and enhances digestion',
    'Answer With Medical Contradictions (increase vs decrease)'
);

// Test 5: Answer with contradictory concepts about parasympathetic/sympathetic
testConservativeEvaluation(
    $evaluationService,
    'The sympathetic nervous system decreases heart rate and increases digestion',
    'The sympathetic nervous system increases heart rate and decreases digestion',
    'Answer With System Contradictions (sympathetic having parasympathetic effects)'
);

// Test 6: Borderline answer that's close to threshold (should err on side of caution)
testConservativeEvaluation(
    $evaluationService,
    'The heart moves blood in the circulatory system',
    'The heart pumps blood throughout the body',
    'Borderline Answer (close to threshold)',
    [],
    0.86 // Set threshold so the answer is just below it
);

// Test 7: Answer with typos but correct concepts (should be lenient on typos)
testConservativeEvaluation(
    $evaluationService,
    'The haert pumps blod throughout the boody',
    'The heart pumps blood throughout the body',
    'Answer With Typos But Correct Concepts'
);

// Test 8: Answer that's completely off-topic (should be marked incorrect)
testConservativeEvaluation(
    $evaluationService,
    'The lungs exchange oxygen and carbon dioxide',
    'The heart pumps blood throughout the body',
    'Completely Off-Topic Answer'
);

// Test 9: Answer with alternative wording that's conceptually correct
testConservativeEvaluation(
    $evaluationService,
    'The heart is responsible for circulating blood throughout the entire body',
    'The heart pumps blood throughout the body',
    'Alternative Wording - Conceptually Correct'
);

// Test 10: Answer with medical abbreviations
testConservativeEvaluation(
    $evaluationService,
    'The PNS decreases HR and increases GI activity',
    'The parasympathetic nervous system decreases heart rate and increases digestive activity',
    'Medical Abbreviations Answer'
);

// Test 11: Very brief answer missing details
testConservativeEvaluation(
    $evaluationService,
    'Pumps blood',
    'The heart pumps blood throughout the body',
    'Very Brief Answer Missing Details'
);

// Test 12: Answer with extra irrelevant information
testConservativeEvaluation(
    $evaluationService,
    'The heart, which is made of cardiac muscle tissue and has four chambers, pumps blood throughout the body and beats approximately 70 times per minute in adults',
    'The heart pumps blood throughout the body',
    'Answer With Extra Information'
);

echo "\nAll tests completed.\n"; 