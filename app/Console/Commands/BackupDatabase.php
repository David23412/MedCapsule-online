<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Backup the database to a JSON file';

    public function handle()
    {
        $this->info('Starting database backup...');

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $backup = [];

        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            
            // Skip migrations table
            if ($tableName === 'migrations') {
                continue;
            }

            $this->info("Backing up table: {$tableName}");
            
            // Get all records from the table
            $records = DB::table($tableName)->get();
            $backup[$tableName] = $records;
        }

        // Create backup directory if it doesn't exist
        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Generate filename with timestamp
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$timestamp}.json";
        $filepath = $backupDir . '/' . $filename;

        // Save backup to file
        file_put_contents($filepath, json_encode($backup, JSON_PRETTY_PRINT));
        
        $this->info("Backup completed successfully!");
        $this->info("Backup saved to: {$filepath}");
    }
} 