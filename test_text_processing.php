<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\TextProcessingService;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create text processing service
$textProcessor = new TextProcessingService();
$textProcessor->setCacheConfig(false); // Disable caching for testing

/**
 * Test text normalization
 */
function testNormalization($textProcessor, $original)
{
    echo "\n--------------------------------------------------\n";
    echo "NORMALIZATION TEST\n";
    echo "Original: \"$original\"\n";
    
    $normalized = $textProcessor->normalizeText($original);
    echo "Normalized: \"$normalized\"\n";
    echo "--------------------------------------------------\n";
}

// Various normalization tests
$testCases = [
    'The HeArt pumps BLOOD!!!',
    'BP is 120/80 mmHg due to HTN.',
    'Pt presents with SOB and DVT.',
    'The   text    has   multiple   spaces.',
    'This text has (parentheses) and [brackets].',
    'HeArt-RaTE & BlOoD-PReSsUrE',
];

foreach ($testCases as $testCase) {
    testNormalization($textProcessor, $testCase);
}

/**
 * Test filler word removal
 */
function testFillerWordRemoval($textProcessor, $original)
{
    echo "\n--------------------------------------------------\n";
    echo "FILLER WORD REMOVAL TEST\n";
    echo "Original: \"$original\"\n";
    
    $withoutFillers = $textProcessor->removeFillerWords($original);
    echo "Without fillers: \"$withoutFillers\"\n";
    echo "--------------------------------------------------\n";
}

// Test filler word removal
$fillerTests = [
    'The heart is the organ that pumps blood',
    'If the patient has been diagnosed with hypertension, they should be treated with medication',
    'It is important to note that the heart has four chambers',
];

foreach ($fillerTests as $test) {
    testFillerWordRemoval($textProcessor, $test);
}

/**
 * Test similarity metrics
 */
function testSimilarityMetrics($textProcessor, $text1, $text2)
{
    echo "\n--------------------------------------------------\n";
    echo "SIMILARITY METRICS TEST\n";
    echo "Text 1: \"$text1\"\n";
    echo "Text 2: \"$text2\"\n\n";
    
    // Calculate similarities
    $levenshtein = $textProcessor->getLevenshteinSimilarity($text1, $text2);
    $jaccard = $textProcessor->getJaccardSimilarity($text1, $text2);
    $keyword = $textProcessor->getKeywordOverlapRatio($text1, $text2);
    $conceptual = $textProcessor->getConceptualSimilarity($text1, $text2);
    $combined = $textProcessor->getCombinedSimilarity($text1, $text2);
    
    echo "Similarity Metrics:\n";
    echo "  - Levenshtein similarity: " . number_format($levenshtein, 2) . "\n";
    echo "  - Jaccard similarity: " . number_format($jaccard, 2) . "\n";
    echo "  - Keyword overlap: " . number_format($keyword, 2) . "\n";
    echo "  - Conceptual similarity: " . number_format($conceptual, 2) . "\n";
    echo "  - Combined similarity: " . number_format($combined, 2) . "\n";
    echo "--------------------------------------------------\n";
}

// Array of pairs to test similarity
$similarityTests = [
    [
        'The heart pumps blood throughout the body',
        'The heart circulates blood in the body'
    ],
    [
        'Hypertension is high blood pressure',
        'HTN refers to elevated BP'
    ],
    [
        'The parasympathetic nervous system decreases heart rate',
        'Parasympathetic stimulation reduces cardiac rate'
    ],
    [
        'Myocardial infarction occurs when blood flow to the heart is blocked',
        'Heart attack happens when coronary arteries are occluded'
    ],
    [
        'Diabetes mellitus is characterized by high blood sugar',
        'DM is a condition of elevated glucose levels'
    ],
    [
        'The lungs are responsible for gas exchange',
        'The kidneys filter waste from the blood'
    ]
];

foreach ($similarityTests as $pair) {
    testSimilarityMetrics($textProcessor, $pair[0], $pair[1]);
}

/**
 * Test concept matching
 */
function testConceptMatching($textProcessor, $text)
{
    echo "\n--------------------------------------------------\n";
    echo "CONCEPT MATCHING TEST\n";
    echo "Text: \"$text\"\n\n";
    
    // Since extractMedicalConcepts is protected, we can only test through public methods
    // First normalize and tokenize
    $normalized = $textProcessor->normalizeText($text);
    
    // Extract concepts indirectly by comparing with similar/different texts
    $similarText = "The parasympathetic system reduces heart rate";
    $differentText = "The kidneys filter waste from blood";
    
    $similarConceptual = $textProcessor->getConceptualSimilarity($normalized, $similarText);
    $differentConceptual = $textProcessor->getConceptualSimilarity($normalized, $differentText);
    
    echo "Concept matching with similar text: " . number_format($similarConceptual, 2) . "\n";
    echo "Concept matching with different text: " . number_format($differentConceptual, 2) . "\n";
    echo "--------------------------------------------------\n";
}

// Test concept extraction
$conceptTests = [
    'The parasympathetic nervous system slows down heart rate',
    'Sympathetic stimulation increases heart rate and blood pressure',
    'Myocardial infarction is caused by coronary artery occlusion',
    'Hypertension can lead to heart failure and stroke'
];

foreach ($conceptTests as $conceptTest) {
    testConceptMatching($textProcessor, $conceptTest);
}

echo "\nDone! All text processing tests completed.\n"; 