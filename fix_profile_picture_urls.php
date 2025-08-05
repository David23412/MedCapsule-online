<?php

/**
 * Profile Picture URL Fixer
 * This script helps identify and fix any hardcoded ngrok URLs in profile picture paths
 */

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "🔍 Checking profile picture URLs...\n\n";

// Get all users with profile pictures
$users = User::whereNotNull('profile_picture_url')->get();

echo "Found " . $users->count() . " users with profile pictures\n\n";

$needsFixing = [];

foreach ($users as $user) {
    $url = $user->profile_picture_url;
    
    // Check if URL contains hardcoded ngrok domains
    if (strpos($url, 'ngrok-free.app') !== false || strpos($url, 'ngrok.io') !== false) {
        $needsFixing[] = [
            'user' => $user,
            'current_url' => $url
        ];
        
        echo "❌ User ID {$user->id} ({$user->name}): {$url}\n";
    } else {
        echo "✅ User ID {$user->id} ({$user->name}): {$url}\n";
    }
}

if (empty($needsFixing)) {
    echo "\n🎉 All profile picture URLs are correctly formatted!\n";
    exit;
}

echo "\n⚠️  Found " . count($needsFixing) . " users with hardcoded ngrok URLs\n";
echo "Would you like to fix them? (y/n): ";

$handle = fopen("php://stdin", "r");
$response = trim(fgets($handle));
fclose($handle);

if (strtolower($response) !== 'y') {
    echo "Operation cancelled.\n";
    exit;
}

echo "\n🔧 Fixing URLs...\n";

foreach ($needsFixing as $item) {
    $user = $item['user'];
    $currentUrl = $item['current_url'];
    
    // Extract just the storage path part
    if (preg_match('/storage\/profile_pictures\/(.+)$/', $currentUrl, $matches)) {
        $newUrl = 'storage/profile_pictures/' . $matches[1];
        
        $user->profile_picture_url = $newUrl;
        $user->save();
        
        echo "✅ Fixed User ID {$user->id}: {$currentUrl} → {$newUrl}\n";
    } else {
        echo "⚠️  Could not parse URL for User ID {$user->id}: {$currentUrl}\n";
    }
}

echo "\n🎉 Profile picture URL fixing complete!\n";
echo "The blade templates will now use asset() helper to generate correct URLs.\n";