<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PHASE 4: Final Performance Optimizations
     * - Timestamp indexes for audit queries  
     * - Full-text search indexes
     * - Additional composite indexes
     */
    public function up(): void
    {
        // 1. TIMESTAMP INDEXES FOR AUDIT QUERIES
        
        // Users table - timestamp indexes
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_active_at') && !DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_last_active_idx'")) {
                $table->index('last_active_at', 'users_last_active_idx');
            }
            if (Schema::hasColumn('users', 'email_verified_at') && !DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_email_verified_idx'")) {
                $table->index('email_verified_at', 'users_email_verified_idx');
            }
        });

        // Notifications table - read_at index for better performance
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'read_at') && !DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'notifications_read_at_performance_idx'")) {
                $table->index('read_at', 'notifications_read_at_performance_idx');
            }
        });

        // Payments table - paid_at index
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'paid_at') && !DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_paid_at_idx'")) {
                $table->index('paid_at', 'payments_paid_at_idx');
            }
        });

        // Course_user table - enrolled_at index  
        Schema::table('course_user', function (Blueprint $table) {
            if (Schema::hasColumn('course_user', 'enrolled_at') && !DB::select("SHOW INDEX FROM course_user WHERE Key_name = 'course_user_enrolled_at_idx'")) {
                $table->index('enrolled_at', 'course_user_enrolled_at_idx');
            }
        });

        // 2. FULL-TEXT SEARCH INDEXES
        
        // Courses table - full-text search on name and description
        Schema::table('courses', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM courses WHERE Key_name = 'courses_search_idx'")) {
                $table->fullText(['name', 'description'], 'courses_search_idx');
            }
        });

        // Questions table - full-text search on question content
        Schema::table('questions', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_search_idx'")) {
                $table->fullText(['question'], 'questions_search_idx');
            }
        });

        // 3. ADDITIONAL COMPOSITE INDEXES FOR COMPLEX QUERIES

        // Quiz attempts - user performance tracking
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_user_created_performance_idx'")) {
                $table->index(['user_id', 'created_at', 'percentage_grade'], 'quiz_attempts_user_created_performance_idx');
            }
        });

        // Mistakes - user question mastery tracking
        Schema::table('mistakes', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_user_mastery_idx'")) {
                $table->index(['user_id', 'mastered', 'created_at'], 'mistakes_user_mastery_idx');
            }
        });

        // User topic progress - completion tracking
        Schema::table('user_topic_progress', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM user_topic_progress WHERE Key_name = 'user_topic_progress_completion_idx'")) {
                $table->index(['user_id', 'percentage_grade', 'last_attempt_date'], 'user_topic_progress_completion_idx');
            }
        });

        // 4. COURSE ACCESS OPTIMIZATION
        Schema::table('course_user', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM course_user WHERE Key_name = 'course_user_access_optimization_idx'")) {
                $table->index(['user_id', 'course_id', 'enrollment_status'], 'course_user_access_optimization_idx');
            }
        });

        // 5. NOTIFICATION PERFORMANCE INDEX
        Schema::table('notifications', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'notifications_user_unread_idx'")) {
                $table->index(['user_id', 'is_read', 'created_at'], 'notifications_user_unread_idx');
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
            $table->dropIndex('notifications_user_unread_idx');
        });

        Schema::table('course_user', function (Blueprint $table) {
            $table->dropIndex('course_user_access_optimization_idx');
        });

        Schema::table('user_topic_progress', function (Blueprint $table) {
            $table->dropIndex('user_topic_progress_completion_idx');
        });

        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('mistakes_user_mastery_idx');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_user_created_performance_idx');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropFullText('questions_search_idx');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropFullText('courses_search_idx');
        });

        Schema::table('course_user', function (Blueprint $table) {
            $table->dropIndex('course_user_enrolled_at_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_paid_at_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_read_at_performance_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_verified_idx');
            $table->dropIndex('users_last_active_idx');
        });
    }
};