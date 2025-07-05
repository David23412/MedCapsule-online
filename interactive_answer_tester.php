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

// Turn off caching for interactive testing
$textProcessor->setCacheConfig(false);
$patternMatcher->setCacheConfig(false);
$evaluationService->setCacheConfig(false);

/**
 * Function to evaluate and display answer results
 */
function evaluateAndDisplay($evaluationService, $submittedAnswer, $correctAnswer, $alternatives = [], $threshold = 0.80)
{
    echo "\n============================================================\n";
    echo "SUBMITTED ANSWER: \"$submittedAnswer\"\n";
    echo "CORRECT ANSWER:   \"$correctAnswer\"\n";
    
    if (!empty($alternatives)) {
        echo "ALTERNATIVE ANSWERS: \n";
        foreach ($alternatives as $alt) {
            echo "  - \"$alt\"\n";
        }
    }
    
    echo "THRESHOLD: $threshold\n";
    echo "============================================================\n\n";
    
    $result = $evaluationService->evaluateAnswer($submittedAnswer, $correctAnswer, $alternatives, $threshold);
    
    echo "\033[1mEVALUATION RESULT: " . ($result['isCorrect'] ? "\033[32mCORRECT" : "\033[31mINCORRECT") . "\033[0m\n\n";
    echo "Similarity Score: " . number_format($result['similarity'] * 100, 1) . "%\n";
    echo "Decision Reason: " . $result['reason'] . "\n";
    echo "Feedback: " . $result['feedback'] . "\n\n";
    
    if (isset($result['metrics'])) {
        echo "\033[1m== DETAILED METRICS ==\033[0m\n";
        
        if (isset($result['metrics']['levenshtein'])) {
            echo "  • Levenshtein similarity: " . number_format($result['metrics']['levenshtein'] * 100, 1) . "%\n";
        }
        
        if (isset($result['metrics']['jaccard'])) {
            echo "  • Jaccard similarity: " . number_format($result['metrics']['jaccard'] * 100, 1) . "%\n";
        }
        
        if (isset($result['metrics']['keyword'])) {
            echo "  • Keyword overlap: " . number_format($result['metrics']['keyword'] * 100, 1) . "%\n";
        }
        
        if (isset($result['metrics']['conceptual'])) {
            echo "  • Conceptual similarity: " . number_format($result['metrics']['conceptual'] * 100, 1) . "%\n";
        }
        
        if (isset($result['metrics']['weighted'])) {
            echo "  • Weighted similarity: " . number_format($result['metrics']['weighted'] * 100, 1) . "%\n";
        }
        
        if (isset($result['metrics']['pattern']) && isset($result['metrics']['pattern']['pattern_similarity'])) {
            echo "  • Pattern similarity: " . number_format($result['metrics']['pattern']['pattern_similarity'] * 100, 1) . "%\n";
        }
        
        // Display any pattern matches
        if (isset($result['metrics']['pattern']) && isset($result['metrics']['pattern']['matching_patterns']) && !empty($result['metrics']['pattern']['matching_patterns'])) {
            echo "\n  Matching patterns: " . implode(', ', array_keys($result['metrics']['pattern']['matching_patterns'])) . "\n";
        }
    }
    
    if (isset($result['rawSimilarity'])) {
        echo "\n  Raw similarity: " . number_format($result['rawSimilarity'] * 100, 1) . "%\n";
    }
    
    if (isset($result['domainBoost']) && $result['domainBoost'] > 0) {
        echo "  Domain context boost: " . number_format($result['domainBoost'] * 100, 1) . "%\n";
    }
    
    if (isset($result['missingWords']) && !empty($result['missingWords'])) {
        echo "\n\033[1m== MISSING KEYWORDS ==\033[0m\n";
        echo "  " . implode(', ', $result['missingWords']) . "\n";
    }
    
    echo "\n============================================================\n";
}

/**
 * Sample medical domain questions for testing
 */
$sampleQuestions = [
    1 => [
        'question' => 'Describe the function of the heart.',
        'answer' => 'The heart pumps blood throughout the body, delivering oxygen and nutrients to tissues and removing waste products.',
        'alternatives' => [
            'The heart is responsible for blood circulation throughout the body.',
            'The heart is a muscular organ that circulates blood through the cardiovascular system.'
        ]
    ],
    2 => [
        'question' => 'What are the effects of parasympathetic nervous system activation?',
        'answer' => 'The parasympathetic nervous system decreases heart rate, increases digestive activity, and constricts pupils.',
        'alternatives' => [
            'Parasympathetic stimulation slows the heart, enhances digestion, and causes pupillary constriction.',
            'The PNS has effects including bradycardia, increased GI motility, and miosis.'
        ]
    ],
    3 => [
        'question' => 'What are the effects of sympathetic nervous system activation?',
        'answer' => 'The sympathetic nervous system increases heart rate, elevates blood pressure, dilates pupils, and decreases digestive activity.',
        'alternatives' => [
            'Sympathetic stimulation causes tachycardia, hypertension, mydriasis, and reduced GI motility.',
            'The SNS accelerates the heart, raises BP, dilates pupils, and inhibits digestion.'
        ]
    ],
    4 => [
        'question' => 'What is myocardial infarction and what causes it?',
        'answer' => 'Myocardial infarction, commonly known as heart attack, is caused by coronary artery occlusion leading to necrosis of heart muscle due to inadequate blood supply.',
        'alternatives' => [
            'A heart attack occurs when blood flow to the heart is blocked, causing death of cardiac tissue.',
            'MI happens when coronary arteries are blocked, resulting in cardiac tissue death from oxygen deprivation.'
        ]
    ],
    5 => [
        'question' => 'What is hypertension and what complications can it lead to?',
        'answer' => 'Hypertension is chronically elevated blood pressure that can lead to heart failure, stroke, kidney damage, and vision problems.',
        'alternatives' => [
            'High blood pressure, if untreated, increases risk of cardiac failure, cerebrovascular accidents, renal damage, and retinopathy.',
            'HTN is sustained high BP that may cause heart, brain, kidney, and eye damage over time.'
        ]
    ]
];

// Define some test answer variations for each question
$testAnswers = [
    1 => [
        'The heart pumps blood around the body',
        'Heart is the organ that circulates the blood',
        'The heart is a muscular organ that pumps blood',
        'The lungs oxygenate blood',
        'The heart distributes oxygen-rich blood to tissues'
    ],
    2 => [
        'Parasympathetic stimulation decreases heart rate and increases digestion',
        'The PNS slows down heart rate and enhances GI activity',
        'Parasympathetic effects include lower heart rate and increased digestive function',
        'The parasympathetic system causes bradycardia and improved digestion',
        'The parasympathetic decreases HR and dilates pupils'
    ],
    3 => [
        'Sympathetic stimulation increases heart rate and blood pressure',
        'The SNS accelerates heart rate and elevates BP',
        'Sympathetic effects include tachycardia, hypertension, and mydriasis',
        'The sympathetic system increases HR, BP, and dilates pupils',
        'Sympathetic stimulation speeds up the heart'
    ],
    4 => [
        'A heart attack is when coronary arteries get blocked',
        'MI occurs due to blockage of blood flow to heart tissue',
        'Myocardial infarction happens when heart muscle dies due to lack of oxygen',
        'Heart attack is caused by coronary artery occlusion',
        'Chest pain occurs during an MI'
    ],
    5 => [
        'High blood pressure can cause heart failure and stroke',
        'HTN increases risk of CVA and heart problems',
        'Hypertension damages organs like heart, kidneys, and brain',
        'Long-term high BP damages blood vessels',
        'Headaches and dizziness are symptoms of hypertension'
    ]
];

/**
 * Interactive menu
 */
function showMenu() {
    echo "\n===== WRITTEN ANSWER EVALUATION TESTER =====\n\n";
    echo "1. Test with sample questions\n";
    echo "2. Enter custom question/answer\n";
    echo "3. Compare two answers\n";
    echo "4. Exit\n\n";
    echo "Enter your choice (1-4): ";
    $choice = trim(fgets(STDIN));
    return $choice;
}

/**
 * Main program loop
 */
while (true) {
    $choice = showMenu();
    
    if ($choice == '1') {
        // Show sample questions
        echo "\n===== SAMPLE QUESTIONS =====\n\n";
        foreach ($sampleQuestions as $id => $questionData) {
            echo "$id. {$questionData['question']}\n";
        }
        
        echo "\nSelect a question (1-" . count($sampleQuestions) . "): ";
        $questionId = trim(fgets(STDIN));
        
        if (!isset($sampleQuestions[$questionId])) {
            echo "\nInvalid question ID!\n";
            continue;
        }
        
        $question = $sampleQuestions[$questionId];
        
        echo "\n===== TEST ANSWERS =====\n\n";
        foreach ($testAnswers[$questionId] as $idx => $answer) {
            echo ($idx + 1) . ". $answer\n";
        }
        echo "\nSelect a test answer or enter 0 to write your own: ";
        $answerChoice = trim(fgets(STDIN));
        
        if ($answerChoice == '0') {
            echo "\nEnter your answer: ";
            $submittedAnswer = trim(fgets(STDIN));
        } else {
            $answerIndex = $answerChoice - 1;
            if (!isset($testAnswers[$questionId][$answerIndex])) {
                echo "\nInvalid answer choice!\n";
                continue;
            }
            $submittedAnswer = $testAnswers[$questionId][$answerIndex];
        }
        
        echo "\nEnter similarity threshold (0.0-1.0) or press enter for default (0.8): ";
        $threshold = trim(fgets(STDIN));
        if (empty($threshold)) {
            $threshold = 0.8;
        } else {
            $threshold = (float)$threshold;
        }
        
        evaluateAndDisplay(
            $evaluationService,
            $submittedAnswer,
            $question['answer'],
            $question['alternatives'],
            $threshold
        );
        
    } else if ($choice == '2') {
        // Custom question/answer
        echo "\nEnter the question: ";
        $questionText = trim(fgets(STDIN));
        
        echo "Enter the correct answer: ";
        $correctAnswer = trim(fgets(STDIN));
        
        $alternatives = [];
        $addMore = true;
        while ($addMore) {
            echo "Enter an alternative answer (or press enter to continue): ";
            $alt = trim(fgets(STDIN));
            if (empty($alt)) {
                $addMore = false;
            } else {
                $alternatives[] = $alt;
            }
        }
        
        echo "Enter your submitted answer: ";
        $submittedAnswer = trim(fgets(STDIN));
        
        echo "Enter similarity threshold (0.0-1.0) or press enter for default (0.8): ";
        $threshold = trim(fgets(STDIN));
        if (empty($threshold)) {
            $threshold = 0.8;
        } else {
            $threshold = (float)$threshold;
        }
        
        evaluateAndDisplay(
            $evaluationService,
            $submittedAnswer,
            $correctAnswer,
            $alternatives,
            $threshold
        );
        
    } else if ($choice == '3') {
        // Compare two texts
        echo "\nEnter first text: ";
        $text1 = trim(fgets(STDIN));
        
        echo "Enter second text: ";
        $text2 = trim(fgets(STDIN));
        
        echo "\n============================================================\n";
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
        $combined = $textProcessor->getCombinedSimilarity($text1, $text2);
        
        echo "Similarity Metrics:\n";
        echo "  • Levenshtein similarity: " . number_format($levenshtein * 100, 1) . "%\n";
        echo "  • Jaccard similarity: " . number_format($jaccard * 100, 1) . "%\n";
        echo "  • Keyword overlap: " . number_format($keyword * 100, 1) . "%\n";
        echo "  • Conceptual similarity: " . number_format($conceptual * 100, 1) . "%\n";
        echo "  • Combined similarity: " . number_format($combined * 100, 1) . "%\n";
        
        // Get patterns
        $patterns1 = $patternMatcher->identifyPatterns($text1);
        $patterns2 = $patternMatcher->identifyPatterns($text2);
        
        if (!empty($patterns1)) {
            echo "\nPatterns identified in Text 1:\n";
            foreach ($patterns1 as $pattern => $description) {
                echo "  • $pattern\n";
            }
        }
        
        if (!empty($patterns2)) {
            echo "\nPatterns identified in Text 2:\n";
            foreach ($patterns2 as $pattern => $description) {
                echo "  • $pattern\n";
            }
        }
        
        echo "\n============================================================\n";
        
    } else if ($choice == '4') {
        // Exit
        echo "\nGoodbye!\n";
        exit(0);
    } else {
        echo "\nInvalid choice! Please try again.\n";
    }
} 