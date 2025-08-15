<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Phase A: Add constraints for progress tracking
     */
    public function up(): void
    {
        // Add constraint to ensure progress_percentage is within valid range
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('
                    ALTER TABLE video_progress 
                    ADD CONSTRAINT chk_progress_percentage_range 
                    CHECK (progress_percentage >= 0 AND progress_percentage <= 100)
                ');
            } catch (\Exception $e) {
                // If CHECK constraints not supported, create trigger
                DB::statement('DROP TRIGGER IF EXISTS trg_video_progress_check');
                DB::statement('
                    CREATE TRIGGER trg_video_progress_check 
                    BEFORE INSERT ON video_progress
                    FOR EACH ROW
                    BEGIN
                        IF NEW.progress_percentage < 0 OR NEW.progress_percentage > 100 THEN
                            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "progress_percentage must be between 0 and 100";
                        END IF;
                    END
                ');
                
                DB::statement('
                    CREATE TRIGGER trg_video_progress_check_update 
                    BEFORE UPDATE ON video_progress
                    FOR EACH ROW
                    BEGIN
                        IF NEW.progress_percentage < 0 OR NEW.progress_percentage > 100 THEN
                            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "progress_percentage must be between 0 and 100";
                        END IF;
                    END
                ');
            }
        }
        
        // Add soft constraint for video completion consistency during Phase A
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('
                    ALTER TABLE video_progress 
                    ADD CONSTRAINT chk_completion_consistency_phase_a 
                    CHECK (completed = 0 OR progress_percentage >= 100)
                ');
            } catch (\Exception $e) {
                // Create trigger if CHECK not supported
                DB::statement('DROP TRIGGER IF EXISTS trg_video_completion_consistency');
                DB::statement('
                    CREATE TRIGGER trg_video_completion_consistency 
                    BEFORE INSERT ON video_progress
                    FOR EACH ROW
                    BEGIN
                        IF NEW.completed = 1 AND NEW.progress_percentage < 100 THEN
                            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Cannot mark as completed with progress < 100%";
                        END IF;
                    END
                ');
                
                DB::statement('
                    CREATE TRIGGER trg_video_completion_consistency_update 
                    BEFORE UPDATE ON video_progress
                    FOR EACH ROW
                    BEGIN
                        IF NEW.completed = 1 AND NEW.progress_percentage < 100 THEN
                            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Cannot mark as completed with progress < 100%";
                        END IF;
                    END
                ');
            }
        }
        
        // Add indexes for performance
        if (!$this->indexExists('video_progress', 'idx_video_progress_completion')) {
            Schema::table('video_progress', function (Blueprint $table) {
                $table->index(['user_id', 'progress_percentage'], 'idx_video_progress_completion');
            });
        }
        
        // Add constraint for mistake streak consistency
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('
                    ALTER TABLE mistakes 
                    ADD CONSTRAINT chk_correct_streak_positive 
                    CHECK (correct_streak >= 0)
                ');
            } catch (\Exception $e) {
                // Create trigger if CHECK not supported
                DB::statement('DROP TRIGGER IF EXISTS trg_mistakes_streak_check');
                DB::statement('
                    CREATE TRIGGER trg_mistakes_streak_check 
                    BEFORE INSERT ON mistakes
                    FOR EACH ROW
                    BEGIN
                        IF NEW.correct_streak < 0 THEN
                            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "correct_streak cannot be negative";
                        END IF;
                    END
                ');
                
                DB::statement('
                    CREATE TRIGGER trg_mistakes_streak_check_update 
                    BEFORE UPDATE ON mistakes
                    FOR EACH ROW
                    BEGIN
                        IF NEW.correct_streak < 0 THEN
                            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "correct_streak cannot be negative";
                        END IF;
                    END
                ');
            }
        }
        
        // Add index for mistake queries by streak
        if (!$this->indexExists('mistakes', 'idx_mistakes_user_streak')) {
            Schema::table('mistakes', function (Blueprint $table) {
                $table->index(['user_id', 'correct_streak', 'skipped'], 'idx_mistakes_user_streak');
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
                DB::statement('ALTER TABLE video_progress DROP CONSTRAINT chk_progress_percentage_range');
                DB::statement('ALTER TABLE video_progress DROP CONSTRAINT chk_completion_consistency_phase_a');
                DB::statement('ALTER TABLE mistakes DROP CONSTRAINT chk_correct_streak_positive');
            } catch (\Exception $e) {
                // Remove triggers if constraints don't exist
                DB::statement('DROP TRIGGER IF EXISTS trg_video_progress_check');
                DB::statement('DROP TRIGGER IF EXISTS trg_video_progress_check_update');
                DB::statement('DROP TRIGGER IF EXISTS trg_video_completion_consistency');
                DB::statement('DROP TRIGGER IF EXISTS trg_video_completion_consistency_update');
                DB::statement('DROP TRIGGER IF EXISTS trg_mistakes_streak_check');
                DB::statement('DROP TRIGGER IF EXISTS trg_mistakes_streak_check_update');
            }
        }
        
        // Remove indexes
        Schema::table('video_progress', function (Blueprint $table) {
            $table->dropIndex('idx_video_progress_completion');
        });
        
        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('idx_mistakes_user_streak');
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