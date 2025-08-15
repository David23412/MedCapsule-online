<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Phase A: Add cached status column for assignments
     */
    public function up(): void
    {
        // Add cached status column for performance (optional optimization)
        if (!Schema::hasColumn('assignments', 'status_cached')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->enum('status_cached', ['upcoming', 'active', 'expired', 'overdue'])
                      ->nullable()
                      ->after('assigned_source')
                      ->comment('Cached computed status for performance');
                      
                $table->timestamp('status_cached_at')->nullable()->after('status_cached');
            });
        }
        
        // Add index for status filtering
        if (!$this->indexExists('assignments', 'idx_assignments_status_cached')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->index(['status_cached', 'deadline'], 'idx_assignments_status_cached');
            });
        }
        
        // Add trigger to invalidate cache when relevant fields change
        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS trg_assignments_invalidate_status_cache');
            DB::statement('
                CREATE TRIGGER trg_assignments_invalidate_status_cache 
                BEFORE UPDATE ON assignments
                FOR EACH ROW
                BEGIN
                    IF (OLD.deadline != NEW.deadline OR 
                        OLD.duration_minutes != NEW.duration_minutes OR 
                        OLD.created_at != NEW.created_at) THEN
                        SET NEW.status_cached = NULL;
                        SET NEW.status_cached_at = NULL;
                    END IF;
                END
            ');
        }
        
        // Add performance indexes for deadline and duration queries
        if (!$this->indexExists('assignments', 'idx_assignments_deadline_duration')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->index(['deadline', 'duration_minutes', 'created_at'], 'idx_assignments_deadline_duration');
            });
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Remove trigger
        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS trg_assignments_invalidate_status_cache');
        }
        
        // Remove indexes
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_status_cached');
            $table->dropIndex('idx_assignments_deadline_duration');
        });
        
        // Remove columns
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['status_cached', 'status_cached_at']);
        });
    }
    
    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};