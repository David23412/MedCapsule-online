<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * SSOT ENFORCEMENT: Clean up any inconsistent data and ensure SSOT compliance
     * This migration ensures all existing data follows the SSOT patterns
     */
    public function up(): void
    {
        // 1. ENROLLMENT SSOT: Ensure course_user table follows enrolled_at pattern
        if (Schema::hasColumn('course_user', 'is_enrolled')) {
            // Set enrolled_at for records where is_enrolled=true but enrolled_at is null
            DB::statement('
                UPDATE course_user 
                SET enrolled_at = COALESCE(enrolled_at, created_at, NOW()) 
                WHERE is_enrolled = 1 AND enrolled_at IS NULL
            ');
            
            // Clear enrolled_at for records where is_enrolled=false
            DB::statement('
                UPDATE course_user 
                SET enrolled_at = NULL 
                WHERE is_enrolled = 0
            ');
            
            // Drop the redundant is_enrolled column
            Schema::table('course_user', function (Blueprint $table) {
                $table->dropColumn('is_enrolled');
                $table->dropIndex(['user_id', 'is_enrolled']); // Remove old index
            });
            
            echo "✅ Removed is_enrolled column - using enrolled_at SSOT\n";
        }

        // 2. PAYMENT SSOT: Ensure payments table follows paid_at pattern
        if (Schema::hasColumn('payments', 'status')) {
            // Set paid_at for completed payments that don't have it
            DB::statement('
                UPDATE payments 
                SET paid_at = COALESCE(paid_at, updated_at, created_at) 
                WHERE status = "completed" AND paid_at IS NULL
            ');
            
            // Set failed_at for failed payments
            DB::statement('
                UPDATE payments 
                SET failed_at = COALESCE(failed_at, updated_at, created_at) 
                WHERE status IN ("failed", "cancelled", "rejected") AND failed_at IS NULL
            ');
            
            // Drop the redundant status column
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('status');
                $table->dropIndex('status'); // Remove old index
            });
            
            echo "✅ Removed status column - using paid_at/failed_at SSOT\n";
        }

        // 3. VIDEO PROGRESS SSOT: Ensure video_progress follows progress_percentage pattern
        if (Schema::hasColumn('video_progress', 'completed')) {
            // Set completed_at for videos that are marked completed
            DB::statement('
                UPDATE video_progress 
                SET completed_at = COALESCE(completed_at, updated_at, created_at) 
                WHERE completed = 1 AND completed_at IS NULL
            ');
            
            // Ensure progress_percentage is >= 90 for completed videos
            DB::statement('
                UPDATE video_progress 
                SET progress_percentage = GREATEST(progress_percentage, 90) 
                WHERE completed = 1 AND progress_percentage < 90
            ');
            
            // Drop the redundant completed column
            Schema::table('video_progress', function (Blueprint $table) {
                $table->dropColumn('completed');
                $table->dropIndex(['user_id', 'completed']); // Remove old index
            });
            
            echo "✅ Removed completed column - using progress_percentage SSOT\n";
        }

        // 4. Add performance indexes for SSOT queries
        $this->addSSoTIndexes();
        
        echo "🎉 SSOT enforcement complete - all split-brain patterns eliminated!\n";
    }

    /**
     * Add optimized indexes for SSOT queries
     */
    private function addSSoTIndexes(): void
    {
        // Enrollment SSOT indexes
        if (!$this->indexExists('course_user', 'idx_course_user_enrolled_ssot')) {
            Schema::table('course_user', function (Blueprint $table) {
                $table->index(['user_id', 'enrolled_at'], 'idx_course_user_enrolled_ssot');
                $table->index(['course_id', 'enrolled_at'], 'idx_course_enrolled_ssot');
            });
        }

        // Payment SSOT indexes  
        if (!$this->indexExists('payments', 'idx_payments_completion_ssot')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['user_id', 'paid_at'], 'idx_payments_completion_ssot');
                $table->index(['course_id', 'paid_at'], 'idx_payments_course_ssot');
                $table->index('failed_at', 'idx_payments_failed_ssot');
            });
        }

        // Video progress SSOT indexes
        if (Schema::hasTable('video_progress') && !$this->indexExists('video_progress', 'idx_video_progress_ssot')) {
            Schema::table('video_progress', function (Blueprint $table) {
                $table->index(['user_id', 'progress_percentage'], 'idx_video_progress_ssot');
                $table->index(['user_id', 'completed_at'], 'idx_video_completed_ssot');
            });
        }
    }

    /**
     * Reverse the migrations (NOT RECOMMENDED - data loss possible)
     */
    public function down(): void
    {
        // WARNING: This will cause data loss and recreate split-brain patterns
        // Only use for testing/development
        
        if (app()->environment(['local', 'testing'])) {
            // Re-add split-brain columns (NOT RECOMMENDED)
            if (!Schema::hasColumn('course_user', 'is_enrolled')) {
                Schema::table('course_user', function (Blueprint $table) {
                    $table->boolean('is_enrolled')->default(true);
                });
                
                // Populate from enrolled_at
                DB::statement('UPDATE course_user SET is_enrolled = (enrolled_at IS NOT NULL)');
            }

            if (!Schema::hasColumn('payments', 'status')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('status')->default('pending');
                });
                
                // Populate from timestamps
                DB::statement('
                    UPDATE payments 
                    SET status = CASE 
                        WHEN paid_at IS NOT NULL THEN "completed"
                        WHEN failed_at IS NOT NULL THEN "failed"
                        WHEN created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN "expired"
                        ELSE "pending"
                    END
                ');
            }

            if (Schema::hasTable('video_progress') && !Schema::hasColumn('video_progress', 'completed')) {
                Schema::table('video_progress', function (Blueprint $table) {
                    $table->boolean('completed')->default(false);
                });
                
                // Populate from progress_percentage
                DB::statement('UPDATE video_progress SET completed = (progress_percentage >= 90)');
            }
        } else {
            throw new \Exception('SSOT rollback not allowed in production - would recreate split-brain patterns!');
        }
    }
    
    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};