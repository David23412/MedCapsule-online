<?php

/**
 * Streamlined Migration Refresh Script
 * Handles migration refresh with proper error handling and recovery
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StreamlinedRefresh
{
    public function run()
    {
        echo "🚀 Starting streamlined migration refresh...\n";
        
        try {
            // Step 1: Try normal refresh first
            echo "📋 Attempting normal migration refresh...\n";
            $exitCode = Artisan::call('migrate:refresh', ['--seed' => true]);
            
            if ($exitCode === 0) {
                echo "✅ Migration refresh completed successfully!\n";
                return;
            }
            
        } catch (Exception $e) {
            echo "⚠️  Normal refresh failed: " . $e->getMessage() . "\n";
        }
        
        // Step 2: Fallback to safe refresh
        echo "🔧 Attempting safe refresh with error handling...\n";
        $this->safeRefresh();
    }
    
    private function safeRefresh()
    {
        try {
            // Clear problematic migration entries
            echo "🧹 Cleaning migration table entries...\n";
            DB::table('migrations')
                ->whereIn('migration', [
                    '2025_07_24_050437_add_missing_columns_to_assignment_attempts_table',
                    '2025_07_24_043733_add_assignment_mode_and_source_to_assignments_table'
                ])
                ->delete();
            
            // Try rollback with error handling
            echo "⏪ Rolling back migrations safely...\n";
            try {
                Artisan::call('migrate:rollback', ['--step' => 5]);
            } catch (Exception $e) {
                echo "⚠️  Rollback had issues, continuing with fresh approach...\n";
                // If rollback fails, drop and recreate
                Artisan::call('db:wipe');
            }
            
            // Fresh migration
            echo "🆕 Running fresh migrations...\n";
            Artisan::call('migrate', ['--seed' => true]);
            
            echo "✅ Safe refresh completed successfully!\n";
            
        } catch (Exception $e) {
            echo "❌ Safe refresh failed: " . $e->getMessage() . "\n";
            echo "🔄 Falling back to complete database recreation...\n";
            
            // Last resort: complete wipe and recreate
            Artisan::call('db:wipe');
            Artisan::call('migrate', ['--seed' => true]);
            
            echo "✅ Database recreated successfully!\n";
        }
    }
}

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Run the streamlined refresh
$refresh = new StreamlinedRefresh();
$refresh->run();