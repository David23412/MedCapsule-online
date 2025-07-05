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
 * Test function to evaluate an answer with detailed output
 */
function testDisciplinaryAnswer($evaluationService, $submittedAnswer, $correctAnswer, $testName, $course, $alternatives = [], $threshold = 0.85)
{
    echo "\n=================================================================\n";
    echo "TEST: " . $testName . "\n";
    echo "COURSE: " . strtoupper($course) . "\n";
    echo "=================================================================\n";
    echo "SUBMITTED: \"$submittedAnswer\"\n";
    echo "CORRECT:   \"$correctAnswer\"\n";
    
    if (!empty($alternatives)) {
        echo "ALTERNATIVES: \n";
        foreach ($alternatives as $alt) {
            echo "  - \"$alt\"\n";
        }
    }
    
    $result = $evaluationService->evaluateAnswer($submittedAnswer, $correctAnswer, $alternatives, $threshold);
    
    echo "\nRESULT: " . ($result['isCorrect'] ? "\033[32mCORRECT" : "\033[31mINCORRECT") . "\033[0m\n";
    echo "SIMILARITY: " . number_format($result['similarity'] * 100, 2) . "%\n";
    echo "DETECTED COURSE TOPIC: " . ($result['courseTopic'] ?? 'Not detected') . "\n";
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
    
    if (isset($result['domainBoost']) && $result['domainBoost'] > 0) {
        echo "  • Domain boost: " . number_format($result['domainBoost'] * 100, 2) . "%\n";
    }
    
    if (isset($result['missingWords']) && !empty($result['missingWords'])) {
        echo "\nMISSING KEYWORDS: " . implode(', ', $result['missingWords']) . "\n";
    }
    
    echo "=================================================================\n";
    
    return $result;
}

// ---------------------------------------------------------------------------------
echo "\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "MULTIDISCIPLINARY MEDICAL EDUCATION ANSWER EVALUATION TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ---------------------------------------------------------------------------------
// ANATOMY TESTS
// ---------------------------------------------------------------------------------
echo "\n\033[1m== ANATOMY TESTS ==\033[0m\n";

// Test 1: Anatomical structure exact match
testDisciplinaryAnswer(
    $evaluationService,
    "The brachial plexus is formed by the ventral rami of spinal nerves C5-T1.",
    "The brachial plexus is formed by the ventral rami of spinal nerves C5-T1.",
    "Anatomical Structure Exact Match",
    "anatomy"
);

// Test 2: Anatomical position terminology
testDisciplinaryAnswer(
    $evaluationService,
    "The heart is located in the mediastinum, with the apex pointing anteriorly and to the left.",
    "The heart is located in the mediastinum of the thoracic cavity, with its apex directed anteriorly, inferiorly, and to the left.",
    "Anatomical Position Terminology",
    "anatomy"
);

// Test 3: Missing key anatomical terms
testDisciplinaryAnswer(
    $evaluationService,
    "The structure is in the chest and pumps blood.",
    "The heart is a muscular organ located in the mediastinum of the thoracic cavity that pumps blood through the circulatory system.",
    "Missing Key Anatomical Terms",
    "anatomy"
);

// Test 4: Anatomical contradiction
testDisciplinaryAnswer(
    $evaluationService,
    "The biceps brachii is located on the posterior surface of the humerus and acts to extend the forearm.",
    "The biceps brachii is located on the anterior surface of the humerus and acts to flex the forearm.",
    "Anatomical Contradiction",
    "anatomy"
);

// Test 5: Anatomy with abbreviations
testDisciplinaryAnswer(
    $evaluationService,
    "The IVC returns deoxygenated blood to the RA of the heart.",
    "The inferior vena cava returns deoxygenated blood to the right atrium of the heart.",
    "Anatomy with Abbreviations",
    "anatomy"
);

// ---------------------------------------------------------------------------------
// PHYSIOLOGY TESTS
// ---------------------------------------------------------------------------------
echo "\n\033[1m== PHYSIOLOGY TESTS ==\033[0m\n";

// Test 1: Cardiac cycle description
testDisciplinaryAnswer(
    $evaluationService,
    "During systole, the ventricles contract, forcing blood into the pulmonary artery and aorta. During diastole, the ventricles relax and fill with blood from the atria.",
    "The cardiac cycle consists of systole, when ventricles contract and eject blood into the great arteries, and diastole, when ventricles relax and fill with blood from the atria.",
    "Cardiac Cycle Description",
    "physiology"
);

// Test 2: Action potential with abbreviations
testDisciplinaryAnswer(
    $evaluationService,
    "The AP begins with depolarization when Na+ channels open, followed by repolarization when K+ channels activate.",
    "The action potential begins with rapid depolarization when voltage-gated sodium channels open, followed by repolarization as potassium channels activate.",
    "Action Potential with Abbreviations",
    "physiology"
);

// Test 3: Physiological contradiction
testDisciplinaryAnswer(
    $evaluationService,
    "Increased sympathetic stimulation causes vasodilation in the peripheral vasculature and decreased heart rate.",
    "Increased sympathetic stimulation causes vasoconstriction in the peripheral vasculature and increased heart rate.",
    "Physiological Contradiction",
    "physiology"
);

// Test 4: Renal physiology
testDisciplinaryAnswer(
    $evaluationService,
    "Glomerular filtration is driven by hydrostatic pressure, while tubular reabsorption returns needed substances to the blood.",
    "Glomerular filtration is the process of blood filtration in the renal corpuscle driven by hydrostatic pressure, while tubular reabsorption returns essential substances from the filtrate back to the blood.",
    "Renal Physiology",
    "physiology"
);

// ---------------------------------------------------------------------------------
// BIOCHEMISTRY TESTS
// ---------------------------------------------------------------------------------
echo "\n\033[1m== BIOCHEMISTRY TESTS ==\033[0m\n";

// Test 1: Glycolysis pathway
testDisciplinaryAnswer(
    $evaluationService,
    "Glycolysis converts glucose to pyruvate, generating 2 ATP and 2 NADH per glucose molecule.",
    "Glycolysis is a metabolic pathway that converts glucose into pyruvate, generating 2 ATP and 2 NADH molecules per glucose.",
    "Glycolysis Pathway",
    "biochemistry"
);

// Test 2: TCA cycle with abbreviations
testDisciplinaryAnswer(
    $evaluationService,
    "The TCA cycle begins when Acetyl-CoA combines with OAA, and through a series of rxns produces NADH, FADH2, and CO2.",
    "The tricarboxylic acid cycle begins when acetyl-CoA combines with oxaloacetate, and through a series of reactions produces NADH, FADH2, and carbon dioxide.",
    "TCA Cycle with Abbreviations",
    "biochemistry"
);

// Test 3: Biochemical contradiction
testDisciplinaryAnswer(
    $evaluationService,
    "DNA replication is an endergonic process that releases energy and requires nucleotides.",
    "DNA replication is an exergonic process that requires energy and nucleotides.",
    "Biochemical Contradiction",
    "biochemistry"
);

// Test 4: Protein synthesis
testDisciplinaryAnswer(
    $evaluationService,
    "Translation occurs when mRNA codons are read by tRNA to synthesize proteins.",
    "Translation is the process by which ribosomes synthesize proteins using mRNA as a template, with tRNAs delivering amino acids according to the mRNA codons.",
    "Protein Synthesis",
    "biochemistry"
);

// ---------------------------------------------------------------------------------
// HISTOLOGY TESTS
// ---------------------------------------------------------------------------------
echo "\n\033[1m== HISTOLOGY TESTS ==\033[0m\n";

// Test 1: Epithelial tissue
testDisciplinaryAnswer(
    $evaluationService,
    "Simple squamous epithelium consists of a single layer of flat cells and is found lining blood vessels.",
    "Simple squamous epithelium consists of a single layer of flattened cells with centrally located nuclei and is found lining blood vessels, alveoli, and forming the walls of capillaries.",
    "Epithelial Tissue",
    "histology"
);

// Test 2: Connective tissue
testDisciplinaryAnswer(
    $evaluationService,
    "Dense CT contains closely packed collagen fibers and fewer cells than loose CT.",
    "Dense connective tissue contains closely packed collagen fibers with fewer cells and less ground substance than loose connective tissue.",
    "Connective Tissue with Abbreviations",
    "histology"
);

// Test 3: Histological contradiction
testDisciplinaryAnswer(
    $evaluationService,
    "Cardiac muscle cells are multinucleated with striations and no intercalated discs.",
    "Cardiac muscle cells are mononucleated with striations and connected by intercalated discs.",
    "Histological Contradiction",
    "histology"
);

// Test 4: Cell junction
testDisciplinaryAnswer(
    $evaluationService,
    "GJs allow direct communication between adjacent cells through connexons.",
    "Gap junctions allow direct communication between adjacent cells through connexons, which are channels formed by connexin proteins.",
    "Cell Junction with Abbreviations",
    "histology"
);

echo "\nAll multidisciplinary tests completed.\n"; 