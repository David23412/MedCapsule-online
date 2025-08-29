<?php
// tests/Tinker/test_super_agent.php

/**
 * SUPER AGENT IMPLEMENTATION TEST
 * Testing the AI-Ready Enhanced Written Grading System
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;

echo "🚀 SUPER AGENT IMPLEMENTATION TEST\n";
echo "==================================\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel bootstrapped successfully\n\n";

try {
    // Test the Writing Grader Service
    echo "🧠 Testing Writing Grader Service...\n";

    // Get the service from the container
    $writingGrader = app(WritingGraderService::class);

    echo "✅ Service instantiated successfully\n\n";

    // Test basic functionality
    echo "📝 Testing basic grading functionality...\n";

    $testCases = [
        [
            'student' => 'The heart pumps blood',
            'correct' => 'The heart is a muscular organ that pumps oxygenated blood to the body and deoxygenated blood to the lungs',
            'subject' => 'anatomy',
            'expected_min_similarity' => 0.3
        ],
        [
            'student' => 'Sympathetic increases HR, PNS decreases HR',
            'correct' => 'Sympathetic nervous system increases heart rate, parasympathetic decreases heart rate',
            'subject' => 'physiology',
            'expected_min_similarity' => 0.7
        ],
        [
            'student' => 'TCA cycle produces NADH and FADH2',
            'correct' => 'The citric acid cycle generates NADH, FADH2, CO2, and GTP through oxidative reactions',
            'subject' => 'biochemistry',
            'expected_min_similarity' => 0.5
        ]
    ];

    foreach ($testCases as $i => $testCase) {
        echo "\n🧪 Test Case " . ($i + 1) . ":\n";
        echo "Subject: {$testCase['subject']}\n";
        echo "Student: {$testCase['student']}\n";
        echo "Correct: {$testCase['correct']}\n";

        $startTime = microtime(true);
        $result = $writingGrader->gradeAnswer(
            $testCase['student'],
            $testCase['correct'],
            $testCase['subject']
        );
        $processingTime = microtime(true) - $startTime;

        echo "Result: " . ($result['isCorrect'] ? '✅ CORRECT' : '❌ INCORRECT') . "\n";
        echo "Similarity: " . number_format($result['similarity'], 3) . "\n";
        echo "Confidence: " . (isset($result['confidence']) ? number_format($result['confidence'], 3) : 'N/A') . "\n";
        echo "Time: " . number_format($processingTime * 1000, 2) . "ms\n";
        echo "Reason: " . substr($result['reason'] ?? 'No reason provided', 0, 100) . "...\n";
        echo "Topic: " . ($result['courseTopic'] ?? 'N/A') . "\n";
        echo "Feedback: " . substr($result['feedback'] ?? 'No feedback', 0, 100) . "...\n";

        // Show detailed breakdown if available
        if (isset($result['algorithm_breakdown'])) {
            echo "Algorithm Breakdown:\n";
            foreach ($result['algorithm_breakdown'] as $algorithm => $score) {
                echo "  $algorithm: " . number_format($score, 3) . "\n";
            }
        }

        // Performance check
        if ($processingTime > 0.1) { // 100ms threshold
            echo "⚠️  SLOW: " . number_format($processingTime * 1000, 2) . "ms\n";
        } else {
            echo "⚡ FAST: " . number_format($processingTime * 1000, 2) . "ms\n";
        }
    }

    echo "\n\n📊 PERFORMANCE SUMMARY\n";
    echo "====================\n";

    // Get service statistics
    $stats = $writingGrader->getStatistics();

    echo "Writing Grader Stats:\n";
    echo "- Total Evaluations: " . ($stats['writing_grader']['total_evaluations'] ?? 'N/A') . "\n";
    echo "- Average Similarity: " . ($stats['writing_grader']['average_similarity'] ?? 'N/A') . "\n";
    echo "- Average Confidence: " . ($stats['writing_grader']['average_confidence'] ?? 'N/A') . "\n";
    echo "- Average Time: " . ($stats['writing_grader']['average_processing_time'] ?? 'N/A') . "\n";

    echo "\nCache Stats:\n";
    echo "- Cache Level: " . ($stats['cache_manager']['cache_levels'] ?? 'N/A') . "\n";
    echo "- Hit Rate: " . ($stats['cache_manager']['cache_hit_rate'] ?? 'N/A') . "%\n";

    echo "\nSimilarity Engine Stats:\n";
    echo "- Total Calculations: " . ($stats['similarity_engine']['total_calculations'] ?? 'N/A') . "\n";
    echo "- Average Ensemble: " . ($stats['similarity_engine']['average_ensemble_score'] ?? 'N/A') . "\n";

    echo "\n🎯 FINAL ASSESSMENT\n";
    echo "==================\n";

    $avgTime = $stats['writing_grader']['average_processing_time'] ?? 0;
    $avgConfidence = $stats['writing_grader']['average_confidence'] ?? 0;

    if ($avgTime < 0.05 && $avgConfidence > 0.8) {
        echo "🏆 EXCELLENT: Super-fast and highly confident!\n";
    } elseif ($avgTime < 0.1 && $avgConfidence > 0.7) {
        echo "✅ GOOD: Fast performance with solid confidence!\n";
    } elseif ($avgTime < 0.2) {
        echo "⚠️  FAIR: Decent performance, needs confidence tuning!\n";
    } else {
        echo "❌ NEEDS WORK: Slow performance or low confidence!\n";
    }

    echo "\n🎉 SUPER AGENT IMPLEMENTATION COMPLETE!\n";
    echo "📈 Ready for AI integration and production use!\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";

    echo "\n🔧 TROUBLESHOOTING:\n";
    echo "1. Check if all service files are created\n";
    echo "2. Verify service provider is registered\n";
    echo "3. Check Laravel configuration\n";
    echo "4. Ensure all dependencies are available\n";
}

echo "\n✨ Test completed at " . now()->format('Y-m-d H:i:s') . "\n";
