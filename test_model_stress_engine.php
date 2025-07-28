<?php

/**
 * LETHAL MODEL STRESS TESTING ENGINE RUNNER
 * 
 * This script will execute the most aggressive model stress testing
 * ever created for our Laravel application.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                                                              ║\n";
echo "║               💀 LETHAL MODEL STRESS TESTER 💀               ║\n";
echo "║                                                              ║\n";
echo "║              \"DESTROY MODELS TO MAKE THEM STRONGER\"         ║\n";
echo "║                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 INITIATING MODEL STRESS TESTING ENGINE...\n\n";

try {
    // Execute different stress test scenarios
    $scenarios = [
        [
            'name' => 'LIGHT STRESS TEST',
            'level' => 10000,
            'description' => 'Basic model stress testing'
        ],
        [
            'name' => 'MEDIUM STRESS TEST', 
            'level' => 100000,
            'description' => 'Moderate model stress testing'
        ],
        [
            'name' => 'HEAVY STRESS TEST',
            'level' => 500000,
            'description' => 'Intensive model stress testing'
        ],
        [
            'name' => 'LETHAL STRESS TEST',
            'level' => 1000000,
            'description' => 'MAXIMUM DESTRUCTION MODE'
        ]
    ];

    foreach ($scenarios as $scenario) {
        echo "🎯 EXECUTING: {$scenario['name']}\n";
        echo "📊 Level: {$scenario['level']} operations\n";
        echo "📝 Description: {$scenario['description']}\n\n";
        
        $exitCode = Artisan::call('stress:models', [
            '--level' => $scenario['level'],
            '--force' => true,
            '--skip-backup' => true
        ]);
        
        if ($exitCode === 0) {
            echo "✅ {$scenario['name']} COMPLETED SUCCESSFULLY\n\n";
        } else {
            echo "❌ {$scenario['name']} FAILED\n\n";
            break;
        }
        
        // Brief pause between scenarios
        sleep(2);
    }
    
    echo "🏆 ALL STRESS TESTING SCENARIOS COMPLETED!\n";
    echo "📄 Check storage/logs/ for detailed reports\n\n";
    
} catch (Exception $e) {
    echo "💥 CRITICAL FAILURE: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "🎉 MODEL STRESS TESTING ENGINE EXECUTION COMPLETE!\n\n";