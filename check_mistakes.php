<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking database for recently recorded mistakes...\n";

$m = \App\Models\Mistake::where('user_id', 1)->orderBy('created_at', 'desc')->limit(10)->get();

echo 'Found ' . $m->count() . ' mistakes for user 1' . "\n";

foreach($m as $mistake) {
    echo 'ID: ' . $mistake->id . ', Question: ' . $mistake->question_id . ', Answer: ' . $mistake->submitted_answer . ', Created: ' . $mistake->created_at . "\n";
}

echo "\nChecking total mistakes count...\n";
$total = \App\Models\Mistake::where('user_id', 1)->count();
echo 'Total mistakes for user 1: ' . $total . "\n";

echo "\nChecking mistakes from last 5 minutes...\n";
$recent = \App\Models\Mistake::where('user_id', 1)
    ->where('created_at', '>=', now()->subMinutes(5))
    ->count();
echo 'Recent mistakes (last 5 min): ' . $recent . "\n";
