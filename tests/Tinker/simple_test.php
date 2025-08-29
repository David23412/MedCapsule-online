<?php
// tests/Tinker/simple_test.php

/**
 * SIMPLE SUPER AGENT TEST
 * Minimal test to debug the confidence key issue
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\Grading\WritingGraderService;

echo "🔍 SIMPLE SUPER AGENT DEBUG TEST\n";
echo "================================\n\n";

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

    // Test with simple data
    echo "📝 Testing simple grading...\n";

    $result = $writingGrader->gradeAnswer(
        'The heart pumps blood',
        'The heart is a muscular organ that pumps blood',
        'anatomy'
    );

    echo "Result structure:\n";
    print_r($result);

    echo "\n🎉 Simple test completed successfully!\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✨ Test completed at " . now()->format('Y-m-d H:i:s') . "\n";
