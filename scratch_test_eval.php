<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\TextProcessingService;
use App\Services\WrittenAnswerEvaluationService;
use App\Services\PatternMatcherService;
use App\Services\MedicalKnowledgeService;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// Setup a minimal Laravel container
$app = new Container();
$app->singleton(TextProcessingService::class, function() {
    return new TextProcessingService();
});
$app->singleton(PatternMatcherService::class, function($app) {
    return new PatternMatcherService($app->make(TextProcessingService::class));
});
$app->singleton(MedicalKnowledgeService::class, function() {
    return new MedicalKnowledgeService();
});
$app->singleton(WrittenAnswerEvaluationService::class, function($app) {
    return new WrittenAnswerEvaluationService(
        $app->make(TextProcessingService::class),
        $app->make(PatternMatcherService::class),
        $app->make(MedicalKnowledgeService::class)
    );
});

Container::setInstance($app);

// Mock Facades if needed (this is a bit tricky in a standalone script)
// For simplicity, we just use the instances directly.

$service = $app->make(WrittenAnswerEvaluationService::class);

$testCases = [
    [
        'sub' => 'Nautral active immunity',
        'cor' => 'Naturally acquired active immunity',
        'expected' => true
    ],
    [
        'sub' => 'Natural active immunity',
        'cor' => 'Naturally acquired active immunity',
        'expected' => true
    ],
    [
        'sub' => 'Natuaral active immunity',
        'cor' => 'Naturally acquired active immunity',
        'expected' => true
    ],
    [
        'sub' => 'inactive immunity',
        'cor' => 'active immunity',
        'expected' => false
    ]
];

foreach ($testCases as $case) {
    $result = $service->evaluateAnswer($case['sub'], $case['cor']);
    echo "Sub: {$case['sub']}\n";
    echo "Cor: {$case['cor']}\n";
    echo "Is Correct: " . ($result['isCorrect'] ? 'YES' : 'NO') . "\n";
    echo "Similarity: " . round($result['similarity'], 4) . "\n";
    echo "Reason: " . ($result['reason'] ?? 'N/A') . "\n";
    echo "Feedback: " . ($result['feedback'] ?? 'None') . "\n";
    echo "----------------------------------------\n";
}
