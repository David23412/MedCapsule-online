<?php

require_once 'vendor/autoload.php';

use App\Models\User;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ANALYTICS FIX TEST ===\n\n";

// Get a tutor and test analytics
$tutor = User::where('role', 'tutor')->first();
if (!$tutor) {
    echo "No tutors found!\n";
    exit;
}

echo "Testing analytics for: {$tutor->name}\n";

// Simulate authentication
auth()->login($tutor);

$controller = new App\Http\Controllers\Tutor\TutorDashboardController();

try {
    $request = new Illuminate\Http\Request();
    $response = $controller->index($request);
    
    // Get the view data
    $viewData = $response->getData();
    $analytics = $viewData['analytics'];
    
    echo "\n📊 ANALYTICS DATA:\n";
    echo "   Total Students: " . ($analytics['total_students'] ?? 'MISSING') . "\n";
    echo "   Active Students: " . ($analytics['active_students'] ?? 'MISSING') . "\n";
    echo "   Struggling Students: " . ($analytics['struggling_students'] ?? 'MISSING') . "\n";
    echo "   High Performers: " . ($analytics['high_performers'] ?? 'MISSING') . "\n";
    echo "   Total Quizzes: " . ($analytics['total_quizzes'] ?? 'MISSING') . "\n";
    echo "   Class Average: " . ($analytics['class_average'] ?? 'MISSING') . "%\n";
    echo "   Engagement Rate: " . ($analytics['engagement_rate'] ?? 'MISSING') . "%\n";
    
    // Check if all required keys are present
    $requiredKeys = ['total_students', 'active_students', 'struggling_students', 'high_performers', 'total_quizzes', 'class_average', 'engagement_rate'];
    $missingKeys = [];
    
    foreach ($requiredKeys as $key) {
        if (!isset($analytics[$key])) {
            $missingKeys[] = $key;
        }
    }
    
    if (empty($missingKeys)) {
        echo "\n✅ All analytics keys are present!\n";
    } else {
        echo "\n❌ Missing keys: " . implode(', ', $missingKeys) . "\n";
    }
    
    // Test specific analytics calculations
    $students = $viewData['students'];
    echo "\n🔍 DETAILED BREAKDOWN:\n";
    
    $strugglingCount = 0;
    $highPerformerCount = 0;
    $activeCount = 0;
    
    foreach ($students as $student) {
        $avgScore = $student->quizAttempts->avg('score') ?? 0;
        $isActive = $student->last_active_at && $student->last_active_at->gte(now()->subDays(7));
        
        echo "   - {$student->name}: " . number_format($avgScore, 1) . "% avg";
        
        if ($avgScore < 60 && $student->quizAttempts->count() > 0) {
            echo " (STRUGGLING)";
            $strugglingCount++;
        } elseif ($avgScore >= 80) {
            echo " (HIGH PERFORMER)";
            $highPerformerCount++;
        }
        
        if ($isActive) {
            echo " (ACTIVE)";
            $activeCount++;
        }
        
        echo "\n";
    }
    
    echo "\n📈 CALCULATED TOTALS:\n";
    echo "   Struggling: {$strugglingCount} (should match: {$analytics['struggling_students']})\n";
    echo "   High Performers: {$highPerformerCount} (should match: {$analytics['high_performers']})\n";
    echo "   Active: {$activeCount} (should match: {$analytics['active_students']})\n";
    
    // Verify calculations match
    if ($strugglingCount == $analytics['struggling_students'] && 
        $highPerformerCount == $analytics['high_performers'] && 
        $activeCount == $analytics['active_students']) {
        echo "\n✅ All calculations match perfectly!\n";
    } else {
        echo "\n❌ Calculation mismatch detected!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== ANALYTICS TEST COMPLETE ===\n";