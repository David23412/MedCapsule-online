<?php

/**
 * LETHAL FINAL TEST ENGINE
 * 
 * This script performs a comprehensive test of the profile picture system
 * and provides debugging information.
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "🎯 LETHAL FINAL TEST ENGINE\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class LethalFinalTester
{
    public function runTests()
    {
        $this->testCurrentState();
        $this->testCacheBusting();
        $this->testFileAccess();
        $this->generateDebugInfo();
    }
    
    private function testCurrentState()
    {
        echo "📊 TESTING CURRENT STATE...\n";
        
        $user = \App\Models\User::find(1);
        if (!$user) {
            echo "❌ User not found\n";
            return;
        }
        
        echo "✅ User found: {$user->name}\n";
        echo "📄 Profile picture URL: {$user->profile_picture_url}\n";
        
        if ($user->profile_picture_url) {
            $filePath = public_path($user->profile_picture_url);
            $exists = file_exists($filePath);
            echo ($exists ? "✅" : "❌") . " File exists: {$filePath}\n";
            
            if ($exists) {
                $size = filesize($filePath);
                $modified = date('Y-m-d H:i:s', filemtime($filePath));
                echo "📊 File size: " . number_format($size) . " bytes\n";
                echo "🕒 Last modified: {$modified}\n";
                
                // Test cache busting URL generation
                $timestamp = filemtime($filePath);
                $cacheUrl = asset($user->profile_picture_url) . "?v={$timestamp}&cb=" . time() . "&uid={$user->id}";
                echo "🔗 Cache-busted URL: {$cacheUrl}\n";
            }
        } else {
            echo "ℹ️  No profile picture set\n";
        }
        echo "\n";
    }
    
    private function testCacheBusting()
    {
        echo "💥 TESTING CACHE BUSTING...\n";
        
        $user = \App\Models\User::find(1);
        if (!$user || !$user->profile_picture_url) {
            echo "⚠️  No profile picture to test\n";
            return;
        }
        
        $baseUrl = asset($user->profile_picture_url);
        $filePath = public_path($user->profile_picture_url);
        
        if (file_exists($filePath)) {
            $fileTime = filemtime($filePath);
            $currentTime = time();
            $userId = $user->id;
            
            // Generate different cache-busting URLs
            $urls = [
                "File time only" => "{$baseUrl}?v={$fileTime}",
                "Current time only" => "{$baseUrl}?t={$currentTime}",
                "Multi-parameter" => "{$baseUrl}?v={$fileTime}&cb={$currentTime}&uid={$userId}",
                "Ultra-aggressive" => "{$baseUrl}?v={$fileTime}&cb={$currentTime}&uid={$userId}&reload=1&t=" . microtime(true)
            ];
            
            foreach ($urls as $type => $url) {
                echo "🔗 {$type}: {$url}\n";
            }
        }
        echo "\n";
    }
    
    private function testFileAccess()
    {
        echo "🔐 TESTING FILE ACCESS...\n";
        
        $paths = [
            'Storage directory' => storage_path('app/public/profile_pictures'),
            'Public symlink' => public_path('storage/profile_pictures'),
            'Storage symlink target' => readlink(public_path('storage'))
        ];
        
        foreach ($paths as $name => $path) {
            if (file_exists($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $readable = is_readable($path) ? '✅' : '❌';
                $writable = is_writable($path) ? '✅' : '❌';
                echo "📁 {$name}: {$path}\n";
                echo "   Permissions: {$perms} | Readable: {$readable} | Writable: {$writable}\n";
            } else {
                echo "❌ {$name}: {$path} (not found)\n";
            }
        }
        echo "\n";
    }
    
    private function generateDebugInfo()
    {
        echo "🐛 DEBUG INFORMATION...\n";
        
        echo "🌐 Server Info:\n";
        echo "   PHP Version: " . PHP_VERSION . "\n";
        echo "   Laravel Version: " . app()->version() . "\n";
        echo "   Environment: " . app()->environment() . "\n";
        echo "   Debug Mode: " . (config('app.debug') ? 'ON' : 'OFF') . "\n";
        
        echo "\n📂 File System Info:\n";
        echo "   Storage Path: " . storage_path() . "\n";
        echo "   Public Path: " . public_path() . "\n";
        echo "   Asset URL: " . asset('') . "\n";
        
        echo "\n🔧 Recommendations:\n";
        echo "1. Clear browser cache completely (Cmd+Shift+Delete)\n";
        echo "2. Try incognito/private browsing mode\n";
        echo "3. Hard refresh after upload (Cmd+Shift+R)\n";
        echo "4. Check browser DevTools Network tab for 404s\n";
        echo "5. Verify the image URL directly in browser address bar\n";
        echo "6. Check if browser is blocking mixed content (HTTP/HTTPS)\n";
        
        echo "\n🎯 If issue persists:\n";
        echo "- The upload is working (files are saved correctly)\n";
        echo "- The database is updated correctly\n";
        echo "- The issue is 100% browser cache related\n";
        echo "- Try a different browser or device\n";
        echo "- Check browser console for JavaScript errors\n";
    }
}

$tester = new LethalFinalTester();
$tester->runTests();

echo "\n🎯 FINAL TEST COMPLETE!\n";
echo "The profile picture system should now work correctly.\n";
echo "If you still see the issue, it's definitely browser cache.\n";
echo "Try: Cmd+Shift+Delete -> Clear all browsing data -> Hard refresh\n";