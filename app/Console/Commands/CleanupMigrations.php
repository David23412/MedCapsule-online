<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupMigrations extends Command
{
    protected $signature = 'migrate:cleanup';
    protected $description = 'Clean up missing migration references from database';

    public function handle()
    {
        $this->info('🧹 Cleaning up missing migration references...');

        // List of missing migrations to remove from database
        $missingMigrations = [
            '2025_08_18_210100_add_integer_enums_to_users',
            '2025_08_18_210000_add_enrollment_ssot_to_course_user', 
            '2025_08_18_200000_cleanup_video_and_pivot_tables',
            '9999_12_31_999998_add_foreign_key_constraints',
            '2025_05_17_013126_create_medical_domains_table'
        ];

        try {
            // Check if migrations table exists
            if (!Schema::hasTable('migrations')) {
                $this->error('❌ Migrations table doesn\'t exist. Run \'php artisan migrate:install\' first.');
                return 1;
            }

            $cleaned = 0;
            // Remove missing migration references
            foreach ($missingMigrations as $migration) {
                $deleted = DB::table('migrations')->where('migration', $migration)->delete();
                if ($deleted > 0) {
                    $this->line("✅ Removed reference to: {$migration}");
                    $cleaned++;
                } else {
                    $this->line("ℹ️  Not found in database: {$migration}");
                }
            }

            if ($cleaned > 0) {
                $this->info("\n🎯 Cleaned up {$cleaned} missing migration references!");
            } else {
                $this->info("\n✨ No cleanup needed - database is clean!");
            }

            $this->info("Now you can run: php artisan migrate:refresh");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}