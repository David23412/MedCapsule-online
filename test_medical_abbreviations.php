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
 * Test function
 */
function testMedicalAbbreviations($evaluationService, $textProcessor, $submittedAnswer, $correctAnswer, $title) {
    echo "\n==================================================================\n";
    echo "TEST: " . $title . "\n";
    echo "==================================================================\n";
    echo "SUBMITTED: \"$submittedAnswer\"\n";
    echo "CORRECT:   \"$correctAnswer\"\n\n";

    // Get normalized forms
    $normalizedSubmitted = $textProcessor->normalizeText($submittedAnswer);
    $normalizedCorrect = $textProcessor->normalizeText($correctAnswer);
    
    echo "NORMALIZED SUBMITTED: \"$normalizedSubmitted\"\n";
    echo "NORMALIZED CORRECT:   \"$normalizedCorrect\"\n\n";
    
    // Evaluate the answer
    $result = $evaluationService->evaluateAnswer($submittedAnswer, $correctAnswer);
    
    echo "RESULT: " . ($result['isCorrect'] ? "\033[32mCORRECT" : "\033[31mINCORRECT") . "\033[0m\n";
    echo "SIMILARITY: " . number_format($result['similarity'] * 100, 2) . "%\n";
    echo "REASON: " . $result['reason'] . "\n\n";
    
    if (isset($result['metrics'])) {
        echo "METRICS:\n";
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
    
    echo "==================================================================\n";
}

// Test cases for medical abbreviations
echo "\nMEDICAL ABBREVIATION EVALUATION TESTS\n";

// Test case 1: Basic blood pressure abbreviation
testMedicalAbbreviations(
    $evaluationService,
    $textProcessor,
    "The BP should be measured regularly",
    "The blood pressure should be measured regularly",
    "Blood Pressure Abbreviation"
);

// Test case 2: Multiple abbreviations
testMedicalAbbreviations(
    $evaluationService,
    $textProcessor,
    "HTN can lead to CHF, MI, and CVA if untreated",
    "Hypertension can lead to congestive heart failure, myocardial infarction, and cerebrovascular accident if untreated",
    "Multiple Cardiovascular Abbreviations"
);

// Test case 3: Parasympathetic nervous system - pattern critical
testMedicalAbbreviations(
    $evaluationService,
    $textProcessor,
    "The PNS decreases HR and increases GI activity",
    "The parasympathetic nervous system decreases heart rate and increases digestive activity",
    "Parasympathetic Nervous System Abbreviation"
);

// Test case 4: Sympathetic nervous system - pattern critical
testMedicalAbbreviations(
    $evaluationService,
    $textProcessor,
    "SNS activation causes ↑ HR, ↑ BP, and mydriasis",
    "Sympathetic nervous system activation causes increased heart rate, elevated blood pressure, and pupil dilation",
    "Sympathetic Nervous System with Symbols"
);

// Test case 5: Respiratory abbreviations
testMedicalAbbreviations(
    $evaluationService,
    $textProcessor,
    "COPD pts often have SOB and ↓ O2 sats",
    "Chronic obstructive pulmonary disease patients often have shortness of breath and decreased oxygen saturation",
    "Respiratory Abbreviations"
);

// Test case 6: Medication abbreviations
testMedicalAbbreviations(
    $evaluationService,
    $textProcessor,
    "Take 1 tab PO QID PRN for pain",
    "Take 1 tablet by mouth four times daily as needed for pain",
    "Medication Administration Abbreviations"
);

// Test case 7: Abbreviations with incorrect concepts (should be marked wrong)
testMedicalAbbreviations(
    $evaluationService,
    $textProcessor,
    "SNS activation causes ↓ HR and ↑ GI motility",
    "Sympathetic nervous system activation causes increased heart rate and decreased GI motility",
    "Abbreviations with Incorrect Concepts"
);

// Test case 8: Mixed abbreviation and complete terms
testMedicalAbbreviations(
    $evaluationService,
    $textProcessor,
    "ACE inhibitors reduce BP in patients with HTN and CHF",
    "Angiotensin-converting enzyme inhibitors reduce blood pressure in patients with hypertension and congestive heart failure",
    "Mixed Abbreviation and Complete Terms"
);

echo "\nAll medical abbreviation tests completed.\n"; 