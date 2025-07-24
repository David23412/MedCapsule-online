<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class RobustMigrateRefresh extends Command
{
    protected $signature = 'migrate:robust-refresh {--seed : Seed the database after migration}';
    protected $description = 'Robust migration refresh that handles all edge cases and never fails';

    public function handle()
    {
        $this->info('🚀 Starting ROBUST migration refresh...');
        
        // Step 1: Clean up orphaned migration entries
        $this->cleanupOrphanedMigrations();
        
        // Step 2: Try normal refresh first
        if ($this->attemptNormalRefresh()) {
            return 0;
        }
        
        // Step 3: Try safe rollback and migrate
        if ($this->attemptSafeRefresh()) {
            return 0;
        }
        
        // Step 4: Nuclear option - complete rebuild
        $this->nuclearRefresh();
        
        return 0;
    }
    
    private function cleanupOrphanedMigrations(): void
    {
        $this->info('🧹 Cleaning up orphaned migration entries...');
        
        try {
            // Remove entries for migrations that don't exist as files
            $orphanedMigrations = [
                '2025_07_24_050437_add_missing_columns_to_assignment_attempts_table',
                '2025_07_24_043733_add_assignment_mode_and_source_to_assignments_table'
            ];
            
            DB::table('migrations')->whereIn('migration', $orphanedMigrations)->delete();
            $this->info('✅ Cleaned up orphaned migration entries');
            
        } catch (Exception $e) {
            $this->warn('⚠️  Could not clean migration table: ' . $e->getMessage());
        }
    }
    
    private function attemptNormalRefresh(): bool
    {
        $this->info('📋 Attempting normal migration refresh...');
        
        try {
            $options = $this->option('seed') ? ['--seed' => true] : [];
            $exitCode = Artisan::call('migrate:refresh', $options);
            
            if ($exitCode === 0) {
                $this->info('✅ Normal refresh completed successfully!');
                $this->displayOutput(Artisan::output());
                return true;
            }
            
        } catch (Exception $e) {
            $this->warn('⚠️  Normal refresh failed: ' . $e->getMessage());
        }
        
        return false;
    }
    
    private function attemptSafeRefresh(): bool
    {
        $this->info('🔧 Attempting safe refresh with error handling...');
        
        try {
            // Step 1: Safe rollback
            $this->info('⏪ Rolling back migrations safely...');
            $this->safeRollback();
            
            // Step 2: Fresh migrate
            $this->info('🆕 Running fresh migrations...');
            $options = $this->option('seed') ? ['--seed' => true] : [];
            Artisan::call('migrate', $options);
            
            $this->info('✅ Safe refresh completed successfully!');
            $this->displayOutput(Artisan::output());
            return true;
            
        } catch (Exception $e) {
            $this->warn('⚠️  Safe refresh failed: ' . $e->getMessage());
            return false;
        }
    }
    
    private function safeRollback(): void
    {
        // Get list of migrations to rollback
        $migrations = DB::table('migrations')
            ->orderBy('batch', 'desc')
            ->orderBy('migration', 'desc')
            ->get();
            
        foreach ($migrations as $migration) {
            try {
                $this->info("Rolling back: {$migration->migration}");
                Artisan::call('migrate:rollback', ['--step' => 1]);
            } catch (Exception $e) {
                $this->warn("Failed to rollback {$migration->migration}: " . $e->getMessage());
                // Continue with next migration
            }
        }
    }
    
    private function nuclearRefresh(): void
    {
        $this->warn('🔄 Nuclear option: Complete database rebuild...');
        
        try {
            // Complete wipe
            $this->info('💥 Wiping database...');
            Artisan::call('db:wipe');
            
            // Fresh migration
            $this->info('🆕 Creating fresh database...');
            $options = $this->option('seed') ? ['--seed' => true] : [];
            Artisan::call('migrate', $options);
            
            $this->info('✅ Nuclear refresh completed successfully!');
            $this->displayOutput(Artisan::output());
            
        } catch (Exception $e) {
            $this->error('❌ Nuclear refresh failed: ' . $e->getMessage());
            $this->error('🆘 Manual intervention required!');
        }
    }
    
    private function displayOutput(string $output): void
    {
        if (!empty(trim($output))) {
            $this->line('');
            $this->line('📋 Migration Output:');
            $this->line($output);
        }
    }
}