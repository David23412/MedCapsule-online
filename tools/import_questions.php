<?php

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/**
 * Medical Question Import Utility
 * 
 * This tool helps educators import medical education questions from a CSV file
 * into the database. It automatically detects the course domain, validates the
 * expected answers, and creates alternative answers.
 * 
 * CSV Format:
 * question_text, correct_answer, alternative_answer_1, alternative_answer_2, ...
 * 
 * Usage:
 * php import_questions.php path/to/questions.csv [course_id]
 */

// Check command line arguments
if (!isset($argv[1])) {
    echo "Error: Missing file path\n";
    echo "Usage: php import_questions.php path/to/questions.csv [course_id]\n";
    exit(1);
}

$filePath = $argv[1];
$courseId = $argv[2] ?? null;

// Validate file existence
if (!file_exists($filePath)) {
    echo "Error: File not found: $filePath\n";
    exit(1);
}

// Function to detect course topic from question/answer text
function detectCourseTopic($text) {
    $topics = [
        'anatomy' => ['anatomy', 'anatomical', 'structure', 'organ', 'bone', 'muscle', 'vessel', 'nerve', 'joint', 'skeletal', 'skull', 'thorax', 'abdomen', 'pelvis', 'limb', 'cranial'],
        'physiology' => ['physiology', 'function', 'mechanism', 'regulation', 'homeostasis', 'system', 'response', 'signaling', 'reflex', 'hormone', 'action potential', 'cardiac cycle', 'breathing', 'filtration'],
        'biochemistry' => ['biochemistry', 'molecule', 'protein', 'enzyme', 'pathway', 'metabolism', 'substrate', 'reaction', 'synthesis', 'degradation', 'glycolysis', 'krebs', 'tca', 'electron transport', 'dna', 'rna', 'transcription', 'translation'],
        'histology' => ['histology', 'tissue', 'cell', 'epithelium', 'connective', 'microscopic', 'stain', 'hematoxylin', 'eosin', 'layer', 'membrane', 'ultrastructure', 'organelle', 'matrix', 'fibers']
    ];
    
    $text = strtolower($text);
    $scores = [];
    
    foreach ($topics as $topic => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $score++;
            }
        }        $scores[$topic] = $score;
    }
    
    arsort($scores);
    return key($scores);
}

// Function to validate answer quality
function validateAnswer($answer) {
    $minWords = 5;
    $wordsCount = str_word_count($answer);
    
    if ($wordsCount < $minWords) {
        return [
            'valid' => false,
            'reason' => "Answer too short ($wordsCount words, minimum $minWords required)"
        ];
    }
    
    return ['valid' => true];
}

// Function to generate additional alternative answers
function generateAlternativeAnswers($correctAnswer) {
    $alternativeAnswers = [];
    
    // Create an alternative with medical abbreviations
    $abbreviations = [
        'heart rate' => 'HR',
        'blood pressure' => 'BP',
        'central nervous system' => 'CNS',
        'peripheral nervous system' => 'PNS',
        'sympathetic nervous system' => 'SNS',
        'parasympathetic nervous system' => 'PNS',
        'gastrointestinal' => 'GI',
        'myocardial infarction' => 'MI',
        'congestive heart failure' => 'CHF',
        'chronic obstructive pulmonary disease' => 'COPD',
        'arteriovenous' => 'AV',
        'adenosine triphosphate' => 'ATP'
    ];
    
    $alternativeWithAbbreviations = $correctAnswer;
    foreach ($abbreviations as $full => $abbr) {
        $alternativeWithAbbreviations = str_ireplace($full, $abbr, $alternativeWithAbbreviations);
    }
    
    // Only add if it's different from the original
    if ($alternativeWithAbbreviations !== $correctAnswer) {
        $alternativeAnswers[] = $alternativeWithAbbreviations;
    }
    
    // Create passive voice alternative
    $activeToPassive = [
        'increases' => 'is increased by',
        'decreases' => 'is decreased by',
        'produces' => 'is produced by',
        'regulates' => 'is regulated by',
        'controls' => 'is controlled by',
        'inhibits' => 'is inhibited by',
        'activates' => 'is activated by',
        'stimulates' => 'is stimulated by'
    ];
    
    $alternativePassive = $correctAnswer;
    foreach ($activeToPassive as $active => $passive) {
        $alternativePassive = str_ireplace($active, $passive, $alternativePassive);
    }
    
    // Only add if it's different from the original and first alternative
    if ($alternativePassive !== $correctAnswer && $alternativePassive !== $alternativeWithAbbreviations) {
        $alternativeAnswers[] = $alternativePassive;
    }
    
    return $alternativeAnswers;
}

// Begin processing the file
echo "Starting import from $filePath\n";
echo "--------------------------------------\n";

$handle = fopen($filePath, 'r');
if (!$handle) {
    echo "Error: Unable to open file\n";
    exit(1);
}

$lineNumber = 0;
$successCount = 0;
$errorCount = 0;

// Process each line
while (($line = fgetcsv($handle)) !== false) {
    $lineNumber++;
    
    // Skip header line
    if ($lineNumber === 1 && strcasecmp(trim($line[0]), 'question') === 0) {
        continue;
    }
    
    // Validate line format
    if (count($line) < 2) {
        echo "Line $lineNumber: Error - Missing required fields\n";
        $errorCount++;
        continue;
    }
    
    $question = trim($line[0]);
    $correctAnswer = trim($line[1]);
    
    // Get provided alternative answers
    $providedAlternatives = [];
    for ($i = 2; $i < count($line); $i++) {
        if (!empty(trim($line[$i]))) {
            $providedAlternatives[] = trim($line[$i]);
        }
    }
    
    // Validate question
    if (empty($question)) {
        echo "Line $lineNumber: Error - Empty question\n";
        $errorCount++;
        continue;
    }
    
    // Validate correct answer
    $validation = validateAnswer($correctAnswer);
    if (!$validation['valid']) {
        echo "Line $lineNumber: Error - " . $validation['reason'] . "\n";
        $errorCount++;
        continue;
    }
    
    // Detect course topic
    $courseTopic = detectCourseTopic($question . ' ' . $correctAnswer);
    
    // Generate additional alternatives
    $generatedAlternatives = generateAlternativeAnswers($correctAnswer);
    
    // Combine provided and generated alternatives
    $allAlternatives = array_merge($providedAlternatives, $generatedAlternatives);
    
    // Remove duplicates
    $allAlternatives = array_unique($allAlternatives);
    
    // Prepare data for database
    $data = [
        'question_text' => $question,
        'correct_answer' => $correctAnswer,
        'alternative_answers' => json_encode($allAlternatives),
        'question_type' => 'written',
        'difficulty' => 'medium',
        'created_at' => now(),
        'updated_at' => now(),
        'similarity_threshold' => 0.85, // Default threshold
        'domain' => $courseTopic
    ];
    
    // Add course_id if provided
    if ($courseId) {
        $data['course_id'] = $courseId;
    }
    
    try {
        // Insert into database
        DB::table('questions')->insert($data);
        
        echo "Line $lineNumber: Imported question about " . ucfirst($courseTopic) . " with " . count($allAlternatives) . " alternative answers\n";
        $successCount++;
    } catch (\Exception $e) {
        echo "Line $lineNumber: Database error - " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

fclose($handle);

// Summary
echo "\n--------------------------------------\n";
echo "Import completed\n";
echo "Total processed: " . ($lineNumber - 1) . "\n";
echo "Successful imports: $successCount\n";
echo "Errors: $errorCount\n";

if ($errorCount > 0) {
    exit(1);
}

exit(0); 