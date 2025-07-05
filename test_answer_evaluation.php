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

/**
 * Test different types of answers
 */
function testAnswer($evaluationService, $submittedAnswer, $correctAnswer, $alternatives = [], $threshold = 0.80)
{
    echo "\n--------------------------------------------------\n";
    echo "Submitted answer: \"$submittedAnswer\"\n";
    echo "Correct answer:   \"$correctAnswer\"\n";
    
    if (!empty($alternatives)) {
        echo "Alternative answers: \n";
        foreach ($alternatives as $alt) {
            echo "  - \"$alt\"\n";
        }
    }
    
    echo "Similarity threshold: $threshold\n\n";
    
    $result = $evaluationService->evaluateAnswer($submittedAnswer, $correctAnswer, $alternatives, $threshold);
    
    echo "RESULT: " . ($result['isCorrect'] ? "CORRECT" : "INCORRECT") . "\n";
    echo "Similarity score: " . number_format($result['similarity'], 2) . "\n";
    echo "Reason: " . $result['reason'] . "\n";
    echo "Feedback: " . $result['feedback'] . "\n\n";
    
    if (isset($result['metrics'])) {
        echo "Detailed metrics:\n";
        
        if (isset($result['metrics']['levenshtein'])) {
            echo "  - Levenshtein similarity: " . number_format($result['metrics']['levenshtein'], 2) . "\n";
        }
        
        if (isset($result['metrics']['jaccard'])) {
            echo "  - Jaccard similarity: " . number_format($result['metrics']['jaccard'], 2) . "\n";
        }
        
        if (isset($result['metrics']['keyword'])) {
            echo "  - Keyword overlap: " . number_format($result['metrics']['keyword'], 2) . "\n";
        }
        
        if (isset($result['metrics']['conceptual'])) {
            echo "  - Conceptual similarity: " . number_format($result['metrics']['conceptual'], 2) . "\n";
        }
        
        if (isset($result['metrics']['weighted'])) {
            echo "  - Weighted similarity: " . number_format($result['metrics']['weighted'], 2) . "\n";
        }
        
        if (isset($result['rawSimilarity'])) {
            echo "  - Raw similarity: " . number_format($result['rawSimilarity'], 2) . "\n";
        }
        
        if (isset($result['domainBoost'])) {
            echo "  - Domain boost: " . number_format($result['domainBoost'], 2) . "\n";
        }
    }
    
    if (isset($result['missingWords']) && !empty($result['missingWords'])) {
        echo "Missing keywords: " . implode(', ', $result['missingWords']) . "\n";
    }
    
    echo "--------------------------------------------------\n";
}

// Test 1: Exact match
testAnswer(
    $evaluationService,
    'The heart pumps blood throughout the body',
    'The heart pumps blood throughout the body'
);

// Test 2: Similar answer
testAnswer(
    $evaluationService,
    'The heart circulates blood in the body',
    'The heart pumps blood throughout the body'
);

// Test 3: Alternative answer
testAnswer(
    $evaluationService,
    'Blood is pumped by the heart',
    'The heart pumps blood throughout the body',
    ['Blood is pumped by the heart', 'The heart is responsible for blood circulation']
);

// Test 4: Dissimilar answer
testAnswer(
    $evaluationService,
    'The kidneys filter waste from blood',
    'The heart pumps blood throughout the body'
);

// Test 5: Medical domain-specific test - Parasympathetic effects
testAnswer(
    $evaluationService,
    'Parasympathetic stimulation slows down heart rate and increases digestive activity',
    'The parasympathetic nervous system decreases heart rate and enhances digestion'
);

// Test 6: Medical domain-specific test - Sympathetic effects
testAnswer(
    $evaluationService,
    'Sympathetic stimulation increases heart rate and elevates blood pressure',
    'The sympathetic nervous system accelerates heart rate and raises blood pressure'
);

// Test 7: Test with empty answer
testAnswer(
    $evaluationService,
    '',
    'The heart pumps blood throughout the body'
);

// Test 8: Test with different threshold
testAnswer(
    $evaluationService,
    'The heart moves blood through the body',
    'The heart pumps blood throughout the body',
    [],
    0.70 // Lower threshold
);

// Display metrics for two texts
function compareTexts($textProcessor, $text1, $text2) {
    echo "\n--------------------------------------------------\n";
    echo "TEXT COMPARISON\n";
    echo "Text 1: \"$text1\"\n";
    echo "Text 2: \"$text2\"\n\n";
    
    // Normalize texts
    $normalized1 = $textProcessor->normalizeText($text1);
    $normalized2 = $textProcessor->normalizeText($text2);
    
    echo "Normalized Text 1: \"$normalized1\"\n";
    echo "Normalized Text 2: \"$normalized2\"\n\n";
    
    // Calculate similarities
    $levenshtein = $textProcessor->getLevenshteinSimilarity($text1, $text2);
    $jaccard = $textProcessor->getJaccardSimilarity($text1, $text2);
    $keyword = $textProcessor->getKeywordOverlapRatio($text1, $text2);
    $conceptual = $textProcessor->getConceptualSimilarity($text1, $text2);
    
    echo "Similarity Metrics:\n";
    echo "  - Levenshtein similarity: " . number_format($levenshtein, 2) . "\n";
    echo "  - Jaccard similarity: " . number_format($jaccard, 2) . "\n";
    echo "  - Keyword overlap: " . number_format($keyword, 2) . "\n";
    echo "  - Conceptual similarity: " . number_format($conceptual, 2) . "\n";
    
    echo "--------------------------------------------------\n";
}

// Compare some medical texts
compareTexts(
    $textProcessor,
    'HTN can lead to CHF and MI.',
    'Hypertension may cause congestive heart failure and myocardial infarction.'
);

compareTexts(
    $textProcessor,
    'The parasympathetic system slows HR and increases GI activity.',
    'Parasympathetic stimulation decreases heart rate and enhances digestion.'
);

echo "\nDone! All tests completed.\n"; 