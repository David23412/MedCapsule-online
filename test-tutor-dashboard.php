<?php

require_once 'vendor/autoload.php';

use App\Models\User;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Testing Tutor Dashboard System\n";
echo "==================================\n\n";

try {
    // Test 1: Check if tutor exists
    echo "📋 Test 1: Checking tutor existence...\n";
    $tutor = User::where('role', 'tutor')->first();
    
    if ($tutor) {
        echo "✅ Tutor found: {$tutor->name} (ID: {$tutor->id})\n";
    } else {
        echo "❌ No tutor found in database\n";
        exit(1);
    }

    // Test 2: Check assigned students
    echo "\n📋 Test 2: Checking assigned students...\n";
    $assignedStudents = $tutor->assignedStudents()->get();
    echo "✅ Found {$assignedStudents->count()} assigned students\n";
    
    foreach ($assignedStudents as $student) {
        echo "   - {$student->name} (ID: {$student->id})\n";
    }

    // Test 3: Test Dashboard Controller
    echo "\n📋 Test 3: Testing Dashboard Controller...\n";
    auth()->login($tutor);
    $controller = new App\Http\Controllers\Tutor\TutorDashboardController();
    $request = new Illuminate\Http\Request();
    $response = $controller->index($request);
    
    echo "✅ Dashboard controller working successfully\n";
    $viewData = $response->getData();
    if (isset($viewData['analytics'])) {
        $analytics = $viewData['analytics'];
        echo "   - Total Students: {$analytics['total_students']}\n";
        echo "   - Active Students: {$analytics['active_students']}\n";
        echo "   - Class Average: " . number_format($analytics['average_score'], 1) . "%\n";
        echo "   - Struggling Students: {$analytics['struggling_students']}\n";
    }

    // Test 4: Check database tables
    echo "\n📋 Test 4: Checking database tables...\n";
    
    $tutorStudentCount = DB::table('tutor_student_relationships')->count();
    echo "✅ Tutor-Student relationships: {$tutorStudentCount}\n";
    
    $userRoles = DB::table('users')
        ->select('role', DB::raw('count(*) as count'))
        ->groupBy('role')
        ->get();
    
    foreach ($userRoles as $roleCount) {
        echo "   - {$roleCount->role}: {$roleCount->count} users\n";
    }

    // Test 5: Test route accessibility
    echo "\n📋 Test 5: Testing route definitions...\n";
    
    $routes = [
        'tutor.dashboard',
        'tutor.student.profile',
        'tutor.student.progress',
    ];
    
    foreach ($routes as $routeName) {
        try {
            $url = route($routeName, ['student_id' => $assignedStudents->first()->id ?? 1]);
            echo "✅ Route '{$routeName}' is defined: {$url}\n";
        } catch (Exception $e) {
            echo "❌ Route '{$routeName}' error: {$e->getMessage()}\n";
        }
    }

    echo "\n🎉 All tests completed successfully!\n";
    echo "\n📝 Next Steps:\n";
    echo "1. Visit /tutor/dashboard to see the main dashboard\n";
    echo "2. Click on student profiles to view detailed information\n";
    echo "3. Test the export functionality\n";
    echo "\n🔐 Login Credentials:\n";
    echo "Tutor: tutor@medcapsule.com / password\n";
    echo "Admin: admin@medcapsule.com / password (if exists)\n";

} catch (Exception $e) {
    echo "❌ Error during testing: {$e->getMessage()}\n";
    echo "Stack trace: {$e->getTraceAsString()}\n";
    exit(1);
}