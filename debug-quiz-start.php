<?php
// Debug script to test quiz start functionality
require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simulate a request to the source-progress endpoint
$request = Illuminate\Http\Request::create('/topics/1/source-progress?mode=normal', 'GET');
$request->headers->set('Accept', 'application/json');
$request->headers->set('Content-Type', 'application/json');

// Add authentication (you'll need to adjust this based on your auth setup)
// For testing, we'll simulate being logged in as user ID 1
$user = App\Models\User::find(1);
if ($user) {
    auth()->login($user);
    echo "Authenticated as user: " . $user->email . "\n";
} else {
    echo "No user found with ID 1\n";
    exit(1);
}

try {
    $response = $kernel->handle($request);
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}