<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Phase A: Setup for enrollment SSoT
     */
    public function up(): void
    {
        // Ensure enrolled_at column exists in course_user table
        if (!Schema::hasColumn('course_user', 'enrolled_at')) {
            Schema::table('course_user', function (Blueprint $table) {
                $table->timestamp('enrolled_at')->nullable()->after('is_enrolled');
            });
        }
        
        // Add soft constraint during Phase A (allows both representations)
        // This prevents is_enrolled=1 when enrolled_at IS NULL
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('
                    ALTER TABLE course_user 
                    ADD CONSTRAINT chk_enrollment_consistency_phase_a 
                    CHECK (is_enrolled = 0 OR enrolled_at IS NOT NULL)
                ');
            } catch (\Exception $e) {
                // If CHECK constraints not supported, create trigger
                DB::statement('DROP TRIGGER IF EXISTS trg_course_user_enrollment_check');
                DB::statement('
                    CREATE TRIGGER trg_course_user_enrollment_check 
                    BEFORE INSERT ON course_user
                    FOR EACH ROW
                    BEGIN
                        IF NEW.is_enrolled = 1 AND NEW.enrolled_at IS NULL THEN
                            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Cannot set is_enrolled=1 without enrolled_at timestamp";
                        END IF;
                    END
                ');
                
                DB::statement('
                    CREATE TRIGGER trg_course_user_enrollment_check_update 
                    BEFORE UPDATE ON course_user
                    FOR EACH ROW
                    BEGIN
                        IF NEW.is_enrolled = 1 AND NEW.enrolled_at IS NULL THEN
                            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Cannot set is_enrolled=1 without enrolled_at timestamp";
                        END IF;
                    END
                ');
            }
        }
        
        // Add index for performance on new SSoT queries
        if (!$this->indexExists('course_user', 'idx_course_user_enrolled_at')) {
            Schema::table('course_user', function (Blueprint $table) {
                $table->index(['user_id', 'course_id', 'enrolled_at'], 'idx_course_user_enrolled_at');
            });
        }
        
        // Add index for payment status queries
        if (!$this->indexExists('payments', 'idx_payments_user_course_status')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['user_id', 'course_id', 'status'], 'idx_payments_user_course_status');
            });
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Remove constraints and triggers
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE course_user DROP CONSTRAINT chk_enrollment_consistency_phase_a');
            } catch (\Exception $e) {
                // Remove triggers if constraints don't exist
                DB::statement('DROP TRIGGER IF EXISTS trg_course_user_enrollment_check');
                DB::statement('DROP TRIGGER IF EXISTS trg_course_user_enrollment_check_update');
            }
        }
        
        // Remove indexes
        Schema::table('course_user', function (Blueprint $table) {
            $table->dropIndex('idx_course_user_enrolled_at');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_user_course_status');
        });
        
        // Note: We don't drop enrolled_at column in rollback as it may contain data
        // Manual intervention required if full rollback is needed
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