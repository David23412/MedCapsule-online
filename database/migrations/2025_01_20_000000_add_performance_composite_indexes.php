<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * OPTIMIZATION: Add composite indexes for common query patterns
     */
    public function up(): void
    {
        // Questions table - composite indexes for quiz queries
        Schema::table('questions', function (Blueprint $table) {
            // Composite index for topic + source + type filtering
            if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'idx_topic_source_type'")) {
                $table->index(['topic_id', 'source', 'question_type'], 'idx_topic_source_type');
            }
            
            // Covering index for question retrieval
            if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'idx_topic_deleted_covering'")) {
                $table->index(['topic_id', 'id'], 'idx_topic_deleted_covering');
            }
        });

        // Quiz attempts table - composite indexes for user performance
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Composite index for user + topic + date queries
            if (!DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'idx_user_topic_date'")) {
                $table->index(['user_id', 'topic_id', 'created_at'], 'idx_user_topic_date');
            }
            
            // Index for performance analysis
            if (!DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'idx_user_percentage_grade'")) {
                $table->index(['user_id', 'percentage_grade'], 'idx_user_percentage_grade');
            }
        });

        // User topic progress table - composite indexes
        Schema::table('user_topic_progress', function (Blueprint $table) {
            // Composite index for user progress queries
            if (!DB::select("SHOW INDEX FROM user_topic_progress WHERE Key_name = 'idx_user_topic_grade'")) {
                $table->index(['user_id', 'topic_id', 'percentage_grade'], 'idx_user_topic_grade');
            }
        });

        // Payments table - composite indexes for payment queries
        // Commented out until payments table is created
        /*
        Schema::table('payments', function (Blueprint $table) {
            // Composite index for user payment status queries
            if (!DB::select("SHOW INDEX FROM payments WHERE Key_name = 'idx_user_status_date'")) {
                $table->index(['user_id', 'status', 'created_at'], 'idx_user_status_date');
            }
            
            // Index for payment method analysis
            if (!DB::select("SHOW INDEX FROM payments WHERE Key_name = 'idx_status_method'")) {
                $table->index(['status', 'payment_method'], 'idx_status_method');
            }
        });
        */

        // Topics table - composite indexes for course queries
        Schema::table('topics', function (Blueprint $table) {
            // Composite index for course + order queries
            if (!DB::select("SHOW INDEX FROM topics WHERE Key_name = 'idx_course_order_covering'")) {
                $table->index(['course_id', 'display_order', 'id', 'name'], 'idx_course_order_covering');
            }
        });

        // Mistakes table - composite indexes for review queries
        Schema::table('mistakes', function (Blueprint $table) {
            // Composite index for user mistake analysis
            if (!DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'idx_user_question_mastered'")) {
                $table->index(['user_id', 'question_id', 'mastered'], 'idx_user_question_mastered');
            }
            
            // Index for streak analysis
            if (!DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'idx_user_streak'")) {
                $table->index(['user_id', 'correct_streak'], 'idx_user_streak');
            }
        });

        // Notifications table - composite indexes
        Schema::table('notifications', function (Blueprint $table) {
            // Composite index for user notification queries
            if (!DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'idx_user_read'")) {
                $table->index(['user_id', 'read_at'], 'idx_user_read');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_user_read');
        });

        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('idx_user_streak');
            $table->dropIndex('idx_user_question_mastered');
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex('idx_course_order_covering');
        });

        // Schema::table('payments', function (Blueprint $table) {
        //     $table->dropIndex('idx_status_method');
        //     $table->dropIndex('idx_user_status_date');
        // });

        Schema::table('user_topic_progress', function (Blueprint $table) {
            $table->dropIndex('idx_user_topic_grade');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_user_percentage_grade');
            $table->dropIndex('idx_user_topic_date');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_topic_deleted_covering');
            $table->dropIndex('idx_topic_source_type');
        });
    }
}; 