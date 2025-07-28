<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ModelStressTestSeeder;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * LETHAL MODEL STRESS TESTING ENGINE COMMAND
 * 
 * Execute the most aggressive model stress testing ever created.
 * This command will push your Eloquent models to their absolute limits!
 */
class ModelStressTestEngine extends Command
{
    protected $signature = 'stress:models 
                           {--level=1000000 : Stress test intensity level}
                           {--models=* : Specific models to test (optional)}
                           {--skip-backup : Skip database backup}
                           {--force : Force execution without confirmation}';

    protected $description = '💀 Execute lethal stress testing on all Eloquent models';

    public function handle(): int
    {
        $this->displayBanner();
        
        if (!$this->option('force') && !$this->confirm('⚠️  This will execute EXTREME stress testing. Continue?')) {
            $this->error('❌ Stress testing aborted by user');
            return 1;
        }

        try {
            $this->info('🚀 INITIATING LETHAL MODEL STRESS TESTING ENGINE...');
            
            // Backup database if requested
            if (!$this->option('skip-backup')) {
                $this->backupDatabase();
            }
            
            // Execute the stress test seeder
            $seeder = new ModelStressTestSeeder();
            $seeder->setCommand($this);
            
            // Set stress level if provided
            if ($this->option('level')) {
                $seeder->setStressLevel((int) $this->option('level'));
            }
            
            // Set specific models if provided
            if ($this->option('models')) {
                $seeder->setSpecificModels($this->option('models'));
            }
            
            $seeder->run();
            
            $this->newLine();
            $this->info('✅ MODEL STRESS TESTING COMPLETED SUCCESSFULLY!');
            $this->info('📄 Check storage/logs/ for detailed reports');
            
            return 0;
            
        } catch (Exception $e) {
            $this->error('💥 CRITICAL FAILURE: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Display the lethal banner
     */
    private function displayBanner(): void
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════════╗');
        $this->line('║                                                              ║');
        $this->line('║               💀 LETHAL MODEL STRESS TESTER 💀               ║');
        $this->line('║                                                              ║');
        $this->line('║              "DESTROY MODELS TO MAKE THEM STRONGER"         ║');
        $this->line('║                                                              ║');
        $this->line('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * Backup database before stress testing
     */
    private function backupDatabase(): void
    {
        $this->info('💾 Creating database backup...');
        
        try {
            $backupFile = storage_path('backups/model_stress_test_backup_' . now()->format('Y-m-d_H-i-s') . '.sql');
            
            // Create backups directory if it doesn't exist
            if (!is_dir(dirname($backupFile))) {
                mkdir(dirname($backupFile), 0755, true);
            }
            
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            
            $command = "mysqldump -h {$host} -u {$username} -p{$password} {$database} > {$backupFile}";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->info("✅ Database backup created: {$backupFile}");
            } else {
                $this->warn("⚠️  Database backup failed, continuing without backup");
            }
            
        } catch (Exception $e) {
            $this->warn("⚠️  Database backup failed: " . $e->getMessage());
        }
    }
}