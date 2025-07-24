<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test the route directly
echo "=== DIRECT ROUTE TEST ===" . PHP_EOL;

// Simulate the request
$request = Illuminate\Http\Request::create('/topics/1', 'GET');
$request->headers->set('Host', '69b3eb25755b.ngrok-free.app');

// Get the route
$router = app('router');
$route = $router->getRoutes()->match($request);

echo "Route Name: " . ($route ? $route->getName() : 'NO_ROUTE') . PHP_EOL;
echo "Route Parameters: " . json_encode($route ? $route->parameters() : []) . PHP_EOL;
echo "Route Parameter Names: " . json_encode($route ? $route->parameterNames() : []) . PHP_EOL;

// Test direct controller call
echo PHP_EOL . "=== DIRECT CONTROLLER TEST ===" . PHP_EOL;

$controller = new App\Http\Controllers\TopicController();

// Set up authentication context
$user = App\Models\User::find(8);
auth()->login($user);

echo "Authenticated User ID: " . auth()->id() . PHP_EOL;
echo "Authenticated User Role: " . auth()->user()->role . PHP_EOL;

// Test with courseId = 1
echo PHP_EOL . "Testing with courseId = 1:" . PHP_EOL;
try {
    $course1 = App\Models\Course::find(1);
    echo "Course 1 exists: " . ($course1 ? 'YES' : 'NO') . PHP_EOL;
    if ($course1) {
        echo "Course 1 Name: " . $course1->name . PHP_EOL;
        echo "Course 1 Type: " . $course1->type . PHP_EOL;
    }
    
    $course8 = App\Models\Course::find(8);
    echo "Course 8 exists: " . ($course8 ? 'YES' : 'NO') . PHP_EOL;
    if ($course8) {
        echo "Course 8 Name: " . $course8->name . PHP_EOL;
        echo "Course 8 Type: " . $course8->type . PHP_EOL;
    }
    
    // Test user access
    echo "User has access to Course 1: " . (auth()->user()->hasAccessToCourse(1) ? 'YES' : 'NO') . PHP_EOL;
    echo "User has access to Course 8: " . (auth()->user()->hasAccessToCourse(8) ? 'YES' : 'NO') . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== PARAMETER CONVERSION TEST ===" . PHP_EOL;
$testValues = ['1', 1, '8', 8];
foreach ($testValues as $value) {
    $converted = (int) $value;
    echo "Value: " . var_export($value, true) . " -> Converted: " . $converted . " (type: " . gettype($converted) . ")" . PHP_EOL;
}