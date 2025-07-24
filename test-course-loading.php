<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up authentication
$user = App\Models\User::find(8);
auth()->login($user);

echo "=== TESTING COURSE LOADING LOGIC ===" . PHP_EOL;

// Test the exact same logic as in the controller
$courseId = "1"; // This is what comes from the route
echo "Original courseId parameter: " . var_export($courseId, true) . PHP_EOL;

$requestedCourseId = (int) $courseId;
echo "Converted courseId: " . $requestedCourseId . PHP_EOL;

// Method 1: where() clause (same as controller)
$courseMethod1 = App\Models\Course::where('id', $requestedCourseId)->first();
echo "Method 1 (where clause): " . ($courseMethod1 ? "Course ID {$courseMethod1->id} - {$courseMethod1->name}" : "NULL") . PHP_EOL;

// Method 2: find() method
$courseMethod2 = App\Models\Course::find($requestedCourseId);
echo "Method 2 (find method): " . ($courseMethod2 ? "Course ID {$courseMethod2->id} - {$courseMethod2->name}" : "NULL") . PHP_EOL;

// Method 3: Raw SQL
$courseMethod3 = DB::select('SELECT * FROM courses WHERE id = ?', [$requestedCourseId]);
echo "Method 3 (raw SQL): " . (count($courseMethod3) > 0 ? "Course ID {$courseMethod3[0]->id} - {$courseMethod3[0]->name}" : "NULL") . PHP_EOL;

// Test with different values
echo PHP_EOL . "=== TESTING WITH DIFFERENT VALUES ===" . PHP_EOL;
$testValues = [1, "1", 8, "8"];
foreach ($testValues as $testValue) {
    $converted = (int) $testValue;
    $course = App\Models\Course::where('id', $converted)->first();
    echo "Input: " . var_export($testValue, true) . " -> Converted: {$converted} -> Course: " . ($course ? "ID {$course->id} - {$course->name}" : "NULL") . PHP_EOL;
}

// Check if there's any global scope or model boot method affecting this
echo PHP_EOL . "=== CHECKING MODEL CONFIGURATION ===" . PHP_EOL;
$courseModel = new App\Models\Course();
echo "Table name: " . $courseModel->getTable() . PHP_EOL;
echo "Primary key: " . $courseModel->getKeyName() . PHP_EOL;
echo "Key type: " . $courseModel->getKeyType() . PHP_EOL;
echo "Incrementing: " . ($courseModel->getIncrementing() ? 'YES' : 'NO') . PHP_EOL;

// Check for any global scopes
$query = App\Models\Course::query();
echo "Query SQL: " . $query->toSql() . PHP_EOL;
echo "Query bindings: " . json_encode($query->getBindings()) . PHP_EOL;