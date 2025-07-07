<?php

use App\Models\User;
use App\Models\Course;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Bootstrap Laravel (for standalone script)
$kernel->bootstrap();

echo "=== BULLETPROOF FREE COURSE ENROLLMENT BACKFILL ===\n";
echo "Starting enrollment of all users in all free courses...\n\n";

// Get all free course IDs (price = 0 OR price IS NULL)
$freeCourses = Course::where(function($query) {
    $query->where('price', 0)
          ->orWhereNull('price');
})->get();

if ($freeCourses->isEmpty()) {
    echo "No free courses found. Exiting.\n";
    exit(0);
}

echo "Found " . $freeCourses->count() . " free courses:\n";
foreach ($freeCourses as $course) {
    echo "- {$course->name} (ID: {$course->id}, Price: " . ($course->price ?? 'NULL') . ")\n";
}
echo "\n";

$totalUsers = User::count();
$enrolledUsers = 0;
$failedUsers = 0;
$totalEnrollments = 0;

echo "Processing {$totalUsers} users...\n\n";

User::chunk(100, function ($users) use (&$enrolledUsers, &$failedUsers, &$totalEnrollments) {
    foreach ($users as $user) {
        try {
            $enrolledCount = $user->enrollInAllFreeCourses();
            if ($enrolledCount > 0) {
                $enrolledUsers++;
                $totalEnrollments += $enrolledCount;
                echo "✓ User {$user->id} ({$user->email}): Enrolled in {$enrolledCount} courses\n";
            } else {
                echo "- User {$user->id} ({$user->email}): Already enrolled or no free courses\n";
            }
        } catch (\Exception $e) {
            $failedUsers++;
            echo "✗ User {$user->id} ({$user->email}): Failed - {$e->getMessage()}\n";
        }
    }
});

echo "\n=== ENROLLMENT SUMMARY ===\n";
echo "Total users processed: {$totalUsers}\n";
echo "Users successfully enrolled: {$enrolledUsers}\n";
echo "Users with errors: {$failedUsers}\n";
echo "Total course enrollments created: {$totalEnrollments}\n";
echo "Free courses available: " . $freeCourses->count() . "\n";

if ($failedUsers > 0) {
    echo "\n⚠️  Some users failed to enroll. Check the logs above for details.\n";
} else {
    echo "\n✅ All users processed successfully!\n";
}

echo "\nDone! Enrollment backfill completed.\n"; 