<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up authentication
$user = App\Models\User::find(8);
auth()->login($user);

echo "=== TRACING THE EXACT BUG ===" . PHP_EOL;

// Let's trace the exact same flow as the controller
$courseId = "1"; // This is what should come from the route

echo "Step 1 - Original parameter: " . var_export($courseId, true) . PHP_EOL;

$originalValue = $courseId;
$requestedCourseId = (int) $courseId;

echo "Step 2 - After conversion: " . $requestedCourseId . PHP_EOL;

// Test the exact same queries as in the controller
echo "Step 3 - Testing database queries:" . PHP_EOL;

$courseMethod1 = App\Models\Course::where('id', $requestedCourseId)->first();
echo "  Method 1 result: " . ($courseMethod1 ? "ID {$courseMethod1->id} - {$courseMethod1->name}" : "NULL") . PHP_EOL;

$courseMethod2 = App\Models\Course::find($requestedCourseId);
echo "  Method 2 result: " . ($courseMethod2 ? "ID {$courseMethod2->id} - {$courseMethod2->name}" : "NULL") . PHP_EOL;

$courseMethod3 = DB::select('SELECT * FROM courses WHERE id = ?', [$requestedCourseId]);
echo "  Method 3 result: " . (count($courseMethod3) > 0 ? "ID {$courseMethod3[0]->id} - {$courseMethod3[0]->name}" : "NULL") . PHP_EOL;

// Choose the course (same as controller)
$course = $courseMethod1;

echo "Step 4 - Selected course: " . ($course ? "ID {$course->id} - {$course->name}" : "NULL") . PHP_EOL;

// Now let's check if there's any issue with the user's access or enrollment
echo PHP_EOL . "=== USER ACCESS CHECK ===" . PHP_EOL;
$userCourses = auth()->user()->courses()->get();
echo "User enrolled courses:" . PHP_EOL;
foreach ($userCourses as $userCourse) {
    echo "  - Course ID: {$userCourse->id}, Name: {$userCourse->name}, Status: {$userCourse->pivot->enrollment_status}" . PHP_EOL;
}

echo "User has access to Course 1: " . (auth()->user()->hasAccessToCourse(1) ? 'YES' : 'NO') . PHP_EOL;
echo "User has access to Course 8: " . (auth()->user()->hasAccessToCourse(8) ? 'YES' : 'NO') . PHP_EOL;

// Let's check if there's any issue with the course loading in the view
echo PHP_EOL . "=== SIMULATING VIEW DATA ===" . PHP_EOL;
if ($course) {
    $topics = App\Models\Topic::where('course_id', $course->id)->orderBy('display_order')->get();
    echo "Topics for Course {$course->id}:" . PHP_EOL;
    foreach ($topics as $topic) {
        echo "  - Topic ID: {$topic->id}, Name: {$topic->name}, Course ID: {$topic->course_id}, Case Type: {$topic->case_type}" . PHP_EOL;
    }
    
    echo PHP_EOL . "Course object details:" . PHP_EOL;
    echo "  ID: {$course->id}" . PHP_EOL;
    echo "  Name: {$course->name}" . PHP_EOL;
    echo "  Type: {$course->type}" . PHP_EOL;
    echo "  isMedicalCourse(): " . ($course->isMedicalCourse() ? 'TRUE' : 'FALSE') . PHP_EOL;
    echo "  isCustomCourse(): " . ($course->isCustomCourse() ? 'TRUE' : 'FALSE') . PHP_EOL;
}

// Let's also test what happens if we simulate the exact request
echo PHP_EOL . "=== SIMULATING EXACT REQUEST ===" . PHP_EOL;
$request = Illuminate\Http\Request::create('/topics/1', 'GET');
$route = app('router')->getRoutes()->match($request);
$routeParams = $route->parameters();

echo "Route parameters from simulated request: " . json_encode($routeParams) . PHP_EOL;
if (isset($routeParams['courseId'])) {
    $simulatedCourseId = $routeParams['courseId'];
    $simulatedRequestedId = (int) $simulatedCourseId;
    $simulatedCourse = App\Models\Course::where('id', $simulatedRequestedId)->first();
    echo "Simulated course loading: " . ($simulatedCourse ? "ID {$simulatedCourse->id} - {$simulatedCourse->name}" : "NULL") . PHP_EOL;
}