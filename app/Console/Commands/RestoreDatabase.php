<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RestoreDatabase extends Command
{
    protected $signature = 'db:restore {filename}';
    protected $description = 'Restore the database from a backup file';

    public function handle()
    {
        $filename = $this->argument('filename');
        $backupDir = storage_path('app/backups');
        $filepath = $backupDir . '/' . $filename;

        if (!file_exists($filepath)) {
            $this->error("Backup file not found: {$filepath}");
            return 1;
        }

        $this->info('Starting database restore...');

        // Read backup file
        $backup = json_decode(file_get_contents($filepath), true);

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($backup as $tableName => $records) {
            $this->info("Restoring table: {$tableName}");
            
            // Clear existing records
            DB::table($tableName)->truncate();
            
            // Insert backup records
            foreach ($records as $record) {
                DB::table($tableName)->insert((array) $record);
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->info('Database restore completed successfully!');
    }
} 